<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Taxonomy extends Core\Base
{

	public static function object( $taxonomy )
	{
		if ( ! $taxonomy )
			return $taxonomy;

		return is_object( $taxonomy ) ? $taxonomy : get_taxonomy( $taxonomy );
	}

	public static function viewable( $taxonomy )
	{
		if ( ! $taxonomy )
			return $taxonomy;

		return is_taxonomy_viewable( $taxonomy );
	}

	/**
	 * Determines whether the taxonomy object is hierarchical.
	 * Also accepts taxonomy object.
	 *
	 * @source `is_taxonomy_hierarchical()`
	 *
	 * @param  string|object $taxonomy
	 * @return bool $hierarchical
	 */
	public static function hierarchical( $taxonomy )
	{
		if ( $object = self::object( $taxonomy ) )
			return $object->hierarchical;

		return FALSE;
	}

	// @REF: `is_term_publicly_viewable()` @since WP6.1.0
	public static function viewableTerm( $term )
	{
		$term = get_term( $term );

		if ( ! $term || is_wp_error( $term ) )
			return FALSE;

		return self::viewable( $term->taxonomy );
	}

	public static function can( $taxonomy, $capability = 'manage_terms', $user_id = NULL )
	{
		if ( is_null( $capability ) )
			return TRUE;

		$cap = self::object( $taxonomy )->cap->{$capability};

		return is_null( $user_id )
			? current_user_can( $cap )
			: user_can( $user_id, $cap );
	}

	/**
	 * Retrieves the list of taxonomies.
	 *
	 * Parameter $args is an array of key -> value arguments to match against
	 * the taxonomies. Only taxonomies having attributes that match all
	 * arguments are returned:
	 * name
	 * object_type (array)
	 * label
	 * singular_label
	 * show_ui
	 * show_tagcloud
	 * show_in_rest
	 * public
	 * update_count_callback
	 * rewrite
	 * query_var
	 * manage_cap
	 * edit_cap
	 * delete_cap
	 * assign_cap
	 * _builtin
	 *
	 * @param  int    $mod
	 * @param  array  $args
	 * @param  bool   $object
	 * @param  null|string $capability
	 * @param  null|int $user_id
	 * @return array $list
	 */
	public static function get( $mod = 0, $args = [], $object = FALSE, $capability = NULL, $user_id = NULL )
	{
		$list = [];

		if ( FALSE === $object || 'any' == $object )
			$objects = get_taxonomies( $args, 'objects' );
		else
			$objects = get_object_taxonomies( $object, 'objects' );

		foreach ( $objects as $taxonomy => $taxonomy_obj ) {

			if ( ! self::can( $taxonomy_obj, $capability, $user_id ) )
				continue;

			// just the name!
			if ( -1 === $mod )
				$list[] = $taxonomy_obj->name;

			// label
			else if ( 0 === $mod )
				$list[$taxonomy] = $taxonomy_obj->label ? $taxonomy_obj->label : $taxonomy_obj->name;

			// plural
			else if ( 1 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->name;

			// singular
			else if ( 2 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->singular_name;

			// nooped
			else if ( 3 === $mod )
				$list[$taxonomy] = [
					0          => $taxonomy_obj->labels->singular_name,
					1          => $taxonomy_obj->labels->name,
					'singular' => $taxonomy_obj->labels->singular_name,
					'plural'   => $taxonomy_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				];

			// object
			else if ( 4 === $mod )
				$list[$taxonomy] = $taxonomy_obj;

			// with object_type
			else if ( 5 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->name.Core\HTML::joined( $taxonomy_obj->object_type, ' [', ']' );

			// with name
			else if ( 6 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->menu_name.' ('.$taxonomy_obj->name.')';
		}

		return $list;
	}

	public static function getDefaultTermID( $taxonomy, $fallback = FALSE )
	{
		return get_option( self::getDefaultTermOptionKey( $taxonomy ), $fallback );
	}

	public static function getDefaultTermOptionKey( $taxonomy )
	{
		if ( 'category' == $taxonomy )
			return 'default_category'; // WordPress

		if ( 'product_cat' == $taxonomy )
			return 'default_product_cat'; // WooCommerce

		return 'default_term_'.$taxonomy;
	}

	// @REF: `wp_count_terms()`
	public static function hasTerms( $taxonomy, $object_id = FALSE, $empty = TRUE )
	{
		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => ! $empty,

			'fields'  => 'count',
			'orderby' => 'none',

			'suppress_filter'        => TRUE,
			'update_term_meta_cache' => FALSE,
		];

		if ( $object_id )
			$args['object_ids'] = (array) $object_id;

		$query = new \WP_Term_Query();
		return $query->query( $args );
	}

	// @REF: `wp_update_term_count_now()`
	// NOTE: without taxonomy
	public static function updateTermCount( $term_ids )
	{
		$list = [];

		foreach ( self::getTermTaxonomies( $term_ids ) as $term_id => $taxonomy ) {

			if ( ! $object = self::object( $taxonomy ) )
				continue;

			if ( ! $callback = self::updateCountCallback( $object ) )
				continue;

			call_user_func( $callback, [ $term_id ], $object );

			$list[] = $term_id;
		}

		clean_term_cache( $list, '', FALSE );

		return count( $list );
	}

	public static function getTermTaxonomies( $term_ids )
	{
		global $wpdb;

		if ( empty( $term_ids ) )
			return [];

		$list = $wpdb->get_results( "
			SELECT term_id, taxonomy
			FROM {$wpdb->term_taxonomy}
			WHERE term_id IN ( ".implode( ", ", esc_sql( $term_ids ) )." )
		", ARRAY_A );

		return count( $list ) ? Core\Arraay::pluck( $list, 'taxonomy', 'term_id' ) : [];
	}

	public static function updateCountCallback( $taxonomy )
	{
		static $callbacks = [];

		if ( ! $object = self::object( $taxonomy ) )
			return FALSE;

		if ( ! empty( $callbacks[$object->name] ) )
			return $callbacks[$object->name];

		if ( ! empty( $object->update_count_callback ) ) {

			$callback = $object->update_count_callback;

		} else {

			$types = (array) $object->object_type;

			foreach ( $types as &$type )
				if ( Core\Text::starts( $type, 'attachment:' ) )
					list( $type ) = explode( ':', $type );

			if ( array_filter( $types, 'post_type_exists' ) == $types )
				// Only post types are attached to this taxonomy.
				$callback = '_update_post_term_count';

			else
				// Default count updater.
				$callback = '_update_generic_term_count';
		}

		return $callbacks[$object->name] = $callback;
	}

	// @REF: `get_the_term_list()`
	public static function getTheTermList( $taxonomy, $post = NULL, $before = '', $after = '' )
	{
		if ( ! $terms = self::getPostTerms( $taxonomy, $post ) )
			return [];

		$list = [];

		foreach ( $terms as $term )
			$list[] = $before.Core\HTML::tag( 'a', [
				'href'  => get_term_link( $term, $taxonomy ),
				'class' => '-term',
			], sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy, 'display' ) ).$after;

		return apply_filters( 'term_links-'.$taxonomy, $list );
	}

	public static function getTermParents( $term_id, $taxonomy )
	{
		static $data = [];

		if ( isset( $data[$taxonomy][$term_id] ) )
			return $data[$taxonomy][$term_id];

		$current = $term_id;
		$parents = [];
		$up      = TRUE;

		while ( $up ) {

			$term = get_term( (int) $current, $taxonomy );

			if ( $term->parent )
				$parents[] = (int) $term->parent;

			else
				$up = FALSE;

			$current = $term->parent;
		}

		return $data[$taxonomy][$term_id] = $parents;
	}

	// TODO: must suport different parents
	public static function getTargetTerm( $target, $taxonomy, $args = [], $meta = [] )
	{
		$target = trim( $target );

		if ( is_numeric( $target ) ) {

			if ( $term = term_exists( (int) $target, $taxonomy ) )
				return get_term( $term['term_id'], $taxonomy );

			else
				return FALSE; // avoid inserting numbers as new terms!

		} else if ( $term = term_exists( $target, $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( apply_filters( 'string_format_i18n', $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( Core\Text::nameFamilyFirst( $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( Core\Text::nameFamilyLast( $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );
		}

		// avoid filtering the new term
		$term = wp_insert_term( $target, $taxonomy, $args );

		if ( self::isError( $term ) )
			return FALSE;

		foreach ( $meta as $meta_key => $meta_value )
			add_term_meta( $term['term_id'], $meta_key, $meta_value, TRUE );

		return get_term( $term['term_id'], $taxonomy );
	}

	public static function getObjectTerms( $taxonomy, $object_id, $fields = 'ids', $extra = [] )
	{
		$args = array_merge( [
			'taxonomy'   => $taxonomy,
			'object_ids' => $object_id,
			'hide_empty' => FALSE,

			'fields'  => $fields,
			'orderby' => 'none',

			'suppress_filter'        => TRUE,
			'update_term_meta_cache' => FALSE,
		], $extra );

		$query = new \WP_Term_Query();
		return $query->query( $args );
	}

	/**
	 * Determines whether a taxonomy term exists.
	 * Formerly is_term(), introduced in 2.3.0.
	 *
	 * @SEE: https://make.wordpress.org/core/2022/04/28/taxonomy-performance-improvements-in-wordpress-6-0/
	 * @SOURCE: OLD VERSION OF `term_exists()`
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int|string $term     The term to check. Accepts term ID, slug, or name.
	 * @param string     $taxonomy Optional. The taxonomy name to use.
	 * @param int        $parent   Optional. ID of parent term under which to confine the exists search.
	 * @return mixed Returns null if the term does not exist.
	 *               Returns the term ID if no taxonomy is specified and the term ID exists.
	 *               Returns an array of the term ID and the term taxonomy ID if the taxonomy is specified and the pairing exists.
	 *               Returns 0 if term ID 0 is passed to the function.
	 */
	public static function termExists( $term, $taxonomy = '', $parent = NULL )
	{
		global $wpdb;

		if ( NULL === $term )
			return NULL;

		$select     = "SELECT term_id FROM $wpdb->terms as t WHERE ";
		$tax_select = "SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_id = t.term_id WHERE ";

		if ( is_int( $term ) ) {
			if ( 0 === $term ) {
				return 0;
			}
			$where = 't.term_id = %d';
			if ( ! empty( $taxonomy ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
				return $wpdb->get_row( $wpdb->prepare( $tax_select . $where . ' AND tt.taxonomy = %s', $term, $taxonomy ), ARRAY_A );
			} else {
				return $wpdb->get_var( $wpdb->prepare( $select . $where, $term ) );
			}
		}

		$term = trim( wp_unslash( $term ) );
		$slug = sanitize_title( $term );

		$where             = 't.slug = %s';
		$else_where        = 't.name = %s';
		$where_fields      = array( $slug );
		$else_where_fields = array( $term );
		$orderby           = 'ORDER BY t.term_id ASC';
		$limit             = 'LIMIT 1';
		if ( ! empty( $taxonomy ) ) {
			if ( is_numeric( $parent ) ) {
				$parent              = (int) $parent;
				$where_fields[]      = $parent;
				$else_where_fields[] = $parent;
				$where              .= ' AND tt.parent = %d';
				$else_where         .= ' AND tt.parent = %d';
			}

			$where_fields[]      = $taxonomy;
			$else_where_fields[] = $taxonomy;

			$result = $wpdb->get_row( $wpdb->prepare( "SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_id = t.term_id WHERE $where AND tt.taxonomy = %s $orderby $limit", $where_fields ), ARRAY_A );
			if ( $result ) {
				return $result;
			}

			return $wpdb->get_row( $wpdb->prepare( "SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_id = t.term_id WHERE $else_where AND tt.taxonomy = %s $orderby $limit", $else_where_fields ), ARRAY_A );
		}

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$result = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms as t WHERE $where $orderby $limit", $where_fields ) );
		if ( $result ) {
			return $result;
		}

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms as t WHERE $else_where $orderby $limit", $else_where_fields ) );
	}

	/**
	 * Inserts set of terms into a taxonomy.
	 *
	 * `$update_terms` accepts: `not_name_desc`, `not_name`
	 *
	 * @param  string|object $taxonomy
	 * @param  array $terms
	 * @param  bool|string $update_terms
	 * @param  int $force_parent
	 * @return array $count
	 */
	public static function insertDefaultTerms( $taxonomy, $terms, $update_terms = TRUE, $force_parent = 0 )
	{
		if ( ! $object = self::object( $taxonomy ) )
			return FALSE;

		$count = [];

		foreach ( $terms as $slug => $term ) {

			$name   = $term;
			$meta   = $children = [];
			$args   = [ 'slug' => $slug, 'name' => $term ];
			$update = $update_terms;

			if ( is_array( $term ) ) {

				if ( ! empty( $term['name'] ) )
					$name = $args['name'] = $term['name'];
				else
					$name = $slug;

				if ( ! empty( $term['description'] ) )
					$args['description'] = $term['description'];

				if ( ! empty( $term['slug'] ) )
					$args['slug'] = $term['slug'];

				if ( ! empty( $term['children'] ) )
					$children = $term['children'];

				if ( $force_parent ) {

					if ( is_numeric( $force_parent ) )
						$args['parent'] = $force_parent;

					else if ( $parent = term_exists( $force_parent, $object->name ) )
						$args['parent'] = $parent['term_id'];

				} else if ( ! empty( $term['parent'] ) ) {


					if ( is_numeric( $term['parent'] ) )
						$args['parent'] = $term['parent'];

					else if ( $parent = term_exists( $term['parent'], $object->name ) )
						$args['parent'] = $parent['term_id'];
				}

				if ( ! empty( $term['meta'] ) && is_array( $term['meta'] ) )
					foreach ( $term['meta'] as $term_meta_key => $term_meta_value )
						$meta[$term_meta_key] = $term_meta_value;

				if ( array_key_exists( 'update', $term ) )
					$update = $term['update'];
			}

			if ( $existed = term_exists( $args['slug'], $object->name ) ) {

				if ( 'not_name_desc' === $update )
					wp_update_term( $existed['term_id'], $object->name,
						Core\Arraay::stripByKeys( $args, [ 'name', 'description' ] ) );

				else if ( 'not_name' === $update )
					wp_update_term( $existed['term_id'], $object->name,
						Core\Arraay::stripByKeys( $args, [ 'name' ] ) );

				else if ( $update )
					wp_update_term( $existed['term_id'], $object->name, $args );

			} else {

				$existed = wp_insert_term( $name, $object->name, $args );
			}

			if ( ! is_wp_error( $existed ) ) {

				foreach ( $meta as $meta_key => $meta_value ) {

					if ( $update )
						update_term_meta( $existed['term_id'], $meta_key, $meta_value );
					else
						// will bail if an entry with the same key is found
						add_term_meta( $existed['term_id'], $meta_key, $meta_value, TRUE );
				}

				if ( count( $children ) )
					self::insertDefaultTerms( $object->name, $children, $update_terms, $existed['term_id'] );

				$count[] = $existed;
			}
		}

		return $count;
	}

	// `get_objects_in_term()` without cache updating
	// @SOURCE: `wp_delete_term()`
	public static function getTermObjects( $term_taxonomy_id, $taxonomy )
	{
		global $wpdb;

		if ( empty( $term_taxonomy_id ) )
			return [];

		$query = $wpdb->prepare( "
			SELECT object_id
			FROM {$wpdb->term_relationships}
			WHERE term_taxonomy_id = %d
		", $term_taxonomy_id );

		$objects = $wpdb->get_col( $query );

		return $objects ? (array) $objects : [];
	}

	// @SOURCE: `wp_remove_object_terms()`
	public static function removeTermObjects( $term, $taxonomy )
	{
		global $wpdb;

		if ( ! $exists = term_exists( $term, $taxonomy ) )
			return FALSE;

		$tt_id = $exists['term_taxonomy_id'];
		$count = 0;

		foreach ( self::getTermObjects( $tt_id, $taxonomy ) as $object_id ) {

			do_action( 'delete_term_relationships', $object_id, $tt_id, $taxonomy );

			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->term_relationships}
				WHERE object_id = %d
				AND term_taxonomy_id = %d
			", $object_id, $tt_id );

			if ( $wpdb->query( $query ) )
				$count++;

			wp_cache_delete( $object_id, $taxonomy.'_relationships' );
			do_action( 'deleted_term_relationships', $object_id, $tt_id, $taxonomy );
		}

		wp_cache_delete( 'last_changed', 'terms' );
		wp_update_term_count( $tt_id, $taxonomy );

		return $count;
	}

	// @SOURCE: `wp_set_object_terms()`
	public static function setTermObjects( $objects, $term, $taxonomy )
	{
		global $wpdb;

		if ( ! $exists = term_exists( $term, $taxonomy ) )
			return FALSE;

		$tt_id = $exists['term_taxonomy_id'];
		$count = 0;

		foreach ( $objects as $object_id ) {

			$query = $wpdb->prepare( "
				SELECT term_taxonomy_id
				FROM {$wpdb->term_relationships}
				WHERE object_id = %d
				AND term_taxonomy_id = %d
			", $object_id, $tt_id );

			// already inserted
			if ( $wpdb->get_var( $query ) )
				continue;

			do_action( 'add_term_relationship', $object_id, $tt_id, $taxonomy );

			$wpdb->insert( $wpdb->term_relationships, [
				'object_id'        => $object_id,
				'term_taxonomy_id' => $tt_id,
			] );

			wp_cache_delete( $object_id, $taxonomy.'_relationships' );
			do_action( 'added_term_relationship', $object_id, $tt_id, $taxonomy );

			$count++;
		}

		wp_cache_delete( 'last_changed', 'terms' );
		wp_update_term_count( $tt_id, $taxonomy );

		return $count;
	}

	// @REF: `_update_post_term_count()`
	public static function countTermObjects( $term, $taxonomy )
	{
		global $wpdb;

		if ( ! $exists = term_exists( $term, $taxonomy ) )
			return FALSE;

		$query = $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$wpdb->term_relationships}
			WHERE term_taxonomy_id = %d
		", $exists['term_taxonomy_id'] );

		return $wpdb->get_var( $query );
	}

	/**
	 * Retrieves children of taxonomy as term IDs.
	 * without option save and accepts taxonomy object.
	 *
	 * @source `_get_term_hierarchy()`
	 *
	 * @param  string|object $taxonomy
	 * @return array $children
	 */
	public static function getHierarchy( $taxonomy )
	{
		if ( ! self::hierarchical( $taxonomy ) )
			return [];

		$children = [];
		$terms    = get_terms( [
			'taxonomy'   => self::object( $taxonomy )->name,
			'get'        => 'all',
			'orderby'    => 'id',
			'fields'     => 'id=>parent',
			'hide_empty' => FALSE, // FIXME: WTF?!

			'update_term_meta_cache' => FALSE,
		] );

		foreach ( $terms as $term_id => $parent )
			if ( $parent > 0 )
				$children[$parent][] = $term_id;

		return $children;
	}

	public static function getEmptyTermIDs( $taxonomy, $check_description = FALSE, $max = 0, $min = 0 )
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT t.term_id
			FROM {$wpdb->terms} AS t
			INNER JOIN {$wpdb->term_taxonomy} AS tt
			ON t.term_id = tt.term_id
			WHERE tt.taxonomy IN ( '".implode( "', '", esc_sql( (array) $taxonomy ) )."' )
			AND tt.count < %d
			AND tt.count > %d
		", ( ( (int) $max ) + 1 ), ( ( (int) $min ) - 1 ) );

		if ( $check_description )
			$query.= " AND (TRIM(COALESCE(tt.description, '')) = '') ";

		return $wpdb->get_col( $query );
	}

	/**
	 * Retrieves terms with no children.
	 *
	 * @param  string|object $taxonomy
	 * @param  array $extra
	 * @return array $list
	 */
	public static function listChildLessTerms( $taxonomy, $fields = NULL, $extra = [] )
	{
		if ( ! $object = self::object( $taxonomy ) )
			return FALSE;

		$args = array_merge( [
			'taxonomy'   => $object->name,
			'hide_empty' => FALSE,

			'fields'  => is_null( $fields ) ? 'id=>name' : $fields,
			'orderby' => 'none',

			'suppress_filter'        => TRUE,
			'update_term_meta_cache' => FALSE,
		], $extra );

		if ( $hierarchy = self::getHierarchy( $object ) )
			$args['exclude'] = implode( ', ', array_keys( $hierarchy ) );

		$query = new \WP_Term_Query();
		return $query->query( $args );
	}

	// NOTE: hits cached terms for the post
	public static function getPostTerms( $taxonomy, $post = NULL, $object = TRUE, $key = FALSE, $index_key = NULL )
	{
		$terms = get_the_terms( $post, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) )
			return [];

		if ( ! $object )
			return Core\Arraay::pluck( $terms, $key ?: 'term_id', $index_key );

		if ( $key )
			return Core\Arraay::reKey( $terms, $key );

		return $terms;
	}

	// @REF: https://developer.wordpress.org/?p=22286
	public static function listTerms( $taxonomy, $fields = NULL, $extra = [] )
	{
		$query = new \WP_Term_Query( array_merge( [
			'taxonomy'   => (array) $taxonomy,
			'order'      => 'ASC',
			'orderby'    => 'meta_value_num', // 'name',
			'meta_query' => [
				// @REF: https://core.trac.wordpress.org/ticket/34996
				'relation' => 'OR',
				[
					'key'     => 'order',
					'compare' => 'NOT EXISTS'
				],
				[
					'key'     => 'order',
					'compare' => '>=',
					'value'   => 0,
				],
			],
			'fields'     => is_null( $fields ) ? 'id=>name' : $fields,
			'hide_empty' => FALSE,
		], $extra ) );

		if ( empty( $query->terms ) )
			return [];

		return $query->terms;
	}

	public static function getTerm( $term_or_id, $taxonomy = '' )
	{
		if ( $term_or_id instanceof \WP_Term )
			return $term_or_id;

		if ( ! $term_or_id ) {

			if ( is_admin() ) {

				if ( is_null( $term_or_id ) && ( $query = self::req( 'tag_ID' ) ) )
					return self::get( (int) $query, $taxonomy );

				return FALSE;
			}

			if ( 'category' == $taxonomy && ! is_category() )
				return FALSE;

			if ( 'post_tag' == $taxonomy && ! is_tag() )
				return FALSE;

			if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ) )
				&& ! is_tax( $taxonomy ) )
					return FALSE;

			if ( ! $term_or_id = get_queried_object_id() )
				return FALSE;
		}

		if ( is_numeric( $term_or_id ) )
			// $term = get_term_by( 'id', $term_or_id, $taxonomy );
			$term = get_term( (int) $term_or_id, $taxonomy ); // allows for empty taxonomy

		else if ( $taxonomy )
			$term = get_term_by( 'slug', $term_or_id, $taxonomy );

		else
			$term = get_term( $term_or_id, $taxonomy ); // allows for empty taxonomy

		if ( ! $term || is_wp_error( $term ) )
			return FALSE;

		return $term;
	}

	/**
	 * retrieves meta-data for a given term.
	 *
	 * @param  object|int $term
	 * @param  bool|array $keys `false` for all meta
	 * @param  bool $single
	 * @return array
	 */
	public static function getTermMeta( $term, $keys = FALSE, $single = TRUE )
	{
		if ( ! $term = self::getTerm( $term ) )
			return FALSE;

		$list = [];

		if ( FALSE === $keys ) {

			if ( $single ) {

				foreach ( (array) get_term_meta( $term->term_id ) as $key => $meta )
					$list[$key] = maybe_unserialize( $meta[0] );

			} else {

				foreach ( (array) get_term_meta( $term->term_id ) as $key => $meta )
					foreach ( $meta as $offset => $value )
						$list[$key][$offset] = maybe_unserialize( $value );
			}

		} else {

			foreach ( $keys as $key => $default )
				$list[$key] = get_term_meta( $term->term_id, $key, $single ) ?: $default;
		}

		return $list;
	}

	public static function getArchiveLink( $taxonomy )
	{
		return apply_filters( 'gnetwork_taxonomy_archive_link', FALSE, $taxonomy );
	}

	public static function disableTermCounting()
	{
		wp_defer_term_counting( TRUE );

		// also avoids query for post terms
		remove_action( 'transition_post_status', '_update_term_count_on_transition_post_status', 10 );

		// WooCommerce
		add_filter( 'woocommerce_product_recount_terms', '__return_false' );
	}
}

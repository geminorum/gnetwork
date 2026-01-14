<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Taxonomy extends Core\Base
{

	const NAME_INPUT_PATTERN = '[-a-zA-Z0-9_]{3,32}';

	public static function object( $taxonomy_or_term )
	{
		if ( ! $taxonomy_or_term )
			return FALSE;

		if ( $taxonomy_or_term instanceof \WP_Term )
			return get_taxonomy( $taxonomy_or_term->taxonomy );

		if ( $taxonomy_or_term instanceof \WP_Taxonomy )
			return $taxonomy_or_term;

		return get_taxonomy( $taxonomy_or_term );
	}

	/**
	 * Determines whether a taxonomy is registered.
	 * @source: `taxonomy_exists()`
	 *
	 * @param string|object $taxonomy_or_term
	 * @return bool
	 */
	public static function exists( $taxonomy_or_term )
	{
		return (bool) self::object( $taxonomy_or_term );
	}

	/**
	 * Determines whether a taxonomy is considered “viewable”.
	 *
	 * @param string|object $taxonomy
	 * @return bool
	 */
	public static function viewable( $taxonomy )
	{
		if ( ! $taxonomy )
			return $taxonomy;

		return is_taxonomy_viewable( $taxonomy );
	}

	public static function queryVar( $taxonomy )
	{
		if ( ! $object = self::object( $taxonomy ) )
			return FALSE;

		return empty( $object->query_var )
			? $object->name
			: $object->query_var;
	}

	public static function types( $taxonomy )
	{
		if ( ! $object = self::object( $taxonomy ) )
			return [];

		return (array) $object->object_type;
	}

	/**
	 * Determines whether the taxonomy object is hierarchical.
	 * Also accepts taxonomy object.
	 *
	 * @source `is_taxonomy_hierarchical()`
	 *
	 * @param string|object $taxonomy
	 * @return bool
	 */
	public static function hierarchical( $taxonomy )
	{
		if ( $object = self::object( $taxonomy ) )
			return $object->hierarchical;

		return FALSE;
	}

	/**
	 * Checks for taxonomy capability.
	 * NOTE: caches the results
	 *
	 * @param string|object $taxonomy
	 * @param null|string $capability
	 * @param null|int|object $user_id
	 * @param bool $fallback
	 * @return bool
	 */
	public static function can( $taxonomy, $capability = 'manage_terms', $user_id = NULL, $fallback = FALSE )
	{
		static $cache = [];

		if ( is_null( $capability ) )
			return TRUE;

		else if ( ! $capability )
			return $fallback;

		if ( ! $object = self::object( $taxonomy ) )
			return $fallback;

		if ( ! isset( $object->cap->{$capability} ) )
			return $fallback;

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		else if ( is_object( $user_id ) )
			$user_id = $user_id->ID;

		if ( ! $user_id )
			return user_can( $user_id, $object->cap->{$capability} );

		if ( isset( $cache[$user_id][$object->name][$capability] ) )
			return $cache[$user_id][$object->name][$capability];

		$can = user_can( $user_id, $object->cap->{$capability} );

		return $cache[$user_id][$object->name][$capability] = $can;
	}

	/**
	 * Retrieves the capability assigned to the taxonomy.
	 *
	 * @param string|object $taxonomy
	 * @param string $capability
	 * @param string $fallback
	 * @return string
	 */
	public static function cap( $taxonomy, $capability = 'manage_terms', $fallback = NULL )
	{
		if ( is_null( $capability ) )
			return TRUE;

		else if ( ! $capability )
			return $fallback;

		if ( ! $object = self::object( $taxonomy ) )
			return $fallback;

		if ( isset( $object->cap->{$capability} ) )
			return $object->cap->{$capability};

		return $fallback ?? $object->cap->manage_terms; // WTF?!
	}

	/**
	 * Retrieves the list of taxonomies.
	 *
	 * Parameter `$args` is an array of key -> value arguments to match against
	 * the taxonomies. Only taxonomies having attributes that match all
	 * arguments are returned:
	 * `name`
	 * `object_type` (array)
	 * `label`
	 * `singular_label`
	 * `show_ui`
	 * `show_tagcloud`
	 * `show_in_rest`
	 * `public`
	 * `update_count_callback`
	 * `rewrite`
	 * `query_var`
	 * `manage_cap`
	 * `edit_cap`
	 * `delete_cap`
	 * `assign_cap`
	 * `_builtin`
	 *
	 * @param int $mod
	 * @param array $args
	 * @param bool $object
	 * @param null|string $capability
	 * @param null|int $user_id
	 * @return array
	 */
	public static function get( $mod = 0, $args = [], $object = FALSE, $capability = NULL, $user_id = NULL )
	{
		$list = [];

		if ( FALSE === $object || 'any' == $object )
			$objects = get_taxonomies( $args, 'objects' );
		else
			$objects = Core\Arraay::filter( get_object_taxonomies( $object, 'objects' ), $args );

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
				$list[$taxonomy] = $taxonomy_obj->labels->name.Core\HTML::joined( (array) $taxonomy_obj->object_type, ' [', ']' );

			// with name
			else if ( 6 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->menu_name.' ('.$taxonomy_obj->name.')';

			// list of object types
			else if ( 7 === $mod )
				$list[$taxonomy] = (array) $taxonomy_obj->object_type;
		}

		return $list;
	}

	/**
	 * Retrieves taxonomy archive link.
	 *
	 * @param string|object $taxonomy
	 * @param mixed $fallback
	 * @return string
	 */
	public static function link( $taxonomy, $fallback = NULL )
	{
		if ( ! $object = self::object( $taxonomy ) )
			return $fallback;

		return apply_filters( 'geditorial_taxonomy_archive_link', $fallback, $object->name );
	}

	/**
	 * Retrieves the URL for editing a given taxonomy.
	 * @old `WordPress::getEditTaxLink()`
	 *
	 * @param string|object $taxonomy
	 * @param array $extra
	 * @param mixed $fallback
	 * @return string
	 */
	public static function edit( $taxonomy, $extra = [], $fallback = FALSE )
	{
		if ( ! $object = self::object( $taxonomy ) )
			return $fallback;

		if ( ! self::can( $object, 'manage_terms' ) )
			return $fallback;

		return add_query_arg( array_merge( [
			'taxonomy' => $object->name,
		], $extra ), admin_url( 'edit-tags.php' ) );
	}

	public static function getDefaultTermID( $taxonomy, $fallback = FALSE )
	{
		return get_option( self::getDefaultTermOptionKey( $taxonomy ), $fallback );
	}

	public static function getDefaultTermOptionKey( $taxonomy )
	{
		if ( 'category' == $taxonomy )
			return 'default_category'; // WordPress

		if ( $taxonomy == WooCommerce::PROCUCT_CATEGORY && WooCommerce::isActive() )
			return 'default_product_cat'; // WooCommerce

		return 'default_term_'.$taxonomy;
	}

	/**
	 * Counts posts with no terms assigned given taxonomy and post-types.
	 *
	 * @also: `Database::countPostsByNotTaxonomy()`
	 * @ref: `https://core.trac.wordpress.org/ticket/29181`
	 *
	 * @param string $taxonomy
	 * @param string|array $posttypes
	 * @param string|object $extra_term
	 * @param null|false|array $exclude_statuses
	 * @return int
	 */
	public static function countPostsWithoutTerms( $taxonomy, $posttypes, $extra_term = FALSE, $exclude_statuses = FALSE )
	{
		if ( ! $taxonomy || empty( $posttypes ) )
			return 0;

		$args = [
			'orderby'        => 'none',
			'fields'         => 'ids',
			'post_status'    => Status::acceptable( $posttypes ),
			'post_type'      => $posttypes,
			'posts_per_page' => -1,

			'tax_query' => [ [
				'taxonomy' => $taxonomy,
				'operator' => 'NOT EXISTS',
			] ],

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		if ( is_null( $exclude_statuses ) || $exclude_statuses )
			$args['post_status'] = Status::acceptable( $posttypes, 'counts', Database::getExcludeStatuses( $exclude_statuses ) );

		if ( $term = Term::get( $extra_term ) ) {
			$args['tax_query']['relation'] = 'AND';
			$args['tax_query'][] = [
				'taxonomy' => $term->taxonomy,
				'terms'    => $term->term_id,
				'field'    => 'term_id',
			];
		}

		$query = new \WP_Query();
		$posts = $query->query( $args );

		return $posts ? count( $posts ) : 0;
	}

	// NOTE: results are compatible with `WordPress\Database::countPostsByTaxonomy()`
	// -> `$counts[$term_slug][$posttype] = $term_count;`
	public static function countPostsDoubleTerms( $the_term, $second_taxonomy, $posttypes, $exclude_statuses = NULL )
	{
		$counts = [];
		$totals = array_fill_keys( $posttypes, 0 );

		if ( ! $the_term = Term::get( $the_term ) )
			return $counts;

		$terms = is_array( $second_taxonomy )
			? $second_taxonomy
			: get_terms( [ 'taxonomy' => $second_taxonomy ] );

		foreach ( $terms as $term ) {

			$counts[$term->slug] = $totals;

			foreach ( $posttypes as $posttype ) {

				$args = [
					'orderby'        => 'none',
					'fields'         => 'ids',
					'post_status'    => Status::acceptable( $posttype, 'counts', Database::getExcludeStatuses( $exclude_statuses ) ),
					'post_type'      => $posttype,
					'posts_per_page' => -1,

					'tax_query' => [
						'relation' => 'AND',
						[
							'taxonomy' => $the_term->taxonomy,
							'terms'    => $the_term->term_id,
							'field'    => 'term_id',
						],
						[
							'taxonomy' => $term->taxonomy,
							'terms'    => $term->term_id,
							'field'    => 'term_id',
						]
					],

					'no_found_rows'          => TRUE,
					'suppress_filters'       => TRUE,
					'update_post_meta_cache' => FALSE,
					'update_post_term_cache' => FALSE,
					'lazy_load_term_meta'    => FALSE,
				];

				$query = new \WP_Query();
				$posts = $query->query( $args );

				$counts[$term->slug][$posttype] = $posts ? count( $posts ) : 0;
			}
		}

		return $counts;
	}

	// @REF: `wp_count_terms()`
	public static function hasTerms( $taxonomy, $object_id = FALSE, $empty = TRUE, $extra = [] )
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
		return $query->query( array_merge( $args, $extra ) );
	}

	// NOTE: DEPRECATED: use `Term::taxonomy()`
	public static function getTermTaxonomy( $term_or_id, $fallback = FALSE )
	{
		return Term::taxonomy( $term_or_id ) ?: $fallback;
	}

	// NOTE: DEPRECATED: use `Term::get()`
	public static function getTerm( $term_or_id, $taxonomy = '' )
	{
		return Term::get( $term_or_id, $taxonomy );
	}

	public static function getTheTermRows( $taxonomy, $post = NULL )
	{
		if ( ! $terms = self::getPostTerms( $taxonomy, $post ) )
			return '';

		$rows = [];

		foreach ( $terms as $term )
			$rows[] = Core\HTML::row( Core\HTML::tag( 'a', [
				'href'  => get_term_link( $term, $taxonomy ),
				'class' => '-term',
			], sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy, 'display' ) ) );

		return Core\HTML::rows( $rows, '-rows-'.$taxonomy, [ 'taxonomy' => $taxonomy ] );
	}

	// @REF: `get_the_term_list()`
	public static function getTheTermList( $taxonomy, $post = NULL, $before = '', $after = '' )
	{
		if ( ! $terms = self::getPostTerms( $taxonomy, $post ) )
			return [];

		$list = [];

		foreach ( $terms as $term )
			$list[] = $before.Term::htmlLink( $term ).$after;

		return apply_filters( 'term_links-'.$taxonomy, $list );
	}

	// FIXME: rewrite this!
	public static function getTerms( $taxonomy, $object_id = FALSE, $object = FALSE, $key = 'term_id', $extra = [], $post_object = TRUE )
	{
		if ( FALSE === $object_id ) {

			$id = FALSE;

		} else if ( $post_object && ( $post = get_post( $object_id ) ) ) {

			$id = $post->ID;

		} else {

			$id = (int) $object_id;
		}

		if ( $id ) {

			// NOTE: use `Taxonomy::getPostTerms()` instead!

			// Using cached terms, only for posts, when no extra arguments provided
			// @REF: https://developer.wordpress.org/reference/functions/wp_get_object_terms/#comment-1582
			$terms = $post_object && empty( $extra )
				? get_the_terms( $id, $taxonomy )
				: wp_get_object_terms( $id, $taxonomy, $extra );

		} else {

			// TODO: use WP_Term_Query directly

			$terms = get_terms( array_merge( [
				'taxonomy'   => $taxonomy,
				'hide_empty' => FALSE,
				// 'order'      => 'ASC',
				'order'      => 'DESC',
				// 'orderby'    => 'meta_value_num',
				'orderby'    => 'order_clause',
				'meta_query' => [
					// @REF: https://core.trac.wordpress.org/ticket/34996
					// @SEE: https://wordpress.stackexchange.com/a/246206
					// @SEE: https://wordpress.stackexchange.com/a/277755
					'relation' => 'OR',
					'order_clause' => [
						'key'     => 'order',
						// 'value'   => 0,
						// 'compare' => '>=',
						'type'    => 'NUMERIC'
					],
					[
						'key'     => 'order',
						'compare' => 'NOT EXISTS'
					],
				],
				'update_term_meta_cache' => FALSE,
			], $extra ) );
		}

		if ( ! $terms || is_wp_error( $terms ) )
			return [];

		$list = Core\Arraay::pluck( $terms, $key );

		return $object ? array_combine( $list, $terms ) : $list;
	}

	public static function prepTerms( $taxonomy, $extra = [], $terms = NULL, $key = 'term_id', $object = TRUE )
	{
		$new_terms = [];

		if ( is_null( $terms ) ) {
			$terms = get_terms( array_merge( [
				'taxonomy'               => $taxonomy,
				'hide_empty'             => FALSE,
				'orderby'                => 'name',
				'order'                  => 'ASC',
				'update_term_meta_cache' => FALSE,
			], $extra ) );
		}

		if ( is_wp_error( $terms ) || FALSE === $terms )
			return $new_terms;

		foreach ( $terms as $term ) {

			/**
			 * WP_Term Object
			 * (
			 *     [term_id] =>
			 *     [name] =>
			 *     [slug] =>
			 *     [term_group] =>
			 *     [term_taxonomy_id] =>
			 *     [taxonomy] =>
			 *     [description] =>
			 *     [parent] =>
			 *     [count] =>
			 *     [filter] =>
			 * )
			 */
			$new = [
				'name'        => $term->name,
				// 'name'        => sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
				'description' => $term->description,
				'link'        => get_term_link( $term ),
				'count'       => $term->count,
				'parent'      => $term->parent,
				'slug'        => $term->slug,
				'id'          => $term->term_id,
			];

			$new_terms[$term->{$key}] = $object ? (object) $new : $new;
		}

		return $new_terms;
	}

	/**
	 * Tries to re-order list of terms given meta-key or order list.
	 *
	 * @param array $terms
	 * @param string|array $reference
	 * @param string $fields
	 * @return array
	 */
	public static function reorderTermsByMeta( $terms, $reference = 'order', $fields = 'all' )
	{
		if ( empty( $terms ) || count( $terms ) === 1 || 'count' === $fields )
			return $terms;

		$type = 'object';
		$prop = '_order';
		$list = [];

		if ( in_array( $fields, [ 'ids', 'tt_ids' ], TRUE ) )
			$type = 'array';

		else if ( Core\Text::starts( $fields, 'id=>' ) )
			$type = 'assoc';

		foreach ( $terms as $index => $data ) {

			if ( 'array' == $type )
				$term_id = $data;

			else if ( 'assoc' == $type )
				$term_id = $index;

			else if ( isset( $data->term_id ) )
				$term_id = $data->term_id;

			else
				continue;

			if ( is_array( $reference ) )
				$order = isset( $reference[$term_id] ) ? intval( $reference[$term_id] ) : 0;

			else if ( $meta = get_term_meta( $term_id, $reference, TRUE ) )
				$order = (int) $meta;

			else
				$order = 0;

			if ( 'array' == $type ) {

				$list[] = [
					'term_id' => $data,
					$prop     => $order,
				];

			} else if ( 'assoc' == $type ) {

				$list[] = [
					'term_id' => $index,
					'data'    => $data,
					$prop     => $order,
				];

			} else if ( 'object' == $type ) {

				$data->{$prop} = $order;
				$list[] = $data;
			}
		}

		// Bailing if cannot determine the term ids
		if ( empty( $list ) )
			return $terms;

		if ( 'array' == $type )
			return array_column( Core\Arraay::sortByPriority( $list, $prop ), 'term_id' );

		if ( 'assoc' == $type )
			return array_column( Core\Arraay::sortByPriority( $list, $prop ), 'data', 'term_id' );

		return Core\Arraay::sortObjectByPriority( $list, $prop );
	}

	// EXPERIMENTAL: parsing: 'category:12,11|post_tag:3|people:58'
	public static function parseTerms( $string )
	{
		if ( empty( $string ) || ! $string )
			return FALSE;

		$taxonomies = [];

		foreach ( explode( '|', $string ) as $taxonomy ) {

			list( $tax, $terms ) = explode( ':', $taxonomy );

			$terms = explode( ',', $terms );
			$terms = array_map( 'intval', $terms );

			$taxonomies[$tax] = array_unique( $terms );
		}

		return $taxonomies;
	}

	// NOTE: hits cached terms for the post
	public static function theTerm( $taxonomy, $post = NULL, $object = FALSE )
	{
		$terms = get_the_terms( $post, $taxonomy );

		if ( $terms && ! is_wp_error( $terms ) )
			foreach ( $terms as $term )
				return $object ? $term : $term->term_id;

		return '0';
	}

	// NOTE: hits cached terms for the post
	public static function theTermCount( $taxonomy, $post = NULL )
	{
		if ( ! empty( $taxonomy ) )
			return 0;

		$terms = get_the_terms( $post, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) )
			return 0;

		return count( $terms );
	}

	// NOTE: DEPRECATED: use `Term::add()`
	public static function addTerm( $term, $taxonomy, $sanitize = TRUE )
	{
		return Term::add( $term, $taxonomy, $sanitize );
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

	public static function getIDbyMeta( $key, $value, $single = TRUE )
	{
		global $wpdb, $gEditorialTermIDbyMeta;

		if ( empty( $key ) || empty( $value ) )
			return FALSE;

		if ( empty( $gEditorialTermIDbyMeta ) )
			$gEditorialTermIDbyMeta = [];

		$group = $single ? 'single' : 'all';

		if ( isset( $gEditorialTermIDbyMeta[$key][$group][$value] ) )
			return $gEditorialTermIDbyMeta[$key][$group][$value];

		$query = $wpdb->prepare( "
			SELECT term_id
			FROM {$wpdb->termmeta}
			WHERE meta_key = %s
			AND meta_value = %s
		", $key, $value );

		$results = $single
			? $wpdb->get_var( $query )
			: $wpdb->get_col( $query );

		return $gEditorialTermIDbyMeta[$key][$group][$value] = $results;
	}

	public static function invalidateIDbyMeta( $meta, $value = FALSE )
	{
		global $gEditorialTermIDbyMeta;

		if ( empty( $meta ) )
			return TRUE;

		if ( empty( $gEditorialTermIDbyMeta ) )
			return TRUE;

		if ( FALSE === $value ) {

			// clear all meta by key
			foreach ( (array) $meta as $key ) {
				unset( $gEditorialTermIDbyMeta[$key]['all'] );
				unset( $gEditorialTermIDbyMeta[$key]['single'] );
			}

		} else {

			foreach ( (array) $meta as $key ) {
				unset( $gEditorialTermIDbyMeta[$key]['all'][$value] );
				unset( $gEditorialTermIDbyMeta[$key]['single'][$value] );
			}
		}

		return TRUE;
	}

	/**
	 * Retrieves the list of parents by term ID.
	 *
	 * @param array $terms
	 * @return array
	 */
	public static function getParentsList( $terms )
	{
		$list = [];

		foreach ( (array) $terms as $data )
			if ( $term = Term::get( $data ) )
				$list[$term->term_id] = $term->parent;

		return array_filter( $list );
	}

	public static function appendParentTermIDs( $term_ids, $taxonomy )
	{
		if ( ! self::object( $taxonomy )->hierarchical )
			return $term_ids;

		$terms = [];

		foreach ( (array) $term_ids as $term_id )
			$terms = array_merge( $terms, self::getTermParents( $term_id, $taxonomy ) );

		return Core\Arraay::prepNumeral( $term_ids, $terms );
	}

	public static function getTermParents( $term_id, $taxonomy )
	{
		static $data = [];

		if ( isset( $data[$taxonomy][$term_id] ) )
			return $data[$taxonomy][$term_id];

		$current = $term_id;
		$parents = [];
		$parent  = TRUE;

		while ( $parent ) {

			$term = get_term( (int) $current, $taxonomy );

			if ( $term->parent )
				$parents[] = (int) $term->parent;

			else
				$parent = FALSE;

			$current = $term->parent;
		}

		return $data[$taxonomy][$term_id] = $parents;
	}

	// TODO: must support different parents
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
	 * Formerly `is_term()`, Introduced in WP 2.3.0.
	 *
	 * @SEE: https://make.wordpress.org/core/2022/04/28/taxonomy-performance-improvements-in-wordpress-6-0/
	 * @SOURCE: OLD VERSION OF `term_exists()`
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int|string $term: The term to check. Accepts term ID, slug, or name.
	 * @param string $taxonomy: Optional. The taxonomy name to use.
	 * @param int $parent: Optional. ID of parent term under which to confine the exists search.
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

			if ( 0 === $term )
				return 0;

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
		$where_fields      = [ $slug ];
		$else_where_fields = [ $term ];
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

		if ( $result )
			return $result;

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms as t WHERE $else_where $orderby $limit", $else_where_fields ) );
	}

	/**
	 * Inserts set of terms into a taxonomy.
	 *
	 * `$update_terms` accepts: `not_name_desc`, `not_name`
	 *
	 * @param string|object $taxonomy
	 * @param array $terms
	 * @param bool|string $update_terms
	 * @param int $force_parent
	 * @return array
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
				++$count;

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

			++$count;
		}

		wp_cache_delete( 'last_changed', 'terms' );
		wp_update_term_count( $tt_id, $taxonomy );

		return $count;
	}

	// @REF: `_update_generic_term_count()`
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
	 * Retrieves children of taxonomy as term IDs,
	 * without option save and accepts taxonomy object.
	 *
	 * @source `_get_term_hierarchy()`
	 *
	 * @param string|object $taxonomy
	 * @return array
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
	 * @param string|object $taxonomy
	 * @param array $extra
	 * @return array
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

	/**
	 * Retrieves the terms of the taxonomy that are attached to the post.
	 * NOTE: hits cached terms for the post
	 *
	 * @param string $taxonomy
	 * @param object $post
	 * @param bool $object
	 * @param bool $key
	 * @param string $index_key
	 * @return array
	 */
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

	// FIXME: check and exclude terms with `trashed` meta
	// @REF: https://developer.wordpress.org/?p=22286
	public static function listTerms( $taxonomy, $fields = NULL, $extra = [], $ordering = TRUE )
	{
		$args = [
			'taxonomy'   => (array) $taxonomy,
			'fields'     => is_null( $fields ) ? 'id=>name' : $fields,
			'hide_empty' => FALSE,
		];

		if ( $ordering )
			$args = array_merge( $args, [
				'order'      => 'ASC',
				'orderby'    => 'meta_value_num, name', // 'name',
				'meta_query' => [
					// @REF: https://core.trac.wordpress.org/ticket/34996
					// FIXME: drop order here: see Terms: `apply_ordering`
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
			] );

		$query = new \WP_Term_Query( array_merge( $args, $extra ) );

		if ( empty( $query->terms ) )
			return [];

		return $query->terms;
	}

	public static function listTermsJS( $taxonomy, $fields = NULL, $extra = [] )
	{
		if ( is_null( $fields ) )
			$fields = [ 'term_id', 'name' ];

		return array_map( function ( $term ) use ( $fields ) {
			return Core\Arraay::keepByKeys( get_object_vars( $term ), $fields );
		}, self::listTerms( $taxonomy, 'all', $extra ) );
	}

	// NOTE: DEPRECATED: use `Term::getMeta()`
	public static function getTermMeta( $term, $keys = FALSE, $single = TRUE )
	{
		return Term::getMeta( $term, $keys, $single );
	}

	public static function addSupport( $taxonomy, $features )
	{
		global $gEditorialTaxonomyFeatures;

		foreach ( (array) $features as $feature )

			if ( 2 == func_num_args() )
				$gEditorialTaxonomyFeatures[$taxonomy][$feature] = TRUE;

			else
				$gEditorialTaxonomyFeatures[$taxonomy][$feature] = array_slice( func_get_args(), 2 );
	}

	public static function removeSupport( $taxonomy, $feature )
	{
		global $gEditorialTaxonomyFeatures;

		unset( $gEditorialTaxonomyFeatures[$taxonomy][$feature] );
	}

	public static function getAllSupports( $taxonomy )
	{
		global $gEditorialTaxonomyFeatures;

		if ( isset( $gEditorialTaxonomyFeatures[$taxonomy] ) )
			return $gEditorialTaxonomyFeatures[$taxonomy];

		return [];
	}

	public static function supports( $taxonomy, $feature )
	{
		$all = self::getAllSupports( $taxonomy );

		if ( isset( $all[$feature][0] ) && is_array( $all[$feature][0] ) )
			return $all[$feature][0];

		return [];
	}

	public static function getBySupport( $feature, $operator = 'and' )
	{
		global $gEditorialTaxonomyFeatures;

		$features = array_fill_keys( (array) $feature, TRUE );

		return array_keys( wp_filter_object_list( $gEditorialTaxonomyFeatures, $features, $operator ) );
	}

	public static function isThumbnail( $attachment_id, $metakey = 'image' )
	{
		if ( ! $attachment_id )
			return FALSE;

		$query = new \WP_Term_Query( [
			// 'taxonomy'   => (array) $taxonomy,
			'orderby'     => 'none',
			'meta_query'  => [ [
				'value'   => $attachment_id,
				'key'     => $metakey,
				'compare' => '=',
			] ],
			'fields'     => 'ids',
			'hide_empty' => FALSE,
		] );

		return empty( $query->terms ) ? [] : $query->terms;
	}

	// NOTE: must add `add_thickbox()` for thick-box
	// @SEE: `Scripts::enqueueThickBox()`
	public static function htmlFeaturedImage( $term_id, $size = NULL, $link = TRUE, $metakey = NULL )
	{
		if ( is_null( $size ) )
			$size = Media::getAttachmentImageDefaultSize( NULL, Term::taxonomy( $term_id ) ?: NULL );

		return Media::htmlAttachmentImage(
			self::getThumbnailID( $term_id, $metakey ),
			$size,
			$link,
			[ 'term' => $term_id ],
			'-featured'
		);
	}

	public static function getThumbnailID( $term_id, $metakey = NULL )
	{
		if ( is_null( $metakey ) )
			$thumbnail_id = (int) get_term_meta( $term_id, 'image', TRUE );

		else if ( $metakey )
			$thumbnail_id = (int) get_term_meta( $term_id, $metakey, TRUE );

		else
			$thumbnail_id = FALSE;

		return apply_filters( 'geditorial_get_term_thumbnail_id', $thumbnail_id, $term_id, $metakey );
	}

	// NOTE: DEPRECATED
	public static function getArchiveLink( $taxonomy )
	{
		self::_dep( 'Taxonomy::link()' );
		return self::link( $taxonomy );
	}

	// NOTE: DEPRECATED
	public static function getTermTitle( $term, $fallback = NULL, $filter = TRUE )
	{
		self::_dep( 'Term::title()' );
		return Term::title( $term, $fallback, $filter );
	}

	/**
	 * Retrieves taxonomy rest route given taxonomy name or object.
	 *
	 * @param string $taxonomy
	 * @return false|string
	 */
	public static function getRestRoute( $taxonomy )
	{
		if ( ! $object = self::object( $taxonomy ) )
			return FALSE;

		if ( ! $object->show_in_rest )
			return FALSE;

		return sprintf( '/%s/%s', $object->rest_namespace, $object->rest_base );
	}

	public static function disableTermCounting()
	{
		wp_defer_term_counting( TRUE );

		// Also avoids query for post terms
		remove_action( 'transition_post_status', '_update_term_count_on_transition_post_status', 10 );

		// WooCommerce
		add_filter( 'woocommerce_product_recount_terms', '__return_false' );
	}

	public static function sortByName( $terms )
	{
		usort( $terms, function ( $a, $b ) {

			$aLast = end( explode( ' ', $a->name ) );
			$bLast = end( explode( ' ', $b->name ) );

			return strcasecmp( $aLast, $bLast );
		} );

		return $terms;
	}
}

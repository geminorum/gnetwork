<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Database extends Core\Base
{

	// @SEE: https://make.wordpress.org/core/2022/10/08/escaping-table-and-field-names-with-wpdbprepare-in-wordpress-6-1/

	public static function getResults( $query, $output = OBJECT, $key = 'default', $group = 'geditorial' )
	{
		global $wpdb;

		$sub = md5( $query );

		if ( ! $cache = wp_cache_get( $key, $group ) )
			$cache = [];

		if ( isset( $cache[$sub] ) )
			return $cache[$sub];

		$cache[$sub] = $wpdb->get_results( $query, $output );

		wp_cache_set( $key, $cache, $group );

		return $cache[$sub];
	}

	public static function hasPosts( $posttypes = [ 'post' ], $exclude_statuses = NULL )
	{
		global $wpdb;

		return (bool) $wpdb->get_var( "
			SELECT 1 as test
			FROM {$wpdb->posts}
			WHERE post_type IN ( '".implode( "', '", esc_sql( (array) $posttypes ) )."' )
			AND post_status NOT IN ( '".implode( "', '", esc_sql( self::getExcludeStatuses( $exclude_statuses ) ) )."' )
			LIMIT 1
		" );
	}

	public static function getExcludeStatuses( $statuses = NULL )
	{
		if ( is_null( $statuses ) )
			return [
				'draft',
				'private',
				'trash',
				'auto-draft',
				'inherit',
			];

		return (array) $statuses;
	}

	public static function getTaxonomies( $same_key = FALSE )
	{
		global $wpdb;

		$taxonomies = $wpdb->get_col( "
			SELECT taxonomy
			FROM {$wpdb->term_taxonomy}
			GROUP BY taxonomy
			ORDER BY taxonomy ASC
		" );

		return $same_key ? Core\Arraay::sameKey( $taxonomies ) : $taxonomies;
	}

	public static function getPostMetaForDropdown( $metakey, $exclude_statuses = NULL )
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '%s'
			AND p.post_status NOT IN ( '".implode( "', '", esc_sql( self::getExcludeStatuses( $exclude_statuses ) ) )."' )
			ORDER BY pm.meta_value",
			$metakey
		);

		return $wpdb->get_col( $query );
	}

	// @REF: https://github.com/scribu/wp-custom-field-taxonomies
	// FIXME: must limit to selected posttypes
	public static function getPostMetaRows( $meta_key, $limit = FALSE )
	{
		global $wpdb;

		if ( $limit )
			$query = $wpdb->prepare( "
				SELECT post_id, GROUP_CONCAT( meta_value ) as meta
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				GROUP BY post_id
				LIMIT %d
			", $meta_key, $limit );
		else
			$query = $wpdb->prepare( "
				SELECT post_id, GROUP_CONCAT( meta_value ) as meta
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				GROUP BY post_id
			", $meta_key );

		return $wpdb->get_results( $query );
	}

	// @REF: https://github.com/scribu/wp-custom-field-taxonomies
	// FIXME: must limit to selected posttypes
	public static function getPostMetaKeys( $same_key = FALSE )
	{
		global $wpdb;

		$meta_keys = $wpdb->get_col( "
			SELECT meta_key
			FROM {$wpdb->postmeta}
			GROUP BY meta_key
			HAVING meta_key NOT LIKE '\_%'
			ORDER BY meta_key ASC
		" );

		return $same_key ? Core\Arraay::sameKey( $meta_keys ) : $meta_keys;
	}

	// @SEE: `delete_post_meta_by_key( 'related_posts' )`
	public static function deletePostMeta( $meta_key, $limit = FALSE )
	{
		global $wpdb;

		if ( $limit )
			$query = $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT %d", $meta_key, $limit );
		else
			$query = $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $meta_key );

		return $wpdb->query( $query, ARRAY_A );
	}

	public static function deleteEmptyMeta( $meta_key )
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value = ''
		" , $meta_key );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	// FIXME
	// public static function getPostsByMultipleTerms( $taxonomy, $posttypes = [ 'post' ], $user_id = 0, $exclude_statuses = NULL ) {}

	// @REF: https://core.trac.wordpress.org/ticket/29181
	// @REF: `WordPress\Taxonomy::countPostsWithoutTerms()`
	public static function countPostsByNotTaxonomy( $taxonomies, $posttypes = [ 'post' ], $user_id = 0, $exclude_statuses = NULL )
	{
		global $wpdb;

		$key = md5( 'not_'.serialize( $taxonomies ).'_'.serialize( $posttypes ).'_'.$user_id );

		if ( FALSE !== ( $cached = wp_cache_get( $key, 'counts' ) ) )
			return $cached;

		$counts = array_fill_keys( $posttypes, 0 );
		$author = $user_id ? $wpdb->prepare( "AND posts.post_author = %d", $user_id ) : '';
		$query  = "
			SELECT posts.post_type, COUNT( * ) AS total
			FROM {$wpdb->posts} AS posts
			WHERE posts.post_type IN ( '".implode( "', '", esc_sql( $posttypes ) )."' )
			AND posts.post_status NOT IN ( '".implode( "', '", esc_sql( self::getExcludeStatuses( $exclude_statuses ) ) )."' )
			{$author}
			AND NOT EXISTS ( SELECT 1
				FROM {$wpdb->term_relationships}
				INNER JOIN {$wpdb->term_taxonomy}
				ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id
				WHERE {$wpdb->term_taxonomy}.taxonomy IN ( '".implode( "', '", esc_sql( (array) $taxonomies ) )."' )
				AND {$wpdb->term_relationships}.object_id = posts.ID
			)
			GROUP BY posts.post_type
		";

		foreach ( (array) $wpdb->get_results( $query, ARRAY_A ) as $row )
			$counts[$row['post_type']] = (int) $row['total'];

		wp_cache_set( $key, $counts, 'counts' );

		return $counts;
	}

	// @REF: `wp_count_posts()`
	// FIXME: probably wrong count if multiple post-types requested
	public static function countPostsByTaxonomy( $taxonomy, $posttypes = [ 'post' ], $user_id = 0, $exclude_statuses = NULL )
	{
		$key    = md5( serialize( $taxonomy ).'_'.serialize( $posttypes ).'_'.$user_id );
		$counts = wp_cache_get( $key, 'counts' );

		if ( FALSE !== $counts )
			return $counts;

		$terms = is_array( $taxonomy ) ? $taxonomy : get_terms( [ 'taxonomy' => $taxonomy ] );

		if ( empty( $terms ) )
			return [];

		global $wpdb;

		$counts = [];
		$totals = array_fill_keys( $posttypes, 0 );

		$author = $user_id ? $wpdb->prepare( "AND posts.post_author = %d", $user_id ) : '';

		foreach ( $terms as $term ) {

			$counts[$term->slug] = $totals;

			$query = $wpdb->prepare("
				SELECT posts.post_type, COUNT( * ) AS total
				FROM {$wpdb->posts} AS posts, {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_id = t.term_id
				INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE t.term_id = %d
				AND tr.object_id = posts.ID
				AND posts.post_type IN ( '".implode( "', '", esc_sql( $posttypes ) )."' )
				AND posts.post_status NOT IN ( '".implode( "', '", esc_sql( self::getExcludeStatuses( $exclude_statuses ) ) )."' )
				{$author}
				GROUP BY posts.post_type
			", $term->term_id );

			foreach ( (array) $wpdb->get_results( $query, ARRAY_A ) as $row )
				$counts[$term->slug][$row['post_type']] = (int) $row['total'];
		}

		wp_cache_set( $key, $counts, 'counts' );

		return $counts;
	}

	// @REF: `wp_count_posts()`
	public static function countPostsByPosttype( $posttype = 'post', $user_id = 0, $period = [] )
	{
		global $wpdb;

		$author = $from = $to = '';
		$counts = array_fill_keys( get_post_stati(), 0 );

		if ( $user_id )
			$author = $wpdb->prepare( "AND post_author = %d", $user_id );

		if ( ! empty( $period[0] ) )
			$from = $wpdb->prepare( "AND post_date >= %s", $period[0] );

		if ( ! empty( $period[1] ) )
			$to = $wpdb->prepare( "AND post_date <= %s", $period[1] );

		$query = $wpdb->prepare( "
			SELECT post_status, COUNT( * ) AS total
			FROM {$wpdb->posts}
			WHERE post_type = %s
			{$author}
			{$from}
			{$to}
			GROUP BY post_status
		", $posttype );

		$results = self::getResults( $query, ARRAY_A, 'counts' );

		foreach ( (array) $results as $row )
			$counts[$row['post_status']] = (int) $row['total'];

		return $counts;
	}

	public static function countPostsByUser( $user_id = NULL, $args = [], $period = [] )
	{
		global $wpdb;

		$counts = [];
		$from   = $to = '';

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		$extra_checks = "AND post_status != 'auto-draft'";

		if ( ! isset( $args['post_status'] )
			|| 'trash' !== $args['post_status'] )
				$extra_checks.= " AND post_status != 'trash'";

		else if ( isset( $args['post_status'] ) )
			$extra_checks = $wpdb->prepare( 'AND post_status = %s', $args['post_status'] );

		if ( ! empty( $period[0] ) )
			$from = $wpdb->prepare( "AND post_date >= %s", $period[0] );

		if ( ! empty( $period[1] ) )
			$to = $wpdb->prepare( "AND post_date <= %s", $period[1] );

		$query = $wpdb->prepare( "
			SELECT post_type, COUNT( * ) AS total
			FROM {$wpdb->posts}
			WHERE post_author = %d
			{$extra_checks}
			{$from}
			{$to}
			GROUP BY post_type
		", $user_id );

		$results = self::getResults( $query, ARRAY_A, 'counts' );

		foreach ( (array) $results as $row )
			$counts[$row['post_type']] = (int) $row['total'];

		return $counts;
	}

	public static function getPostTypeMonths( $posttype = 'post', $args = [], $user_id = 0 )
	{
		global $wpdb, $wp_locale;

		$author = $user_id ? $wpdb->prepare( "AND post_author = %d", $user_id ) : '';

		$extra_checks = "AND post_status != 'auto-draft'";

		if ( ! isset( $args['post_status'] )
			|| 'trash' !== $args['post_status'] )
				$extra_checks.= " AND post_status != 'trash'";

		else if ( isset( $args['post_status'] ) )
			$extra_checks = $wpdb->prepare( 'AND post_status = %s', $args['post_status'] );

		$query = $wpdb->prepare( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM {$wpdb->posts}
			WHERE post_type = %s
			{$author}
			{$extra_checks}
			ORDER BY post_date DESC
		", $posttype );

		$key = md5( $query );
		$cache = wp_cache_get( 'wp_get_archives', 'general' );

		if ( ! isset( $cache[$key] ) ) {
			$months = $wpdb->get_results( $query );
			$cache[$key] = $months;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$months = $cache[$key];
		}

		$count = count( $months );
		if ( ! $count || ( 1 == $count && 0 == $months[0]->month ) )
			return FALSE;

		$list = [];

		foreach ( $months as $row ) {

			if ( 0 == $row->year )
				continue;

			$year  = $row->year;
			$month = Core\Number::zeroise( $row->month, 2 );

			$list[$year.$month] = sprintf( '%1$s %2$s', $wp_locale->get_month( $month ), $year );
		}

		return $list;
	}

	// @REF: `_update_generic_term_count()`
	// @REF: `_update_post_term_count()`
	// @SEE: `update_post_term_count_statuses` filter
	public static function updateCountCallback( $terms, $taxonomy )
	{
		global $wpdb;

		foreach ( (array) $terms as $term ) {

			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", $term ) );

			do_action( 'edit_term_taxonomy', $term, $taxonomy->name );

			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), [ 'term_taxonomy_id' => $term ] );

			do_action( 'edited_term_taxonomy', $term, $taxonomy->name );
		}
	}

	// ADOPTED FROM: LH User Taxonomies v1.6
	public static function updateUserTermCountCallback( $terms, $taxonomy )
	{
		global $wpdb;

		foreach ( (array) $terms as $term ) {

			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*)
				FROM {$wpdb->term_relationships}, {$wpdb->users}
				WHERE {$wpdb->term_relationships}.object_id = {$wpdb->users}.ID
				AND {$wpdb->term_relationships}.term_taxonomy_id = %d", $term ) );

			do_action( 'edit_term_taxonomy', $term, $taxonomy->name );

			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), [ 'term_taxonomy_id' => $term ] );

			do_action( 'edited_term_taxonomy', $term, $taxonomy->name );
		}
	}

	// register a table with $wpdb
	// @SOURCE: `scb_register_table()`
	public static function registerTable( $key, $name = FALSE )
	{
		global $wpdb;

		if ( ! $name )
			$name = $key;

		$wpdb->tables[] = $name;
		$wpdb->$key = $wpdb->prefix.$name;
	}

	// runs the SQL query for installing/upgrading a table
	// @SOURCE: `scb_install_table()`
	public static function installTable( $key, $columns, $options = [] )
	{
		global $wpdb;

		$full_table_name = $wpdb->$key;

		if ( is_string( $options ) )
			$options = [ 'upgrade_method' => $options ];

		$options = self::args( $options, [
			'upgrade_method' => 'dbDelta',
			'table_options'  => '',
		] );

		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {

			if ( ! empty( $wpdb->charset ) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

			if ( ! empty( $wpdb->collate ) )
				$charset_collate.= " COLLATE $wpdb->collate";
		}

		$table_options = $charset_collate.' '.$options['table_options'];

		if ( 'dbDelta' == $options['upgrade_method'] ) {

			require_once ABSPATH.'wp-admin/includes/upgrade.php';

			dbDelta( "CREATE TABLE $full_table_name ( $columns ) $table_options" );

			return;
		}

		if ( 'delete_first' == $options['upgrade_method'] )
			$wpdb->query( "DROP TABLE IF EXISTS $full_table_name;" );

		$wpdb->query( "CREATE TABLE IF NOT EXISTS $full_table_name ( $columns ) $table_options;" );
	}

	// runs the SQL query for uninstalling a table
	// @SOURCE: `scb_uninstall_table()`
	public static function uninstallTable( $key )
	{
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS ".$wpdb->{$key} );
	}

	// prepare an array for an IN statement
	// @ SOURCE: `scbUtil::array_to_sql()`
	public static function array2SQL( $values )
	{
		foreach ( $values as &$val )
			$val = "'".esc_sql( trim( $val ) )."'";

		return implode( ',', $values );
	}

	public static function listUserMetakeys()
	{
		global $wpdb;

		$query = "SELECT distinct {$wpdb->usermeta}.meta_key FROM {$wpdb->usermeta}";

		return array_column( $wpdb->get_results( $query, ARRAY_A ), 'meta_key' );
	}

	public static function countPostMetaByKey( $metakey )
	{
		global $wpdb;

		if ( ! $metakey )
			return FALSE;

		return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", $metakey ) );
	}

	public static function changePostMetaKey( $from, $to )
	{
		global $wpdb;

		if ( ! $from || ! $to || $from === $to )
			return FALSE;

		return $wpdb->update( $wpdb->postmeta, [ 'meta_key' => $to ], [ 'meta_key' => $from ], [ '%s' ], [ '%s' ] );
	}
}

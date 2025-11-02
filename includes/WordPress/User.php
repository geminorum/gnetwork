<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class User extends Core\Base
{

	// OLD: `WordPress::getUsers()`
	public static function get( $all_fields = FALSE, $network = FALSE, $extra = [], $rekey = 'ID' )
	{
		$users = get_users( array_merge( [
			'orderby' => 'display_name',
			'blog_id' => $network ? '' : $GLOBALS['blog_id'],
			'fields'  => $all_fields ? 'all_with_meta' : 'all',
		], $extra ) );

		return Core\Arraay::reKey( $users, $rekey );
	}

	// @REF: `get_blogs_of_user()`
	// OLD:: `Core\WordPress::getUserSites()`
	public static function getSites( $user_id, $prefix )
	{
		$blogs = [];
		$keys  = get_user_meta( $user_id );

		if ( empty( $keys ) )
			return $blogs;

		if ( isset( $keys[$prefix.'capabilities'] ) && defined( 'MULTISITE' ) ) {
			$blogs[] = 1;
			unset( $keys[$prefix.'capabilities'] );
		}

		foreach ( array_keys( $keys ) as $key ) {

			if ( 'capabilities' !== substr( $key, -12 ) )
				continue;

			if ( $prefix && 0 !== strpos( $key, $prefix ) )
				continue;

			$blog = str_replace( [ $prefix, '_capabilities' ], '', $key );

			if ( is_numeric( $blog ) )
				$blogs[] = (int) $blog;
		}

		return $blogs;
	}

	// MAYBE: rename to `object`
	public static function user( $field, $key = FALSE )
	{
		if ( 0 === $field || '0' === $field || FALSE === $field )
			return FALSE;

		if ( is_null( $field ) )
			$user = wp_get_current_user();

		if ( $field instanceof \WP_User )
			$user = $field;

		else if ( is_int( $field ) )
			$user = get_user_by( 'id', $field );

		else if ( is_string( $field ) )
			$user = get_user_by( 'login', $field );

		else
			return FALSE;

		if ( ! is_object( $user ) )
			return FALSE;

		if ( ! $key )
			return $user;

		if ( isset( $user->{$key} ) )
			return $user->{$key};

		return FALSE;
	}

	// current user can
	// OLD: `Core\WordPress::cuc()`
	public static function cuc( $cap, $none = TRUE )
	{
		if ( 'none' == $cap || '0' == $cap )
			return $none;

		if ( ! is_user_logged_in() )
			return FALSE;

		// pseudo-cap for network users
		if ( '_member_of_network' == $cap )
			return TRUE;

		// pseudo-cap for site users
		if ( '_member_of_site' == $cap )
			return is_user_member_of_blog() || self::isSuperAdmin();

		return current_user_can( $cap );
	}

	/**
	 * Retrieves the URL for editing a given user.
	 * OLD: `Core\WordPress::getUserEditLink()`
	 *
	 * @param int $user_id
	 * @param array $extra
	 * @param bool $network
	 * @param bool $check
	 * @return false|string
	 */
	public static function edit( $user_id, $extra = [], $network = FALSE, $check = TRUE )
	{
		if ( ! $user_id )
			return FALSE;

		if ( $check && ! current_user_can( 'edit_user', $user_id ) )
			return FALSE;

		return add_query_arg( array_merge( [
			'user_id' => $user_id,
		], $extra ), $network
			? network_admin_url( 'user-edit.php' )
			: admin_url( 'user-edit.php' ) );

		return FALSE;
	}

	public static function getTitleRow( $user, $fallback = '', $template = NULL )
	{
		if ( ! $object = self::user( $user ) )
			return $fallback;

		return sprintf( $template ?? '%s (%s)', $object->display_name, $object->user_email );
	}

	// NOTE: alternative to `is_super_admin()`
	// OLD: `Core\WordPress::isSuperAdmin()`
	public static function isSuperAdmin( $user_id = FALSE )
	{
		$cap = is_multisite() ? 'manage_network' : 'manage_options';
		return $user_id ? user_can( $user_id, $cap ) : current_user_can( $cap );
	}

	// OLD: `Core\WordPress::superAdminOnly()`
	public static function superAdminOnly()
	{
		if ( ! self::isSuperAdmin() )
			self::cheatin();
	}

	public static function getObjectbyMeta( $meta, $value, $network = TRUE )
	{
		$args = [
			'meta_key'    => $meta,
			'meta_value'  => $value,
			'compare'     => '=',
			'number'      => 1,
			'count_total' => FALSE,
		];

		if ( $network )
			$args['blog_id'] = 0;

		$query = new \WP_User_Query( $args );
		$users = $query->get_results();

		return reset( $users );
	}

	public static function getIDbyMeta( $key, $value, $single = TRUE )
	{
		global $wpdb, $gEditorialUserIDbyMeta;

		if ( empty( $key ) || empty( $value ) )
			return FALSE;

		if ( empty( $gEditorialUserIDbyMeta ) )
			$gEditorialUserIDbyMeta = [];

		$group = $single ? 'single' : 'all';

		if ( isset( $gEditorialUserIDbyMeta[$key][$group][$value] ) )
			return $gEditorialUserIDbyMeta[$key][$group][$value];

		$query = $wpdb->prepare( "
			SELECT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = %s
			AND meta_value = %s
		", $key, $value );

		$results = $single
			? $wpdb->get_var( $query )
			: $wpdb->get_col( $query );

		return $gEditorialUserIDbyMeta[$key][$group][$value] = $results;
	}

	public static function invalidateIDbyMeta( $meta, $value = FALSE )
	{
		global $gEditorialUserIDbyMeta;

		if ( empty( $meta ) )
			return TRUE;

		if ( empty( $gEditorialUserIDbyMeta ) )
			return TRUE;

		if ( FALSE === $value ) {

			// clear all meta by key
			foreach ( (array) $meta as $key ) {
				unset( $gEditorialUserIDbyMeta[$key]['all'] );
				unset( $gEditorialUserIDbyMeta[$key]['single'] );
			}

		} else {

			foreach ( (array) $meta as $key ) {
				unset( $gEditorialUserIDbyMeta[$key]['all'][$value] );
				unset( $gEditorialUserIDbyMeta[$key]['single'][$value] );
			}
		}

		return TRUE;
	}

	// @REF: `get_blogs_of_user()`
	public static function getUserBlogs( $user_id, $prefix )
	{
		$blogs = [];
		$keys  = get_user_meta( $user_id );

		if ( empty( $keys ) )
			return $blogs;

		if ( isset( $keys[$prefix.'capabilities'] ) && defined( 'MULTISITE' ) ) {
			$blogs[] = 1;
			unset( $keys[$prefix.'capabilities'] );
		}

		foreach ( array_keys( $keys ) as $key ) {

			if ( 'capabilities' !== substr( $key, -12 ) )
				continue;

			if ( $prefix && 0 !== strpos( $key, $prefix ) )
				continue;

			$blog = str_replace( [ $prefix, '_capabilities' ], '', $key );

			if ( is_numeric( $blog ) )
				$blogs[] = (int) $blog;
		}

		return $blogs;
	}

	// @SEE: https://core.trac.wordpress.org/ticket/38741
	public static function isLargeCount( $network_id = NULL )
	{
		if ( function_exists( 'wp_is_large_user_count' ) )
			return wp_is_large_user_count( $network_id ); // @since WP 6.0.0

		if ( function_exists( 'wp_is_large_network' ) )
			return wp_is_large_network( 'users', $network_id );

		if ( defined( 'GNETWORK_LARGE_NETWORK_IS' ) && GNETWORK_LARGE_NETWORK_IS )
			return get_user_count( $network_id ) > GNETWORK_LARGE_NETWORK_IS;

		return FALSE;
	}

	public static function changeUsername( $old, $new )
	{
		global $wpdb;

		// Do nothing if old username does not exist.
		if ( ! username_exists( $old ) || username_exists( $new ) )
			return FALSE;

		// change `username`
		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->users
			SET user_login = %s
			WHERE user_login = %s
		", $new, $old ) );

		// change `nicename` if needed
		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->users
			SET user_nicename = %s
			WHERE user_login = %s
			AND user_nicename = %s
		", $new, $new, $old ) );

		// change `display_name` if needed
		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->users
			SET display_name = %s
			WHERE user_login = %s
			AND display_name = %s
		", $new, $new, $old ) );

		if ( is_multisite() ) {

			// When on multi-site, check if old username is in the `site_admins`
			// options array. If so, replace with new username to retain
			// super-admin rights.

			$supers = (array) get_site_option( 'site_admins', [ 'admin' ] );

			if ( $key = array_search( $old, $supers ) )
				$supers[$key] = $new;

			update_site_option( 'site_admins', $supers );
		}

		return $new;
	}
}

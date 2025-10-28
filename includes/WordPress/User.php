<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class User extends Core\Base
{

	public static function get( $all_fields = FALSE, $network = FALSE, $extra = [], $rekey = 'ID' )
	{
		$users = get_users( array_merge( [
			'orderby' => 'display_name',
			'blog_id' => $network ? '' : $GLOBALS['blog_id'],
			'fields'  => $all_fields ? 'all_with_meta' : 'all',
		], $extra ) );

		return Core\Arraay::reKey( $users, $rekey );
	}

	public static function user( $field, $key = FALSE )
	{
		if ( ! $field )
			return FALSE;

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

	// alt to `is_super_admin()`
	public static function isSuperAdmin( $user_id = FALSE )
	{
		$cap = is_multisite() ? 'manage_network' : 'manage_options';
		return $user_id ? user_can( $user_id, $cap ) : current_user_can( $cap );
	}

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

	public static function getIDbyMeta( $meta, $value, $single = TRUE )
	{
		static $data = [];

		$group = $single ? 'single' : 'all';

		if ( isset( $data[$meta][$group][$value] ) )
			return $data[$meta][$group][$value];

		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = %s
			AND meta_value = %s
		", $meta, $value );

		$results = $single
			? $wpdb->get_var( $query )
			: $wpdb->get_col( $query );

		return $data[$meta][$group][$value] = $results;
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

	// @REF: `get_role_list()`
	// FIXME: move to `WordPress\Role`
	public static function getRoleList( $user_id = FALSE )
	{
		if ( ! $user_id )
			return self::getAllRoleList();

		$list = [];
		$user = is_object( $user_id ) ? $user_id : get_user_by( 'id', $user_id );

		if ( ! is_object( $user ) )
			return $list;

		$roles = wp_roles();

		foreach ( $user->roles as $role )
			if ( isset( $roles->role_names[$role] ) )
				$list[$role] = translate_user_role( $roles->role_names[$role] );

		return $list;
	}

	/**
	 * Retrieves roles for given user.
	 * FIXME: move to `WordPress\Role`
	 *
	 * @param null|int $user_id
	 * @return array
	 */
	public static function getRoles( $user_id = NULL )
	{
		$user = get_user_by( 'id', ( $user_id ?: get_current_user_id() ) );
		return empty( $user ) ? [] : (array) $user->roles;
	}

	/**
	 * Checks if the user has given role.
	 * FIXME: move to `WordPress\Role`
	 *
	 * @param string|array $role
	 * @param null|int $user_id
	 * @return bool
	 */
	public static function hasRole( $role, $user_id = NULL )
	{
		if ( empty( $role ) )
			return FALSE;

		$currents = self::getRoles( $user_id );

		if ( empty( $currents ) )
			return FALSE;

		return (bool) count( array_intersect( (array) $role, $currents ) );
	}

	// current user role
	// TODO: move to `WordPress\Role`
	public static function cur( $role = FALSE )
	{
		$roles = self::getRoles();
		return $role ? in_array( $role, $roles, TRUE ) : $roles;
	}

	// TODO: move to `WordPress\Role`
	public static function getAllRoleList( $filtered = TRUE, $object = FALSE )
	{
		$roles = $filtered ? get_editable_roles() : wp_roles()->roles;
		$list  = $object ? new \stdClass : [];

		foreach ( $roles as $role_name => $role )

			if ( $object )
				$list->{$role_name} = translate_user_role( $role['name'] );

			else
				$list[$role_name] = translate_user_role( $role['name'] );

		return $list;
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

		// do nothing if old username does not exist.
		if ( ! username_exists( $old ) || username_exists( $new ) )
			return FALSE;

		// change username
		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->users
			SET user_login = %s
			WHERE user_login = %s
		", $new, $old ) );

		// change nicename if needed
		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->users
			SET user_nicename = %s
			WHERE user_login = %s
			AND user_nicename = %s
		", $new, $new, $old ) );

		// change display name if needed
		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->users
			SET display_name = %s
			WHERE user_login = %s
			AND display_name = %s
		", $new, $new, $old ) );

		if ( is_multisite() ) {

			// when on multisite, check if old username is in the `site_admins`
			// options array. if so, replace with new username to retain
			// superadmin rights.

			$supers = (array) get_site_option( 'site_admins', [ 'admin' ] );

			if ( $key = array_search( $old, $supers ) )
				$supers[$key] = $new;

			update_site_option( 'site_admins', $supers );
		}

		return $new;
	}
}

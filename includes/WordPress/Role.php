<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

/***
	[Summary of Roles](https://wordpress.org/documentation/article/roles-and-capabilities/#summary-of-roles)
	--------------------------------------------------------------------------------------------------------

	-   [Super Admin]: somebody with access to the site network administration features and all other features. See the [Create a Network](https://wordpress.org/support/article/create-a-network/)article.
	-   [Administrator]: (*slug: 'administrator'*) -- somebody who has access to all the administration features within a single site.
	-   [Editor]: (*slug: 'editor'*) -- somebody who can publish and manage posts including the posts of other users.
	-   [Author]: (*slug: 'author'*) -- somebody who can publish and manage their own posts.
	-   [Contributor]: (*slug: 'contributor'*) -- somebody who can write and manage their own posts but cannot publish them.
	-   [Subscriber]: (*slug: 'subscriber'*) -- somebody who can only manage their profile.

	Upon installing WordPress, an Administrator account is automatically created.
***/

class Role extends Core\Base
{

	// $role->add_cap( $cap );
	// $role->remove_cap( $cap );
	// remove_role( $role );
	// https://learn.wordpress.org/tutorial/custom-post-types-and-capabilities/

	public static function object( $role )
	{
		if ( ! $role )
			return FALSE;

		if ( $role instanceof \WP_Role )
			return $role;

		return get_role( $role ) ?: FALSE;
	}

	public static function capabilities( $role, $fallback = [] )
	{
		if ( ! $object = self::object( $role ) )
			return $fallback;

		if ( \property_exists( $object, 'capabilities' ) )
			return $object->capabilities ?: $fallback;

		return $fallback;
	}

	public static function default( $fallback = 'subscriber' )
	{
		return self::object( get_option( 'default_role', $fallback ) );
	}

	// @REF: `WP_Users_List_Table::get_role_list()`
	// OLD: `WordPress\User::getAllRoleList()`
	// OLD: `WordPress\User::getRoleList()`
	// OLD: `Core\WordPress::getUserRoles()` : `WordPress\Role::get( 2, [], NULL )`
	// OLD: `WordPress\User::getRoles()`: `WordPress\Role::get( 2, [], NULL )`
	public static function get( $mod = 0, $args = [], $user = FALSE, $filtered = TRUE )
	{
		$roles = $filtered && is_admin() ? get_editable_roles() : wp_roles()->roles;
		$user  = User::user( $user ?? get_current_user_id() );

		switch ( $mod ) {

			 case 0: // translated array

				$list = [];

				foreach ( $roles as $role_name => $role )
					if ( ! $user )
						$list[$role_name] = translate_user_role( $role['name'] );

					else if ( in_array( $role_name, $user->roles, TRUE ) )
						$list[$role_name] = translate_user_role( $role['name'] );

				break;

			case 1: // translated object

				$list = new \stdClass;

				foreach ( $roles as $role_name => $role )
					if ( ! $user )
						$list->{$role_name} = translate_user_role( $role['name'] );

					else if ( in_array( $role_name, $user->roles, TRUE ) )
						$list->{$role_name} = translate_user_role( $role['name'] );

				break;

			case 2: // keys array

				if ( ! $user )
					$list = array_keys( $roles );
				else
					$list = $user->roles;

				break;

			case 3: // non-translated array
			default:

				if ( ! $user )
					$list = $roles;
				else
					$list = Core\Arraay::keepByKeys( $roles, $user->roles );
		}

		return $user
			// NOTE: core filter @since WP 4.4.0
			? apply_filters( 'get_role_list', $list, $user )
			: $list;
	}

	/**
	 * Checks if the user has given role.
	 * OLD: `Core\WordPress::userHasRole()`
	 * OLD: `WordPress\User::hasRole()`
	 * OLD: `WordPress\User::cur()`
	 * OLD: `Core\WordPress::cur()`
	 *
	 * @param string|array $roles
	 * @param null|int $user_id
	 * @return bool
	 */
	public static function has( $roles, $user = NULL )
	{
		if ( empty( $roles ) )
			return FALSE;

		// NOTE: `filtered` may cause infinite loop
		$currents = self::get( 2, [], $user, FALSE );

		if ( empty( $currents ) )
			return FALSE;

		return (bool) count( array_intersect( (array) $roles, $currents ) );
	}

	// @REF: `wp_get_users_with_no_role()`
	// OLD: `Core\WordPress::getUsersWithNoRole()`
	public static function listHasNoRole( $site_id = NULL )
	{
		global $wpdb;

		$current = get_current_blog_id();

		if ( is_null( $site_id ) )
			$site_id = $current;

		if ( is_multisite() && $site_id != $current ) {

			switch_to_blog( $site_id );

			$role_names = wp_roles()->get_names();

			restore_current_blog();

		} else {

			$role_names = wp_roles()->get_names();
		}

		$regex = implode( '|', array_keys( $role_names ) );

		$prefix = $wpdb->get_blog_prefix( $site_id );
		$query  = $wpdb->prepare( "
			SELECT user_id
			FROM $wpdb->usermeta
			WHERE meta_key = '{$prefix}capabilities'
			AND meta_value NOT REGEXP %s
		", preg_replace( '/[^a-zA-Z_\|-]/', '', $regex ) );

		return $wpdb->get_col( $query );
	}

	// @REF: `wp_get_users_with_no_role()`
	// OLD: `Core\WordPress::getUsersWithRole()`
	public static function listWithRole( $role, $site_id = NULL )
	{
		global $wpdb;

		$prefix = $wpdb->get_blog_prefix( $site_id );
		$query  = $wpdb->prepare( "
			SELECT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = '{$prefix}capabilities'
			AND meta_value REGEXP %s
		", preg_replace( '/[^a-zA-Z_\|-]/', '', $role ) );

		return $wpdb->get_col( $query );
	}

	public static function sanitize( $input )
	{
		return preg_replace( '/[^a-zA-Z0-9_]/gu', '', Core\Number::translate( Core\Text::trim( $input ) ) );
	}
}

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class WordPress extends Base
{

	public static function isDebug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::isDev() )
			return TRUE;

		return FALSE;
	}

	public static function isDev()
	{
		if ( defined( 'WP_STAGE' )
			&& 'development' == constant( 'WP_STAGE' ) )
				return TRUE;

		return FALSE;
	}

	public static function isFlush()
	{
		if ( isset( $_GET['flush'] ) )
			return did_action( 'init' ) && current_user_can( 'publish_posts' );

		return FALSE;
	}

	public static function isAJAX()
	{
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	public static function isCRON()
	{
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	public static function isCLI()
	{
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	public static function registerURL( $register = FALSE )
	{
		if ( function_exists( 'buddypress' ) ) {

			if ( bp_get_signup_allowed() )
				return bp_get_signup_page();

		} else if ( get_option( 'users_can_register' ) ) {

			if ( is_multisite() )
				return apply_filters( 'wp_signup_location', network_site_url( 'wp-signup.php' ) );
			else
				return wp_registration_url();

		} else if ( 'site' == $register ) {
			return site_url( '/' );
		}

		return $register;
	}

	// BETTER: `HTTP::currentURL()`
	public static function currentURL( $trailingslashit = FALSE )
	{
		global $wp;

		$request = $wp->request ? add_query_arg( array(), $wp->request ) : add_query_arg( array() );
		$current = home_url( $request );

		if ( $trailingslashit )
			return self::trail( $current );

		return $current;
	}

	public static function currentBlog()
	{
		$blog = home_url();

		$blog = str_ireplace( array( 'https://', 'http://' ), '', $blog );
		$blog = str_ireplace( array( '/', '\/' ), '-', $blog );

		return $blog;
	}

	public static function getCurrentSiteBlogID()
	{
		if ( ! is_multisite() )
			return get_current_blog_id();

		global $current_site;
		return absint( $current_site->blog_id );
	}

	// get an appropriate hostname. varies depending on site configuration.
	// originally from BuddyPress 2.5.0
	public static function getHostName()
	{
		if ( is_multisite() )
			return get_current_site()->domain;

		return preg_replace( '#^https?://#i', '', get_option( 'home' ) );
	}

	// FIXME: add general options for on a network panel
	public static function getSiteUserID( $fallback = FALSE )
	{
		if ( defined( 'GNETWORK_SITE_USER_ID' ) && GNETWORK_SITE_USER_ID )
			return intval( GNETWORK_SITE_USER_ID );

		if ( function_exists( 'gtheme_get_option' ) ) {
			if ( $gtheme_user = gtheme_get_option( 'default_user', 0 ) )
				return intval( $gtheme_user );
		}

		if ( $fallback )
			return intval( get_current_user_id() );

		return 0;
	}

	public static function superAdminOnly()
	{
		if ( ! is_super_admin() )
			self::cheatin();
	}

	public static function getUserRoles( $user_id = FALSE )
	{
		$user = get_user_by( 'id', ( $user_id ? $user_id : get_current_user_id() ) );
		return empty( $user ) ? array() : $user->roles;
	}

	public static function userHasRole( $role, $user_id = FALSE )
	{
		return in_array( $role, self::getUserRoles( $user_id ) );
	}

	// current user role
	public static function cur( $role = FALSE )
	{
		$roles = self::getUserRoles();
		return $role ? in_array( $role, $roles ) : $roles;
	}

	public static function getUserRoleList( $object = FALSE )
	{
		$roles = $object ? new stdClass : array();

		foreach ( get_editable_roles() as $role_name => $role )

			if ( $object )
				$roles->{$role_name} = translate_user_role( $role['name'] );

			else
				$roles[$role_name] = translate_user_role( $role['name'] );

		return $roles;
	}

	public static function getUsers( $all_fields = FALSE, $network = FALSE, $extra = array() )
	{
		$users = get_users( array_merge( array(
			'blog_id' => ( $network ? '' : $GLOBALS['blog_id'] ),
			'orderby' => 'display_name',
			'fields'  => ( $all_fields ? 'all_with_meta' : 'all' ),
		), $extra ) );

		return Arraay::reKey( $users, 'ID' );
	}

	// current user can
	public static function cuc( $cap, $none = TRUE )
	{
		if ( 'none' == $cap || '0' == $cap )
			return $none;

		return current_user_can( $cap );
	}

	public static function cheatin( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Cheatin&#8217; uh?', 'Core: WordPress', GNETWORK_TEXTDOMAIN );

		wp_die( $message, 403 );
	}

	// FIXME: add to filter: 'search_link' / DEPRICATE THIS
	public static function getSearchLink( $query = '', $url = FALSE, $query_id = GNETWORK_SEARCH_QUERYID )
	{
		if ( $url )
			return $query ? add_query_arg( $query_id, urlencode( $query ), $url ) : $url;

		if ( GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( $query_id, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return get_search_link( $query );
	}

	// EDITED: 8/12/2016, 8:53:06 AM
	public static function getPostTypes( $mod = 0, $args = array( 'public' => TRUE ) )
	{
		$list = array();

		foreach ( get_post_types( $args, 'objects' ) as $post_type => $post_type_obj ) {

			// label
			if ( 0 === $mod )
				$list[$post_type] = $post_type_obj->label;

			// plural
			else if ( 1 === $mod )
				$list[$post_type] = $post_type_obj->labels->name;

			// singular
			else if ( 2 === $mod )
				$list[$post_type] = $post_type_obj->labels->singular_name;

			// nooped
			else if ( 3 === $mod )
				$list[$post_type] = array(
					0          => $post_type_obj->labels->singular_name,
					1          => $post_type_obj->labels->name,
					'singular' => $post_type_obj->labels->singular_name,
					'plural'   => $post_type_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				);

			// object
			else if ( 4 === $mod )
				$list[$post_type] = $post_type_obj;
		}

		return $list;
	}

	public static function customStyleSheet( $css, $link = TRUE, $version = NULL )
	{
		$url = FALSE;

		if ( file_exists( get_stylesheet_directory().'/'.$css ) )
			$url = get_stylesheet_directory_uri().'/'.$css;

		else if ( file_exists( get_template_directory().'/'.$css ) )
			$url = get_template_directory_uri().'/'.$css;

		else if ( file_exists( WP_CONTENT_DIR.'/'.$css ) )
			$url = WP_CONTENT_URL.'/'.$css;

		if ( ! $url || ! $link )
			return $url;

		HTML::linkStyleSheet( $url, $version );
	}

	// shows all the "filters" currently attached to a hook
	public static function filters( $hook )
	{
		global $wp_filter;

		if ( ! isset( $wp_filter[$hook] ) )
			return;

		self::dump( $wp_filter[$hook] );
	}
}

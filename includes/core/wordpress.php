<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class WordPress extends Base
{

	public static function isMinWPv( $minimum_version )
	{
		return ( version_compare( get_bloginfo( 'version' ), $minimum_version ) >= 0 );
	}

	public static function mustRegisterUI( $check_admin = TRUE )
	{
		if ( self::isAJAX()
			|| self::isCLI()
			|| self::isCRON()
			|| self::isXMLRPC()
			|| self::isREST()
			|| self::isIFrame() )
				return FALSE;

		if ( $check_admin && ! is_admin() )
			return FALSE;

		return TRUE;
	}

	// @REF: `vars.php`
	public static function pageNow()
	{
		if ( preg_match( '#([^/]+\.php)([?/].*?)?$#i', $_SERVER['PHP_SELF'], $matches ) )
			return strtolower( $matches[1] );

		return 'index.php';
	}

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

	// @SEE: `wp_doing_ajax()` since 4.7.0
	public static function isAJAX()
	{
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	// @SEE: `wp_doing_cron()` since 4.8.0
	public static function isCRON()
	{
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	public static function isCLI()
	{
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	public static function isXMLRPC()
	{
		return defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
	}

	public static function isREST()
	{
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	public static function isIFrame()
	{
		return defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST;
	}

	public static function doNotCache()
	{
		defined( 'DONOTCACHEPAGE' ) or define( 'DONOTCACHEPAGE', TRUE );
	}

	// @REF: `wp_referer_field()`
	public static function fieldReferer()
	{
		HTML::inputHidden( '_wp_http_referer', self::unslash( $_SERVER['REQUEST_URI'] ) );
	}

	public static function redirect( $location = NULL, $status = 302 )
	{
		if ( is_null( $location ) )
			$location = add_query_arg( wp_get_referer() );

		wp_redirect( $location, $status );

		die;
	}

	public static function redirectReferer( $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, wp_get_referer() );
		else
			$url = add_query_arg( $key, $message, wp_get_referer() );

		self::redirect( $url );
	}

	public static function redirectLogin( $location = '', $status = 302 )
	{
		self::redirect( self::loginURL( $location, TRUE ), $status );
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

	// @REF: `wp_login_url()`
	public static function loginURL( $redirect = '', $force_reauth = FALSE )
	{
		// working, but disabled due to problem with redirects on wp networks
		// $login_url = get_blog_option( get_main_site_id(), 'siteurl' ).'/wp-login.php';
		$login_url = site_url( 'wp-login.php', 'login' );

		if ( ! empty( $redirect ) )
			$login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );

		if ( $force_reauth )
			$login_url = add_query_arg( 'reauth', '1', $login_url );

		return apply_filters( 'login_url', $login_url, $redirect, $force_reauth );
	}

	// BETTER: `URL::current()`
	// @SEE: `wp_guess_url()`
	public static function currentURL( $trailingslashit = FALSE )
	{
		global $wp;

		$request = $wp->request ? add_query_arg( array(), $wp->request ) : add_query_arg( array() );
		$current = home_url( $request );

		if ( $trailingslashit )
			return URL::trail( $current );

		return $current;
	}

	public static function getHostName()
	{
		return is_multisite() ? preg_replace( '#^https?://#i', '', get_option( 'home' ) ) : get_current_site()->domain;
	}

	// OLD: `getBlogNameforEmail()`
	public static function getSiteNameforEmail( $site = FALSE )
	{
		if ( ! $site && is_multisite() )
			return get_network()->site_name;

		// The blogname option is escaped with esc_html on the way into the database
		// in sanitize_option we want to reverse this for the plain text arena of emails.
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	// OLD: `currentSite()`
	public static function currentNetworkURL()
	{
		$network = get_current_site();
		$scheme  = is_ssl() ? 'https' : 'http';

		return URL::untrail( "$scheme://{$network->domain}{$network->path}" );
	}

	// OLD: `currentBlog()`
	public static function currentSiteName( $slash = TRUE )
	{
		return URL::prepTitle( get_option( 'home' ), $slash );
	}

	// FIXME: as option on user module
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
		if ( ! self::isSuperAdmin() )
			self::cheatin();
	}

	public static function getUserRoles( $user_id = FALSE )
	{
		$user = get_user_by( 'id', ( $user_id ? $user_id : get_current_user_id() ) );
		return empty( $user ) ? array() : (array) $user->roles;
	}

	public static function userHasRole( $role, $user_id = FALSE )
	{
		$roles = self::getUserRoles( $user_id );

		foreach ( (array) $role as $name )
			if ( in_array( $name, $roles ) )
				return TRUE;

		return FALSE;
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

		if ( 'read' != $cap && ! is_user_logged_in() )
			return FALSE;

		return current_user_can( $cap );
	}

	// alt to `is_super_admin()`
	public static function isSuperAdmin( $user_id = FALSE )
	{
		$cap = is_multisite() ? 'manage_network' : 'manage_options';
		return $user_id ? user_can( $user_id, $cap ) : current_user_can( $cap );
	}

	// mocking `get_sites()` results
	public static function getAllBlogs( $user_id = FALSE, $all_sites = TRUE, $orderby_site = FALSE )
	{
		global $wpdb;

		$clause_site = $clause_blog = '';

		if ( ! $all_sites )
			$clause_site = $wpdb->prepare( "AND site_id = %d", get_current_network_id() );

		if ( $user_id ) {

			$ids = self::getUserBlogs( $user_id, $wpdb->base_prefix );

			// user has no blogs!
			if ( ! $ids )
				return [];

			$clause_blog = "AND blog_id IN ( '".join( "', '", esc_sql( $ids ) )."' )";
		}

		$clause_order = $orderby_site ? 'ORDER BY domain, path ASC' : 'ORDER BY registered DESC';

		$query = "
			SELECT blog_id, domain, path
			FROM {$wpdb->blogs}
			WHERE spam = '0'
			AND deleted = '0'
			AND archived = '0'
			{$clause_site}
			{$clause_blog}
			{$clause_order}
		";

		$blogs  = [];
		$scheme = is_ssl() ? 'https' : 'http';

		foreach ( $wpdb->get_results( $query, ARRAY_A ) as $blog )
			$blogs[$blog['blog_id']] = (object) [
				'userblog_id' => $blog['blog_id'],
				'domain'      => $blog['domain'],
				'path'        => $blog['path'],
				'siteurl'     => URL::untrail( $scheme.'://'.$blog['domain'].$blog['path'] ),
			];

		return $blogs;
	}

	// @REF: `get_blogs_of_user()`
	public static function getUserBlogs( $user_id, $prefix )
	{
		$blogs = array();
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

			$blog = str_replace( array( $prefix, '_capabilities' ), '', $key );

			if ( is_numeric( $blog ) )
				$blogs[] = (int) $blog;
		}

		return $blogs;
	}

	public static function cheatin( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = __( 'Cheatin&#8217; uh?' );

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

	public static function getPostEditLink( $post_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'action' => 'edit',
			'post'   => $post_id,
		), $extra ), admin_url( 'post.php' ) );
	}

	public static function getPostShortLink( $post_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'p' => $post_id,
		), $extra ), get_bloginfo( 'url' ) );
	}

	public static function getPostNewLink( $post_type, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'post_type' => $post_type,
		), $extra ), admin_url( 'post-new.php' ) );
	}

	public static function getUserEditLink( $user_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'user_id' => $user_id,
		), $extra ), admin_url( 'user-edit.php' ) );
	}

	public static function getAuthorEditHTML( $post_type, $author, $extra = array() )
	{
		if ( $author_data = get_user_by( 'id', $author ) )
			return HTML::tag( 'a', array(
				'href' => add_query_arg( array_merge( array(
					'post_type' => $post_type,
					'author'    => $author,
				), $extra ), admin_url( 'edit.php' ) ),
				'title' => $author_data->user_login,
				'class' => '-author',
			), HTML::escape( $author_data->display_name ) );

		return FALSE;
	}

	public static function upload( $post = FALSE )
	{
		if ( FALSE === $post )
			return wp_upload_dir();

		if ( ! $post = get_post( $post ) )
			return wp_upload_dir();

		if ( 'page' === $post->post_type )
			return wp_upload_dir();

		return wp_upload_dir( ( substr( $post->post_date, 0, 4 ) > 0 ? $post->post_date : NULL ) );
	}

	public static function getAttachments( $post_id, $mime_type = 'image' )
	{
		return get_children( array(
			'post_mime_type' => $mime_type,
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'numberposts'    => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
	}

	// FIXME: get title if html is empty
	public static function htmlAttachmentShortLink( $id, $html )
	{
		return HTML::tag( 'a', [
			'href'  => self::getPostShortLink( $id ),
			'rel'   => 'attachment',
			'class' => '-attachment',
			'data'  => [ 'id' => $id ],
		], $html );
	}

	public static function getPostTypes( $mod = 0, $args = array( 'public' => TRUE ) )
	{
		$list = array();

		foreach ( get_post_types( $args, 'objects' ) as $post_type => $post_type_obj ) {

			// label
			if ( 0 === $mod )
				$list[$post_type] = $post_type_obj->label ? $post_type_obj->label : $post_type_obj->name;

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

	public static function getTaxonomies( $mod = 0, $args = array(), $object = FALSE )
	{
		$list = array();

		if ( FALSE === $object )
			$objects = get_taxonomies( $args, 'objects' );
		else
			$objects = get_object_taxonomies( $object, 'objects' );

		foreach ( $objects as $taxonomy => $taxonomy_obj ) {

			// label
			if ( 0 === $mod )
				$list[$taxonomy] = $taxonomy_obj->label ? $taxonomy_obj->label : $taxonomy_obj->name;

			// plural
			else if ( 1 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->name;

			// singular
			else if ( 2 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->singular_name;

			// nooped
			else if ( 3 === $mod )
				$list[$taxonomy] = array(
					0          => $taxonomy_obj->labels->singular_name,
					1          => $taxonomy_obj->labels->name,
					'singular' => $taxonomy_obj->labels->singular_name,
					'plural'   => $taxonomy_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				);

			// object
			else if ( 4 === $mod )
				$list[$taxonomy] = $taxonomy_obj;

			// with object_type
			else if ( 5 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->name.HTML::joined( $taxonomy_obj->object_type, ' [', ']' );

			// with name
			else if ( 6 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->menu_name.' ('.$taxonomy_obj->name.')';
		}

		return $list;
	}

	public static function customFile( $filename, $path = FALSE )
	{
		$stylesheet = get_stylesheet_directory();

		if ( file_exists( $stylesheet.'/'.$filename ) )
			return $path ? ( $stylesheet().'/'.$filename )
				: get_stylesheet_directory_uri().'/'.$filename;

		$template = get_template_directory();

		if ( file_exists( $template.'/'.$filename ) )
			return $path ? ( $template.'/'.$filename )
				: get_template_directory_uri().'/'.$filename;

		if ( file_exists( WP_CONTENT_DIR.'/'.$filename ) )
			return $path ? ( WP_CONTENT_DIR.'/'.$filename )
				: ( WP_CONTENT_URL.'/'.$filename );

		return FALSE;
	}

	// shows all the "filters" currently attached to a hook
	public static function filters( $hook )
	{
		global $wp_filter;

		if ( ! isset( $wp_filter[$hook] ) )
			return;

		self::dump( $wp_filter[$hook] );
	}

	// @SOURCE: `wp-load.php`
	public static function getConfigPHP( $path = ABSPATH )
	{
		// The config file resides in ABSPATH
		if ( file_exists( $path.'wp-config.php' ) )
			return $path.'wp-config.php';

		// The config file resides one level above ABSPATH but is not part of another install
		$above = dirname( $path );

		if ( @file_exists( $above.'/wp-config.php' ) && ! @file_exists( $above.'/wp-settings.php' ) )
			return $above.'/wp-config.php';

		return FALSE;
	}

	public static function definedConfigPHP( $constant = 'WP_DEBUG' )
	{
		if ( ! $file = self::getConfigPHP() )
			return FALSE;

		$contents = file_get_contents( $file );
		$pattern  = "define\( ?'".$constant."'";
		$pattern  = "/^$pattern.*/m";

		if ( preg_match_all( $pattern, $contents, $matches ) )
			return TRUE;

		return FALSE;
	}
}

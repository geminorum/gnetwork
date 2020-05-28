<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class WordPress extends Base
{

	public static function isMinWPv( $minimum_version )
	{
		self::_dep( 'WordPress::isWPcompatible()' );
		return ( version_compare( $GLOBALS['wp_version'], $minimum_version ) >= 0 );
	}

	// Checks compatibility with the current WordPress version.
	// @REF: `is_wp_version_compatible()`
	public static function isWPcompatible( $required )
	{
		return empty( $required ) || version_compare( $GLOBALS['wp_version'], $required, '>=' );
	}

	// Checks compatibility with the current PHP version.
	// @REF: `wp_is_php_compatible()`
	public static function isPHPcompatible( $required )
	{
		return empty( $required ) || version_compare( phpversion(), $required, '>=' );
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
	// TODO: support arrays
	public static function pageNow( $page = NULL )
	{
		$now = 'index.php';

		if ( preg_match( '#([^/]+\.php)([?/].*?)?$#i', $_SERVER['PHP_SELF'], $matches ) )
			$now = strtolower( $matches[1] );

		return is_null( $page ) ? $now : ( $now == $page );
	}

	// @REF: https://core.trac.wordpress.org/ticket/19898
	public static function isLogin()
	{
		return Text::has( self::loginURL(), $_SERVER['SCRIPT_NAME'] );
	}

	// @REF: https://make.wordpress.org/core/2019/04/17/block-editor-detection-improvements-in-5-2/
	public static function isBlockEditor()
	{
		if ( ! function_exists( 'get_current_screen' ) )
			return FALSE;

		if ( ! $screen = get_current_screen() )
			return FALSE;

		if ( ! is_callable( [ $screen, 'is_block_editor' ] ) )
			return FALSE;

		return (bool) $screen->is_block_editor();
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

	public static function isFlush( $cap = 'publish_posts' )
	{
		if ( isset( $_GET['flush'] ) )
			return did_action( 'init' ) && current_user_can( $cap );

		return FALSE;
	}

	public static function isAJAX()
	{
		// return defined( 'DOING_AJAX' ) && DOING_AJAX;
		return wp_doing_ajax(); // @since WP 4.7.0
	}

	public static function isCRON()
	{
		// return defined( 'DOING_CRON' ) && DOING_CRON;
		return wp_doing_cron(); // @since WP 4.8.0
	}

	public static function isSSL()
	{
		return is_ssl();
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

	public static function isXML()
	{
		if ( function_exists( 'wp_is_xml_request' ) && wp_is_xml_request() )
			return TRUE;

		if ( ! isset( $GLOBALS['wp_query'] ) )
			return FALSE;

		if ( function_exists( 'is_feed' ) && is_feed() )
			return TRUE;

		if ( function_exists( 'is_comment_feed' ) && is_comment_feed() )
			return TRUE;

		if ( function_exists( 'is_trackback' ) && is_trackback() )
			return TRUE;

		return FALSE;
	}

	public static function doNotCache()
	{
		defined( 'DONOTCACHEPAGE' ) || define( 'DONOTCACHEPAGE', TRUE );
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

		if ( wp_redirect( $location, $status ) )
			exit;

		wp_die(); // something's wrong!
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

		$request = $wp->request
			? add_query_arg( array(), $wp->request )
			: add_query_arg( array() );

		$current = home_url( $request );

		return $trailingslashit ? URL::trail( $current ) : $current;
	}

	public static function getHostName()
	{
		return is_multisite() && function_exists( 'get_network' )
			? get_network()->domain
			: preg_replace( '#^https?://#i', '', get_option( 'home' ) );
	}

	// OLD: `getBlogNameforEmail()`
	public static function getSiteNameforEmail( $site = FALSE )
	{
		if ( ! $site && is_multisite() && function_exists( 'get_network' ) )
			return get_network()->site_name;

		// The blogname option is escaped with esc_html on the way into the database
		// in sanitize_option we want to reverse this for the plain text arena of emails.
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	// OLD: `currentBlog()`
	public static function currentSiteName( $slash = TRUE )
	{
		return URL::prepTitle( get_option( 'home' ), $slash );
	}

	// DEPRECATED
	public static function getSiteUserID( $fallback = FALSE )
	{
		self::_dep();
		return gNetwork()->user( $fallback );
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

	public static function getUsers( $all_fields = FALSE, $network = FALSE, $extra = array(), $rekey = 'ID' )
	{
		$users = get_users( array_merge( array(
			'blog_id' => ( $network ? '' : $GLOBALS['blog_id'] ),
			'orderby' => 'display_name',
			'fields'  => ( $all_fields ? 'all_with_meta' : 'all' ),
		), $extra ) );

		return Arraay::reKey( $users, $rekey );
	}

	// current user can
	public static function cuc( $cap, $none = TRUE )
	{
		if ( 'none' == $cap || '0' == $cap )
			return $none;

		if ( ! $logged_in = is_user_logged_in() )
			return FALSE;

		// pseudo-cap for network users
		if ( '_member_of_network' == $cap )
			return TRUE;

		// pseudo-cap for site users
		if ( '_member_of_site' == $cap )
			return is_user_member_of_blog() || self::isSuperAdmin();

		return current_user_can( $cap );
	}

	public static function cucTaxonomy( $taxonomy, $cap )
	{
		if ( ! $object = get_taxonomy( $taxonomy ) )
			return FALSE;

		return current_user_can( $object->cap->{$cap} );
	}

	// alt to `is_super_admin()`
	public static function isSuperAdmin( $user_id = FALSE )
	{
		$cap = is_multisite() ? 'manage_network' : 'manage_options';
		$can = $user_id ? user_can( $user_id, $cap ) : current_user_can( $cap );

		return (bool) $can;
	}

	public static function getSiteName( $blog_id, $switch = FALSE )
	{
		$name = FALSE;

		// WORKING BUT DISABLED!
		// if ( function_exists( 'bp_blogs_get_blogmeta' ) )
		// 	$name = bp_blogs_get_blogmeta( $blog_id, 'name', TRUE );

		if ( ! $name && function_exists( 'get_site_meta' ) )
			$name = get_site_meta( $blog_id, 'blogname', TRUE );

		if ( ! $name && $blog_id == get_current_blog_id() )
			return get_option( 'blogname' );

		if ( ! $name && $switch ) {

			switch_to_blog( $site_id );
			$name = get_option( 'blogname' );
			restore_current_blog();
		}

		return $name;
	}

	public static function getSiteURL( $blog_id, $switch = FALSE )
	{
		$url = FALSE;

		// WORKING BUT DISABLED!
		// if ( function_exists( 'bp_blogs_get_blogmeta' ) )
		// 	$url = bp_blogs_get_blogmeta( $blog_id, 'url', TRUE );

		if ( ! $url && function_exists( 'get_site_meta' ) )
			$url = get_site_meta( $blog_id, 'siteurl', TRUE );

		if ( ! $url && $blog_id == get_current_blog_id() )
			return get_option( 'siteurl' );

		if ( ! $url && $switch ) {

			switch_to_blog( $site_id );
			$url = get_option( 'siteurl' );
			restore_current_blog();
		}

		return $url;
	}

	// mocking `get_sites()` results
	public static function getAllSites( $user_id = FALSE, $network = NULL, $retrieve_url = TRUE, $orderby_path = FALSE )
	{
		global $wpdb;

		$clause_site = $clause_network = '';

		if ( $user_id ) {

			$ids = self::getUserSites( $user_id, $wpdb->base_prefix );

			// user has no sites!
			if ( ! $ids )
				return [];

			$clause_site = "AND blog_id IN ( '".join( "', '", esc_sql( $ids ) )."' )";
		}

		if ( TRUE !== $network )
			$clause_network = $wpdb->prepare( "AND site_id = %d", $network ?: get_current_network_id() );

		$clause_order = $orderby_path
			? 'ORDER BY domain, path ASC'
			: 'ORDER BY registered ASC';

		$query = "
			SELECT blog_id, site_id, domain, path
			FROM {$wpdb->blogs}
			WHERE spam = '0'
			AND deleted = '0'
			AND archived = '0'
			{$clause_network}
			{$clause_site}
			{$clause_order}
		";

		$blogs  = [];
		$scheme = self::isSSL() ? 'https' : 'http';

		foreach ( $wpdb->get_results( $query, ARRAY_A ) as $blog ) {

			$siteurl = FALSE;

			if ( $retrieve_url )
				$siteurl = self::getSiteURL( $blog['blog_id'] );

			if ( ! $siteurl )
				$siteurl = $scheme.'://'.$blog['domain'].$blog['path'];

			$blogs[$blog['blog_id']] = (object) [
				'userblog_id' => $blog['blog_id'],
				'network_id'  => $blog['site_id'],
				'domain'      => $blog['domain'],
				'path'        => $blog['path'],
				'siteurl'     => URL::untrail( $siteurl ),
			];
		}

		return $blogs;
	}

	// @REF: `get_blogs_of_user()`
	public static function getUserSites( $user_id, $prefix )
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
			$message = __( 'You don&#8217;t have permission to do this.' );

		wp_die( $message, 403 );
	}

	// FIXME: add to filter: 'search_link'
	public static function getSearchLink( $query = '', $url = FALSE, $query_id = GNETWORK_SEARCH_QUERYID )
	{
		if ( $url )
			return $query ? add_query_arg( $query_id, urlencode( $query ), $url ) : $url;

		if ( GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( $query_id, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return get_search_link( $query );
	}

	public static function getAdminPostLink( $action, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'action' => $action,
		), $extra ), admin_url( 'admin-post.php' ) );
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

	public static function getPostNewLink( $posttype, $extra = array() )
	{
		$args = 'post' == $posttype ? array() : array( 'post_type' => $posttype );

		return add_query_arg( array_merge( $args, $extra ), admin_url( 'post-new.php' ) );
	}

	public static function getUserEditLink( $user_id, $extra = array(), $network = FALSE, $check = TRUE )
	{
		if ( ! $user_id )
			return FALSE;

		if ( $check && ! current_user_can( 'edit_user', $user_id ) )
			return FALSE;

		return add_query_arg( array_merge( array(
			'user_id' => $user_id,
		), $extra ), $network
			? network_admin_url( 'user-edit.php' )
			: admin_url( 'user-edit.php' ) );

		return FALSE;
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

	// TODO: get title if html is empty
	public static function htmlAttachmentShortLink( $id, $html, $extra = '' )
	{
		return HTML::tag( 'a', [
			'href'  => self::getPostShortLink( $id ),
			'rel'   => 'attachment',
			'class' => HTML::attrClass( $extra, '-attachment' ),
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

		$pattern = "define\( ?'".$constant."'";
		$pattern = "/^$pattern.*/m";

		$contents = File::getContents( $file );

		if ( preg_match_all( $pattern, $contents, $matches ) )
			return TRUE;

		return FALSE;
	}

	// @REF: https://pippinsplugins.com/retrieve-attachment-id-from-image-url/
	public static function getAttachmentByURL( $url )
	{
		global $wpdb;

		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid='%s';", $url ) );

		return empty( $attachment ) ? NULL : $attachment[0];
	}

	// @REF: `is_plugin_active()`
	public static function isPluginActive( $plugin, $network_check = TRUE )
	{
		if ( in_array( $plugin, (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
			return TRUE;

		if ( $network_check && self::isPluginActiveForNetwork( $plugin ) )
			return TRUE;

		return FALSE;
	}

	// @REF: `is_plugin_active_for_network()`
	public static function isPluginActiveForNetwork( $plugin, $network = NULL )
	{
		if ( is_multisite() )
			return (bool) in_array( $plugin, (array) get_network_option( $network, 'active_sitewide_plugins' ) );

		return FALSE;
	}

	// `is_main_network()` with extra checks
	public static function isMainNetwork( $network_id = NULL )
	{
		// fallback
		if ( ! defined( 'GNETWORK_MAIN_NETWORK' ) )
			return is_main_network( $network_id );

		// every network is main network!
		if ( FALSE === GNETWORK_MAIN_NETWORK )
			return TRUE;

		if ( is_null( $network_id ) )
			$network_id = get_current_network_id();

		if ( GNETWORK_MAIN_NETWORK == $network_id )
			return TRUE;

		return FALSE;
	}

	// @REF: `network_admin_url()`
	// like core's but with custom network
	public static function networkAdminURL( $network = NULL, $path = '', $scheme = 'admin' )
	{
		if ( ! is_multisite() )
			return admin_url( $path, $scheme );

		$url = self::networkSiteURL( $network, 'wp-admin/network/', $scheme );

		if ( $path && is_string( $path ) )
			$url.= ltrim( $path, '/' );

		return apply_filters( 'network_admin_url', $url, $path );
	}

	// @REF: `user_admin_url()`
	// like core's but with custom network
	public static function userAdminURL( $network = NULL, $path = '', $scheme = 'admin' )
	{
		$url = self::networkSiteURL( $network, 'wp-admin/user/', $scheme );

		if ( $path && is_string( $path ) )
			$url.= ltrim( $path, '/' );

		return apply_filters( 'user_admin_url', $url, $path );
	}

	// @REF: `network_site_url()`
	// like core's but with custom network
	public static function networkSiteURL( $network = NULL, $path = '', $scheme = NULL )
	{
		if ( ! is_multisite() || ! function_exists( 'get_network' ) )
			return site_url( $path, $scheme );

		if ( ! $network )
			$network = get_network();

		if ( 'relative' == $scheme )
			$url = $network->path;

		else
			$url = set_url_scheme( 'http://'.$network->domain.$network->path, $scheme );

		if ( $path && is_string( $path ) )
			$url.= ltrim( $path, '/' );

		return apply_filters( 'network_site_url', $url, $path, $scheme );
	}

	// @REF: `network_home_url()`
	// like core's but with custom network
	public static function networkHomeURL( $network = NULL, $path = '', $scheme = NULL )
	{
		if ( ! is_multisite() || ! function_exists( 'get_network' ) )
			return home_url( $path, $scheme );

		if ( ! $network )
			$network = get_network();

		$original_scheme = $scheme;

		if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ) ) )
			$scheme = is_ssl() && ! is_admin() ? 'https' : 'http';

		if ( 'relative' == $scheme )
			$url = $network->path;

		else
			$url = set_url_scheme( 'http://'.$network->domain.$network->path, $scheme );

		if ( $path && is_string( $path ) )
			$url.= ltrim( $path, '/' );

		return apply_filters( 'network_home_url', $url, $path, $original_scheme );
	}

	// flush rewrite rules when it's necessary.
	// this could be put in an init hook or the like and ensures that
	// the rewrite rules option is only rewritten when the generated rules
	// don't match up with the option
	// @REF: https://gist.github.com/tott/9548734
	public static function maybeFlushRules( $flush = FALSE )
	{
		global $wp_rewrite;

		$list    = [];
		$missing = FALSE;

		foreach ( get_option( 'rewrite_rules' ) as $rule => $rewrite )
			$list[$rule]['rewrite'] = $rewrite;

		$list = array_reverse( $list, TRUE );

		foreach ( $wp_rewrite->rewrite_rules() as $rule => $rewrite ) {
			if ( ! array_key_exists( $rule, $list ) ) {
				$missing = TRUE;
				break;
			}
		}

		if ( $missing && $flush )
			flush_rewrite_rules();

		return $missing;
	}

	// @REF: `wp_get_users_with_no_role()`
	public static function getUsersWithNoRole( $site_id = NULL )
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
	public static function getUsersWithRole( $role, $site_id = NULL )
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
}

<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class IsIt extends Core\Base
{

	/**
	 * Checks compatibility with the current WordPress version.
	 * @source `is_wp_version_compatible()`
	 * @old: `Core\WordPress::isWPcompatible()`
	 *
	 * @param string $required
	 * @return bool
	 */
	public static function compatWP( $required )
	{
		return empty( $required ) || version_compare( get_bloginfo( 'version' ), $required, '>=' );
	}

	/**
	 * Checks compatibility with the current PHP version.
	 * @source `is_php_version_compatible()`
	 * @old: `Core\WordPress::isPHPcompatible()`
	 *
	 * @param string $required
	 * @return bool
	 */
	public static function compatPHP( $required )
	{
		return empty( $required ) || version_compare( PHP_VERSION, $required, '>=' );
	}

	/**
	 * Determine whether the current request is for an AMP page.
	 * @see https://amp-wp.org/reference/function/amp_is_request/
	 *
	 * This function cannot be called before the parse_query action because it
	 * needs to be able to determine the queried object is able to be served
	 * as AMP. If 'amp' theme support is not present, this function returns true
	 * just if the query var is present. If theme support is present, then it
	 * returns true in transitional mode if an AMP template is available and
	 * the query var is present, or else in standard mode if just the template
	 * is available.
	 *
	 * @return bool
	 */
	public static function amp()
	{
		if ( function_exists( 'amp_is_request' ) )
			return amp_is_request();

		// This function has been deprecated.
		if ( function_exists( 'is_amp_endpoint' ) )
			return is_amp_endpoint();

		return FALSE;
	}

	/**
	 * Determines whether the current request is for the customize preview screen.
	 * NOTE: wrapper for `is_customize_preview()` @since WP 4.0.0
	 *
	 * @return bool
	 */
	public static function customize()
	{
		return is_customize_preview();
	}

	/**
	 * Determines whether the current request is for the login screen.
	 * NOTE: `is_login()` @since WP 6.1.0
	 * @see https://core.trac.wordpress.org/ticket/19898
	 * @link https://make.wordpress.org/core/2022/09/11/new-is_login-function-for-determining-if-a-page-is-the-login-screen/
	 *
	 * @return bool
	 */
	public static function login()
	{
		return FALSE !== stripos( wp_login_url(), $_SERVER['SCRIPT_NAME'] );
	}

	// @REF: https://make.wordpress.org/core/2019/04/17/block-editor-detection-improvements-in-5-2/
	// @old: `Core\WordPress::isBlockEditor()`
	public static function blockEditor()
	{
		if ( ! function_exists( 'get_current_screen' ) )
			return FALSE;

		if ( ! $screen = get_current_screen() )
			return FALSE;

		if ( ! is_callable( [ $screen, 'is_block_editor' ] ) )
			return FALSE;

		return (bool) $screen->is_block_editor();
	}

	// @old: `Core\WordPress::isDebug()`
	public static function debug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::dev() )
			return TRUE;

		return FALSE;
	}

	// @REF: https://make.wordpress.org/core/2020/08/27/wordpress-environment-types/
	// @REF: https://make.wordpress.org/core/2023/07/14/configuring-development-mode-in-6-3/
	// NOTE: `wp_get_environment_type()` @since WP 5.5.0
	// NOTE: `wp_is_development_mode()` @since WP 6.3.0
	// @old: `Core\WordPress::isDev()`
	public static function dev()
	{
		if ( 'development' === self::const( 'WP_STAGE' ) )
			return TRUE;

		if ( function_exists( 'wp_get_environment_type' ) )
			return 'development' === wp_get_environment_type();

		// if ( function_exists( 'wp_is_development_mode' ) )
		// 	return wp_is_development_mode( 'all' );

		return FALSE;
	}

	// @old: `Core\WordPress::isFlush()`
	public static function flush( $cap = 'publish_posts', $key = 'flush' )
	{
		if ( $cap && isset( $_GET[$key] ) )
			return did_action( 'init' ) && ( TRUE === $cap || current_user_can( $cap ) );

		return FALSE;
	}

	// @old: `Core\WordPress::isAdminAJAX()`
	public static function ajaxAdmin()
	{
		return self::ajax() && FALSE !== strpos( wp_get_raw_referer(), '/wp-admin/' );
	}

	// @old: `Core\WordPress::isAJAX()`
	public static function ajax()
	{
		// return defined( 'DOING_AJAX' ) && DOING_AJAX;
		return wp_doing_ajax(); // @since WP 4.7.0
	}

	// @old: `Core\WordPress::isCRON()`
	public static function cron()
	{
		// return defined( 'DOING_CRON' ) && DOING_CRON;
		return wp_doing_cron(); // @since WP 4.8.0
	}

	// support if behind web proxy/balancer
	// @REF: https://developer.wordpress.org/reference/functions/is_ssl/#comment-4265
	// @old: `Core\WordPress::isSSL()`
	public static function ssl()
	{
		// `Cloudflare`
		if ( ! empty( $_SERVER['HTTP_CF_VISITOR'] ) ) {

			$visitor = json_decode( $_SERVER['HTTP_CF_VISITOR'] );

			if ( isset( $visitor->scheme )
				&& 'https' === $visitor->scheme )
					return TRUE;
		}

		// other proxy
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] )
			&& 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] )
				return TRUE;


		return function_exists( 'is_ssl' ) ? is_ssl() : FALSE;
	}

	// @old: `Core\WordPress::siteIsHTTPS()`
	public static function https()
	{
		if ( function_exists( 'wp_is_using_https' ) )
			return wp_is_using_https(); // @since WP 5.7.0

		return 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );  // @source: `wp_is_home_url_using_https()`
		// return FALSE   !== strstr( get_option( 'home' ), 'https:' );    // @source: `wc_site_is_https()`
	}

	// @old: `Core\WordPress::isImporting()`
	public static function importing()
	{
		return defined( 'WP_IMPORTING' ) && WP_IMPORTING;
	}

	// @old: `Core\WordPress::isExport()`
	public static function exporting()
	{
		if ( defined( 'GNETWORK_IS_WP_EXPORT' ) && GNETWORK_IS_WP_EXPORT )
			return TRUE;

		return FALSE;
	}

	// @old: `Core\WordPress::isCLI()`
	public static function cli()
	{
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	// @old: `Core\WordPress::isXMLRPC()`
	public static function xmlRPC()
	{
		return defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
	}

	/**
	 * Determines whether WordPress is currently serving a REST API request.
	 * @source `wp_is_serving_rest_request()`
	 * @old: `Core\WordPress::isREST()`
	 *
	 * @return bool
	 */
	public static function rest()
	{
		if ( function_exists( 'wp_is_serving_rest_request' ) )
			return wp_is_serving_rest_request(); // @since WP 6.5.0

		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Checks whether a REST API endpoint request is currently being handled.
	 *
	 * This maybe a standalone REST API request, or an internal request
	 * dispatched from within a regular page load.
	 *
	 * @source `wp_is_rest_endpoint()`
	 * @old: `Core\WordPress::isEndpointREST()`
	 *
	 * @return bool
	 */
	public static function restEndpoint()
	{
		if ( function_exists( 'wp_is_rest_endpoint' ) )
			return wp_is_rest_endpoint(); // @since WP 6.5.0

		return self::rest();
	}

	// @old: `Core\WordPress::isIFrame()`
	public static function iFrame()
	{
		return defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST;
	}

	// @old: `Core\WordPress::isXML()`
	public static function xml()
	{
		// NOTE: known conflict with caching plugins
		// if ( function_exists( 'wp_is_xml_request' ) && wp_is_xml_request() )
		// 	return TRUE;

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

	// including `WP_CACHE` with a value of `true` loads `advanced-cache.php`.
	// `Object-cache.php` is loaded and used automatically.
	// @old: `Core\WordPress::isAdvancedCache()`
	public static function advancedCache()
	{
		return defined( 'WP_CACHE' ) && WP_CACHE;
	}

	/**
	 * Test if the current browser runs on a mobile device (smart phone, tablet, etc.).
	 *
	 * Whilst caching issues are mentioned in more information for
	 * this function it cannot be restated enough that any page caching,
	 * which does not split itself into mobile and non-mobile buckets,
	 * will break this function. If your page caching is global and a
	 * desktop device triggers a refresh, the return of this function
	 * will always be FALSE until the next refresh. Likewise if a mobile
	 * device triggers the refresh, the return will always be TRUE.
	 * IF you expect the result of this function to change on a peruser
	 * basis, ensure that you have considered how caching will affect your code.
	 * @source https://developer.wordpress.org/reference/functions/wp_is_mobile/
	 *
	 * @return bool
	 */
	public static function mobile()
	{
		if ( \function_exists( 'wp_is_mobile' ) )
			return wp_is_mobile();

		if ( isset( $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ) )
			return '?1' === $_SERVER['HTTP_SEC_CH_UA_MOBILE'];

		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) )
			return FALSE;

		$agents = [
			'Mobile',
			'Android',
			'Silk/',
			'Kindle',
			'BlackBerry',
			'Opera Mini',
			'Opera Mobi',
		];

		foreach ( $agents as $agent )
			if ( FALSE !== strpos( $_SERVER['HTTP_USER_AGENT'], $agent ) )
				return TRUE;

		return FALSE;
	}

	public static function sitemap()
	{
		return FALSE !== strpos( $_SERVER[REQUEST_URI], 'wp-sitemap' );
	}

	// `is_main_network()` with extra checks
	// @old: `Core\WordPress::isMainNetwork()`
	public static function mainNetwork( $network_id = NULL )
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

	public static function contentHasPages()
	{
		global $pages;

		if ( empty( $pages ) )
			return FALSE;

		$count = is_array( $pages ) ? count( $pages ) : 0;

		if ( is_array( $pages ) && $count < 2 )
			return FALSE;

		return $count;
	}

	public static function contentCurrentPage()
	{
		global $page;

		if ( empty( $page ) )
			return 1;

		return (int) $page;
	}

	public static function contentFirstPage()
	{
		global $page;

		if ( empty( $page ) )
			return TRUE;

		return 1 === ( (int) $page );
	}

	public static function contentLastPage()
	{
		global $page;

		if ( empty( $page ) )
			return TRUE;

		if ( ! $pages = self::contentHasPages() )
			return TRUE;

		return $page === $pages;
	}
}

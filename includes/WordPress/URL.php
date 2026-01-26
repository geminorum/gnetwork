<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class URL extends Core\Base
{
	/**
	 * Retrieves the current request URL.
	 * @see: `wp_guess_url()`
	 * OLD: `Core\WordPress::currentURL()`
	 * BETTER: `Core\URL::current()`
	 *
	 * @global object $wp
	 * @param bool $trailing_slash_it
	 * @return string
	 */
	public static function current( $trailing_slash_it = FALSE )
	{
		global $wp;

		$request = $wp->request
			? add_query_arg( [], $wp->request )
			: add_query_arg( [] );

		$current = home_url( $request );

		return $trailing_slash_it ? trailingslashit( $current ) : $current;
	}

	/**
	 * Retrieves the login URL.
	 * NOTE: wrapper for `wp_login_url()`
	 * OLD: `Core\WordPress::loginURL()`
	 *
	 * @param string $redirect
	 * @param bool $force_reauth
	 * @return string
	 */
	public static function login( $redirect = '', $force_reauth = FALSE )
	{
		return wp_login_url( $redirect, $force_reauth );
	}

	/**
	 * Returns the URL that allows the user to register on the site.
	 * OLD: `Core\WordPress::registerURL()`
	 *
	 * @param bool $custom
	 * @return string
	 */
	public static function register( $custom = FALSE )
	{
		if ( function_exists( 'buddypress' ) ) {

			if ( bp_get_signup_allowed() )
				return bp_get_signup_page();

		} else if ( get_option( 'users_can_register' ) ) {

			if ( is_multisite() )
				return apply_filters( 'wp_signup_location', network_site_url( 'wp-signup.php' ) );
			else
				return wp_registration_url();

		} else if ( 'site' === $custom ) {

			return site_url( '/' );
		}

		return $custom;
	}

	// OLD: `Core\WordPress::getAdminPageLink()`
	public static function admin( $page, $extra = [], $base = NULL )
	{
		return add_query_arg( array_merge( [ 'page' => $page ], $extra ), admin_url( $base ?? 'admin.php' ) );
	}

	/**
	 * Returns the URL of WordPress generic request (POST/GET) handler.
	 * OLD: `Core\WordPress::getAdminPostLink()`
	 *
	 * @param string $action
	 * @param array $extra
	 * @return string
	 */
	public static function adminPOST( $action, $extra = [] )
	{
		return add_query_arg( array_merge( [ 'action' => $action ], $extra ), admin_url( 'admin-post.php' ) );
	}

	public static function searchAdminTerm( $criteria, $taxonomy, $extra = [] )
	{
		if ( ! Taxonomy::can( $taxonomy, 'manage_terms' ) )
			return FALSE;

		return add_query_arg( array_merge( [
			'taxonomy' => $taxonomy,
			's'        => $criteria,
		], $extra ), admin_url( 'edit-tags.php' ) );
	}

	// OLD: `Core\WordPress::getAdminSearchLink()`
	public static function searchAdmin( $criteria = FALSE, $posttype = NULL, $extra = [] )
	{
		$query = [ 's' => $criteria ];

		if ( PostType::can( $posttype, 'read' ) )
			$query['post_type'] = $posttype;

		return add_query_arg( array_merge( $query, $extra ), admin_url( 'edit.php' ) );
	}

	// TODO: add to filter: 'search_link'
	// OLD: `Core\WordPress::getSearchLink()`
	public static function search( $query = FALSE, $url = FALSE, $query_id = 's' )
	{
		if ( $url )
			return $query ? add_query_arg( $query_id, urlencode( $query ), $url ) : $url;

		if ( defined( 'GNETWORK_SEARCH_REDIRECT' ) && GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( GNETWORK_SEARCH_QUERYID, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return get_search_link( $query );
	}

	// NOTE: does not any checks!
	// @SEE: `WordPress\PostType::edit()`
	public static function editPostType( $posttype = 'post', $extra = [] )
	{
		if ( $posttype instanceof \WP_Post_Type )
			$posttype = $posttype->name;

		$args = 'post' === $posttype ? [] : [
			'post_type' => $posttype,
		];

		return add_query_arg( array_merge( $args, $extra ), admin_url( 'edit.php' ) );
	}

	// NOTE: does not any checks!
	// @SEE: `WordPress\Taxonomy::edit()`
	public static function editTaxonomy( $taxonomy = 'category', $extra = [] )
	{
		if ( $taxonomy instanceof \WP_Taxonomy )
			$taxonomy = $taxonomy->name;

		$args = [ 'taxonomy' => $taxonomy ];

		return add_query_arg( array_merge( $args, $extra ), admin_url( 'edit-tags.php' ) );
	}

	// @REF: `network_admin_url()`
	// like core's but with custom network
	// OLD: `Core\WordPress::networkAdminURL()`
	public static function networkAdmin( $network = NULL, $path = '', $scheme = 'admin' )
	{
		if ( ! is_multisite() )
			return admin_url( $path, $scheme );

		$url = self::networkSite( $network, 'wp-admin/network/', $scheme );

		if ( $path && is_string( $path ) )
			$url.= ltrim( $path, '/' );

		return apply_filters( 'network_admin_url', $url, $path );
	}

	// @REF: `user_admin_url()`
	// like core's but with custom network
	// OLD: `Core\WordPress::userAdminURL()`
	public static function userAdmin( $network = NULL, $path = '', $scheme = 'admin' )
	{
		$url = self::networkSite( $network, 'wp-admin/user/', $scheme );

		if ( $path && is_string( $path ) )
			$url.= ltrim( $path, '/' );

		return apply_filters( 'user_admin_url', $url, $path );
	}

	// @REF: `network_site_url()`
	// like core's but with custom network
	// OLD: `Core\WordPress::networkSiteURL()`
	public static function networkSite( $network = NULL, $path = '', $scheme = NULL )
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
	// OLD: `Core\WordPress::networkHomeURL()`
	public static function networkHome( $network = NULL, $path = '', $scheme = NULL )
	{
		if ( ! is_multisite() || ! function_exists( 'get_network' ) )
			return home_url( $path, $scheme );

		if ( ! $network )
			$network = get_network();

		$original_scheme = $scheme;

		if ( ! in_array( $scheme, [ 'http', 'https', 'relative' ], TRUE ) )
			$scheme = is_ssl() && ! is_admin() ? 'https' : 'http';

		if ( 'relative' == $scheme )
			$url = $network->path;

		else
			$url = set_url_scheme( 'http://'.$network->domain.$network->path, $scheme );

		if ( $path && is_string( $path ) )
			$url.= ltrim( $path, '/' );

		return apply_filters( 'network_home_url', $url, $path, $original_scheme );
	}

	/**
	 * Flushes rewrite rules when it's necessary.
	 * This could be put in an init hook or the like and ensures that
	 * the rewrite rules option is only rewritten when the generated rules
	 * don't match up with the option.
	 * @source https://gist.github.com/tott/9548734
	 * OLD: `Core\WordPress::maybeFlushRules()`
	 *
	 * @param bool $flush
	 * @return bool
	 */
	public static function maybeFlushRules( $flush = FALSE )
	{
		global $wp_rewrite;

		$list    = [];
		$missing = FALSE;

		foreach ( get_option( 'rewrite_rules', [] ) as $rule => $rewrite )
			$list[$rule]['rewrite'] = $rewrite;

		$list = array_reverse( $list, TRUE );

		foreach ( $wp_rewrite->rewrite_rules() as $rule => $rewrite ) {
			if ( ! array_key_exists( $rule, $list ) ) {
				$missing = TRUE;
				break;
			}
		}

		if ( $missing && $flush ) {
			flush_rewrite_rules();
			wp_cache_delete( 'rewrite_rules', 'options' );
		}

		return $missing;
	}
}

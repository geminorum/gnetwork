<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Screen extends Core\Base
{
	// OLD: `Core\WordPress::mustRegisterUI()`
	public static function mustRegisterUI( bool $check_admin = TRUE ): bool
	{
		if ( IsIt::ajax()
			|| IsIt::cli()
			|| IsIt::cron()
			|| IsIt::xmlRPC()
			|| IsIt::rest() )
				return FALSE;

		if ( $check_admin && ! is_admin() )
			return FALSE;

		return TRUE;
	}

	// @REF: `vars.php`
	// OLD: `Core\WordPress::pageNow()`
	public static function pageNow( ?string $page = NULL ): bool|string
	{
		$now = 'index.php';

		if ( preg_match( '#([^/]+\.php)([?/].*?)?$#i', $_SERVER['PHP_SELF'], $matches ) )
			$now = strtolower( $matches[1] );

		if ( is_null( $page ) )
			return $now;

		return in_array( $now, (array) $page, TRUE );
	}

	public static function isPosttype( string $posttype, ?object $screen = NULL ): bool
	{
		if ( empty( $posttype ) )
			return FALSE;

		if ( ! $screen = $screen ?? get_current_screen() )
			return FALSE;

		if ( $posttype instanceof \WP_Post_Type )
			$posttype = $posttype->name;

		if ( $screen->post_type !== $posttype )
			return FALSE;

		// MAYBE: check for id/base

		return TRUE;
	}

	public static function isTaxonomy( string $taxonomy, ?object $screen = NULL ): bool
	{
		if ( empty( $taxonomy ) )
			return FALSE;

		if ( ! $screen = $screen ?? get_current_screen() )
			return FALSE;

		if ( $taxonomy instanceof \WP_Taxonomy )
			$taxonomy = $taxonomy->name;

		if ( $screen->taxonomy !== $taxonomy )
			return FALSE;

		// MAYBE: check for id/base

		return TRUE;
	}

	/**
	 * Retrieves the markup for an accessible tooltip.
	 * NOTE: wrapper for `wp_get_tooltip()`
	 *
	 * The required CSS for tooltips is loaded globally, but the JavaScript is
	 * only loaded by default where post meta boxes are used and in the login screen.
	 * @link https://make.wordpress.org/core/2026/08/03/introducing-name-and-informational-tool-tips-in-wordpress-7-1/
	 * @ticket https://core.trac.wordpress.org/ticket/55343
	 *
	 * @param string $content
	 * @param array $arguments
	 * @return string
	 */
	public static function tooltip( string $content, array $arguments = [] ): string
	{
		if ( function_exists( 'wp_get_tooltip' ) )  // @since WP 7.1.0
			return wp_get_tooltip( $content, $arguments );

		// TODO: WTF: fallback!

		return $content;
	}

	/**
	 * Retrieves the markup for an accessible toggle tip.
	 * NOTE: wrapper for `wp_get_toggletip()`
	 *
	 * @param string $content
	 * @param array $arguments
	 * @return string
	 */
	public static function toggletip( string $content, array $arguments = [] ): string
	{
		if ( function_exists( 'wp_get_toggletip' ) )  // @since WP 7.1.0
			return wp_get_toggletip( $content, $arguments );

		// TODO: WTF: fallback!

		return $content;
	}
}

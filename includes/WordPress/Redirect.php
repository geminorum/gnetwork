<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Redirect extends Core\Base
{
	/**
	 * Sanitizes a URL for use in a redirect.
	 * NOTE: wrapper for `wp_sanitize_redirect()`
	 *
	 * @param bool|string $location
	 * @return bool|string
	 */
	public static function sanitize( bool|string|null $location ): bool|string
	{
		return wp_sanitize_redirect( $location ?? self::getReferer() );
	}

	// @REF: `wp_referer_field()`
	public static function fieldReferer(): void
	{
		Core\HTML::inputHidden( '_wp_http_referer', self::unslash( remove_query_arg( [
			'_wp_http_referer',
			'message',
			'action',
			// 'paged',
			'count',
		] ) ) );
	}

	// @see `wp_removable_query_args()`
	// wrapper for `wp_get_referer()`
	public static function getReferer(): string
	{
		return remove_query_arg( [
			'_wp_http_referer',
			'message',
			'action',
			// 'paged',
			'count',
		], wp_get_referer() );
	}

	// OLD: `Core\WordPress::redirectJS()`
	public static function doJS( $location = NULL, $timeout = 3000 ): true
	{
		?><script>
function redirect_to_another_page() {
	location.href = "<?php echo self::sanitize( $location ); ?>";
}
setTimeout( "redirect_to_another_page()", <?php echo $timeout; ?> );
</script><?php

		return TRUE; // to help the caller
	}

	// OLD: `Core\WordPress::redirect()`
	public static function doWP( $location = NULL, $status = 302 ): true
	{
		if ( wp_redirect( $location ?? self::getReferer(), $status ) ) // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;

		wp_die(); // something's wrong!

		return TRUE; // for type decolarations only!
	}

	public static function doRefererWithLog( $log, $message = 'updated', $key = 'message' ): true
	{
		self::_log_error( $log );
		return self::doReferer( $message, $key );
	}

	// OLD: `Core\WordPress::redirectReferer()`
	public static function doReferer( $message = 'updated', $key = 'message' ): true
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, self::getReferer() );
		else
			$url = add_query_arg( $key, $message, self::getReferer() );

		return self::doWP( $url );
	}

	// OLD: `Core\WordPress::redirectURL()`
	public static function doURL( $location, $message = 'updated', $key = 'message' ): true
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, $location );
		else
			$url = add_query_arg( $key, $message, $location );

		return self::doWP( $url );
	}

	// OLD: `Core\WordPress::redirectLogin()`
	public static function doLogin( $location = '', $status = 302 ): true
	{
		return self::doWP( wp_login_url( $location, TRUE ), $status );
	}
}

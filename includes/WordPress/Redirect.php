<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Redirect extends Core\Base
{
	// @REF: `wp_referer_field()`
	public static function fieldReferer()
	{
		Core\HTML::inputHidden( '_wp_http_referer', self::unslash( remove_query_arg( [
			'_wp_http_referer',
			'message',
			'action',
			// 'paged',
			'count',
		] ) ) );
	}

	// wrapper for `wp_get_referer()`
	public static function getReferer()
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
	public static function doJS( $location = NULL, $timeout = 3000 )
	{
		?><script>
function nextpage() {
	location.href = "<?php echo ( $location ?? self::getReferer() ); ?>";
}
setTimeout( "nextpage()", <?php echo $timeout; ?> );
</script><?php

		return TRUE; // to help the caller
	}

	// OLD: `Core\WordPress::redirect()`
	public static function doWP( $location = NULL, $status = 302 )
	{
		if ( wp_redirect( $location ?? self::getReferer(), $status ) )
			exit;

		wp_die(); // something's wrong!
	}

	// OLD: `Core\WordPress::redirectReferer()`
	public static function doReferer( $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, self::getReferer() );
		else
			$url = add_query_arg( $key, $message, self::getReferer() );

		self::doWP( $url );
	}

	// OLD: `Core\WordPress::redirectURL()`
	public static function doURL( $location, $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, $location );
		else
			$url = add_query_arg( $key, $message, $location );

		self::doWP( $url );
	}

	// OLD: `Core\WordPress::redirectLogin()`
	public static function doLogin( $location = '', $status = 302 )
	{
		self::doWP( wp_login_url( $location, TRUE ), $status );
	}
}

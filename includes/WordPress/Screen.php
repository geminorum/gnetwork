<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Screen extends Core\Base
{
	// OLD: `Core\WordPress::mustRegisterUI()`
	public static function mustRegisterUI( $check_admin = TRUE )
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
	public static function pageNow( $page = NULL )
	{
		$now = 'index.php';

		if ( preg_match( '#([^/]+\.php)([?/].*?)?$#i', $_SERVER['PHP_SELF'], $matches ) )
			$now = strtolower( $matches[1] );

		if ( is_null( $page ) )
			return $now;

		return in_array( $now, (array) $page, TRUE );
	}
}

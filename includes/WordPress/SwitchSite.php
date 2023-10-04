<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class SwitchSite extends Core\Base
{

	/**
	 * Wraps core function for `switch_to_blog()`
	 *
	 * @param  int  $site_id
	 * @return bool $switched
	 */
	public static function to( $site_id )
	{
		return switch_to_blog( $site_id );
	}

	/**
	 * Wraps core function for `restore_current_blog()`
	 *
	 * @return bool $switched
	 */
	public static function restore()
	{
		return restore_current_blog();
	}

	// @REF: `ms_is_switched()`
	public static function is()
	{
		return ! empty( $GLOBALS['_wp_switched_stack'] );
	}

	/**
	 * When calling `switch_to_blog()` repeatedly, either call `restore_current_blog()`
	 * each time, or save the original blog ID until the end and call `switch_to_blog()`
	 * with that and do this.
	 * @see https://wordpress.stackexchange.com/a/123516
	 *
	 * @return void
	 */
	public static function lap()
	{
		$GLOBALS['_wp_switched_stack'] = [];
		$GLOBALS['switched']           = FALSE;
	}
}

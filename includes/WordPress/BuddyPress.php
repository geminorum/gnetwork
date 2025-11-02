<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class BuddyPress extends Core\Base
{

	const PLUGIN = 'buddypress/bp-loader.php';

	public static function isActive()
	{
		return Extend::isPluginActive( static::PLUGIN );
	}
}

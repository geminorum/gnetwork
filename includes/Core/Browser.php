<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Browser extends Base
{

	public static function getAgent()
	{
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) )
			return FALSE;

		return htmlentities( $_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8' );
	}

	// @REF: https://wp-mix.com/php-detect-all-versions-ie/
	public static function isIE()
	{
		if ( ! $agent = self::getAgent() )
			return FALSE;

		if ( preg_match( '~MSIE|Internet Explorer~i', $agent ) )
			return TRUE;

		if ( Text::has( $agent, [ 'Trident/7.0', 'rv:11.0' ], 'AND' ) )
			return TRUE;

		return FALSE;
	}

	// @REF: `wp_is_mobile()`
	public static function isMobile()
	{
		if ( ! $agent = self::getAgent() )
			return FALSE;

		$needles = [
			'Mobile', // many mobile devices (all iPhone, iPad, etc.)
			'Android',
			'Silk/',
			'Kindle',
			'BlackBerry',
			'Opera Mobi',
		];

		if ( Text::has( $agent, $needles ) )
			return TRUE;

		return FALSE;
	}
}

<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class L10n extends Base
{

	public static function getNooped( $singular, $plural )
	{
		return array( 'singular' => $singular, 'plural' => $plural, 'context' => NULL, 'domain' => NULL );
	}

	public static function sprintfNooped( $nooped, $count )
	{
		return sprintf( translate_nooped_plural( $nooped, $count ), Number::format( $count ) );
	}
}

<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Crypto extends Base
{

	// @REF: https://github.com/kasparsd/numeric-shortlinks
	const BIJECTION_DIC  = 'abcdefghijklmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789';
	const BIJECTION_BASE = 57; // strlen( BIJECTION_DIC )

	public static function encodeBijection( $id )
	{
		$slug = array();
		$dic  = static::BIJECTION_DIC;

		while ( $id > 0 ) {

			$key = $id % static::BIJECTION_BASE;

			$slug[] = $dic[$key];

			$id = floor( $id / static::BIJECTION_BASE );
		}

		return implode( '', array_reverse( $slug ) );
	}

	public static function decodeBijection( $slug )
	{
		$id  = 0;
		$dic = static::BIJECTION_DIC;

		foreach ( str_split( trim( $slug ) ) as $char ) {

			$pos = strpos( $dic, $char );

			if ( FALSE === $pos )
				return $slug;

			$id = $id * static::BIJECTION_BASE + $pos;
		}

		return $id ?: $slug;
	}
}

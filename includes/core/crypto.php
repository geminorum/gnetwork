<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Crypto extends Base
{

	// @REF: https://github.com/kasparsd/numeric-shortlinks
	const BIJECTION_DIC  = 'abcdefghijklmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789';
	const BIJECTION_BASE = 57; // strlen( BIJECTION_DIC )

	public function encodeBijection( $id )
	{
		$slug = array();

		while ( $id > 0 ) {

			$slug[] = static::BIJECTION_DIC[$id % static::BIJECTION_BASE];

			$id = floor( $id / static::BIJECTION_BASE );
		}

		return implode( '', array_reverse( $slug ) );
	}

	public function decodeBijection( $slug )
	{
		$id = 0;

		foreach ( str_split( $slug ) as $char ) {

			if ( FALSE === ( $pos = strpos( static::BIJECTION_DIC, $char ) ) )
				return $slug;

			$id = $id * static::BIJECTION_BASE + $pos;
		}

		return $id ?: $slug;
	}
}

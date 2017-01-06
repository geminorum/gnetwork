<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Number extends Base
{

	// FIXME: use our own
	public static function format( $number, $decimals = 0, $locale = NULL )
	{
		return apply_filters( 'number_format_i18n', $number );
	}

	// @SOURCE: WP's `zeroise()`
	public static function zeroise( $number, $threshold, $locale = NULL )
	{
		return sprintf( '%0'.$threshold.'s', $number );
	}

	public static $readable_suffix = array(
		'trillion' => '%s trillion',
		'billion'  => '%s billion',
		'million'  => '%s million',
		'thousand' => '%s thousand',
	);

	// @REF: http://php.net/manual/en/function.number-format.php#89888
	public static function formatReadable( $number, $suffix = NULL )
	{
		// strip any formatting;
		$number = ( 0 + str_replace( ',', '', $number ) );

		if ( ! is_numeric( $number ) )
			return FALSE;

		if ( is_null( $suffix ) )
			$suffix = self::$readable_suffix;

		if ( $number > 1000000000000 )
			return sprintf( $suffix['trillion'], round( ( $number / 1000000000000 ), 1 ) );

		else if ( $number > 1000000000 )
			return sprintf( $suffix['billion'], round( ( $number / 1000000000 ), 1 ) );

		else if ( $number > 1000000 )
			return sprintf( $suffix['million'], round( ( $number / 1000000 ), 1 ) );

		else if ( $number > 1000 )
			return sprintf( $suffix['thousand'], round( ( $number / 1000 ), 1 ) );

		return self::format( $number );
	}

	// @REF: http://php.net/manual/en/function.number-format.php#89655
	public static function formatOrdinal_en( $number )
	{
		// special case "teenth"
		if ( ( $number / 10 ) % 10 != 1 ) {

			// handle 1st, 2nd, 3rd
			switch( $number % 10 ) {
				case 1: return $number.'st';
				case 2: return $number.'nd';
				case 3: return $number.'rd';
			}
		}

		// everything else is "nth"
		return $number.'th';
	}
}

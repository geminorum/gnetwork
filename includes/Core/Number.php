<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Number extends Base
{

	public static function localize( $number )
	{
		return apply_filters( 'number_format_i18n', $number );
	}

	public static function format( $number, $decimals = 0, $locale = NULL )
	{
		return apply_filters( 'number_format_i18n', number_format( $number, absint( $decimals ) ), $number, $decimals );
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function intval( $text, $force = TRUE )
	{
		$number = apply_filters( 'string_format_i18n_back', $text );

		return $force ? intval( $number ) : $number;
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function floatval( $text, $force = TRUE )
	{
		$number = apply_filters( 'string_format_i18n_back', $text );

		return $force ? floatval( $number ) : $number;
	}

	// never let a numeric value be less than zero.
	// @SOURCE: `bbp_number_not_negative()`
	public static function notNegative( $number )
	{
		if ( is_string( $number ) ) {

			// protect against formatted strings
			$number = strip_tags( $number ); // no HTML
			$number = preg_replace( '/[^0-9-]/', '', $number ); // no number-format

		} else if ( ! is_numeric( $number ) ) {

			// protect against objects, arrays, scalars, etc...
			$number = 0;
		}

		// make the number an integer
		$int = intval( $number );

		// pick the maximum value, never less than zero
		$not_less_than_zero = max( 0, $int );

		return $not_less_than_zero;
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
			switch ( $number % 10 ) {

				case 1: return $number.'st';
				case 2: return $number.'nd';
				case 3: return $number.'rd';
			}
		}

		// everything else is "nth"
		return $number.'th';
	}
}

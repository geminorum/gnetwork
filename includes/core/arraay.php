<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Arraay extends Base
{

	// deep array_filter()
	public static function filterArray( $input, $callback = NULL )
	{
		foreach ( $input as &$value )
			if ( is_array( $value ) )
				$value = self::filterArray( $value, $callback );

		return $callback ? array_filter( $input, $callback ) : array_filter( $input );
	}

	public static function roundArray( $array, $precision = -3, $mode = PHP_ROUND_HALF_UP )
	{
		$rounded = array();

		foreach( (array) $array as $key => $value )
			$rounded[$key] = round( (float) $value, $precision, $mode );

		return $rounded;
	}

	public static function reKey( $list, $key )
	{
		if ( ! empty( $list ) ) {
			$ids  = wp_list_pluck( $list, $key );
			$list = array_combine( $ids, $list );
		}

		return $list;
	}

	public static function sameKey( $old )
	{
		$new = array();

		foreach ( $old as $key => $value )
			$new[$value] = $value;

		return $new;
	}

	public static function getKeys( $options, $if = TRUE )
	{
		$keys = array();

		foreach ( (array) $options as $key => $value )
			if ( $value == $if )
				$keys[] = $key;

		return $keys;
	}

	public static function range( $start, $end, $step = 1, $format = TRUE )
	{
		$array = array();

		foreach ( range( $start, $end, $step ) as $number )
			$array[$number] = $format ? Number::format( $number ) : $number;

		return $array;
	}

	// for useing with $('form').serializeArray();
	// http://api.jquery.com/serializeArray/
	public static function parseJSArray( $array )
	{
		$parsed = array();
		foreach ( $array as $part )
			$parsed[$part['name']] = $part['value'];
		return $parsed;
	}

	public static function strposArray( $needles, $haystack )
	{
		foreach ( (array) $needles as $key => $needle )
			if ( FALSE !== strpos( $haystack, $needle ) )
				return $key;

		return FALSE;
	}

	public static function stripDefaults( $atts, $defaults = array() )
	{
		foreach ( $defaults as $key => $value )
			if ( isset( $atts[$key] ) && $value === $atts[$key] )
				unset( $atts[$key] );

		return $atts;
	}
}

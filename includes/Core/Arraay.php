<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Arraay extends Base
{

	public static function prepString()
	{
		$args = func_get_args();

		return empty( $args ) ? [] : array_unique( array_filter( array_map( 'trim', array_merge( ...$args ) ) ) );
	}

	public static function prepNumeral()
	{
		$args = func_get_args();

		return empty( $args ) ? [] : array_unique( array_filter( array_map( 'intval', array_merge( ...$args ) ) ) );
	}

	public static function prepSplitters( $string, $default = '|' )
	{
		return empty( $string ) ? [ $default ] : self::prepString( preg_split( '//u', $string, -1, PREG_SPLIT_NO_EMPTY ), [ $default ] );
	}

	// deep array_filter()
	public static function filterArray( $input, $callback = NULL )
	{
		foreach ( $input as &$value )
			if ( is_array( $value ) )
				$value = self::filterArray( $value, $callback );

		return $callback ? array_filter( $input, $callback ) : array_filter( $input );
	}

	// @REF: https://stackoverflow.com/a/28115783
	// php 5.3
	public static function prefixValues( $array, $prefix )
	{
		return preg_filter( '/^/', $prefix, $array );
	}

	public static function roundArray( $array, $precision = -3, $mode = PHP_ROUND_HALF_UP )
	{
		$rounded = array();

		foreach ( (array) $array as $key => $value )
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

	// OR: `array_combine( $array, $array );`
	public static function sameKey( $old )
	{
		$new = array();

		foreach ( (array) $old as $key => $value )
			if ( FALSE !== $value && NULL !== $value )
				$new[$value] = $value;

		return $new;
	}

	// USE: `array_keys()` on posted checkboxes
	public static function getKeys( $options, $if = TRUE )
	{
		$keys = array();

		foreach ( (array) $options as $key => $value )
			if ( $value == $if )
				$keys[] = $key;

		return $keys;
	}

	// @SOURCE: http://stackoverflow.com/a/24436324
	// USEAGE: Arraay::replaceKeys( $array, array( 'old_key_1' => 'new_key_1', 'old_key_2' => 'new_key_2' ) );
	public static function replaceKeys( $array, $keys_map )
	{
		$keys = array_keys( $array );

		foreach ( $keys_map as $old_key => $new_key ) {

			if ( FALSE === $index = array_search( $old_key, $keys ) )
				continue;

			$keys[$index] = $new_key;
		}

		return array_combine( $keys, array_values( $array ) );
	}

	// @REF: https://stackoverflow.com/a/6252803
	public static function equalKeys( $one, $two )
	{
		return ! array_diff_key( $one, $two ) && ! array_diff_key( $two, $one );
	}

	// `$a == $b` TRUE if $a and $b have the same key/value pairs
	// `$a === $b` TRUE if $a and $b have the same key/value pairs in the same order and of the same types
	// @REF: https://stackoverflow.com/a/5678990
	public static function equalAssoc( $one, $two )
	{
		return ( $one == $two );
	}

	public static function equalNoneAssoc( $one, $two )
	{
		// return ( [] == array_diff( $one, $two ) && [] == array_diff( $two, $one) ); // @REF: https://stackoverflow.com/a/57330018
		return ( $one === array_intersect( $one, $two ) && $two === array_intersect( $two, $one ) ); // @REF: https://stackoverflow.com/a/32811051
	}

	public static function range( $start, $end, $step = 1, $format = TRUE )
	{
		$array = array();

		foreach ( range( $start, $end, $step ) as $number )
			$array[$number] = $format ? Number::format( $number ) : $number;

		return $array;
	}

	// for using with $('form').serializeArray();
	// @REF: http://api.jquery.com/serializeArray/
	// @INPUT: [{name:"a",value:"1"},{name:"b",value:"2"}]
	// @OLD: `parseJSArray()`
	public static function parseSerialized( $array )
	{
		$parsed = array();

		foreach ( $array as $part )
			$parsed[$part['name']] = $part['value'];

		return $parsed;
	}

	// FIXME: TEST THIS!
	// array map, but maps values to new keys instead of new values
	// @REF: https://gist.github.com/abiusx/4ed90007ca693802cc7a56446cfd9394
	// @SEE: https://ryanwinchester.ca/posts/php-array-map-with-keys
	public static function mapKeys( $callback, $array )
	{
		return array_reduce( $array, static function ( $key, $value ) use ( $array, $callback ) {
			return [ call_user_func( $callback, $key, $value ) => $value ];
		} );
	}

	// array map for keys, php > 5.6
	// @SEE: https://stackoverflow.com/a/43004994
	public static function mapAssoc( $callback, $array )
	{
		return array_merge( ...array_map( $callback, array_keys( $array ), $array ) );
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

	// @REF: http://stackoverflow.com/a/11026840#comment44080768_11026840
	public static function stripByValue( $array, $value )
	{
		return array_diff_key( $array, array_flip( array_keys( $array, $value ) ) );
	}

	//@RF: https://stackoverflow.com/a/11026840
	public static function stripByKeys( $array, $keys )
	{
		return array_diff_key( $array, array_flip( $keys ) );
	}

	// @REF: https://stackoverflow.com/a/34575007
	public static function keepByKeys( $array, $keys )
	{
		return array_intersect_key( $array, array_flip( $keys ) );
	}

	// @REF: WooCommerce: `_sort_priority_callback()`
	public static function sortByPriority( $array, $priority_key )
	{
		uasort( $array, static function( $a, $b ) use ( $priority_key ) {

			if ( ! isset( $a[$priority_key], $b[$priority_key] )
				|| $a[$priority_key] === $b[$priority_key] )
					return 0;

			return ( $a[$priority_key] < $b[$priority_key] ) ? -1 : 1;
		} );

		return $array;
	}

	// @REF: WooCommerce: `_sort_priority_callback()`
	public static function sortObjectByPriority( $array, $priority_key )
	{
		uasort( $array, static function( $a, $b ) use ( $priority_key ) {

			if ( ! isset( $a->{$priority_key}, $b->{$priority_key} )
				|| $a->{$priority_key} === $b->{$priority_key} )
					return 0;

			return ( $a->{$priority_key} < $b->{$priority_key} ) ? -1 : 1;
		} );

		return $array;
	}

	// FIXME: TEST THIS!
	// @REF: http://stackoverflow.com/a/4582659
	// USAGE: Arraay::multiSort( $array, array( 'key_1' => SORT_ASC, 'key_2' => SORT_ASC ) );
	public static function multiSort( $array, $sort )
	{
		if ( empty( $array ) )
			return $array;

		$map = $args = array();

		foreach ( $array as $key => $val )
			foreach ( $sort as $by => $order )
				$map[$by][$key] = $val[$by];

		foreach ( $sort as $by => $order ) {
			$args[] = $map[$by];
			$args[] = $order;
		}

		$args[] = &$array;

		call_user_func_array( 'array_multisort', $args );

		// return array_pop( $args ); // @SEE: http://php.net/manual/en/function.array-multisort.php#100534
		return $array;
	}

	// insert an array into another array before/after a certain key
	// @SOURCE: https://gist.github.com/scribu/588429
	public static function insert( $array, $pairs, $key, $position = 'after', $anyways = TRUE )
	{
		$key_pos = array_search( $key, array_keys( $array ) );

		if ( 'after' == $position )
			$key_pos++;

		if ( FALSE !== $key_pos ) {

			$result = array_slice( $array, 0, $key_pos );
			$result = array_merge( $result, $pairs );
			$result = array_merge( $result, array_slice( $array, $key_pos ) );

		} else if ( $anyways ) {

			$result = 'after' == $position ? array_merge( $array, $pairs ) : array_merge( $pairs, $array );

		} else {

			$result = $array;
		}

		return $result;
	}

	// @REF: http://php.net/manual/en/function.array-splice.php#92651
	public static function keyMoveUp( $input, $index )
	{
		$new = $input;

		if ( ( count( $new ) > $index ) && ( $index > 0 ) ) {
			array_splice( $new, $index - 1, 0, $input[$index] );
			array_splice( $new, $index + 1, 1 );
		}

		return $new;
	}

	// @REF: http://php.net/manual/en/function.array-splice.php#92651
	public static function keyMoveDown( $input, $index )
	{
		$new = $input;

		if ( count( $new ) > $index ) {
			array_splice( $new, $index + 2, 0, $input[$index] );
			array_splice( $new, $index, 1 );
		}

		return $new;
	}

	// `array_key_first()` for php < 7.3.0
	public static function keyFirst( $input )
	{
		if ( function_exists( 'array_key_first' ) )
			return array_key_first( $input ); // phpcs:ignore

		foreach ( $input as $key => $value )
			return $key;

		return NULL;
	}

	// `array_key_last()` for php < 7.3.0
	public static function keyLast( $input )
	{
		if ( function_exists( 'array_key_last' ) )
			return array_key_last( $input ); // phpcs:ignore

		if ( ! is_array( $input ) || empty( $input ) )
			return NULL;

		return array_keys( $input )[count( $input ) - 1];
	}

	// `array_column()` for php < 5.5
	// @SEE: https://github.com/ramsey/array_column/blob/master/src/array_column.php
	// @REF: http://php.net/manual/en/function.array-column.php#118831
	// ALT: `wp_list_pluck()`
	public static function column( $input, $column_key, $index_key = NULL )
	{
		if ( function_exists( 'array_column' ) )
			return array_column( $input, $column_key, $index_key ); // phpcs:ignore

		$arr = array_map( function( $d ) use ( $column_key, $index_key ) {

			if ( ! isset( $d[$column_key] ) )
				return NULL;

			if ( NULL !== $index_key )
				return array( $d[$index_key] => $d[$column_key] );

			return $d[$column_key];

		}, $input );

		if ( NULL !== $index_key ) {

			$tmp = array();

			foreach ( $arr as $ar )
				$tmp[key( $ar )] = current( $ar );

			$arr = $tmp;
		}

		return $arr;
	}

	// `array_is_list()` for php < 8.1
	// @REF: https://www.php.net/manual/en/function.array-is-list.php#126574
	// an array is considered a list if its keys consist of consecutive numbers from 0 to count $array
	public static function isList( $array )
	{
		return $array === [] || ( array_keys( $array ) === range( 0, count( $array ) - 1 ) );
	}

	// @REF: http://stackoverflow.com/a/11320508
	public static function find( $needle, &$haystack, $default = NULL )
	{
		$current = array_shift( $needle );

		if ( ! isset( $haystack[$current] ) )
			return $default;

		if ( ! is_array( $haystack[$current] ) )
			return $haystack[$current];

		return self::find( $needle, $haystack[$current], $default );
	}

	// is associative or sequential?
	// @REF: https://stackoverflow.com/a/173479
	public static function isAssoc( $array )
	{
		if ( $array === array() )
			return FALSE;

		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	// @REF: https://stackoverflow.com/a/4254008
	// To merely check whether the array has non-integer keys, not whether the
	// array is sequentially-indexed or zero-indexed. If there is at least one
	// string key, the array will be regarded as an associative array.
	public static function hasStringKeys( $array )
	{
		return count( array_filter( array_keys( $array ), 'is_string' ) ) > 0;
	}

	public static function changeCaseLower( $array )
	{
		return array_map( [ __CLASS__, 'changeCaseLowerCallback' ], $array );
	}

	private static function changeCaseLowerCallback( $value )
	{
		return is_array( $value ) ? array_map( [ __CLASS__, 'changeCaseLowerCallback' ], $value ) : strtolower( $value );
	}

	public static function reKeyByMap( $array, $map )
	{
		if ( empty( $array ) || empty( $map ) || ! self::isAssoc( $array ) )
			return $array;

		$new = [];

		foreach ( $array as $key => $value ) {

			if ( '' === $key || is_numeric( $key ) ) {

				$new[$key] = $value;

			} else {

				$passed = FALSE;

				foreach ( $map as $target => $keys ) {

					if ( '' === $target || is_numeric( $target ) || ! $keys )
						continue;

					$keys = (array) $keys;
					$lows = array_map( 'strtolower', $keys );

					if ( in_array( $key, $keys, TRUE )
						|| in_array( $key, $lows, TRUE ) ) {

						$new[$target] = $value;
						$passed = TRUE;
						break;
					}
				}

				if ( ! $passed )
					$new[$key] = $value;
			}
		}

		return $new;
	}

	// @REF: `wp_array_slice_assoc()`
	public static function sliceKeys( $array, $keys )
	{
		$slice = array();

		foreach ( $keys as $key )
			if ( isset( $array[$key] ) )
				$slice[$key] = $array[$key];

		return $slice;
	}

	// splits a list into sets, grouped by the result of running each value through $callback
	// @SOURCE: `scb_list_group_by()`
	public static function groupBy( $list, $callback )
	{
		$groups = array();

		foreach ( $list as $item ) {
			$key = call_user_func( $callback, $item );

			if ( NULL === $key )
				continue;

			$groups[$key][] = $item;
		}

		return $groups;
	}

	// transform a list of objects into an associative array
	// @SOURCE: `scb_list_fold()`
	public static function listFold( $list, $key, $value )
	{
		$array = array();

		if ( is_array( reset( $list ) ) )

			foreach ( $list as $item )
				$array[$item[$key]] = $item[$value];

		else

			foreach ( $list as $item )
				$array[$item->{$key}] = $item->{$value};

		return $array;
	}
}

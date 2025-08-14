<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Arraay extends Base
{

	public static function prepString()
	{
		$args = array_map( function ( $value ) {
			return $value ? (array) $value : [];
		}, func_get_args() );

		return empty( $args ) ? [] : array_values( array_unique( array_filter( array_map( [ __NAMESPACE__.'\\Text', 'trim' ], array_merge( ...$args ) ) ) ) );
	}

	public static function prepNumeral()
	{
		$args = array_map( function ( $value ) {
			return $value ? (array) $value : [];
		}, func_get_args() );

		return empty( $args ) ? [] : array_values( array_unique( array_filter( array_map( [ __NAMESPACE__.'\\Number', 'intval' ], array_merge( ...$args ) ) ) ) );
	}

	public static function prepSplitters( $text, $default = '|' )
	{
		if ( is_null( $text ) )
			return NULL;

		return empty( $text ) ? [ $default ] : self::prepString( preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY ), [ $default ] );
	}

	// deep array_filter()
	public static function filterArray( $input, $callback = NULL )
	{
		foreach ( $input as &$value )
			if ( is_array( $value ) )
				$value = self::filterArray( $value, $callback );

		return $callback ? array_filter( $input, $callback ) : array_filter( $input );
	}

	/**
	 * Adds a prefix to each item value of an array.
	 * @source https://stackoverflow.com/a/28115783
	 * @since PHP 5.3.0
	 *
	 * @param array $array
	 * @param string $prefix
	 * @return array
	 */
	public static function prefixValues( $array, $prefix )
	{
		if ( empty( $prefix ) || empty( $array ) )
			return $array;

		return preg_filter( '/^/', $prefix, $array );
	}

	/**
	 * Adds a prefix to each item key of an array.
	 *
	 * @param array $array
	 * @param string $prefix
	 * @return array
	 */
	public static function prefixKeys( $array, $prefix )
	{
		if ( empty( $prefix ) || empty( $array ) )
			return $array;

		$keys = array_keys( $array );

		return self::replaceKeys( $array, array_combine( $keys, self::prefixValues( $keys, $prefix ) ) );
	}

	public static function roundArray( $array, $precision = -3, $mode = PHP_ROUND_HALF_UP )
	{
		$rounded = [];

		foreach ( (array) $array as $key => $value )
			$rounded[$key] = round( (float) $value, $precision, $mode );

		return $rounded;
	}

	public static function reKey( $list, $key )
	{
		if ( ! empty( $list ) ) {
			$ids  = self::pluck( $list, $key );
			$list = array_combine( $ids, $list );
		}

		return $list;
	}

	// OR: `array_combine( $array, $array );`
	public static function sameKey( $old )
	{
		$new = [];

		foreach ( (array) $old as $key => $value )
			if ( FALSE !== $value && NULL !== $value )
				$new[$value] = $value;

		return $new;
	}

	// USE: `array_keys()` on posted checkboxes
	public static function getKeys( $options, $if = TRUE )
	{
		$keys = [];

		foreach ( (array) $options as $key => $value )
			if ( $value == $if )
				$keys[] = $key;

		return $keys;
	}

	// @SOURCE: http://stackoverflow.com/a/24436324
	// USAGE: `Arraay::replaceKeys( $array, [ 'old_key_1' => 'new_key_1', 'old_key_2' => 'new_key_2' ] );`
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

	/**
	 * Checks if two arrays contain the same values,
	 * based on the type coerced values and keys, regardless of order.
	 *
	 * @source https://eddmann.com/posts/handling-array-equality-in-php/
	 * @example `Arraay::equalValues( ['1', 2, 3], [3, 2, 1] )` // TRUE
	 *
	 * With the equal operator `==` you are able to check for equality
	 * based on the type coerced values and keys (regardless of order).
	 *
	 * `[1, 2, 3] == [1, 2, 3]`                   // TRUE
	 * `[1, 2, 3] == [1, 2, '3']`                 // TRUE
	 * `[1, 2, 3] == [1, 3, 2]`                   // FALSE
	 * `[1, 2, 3] == [0 => 1, '2' => 3, 1 => 2]`  // TRUE
	 *
	 * @param array $a
	 * @param array $b
	 * @return bool
	 */
	public static function equalValues( $a, $b )
	{
		$x = array_values( $a );
		$y = array_values( $b );

		sort( $x );
		sort( $y );

		return $x == $y;
	}

	/**
	 * Checks if two arrays contain the same values,
	 * based on the type and exact ordering of the keys.
	 *
	 * @source https://eddmann.com/posts/handling-array-equality-in-php/
	 * @example `Arraay::identicalValues([1, 2, 3], [3, 2, 1])` // TRUE
	 *
	 * With the identical operator `===` you are able to check for equality
	 * based on the type and exact ordering of the keys.
	 *
	 * `[1, 2, 3] === [1, 2, 3]`                // TRUE
	 * `[1, 2, 3] === [1, 2, '3']`              // FALSE
	 * `[1, 2, 3] === [1, 3, 2]`                // FALSE
	 * `[1, 2, 3] === [0 => 1, 2 => 3, 1 => 2]` // FALSE
	 *
	 * @param array $a
	 * @param array $b
	 * @return bool
	 */
	public static function identicalValues( $a, $b )
	{
		$x = array_values( $a );
		$y = array_values( $b );

		sort( $x );
		sort( $y );

		return $x === $y;
	}

	// @REF: https://stackoverflow.com/a/56428184
	public static function getByKeyLike( $array, $pattern )
	{
		return array_intersect_key( $array, array_flip( preg_grep( $pattern, array_keys( $array ) ) ) );
	}

	// `$a == $b` TRUE if `$a` and `$b` have the same key/value pairs
	// `$a === $b` TRUE if `$a` and `$b` have the same key/value pairs in the same order and of the same types
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
		$array = [];

		foreach ( range( $start, $end, $step ) as $number )
			$array[$number] = $format ? Number::format( $number ) : $number;

		return $array;
	}

	// for using with `$('form').serializeArray();`
	// @REF: http://api.jquery.com/serializeArray/
	// @INPUT: [{name:"a",value:"1"},{name:"b",value:"2"}]
	// @OLD: `parseJSArray()`
	public static function parseSerialized( $array, $name = 'name', $value = 'value' )
	{
		$parsed = [];

		foreach ( $array as $part )
			$parsed[$part[$name]] = $part[$value];

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

	public static function stripDefaults( $atts, $defaults = [] )
	{
		foreach ( $defaults as $key => $value )
			if ( isset( $atts[$key] ) && $value === $atts[$key] )
				unset( $atts[$key] );

		return $atts;
	}

	// @REF: http://stackoverflow.com/a/11026840#comment44080768_11026840
	public static function stripByValue( $array, $value, $strict = FALSE )
	{
		if ( empty( $array ) || empty( $value ) )
			return $array;

		return array_diff_key( $array, array_flip( array_keys( $array, $value, $strict ) ) );
	}

	//@RF: https://stackoverflow.com/a/11026840
	public static function stripByKeys( $array, $keys )
	{
		if ( empty( $array ) || empty( $keys ) )
			return $array;

		return array_diff_key( $array, array_flip( $keys ) );
	}

	// @REF: https://stackoverflow.com/a/34575007
	// @SEE: `wp_array_slice_assoc()`
	public static function keepByKeys( $array, $keys )
	{
		if ( empty( $array ) || empty( $keys ) )
			return [];

		return array_intersect_key( $array, array_flip( $keys ) );
	}

	public static function keepByValue( $array, $values )
	{
		if ( empty( $array ) || empty( $values ) )
			return [];

		return array_intersect( $array, $values );
	}

	// @REF: WooCommerce: `_sort_priority_callback()`
	public static function sortByPriority( $array, $priority_key, $descending = TRUE )
	{
		if ( empty( $array ) || empty( $priority_key ) )
			return $array;

		$up   = $descending ? -1 : 1;
		$down = $descending ? 1 : -1;

		uasort( $array,
			static function ( $a, $b ) use ( $priority_key, $up, $down ) {

				if ( ! isset( $a[$priority_key], $b[$priority_key] )
					|| $a[$priority_key] === $b[$priority_key] )
						return 0;

				return ( $a[$priority_key] < $b[$priority_key] ) ? $up : $down;
			} );

		return $array;
	}

	// @REF: WooCommerce: `_sort_priority_callback()`
	public static function sortObjectByPriority( $array, $priority_key, $descending = TRUE )
	{
		if ( empty( $array ) || empty( $priority_key ) )
			return $array;

		$up   = $descending ? -1 : 1;
		$down = $descending ? 1 : -1;

		uasort( $array,
			static function ( $a, $b ) use ( $priority_key, $up, $down ) {

				if ( ! isset( $a->{$priority_key}, $b->{$priority_key} )
					|| $a->{$priority_key} === $b->{$priority_key} )
						return 0;

				return ( $a->{$priority_key} < $b->{$priority_key} ) ? $up : $down;
			} );

		return $array;
	}

	// FIXME: TEST THIS!
	// @REF: http://stackoverflow.com/a/4582659
	// USAGE: `Arraay::multiSort( $array, [ 'key_1' => SORT_ASC, 'key_2' => SORT_ASC ] );`
	public static function multiSort( $array, $sort )
	{
		if ( empty( $array ) )
			return $array;

		$map = $args = [];

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

	/**
	 * Insert an array into another array before/after a certain key.
	 * @source https://gist.github.com/scribu/588429
	 *
	 * @param array $array
	 * @param array $pairs
	 * @param string $key
	 * @param string $position
	 * @param bool $anyways
	 * @return array
	 */
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

	/**
	 * Retrieves the objects from the list that match the given arguments.
	 * Key represents property name, and value represents property value.
	 * NOTE: wrapper for `wp_list_filter()` and `wp_filter_object_list()`
	 *
	 * @param array $input
	 * @param array $args
	 * @param string $operator
	 * @param bool $field
	 * @return array
	 */
	public static function filter( $input, $args = [], $operator = 'and', $field = FALSE )
	{
		if ( empty( $input ) )
			return [];

		if ( empty( $args ) && empty( $field ) )
			return $input;

		return wp_filter_object_list( $input, $args, $operator, $field );
	}

	/**
	 * Plucks a certain field out of each array or object in an array.
	 * NOTE: wrapper for `wp_list_pluck()`
	 *
	 * @param array $input
	 * @param int|string $field
	 * @param int|string|null $index_key
	 * @return array
	 */
	public static function pluck( $input, $field, $index_key = NULL )
	{
		if ( empty( $input ) || empty( $field ) )
			return [];

		return wp_list_pluck( $input, $field, $index_key );
	}

	// `array_column()` for php < 5.5
	// @SEE: https://github.com/ramsey/array_column/blob/master/src/array_column.php
	// @REF: http://php.net/manual/en/function.array-column.php#118831
	// ALT: `wp_list_pluck()`
	public static function column( $input, $column_key, $index_key = NULL )
	{
		if ( function_exists( 'array_column' ) )
			return array_column( $input, $column_key, $index_key ); // phpcs:ignore

		$arr = array_map( function ( $d ) use ( $column_key, $index_key ) {

			if ( ! isset( $d[$column_key] ) )
				return NULL;

			if ( NULL !== $index_key )
				return [ $d[$index_key] => $d[$column_key] ];

			return $d[$column_key];

		}, $input );

		if ( NULL !== $index_key ) {

			$tmp = [];

			foreach ( $arr as $ar )
				$tmp[key( $ar )] = current( $ar );

			$arr = $tmp;
		}

		return $arr;
	}

	// `array_is_list()` for PHP < 8.1
	// @REF: https://www.php.net/manual/en/function.array-is-list.php#126574
	// an array is considered a list if its keys consist of consecutive numbers from 0 to count $array
	public static function isList_ALT( $array )
	{
		return $array === [] || ( array_keys( $array ) === range( 0, count( $array ) - 1 ) );
	}

	/**
	 * Poly-fill for `array_is_list()` function added in PHP 8.1.
	 * Determines if the given array is a list.
	 * An array is considered a list if its keys consist of consecutive numbers from 0 to count( $array ) - 1.
	 * @see https://github.com/symfony/polyfill-php81/tree/main
	 * @see https://php.watch/versions/8.1/array_is_list
	 *
	 * @param array $array The array being evaluated.
	 * @return bool $list `TRUE` if array is a list, `FALSE` otherwise.
	 */
	public static function isList( $array )
	{
		if ( function_exists( 'array_is_list' ) )
			return array_is_list( $array );

		if ( ( [] === $array ) || ( array_values( $array ) === $array ) )
			return TRUE;

		$next = -1;

		foreach ( $array as $key => $value )
			if ( ++$next !== $key )
				return FALSE;

		return TRUE;
	}

	public static function has( $needle, $haystack, $strict = TRUE )
	{
		return FALSE !== array_search( $needle, (array) $haystack, $strict );
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

	/**
	 * Is associative or sequential?
	 * @source https://stackoverflow.com/a/173479
	 *
	 * @param array $array
	 * @return bool
	 */
	public static function isAssoc( $array )
	{
		if ( ! is_array( $array ) || $array === [] )
			return FALSE;

		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	/**
	 * Determines if the variable is a numeric-indexed array.
	 *
	 * To merely check whether the array has non-integer keys, not whether the
	 * array is sequentially-indexed or zero-indexed. If there is at least one
	 * string key, the array will be regarded as an associative array.
	 *
	 * @source https://stackoverflow.com/a/4254008
	 * @source `wp_is_numeric_array()`
	 *
	 * @param array $array
	 * @return bool
	 */
	public static function isNumeric( $array )
	{
		if ( ! is_array( $array ) || $array === [] )
			return FALSE;

		return count( array_filter( array_keys( $array ), 'is_string' ) ) === 0;
	}

	// NOTE: DEPRECATED
	public static function hasStringKeys( $array )
	{
		return self::isNumeric( $array );
	}

	public static function changeCaseLower( $array )
	{
		return array_map( [ __CLASS__, 'changeCaseLowerCallback' ], $array );
	}

	private static function changeCaseLowerCallback( $value )
	{
		return is_array( $value ) ? array_map( [ __CLASS__, 'changeCaseLowerCallback' ], $value ) : strtolower( $value );
	}

	/**
	 * Replaces keys in an array based on another mapping array.
	 *
	 * @source https://stackoverflow.com/a/36435888
	 *
	 * @param  array $array
	 * @param  array $map
	 * @return array $array
	 */
	public static function reKeyByMap_ALT( $array, $map )
	{
		if ( empty( $array ) || empty( $map ) || ! self::isAssoc( $array ) )
			return $array;

		return array_combine( array_map( function ( $el ) use ( $map ) {
			return isset( $map[$el] ) ? $map[$el] : $el;
		}, array_keys( $array ) ), array_values( $array ) );
	}

	/**
	 * Replaces keys in an array based on another mapping array.
	 *
	 * @param  array $array
	 * @param  array $map
	 * @return array $array
	 */
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
		$slice = [];

		foreach ( $keys as $key )
			if ( isset( $array[$key] ) )
				$slice[$key] = $array[$key];

		return $slice;
	}

	// splits a list into sets, grouped by the result of running each value through $callback
	// @SOURCE: `scb_list_group_by()`
	public static function groupBy( $list, $callback )
	{
		$groups = [];

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
		$array = [];

		if ( is_array( reset( $list ) ) )

			foreach ( $list as $item )
				$array[$item[$key]] = $item[$value];

		else

			foreach ( $list as $item )
				$array[$item->{$key}] = $item->{$value};

		return $array;
	}

	// @SOURCE: https://www.php.net/manual/en/function.array-intersect.php#69762
	public static function union( $a, $b )
	{
                                        //  $a = 1 2 3 4
		return                          //  $b =   2   4 5 6
		array_merge(
			array_intersect( $a, $b ),  //         2   4
			array_diff( $a, $b ),       //       1   3
			array_diff( $b, $a )        //               5 6
		);                              //  $u = 1 2 3 4 5 6
	}

	/**
	 * Retrieves duplicate values of an array.
	 * @source https://stackoverflow.com/a/3450063
	 *
	 * @param  array $array
	 * @return array $duplicates
	 */
	public static function duplicates( $array )
	{
		return \array_diff_assoc( $array, \array_unique( $array ) );
	}

	/**
	 * Prepears list of objects with provided keys.
	 *
	 * @param  array $array
	 * @param  string $index_key
	 * @param  string $value_key
	 * @return array $list
	 */
	public static function toObjectForJS( $array, $index_key = 'code', $value_key = 'label' )
	{
		if ( empty( $array ) )
			return $array;

		$list = [];

		foreach ( $array as $index => $value )
			$list[] = (object) [
				$index_key => $index,
				$value_key => $value,
			];

		return $list;
	}

	/**
	 * Converts an object into assoc array.
	 *
	 * @source https://stackoverflow.com/a/4345578
	 *
	 * @param  object $object
	 * @return array $array
	 */
	public static function fromObject( $object )
	{
		if ( \is_array( $object ) || \is_object( $object ) ) {

			$array = [];

			foreach ( $object as $key => $value )
				$array[$key] = ( \is_array( $value ) || \is_object( $value ) )
					? self::fromObject( $value )
					: $value;

			return $array;
		}

		return $object;
	}

	/**
	 * Converts all applicable objects into associative array.
	 * @source https://www.php.net/manual/en/function.xml-parse.php#97556
	 * @example `XML::objectsInto( simplexml_load_string( file_get_contents( 'feed.xml' ) ) );`
	 *
	 * This works with not only SimpleXML but any kind of object.
	 * The input can be either array or object. This function also
	 * takes an options parameter as array of indices to be excluded
	 * in the return array. And keep in mind, this returns only the
	 * array of non-static and accessible variables of the object
	 * since using the function `get_object_vars()`.
	 *
	 * @param  object $object
	 * @param  array  $arrSkipIndices
	 * @return array  $array
	 */
	public static function objectsInto( $object, $arrSkipIndices = [] )
	{
		$array = [];

		// if input is object, convert into array
		if ( is_object( $object ) )
			$object = get_object_vars( $object );

		if ( is_array( $object ) ) {

			foreach ( $object as $index => $value ) {

				if ( is_object( $value ) || is_array( $value ) )
					$value = self::objectsInto( $value, $arrSkipIndices ); // recursive call

				if ( in_array( $index, $arrSkipIndices ) )
					continue;

				$array[$index] = $value;
			}
		}

		return $array;
	}

	/**
	 * Converts an object into assoc array.
	 *
	 * @source https://stackoverflow.com/a/16111687
	 *
	 * @param  object $object
	 * @return array $array
	 */
	public static function fromObject_ALT( $object )
	{
		return \json_decode( \json_encode( $object ), TRUE );
	}

	/**
	 * Flips an array and group the elements by value.
	 * @source https://www.php.net/manual/en/function.array-combine.php#116714
	 *
	 * @param  array $array
	 * @return array $grouped
	 */
	public static function flipAndGroup( $array )
	{
		$grouped = [];

		\array_walk( $array, static function ( $value, $key ) use ( &$grouped ) {
			if ( ! isset( $grouped[$value] ) || ! is_array( $grouped[$value] ) )
				$grouped[$value] = [];
			$grouped[$value][] = $key;
		} );

		return $grouped;
	}

	/**
	 * Handles combine from unequal arrays.
	 * @source https://www.php.net/manual/en/function.array-combine.php#106318
	 *
	 * @param  array $keys
	 * @param  array $values
	 * @param  bool  $pad
	 * @return array $combined
	 */
	public static function combine( $keys, $values, $pad = TRUE )
	{
		if ( empty( $keys ) && empty( $values ) )
			return [];

		$acount = count( $keys );
		$bcount = count( $values );

		if ( $acount === $bcount ) {

			// regular combine works

		} else if ( ! $pad ) {

			// more elements in $keys than $values
			// but we don't want to pad either

			$size   = $acount > $bcount ? $bcount : $acount;
			$keys   = array_slice( $keys, 0, $size );
			$values = array_slice( $values, 0, $size );

		} else {

			// more keys than values fields
			if ( $acount > $bcount ) {

				// how many fields are we missing at the end of the values array?
				// add empty strings to ensure arrays $keys and $values have same number of elements
				$more = $acount - $bcount;

				for ( $i = 0; $i < $more; $i++ )
					$values[] = '';

			// more values than keys
			} else if ( $acount < $bcount ) {

				// fewer elements in the first array, add extra keys
				$more = $bcount - $acount;

				for ( $i = 0; $i < $more; $i++ )
					$keys[] = $acount + $i;
			}
		}

		return array_combine( $keys, $values );
	}

	/**
	 * Checks whether all array values are strings or not.
	 * @source https://www.w3resource.com/php-exercises/php-array-exercise-46.php
	 *
	 * @param  array $array
	 * @return bool $is_string
	 */
	public static function allStringValues( $array )
	{
		return array_sum( array_map( 'is_string', $array ) ) === count( $array );
	}

	/**
	 * Divides an array into a desired number of split lists.
	 *
	 * useful procedure for "chunking" up objects or text items into
	 * columns, or partitioning any type of data resource.
	 *
	 * NOTE: `array_chunk()`: fixed number of sub-items
	 * NOTE: `::partition()`: fixed number of columns
	 *
	 * @source https://www.php.net/manual/en/function.array-chunk.php#75022
	 * @source https://stackoverflow.com/a/15723262
	 *
	 * @param  array $array
	 * @param  int   $columns
	 * @return array $partition
	 */
	public static function partition( $array, $columns )
	{
		if ( $columns < 2 )
        	return [ $array ];

		$partition = [];
		$count     = count( $array );
		$length    = floor( $count / $columns );
		$remains   = $count % $columns;
		$marked    = 0;

		for ( $i = 0; $i < $columns; $i++ ) {
			$increase = ( $i < $remains ) ? $length + 1 : $length;
			$partition[$i] = array_slice( $array, $marked, $increase );
			$marked += $increase;
		}

		return $partition;
	}

	// @REF: https://medium.com/@assertchris/dot-notation-3fd3e42edc61
	// This is from `Illuminate/Support/Arr.php`
	public static function getByNotation( $array, $key, $default = NULL, $notation = '.' )
	{
		if ( is_null( $key ) )
			return $default;

		if ( array_key_exists( $key, $array ) )
			return $array[$key];

		foreach ( explode( $notation, $key ) as $segment ) {

			if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) )
				return value( $default );

			$array = $array[$segment];
	  	}

		return $array;
	}

	// @REF: https://medium.com/@assertchris/dot-notation-3fd3e42edc61
	// This is from `Illuminate/Support/Arr.php`
	public static function setByNotation( &$array, $key, $value, $notation = '.' )
	{
		if ( is_null( $key ) )
			return $array = $value;

		$keys = explode( $notation, $key );

		while ( count( $keys ) > 1 ) {

			$key = array_shift( $keys );

			if ( ! isset( $array[$key] ) || ! is_array( $array[$key] ) )
				$array[$key] = [];

			$array =& $array[$key];
		}

		$array[array_shift( $keys )] = $value;

		return $array;
	}

	/**
	 * Checks whether the $callback returns TRUE for ANY of the array
	 *  elements.
	 *
	 * @source https://github.com/PHP-Polyfills/array-find
	 * @REF: https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
	 * NOTE: Polyfill for below PHP 8.4
	 *
	 * @param array $array The array that should be searched.
	 * @param callable $callback The callback function to call to check
	 *  each element. The first parameter contains the value ($value), the
	 *  second parameter contains the corresponding key ($key). If this
	 *  function returns TRUE (or a truthy value), TRUE is returned
	 *  immediately and the $callback will not be called for further
	 *  elements.
	 *
	 * @return bool TRUE if there is at least one element for which
	 *  $callback returns TRUE. FALSE otherwise.
	 */
	public static function array_any( array $array, callable $callback ): bool
	{
		if ( function_exists( 'array_any' ) )
			return array_any( $array, $callback );

		foreach ( $array as $key => $value )
			if ( $callback( $value, $key ) )
				return TRUE;

		return FALSE;
	}

	/**
	 * Checks whether the $callback returns TRUE for ALL the array
	 *  elements.
	 *
	 * @source https://github.com/PHP-Polyfills/array-find
	 * @REF: https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
	 * NOTE: Polyfill for below PHP 8.4
	 *
	 * @param array $array The array that should be searched.
	 * @param callable $callback The callback function to call to check
	 *  each element. The first parameter contains the value ($value), the
	 *  second parameter contains the corresponding key. If this function
	 *  returns FALSE (or any falsy value), FALSE is returned immediately
	 *  and the $callback will not be called for further elements.
	 *
	 * @return bool TRUE, if $callback returns TRUE for all elements.
	 *  FALSE otherwise.
	 */
	public static function array_all( array $array, callable $callback ): bool
	{
		if ( function_exists( 'array_all' ) )
			return array_all( $array, $callback );

		foreach ( $array as $key => $value )
			if ( ! $callback( $value, $key ) )
				return FALSE;

		return TRUE;
	}

	/**
	 * Returns the VALUE of the first element from $array for which the
	 *  `$callback` returns true. Returns NULL if no matching element is
	 *  found.
	 *
	 * @source https://github.com/PHP-Polyfills/array-find
	 * @REF: https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
	 * NOTE: Polyfill for below PHP 8.4
	 *
	 * @param array $array The array that should be searched.
	 * @param callable $callback The callback function to call to check
	 *  each element. The first parameter contains the value ($value),
	 *  the second parameter contains the corresponding key ($key).
	 *  If this callback returns TRUE (or a truthy value), the value
	 *  ($value) is returned immediately and the callback will not be
	 *  called for further elements.
	 *
	 * @return mixed The function returns the value of the first
	 *  element for which the `$callback` returns TRUE. NULL, if no
	 *  matching element is found. Note that the matching element value
	 *  itself could be NULL as well.
	 */
	public static function array_find( array $array, callable $callback )
	{
		if ( function_exists( 'array_find' ) )
			return array_find( $array, $callback );

		foreach ( $array as $key => $value )
			if ( $callback( $value, $key ) )
				return $value;

		return NULL;
	}

	/**
	 * Returns the KEY of the first element from $array for which the
	 *  `$callback` returns TRUE. If no matching element is found the
	 *  function returns NULL.
	 *
	 * @source https://github.com/PHP-Polyfills/array-find
	 * @REF: https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
	 * NOTE: Poly-fill for below PHP 8.4
	 *
	 * @param array $array The array that should be searched.
	 * @param callable $callback The callback function to call to check
	 *  each element. The first parameter contains the value ($value),
	 *  the second parameter contains the corresponding key ($key). If
	 *  this function returns TRUE, the key ($key) is returned
	 *  immediately and the callback will not be called for further
	 *  elements.
	 *
	 * @return mixed The key of the first element for which the
	 *  `$callback` returns TRUE. NULL, If no matching element is found.
	 */
	public static function array_find_key( array $array, callable $callback )
	{
		if ( function_exists( 'array_find_key' ) )
			return array_find_key( $array, $callback );

		foreach ( $array as $key => $value )
			if ( $callback($value, $key ) )
				return $key;

		return NULL;
	}

	/**
	 * Merges consecutive values to one value in one array.
	 * @source https://stackoverflow.com/a/41814878
	 *
	 * @param array $array
	 * @param int $chunk
	 * @param string $glue
	 * @return array
	 */
	public static function mergeConsecutive( $array, $chunk = 2, $glue = '' )
	{
		return array_map(
			static function ( $value ) use ( $glue ) {
				return implode( $glue, $value );
			},
			array_chunk( $array, $chunk )
		);
	}
}

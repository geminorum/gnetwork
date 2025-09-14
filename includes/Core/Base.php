<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Base
{

	public static function define( $name, $value )
	{
		if ( $name && ! defined( $name ) )
			define( $name, $value );
	}

	public static function const( $name, $default = FALSE )
	{
		return defined( $name ) ? constant( $name ) : $default;
	}

	/**
	 * Checks if a class constant exists and returns it.
	 * @source https://stackoverflow.com/a/50697766
	 * @needs `PHP >= 7.1.0`
	 *
	 * @param string $const
	 * @param object $object
	 * @return mixed
	 */
	public static function classConst( $const, $object = NULL )
	{
		$class = $object ? get_class( $object ) : __CLASS__;

		try {

			$reflex = new \ReflectionClassConstant( $class, $const );
			$value  = $reflex->getValue();

		} catch ( \ReflectionException $e ) {

			$value = NULL;
		}

		return $value;
	}

	public static function empty( $value )
	{
		if ( empty( $value ) )
			return TRUE;

		// only checks for empty strings
		if ( ! is_string( $value ) )
			return FALSE;

		if ( ! $value = Text::trim( $value ) )
			return TRUE;

		if ( ! $value = Number::translate( $value ) )
			return TRUE;

		// TODO: check empty types: HTML/dashes
		// @see `WordPress\Strings::isEmpty()`

		return FALSE;
	}

	// converts to boolean
	public static function bool( $value )
	{
		if ( is_bool( $value ) )
			return $value;

		if ( self::empty( $value ) )
			return FALSE;

		if ( is_string( $value ) && in_array( strtolower( $value ), [ 'false', 'none', '0', 'off', 'no' ], TRUE ) )
			return FALSE;

		return TRUE;
	}

	public static function req( $key, $default = '' )
	{
		return isset( $_REQUEST[$key] ) ? $_REQUEST[$key] : $default;
	}

	public static function do( $values, $key = 'action', $default = FALSE )
	{
		if ( ! isset( $_REQUEST[$key] ) )
			return $default;

		foreach ( (array) $values as $value )
			if ( $value == $_REQUEST[$key] )
				return $value ?: TRUE;

		return $default;
	}

	public static function step( $value = NULL, $key = 'action', $default = '' )
	{
		$request = self::req( $key, [] );
		$action  = is_array( $request )
			? Arraay::keyFirst( $request )
			: $request;

		if ( empty( $action ) && ! is_null( $value ) )
			return $default;

		return is_null( $value ) ? $action : ( (string) $action === (string) $value );
	}

	public static function limit( $default = 25, $key = 'limit' )
	{
		return (int) self::req( $key, $default );
	}

	public static function paged( $default = 1, $key = 'paged' )
	{
		return (int) self::req( $key, $default );
	}

	public static function orderby( $default = 'title', $key = 'orderby' )
	{
		return self::req( $key, $default );
	}

	public static function order( $default = 'desc', $key = 'order' )
	{
		$req = strtoupper( self::req( $key, $default ) );
		return ( 'ASC' === $req || 'DESC' === $req ) ? $req : $default;
	}

	public static function buffer( $callback, $args = [], $fallback = '' )
	{
		if ( ! $callback || ! is_callable( $callback ) )
			return $fallback;

		ob_start();

			call_user_func_array( $callback, $args );

		return trim( ob_get_clean() );
	}

	public static function dump( $var, $safe = TRUE, $verbose = TRUE )
	{
		$export = var_export( $var, TRUE );
		if ( $safe ) $export = htmlspecialchars( $export );
		$export = '<pre dir="ltr" style="text-align:left;direction:ltr;">'.$export.'</pre>';
		if ( ! $verbose ) return $export;
		echo $export;
	}

	public static function kill()
	{
		foreach ( func_get_args() as $arg )
			self::dump( $arg );
		echo self::stat();
		die();
	}

	public static function cheatin( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = __( 'You don&#8217;t have permission to do this.' );

		wp_die( $message, 403 );
	}

	public static function _log_req()
	{
		return self::_log( $_REQUEST );
	}

	// INTERNAL
	public static function _log()
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return FALSE; // help the caller

		foreach ( func_get_args() as $data )

			if ( self::isError( $data ) )
				error_log( sprintf( 'WPError: %s', strip_tags( $data->get_error_message() ) ) );

			else if ( is_array( $data ) || is_object( $data ) )
				error_log( print_r( $data, TRUE ) );

			else if ( is_bool( $data ) )
				error_log( $data ? 'TRUE' : 'FALSE' );

			else if ( is_null( $data ) )
				error_log( 'NULL' );

			else
				error_log( $data );

		return FALSE; // help the caller
	}

	// INTERNAL: used on anything deprecated
	// TODO: new syntax on php 8.4: `#[\Deprecated(message)]`
	protected static function _dep( $note = '', $prefix = 'DEP: ', $offset = 1 )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		$trace = debug_backtrace();

		$log = $prefix;

		if ( isset( $trace[$offset]['object'] ) )
			$log.= get_class( $trace[$offset]['object'] ).'::';

		else if ( isset( $trace[$offset]['class'] ) )
			$log.= $trace[$offset]['class'].'::';

		$log.= $trace[$offset]['function'].'()';

		$offset++;

		if ( isset( $trace[$offset]['function'] ) ) {

			$log.= '|FROM: ';

			if ( isset( $trace[$offset]['object'] ) )
				$log.= get_class( $trace[$offset]['object'] ).'::';

			else if ( isset( $trace[$offset]['class'] ) )
				$log.= $trace[$offset]['class'].'::';

			$log.= $trace[$offset]['function'].'()';
		}

		if ( $note )
			$log.= '|'.$note;

		error_log( $log );
	}

	// INTERNAL: used on anything deprecated : only on dev mode
	protected static function _dev_dep( $note = '', $prefix = 'DEP: ', $offset = 2 )
	{
		if ( WordPress::isDev() )
			self::_dep( $note, $prefix, $offset );
	}

	// INTERNAL: used on functions deprecated
	public static function _dev_func( $func, $version, $replacement = NULL )
	{
		if ( is_null( $replacement ) )
			self::_log( sprintf( 'DEP: \'%1$s\' function, since %2$s with no alternative', $func, $version ) );
		else
			self::_log( sprintf( 'DEP: \'%1$s\' function, since %2$s, Use \'%3$s\'', $func, $version, $replacement ) );
	}

	public static function console( $data, $table = FALSE )
	{
		$func = $table ? 'table' : 'log';

		if ( is_array( $data ) || is_object( $data ) )
			echo '<script>console.'.$func.'('.wp_json_encode( $data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ).');</script>';
		else
			echo '<script>console.'.$func.'('.$data.');</script>';
	}

	public static function _log_trace()
	{
		// http://stackoverflow.com/a/7039409
		$e = new \Exception();
		self::_log( $e->getTraceAsString() );
	}

	public static function trace( $old = TRUE )
	{
		// https://gist.github.com/eddieajau/2651181
		if ( $old ) {
			foreach ( debug_backtrace() as $trace )
				printf( "\n%s:%s %s::%s", $trace['file'], $trace['line'], $trace['class'], $trace['function'] );
			die();
		}

		// http://stackoverflow.com/a/7039409
		$e = new \Exception();
		self::dump( $e->getTraceAsString() );
		die();
	}

	// USAGE: Base::callStack( debug_backtrace() );
	// @REF: http://stackoverflow.com/a/8497530
	public static function callStack( $stacktrace )
	{
		print str_repeat( '=', 50 )."\n";
		$i = 1;
		foreach ( $stacktrace as $node ) {
			print "$i. ".basename( $node['file'] ).':'.$node['function'].'('.$node['line'].")\n";
			$i++;
		}
	}

	public static function stat( $format = NULL )
	{
		if ( is_null( $format ) )
			$format = '%d queries in %.3f seconds, using %.2fMB memory.';

		return sprintf( $format,
			@$GLOBALS['wpdb']->num_queries,
			self::timerStop( FALSE, 3 ),
			memory_get_peak_usage() / 1024 / 1024
		);
	}

	// WP core function without number_format_i18n
	public static function timerStop( $verbose = FALSE, $precision = 3 )
	{
		global $timestart;
		$total = number_format( ( microtime( TRUE ) - $timestart ), $precision );
		if ( $verbose ) echo $total;
		return $total;
	}

	public static function isFuncDisabled( $func = NULL )
	{
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		if ( is_null( $func ) )
			return $disabled;

		return in_array( $func, $disabled, TRUE );
	}

	// http://stackoverflow.com/a/13272939
	public static function varSize( $var )
	{
		try {

			$start_memory = memory_get_usage();
			$var = unserialize( serialize( $var ) );
			return memory_get_usage() - $start_memory - PHP_INT_SIZE * 8;

		} catch ( \Exception $e ) {

			self::_log( 'varSize() :: '.$e->getMessage() );

			return 0;
		}
	}

	/**
	 * Converts a comma- or space-separated list of scalar values to an array.
	 * @ref: `wp_parse_list()`
	 *
	 * @param mixed $input
	 * @return array $list
	 */
	public static function list( $input )
	{
		if ( ! is_array( $input ) )
			return preg_split( '/[\s,]+/', $input, -1, PREG_SPLIT_NO_EMPTY );

		// NOTE: A value is considered scalar if it is of type `int`, `float`, `string` or `bool`.
		return array_filter( $input, 'is_scalar' );
	}

	/**
	 * Cleans up an array, comma- or space-separated list of IDs.
	 * @ref `wp_parse_id_list()`
	 *
	 * @param mixed $input
	 * @return array $ids
	 */
	public static function ids( $input )
	{
		return Arraay::prepNumeral( self::list( $input ) );
	}

	// @REF: `shortcode_atts()`
	public static function atts( $pairs, $atts )
	{
		$atts = (array) $atts;
		$out  = [];

		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}

		return $out;
	}

	// @REF: `wp_parse_args()`
	public static function args( $args, $defaults = '' )
	{
		if ( is_object( $args ) )
			$r = get_object_vars( $args );

		else if ( is_array( $args ) )
			$r = &$args;

		else
			// wp_parse_str( $args, $r );
			parse_str( $args, $r );

		if ( is_array( $defaults ) )
			return array_merge( $defaults, $r );

		return $r;
	}

	/**
	 * Recursive Argument Parsing
	 * @source: https://gist.github.com/boonebgorges/5510970
	 *
	 * Values from `$a` override those from `$b`; keys in `$b` that don't exist
	 * in `$a` are passed through.
	 *
	 * This is different from `array_merge_recursive()`, both because of the
	 * order of preference (`$a` overrides `$b`) and because of the fact that
	 * `array_merge_recursive()` combines arrays deep in the tree, rather
	 * than overwriting the `$b` array with the `$a` array.
	*/
	public static function recursiveParseArgs( &$a, $b )
	{
		$a = (array) $a;
		$b = (array) $b;
		$r = $b;

		foreach ( $a as $k => &$v )
			if ( is_array( $v ) && isset( $r[$k] ) )
				$r[$k] = self::recursiveParseArgs( $v, $r[$k] );
			else
				$r[$k] = $v;

		return $r;
	}

	// @SOURCE: https://github.com/kallookoo/wp_parse_args_recursive
	public static function recursiveParseArgsALT( $args, $defaults, $preserve_type = TRUE, $preserve_integer_keys = FALSE )
	{
		$output = [];

		foreach ( [ $defaults, $args ] as $list ) {

			foreach ( (array) $list as $key => $value ) {

				if ( is_integer( $key ) && ! $preserve_integer_keys ) {

					$output[] = $value;

				} else if ( isset( $output[$key] )
					&& ( is_array( $output[$key] ) || is_object( $output[$key] ) )
					&& ( is_array( $value ) || is_object( $value ) ) ) {

					$output[$key] = self::recursiveParseArgsALT( $value, $output[$key], $preserve_type, $preserve_integer_keys );

				} else {

					$output[$key] = $value;
				}
			}
		}

		return ( $preserve_type && ( is_object( $args ) || is_object( $defaults ) ) ) ? (object) $output : $output;
	}

	// maps a function to all non-iterable elements of an array or an object
	// this is similar to `array_walk_recursive()` but acts upon objects too
	// @REF: `map_deep()`
	public static function mapDeep( $data, $callback )
	{
		if ( is_array( $data ) )
			foreach ( $data as $index => $item )
				$data[$index] = self::mapDeep( $item, $callback );

		else if ( is_object( $data ) )
			foreach ( get_object_vars( $data ) as $name => $value )
				$data->$name = self::mapDeep( $value, $callback );

		else
			$data = call_user_func( $callback, $data );

		return $data;
	}

	// remove slashes from a string or array of strings
	// @REF: `wp_unslash()`
	// @REF: `stripslashes_deep()`
	public static function unslash( $array )
	{
		if ( empty( $array ) )
			return $array;

		return self::mapDeep( $array, static function ( $value ) {
			return is_string( $value ) ? stripslashes( $value ) : $value;
		} );
	}

	// @REF: `wp_validate_boolean()`
	public static function validateBoolean( $var )
	{
		if ( is_bool( $var ) )
			return $var;

		if ( is_string( $var ) && 'false' === strtolower( $var ) )
			return FALSE;

		return (bool) $var;
	}

	/**
	 * Swaps the values of two variables.
	 * NOTE: There isn't a built-in function!
	 * @source https://stackoverflow.com/a/26549027
	 *
	 * @param  mixed $x
	 * @param  mixed $y
	 * @return void
	 */
	public static function swap( &$x, &$y )
	{
		$t = $x;
		$x = $y;
		$y = $t;
	}

	// ANCESTOR: is_wp_error()
	public static function isError( $thing )
	{
		return ( ( $thing instanceof \WP_Error ) || ( $thing instanceof Error ) );
	}

	// NOTE: USAGE: `self::triggerError( __FUNCTION__, 'This is the Message' );`
	public static function triggerError( $function_name, $message, $error_level = NULL )
	{
		return function_exists( 'wp_trigger_error' )
			? wp_trigger_error( $function_name, $message, $error_level ?? E_USER_NOTICE )
			: trigger_error( $message, $error_level ?? E_USER_NOTICE );
	}
}

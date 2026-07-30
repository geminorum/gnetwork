<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Base
{
	public static function spc(): string
	{
		return Text::glued( func_get_args(), ' ' );
	}

	public static function dot(): string
	{
		return Text::glued( func_get_args(), '.' );
	}

	public static function dsh(): string
	{
		return Text::glued( func_get_args(), '-' );
	}

	public static function und(): string
	{
		return Text::glued( func_get_args(), '_' );
	}

	// NOTE: pseudo magic method!
	public function setVars( array $args ): array
	{
		$keys    = array_keys( get_object_vars( $this ) );
		$updated = [];

		foreach ( $keys as $key ) {
			if ( isset( $args[$key] ) ) {
				$this->{$key} = $args[$key];
				$updated[] = $key;
			}
		}

		return $updated;
	}

	// NOTE: pseudo magic method!
	public function setVar( string $key, mixed $value ): mixed
	{
		return $this->{$key} = $value;
	}

	// NOTE: pseudo magic method!
	public function getVar( string $key, mixed $default = NULL ): mixed
	{
		return isset( $this->{$key} ) ? $this->{$key} : $default;
	}

	public static function define( string $name, mixed $value ): bool
	{
		if ( $name && ! defined( $name ) )
			return define( $name, $value );

		return FALSE;
	}

	public static function const( string $name, mixed $default = FALSE ): mixed
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
	public static function classConst( string $const, ?object $object = NULL ): mixed
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

	/**
	 * Determines whether a variable is empty.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public static function empty( mixed $value ): bool
	{
		if ( empty( $value ) )
			return TRUE;

		if ( is_array( $value ) && empty( array_filter( $value ) ) )
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

	// converts into boolean
	// @REF: `wp_validate_boolean()`
	// @OLD: `Base::validateBoolean()`
	public static function bool( mixed $value ): bool
	{
		if ( is_bool( $value ) )
			return $value;

		if ( self::empty( $value ) )
			return FALSE;

		if ( is_string( $value ) && in_array( strtolower( $value ), [ 'false', 'none', '0', 'off', 'no' ], TRUE ) )
			return FALSE;

		return TRUE;
	}

	public static function req( string $key, mixed $default = '', false|string $subkey = FALSE ): mixed
	{
		return $subkey
			? ( $_REQUEST[$key][$subkey] ?? $default )
			: ( $_REQUEST[$key] ?? $default );
	}

	public static function do( string|array $values, string $key = 'action', mixed $default = FALSE ): mixed
	{
		if ( ! isset( $_REQUEST[$key] ) )
			return $default;

		foreach ( (array) $values as $value )
			if ( $value == $_REQUEST[$key] )
				return $value ?: TRUE;

		return $default;
	}

	public static function step( ?string $value = NULL, false|string $key = 'action', mixed $default = '' ): mixed
	{
		$request = self::req( $key, [] );
		$action  = is_array( $request )
			? Arraay::keyFirst( $request )
			: $request;

		if ( empty( $action ) && ! is_null( $value ) )
			return $default;

		return is_null( $value ) ? $action : ( (string) $action === (string) $value );
	}

	public static function limit( int $default = 25, string $key = 'limit' ): int
	{
		return (int) self::req( $key, $default );
	}

	public static function paged( int $default = 1, string $key = 'paged' ): int
	{
		return (int) self::req( $key, $default );
	}

	public static function orderby( string $default = 'title', string $key = 'orderby' ): string
	{
		return (string) self::req( $key, $default );
	}

	public static function order( string $default = 'desc', string $key = 'order' ): string
	{
		$req = strtoupper( self::req( $key, $default ) );
		return ( 'ASC' === $req || 'DESC' === $req ) ? $req : $default;
	}

	public static function buffer( callable $callback, array $args = [], mixed $fallback = '' ): mixed
	{
		if ( ! $callback || ! is_callable( $callback ) )
			return $fallback;

		ob_start();

			call_user_func_array( $callback, $args );

		return trim( ob_get_clean() );
	}

	public static function dumpSuccess(): void
	{
		echo '<div style="color:green;">';

		foreach ( func_get_args() as $arg )
			self::dump( $arg );

		echo '</div>';
	}

	public static function dumpError(): void
	{
		echo '<div style="color:red;">';

		foreach ( func_get_args() as $arg )
			self::dump( $arg );

		echo '</div>';
	}

	public static function dump( mixed $var, bool $safe = TRUE, bool $verbose = TRUE ): string|true
	{
		$export = var_export( $var, TRUE );
		if ( $safe ) $export = htmlspecialchars( $export );
		$export = '<pre dir="ltr" style="text-align:left;direction:ltr;">'.$export.'</pre>';
		if ( ! $verbose ) return $export;
		echo $export;
		return TRUE;
	}

	public static function kill(): void
	{
		foreach ( func_get_args() as $arg )
			self::dump( $arg );
		echo self::stat();
		die ();
	}

	public static function cheatin( ?string $message = NULL ): bool
	{
		wp_die( $message ?? __( 'You don&#8217;t have permission to do this.' ), 403 );

		return FALSE; // help the caller
	}

	public static function _log_req(): false
	{
		return self::_log( $_REQUEST );
	}

	public static function _log_error(): false
	{
		foreach ( func_get_args() as $error )
			if ( self::isError( $error ) )
				self::_log( vsprintf( '%s :: %s', [
					strtoupper( $error->get_error_code() ?: 'Error' ),
					$error->get_error_message()
				] ) );

			else if ( $error )
				self::_log( $error );

		return FALSE; // help the caller
	}

	// INTERNAL
	public static function _log(): false
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
	// TODO: new syntax on PHP 8.4: `#[\Deprecated(message)]`
	// @SEE: https://php.watch/versions/8.4/Deprecated
	protected static function _dep( string $note = '', string $prefix = 'DEP: ', int $offset = 1 ): void
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

		++$offset;

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
	protected static function _dev_dep( string $note = '', string $prefix = 'DEP: ', int $offset = 2 ): void
	{
		if ( 'development' === self::const( 'WP_STAGE' ) )
			self::_dep( $note, $prefix, $offset );
	}

	// INTERNAL: used on functions deprecated
	public static function _dev_func( string $func, string $version, ?string $replacement = NULL ): void
	{
		if ( is_null( $replacement ) )
			self::_log( sprintf( 'DEP: \'%1$s\' function, since %2$s with no alternative', $func, $version ) );

		else
			self::_log( sprintf( 'DEP: \'%1$s\' function, since %2$s, Use \'%3$s\'', $func, $version, $replacement ) );
	}

	public static function console( mixed $data, bool $table = FALSE ): void
	{
		$func = $table ? 'table' : 'log';

		if ( is_array( $data ) || is_object( $data ) )
			echo '<script>console.'.$func.'('.wp_json_encode( $data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ).');</script>';
		else
			echo '<script>console.'.$func.'('.$data.');</script>';
	}

	public static function _log_trace(): void
	{
		// http://stackoverflow.com/a/7039409
		$e = new \Exception();
		self::_log( $e->getTraceAsString() );
	}

	public static function trace( bool $old = TRUE ): void
	{
		// https://gist.github.com/eddieajau/2651181
		if ( $old ) {
			foreach ( debug_backtrace() as $trace )
				printf( "\n%s:%s %s::%s", $trace['file'], $trace['line'], $trace['class'], $trace['function'] );
			die ();
		}

		// http://stackoverflow.com/a/7039409
		$e = new \Exception();
		self::dump( $e->getTraceAsString() );
		die ();
	}

	// USAGE: `Base::callStack( debug_backtrace() );`
	// @REF: http://stackoverflow.com/a/8497530
	public static function callStack( array $stacktrace ): void
	{
		print str_repeat( '=', 50 )."\n";
		$i = 1;

		foreach ( $stacktrace as $node ) {
			print "$i. ".basename( $node['file'] ).':'.$node['function'].'('.$node['line'].")\n";
			++$i;
		}
	}

	public static function stat( ?string $format = NULL ): string
	{
		return sprintf( $format ?? '%d queries in %.3f seconds, using %.2fMB memory.',
			@$GLOBALS['wpdb']->num_queries,
			self::timerStop( FALSE, 3 ),
			memory_get_peak_usage() / 1024 / 1024
		);
	}

	// NOTE: WP core function without `number_format_i18n()`
	public static function timerStop( bool $verbose = FALSE, int $precision = 3 ): string|true
	{
		global $timestart;

		$total = number_format( ( microtime( TRUE ) - $timestart ), $precision );

		if ( ! $verbose )
			return $total;

		echo $total;
		return TRUE;
	}

	public static function isFuncDisabled( ?string $func = NULL ): array|bool
	{
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		if ( is_null( $func ) )
			return $disabled;

		return in_array( $func, $disabled, TRUE );
	}

	// http://stackoverflow.com/a/13272939
	public static function varSize( mixed $var ): int
	{
		try {

			$start = memory_get_usage();
			$var   = unserialize( serialize( $var ) );

			return memory_get_usage() - $start - PHP_INT_SIZE * 8;

		} catch ( \Exception $e ) {

			self::_log( 'Exception: `varSize()` :: '.$e->getMessage() );

			return 0;
		}
	}

	/**
	 * Converts a comma or space-separated list of scalar values to an array.
	 * @source: `wp_parse_list()`
	 *
	 * @param int|string|array $input
	 * @return array
	 */
	public static function parseList( int|string|array $input ): array
	{
		if ( ! is_array( $input ) )
			return preg_split( '/[\s,]+/', $input, -1, PREG_SPLIT_NO_EMPTY );

		// NOTE: A value is considered scalar if it is of type `int`, `float`, `string` or `bool`.
		return array_filter( $input, 'is_scalar' );
	}

	/**
	 * Cleans up an array, comma, or space-separated list of IDs.
	 * @source `wp_parse_id_list()`
	 *
	 * @param int|string|array $input
	 * @return array
	 */
	public static function parseIDs( int|string|array $input ): array
	{
		return Arraay::prepNumeral( self::parseList( $input ) );
	}

	// @REF: `shortcode_atts()`
	#[\Deprecated('USE `Core\Base::parsed()`')]
	public static function atts( array $pairs, array $atts ): array
	{
		$out = [];

		foreach ( $pairs as $name => $default )
			$out[$name] = array_key_exists( $name, $atts ) ? $atts[$name] : $default;

		return $out;
	}

	/**
	 * Combines provided arguments with expected and fill in defaults when needed.
	 * NOTE: difference with `Base::args()` is that only return existing keys on defaults.
	 *
	 * @old `Base::atts()`
	 * @source `shortcode_atts()`
	 *
	 * @param array $defaults
	 * @param string|array $data
	 * @return array
	 */
	public static function parsed( array $defaults, string|array $data ): array
	{
		$parsed = [];
		$data   = self::args( $data );

		if ( empty( $defaults ) )
			return $parsed;

		if ( empty( $data ) )
			return $defaults;

		foreach ( $defaults as $key => $value ) {

			if ( array_key_exists( $key, $data ) )
				$parsed[$key] = $data[$key];

			else
				$parsed[$key] = $value;
		}

		return $parsed;
	}

	/**
	 * Merges user defined arguments into defaults array.
	 * Allows for both string or array to be merged into another array.
	 * NOTE: difference with `Base::parsed()` is that merges data with defaults.
	 *
	 * @source `wp_parse_args()`
	 *
	 * @param string|array $arguments
	 * @param array $defaults
	 * @return array
	 */
	public static function args( string|array $arguments, array $defaults = [] ): array
	{
		$parsed = [];

		if ( is_object( $arguments ) )
			$parsed = get_object_vars( $arguments );

		else if ( is_array( $arguments ) )
			$parsed = &$arguments;

		else if ( ! empty( $args ) )
			parse_str( (string) $arguments, $parsed );

		if ( is_array( $defaults ) && $defaults )
			return array_merge( $defaults, $parsed );

		return $parsed;
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
	public static function recursiveParseArgs( array &$a, array $b ): array
	{
		$result = $b;

		foreach ( $a as $key => &$value )
			if ( is_array( $value ) && isset( $result[$key] ) )
				$result[$key] = self::recursiveParseArgs( $value, $result[$key] );

			else
				$result[$key] = $value;

		return $result;
	}

	// @SOURCE: https://github.com/kallookoo/wp_parse_args_recursive
	public static function recursiveParseArgsALT(
		array|object $args,
		array|object $defaults,
		bool $preserve_type = TRUE,
		bool $preserve_integer_keys = FALSE,
	): array {

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

	// Maps a function to all non-iterable elements of an array or an object.
	// NOTE: similar to `array_walk_recursive()` but acts upon objects too.
	// @REF: `map_deep()`
	public static function mapDeep( mixed $data, callable $callback ): mixed
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

	/**
	 * Removes slashes from given string or array of strings
	 * @source `wp_unslash()` / `stripslashes_deep()`
	 *
	 * @param string|array $array
	 * @return string|array
	 */
	public static function unslash( string|array $array ): string|array
	{
		if ( empty( $array ) )
			return $array;

		return self::mapDeep( $array, static function ( $value ) {
			return is_string( $value ) ? stripslashes( $value ) : $value;
		} );
	}

	#[\Deprecated('USE `Core\Base::bool()`')]
	public static function validateBoolean( mixed $var ): bool
	{
		return self::bool( $var );
	}

	/**
	 * Swaps the values of two variables.
	 * NOTE: There isn't a built-in function!
	 * @source https://stackoverflow.com/a/26549027
	 *
	 * @param mixed $x
	 * @param mixed $y
	 * @return void
	 */
	public static function swap( mixed &$x, mixed &$y ): void
	{
		$t = $x;
		$x = $y;
		$y = $t;
	}

	// ANCESTOR: `is_wp_error()`
	public static function isError( mixed $thing ): bool
	{
		return ( ( $thing instanceof \WP_Error ) || ( $thing instanceof Error ) );
	}

	/**
	 * Generates a user-level error/warning/notice/deprecation message.
	 * NOTE: wrapper for `wp_trigger_error()` with fallback.
	 * @example `self::triggerError( __FUNCTION__, 'This is the Message' );`
	 *
	 * @param string $function_name
	 * @param string $message
	 * @param int|null $error_level
	 * @return false
	 */
	public static function triggerError( string $function_name, string $message, ?int $error_level = NULL ): false
	{
		if ( function_exists( 'wp_trigger_error' ) )
			wp_trigger_error( $function_name, $message, $error_level ?? E_USER_NOTICE );

		else
			trigger_error( $message, $error_level ?? E_USER_NOTICE );

		return FALSE; // to help the caller!
	}
}

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Base
{

	public static function dump( $var, $htmlsafe = TRUE, $echo = TRUE )
	{
		$result = var_export( $var, TRUE );

		$html = '<pre dir="ltr" style="text-align:left;direction:ltr;">'
			.( $htmlsafe ? htmlspecialchars( $result ) : $result ).'</pre>';

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public static function kill( $var = FALSE )
	{
		if ( $var )
			self::dump( $var );

		// FIXME: add query/memory/time info

		die();
	}

	public static function dumpDev( $var )
	{
		if ( WordPress::isDev() )
			self::dump( $var );
	}

	// INTERNAL: used on anything deprecated
	protected static function __dep( $note = '', $prefix = 'DEP: ', $offset = 1 )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		$trace = debug_backtrace();

		$log = $prefix;

		if ( isset( $trace[$offset]['object'] ) )
			$log .= get_class( $trace[$offset]['object'] ).'::';
		else if ( isset( $trace[$offset]['class'] ) )
			$log .= $trace[$offset]['class'].'::';

		$log .= $trace[$offset]['function'].'()';

		$offset++;

		if ( isset( $trace[$offset]['function'] ) ) {
			$log .= '|FROM: ';
			if ( isset( $trace[$offset]['object'] ) )
				$log .= get_class( $trace[$offset]['object'] ).'::';
			else if ( isset( $trace[$offset]['class'] ) )
				$log .= $trace[$offset]['class'].'::';
			$log .= $trace[$offset]['function'].'()';
		}

		if ( $note )
			$log .= '|'.$note;

		error_log( $log );
	}

	// INTERNAL: used on anything deprecated : only on dev mode
	protected static function __dev_dep( $note = '', $prefix = 'DEP: ', $offset = 2 )
	{
		if ( WordPress::isDev() )
			self::__dep( $note, $prefix, $offset );
	}

	public static function console( $data, $table = FALSE )
	{
		$func = $table ? 'table' : 'log';

		if ( is_array( $data ) || is_object( $data ) )
			echo '<script>console.'.$func.'('.wp_json_encode($data).');</script>';
		else
			echo '<script>console.'.$func.'('.$data.');</script>';
	}

	public static function trace( $old = TRUE )
	{
		// https://gist.github.com/eddieajau/2651181
		if ( $old ) {
			foreach ( debug_backtrace() as $trace )
				echo sprintf( "\n%s:%s %s::%s", $trace['file'], $trace['line'], $trace['class'], $trace['function'] );
			die();
		}

		// http://stackoverflow.com/a/7039409
		$e = new Exception;
		self::dump( $e->getTraceAsString() );
		die();
	}

	public static function stat( $format = NULL )
	{
		if ( is_null( $format ) )
			$format = '%d queries in %.3f seconds, using %.2fMB memory.';

		return sprintf( $format,
			get_num_queries(),
			self::timer_stop( FALSE, 3 ),
			memory_get_peak_usage() / 1024 / 1024
		);
	}

	// WP core function without number_format_i18n
	public static function timer_stop( $echo = FALSE, $precision = 3 )
	{
		global $timestart;

		$html = number_format( ( microtime( TRUE ) - $timestart ), $precision );

		if ( $echo )
			echo $html;

		return $html;
	}

	public static function isFuncDisabled( $func = NULL )
	{
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		if ( is_null( $func ) )
			return $disabled;

		return in_array( $func, $disabled );
	}

	// http://stackoverflow.com/a/13272939
	public static function size( $var )
	{
		$start_memory = memory_get_usage();
		$var = unserialize( serialize( $var ) );
		return memory_get_usage() - $start_memory - PHP_INT_SIZE * 8;
	}

	public static function range( $start, $end, $step = 1, $format = TRUE )
	{
		$array = array();

		foreach ( range( $start, $end, $step ) as $number )
			$array[$number] = $format ? number_format_i18n( $number ) : $number;

		return $array;
	}

	public static function req( $key, $default = '' )
	{
		return empty( $_REQUEST[$key] ) ? $default : $_REQUEST[$key];
	}

	public static function limit( $default = 25, $key = 'limit' )
	{
		return intval( self::req( $key, $default ) );
	}

	public static function paged( $default = 1, $key = 'paged' )
	{
		return intval( self::req( $key, $default ) );
	}

	public static function orderby( $default = 'title', $key = 'orderby' )
	{
		return self::req( $key, $default );
	}

	public static function order( $default = 'desc', $key = 'order' )
	{
		return self::req( $key, $default );
	}

	public static function elog( $data )
	{
		error_log( print_r( compact( 'data' ), TRUE ) );
	}

	// USE: BaseCore::callStack( debug_backtrace() );
	// http://stackoverflow.com/a/8497530
	public static function callStack( $stacktrace )
	{
		print str_repeat( '=', 50 )."\n";
		$i = 1;
		foreach ( $stacktrace as $node ) {
			print "$i. ".basename( $node['file'] ).':'.$node['function'].'('.$node['line'].")\n";
			$i++;
		}
	}

	public static function genRandomKey( $salt )
	{
		$chr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$len = 32;
		$key = '';

		for ( $i = 0; $i < $len; $i++ )
			$key .= $chr[( wp_rand( 0,( strlen( $chr ) - 1 ) ) )];

		return md5( $salt.$key );
	}

	// will remove trailing forward and backslashes if it exists already before adding
	// a trailing forward slash. This prevents double slashing a string or path.
	// ANCESTOR: trailingslashit()
	public static function trail( $string )
	{
		return self::untrail( $string ).'/';
	}

	// removes trailing forward slashes and backslashes if they exist.
	// ANCESTOR: untrailingslashit()
	public static function untrail( $string )
	{
		return rtrim( $string, '/\\' );
	}

	public static function error( $message, $echo = FALSE )
	{
		return HTML::notice( $message, 'notice-error fade', $echo );
	}

	// FIXME: DEPRICATED: use `HTML::success()`
	public static function updated( $message, $echo = FALSE )
	{
		self::__dev_dep( 'HTML::success()' );
		return HTML::notice( $message, 'notice-success fade', $echo );
	}

	public static function success( $message, $echo = FALSE )
	{
		return HTML::notice( $message, 'notice-success fade', $echo );
	}

	public static function warning( $message, $echo = FALSE )
	{
		return HTML::notice( $message, 'notice-warning fade', $echo );
	}

	public static function info( $message, $echo = FALSE )
	{
		return HTML::notice( $message, 'notice-info fade', $echo );
	}

	public static function log( $error = '[Unknown]', $message = FALSE, $extra = FALSE )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG )
			error_log( self::getLogTime()
				.$error.' '
				.HTTP::IP( TRUE )
				.( $message ? ' :: '.strip_tags( $message ) : '' )
				.( $extra ? ' :: '.$extra : '' )
				."\n", 3, GNETWORK_DEBUG_LOG );
	}

	// EXAMPLE: [03-Feb-2015 21:20:19 UTC]
	public static function getLogTime()
	{
		return '['.gmdate( 'd-M-Y H:i:s e' ).'] ';
	}

	public static function logArray( $error = '[Unknown]', $data = array(), $wp_error = NULL )
	{
		if ( ! WP_DEBUG_LOG )
			return;

		$log = array_merge( array(
			'error'   => $error,
			'time'    => current_time( 'mysql' ),
			'ip'      => HTTP::IP(),
			'message' => ( is_null( $wp_error ) ? '[NO Error Object]' : $wp_error->get_error_message() ),
		), $data );

		error_log( print_r( $log, TRUE ) );
	}

	// ANCESTOR: shortcode_atts()
	public static function atts( $pairs, $atts )
	{
		$atts = (array) $atts;
		$out  = array();

		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}

		return $out;
	}

	// ANCESTOR: wp_parse_args()
	public static function args( $args, $defaults = '' )
	{
		if ( is_object( $args ) )
			$r = get_object_vars( $args );

		elseif ( is_array( $args ) )
			$r = &$args;

		else
			// wp_parse_str( $args, $r );
			parse_str( $args, $r );

		if ( is_array( $defaults ) )
			return array_merge( $defaults, $r );

		return $r;
	}

	// recursive argument parsing
	// @SOURCE: https://gist.github.com/boonebgorges/5510970
	/***
	* Values from $a override those from $b; keys in $b that don't exist
	* in $a are passed through.
	*
	* This is different from array_merge_recursive(), both because of the
	* order of preference ($a overrides $b) and because of the fact that
	* array_merge_recursive() combines arrays deep in the tree, rather
	* than overwriting the b array with the a array.
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

	public static function redirect( $location = NULL, $status = 302 )
	{
		if ( is_null( $location ) )
			$location = add_query_arg( wp_get_referer() );

		wp_redirect( $location, $status );
		exit();
	}

	public static function redirect_referer( $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, wp_get_referer() );
		else
			$url = add_query_arg( $key, $message, wp_get_referer() );

		self::redirect( $url );
	}

	public static function redirect_login( $location = '', $status = 302 )
	{
		self::redirect( wp_login_url( $location, TRUE ), $status );
	}

	// ANCESTOR: is_wp_error()
	public static function isError( $thing )
	{
		return ( ( $thing instanceof \WP_Error ) || ( $thing instanceof Error ) );
	}
}

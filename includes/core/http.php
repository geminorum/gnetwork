<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class HTTP extends Base
{

	// http://code.tutsplus.com/tutorials/a-look-at-the-wordpress-http-api-a-brief-survey-of-wp_remote_get--wp-32065
	// http://wordpress.stackexchange.com/a/114922
	public static function getJSON( $url, $atts = array(), $assoc = FALSE )
	{
		$args = self::recursiveParseArgs( $atts, array(
			'timeout' => 15,
		) );

		$response = wp_remote_get( $url, $args );

		if ( ! self::isError( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return json_decode( wp_remote_retrieve_body( $response ), $assoc );
		}

		return FALSE;
	}

	public static function getHTML( $url, $atts = array() )
	{
		$args = self::recursiveParseArgs( $atts, array(
			'timeout' => 15,
		) );

		$response = wp_remote_get( $url, $args );

		if ( ! self::isError( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return wp_remote_retrieve_body( $response );
		}

		return FALSE;
	}

	public static function getContents( $url )
	{
		$handle = curl_init();

		curl_setopt( $handle, CURLOPT_URL, $url );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, TRUE );

		$contents = curl_exec( $handle );

		curl_close( $handle );

		if ( ! $contents )
			return FALSE;

		return $contents;
	}

	// @SOURCE: http://stackoverflow.com/a/8891890/4864081
	public static function currentURL( $trailingslashit = FALSE, $forwarded_host = FALSE )
	{
		$ssl  = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
		$sp   = strtolower( $_SERVER['SERVER_PROTOCOL'] );
		$prot = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
		$port = $_SERVER['SERVER_PORT'];
		$port = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
		$host = ( $forwarded_host && isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : NULL );
		$host = isset( $host ) ? $host : $_SERVER['SERVER_NAME'].$port;

		return $prot.'://'.$host.$_SERVER['REQUEST_URI'];
	}

	// @SOURCE: wp_get_raw_referer()
	public static function referer()
	{
		if ( ! empty( $_REQUEST['_wp_http_referer'] ) )
			return wp_unslash( $_REQUEST['_wp_http_referer'] );

		if ( ! empty( $_SERVER['HTTP_REFERER'] ) )
			return wp_unslash( $_SERVER['HTTP_REFERER'] );

		return FALSE;
	}

	public static function IP( $pad = FALSE )
	{
		$ip = '';

		if ( getenv( 'HTTP_CLIENT_IP' ) )
			$ip = getenv( 'HTTP_CLIENT_IP' );

		else if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_X_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_X_FORWARDED' ) )
			$ip = getenv( 'HTTP_X_FORWARDED' );

		else if ( getenv( 'HTTP_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_FORWARDED' ) )
			$ip = getenv( 'HTTP_FORWARDED' );

		else
			$ip = getenv( 'REMOTE_ADDR' );

		if ( $pad )
			return str_pad( $ip, 15, ' ', STR_PAD_LEFT );

		return $ip;
	}

	public static function headers( $array )
	{
		foreach ( $array as $h => $k )
			@header( "{$h}: {$k}", TRUE );
	}

	public static function headerRetryInMinutes( $minutes = '30' )
	{
		@header( "Retry-After: ".( absint( $minutes ) * MINUTE_IN_SECONDS ) );
	}

	public static function headerContentUTF8()
	{
		@header( "Content-Type: text/html; charset=utf-8" );
	}
}

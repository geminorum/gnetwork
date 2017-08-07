<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class HTTP extends Base
{

	// if this is a POST request
	public static function isPOST()
	{
		return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
	}

	// if this is a GET request
	public static function isGET()
	{
		return (bool) ( 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
	}

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
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		$handle = curl_init();

		curl_setopt( $handle, CURLOPT_URL, $url );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, TRUE );

		$contents = curl_exec( $handle );

		curl_close( $handle );

		if ( ! $contents )
			return FALSE;

		return $contents;
	}

	// @SOURCE: `wp_get_raw_referer()`
	public static function referer()
	{
		if ( ! empty( $_REQUEST['_wp_http_referer'] ) )
			return self::unslash( $_REQUEST['_wp_http_referer'] );

		if ( ! empty( $_SERVER['HTTP_REFERER'] ) )
			return self::unslash( $_SERVER['HTTP_REFERER'] );

		return FALSE;
	}

	// @REF: `WP_Community_Events::get_unsafe_client_ip()`
	public static function IP( $pad = FALSE )
	{
		$ip = '';

		if ( getenv( 'HTTP_CLIENT_IP' ) )
			$ip = getenv( 'HTTP_CLIENT_IP' );

		else if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_X_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_X_FORWARDED' ) )
			$ip = getenv( 'HTTP_X_FORWARDED' );

		else if ( getenv( 'HTTP_X_CLUSTER_CLIENT_IP' ) )
			$ip = getenv( 'HTTP_X_CLUSTER_CLIENT_IP' );

		else if ( getenv( 'HTTP_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_FORWARDED' ) )
			$ip = getenv( 'HTTP_FORWARDED' );

		else
			$ip = getenv( 'REMOTE_ADDR' );

		// HTTP_X_FORWARDED_FOR can contain a chain of comma-separated addresses
		$ip = explode( ',', $ip );
		$ip = trim( $ip[0] );

		$ip = self::normalizeIP( $ip );

		if ( $pad )
			return str_pad( $ip, 15, ' ', STR_PAD_LEFT );

		return $ip;
	}

	public static function normalizeIP( $ip )
	{
		return trim( preg_replace( '/[^0-9a-fA-F:., ]/', '', stripslashes( $ip ) ) );
	}

	public static function IPinRange( $ip, $range )
	{
		// 1.2.3/24  OR  1.2.3.4/255.255.255.0
		if ( FALSE !== strpos( $range, '/' ) )
			return self::IPinCIDR( $ip, $range );

		// 255.255.*.*
		if ( FALSE !== strpos( $range, '*' ) )
			$range = ( str_replace( '*', '0', $range )
				.'-'.str_replace( '*', '255', $range ) );

		$long = ip2long( $ip );

		// 1.6.0.0 - 1.7.255.255
		if ( FALSE !== strpos( $range, '-' ) ) {

			$block = array_map( 'trim', explode( '-', $range, 2 ) );

			if ( $long >= ip2long( $block[0] )
				&& $long <= ip2long( $block[1] ) )
					return TRUE;
		}

		// 1.8.0.1
		if ( $long == ip2long( trim( $range ) ) )
			return TRUE;

		return FALSE;
	}

	// @REF: https://stackoverflow.com/a/594134/4864081
	public static function IPinCIDR( $ip, $range )
	{
		list ( $subnet, $bits ) = explode( '/', $range );

		$ip     = ip2long( $ip );
		$subnet = ip2long( $subnet );
		$mask   = -1 << ( 32 - $bits );

		// in case the supplied subnet wasn't correctly aligned
		$subnet &= $mask;

		return ( $ip & $mask ) == $subnet;
	}

	/**
	 * Check if a given ip is in a network
	 * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
	 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
	 * @return boolean true if the ip is in this range / false if not.
	 *
	 * @REF: https://gist.github.com/tott/7684443
	 */
	public static function ip_is_in_range( $ip, $range )
	{
		if ( FALSE === strpos( $range, '/' ) )
			$range .= '/32';

		// $range is in IP/CIDR format eg 127.0.0.1/24
		list( $range, $netmask ) = explode( '/', $range, 2 );

		$range_decimal    = ip2long( $range );
		$ip_decimal       = ip2long( $ip );
		$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
		$netmask_decimal  = ~ $wildcard_decimal;

		return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
	}

	// @REF: https://gist.github.com/tott/7684443#gistcomment-1645778
	public static function CheckIpRange($ip, $min, $max)
	{
		return ( ip2long( $min ) < ip2long( $ip ) && ip2long( $ip ) < ip2long( $max ) );
	}

	// a quick method to convert a netmask (ex: 255.255.255.240) to a cidr mask (ex: /28):
	// xor-ing will give you the inverse mask, log base 2 of that +1 will return
	// the number of bits that are off in the mask and subtracting from 32
	// gets you the cidr notation
	// @REF: http://php.net/manual/en/function.ip2long.php#94787
	public static function mask2cidr( $mask )
	{
		return 32 - log( ( ip2long( $mask ) ^ ip2long( '255.255.255.255' ) ) + 1, 2 );
	}

	// @REF: https://web.archive.org/web/20090429105552/http://pgregg.com/blog/2009/04/php-algorithms-determining-if-an-ip-is-within-a-specific-range.html
	// @REF: https://web.archive.org/web/20090503013408/http://pgregg.com:80/projects/php/ip_in_range/ip_in_range.phps
	/*
	 * ip_in_range.php - Function to determine if an IP is located in a
	 *                   specific range as specified via several alternative
	 *                   formats.
	 *
	 * Network ranges can be specified as:
	 * 1. Wildcard format:     1.2.3.*
	 * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
	 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
	 *
	 * Return value BOOLEAN : ip_in_range($ip, $range);
	 *
	 * Copyright 2008: Paul Gregg <pgregg@pgregg.com>
	 * 10 January 2008
	 * Version: 1.2
	 *
	 * Source website: http://www.pgregg.com/projects/php/ip_in_range/
	 * Version 1.2
	 *
	 * This software is Donationware - if you feel you have benefited from
	 * the use of this tool then please consider a donation. The value of
	 * which is entirely left up to your discretion.
	 * http://www.pgregg.com/donate/
	 *
	 * Please do not remove this header, or source attibution from this file.
	 */

	// decbin32
	// In order to simplify working with IP addresses (in binary) and their
	// netmasks, it is easier to ensure that the binary strings are padded
	// with zeros out to 32 characters - IP addresses are 32 bit numbers
	public static function decbin32( $dec )
	{
		return str_pad( decbin( $dec ), 32, '0', STR_PAD_LEFT );
	}

	// ip_in_range
	// This function takes 2 arguments, an IP address and a "range" in several
	// different formats.
	// Network ranges can be specified as:
	// 1. Wildcard format:     1.2.3.*
	// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
	// 3. Start-End IP format: 1.2.3.0-1.2.3.255
	// The function will return true if the supplied IP is within the range.
	// Note little validation is done on the range inputs - it expects you to
	// use one of the above 3 formats.
	public static function ip_in_range( $ip, $range )
	{
		if ( FALSE !== strpos( $range, '/' ) ) {

			// $range is in IP/NETMASK format
			list( $range, $netmask ) = explode( '/', $range, 2 );

			if ( FALSE !== strpos( $netmask, '.' ) ) {

				// $netmask is a 255.255.0.0 format
				$netmask     = str_replace( '*', '0', $netmask );
				$netmask_dec = ip2long( $netmask );

				return ( ( ip2long( $ip ) & $netmask_dec ) == ( ip2long( $range ) & $netmask_dec ) );

			} else {

				// $netmask is a CIDR size block
				// fix the range argument
				$x = explode( '.', $range );

				while( count( $x ) < 4 )
					$x[] = '0';

				list( $a, $b, $c, $d ) = $x;

				$range = sprintf( "%u.%u.%u.%u",
					empty( $a ) ? '0' : $a,
					empty( $b ) ? '0' : $b,
					empty( $c ) ? '0' : $c,
					empty( $d ) ? '0' : $d
				);

				$range_dec = ip2long( $range );
				$ip_dec    = ip2long( $ip );

				// strategy 1: create the netmask with 'netmask' 1s and then fill it to 32 with 0s
				// $netmask_dec = bindec( str_pad( '', $netmask, '1' ).str_pad( '', 32 - $netmask, '0' ) );

				// strategy 2: use math to create it
				$wildcard_dec = pow( 2, ( 32 - $netmask ) ) - 1;
				$netmask_dec  = ~ $wildcard_dec;

				return ( ( $ip_dec & $netmask_dec ) == ( $range_dec & $netmask_dec ) );
			}

		} else {

			// range might be 255.255.*.* or 1.2.3.0-1.2.3.255
			if ( FALSE !== strpos( $range, '*' ) ) {
				// a.b.*.* format

				// just convert to A-B format by setting * to 0 for A and 255 for B
				$lower = str_replace( '*', '0', $range );
				$upper = str_replace( '*', '255', $range );
				$range = "$lower-$upper";
			}

			if ( FALSE !== strpos( $range, '-' ) ) {
				// A-B format

				list( $lower, $upper ) = explode( '-', $range, 2 );

				$lower_dec = (float) sprintf( "%u", ip2long( $lower ) );
				$upper_dec = (float) sprintf( "%u", ip2long( $upper ) );
				$ip_dec    = (float) sprintf( "%u", ip2long( $ip ) );

				return ( ( $ip_dec >= $lower_dec ) && ( $ip_dec <= $upper_dec ) );
			}

			// range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format
			return FALSE;
		}
	}

	public static function headers( $array )
	{
		foreach ( $array as $h => $k )
			@header( "{$h}: {$k}", TRUE );
	}

	public static function headerRetryInMinutes( $minutes = '30' )
	{
		@header( "Retry-After: ".( absint( $minutes ) * 60 ) );
	}

	public static function headerContentUTF8()
	{
		@header( "Content-Type: text/html; charset=utf-8" );
	}

	// @REF: https://gist.github.com/eric1234/37fd102798d99d94b0dcebde6bb29ef3
	//
	// Abstracts the idiocy of the CURL API for something simpler. Assumes we are
	// downloading data (so a GET request) and we need no special request headers.
	// Returns an IO stream which will be the data requested. The headers of the
	// response will be stored in the $headers param reference.
	//
	// If the request fails for some reason FALSE is returned with the $err_msg
	// param containing more info.
	public static function download( $url, &$headers = array(), &$err_msg )
	{
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		$in_out  = curl_init( $url );
		$stream = fopen( 'php://temp', 'w+' );

		curl_setopt_array( $in_out, array(
			CURLOPT_FAILONERROR    => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_HEADER         => TRUE,
			CURLOPT_FILE           => $stream,
		) );

		if ( FALSE === curl_exec( $in_out ) ) {
			$err_msg << curl_error( $in_out );
			return FALSE;
		}

		curl_close( $in_out );
		rewind( $stream );

		$line = trim( fgets( $stream ) );

		if ( preg_match( '/^HTTP\/([^ ]+) (.*)/i', $line, $matches ) ) {
			$headers['HTTP_VERSION'] = $matches[1];
			$headers['STATUS']       = $matches[2];
		}

		while ( $line = fgets( $stream ) ) {
			if ( preg_match( '/^\s+$/', $line ) )
				break;
			list( $key, $value ) = preg_split( '/\s*:\s*/', $line, 2 );
			$headers[strtoupper( $key )] = trim( $value );
		}

		return $stream;
	}

	// @REF: http://arguments.callee.info/2010/02/21/multiple-curl-requests-with-php/
	// @REF: http://stackoverflow.com/a/9950468/4864081
	public static function checkURLs( $urls = array() )
	{
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		if ( ! count( $urls ) )
			return array();

		$ch = $results = array();

		$urls = array_values( array_unique( $urls ) );
		$mh   = curl_multi_init();

		for ( $i = 0; $i < count( $urls ); $i++ ) {

			$ch[$i] = curl_init();

			curl_setopt( $ch[$i], CURLOPT_URL, $urls[$i] );
			curl_setopt( $ch[$i], CURLOPT_RETURNTRANSFER, TRUE );
			// curl_setopt( $ch[$i], CURLOPT_CUSTOMREQUEST, 'HEAD' );
			curl_setopt( $ch[$i], CURLOPT_HEADER, FALSE );
			curl_setopt( $ch[$i], CURLOPT_NOBODY, TRUE );
			curl_setopt( $ch[$i], CURLOPT_SSL_VERIFYPEER, FALSE );
			curl_setopt( $ch[$i], CURLOPT_FOLLOWLOCATION, FALSE );
			curl_setopt( $ch[$i], CURLOPT_FAILONERROR, TRUE );

			curl_multi_add_handle( $mh, $ch[$i] );
		}

		do { // execute all queries simultaneously, and continue when all are complete

			curl_multi_exec( $mh, $running );

		} while ( $running );

		for ( $i = 0; $i < count( $urls ); $i++ ) {
			$results[$urls[$i]] = curl_getinfo( $ch[$i], CURLINFO_HTTP_CODE );
			curl_multi_remove_handle( $mh, $ch[$i] );
		}

		curl_multi_close( $mh );

		return $results;
	}
}

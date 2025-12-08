<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class HTTP extends Base
{

	/**
	 * Determine whether the request is a POST.
	 *
	 * @return bool
	 */
	public static function isPOST()
	{
		return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
	}

	/**
	 * Determine whether the request is a GET.
	 *
	 * @return bool
	 */
	public static function isGET()
	{
		return (bool) ( 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
	}

	public static function htmlStatus( $code, $title = NULL, $template = NULL )
	{
		if ( ! $code )
			return '';

		if ( is_null( $title ) )
			$title = self::getStatusDesc( $code );

		if ( is_null( $template ) )
			$template = '<small><code class="-status" title="%s" style="color:%s">%s</code></small>&nbsp;';

		$code = absint( $code );

		if ( 200 == $code )
			$color = 'green';

		else if ( $code >= 500 )
			$color = 'gray';

		else if ( $code >= 400 )
			$color = 'red';

		else if ( $code >= 300 )
			$color = '#0040FF';

		else
			$color = 'inherit';

		return sprintf( $template, $title, $color, $code );
	}

	/**
	 * Retrieves the description for the HTTP status.
	 * @ref https://httpstatuses.com
	 * @alt `get_status_header_desc()`
	 *
	 * @param int|string $code
	 * @param string $fallback
	 * @return string
	 */
	public static function getStatusDesc( $code, $fallback = '' )
	{
		static $data = NULL;

		if ( is_null( $data ) )
			$data = [

				// 1×× Informational
				100 => 'Continue',
				101 => 'Switching Protocols',
				102 => 'Processing',
				103 => 'Early Hints',

				// 2×× Success
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				207 => 'Multi-Status',
				208 => 'Already Reported',
				226 => 'IM Used',

				// 3×× Redirection
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				307 => 'Temporary Redirect',
				308 => 'Permanent Redirect',

				// 4×× Client Error
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',                                 // `The author deleted this story.`
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Payload Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				418 => 'I\'m a teapot',
				421 => 'Misdirected Request',
				422 => 'Unprocessable Entity',
				423 => 'Locked',
				424 => 'Failed Dependency',
				426 => 'Upgrade Required',
				428 => 'Precondition Required',
				429 => 'Too Many Requests',
				431 => 'Request Header Fields Too Large',
				444 => 'Connection Closed Without Response',
				451 => 'Unavailable For Legal Reasons',
				499 => 'Client Closed Request',

				// 5×× Server Error
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported',
				506 => 'Variant Also Negotiates',
				507 => 'Insufficient Storage',
				508 => 'Loop Detected',
				510 => 'Not Extended',
				511 => 'Network Authentication Required',
				599 => 'Network Connect Timeout Error',
			];

		$code = absint( $code );

		if ( isset( $data[$code] ) )
			return $data[$code];

		return $fallback;
	}

	/**
	 * Logs the errors of an HTTP request with extra info.
	 *
	 * @param string $url
	 * @param string $message
	 * @param string $context
	 * @return false
	 */
	public static function logError( $url = NULL, $message = NULL, $context = NULL )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return FALSE; // help the caller

		if ( $url && $message )
			$log = sprintf( '{%s}: %s', $url, $message );

		else if ( $message )
			$log = sprintf( '%s', $message );

		else if ( $url )
			$log = sprintf( '{%s}', $url );

		if ( $context )
			$log = sprintf( '[%s]: %s', $context, $log );

		error_log( $log );

		return FALSE; // help the caller
	}

	/**
	 * Retrieves data from the JSON body of a GET request, given a URL.
	 *
	 * @param string $url
	 * @param array $atts
	 * @param bool $assoc
	 * @return false|array|object
	 */
	public static function getJSON( $url, $atts = [], $assoc = TRUE )
	{
		if ( ! $url )
			return FALSE;

		$args = self::recursiveParseArgs( $atts, [
			'timeout' => 15,
			'headers' => [ 'Accept' => 'application/json' ],
		] );

		// $response = wp_remote_get( $url, $args );
		$response = wp_safe_remote_get( $url, $args );

		if ( self::isError( $response ) )
			return self::logError( $url, $response->get_error_message(), 'GETJSON' );

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status )
			return self::logError( $url, sprintf( '%d: %s', $status, self::getStatusDesc( $status, 'UKNOWN STATUS' ) ), 'GETJSON' );

		if ( ! $body = wp_remote_retrieve_body( $response ) )
			return self::logError( $url, '200: EMPTY BODY', 'GETJSON' );

		$data = json_decode( $body, $assoc );

		if ( json_last_error() !== JSON_ERROR_NONE )
			return self::logError( $url, sprintf( '200: JSON MALFORMED', json_last_error_msg() ), 'GETJSON' );

		return $data;
	}

	/**
	 * Puts data as JSON body of a POST request, given a URL.
	 *
	 * @param mixed $body
	 * @param string $url
	 * @param array $atts
	 * @param bool $assoc
	 * @return false|array|object
	 */
	public static function postJSON( $body, $url, $atts = [], $assoc = TRUE )
	{
		if ( ! $url )
			return FALSE;

		$args = self::recursiveParseArgs( $atts, [
			'body'    => $body,
			'timeout' => 15,
			'headers' => [ 'Accept' => 'application/json' ],
		] );

		$response = wp_remote_post( $url, $args );

		if ( 'development' === self::const( 'WP_STAGE' ) )
			self::_log( $args, wp_remote_retrieve_body( $response ) );

		if ( self::isError( $response ) )
			return self::logError( $url, $response->get_error_message(), 'POSTJSON' );

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status )
			return self::logError( $url, sprintf( '%d: %s', $status, self::getStatusDesc( $status, 'UKNOWN STATUS' ) ), 'POSTJSON' );

		if ( ! $body = wp_remote_retrieve_body( $response ) )
			return self::logError( $url, '200: EMPTY BODY', 'POSTJSON' );

		$data = json_decode( $body, $assoc );

		if ( json_last_error() !== JSON_ERROR_NONE )
			return self::logError( $url, sprintf( '200: JSON MALFORMED', json_last_error_msg() ), 'POSTJSON' );

		return $data;
	}

	/**
	 * Retrieves data from the HTML body of a GET request, given a URL.
	 *
	 * @see https://deliciousbrains.com/wordpress-http-api-requests/
	 *
	 * @param string $url
	 * @param array $atts
	 * @return false|string
	 */
	public static function getHTML( $url, $atts = [] )
	{
		if ( ! $url )
			return FALSE;

		$args = self::recursiveParseArgs( $atts, [
			'timeout' => 15,
			'headers' => [ 'Accept' => 'text/html' ],
		] );

		// $response = wp_remote_get( $url, $args );
		$response = wp_safe_remote_get( $url, $args );

		if ( self::isError( $response ) )
			return self::logError( $url, $response->get_error_message(), 'GETHTML' );

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status )
			return self::logError( $url, sprintf( '%d: %s', $status, self::getStatusDesc( $status, 'UKNOWN STATUS' ) ), 'GETHTML' );

		if ( ! $body = wp_remote_retrieve_body( $response ) )
			return self::logError( $url, '200: EMPTY BODY', 'GETHTML' );

		return $body;
	}

	/**
	 * Retrieves data from the content body of a GET request, given a URL.
	 * NOTE: without `accept` header
	 *
	 * @param string $url
	 * @param array $atts
	 * @return false|string
	 */
	public static function getContents( $url, $atts = [] )
	{
		if ( ! $url )
			return FALSE;

		$args = self::recursiveParseArgs( $atts, [
			'timeout' => 15,
		] );

		$response = wp_safe_remote_get( $url, $args );

		if ( self::isError( $response ) )
			return self::logError( $url, $response->get_error_message(), 'GETCONTENTS' );

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status )
			return self::logError( $url, sprintf( '%d: %s', $status, self::getStatusDesc( $status, 'UKNOWN STATUS' ) ), 'GETCONTENTS' );

		if ( ! $body = wp_remote_retrieve_body( $response ) )
			return self::logError( $url, '200: EMPTY BODY', 'GETCONTENTS' );

		return $body;
	}

	public static function getContents_OLD( $url )
	{
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		$handle = curl_init();

		curl_setopt( $handle, CURLOPT_URL, $url );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, TRUE );

		if ( 'development' === wp_get_environment_type() ) {
			curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, FALSE );
			curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, FALSE );
		}

		$contents = curl_exec( $handle );

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
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

	// @REF: https://github.com/10up/restricted-site-access/blob/develop/restricted_site_access.php
	// @SEE: https://wordpress.org/support/topic/how-to-troubleshoot-client-ip-detection/
	// `CloudFlare`: https://www.cloudflare.com/ips/
	public static function clientIP()
	{
		$headers = [
			'HTTP_CF_CONNECTING_IP' ,  // CloudFlare // @REF: https://github.com/10up/restricted-site-access/issues/109
			'HTTP_INCAP_CLIENT_IP'  ,  // Incapsula
			'HTTP_X_SUCURI_CLIENTIP',  // Sucuri
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR'  ,  // Any Proxy
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $headers as $key ) {

			if ( ! isset( $_SERVER[$key] ) )
				continue;

			$list = explode( ',', sanitize_text_field( self::unslash( $_SERVER[$key] ) ) );

			foreach ( $list as $ip ) {

				$ip = trim( $ip );

				if ( FALSE !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) )
					return $ip;
			}
		}

		return '';
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

		return $pad ? str_pad( $ip, 15, ' ', STR_PAD_LEFT ) : $ip;
	}

	public static function normalizeIP( $ip )
	{
		return trim( preg_replace( '/[^0-9a-fA-F:., ]/', '', stripslashes( $ip ) ) );
	}

	public static function IPinRange( $ip, $range )
	{
		// `1.2.3/24` OR `1.2.3.4/255.255.255.0`
		if ( FALSE !== strpos( $range, '/' ) )
			return self::IPinCIDR( $ip, $range );

		// `255.255.*.*`
		if ( FALSE !== strpos( $range, '*' ) )
			$range = ( str_replace( '*', '0', $range )
				.'-'.str_replace( '*', '255', $range ) );

		$long = ip2long( $ip );

		// `1.6.0.0 - 1.7.255.255`
		if ( FALSE !== strpos( $range, '-' ) ) {

			$block = array_map( 'trim', explode( '-', $range, 2 ) );

			if ( $long >= ip2long( $block[0] )
				&& $long <= ip2long( $block[1] ) )
					return TRUE;
		}

		// `1.8.0.1`
		if ( $long == ip2long( trim( $range ) ) )
			return TRUE;

		return FALSE;
	}

	// @REF: https://stackoverflow.com/a/594134
	public static function IPinCIDR( $ip, $range )
	{
		list( $subnet, $bits ) = explode( '/', $range );

		$ip     = ip2long( $ip );
		$subnet = ip2long( $subnet );
		$mask   = -1 << ( 32 - $bits );

		// in case the supplied subnet wasn't correctly aligned
		$subnet &= $mask;

		return ( $ip & $mask ) == $subnet;
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

	/**
	 * Performs an HTTP request using the GET method and returns its response.
	 *
	 * Abstracts the idiocy of the CURL API for something simpler. Assumes we are
	 * downloading data (so a GET request) and we need no special request headers.
	 * Returns an `IO` stream which will be the data requested. The headers of the
	 * response will be stored in the $headers param reference.
	 *
	 * If the request fails for some reason FALSE is returned with the `$err_msg`
	 * param containing more info.
	 *
	 * @source https://gist.github.com/eric1234/37fd102798d99d94b0dcebde6bb29ef3
	 * @see `wp_remote_get()`
	 *
	 * @param string $url
	 * @param array $headers
	 * @param string $err_msg
	 * @return false|stream
	 */
	public static function download( $url, &$headers, &$err_msg )
	{
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		$in_out = curl_init( $url );
		$stream = fopen( 'php://temp', 'w+' );

		curl_setopt_array( $in_out, [
			CURLOPT_FAILONERROR    => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_HEADER         => TRUE,
			CURLOPT_FILE           => $stream,
		] );

		if ( FALSE === curl_exec( $in_out ) ) {
			$err_msg << curl_error( $in_out );
			return FALSE;
		}

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
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
	// @REF: http://stackoverflow.com/a/9950468
	public static function checkURLs( $urls = [] )
	{
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		if ( self::empty( $urls ) )
			return [];

		$ch = $results = [];

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

		do {

			// Executes all queries simultaneously,
			// and continue when all are complete
			curl_multi_exec( $mh, $running );

		} while ( $running );

		for ( $i = 0; $i < count( $urls ); $i++ ) {
			$results[$urls[$i]] = curl_getinfo( $ch[$i], CURLINFO_HTTP_CODE );
			curl_multi_remove_handle( $mh, $ch[$i] );
		}

		curl_multi_close( $mh );

		return $results;
	}

	// @SEE: https://stackoverflow.com/a/12628971
	// @REF: https://stackoverflow.com/a/12629254
	public static function getStatus( $url, $verify_ssl = TRUE )
	{
		if ( self::empty( $url ) || ! extension_loaded( 'curl' ) )
			return FALSE;

		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_HEADER, TRUE ); // we want headers
		curl_setopt( $ch, CURLOPT_NOBODY, TRUE ); // we don't need body
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );

		if ( ! $verify_ssl ) {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		}

		$output = curl_exec( $ch );
		$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
			curl_close( $ch );

		return $status;
	}

	/**
	 * Finds where the URL will redirected using cURL.
	 * @source https://www.geeksforgeeks.org/php/how-to-find-where-the-url-will-redirected-using-curl/
	 *
	 * @param string $url
	 * @param bool $verify_ssl
	 * @return false|string
	 */
	public static function getRedirect( $url, $verify_ssl = TRUE )
	{
		if ( self::empty( $url ) || ! extension_loaded( 'curl' ) )
			return FALSE;

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE ); // Return follow location true

		if ( ! $verify_ssl ) {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		}

		$output   = curl_exec( $ch );
		$redirect = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
			curl_close( $ch );

		return ( $url === $redirect ) ? FALSE : $redirect;
	}

	/**
	 * Returns the size of a file without downloading.
	 * @source https://stackoverflow.com/a/2602624
	 *
	 * @param string $url
	 * @param bool $verify_ssl
	 * @return int|bool
	 */
	public static function getSize( $url, $verify_ssl = TRUE )
	{
		if ( empty( $url ) )
			return FALSE;

		if ( ! extension_loaded( 'curl' ) )
			return self::getSizeFromHeaders( $url );

		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_NOBODY, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, TRUE );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
		curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );

		if ( ! $verify_ssl ) {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		}

		$result = -1; // assume failure
		$output = curl_exec( $ch );

		$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$length = curl_getinfo( $ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD );

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
			curl_close( $ch );

		// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
		if ( $status == 200 || ( $status > 300 && $status <= 308 ) )
			$result = $length;

		return $result;
	}

	// @REF: https://stackoverflow.com/a/43520299
	public static function getSizeFromHeaders( $url )
	{
		if ( empty( $url ) )
			return FALSE;

		if ( ! $headers = get_headers( $url, TRUE ) )
			return FALSE;

		if ( array_key_exists( 'content-length', $headers ) )
			return $headers['content-length'];

		if ( array_key_exists( 'Content-Length', $headers ) )
			return $headers['Content-Length'];

		return -1;
	}
}

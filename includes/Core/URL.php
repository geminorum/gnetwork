<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class URL extends Base
{

	/**
	 * Sanitizes a URL for storage or redirect.
	 * @source `sanitize_url()`
	 *
	 * @param string $url
	 * @return string $sanitized
	 */
	public static function sanitize( $url )
	{
		$sanitized = Text::trim( $url );

		// @SEE: `esc_url()`
		if ( $sanitized && ! preg_match( '/^http(s)?:\/\//', $sanitized ) )
			$sanitized = 'http://'.$sanitized;

		return esc_url( $sanitized, NULL, 'db' );
	}

	// @SOURCE: http://stackoverflow.com/a/8891890
	public static function current( $trailingslashit = FALSE, $forwarded_host = FALSE )
	{
		$ssl = ( ! empty( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] );

		$protocol = strtolower( $_SERVER['SERVER_PROTOCOL'] );
		$protocol = substr( $protocol, 0, strpos( $protocol, '/' ) ).( ( $ssl ) ? 's' : '' );

		$port = $_SERVER['SERVER_PORT'];
		$port = ( ( ! $ssl && '80' == $port ) || ( $ssl && '443' == $port ) ) ? '' : ':'.$port;

		$host = ( $forwarded_host && isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : NULL );
		$host = $host ?? $_SERVER['SERVER_NAME'].$port;

		$current = $protocol.'://'.$host.$_SERVER['REQUEST_URI'];
		return $trailingslashit ? self::trail( $current ) : $current;
	}

	// like twitter links
	public static function prepTitle( $url, $convert_slash = FALSE )
	{
		$title = preg_replace( '|^http(s)?://(www\.)?|i', '', $url );
		$title = self::untrail( $title );
		return $convert_slash ? str_ireplace( [ '/', '\/' ], '-', $title ) : $title;
	}

	public static function prepTitleQuery( $string )
	{
		return str_ireplace( [ '_', '-' ], ' ', urldecode( $string ) );
	}

	// wrapper for `wp_parse_url()`
	public static function parse( $url, $component = -1 )
	{
		return wp_parse_url( $url, $component );
	}

	// @SOURCE: `add_query_arg()`
	public static function parseDeep( $url )
	{
		if ( $frag = strstr( $url, '#' ) )
			$url = substr( $url, 0, -strlen( $frag ) );
		else
			$frag = '';

		if ( 0 === stripos( $url, 'http://' ) ) {
			$pro = 'http';
			$url = substr( $url, 7 );
		} else if ( 0 === stripos( $url, 'https://' ) ) {
			$pro = 'https';
			$url = substr( $url, 8 );
		} else {
			$pro = '';
		}

		if ( FALSE !== strpos( $url, '?' ) ) {
			list( $base, $query ) = explode( '?', $url, 2 );
		} else if ( $pro || FALSE === strpos( $url, '=' ) ) {
			$base  = $url;
			$query = '';
		} else {
			$base  = '';
			$query = $url;
		}

		parse_str( $query, $args );

		return [
			'base'     => $base,
			'query'    => $args,
			'protocol' => $pro,
			'fragment' => $frag,
		];
	}

	// strips the #fragment from a URL, if one is present
	// @REF: `strip_fragment_from_url()`
	public static function stripFragment( $url )
	{
		$parsed = self::parse( $url );

		if ( empty( $parsed['host'] ) )
			return $url;

		// this mirrors code in `redirect_canonical()`
		// it does not handle every case
		$url = $parsed['scheme'].'://'.$parsed['host'];

		if ( ! empty( $parsed['port'] ) )
			$url.= ':'.$parsed['port'];

		if ( ! empty( $parsed['path'] ) )
			$url.= $parsed['path'];

		if ( ! empty( $parsed['query'] ) )
			$url.= '?'.$parsed['query'];

		return $url;
	}

	/**
	 * Appends a trailing slash.
	 * @source `trailingslashit()`
	 *
	 * Will remove trailing forward and backslashes if it exists already before
	 * adding a trailing forward slash. This prevents double slashing a string or URL.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function trail( $path )
	{
		return $path ? ( self::untrail( $path ).'/' ) : $path;
	}

	/**
	 * Removes trailing forward slashes and backslashes if they exist.
	 * @source `untrailingslashit()`
	 *
	 * @param string $path
	 * @return string
	 */
	public static function untrail( $path )
	{
		return $path ? rtrim( $path, '/\\' ) : $path;
	}

	// FIXME: strip all the path
	public static function domain( $path )
	{
		if ( FALSE === strpos( $path, '.' ) )
			return $path;

		$parts = explode( '.', $path );
		return strtolower( $parts[0] );
	}

	// @SOURCE: `wp_make_link_relative()`
	public static function relative( $url )
	{
		return preg_replace( '|^(https?:)?//[^/]+(/?.*)|i', '$2', $url );
	}

	// @REF: https://developer.wordpress.org/reference/functions/wp_get_attachment_url/
	public static function relative_ALT( $url )
	{
		$parsed = parse_url( $url );
		return dirname( $parsed ['path'] ).'/'.rawurlencode( basename( $parsed['path'] ) );
	}

	public static function fromPath( $path, $base = ABSPATH )
	{
		return str_ireplace(
			File::normalize( $base ),
			self::trail( get_option( 'siteurl' ) ),
			File::normalize( $path )
			// File::normalize( File::join( $base, $path ) )
		);
	}

	public static function toPath( $url, $base = ABSPATH )
	{
		return str_ireplace(
			self::trail( get_bloginfo( 'url' ) ),
			File::normalize( $base ),
			$url
		);
	}

	public static function home( $path = '' )
	{
		return $path ? ( self::trail( get_option( 'home' ) ).$path ) : self::untrail( get_option( 'home' ) );
	}

	// check whether the given URL belongs to this site
	public static function isLocal( $url, $domain = NULL )
	{
		return self::parse( $url, PHP_URL_HOST ) === self::parse( ( is_null( $domain ) ? home_url() : $domain ), PHP_URL_HOST );
	}

	// check whether the given URL is relative or not
	public static function isRelative( $url )
	{
		$parsed = self::parse( $url );
		return empty( $parsed['host'] ) && empty( $parsed['scheme'] );
	}

	// @ALSO: `sanitize_url( $url ) === $url`, `wp_http_validate_url()`
	// @REF: https://halfelf.org/2015/url-validation/
	// @REF: https://d-mueller.de/blog/why-url-validation-with-filter_var-might-not-be-a-good-idea/
	public static function isValid( $url )
	{
		$url = Text::trim( $url );

		if ( self::empty( $url ) )
			return FALSE;

		if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) )
			return FALSE;

		// $url = filter_var( $url, FILTER_SANITIZE_STRING );
		$url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );

		if ( FALSE !== filter_var( $url, FILTER_VALIDATE_URL ) )
			return TRUE;

		return FALSE;
	}

	public static function checkExternals( $urls = [], $site = NULL )
	{
		if ( empty( $urls ) )
			return [];

		if ( is_null( $site ) )
			$site = get_option( 'siteurl' );

		$urls    = array_values( array_unique( $urls ) );
		$length  = strlen( $site );
		$results = [];

		foreach ( $urls as $url )
			$results[$url] = $site !== substr( $url, 0, $length );

		return $results;
	}

	/**
	 * Retrieves a single redirected URL.
	 *
	 * This makes a single request and reads the "Location" header to determine
	 * the destination. It doesn't check if that location is valid or not.
	 * @source https://gist.github.com/davejamesmiller/dbefa0ff167cc5c08d6d
	 *
	 * @param string $url
	 * @return string
	 */
	public static function getRedirectTargetSingle( $url )
	{
		if ( empty( $url ) )
			return FALSE;

		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_NOBODY, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		$headers = curl_exec( $ch );

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
			curl_close( $ch );

		// Check if there's a Location: header (redirect)
		if ( preg_match( '/^Location: (.+)$/im', $headers, $matches ) )
			return trim( $matches[1] );

		return FALSE;
	}

	/**
	 * Retrieves the final redirected URL.
	 *
	 * This makes multiple requests, following each redirect until it reaches
	 * the final destination.
	 * @source https://gist.github.com/davejamesmiller/dbefa0ff167cc5c08d6d
	 * @source https://stackoverflow.com/a/35046466
	 *
	 * @param string $url
	 * @return string
	 */
	public static function getRedirectTargetFinal( $url )
	{
		if ( empty( $url ) )
			return FALSE;

		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_NOBODY, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );  // follow redirects
		curl_setopt( $ch, CURLOPT_AUTOREFERER, 1 );     // set referrer on redirect

		curl_exec( $ch );

		$target = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
			curl_close( $ch );

		return $target ?: FALSE;
	}

	// converts a URL to just the domain
	// @SOURCE: https://gist.github.com/davejamesmiller/1965937
	public static function getDomain( $url )
	{
		$host = self::parse( $url, PHP_URL_HOST );

		if ( ! $host )
			$host = $url;

		if ( 'www.' == substr( $host, 0, 4 ) )
			$host = substr( $host, 4 );

		return $host;
	}
}

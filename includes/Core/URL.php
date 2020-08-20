<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class URL extends Base
{

	// @SOURCE: http://stackoverflow.com/a/8891890
	public static function current( $trailingslashit = FALSE, $forwarded_host = FALSE )
	{
		$ssl = ( ! empty( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] );

		$protocol = strtolower( $_SERVER['SERVER_PROTOCOL'] );
		$protocol = substr( $protocol, 0, strpos( $protocol, '/' ) ).( ( $ssl ) ? 's' : '' );

		$port = $_SERVER['SERVER_PORT'];
		$port = ( ( ! $ssl && '80' == $port ) || ( $ssl && '443' == $port ) ) ? '' : ':'.$port;

		$host = ( $forwarded_host && isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : NULL );
		$host = isset( $host ) ? $host : $_SERVER['SERVER_NAME'].$port;

		$current = $protocol.'://'.$host.$_SERVER['REQUEST_URI'];
		return $trailingslashit ? self::trail( $current ) : $current;
	}

	// like twitter links
	public static function prepTitle( $url, $slash = FALSE )
	{
		$title = preg_replace( '|^http(s)?://(www\.)?|i', '', $url );
		$title = self::untrail( $title );
		return $slash ? str_ireplace( array( '/', '\/' ), '-', $title ) : $title;
	}

	public static function prepTitleQuery( $string )
	{
		return str_ireplace( array( '_', '-' ), ' ', urldecode( $string ) );
	}

	// @SOURCE: `add_query_arg()`
	// @SEE: `URI::parse()`
	public static function parse( $url )
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

		return array(
			'base'     => $base,
			'query'    => $args,
			'protocol' => $pro,
			'fragment' => $frag,
		);
	}

	// will remove trailing forward and backslashes if it exists already before adding
	// a trailing forward slash. This prevents double slashing a string or path.
	// @SOURCE: `trailingslashit()`
	public static function trail( $path )
	{
		return self::untrail( $path ).'/';
	}

	// removes trailing forward slashes and backslashes if they exist.
	// @SOURCE: `untrailingslashit()`
	public static function untrail( $path )
	{
		return rtrim( $path, '/\\' );
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

	public static function fromPath( $path, $base = ABSPATH )
	{
		return str_ireplace(
			File::normalize( $base ),
			self::trail( get_option( 'siteurl' ) ),
			File::normalize( $path )
		);
	}

	public static function home( $path = '' )
	{
		return $path ? ( self::trail( get_option( 'home' ) ).$path ) : self::untrail( get_option( 'home' ) );
	}

	// check whether the given URL belongs to this site
	public static function isLocal( $url, $domain = NULL )
	{
		return wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( ( is_null( $domain ) ? home_url() : $domain ), PHP_URL_HOST );
	}

	// check whether the given URL is relative or not
	public static function isRelative( $url )
	{
		$parsed = wp_parse_url( $url );
		return empty( $parsed['host'] ) && empty( $parsed['scheme'] );
	}

	// @ALSO: `esc_url_raw( $url ) === $url`, `wp_http_validate_url()`
	// @REF: https://halfelf.org/2015/url-validation/
	// @REF: https://d-mueller.de/blog/why-url-validation-with-filter_var-might-not-be-a-good-idea/
	public static function isValid( $url )
	{
		$url = trim( $url );

		if ( empty( $url ) )
			return FALSE;

		if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) )
			return FALSE;

		$url = filter_var( $url, FILTER_SANITIZE_STRING );

		if ( FALSE !== filter_var( $url, FILTER_VALIDATE_URL ) )
			return TRUE;

		return FALSE;
	}

	public static function checkExternals( $urls = array(), $site = NULL )
	{
		if ( empty( $urls ) )
			return array();

		if ( is_null( $site ) )
			$site = get_option( 'siteurl' );

		$urls    = array_values( array_unique( $urls ) );
		$length  = strlen( $site );
		$results = array();

		foreach ( $urls as $url )
			$results[$url] = $site !== substr( $url, 0, $length );

		return $results;
	}

	/// FOLLOW A SINGLE REDIRECT
	// This makes a single request and reads the "Location" header to determine
	// the destination. It doesn't check if that location is valid or not.
	// @SOURCE: https://gist.github.com/davejamesmiller/dbefa0ff167cc5c08d6d
	public static function getRedirectTargetSingle( $url )
	{
		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_NOBODY, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		$headers = curl_exec( $ch );

		curl_close( $ch );

		// Check if there's a Location: header (redirect)
		if ( preg_match( '/^Location: (.+)$/im', $headers, $matches ) )
			return trim( $matches[1] );

		// If not, there was no redirect so return the original URL
		// (Alternatively change this to return false)
		return $url;
	}

	/// FOLLOW ALL REDIRECTS
	// This makes multiple requests, following each redirect until it reaches
	// the final destination.
	// @SOURCE: https://gist.github.com/davejamesmiller/dbefa0ff167cc5c08d6d
	public static function getRedirectTargetFinal( $url )
	{
		$ch = curl_init($url);

		curl_setopt( $ch, CURLOPT_NOBODY, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 ); // follow redirects
		curl_setopt( $ch, CURLOPT_AUTOREFERER, 1 ); // set referer on redirect

		curl_exec( $ch );

		$target = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );

		curl_close( $ch );

		return $target ?: FALSE;
	}

	// converts a URL to just the domain
	// @SOURCE: https://gist.github.com/davejamesmiller/1965937
	public static function getDomain( $url )
	{
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $host )
			$host = $url;

		if ( 'www.' == substr( $host, 0, 4 ) )
			$host = substr( $host, 4 );

		return $host;
	}
}

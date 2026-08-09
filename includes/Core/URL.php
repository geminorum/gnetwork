<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\WordPress;

class URL extends Base
{

	/**
	 * Sanitizes a URL for storage or redirect.
	 * @source `sanitize_url()`/`esc_url_raw()`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function sanitize( mixed $input ): string
	{
		if ( ! $sanitized = Text::force( $input ) )
			return '';

		// @SEE: `esc_url()`
		if ( $sanitized && ! preg_match( '/^http(s)?:\/\//', $sanitized ) )
			$sanitized = 'https://'.$sanitized;

		return esc_url( $sanitized, NULL, 'db' );
	}

	// TODO: support schemes: `geo:`
	public static function sanitizeForStorage( mixed $input ): string
	{
		$raw = $input;

		if ( ! $sanitized = Text::force( $input ) )
			return '';

		$sanitized = Text::trim( rawurldecode( $sanitized ) );

		if ( self::isRelative( $sanitized ) )
			$sanitized = sanitize_url( $sanitized ); // avoid forced scheme

		else if ( self::isLocal( $sanitized ) )
			$sanitized = sanitize_url( self::relative( $sanitized ) ); // avoid forced scheme

		else
			$sanitized = self::sanitize( $sanitized );

		return apply_filters( 'nucleus_url_sanitize',
			$sanitized,
			$raw,
		);
	}

	// @SOURCE: http://stackoverflow.com/a/8891890
	public static function current( bool $trailingslashit = FALSE, bool $forwarded_host = FALSE ): string
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
	public static function prepTitle( string $url, bool $convert_slash = FALSE ): string
	{
		$title = preg_replace( '|^http(s)?://(www\.)?|i', '', $url );
		$title = self::untrail( $title );
		return $convert_slash ? str_ireplace( [ '/', '\/' ], '-', $title ) : $title;
	}

	public static function prepTitleQuery( string $string ): string
	{
		return str_ireplace( [ '_', '-' ], ' ', urldecode( $string ) );
	}

	#[\Deprecated('USE `WordPress\URL::parse()`')]
	public static function parse( string $url, int $component = -1 ): mixed
	{
		return wp_parse_url( $url, $component );
	}

	// @SOURCE: `add_query_arg()`
	public static function parseDeep( string $url ): array
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
			'protocol' => $pro,
			'scheme'   => $pro, // back-compatibility with `parse_url`
			'fragment' => $frag,
			'host'     => explode( '/', $base, 2 )[0] ?? '',
			'path'     => strrchr( $base, '/' ),
			'query'    => $args,
		];
	}

	/**
	 * Strips the fragment from a URL, if one is present.
	 * @REF: `strip_fragment_from_url()`
	 *
	 * @param string $url
	 * @return string
	 */
	public static function stripFragment( string $url ): string
	{
		$parsed = WordPress\URL::parse( $url );

		if ( empty( $parsed['host'] ) )
			return $url;

		// This mirrors code in `redirect_canonical()`
		// it does not handle every case.
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

	// https://wp-mix.com/wordpress-remove-query-strings-scripts-styles/
	// https://wordpress.stackexchange.com/a/224300
	public static function unquery( string $input ): string
	{
		return explode( '?', $input, 2 )[0] ?? $input;
	}

	// FIXME: strip all the path
	public static function domain( $path )
	{
		if ( FALSE === strpos( $path, '.' ) )
			return $path;

		$parts = explode( '.', $path );
		return strtolower( $parts[0] );
	}

	/**
	 * Converts full URL paths to absolute paths.
	 * @source `wp_make_link_relative()`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function relative( mixed $input ): string
	{
		if ( ! $url = Text::force( $input ) )
			return '';

		return preg_replace( '|^(https?:)?//[^/]+(/?.*)|i', '$2', $url );
	}

	// @REF: https://developer.wordpress.org/reference/functions/wp_get_attachment_url/
	public static function relative_ALT( $url )
	{
		$parsed = parse_url( $url );
		return dirname( $parsed ['path'] ).'/'.rawurlencode( basename( $parsed['path'] ) );
	}

	public static function fromPath( string $path, string $base = ABSPATH ): string
	{
		return str_ireplace(
			File::normalize( $base ),
			self::trail( get_option( 'siteurl' ) ),
			File::normalize( $path )
			// File::normalize( File::join( $base, $path ) )
		);
	}

	public static function toPath( string $url, string $base = ABSPATH ): string
	{
		return str_ireplace(
			self::trail( get_bloginfo( 'url' ) ),
			File::normalize( $base ),
			$url
		);
	}

	public static function toScheme( string $url, string $scheme ): string
	{
		return preg_replace( '/^http(s?):/i', sprintf( '%s:', $scheme ), $url );
	}

	public static function home( string $path = '' ): string
	{
		return $path ? ( self::trail( get_option( 'home' ) ).$path ) : self::untrail( get_option( 'home' ) );
	}

	// Checks whether the given URL belongs to this site.
	public static function isLocal( string $url, $domain = NULL ): bool
	{
		return parse_url( $url, PHP_URL_HOST ) === parse_url( ( is_null( $domain ) ? home_url() : $domain ), PHP_URL_HOST );
	}

	// Checks whether the given URL is relative or not.
	public static function isRelative( string $url ): bool
	{
		$parsed = parse_url( $url );
		return empty( $parsed['host'] ) && empty( $parsed['scheme'] );
	}

	// @ALSO: `sanitize_url( $url ) === $url`, `wp_http_validate_url()`
	// @REF: https://halfelf.org/2015/url-validation/
	// @REF: https://d-mueller.de/blog/why-url-validation-with-filter_var-might-not-be-a-good-idea/
	public static function isValid( string $url ): bool
	{
		if ( ! $url = Text::force( $url ) )
			return FALSE;

		if ( self::empty( $url ) )
			return FALSE;

		if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) )
			return FALSE;

		// `$url = filter_var( $url, FILTER_SANITIZE_STRING );`
		// `$url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );`
		$url = Coding::entityEncodeQUOTES( $url );

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

	/**
	 * Converts a URL to just the domain.
	 * @source: https://gist.github.com/davejamesmiller/1965937
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function getDomain( mixed $input ): string
	{
		if ( ! $url = Text::force( $input ) )
			return '';

		$host = parse_url( $url, PHP_URL_HOST );

		if ( ! $host )
			$host = $url;

		if ( 'www.' == substr( $host, 0, 4 ) )
			$host = substr( $host, 4 );

		return $host;
	}
}

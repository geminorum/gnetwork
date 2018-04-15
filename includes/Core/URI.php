<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

// @REF: http://sabre.io/uri/usage/
// @SOURCE: https://github.com/fruux/sabre-uri/blob/1.x/lib/functions.php

/**
 * This file contains all the uri handling functions.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/
 */

class URI extends Base
{

	/**
	 * Resolves relative urls, like a browser would.
	 *
	 * This function takes a basePath, which itself _may_ also be relative, and
	 * then applies the relative path on top of it.
	 *
	 * @param string $basePath
	 * @param string $newPath
	 * @return string
	 */
	public static function resolve( $basePath, $newPath )
	{
		$base  = self::parse( $basePath );
		$delta = self::parse( $newPath );

		// If the new path defines a scheme,
		// it's absolute and we can just return that.
		if ( $delta['scheme'] )
			return self::build( $delta );

		$pick = function( $part ) use ( $base, $delta ) {

			if ( $delta[$part] )
				return $delta[$part];

			else if ( $base[$part] )
				return $base[$part];

			return NULL;
		};

		$newParts = [];

		$newParts['scheme'] = $pick( 'scheme' );
		$newParts['host']   = $pick( 'host' );
		$newParts['port']   = $pick( 'port' );

		$path = '';

		if ( $delta['path'] ) {

			// If the path starts with a slash
			if ( '/' === $delta['path'][0] ) {

				$path = $delta['path'];

			} else {

				// Removing last component from base path.
				$path = $base['path'];

				if ( FALSE !== strpos( $path, '/' ) )
					$path = substr( $path, 0, strrpos( $path, '/' ) );

				$path.= '/'.$delta['path'];
			}

		} else {

			$path = $base['path'] ?: '/';
		}

		// Removing .. and .
		$pathParts    = explode( '/', $path );
		$newPathParts = [];

		foreach ( $pathParts as $pathPart ) {

			switch ( $pathPart ) {

				// case '':
				case '.':

				break;
				case '..':

					array_pop( $newPathParts );

				break;
				default:

					$newPathParts[] = $pathPart;
					break;
			}
		}

		$path = implode( '/', $newPathParts );

		// If the source url ended with a /, we want to preserve that.
		$newParts['path'] = $path;

		if ( $delta['query'] ) {

			$newParts['query'] = $delta['query'];

		} else if ( ! empty( $base['query'] ) && empty( $delta['host'] ) && empty( $delta['path'] ) ) {
			// Keep the old query if host and path didn't change
			$newParts['query'] = $base['query'];
		}

		if ( $delta['fragment'] )
			$newParts['fragment'] = $delta['fragment'];

		return self::build( $newParts );
	}

	/**
	 * Takes a URI or partial URI as its argument, and normalizes it.
	 *
	 * After normalizing a URI, you can safely compare it to other URIs.
	 * This function will for instance convert a %7E into a tilde, according to
	 * rfc3986.
	 *
	 * It will also change a %3a into a %3A.
	 *
	 * @param string $uri
	 * @return string
	 */
	public static function normalize( $uri )
	{
		$parts = self::parse( $uri );

		if ( ! empty( $parts['path'] ) ) {

			$pathParts    = explode( '/', ltrim( $parts['path'], '/' ) );
			$newPathParts = [];

			foreach ( $pathParts as $pathPart ) {

				switch ( $pathPart ) {

					case '.':

						// skip

					break;
					case '..':

						// One level up in the hierarchy
						array_pop( $newPathParts );

					break;
					default:

						// Ensuring that everything is correctly percent-encoded.
						$newPathParts[] = rawurlencode( rawurldecode( $pathPart ) );
				}
			}

			$parts['path'] = '/'.implode( '/', $newPathParts );
		}

		if ( $parts['scheme'] ) {

			$parts['scheme'] = strtolower( $parts['scheme'] );
			$defaultPorts    = [ 'http' => '80', 'https' => '443' ];

			if ( ! empty( $parts['port'] ) && isset( $defaultPorts[$parts['scheme']] ) && $defaultPorts[$parts['scheme']] == $parts['port'] ) {

				// Removing default ports.
				unset($parts['port']);
			}

			// A few HTTP specific rules.
			switch ( $parts['scheme'] ) {

				case 'http':
				case 'https':

					if ( empty( $parts['path'] ) ) {
						// An empty path is equivalent to / in http.
						$parts['path'] = '/';
					}
			}
		}

		if ( $parts['host'] )
			$parts['host'] = strtolower( $parts['host'] );

		return self::build( $parts );
	}

	/**
	 * Parses a URI and returns its individual components.
	 *
	 * This method largely behaves the same as PHP's parse_url, except that it will
	 * return an array with all the array keys, including the ones that are not
	 * set by parse_url, which makes it a bit easier to work with.
	 *
	 * Unlike PHP's parse_url, it will also convert any non-ascii characters to
	 * percent-encoded strings. PHP's parse_url corrupts these characters on OS X.
	 *
	 * @param string $uri
	 * @return array
	 */
	public static function parse( $uri )
	{
		// Normally a URI must be ASCII, however. However, often it's not and
		// parse_url might corrupt these strings.
		//
		// For that reason we take any non-ascii characters from the uri and
		// uriencode them first.
		$uri = preg_replace_callback( '/[^[:ascii:]]/u', function( $matches ) {
			return rawurlencode( $matches[0] );
		}, $uri );

		if ( ! $result = parse_url( $uri ) )
			$result = self::_parse_fallback( $uri );

		return $result + [
			'scheme'   => NULL,
			'host'     => NULL,
			'path'     => NULL,
			'port'     => NULL,
			'user'     => NULL,
			'query'    => NULL,
			'fragment' => NULL,
		];
	}

	/**
	 * This function takes the components returned from PHP's parse_url, and uses
	 * it to generate a new uri.
	 *
	 * @param array $parts
	 * @return string
	 */
	public static function build( array $parts )
	{
		$uri  = '';
		$auth = '';

		if ( ! empty( $parts['host'] ) ) {

			$auth = $parts['host'];

			if ( ! empty( $parts['user'] ) )
				$auth = $parts['user'].'@'.$auth;

			if ( ! empty( $parts['port'] ) )
				$auth = $auth.':'.$parts['port'];
		}

		// If there's a scheme, there's also a host.
		if ( ! empty( $parts['scheme'] ) )
			$uri = $parts['scheme'].':';

		// No scheme, but there is a host.
		if ( $auth || ( ! empty( $parts['scheme'] ) && 'file' === $parts['scheme'] ) )
			$uri.= '//'.$auth;

		if ( ! empty( $parts['path'] ) )
			$uri.= $parts['path'];

		if ( ! empty( $parts['query'] ) )
			$uri.= '?'.$parts['query'];

		if ( ! empty( $parts['fragment'] ) )
			$uri.= '#'.$parts['fragment'];

		return $uri;
	}

	/**
	 * Returns the 'dirname' and 'basename' for a path.
	 *
	 * The reason there is a custom function for this purpose, is because
	 * basename() is locale aware (behaviour changes if C locale or a UTF-8 locale
	 * is used) and we need a method that just operates on UTF-8 characters.
	 *
	 * In addition basename and dirname are platform aware, and will treat
	 * backslash (\) as a directory separator on windows.
	 *
	 * This method returns the 2 components as an array.
	 *
	 * If there is no dirname, it will return an empty string. Any / appearing at
	 * the end of the string is stripped off.
	 *
	 * @param string $path
	 * @return array
	 */
	public static function split( $path )
	{
		$matches = [];
		$pattern = '/^(?:(?:(.*)(?:\/+))?([^\/]+))(?:\/?)$/u';

		if ( preg_match( $pattern, $path, $matches ) )
			return [ $matches[1], $matches[2] ];

		return [ NULL, NULL ];
	}

	/**
	 * This function is another implementation of parse_url, except this one is
	 * fully written in PHP.
	 *
	 * The reason is that the PHP bug team is not willing to admit that there are
	 * bugs in the parse_url implementation.
	 *
	 * This function is only called if the main parse method fails. It's pretty
	 * crude and probably slow, so the original parse_url is usually preferred.
	 *
	 * @param string $uri
	 * @return array
	 */
	private static function _parse_fallback( $uri )
	{
		// Normally a URI must be ASCII, however. However, often it's not and
		// parse_url might corrupt these strings.
		//
		// For that reason we take any non-ascii characters from the uri and
		// uriencode them first.
		$uri = preg_replace_callback( '/[^[:ascii:]]/u', function( $matches ) {
			return rawurlencode( $matches[0] );
		}, $uri );

		$result = [
			'scheme'   => NULL,
			'host'     => NULL,
			'path'     => NULL,
			'port'     => NULL,
			'user'     => NULL,
			'query'    => NULL,
			'fragment' => NULL,
		];

		if ( preg_match( '% ^([A-Za-z][A-Za-z0-9+-\.]+): %x', $uri, $matches ) ) {

			$result['scheme'] = $matches[1];

			// Take what's left.
			$uri = substr( $uri, strlen( $result['scheme'] ) + 1 );
		}

		// Taking off a fragment part
		if ( FALSE !== strpos( $uri, '#' ) )
			list( $uri, $result['fragment'] ) = explode( '#', $uri, 2 );

		// Taking off the query part
		if ( FALSE !== strpos( $uri, '?' ) )
			list( $uri, $result['query'] ) = explode( '?', $uri, 2 );


		// The triple slash uris are a bit unusual,
		// but we have special handling for them.
		if ( '///' === substr( $uri, 0, 3 ) ) {

			$result['path'] = substr( $uri, 2 );
			$result['host'] = '';

		// Uris that have an authority part.
		} else if ( '//' === substr( $uri, 0, 2 ) ) {

			$pattern = '
			%^
				//
				(?: (?<user> [^:@]+) (: (?<pass> [^@]+)) @)?
				(?<host> ( [^:/]* | \[ [^\]]+ \] ))
				(?: : (?<port> [0-9]+))?
				(?<path> / .*)?
			$%x
			';

			if ( ! preg_match( $pattern, $uri, $matches ) )
				throw new Exception( 'Invalid, or could not parse URI' );

			if ( $matches['host'] )
				$result['host'] = $matches['host'];

			if ( $matches['port'] )
				$result['port'] = (int) $matches['port'];

			if ( isset( $matches['path'] ) )
				$result['path'] = $matches['path'];

			if ( $matches['user'] )
				$result['user'] = $matches['user'];

			if ( $matches['pass'] )
				$result['pass'] = $matches['pass'];

		} else {

			$result['path'] = $uri;
		}

		return $result;
	}
}
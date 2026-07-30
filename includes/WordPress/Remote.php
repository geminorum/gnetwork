<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Remote extends Core\Base
{
	/**
	 * Retrieves data from the JSON body of a GET request, given a URL.
	 *
	 * @param string $url
	 * @param array $atts
	 * @param bool $assoc
	 * @return false|array|object
	 */
	public static function getJSON( false|string $url, array $atts = [], bool $assoc = TRUE ): false|array|object
	{
		if ( ! $url )
			return FALSE;

		$args = self::recursiveParseArgs( $atts, [
			'timeout' => 15,
			'headers' => [ 'Accept' => 'application/json' ],
		] );

		// `$response = wp_remote_get( $url, $args );`
		$response = wp_safe_remote_get( $url, $args );

		if ( self::isError( $response ) )
			return Core\HTTP::logError( $url, $response->get_error_message(), 'GETJSON' );

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status )
			return Core\HTTP::logError( $url, sprintf( '%d: %s', $status, Core\HTTP::getStatusDesc( $status, 'UKNOWN STATUS' ) ), 'GETJSON' );

		if ( ! $body = wp_remote_retrieve_body( $response ) )
			return Core\HTTP::logError( $url, '200: EMPTY BODY', 'GETJSON' );

		$data = json_decode( $body, $assoc );

		if ( json_last_error() !== JSON_ERROR_NONE )
			return Core\HTTP::logError( $url, sprintf( '200: JSON MALFORMED', json_last_error_msg() ), 'GETJSON' );

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
	public static function postJSON( mixed $body, false|string $url, array $atts = [], bool $assoc = TRUE ): false|array|object
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
			return Core\HTTP::logError( $url, $response->get_error_message(), 'POSTJSON' );

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status )
			return Core\HTTP::logError( $url, sprintf( '%d: %s', $status, Core\HTTP::getStatusDesc( $status, 'UKNOWN STATUS' ) ), 'POSTJSON' );

		if ( ! $body = wp_remote_retrieve_body( $response ) )
			return Core\HTTP::logError( $url, '200: EMPTY BODY', 'POSTJSON' );

		$data = json_decode( $body, $assoc );

		if ( json_last_error() !== JSON_ERROR_NONE )
			return Core\HTTP::logError( $url, sprintf( '200: JSON MALFORMED', json_last_error_msg() ), 'POSTJSON' );

		return $data;
	}

	/**
	 * Retrieves data from the HTML body of a GET request, given a URL.
	 *
	 * @see https://deliciousbrains.com/wordpress-http-api-requests/
	 *
	 * @param false|string $url
	 * @param array $atts
	 * @return false|string
	 */
	public static function getHTML( false|string $url, array $atts = [] ): false|string
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
			return Core\HTTP::logError( $url, $response->get_error_message(), 'GETHTML' );

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status )
			return Core\HTTP::logError( $url, sprintf( '%d: %s', $status, Core\HTTP::getStatusDesc( $status, 'UKNOWN STATUS' ) ), 'GETHTML' );

		if ( ! $body = wp_remote_retrieve_body( $response ) )
			return Core\HTTP::logError( $url, '200: EMPTY BODY', 'GETHTML' );

		return $body;
	}

	/**
	 * Retrieves data from the content body of a GET request, given a URL.
	 * NOTE: without `accept` header
	 *
	 * @param false|string $url
	 * @param array $atts
	 * @return false|string
	 */
	public static function getContents( false|string $url, array $atts = [] ): false|string
	{
		if ( ! $url )
			return FALSE;

		$args = self::recursiveParseArgs( $atts, [
			'timeout' => 15,
		] );

		$response = wp_safe_remote_get( $url, $args );

		if ( self::isError( $response ) )
			return Core\HTTP::logError( $url, $response->get_error_message(), 'GETCONTENTS' );

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status )
			return Core\HTTP::logError( $url, sprintf( '%d: %s', $status, Core\HTTP::getStatusDesc( $status, 'UKNOWN STATUS' ) ), 'GETCONTENTS' );

		if ( ! $body = wp_remote_retrieve_body( $response ) )
			return Core\HTTP::logError( $url, '200: EMPTY BODY', 'GETCONTENTS' );

		return $body;
	}
}

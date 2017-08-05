<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Ajax extends Core\Base
{

	const BASE = 'gnetwork';

	public static function checkReferer( $action = NULL, $key = 'nonce' )
	{
		check_ajax_referer( ( is_null( $action ) ? self::BASE : $action ), $key );
	}

	public static function success( $data = NULL, $status_code = NULL )
	{
		wp_send_json_success( $data, $status_code );
	}

	public static function error( $data = NULL, $status_code = NULL )
	{
		wp_send_json_error( $data, $status_code );
	}

	public static function successHTML( $html, $status_code = NULL )
	{
		self::success( [ 'html' => $html ], $status_code );
	}

	public static function successMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Succesful!', 'Ajax: Ajax Notice', GNETWORK_TEXTDOMAIN );

		if ( $message )
			self::success( HTML::success( $message ) );
		else
			self::success();
	}

	public static function errorMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Error!', 'Ajax: Ajax Notice', GNETWORK_TEXTDOMAIN );

		if ( $message )
			self::error( HTML::error( $message ) );
		else
			self::error();
	}

	public static function errorWhat()
	{
		self::errorMessage( _x( 'What?!', 'Ajax: Ajax Notice', GNETWORK_TEXTDOMAIN ) );
	}
}

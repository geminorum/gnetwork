<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// @SEE: http://code.tutsplus.com/tutorials/wordpress-error-handling-with-wp_error-class-i--cms-21120
// https://codex.wordpress.org/Class_Reference/WP_Error

class Error extends \WP_Error
{

	public function __tostring()
	{
		return $this->get_error_message();
	}
}

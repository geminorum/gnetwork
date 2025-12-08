<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Hook extends Core\Base
{

	// @SEE: https://github.com/Rarst/advanced-hooks-api

	// shows all the "filters" currently attached to a hook
	public static function filters( $hook, $verbose = TRUE )
	{
		global $wp_filter;

		if ( ! isset( $wp_filter[$hook] ) )
			return self::dump( $hook.': none' );

		if ( ! $verbose )
			return $wp_filter[$hook];

		self::dump( $wp_filter[$hook] );
	}

	/**
	 * Adds a callback function to a filter hook and run only once.
	 * This works around the common "filter sandwich" pattern where you have to
	 * remember to call `remove_filter()` again after your call.
	 * @source https://gist.github.com/markjaquith/b752e3aa93d2421285757ada2a4869b1
	 *
	 * @param string $hook_name
	 * @param callable $callback
	 * @param int $priority
	 * @param int $accepted_args
	 * @return void
	 */
	public static function filterOnce( $hook_name, $callback, $priority = 10, $accepted_args = 1 )
	{
		add_filter( $hook_name,
			static function ( $data ) use ( $callback ) {
				static $once = FALSE;
				if ( $once ) return $data;
				$once = TRUE;
				return call_user_func_array( $callback, func_get_args() );

			}, $priority, $accepted_args );
	}
}

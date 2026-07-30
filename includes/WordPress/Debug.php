<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Debug extends Core\Base
{
	/**
	 * Returns the list of functions that have been called
	 * to get to the current point in code.
	 *
	 * NOTE: Clone of `wp_debug_backtrace_summary()`
	 * @source https://gist.github.com/christianwach/ff07358a7fde4b2b7c7df5973f673449
	 * @see https://core.trac.wordpress.org/ticket/19589
	 *
	 * @param string $ignore_class Optional. A class to ignore all function calls within - useful
	 *                             when you want to just give info about the callee. Default null.
	 * @param int    $skip_frames  Optional. A number of stack frames to skip - useful for unwinding
	 *                             back to the source of the issue. Default 0.
	 * @param bool   $pretty       Optional. Whether or not you want a comma separated string or raw
	 *                             array returned. Default true.
	 * @return string|array Either a string containing a reversed comma separated trace or an array
	 *                      of individual calls.
	 */
	public static function backtraceSummary(
		?string $ignore_class = NULL,
		int $skip_frames = 0,
		bool $pretty = TRUE,
	): string|array {

		$trace       = debug_backtrace();
		$caller      = [];
		$check_class = ! is_null( $ignore_class );
		$skip_frames++; // skip this function

		foreach ( $trace as $call ) {

			$line = isset( $call['line'] )
				? ( ' line:'.$call['line'] )
				: ' <unknown line>';

			if ( $skip_frames > 0 ) {

				$skip_frames--;

			} else if ( isset( $call['class'] ) ) {

				if ( $check_class && $ignore_class == $call['class'] )
					continue; // Filters out calls!

				$caller[] = "{$call['class']}{$call['type']}{$call['function']}".$line;

			} else {

				if ( in_array( $call['function'], [ 'do_action', 'apply_filters' ] ) ) {

					$caller[] = "{$call['function']}('{$call['args'][0]}')".$line;

				} else if ( in_array( $call['function'], [ 'include', 'include_once', 'require', 'require_once' ] ) ) {

					$caller[] = $call['function']."('".str_replace( [ WP_CONTENT_DIR, ABSPATH ], '', $call['args'][0])."')".$line;

				} else {

					$caller[] = $call['function'].$line;
				}
			}
		}

		return $pretty
			? join( ', ', array_reverse( $caller ) )
			: $caller;
	}
}

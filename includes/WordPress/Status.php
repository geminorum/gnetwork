<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Status extends Core\Base
{

	/**
	 * Gets a list of post statuses.
	 *
	 * @source `get_post_stati()`
	 *
	 * @param  int $mod
	 * @param  null|string $capability
	 * @param  int $user_id
	 * @return array $list
	 */
	public static function get( $mod = 0, $args = [] )
	{
		$list = [];

		foreach ( get_post_stati( $args, 'objects' ) as $object ) {

			// just the name!
			if ( -1 === $mod )
				$list[] = $object->name;

			// label
			else if ( 0 === $mod )
				$list[$object->name] = $object->label ?: $object->name;

			// object
			else if ( 4 === $mod )
				$list[$object->name] = $object;
		}

		return $list;
	}

	/**
	 * Determines whether a post status is considered “viewable”.
	 *
	 * @param  string|stdClass $status
	 * @return bool $viewable
	 */
	public static function viewable( $status )
	{
		if ( ! $status )
			return FALSE;

		return is_post_status_viewable( $status );
	}
}

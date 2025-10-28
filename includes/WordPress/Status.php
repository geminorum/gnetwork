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
	 * @param int $mod
	 * @param string $capability
	 * @param int $user_id
	 * @return array
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
	 * Retrieves available post statuses for given post-type.
	 * TODO: https://developer.wordpress.org/apis/transients/
	 *
	 * @param string $posttype
	 * @param array $excludes
	 * @return array
	 */
	public static function available( $posttype, $excludes = NULL )
	{
		if ( is_null( $excludes ) )
			$excludes = [
				'trash',
				'private',
				'auto-draft',
			];

		$statuses = Core\WordPress::isAdvancedCache()
			? get_available_post_statuses( $posttype )
			// : [ 'publish', 'future', 'draft' ];
			: [ 'publish', 'future' ];

		return array_diff_key( $statuses, (array) $excludes );
	}

	/**
	 * Determines whether a post status is considered “viewable”.
	 *
	 * @param string|object $status
	 * @return bool
	 */
	public static function viewable( $status )
	{
		if ( ! $status )
			return FALSE;

		return is_post_status_viewable( $status );
	}

	/**
	 * Retrieves filtered post statuses for given post-types.
	 *
	 * @param string|array $posttypes
	 * @param string $context
	 * @param array $excludes
	 * @return array
	 */
	public static function acceptable( $posttypes = 'any', $context = 'query', $excludes = NULL )
	{
		// @SEE: `WordPress\Database::getExcludeStatuses()`
		if ( is_null( $excludes ) )
			$excludes = [
				// 'draft',
				'private',
				'trash',
				'auto-draft',
				'inherit',
			];

		$statuses = [
			'publish',
			'future',
			'pending',
			'draft',
		];

		return apply_filters( 'gnetwork_status_acceptable',
			array_diff( $statuses, (array) $excludes ),
			(array) $posttypes,
			$context,
			$excludes
		);
	}
}

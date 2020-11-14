<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class User extends Core\Base
{

	public static function getObjectbyMeta( $meta, $value, $network = TRUE )
	{
		$args = [
			'meta_key'    => $meta,
			'meta_value'  => $value,
			'compare'     => '=',
			'number'      => 1,
			'count_total' => FALSE,
		];

		if ( $network )
			$args['blog_id'] = 0;

		$query = new \WP_User_Query( $args );
		$users = $query->get_results();

		return reset( $users );
	}

	public static function getIDbyMeta( $meta, $value )
	{
		static $results = [];

		if ( isset( $results[$meta][$value] ) )
			return $results[$meta][$value];

		global $wpdb;

		$post_id = $wpdb->get_var(
			$wpdb->prepare( "
				SELECT user_id
				FROM {$wpdb->usermeta}
				WHERE meta_key = %s
				AND meta_value = %s
			", $meta, $value )
		);

		return $results[$meta][$value] = $post_id;
	}
}

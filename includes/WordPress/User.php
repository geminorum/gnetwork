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

	public static function getIDbyMeta( $meta, $value, $single = TRUE )
	{
		static $data = [];

		$group = $single ? 'single' : 'all';

		if ( isset( $data[$meta][$group][$value] ) )
			return $data[$meta][$group][$value];

		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = %s
			AND meta_value = %s
		", $meta, $value );

		$results = $single
			? $wpdb->get_var( $query )
			: $wpdb->get_col( $query );

		return $data[$meta][$group][$value] = $results;
	}
}

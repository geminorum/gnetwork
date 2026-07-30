<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class TermMeta extends Core\Base
{
	public static function listAvailable( bool $include_private = FALSE ): array
	{
		global $wpdb;

		if ( $include_private )
			return $wpdb->get_col( "
				SELECT meta_key
				FROM {$wpdb->termmeta}
				GROUP BY meta_key
				ORDER BY meta_key ASC
			" );

		else
			return $wpdb->get_col( "
				SELECT meta_key
				FROM {$wpdb->termmeta}
				GROUP BY meta_key
				HAVING meta_key NOT LIKE '\_%'
				ORDER BY meta_key ASC
			" );
	}

	// NOTE: makes multiple values flat by comma
	public static function listByKey( string $meta_key, ?int $limit = NULL ): array
	{
		global $wpdb;

		if ( absint( $limit ?: 0 ) )
			$query = $wpdb->prepare( "
				SELECT term_id, GROUP_CONCAT( meta_value ) as meta
				FROM {$wpdb->termmeta}
				WHERE meta_key = %s
				GROUP BY term_id
				LIMIT %d
			", $meta_key, (int) $limit );

		else
			$query = $wpdb->prepare( "
				SELECT term_id, GROUP_CONCAT( meta_value ) as meta
				FROM {$wpdb->termmeta}
				WHERE meta_key = %s
				GROUP BY term_id
			", $meta_key );

		return $wpdb->get_results( $query );
	}

	// @ALT: `delete_post_meta_by_key( $meta_key )`
	public static function deleteByKey( string $meta_key, ?int $limit = 0 ): int|bool
	{
		global $wpdb;

		if ( absint( $limit ?: 0 ) )
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->termmeta}
				WHERE meta_key = %s
				LIMIT %d
			", $meta_key, $limit );

		else
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->termmeta}
				WHERE meta_key = %s
			", $meta_key );

		return $wpdb->query( $query );
	}

	public static function deleteEmpty( string $meta_key ): array
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->termmeta}
			WHERE meta_key = %s
			AND meta_value = ''
		" , $meta_key );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	public static function changeKey( string $from, string $to ): int|false
	{
		global $wpdb;

		if ( ! $from || ! $to || $from === $to )
			return FALSE;

		return $wpdb->update(
			$wpdb->termmeta,
			[ 'meta_key' => $to   ],
			[ 'meta_key' => $from ],
			[ '%s' ],
			[ '%s' ],
		);
	}

	// @REF https://anchor.host/bulk-renaming-meta-fields/
	public static function updateKey( string $old_key, string $new_key ): array
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			UPDATE {$wpdb->termmeta}
			SET meta_key = %s
			WHERE meta_key = %s
		", $new_key, $old_key );

		return $wpdb->get_results( $query, ARRAY_A );
	}
}

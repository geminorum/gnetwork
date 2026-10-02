<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class PostMeta extends Core\Base
{
	// @REF: https://github.com/scribu/wp-custom-field-taxonomies
	// FIXME: must limit to the selected post-types
	// @OLD: `WordPress\Database::getPostMetaKeys()`
	public static function listAvailable( bool $include_private = FALSE ): array
	{
		global $wpdb;

		if ( $include_private )
			return $wpdb->get_col( "
				SELECT meta_key
				FROM {$wpdb->postmeta}
				GROUP BY meta_key
				ORDER BY meta_key ASC
			" );

		else
			return $wpdb->get_col( "
				SELECT meta_key
				FROM {$wpdb->postmeta}
				GROUP BY meta_key
				HAVING meta_key NOT LIKE '\_%'
				ORDER BY meta_key ASC
			" );
	}

	// @REF: https://github.com/scribu/wp-custom-field-taxonomies
	// FIXME: must limit to the selected post-types
	// @OLD: `WordPress\Database::getPostMetaRows()`
	// NOTE: makes multiple values flat by comma
	public static function listByKey( string $meta_key, ?int $limit = NULL ): array
	{
		global $wpdb;

		if ( absint( $limit ?: 0 ) )
			$query = $wpdb->prepare( "
				SELECT post_id, GROUP_CONCAT( meta_value ) as meta
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				GROUP BY post_id
				LIMIT %d
			", $meta_key, (int) $limit );

		else
			$query = $wpdb->prepare( "
				SELECT post_id, GROUP_CONCAT( meta_value ) as meta
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				GROUP BY post_id
			", $meta_key );

		return $wpdb->get_results( $query );
	}

	// @ALT: `delete_post_meta_by_key( $meta_key )`
	// @OLD: `WordPress\Database::deletePostMeta()`
	public static function deleteByKey( string $meta_key, ?int $limit = 0 ): int|bool
	{
		global $wpdb;

		if ( absint( $limit ?: 0 ) )
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				LIMIT %d
			", $meta_key, $limit );

		else
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->postmeta}
				WHERE meta_key = %s
			", $meta_key );

		return $wpdb->query( $query );
	}

	// @OLD: `WordPress\Database::deleteEmptyMeta()`
	public static function deleteEmpty( string $meta_key ): array
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value = ''
		" , $meta_key );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	// @OLD: `WordPress\Database::changePostMetaKey()`
	public static function changeKey( string $from, string $to ): int|false
	{
		global $wpdb;

		if ( ! $from || ! $to || $from === $to )
			return FALSE;

		return $wpdb->update(
			$wpdb->postmeta,
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
			UPDATE {$wpdb->postmeta}
			SET meta_key = %s
			WHERE meta_key = %s
		", $new_key, $old_key );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Retrieves the post-id given meta-key and value.
	 * @old `WordPress\PostType::getIDbyMeta()`
	 * @SEE: https://tommcfarlin.com/get-post-id-by-meta-value/
	 * TODO: support regex on meta-keys
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param bool $single
	 * @return false|int|array
	 */
	public static function getID( string $key, mixed $value, bool $single = TRUE ): false|int|array
	{
		global $wpdb, $NucleusPostIDbyMeta;

		if ( self::empty( $value ) )
			return FALSE;

		if ( ! $key = Core\Text::force( $key ) )
			return FALSE;

		if ( empty( $NucleusPostIDbyMeta ) )
			$NucleusPostIDbyMeta = [];

		$group = $single ? 'single' : 'all';

		if ( isset( $NucleusPostIDbyMeta[$key][$group][$value] ) )
			return $NucleusPostIDbyMeta[$key][$group][$value];

		$query = $wpdb->prepare( "
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value = %s
		", $key, $value );

		$results = $single
			? $wpdb->get_var( $query )
			: $wpdb->get_col( $query );

		return $NucleusPostIDbyMeta[$key][$group][$value] = $results;
	}

	/**
	 * Retrieves a list of post-ids given meta-key and values.
	 * @OLD: `WordPress\PostType::getIDListbyMeta()`
	 *
	 * @param string $meta_key
	 * @param array $values
	 * @return false|array
	 */
	public static function getIDList( string $meta_key, array $values ): false|array
	{
		global $wpdb, $NucleusPostIDbyMeta;

		if ( ! $meta_key = Core\Text::force( $meta_key ) )
			return FALSE;

		$filtered = array_filter( (array) $values );

		if ( empty( $filtered ) )
			return FALSE;

		$query = $wpdb->prepare( "
			SELECT post_id, meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value IN ( '".implode( "', '", esc_sql( $filtered ) )."' )
		", $meta_key );

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( empty( $results ) )
			return [];

		$list = Core\Arraay::pluck( $results, 'post_id', 'meta_value' );

		if ( empty( $NucleusPostIDbyMeta ) )
			$NucleusPostIDbyMeta = [];

		// update cache
		foreach ( $filtered as $value )
			$NucleusPostIDbyMeta[$meta_key]['single'][$value] = array_key_exists( $value, $list ) ? $list[$value] : FALSE;

		return $list;
	}

	// OLD: `WordPress\PostType::invalidateIDbyMeta()`
	public static function invalidateID( string|array $meta_keys, mixed $value = FALSE ): bool
	{
		global $NucleusPostIDbyMeta;

		if ( empty( $NucleusPostIDbyMeta ) )
			return TRUE;

		if ( empty( $meta_keys ) )
			return FALSE;

		if ( FALSE === $value ) {

			// clear all meta by key
			foreach ( (array) $meta_keys as $key ) {
				unset( $NucleusPostIDbyMeta[$key]['all'] );
				unset( $NucleusPostIDbyMeta[$key]['single'] );
			}

		} else {

			foreach ( (array) $meta_keys as $key ) {
				unset( $NucleusPostIDbyMeta[$key]['all'][$value] );
				unset( $NucleusPostIDbyMeta[$key]['single'][$value] );
			}
		}

		return TRUE;
	}
}

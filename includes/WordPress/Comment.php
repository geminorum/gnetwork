<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Comment extends Core\Base
{

	/**
	 * Retrieves comment data given a comment ID or comment object.
	 * NOTE: simplified version of `get_comment()`
	 *
	 * @param int|object $comment
	 * @param string $output
	 * @return object
	 */
	public static function get( $comment = NULL, $output = OBJECT )
	{
		if ( FALSE === $comment )
			return $comment;

		if ( $comment instanceof \WP_Comment )
			return $comment;

		if ( $_comment = get_comment( $comment, $output ) )
			return $_comment;

		if ( is_null( $comment ) && is_admin() && ( $query = self::req( 'c' ) ) )
			return get_comment( $query, $output );

		return NULL;
	}

	/**
	 * Retrieves comment type given a comment ID or comment object.
	 *
	 * @param int|object $comment
	 * @return string
	 */
	public static function type( $comment = NULL )
	{
		if ( $comment = self::get( $comment ) )
			return $comment->comment_type;

		return FALSE;
	}

	/**
	 * Retrieves meta-data for a given comment.
	 *
	 * @param object|int $comment
	 * @param bool|array $keys `false` for all meta
	 * @param bool $single
	 * @return array
	 */
	public static function getMeta( $comment, $keys = FALSE, $single = TRUE )
	{
		if ( ! $comment = self::get( $comment ) )
			return FALSE;

		$list = [];

		if ( FALSE === $keys ) {

			if ( $single ) {

				foreach ( (array) get_metadata( 'comment', $comment->comment_ID ) as $key => $meta )
					$list[$key] = maybe_unserialize( $meta[0] );

			} else {

				foreach ( (array) get_metadata( 'comment', $comment->comment_ID ) as $key => $meta )
					foreach ( $meta as $offset => $value )
						$list[$key][$offset] = maybe_unserialize( $value );
			}

		} else {

			foreach ( $keys as $key => $default )
				$list[$key] = get_metadata( 'comment', $comment->comment_ID, $key, $single ) ?: $default;
		}

		return $list;
	}

	/**
	 * Retrieves comment rest route given a comment ID or comment object.
	 *
	 * @param int|object $comment
	 * @return false|string
	 */
	public static function getRestRoute( $comment = NULL )
	{
		if ( ! $comment = self::get( $comment ) )
			return FALSE;

		return sprintf( '/wp/v2/comments/%d', $comment->comment_ID );
	}

	public static function setKarma( $karma, $comment = NULL )
	{
		global $wpdb;

		if ( ! $comment = self::get( $comment ) )
			return FALSE;

		$result = $wpdb->update(
			$wpdb->comments,
			[ 'comment_karma' => $karma               ],
			[ 'comment_ID'    => $comment->comment_ID ]
		);

		if ( FALSE === $result )
			return FALSE;

		clean_comment_cache( $comment->comment_ID );

		return $comment->comment_ID;
	}
}

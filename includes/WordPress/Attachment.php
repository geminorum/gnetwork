<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Attachment extends Core\Base
{

	/**
	 * Retrieves attachments for a parent post given mime type.
	 * @old `Media::getAttachments()`
	 * @old `Attachment::get()`
	 *
	 * @param null|int|object $parent
	 * @param string $mime
	 * @param mixed $fallback
	 * @return array $attachments
	 */
	public static function list( $parent = NULL, $mime = 'image', $fallback = [] )
	{
		if ( ! $post = Post::get( $parent ) )
			return $fallback;

		return get_children( [
			'post_mime_type' => $mime,
			'post_parent'    => $post->ID,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'numberposts'    => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		] );
	}

	// public static function viewable( $attachment ) {}
	// public static function link( $attachment ) {}
	// public static function shortlink( $attachment ) {}
	// public static function summary( $attachment, $context = NULL ) {}
	// public static function select( $mime = NULL, $context = NULL ) {} // Media::selectAttachment();

	/**
	 * Retrieves the user capability for a given attachment.
	 * NOTE: caches the result
	 *
	 * @param int|object $attachment
	 * @param null|string $capability
	 * @param null|int|object $user_id
	 * @param bool $fallback
	 * @return bool $can
	 */
	public static function can( $attachment, $capability, $user_id = NULL, $fallback = FALSE )
	{
		return Post::can( $attachment, $capability, $user_id, $fallback );
	}

	/**
	 * Retrieves the URL for editing a given attachment.
	 *
	 * @param int|object $attachment
	 * @param array $extra
	 * @param mixed $fallback
	 * @return string $link
	 */
	public static function edit( $attachment, $extra = [], $fallback = FALSE )
	{
		return Post::edit( $attachment, $extra, $fallback );
	}

	/**
	 * Retrieves mime type given an attachment ID or attachment object.
	 *
	 * @param null|int|object $attachment
	 * @param mixed $fallback
	 * @return string $mime
	 */
	public static function type( $attachment = NULL, $fallback = FALSE )
	{
		if ( $post = Post::get( $attachment ) )
			return $post->post_mime_type;

		return $fallback;
	}

	/**
	 * Retrieves attachment title given an attachment ID or attachment object.
	 *
	 * @param null|int|object $attachment
	 * @param null|string $fallback
	 * @param bool $filter
	 * @return string $title
	 */
	public static function title( $attachment = NULL, $fallback = NULL, $filter = TRUE )
	{
		return Post::title( $attachment, $fallback, $filter );
	}

	public static function caption( $attachment = NULL, $fallback = FALSE )
	{
		if ( $post = Post::get( $attachment ) )
			return wp_get_attachment_caption( $post->ID );

		return $fallback;
	}

	/**
	 * Retrieves attachment rest route given an attachment ID or attachment object.
	 *
	 * @param null|int|object $post
	 * @return false|string $route
	 */
	public static function getRestRoute( $post = NULL )
	{
		return Post::getRestRoute( $post );
	}
}

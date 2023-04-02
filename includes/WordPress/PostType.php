<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class PostType extends Core\Base
{

	public static function object( $posttype_or_post )
	{
		if ( ! $posttype_or_post )
			return FALSE;

		if ( $posttype_or_post instanceof \WP_Post )
			return get_post_type_object( $posttype_or_post->post_type );

		if ( $posttype_or_post instanceof \WP_Post_Type )
			return $posttype_or_post;

		return get_post_type_object( $posttype_or_post );
	}

	// TODO: also passing a post
	public static function viewable( $posttype )
	{
		return is_post_type_viewable( $posttype );
	}

	// TODO: also passing a post
	public static function viewableStatus( $status )
	{
		return is_post_status_viewable( $status );
	}

	// @REF: `is_post_publicly_viewable()` @since WP5.7.0
	public static function viewablePost( $post )
	{
		if ( ! $post = self::getPost( $post ) )
			return FALSE;

		return self::viewable( $post->post_type )
			&& self::viewableStatus( get_post_status( $post ) );
	}

	public static function can( $posttype, $capability = 'edit_posts', $user_id = NULL )
	{
		if ( is_null( $capability ) )
			return TRUE;

		$cap = self::object( $posttype )->cap->{$capability};

		return is_null( $user_id )
			? current_user_can( $cap )
			: user_can( $user_id, $cap );
	}

	public static function get( $mod = 0, $args = [ 'public' => TRUE ], $capability = NULL, $user_id = NULL )
	{
		$list = [];

		foreach ( get_post_types( $args, 'objects' ) as $posttype => $posttype_obj ) {

			if ( ! self::can( $posttype_obj, $capability, $user_id ) )
				continue;

			// just the name!
			if ( -1 === $mod )
				$list[] = $posttype_obj->name;

			// label
			else if ( 0 === $mod )
				$list[$posttype] = $posttype_obj->label ? $posttype_obj->label : $posttype_obj->name;

			// plural
			else if ( 1 === $mod )
				$list[$posttype] = $posttype_obj->labels->name;

			// singular
			else if ( 2 === $mod )
				$list[$posttype] = $posttype_obj->labels->singular_name;

			// nooped
			else if ( 3 === $mod )
				$list[$posttype] = [
					0          => $posttype_obj->labels->singular_name,
					1          => $posttype_obj->labels->name,
					'singular' => $posttype_obj->labels->singular_name,
					'plural'   => $posttype_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				];

			// object
			else if ( 4 === $mod )
				$list[$posttype] = $posttype_obj;
		}

		return $list;
	}

	// * 'publish' - a published post or page
	// * 'pending' - post is pending review
	// * 'draft' - a post in draft status
	// * 'auto-draft' - a newly created post, with no content
	// * 'future' - a post to publish in the future
	// * 'private' - not visible to users who are not logged in
	// * 'inherit' - a revision. see get_children.
	// * 'trash' - post is in trashbin. added with Version 2.9.
	public static function getStatuses()
	{
		global $wp_post_statuses;

		$statuses = array();

		foreach ( $wp_post_statuses as $status )
			$statuses[$status->name] = $status->label;

		return $statuses;
	}

	public static function getArchiveLink( $posttype )
	{
		return apply_filters( 'gnetwork_posttype_archive_link', get_post_type_archive_link( $posttype ), $posttype );
	}
}

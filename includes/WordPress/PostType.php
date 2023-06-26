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

	public static function viewable( $posttype )
	{
		if ( ! $posttype )
			return FALSE;

		return is_post_type_viewable( $posttype );
	}

	// @REF: `is_post_publicly_viewable()` @since WP5.7.0
	public static function viewablePost( $post )
	{
		if ( ! $post = self::getPost( $post ) )
			return FALSE;

		return self::viewable( $post->post_type )
			&& Status::viewable( get_post_status( $post ) );
	}

	/**
	 * Retrieves post data given a post ID or post object.
	 *
	 * simplified `get_post()`
	 *
	 * @param  null|int|object $post
	 * @param  string $output
	 * @param  string $filter
	 * @return object $post
	 */
	public static function getPost( $post = NULL, $output = OBJECT, $filter = 'raw' )
	{
		if ( $post instanceof \WP_Post )
			return $post;

		// handling dummy posts!
		if ( '-9999' == $post )
			$post = NULL;

		if ( $_post = get_post( $post, $output, $filter ) )
			return $_post;

		if ( is_null( $post ) && is_admin() && ( $query = self::req( 'post' ) ) )
			return get_post( $query, $output, $filter );

		return NULL;
	}

	/**
	 * Checks for posttype capability.
	 *
	 * If assigned posttype `capability_type` arg:
	 *
	 * /// Meta capabilities
	 * 	[edit_post]   => "edit_{$capability_type}"
	 * 	[read_post]   => "read_{$capability_type}"
	 * 	[delete_post] => "delete_{$capability_type}"
	 *
	 * /// Primitive capabilities used outside of map_meta_cap():
	 * 	[edit_posts]             => "edit_{$capability_type}s"
	 * 	[edit_others_posts]      => "edit_others_{$capability_type}s"
	 * 	[publish_posts]          => "publish_{$capability_type}s"
	 * 	[read_private_posts]     => "read_private_{$capability_type}s"
	 *
	 * /// Primitive capabilities used within map_meta_cap():
	 * 	[read]                   => "read",
	 * 	[delete_posts]           => "delete_{$capability_type}s"
	 * 	[delete_private_posts]   => "delete_private_{$capability_type}s"
	 * 	[delete_published_posts] => "delete_published_{$capability_type}s"
	 * 	[delete_others_posts]    => "delete_others_{$capability_type}s"
	 * 	[edit_private_posts]     => "edit_private_{$capability_type}s"
	 * 	[edit_published_posts]   => "edit_published_{$capability_type}s"
	 * 	[create_posts]           => "edit_{$capability_type}s"
	 *
	 * @param  string|object $posttype
	 * @param  null|string $capability
	 * @param  null|int|object $user_id
	 * @return bool $can
	 */
	public static function can( $posttype, $capability = 'edit_posts', $user_id = NULL )
	{
		if ( is_null( $capability ) )
			return TRUE;

		if ( ! $object = self::object( $posttype ) )
			return FALSE;

		if ( ! isset( $object->cap->{$capability} ) )
			return FALSE;

		return is_null( $user_id )
			? current_user_can( $object->cap->{$capability} )
			: user_can( $user_id, $object->cap->{$capability} );
	}

	/**
	 * Retrieves the list of posttypes.
	 *
	 * Argument values for `$args` include:
	 * 	`public` Boolean: If true, only public post types will be returned.
	 * 	`publicly_queryable` Boolean
	 * 	`exclude_from_search` Boolean
	 * 	`show_ui` Boolean
	 * 	`capability_type`
	 * 	`hierarchical`
	 * 	`menu_position`
	 * 	`menu_icon`
	 * 	`permalink_epmask`
	 *  `rewrite`
	 * 	`query_var`
	 *  `show_in_rest` Boolean: If true, will return post types whitelisted for the REST API
	 * 	`_builtin` Boolean: If true, will return WordPress default post types. Use false to return only custom post types.
	 *
	 * @param  int $mod
	 * @param  array $args
	 * @param  null|string $capability
	 * @param  int $user_id
	 * @return array $list
	 */
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
	// FIXME: DEPRECATED
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

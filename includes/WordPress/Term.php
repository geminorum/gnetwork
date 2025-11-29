<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Term extends Core\Base
{

	// TODO: `Term::setParent()`

	/**
	 * Gets all term data.
	 *
	 * @param int|object $term_or_id
	 * @param string $taxonomy
	 * @return false|object
	 */
	public static function get( $term_or_id = NULL, $taxonomy = '' )
	{
		if ( FALSE === $term_or_id )
			return $term_or_id;

		if ( $term_or_id instanceof \WP_Term )
			return $term_or_id;

		if ( is_wp_error( $term_or_id ) )
			return FALSE;

		if ( ! $term_or_id ) {

			if ( is_admin() ) {

				if ( is_null( $term_or_id ) && ( $query = self::req( 'tag_ID' ) ) )
					return self::get( (int) $query, $taxonomy );

				return FALSE;
			}

			if ( 'category' == $taxonomy && ! is_category() )
				return FALSE;

			if ( 'post_tag' == $taxonomy && ! is_tag() )
				return FALSE;

			if ( ! in_array( $taxonomy, [ 'category', 'post_tag' ] )
				&& ! is_tax( $taxonomy ) )
					return FALSE;

			if ( ! $term_or_id = get_queried_object_id() )
				return FALSE;
		}

		if ( is_numeric( $term_or_id ) )
			// $term = get_term_by( 'id', $term_or_id, $taxonomy );
			$term = get_term( (int) $term_or_id, $taxonomy ); // allows for empty taxonomy

		else if ( $taxonomy )
			$term = get_term_by( 'slug', $term_or_id, $taxonomy );

		else
			$term = get_term( $term_or_id, $taxonomy ); // allows for empty taxonomy

		if ( ! $term || is_wp_error( $term ) )
			return FALSE;

		return $term;
	}

	/**
	 * Retrieves the user capability for a given term.
	 * NOTE: caches the results
	 *
	 * @param int|object $term
	 * @param string $capability
	 * @param int|object $user_id
	 * @param mixed $fallback
	 * @return bool
	 */
	public static function can( $term, $capability, $user_id = NULL, $fallback = FALSE )
	{
		static $cache = [];

		if ( is_null( $capability ) )
			return TRUE;

		else if ( ! $capability )
			return $fallback;

		if ( ! $term = self::get( $term ) )
			return $fallback;

		/**
		 * The taxonomy is not registered, so it may not be reliable
		 * to check the capability against an unregistered taxonomy.
		 */
		if ( ! Taxonomy::exists( $term ) )
			return $fallback;

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		else if ( is_object( $user_id ) )
			$user_id = $user_id->ID;

		if ( ! $user_id )
			return user_can( $user_id, $capability, $term->term_id );

		if ( isset( $cache[$user_id][$term->term_id][$capability] ) )
			return $cache[$user_id][$term->term_id][$capability];

		$can = user_can( $user_id, $capability, $term->term_id );

		return $cache[$user_id][$term->term_id][$capability] = $can;
	}

	/**
	 * Retrieves term title given a term ID or term object.
	 * @old `Taxonomy::getTermTitle()`
	 *
	 * @param int|object $term
	 * @param string $fallback
	 * @param bool $filter
	 * @return string
	 */
	public static function title( $term, $fallback = NULL, $filter = TRUE )
	{
		if ( ! $term = self::get( $term ) )
			return '';

		$title = $filter
			? sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' )
			: $term->name;

		if ( ! empty( $title ) )
			return $title;

		if ( FALSE === $fallback )
			return '';

		if ( is_null( $fallback ) )
			return __( '(Untitled)' );

		return $fallback;
	}

	/**
	 * Retrieves term parent titles given a term ID or term object.
	 * NOTE: parent post-type can be different
	 *
	 * @param null|int|object $term
	 * @param string $suffix
	 * @param string|bool $linked
	 * @param string $separator
	 * @return string
	 */
	public static function getParentTitles( $term, $suffix = '', $linked = FALSE, $separator = NULL )
	{
		if ( ! $term = self::get( $term ) )
			return $suffix;

		if ( ! $term->parent )
			return $suffix;

		if ( is_null( $separator ) )
			$separator = Core\HTML::rtl() ? ' &rsaquo; ' : ' &lsaquo; ';

		$current = $term->term_id;
		$parents = [];
		$parent  = TRUE;

		while ( $parent ) {

			$object = self::get( (int) $current );
			$edit   = self::edit( $object );
			$link   = ( $edit && 'edit' === $linked ) ? $edit : self::link( $object );

			if ( $object && $object->parent )
				$parents[] = $linked && $link
					? Core\HTML::link( self::title( $object->parent ), $link )
					: self::title( $object->parent );

			else
				$parent = FALSE;

			if ( $object )
				$current = $object->parent;
		}

		if ( empty( $parents ) )
			return $suffix;

		return Strings::getJoined( array_reverse( $parents ), '', $suffix ? $separator.$suffix : '', '', $separator );
	}

	/**
	 * Retrieves term taxonomy given a term ID or term object.
	 *
	 * @param null|int|string|object $term
	 * @return string
	 */
	public static function taxonomy( $term )
	{
		if ( $object = self::get( $term ) )
			return $object->taxonomy;

		return FALSE;
	}

	/**
	 * Retrieves term link given a term ID or term object.
	 *
	 * @param null|int|string|object $term
	 * @param null|string $fallback
	 * @return string
	 */
	public static function link( $term, $fallback = NULL )
	{
		if ( ! $term = self::get( $term ) )
			return $fallback;

		if ( ! $url = get_term_link( $term, $term->taxonomy ) )
			return $fallback;

		return $url;
	}

	/**
	 * Retrieves the URL for editing a given term.
	 * @ref `get_edit_term_link()`
	 * @old `WordPress::getEditTaxLink()`
	 *
	 * @param int|object $term
	 * @param array $extra
	 * @param mixed $fallback
	 * @return string
	 */
	public static function edit( $term, $extra = [], $fallback = FALSE )
	{
		if ( ! $term = self::get( $term ) )
			return $fallback;

		if ( ! self::can( $term, 'edit_term' ) )
			return $fallback;

		$link = add_query_arg( array_merge( [
			'taxonomy' => $term->taxonomy,
			'tag_ID'   => $term->term_id,
		], $extra ), admin_url( 'term.php' ) );

		return apply_filters( 'get_edit_term_link', $link, $term->term_id, $term->taxonomy, '' );
	}

	/**
	 * Retrieves term short-link given a term ID or term object.
	 * OLD: `Core\WordPress::getTermShortLink()`
	 *
	 * @param int|object $term
	 * @param array $extra
	 * @param mixed $fallback
	 * @return string
	 */
	public static function shortlink( $term, $extra = [], $fallback = FALSE )
	{
		if ( ! $term = self::get( $term ) )
			return $fallback;

		return add_query_arg( array_merge( [ 't' => $term->term_id ], $extra ), get_bloginfo( 'url' ) );
	}

	public static function endpointURL( $endpoint, $term, $data = NULL, $extra = [], $fallback = FALSE )
	{
		if ( ! $term = self::get( $term ) )
			return $fallback;

		if ( ! $link = get_term_link( $term, $term->taxonomy ) )
			return $fallback;

		if ( $GLOBALS['wp_rewrite']->using_permalinks() ) {

			$link = Core\URL::trail( $link ).$endpoint.( $data ? ( '/'.$data ) : '' );

			return $extra ? add_query_arg( $extra, $link ) : $link;
		}

		return add_query_arg( array_merge( $extra, [ $endpoint => $data ?? '' ] ), $link );
	}

	/**
	 * Retrieves a contextual link given a term id or term object.
	 *
	 * @param null|int|object $term
	 * @param null|string $context
	 * @return false|string
	 */
	public static function overview( $term, $context = NULL )
	{
		if ( ! $term = self::get( $term ) )
			return FALSE;

		$filtered = apply_filters( 'geditorial_term_overview_pre_link', NULL, $term, $context );

		if ( ! is_null( $filtered ) )
			return $filtered;

		if ( is_admin() && ( $edit = self::edit( $term ) ) )
			return $edit;

		if ( Taxonomy::viewable( $term->taxonomy ) )
			return self::link( $term, FALSE );

		return FALSE;
	}

	/**
	 * Generates HTML link for given term
	 *
	 * @param null|int|string|object $term
	 * @param null|false|string $title
	 * @param bool|string $fallback
	 * @return false|string
	 */
	public static function htmlLink( $term, $title = NULL, $fallback = FALSE )
	{
		if ( ! $term = self::get( $term ) )
			return $fallback;

		if ( ! $url = get_term_link( $term, $term->taxonomy ) )
			return $fallback;

		if ( is_wp_error( $url ) )
			return $fallback;

		if ( FALSE === $title )
			return $url;

		if ( is_null( $title ) )
			$title = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );

		return Core\HTML::tag( 'a', [
			'href'  => $url,
			'class' => [ '-term', '-term-link' ],
			'data'  => [
				'term_id'  => $term->term_id,
				'taxonomy' => $term->taxonomy,
			],
		], $title );
	}

	/**
	 * Retrieves a contextual summary given a term ID or term object.
	 *
	 * @param null|int|object $term
	 * @param null|string $context
	 * @return false|array
	 */
	public static function summary( $term, $context = NULL )
	{
		if ( ! $term = self::get( $term ) )
			return FALSE;

		$taxonomy  = Taxonomy::object( $term );
		$timestamp = strtotime( get_term_meta( $term->term_id, 'datetime', TRUE ) );

		return [
			'_id'         => $term->term_id,
			'_type'       => $term->taxonomy,
			'_rest'       => Taxonomy::getRestRoute( $taxonomy ),
			'_base'       => $taxonomy->rest_base,
			'type'        => $taxonomy->label,
			'viewable'    => Taxonomy::viewable( $term->taxonomy ),
			'author'      => User::getTitleRow( get_term_meta( $term->term_id, 'author', TRUE ) ),
			'title'       => self::title( $term ),
			'link'        => self::link( $term, FALSE ),
			'edit'        => self::edit( $term ),
			'date'        => wp_date( get_option( 'date_format' ), $timestamp ),
			'time'        => wp_date( get_option( 'time_format' ), $timestamp ),
			'ago'         => $timestamp ? human_time_diff( $timestamp ) : FALSE,
			'image'       => self::image( $term, $context ),
			'description' => wpautop( apply_filters( 'html_format_i18n', $term->description ) ),
			'subtitle'    => get_term_meta( $term->term_id, 'subtitle', TRUE ) ?: get_term_meta( $term->term_id, 'tagline', TRUE ),
		];
	}

	/**
	 * Checks if a term exists and return term id only.
	 * @source `term_exists()`
	 *
	 * @param int|string $term
	 * @param string $taxonomy
	 * @param int $parent
	 * @return false|int
	 */
	public static function exists( $term, $taxonomy = '', $parent = NULL )
	{
		if ( ! $term )
			return FALSE;

		if ( $exists = term_exists( $term, $taxonomy, $parent ) )
			return $exists['term_id'];

		return FALSE;
	}

	/**
	 * Checks if a term is publicly viewable.
	 * @source: `is_term_publicly_viewable()` @since WP 6.1.0
	 *
	 * @param int|string|object $term
	 * @return bool
	 */
	public static function viewable( $term )
	{
		if ( ! $term = self::get( $term ) )
			return FALSE;

		return Taxonomy::viewable( $term->taxonomy );
	}

	/**
	 * Updates the taxonomy for the term.
	 *
	 * Also accepts term and taxonomy objects
	 * and checks if it's a different taxonomy.
	 *
	 * @param int|object $term
	 * @param string|object $taxonomy
	 * @param bool $clean_taxonomy
	 * @return bool
	 */
	public static function setTaxonomy( $term, $taxonomy, $clean_taxonomy = TRUE )
	{
		global $wpdb;

		if ( ! $taxonomy = Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( ! $term = self::get( $term ) )
			return FALSE;

		if ( $taxonomy->name === $term->taxonomy )
			return TRUE;

		$success = $wpdb->query( $wpdb->prepare( "
			UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE term_taxonomy_id = %d
		", $taxonomy->name, absint( $term->term_taxonomy_id ) ) );

		clean_term_cache( $term->term_taxonomy_id, $term->taxonomy, $clean_taxonomy );
		clean_term_cache( $term->term_taxonomy_id, $taxonomy->name, $clean_taxonomy );

		return $success;
	}

	/**
	 * Retrieves meta-data for a given term.
	 * @OLD: `Taxonomy::getTermMeta()`
	 *
	 * @param object|int $term
	 * @param bool|array $keys `false` for all meta
	 * @param bool $single
	 * @return array
	 */
	public static function getMeta( $term, $keys = FALSE, $single = TRUE )
	{
		if ( ! $term = self::get( $term ) )
			return FALSE;

		$list = [];

		if ( FALSE === $keys ) {

			if ( $single ) {

				foreach ( (array) get_metadata( 'term', $term->term_id ) as $key => $meta )
					$list[$key] = maybe_unserialize( $meta[0] );

			} else {

				foreach ( (array) get_metadata( 'term', $term->term_id ) as $key => $meta )
					foreach ( $meta as $offset => $value )
						$list[$key][$offset] = maybe_unserialize( $value );
			}

		} else {

			foreach ( $keys as $key => $default )
				$list[$key] = get_metadata( 'term', $term->term_id, $key, $single ) ?: $default;
		}

		return $list;
	}

	public static function add( $term, $taxonomy, $sanitize = TRUE )
	{
		if ( ! Taxonomy::exists( $taxonomy ) )
			return FALSE;

		if ( self::get( $term, $taxonomy ) )
			return TRUE;

		if ( TRUE === $sanitize )
			$slug = sanitize_title( $term );
		else if ( ! $sanitize )
			$slug = $term;
		else
			$slug = $sanitize;

		return wp_insert_term( $term, $taxonomy, [ 'slug' => $slug ] );
	}

	/**
	 * Retrieves a term object default properties.
	 *
	 * @return array
	 */
	public static function props()
	{
		return [
			'term_id'          => 0,
			'name'             => '',
			'slug'             => '',
			'term_group'       => '',
			'term_taxonomy_id' => 0,
			'taxonomy'         => '',
			'description'      => '',
			'parent'           => 0,
			'count'            => 0,
			'filter'           => 'raw',
		];
	}

	/**
	 * Retrieves term rest route given a term ID or term object.
	 *
	 * @param int|object $term_or_id
	 * @param string $taxonomy
	 * @return false|string
	 */
	public static function getRestRoute( $term_or_id, $taxonomy = '' )
	{
		if ( ! $term = self::get( $term_or_id, $taxonomy ) )
			return FALSE;

		if ( ! $object = Taxonomy::object( $term ) )
			return FALSE;

		if ( ! $object->show_in_rest )
			return FALSE;

		return sprintf( '/%s/%s/%d',
			$object->rest_namespace,
			$object->rest_base ?: $object->name,
			$term->term_id
		);
	}

	public static function image( $term, $context = NULL, $size = NULL, $thumbnail_id = NULL )
	{
		if ( ! $term = self::get( $term ) )
			return FALSE;

		$filtered = apply_filters( 'geditorial_term_image_pre_src', NULL, $term, $context, $size, $thumbnail_id );

		if ( ! is_null( $filtered ) )
			return $filtered;

		if ( is_null( $thumbnail_id ) )
			$thumbnail_id = Taxonomy::getThumbnailID( $term->term_id );

		if ( ! $thumbnail_id )
			return FALSE;

		if ( is_null( $size ) )
			$size = Media::getAttachmentImageDefaultSize( NULL, $term->taxonomy );

		if ( ! $image = image_downsize( $thumbnail_id, $size ) )
			return FALSE;

		if ( isset( $image[0] ) )
			return $image[0];

		return FALSE;
	}

	/**
	 * Performs term count update immediately.
	 *
	 * @param int|string $term_or_id
	 * @param string $taxonomy
	 * @return bool
	 */
	public static function updateCount( $term_or_id, $taxonomy )
	{
		if ( ! $term = self::get( $term_or_id, $taxonomy ) )
			return FALSE;

		return wp_update_term_count_now( [ $term->term_id ], $taxonomy );
	}
}

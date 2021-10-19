<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Taxonomy extends Core\Base
{

	public static function object( $taxonomy )
	{
		return is_object( $taxonomy ) ? $taxonomy : get_taxonomy( $taxonomy );
	}

	public static function getDefaultTermID( $taxonomy, $fallback = FALSE )
	{
		return get_option( self::getDefaultTermOptionKey( $taxonomy ), $fallback );
	}

	public static function getDefaultTermOptionKey( $taxonomy )
	{
		if ( 'category' == $taxonomy )
			return 'default_category'; // WordPress

		if ( 'product_cat' == $taxonomy )
			return 'default_product_cat'; // WooCommerce

		return 'default_term_'.$taxonomy;
	}

	// @REF: `get_the_term_list()`
	public static function getTheTermList( $taxonomy, $post = NULL, $before = '', $after = '' )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$terms = get_the_terms( $post, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) )
			return FALSE;

		$list = [];

		foreach ( $terms as $term )
			$list[] = Core\HTML::tag( 'a', [
				'href'  => get_term_link( $term, $taxonomy ),
				'class' => '-term',
			], sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy, 'display' ) );

		return apply_filters( 'term_links-'.$taxonomy, $list );
	}

	public static function getTermParents( $term_id, $taxonomy )
	{
		$parents = [];
		$up      = TRUE;

		while ( $up ) {

			$term = get_term( (int) $term_id, $taxonomy );

			if ( $term->parent )
				$parents[] = (int) $term->parent;

			else
				$up = FALSE;

			$term_id = $term->parent;
		}

		return count( $parents ) ? $parents : FALSE;
	}

	// TODO: must suport different parents
	public static function getTargetTerm( $target, $taxonomy, $args = [], $meta = [] )
	{
		$target = trim( $target );

		if ( is_numeric( $target ) ) {

			if ( $term = term_exists( (int) $target, $taxonomy ) )
				return get_term( $term['term_id'], $taxonomy );

			else
				return FALSE; // avoid inserting numbers as new terms!

		} else if ( $term = term_exists( $target, $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( apply_filters( 'string_format_i18n', $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( Core\Text::nameFamilyFirst( $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( Core\Text::nameFamilyLast( $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );
		}

		// avoid filtering the new term
		$term = wp_insert_term( $target, $taxonomy, $args );

		if ( self::isError( $term ) )
			return FALSE;

		foreach ( $meta as $meta_key => $meta_value )
			add_term_meta( $term['term_id'], $meta_key, $meta_value, TRUE );

		return get_term( $term['term_id'], $taxonomy );
	}

	public static function insertDefaultTerms( $taxonomy, $terms, $update_terms = TRUE )
	{
		if ( ! taxonomy_exists( $taxonomy ) )
			return FALSE;

		$count = [];

		foreach ( $terms as $slug => $term ) {

			$name   = $term;
			$meta   = array();
			$args   = array( 'slug' => $slug, 'name' => $term );
			$update = $update_terms;

			if ( is_array( $term ) ) {

				if ( ! empty( $term['name'] ) )
					$name = $args['name'] = $term['name'];
				else
					$name = $slug;

				if ( ! empty( $term['description'] ) )
					$args['description'] = $term['description'];

				if ( ! empty( $term['slug'] ) )
					$args['slug'] = $term['slug'];

				if ( ! empty( $term['parent'] ) ) {

					if ( is_numeric( $term['parent'] ) )
						$args['parent'] = $term['parent'];

					else if ( $parent = term_exists( $term['parent'], $taxonomy ) )
						$args['parent'] = $parent['term_id'];
				}

				if ( ! empty( $term['meta'] ) && is_array( $term['meta'] ) )
					foreach ( $term['meta'] as $term_meta_key => $term_meta_value )
						$meta[$term_meta_key] = $term_meta_value;

				if ( array_key_exists( 'update', $term ) )
					$update = $term['update'];
			}

			if ( $existed = term_exists( $slug, $taxonomy ) ) {

				if ( $update )
					wp_update_term( $existed['term_id'], $taxonomy, $args );

			} else {

				$existed = wp_insert_term( $name, $taxonomy, $args );
			}

			if ( ! is_wp_error( $existed ) ) {

				foreach ( $meta as $meta_key => $meta_value ) {

					if ( $update )
						update_term_meta( $existed['term_id'], $meta_key, $meta_value );
					else
						// will bail if an entry with the same key is found
						add_term_meta( $existed['term_id'], $meta_key, $meta_value, TRUE );
				}

				$count[] = $existed;
			}
		}

		return $count;
	}

	// @REF: https://developer.wordpress.org/?p=22286
	public static function listTerms( $taxonomy, $fields = NULL, $extra = array() )
	{
		$query = new \WP_Term_Query( array_merge( array(
			'taxonomy'   => (array) $taxonomy,
			'order'      => 'ASC',
			'orderby'    => 'meta_value_num', // 'name',
			'meta_query' => [
				// @REF: https://core.trac.wordpress.org/ticket/34996
				'relation' => 'OR',
				[
					'key'     => 'order',
					'compare' => 'NOT EXISTS'
				],
				[
					'key'     => 'order',
					'compare' => '>=',
					'value'   => 0,
				],
			],
			'fields'     => is_null( $fields ) ? 'id=>name' : $fields,
			'hide_empty' => FALSE,
		), $extra ) );

		if ( empty( $query->terms ) )
			return array();

		return $query->terms;
	}
}

<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\WordPress\Media as WPMedia;
use geminorum\gNetwork\WordPress\Taxonomy as WPTaxonomy;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally Based on: [Feed JSON](https://wordpress.org/plugins/feed-json/)
/// By wokamoto : http://twitter.com/wokamoto
/// Updated on: 20150918 / v1.0.9

/**
 * JSON Feed Template for displaying JSON Posts feed.
 */

if ( have_posts() ) {

	global $wp_query, $post;

	$query_array = $wp_query->query;

	// make sure query args are always in the same order
	ksort( $query_array );

	$json = [];

	while ( have_posts() ) {

		the_post();

		$single = [
			'id'        => $post->ID,
			'title'     => get_the_title(),
			'permalink' => get_permalink(),
			'shortlink' => wp_get_shortlink( 0, 'query' ),
			'content'   => get_the_content_feed( 'json' ),
			'excerpt'   => get_the_excerpt(),
			'date'      => get_the_date( 'Y-m-d H:i:s', '', '', FALSE ),
			'author'    => get_the_author(),
			'terms'     => [],
			'thumbnail' => WPMedia::prepAttachmentData( get_post_thumbnail_id( $post ) ),
		];

		if ( $tumbnail = get_the_post_thumbnail_url( $post->ID ) )
			$single['thumbnail'] = $tumbnail;

		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {

			if ( ! WPTaxonomy::viewable( $taxonomy ) )
				continue;

			$terms = get_the_terms( $post->ID, $taxonomy->name );
			$base  = $taxonomy->rest_base ?: $taxonomy->name;

			if ( $terms && ! is_wp_error( $terms ) ) {

				foreach ( $terms as $term ) {

					$single['terms'][$base][] = [
						'name' => sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy->name, 'js' ),
						'slug' => sanitize_term_field( 'slug', $term->slug, $term->term_id, $taxonomy->name, 'js' ),
						'link' => esc_url( get_term_link( $term ) ),
					];
				}
			}
		}

		if ( empty( $single['terms'] ) )
			unset( $single['terms'] );

		$json[] = $single;
	}

	$callback = trim( esc_html( get_query_var( 'callback' ) ) );
	$json     = wp_json_encode( $json, JSON_UNESCAPED_UNICODE );

	if ( ! empty( $callback ) ) {

		header( 'Content-Type: application/x-javascript; charset=utf-8' );
		echo "{$callback}({$json});";

	} else {

		header( 'Content-Type: application/json; charset=utf-8' );
		echo $json;
	}

} else {

	// TODO: use `_json_wp_die_handler()` since WP 5.2.0

	header( 'Content-Type: application/json; charset=utf-8' );
	status_header( 404 );
	nocache_headers();

	echo wp_json_encode( [
		'code'    => 404,
		'message' => 'Not Found',
	] );
}

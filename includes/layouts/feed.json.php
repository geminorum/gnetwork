<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally Based on: [Feed JSON](https://wordpress.org/plugins/feed-json/)
/// By wokamoto : http://twitter.com/wokamoto
/// Updated on: 20150918 / v1.0.9

/**
 * JSON Feed Template for displaying JSON Posts feed.
 */

$callback = trim( esc_html( get_query_var( 'callback' ) ) );
$charset  = get_option( 'charset' );

if ( have_posts() ) {

	global $wp_query, $post;

	$query_array = $wp_query->query;

	// make sure query args are always in the same order
	ksort( $query_array );

	$json = array();

	while ( have_posts() ) {

		the_post();

		$single = array(
			'id'        => $post->ID,
			'title'     => get_the_title(),
			'permalink' => get_permalink(),
			'content'   => get_the_content_feed( 'json' ),
			'excerpt'   => get_the_excerpt(),
			'date'      => get_the_date( 'Y-m-d H:i:s', '', '', FALSE ),
			'author'    => get_the_author(),
			'terms'     => array(),
		);

		if ( $tumbnail = get_the_post_thumbnail_url( $post->ID ) )
			$single['thumbnail'] = $tumbnail;

		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {

			if ( ! $taxonomy->public )
				continue;

			$terms = get_the_terms( $post->ID, $taxonomy->name );

			if ( $terms && ! is_wp_error( $terms ) ) {

				foreach ( $terms as $term ) {

					$name = sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy->name, 'display' );
					$url  = get_term_link( $term->slug, $taxonomy->name );

					$single['terms'][$taxonomy->label][$name] = esc_url( $url );
				}
			}
		}

		if ( ! count( $single['terms'] ) )
			unset( $single['terms'] );

		$json[] = $single;
	}

	$json = wp_json_encode( $json, JSON_UNESCAPED_UNICODE );

	nocache_headers();

	if ( ! empty( $callback ) ) {
		header( 'Content-Type: application/x-javascript; charset='.$charset );
		echo "{$callback}({$json});";

	} else {
		header( 'Content-Type: application/json; charset='.$charset );
		echo $json;
	}

} else {

	status_header( '404' );
	wp_die( '404 Not Found' );
}

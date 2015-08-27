<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkSearch extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	protected function setup_actions()
	{
		add_filter( 'posts_search', array( &$this, 'posts_search' ) );
		add_action( 'template_redirect', array( &$this, 'template_redirect_singlepost' ), 9 );

		// add_action( 'template_redirect', array( &$this, 'template_redirect_search_again' ), 999 );

		if ( constant( 'GNETWORK_SEARCH_REDIRECT' ) )
			add_action( 'template_redirect', array( &$this, 'template_redirect_search' ), 1 );
	}

	// http://www.wprecipes.com/how-to-redirect-to-post-if-search-results-only-returns-one-post
	public function template_redirect_singlepost()
	{
		if ( is_search() ) {
			global $wp_query;
			if ( $wp_query->post_count == 1 )
				wp_redirect( get_permalink( $wp_query->posts['0']->ID ) );
		}
	}

	// DEPRECATED : see the other method
	// https://gist.github.com/danielbachhuber/1760666
	// Include posts from authors in the search results where either their display name or user login matches the query string
	// @author danielbachhuber
	public function posts_search_OLD( $posts_search )
	{
		// Don't modify the query at all if we're not on the search template or if the LIKE is empty
		if ( ! is_search() || empty( $posts_search ) )
			return $posts_search;

		global $wpdb, $wp_query;

		// Get all of the users of the blog and see if the search query matches either the display name or the user login
		$all_users = get_users();
		$matching_users = array();
		foreach ( $all_users as $blog_user ) {
			if ( FALSE !== stripos( $blog_user->display_name, $wp_query->query_vars['s'] )
				|| FALSE !== stripos( $blog_user->user_login, $wp_query->query_vars['s'] ) )
					$matching_users[] = $blog_user->ID;
		}

		// Don't modify the query if there aren't any matching users
		if ( empty( $matching_users ) )
			return $posts_search;

		// Take a slightly different approach than core where we want all of the posts from these authors
		$posts_search = rtrim( $posts_search, ') ' );
		$posts_search .= ")) OR ( $wpdb->posts.post_author IN (".implode( ',', array_map( 'absint', $matching_users ) ).")";
		return $posts_search.'))';

	}

	// https://gist.github.com/danielbachhuber/7126249
	// Include posts from authors in the search results where either their display name or user login matches the query string
	// @author danielbachhuber
	public function posts_search( $posts_search )
	{
		if ( ! is_search() || empty( $posts_search ) )
			return $posts_search;

		global $wpdb;

		// get all of the users of the blog and see if the search query matches either the display name or the user login
		add_filter( 'pre_user_query', array( &$this, 'pre_user_query' ) );

		$matching_users = get_users( array(
			'count_total'   => FALSE,
			'search'        => sprintf( '*%s*', sanitize_text_field( get_query_var( 's' ) ) ),
			'fields'        => 'ID',
			'search_fields' => array(
				'display_name',
				'user_login',
			),
		) );

		remove_filter( 'pre_user_query', array( &$this, 'pre_user_query' ) );

		// don't modify the query if there aren't any matching users
		if ( empty( $matching_users ) )
			return $posts_search;

		// take a slightly different approach than core where we want all of the posts from these authors
		$posts_search = str_replace( ')))', ")) OR ( {$wpdb->posts}.post_author IN (" . implode( ',', array_map( 'absint', $matching_users ) ) . ")))", $posts_search );

		return $posts_search;
	}

	// Modify get_users() to search display_name instead of user_nicename
	public function pre_user_query( &$user_query )
	{
		if ( is_object( $user_query ) )
			$user_query->query_where = str_replace( "user_nicename LIKE", "display_name LIKE", $user_query->query_where );

		return $user_query;
	}

	// Replace your WordPress error pages with a search for the request URI
	// https://gist.github.com/chrisguitarguy/4477015
	public function template_redirect_search_again()
	{
		global $wp_the_query, $wp_query;

		if ( ! is_404() )
			return;

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? trim( $_SERVER['REQUEST_URI'], '/' ) : FALSE;
		if ( ! $uri ) // no request uri? okay.
			return;

		// destroy the query and replace it with our own.
		$wp_the_query = $wp_query = new WP_Query(array(
			'post_type' => apply_filters( 'error_search_post_types', 'any' ),
			's'         => str_replace( '/', ' ', $uri ),
		));
	}

	public function template_redirect_search()
	{
		global $wp_query;

		if ( $wp_query->is_search )
			self::redirect( add_query_arg( GNETWORK_SEARCH_QUERYID, $wp_query->query_vars['s'], GNETWORK_SEARCH_URL ) );
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkSearch extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;

	protected function setup_actions()
	{
		add_action( 'template_redirect', array( $this, 'template_redirect_singlepost' ), 9 );
		// add_action( 'template_redirect', array( $this, 'template_redirect_search_again' ), 999 );

		if ( constant( 'GNETWORK_SEARCH_REDIRECT' ) )
			add_action( 'template_redirect', array( $this, 'template_redirect_search' ), 1 );
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

	// replace error pages with a search for the request URI
	// https://gist.github.com/chrisguitarguy/4477015
	public function template_redirect_search_again()
	{
		global $wp_the_query, $wp_query;

		if ( ! is_404() )
			return;

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? trim( $_SERVER['REQUEST_URI'], '/' ) : FALSE;

		if ( ! $uri )
			return;

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

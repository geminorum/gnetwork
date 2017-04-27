<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork;
use geminorum\gNetwork\Core\WordPress;

class Search extends gNetwork\Module
{

	protected $key     = 'search';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'template_redirect', 1, 9, 'singlepost' );
		// $this->action( 'template_redirect', 1, 999, 'search_again' );

		if ( constant( 'GNETWORK_SEARCH_REDIRECT' ) )
			$this->action( 'template_redirect', 1, 1, 'search' );
	}

	public function template_redirect_singlepost()
	{
		if ( is_search() ) {

			global $wp_query;

			// redirect to post if search results only returns single post
			if ( $wp_query->post_count == 1 )
				wp_redirect( get_permalink( $wp_query->posts['0']->ID ) );

			add_action( 'wp_head', function(){
				// prevent search bots from indexing search results
				echo "\t".'<meta name="robots" content="noindex, nofollow" />'."\n";
			}, 1 );
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

		$wp_the_query = $wp_query = new \WP_Query( [
			'post_type' => $this->filters( 'error_post_types', 'any' ),
			's'         => str_replace( '/', ' ', $uri ),
		] );
	}

	public function template_redirect_search()
	{
		global $wp_query;

		if ( $wp_query->is_search )
			WordPress::redirect( add_query_arg( GNETWORK_SEARCH_QUERYID, $wp_query->query_vars['s'], GNETWORK_SEARCH_URL ) );
	}
}

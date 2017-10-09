<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Search extends gNetwork\Module
{

	protected $key     = 'search';
	protected $network = FALSE;

	private $query = NULL;

	protected function setup_actions()
	{
		if ( is_admin() )
			return;

		$this->action( 'template_redirect', 0, 1 );

		if ( $this->options['include_meta'] ) {
			$this->filter( 'posts_search', 2 );
			$this->filter( 'posts_join' );
			$this->filter( 'posts_request' );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Search', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'redirect_single'  => '1',
			'search_again_uri' => '0',
			'include_meta'     => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'redirect_single',
					'title'       => _x( 'Redirect Single Result', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Redirects to the post if search results only returns single post.', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'search_again_uri',
					'title'       => _x( 'Redirect 404 to URI', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Redirects 404 pages to a search for the request URI.', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'include_meta',
					'title'       => _x( 'Include Metadata', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Expands search results into post metadata.', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		$page = WordPress::getSearchLink();

		HTML::desc( sprintf( _x( 'Current Page: %s', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
			'<code>'.HTML::link( URL::relative( $page ), $page, TRUE ).'</code>' ) );
	}

	// @SOURCE: `se_search_where()`
	public function posts_search( $search, $wp_query )
	{
		if ( ! $wp_query->is_search() )
			return $search;

		$this->query = &$wp_query;

		$clause = $this->clause_default();
		$clause .= $this->clause_meta();

		return $clause ? " AND ( ( {$clause} ) ) " : $search;
	}

	public function posts_request( $query )
	{
		if ( ! empty( $this->query->query_vars['s'] )
			&& ! strstr( $query, 'DISTINCT' ) )
				return str_replace( 'SELECT', 'SELECT DISTINCT', $query );

		return $query;
	}

	// search for terms in default locations like title and content replacing
	// the old search terms seems to be the best way to avoid issue with
	// multiple terms
	// @SOURCE: `se_search_default()`
	private function clause_default()
	{
		global $wpdb;

		$clause = $sep = '';

		foreach ( $this->searched() as $searched ) {
			$escaped = $wpdb->prepare( '%s', empty( $this->query->query_vars['exact'] ) ? '%'.$searched.'%' : $searched );
			$clause .= $sep."( ( {$wpdb->posts}.post_title LIKE {$escaped} ) OR ( {$wpdb->posts}.post_content LIKE {$escaped} ) )";
			$sep = ' AND ';
		}

		return $clause ? "( {$clause} )" : '';
	}

	// creates the list of search keywords from the 's' parameters.
	// @SOURCE: `se_get_search_terms()`
	private function searched()
	{
		if ( empty( $this->query->query_vars['s'] ) )
			return [];

		// added slashes screw with quote grouping when done early, so done later
		$searched = stripslashes( $this->query->query_vars['s'] );

		if ( ! empty( $this->query->query_vars['sentence'] ) )
			return [ $searched ];

		preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $searched, $matches );

		return array_filter( array_map( function( $value ){
			return trim( $value, "\"\'\n\r " );
		}, $matches[0] ) );
	}

	// create the search meta data query
	// @SOURCE: `se_build_search_metadata()`
	private function clause_meta()
	{
		global $wpdb;

		$clause = $sep = '';

		foreach ( $this->searched() as $searched ) {
			$escaped = $wpdb->prepare( '%s', empty( $this->query->query_vars['exact'] ) ? '%'.$searched.'%' : $searched );
			$clause .= $sep."( m.meta_value LIKE {$escaped} )";
			$sep = ' AND ';
		}

		$sentence = $wpdb->prepare( '%s', $this->query->query_vars['s'] );

		if ( count( $searched ) > 1 && $searched[0] != $sentence )
			$clause = "( {$clause} ) OR ( m.meta_value LIKE {$sentence} )";

		if ( ! empty( $clause ) )
			$clause = " OR ( {$clause} ) ";

		return $clause;
	}

	public function posts_join( $where )
	{
		global $wpdb;

		if ( ! empty( $this->query->query_vars['s'] ) )
			$where .= " LEFT JOIN {$wpdb->postmeta} AS m ON ( {$wpdb->posts}.ID = m.post_id ) ";

		return $where;
	}

	public function template_redirect()
	{
		global $wp_the_query, $wp_query;

		if ( is_search() ) {

			if ( GNETWORK_SEARCH_REDIRECT )
				WordPress::redirect( add_query_arg( GNETWORK_SEARCH_QUERYID, $wp_query->query_vars['s'], GNETWORK_SEARCH_URL ) );

			//
			if ( $this->options['redirect_single'] && $wp_query->post_count == 1 )
				WordPress::redirect( get_permalink( $wp_query->posts['0']->ID ) );

			add_action( 'wp_head', function(){
				// prevent search bots from indexing search results
				echo "\t".'<meta name="robots" content="noindex, nofollow" />'."\n";
			}, 1 );

		} else if ( is_404() ) {

			// @SOURCE: https://gist.github.com/chrisguitarguy/4477015
			if ( $this->options['search_again_uri'] ) {

				$uri = isset( $_SERVER['REQUEST_URI'] ) ? trim( $_SERVER['REQUEST_URI'], '/' ) : FALSE;

				if ( ! $uri )
					return;

				$wp_the_query = $wp_query = new \WP_Query( [
					'post_type' => $this->filters( '404_posttypes', 'any' ),
					's'         => str_replace( '/', ' ', $uri ),
				] );
			}
		}
	}
}

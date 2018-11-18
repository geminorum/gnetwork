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
		if ( $this->options['register_shortcodes'] )
			$this->action( 'init', 0, 12 );

		if ( is_admin() )
			return;

		$this->action( 'template_redirect', 0, 1 );

		if ( $this->options['include_meta'] ) {
			$this->filter( 'posts_search', 2 );
			$this->filter( 'posts_join' );
			$this->filter( 'posts_request' );
		}

		$this->filter( 'get_search_form' );
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
			'redirect_single'     => '1',
			'include_meta'        => '0',
			'register_shortcodes' => '0',
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
					'field'       => 'include_meta',
					'title'       => _x( 'Include Metadata', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Expands search results into post metadata.', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
				],
				'register_shortcodes',
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		$page = WordPress::getSearchLink();

		HTML::desc( sprintf( _x( 'Current Page: %s', 'Modules: Search: Settings', GNETWORK_TEXTDOMAIN ),
			'<code>'.HTML::link( URL::relative( $page ), $page, TRUE ).'</code>' ) );
	}

	public function init()
	{
		if ( $this->options['register_shortcodes'] )
			$this->shortcodes( $this->get_shortcodes() );
	}

	protected function get_shortcodes()
	{
		return [
			'search-form' => 'shortcode_search_form',
		];
	}

	// @SOURCE: `se_search_where()`
	public function posts_search( $search, $wp_query )
	{
		if ( ! $wp_query->is_search() )
			return $search;

		$this->query = &$wp_query;

		$clause = $this->clause_default();
		$clause.= $this->clause_meta();

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
			$clause.= $sep."( ( {$wpdb->posts}.post_title LIKE {$escaped} ) OR ( {$wpdb->posts}.post_content LIKE {$escaped} ) )";
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

		$searched = $this->searched();
		$clause   = $sep = '';

		foreach ( $searched as $term ) {
			$escaped = $wpdb->prepare( '%s', empty( $this->query->query_vars['exact'] ) ? '%'.$term.'%' : $term );
			$clause.= $sep."( m.meta_value LIKE {$escaped} )";
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
			$where.= " LEFT JOIN {$wpdb->postmeta} AS m ON ( {$wpdb->posts}.ID = m.post_id ) ";

		return $where;
	}

	public function template_redirect()
	{
		global $wp_the_query, $wp_query;

		if ( is_search() ) {

			if ( GNETWORK_SEARCH_REDIRECT )
				WordPress::redirect( add_query_arg( GNETWORK_SEARCH_QUERYID, $wp_query->query_vars['s'], GNETWORK_SEARCH_URL ) );

			if ( $this->options['redirect_single'] && $wp_query->post_count == 1 )
				WordPress::redirect( get_permalink( $wp_query->posts['0']->ID ) );

			add_action( 'wp_head', function(){
				// prevent search bots from indexing search results
				echo "\t".'<meta name="robots" content="noindex, nofollow" />'."\n";
			}, 1 );
		}
	}

	public function get_search_form( $form )
	{
		// bail if theme has template
		if ( locate_template( 'searchform.php' ) )
			return $form;

		return $this->search_form();
	}

	// also overrided for strings!
	public function search_form()
	{
		$html = '<form role="search" method="get" class="search-form" action="'.GNETWORK_SEARCH_URL.'">';
			$html.= '<label><span class="screen-reader-text">'._x( 'Search for:', 'Modules: Search: Form: Label', GNETWORK_TEXTDOMAIN ).'</span>';
			$html.= '<input type="search" class="search-field" placeholder="'.esc_attr_x( 'Search &hellip;', 'Modules: Search: Form: Placeholder', GNETWORK_TEXTDOMAIN );
			$html.= '" value="'.get_search_query().'" name="'.GNETWORK_SEARCH_QUERYID.'" />';
			$html.= '</label><input type="submit" class="search-submit" value="'.esc_attr_x( 'Search', 'Modules: Search: Form: Submit Button', GNETWORK_TEXTDOMAIN ).'" />';
		$html.= '</form>';

		return $html;
	}

	public function shortcode_search_form( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'theme'   => FALSE,
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( $args['theme'] )
			$html = get_search_form( FALSE );
		else
			$html = $this->search_form();

		return self::shortcodeWrap( $html, 'search-form', $args );
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Search extends gNetwork\Module
{

	protected $key     = 'search';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( $this->options['register_shortcodes'] )
			$this->action( 'init', 0, 12 );

		if ( is_admin() )
			return;

		$this->action( 'template_redirect', 0, 1 );

		if ( 'include_meta' == $this->options['search_context'] ) {

			$this->filter( 'posts_search', 2, 99, 'include_meta' );
			$this->filter( 'posts_join', 2, 99, 'include_meta' );
			$this->filter( 'posts_request', 2, 99, 'include_meta' );

		} else if ( 'include_terms' == $this->options['search_context'] ) {

			if ( count( $this->options['include_taxonomies'] ) ) {
				$this->filter( 'posts_join', 2, 99, 'include_terms' );
				$this->filter( 'posts_where', 2, 99, 'include_terms' );
				$this->filter( 'posts_groupby', 2, 99, 'include_terms' );
			}

		} else if ( 'titles_only' == $this->options['search_context'] ) {

			$this->filter( 'posts_search', 2, 99, 'titles_only' );
		}

		if ( '-' !== $this->options['exclusion_prefix'] )
			$this->filter( 'wp_query_search_exclusion_prefix' );

		if ( ! empty( $this->options['search_columns'] ) )
			$this->filter( 'post_search_columns', 3, 9 );

		$this->filter( 'get_search_form' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Search', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'search_columns'      => [],
			'search_context'      => 'default',
			'include_taxonomies'  => [],
			'redirect_single'     => '1',
			'linkify_hashtags'    => '0',
			'register_shortcodes' => '0',
			'exclusion_prefix'    => '-',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'search_columns',
					'type'        => 'checkboxes-values',
					'title'       => _x( 'Search Columns', 'Modules: Search: Settings', 'gnetwork' ),
					'description' => _x( 'Controls which fields are searched in a search query. Select none for default.', 'Modules: Search: Settings', 'gnetwork' ),
					'values'      => [
						'post_title'   => _x( 'Post Title', 'Modules: Search: Settings', 'gnetwork' ),
						'post_excerpt' => _x( 'Post Excerpt', 'Modules: Search: Settings', 'gnetwork' ),
						'post_content' => _x( 'Post Content', 'Modules: Search: Settings', 'gnetwork' ),
					],
				],
				[
					'field'   => 'search_context',
					'type'    => 'radio',
					'title'   => _x( 'Search Context', 'Modules: Search: Settings', 'gnetwork' ),
					'default' => 'default',
					'values'  => [
						'default'       => _x( 'WordPress Default &ndash; Does not alter core search.', 'Modules: Search: Settings', 'gnetwork' ),
						'titles_only'   => _x( 'Titles Only &ndash; Limits search to post titles only.', 'Modules: Search: Settings', 'gnetwork' ),
						'include_meta'  => _x( 'Include Metadata &ndash; Expands search results into post metadata.', 'Modules: Search: Settings', 'gnetwork' ),
						'include_terms' => _x( 'Include Terms &ndash; Expands search results into terms of selected taxonomies.', 'Modules: Search: Settings', 'gnetwork' ),
					],
				],
				[
					'field'       => 'include_taxonomies',
					'type'        => 'taxonomies',
					'title'       => _x( 'Included Taxonomies', 'Modules: Search: Settings', 'gnetwork' ),
					'description' => _x( 'Terms from selected taxonomies will be included on search context.', 'Modules: Search: Settings', 'gnetwork' ),
					'extra'       => [ 'public' => TRUE ],
				],
				[
					'field'       => 'exclusion_prefix',
					'type'        => 'radio',
					'title'       => _x( 'Exclusion Prefix', 'Modules: Search: Settings', 'gnetwork' ),
					'description' => _x( 'Filters the prefix that indicates that a search term should be excluded from results.', 'Modules: Search: Settings', 'gnetwork' ),
					'default'     => '-',
					'values'      => [
						/* translators: %s: prefix char */
						'-' => sprintf( _x( '%s Hyphen &ndash; Default WordPress character to exclude terms.', 'Modules: Search: Settings', 'gnetwork' ), HTML::tag( 'code', '-' ) ),
						/* translators: %s: prefix char */
						'!' => sprintf( _x( '%s Exclamation Mark &ndash; Using exclamation as exclude prfix.', 'Modules: Search: Settings', 'gnetwork' ), HTML::tag( 'code', '!' ) ),
						'0' => _x( 'Disable Exclusion &ndash; Ignores exclude prefixes all together.', 'Modules: Search: Settings', 'gnetwork' ),
					],
				],
				[
					'field'       => 'redirect_single',
					'title'       => _x( 'Redirect Single Result', 'Modules: Search: Settings', 'gnetwork' ),
					'description' => _x( 'Redirects to the post if search results only returns single post.', 'Modules: Search: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'linkify_hashtags',
					'title'       => _x( 'Linkify Hash-tags', 'Modules: Search: Settings', 'gnetwork' ),
					'description' => _x( 'Tries to linkify hash-tags on the content. Must enable &ldquo;Linkify Content&rdquo; setting on Typography Module.', 'Modules: Search: Settings', 'gnetwork' ),
				],
				'register_shortcodes',
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		$page = WordPress::getSearchLink();

		/* translators: %s: search page path */
		HTML::desc( sprintf( _x( 'Current Page: %s', 'Modules: Search: Settings', 'gnetwork' ),
			HTML::tag( 'code', HTML::link( URL::relative( $page ), $page, TRUE ) ) ) );
	}

	public function init()
	{
		$this->register_shortcodes();
	}

	protected function get_shortcodes()
	{
		return [
			'search-form' => 'shortcode_search_form',
		];
	}

	// @SOURCE: `se_search_where()`
	public function posts_search_include_meta( $search, $wp_query )
	{
		if ( ! $wp_query->is_search() )
			return $search;

		$clause = $this->clause_default( $wp_query );
		$clause.= $this->clause_meta( $wp_query );

		return $clause ? " AND ( ( {$clause} ) ) " : $search;
	}

	public function posts_request_include_meta( $request, $wp_query )
	{
		if ( ! empty( $wp_query->query_vars['s'] ) && ! strstr( $request, 'DISTINCT' ) )
			return str_replace( 'SELECT', 'SELECT DISTINCT', $request );

		return $request;
	}

	// search for terms in default locations like title and content replacing
	// the old search terms seems to be the best way to avoid issue with
	// multiple terms
	// @SOURCE: `se_search_default()`
	private function clause_default( $wp_query )
	{
		global $wpdb;

		$clause = $sep = '';

		foreach ( $this->searched( $wp_query ) as $searched ) {
			$escaped = $wpdb->prepare( '%s', empty( $wp_query->query_vars['exact'] ) ? '%'.$searched.'%' : $searched );
			$clause.= $sep."( ( {$wpdb->posts}.post_title LIKE {$escaped} ) OR ( {$wpdb->posts}.post_content LIKE {$escaped} ) )";
			$sep = ' AND ';
		}

		return $clause ? "( {$clause} )" : '';
	}

	// creates the list of search keywords from the 's' parameters.
	// @SOURCE: `se_get_search_terms()`
	private function searched( $wp_query )
	{
		if ( empty( $wp_query->query_vars['s'] ) )
			return [];

		// added slashes screw with quote grouping when done early, so done later
		$searched = stripslashes( $wp_query->query_vars['s'] );

		if ( ! empty( $wp_query->query_vars['sentence'] ) )
			return [ $searched ];

		// preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $searched, $matches );
		preg_match_all( '/(".*?)("|$)|((?<=[\s",+])|^)[^\s",+]+/', $searched, $matches );

		return array_filter( array_map( function ( $value ) {
			return trim( $value, "\"\'\n\r " );
		}, $matches[0] ) );
	}

	// create the search meta data query
	// @SOURCE: `se_build_search_metadata()`
	private function clause_meta( $wp_query )
	{
		global $wpdb;

		$searched = $this->searched( $wp_query );
		$clause   = $sep = '';

		foreach ( $searched as $term ) {
			$escaped = $wpdb->prepare( '%s', empty( $wp_query->query_vars['exact'] ) ? '%'.$term.'%' : $term );
			$clause.= $sep."( m.meta_value LIKE {$escaped} )";
			$sep = ' AND ';
		}

		$sentence = $wpdb->prepare( '%s', $wp_query->query_vars['s'] );

		if ( count( $searched ) > 1 && $searched[0] != $sentence )
			$clause = "( {$clause} ) OR ( m.meta_value LIKE {$sentence} )";

		if ( ! empty( $clause ) ) {
			$clause = " OR ( {$clause} ) ";

			if ( ! is_user_logged_in() )
				$clause.= " AND ( {$wpdb->posts}.post_password = '' ) ";
		}

		return $clause;
	}

	public function posts_join_include_meta( $join, $wp_query )
	{
		global $wpdb;

		if ( $wp_query->is_search() )
			$join.= " LEFT JOIN {$wpdb->postmeta} AS m ON ( {$wpdb->posts}.ID = m.post_id ) ";

		return $join;
	}

	// @REF: https://stackoverflow.com/a/13493126
	public function posts_join_include_terms( $join, $wp_query )
	{
		global $wpdb;

		if ( $wp_query->is_search() ) {
			$join.= " INNER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id ";
			$join.= " INNER JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id ";
			$join.= " INNER JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id ";
		}

		return $join;
	}

	public function posts_where_include_terms( $where, $wp_query )
	{
		global $wpdb;

		if ( $wp_query->is_search() ) {

			foreach ( $this->options['include_taxonomies'] as $taxonomy ) {

				$taxonomy = $wpdb->prepare( '%s', $taxonomy );
				$clause   = $sep = '';

				foreach ( $this->searched( $wp_query ) as $searched ) {
					$escaped = $wpdb->prepare( '%s', empty( $wp_query->query_vars['exact'] ) ? '%'.$searched.'%' : $searched );
					$clause.= $sep."( ( {$wpdb->term_taxonomy}.taxonomy LIKE {$taxonomy} ) AND ( {$wpdb->terms}.name LIKE {$escaped} ) ) ";
					$sep = ' AND ';
				}

				if ( ! empty( $clause ) )
					$where.= " OR ( {$clause} ) ";
			}
		}

		return $where;
	}

	// @REF: https://wordpress.stackexchange.com/a/5404
	public function posts_groupby_include_terms( $groupby, $wp_query )
	{
		global $wpdb;

		$bypostid = "{$wpdb->posts}.ID";

		if ( ! $wp_query->is_search() || Text::has( $groupby, $bypostid ) )
			return $groupby;

		return empty( trim( $groupby ) )
			? $bypostid
			: $groupby.', '.$bypostid;
	}

	// @REF: https://nathaningram.com/restricting-wordpress-search-to-titles-only/
	public function posts_search_titles_only( $search, $wp_query )
	{
		if ( empty( $search ) || ! $wp_query->is_search() )
			return $search;

		global $wpdb;

		$searched = $this->searched( $wp_query );
		$clause   = $sep = '';

		foreach ( $searched as $term ) {
			$escaped = $wpdb->prepare( '%s', empty( $wp_query->query_vars['exact'] ) ? '%'.$term.'%' : $term );
			$clause.= $sep."( {$wpdb->posts}.post_title LIKE {$escaped} )";
			$sep = ' AND ';
		}

		if ( ! empty( $clause ) ) {
			$clause = " AND ( {$clause} ) ";

			if ( ! is_user_logged_in() )
				$clause.= " AND ( {$wpdb->posts}.post_password = '' ) ";
		}

		return $clause;
	}

	public function template_redirect()
	{
		global $wp_query;

		if ( ! is_search() )
			return;

		if ( GNETWORK_SEARCH_LOG && ( $query = get_query_var( 's' ) ) )
			Logger::siteSearch( 'QUERY', sprintf( '%s -- %s', $query, ( isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : 'NO-REFERER' ) ) ); // TODO: must decode referrer

		if ( GNETWORK_SEARCH_REDIRECT )
			WordPress::redirect( add_query_arg( GNETWORK_SEARCH_QUERYID, $wp_query->query_vars['s'], GNETWORK_SEARCH_URL ) );

		if ( $this->options['redirect_single'] && $wp_query->post_count == 1 && ! is_paged() )
			WordPress::redirect( get_permalink( $wp_query->posts['0']->ID ) );

		// no-robots added @since WP 5.7
		// $this->action( 'wp_head', 0, 8 );
	}

	public function wp_head()
	{
		// prevent search bots from indexing search results
		echo '<meta name="robots" content="noindex, noarchive" />'."\n";
	}

	public function wp_query_search_exclusion_prefix( $prefix )
	{
		return $this->options['exclusion_prefix'] ?: FALSE;
	}

	// @SEE: https://core.trac.wordpress.org/ticket/43867
	// The supported columns are `post_title`, `post_excerpt` and `post_content`.
	public function post_search_columns( $search_columns, $search, $query )
	{
		return $this->options['search_columns'];
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
		$html = '<form role="search" method="get" class="search-form" action="'.esc_url( GNETWORK_SEARCH_URL ).'">';
			// TODO: do action: `search_form_before`
			$html.= '<label><span class="screen-reader-text">'._x( 'Search for:', 'Modules: Search: Form: Label', 'gnetwork' ).'</span>';
			$html.= '<input type="search" class="search-field" placeholder="'.esc_attr_x( 'Search &hellip;', 'Modules: Search: Form: Placeholder', 'gnetwork' );
			$html.= '" value="'.get_search_query().'" name="'.GNETWORK_SEARCH_QUERYID.'" />';
			$html.= '</label><input type="submit" class="search-submit" value="'.esc_attr_x( 'Search', 'Modules: Search: Form: Submit Button', 'gnetwork' ).'" />';
			// TODO: do action: `search_form_after`
		$html.= '</form>';

		return $html;
	}

	public function shortcode_search_form( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'theme'   => FALSE,
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] || WordPress::isXML() || WordPress::isREST() )
			return NULL;

		$html = $args['theme']
			? get_search_form( [ 'echo' => FALSE ] )
			: $this->search_form();

		return self::shortcodeWrap( $html, 'search-form', $args );
	}
}

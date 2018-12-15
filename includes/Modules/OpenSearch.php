<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\Date;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class OpenSearch extends gNetwork\Module
{
	protected $key     = 'opensearch';
	protected $network = FALSE;

	private $ajax_action = 'opensearch_suggestions';

	protected function setup_actions()
	{
		if ( ! $this->options['opensearch'] )
			return;

		if ( ! constant( 'GNETWORK_SEARCH_REDIRECT' ) ) {
			$this->action( 'rss2_ns' );
			add_action( 'atom_ns', [ $this, 'rss2_ns' ] );
			$this->action( 'rss2_head' );
			add_action( 'atom_head', [ $this, 'do_link_tag' ] );
		}

		// $this->action( 'rewrite_rules_array', 1, 8 );

		if ( ! is_admin() ) {
			$this->action( 'parse_request', 1, 1 );
			$this->filter( 'redirect_canonical', 2 );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Open Search', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	protected function setup_ajax( $request )
	{
		// if ( $this->options['suggestions'] )
		// 	$this->_hook_ajax( TRUE, 'opensearch_suggestions' );
	}

	public function default_options()
	{
		return [
			'opensearch'  => '0',
			'suggestions' => '0',
			'shortname'   => '',
			'longname'    => '',
			'description' => '',
			'contact'     => '',
			'tags'        => '',
			'attribution' => '',
			'syndication' => 'open',
		];
	}

	public function default_settings()
	{
		$name = get_bloginfo( 'name', 'display' );

		return [
			'_general' => [
				[
					'field'       => 'opensearch',
					'type'        => 'enabled',
					'title'       => _x( 'Open Search', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'OpenSearch support for this blog.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				],
				[
					'field'       => 'suggestions',
					'type'        => 'enabled',
					'title'       => _x( 'Suggestions', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'OpenSearch Suggestions support for this site.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				],
				[
					'field'       => 'shortname',
					'type'        => 'text',
					'title'       => _x( 'ShortName', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A short name for the search engine. <b>16</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => $name,
					'field_class' => 'medium-text',
				],
				[
					'field'       => 'longname',
					'type'        => 'text',
					'title'       => _x( 'LongName', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'An extended name for the search engine. <b>48</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'description',
					'type'        => 'textarea',
					'title'       => _x( 'Description', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A brief description of the search engine. <b>1024</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => sprintf( _x( 'Search %s', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ), $name ),
				],
				[
					'field'       => 'attribution',
					'type'        => 'text',
					'title'       => _x( 'Attribution', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A list of all sources or entities that should be credited. <b>256</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => sprintf( _x( 'Search data copyright %s', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ), $name ),
					'field_class' => 'large-text',
				],
				[
					'field'       => 'syndication',
					'type'        => 'select',
					'title'       => _x( 'Syndication Right', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Indicates the degree to which the search results provided.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'open',
					'values'      => [
						'open'    => _x( 'Open', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
						'limited' => _x( 'Limited', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
						'private' => _x( 'Private', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
						'closed'  => _x( 'Closed', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					],
				],
				[
					'field'       => 'contact',
					'type'        => 'email',
					'title'       => _x( 'Contact', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'An email address at which the maintainer of the search engine can be reached.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => get_network_option( NULL, 'admin_email' ),
				],
				[
					'field'       => 'tags',
					'type'        => 'text',
					'title'       => _x( 'Tags', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A set of words that are used as keywords to identify and categorize this search content. Single words and are delimited by space. <b>256</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text',
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( $this->options['opensearch'] ) {

			$manifest = self::url( FALSE );

			HTML::desc( sprintf( _x( 'Current Manifest: %s', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ),
				'<code>'.HTML::link( URL::relative( $manifest ), $manifest ).'</code>' ) );
		} else {

			HTML::desc( _x( 'There are no manifest available.', 'Modules: OpenSearch: Settings', GNETWORK_TEXTDOMAIN ) );
		}
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [
			 [
				'id'      => $this->classs( 'help' ),
				'title'   => _x( 'Open Search', 'Modules: OpenSearch: Help Tab Title', GNETWORK_TEXTDOMAIN ),
				'content' => '<p>OpenSearch is a collection of simple formats for the sharing of search results.</p>
					<p>This blog\'s OpenSearch description file is:<br /><a href="'.self::url().'" target="_blank">'.self::url().'</a></p>
				<p>Fore more information:<br />
					<a href="https://developer.mozilla.org/en-US/Add-ons/Creating_OpenSearch_plugins_for_Firefox" target="_blank">Creating OpenSearch plugins for Firefox</a><br />
					<a href="https://developer.mozilla.org/en-US/docs/Adding_search_engines_from_web_pages" target="_blank">Adding search engines from web pages</a><br />
					<a href="http://opensearch.org" target="_blank">OpenSearch.org</a><br />
				</p>',
			],
		];
	}

	public function do_link_tag()
	{
		echo '<link rel="search" type="application/opensearchdescription+xml" href="'
			.self::url().'" title="'.$this->options['shortname'].'" />'."\n";
	}

	public function rss2_ns()
	{
		if ( defined( 'GNETWORK_IS_WP_EXPORT' )
			&& GNETWORK_IS_WP_EXPORT )
				return;

		echo 'xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/"'."\n";
	}

	public function rss2_head()
	{
		if ( defined( 'GNETWORK_IS_WP_EXPORT' )
			&& GNETWORK_IS_WP_EXPORT )
				return;

		echo "\t".'<atom:link rel="search" type="application/opensearchdescription+xml" href="'
			.self::url().'" title="'.$this->options['shortname'].'" />'."\n";
	}

	// TODO: make suggestions an AJAX call
	public function parse_request( $request )
	{
		if ( 'osd.xml' == $request->request )
			$this->xml();

		else if ( 'oss.json' == $request->request )
			$this->suggestions();
	}

	// DISABLED
	public function rewrite_rules_array( $rules )
	{
		return array_merge( [
			'osd\.xml$'  => 'index.php?opensearch=1',
			'oss\.json$' => 'index.php?opensearch_suggestions=1',
			// 'oss\.json$' => 'wp-admin/admin-ajax.php?action=opensearch_suggestions', // NOT WORKING
		], $rules );
	}

	public function redirect_canonical( $redirect_url, $requested_url )
	{
		if ( 'osd.xml' == substr( $requested_url, -7 ) )
			return FALSE;

		else if ( 'oss.json' == substr( $requested_url, -8 ) )
			return FALSE;

		return $redirect_url;
	}

	// https://developer.mozilla.org/en-US/docs/Adding_search_engines_from_web_pages
	// http://eoinoc.net/create-a-custom-search-engine-for-firefox-ie-chrome/
	public static function link( $text = NULL, $title = NULL, $link = NULL, $before = '', $after = '' )
	{
		if ( is_null( $link ) )
			$link = GNETWORK_SEARCH_REDIRECT ? GNETWORK_SEARCH_URL : '#';

		if ( is_null( $text ) )
			$text = _x( 'Search Engine', 'Modules: OpenSearch', GNETWORK_TEXTDOMAIN );

		if ( is_null( $title ) )
			$title = _x( 'Add this site search engine plugin to your browser.', 'Modules: OpenSearch', GNETWORK_TEXTDOMAIN );

		$script = "function AddSearchEngine(){
			if(window.external && ('AddSearchProvider' in window.external)){window.external.AddSearchProvider('".self::url()."');
			}else{alert('"._x( 'Your browser does not support the AddSearchProvider method!', 'Modules: OpenSearch', GNETWORK_TEXTDOMAIN )."');
			};return false;}";

		echo '<script type="text/javascript">'.$script.'</script>';

		echo $before.HTML::tag( 'a', [
			'href'    => $link,
			'title'   => $title,
			'onclick' => 'return AddSearchEngine()',
		], $text ).$after;
	}

	public static function url( $escape = TRUE )
	{
		$url = get_bloginfo( 'url', 'display' ).'/osd.xml';
		return $escape ? esc_url( $url ) : $url;
	}

	// http://www.opensearch.org/Specifications/OpenSearch/1.1
	// https://developer.mozilla.org/en-US/Add-ons/Creating_OpenSearch_plugins_for_Firefox
	private function xml()
	{
		// _donot_cache_page();

		$url = add_query_arg( GNETWORK_SEARCH_QUERYID, '{searchTerms}', GNETWORK_SEARCH_URL );

		$xml = "\t".HTML::tag( 'ShortName', trim( $this->options['shortname'] ) )."\n";

		if ( $this->options['longname'] )
			$xml.= "\t".HTML::tag( 'LongName', $this->options['longname'] )."\n";

		$xml.= "\t".HTML::tag( 'Description', $this->options['description'] )."\n";
		$xml.= "\t".HTML::tag( 'InputEncoding', get_bloginfo( 'charset' ) )."\n";
		$xml.= "\t".HTML::tag( 'OutputEncoding', get_bloginfo( 'charset' ) )."\n";
		$xml.= "\t".HTML::tag( 'Language', get_bloginfo( 'language' ) )."\n";

		if ( GNETWORK_SEARCH_REDIRECT ) {

			$xml.= "\t".HTML::tag( 'moz:SearchForm', GNETWORK_SEARCH_URL )."\n";

		} else {

			// TODO: generate customized atom/rss results
			// SEE: gNetwork json feed
			// SEE: https://www.drupal.org/project/opensearch
			// ALSO : http://www.opensearch.org/Documentation/Developer_how_to_guide#How_to_indicate_errors

			$xml.= "\t".HTML::tag( 'Url', [
				'type'     => 'application/atom+xml',
				'template' => add_query_arg( [
					'feed'                  => 'atom',
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
				], GNETWORK_SEARCH_URL ),
			] )."\n";

			$xml.= "\t".HTML::tag( 'Url', [
				'type'     => 'application/rss+xml',
				'template' => add_query_arg( [
					'feed'                  => 'rss2',
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
				], GNETWORK_SEARCH_URL ),
			] )."\n";

			if ( $this->options['suggestions'] ) {
				$xml.= "\t".HTML::tag( 'Url', [
					// 'type'     => 'application/json',
					'type'     => 'application/x-suggestions+json',
					// 'type'     => 'application/x-moz-keywordsearch',
					// 'method'   => 'get',
					// 'rel'      => 'suggestions',
					'template' => add_query_arg( 'query', '{searchTerms}', get_bloginfo( 'url', 'display' ).'/oss.json' ),
				] )."\n";

				$url = add_query_arg( [
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
					'prefix'                => '{suggestions:suggestionPrefix?}',
					'index'                 => '{suggestions:suggestionIndex?}',
				], GNETWORK_SEARCH_URL );
			}
		}

		// TODO: add more query strings
		// LIKE: /?s={searchTerms}&itemstart={startIndex}&itempage={startPage}&itemlimit={count}

		$xml.= "\t".HTML::tag( 'Url', [
			'type'     => 'text/html',
			'method'   => 'get',
			'template' => $url,
		] )."\n";

		$xml.= "\t".HTML::tag( 'Url', [
			'type'     => 'application/opensearchdescription+xml',
			'rel'      => 'self',
			'template' => self::url(),
		] )."\n";

		if ( file_exists( ABSPATH.'favicon.ico' ) )
			$xml.= "\t".HTML::tag( 'Image', [
				'type'   => 'image/x-icon',
				'width'  => '16',
				'height' => '16',
			], get_bloginfo( 'url' ).'/favicon.ico' )."\n";

		if ( file_exists( ABSPATH.'favicon.png' ) )
			$xml.= "\t".HTML::tag( 'Image', [
				'type'   => 'image/png',
				'width'  => '64',
				'height' => '64',
			], get_bloginfo( 'url' ).'/favicon.png' )."\n";

		if ( $this->options['contact'] )
			$xml.= "\t".HTML::tag( 'Contact', $this->options['contact'] )."\n";

		if ( $this->options['tags'] )
			$xml.= "\t".HTML::tag( 'Tags', $this->options['tags'] )."\n";

		if ( $this->options['attribution'] )
			$xml.= "\t".HTML::tag( 'Attribution', $this->options['attribution'] )."\n";

		$xml.= "\t".HTML::tag( 'SyndicationRight', $this->options['syndication'] )."\n";

		$xml.= "\t".'<Query role="example" searchTerms="tag" />'."\n";
		$xml.= "\t".'<AdultContent>false</AdultContent>';

		// header( 'Content-Type: text/xml; charset=utf-8' );
		header( 'Content-Type: application/opensearchdescription+xml; charset=utf-8' );
		header( 'Expires: '.gmdate( 'D, d M Y H:i:s', time() + Date::MONTH_IN_SECONDS ).' GMT' );
		header( 'Cache-Control: max-age='.Date::MONTH_IN_SECONDS.', must-revalidate' );

		echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		echo HTML::tag( 'OpenSearchDescription', [
			'xmlns'             => 'http://a9.com/-/spec/opensearch/1.1/',
			'xmlns:moz'         => 'http://www.mozilla.org/2006/browser/search/',
			'xmlns:suggestions' => $this->options['suggestions'] ? 'http://www.opensearch.org/specifications/opensearch/extensions/suggestions/1.1' : FALSE,
		], "\n".$xml."\n" );

		exit();
	}

	// FIXME: WORKING BUT: firefox and chrome will try to get it but wont display suggestions!
	// https://wiki.mozilla.org/Search_Service/Suggestions
	// https://developer.mozilla.org/en-US/docs/Supporting_search_suggestions_in_search_plugins
	// http://www.opensearch.org/Specifications/OpenSearch/Extensions/Suggestions
	private function suggestions()
	{
		$completions = $descriptions = $query_urls = [];

		if ( isset( $_REQUEST['query'] ) )
			$query_string = $_REQUEST['query'];

		else if ( isset( $_REQUEST[GNETWORK_SEARCH_QUERYID] ) )
			$query_string = $_REQUEST[GNETWORK_SEARCH_QUERYID];

		else if ( isset( $_REQUEST['s'] ) )
			$query_string = $_REQUEST['s'];

		else if ( isset( $_REQUEST['q'] ) )
			$query_string = $_REQUEST['q'];

		else if ( isset( $_REQUEST['search'] ) )
			$query_string = $_REQUEST['search'];

		else
			$query_string = '';

		if ( $query_string ) {

			// TODO: make this query as light as possible
			// SEE: http://code.tutsplus.com/series/mastering-wp_query--cms-818

			$the_query = new \WP_Query( [
				's'                      => $query_string,
				'post_status'            => 'publish',
				'post_type'              => 'any',
				'posts_per_page'         => 10,
				'suppress_filters'       => TRUE,
				'cache_results'          => FALSE,
				'no_found_rows'          => TRUE, // counts posts, remove if pagination required
				'update_post_term_cache' => FALSE, // grabs terms
				'update_post_meta_cache' => FALSE, // grabs post meta
			] );

			if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();

					$title = strip_tags( str_replace( '&nbsp;', ' ', get_the_title() ) );
					$completions[] = $title;
					$descriptions[] = '';
					$query_urls[] = WordPress::getSearchLink( $title );
				}
			}
		}

		wp_send_json( [
			$query_string,
			$completions,
			$descriptions,
			$query_urls,
		] );
	}

	// FIXME: UNFINISHED
	// DISABLED
	public function ajax()
	{
		if ( WP_DEBUG_LOG )
			error_log( print_r( $_REQUEST, TRUE ) );

		return;
		$post = self::unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';
	}
}

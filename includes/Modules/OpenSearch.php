<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

class OpenSearch extends gNetwork\Module
{
	protected $key     = 'opensearch';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( ! $this->options['opensearch'] )
			return;

		if ( ! self::const( 'GNETWORK_SEARCH_REDIRECT' ) ) {
			$this->action( [ 'rss2_ns', 'atom_ns' ] );
			$this->action( 'rss2_head' );
			$this->action( 'atom_head' );
		}

		$this->action( 'init', 0, 99, 'late' );
		$this->action( 'template_redirect', 0, 1 );
		$this->filter( 'redirect_canonical', 2 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'OpenSearch', 'Modules: Menu Name', 'gnetwork' ) );
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
					'title'       => _x( 'OpenSearch', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'OpenSearch support for this blog.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'default'     => '0',
				],
				[
					'field'       => 'suggestions',
					'type'        => 'enabled',
					'title'       => _x( 'Suggestions', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'OpenSearch Suggestions support for this site.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'default'     => '0',
				],
				[
					'field'       => 'shortname',
					'type'        => 'text',
					'title'       => _x( 'ShortName', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'A short name for the search engine. <b>16</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'default'     => $name,
					'field_class' => 'medium-text',
				],
				[
					'field'       => 'longname',
					'type'        => 'text',
					'title'       => _x( 'LongName', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'An extended name for the search engine. <b>48</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'description',
					'type'        => 'textarea',
					'title'       => _x( 'Description', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'A brief description of the search engine. <b>1024</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'default'     => sprintf(
						/* translators: `%s`: site name */
						_x( 'Search %s', 'Modules: OpenSearch: Settings', 'gnetwork' ),
						$name
					),
				],
				[
					'field'       => 'attribution',
					'type'        => 'text',
					'title'       => _x( 'Attribution', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'A list of all sources or entities that should be credited. <b>256</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'field_class' => 'large-text',
					'default'     => sprintf(
						/* translators: `%s`: site name */
						_x( 'Search data copyright %s', 'Modules: OpenSearch: Settings', 'gnetwork' ),
						$name
					),
				],
				[
					'field'       => 'syndication',
					'type'        => 'select',
					'title'       => _x( 'Syndication Right', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'Indicates the degree to which the search results provided.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'default'     => 'open',
					'values'      => [
						'open'    => _x( 'Open', 'Modules: OpenSearch: Settings', 'gnetwork' ),
						'limited' => _x( 'Limited', 'Modules: OpenSearch: Settings', 'gnetwork' ),
						'private' => _x( 'Private', 'Modules: OpenSearch: Settings', 'gnetwork' ),
						'closed'  => _x( 'Closed', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					],
				],
				[
					'field'       => 'contact',
					'type'        => 'email',
					'title'       => _x( 'Contact', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'An email address at which the maintainer of the search engine can be reached.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'default'     => get_network_option( NULL, 'admin_email' ),
				],
				[
					'field'       => 'tags',
					'type'        => 'text',
					'title'       => _x( 'Tags', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'description' => _x( 'A set of words that are used as keywords to identify and categorize this search content. Single words and are delimited by space. <b>256</b> chars or less, no HTML.', 'Modules: OpenSearch: Settings', 'gnetwork' ),
					'field_class' => 'large-text',
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( $this->options['opensearch'] ) {

			$manifest = self::getManifestURL();

			Core\HTML::desc( sprintf(
				/* translators: `%s`: manifest url */
				_x( 'Current Manifest: %s', 'Modules: OpenSearch: Settings', 'gnetwork' ),
				Core\HTML::tag( 'code', Core\HTML::link( Core\URL::relative( $manifest ), $manifest ) )
			) );

		} else {

			Core\HTML::desc( _x( 'There is no manifest available.', 'Modules: OpenSearch: Settings', 'gnetwork' ) );
		}
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [
			 [
				'id'      => $this->classs( 'help' ),
				'title'   => _x( 'OpenSearch', 'Modules: OpenSearch: Help Tab Title', 'gnetwork' ),
				'content' => '<p>OpenSearch is a collection of simple formats for the sharing of search results.</p>
					<p>This site\'s OpenSearch description file is located on:<br />'.Core\HTML::link( NULL, self::getManifestURL() ).'</p>
				<p>Fore more information:<br />
					<a href="https://github.com/dewitt/opensearch" target="_blank">OpenSearch Documentation</a><br />
					<a href="https://developer.mozilla.org/en-US/docs/Web/OpenSearch" target="_blank">OpenSearch on MDN</a><br />
				</p>',
			],
		];
	}

	public function do_link_tag()
	{
		echo '<link rel="search" type="application/opensearchdescription+xml" href="'
			.esc_url( self::getManifestURL() ).'" title="'.$this->options['shortname'].'" />'."\n";
	}

	public function rss2_ns()
	{
		if ( Core\WordPress::isExport() )
			return;

		echo 'xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/"'."\n";
	}

	public function rss2_head()
	{
		if ( Core\WordPress::isExport() )
			return;

		echo "\t".'<atom:link rel="search" type="application/opensearchdescription+xml" href="'
			.esc_url( self::getManifestURL() ).'" title="'.$this->options['shortname'].'" />'."\n";
	}

	public function atom_head()
	{
		$this->do_link_tag();
	}

	public function init_late()
	{
		add_rewrite_tag( "%{$this->key}%", '([^&]+)' );
		add_rewrite_rule( '^osd\.xml?$', sprintf( 'index.php?%s=description', $this->key ), 'top' );
		add_rewrite_rule( '^oss\.json?$', sprintf( 'index.php?%s=suggestions', $this->key ), 'top' );
	}

	public function template_redirect()
	{
		switch ( sanitize_text_field( get_query_var( $this->key ) ) ) {
			case 'description': $this->render_description(); break;
			case 'suggestions': $this->render_suggestions(); break;
		}
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
			$text = _x( 'Search Engine', 'Modules: OpenSearch', 'gnetwork' );

		if ( is_null( $title ) )
			$title = _x( 'Add this site search engine plugin to your browser.', 'Modules: OpenSearch', 'gnetwork' );

		$script = "function AddSearchEngine(){
			if(window.external && ('AddSearchProvider' in window.external)){window.external.AddSearchProvider('".esc_js( self::getManifestURL() )."');
			}else{alert('"._x( 'Your browser does not support the AddSearchProvider method!', 'Modules: OpenSearch', 'gnetwork' )."');
			};return false;}";

		echo '<script type="text/javascript">'.$script.'</script>';

		echo $before.Core\HTML::tag( 'a', [
			'href'    => $link,
			'title'   => $title,
			'onclick' => 'return AddSearchEngine()',
		], $text ).$after;
	}

	public static function getManifestURL()
	{
		return get_site_url( NULL, 'osd.xml' );
	}

	// http://www.opensearch.org/Specifications/OpenSearch/1.1
	// https://developer.mozilla.org/en-US/Add-ons/Creating_OpenSearch_plugins_for_Firefox
	private function render_description()
	{
		// _donot_cache_page();

		$url = add_query_arg( GNETWORK_SEARCH_QUERYID, '{searchTerms}', GNETWORK_SEARCH_URL );

		$xml = "\t".Core\HTML::tag( 'ShortName', trim( $this->options['shortname'] ) )."\n";

		if ( $this->options['longname'] )
			$xml.= "\t".Core\HTML::tag( 'LongName', $this->options['longname'] )."\n";

		$xml.= "\t".Core\HTML::tag( 'Description', $this->options['description'] )."\n";
		$xml.= "\t".Core\HTML::tag( 'InputEncoding', get_bloginfo( 'charset' ) )."\n";
		$xml.= "\t".Core\HTML::tag( 'OutputEncoding', get_bloginfo( 'charset' ) )."\n";
		$xml.= "\t".Core\HTML::tag( 'Language', get_bloginfo( 'language' ) )."\n";

		if ( GNETWORK_SEARCH_REDIRECT ) {

			$xml.= "\t".Core\HTML::tag( 'moz:SearchForm', esc_url( GNETWORK_SEARCH_URL ) )."\n";

		} else {

			// TODO: generate customized atom/rss results
			// SEE: gNetwork json feed
			// SEE: https://www.drupal.org/project/opensearch
			// ALSO : http://www.opensearch.org/Documentation/Developer_how_to_guide#How_to_indicate_errors

			$xml.= "\t".Core\HTML::tag( 'Url', [
				'type'     => 'application/atom+xml',
				'template' => add_query_arg( [
					'feed'                  => 'atom',
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
				], GNETWORK_SEARCH_URL ),
			] )."\n";

			$xml.= "\t".Core\HTML::tag( 'Url', [
				'type'     => 'application/rss+xml',
				'template' => add_query_arg( [
					'feed'                  => 'rss2',
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
				], GNETWORK_SEARCH_URL ),
			] )."\n";

			if ( $this->options['suggestions'] ) {

				$xml.= "\t".Core\HTML::tag( 'Url', [
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

		$xml.= "\t".Core\HTML::tag( 'Url', [
			'type'     => 'text/html',
			'method'   => 'get',
			'template' => $url,
		] )."\n";

		$xml.= "\t".Core\HTML::tag( 'Url', [
			'type'     => 'application/opensearchdescription+xml',
			'rel'      => 'self',
			'template' => self::getManifestURL(),
		] )."\n";

		// TODO: use the one from branding module
		if ( file_exists( ABSPATH.'favicon.ico' ) )
			$xml.= "\t".Core\HTML::tag( 'Image', [
				'type'   => 'image/x-icon',
				'width'  => '16',
				'height' => '16',
			], get_bloginfo( 'url' ).'/favicon.ico' )."\n";

		// TODO: use the one from branding module
		if ( file_exists( ABSPATH.'favicon.png' ) )
			$xml.= "\t".Core\HTML::tag( 'Image', [
				'type'   => 'image/png',
				'width'  => '64',
				'height' => '64',
			], get_bloginfo( 'url' ).'/favicon.png' )."\n";

		if ( $this->options['contact'] )
			$xml.= "\t".Core\HTML::tag( 'Contact', $this->options['contact'] )."\n";

		if ( $this->options['tags'] )
			$xml.= "\t".Core\HTML::tag( 'Tags', $this->options['tags'] )."\n";

		if ( $this->options['attribution'] )
			$xml.= "\t".Core\HTML::tag( 'Attribution', $this->options['attribution'] )."\n";

		$xml.= "\t".Core\HTML::tag( 'SyndicationRight', $this->options['syndication'] )."\n";

		$xml.= "\t".'<Query role="example" searchTerms="tag" />'."\n";
		$xml.= "\t".'<AdultContent>false</AdultContent>';

		// header( 'Content-Type: text/xml; charset=utf-8' );
		header( 'Content-Type: application/opensearchdescription+xml; charset=utf-8' );
		header( 'Expires: '.gmdate( 'D, d M Y H:i:s', time() + Core\Date::MONTH_IN_SECONDS ).' GMT' );
		header( 'Cache-Control: max-age='.Core\Date::MONTH_IN_SECONDS.', must-revalidate' );

		echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		echo Core\HTML::tag( 'OpenSearchDescription', [
			'xmlns'             => 'http://a9.com/-/spec/opensearch/1.1/',
			'xmlns:moz'         => 'http://www.mozilla.org/2006/browser/search/',
			'xmlns:suggestions' => $this->options['suggestions'] ? 'http://www.opensearch.org/specifications/opensearch/extensions/suggestions/1.1' : FALSE,
		], "\n".$xml."\n" );

		exit;
	}

	// FIXME: WORKING BUT: firefox and chrome will try to get it but wont display suggestions!
	// https://wiki.mozilla.org/Search_Service/Suggestions
	// https://developer.mozilla.org/en-US/docs/Supporting_search_suggestions_in_search_plugins
	// http://www.opensearch.org/Specifications/OpenSearch/Extensions/Suggestions
	private function render_suggestions()
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
					$query_urls[] = Core\WordPress::getSearchLink( $title );
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
}

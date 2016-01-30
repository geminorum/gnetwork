<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkOpenSearch extends gNetworkModuleCore
{

	protected $option_key = 'opensearch';
	protected $network    = FALSE;

	private $ajax_action = 'opensearch_suggestions';

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'opensearch',
			_x( 'Open Search', 'OpenSearch Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( ! $this->options['opensearch'] )
			return;

		add_action( 'wp_head', array( $this, 'wp_head' ) );

		if ( ! constant( 'GNETWORK_SEARCH_REDIRECT' ) ) {
			add_action( 'atom_ns', array( $this, 'rss2_ns' ) );
			add_action( 'rss2_ns', array( $this, 'rss2_ns' ) );
			add_action( 'atom_head', array( $this, 'wp_head' ) );
			add_action( 'rss2_head', array( $this, 'rss2_head' ) );
		}

		// if ( $this->options['suggestions'] ) {
		// 	add_action( 'wp_ajax_opensearch_suggestions', array( $this, 'ajax' ) );
		// 	add_action( 'wp_ajax_nopriv_opensearch_suggestions', array( $this, 'ajax' ) );
		// }

		// add_action( 'rewrite_rules_array', array( $this, 'rewrite_rules_array' ), 8 );
		add_filter( 'redirect_canonical', array( $this, 'redirect_canonical' ), 10, 2 );
		add_action( 'parse_request', array( $this, 'parse_request' ), 1 );
	}

	public function settings_help_tabs()
	{
		return array(
			 array(
				'id'      => 'gnetwork-opensearch-help',
				'title'   => _x( 'Open Search', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
				'content' => '<p>OpenSearch is a collection of simple formats for the sharing of search results.</p>
					<p>This blog\'s OpenSearch description file is:<br /><a href="'.self::url().'" target="_blank">'.self::url().'</a></p>
				<p>Fore more information:<br />
					<a href="https://developer.mozilla.org/en-US/Add-ons/Creating_OpenSearch_plugins_for_Firefox" target="_blank">Creating OpenSearch plugins for Firefox</a><br />
					<a href="https://developer.mozilla.org/en-US/docs/Adding_search_engines_from_web_pages" target="_blank">Adding search engines from web pages</a><br />
					<a href="https://opensearch.org" target="_blank">OpenSearch.org</a><br />
				</p>',
				'callback' => FALSE,
			),
		);
	}

	public function default_settings()
	{
		$name = get_bloginfo( 'name', 'display' );
		return array(
			'_general' => array(
				array(
					'field'       => 'opensearch',
					'type'        => 'enabled',
					'title'       => _x( 'Open Search', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'OpenSearch support for this blog.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'suggestions',
					'type'        => 'enabled',
					'title'       => _x( 'Suggestions', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'OpenSearch Suggestions support for this site.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'shortname',
					'type'        => 'text',
					'title'       => _x( 'ShortName', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A short name for the search engine. <b>16</b> chars or less, no HTML.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'medium-text',
				),
				array(
					'field'       => 'longname',
					'type'        => 'text',
					'title'       => _x( 'LongName', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'An extended name for the search engine. <b>48</b> chars or less, no HTML.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'description',
					'type'        => 'textarea',
					'title'       => _x( 'Description', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A brief description of the search engine. <b>1024</b> chars or less, no HTML.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'attribution',
					'type'        => 'text',
					'title'       => _x( 'Attribution', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A list of all sources or entities that should be credited. <b>256</b> chars or less, no HTML.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'default'     => sprintf( _x( 'Search data copyright %s', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ), $name ),
					'field_class' => 'large-text',
				),
				array(
					'field'       => 'syndication',
					'type'        => 'select',
					'title'       => _x( 'Syndication Right', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Indicates the degree to which the search results provided.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'default'     => 'open',
					'values'      => array(
						'open'    => _x( 'Open', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
						'limited' => _x( 'Limited', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
						'private' => _x( 'Private', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
						'closed'  => _x( 'Closed', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'contact',
					'type'        => 'text',
					'title'       => _x( 'Contact', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'An email address at which the maintainer of the search engine can be reached.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'default'     => get_site_option( 'admin_email' ),
				),
				array(
					'field'       => 'tags',
					'type'        => 'text',
					'title'       => _x( 'Tags', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A set of words that are used as keywords to identify and categorize this search content. Single words and are delimited by space. <b>256</b> chars or less, no HTML.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text',
				),
			),
		);
	}

	public function default_options()
	{
		$name = get_bloginfo( 'name', 'display' );

		return array(
			'opensearch'  => '0',
			'suggestions' => '0',
			'shortname'   => $name,
			'longname'    => '',
			'description' => sprintf( _x( 'Search %s', 'OpenSearch Module', GNETWORK_TEXTDOMAIN ), $name ),
			'contact'     => '',
			'tags'        => '',
			'attribution' => '',
			'syndication' => 'open',
		);
	}

	public function wp_head()
	{
		echo "\t".'<link rel="search" type="application/opensearchdescription+xml" href="'
			.self::url().'" title="'.$this->options['shortname'].'" />'."\n";
	}

	public function rss2_ns()
	{
		if ( defined( 'GNETWORK_IS_WP_EXPORT' ) && GNETWORK_IS_WP_EXPORT )
			return;

		echo 'xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/"'."\n";
	}

	public function rss2_head()
	{
		if ( defined( 'GNETWORK_IS_WP_EXPORT' ) && GNETWORK_IS_WP_EXPORT )
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
		return array_merge( array(
			'osd\.xml$'  => 'index.php?opensearch=1',
			'oss\.json$' => 'index.php?opensearch_suggestions=1',
			// 'oss\.json$' => 'wp-admin/admin-ajax.php?action=opensearch_suggestions', // NOT WORKING
		), $rules );
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
			$text = _x( 'Search Engine', 'OpenSearch Module', GNETWORK_TEXTDOMAIN );

		if ( is_null( $title ) )
			$title = _x( 'Add this site search engine plugin to your browser.', 'OpenSearch Module', GNETWORK_TEXTDOMAIN );

		$script = "function AddSearchEngine(){
			if(window.external && ('AddSearchProvider' in window.external)){window.external.AddSearchProvider('".self::url()."');
			}else{alert('"._x( 'Your browser does not support the AddSearchProvider method!', 'OpenSearch Module', GNETWORK_TEXTDOMAIN )."');
			};return false;}";

		echo '<script type="text/javascript">'.$script.'</script>';

		echo $before.self::html( 'a', array(
			'href'    => $link,
			'title'   => $title,
			'onclick' => 'return AddSearchEngine()',
		), $text ).$after;
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
		// __donot_cache_page();

		$url = add_query_arg( GNETWORK_SEARCH_QUERYID, '{searchTerms}', GNETWORK_SEARCH_URL );

		$xml = "\t".self::html( 'ShortName', trim( $this->options['shortname'] ) )."\n";

		if ( $this->options['longname'] )
			$xml .= "\t".self::html( 'LongName', $this->options['longname'] )."\n";

		$xml .= "\t".self::html( 'Description', $this->options['description'] )."\n";
		$xml .= "\t".self::html( 'InputEncoding', get_bloginfo( 'charset' ) )."\n";
		$xml .= "\t".self::html( 'OutputEncoding', get_bloginfo( 'charset' ) )."\n";
		$xml .= "\t".self::html( 'Language', get_bloginfo( 'language' ) )."\n";

		if ( GNETWORK_SEARCH_REDIRECT ) {

			$xml .= "\t".self::html( 'moz:SearchForm', GNETWORK_SEARCH_URL )."\n";

		} else {

			// TODO: generate customized atom/rss results
			// SEE: gNetwork json feed
			// SEE: https://www.drupal.org/project/opensearch
			// ALSO : http://www.opensearch.org/Documentation/Developer_how_to_guide#How_to_indicate_errors

			$xml .= "\t".self::html( 'Url', array(
				'type'     => 'application/atom+xml',
				'template' => add_query_arg( array(
					'feed'                  => 'atom',
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
				), GNETWORK_SEARCH_URL ),
			) )."\n";

			$xml .= "\t".self::html( 'Url', array(
				'type'     => 'application/rss+xml',
				'template' => add_query_arg( array(
					'feed'                  => 'rss2',
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
				), GNETWORK_SEARCH_URL ),
			) )."\n";

			if ( $this->options['suggestions'] ) {
				$xml .= "\t".self::html( 'Url', array(
					// 'type'     => 'application/json',
					'type'     => 'application/x-suggestions+json',
					// 'type'     => 'application/x-moz-keywordsearch',
					// 'method'   => 'get',
					// 'rel'      => 'suggestions',
					'template' => add_query_arg( 'query', '{searchTerms}', get_bloginfo( 'url', 'display' ).'/oss.json' ),
				) )."\n";

				$url = add_query_arg( array(
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
					'prefix'                => '{suggestions:suggestionPrefix?}',
					'index'                 => '{suggestions:suggestionIndex?}',
				), GNETWORK_SEARCH_URL );
			}
		}

		// TODO: add more query strings
		// LIKE: /?s={searchTerms}&itemstart={startIndex}&itempage={startPage}&itemlimit={count}

		$xml .= "\t".self::html( 'Url', array(
			'type'     => 'text/html',
			'method'   => 'get',
			'template' => $url,
		) )."\n";

		$xml .= "\t".self::html( 'Url', array(
			'type'     => 'application/opensearchdescription+xml',
			'rel'      => 'self',
			'template' => self::url(),
		) )."\n";

		if ( file_exists( ABSPATH.'favicon.ico' ) )
			$xml .= "\t".self::html( 'Image', array(
				'type'   => 'image/x-icon',
				'width'  => '16',
				'height' => '16',
			), get_bloginfo( 'url' ).'/favicon.ico' )."\n";

		if ( file_exists( ABSPATH.'favicon.png' ) )
			$xml .= "\t".self::html( 'Image', array(
				'type'   => 'image/png',
				'width'  => '64',
				'height' => '64',
			), get_bloginfo( 'url' ).'/favicon.png' )."\n";

		if ( $this->options['contact'] )
			$xml .= "\t".self::html( 'Contact', $this->options['contact'] )."\n";

		if ( $this->options['tags'] )
			$xml .= "\t".self::html( 'Tags', $this->options['tags'] )."\n";

		if ( $this->options['attribution'] )
			$xml .= "\t".self::html( 'Attribution', $this->options['attribution'] )."\n";

		$xml .= "\t".self::html( 'SyndicationRight', $this->options['syndication'] )."\n";

		$xml .= "\t".'<Query role="example" searchTerms="tag" />'."\n";
		$xml .= "\t".'<AdultContent>false</AdultContent>';

		// header( 'Content-Type: text/xml; charset=utf-8' );
		header( 'Content-Type: application/opensearchdescription+xml; charset=utf-8' );

		echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		echo self::html( 'OpenSearchDescription', array(
			'xmlns'             => 'http://a9.com/-/spec/opensearch/1.1/',
			'xmlns:moz'         => 'http://www.mozilla.org/2006/browser/search/',
			'xmlns:suggestions' => $this->options['suggestions'] ? 'http://www.opensearch.org/specifications/opensearch/extensions/suggestions/1.1' : FALSE,
		), "\n".$xml."\n" );

		exit();
	}

	// FIXME: WORKING BUT: firefox and chrome will try to get it but wont display suggestions!
	// https://wiki.mozilla.org/Search_Service/Suggestions
	// https://developer.mozilla.org/en-US/docs/Supporting_search_suggestions_in_search_plugins
	// http://www.opensearch.org/Specifications/OpenSearch/Extensions/Suggestions
	private function suggestions()
	{
		$completions = $descriptions = $query_urls = array();

		// if ( WP_DEBUG_LOG )
		// 	error_log( print_r( $_REQUEST, TRUE ) );

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

			$the_query = new WP_Query( array(
				's'                      => $query_string,
				'post_status'            => 'publish',
				'post_type'              => 'any',
				'posts_per_page'         => 10,
				'suppress_filters'       => TRUE,
				'cache_results'          => FALSE,
				'no_found_rows'          => TRUE, // counts posts, remove if pagination required
				'update_post_term_cache' => FALSE, // grabs terms
				'update_post_meta_cache' => FALSE, // grabs post meta
			) );

			if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();

					$title = strip_tags( str_replace( '&nbsp;', ' ', get_the_title() ) );
					$completions[]  = $title;
					$descriptions[] = '';
					$query_urls[]   = self::getSearchLink( $title );
				}
			}
		}

		// if ( WP_DEBUG_LOG ) {
		// 	$results = array(
		// 		$query_string,
		// 		$completions,
		// 		$descriptions,
		// 		$query_urls,
		// 	);
		// 	error_log( print_r( $results, TRUE ) );
		// }

		wp_send_json( array(
			$query_string,
			$completions,
			$descriptions,
			$query_urls,
		) );
	}

	// FIXME: UNFINISHED
	// DISABLED
	public function ajax()
	{
		if ( WP_DEBUG_LOG )
			error_log( print_r( $_REQUEST, TRUE ) );

		return;
		$post = wp_unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';
	}
}

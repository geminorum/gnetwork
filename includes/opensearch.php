<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkOpenSearch extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = 'opensearch';

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'opensearch',
			__( 'Open Search', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( ! $this->options['opensearch'] )
			return;

		add_action( 'wp_head', array( $this, 'wp_head' ) );

		if ( ! constant( 'GNETWORK_SEARCH_REDIRECT' ) ) {
			add_action( 'atom_head', array( $this, 'wp_head' ) );
			add_action( 'rss2_head', array( $this, 'rss2_head' ) );
		}

		// add_action( 'rewrite_rules_array', array( $this, 'rewrite_rules_array' ), 8 );
		add_filter( 'redirect_canonical', array( $this, 'redirect_canonical' ), 10, 2 );
		add_action( 'parse_request', array( $this, 'parse_request' ), 1 );
	}

	public function settings_help_tabs()
	{
		return array(
			 array(
				'id'      => 'gnetwork-opensearch-help',
				'title'   => __( 'Open Search', GNETWORK_TEXTDOMAIN ),
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
					'field'   => 'opensearch',
					'type'    => 'enabled',
					'title'   => __( 'Open Search', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'OpenSearch support for this blog.', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'suggestions',
					'type'    => 'enabled',
					'title'   => __( 'Suggestions', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'OpenSearch Suggestions support for this site.', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'shortname',
					'type'    => 'text',
					'title'   => __( 'ShortName', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'A short name for the search engine. <b>16</b> chars or less, no HTML.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
					'class'   => 'medium-text',
				),
				array(
					'field'   => 'longname',
					'type'    => 'text',
					'title'   => __( 'LongName', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'An extended name for the search engine. <b>48</b> chars or less, no HTML.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'description',
					'type'    => 'textarea',
					'title'   => __( 'Description', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'A brief description of the search engine. <b>1024</b> chars or less, no HTML.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'attribution',
					'type'    => 'text',
					'title'   => __( 'Attribution', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'A list of all sources or entities that should be credited. <b>256</b> chars or less, no HTML.', GNETWORK_TEXTDOMAIN ),
					'default' => sprintf( __( 'Search data copyright %s', GNETWORK_TEXTDOMAIN ), $name ),
					'class'   => 'large-text',
				),
				array(
					'field'   => 'syndication',
					'type'    => 'select',
					'title'   => __( 'Syndication Right', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Indicates the degree to which the search results provided.', GNETWORK_TEXTDOMAIN ),
					'default' => 'open',
					'values'  => array(
						'open'    => __( 'Open', GNETWORK_TEXTDOMAIN ),
						'limited' => __( 'Limited', GNETWORK_TEXTDOMAIN ),
						'private' => __( 'Private', GNETWORK_TEXTDOMAIN ),
						'closed'  => __( 'Closed', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'   => 'contact',
					'type'    => 'text',
					'title'   => __( 'Contact', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'An email address at which the maintainer of the search engine can be reached.', GNETWORK_TEXTDOMAIN ),
					'default' => get_site_option( 'admin_email' ),
				),
				array(
					'field'   => 'tags',
					'type'    => 'text',
					'title'   => __( 'Tags', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'A set of words that are used as keywords to identify and categorize this search content. Single words and are delimited by space. <b>256</b> chars or less, no HTML.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
					'class'   => 'large-text',
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
			'description' => sprintf( __( 'Search &#x201C;%s&#x201D;', GNETWORK_TEXTDOMAIN ), $name ),
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

	public function rss2_head()
	{
		if ( defined( 'GNETWORK_IS_WP_EXPORT' ) && GNETWORK_IS_WP_EXPORT )
			return;

		echo "\t".'<atom:link rel="search" type="application/opensearchdescription+xml" href="'
			.self::url().'" title="'.$this->options['shortname'].'" />'."\n";
	}

	public function parse_request( $request )
	{
		if ( 'osd.xml' == $request->request )
			$this->xml();
		else if ( 'oss.json' == $request->request )
			$this->suggestions();
	}

	public function rewrite_rules_array( $rules )
	{
		return array_merge( array(
			'osd\.xml$'  => 'index.php?opensearch=1',
			'oss\.json$' => 'index.php?opensearch_suggestions=1',
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
	public static function link( $text = NULL, $title = NULL, $link = '#' )
	{
		if ( is_null( $text ) )
			$text = __( 'Search Plugin', GNETWORK_TEXTDOMAIN );

		if ( is_null( $title ) )
			$title = __( 'Add this site search engine plugin to your browser.', GNETWORK_TEXTDOMAIN );

		$script = "function AddSearchEngine () {
			if (window.external && ('AddSearchProvider' in window.external)) {
				window.external.AddSearchProvider ('".self::url()."');
			} else {
				alert ('".__( 'Your browser does not support the AddSearchProvider method!', GNETWORK_TEXTDOMAIN )."');
			}}";

		echo '<script type="text/javascript">'.$script.'</script>';

		echo gNetworkUtilities::html( 'a', array(
			'href'    => $link,
			'title'   => $title,
			'onclick' => 'AddSearchEngine()',
		), $text );
	}

	public static function url( $escape = TRUE )
	{
		$url = get_bloginfo( 'url', 'display' ).'/osd.xml';
		if ( $escape )
			return esc_url( $url );
		return $url;
	}

	// http://www.opensearch.org/Specifications/OpenSearch/1.1
	// https://developer.mozilla.org/en-US/Add-ons/Creating_OpenSearch_plugins_for_Firefox
	// http://www.chromium.org/tab-to-search
	// https://code.google.com/p/gsa-open-search-via-opensearch/
	private function xml()
	{
		// __donot_cache_page();

		$xml  = gNetworkUtilities::html( 'ShortName', array(), trim( $this->options['shortname'] ) );

		if ( $this->options['longname'] )
			$xml .= gNetworkUtilities::html( 'LongName', array(), $this->options['longname'] );

		$xml .= gNetworkUtilities::html( 'Description', array(), $this->options['description'] );
		$xml .= gNetworkUtilities::html( 'InputEncoding', array(), get_bloginfo( 'charset' ) );
		$xml .= gNetworkUtilities::html( 'OutputEncoding', array(), get_bloginfo( 'charset' ) );
		$xml .= gNetworkUtilities::html( 'Language', array(), get_bloginfo( 'language' ) );

		if ( constant( 'GNETWORK_SEARCH_REDIRECT' ) ) {

			$xml .= gNetworkUtilities::html( 'moz:SearchForm', array(), GNETWORK_SEARCH_URL );

		} else {

			$xml .= gNetworkUtilities::html( 'Url', array(
				'type'     => 'application/atom+xml',
				'template' => add_query_arg( array(
					'feed'                  => 'atom',
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
				), GNETWORK_SEARCH_URL ),
			) );

			$xml .= gNetworkUtilities::html( 'Url', array(
				'type'     => 'application/rss+xml',
				'template' => add_query_arg( array(
					'feed'                  => 'rss2',
					GNETWORK_SEARCH_QUERYID => '{searchTerms}',
				), GNETWORK_SEARCH_URL ),
			) );

			// <Url type="application/json" rel="suggestions" template="http://my_site/suggest?q={searchTerms}" />
			if ( $this->options['suggestions'] )
				$xml .= gNetworkUtilities::html( 'Url', array(
					'type'     => 'application/json',
					// 'type'     => 'application/x-suggestions+json',
					// 'method'   => 'get',
					'rel'      => 'suggestions',
					'template' => add_query_arg( 'q', '{searchTerms}', get_bloginfo( 'url', 'display' ).'/oss.json' ),
				) );
		}

		$xml .= gNetworkUtilities::html( 'Url', array(
			'type'     => 'text/html',
			'method'   => 'get',
			'template' => add_query_arg( GNETWORK_SEARCH_QUERYID, '{searchTerms}', GNETWORK_SEARCH_URL ),
		) );

		$xml .= gNetworkUtilities::html( 'Url', array(
			'type'     => 'application/opensearchdescription+xml',
			'rel'      => 'self',
			'template' => self::url(),
		) );

		if ( file_exists( ABSPATH.'favicon.ico' ) )
			$xml .= gNetworkUtilities::html( 'Image', array(
				'type'   => 'image/x-icon',
				'width'  => '16',
				'height' => '16',
			), get_bloginfo( 'url' ).'/favicon.ico' );

		if ( file_exists( ABSPATH.'favicon.png' ) )
			$xml .= gNetworkUtilities::html( 'Image', array(
				'type'   => 'image/png',
				'width'  => '64',
				'height' => '64',
			), get_bloginfo( 'url' ).'/favicon.png' );

		if ( $this->options['contact'] )
			$xml .= gNetworkUtilities::html( 'Contact', array(), $this->options['contact'] );

		if ( $this->options['tags'] )
			$xml .= gNetworkUtilities::html( 'Tags', array(), $this->options['tags'] );

		if ( $this->options['attribution'] )
			$xml .= gNetworkUtilities::html( 'Attribution', array(), $this->options['attribution'] );

		$xml .= gNetworkUtilities::html( 'SyndicationRight', array(), $this->options['syndication'] );

		$xml .= '<Query role="example" searchTerms="tag" />';
		$xml .= '<AdultContent>false</AdultContent>';


		header( 'Content-Type: text/xml; charset=utf-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		echo gNetworkUtilities::html( 'OpenSearchDescription', array(
			'xmlns'     => 'http://a9.com/-/spec/opensearch/1.1/',
			'xmlns:moz' => 'http://www.mozilla.org/2006/browser/search/',
		), $xml );
		exit();
	}

	// TODO: NOT WORKING, NEEDS TEST
	// https://wiki.mozilla.org/Search_Service/Suggestions
	// http://www.opensearch.org/Specifications/OpenSearch/Extensions/Suggestions
	private function suggestions()
	{
		$results = array();

		trigger_error( $_REQUEST['q'] );

		$the_query = new WP_Query( array(
			's' => $_REQUEST['q'],
			//'posts_per_page' => 10,
		) );

		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$results[] = strip_tags( get_the_title() );
			}
		}

		header( 'Content-Length:'.count( $results ) );
		header( 'Content-Type: application/json; charset=utf-8' );
		echo '["'.$_REQUEST['q'].'",'.wp_json_encode( $results ).']';

		exit();
	}
}

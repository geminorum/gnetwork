<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBlog extends gNetworkModuleCore
{

	protected $option_key = 'general';
	protected $network    = FALSE;
	protected $ajax       = TRUE;

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'general',
			__( 'General', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		add_filter( 'init', array( $this, 'init_early' ), 1 );

		add_filter( 'frontpage_template', array( $this, 'frontpage_template' ) );

		if ( ! $this->options['xmlrpc_enabled'] )
			add_filter( 'xmlrpc_enabled', '__return_false', 12 );

		if ( $this->options['linkmanager_enabled'] )
			add_filter( 'pre_option_link_manager_enabled', '__return_true', 12 );

		if ( $this->options['page_copyright'] )
			add_filter( 'wp_head', array( $this, 'wp_head_copyright' ) );

		if ( $this->options['page_404'] )
			add_filter( '404_template', array( $this, 'custom_404_template' ) );
	}

	public function default_options()
	{
		return array(
			'admin_locale'        => '',
			'blog_redirect'       => '',
			'linkmanager_enabled' => '0',
			'xmlrpc_enabled'      => '0',
			'page_copyright'      => '0',
			'page_404'            => '0',
			'feed_json'           => '0',
			'ga_override'         => '',
		);
	}

	public function default_settings()
	{
		$exclude = array_filter( array(
			get_option( 'page_on_front' ),
			get_option( 'page_for_posts' ),
		) );

		$settings = array(
			'_general' => array(
				array(
					'field'       => 'blog_redirect',
					'type'        => 'text',
					'title'       => __( 'Blog Redirect to', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'The site will redirect to this URL. Leave empty to disable.', GNETWORK_TEXTDOMAIN ),
					'default'     => '',
					'dir'         => 'ltr',
					'placeholder' => 'http://example.com',
				),
				// FIXME: wont work, wont enable!
				array(
					'field'       => 'linkmanager_enabled',
					'type'        => 'enabled',
					'title'       => __( 'Link Manager', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'Enables the Link Manager that existed in WordPress until version 3.5.', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'after'       => sprintf( '<span class="field-after icon-wrap">%s</span>', self::getWPCodexLink( 'Links_Manager' ) ),
				),
				array(
					'field'       => 'xmlrpc_enabled',
					'type'        => 'enabled',
					'title'       => __( 'XML-RPC', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'Whether XML-RPC services are enabled on this site.', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'feed_json',
					'type'        => 'enabled',
					'title'       => __( 'Feed JSON', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'Adds JSON as new type of feed you can subscribe to.', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'after'       => sprintf( '<code class="field-after"><a href="%1$s">%1$s</a></code>', get_feed_link( 'json' ) ),
				),
				array(
					'field'       => 'page_copyright',
					'type'        => 'page',
					'title'       => __( 'Page for Copyright', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'Set any page to be used as copyright page on html head.', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => sprintf( '<span class="field-after icon-wrap">%s</span>', self::getNewPostTypeLink( 'page' ) ),
				),
				array(
					'field'       => 'page_404',
					'type'        => 'page',
					'title'       => __( 'Page for Error 404', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'Set any page to be used as the 404 error page.', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => sprintf( '<span class="field-after icon-wrap">%s</span>', self::getNewPostTypeLink( 'page' ) ),
				),
			),
		);

		if ( class_exists( 'gNetworkTracking' ) )
			$settings['_tracking'] = array(
				array(
					'field'       => 'ga_override',
					'type'        => 'text',
					'title'       => __( 'GA Override', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'This blog Google Analytics account to override the network', GNETWORK_TEXTDOMAIN ),
					'default'     => '',
					'dir'         => 'ltr',
					'placeholder' => 'UA-XXXXX-X',
				),
			);

		if ( class_exists( 'gNetworkLocale' ) )
			$settings['_locale'] = array(
				array(
					'field'   => 'admin_locale',
					'type'    => 'select',
					'title'   => __( 'Admin Language', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Despite of the site language, always display admin in this locale', GNETWORK_TEXTDOMAIN ),
					'values'  => self::sameKey( gNetworkLocale::available() ),
					'default' => get_locale(),
				),
			);

		return $settings;
	}

	public function init_early()
	{
		if ( $this->options['blog_redirect'] )
			$this->blog_redirect();

		if ( isset( $_GET['gnetwork_action'] ) && trim( $_GET['gnetwork_action'] ) ) {

			if ( 'locale' == $_GET['gnetwork_action']
				&& isset( $_GET['locale'] ) && $_GET['locale']
				&& class_exists( 'gNetworkLocale' ) ) {
					if ( $result = gNetworkLocale::changeLocale( trim( $_GET['locale'] ) ) )
						self::redirect( remove_query_arg( array( 'locale', 'gnetwork_action' ), self::currentURL() ) );
			}
		}

		if ( $this->options['feed_json'] ) {

			add_feed( 'json', array( $this, 'do_feed_json' ) );

			add_filter( 'query_vars', function( $public_query_vars ){
				$public_query_vars[] = 'callback';
				$public_query_vars[] = 'limit';
				return $public_query_vars;
			} );

			add_filter( 'template_include', array( $this, 'feed_json_template_include' ) );
		}
	}

	public function wp_head_copyright()
	{
		echo "\t".'<link rel="copyright" href="'.get_page_link( $this->options['page_copyright'] ).'">'."\n";
	}

	// FIXME: test this
	private function blog_redirect()
	{
		if ( is_user_logged_in()
			&& current_user_can( 'manage_options' ) )
				return;

		if ( $_SERVER['SERVER_NAME'] !== ( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) ) )
			return;

		if ( FALSE === self::whiteListed() )
			self::redirect( $this->options['blog_redirect'].$_SERVER['REQUEST_URI'], 307 );
	}

	public static function whiteListed( $request_uri = NULL )
	{
		if ( is_null( $request_uri ) )
			$request_uri = $_SERVER['REQUEST_URI'];

		return self::strpos_arr( array(
			'wp-cron.php',
			'wp-mail.php',
			'wp-login.php',
			'wp-signup.php',
			'wp-activate.php',
			'wp-trackback.php',
			'wp-links-opml.php',
			'xmlrpc.php',
			'wp-admin',
		), $request_uri );
	}

	// http://kaspars.net/blog/wordpress/custom-page-template-front-page
	public function frontpage_template( $template )
	{
		// check if a custom template has been selected
		if ( get_page_template_slug() )
			return get_page_template();

		return $template;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally Based on: [Feed JSON](https://wordpress.org/plugins/feed-json/)
/// By wokamoto : http://twitter.com/wokamoto
/// Updated on: 20150918 / v1.0.9

	public function do_feed_json()
	{
		gNetworkUtilities::getLayout( 'feed.json', TRUE );
	}

	public function feed_json_template_include( $template )
	{
		if ( 'json' === get_query_var( 'feed' )
			&& $layout = gNetworkUtilities::getLayout( 'feed.json' ) )
				return $layout;

		return $template;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally Based on: [kasparsd/custom-404-page](https://github.com/kasparsd/custom-404-page)
/// By Kaspars Dambis : http://kaspars.net/
/// Updated on: 20150918

	// set WP to use page template (page.php) even when returning 404
	public function custom_404_template( $template )
	{
		global $wp_query, $post;

		if ( is_404() ) {

			// get our custom 404 post object. We need to assign
			// $post global in order to force get_post() to work
			// during page template resolving.
			$post = get_post( $this->options['page_404'] );

			// populate the posts array with our 404 page object
			$wp_query->posts = array( $post );

			// set the query object to enable support for custom page templates
			$wp_query->queried_object_id = $this->options['page_404'];
			$wp_query->queried_object    = $post;

			// set post counters to avoid loop errors
			$wp_query->post_count    = 1;
			$wp_query->found_posts   = 1;
			$wp_query->max_num_pages = 0;

			return get_page_template();
		}

		return $template;
	}
}

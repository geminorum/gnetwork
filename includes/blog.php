<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBlog extends gNetworkModuleCore
{

	protected $option_key = 'general';
	protected $network    = FALSE;
	protected $ajax       = TRUE;

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'general',
			_x( 'General', 'Blog Module: Menu Name', GNETWORK_TEXTDOMAIN ),
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
					'type'        => 'url',
					'title'       => _x( 'Blog Redirect to', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'The site will redirect to this URL. Leave empty to disable.', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'url-text' ),
					'placeholder' => 'http://example.com',
				),
				// FIXME: wont work, wont enable!
				array(
					'field'       => 'linkmanager_enabled',
					'type'        => 'enabled',
					'title'       => _x( 'Link Manager', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Enables the Link Manager that existed in WordPress until version 3.5.', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'after'       => sprintf( '<span class="field-after icon-wrap">%s</span>', self::getWPCodexLink( 'Links_Manager' ) ),
				),
				array(
					'field'       => 'xmlrpc_enabled',
					'type'        => 'enabled',
					'title'       => _x( 'XML-RPC', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether XML-RPC services are enabled on this site.', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				// TODO: move this to feed module
				array(
					'field'       => 'feed_json',
					'type'        => 'enabled',
					'title'       => _x( 'Feed JSON', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds JSON as new type of feed you can subscribe to.', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'after'       => sprintf( '<code class="field-after"><a href="%1$s">%1$s</a></code>', get_feed_link( 'json' ) ),
				),
				array(
					'field'       => 'page_copyright',
					'type'        => 'page',
					'title'       => _x( 'Page for Copyright', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Set any page to be used as copyright page on html head.', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => sprintf( '<span class="field-after icon-wrap">%s</span>', self::getNewPostTypeLink( 'page' ) ),
				),
				array(
					'field'       => 'page_404',
					'type'        => 'page',
					'title'       => _x( 'Page for Error 404', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Set any page to be used as the 404 error page.', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => sprintf( '<span class="field-after icon-wrap">%s</span>', self::getNewPostTypeLink( 'page' ) ),
				),
			),
		);

		if ( class_exists( 'gNetworkTracking' ) && is_multisite() )
			$settings['_tracking'] = array(
				array(
					'field'       => 'ga_override',
					'type'        => 'text',
					'title'       => _x( 'GA Override', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This blog Google Analytics account to override the network', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'placeholder' => 'UA-XXXXX-X',
					'field_class' => array( 'regular-text', 'code-text' ),
				),
			);

		if ( class_exists( 'gNetworkLocale' ) )
			$settings['_locale'] = array(
				array(
					'field'       => 'admin_locale',
					'type'        => 'select',
					'title'       => _x( 'Admin Language', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Despite of the site language, always display admin in this locale', 'Blog Module', GNETWORK_TEXTDOMAIN ),
					'values'      => self::sameKey( gNetworkLocale::available() ),
					'default'     => get_locale(),
				),
			);

		return $settings;
	}

	public function init_early()
	{
		if ( $this->options['blog_redirect'] && ! is_admin() && ! self::isAJAX() )
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
		global $pagenow;

		if ( is_user_logged_in()
			&& current_user_can( 'manage_options' ) )
				return;

		// FIXME: WHY is that?!
		// if ( $_SERVER['SERVER_NAME'] !== ( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) ) )
		// 	return;

		$redirect = self::untrail( $this->options['blog_redirect'] ).$_SERVER['REQUEST_URI'];

		if ( ! empty( $pagenow ) && 'index.php' == $pagenow )
			self::redirect( $redirect, 307 );

		// DEPRECATED: FALLBACK
		if ( FALSE === self::whiteListed() )
			self::redirect( $redirect, 307 );
	}

	public static function whiteListed( $request_uri = NULL )
	{
		if ( is_null( $request_uri ) )
			$request_uri = $_SERVER['REQUEST_URI'];

		return self::strposArray( array(
			'wp-cron.php',
			'wp-mail.php',
			'wp-login.php',
			'wp-signup.php',
			'wp-activate.php',
			'wp-trackback.php',
			'wp-links-opml.php',
			'xmlrpc.php',
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

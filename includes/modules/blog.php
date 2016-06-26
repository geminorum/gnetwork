<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Blog extends ModuleCore
{

	protected $key     = 'general';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		add_filter( 'init', array( $this, 'init_early' ), 1 );
		add_filter( 'wp_loaded', array( $this, 'wp_loaded' ), 99 );

		add_filter( 'frontpage_template', array( $this, 'frontpage_template' ) );

		if ( ! $this->options['xmlrpc_enabled'] )
			add_filter( 'xmlrpc_enabled', '__return_false', 12 );

		if ( $this->options['linkmanager_enabled'] )
			add_filter( 'pre_option_link_manager_enabled', '__return_true', 12 );

		if ( $this->options['page_copyright'] )
			add_filter( 'wp_head', array( $this, 'wp_head_copyright' ) );

		if ( $this->options['page_404'] )
			add_filter( '404_template', array( $this, 'custom_404_template' ) );

		// @REF: http://wordpress.stackexchange.com/a/212472
		if ( '-1' == $this->options['rest_api_support'] ) {

			add_action( 'after_setup_theme', function() {

				// Remove the REST API lines from the HTML Header
				remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );

				// Remove the REST API endpoint.
				remove_action( 'rest_api_init', 'wp_oembed_register_route' );

				// Turn off oEmbed auto discovery.
				add_filter( 'embed_oembed_discover', '__return_false' );

				// Don't filter oEmbed results.
				remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );

				// Remove oEmbed discovery links.
				remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

				// Remove oEmbed-specific JavaScript from the front-end and back-end.
				remove_action( 'wp_head', 'wp_oembed_add_host_js' );

				// Remove all embeds rewrite rules.
				add_filter( 'rewrite_rules_array', 'disable_embeds_rewrites' );
			} );

		} else if ( ! $this->options['rest_api_support'] ) {

			// `{"code":"rest_disabled","message":"The REST API is disabled on this site."}`
			add_action( 'after_setup_theme', function() {

				// Filters for WP-API version 1.x
				add_filter( 'json_enabled', '__return_false' );
				add_filter( 'json_jsonp_enabled', '__return_false' );

				// Filters for WP-API version 2.x
				add_filter( 'rest_enabled', '__return_false' );
				add_filter( 'rest_jsonp_enabled', '__return_false' );
			} );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'General', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'admin_locale'        => '',
			'blog_redirect'       => '',
			'blog_redirect_status'=> '301',
			'linkmanager_enabled' => '0',
			'xmlrpc_enabled'      => '0',
			'page_copyright'      => '0',
			'page_404'            => '0',
			'feed_json'           => '0',
			'rest_api_support'    => '0',
			'disable_emojis'      => GNETWORK_DISABLE_EMOJIS,
			'ga_override'         => '',
			'from_email'          => '',
			'from_name'           => '',
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
					'title'       => _x( 'Redirect', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'The Site Will Redirect to This URL', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'url-text' ),
					'placeholder' => 'http://example.com',
				),
				array(
					'field'       => 'blog_redirect_status',
					'type'        => 'select',
					'title'       => _x( 'Redirect Status Code', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'HTTP Status Header Code', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( Settings::getMoreInfoIcon( 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection' ) ),
					'dir'         => 'ltr',
					'default'     => '301',
					'values'      => array(
						'301' => '301 Moved Permanently',
						'302' => '302 Found',
						'303' => '303 See Other',
						'307' => '307 Temporary Redirect',
						'308' => '308 Permanent Redirect',
					),
				),
				array(
					'field'       => 'rest_api_support',
					'type'        => 'select',
					'title'       => _x( 'Rest API', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Disable or Remove WordPress JSON Discovery API', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					// 'after'       => // TODO: get api endpoint
					'values'      => array(
						'0' => __( 'Disabled', GNETWORK_TEXTDOMAIN ),
						'1' => __( 'Enabled' , GNETWORK_TEXTDOMAIN ),
						'-1' => __( 'Removed', GNETWORK_TEXTDOMAIN ),
					),
				),
				// FIXME: wont work, wont enable!
				// array(
				// 	'field'       => 'linkmanager_enabled',
				// 	'title'       => _x( 'Link Manager', 'Modules: Blog', GNETWORK_TEXTDOMAIN ),
				// 	'description' => _x( 'Enables the Link Manager that existed in WordPress until version 3.5.', 'Modules: Blog', GNETWORK_TEXTDOMAIN ),
				// 	'after'       => Settings::fieldAfterIcon( Settings::getWPCodexLink( 'Links_Manager' ) ),
				// ),
				array(
					'field'       => 'xmlrpc_enabled',
					'title'       => _x( 'XML-RPC', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether XML-RPC Services Are Enabled on This Site', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'feed_json',
					'title'       => _x( 'JSON Feed', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds JSON as New Type of Feed You Can Subscribe To', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterLink( get_feed_link( 'json' ) ),
				),
				array(
					'field'       => 'disable_emojis',
					'title'       => _x( 'Emojis', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Removes the Extra Code Bloat Used to Add Support for Emoji\'s in Older Browsers', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => GNETWORK_DISABLE_EMOJIS,
					'after'       => Settings::fieldAfterIcon( Settings::getWPCodexLink( 'Emoji' ) ),
					'values'      => array(
						__( 'Enabled' , GNETWORK_TEXTDOMAIN ),
						__( 'Disabled', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'page_copyright',
					'type'        => 'page',
					'title'       => _x( 'Page for Copyright', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Set Any Page to Be Used as Copyright Page on Html Head', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => Settings::fieldAfterIcon( Settings::getNewPostTypeLink( 'page' ) ),
				),
				array(
					'field'       => 'page_404',
					'type'        => 'page',
					'title'       => _x( 'Page for 404 Error', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Set Any Page to Be Used as the 404 Error Page', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => Settings::fieldAfterIcon( Settings::getNewPostTypeLink( 'page' ) ),
				),
			),
		);

		if ( class_exists( __NAMESPACE__.'\\Mail' ) && is_multisite() ) {
			$settings['_email'] = array(
				array(
					'field'       => 'from_email',
					'type'        => 'text',
					'title'       => _x( 'From Email', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This Blog Email Address That Emails Should Be Sent From. Set to Override the Network', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'email-text' ),
				),
				array(
					'field'       => 'from_name',
					'type'        => 'text',
					'title'       => _x( 'From Name', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This Blog Email Name That Emails Should Be Sent From. Set to Override the Network', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				),
			);
		}

		if ( class_exists( __NAMESPACE__.'\\Tracking' ) && is_multisite() )
			$settings['_tracking'] = array(
				array(
					'field'       => 'ga_override',
					'type'        => 'text',
					'title'       => _x( 'GA Override', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This Blog Google Analytics Account. Set to Override the Network', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => 'UA-XXXXX-X',
					'field_class' => array( 'regular-text', 'code-text' ),
				),
			);

		if ( class_exists( __NAMESPACE__.'\\Locale' ) )
			$settings['_locale'] = array(
				array(
					'field'       => 'admin_locale',
					'type'        => 'select',
					'title'       => _x( 'Admin Language', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Despite of the Site Language, Always Display Admin in This Locale', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Arraay::sameKey( Locale::available() ),
					'default'     => get_locale(),
				),
			);

		return $settings;
	}

	public function wp_loaded()
	{
		if ( $this->is_action( 'flushrewrite' )
			&& WordPress::cuc( 'edit_others_posts' ) ) {

			flush_rewrite_rules();

			self::redirect( $this->remove_action() );
		}
	}

	public function init_early()
	{
		if ( $this->options['blog_redirect']
			&& ! is_admin()
			&& ! WordPress::isAJAX() )
				$this->blog_redirect();

		if ( ( $locale = $this->is_action( 'locale', 'locale' ) )
			&& class_exists( __NAMESPACE__.'\\Locale' )
			&& ( $result = Locale::changeLocale( $locale ) ) )
				self::redirect( $this->remove_action( 'locale' ) );

		if ( $this->options['feed_json'] ) {

			add_feed( 'json', array( $this, 'do_feed_json' ) );

			add_filter( 'query_vars', function( $public_query_vars ){
				$public_query_vars[] = 'callback';
				$public_query_vars[] = 'limit';
				return $public_query_vars;
			} );

			add_filter( 'template_include', array( $this, 'feed_json_template_include' ) );
		}

		// originally from: Disable Emojis v1.5.1
		// @SOURCE: https://wordpress.org/plugins/disable-emojis/
		if ( $this->options['disable_emojis'] ) {

			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );

			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

			add_filter( 'tiny_mce_plugins', function( $plugins ) {
				return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
			} );
		}
	}

	public function init_late()
	{
		// no need for redirect option check
		$this->blog_redirect( FALSE );
	}

	private function blog_redirect( $check = TRUE )
	{
		global $pagenow;

		if ( is_user_logged_in()
			&& current_user_can( 'manage_options' ) )
				return;

		// postpone checking in favor of WP Remote
		if ( $check && ! empty( $_POST['wpr_verify_key'] ) ) {
			add_filter( 'init', array( $this, 'init_late' ), 999 ); // must be over 100
			return;
		}

		$redirect = self::untrail( $this->options['blog_redirect'] ).$_SERVER['REQUEST_URI'];

		if ( ! empty( $pagenow ) && 'index.php' == $pagenow )
			self::redirect( $redirect, $this->options['blog_redirect_status'] );

		if ( FALSE === self::whiteListed() )
			self::redirect( $redirect, $this->options['blog_redirect_status'] );
	}

	public static function whiteListed( $request_uri = NULL )
	{
		if ( is_null( $request_uri ) )
			$request_uri = $_SERVER['REQUEST_URI'];

		return Arraay::strposArray( array(
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

	public function wp_head_copyright()
	{
		echo "\t".'<link rel="copyright" href="'.get_page_link( $this->options['page_copyright'] ).'">'."\n";
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally Based on: [Feed JSON](https://wordpress.org/plugins/feed-json/)
/// By wokamoto : http://twitter.com/wokamoto
/// Updated on: 20150918 / v1.0.9

	public function do_feed_json()
	{
		Utilities::getLayout( 'feed.json', TRUE );
	}

	public function feed_json_template_include( $template )
	{
		if ( 'json' === get_query_var( 'feed' )
			&& $layout = Utilities::getLayout( 'feed.json' ) )
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

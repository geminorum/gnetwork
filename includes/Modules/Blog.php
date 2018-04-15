<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\Crypto;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Blog extends gNetwork\Module
{

	protected $key     = 'general';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	private $autosave_interval = FALSE;

	protected function setup_actions()
	{
		$this->action( 'plugins_loaded', 0, 1 );
		$this->action( 'init', 0, 1 );
		$this->action( 'init', 0, 99, 'late' );
		$this->action( 'wp_loaded', 0, 99 );

		if ( is_admin() ) {

			$this->action( 'export_wp', 0, 1 );

			if ( $this->options['thrift_mode'] ) {

				// add_action( 'admin_init', function(){
				// 	// when there are a lot of terms
				// 	wp_deregister_script( 'suggest' );
				// } );

				add_filter( 'disable_months_dropdown', '__return_true', 5 );
				add_filter( 'media_library_show_audio_playlist', '__return_false', 5 );
				add_filter( 'media_library_show_video_playlist', '__return_false', 5 );
				add_filter( 'media_library_months_with_files', '__return_empty_array', 5 );
			}

		} else {

			$this->action( 'wp_head', 0, 12 );
			$this->action( 'embed_head', 0, 12 );

			if ( $this->options['shortlink_numeric'] ) {
				$this->action( 'template_redirect', 0, 5 );
				$this->filter( 'pre_get_shortlink', 4 );
			}

			if ( $this->options['no_found_rows'] ) {
				$this->filter( 'pre_get_posts' );
				$this->filter( 'posts_clauses', 2 );
			}

			if ( $this->options['page_404'] )
				add_filter( '404_template', [ $this, 'custom_404_template' ] );

			if ( $this->options['feed_delay'] )
				$this->filter( 'posts_where', 2 );
		}

		if ( ! $this->options['rest_api_enabled'] )
			$this->filter( 'rest_authentication_errors', 1, 999 );

		if ( ! $this->options['xmlrpc_enabled'] ) {
			$this->filter( 'wp_headers' );
			$this->filter_false( 'xmlrpc_enabled', 12 );
		}

		add_filter( 'jetpack_get_default_modules', '__return_empty_array' );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'General', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ], 'manage_options', 5
		);
	}

	public function default_options()
	{
		return [
			'thrift_mode'          => 0,
			'no_found_rows'        => 0,
			'admin_locale'         => '',
			'admin_chosen'         => 0,
			'blog_redirect'        => '',
			'blog_redirect_status' => '301',
			'heartbeat_mode'       => 'default',
			'heartbeat_frequency'  => 'default',
			'autosave_interval'    => '',
			'rest_api_enabled'     => '1',
			'xmlrpc_enabled'       => '0',
			'wlw_enabled'          => '0',
			'page_copyright'       => '0',
			'page_404'             => '0',
			'content_width'        => '',
			'meta_revised'         => '0',
			'noindex_attachments'  => '0',
			'feed_json'            => '0',
			'feed_delay'           => '10',
			'disable_emojis'       => GNETWORK_DISABLE_EMOJIS,
			'ga_override'          => '',
			'from_email'           => '',
			'from_name'            => '',
			'text_copyright'       => '',
			'theme_color'          => '',
			'shortlink_numeric'    => '0',
			'shortlink_type'       => 'numeric',
		];
	}

	public function default_settings()
	{
		$settings  = [];
		$multisite = is_multisite();

		$exclude = array_filter( [
			get_option( 'page_on_front' ),
			get_option( 'page_for_posts' ),
		] );

		if ( class_exists( __NAMESPACE__.'\\Locale' ) )
			$settings['_locale'][] = [
				'field'       => 'admin_locale',
				'type'        => 'select',
				'title'       => _x( 'Admin Language', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Despite of the Site Language, Always Display Admin in This Locale', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'none_title'  => _x( 'Site Default', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'none_value'  => '',
				'values'      => Arraay::sameKey( Locale::available() ),
			];

		$settings['_enhancements'][] = [
			'field'       => 'admin_chosen',
			'title'       => _x( 'Admin Chosen', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Chosen is a jQuery plugin that makes long, unwieldy select boxes much more user-friendly.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'after'       => Settings::fieldAfterIcon( 'https://harvesthq.github.io/chosen/' ),
		];

		$settings['_thrift'][] = [
			'field'       => 'thrift_mode',
			'title'       => _x( 'Thrift Mode', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Trying to make your host happy!', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
		];

		$settings['_thrift'][] = [
			'field'       => 'no_found_rows',
			'title'       => _x( 'No Found-Rows', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Avoids query count for paginations.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'after'       => Settings::fieldAfterIcon( 'https://wpartisan.me/?p=166' ),
		];

		$settings['_thrift'][] = [
			'field'       => 'heartbeat_mode',
			'type'        => 'select',
			'title'       => _x( 'Heartbeat Mode', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Controls the Heartbeat API locations.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => [
				'default'   => _x( 'Use default', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'disable'   => _x( 'Disable everywhere', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'dashboard' => _x( 'Disable on Dashboard page', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'postedit'  => _x( 'Allow only on Post Edit pages', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
			],
		];

		$settings['_thrift'][] = [
			'field'       => 'heartbeat_frequency',
			'type'        => 'select',
			'title'       => _x( 'Heartbeat Frequency', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Overrides the Heartbeat API frequency.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => [
				'default' => _x( 'Use default', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'15'      => _x( '15 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'20'      => _x( '20 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'25'      => _x( '25 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'30'      => _x( '30 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'35'      => _x( '35 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'40'      => _x( '40 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'45'      => _x( '45 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'50'      => _x( '50 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
				'60'      => _x( '60 seconds', 'Modules: Blog: Settings: Option', GNETWORK_TEXTDOMAIN ),
			],
		];

		if ( $this->autosave_interval )
			$settings['_thrift'][] = [
				'field'       => 'autosave_interval',
				'type'        => 'number',
				'title'       => _x( 'Autosave Interval', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => sprintf( _x( 'Time in seconds that WordPress will save the currently editing posts. default is %s seconds.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ), '<code>'.AUTOSAVE_INTERVAL.'</code>' ),
				'min_attr'    => '20',
				'default'     => '120',
			];

		$settings['_services'][] = [
			'field'       => 'rest_api_enabled',
			'title'       => _x( 'Rest API', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Whether REST API services are enabled on this site.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => '1',
		];

		$settings['_services'][] = [
			'field'       => 'xmlrpc_enabled',
			'title'       => _x( 'XML-RPC', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Whether XML-RPC services are enabled on this site.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
		];

		$settings['_services'][] = [
			'field'       => 'wlw_enabled',
			'title'       => _x( 'WLW', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Whether Windows Live Writer manifest enabled on this site.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
		];

		$settings['_feeds'][] = [
			'field'       => 'feed_delay',
			'type'        => 'select',
			'title'       => _x( 'Delay Feeds', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Delays appearing published posts on the site feeds.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'none_title'  => _x( 'No Delay', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => Settings::minutesOptions(),
			'default'     => '10',
		];

		$settings['_feeds'][] = [
			'field'       => 'feed_json',
			'title'       => _x( 'JSON Feed', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Adds JSON as new type of feed that anyone can subscribe to.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'after'       => $this->options['feed_json'] ? Settings::fieldAfterLink( get_feed_link( 'json' ) ) : '',
		];

		$settings['_thrift'][] = [
			'field'       => 'disable_emojis',
			'title'       => _x( 'Emojis', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes the extra code bloat used to add support for Emoji\'s in older browsers.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => GNETWORK_DISABLE_EMOJIS,
			'after'       => Settings::fieldAfterIcon( Settings::getWPCodexLink( 'Emoji' ) ),
			'values'      => Settings::reverseEnabled(),
		];

		$settings['_theme'][] = [
			'field'       => 'page_copyright',
			'type'        => 'page',
			'title'       => _x( 'Copyright Information', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Defines an HTML meta tag as copyright manifest page for this site.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => '0',
			'exclude'     => $exclude,
			'after'       => Settings::fieldAfterNewPostType( 'page' ),
		];

		$settings['_theme'][] = [
			'field'       => 'page_404',
			'type'        => 'page',
			'title'       => _x( 'Custom 404 Error', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Displays the selected page as 404 Error page on this site.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => '0',
			'exclude'     => $exclude,
			'after'       => Settings::fieldAfterNewPostType( 'page' ),
		];

		$settings['_theme'][] = [
			'field'       => 'content_width',
			'type'        => 'number',
			'title'       => _x( 'Content Width', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Overrides content width of the active theme. Leave empty to disable.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'after'       => ! empty( $GLOBALS['content_width'] ) && ! $this->options['content_width'] ? Settings::fieldAfterText( sprintf( _x( 'Current is %s', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ), '<code>'.$GLOBALS['content_width'].'</code>' ) ) : FALSE,
		];

		$settings['_theme'][] = [
			'field'       => 'meta_revised',
			'title'       => _x( 'Meta Revised', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Defines an HTML meta tag for last modified time of each post.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
		];

		$settings['_theme'][] = [
			'field'       => 'noindex_attachments',
			'title'       => _x( 'No Index Attachments', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Defines No Index/No Follow HTML meta tags for attachment pages.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
		];

		$settings['_theme'][] = [
			'field'       => 'theme_color',
			'type'        => 'color',
			'title'       => _x( 'Theme Color', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Defines color of the mobile browser address bar. Set to override the network.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
		];

		if ( $multisite && class_exists( __NAMESPACE__.'\\Mail' ) ) {

			$settings['_email'][] = [
				'field'       => 'from_email',
				'type'        => 'email',
				'title'       => _x( 'From Email', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'This site email address that emails should be sent from. Set to override the network.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			];

			$settings['_email'][] = [
				'field'       => 'from_name',
				'type'        => 'text',
				'title'       => _x( 'From Name', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'This site email name that emails should be sent from. Set to override the network.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			];
		}

		if ( $multisite && class_exists( __NAMESPACE__.'\\Tracking' ) )
			$settings['_tracking'][] = [
				'field'       => 'ga_override',
				'type'        => 'text',
				'title'       => _x( 'GA Override', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'This site Google Analytics tracking account. Set to override the network.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'placeholder' => 'UA-XXXXX-X',
				'field_class' => [ 'regular-text', 'code-text' ],
			];

		$settings['_redirect'][] = [
			'field'       => 'blog_redirect',
			'type'        => 'url',
			'title'       => _x( 'Redirect URL', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Redirects the site to a custom URL. Leave empty to disable.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'placeholder' => 'http://anothersite.com',
		];

		$settings['_redirect'][] = [
			'field'       => 'blog_redirect_status',
			'type'        => 'select',
			'title'       => _x( 'Redirect Status Code', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'HTTP status header code for redirection of this site.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'after'       => Settings::fieldAfterIcon( 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection' ),
			'dir'         => 'ltr',
			'default'     => '301',
			'values'      => [
				'301' => '301 Moved Permanently',
				'302' => '302 Found',
				'303' => '303 See Other',
				'307' => '307 Temporary Redirect',
				'308' => '308 Permanent Redirect',
			],
		];

		$settings['_shortlinks'][] = [
			'field'       => 'shortlink_numeric',
			'title'       => _x( 'Numeric Shortlinks', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Adds support for numeric/alpha-numeric shortlinks.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
		];

		$settings['_shortlinks'][] = [
			'field'   => 'shortlink_type',
			'type'    => 'select',
			'title'   => _x( 'Shortlink Type', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			'default' => 'numeric',
			'values'  => [
				'numeric'   => sprintf( _x( 'Numeric (%s)', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ), 'example.com/123' ),
				'bijection' => sprintf( _x( 'Alpha-Numeric (%s)', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ), 'example.com/d3E' ),
			],
		];

		if ( $multisite )
			$settings['_branding'][] = [
				'field'       => 'text_copyright',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Copyright Notice', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Displays as copyright notice on the footer on the front-end. Set to override the network.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
			];

		return $settings;
	}

	protected function settings_setup( $sub = NULL )
	{
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	public function plugins_loaded()
	{
		if ( ! defined( 'AUTOSAVE_INTERVAL' ) ) {

			if ( $this->options['autosave_interval'] )
				define( 'AUTOSAVE_INTERVAL', $this->options['autosave_interval'] );

			$this->autosave_interval = TRUE;
		}
	}

	public function wp_loaded()
	{
		if ( $this->is_action( 'flushrewrite' )
			&& WordPress::cuc( 'edit_others_posts' ) ) {

			flush_rewrite_rules();

			WordPress::redirect( $this->remove_action() );
		}
	}

	public function init()
	{
		if ( $this->options['blog_redirect'] && WordPress::mustRegisterUI() )
			$this->blog_redirect();

		if ( ( $locale = $this->is_action( 'locale', 'locale' ) )
			&& class_exists( __NAMESPACE__.'\\Locale' )
			&& ( $result = Locale::changeLocale( $locale ) ) )
				WordPress::redirect( $this->remove_action( 'locale' ) );

		if ( $this->options['feed_json'] ) {

			add_feed( 'json', [ $this, 'do_feed_json' ] );

			add_filter( 'query_vars', function( $public_query_vars ){
				$public_query_vars[] = 'callback';
				$public_query_vars[] = 'limit';
				return $public_query_vars;
			} );

			add_filter( 'template_include', [ $this, 'feed_json_template_include' ] );
		}

		// originally from: Disable Emojis v1.5.1
		// @SOURCE: https://wordpress.org/plugins/disable-emojis/
		if ( $this->options['disable_emojis'] ) {

			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'embed_head', 'print_emoji_detection_script' );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );

			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

			add_filter( 'tiny_mce_plugins', function( $plugins ) {
				return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
			} );
		}

		// RSD works through xml-rpc
		if ( ! $this->options['xmlrpc_enabled'] )
			remove_action( 'wp_head', 'rsd_link' );

		if ( ! $this->options['wlw_enabled'] )
			remove_action( 'wp_head', 'wlwmanifest_link' );

		if ( $this->options['content_width'] )
			$this->set_content_width( $this->options['content_width'] );


		if ( 'disable' == $this->options['heartbeat_mode'] ) {

			$this->deregister_heartbeat();

		} else if ( 'dashboard' == $this->options['heartbeat_mode'] ) {

			if ( 'index.php' == $GLOBALS['pagenow'] )
				$this->deregister_heartbeat();

		} else if ( 'postedit' == $this->options['heartbeat_mode'] ) {

			if ( 'post.php' == $GLOBALS['pagenow']
				|| 'post-new.php' == $GLOBALS['pagenow'] )
					$this->deregister_heartbeat();
		}

		if ( 'default' != $this->options['heartbeat_frequency'] )
			add_filter( 'heartbeat_settings', function( $settings ){
				return array_merge( $settings, [ 'interval' => intval( $this->options['heartbeat_frequency'] ) ] );
			} );
	}

	private function deregister_heartbeat()
	{
		wp_deregister_script( 'heartbeat' );
		wp_register_script( 'heartbeat', NULL ); // for dependency
	}

	public function init_late()
	{
		// Search Everything http://wordpress.org/plugins/search-everything/
		remove_action( 'wp_head', 'se_global_head' );

		if ( defined( 'WPCF7_VERSION' ) ) {

			if ( defined( 'WPCF7_AUTOP' ) && WPCF7_AUTOP )
				$this->filter( 'wpcf7_form_elements' );

			$this->filter_false( 'wpcf7_load_css', 15 );
		}
	}

	private function blog_redirect( $check = TRUE )
	{
		global $pagenow;

		if ( WordPress::cuc( 'manage_options' ) )
			return;

		// postpone checking in favor of WP Remote
		if ( $check && ! empty( $_POST['wpr_verify_key'] ) )
			return $this->action( 'init', 0, 999, 'late_check' ); // must be over 100

		if ( ( ! empty( $pagenow ) && 'index.php' == $pagenow && ! is_admin() )
			|| FALSE === self::whiteListed() ) {

			$redirect = URL::untrail( $this->options['blog_redirect'] ).$_SERVER['REQUEST_URI'];
			$referer  = HTTP::referer();

			Logger::siteNOTICE( 'BLOG-REDIRECT', esc_url( $redirect ).( $referer ? ' :: '.$referer : '' ) );

			WordPress::redirect( $redirect, $this->options['blog_redirect_status'] );
		}
	}

	public function init_late_check()
	{
		// no need for redirect option check
		$this->blog_redirect( FALSE );
	}

	public static function whiteListed( $request_uri = NULL )
	{
		if ( is_null( $request_uri ) )
			$request_uri = $_SERVER['REQUEST_URI'];

		return Arraay::strposArray( [
			'wp-admin',
			'wp-activate.php',
			'wp-comments-post.php',
			'wp-cron.php',
			'wp-links-opml.php',
			'wp-login.php',
			'wp-mail.php',
			'wp-signup.php',
			'wp-trackback.php',
			'xmlrpc.php',
		], $request_uri );
	}

	public function set_content_width( $width )
	{
		global $content_width;

		if ( ! $width )
			return FALSE;

		return $content_width = intval( $width );
	}

	public function export_wp()
	{
		@set_time_limit( 0 );

		defined( 'GNETWORK_IS_WP_EXPORT' ) or define( 'GNETWORK_IS_WP_EXPORT', TRUE );
	}

	public function rest_authentication_errors( $null )
	{
		return new Error( 'rest_disabled', 'The REST API is disabled on this site.', [ 'status' => 503 ] );
	}

	public function wp_headers( $headers )
	{
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	public function wp_head()
	{
		$mainsite = is_main_site();
		$singular = is_singular();

		if ( $color = gNetwork()->option( 'theme_color', 'branding', $this->options['theme_color'] ) ) {
			echo '<meta name="theme-color" content="'.$color.'" />'."\n";
			echo '<meta name="msapplication-navbutton-color" content="'.$color.'">'."\n";
			echo '<meta name="apple-mobile-web-app-capable" content="yes">'."\n";
			echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'."\n";
		}

		if ( gNetwork()->option( 'opensearch', 'opensearch' ) )
			gNetwork()->opensearch->do_link_tag();

		if ( $mainsite && gNetwork()->option( 'webapp_manifest', 'branding' ) )
			gNetwork()->branding->do_link_tag();

		if ( $this->options['page_copyright'] )
			echo '<link rel="copyright" href="'.get_page_link( $this->options['page_copyright'] ).'" />'."\n";

		if ( $this->options['meta_revised'] && $singular )
			echo '<meta name="revised" content="'.get_post_modified_time( 'D, m M Y G:i:s', TRUE ).'" />'."\n";

		if ( $this->options['noindex_attachments'] && is_attachment() )
			echo '<meta name="robots" content="noindex,nofollow" />'."\n";

		// @REF: http://universaleditbutton.org/WordPress_plugin
		if ( $singular && ( $edit = get_edit_post_link() ) )
			echo '<link rel="alternate" type="application/x-wiki" title="'._x( 'Edit this page', 'Modules: Blog', GNETWORK_TEXTDOMAIN ).'" href="'.HTML::escapeURL( $edit ).'" />'."\n";

		if ( defined( 'GNETWORK_DISABLE_FRONT_STYLES' )
			&& GNETWORK_DISABLE_FRONT_STYLES )
				return;

		Utilities::linkStyleSheet( 'front.all' );
	}

	public function embed_head()
	{
		if ( defined( 'GNETWORK_DISABLE_FRONT_STYLES' )
			&& GNETWORK_DISABLE_FRONT_STYLES )
				return;

		Utilities::linkStyleSheet( 'embed.all' );
	}

	public function posts_where( $where, $query )
	{
		if ( $query->is_main_query()
			&& $query->is_feed() ) {

			global $wpdb;

			$now  = gmdate( 'Y-m-d H:i:s' );
			$wait = $this->options['feed_delay'];
			$unit = 'MINUTE'; // MINUTE, HOUR, DAY, WEEK, MONTH, YEAR

			$where.= " AND TIMESTAMPDIFF( {$unit}, {$wpdb->posts}.post_date_gmt, '{$now}' ) > {$wait} ";
		}

		return $where;
	}

	public function pre_get_posts( $wp_query )
	{
		$wp_query->set( 'no_found_rows', TRUE );
	}

	// uses the query parts to run a custom count(*) query against the database
	// then constructs and sets the pagination results for this wp_query
	// @REF: https://wpartisan.me/?p=166
	public function posts_clauses( $clauses, $wp_query )
	{
		global $wpdb;

		// don't proceed if it's a singular page
		if ( $wp_query->is_singular() )
			return $clauses;

		$where = isset( $clauses[ 'where' ] )    ? $clauses[ 'where' ]    : '';
		$join  = isset( $clauses[ 'join' ] )     ? $clauses[ 'join' ]     : '';
		$dist  = isset( $clauses[ 'distinct' ] ) ? $clauses[ 'distinct' ] : '';

		// construct and run the query. Set the result as the 'found_posts'
		// param on the main query we want to run
		$wp_query->found_posts = $wpdb->get_var( "SELECT {$dist} COUNT(*) FROM {$wpdb->posts} {$join} WHERE 1=1 {$where}" );

		// work out how many posts per page there should be
		$posts_per_page = empty( $wp_query->query_vars['posts_per_page'] )
			? absint( get_option( 'posts_per_page' ) )
			: absint( $wp_query->query_vars['posts_per_page'] );

		$wp_query->max_num_pages = ceil( $wp_query->found_posts / $posts_per_page );

		return $clauses;
	}

	// does not apply the `autop()` to the form content
	// ADOPTED FROM: Contact Form 7 Controls - v0.4.0 - 20170926
	// @SOURCE: https://github.com/kasparsd/contact-form-7-extras
	public function wpcf7_form_elements( $form )
	{
		$instance = \WPCF7_ContactForm::get_current();
		$manager  = \WPCF7_ShortcodeManager::get_instance();

		$form = $manager->do_shortcode( get_post_meta( $instance->id(), '_form', TRUE ) );

		$instance->set_properties( [ 'form' => $form ] );

		return $form;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// adopted from: Numeric Shortlinks v1.6.5 - 2018-01-11
/// by Kaspars Dambis : http://kaspars.net
/// @SOURCE: https://github.com/kasparsd/numeric-shortlinks

	public function template_redirect()
	{
		global $wp;

		if ( ! is_404() )
			return;

		// make sure that this is not a paginatad request
		if ( 1 !== count( explode( '/', $wp->request ) ) )
			return;

		// get the trailing part of the request URL
		$request = basename( $wp->request );

		// get the trailing part of the URI
		if ( 'bijection' == $this->options['shortlink_type'] )
			$maybe_post_id = (int) Crypto::decodeBijection( $request );
		else
			$maybe_post_id = (int) $request;

		// check if it is a valid post ID
		if ( empty( $maybe_post_id ) || ! is_numeric( $maybe_post_id ) )
			return;

		if ( $permalink = get_permalink( $maybe_post_id ) )
			WordPress::redirect( $permalink, 301 );
	}

	public function pre_get_shortlink( $return, $id, $context, $slugs )
	{
		if ( empty( $id ) && is_singular() )
			$id = get_queried_object_id();

		if ( 'bijection' == $this->options['shortlink_type'] )
			$id = Crypto::encodeBijection( $id );

		return empty( $id ) ? $return : home_url( '/'.$id );
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
/// originally based on: Custom 404 Error Page v0.2.5 - 20170925
/// @REF: https://github.com/kasparsd/custom-404-page
/// by Kaspars Dambis

	// set WP to use page template (page.php) even when returning 404
	public function custom_404_template( $template )
	{
		global $wp_query, $post;

		if ( is_404() ) {

			$page_404 = (int) $this->options['page_404'];

			// get our custom 404 post object. We need to assign
			// $post global in order to force get_post() to work
			// during page template resolving.
			$post = get_post( $page_404 );

			// populate the posts array with our 404 page object
			$wp_query->posts = [ $post ];

			// set the query object to enable support for custom page templates
			$wp_query->queried_object_id = $page_404;
			$wp_query->queried_object    = $post;

			// set post counters to avoid loop errors
			$wp_query->post_count    = 1;
			$wp_query->found_posts   = 1;
			$wp_query->max_num_pages = 0;

			// return the page.php template instead of 404.php
			return get_page_template();
		}

		return $template;
	}
}
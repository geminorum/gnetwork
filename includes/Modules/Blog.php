<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

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

		$this->filter( 'login_redirect', 3, 12 );
		$this->filter( 'logout_redirect', 3, 12 );

		if ( is_admin() ) {

			$this->action( 'export_wp', 0, 1 );

			if ( $this->options['thrift_mode'] ) {

				// add_action( 'admin_init', static function () {
				// 	// when there are a lot of terms
				// 	wp_deregister_script( 'suggest' );
				// } );

				$this->filter_true( 'disable_months_dropdown', 5 );
				$this->filter_false( 'media_library_show_audio_playlist', 5 );
				$this->filter_false( 'media_library_show_video_playlist', 5 );
				$this->filter_empty_array( 'media_library_months_with_files', 5 );
				$this->filter_empty_array( 'get_available_post_mime_types', 5 );
				$this->filter_empty_array( 'pre_get_available_post_mime_types', 5 );

				self::define( 'GEDITORIAL_THRIFT_MODE', TRUE );
			}

			if ( $this->options['disable_pointers'] )
				add_action( 'admin_init', static function () {
					remove_action( 'admin_enqueue_scripts', [ 'WP_Internal_Pointers', 'enqueue_scripts' ] );
				} );

			if ( $this->options['disable_globalstyles'] )
				add_action( 'admin_init', static function () {
					remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_global_styles_css_custom_properties' );
					remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );
				} );

		} else {

			$this->action( 'wp_head', 0, 12 );
			$this->action( 'embed_head', 0, 12 );

			if ( $this->options['noindex_attachments'] )
				add_action( 'template_redirect', static function () {

					if ( is_attachment() )
						add_filter( 'wp_robots', 'wp_robots_sensitive_page' );

				}, 9999 );

			if ( $this->options['thrift_mode'] ) {
				// @REF: https://core.trac.wordpress.org/ticket/37114
				$this->filter_empty_array( 'post_class_taxonomies', 5 );

				self::define( 'GEDITORIAL_THRIFT_MODE', TRUE );
			}

			if ( $this->options['shortlink_numeric'] ) {
				$this->action( 'template_redirect', 0, 5, 'shortlink' );
				$this->filter( 'pre_get_shortlink', 4 );
			}

			if ( $this->options['no_found_rows'] ) {
				$this->filter( 'pre_get_posts' );
				$this->filter( 'posts_clauses', 2 );
			}

			if ( $this->options['disable_globalstyles'] )
				add_action( 'init', static function () {
					remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
					remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
					remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
					remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
					remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
				}, 99 );
		}

		if ( ! $this->options['xmlrpc_enabled'] ) {
			$this->filter( 'wp_headers' );
			$this->filter_false( 'xmlrpc_enabled', 12 );
		}

		if ( $this->options['disable_privacytools'] )
			$this->_setup_disable_privacytools();

		$this->filter_empty_array( 'jetpack_get_default_modules' );

		// ADOPTED FROM: Jetpack Without Promotions v1.0.0 by required
		// @REF: https://github.com/wearerequired/hide-jetpack-promotions
		// $this->filter_false( 'can_display_jetpack_manage_notice', 20 );
		$this->filter_false( 'jetpack_just_in_time_msgs', 20 );
		$this->filter_false( 'jetpack_show_promotions', 20 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'General', 'Modules: Menu Name', 'gnetwork' ), NULL, 5 );
	}

	public function default_options()
	{
		return [
			'ssl_support'          => 0, // for non-multi-site only
			'thrift_mode'          => 0,
			'no_found_rows'        => 0,
			'admin_locale'         => '',
			'admin_chosen'         => 0,
			'blog_redirect'        => '',
			'blog_redirect_status' => '301',
			'heartbeat_mode'       => 'default',
			'heartbeat_frequency'  => 'default',
			'autosave_interval'    => '',
			'xmlrpc_enabled'       => '0',
			'wlw_enabled'          => '0',
			'page_copyright'       => '0',
			'content_width'        => '',
			'meta_revised'         => '0',
			'noindex_attachments'  => '0',
			'disable_emojis'       => '1',
			'disable_pointers'     => '1',
			'disable_globalstyles' => '1', // @REF: https://github.com/WordPress/gutenberg/issues/36834
			'disable_privacytools' => '1',
			'ga_override'          => '',
			'from_email'           => '',
			'from_name'            => '',
			'text_copyright'       => '',
			'theme_color'          => '',
			'shortlink_numeric'    => '0',
			'shortlink_type'       => 'numeric',
			'login_after_cap'      => 'edit_posts',
			'redirect_login'       => '',
			'redirect_logout'      => '',
		];
	}

	public function default_settings()
	{
		$settings  = array_fill_keys( [ '_general', '_admin', '_economics', '_services', '_front', '_login', '_overrides', '_misc' ], [] );
		$multisite = is_multisite();

		if ( ! $multisite )
			$settings['_general'][] = [
				'field'       => 'ssl_support',
				'title'       => _x( 'SSL', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => _x( 'Enables SSL tools to support the network sites.', 'Modules: Blog: Settings', 'gnetwork' ),
				'disabled'    => GNETWORK_DISABLE_SSL,
			];

		if ( class_exists( __NAMESPACE__.'\\Locale' ) )
			$settings['_admin'][] = [
				'field'       => 'admin_locale',
				'type'        => 'select',
				'title'       => _x( 'Admin Language', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => _x( 'Despite of the site language, always display admin in this locale.', 'Modules: Blog: Settings', 'gnetwork' ),
				'none_title'  => _x( 'Site Default', 'Modules: Blog: Settings', 'gnetwork' ),
				'none_value'  => '',
				'values'      => Core\Arraay::sameKey( Locale::available() ),
			];

		$settings['_admin'][] = [
			'field'       => 'admin_chosen',
			'title'       => _x( 'Admin Chosen', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Chosen is a jQuery plugin that makes long, unwieldy select boxes much more user-friendly.', 'Modules: Blog: Settings', 'gnetwork' ),
			'after'       => Settings::fieldAfterIcon( 'https://harvesthq.github.io/chosen/' ),
		];

		$settings['_economics'][] = [
			'field'       => 'thrift_mode',
			'title'       => _x( 'Thrift Mode', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Trying to make your host happy!', 'Modules: Blog: Settings', 'gnetwork' ),
		];

		$settings['_economics'][] = [
			'field'       => 'no_found_rows',
			'title'       => _x( 'No Found-Rows', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Avoids query count for paginations.', 'Modules: Blog: Settings', 'gnetwork' ),
			'after'       => Settings::fieldAfterIcon( 'https://wpartisan.me/?p=166' ),
		];

		$settings['_economics'][] = [
			'field'       => 'heartbeat_mode',
			'type'        => 'select',
			'title'       => _x( 'Heartbeat Mode', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Controls the Heartbeat API locations.', 'Modules: Blog: Settings', 'gnetwork' ),
			'values'      => [
				'default'   => _x( 'Use default', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'disable'   => _x( 'Disable everywhere', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'dashboard' => _x( 'Disable on Dashboard page', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'postedit'  => _x( 'Allow only on Post Edit pages', 'Modules: Blog: Settings: Option', 'gnetwork' ),
			],
		];

		$settings['_economics'][] = [
			'field'       => 'heartbeat_frequency',
			'type'        => 'select',
			'title'       => _x( 'Heartbeat Frequency', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Overrides the Heartbeat API frequency.', 'Modules: Blog: Settings', 'gnetwork' ),
			'values'      => [
				'default' => _x( 'Use default', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'15'      => _x( '15 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'20'      => _x( '20 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'25'      => _x( '25 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'30'      => _x( '30 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'35'      => _x( '35 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'40'      => _x( '40 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'45'      => _x( '45 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'50'      => _x( '50 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
				'60'      => _x( '60 seconds', 'Modules: Blog: Settings: Option', 'gnetwork' ),
			],
		];

		if ( $this->autosave_interval )
			$settings['_economics'][] = [
				'field'       => 'autosave_interval',
				'type'        => 'number',
				'title'       => _x( 'Autosave Interval', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => sprintf(
					/* translators: `%s`: constant placeholder */
					_x( 'Time in seconds that WordPress will save the currently editing posts. default is %s seconds.', 'Modules: Blog: Settings', 'gnetwork' ),
					Core\HTML::tag( 'code', AUTOSAVE_INTERVAL )
				),
				'min_attr'    => '20',
				'default'     => '120',
			];

		$settings['_services'][] = [
			'field'       => 'xmlrpc_enabled',
			'title'       => _x( 'XML-RPC', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Whether XML-RPC services are enabled on this site.', 'Modules: Blog: Settings', 'gnetwork' ),
		];

		$settings['_services'][] = [
			'field'       => 'wlw_enabled',
			'title'       => _x( 'WLW', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Whether Windows Live Writer manifest enabled on this site.', 'Modules: Blog: Settings', 'gnetwork' ),
		];

		$settings['_services'][] = [
			'field'       => 'disable_emojis',
			'type'        => 'disabled',
			'title'       => _x( 'Emojis', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Removes the extra code bloat used to add support for Emoji\'s in older browsers.', 'Modules: Blog: Settings', 'gnetwork' ),
			'after'       => Settings::fieldAfterIcon( 'https://wordpress.org/support/article/emoji/' ),
			'default'     => '1',
		];

		$settings['_services'][] = [
			'field'       => 'disable_privacytools',
			'type'        => 'disabled',
			'title'       => _x( 'Privacy Tools', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Disables core\'s privacy tools including tools for exporting\/erasing personal data.', 'Modules: Blog: Settings', 'gnetwork' ),
			'default'     => '1',
		];

		$settings['_admin'][] = [
			'field'       => 'disable_pointers',
			'type'        => 'disabled',
			'title'       => _x( 'Admin Pointers', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Removes all admin pointer tooltips.', 'Modules: Blog: Settings', 'gnetwork' ),
			'default'     => '1',
		];

		$settings['_front'][] = [
			'field'       => 'disable_globalstyles',
			'type'        => 'disabled',
			'title'       => _x( 'Global Styles', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Removes all admin pointer tooltips.', 'Modules: Blog: Settings', 'gnetwork' ),
			'after'       => Settings::fieldAfterIcon( 'https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-json/#presets' ),
			'default'     => '1',
		];

		$settings['_front'][] = [
			'field'       => 'page_copyright',
			'type'        => 'page',
			'title'       => _x( 'Copyright Information', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Defines an HTML meta tag as copyright manifest page for this site.', 'Modules: Blog: Settings', 'gnetwork' ),
			'default'     => '0',
			'exclude'     => Settings::getPageExcludes(),
			'after'       => Settings::fieldAfterNewPostType( 'page' ),
		];

		$settings['_front'][] = [
			'field'       => 'content_width',
			'type'        => 'number',
			'title'       => _x( 'Content Width', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Overrides content width of the active theme. Leave empty to disable.', 'Modules: Blog: Settings', 'gnetwork' ),
			'after'       => ! empty( $GLOBALS['content_width'] ) && ! $this->options['content_width'] ? Settings::fieldAfterText( sprintf(
				/* translators: `%s`: content width placeholder */
				_x( 'Current is %s', 'Modules: Blog: Settings', 'gnetwork' ),
				Core\HTML::tag( 'code', $GLOBALS['content_width'] )
			) ) : FALSE,
		];

		$settings['_front'][] = [
			'field'       => 'meta_revised',
			'title'       => _x( 'Meta Revised', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Defines an HTML meta tag for last modified time of each post.', 'Modules: Blog: Settings', 'gnetwork' ),
		];

		$settings['_front'][] = [
			'field'       => 'noindex_attachments',
			'title'       => _x( 'No Index Attachments', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Defines No Index/No Follow HTML meta tags for attachment pages.', 'Modules: Blog: Settings', 'gnetwork' ),
		];

		$settings['_front'][] = [
			'field'       => 'theme_color',
			'type'        => 'color',
			'title'       => _x( 'Theme Color', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Defines color of the mobile browser address bar. Set to override the network.', 'Modules: Blog: Settings', 'gnetwork' ),
		];

		if ( $multisite && class_exists( __NAMESPACE__.'\\Mail' ) ) {

			$settings['_overrides'][] = [
				'field'       => 'from_email',
				'type'        => 'email',
				'title'       => _x( 'From Email', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => _x( 'This site email address that emails should be sent from. Set to override the network.', 'Modules: Blog: Settings', 'gnetwork' ),
			];

			$settings['_overrides'][] = [
				'field'       => 'from_name',
				'type'        => 'text',
				'title'       => _x( 'From Name', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => _x( 'This site email name that emails should be sent from. Set to override the network.', 'Modules: Blog: Settings', 'gnetwork' ),
			];
		}

		if ( $multisite && class_exists( __NAMESPACE__.'\\Tracking' ) )
			$settings['_overrides'][] = [
				'field'       => 'ga_override',
				'type'        => 'text',
				'title'       => _x( 'GA Override', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => _x( 'This site Google Analytics tracking account. Set to override the network.', 'Modules: Blog: Settings', 'gnetwork' ),
				'placeholder' => 'UA-XXXXXXXX-X',
				'field_class' => [ 'regular-text', 'code-text' ],
			];

		$settings['_misc'][] = [
			'field'       => 'blog_redirect',
			'type'        => 'url',
			'title'       => _x( 'Redirect URL', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Redirects the site to a custom URL. Leave empty to disable.', 'Modules: Blog: Settings', 'gnetwork' ),
			'placeholder' => 'https://anothersite.com',
		];

		$settings['_misc'][] = [
			'field'       => 'blog_redirect_status',
			'type'        => 'select',
			'title'       => _x( 'Redirect Status Code', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'HTTP status header code for redirection of this site.', 'Modules: Blog: Settings', 'gnetwork' ),
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

		$settings['_misc'][] = [
			'field'       => 'shortlink_numeric',
			'title'       => _x( 'Numeric Shortlinks', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Adds support for numeric/alpha-numeric shortlinks.', 'Modules: Blog: Settings', 'gnetwork' ),
		];

		$settings['_misc'][] = [
			'field'   => 'shortlink_type',
			'type'    => 'select',
			'title'   => _x( 'Shortlink Type', 'Modules: Blog: Settings', 'gnetwork' ),
			'default' => 'numeric',
			'values'  => [
				'numeric'   => sprintf(
					/* translators: `%s`: short-link type placeholder */
					_x( 'Numeric (%s)', 'Modules: Blog: Settings', 'gnetwork' ),
					Core\URL::home( '123' )
				),
				'bijection' => sprintf(
					/* translators: `%s`: short-link type placeholder */
					_x( 'Alpha-Numeric (%s)', 'Modules: Blog: Settings', 'gnetwork' ),
					Core\URL::home( 'd3E' )
				),
			],
		];

		$settings['_login'][] = [
			'field'       => 'login_after_cap',
			'type'        => 'cap',
			'title'       => _x( 'Log-in to Admin', 'Modules: Blog: Settings', 'gnetwork' ),
			'description' => _x( 'Selected and above will redirect after successful log-in to admin.', 'Modules: Blog: Settings', 'gnetwork' ),
			'default'     => 'edit_posts',
		];

		if ( $multisite ) {

			$settings['_overrides'][] = [
				'field'       => 'text_copyright',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Copyright Notice', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => _x( 'Displays as copyright notice on the footer on the front-end. Set to override the network.', 'Modules: Blog: Settings', 'gnetwork' ),
			];

			$settings['_login'][] = [
				'field'       => 'redirect_login',
				'type'        => 'url',
				'title'       => _x( 'Log-in After', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => _x( 'Full URL to redirect after successful log-in. Leave empty to use the home. Set to override the network.', 'Modules: Blog: Settings', 'gnetwork' ),
			];

			$settings['_login'][] = [
				'field'       => 'redirect_logout',
				'type'        => 'url',
				'title'       => _x( 'Log-out After', 'Modules: Blog: Settings', 'gnetwork' ),
				'description' => _x( 'Full URL to redirect after compelete log-out. Leave empty to use the home. Set to override the network.', 'Modules: Blog: Settings', 'gnetwork' ),
			];
		}

		return $settings;
	}

	protected function settings_setup( $sub = NULL )
	{
		Scripts::enqueueColorPicker();
	}

	public function plugins_loaded()
	{
		if ( ! GNETWORK_DISABLE_SSL )
			force_ssl_admin( gNetwork()->ssl() );

		if ( ! defined( 'AUTOSAVE_INTERVAL' ) ) {

			if ( $this->options['autosave_interval'] )
				define( 'AUTOSAVE_INTERVAL', $this->options['autosave_interval'] );

			$this->autosave_interval = TRUE;
		}
	}

	public function wp_loaded()
	{
		if ( $this->is_request_action( 'flushrewrite' )
			&& WordPress\User::cuc( 'edit_others_posts' ) ) {

			gNetwork()->rewrite->flush_rewrite_rules();

			WordPress\Redirect::doWP( $this->remove_request_action() );
		}
	}

	public function init()
	{
		if ( $this->options['blog_redirect'] && WordPress\Screen::mustRegisterUI() )
			$this->blog_redirect();

		if ( ( $locale = $this->is_request_action( 'locale', 'locale' ) )
			&& class_exists( __NAMESPACE__.'\\Locale' )
			&& ( $result = Locale::changeLocale( $locale ) ) )
				WordPress\Redirect::doWP( $this->remove_request_action( 'locale' ) );

		if ( gNetwork()->ssl() ) {

			$this->action( 'wp', 0, 40 );
			$this->filter( 'wp_headers', 1, 9, 'ssl' );
			$this->action( 'wp_print_scripts' );
			// $this->action( 'rest_api_init', 0, -999 ); // WTF: redirects in `POST` is discouraged
			$this->filter( 'wp_get_attachment_url', 2, -999 );
			$this->filter_true( 'wp_should_replace_insecure_home_url' );

		} else {

			$this->filter_false( 'wp_should_replace_insecure_home_url' );
			$this->filter_false( 'https_local_ssl_verify' );
		}

		// originally from: Disable Emojis v1.7.2 - 2018-10-03
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

			add_filter( 'tiny_mce_plugins', static function ( $plugins ) {
				return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
			} );

			add_filter( 'wp_resource_hints', static function ( $urls, $relation_type ) {

				if ( 'dns-prefetch' !== $relation_type )
					return $urls;

				if ( $filtered = apply_filters( 'emoji_svg_url', FALSE ) )
					$urls = array_diff( $urls, [ $filtered ] );

				foreach ( $urls as $key => $url )
					// Strip out any URLs referencing the WordPress.org emoji location
					if ( Core\Text::has( $url, 'https://s.w.org/images/core/emoji/' ) )
						unset( $urls[$key] );

				return $urls;
			}, 10, 2 );
		}

		if ( $this->options['content_width'] )
			$this->set_content_width( $this->options['content_width'] );

		if ( is_admin() ) {

			if ( 'disable' == $this->options['heartbeat_mode'] ) {

				$this->deregister_heartbeat();

			} else if ( 'dashboard' == $this->options['heartbeat_mode'] ) {

				if ( WordPress\Screen::pageNow( 'index.php' ) )
					$this->deregister_heartbeat();

			} else if ( 'postedit' == $this->options['heartbeat_mode'] ) {

				if ( in_array( WordPress\Screen::pageNow(), [ 'post.php', 'post-new.php' ] ) )
					$this->deregister_heartbeat();
			}

			if ( 'default' != $this->options['heartbeat_frequency'] )
				add_filter( 'heartbeat_settings', function ( $settings ) {
					return array_merge( $settings, [ 'interval' => (int) $this->options['heartbeat_frequency'] ] );
				} );

		} else {

			// `RSD` works through `xml-rpc`
			if ( ! $this->options['xmlrpc_enabled'] )
				remove_action( 'wp_head', 'rsd_link' );

			if ( ! $this->options['wlw_enabled'] )
				remove_action( 'wp_head', 'wlwmanifest_link' ); // @since WP6.3.0 removed from core
		}
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
		if ( WordPress\User::cuc( 'manage_options' ) )
			return;

		// Postpone checking in favor of WP Remote: https://app.maek.it/remote
		if ( $check && ! empty( $_POST['wpr_verify_key'] ) )
			return $this->action( 'init', 0, 999, 'late_check' ); // must be over 100

		if ( ( WordPress\Screen::pageNow( 'index.php' ) && ! is_admin() ) || FALSE === self::whiteListed() ) {

			$redirect = Core\URL::untrail( $this->options['blog_redirect'] ).$_SERVER['REQUEST_URI'];
			$referer  = Core\HTTP::referer();

			Logger::siteNOTICE( 'BLOG-REDIRECT', esc_url( $redirect ).( $referer ? ' :: '.$referer : '' ) );

			WordPress\Redirect::doWP( $redirect, $this->options['blog_redirect_status'] );
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

		return Core\Arraay::strposArray( [
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

	public function login_redirect( $redirect_to, $requested_redirect_to, $user )
	{
		if ( ! empty( $GLOBALS['interim_login'] ) )
			return $redirect_to;

		if ( $redirect_to && GNETWORK_REDIRECT_404_URL
			&& GNETWORK_REDIRECT_404_URL === Core\URL::untrail( $redirect_to ) )
			$redirect_to = ''; // reset the redirect

		if ( WordPress\IsIt::ajax() )
			return $redirect_to;

		if ( self::isError( $user ) )
			return $redirect_to;

		if ( ! empty( $requested_redirect_to ) )
			return $redirect_to;

		if ( $this->options['redirect_login'] )
			return $this->options['redirect_login'];

		if ( $custom = gNetwork()->option( 'redirect_login', 'login' ) )
			return $custom;

		if ( $user->has_cap( $this->options['login_after_cap'] ) )
			return get_admin_url();

		return get_home_url();
	}

	public function logout_redirect( $redirect_to, $requested_redirect_to, $user )
	{
		if ( ! empty( $requested_redirect_to ) )
			return $requested_redirect_to;

		if ( $this->options['redirect_logout'] )
			return $this->options['redirect_logout'];

		if ( $custom = gNetwork()->option( 'redirect_logout', 'login' ) )
			return $custom;

		return get_home_url();
	}

	public function set_content_width( $width )
	{
		global $content_width;

		if ( ! $width )
			return FALSE;

		return $content_width = (int) $width;
	}

	public function export_wp()
	{
		@set_time_limit( 0 );

		self::define( 'GNETWORK_IS_WP_EXPORT', TRUE );

		$this->filter( 'export_wp_filename', 3, 12 );
		$this->filter( 'wxr_export_skip_postmeta', 3, 12 );
	}

	public function export_wp_filename( $filename, $sitename, $date )
	{
		return Core\File::prepName( sprintf( '%s.xml', self::req( 'content', 'all' ) ) );
	}

	public function wxr_export_skip_postmeta( $skip, $metakey, $metaobject )
	{
		return in_array( $metakey, [
			'_edit_last',
			'_edit_lock',
			'_wp_old_date',
			'_wp_old_slug',
			'_pingme',
			'_encloseme',
			'_trackbackme',
		], TRUE ) ? TRUE : $skip;
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

		Core\Third::htmlThemeColor( $this->options['theme_color'] ?: gNetwork()->option( 'theme_color', 'branding' ) );

		if ( gNetwork()->option( 'opensearch', 'opensearch' ) )
			gNetwork()->opensearch->do_link_tag();

		if ( $mainsite && gNetwork()->option( 'webapp_manifest', 'branding' ) )
			gNetwork()->branding->do_link_tag();

		if ( $this->options['page_copyright'] )
			echo '<link rel="copyright" href="'.get_page_link( $this->options['page_copyright'] ).'" />'."\n";

		if ( $this->options['meta_revised'] && $singular )
			echo '<meta name="revised" content="'.get_post_modified_time( 'D, m M Y G:i:s', TRUE ).'" />'."\n";

		// @REF: http://universaleditbutton.org/WordPress_plugin
		if ( $singular && ( $edit = get_edit_post_link() ) )
			echo '<link rel="alternate" type="application/x-wiki" title="'._x( 'Edit this page', 'Modules: Blog', 'gnetwork' ).'" href="'.Core\HTML::escapeURL( $edit ).'" />'."\n";

		if ( is_admin_bar_showing() ) {
			Utilities::linkStyleSheet( 'adminbar.all' );

			if ( gNetwork()->option( 'adminbar_styles', 'branding' ) )
				gNetwork()->branding->do_adminbar_styles();

			else
				Utilities::customStyleSheet( 'adminbar.css' );
		}

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

	// NOTE: redirects in `POST` is discouraged
	// FIXME: DISABLED/DROP THIS
	public function rest_api_init()
	{
		if ( ! empty( $_SERVER['HTTP_HOST'] ) && ! WordPress\IsIt::ssl() )
			WordPress\Redirect::doWP( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 301 );
	}

	public function wp()
	{
		if ( ! empty( $_SERVER['HTTP_HOST'] ) && ! WordPress\IsIt::ssl() )
			WordPress\Redirect::doWP( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 301 );
	}

	/**
	 * Adds an HSTS header to the response.
	 * @source https://github.com/GoogleChromeLabs/pwa-wp/wiki/HTTPS
	 *
	 * HSTS header (HTTP Strict-Transport-Security) indicates to the browser
	 * to only load the site with HTTPS, not HTTP.
	 * @ref https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security
	 *
	 * @param array $headers The headers to filter.
	 * @return array $headers The filtered headers.
	 */
	public function wp_headers_ssl( $headers )
	{
		$headers['Strict-Transport-Security'] = 'max-age=3600'; // Or another max-age.
		return $headers;
	}

	public function wp_print_scripts()
	{
		echo "<script>if(document.location.protocol!='https:'){document.location=document.URL.replace(/^http:/i,'https:');}</script>";
	}

	public function wp_get_attachment_url( $url, $post_ID )
	{
		return str_replace( 'http://', 'https://', $url );
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
	// ADOPTED FROM: Contact Form 7 Controls - v0.6.1 - 2018-10-15
	// @SOURCE: https://github.com/kasparsd/contact-form-7-extras
	public function wpcf7_form_elements( $form )
	{
		$instance = \WPCF7_ContactForm::get_current();
		$manager  = \WPCF7_FormTagsManager::get_instance();

		$form = $manager->replace_all( get_post_meta( $instance->id(), '_form', TRUE ) );

		$instance->set_properties( [ 'form' => $form ] );

		return $form;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// adopted from: Numeric Shortlinks v1.6.5 - 2018-01-11
/// by Kaspars Dambis : http://kaspars.net
/// @SOURCE: https://github.com/kasparsd/numeric-shortlinks

	public function template_redirect_shortlink()
	{
		global $wp;

		if ( ! is_404() )
			return;

		// Make sure that this is not a paginated request
		if ( 1 !== count( explode( '/', $wp->request ) ) )
			return;

		// Get the trailing part of the request URL
		$request = Core\File::basename( $wp->request );

		// Check if request not encoded
		if ( $request != urldecode( $request ) )
			return;

		if ( 'bijection' == $this->options['shortlink_type'] )
			$maybe_post_id = (int) Core\Crypto::decodeBijection( $request );
		else
			$maybe_post_id = (int) $request;

		if ( empty( $maybe_post_id ) || ! is_numeric( $maybe_post_id ) )
			return;

		if ( $permalink = get_permalink( $maybe_post_id ) )
			WordPress\Redirect::doWP( $permalink, 301 );
	}

	public function pre_get_shortlink( $return, $id, $context, $slugs )
	{
		if ( empty( $id ) && is_singular() )
			$id = get_queried_object_id();

		if ( 'bijection' == $this->options['shortlink_type'] )
			$id = Core\Crypto::encodeBijection( $id );

		return empty( $id ) ? $return : home_url( '/'.$id );
	}

	/**
	 * Adopted from: `Disable Privacy Tools` by Dominik Schilling v1.1
	 * @source https://github.com/ocean90/wp-disable-privacy-tools
	 */
	private function _setup_disable_privacytools()
	{
		if ( ! self::const( 'GNETWORK_DISABLE_PRIVACY_TOOLS', TRUE ) )
			return;

		// Short circuits the option for the privacy policy page to always return 0.
		// The option is used by `get_privacy_policy_url()` among others.
		add_filter( 'pre_option_wp_page_for_privacy_policy', '__return_zero' );

		// Removes the default scheduled event used to delete old export files.
		remove_action( 'init', 'wp_schedule_delete_old_privacy_export_files' );

		// Removes the hook attached to the default scheduled event for removing old export files.
		remove_action( 'wp_privacy_delete_old_export_files', 'wp_privacy_delete_old_export_files' );

		$this->filter( 'map_meta_cap', 2, 12, 'disable_privacytools' );
	}

	/**
	 * Removes required user's capabilities for core privacy tools by adding the
	 * `do_not_allow` capability.
	 *
	 *  - Disables the feature pointer.
	 *  - Removes the Privacy and Export/Erase Personal Data admin menu items.
	 *  - Disables the privacy policy guide and update bubbles.
	 *
	 * @param string[] $caps Array of the user's capabilities.
	 * @param string $cap Capability name.
	 * @return string[] Array of the user's capabilities.
	 */
	public function map_meta_cap_disable_privacytools( $caps, $cap )
	{
		switch ( $cap ) {
			case 'export_others_personal_data':
			case 'erase_others_personal_data':
			case 'manage_privacy_options':
				$caps[] = 'do_not_allow';
				break;
		}

		return $caps;
	}
}

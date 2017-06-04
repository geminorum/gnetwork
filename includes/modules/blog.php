<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Blog extends gNetwork\Module
{

	protected $key     = 'general';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 1 );
		$this->action( 'wp_loaded', 0, 99 );

		if ( is_admin() ) {

			$this->action( 'export_wp', 0, 1 );

		} else {

			if ( $this->options['page_copyright']
				|| $this->options['noindex_attachments']
				|| $this->options['meta_revised'] )
					$this->action( 'wp_head' );

			if ( $this->options['page_404'] )
				add_filter( '404_template', [ $this, 'custom_404_template' ] );

			if ( $this->options['feed_delay'] )
				$this->filter( 'posts_where', 2 );
		}

		if ( ! $this->options['rest_api_enabled'] )
			$this->filter( 'rest_authentication_errors', 1, 999 );

		if ( ! $this->options['xmlrpc_enabled'] )
			add_filter( 'xmlrpc_enabled', '__return_false', 12 );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'General', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'admin_locale'         => '',
			'blog_redirect'        => '',
			'blog_redirect_status' => '301',
			'rest_api_enabled'     => '0',
			'xmlrpc_enabled'       => '0',
			'wlw_enabled'          => '0',
			'page_copyright'       => '0',
			'page_404'             => '0',
			'meta_revised'         => '0',
			'noindex_attachments'  => '0',
			'feed_json'            => '0',
			'feed_delay'           => '10',
			'disable_emojis'       => GNETWORK_DISABLE_EMOJIS,
			'ga_override'          => '',
			'from_email'           => '',
			'from_name'            => '',
		];
	}

	public function default_settings()
	{
		$exclude = array_filter( [
			get_option( 'page_on_front' ),
			get_option( 'page_for_posts' ),
		] );

		$settings = [
			'_general' => [
				[
					'field'       => 'blog_redirect',
					'type'        => 'url',
					'title'       => _x( 'Redirect', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'The Site Will Redirect to This URL', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'regular-text', 'url-text' ],
					'placeholder' => 'http://example.com',
				],
				[
					'field'       => 'blog_redirect_status',
					'type'        => 'select',
					'title'       => _x( 'Redirect Status Code', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'HTTP Status Header Code', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
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
				],
				[
					'field'       => 'rest_api_enabled',
					'title'       => _x( 'Rest API', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether REST API Services Are Enabled on This Site', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'xmlrpc_enabled',
					'title'       => _x( 'XML-RPC', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether XML-RPC Services Are Enabled on This Site', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'wlw_enabled',
					'title'       => _x( 'WLW', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether Windows Live Writer manifest enabled for this site.', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'feed_json',
					'title'       => _x( 'JSON Feed', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds JSON as New Type of Feed You Can Subscribe To', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => $this->options['feed_json'] ? Settings::fieldAfterLink( get_feed_link( 'json' ) ) : '',
				],
				[
					'field'       => 'disable_emojis',
					'title'       => _x( 'Emojis', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Removes the Extra Code Bloat Used to Add Support for Emoji\'s in Older Browsers', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => GNETWORK_DISABLE_EMOJIS,
					'after'       => Settings::fieldAfterIcon( Settings::getWPCodexLink( 'Emoji' ) ),
					'values'      => Settings::reverseEnabled(),
				],
				[
					'field'       => 'page_copyright',
					'type'        => 'page',
					'title'       => _x( 'Page for Copyright', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Set Any Page to Be Used as Copyright Page on Html Head', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => Settings::fieldAfterNewPostType( 'page' ),
				],
				[
					'field'       => 'page_404',
					'type'        => 'page',
					'title'       => _x( 'Page for 404 Error', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Set Any Page to Be Used as the 404 Error Page', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => Settings::fieldAfterNewPostType( 'page' ),
				],
				[
					'field'       => 'meta_revised',
					'title'       => _x( 'Meta Revised', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'HTML Revised Meta Tags for Posts', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'noindex_attachments',
					'title'       => _x( 'No Index Attachments', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'No Index/No Follow Meta Tags for Attachments', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'feed_delay',
					'type'        => 'select',
					'title'       => _x( 'Delay Feeds', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Delay published posts on feeds', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'none_title'  => _x( 'No Delay', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Settings::minutesOptions(),
					'default'     => '10',
				],
			],
		];

		if ( class_exists( __NAMESPACE__.'\\Mail' ) && is_multisite() ) {
			$settings['_email'] = [
				[
					'field'       => 'from_email',
					'type'        => 'text',
					'title'       => _x( 'From Email', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This Blog Email Address That Emails Should Be Sent From. Set to Override the Network', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'regular-text', 'email-text' ],
				],
				[
					'field'       => 'from_name',
					'type'        => 'text',
					'title'       => _x( 'From Name', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This Blog Email Name That Emails Should Be Sent From. Set to Override the Network', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
				],
			];
		}

		if ( class_exists( __NAMESPACE__.'\\Tracking' ) && is_multisite() )
			$settings['_tracking'] = [
				[
					'field'       => 'ga_override',
					'type'        => 'text',
					'title'       => _x( 'GA Override', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This Blog Google Analytics Account. Set to Override the Network', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => 'UA-XXXXX-X',
					'field_class' => [ 'regular-text', 'code-text' ],
				],
			];

		if ( class_exists( __NAMESPACE__.'\\Locale' ) )
			$settings['_locale'] = [
				[
					'field'       => 'admin_locale',
					'type'        => 'select',
					'title'       => _x( 'Admin Language', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Despite of the Site Language, Always Display Admin in This Locale', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'none_title'  => _x( 'Site Default', 'Modules: Blog: Settings', GNETWORK_TEXTDOMAIN ),
					'none_value'  => '',
					'values'      => Arraay::sameKey( Locale::available() ),
				],
			];

		return $settings;
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
		if ( $this->options['blog_redirect']
			&& ! is_admin()
			&& ! WordPress::isAJAX() )
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
		if ( $check && ! empty( $_POST['wpr_verify_key'] ) )
			return $this->action( 'init', 0, 999, 'late' ); // must be over 100

		if ( ( ! empty( $pagenow ) && 'index.php' == $pagenow && ! is_admin() )
			|| FALSE === self::whiteListed() ) {

			$redirect = URL::untrail( $this->options['blog_redirect'] ).$_SERVER['REQUEST_URI'];

			Logger::NOTICE( 'BLOG-REDIRECT: '.esc_url( $redirect ) );

			WordPress::redirect( $redirect, $this->options['blog_redirect_status'] );
		}
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

	public function export_wp()
	{
		@set_time_limit( 0 );

		defined( 'GNETWORK_IS_WP_EXPORT' ) or define( 'GNETWORK_IS_WP_EXPORT', TRUE );
	}

	public function rest_authentication_errors( $null )
	{
		return new Error( 'rest_disabled', 'The REST API is disabled on this site.', [ 'status' => 503 ] );
	}

	public function wp_head()
	{
		if ( $this->options['page_copyright'] )
			echo "\t".'<link rel="copyright" href="'.get_page_link( $this->options['page_copyright'] ).'" />'."\n";

		if ( $this->options['meta_revised'] && is_singular() )
			echo "\t".'<meta name="revised" content="'.get_post_modified_time( 'D, m M Y G:i:s', TRUE ).'" />'."\n";

		if ( $this->options['noindex_attachments'] && is_attachment() )
			echo "\t".'<meta name="robots" content="noindex,nofollow" />'."\n";
	}

	public function posts_where( $where, $query )
	{
		if ( $query->is_main_query()
			&& $query->is_feed() ) {

			global $wpdb;

			$now  = gmdate( 'Y-m-d H:i:s' );
			$wait = $this->options['feed_delay'];
			$unit = 'MINUTE'; // MINUTE, HOUR, DAY, WEEK, MONTH, YEAR

			$where .= " AND TIMESTAMPDIFF( {$unit}, {$wpdb->posts}.post_date_gmt, '{$now}' ) > {$wait} ";
		}

		return $where;
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
			$wp_query->posts = [ $post ];

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

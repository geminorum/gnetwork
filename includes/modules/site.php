<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;

class Site extends gNetwork\Module
{

	protected $key = 'general';

	protected function setup_actions()
	{
		if ( ! is_multisite() )
			return FALSE;

		if ( GNETWORK_LARGE_NETWORK_IS ) {
			$this->filter( 'wp_is_large_network', 3, 9 );
			// $this->filter( 'wp_is_large_user_count', 2, 9 );
		}

		$this->action( 'wpmu_new_blog', 6, 12 );

		if ( is_admin() ) {

			$this->action( 'admin_menu' );

		} else {

			$this->action( 'get_header' );
		}
	}

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'Global', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'admin_locale'      => 'en_US',
			'access_denied'     => '',
			'denied_message'    => '',
			'denied_extra'      => '',
			'list_sites'        => '1',
			'lookup_ip_service' => 'http://freegeoip.net/?q=%s',
		];
	}

	public function default_settings()
	{
		$settings = [];

		if ( class_exists( __NAMESPACE__.'\\Locale' ) ) {
			$settings['_locale'] = [
				[
					'field'       => 'admin_locale',
					'type'        => 'select',
					'title'       => _x( 'Network Locale', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Overrides network admin language, despite of the main site locale.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'en_US',
					'values'      => Arraay::sameKey( Locale::available() ),
				],
			];
		}

		if ( is_multisite() ) {

			$settings['_denied'] = [
				[
					'field'       => 'access_denied',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Access Denied', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays when access to an admin page is denied. Leave empty to use default or <code>0</code> to disable.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => __( 'Sorry, you are not allowed to access this page.' ),
				],
				[
					'field'       => 'denied_message',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Denied Message', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays this message when a user tries to view a site\'s dashboard they do not have access to. Leave empty to use default or <code>0</code> to disable.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => __( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Blog Name' ),
					'field_class' => [ 'large-text' ],
				],
				[
					'field'       => 'denied_extra',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Extra Message', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays this message before the list of sites. Leave empty to use default or <code>0</code> to disable.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => __( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.' ),
				],
				[
					'field'       => 'list_sites',
					'title'       => _x( 'List of Sites', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays the current user list of sites after access denied message.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
			];
		}

		$settings['_misc'] = [
			[
				'field'       => 'lookup_ip_service',
				'type'        => 'text',
				'title'       => _x( 'Lookup IP URL', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'URL template to to use for looking up IP adresses. Will replace <code>%s</code> with the IP.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => 'http://freegeoip.net/?q=%s',
				'dir'         => 'ltr',
				'after'       => $this->options['lookup_ip_service'] ? Settings::fieldAfterLink( sprintf( $this->options['lookup_ip_service'], HTTP::IP() ) ) : '',
			],
		];

		return $settings;
	}

	public function settings_section_denied()
	{
		Settings::fieldSection( _x( 'Dashboard Access', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ) );
	}

	public function admin_menu()
	{
		remove_action( 'admin_page_access_denied', '_access_denied_splash', 99 );
		$this->action( 'admin_page_access_denied' );
	}

	// @REF: `_access_denied_splash()`
	public function admin_page_access_denied()
	{
		$access_denied = $this->default_option( 'access_denied',
			__( 'Sorry, you are not allowed to access this page.' ) );

		if ( ! $user_id = get_current_user_id() )
			wp_die( $access_denied, 403 );

		// getting lighter list of blogs
		$blogs = $this->options['list_sites']
			? get_blogs_of_user( $user_id )
			: WordPress::getUserBlogs( $user_id, $GLOBALS['wpdb']->base_prefix );

		// this will override default message
		if ( wp_list_filter( $blogs, [ 'userblog_id' => get_current_blog_id() ] ) )
			wp_die( $access_denied, 403 );

		$message = $this->default_option( 'denied_message',
			__( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ) );

		if ( $message )
			$message = Text::autoP( sprintf( $message, get_bloginfo( 'name' ) ) );

		if ( empty( $blogs ) )
			wp_die( $message, 403 );

		$extra = $this->default_option( 'denied_extra',
			__( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.' ) );

		if ( $extra )
			$message.= Text::autoP( $extra );

		if ( $this->options['list_sites'] )
			$message.= self::tableUserSites( $blogs );

		wp_die( $message, 403 );
	}

	// FIXME: customize the list
	public static function tableUserSites( $blogs, $title = NULL )
	{
		$html = '';

		if ( is_null( $title ) )
			$html.= '<h3>'._x( 'Your Sites', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ).'</h3>';

		else if ( $title )
			$html.= $title;

		$html.= '<table>';

		foreach ( $blogs as $blog ) {
			$html.= '<tr><td>'.$blog->blogname.'</td><td>';
			$html.= HTML::link( _x( 'Visit Dashboard', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ), get_admin_url( $blog->userblog_id ) );
			$html.= ' | ';
			$html.= HTML::link( _x( 'View Site', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ), $blog->siteurl );
			$html.= '</td></tr>';
		}

		return $html.'</table>';
	}

	// TODO: on signup form: http://stackoverflow.com/a/10372861
	public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $network_id, $meta )
	{
		switch_to_blog( $blog_id );

		if ( $site_user_id = gNetwork()->user() )
			add_user_to_blog( $blog_id, $site_user_id, gNetwork()->option( 'site_user_role', 'user', 'editor' ) );

		$new_blog_options = $this->filters( 'new_blog_options', [
			'blogdescription'        => '',
			'permalink_structure'    => '/entries/%post_id%',
			'default_comment_status' => 'closed',
			'default_ping_status'    => 'closed',
			'use_smilies'            => FALSE,
			'comments_notify'        => FALSE,
			'moderation_notify'      => FALSE,
			// 'admin_email'            => get_site_option( 'admin_email' ), // FIXME: only when created by super admin
		] );

		foreach ( $new_blog_options as $new_blog_option_key => $new_blog_option )
			update_option( $new_blog_option_key, $new_blog_option );

		$new_post_content = $this->filters( 'new_post_content', _x( '[ This page is being completed ]', 'Modules: Site:‌ Initial Page Content', GNETWORK_TEXTDOMAIN ) );

		wp_update_post( [ 'ID' => 1, 'post_status' => 'draft', 'post_title' => '', 'post_name' => '', 'post_content' => $new_post_content, 'post_type' => 'page' ] );
		wp_update_post( [ 'ID' => 2, 'post_status' => 'draft', 'post_title' => '', 'post_name' => '', 'post_content' => $new_post_content ] );
		wp_set_comment_status( 1, 'trash' );

		$new_blog_plugins = $this->filters( 'new_blog_plugins', [
			'geditorial/geditorial.php'     => TRUE,
			'gpersiandate/gpersiandate.php' => TRUE,
		] );

		foreach ( $new_blog_plugins as $new_blog_plugin => $new_blog_plugin_silent )
			activate_plugin( $new_blog_plugin, '', FALSE, $new_blog_plugin_silent );

		restore_current_blog();
		clean_blog_cache( $blog_id );
	}

	public function wp_is_large_network( $is, $using, $count )
	{
		if ( 'users' == $using )
			return $count > GNETWORK_LARGE_NETWORK_IS;

		return $is;
	}

	// @SINCE: WP 4.9.0
	public function wp_is_large_user_count( $large, $count )
	{
		return $count > GNETWORK_LARGE_NETWORK_IS;
	}

	public function get_header( $name )
	{
		$disable_styles = defined( 'GNETWORK_DISABLE_FRONT_STYLES' ) && GNETWORK_DISABLE_FRONT_STYLES;

		if ( 'wp-signup' == $name ) {

			remove_action( 'wp_head', 'wpmu_signup_stylesheet' );

			if ( ! $disable_styles )
				add_action( 'wp_head', function(){
					Utilities::linkStyleSheet( 'signup.all' );
				} );

		} else if ( 'wp-activate' == $name ) {

			remove_action( 'wp_head', 'wpmu_activate_stylesheet' );

			if ( ! $disable_styles )
				add_action( 'wp_head', function(){
					Utilities::linkStyleSheet( 'activate.all' );
				} );
		}
	}
}

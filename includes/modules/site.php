<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Site extends ModuleCore
{

	protected $key = 'general';

	protected function setup_actions()
	{
		if ( ! is_admin() ) {

			if ( $this->options['page_signup'] )
				$this->action( 'before_signup_header', 0, 1 );
		}

		if ( is_multisite() ) {

			$this->action( 'wpmu_new_blog', 6, 12 );

			if ( GNETWORK_LARGE_NETWORK_IS )
				$this->filter( 'wp_is_large_network', 3, 10 );
		}
	}

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'Global', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'admin_locale'      => 'en_US',
			'page_signup'       => '0',
			'lookup_ip_service' => 'http://freegeoip.net/?q=%s',
		);
	}

	public function default_settings()
	{
		$exclude = array_filter( array(
			get_option( 'page_on_front' ),
			get_option( 'page_for_posts' ),
		) );

		$settings = array();

		if ( class_exists( __NAMESPACE__.'\\Locale' ) ) {
			$settings['_locale'] = array(
				array(
					'field'       => 'admin_locale',
					'type'        => 'select',
					'title'       => _x( 'Network Language', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network admin language, despite of the site locale', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'en_US',
					'values'      => Arraay::sameKey( Locale::available() ),
				),
			);
		}

		if ( is_multisite() ) {
			$settings['_signup'] = array(
				array(
					'field'       => 'page_signup',
					'type'        => 'page',
					'title'       => _x( 'Page for Signup', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Redirects signups into this page, if registration <strong>disabled</strong>', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => Settings::fieldAfterNewPostType( 'page' ),
				),
			);
		}

		$settings['_misc'] = array(
			array(
				'field'       => 'lookup_ip_service',
				'type'        => 'text',
				'title'       => _x( 'Lookup IP URL', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'URL template to to use for looking up IP adresses. Will replace <code>%s</code> with the IP.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => 'http://freegeoip.net/?q=%s',
				'dir'         => 'ltr',
				'after'       => $this->options['lookup_ip_service'] ? Settings::fieldAfterLink( sprintf( $this->options['lookup_ip_service'], HTTP::IP() ) ) : '',
			),
		);

		return $settings;
	}

	public function before_signup_header()
	{
		if ( 'none' == get_site_option( 'registration', 'none' ) )
			WordPress::redirect( get_page_link( $this->options['page_signup'] ) );
	}

	// TODO: on signup form: http://stackoverflow.com/a/10372861
	public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta )
	{
		switch_to_blog( $blog_id );

		if ( $site_user_id = WordPress::getSiteUserID() )
			add_user_to_blog( $blog_id, $site_user_id, GNETWORK_SITE_USER_ROLE );

		$new_blog_options = $this->filters( 'new_blog_options', array(
			'blogdescription'        => '',
			'permalink_structure'    => '/entries/%post_id%',
			'default_comment_status' => 'closed',
			'default_ping_status'    => 'closed',
			'comments_notify'        => FALSE,
			'moderation_notify'      => FALSE,
			'admin_email'            => get_site_option( 'admin_email' ),
		) );

		foreach ( $new_blog_options as $new_blog_option_key => $new_blog_option )
			update_option( $new_blog_option_key, $new_blog_option );

		wp_update_post( array( 'ID' => 1, 'post_status' => 'draft' ) );
		wp_update_post( array( 'ID' => 2, 'post_status' => 'draft' ) );
		wp_set_comment_status( 1, 'trash' );

		$new_blog_plugins = $this->filters( 'new_blog_plugins', array(
			'geditorial/geditorial.php'     => TRUE,
			'gpersiandate/gpersiandate.php' => TRUE,
		) );

		foreach ( $new_blog_plugins as $new_blog_plugin => $new_blog_plugin_silent )
			activate_plugin( $new_blog_plugin, '', FALSE, $new_blog_plugin_silent );

		restore_current_blog();
		refresh_blog_details( $blog_id );
	}

	public function wp_is_large_network( $is, $using, $count )
	{
		if ( 'users' == $using )
			return $count > GNETWORK_LARGE_NETWORK_IS;

		return $is;
	}
}

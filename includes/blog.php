<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBlog extends gNetworkModuleCore
{

	var $_option_key = 'blog';
	var $_network    = FALSE;

	public function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'blog',
			__( 'General', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);

		if ( $this->options['blog_redirect'] )
			add_action( 'init', array( &$this, 'init_redirect' ), 1 );
	}

	public function settings( $sub = NULL )
	{
		if ( 'blog' == $sub ) {
			$this->settings_update( $sub );
			add_action( 'gnetwork_admin_settings_sub_blog', array( &$this, 'settings_html' ), 10, 2 );
			$this->register_settings();
		}
	}

	public function default_options()
	{
		return array(
			'blog_redirect' => get_option( 'gnetwork_redirect', '' ),
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'   => 'blog_redirect',
					'type'    => 'text',
					'title'   => __( 'Blog Redirect to', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'The site will redirect to this URL. Leave empty to disable.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
					'dir'     => 'ltr',
				),
			),
		);
	}

	public function init_redirect()
	{
		// admin
		if ( is_user_logged_in()
			&& current_user_can( 'manage_options' ) )
				return;

		if ( $_SERVER['SERVER_NAME'] !== ( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) ) )
			return;

		// wp core pages
		if ( FALSE === self::whiteListed() )
			self::redirect( $this->options['blog_redirect'].$_SERVER['REQUEST_URI'], 307 );
	}

	public static function whiteListed( $request_uri = null )
	{
		if ( is_null( $request_uri ) )
			$request_uri = $_SERVER['REQUEST_URI'];

		return gNetworkUtilities::strpos_arr( array(
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
}

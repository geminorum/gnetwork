<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Site extends ModuleCore
{

	protected $key = 'general';

	protected function setup_actions()
	{
		if ( $this->options['page_signup'] && ! is_admin() )
			add_action( 'before_signup_header', array( $this, 'before_signup_header' ), 1 );
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
			'admin_locale' => 'en_US',
			'page_signup'  => '0',
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
					'description' => _x( 'Despite of the site language, always display network admin in this locale', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'en_US',
					'values'      => Arraay::sameKey( Locale::available() ),
				),
			);
		}

		$settings['_signup'] = array(
			array(
				'field'       => 'page_signup',
				'type'        => 'page',
				'title'       => _x( 'Page for Signup', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Redirects signups into this page, if registration disabled', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => '0',
				'exclude'     => $exclude,
				'after'       => Settings::fieldAfterIcon( Settings::getNewPostTypeLink( 'page' ) ),
			),
		);

		return $settings;
	}

	public function before_signup_header()
	{
		if ( 'none' == get_site_option( 'registration', 'none' ) )
			self::redirect( get_page_link( $this->options['page_signup'] ) );
	}
}
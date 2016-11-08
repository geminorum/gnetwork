<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Site extends ModuleCore
{

	protected $key = 'general';

	protected function setup_actions()
	{
		if ( is_admin() ) {

			if ( ! $this->options['user_locale'] ) {
				$this->filter( 'admin_body_class' );
				$this->filter( 'insert_user_meta', 3, 8 );
			}

		} else {

			if ( $this->options['page_signup'] )
				$this->action( 'before_signup_header', 0, 1 );
		}

		if ( $this->options['contact_methods'] )
			$this->filter( 'user_contactmethods', 2 );
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
			'admin_locale'    => 'en_US',
			'page_signup'     => '0',
			'contact_methods' => '1',
			'user_locale'     => '0',
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

		$settings['_signup'] = array(
			array(
				'field'       => 'page_signup',
				'type'        => 'page',
				'title'       => _x( 'Page for Signup', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Redirects signups into this page, if registration <strong>disabled</strong>', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => '0',
				'exclude'     => $exclude,
				'after'       => Settings::fieldAfterIcon( Settings::getNewPostTypeLink( 'page' ) ),
			),
		);

		$settings['_users'] = array(
			array(
				'field'       => 'contact_methods',
				'title'       => _x( 'Contact Methods', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Adds extra contact methods to user profiles', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => '1',
			),
			array(
				'field'       => 'user_locale',
				'title'       => _x( 'User Language', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'User admin language switcher', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'after'       => Settings::fieldAfterIcon( Settings::getMoreInfoIcon( 'https://core.trac.wordpress.org/ticket/29783' ) ),
			),
		);

		return $settings;
	}

	public function before_signup_header()
	{
		if ( 'none' == get_site_option( 'registration', 'none' ) )
			self::redirect( get_page_link( $this->options['page_signup'] ) );
	}

	public function user_contactmethods( $contactmethods, $user )
	{
		return array_merge( $contactmethods, array(
			'googleplus' => _x( 'Google+ Profile', 'Modules: Site: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'twitter'    => _x( 'Twitter', 'Modules: Site: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'mobile'     => _x( 'Mobile Phone', 'Modules: Site: User Contact Method', GNETWORK_TEXTDOMAIN ),
		) );
	}

	public function admin_body_class( $classes )
	{
		return $classes.' hide-userlocale-option';
	}

	public function insert_user_meta( $meta, $user, $update )
	{
		if ( $update )
			delete_user_meta( $user->ID, 'locale' );

		unset( $meta['locale'] );

		return $meta;
	}
}

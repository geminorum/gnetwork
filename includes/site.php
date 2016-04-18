<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Site extends ModuleCore
{

	protected $key = 'general';

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'Global', 'Site Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'admin_locale' => 'en_US',
		);
	}

	public function default_settings()
	{
		$settings = array();

		if ( class_exists( __NAMESPACE__.'\\Locale' ) ) {
			$settings['_locale'] = array(
				array(
					'field'       => 'admin_locale',
					'type'        => 'select',
					'title'       => _x( 'Network Language', 'Site Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Despite of the site language, always display network admin in this locale', 'Site Module', GNETWORK_TEXTDOMAIN ),
					'default'     => 'en_US',
					'values'      => Utilities::sameKey( Locale::available() ),
				),
			);
		}

		return $settings;
	}
}

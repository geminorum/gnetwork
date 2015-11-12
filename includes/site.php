<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkSite extends gNetworkModuleCore
{

	protected $option_key = 'global';
	protected $network    = TRUE;

	protected function setup_actions()
	{
		$this->register_menu( 'global',
			__( 'Global', GNETWORK_TEXTDOMAIN ),
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

		if ( class_exists( 'gNetworkLocale' ) )
			$settings['_locale'] = array(
				array(
					'field'   => 'admin_locale',
					'type'    => 'select',
					'title'   => __( 'Network Language', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Despite of the site language, always display network admin in this locale', GNETWORK_TEXTDOMAIN ),
					'values'  => gNetworkUtilities::sameKey( gNetworkLocale::available() ),
					'default' => 'en_US',
				),
			);

		return $settings;
	}
}

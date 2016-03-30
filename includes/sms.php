<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkSMS extends gNetworkModuleCore
{

	public $providers = array();

	protected $option_key = 'sms';
	protected $network    = TRUE;
	protected $ajax       = TRUE;
	protected $hidden     = TRUE; // FIXME

	public function setup_actions()
	{
		$this->register_menu( 'sms',
			_x( 'SMS', 'SMS Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( $this->options['load_providers'] )
			$this->init_providers();
	}

	public function default_options()
	{
		return array(
			'load_providers'   => '0',
			'debug_providers'  => '0',
			'default_provider' => 'none',
		);
	}

	public function default_settings()
	{
		$settings = array(
			'_general' => array(
				array(
					'field'       => 'load_providers',
					'type'        => 'enabled',
					'title'       => _x( 'Load Providers', 'SMS Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Load available sms providers', 'SMS Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'debug_providers',
					'type'        => 'enabled',
					'title'       => _x( 'Debug Providers', 'SMS Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Debug available sms providers', 'SMS Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
			),
		);

		if ( $this->options['load_providers'] )
			$settings['_general'][] = array(
				'field'   => 'default_provider',
				'type'    => 'select',
				'title'   => _x( 'Default Provider', 'SMS Module', GNETWORK_TEXTDOMAIN ),
				'default' => 'none',
				'values'  => gNetworkUtilities::getProviders( 'sms', array(
					'none' => _x( '&mdash; Select &mdash;', 'SMS Module', GNETWORK_TEXTDOMAIN ),
				) ),
			);

		return $settings;
	}

	private function init_providers()
	{
		$bundled = array(
			'kavenegar' => array(
				'path'  => GNETWORK_DIR.'includes/provider-kavenegar.php',
				'class' => 'gNetworkProviderKavenegar',
			),
			'farapaymak' => array(
				'path'  => GNETWORK_DIR.'includes/provider-farapaymak.php',
				'class' => 'gNetworkProviderFarapaymak',
			),
		);

		$providers = apply_filters( 'gnetwork_sms_providers', $bundled );

		foreach ( $providers as $provider => $args ) {
			if ( isset( $args['path'] ) && file_exists( $args['path'] ) ) {
				require_once( $args['path'] );

				if ( isset( $args['class'] ) ) {
					$class = $args['class'];
					try {
						$this->providers[$provider] = new $class( $this->options );
					} catch ( \Exception $e ) {
						// do nothing!
						// echo $e->getMessage(); die();
					}
				}
			}
		}
	}

	public static function send( $text, $number = NULL, $atts = array() )
	{
		global $gNetwork;

		if ( $gNetwork->sms->options['load_providers'] ) {

			$provider = $gNetwork->sms->options['default_provider'];
			if ( isset( $gNetwork->sms->providers[$provider] ) ) {

				$results = $gNetwork->sms->providers[$provider]->smsSend( $text, $number, $atts );

				if ( $gNetwork->sms->options['debug_providers'] )
					self::logArray( '[Provider: '.$provider.' - smsSend]', array(
						'text'    => $text,
						'number'  => $number,
						'results' => $results,
					) );

				return $results;
			}
		}

		return FALSE;
	}
}

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class SMS extends ModuleCore
{

	protected $key    = 'sms';
	protected $ajax   = TRUE;
	protected $hidden = TRUE; // FIXME

	public $providers = array();

	public function setup_actions()
	{
		if ( $this->options['load_providers'] )
			$this->init_providers();
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'SMS', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'manage_providers' => 'edit_others_posts',
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
					'title'       => _x( 'Load Providers', 'Modules: SMS: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Load available sms providers', 'Modules: SMS: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'debug_providers',
					'type'        => 'enabled',
					'title'       => _x( 'Debug Providers', 'Modules: SMS: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Debug available sms providers', 'Modules: SMS: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'manage_providers',
					'type'        => 'cap',
					'title'       => _x( 'Access Level', 'Modules: SMS: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Selected and above can view the providers information', 'Modules: SMS: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'edit_others_posts',
				),
			),
		);

		if ( $this->options['load_providers'] )
			$settings['_general'][] = array(
				'field'   => 'default_provider',
				'type'    => 'select',
				'title'   => _x( 'Default Provider', 'Modules: SMS: Settings', GNETWORK_TEXTDOMAIN ),
				'default' => 'none',
				'values'  => gNetwork()->providers( 'sms', array(
					'none' => Settings::showOptionNone(),
				) ),
			);

		return $settings;
	}

	private function init_providers()
	{
		$bundled = array(
			'kavenegar' => array(
				'path'  => GNETWORK_DIR.'includes/providers/kavenegar.php',
				'class' => __NAMESPACE__.'\\KavenegarProvider',
			),
			'farapaymak' => array(
				'path'  => GNETWORK_DIR.'includes/providers/farapaymak.php',
				'class' => __NAMESPACE__.'\\FarapaymakProvider',
			),
		);

		foreach ( $this->filters( 'providers', $bundled ) as $provider => $args ) {

			if ( isset( $args['path'] ) && file_exists( $args['path'] ) ) {

				require_once( $args['path'] );

				if ( isset( $args['class'] ) ) {

					$class = $args['class'];

					try {
						$this->providers[$provider] = new $class( $this->options );

					} catch ( Exception $e ) {
						// echo 'Caught exception: ',  $e->getMessage(), "\n";
						// no need to do anything!
					}
				}
			}
		}

		if ( is_admin() ) {
			add_action( 'wp_network_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );
			add_action( 'wp_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );
		}
	}

	public function wp_dashboard_setup()
	{
		if ( WordPress::cuc( $this->options['manage_providers'] ) )
			wp_add_dashboard_widget(
				'gnetwork_sms_widget_summary',
				_x( 'SMS Providers', 'Modules: SMS: Widget Title', GNETWORK_TEXTDOMAIN ),
				array( $this, 'admin_widget_summary' )
			);
	}

	public function admin_widget_summary()
	{
		foreach ( $this->providers as $name => &$provider ) {

			if ( $provider->providerEnabled() ) {

				$status = $provider->providerStatus();

				if ( self::isError( $status ) ) {

					self::error( vsprintf( _x( '%s: %s', 'Modules: SMS', GNETWORK_TEXTDOMAIN ), array(
						$provider->providerName(),
						$status->get_error_message(),
					) ), TRUE );

				} else {

					echo '<h3>'.$provider->providerName().'</h3>';
					echo ProviderCore::dateFormat( $status['timestamp'] );
					echo '<br/>'.$provider->providerBalance();
				}
			}
		}
	}

	public static function send( $text, $number = NULL, $atts = array() )
	{
		if ( gNetwork()->option( 'load_providers', 'sms' ) ) {

			$provider = gNetwork()->option( 'default_provider', 'sms' );

			if ( isset( gNetwork()->sms->providers[$provider] ) ) {

				$results = gNetwork()->sms->providers[$provider]->smsSend( $text, $number, $atts );

				if ( gNetwork()->option( 'debug_providers', 'sms' ) )
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

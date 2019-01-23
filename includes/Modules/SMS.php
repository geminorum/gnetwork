<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Provider;
use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class SMS extends gNetwork\Module
{

	protected $key  = 'sms';
	protected $ajax = TRUE;

	protected $hidden = TRUE; // FIXME

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'SMS', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
	}

	public function default_options()
	{
		return Provider::getTypeDefaultOptions( 'sms' );
	}

	public function default_settings()
	{
		$settings = [ '_general' => Provider::getTypeGeneralSettings( 'sms' ) ];

		if ( $this->options['load_providers'] )
			$settings['_general'][] = Provider::getSetting_default_provider( 'sms' );

		return $settings;
	}

	protected function setup_providers()
	{
		$bundled = [
			'kavenegar' => [
				'path'  => GNETWORK_DIR.'includes/Providers/Kavenegar.php',
				'class' => 'geminorum\\gNetwork\\Providers\\Kavenegar',
			],
			'farapaymak' => [
				'path'  => GNETWORK_DIR.'includes/Providers/Farapaymak.php',
				'class' => 'geminorum\\gNetwork\\Providers\\Farapaymak',
			],
		];

		foreach ( $this->filters( 'providers', $bundled ) as $provider => $args ) {

			if ( isset( $args['path'] ) && file_exists( $args['path'] ) ) {

				require_once( $args['path'] );

				if ( isset( $args['class'] ) ) {

					$class = $args['class'];

					try {

						$this->providers[$provider] = new $class( $this->options, $this->base, $provider );

					} catch ( Exception $e ) {

						// if ( $this->options['debug_providers'] )
						// 	Logger::DEBUG( 'SMS-DEBUG: provider: '.$provider.' :: '.$e->getMessage() );
					}
				}
			}
		}

		if ( is_admin() ) {
			add_action( 'wp_network_dashboard_setup', [ $this, 'wp_dashboard_setup' ], 20 );
			add_action( 'wp_dashboard_setup', [ $this, 'wp_dashboard_setup' ], 20 );
		}
	}

	public function wp_dashboard_setup()
	{
		if ( WordPress::cuc( $this->options['manage_providers'] ) )
			wp_add_dashboard_widget( $this->classs( 'providers-summary' ),
				_x( 'SMS Providers', 'Modules: SMS: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'admin_widget_summary' ]
			);
	}

	public function admin_widget_summary()
	{
		if ( $this->check_hidden_metabox( 'providers-summary' ) )
			return;

		foreach ( $this->providers as $name => &$provider ) {

			if ( $provider->providerEnabled() ) {

				$status = $provider->providerStatus();

				if ( self::isError( $status ) ) {

					echo HTML::error( vsprintf( _x( '%s: %s', 'Modules: SMS', GNETWORK_TEXTDOMAIN ), [
						$provider->providerName(),
						$status->get_error_message(),
					] ) );

				} else {

					HTML::h3( $provider->providerName() );

					echo gNetwork\Provider::dateFormat( $status['timestamp'] );
					echo '<br/>';
					echo $provider->providerBalance();
				}
			}
		}
	}

	public static function send( $text, $number = NULL, $atts = [] )
	{
		if ( gNetwork()->option( 'load_providers', 'sms' ) ) {

			$provider = gNetwork()->option( 'default_provider', 'sms' );

			if ( isset( gNetwork()->sms->providers[$provider] ) ) {

				$results = gNetwork()->sms->providers[$provider]->smsSend( $text, $number, $atts );

				if ( gNetwork()->option( 'debug_providers', 'sms' ) )
					Logger::DEBUG( 'SMS-SEND: {provider}: {number}::{text} - {results}', [
						'provider' => $provider,
						'number'   => $number,
						'text'     => $text,
						'results'  => $results,
					] );

				return $results;
			}
		}

		return FALSE;
	}
}

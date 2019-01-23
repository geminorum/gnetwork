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

		$this->filter_module( 'dashboard', 'pointers', 1, 10, 'providers' );
	}

	public static function send( $message, $target = NULL, $atts = [] )
	{
		if ( gNetwork()->option( 'load_providers', 'sms' ) ) {

			$provider = gNetwork()->option( 'default_provider', 'sms' );

			if ( isset( gNetwork()->sms->providers[$provider] ) ) {

				$results = gNetwork()->sms->providers[$provider]->smsSend( $message, $target, $atts );

				if ( gNetwork()->option( 'debug_providers', 'sms' ) )
					Logger::DEBUG( 'SMS-SEND: {provider}: {target}::{message} - {results}', [
						'provider' => $provider,
						'target'   => $target,
						'message'  => $message,
						'results'  => $results,
					] );

				return $results;
			}
		}

		return FALSE;
	}
}

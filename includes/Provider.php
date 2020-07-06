<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\WordPress;

class Provider extends Core\Base
{

	public $options = [];
	public $buttons = [];
	public $scripts = [];

	protected $base    = 'gnetwork';
	protected $key     = NULL;
	protected $type    = NULL; // sms / fax / payment / push / remote / auth / messenger bot
	protected $enabled = FALSE;

	protected $network = TRUE;
	protected $user    = NULL;
	protected $front   = TRUE;

	protected $beta = FALSE;
	protected $ajax = FALSE;
	protected $cron = FALSE;
	protected $dev  = NULL;

	protected $api_key = '';
	protected $api_uri = '';
	protected $api_suffix = '';

	protected $soap_wsdl = FALSE;

	public function __construct( $options = [], $base = NULL, $slug = NULL )
	{
		if ( is_null( $this->key ) )
			throw new Exception( 'Key Undefined!' );

		if ( is_null( $this->type ) )
			throw new Exception( 'Type Undefined!' );

		if ( ! GNETWORK_BETA_FEATURES && $this->beta )
			throw new Exception( 'Beta Feature!' );

		if ( ! $this->ajax && WordPress::isAJAX() )
			throw new Exception( 'Not on AJAX Calls!' );

		if ( ! $this->cron && WordPress::isCRON() )
			throw new Exception( 'Not on CRON Calls!' );

		if ( wp_installing() )
			throw new Exception( 'Not while WP is Installing!' );

		if ( ! is_admin() && ! $this->front )
			throw new Exception( 'Not on Frontend!' );

		if ( ! is_null( $this->user ) && is_multisite() ) {
			if ( is_user_admin() ) {
				if ( FALSE === $this->user )
					throw new Exception( 'Not on User Admin!' );
			} else {
				if ( TRUE === $this->user )
					throw new Exception( 'Only on User Admin!' );
			}
		}

		if ( ! is_null( $this->dev ) ) {
			if ( WordPress::isDev() ) {
				if ( FALSE === $this->dev )
					throw new Exception( 'Not on Develepment Environment!' );
			} else {
				if ( TRUE === $this->dev )
					throw new Exception( 'Only on Develepment Environment!' );
			}
		}

		if ( ! is_null( $base ) )
			$this->base = $base;

		if ( ! $this->setup_checks() )
			throw new Exception( 'Failed to pass setup checks!' );

		add_filter( $this->base.'_'.$this->type.'_default_settings', [ $this, 'append_default_settings' ], 8, 1 );
		add_filter( $this->base.'_'.$this->type.'_default_options', [ $this, 'append_default_options' ], 8, 1 );
		add_filter( $this->base.'_'.$this->type.'_settings_section', [ $this, 'append_settings_section' ], 8, 2 );

		$enabled = isset( $options[$this->key.'_enabled'] )
			? $options[$this->key.'_enabled']
			: FALSE;

		if ( ! $enabled )
			throw new Exception( 'Not Enabled!' );

		$this->enabled = TRUE;
		$this->options = array_merge( $this->default_options(), $options );

		$this->setup_actions();
	}

	protected function setup_checks()
	{
		return TRUE;
	}

	protected function setup_actions()
	{
		// WILL BE OVERRIDDEN
	}

	public function default_options()
	{
		return [];
	}

	public function default_settings()
	{
		return [];
	}

	public function append_default_settings( $settings )
	{
		$default_settings = $this->default_settings();

		if ( count( $default_settings ) ) {
			$settings['_provider_'.$this->key][] = [
				'field'       => $this->key.'_enabled',
				'title'       => _x( 'Provider', 'Provider Core', 'gnetwork' ),
				'description' => _x( 'Load this provider', 'Provider Core', 'gnetwork' ),
			];

			foreach ( $default_settings as $field => $args )
				$settings['_provider_'.$this->key][] = array_merge( $args, [ 'field'=> $this->key.'_'.$field ] );
		}

		return $settings;
	}

	public function append_default_options( $options )
	{
		$options[$this->key.'_enabled'] = '0';

		foreach ( $this->default_settings() as $field => $args )
			$options[$this->key.'_'.$field] = isset( $args['default'] ) ? $args['default'] : '';

		return $options;
	}

	public function append_settings_section( $section_callback, $section_suffix )
	{
		if ( '_provider_'.$this->key == $section_suffix
			&& method_exists( $this, 'settings_section' ) )
				return [ $this, 'settings_section' ];

		return $section_callback;
	}

	public static function getTypeDefaultOptions( $type )
	{
		return [
			'manage_providers' => 'edit_others_posts',
			'load_providers'   => '0',
			'debug_providers'  => '0',
			'default_provider' => 'none',
			'log_data'         => '0',
		];
	}

	public static function getTypeGeneralSettings( $type, $current = [] )
	{
		$providers = empty( $current['load_providers'] )
			? []
			: gNetwork()->providers( $type, [ 'none' => Settings::showOptionNone() ] );

		return [
			[
				'field'       => 'load_providers',
				'type'        => 'enabled',
				'title'       => _x( 'Load Providers', 'Provider Core: Settings', 'gnetwork' ),
				'description' => _x( 'Tries to load available providers for this module.', 'Provider Core: Settings', 'gnetwork' ),
				'default'     => '0',
			],
			[
				'field'       => 'debug_providers',
				'type'        => 'enabled',
				'title'       => _x( 'Debug Providers', 'Provider Core: Settings', 'gnetwork' ),
				'description' => _x( 'Shows debug information about available providers.', 'Provider Core: Settings', 'gnetwork' ),
				'default'     => '0',
			],
			[
				'field'       => 'manage_providers',
				'type'        => 'cap',
				'title'       => _x( 'Access Level', 'Provider Core: Settings', 'gnetwork' ),
				'description' => _x( 'Selected and above can view the providers information.', 'Provider Core: Settings', 'gnetwork' ),
				'default'     => 'edit_others_posts',
				'disabled'    => empty( $current['load_providers'] ),
			],
			[
				'field'    => 'default_provider',
				'type'     => 'select',
				'title'    => _x( 'Default Provider', 'Provider Core: Settings', 'gnetwork' ),
				'default'  => 'none',
				'values'   => $providers,
				'disabled' => empty( $current['load_providers'] ),
			],
			[
				'field'       => 'log_data',
				'type'        => 'enabled',
				'title'       => _x( 'Log Data', 'Provider Core: Settings', 'gnetwork' ),
				'description' => _x( 'Logs all data in a secure folder.', 'Provider Core: Settings', 'gnetwork' ),
				'default'     => '0',
				'disabled'    => empty( $current['load_providers'] ),
			],
		];
	}

	public function providerEnabled()
	{
		return $this->enabled;
	}

	public function providerBalance() {}

	public function providerName()
	{
		return '[UNDEFINED]';
	}

	public function providerStatus()
	{
		return FALSE; // [ 'class', 'message', 'link' ]
	}

	protected function apiEndpoint( ...$args )
	{
		if ( $this->api_uri )
			return sprintf( $this->api_uri, $this->api_key )
				.implode( '/', $args ).$this->api_suffix;

		return FALSE;
	}

	public function botSend( $message, $target = NULL, $atts = [] )
	{
		return new Error( 'mothod_undefined', 'method must be over-ridden in a sub-class.' );
	}

	public function smsSend( $message, $target = NULL, $atts = [] )
	{
		return new Error( 'mothod_undefined', 'method must be over-ridden in a sub-class.' );
	}

	public function smsBulk( $message, $target = NULL, $atts = [] )
	{
		return new Error( 'mothod_undefined', 'method must be over-ridden in a sub-class.' );
	}

	public function smsRecive()
	{
		return new Error( 'mothod_undefined', 'method must be over-ridden in a sub-class.' );
	}

	protected function soapExecute( $method, $args = [] )
	{
		if ( ! $this->soap_wsdl )
			return new Error( 'soap_no_wsdl', 'NO WDSL for Soap' );

		$params = array_merge( $this->soapDefaultParams(), $args );

		try {

			$client = new \nusoap_client( $this->soap_wsdl, 'wsdl' );
			$results = $client->call( $method, $params );

			if ( $this->options['debug_providers'] )
				Logger::DEBUG( 'SOAP-SUCCES: {provider}: {params} - {results}', [
					'provider' => $this->key,
					'params'   => $params,
					'results'  => $results,
				] );

			return $results;

		} catch ( \SoapFault $e ) {

			if ( $this->options['debug_providers'] )
				Logger::ERROR( 'SOAP-FAILED: {provider}: {params} - {fault}', [
					'provider' => $this->key,
					'params'   => $params,
					'fault'  => $e->faultstring,
				] );

			return new Error( 'soap_fault', $e->faultstring );
		}
	}

	protected function soapDefaultParams()
	{
		return [];
	}

	protected function curlDefaultHeaders()
	{
		return [];
	}

	protected function curlExecute( $url, $data = [], $method = 'POST', $headers = [] )
	{
		if ( ! $url )
			return new Error( 'curl_no_endpoint', 'NO EndPoint for cURL' );

		$handle = curl_init();

		curl_setopt( $handle, CURLOPT_URL, $url );
		curl_setopt( $handle, CURLOPT_HTTPHEADER, array_merge( $this->curlDefaultHeaders(), $headers ) );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, FALSE );

		switch ( $method ) {

			case 'GET':

			break;
			case 'POST':

				curl_setopt( $handle, CURLOPT_POST, TRUE );
				curl_setopt( $handle, CURLOPT_POSTFIELDS, http_build_query( $data ) );

			break;
			case 'PUT':

				curl_setopt( $handle, CURLOPT_CUSTOMREQUEST, 'PUT' );
				curl_setopt( $handle, CURLOPT_POSTFIELDS, $data );

			break;
			case 'DELETE':

				curl_setopt( $handle, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		}

		$response = curl_exec( $handle );
		$httpcode = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

		if ( $this->options['debug_providers'] )
			Logger::DEBUG( 'CURL-CALL: {provider}: {code}::{url} - {data} - {response}', [
				'provider' => $this->key,
				'code'     => $httpcode,
				'url'      => $url,
				'data'     => $data,
				'response' => $response,
			] );

		return $this->curlResults( $response, $httpcode );
	}

	protected function curlResults( $response, $httpcode )
	{
		return $response;
	}

	protected function isResults( $responce, $status_code = NULL )
	{
		return ! self::isError( $responce );
	}

	public static function dateFormat( $timestamp = NULL )
	{
		return Utilities::dateFormat( $timestamp, 'datetime' );
	}

	// FIXME: DRAFT
	protected function wp_remote_post( $args = [] )
	{
		$response = wp_remote_post( $url, [
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => TRUE,
			'headers'     => [],
			'cookies'     => [],
			'body'        => [
				'username' => 'bob',
				'password' => '1234xyz',
			],
		] );
	}

	protected function hash( ...$args )
	{
		$suffix = '';

		foreach ( $args as $arg )
			$suffix.= maybe_serialize( $arg );

		return md5( $this->base.$this->key.$suffix );
	}
}

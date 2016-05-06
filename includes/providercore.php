<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class ProviderCore extends BaseCore
{

	public $options = array();
	public $buttons = array();
	public $scripts = array();

	protected $base    = 'gnetwork';
	protected $key     = NULL;
	protected $type    = NULL; // sms / fax / payment / push
	protected $enabled = FALSE;

	protected $network = TRUE;
	protected $user    = NULL;
	protected $front   = TRUE;

	protected $ajax   = FALSE;
	protected $cron   = FALSE;
	protected $dev    = NULL;
	protected $hidden = FALSE;

	protected $api_key = '';
	protected $api_uri = '';
	protected $api_suffix = '';

	protected $soap_wsdl = FALSE;

	public function __construct( $options = array(), $base = NULL, $slug = NULL )
	{
		if ( is_null( $this->key ) )
			throw new Exception( 'Key Undefined!' );

		if ( is_null( $this->type ) )
			throw new Exception( 'Type Undefined!' );

		if ( ! GNETWORK_HIDDEN_FEATURES && $this->hidden )
			throw new Exception( 'Hidden Feature!' );

		if ( ! $this->ajax && self::isAJAX() )
			throw new Exception( 'Not on AJAX Calls!' );

		if ( ! $this->cron && self::isCRON() )
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
			if ( self::isDev() ) {
				if ( FALSE === $this->dev )
					throw new Exception( 'Not on Develepment Environment!' );
			} else {
				if ( TRUE === $this->dev )
					throw new Exception( 'Only on Develepment Environment!' );
			}
		}

		if ( ! is_null( $base ) )
			$this->base = $base;

		add_filter( $this->base.'_'.$this->type.'_default_settings', array( $this, 'append_default_settings' ), 8, 1 );
		add_filter( $this->base.'_'.$this->type.'_default_options', array( $this, 'append_default_options' ), 8, 1 );
		add_filter( $this->base.'_'.$this->type.'_settings_section', array( $this, 'append_settings_section' ), 8, 2 );

		$enabled = isset( $options[$this->key.'_enabled'] )
			? $options[$this->key.'_enabled']
			: FALSE;

		if ( ! $enabled )
			throw new Exception( 'Not Enabled!' );

		$this->enabled = TRUE;
		$this->options = array_merge( $this->default_options(), $options );

		$this->setup_actions();
	}

	protected function setup_actions() {}

	public function default_options()
	{
		return array();
	}

	public function default_settings()
	{
		return array();
	}

	public function append_default_settings( $settings )
	{
		$default_settings = $this->default_settings();

		if ( count( $default_settings ) ) {
			$settings['_provider_'.$this->key][] = array(
				'field'       => $this->key.'_enabled',
				'title'       => _x( 'Provider', 'Provider Core', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Load this provider', 'Provider Core', GNETWORK_TEXTDOMAIN ),
			);

			foreach ( $default_settings as $field => $args )
				$settings['_provider_'.$this->key][] = array_merge( $args, array( 'field'=> $this->key.'_'.$field ) );
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
				return array( $this, 'settings_section' );

		return $section_callback;
	}

	public function providerEnabled()
	{
		return $this->enabled;
	}

	public function providerWorking() {}
	public function providerBalance() {}

	public function providerName()
	{
		return '[UNDEFINED]';
	}

	public function providerStatus()
	{
		return new Error( 'mothod_undefined', 'method must be over-ridden in a sub-class.' );
	}

	protected function apiEndpoint()
	{
		if ( $this->api_uri )
			return sprintf( $this->api_uri, $this->api_key ).implode( '/', func_get_args() ).$this->api_suffix;

		return FALSE;
	}

	public function smsSend( $text, $atts = array() )
	{
		return new Error( 'mothod_undefined', 'method must be over-ridden in a sub-class.' );
	}

	public function smsBulk( $text, $atts = array() )
	{
		return new Error( 'mothod_undefined', 'method must be over-ridden in a sub-class.' );
	}

	public function smsRecive() {}

	protected function soapExecute( $method, $args = array() )
	{
		if ( ! $this->soap_wsdl )
			return new Error( 'soap_no_wsdl', 'NO WDSL for Soap' );

		$params = array_merge( $this->soapDefaultParams(), $args );

		try {

			$client = new \nusoap_client( $this->soap_wsdl, 'wsdl' );
			$results = $client->call( $method, $params );

			if ( $this->options['debug_providers'] )
				self::logArray( '[Provider: '.$this->key.' - soap]', array(
					'params'  => $params,
					'results' => $results,
				) );

			return $results;

		} catch ( \SoapFault $e ) {

			if ( $this->options['debug_providers'] )
				self::logArray( '[Provider: '.$this->key.' - soap]', array(
					'params' => $params,
					'fault'  => $e->faultstring,
				) );

			return new Error( 'soap_fault', $e->faultstring );
		}
	}

	protected function soapDefaultParams()
	{
		return array();
	}

	protected function curlDefaultHeaders()
	{
		return array();
	}

	protected function curlExecute( $url, $data = array(), $method = 'POST', $headers = array() )
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

            break;
        }

		$response = curl_exec( $handle );
		$httpcode = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

		if ( $this->options['debug_providers'] )
			self::logArray( '[Provider: '.$this->key.' - cURL]', array(
				'url'      => $url,
				'data'     => $data,
				'code'     => $httpcode,
				'response' => $response,
			) );


        return $this->curlResults( $response, $httpcode );
    }

	protected function curlResults( $response, $httpcode )
	{
		return $response;
	}

	protected function isResults( $responce, $status_code = NULL )
	{
		if ( self::isError( $responce ) )
			return FALSE;
	}

	public static function dateFormat( $timestamp = NULL )
	{
		return date_i18n( Utilities::getDateDefaultFormat( TRUE ), $timestamp );
	}

	// FIXME: DRAFT
	protected function wp_remote_post( $args = array() )
	{
		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => TRUE,
			'headers'     => array(),
			'cookies'     => array(),
			'body'        => array(
				'username' => 'bob',
				'password' => '1234xyz',
			),
		) );
	}
}

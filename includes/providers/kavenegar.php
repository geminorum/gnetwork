<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class KavenegarProvider extends ProviderCore
{

	protected $key  = 'kavenegar';
	protected $type = 'sms';

	protected $api_uri    = 'http://api.kavenegar.com/v1/%s/';
	protected $api_suffix = '.json';
	protected $api_key    = '';

	public function providerName()
	{
		return _x( 'Kavenegar', 'Provider: Kavenegar', GNETWORK_TEXTDOMAIN );
	}

	protected function setup_actions()
	{
		if ( isset( $this->options['kavenegar_api_key'] ) )
			$this->api_key = $this->options['kavenegar_api_key'];
	}

	public function default_settings()
	{
		return array(
			'api_key' => array(
				'type'        => 'text',
				'title'       => _x( 'API Key', 'Provider: Kavenegar', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Key for communication between your site and Kavenegar.', 'Provider: Kavenegar', GNETWORK_TEXTDOMAIN ),
				'field_class' => 'large-text'
			),
			'from_number' => array(
				'type'  => 'text',
				'title' => _x( 'From Number', 'Provider: Kavenegar', GNETWORK_TEXTDOMAIN ),
				'desc'  => _x( 'You can specify the phone number that messages should be sent from. If you leave this blank, the default number will be used.', 'Provider: Kavenegar', GNETWORK_TEXTDOMAIN ),
			),
			'admin_numbers' => array(
				'type'  => 'text',
				'title' => _x( 'Admin Numbers', 'Provider: Kavenegar', GNETWORK_TEXTDOMAIN ),
			),
		);
	}

	public function settings_section()
	{
		ModuleCore::settingsSection(
			_x( 'Kavenegar', 'Provider: Kavenegar: Settings Section Title', GNETWORK_TEXTDOMAIN ),
			_x( 'Kavenegar is a Persian SMS Provider', 'Provider" Kavenegar: Settings Section Desc', GNETWORK_TEXTDOMAIN )
		);
	}

	protected function curlDefaultHeaders()
	{
		return array(
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
			'charset: utf-8',
        );
	}

	protected function curlResults( $response, $code )
	{
		return json_decode( $response, TRUE );
	}

	protected function isResults( $response, $status_code = NULL )
	{
		if ( self::isError( $response ) )
			return FALSE;

		if ( isset( $response['return']['status'] )
			&& 200 == $response['return']['status'] )
				return TRUE;

		return FALSE;
	}

	public function providerStatus()
	{
		$results = $this->curlExecute( $this->apiEndpoint( 'utils', 'getdate' ) );

		return array(
			'status'    => $results['return']['status'],
			'timestamp' => isset( $results['entries']['unixtime'] ) ? $results['entries']['unixtime'] : NULL,
		);
	}

	public function providerBalance()
	{
		$results = $this->curlExecute( $this->apiEndpoint( 'account', 'info' ) );

		if ( ! $this->isResults( $results ) )
			return FALSE;

		return isset( $results['entries']['remaincredit'] ) ? $results['entries']['remaincredit'] : FALSE;
	}

	public function smsSend( $text, $number = NULL, $atts = array() )
	{
		$args = self::atts( array(
			'receptor' => is_null( $number ) ? $this->options['kavenegar_admin_numbers'] : $number,
			'sender'   => $this->options['kavenegar_from_number'],
			'message'  => $text,
			// 'date'    => $date,
			// 'type'    => $type,
			// 'localid' => $localid,
		), $atts );

		if ( ! $args['receptor'] )
			return new Error( 'sms_no_reciver', 'NO SMS Reciver', $args );

		// $args['message'] = iconv( 'UTF-8', 'UTF-8//TRANSLIT', $args['message'] );
		$args['message'] = urlencode( $args['message'] );

		$results = $this->curlExecute( $this->apiEndpoint( 'sms', 'send' ), $args );

		if ( ! $this->isResults( $results ) )
			return FALSE;

		return $results;
	}

	// FIXME: UNFINISHED
	public function smsBulk( $text, $atts = array() )
	{
		$args = self::atts( array(
			'receptor' => $this->options['kavenegar_admin_numbers'],
			'sender'   => $this->options['kavenegar_from_number'],
			'message'  => wp_json_encode( $text ),
			// 'date'             => $date,
			// 'type'             => $type,
            // 'localid' => $localid,
		), $atts );

		if ( ! $args['receptor'] )
			return new Error( 'sms_no_reciver', 'NO SMS Reciver', $args );

		$args['message'] = iconv( 'UTF-8', 'UTF-8//TRANSLIT', $args['message'] );

		$results = $this->curlExecute( $this->apiEndpoint( 'sms', 'sendarray' ), $args );

		if ( ! $this->isResults( $results ) )
			return FALSE;

		return $results;
	}
}
<?php namespace geminorum\gNetwork\Providers;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\Number;

class Kavenegar extends gNetwork\Provider
{

	// https://kavenegar.com/rest.html
	// @SEE: https://github.com/kavenegar/kavenegar-php
	// https://github.com/kavenegar/kavenegar-examples-php/
	// @SEE: https://github.com/MahdiMajidzadeh/laravel-kavenegar

	protected $key  = 'kavenegar';
	protected $type = 'sms';

	protected $api_uri    = 'https://api.kavenegar.com/v1/%s/';
	protected $api_suffix = '.json';

	public function providerName()
	{
		return _x( 'Kavenegar', 'Provider: Kavenegar', 'gnetwork' );
	}

	protected function setup_actions()
	{
		if ( defined( 'KAVENEGAR_API_KEY' ) && KAVENEGAR_API_KEY )
			$this->api_key = KAVENEGAR_API_KEY;

		else if ( isset( $this->options['kavenegar_api_key'] ) )
			$this->api_key = $this->options['kavenegar_api_key'];

		add_filter( $this->base.'_sms_recieve_args', [ $this, 'sms_recieve_args' ] );
	}

	public function default_settings()
	{
		return [
			'api_key' => [
				'type'        => 'text',
				'title'       => _x( 'API Key', 'Provider: Kavenegar', 'gnetwork' ),
				'description' => _x( 'Key for communication between this site and Kavenegar.', 'Provider: Kavenegar', 'gnetwork' ),
				'constant'    => 'KAVENEGAR_API_KEY',
				'field_class' => [ 'regular-text', 'code' ],
				'dir'         => 'ltr',
			],
			'from_number' => [
				'type'        => 'text',
				'title'       => _x( 'From Number', 'Provider: Kavenegar', 'gnetwork' ),
				'description' => _x( 'Specifies the phone number that messages should be sent from. If you leave this blank, the default number will be used.', 'Provider: Kavenegar', 'gnetwork' ),
				'field_class' => [ 'regular-text', 'code' ],
				'dir'         => 'ltr',
			],
			'admin_numbers' => [
				'type'  => 'text',
				'title' => _x( 'Admin Numbers', 'Provider: Kavenegar', 'gnetwork' ),
				'field_class' => [ 'regular-text', 'code' ],
				'dir'         => 'ltr',
			],
		];
	}

	public function settings_section()
	{
		Settings::fieldSection(
			_x( 'Kavenegar', 'Provider: Kavenegar: Settings', 'gnetwork' ),
			_x( 'Kavenegar is a Persian SMS Provider.', 'Provider: Kavenegar: Settings', 'gnetwork' )
		);
	}

	public function sms_recieve_args( $args )
	{
		return [
			'from'    => 'from',
			'to'      => 'to',
			'message' => 'message',
			'id'      => 'messageid',
		];
	}

	protected function curlDefaultHeaders( $method = NULL )
	{
		return [
			'Accept: application/json',
			'Content-Type: application/x-www-form-urlencoded',
			'charset: utf-8',
		];
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
		if ( $balance = $this->providerBalance() )
			/* translators: %s: rial */
			return [ 'working', sprintf( _x( '%s Rials for SMS', 'Provider: Kavenegar', 'gnetwork' ), Number::format( $balance ) ) ];

		return [ 'warning -must-charge', _x( 'Charge SMS Credits!', 'Provider: Kavenegar', 'gnetwork' ) ];
	}

	public function providerBalance()
	{
		static $balance = NULL;

		if ( ! is_null( $balance ) )
			return $balance;

		$results = $this->curlExecute( $this->apiEndpoint( 'account', 'info' ) );

		if ( ! $this->isResults( $results ) )
			return FALSE;

		$balance = isset( $results['entries']['remaincredit'] )
			? $results['entries']['remaincredit']
			: FALSE;

		return $balance;
	}

	public function smsSend( $message, $target = NULL, $atts = [] )
	{
		$args = self::atts( [
			'receptor' => $target ?: $this->options['kavenegar_admin_numbers'],
			'sender'   => $this->options['kavenegar_from_number'],
			'message'  => $message,
			// 'date'    => $date,
			// 'type'    => $type,
			// 'localid' => $localid,
		], $atts );

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
	public function smsBulk( $message, $target = NULL, $atts = [] )
	{
		$args = self::atts( [
			'receptor' => $target ?: $this->options[$this->key.'_admin_numbers'],
			'sender'   => $this->options[$this->key.'_from_number'],
			'message'  => wp_json_encode( $message ),
			// 'date'             => $date,
			// 'type'             => $type,
			// 'localid' => $localid,
		], $atts );

		if ( ! $args['receptor'] )
			return new Error( 'sms_no_reciver', 'NO SMS Reciver', $args );

		$args['message'] = iconv( 'UTF-8', 'UTF-8//TRANSLIT', $args['message'] );

		$results = $this->curlExecute( $this->apiEndpoint( 'sms', 'sendarray' ), $args );

		if ( ! $this->isResults( $results ) )
			return FALSE;

		return $results;
	}
}

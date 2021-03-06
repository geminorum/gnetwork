<?php namespace geminorum\gNetwork\Providers;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Error;

class Farapaymak extends gNetwork\Provider
{

	protected $key  = 'farapaymak';
	protected $type = 'sms';

	public function providerName()
	{
		return _x( 'Farapaymak', 'Provider: Farapaymak', 'gnetwork' );
	}

	protected function setup_actions()
	{
		if ( isset( $this->options['farapaymak_wsdl'] ) )
			$this->soap_wsdl = $this->options['farapaymak_wsdl'];
	}

	protected function soapDefaultParams()
	{
		return [
			'username' => $this->options['farapaymak_username'],
			'password' => $this->options['farapaymak_password'],
		];
	}

	public function default_settings()
	{
		return [
			'wsdl' => [
				'type'        => 'text',
				'title'       => _x( 'Service WSDL', 'Provider: Farapaymak', 'gnetwork' ),
				'default'     => 'http://87.107.121.54/post/Send.asmx?wsdl',
				'field_class' => 'large-text',
			],
			'username' => [
				'type'  => 'text',
				'title' => _x( 'Service Username', 'Provider: Farapaymak', 'gnetwork' ),
			],
			'password' => [
				'type'  => 'text',
				'title' => _x( 'Service Password', 'Provider: Farapaymak', 'gnetwork' ),
			],
			'from_number' => [
				'type'        => 'text',
				'title'       => _x( 'From Number', 'Provider: Farapaymak', 'gnetwork' ),
				'description' => _x( 'Specifies the phone number that messages should be sent from. If you leave this blank, the default number will be used.', 'Provider: Farapaymak', 'gnetwork' ),
				'field_class' => [ 'regular-text', 'code' ],
			],
			'admin_numbers' => [
				'type'        => 'text',
				'title'       => _x( 'Admin Numbers', 'Provider: Farapaymak', 'gnetwork' ),
				'field_class' => [ 'regular-text', 'code' ],
			],
		];
	}

	public function settings_section()
	{
		Settings::fieldSection(
			_x( 'Farapaymak', 'Provider: Farapaymak: Settings', 'gnetwork' ),
			_x( 'Farapaymak is a Persian SMS Provider.', 'Provider: Farapaymak: Settings', 'gnetwork' )
		);
	}

	public function providerBalance()
	{
		return $this->soapExecute( 'GetCredit' );
	}

	public function smsSend( $message, $target = NULL, $atts = [] )
	{
		$args = self::atts( [
			'to'      => $target ?: $this->options['farapaymak_admin_numbers'],
			'from'    => $this->options['farapaymak_from_number'],
			'text'    => $message,
			'isflash' => FALSE,
			'udh'     => '',
			'recId'   => [ 0 ],
			'status'  => 0x0,
		], $atts );

		if ( ! $args['to'] )
			return new Error( 'sms_no_reciver', 'NO SMS Reciver', $args );

		$args['text'] = iconv( 'UTF-8', 'UTF-8//TRANSLIT', $args['text'] );

		return $this->soapExecute( 'SendSimpleSMS2', $args );
	}
}

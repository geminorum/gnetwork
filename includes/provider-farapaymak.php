<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkProviderFarapaymak extends gNetworkProviderCore
{

	protected $provider_slug = 'farapaymak';

	public function providerName()
	{
		return _x( 'Farapaymak', 'Provider Farapaymak', GNETWORK_TEXTDOMAIN );
	}

	protected function setup_actions()
	{
		if ( isset( $this->options['farapaymak_wsdl'] ) )
			$this->soap_wsdl = $this->options['farapaymak_wsdl'];
	}

	protected function soapDefaultParams()
	{
		return array(
			'username' => $this->options['farapaymak_username'],
			'password' => $this->options['farapaymak_password'],
		);
	}

	public function default_settings()
	{
		return array(
			'wsdl' => array(
				'type'        => 'text',
				'title'       => __( 'Service WSDL', GNETWORK_TEXTDOMAIN ),
				'default'     => 'http://87.107.121.54/post/Send.asmx?wsdl',
				'field_class' => 'large-text'
			),
			'username' => array(
				'type'  => 'text',
				'title' => __( 'Service Username', GNETWORK_TEXTDOMAIN ),
			),
			'password' => array(
				'type'  => 'text',
				'title' => __( 'Service Password', GNETWORK_TEXTDOMAIN ),
			),
			'from_number' => array(
				'type'  => 'text',
				'title' => __( 'From Number', GNETWORK_TEXTDOMAIN ),
				'desc'  => __( 'You can specify the phone number that messages should be sent from. If you leave this blank, the default number will be used.', GNETWORK_TEXTDOMAIN ),
			),
			'admin_numbers' => array(
				'type'  => 'text',
				'title' => __( 'Admin Numbers', GNETWORK_TEXTDOMAIN ),
			),
		);
	}

	public function settings_section()
	{
		gNetworkModuleCore::settingsSection(
			_x( 'Farapaymak', 'Provider Farapaymak: Settings Section Title', GNETWORK_TEXTDOMAIN ),
			_x( 'Farapaymak is a Persian SMS Provider', 'Provider Farapaymak: Settings Section Desc', GNETWORK_TEXTDOMAIN )
		);
	}

	public function providerBalance()
	{
		return $this->soapExecute( 'GetCredit' );
	}

	public function smsSend( $text, $atts = array() )
	{
		$args = self::atts( array(
			'to'      => $this->options['farapaymak_admin_numbers'],
			'from'    => $this->options['farapaymak_from_number'],
			'text'    => $text,
			'isflash' => FALSE,
			'udh'     => '',
			'recId'   => array( 0 ),
			'status'  => 0x0,
		), $atts );

		if ( ! $args['to'] )
			return new WP_Error( 'sms_no_reciver', 'NO SMS Reciver', $args );

		$args['text'] = iconv( 'UTF-8', 'UTF-8//TRANSLIT', $args['text'] );

		return $this->soapExecute( 'SendSimpleSMS2', $args );
	}
}

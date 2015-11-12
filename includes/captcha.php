<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCaptcha extends gNetworkModuleCore
{

	protected $option_key = 'captcha';
	protected $network    = TRUE;

	private $public_key;
	private $private_key;

	protected function setup_actions()
	{
		if ( GNETWORK_DISABLE_RECAPTCHA )
			return;

		$this->register_menu( 'captcha',
			_x( 'Captcha', 'Captcha Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( ! $this->options['login_captcha'] )
			return;

		if ( empty( $this->options['public_key'] )
			|| empty( $this->options['private_key'] ) )
				return;

		add_action( 'login_form', array( $this, 'login_form' ) );
		add_action( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 10, 2 );
	}

	public function settings_help_tabs()
	{
		return array(
			array(
				'id'       => 'gnetwork-captcha-help',
				'title'    => _x( 'Google reCAPTCHA', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
				'content'  => '<p><br />Register & get the keys from <a href="https://www.google.com/recaptcha/admin#createsite" target="_blank">here</a>.</p>',
				'callback' => FALSE,
			),
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'   => 'login_captcha',
					'type'    => 'enabled',
					'title'   => _x( 'Login Captcha', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Display captcha field on login form', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'public_key',
					'type'    => 'text',
					'title'   => _x( 'Site Key', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Key in the HTML code your site serves to users.', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'private_key',
					'type'    => 'text',
					'title'   => _x( 'Secret Key', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Key for communication between your site and Google reCAPTCHA.', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
			),
		);
	}

	public function default_options()
	{
		return array(
			'login_captcha' => '0',
			'public_key'    => '',
			'private_key'   => '',
		);
	}

	// http://www.sitepoint.com/integrating-a-captcha-with-the-wordpress-login-form/
	// https://github.com/Collizo4sky/WP-Login-Form-with-reCAPTCHA/
	// https://developers.google.com/recaptcha/

	// output the reCAPTCHA form field
	public function login_form()
	{
		?><style>#login {width:368px !important;} #recaptcha_widget_div {direction:ltr;margin-bottom:20px;}</style>
		<script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k=<?=$this->options['public_key'];?>"></script>
		<noscript><iframe src="http://www.google.com/recaptcha/api/noscript?k=<?=$this->options['public_key'];?>" height="300" width="300" frameborder="0"></iframe>
		<br><textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
		<input type="hidden" name="recaptcha_response_field" value="manual_challenge" />
		</noscript><?php
	}

	// verify the captcha answer
	public function wp_authenticate_user( $user, $password )
	{
		if ( ! isset( $_POST['recaptcha_response_field'] )
			|| empty( $_POST['recaptcha_response_field'] ) )
				return new WP_Error( 'empty_captcha',
					_x( 'CAPTCHA should not be empty', 'Captcha Module', GNETWORK_TEXTDOMAIN ) );


		if ( isset( $_POST['recaptcha_response_field'] )
			&& 'false' == $this->recaptcha_response() )
				return new WP_Error( 'invalid_captcha',
					_x( 'CAPTCHA response was incorrect', 'Captcha Module', GNETWORK_TEXTDOMAIN ) );

		return $user;
	}

	// get the reCAPTCHA API response.
	private function recaptcha_response()
	{
		return $this->recaptcha_post_request( array(
			'privatekey' => $this->options['private_key'],
			'remoteip'   => $_SERVER['REMOTE_ADDR'],
			'challenge'  => isset( $_POST['recaptcha_challenge_field'] ) ? esc_attr( $_POST['recaptcha_challenge_field'] ) : '',
			'response'   => isset( $_POST['recaptcha_response_field'] ) ? esc_attr( $_POST['recaptcha_response_field'] ) : '',
		) );
	}

	// send http post request and return the response.
	private function recaptcha_post_request( $post )
	{
		$args = array( 'body' => $post );
		$req  = wp_remote_post( 'https://www.google.com/recaptcha/api/verify', $args );
		$body = wp_remote_retrieve_body( $req );

		// explode the response body and use the request_status
		// @see https://developers.google.com/recaptcha/docs/verify
		$answers = explode( "\n", $body );
		$status  = trim( $answers[0] );

		return $status;
	}
}

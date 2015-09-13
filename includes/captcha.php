<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCaptcha extends gNetworkModuleCore
{

	var $_network    = TRUE;
	var $_option_key = 'captcha';

	private $public_key, $private_key;

	protected function setup_actions()
	{
		if ( GNETWORK_DISABLE_RECAPTCHA )
			return;

		$this->register_menu( 'captcha',
			__( 'Captcha', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);

		if ( ! $this->options['login_captcha'] )
			return;

		if ( empty( $this->options['public_key'] )
			|| empty( $this->options['private_key'] ) )
				return;

		add_action( 'login_form', array( &$this, 'login_form' ) );
		add_action( 'wp_authenticate_user', array( &$this, 'wp_authenticate_user' ), 10, 2 );
	}

	public function settings_help_tabs()
	{
		return array(
			array(
				'id'       => 'gnetwork-captcha-help',
				'title'    => __( 'Google reCAPTCHA', GNETWORK_TEXTDOMAIN ),
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
					'title'   => __( 'Login Captcha', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Display captcha field on login form', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'public_key',
					'type'    => 'text',
					'title'   => __( 'Site Key', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Key in the HTML code your site serves to users.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'private_key',
					'type'    => 'text',
					'title'   => __( 'Secret Key', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Key for communication between your site and Google reCAPTCHA.', GNETWORK_TEXTDOMAIN ),
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
		</noscript> <?php
	}

	// verify the captcha answer
	public function wp_authenticate_user( $user, $password )
	{
		if ( ! isset( $_POST['recaptcha_response_field'] )
			|| empty( $_POST['recaptcha_response_field'] ) )
				return new WP_Error( 'empty_captcha',
					__( 'CAPTCHA should not be empty', GNETWORK_TEXTDOMAIN ) );


		if ( isset( $_POST['recaptcha_response_field'] )
			&& 'false' == $this->recaptcha_response() )
				return new WP_Error( 'invalid_captcha',
					__( 'CAPTCHA response was incorrect', GNETWORK_TEXTDOMAIN ) );

		return $user;
	}

	// get the reCAPTCHA API response.
	public function recaptcha_response()
	{
		return $this->recaptcha_post_request( array(
			'privatekey' => $this->options['private_key'],
			'remoteip' => $_SERVER['REMOTE_ADDR'],
			'challenge' => isset( $_POST['recaptcha_challenge_field'] ) ? esc_attr( $_POST['recaptcha_challenge_field'] ) : '',
			'response' => isset( $_POST['recaptcha_response_field'] ) ? esc_attr( $_POST['recaptcha_response_field'] ) : '',
		) );
	}

	// send http post request and return the response.
	public function recaptcha_post_request( $post_body )
	{
		$args = array( 'body' => $post_body );
		$request = wp_remote_post( 'https://www.google.com/recaptcha/api/verify', $args );
		$response_body = wp_remote_retrieve_body( $request );

		/**
		* explode the response body and use the request_status
		* @see https://developers.google.com/recaptcha/docs/verify
		*/
		$answers = explode( "\n", $response_body );
		$request_status = trim( $answers[0] );

		return $request_status;
	}
}

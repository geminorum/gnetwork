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

		if ( empty( $this->options['public_key'] )
			|| empty( $this->options['private_key'] ) )
				return;

		add_action( 'init', array( $this, 'init' ) );
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
					'field'       => 'public_key',
					'type'        => 'text',
					'title'       => _x( 'Site Key', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Key in the HTML code your site serves to users.', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'code-text' ),
				),
				array(
					'field'       => 'private_key',
					'type'        => 'text',
					'title'       => _x( 'Secret Key', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Key for communication between your site and Google reCAPTCHA.', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'code-text' ),
				),
				array(
					'field'       => 'login_captcha',
					'type'        => 'enabled',
					'title'       => _x( 'Login Captcha', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Display captcha field on login form', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'logged_in',
					'type'        => 'enabled',
					'title'       => _x( 'Logged In', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Display captcha field for logged in users', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
			),
		);
	}

	public function default_options()
	{
		return array(
			'login_captcha' => '0',
			'logged_in'     => '0',
			'public_key'    => '',
			'private_key'   => '',
		);
	}

	public function init()
	{
		global $gNetwork;

		if ( is_user_logged_in() && ! $this->options['logged_in'] )
			return;

		if ( $this->options['login_captcha'] ) {
			add_action( 'login_form', array( $this, 'login_form' ) );
			add_action( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 10, 2 );
		}

		if ( isset( $gNetwork->comments )
			&& $gNetwork->comments->options['captcha'] ) {

			add_action( 'comment_form_after_fields', array( $this, 'comment_form_after_fields' ), 12 );
			add_action( 'comment_form_logged_in_after', array( $this, 'comment_form_logged_in_after' ), 12, 2 );

			add_action( 'preprocess_comment', array( $this, 'preprocess_comment' ), 0 );
			// add_action( 'comment_post_redirect', array( $this, 'relative_redirect' ), 0, 2 );
		}
	}

	private function recaptcha_errors()
	{
		return array(
			'empty_captcha'   => _x( 'CAPTCHA should not be empty', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
			'invalid_captcha' => _x( 'CAPTCHA response was incorrect', 'Captcha Module', GNETWORK_TEXTDOMAIN ),
		);
	}

	private function recaptcha_form( $rows = '3', $cols = '40' )
	{
		?><script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k=<?=$this->options['public_key'];?>"></script>
		<noscript><iframe src="http://www.google.com/recaptcha/api/noscript?k=<?=$this->options['public_key'];?>" height="300" width="300" frameborder="0"></iframe>
		<br><textarea name="recaptcha_challenge_field" rows="<?=$rows;?>" cols="<?=$cols;?>"></textarea>
		<input type="hidden" name="recaptcha_response_field" value="manual_challenge" /></noscript><?php
	}

	// http://www.sitepoint.com/integrating-a-captcha-with-the-wordpress-login-form/
	// https://github.com/Collizo4sky/WP-Login-Form-with-reCAPTCHA/
	// https://developers.google.com/recaptcha/
	// https://developers.google.com/recaptcha/old/docs/customization?hl=en

	public function login_form()
	{
		echo <<<JS
<script type="text/javascript">
	var RecaptchaOptions = {
		theme : 'white'
	};
</script>
JS;
		echo '<style>#login {width:368px !important;} #recaptcha_widget_div {direction:ltr;margin-bottom:20px;}</style>';
		$this->recaptcha_form();
	}

	// verify the captcha answer
	public function wp_authenticate_user( $user, $password )
	{
		$errors = $this->recaptcha_errors();

		if ( ! isset( $_POST['recaptcha_response_field'] )
			|| empty( $_POST['recaptcha_response_field'] ) )
				return new WP_Error( 'empty_captcha', $errors['empty_captcha'] );

		if ( isset( $_POST['recaptcha_response_field'] )
			&& 'false' == $this->recaptcha_response() )
				return new WP_Error( 'invalid_captcha', $errors['invalid_captcha'] );

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

	public function preprocess_comment( $commentdata )
	{
		if ( in_array( $commentdata['comment_type'], array(
			'trackback',
			'pingback',
		) ) )
			return $commentdata;

		$errors = $this->recaptcha_errors();

		if ( ! isset( $_POST['recaptcha_response_field'] )
			|| empty( $_POST['recaptcha_response_field'] ) )
				wp_die( $errors['empty_captcha'] );

		if ( isset( $_POST['recaptcha_response_field'] )
			&& 'false' == $this->recaptcha_response() )
				wp_die( $errors['invalid_captcha'] );

		return $commentdata;
    }

	public function comment_form_after_fields()
	{
		echo <<<JS
<script type="text/javascript">
	var RecaptchaOptions = {
		theme : 'clean'
	};
</script>
JS;
		echo '<style>#recaptcha_widget_div {direction:ltr;margin-bottom:20px;}</style>';
		$this->recaptcha_form();
	}

	public function comment_form_logged_in_after( $commenter, $user_identity )
	{
		echo <<<JS
<script type="text/javascript">
	var RecaptchaOptions = {
		theme : 'clean'
	};
</script>
JS;
		echo '<style>#recaptcha_widget_div {direction:ltr;margin-bottom:20px;}</style>';
		$this->recaptcha_form();
	}
}

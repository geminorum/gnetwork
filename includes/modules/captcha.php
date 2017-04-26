<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\Exception;

class Captcha extends \geminorum\gNetwork\ModuleCore
{

	// FIXME: move to NoCaptcha: [Using Googles NoCaptcha ReCaptcha In WordPress](https://paulund.co.uk/using-googles-nocaptcha-recaptcha-wordpress)

	protected $key = 'captcha';

	private $public_key;
	private $private_key;

	protected function setup_actions()
	{
		if ( GNETWORK_DISABLE_RECAPTCHA )
			throw new Exception( 'Captcha is diabled!' );

		if ( empty( $this->options['public_key'] )
			|| empty( $this->options['private_key'] ) )
				return;

		add_action( 'init', array( $this, 'init' ) );
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Captcha', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function init()
	{
		if ( is_user_logged_in() && ! $this->options['logged_in'] )
			return;

		if ( $this->options['login_captcha'] ) {
			add_action( 'login_form', array( $this, 'login_form' ) );
			add_action( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ), 10, 2 );
		}

		if ( gNetwork()->option( 'captcha', 'comments' ) ) {

			add_action( 'comment_form_after_fields', array( $this, 'comment_form_after_fields' ), 12 );
			add_action( 'comment_form_logged_in_after', array( $this, 'comment_form_logged_in_after' ), 12, 2 );

			add_action( 'preprocess_comment', array( $this, 'preprocess_comment' ), 0 );
			// add_action( 'comment_post_redirect', array( $this, 'relative_redirect' ), 0, 2 );
		}
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

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'public_key',
					'type'        => 'text',
					'title'       => _x( 'Site Key', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Key in the HTML code your site serves to users.', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'code-text' ),
				),
				array(
					'field'       => 'private_key',
					'type'        => 'text',
					'title'       => _x( 'Secret Key', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Key for communication between your site and Google reCAPTCHA.', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'code-text' ),
				),
				array(
					'field'       => 'login_captcha',
					'title'       => _x( 'Login Captcha', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Display captcha field on login form', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'logged_in',
					'title'       => _x( 'Logged In', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Display captcha field for logged in users', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
				),
			),
		);
	}

	public function settings_help_tabs( $sub = NULL )
	{
		return array(
			array(
				'id'      => 'gnetwork-captcha-help',
				'title'   => _x( 'Google reCAPTCHA', 'Modules: Captcha: Help', GNETWORK_TEXTDOMAIN ),
				'content' => '<p><br />Register & get the keys from <a href="https://www.google.com/recaptcha/admin#createsite" target="_blank">here</a>.</p>',
			),
		);
	}

	private function recaptcha_errors()
	{
		return array(
			'empty_captcha'   => _x( 'CAPTCHA should not be empty', 'Modules: Captcha: ReCaptcha Error', GNETWORK_TEXTDOMAIN ),
			'invalid_captcha' => _x( 'CAPTCHA response was incorrect', 'Modules: Captcha: ReCaptcha Error', GNETWORK_TEXTDOMAIN ),
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

		if ( empty( $_POST['recaptcha_response_field'] ) )
			return new Error( 'empty_captcha', $errors['empty_captcha'] );


		if ( 'false' == $this->recaptcha_response() )
			return new Error( 'invalid_captcha', $errors['invalid_captcha'] );

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
		if ( in_array( $commentdata['comment_type'], [ 'trackback', 'pingback' ] ) )
			return $commentdata;

		$errors = $this->recaptcha_errors();

		if ( empty( $_POST['recaptcha_response_field'] ) ) {
			Logger::NOTICE( 'CAPTCHA-COMMENT: empty captcha' );
			wp_die( $errors['empty_captcha'] );
		}

		if ( 'false' == $this->recaptcha_response() ) {
			Logger::NOTICE( 'CAPTCHA-COMMENT: invalid captcha' );
			wp_die( $errors['invalid_captcha'] );
		}

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

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;

class Captcha extends gNetwork\Module
{

	// @SEE: https://contactform7.com/faq-about-recaptcha-v3/

	protected $key = 'captcha';

	protected $enqueued = FALSE;

	protected function setup_actions()
	{
		// NO NEED: after WPCF7 v5.1
		// if ( ! is_admin() )
		// 	$this->action( 'wpcf7_enqueue_scripts' );

		if ( empty( $this->options['public_key'] )
			|| empty( $this->options['private_key'] ) )
				return;

		$this->action( 'init' );

		if ( ! is_admin() && $this->options['bp_captcha'] )
			$this->action( 'bp_init' );
	}

	protected function setup_checks()
	{
		return ! GNETWORK_DISABLE_RECAPTCHA;
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Captcha', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function init()
	{
		if ( is_user_logged_in() && ! $this->options['logged_in'] )
			return;

		if ( $this->options['login_captcha'] ) {
			$this->action( 'login_form' );
			$this->action( 'wp_authenticate_user', 2 );

			$this->action( 'lostpassword_form' );
			$this->action( 'lostpassword_post' );
		}

		if ( $this->options['register_captcha'] ) {
			$this->action( 'register_form' );
			$this->filter( 'registration_errors' );
		}

		if ( gNetwork()->option( 'captcha', 'comments' ) ) {

			$this->action( 'comment_form_after_fields', 1, 12 );
			$this->action( 'comment_form_logged_in_after', 2, 12 );

			$this->action( 'preprocess_comment', 1, 0 );
			// add_action( 'comment_post_redirect', [ $this, 'relative_redirect' ], 0, 2 );
		}
	}

	public function default_options()
	{
		return [
			'public_key'       => '',
			'private_key'      => '',
			'login_captcha'    => '0',
			'register_captcha' => '0',
			'bp_captcha'       => '0',
			'logged_in'        => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'public_key',
					'type'        => 'text',
					'title'       => _x( 'Site Key', 'Modules: Captcha: Settings', 'gnetwork' ),
					'description' => _x( 'The key in the HTML code your site serves to users.', 'Modules: Captcha: Settings', 'gnetwork' ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'private_key',
					'type'        => 'text',
					'title'       => _x( 'Secret Key', 'Modules: Captcha: Settings', 'gnetwork' ),
					'description' => _x( 'The key for communication between your site and Google reCAPTCHA.', 'Modules: Captcha: Settings', 'gnetwork' ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'login_captcha',
					'title'       => _x( 'Login Captcha', 'Modules: Captcha: Settings', 'gnetwork' ),
					'description' => _x( 'Displays captcha field on login and lost password form.', 'Modules: Captcha: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'register_captcha',
					'title'       => _x( 'Register Captcha', 'Modules: Captcha: Settings', 'gnetwork' ),
					'description' => _x( 'Displays captcha field on register form.', 'Modules: Captcha: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'bp_captcha',
					'title'       => _x( 'BuddyPress Captcha', 'Modules: Captcha: Settings', 'gnetwork' ),
					'description' => _x( 'Displays captcha field on BuddyPress forms.', 'Modules: Captcha: Settings', 'gnetwork' ),
					'disabled'    => ! function_exists( 'buddypress' ),
				],
				[
					'field'       => 'logged_in',
					'title'       => _x( 'Logged In', 'Modules: Captcha: Settings', 'gnetwork' ),
					'description' => _x( 'Displays captcha field also for logged-in users.', 'Modules: Captcha: Settings', 'gnetwork' ),
				],
			],
		];
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [
			[
				'id'      => $this->classs( 'help' ),
				'title'   => _x( 'Google reCAPTCHA', 'Modules: Captcha: Help Tab Title', 'gnetwork' ),
				'content' => '<p>reCAPTCHA is a free service that protects your website from spam and abuse.</p><p>Register and get the keys from <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noreferrer"><i>here</i></a>.</p>',
			],
		];
	}

	public function bp_init()
	{
		$this->action( 'bp_before_registration_submit_buttons', 0, 12 );
		$this->filter( 'bp_core_validate_user_signup' );
	}

	// FIXME: DROP THIS
	// @REF: http://wp.me/p6rU3h-ct
	public function wpcf7_enqueue_scripts()
	{
		$iso = Core\L10n::getISO639();

		if ( 'en' == $iso )
			return;

		$url = add_query_arg( [
			'onload' => 'recaptchaCallback',
			'render' => 'explicit',
			'hl'     => $iso,
		], 'https://www.google.com/recaptcha/api.js' );

		wp_deregister_script( 'google-recaptcha' );
		wp_register_script( 'google-recaptcha', $url, [], '2.0', TRUE );
	}

	// @REF: https://paulund.co.uk/using-googles-nocaptcha-recaptcha-wordpress
	// FIXME: skip jquery: http://youmightnotneedjquery.com/
	public function recaptcha_script()
	{
		if ( $this->enqueued )
			return;

		echo '<script src="https://www.google.com/recaptcha/api.js?hl='.Core\L10n::getISO639().'" async defer></script>'."\n";
		Core\HTML::wrapScript( 'function gnrecaptchacb(){for(var r=["#loginform #wp-submit","#lostpasswordform #wp-submit","#registerform #wp-submit","#commentform #submit"],o=0;o<=r.length;o++)jQuery(r[o]).length>0&&jQuery(r[o]).show()}' );

		wp_enqueue_script( 'jquery' );

		$this->enqueued = TRUE;
	}

	public function recaptcha_form()
	{
		$this->recaptcha_script();

		echo '<div class="g-recaptcha" data-sitekey="'.$this->options['public_key'].'" data-callback="gnrecaptchacb"></div>';
	}

	// @REF: https://developers.google.com/recaptcha/docs/verify
	public function recaptcha_verify( $param = TRUE )
	{
		if ( ! isset( $_POST['g-recaptcha-response'] ) )
			return FALSE;

		$request = vsprintf( 'https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s', [
			$this->options['private_key'],
			$_POST['g-recaptcha-response'],
		] );

		$response = Core\HTTP::getJSON( $request );

		if ( ! empty( $response['error-codes'] ) )
			Logger::siteWARNING( 'CAPTCHA-VERIFY', implode( ', ', (array) $response['error-codes'] ) );

		if ( empty( $response['success'] ) )
			return FALSE;

		return $param;
	}

	public function recaptcha_errors()
	{
		return [
			'empty_captcha'   => _x( 'CAPTCHA should not be empty!', 'Modules: Captcha: ReCaptcha Error', 'gnetwork' ),
			'invalid_captcha' => _x( 'CAPTCHA response was incorrect!', 'Modules: Captcha: ReCaptcha Error', 'gnetwork' ),
		];
	}

	public function login_form()
	{
		echo '<style>#login{min-width:350px}#loginform #wp-submit{display:none}div.g-recaptcha{margin:10px 0 20px}</style>';
		$this->recaptcha_form();
	}

	public function wp_authenticate_user( $user, $password )
	{
		$messages = $this->recaptcha_errors();

		if ( empty( $_POST['g-recaptcha-response'] ) )
			return new Core\Error( 'empty_captcha', $messages['empty_captcha'] );

		if ( FALSE === $this->recaptcha_verify() )
			return new Core\Error( 'invalid_captcha', $messages['invalid_captcha'] );

		return $user;
	}

	public function lostpassword_form()
	{
		echo '<style>#login{min-width:350px}#lostpasswordform #wp-submit{display:none}div.g-recaptcha{margin:10px 0 20px}</style>';
		$this->recaptcha_form();
	}

	public function lostpassword_post( $errors )
	{
		$messages = $this->recaptcha_errors();

		if ( empty( $_POST['g-recaptcha-response'] ) ) {

			Logger::siteFAILED( 'CAPTCHA-LOSTPASSWORD', 'empty captcha' );
			$errors->add( 'empty_captcha', $messages['empty_captcha'] );

		} else if ( FALSE === $this->recaptcha_verify() ) {

			Logger::siteNOTICE( 'CAPTCHA-LOSTPASSWORD', 'invalid captcha' );
			$errors->add( 'invalid_captcha', $messages['invalid_captcha'] );
		}

		// $this->filter_false( 'allow_password_reset' );
	}

	public function register_form()
	{
		echo '<style>#registerform #wp-submit{display:none}</style>';
		$this->recaptcha_form();
	}

	public function registration_errors( $errors )
	{
		$messages = $this->recaptcha_errors();

		if ( empty( $_POST['g-recaptcha-response'] ) ) {

			Logger::siteFAILED( 'CAPTCHA-REGISTER', 'empty captcha' );
			$errors->add( 'empty_captcha', $messages['empty_captcha'] );

		} else if ( FALSE === $this->recaptcha_verify() ) {

			Logger::siteNOTICE( 'CAPTCHA-REGISTER', 'invalid captcha' );
			$errors->add( 'invalid_captcha', $messages['invalid_captcha'] );
		}

		return $errors;
	}

	public function comment_form_after_fields()
	{
		echo '<style>#commentform #submit{display:none}div.g-recaptcha{margin:10px 0 20px}</style>';
		$this->recaptcha_form();
	}

	public function comment_form_logged_in_after( $commenter, $user_identity )
	{
		echo '<style>#commentform #submit{display:none}div.g-recaptcha{margin:10px 0 20px}</style>';
		$this->recaptcha_form();
	}

	public function preprocess_comment( $commentdata )
	{
		if ( in_array( $commentdata['comment_type'], [ 'trackback', 'pingback' ] ) )
			return $commentdata;

		$messages = $this->recaptcha_errors();

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			Logger::siteFAILED( 'CAPTCHA-COMMENT', 'empty captcha' );
			wp_die( $messages['empty_captcha'], 406 );
		}

		if ( FALSE === $this->recaptcha_verify() ) {
			Logger::siteNOTICE( 'CAPTCHA-COMMENT', 'invalid captcha' );
			wp_die( $messages['invalid_captcha'], 406 );
		}

		return $commentdata;
	}

	public function bp_before_registration_submit_buttons()
	{
		do_action( 'bp_'.$this->hook().'_errors' );

		echo '<style>#buddypress #signup_submit{display:none}div.g-recaptcha{margin:10px 0 20px}</style>';
		$this->recaptcha_form();
	}

	public function bp_core_validate_user_signup( $result = [] )
	{
		$messages = $this->recaptcha_errors();

		if ( empty( $_POST['g-recaptcha-response'] ) ) {

			Logger::siteFAILED( 'CAPTCHA-BUDDYPRESS', 'empty captcha' );
			$GLOBALS['bp']->signup->errors[$this->hook()] = $messages['empty_captcha'];

		} else if ( FALSE === $this->recaptcha_verify() ) {

			Logger::siteNOTICE( 'CAPTCHA-BUDDYPRESS', 'invalid captcha' );
			$GLOBALS['bp']->signup->errors[$this->hook()] = $messages['invalid_captcha'];
		}

		return $result;
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\HTTP;

class Captcha extends gNetwork\Module
{

	protected $key = 'captcha';

	protected $enqueued = FALSE;

	protected function setup_actions()
	{
		if ( GNETWORK_DISABLE_RECAPTCHA )
			throw new Exception( 'Captcha is diabled!' );

		if ( empty( $this->options['public_key'] )
			|| empty( $this->options['private_key'] ) )
				return;

		$this->action( 'init' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Captcha', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
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
					'title'       => _x( 'Site Key', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Key in the HTML code your site serves to users.', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'private_key',
					'type'        => 'text',
					'title'       => _x( 'Secret Key', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Key for communication between your site and Google reCAPTCHA.', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'login_captcha',
					'title'       => _x( 'Login Captcha', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Display captcha field on login and lost password form', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'register_captcha',
					'title'       => _x( 'Register Captcha', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Display captcha field on register form', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'logged_in',
					'title'       => _x( 'Logged In', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Display captcha field for logged in users', 'Modules: Captcha: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
		];
	}

	public function settings_help_tabs( $sub = NULL )
	{
		return [
			[
				'id'      => 'gnetwork-captcha-help',
				'title'   => _x( 'Google reCAPTCHA', 'Modules: Captcha: Help', GNETWORK_TEXTDOMAIN ),
				'content' => '<p><br />Register and get the keys from <a href="https://www.google.com/recaptcha/admin" target="_blank"><i>here</i></a>.</p>',
			],
		];
	}

	// @REF: https://paulund.co.uk/using-googles-nocaptcha-recaptcha-wordpress
	// FIXME: skip jquery: http://youmightnotneedjquery.com/
	public function recaptcha_script()
	{
		if ( $this->enqueued )
			return;

		$iso = class_exists( 'geminorum\\gNetwork\\Modules\\Locale' )
			? \geminorum\gNetwork\Modules\Locale::getISO()
			: 'en';

		echo '<script src="https://www.google.com/recaptcha/api.js?hl='.$iso.'" async defer></script>'."\n";
		echo '<script type="text/javascript">/* <![CDATA[ */ function gnrecaptchacb(){for(var r=["#loginform #wp-submit","#lostpasswordform #wp-submit","#registerform #wp-submit","#commentform #submit"],o=0;o<=r.length;o++)jQuery(r[o]).length>0&&jQuery(r[o]).show()} /* ]]> */</script>'."\n";

		wp_enqueue_script( 'jquery' );

		$this->enqueued = TRUE;
	}

	public function recaptcha_form()
	{
		$this->recaptcha_script();

		echo '<div class="g-recaptcha" data-sitekey="'.$this->options['public_key'].'" data-callback="gnrecaptchacb"></div>';
	}

	public function recaptcha_verify( $param = TRUE )
	{
		if ( ! isset( $_POST['g-recaptcha-response'] ) )
			return FALSE;

		$url = 'https://www.google.com/recaptcha/api/siteverify?secret='.$this->options['private_key'].'&response='.$_POST['g-recaptcha-response'];

		// $response = json_decode( wp_remote_retrieve_body( wp_remote_get( $url ) ), TRUE );
		$response = HTTP::getJSON( $url, [], TRUE );

		if ( empty( $response['success'] ) )
			return FALSE;

		return $param;
	}

	public function recaptcha_errors()
	{
		return [
			'empty_captcha'   => _x( 'CAPTCHA should not be empty', 'Modules: Captcha: ReCaptcha Error', GNETWORK_TEXTDOMAIN ),
			'invalid_captcha' => _x( 'CAPTCHA response was incorrect', 'Modules: Captcha: ReCaptcha Error', GNETWORK_TEXTDOMAIN ),
		];
	}

	public function login_form()
	{
		echo '<style>#login{width:350px!important}#loginform #wp-submit{display:none}div.g-recaptcha{margin:10px 0 20px}</style>';
		$this->recaptcha_form();
	}

	public function wp_authenticate_user( $user, $password )
	{
		$messages = $this->recaptcha_errors();

		if ( empty( $_POST['g-recaptcha-response'] ) )
			return new Error( 'empty_captcha', $messages['empty_captcha'] );

		if ( FALSE === $this->recaptcha_verify() )
			return new Error( 'invalid_captcha', $messages['invalid_captcha'] );

		return $user;
	}

	public function lostpassword_form()
	{
		echo '<style>#login{width:350px!important}#lostpasswordform #wp-submit{display:none}div.g-recaptcha{margin:10px 0 20px}</style>';
		$this->recaptcha_form();
	}

	public function lostpassword_post( $errors )
	{
		$messages = $this->recaptcha_errors();

		if ( empty( $_POST['g-recaptcha-response'] ) ) {

			Logger::NOTICE( 'CAPTCHA-LOSTPASSWORD: empty captcha' );
			$errors->add( 'empty_captcha', $messages['empty_captcha'] );

		} else if ( FALSE === $this->recaptcha_verify() ) {

			Logger::NOTICE( 'CAPTCHA-LOSTPASSWORD: invalid captcha' );
			$errors->add( 'invalid_captcha', $messages['invalid_captcha'] );
		}

		// add_filter( 'allow_password_reset', '__return_false' );

		return $errors;
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

			Logger::NOTICE( 'CAPTCHA-REGISTER: empty captcha' );
			$errors->add( 'empty_captcha', $messages['empty_captcha'] );

		} else if ( FALSE === $this->recaptcha_verify() ) {

			Logger::NOTICE( 'CAPTCHA-REGISTER: invalid captcha' );
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
			Logger::NOTICE( 'CAPTCHA-COMMENT: empty captcha' );
			wp_die( $messages['empty_captcha'] );
		}

		if ( FALSE === $this->recaptcha_verify() ) {
			Logger::NOTICE( 'CAPTCHA-COMMENT: invalid captcha' );
			wp_die( $messages['invalid_captcha'] );
		}

		return $commentdata;
	}
}

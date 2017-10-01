<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Login extends gNetwork\Module
{

	protected $key = 'login';

	protected function setup_actions()
	{
		$this->action( 'login_init', 0, 1 );

		if ( $this->options['login_math'] ) {
			$this->action( 'login_form' );
			$this->filter( 'authenticate', 3, 1 );
		}

		if ( $this->options['login_log'] )
			$this->action( 'wp_login', 2 );

		$this->filter( 'wp_login_errors', 2 );
		$this->filter( 'login_errors', 1, 20 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Login', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
	}

	public function default_options()
	{
		return [
			'login_headerurl'   => GNETWORK_BASE,
			'login_headertitle' => GNETWORK_NAME,
			'login_logourl'     => '',
			'login_styles'      => '',
			'login_class'       => 'logindefault',
			'login_remember'    => 0,
			'login_math'        => 0,
			'login_credits'     => 0,
			'login_log'         => 0,
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'login_headerurl',
					'type'        => 'url',
					'title'       => _x( 'Header URL', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Login page header logo link URL', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => GNETWORK_BASE,
					'default'     => GNETWORK_BASE,
				],
				[
					'field'       => 'login_headertitle',
					'type'        => 'text',
					'title'       => _x( 'Header Title', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Login page header logo link title attribute', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => GNETWORK_NAME,
					'placeholder' => GNETWORK_NAME,
				],
				[
					'field'       => 'login_remember',
					'title'       => _x( 'Login Remember', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Always checked Remember Me checkbox', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => [
						_x( 'Not Checked', 'Modules: Login', GNETWORK_TEXTDOMAIN ),
						_x( 'Checked', 'Modules: Login', GNETWORK_TEXTDOMAIN ),
					],
				],
				[
					'field'       => 'login_logourl',
					'type'        => 'url',
					'title'       => _x( 'Logo Image', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Full URL to the login logo image', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( Settings::getLoginLogoLink() ),
				],
				[
					'field'       => 'login_class',
					'type'        => 'select',
					'title'       => _x( 'CSS Class', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Select from pre designed login themes', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( self::getLoginStyleLink() ),
					'none_title'  => Settings::showOptionNone(),
					'none_value'  => 'logindefault',
					'values'      => $this->filters( 'login_class', [
						'sidelogo' => _x( 'SideLogo', 'Modules: Login: Login Class', GNETWORK_TEXTDOMAIN ),
						'webogram' => _x( 'Webogram', 'Modules: Login: Login Class', GNETWORK_TEXTDOMAIN ),
					] ),
				],
				[
					'field'       => 'login_styles',
					'type'        => 'textarea',
					'title'       => _x( 'Extra CSS', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Additional styles to use on login page', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'large-text', 'code-text' ],
				],
				[
					'field'       => 'login_credits',
					'title'       => _x( 'Credits Badge', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays credits badge on bottom of the login page', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'login_log',
					'title'       => _x( 'Log Logins', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Logs user log-in events', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
		];

		if ( ! defined( 'BRUTEPROTECT_VERSION' ) )
			$settings['_math'] = [
				[
					'field'       => 'login_math',
					'title'       => _x( 'Login Math', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Puts a math problem after the login form.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				],
			];

		return $settings;
	}

	public function settings_section_math()
	{
		Settings::fieldSection(
			_x( 'Math Settings', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'Blocks Spam by Math. Verifies that a user answered the math problem correctly while loggin in.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	public function login_init()
	{
		if ( $this->options['login_headerurl'] )
			$this->filter( 'login_headerurl', 1, 1000 );

		if ( $this->options['login_headertitle'] )
			$this->filter( 'login_headertitle', 1, 1000 );

		if ( $this->options['login_remember'] )
			$this->filter( 'login_footer', 1, 99, 'remember' );

		$this->action( 'login_head' );
		$this->action( 'login_header', 0, 1 );
		$this->filter( 'login_body_class', 2, 99 );

		if ( ! GNETWORK_DISABLE_CREDITS && $this->options['login_credits'] )
			$this->filter( 'login_footer', 1, 10, 'badge' );
	}

	public function login_header()
	{
		echo '<div class="-head-placeholder"></div>';
	}

	public function login_head()
	{
		Utilities::linkStyleSheet( 'login.all' );

		if ( $this->options['login_styles'] )
			echo '<style>'.$this->options['login_styles'].'</style>'."\n";
		else
			Utilities::customStyleSheet( 'login.css' );
	}

	public function login_body_class( $classes, $action )
	{
		if ( wp_is_mobile() )
			$classes[] = 'mobile';

		return array_merge( $classes, [ $this->options['login_class'] ] );
	}

	public function login_headerurl( $login_header_url )
	{
		return $this->options['login_headerurl'];
	}

	public function login_headertitle( $login_header_title )
	{
		return $this->options['login_headertitle'];
	}

	public function login_footer_remember()
	{
		echo '<script type="text/javascript">try{document.getElementById("rememberme").checked=true;}catch(e){};</script>';
	}

	public function login_form()
	{
		$one = wp_rand( 0, 10 );
		$two = wp_rand( 1, 10 );

		echo '<p class="sum">';

			echo '<label>'._x( 'Prove your humanity:', 'Modules: Login', GNETWORK_TEXTDOMAIN ).'</label>';
			echo '&nbsp;'.Number::format( $one ).'&nbsp;+&nbsp;'.Number::format( $two ).'&nbsp;=&nbsp; ';

			echo HTML::tag( 'input', [
				'type'         => 'number',
				'name'         => 'num',
				'autocomplete' => 'off',
			] );

			echo HTML::tag( 'input', [
				'type'  => 'hidden',
				'name'  => 'ans',
				'value' => wp_hash( $one + $two ),
			] );

		echo '</p>';
	}

	public function authenticate( $null, $username, $password )
	{
		if ( ! isset( $_POST[ 'log' ] ) )
			return $null;

		$salted  = isset( $_POST['num'] ) ? wp_hash( (int) $_POST['num'] ) : FALSE;
		$correct = isset( $_POST['ans'] ) ? $_POST['ans'] : FALSE;

		if ( FALSE === $correct ) {

			Logger::ALERT( 'LOGIN-MATH: '.WordPress::currentBlog().': not properly configured'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

			wp_die( _x( '<strong>This site is not properly configured.</strong> Please ask this site\'s web developer to review for information on how to resolve this issue.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );

		} else if ( FALSE === $salted || $salted != $correct ) {

			if ( FALSE === $salted )
				Logger::WARNING( 'LOGIN-MATH: '.WordPress::currentBlog().': not posting answer'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );
			else
				Logger::NOTICE( 'LOGIN-MATH: '.WordPress::currentBlog().': failed to correctly answer'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

			wp_die( _x( '<strong>You failed to correctly answer the math problem.</strong> This is used to combat spam Please use your browser\'s back button to return to the login form, press the "refresh" button to generate a new math problem, and try to log in again.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );
		}

		return $null;
	}

	// TODO: add logger to logout / has no action hook with user info!
	public function wp_login( $user_login, $user )
	{
		Logger::NOTICE( 'LOGGED-IN: '.WordPress::currentBlog().': '.sprintf( '%s', $user_login ) );
	}

	public function wp_login_errors( $errors, $redirect_to )
	{
		$log = [
			'test_cookie',
			'incorrect_password',
			'invalid_username',
			'invalid_email',
			'invalidcombo',
			'invalidkey', // your password reset link appears to be invalid
			'expiredkey', // your password reset link has expired
			'empty_password',
			'empty_username',
			'empty_email',
			'empty_captcha',
			'invalid_captcha',
		];

		foreach ( $errors->get_error_codes() as $error )
			if ( in_array( $error, $log ) )
				Logger::NOTICE( 'LOGIN-ERRORS: '.WordPress::currentBlog().': '.str_replace( '_', ' ', $error ).sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

		return $errors;
	}

	public function login_errors( $error )
	{
		return _x( 'Something is wrong!', 'Modules: Login: Ambiguous Error', GNETWORK_TEXTDOMAIN )
			.' '.HTML::link( _x( 'Lost your password?', 'Modules: Login: Ambiguous Error', GNETWORK_TEXTDOMAIN ),
				esc_url( wp_lostpassword_url() ) );
	}

	public function login_footer_badge()
	{
		global $interim_login;

		if ( $interim_login )
			return;

		echo '<div class="gnetwork-wrap -footer">';

			if ( $credits = WordPress::customFile( 'credits-badge.png' ) )
				echo HTML::img( $credits );

			else
				echo Utilities::creditsBadge();

		echo '</div>';
	}

	public static function getLoginStyleLink( $style = NULL, $text = FALSE )
	{
		if ( is_null( $style ) )
			$style = Utilities::customStyleSheet( 'login.css', FALSE );

		if ( $style )
			return HTML::tag( 'a', [
				'href'   => $style,
				'title'  => _x( 'Full URL to the current login style file', 'Modules: Login', GNETWORK_TEXTDOMAIN ),
				'target' => '_blank',
			], ( $text ? _x( 'Login Style', 'Modules: Login', GNETWORK_TEXTDOMAIN ) : HTML::getDashicon( 'admin-customizer' ) ) );

		return FALSE;
	}
}

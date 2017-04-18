<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Login extends ModuleCore
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
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Login', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'login_headerurl'   => GNETWORK_BASE,
			'login_headertitle' => GNETWORK_NAME,
			'login_logourl'     => '',
			'login_styles'      => '',
			'login_class'       => '',
			'login_remember'    => 0,
			'login_math'        => 0,
			'login_credits'     => 0,
			'login_log'         => 0,
		);
	}

	public function default_settings()
	{
		$settings = array(
			'_general' => array(
				array(
					'field'       => 'login_headerurl',
					'type'        => 'url',
					'title'       => _x( 'Header URL', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Login page header logo link URL', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => GNETWORK_BASE,
					'default'     => GNETWORK_BASE,
					'field_class' => array( 'regular-text', 'url-text' ),
				),
				array(
					'field'       => 'login_headertitle',
					'type'        => 'text',
					'title'       => _x( 'Header Title', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Login page header logo link title attribute', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => GNETWORK_NAME,
					'placeholder' => GNETWORK_NAME,
				),
				array(
					'field'       => 'login_remember',
					'title'       => _x( 'Login Remember', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Always checked Remember Me checkbox', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => array(
						_x( 'Not Checked', 'Modules: Login', GNETWORK_TEXTDOMAIN ),
						_x( 'Checked', 'Modules: Login', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'login_logourl',
					'type'        => 'url',
					'title'       => _x( 'Logo Image', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Full URL to the login logo image', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'url-text' ),
					'after'       => Settings::fieldAfterIcon( Settings::getLoginLogoLink() ),
				),
				array(
					'field'       => 'login_class',
					'type'        => 'select',
					'title'       => _x( 'CSS Class', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Select from pre designed login themes', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( self::getLoginStyleLink() ),
					'none_title'  => Settings::showOptionNone(),
					'none_value'  => '',
					'values'      => $this->filters( 'login_class', array(
						'webogram' => _x( 'Webogram', 'Modules: Login: Login Class', GNETWORK_TEXTDOMAIN ),
					) ),
				),
				array(
					'field'       => 'login_styles',
					'type'        => 'textarea',
					'title'       => _x( 'Extra CSS', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Additional styles to use on login page', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'large-text', 'code-text' ),
				),
				array(
					'field'       => 'login_credits',
					'title'       => _x( 'Credits Badge', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays credits badge on bottom of the login page', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'login_log',
					'title'       => _x( 'Log Logins', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Logs user log-in events', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				),
			),
		);

		if ( ! defined( 'BRUTEPROTECT_VERSION' ) )
			$settings['_math'] = array(
				array(
					'field'       => 'login_math',
					'title'       => _x( 'Login Math', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Puts a math problem after the login form.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				),
			);

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
			add_filter( 'login_headerurl', array( $this, 'login_headerurl' ), 1000 );

		if ( $this->options['login_headertitle'] )
			add_filter( 'login_headertitle', array( $this, 'login_headertitle' ), 1000 );

		if ( $this->options['login_remember'] )
			add_filter( 'login_footer', array( $this, 'login_footer_remember' ), 99 );

		if ( $this->options['login_class'] )
			add_filter( 'login_body_class', array( $this, 'login_body_class' ), 99, 2 );

		add_action( 'login_head', array( $this, 'login_head' ) );

		if ( ! GNETWORK_DISABLE_CREDITS && $this->options['login_credits'] )
			add_filter( 'login_footer', array( $this, 'login_footer_badge' ) );

		// FIXME: no way to put this before form
		// echo '<div class="-head-placeholder"></div>';
	}

	public function login_head()
	{
		Utilities::linkStyleSheet( 'login.all.css' );

		if ( $this->options['login_styles'] )
			echo '<style>'.$this->options['login_styles'].'</style>'."\n";
		else
			Utilities::customStyleSheet( 'login.css' );
	}

	public function login_body_class( $classes, $action )
	{
		return array_merge( $classes, array( $this->options['login_class'] ) );
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

			echo HTML::tag( 'input', array(
				'type'         => 'number',
				'name'         => 'num',
				'autocomplete' => 'off',
			) );

			echo HTML::tag( 'input', array(
				'type'  => 'hidden',
				'name'  => 'ans',
				'value' => wp_hash( $one + $two ),
			) );

		echo '</p>';
	}

	public function authenticate( $null, $username, $password )
	{
		if ( ! isset( $_POST[ 'log' ] ) )
			return $null;

		$salted  = isset( $_POST['num'] ) ? wp_hash( (int) $_POST['num'] ) : FALSE;
		$correct = isset( $_POST['ans'] ) ? $_POST['ans'] : FALSE;

		if ( FALSE === $correct ) {

			Logger::ALERT( 'LOGIN-MATH: not properly configured'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

			wp_die( _x( '<strong>This site is not properly configured.</strong> Please ask this site\'s web developer to review for information on how to resolve this issue.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );

		} else if ( FALSE === $salted || $salted != $correct ) {

			if ( FALSE === $salted )
				Logger::WARNING( 'LOGIN-MATH: not posting answer'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );
			else
				Logger::NOTICE( 'LOGIN-MATH: failed to correctly answer'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

			wp_die( _x( '<strong>You failed to correctly answer the math problem.</strong> This is used to combat spam Please use your browser\'s back button to return to the login form, press the "refresh" button to generate a new math problem, and try to log in again.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );
		}

		return $null;
	}

	// TODO: add logger to logout / has no action hook with user info!
	public function wp_login( $user_login, $user )
	{
		Logger::NOTICE( sprintf( 'LOGGED-IN: %s', $user_login ) );
	}

	public function wp_login_errors( $errors, $redirect_to )
	{
		$log = array(
			'test_cookie',
			'incorrect_password',
			'invalid_username',
			'invalid_email',
			'invalidcombo',
			'empty_password',
			'empty_username',
			'empty_email',
			'empty_captcha',
			'invalid_captcha',
		);

		foreach ( $errors->get_error_codes() as $error )
			if ( in_array( $error, $log ) )
				Logger::NOTICE( 'LOGIN-ERRORS: '.str_replace( '_', ' ', $error ).sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

		return $errors;
	}

	public function login_footer_badge()
	{
		global $interim_login;

		if ( $interim_login )
			return;

		echo '<div class="gnetwork-wrap -footer">';

			if ( $credits = WordPress::customFile( 'credits-badge.png' ) )
				echo HTML::tag( 'img', [ 'src' => $credits ] );

			else
				echo Utilities::creditsBadge();

		echo '</div>';
	}

	public static function getLoginStyleLink( $style = NULL, $text = FALSE )
	{
		if ( is_null( $style ) )
			$style = Utilities::customStyleSheet( 'login.css', FALSE );

		if ( $style )
			return HTML::tag( 'a', array(
				'href'   => $style,
				'title'  => _x( 'Full URL to the current login style file', 'Modules: Login', GNETWORK_TEXTDOMAIN ),
				'target' => '_blank',
			), ( $text ? _x( 'Login Style', 'Modules: Login', GNETWORK_TEXTDOMAIN ) : HTML::getDashicon( 'admin-customizer' ) ) );

		return FALSE;
	}
}

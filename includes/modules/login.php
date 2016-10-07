<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Login extends ModuleCore
{

	protected $key = 'login';

	protected function setup_actions()
	{
		add_action( 'login_init', array( $this, 'login_init' ), 1 );

		if ( $this->options['login_math']
			&& $this->options['math_hashkey'] ) {

			add_action( 'login_form', array( $this, 'login_form' ) );
			add_filter( 'authenticate', array( $this, 'authenticate' ), 1, 3 );
		}
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
			'math_hashkey'      => '',
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
					'none_value'  => 'no-class',
					'values'      => apply_filters( $this->hook( 'login_class' ), array(
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
			),
		);

		if ( ! defined( 'BRUTEPROTECT_VERSION' ) )
			$settings['_math'] = array(
				array(
					'field'       => 'login_math',
					'title'       => _x( 'Login Math', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Puts a math problem after the login form.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'math_hashkey',
					'type'        => 'text',
					'title'       => _x( 'Random Hash Key', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Will used to sign with the math answer.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => Utilities::genRandomKey( get_site_option( 'admin_email' ) ),
					'field_class' => array( 'regular-text', 'code-text' ),
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

		if ( ! GNETWORK_DISABLE_CREDITS )
			add_filter( 'login_footer', array( $this, 'login_footer_badge' ) );

		// FIXME: no way to put this before form
		// echo '<div class="-head-placeholder"></div>';
	}

	public function login_head()
	{
		Utilities::linkStyleSheet( GNETWORK_URL.'assets/css/login.all.css' );
		Utilities::customStyleSheet( 'login.css' );

		if ( $this->options['login_styles'] )
			echo '<style>'.$this->options['login_styles'].'</style>';
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
echo <<<JS
<script type="text/javascript">
	document.getElementById('rememberme').checked = true;
</script>
JS;
	}

	public function login_form()
	{
		$one = wp_rand( 0, 10 );
		$two = wp_rand( 1, 10 );
		$sum = $one + $two;
		$ans = sha1( $this->options['math_hashkey'].$sum );

		echo '<p class="sum">';

			echo '<label>'._x( 'Prove your humanity:', 'Modules: Login', GNETWORK_TEXTDOMAIN ).'</label>';
			echo '&nbsp;'.number_format_i18n( $one ).'&nbsp;+&nbsp;'.number_format_i18n( $two ).'&nbsp;=&nbsp; ';

			echo HTML::tag( 'input', array(
				'type'  => 'number',
				'name'  => 'num',
			) );

			echo HTML::tag( 'input', array(
				'type'  => 'hidden',
				'name'  => 'ans',
				'value' => $ans,
			) );

		echo '</p>';
	}

	public function authenticate( $null, $username, $password )
	{
		if ( ! isset( $_POST[ 'log' ] ) )
			return $null;

		$answer  = (int) $_POST['num'];
		$salted  = sha1( $this->options['math_hashkey'].$answer );
		$correct = isset( $_POST['ans'] ) ? $_POST['ans'] : FALSE;

		if ( FALSE === $correct )
			wp_die( _x( '<strong>This site is not properly configured.</strong> Please ask this site\'s web developer to review for information on how to resolve this issue.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );

		else if ( $salted != $correct )
			wp_die( _x( '<strong>You failed to correctly answer the math problem.</strong> This is used to combat spam Please use your browser\'s back button to return to the login form, press the "refresh" button to generate a new math problem, and try to log in again.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );

		return $null;
	}

	public function login_footer_badge()
	{
		global $interim_login;

		if ( $interim_login )
			return;

		echo '<div class="gnetwork-wrap -footer">';
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

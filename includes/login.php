<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkLogin extends gNetworkModuleCore
{

	protected $option_key = 'login';
	protected $network    = TRUE;

	protected function setup_actions()
	{
		$this->register_menu( 'login',
			_x( 'Login', 'Login Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( $this->options['login_headerurl'] )
			add_filter( 'login_headerurl', array( $this, 'login_headerurl' ), 1000 );

		if ( $this->options['login_headertitle'] )
			add_filter( 'login_headertitle', array( $this, 'login_headertitle' ), 1000 );

		if ( $this->options['login_remember'] )
			add_filter( 'login_footer', array( $this, 'login_footer_remember' ) );

		add_action( 'login_head', array( $this, 'login_head' ) );

		if ( $this->options['login_math'] && $this->options['math_hashkey'] ) {
			add_action( 'login_form', array( $this, 'login_form' ) );
			add_filter( 'authenticate', array( $this, 'authenticate' ), 1, 3 );
		}
	}

	public function default_options()
	{
		return array(
			'login_headerurl'   => GNETWORK_BASE,
			'login_headertitle' => GNETWORK_NAME,
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
					'type'        => 'text',
					'title'       => _x( 'Header URL', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Login page header logo link URL', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'default'     => GNETWORK_BASE,
					'dir'         => 'ltr',
					'placeholder' => GNETWORK_BASE,
				),
				array(
					'field'       => 'login_headertitle',
					'type'        => 'text',
					'title'       => _x( 'Header Title', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'desc'        => _x( 'Login page header logo link title attribute', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'default'     => GNETWORK_NAME,
					'placeholder' => GNETWORK_NAME,
				),
				array(
					'field'   => 'login_remember',
					'type'    => 'enabled',
					'title'   => _x( 'Login Remember', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Always checked Remember Me checkbox', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'default' => 0,
				),
			),
		);

		if ( ! defined( 'BRUTEPROTECT_VERSION' ) )
			$settings['_math'] = array(
				array(
					'field'   => 'login_math',
					'type'    => 'enabled',
					'title'   => _x( 'Login Math', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Puts a math problem after the login form.', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'default' => 0,
				),
				array(
					'field'       => 'math_hashkey',
					'type'        => 'text',
					'title'       => _x( 'Random Hash Key', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Will used to sign with the math answer.', 'Login Module', GNETWORK_TEXTDOMAIN ),
					'default'     => gNetworkUtilities::genRandomKey( get_site_option( 'admin_email' ) ),
					'dir'         => 'ltr',
				),
			);

		return $settings;
	}

	public function settings_section_math()
	{
		echo '<h3>'._x( 'Math Settings', 'Login Module', GNETWORK_TEXTDOMAIN ).'</h3>';
		echo '<p class="description">';
			_ex( 'Blocks Spam by Math. Verifies that a user answered the math problem correctly while loggin in.', 'Login Module', GNETWORK_TEXTDOMAIN );
		echo '</p>';
	}

	public function login_head()
	{
		gNetworkUtilities::linkStyleSheet( GNETWORK_URL.'assets/css/login.all.css' );
		gNetworkUtilities::customStyleSheet( 'login.css' );
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
		$one = rand( 0, 10 );
		$two = rand( 1, 10 );
		$sum = $one + $two;
		$ans = sha1( $this->options['math_hashkey'].$sum );

		echo '<p class="sum">';

			echo '<label>'._x( 'Prove your humanity:', 'Login Module', GNETWORK_TEXTDOMAIN ).'</label>';
			echo ' &nbsp; '.number_format_i18n( $one ).' &nbsp; + &nbsp; '.number_format_i18n( $two ).' &nbsp; = &nbsp; ';

			echo gNetworkUtilities::html( 'input', array(
				'type'  => 'number',
				'name'  => 'num',
			) );

			echo gNetworkUtilities::html( 'input', array(
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
			wp_die( _x( '<strong>This site is not properly configured.</strong> Please ask this site\'s web developer to review for information on how to resolve this issue.', 'Login Module', GNETWORK_TEXTDOMAIN ) );

		else if ( $salted != $correct )
			wp_die( _x( '<strong>You failed to correctly answer the math problem.</strong> This is used to combat spam Please use your browser\'s back button to return to the login form, press the "refresh" button to generate a new math problem, and try to log in again.', 'Login Module', GNETWORK_TEXTDOMAIN ) );

		return $null;
	}

	// TODO: add custom credit after login form
	// USE: gNetworkCode::shortcode_shields_io();
	// USE: gNetworkTracking::shortcode_ga_beacon();
}

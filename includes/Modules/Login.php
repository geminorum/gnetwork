<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URI;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Login extends gNetwork\Module
{

	protected $key = 'login';

	private $is_login_page = FALSE;

	protected function setup_actions()
	{
		$this->action( 'login_init', 0, 1 );

		if ( $this->options['login_math'] ) {
			$this->action( 'login_form' );
			$this->filter( 'login_form_middle', 2, 1 );
			$this->filter( 'authenticate', 3, 1 );
		}

		$this->action( 'wp_login', 2, 99 );
		$this->filter( 'wp_login_errors', 2 );
		$this->filter( 'login_message' );
		$this->filter( 'login_redirect', 3, 12 );
		$this->filter( 'logout_redirect', 3, 12 );

		if ( $this->options['ambiguous_error'] )
			$this->filter( 'login_errors', 1, 20 );

		if ( $this->options['login_hide'] ) {
			$this->action( 'plugins_loaded', 0, 9 );
			$this->action( 'wp_loaded', 0, 9 );
			remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
		}
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
			'store_lastlogin'   => 1,
			'ambiguous_error'   => 1,
			'redirect_logout'   => '',
			'login_hide'        => 0,
			'login_slug'        => 'login',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'login_hide',
					'title'       => _x( 'Hidden Login Page', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Protects logins by changing the URL and preventing access to admin while not logged-in.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'login_slug',
					'type'        => 'text',
					'title'       => _x( 'Hidden Login Slug', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Custom slug for the hidden login page.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'login',
				],
				[
					'field'       => 'login_headerurl',
					'type'        => 'url',
					'title'       => _x( 'Header URL', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Login page header logo link URL.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => GNETWORK_BASE,
					'default'     => GNETWORK_BASE,
				],
				[
					'field'       => 'login_headertitle',
					'type'        => 'text',
					'title'       => _x( 'Header Title', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Login page header logo link title attribute.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => GNETWORK_NAME,
					'placeholder' => GNETWORK_NAME,
				],
				[
					'field'       => 'login_remember',
					'title'       => _x( 'Login Remember', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Always checked “Remember Me” checkbox.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => [
						_x( 'Not Checked', 'Modules: Login', GNETWORK_TEXTDOMAIN ),
						_x( 'Checked', 'Modules: Login', GNETWORK_TEXTDOMAIN ),
					],
				],
				[
					'field'       => 'login_logourl',
					'type'        => 'url',
					'title'       => _x( 'Logo Image', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Full URL to the login logo image.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( Settings::getLoginLogoLink() ),
				],
				[
					'field'       => 'login_class',
					'type'        => 'select',
					'title'       => _x( 'CSS Class', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Select styles from pre-configured login themes.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
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
					'description' => _x( 'Additional CSS styles to use on default login page.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'large-text', 'code-text' ],
				],
				[
					'field'       => 'login_credits',
					'title'       => _x( 'Credits Badge', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays credits badge on the bottom of default login page.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'login_log',
					'title'       => _x( 'Log Logins', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Logs user log-in events in the log system.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'store_lastlogin',
					'title'       => _x( 'Last Logins', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Stores last login timestamp for each user.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'ambiguous_error',
					'title'       => _x( 'Ambiguous Error', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Swaps error messages with an ambiguous one.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'redirect_logout',
					'type'        => 'url',
					'title'       => _x( 'Logout After', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Full URL to redirect after compelete logout. Leave empty to use the home.', 'Modules: Login: Settings', GNETWORK_TEXTDOMAIN ),
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

	public function plugins_loaded()
	{
		if ( ! is_multisite() && Text::has( $_SERVER['REQUEST_URI'], [ 'wp-signup', 'wp-activate' ] ) )
			wp_die( _x( 'Move along, nothing to see here!', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );

		$request = URI::parse( $_SERVER['REQUEST_URI'] );

		if ( ! is_admin() && ( Text::has( $_SERVER['REQUEST_URI'], 'wp-login.php' )
			|| URL::untrail( $request['path'] ) === site_url( 'wp-login', 'relative' ) ) ) {

			$this->is_login_page = TRUE;
			$_SERVER['REQUEST_URI'] = self::trailingSlash( '/'.str_repeat( '-/', 10 ) );
			$GLOBALS['pagenow'] = 'index.php';

		} else if ( URL::untrail( $request['path'] ) === home_url( $this->options['login_slug'], 'relative' )
			|| ( ! get_option( 'permalink_structure' )
				&& isset( $_GET[$this->options['login_slug']] )
				&& empty( $_GET[$this->options['login_slug']] ) ) ) {

			$GLOBALS['pagenow'] = 'wp-login.php';
		}
	}

	public function wp_loaded()
	{
		if ( is_admin() && ! is_user_logged_in() && ! WordPress::isAJAX() && $GLOBALS['pagenow'] !== 'admin-post.php' )
			wp_die( _x( 'Move along, nothing to see here!', 'Modules: Login', GNETWORK_TEXTDOMAIN ), 403 );

		$request = URI::parse( $_SERVER['REQUEST_URI'] );

		if ( 'wp-login.php' === $GLOBALS['pagenow']
			&& $request['path'] !== self::trailingSlash( $request['path'] )
				&& get_option( 'permalink_structure' ) ) {

			wp_safe_redirect( self::trailingSlash( $this->custom_login_url() )
				.( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?'.$_SERVER['QUERY_STRING'] ) );

			die();

		} else if ( $this->is_login_page ) {

			if ( ( $referer = wp_get_referer() )
				&& Text::has( $referer, 'wp-activate.php' )
				&& ( $referer = parse_url( $referer ) )
				&& ! empty( $referer['query'] ) ) {

				parse_str( $referer['query'], $referer );

				if ( ! empty( $referer['key'] )
					&& ( $result = wpmu_activate_signup( $referer['key'] ) )
					&& self::isError( $result )
					&& ( $result->get_error_code() === 'already_active'
						|| $result->get_error_code() === 'blog_taken' ) ) {

					wp_safe_redirect( $this->custom_login_url()
						.( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?'.$_SERVER['QUERY_STRING'] ) );

					die();
				}
			}

			$this->template_loader();

		} else if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {

			global $pagenow, $error, $interim_login, $action, $user_login;

			@require_once ABSPATH.'wp-login.php';

			die();
		}
	}

	// OLD: `wp_template_loader()`
	private function template_loader()
	{
		$GLOBALS['pagenow'] = 'index.php';

		defined( 'WP_USE_THEMES' ) or define( 'WP_USE_THEMES', TRUE );

		wp();

		if ( $_SERVER['REQUEST_URI'] === self::trailingSlash( str_repeat( '-/', 10 ) ) )
			$_SERVER['REQUEST_URI'] = self::trailingSlash( '/wp-login-php/' );

		require_once( ABSPATH.WPINC.'/template-loader.php' );

		die();
	}

	// OLD: `new_login_url()`
	private function custom_login_url( $scheme = NULL )
	{
		return get_option( 'permalink_structure' )
			? self::trailingSlash( home_url( '/', $scheme ).$this->options['login_slug'] )
			: add_query_arg( $this->options['login_slug'], '', home_url( '/', $scheme ) );
	}

	// OLD: `use_trailing_slashes()`
	private static function hasTrailingSlashes()
	{
		return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
	}

	// OLD: `user_trailingslashit()`
	private static function trailingSlash( $string )
	{
		return self::hasTrailingSlashes() ? URL::trail( $string ) : URL::untrail( $string );
	}

	public function login_init()
	{
		if ( $this->options['login_headerurl'] )
			$this->filter( 'login_headerurl', 1, 1000 );

		if ( $this->options['login_headertitle'] )
			$this->filter( 'login_headertitle', 1, 1000 );

		if ( $this->options['login_remember'] )
			$this->filter( 'login_footer', 1, 99, 'remember' );

		$this->action( 'login_header', 0, 1 );
		$this->action( 'login_head' );
		$this->filter( 'login_title', 2 );
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

	public function login_title( $login_title, $title )
	{
		return sprintf( _x( '%1$s &lsaquo; %2$s', 'Modules: Login: HTML Title', GNETWORK_TEXTDOMAIN ), $title, get_bloginfo( 'name', 'display' ) );
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
		echo $this->get_html_math();
	}

	public function login_form_middle( $content, $args )
	{
		return $content.$this->get_html_math();
	}

	public function get_html_math()
	{
		$one = wp_rand( 0, 10 );
		$two = wp_rand( 1, 10 );

		$html = '<p class="login-sum">';

			$html.= '<label>'._x( 'Prove your humanity:', 'Modules: Login', GNETWORK_TEXTDOMAIN ).'</label>';
			$html.= '&nbsp;'.Number::format( $one ).'&nbsp;+&nbsp;'.Number::format( $two ).'&nbsp;=&nbsp; ';

			$html.= HTML::tag( 'input', [
				'type'         => 'number',
				'name'         => 'num',
				'autocomplete' => 'off',
			] );

			$html.= HTML::tag( 'input', [
				'type'  => 'hidden',
				'name'  => 'ans',
				'value' => wp_hash( $one + $two ),
			] );

		return $html.'</p>';
	}

	public function authenticate( $null, $username, $password )
	{
		if ( ! isset( $_POST[ 'log' ] ) )
			return $null;

		$salted  = isset( $_POST['num'] ) ? wp_hash( (int) $_POST['num'] ) : FALSE;
		$correct = isset( $_POST['ans'] ) ? $_POST['ans'] : FALSE;

		if ( FALSE === $correct ) {

			Logger::siteALERT( 'LOGIN-MATH', 'not properly configured'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

			wp_die( _x( '<strong>This site is not properly configured.</strong> Please ask this site\'s administrator to review for information on how to resolve this issue.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );

		} else if ( FALSE === $salted || $salted != $correct ) {

			if ( FALSE === $salted )
				Logger::siteWARNING( 'LOGIN-MATH', 'not posting answer'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );
			else
				Logger::siteNOTICE( 'LOGIN-MATH', 'failed to correctly answer'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

			wp_die( _x( '<strong>You failed to correctly answer the math problem.</strong> This is used to combat spam. Please use your browser\'s back button to return to the login form, press the "refresh" button to generate a new math problem, and try to log in again.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) );
		}

		return $null;
	}

	// TODO: add logger to logout / has no action hook with user info!
	public function wp_login( $user_login, $user )
	{
		if ( $this->options['login_log'] )
			Logger::siteNOTICE( 'LOGGED-IN', sprintf( '%s', $user_login ) );

		if ( $this->options['store_lastlogin'] )
			update_user_meta( $user->ID, 'lastlogin', current_time( 'mysql', TRUE ) );

		if ( get_user_meta( $user->ID, 'disable_user', TRUE ) )
			WordPress::redirect( add_query_arg( [ 'disabled' => '' ], WordPress::loginURL( '', TRUE ) ) );
	}

	// TODO: custom notice
	public function login_message( $message )
	{
		if ( isset( $_GET['disabled'] ) )
			$message.= HTML::wrap( $this->filters( 'login_disabled', _x( 'Your account is disabled by an administrator.', 'Modules: Login', GNETWORK_TEXTDOMAIN ) ), 'message -danger' );

		return $message;
	}

	public function login_redirect( $redirect_to, $requested_redirect_to, $user )
	{
		if ( defined( 'DOING_AJAX' ) )
			return $redirect_to;

		if ( is_wp_error( $user ) )
			return $redirect_to;

		if ( empty( $requested_redirect_to ) )
			return $user->has_cap( 'edit_posts' ) // TODO: use `cap` setting type
				? get_admin_url()
				: get_home_url();

		return $redirect_to;
	}

	public function logout_redirect( $redirect_to, $requested_redirect_to, $user )
	{
		if ( ! empty( $requested_redirect_to ) )
			return $requested_redirect_to;

		return $this->options['redirect_logout'] ?: get_option( 'home' );
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
				Logger::siteNOTICE( 'LOGIN-ERRORS', str_replace( '_', ' ', $error ).sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

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
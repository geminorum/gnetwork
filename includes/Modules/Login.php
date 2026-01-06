<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Login extends gNetwork\Module
{

	protected $key        = 'login';
	protected $installing = TRUE;

	private $is_login_page = FALSE;

	protected function setup_actions()
	{
		$this->action( 'login_init', 0, 1 );

		if ( $this->options['login_math'] ) {
			$this->action( 'login_form' );
			$this->filter( 'login_form_middle', 2, 1 );
			$this->filter( 'authenticate', 3, 1 );

			$this->action( 'lostpassword_form' );
			$this->action( 'lostpassword_post' );

			$this->action( 'register_form' );
			$this->filter( 'register_post', 3, 1 );

			$this->action( 'woocommerce_login_form', 0, 99 );
			$this->action( 'woocommerce_lostpassword_form', 0, 99 );
		}

		$this->action( 'wp_logout', 1, 9 );
		$this->action( 'wp_login', 2, 99 );
		$this->filter( 'wp_login_errors', 2 );
		$this->filter( 'login_message' );

		if ( $this->options['login_hide'] ) {
			$this->action( 'plugins_loaded', 0, 9 );
			$this->action( 'wp_loaded', 0, 9 );

			add_action( 'init', static function () {
				remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
			}, 99 );

			$this->filter( 'site_url', 4 );
			$this->filter( 'network_site_url', 3 );
			$this->filter( 'wp_redirect', 2 );

			$this->filter( 'site_option_welcome_email', 3, 12 );
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Login', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'login_math'      => 0,
			'login_language'  => 0,
			'login_log'       => 0,
			'store_lastlogin' => 1,
			'redirect_login'  => '',
			'redirect_logout' => '',
			'login_styles'    => '',
			'login_class'     => 'logindefault',
			'login_remember'  => 0,
			'login_credits'   => 0,
			'login_hide'      => 0,
			'login_slug'      => 'login',
			'disable_reset'   => '0',
			'reset_message'   => '',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'login_math',
					'title'       => _x( 'Login Math', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Puts a math problem after the login form.', 'Modules: Login: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'login_language',
					'title'       => _x( 'Login Language', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Displays Language dropdown after login form.', 'Modules: Login: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'login_log',
					'title'       => _x( 'Log Logins', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Logs user log-in/log-out events in the log system.', 'Modules: Login: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'store_lastlogin',
					'title'       => _x( 'Last Logins', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Stores last login timestamp for each user.', 'Modules: Login: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'disable_reset',
					'type'        => 'disabled',
					'title'       => _x( 'Password Reset', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Disables the password reset request option on login pages.', 'Modules: Login: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'reset_message',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Reset Message', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Displays instead of the password reset link on login pages. Leave empty to disable.', 'Modules: Login: Settings', 'gnetwork' ),
				],
			],
			'_redirects' => [
				[
					'field'       => 'redirect_login',
					'type'        => 'url',
					'title'       => _x( 'Log-in After', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Full URL to redirect after successful log-in. Leave empty to fall-back on site settings.', 'Modules: Login: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'redirect_logout',
					'type'        => 'url',
					'title'       => _x( 'Log-out After', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Full URL to redirect after compelete log-out. Leave empty to fall-back on site settings.', 'Modules: Login: Settings', 'gnetwork' ),
				],
			],
			'_hidden' => [
				[
					'field' => 'login_hide',
					'title' => _x( 'Hidden Login Page', 'Modules: Login: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'login_slug',
					'type'        => 'text',
					'title'       => _x( 'Hidden Login Slug', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Custom slug for the hidden login page.', 'Modules: Login: Settings', 'gnetwork' ),
					'field_class' => [ 'regular-text', 'code-text' ],
					'default'     => 'login',
				],
			],
			'_styling' => [
				[
					'field'       => 'login_remember',
					'title'       => _x( 'Login Remember', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Always checked “Remember Me” checkbox.', 'Modules: Login: Settings', 'gnetwork' ),
					'values'      => [
						_x( 'Not Checked', 'Modules: Login', 'gnetwork' ),
						_x( 'Checked', 'Modules: Login', 'gnetwork' ),
					],
				],
				[
					'field'       => 'login_credits',
					'title'       => _x( 'Credits Badge', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Displays credits badge on the bottom of default login page.', 'Modules: Login: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'login_class',
					'type'        => 'select',
					'title'       => _x( 'CSS Theme', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => _x( 'Select styles from pre-configured login themes.', 'Modules: Login: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( self::getLoginStyleLink() ),
					'none_title'  => Settings::showOptionNone(),
					'none_value'  => 'logindefault',
					'values'      => $this->filters( 'login_class', [
						'darkstories' => _x( 'Dark Stories', 'Modules: Login: Login Theme', 'gnetwork' ),
						'sidelogo'    => _x( 'Side Logo', 'Modules: Login: Login Theme', 'gnetwork' ),
						'webogram'    => _x( 'Webogram', 'Modules: Login: Login Theme', 'gnetwork' ),
						'splitscreen' => _x( 'Split Screen', 'Modules: Login: Login Theme', 'gnetwork' ),
						// TODO: https://codepen.io/geminorum/pen/KwKgmZj
					] ),
				],
				[
					'field'       => 'login_styles',
					'type'        => 'textarea-code-editor',
					'title'       => _x( 'Extra CSS', 'Modules: Login: Settings', 'gnetwork' ),
					'description' => [
						_x( 'Additional CSS styles to use on default login page.', 'Modules: Login: Settings', 'gnetwork' ),
						Settings::fieldDescPlaceholders( [
							'theme_color',
							'webapp_color',
							'network_sitelogo',
							'network_siteicon',
						] ),
					],
					'values'      => [ 'mode' => 'css' ],
				],
			],
		];

		return $settings;
	}

	public function settings_section_hidden()
	{
		Settings::fieldSection(
			_x( 'Hidden', 'Modules: Login: Settings', 'gnetwork' ),
			_x( 'Protects logins by changing the URL and preventing access to admin while not logged-in.', 'Modules: Login: Settings', 'gnetwork' )
		);
	}

	protected function settings_setup( $sub = NULL )
	{
		Scripts::enqueueCodeEditor();
	}

	public function plugins_loaded()
	{
		if ( empty( $_SERVER['REQUEST_URI'] ) || '/favicon.ico' === $_SERVER['REQUEST_URI'] )
			return;

		if ( ! is_multisite() && Core\Text::has( $_SERVER['REQUEST_URI'], [ 'wp-signup', 'wp-activate' ] ) )
			wp_die( _x( 'Move along, nothing to see here!', 'Modules: Login', 'gnetwork' ), 403 );

		if ( ! $request = Core\URL::parse( $_SERVER['REQUEST_URI'] ) )
			return;

		if ( empty( $request['path'] ) )
			return;

		$path = Core\URL::untrail( $request['path'] );

		if ( ! is_admin() && ( Core\Text::has( $_SERVER['REQUEST_URI'], 'wp-login.php' )
			|| $path === site_url( 'wp-login', 'relative' ) ) ) {

			$this->is_login_page = TRUE;
			$_SERVER['REQUEST_URI'] = self::trailingSlash( '/'.str_repeat( '-/', 10 ) );
			$GLOBALS['pagenow'] = 'index.php';

		} else if ( $path === home_url( $this->options['login_slug'], 'relative' )
			|| ( ! get_option( 'permalink_structure' )
				&& isset( $_GET[$this->options['login_slug']] )
				&& empty( $_GET[$this->options['login_slug']] ) ) ) {

			$GLOBALS['pagenow'] = 'wp-login.php';
		}
	}

	public function wp_loaded()
	{
		global $pagenow;

		$request = Core\URL::parse( $_SERVER['REQUEST_URI'] );

		$this->check_admin_page( $request, $pagenow );

		if ( 'wp-login.php' === $pagenow
			&& get_option( 'permalink_structure' )
			&& $request['path'] !== self::trailingSlash( $request['path'] ) ) {

			$this->redirect_custom_login( TRUE );

		} else if ( $this->is_login_page ) {

			$this->check_login_page();

			$this->template_loader();

		} else if ( 'wp-login.php' === $pagenow ) {

			global $error, $interim_login, $action, $user_login;

			@require_once ABSPATH.'wp-login.php';

			die();
		}
	}

	private function check_admin_page( $request, $pagenow )
	{
		// disabling install page
		if ( 'install.php' === $pagenow )
			Utilities::redirect404();

		if ( ! is_admin() )
			return;

		if ( WordPress\IsIt::ajax() )
			return;

		if ( is_user_logged_in() )
			return;

		if ( 'admin-post.php' === $pagenow )
			return;

		if ( ! empty( $_GET['adminhash'] )
			&& '/wp-admin/options.php' === $request['path'] )
				return;

		Utilities::redirect404();
	}

	private function check_login_page()
	{
		if ( ! $referer = WordPress\Redirect::getReferer() )
			return;

		if ( ! Core\Text::has( $referer, 'wp-activate.php' ) )
			return;

		$parsed = Core\URL::parseDeep( $referer );

		if ( empty( $parsed['query']['key'] ) )
			return;

		$activated = wpmu_activate_signup( $parsed['query']['key'] );

		if ( ! self::isError( $activated ) )
			return;

		if ( ! in_array( $activated->get_error_code(), [ 'already_active', 'blog_taken' ] ) )
			return;

		$this->redirect_custom_login();
	}

	private function redirect_custom_login( $slash = FALSE )
	{
		$redirect = $this->custom_login_url();

		if ( $slash )
			$redirect = self::trailingSlash( $redirect );

		if ( ! empty( $_SERVER['QUERY_STRING'] ) )
			$redirect.= '?'.$_SERVER['QUERY_STRING'];

		wp_safe_redirect( $redirect );

		die();
	}

	// OLD: `wp_template_loader()`
	private function template_loader()
	{
		$GLOBALS['pagenow'] = 'index.php';

		self::define( 'WP_USE_THEMES', TRUE );

		wp();

		if ( $_SERVER['REQUEST_URI'] === self::trailingSlash( str_repeat( '-/', 10 ) ) )
			$_SERVER['REQUEST_URI'] = self::trailingSlash( '/wp-login-php/' );

		require_once ABSPATH.WPINC.'/template-loader.php';

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
		return self::hasTrailingSlashes()
			? Core\URL::trail( $string )
			: Core\URL::untrail( $string );
	}

	// OLD: `filter_wp_login_php()`
	private function check_login( $url, $scheme = NULL )
	{
		if ( ! Core\Text::has( $url, 'wp-login.php' ) )
			return $url;

		if ( is_ssl() )
			$scheme = 'https';

		$args = explode( '?', $url );

		if ( isset( $args[1] ) ) {
			parse_str( $args[1], $args );
			return add_query_arg( $args, $this->custom_login_url( $scheme ) );
		}

		return $this->custom_login_url( $scheme );
	}

	public function site_url( $url, $path, $scheme, $blog_id )
	{
		return $this->check_login( $url, $scheme );
	}

	public function network_site_url( $url, $path, $scheme )
	{
		return $this->check_login( $url, $scheme );
	}

	public function wp_redirect( $location, $status )
	{
		return $this->check_login( $location );
	}

	public function site_option_welcome_email( $value, $option, $network_id )
	{
		return str_replace( 'wp-login.php', self::trailingSlash( $this->options['login_slug'] ), $value );
	}

	public function login_init()
	{
		$this->filter( 'login_headerurl', 1, 1000 );
		$this->filter( 'login_headertext', 1, 1000 );

		$this->action( 'login_header', 0, 1 );
		$this->action( 'login_footer', 0, 999 );
		$this->action( 'login_head' );

		$this->filter( 'login_title', 2 );
		$this->filter( 'login_body_class', 2, 99 );

		$this->action( 'login_footer', 1, 9, 'logged_in' );

		// @REF: https://make.wordpress.org/core/2021/12/20/introducing-new-language-switcher-on-the-login-screen-in-wp-5-9/
		if ( ! $this->options['login_language'] )
			$this->filter_false( 'login_display_language_dropdown' );

		if ( $this->options['login_credits']
			&& 'splitscreen' !== $this->options['login_class'] )
				$this->action( 'login_footer', 1, 10, 'badge' );

		if ( $this->options['disable_reset']
			|| $this->options['reset_message'] )
				$this->filter( 'lost_password_html_link' );
	}

	public function login_header()
	{
		if ( 'webogram' == $this->options['login_class'] ) {
			echo '<div class="-head-placeholder"></div>';

		} else if ( 'splitscreen' == $this->options['login_class']
			&& empty( $GLOBALS['interim_login'] ) ) {

			echo '<div class="split-screen"><div class="-side -first">'
				.'<div class="-inner -row-wrap">';

			echo '<div class="-row -head"><div>';
				$this->actions( 'header_splitscreen_head' );
			echo '</div></div>';

			echo '<div class="-row -main"><div class="-brand">';

			echo '<h1><a href="'.esc_url( apply_filters( 'login_headerurl', GNETWORK_BASE ) ).'">'
				.apply_filters( 'login_headertext', GNETWORK_NAME ).'</a></h1>';

			echo '</div></div><div class="-row -foot"><div>';

			$this->actions( 'header_splitscreen_foot' );

			if ( $this->options['login_credits'] )
				$this->render_badge();

			echo '</div></div>';

			echo '</div></div><div class="-side -second">'
				.'<div class="-inner"><div class="-action">';
		}
	}

	public function login_footer()
	{
		if ( ! empty( $GLOBALS['interim_login'] ) )
			return;

		if ( 'splitscreen' == $this->options['login_class'] )
			echo '</div></div></div></div>';

		if ( $this->options['login_remember'] )
			echo '<script>try{document.getElementById("rememberme").checked=true;}catch(e){};</script>';
	}

	public function login_head()
	{
		Utilities::linkStyleSheet( 'login.all' );

		if ( is_rtl() )
			Core\HTML::linkStyleSheet( GNETWORK_URL.'assets/css/login.rtl.css', GNETWORK_VERSION );

		if ( 'splitscreen' == $this->options['login_class'] ) {
			$variables = '.split-screen .-first{background-color:'.gNetwork()->brand( 'color' ).'!important}';
			$variables.= '.split-screen .-second{background-color:'.gNetwork()->brand( 'background' ).'!important}';

			printf( "<style>\n%s\n</style>\n", $variables );
		}

		if ( $this->options['login_styles'] )
			printf( "<style>\n%s\n</style>\n",
				Core\Text::replaceTokens( $this->options['login_styles'], [
					'theme_color'      => gNetwork()->option( 'theme_color', 'branding' ),
					'webapp_color'     => gNetwork()->option( 'webapp_color', 'branding' ),
					'network_sitelogo' => gNetwork()->option( 'network_sitelogo', 'branding' ),
					'network_siteicon' => gNetwork()->option( 'network_siteicon', 'branding' ),
				] )
			);

		else
			Utilities::customStyleSheet( 'login.css' );

		if ( 'logindefault' !== $this->options['login_class'] )
			return;

		if ( has_custom_logo() ) {

			$image   = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' );
			$default = 320; // NOTE: default width for `div#login`

			$style = \vsprintf( '.login h1 a {
				background-image: url(%1$s) !important;
				background-size: contain !important;
				width: %2$spx !important;
				height: %3$spx !important;
				max-width: %4$spx !important;
				max-height: %5$spx !important;
			}', [
				esc_url( $image[0] ),
				absint( $image[1] ),
				absint( $image[2] ),
				( $default / 2 ) + 40,
				$default / 2,
			] );

			printf( "<style>\n%s\n</style>\n", $style );

		} else if ( $sitelogo = gNetwork()->option( 'network_sitelogo', 'branding' ) ) {

			$default = 320; // NOTE: default width for `div#login`

			$style = vsprintf( '.login h1 a {
				background-image: url(%1$s) !important;
				width: %2$spx !important;
				height: %3$spx !important;
			}', [
				esc_url( $sitelogo ),
				$default / 2,
				$default / 2,
			] );

			printf( "<style>\n%s\n</style>\n", $style );
		}
	}

	public function login_title( $login_title, $title )
	{
		/* translators: %1$s: page title, %2$s: site name */
		return sprintf( _x( '%1$s &lsaquo; %2$s', 'Modules: Login: HTML Title', 'gnetwork' ), $title, get_bloginfo( 'name', 'display' ) );
	}

	public function login_body_class( $classes, $action )
	{
		if ( wp_is_mobile() )
			$classes[] = 'mobile';

		if ( function_exists( 'get_network' ) )
			$classes[] = 'network-'.Core\HTML::sanitizeClass( Core\URL::prepTitle( str_replace( '.', '-', get_network()->domain ) ) );

		if ( $this->options['disable_reset'] )
			$classes[] = 'hide-pw-reset';

		return array_merge( $classes, [ $this->options['login_class'] ] );
	}

	public function login_headerurl( $login_header_url )
	{
		return gNetwork()->brand( 'url' );
	}

	public function login_headertext( $login_header_title )
	{
		return gNetwork()->brand( 'name' );
	}

	public function login_form()
	{
		echo $this->_get_html_math( 'login' );
	}

	public function login_form_middle( $content, $args )
	{
		return $content.$this->_get_html_math( 'inline' );
	}

	public function lostpassword_form()
	{
		echo $this->_get_html_math( 'lostpassword' );
	}

	public function register_form()
	{
		echo $this->_get_html_math( 'register' );
	}

	public function woocommerce_login_form()
	{
		echo $this->_get_html_math( 'woocommerce' );
	}

	public function woocommerce_lostpassword_form()
	{
		echo $this->_get_html_math( 'lostpassword' );
	}

	private function _get_html_math( $context = NULL, $label = NULL )
	{
		if ( is_null( $label ) )
			$label = _x( 'Prove your humanity:', 'Modules: Login', 'gnetwork' ); // TODO: customize via option

		$max = 10; // TODO: customize via option
		$one = wp_rand( 0, $max );
		$two = wp_rand( 1, $max );

		$html = '<p class="login-sum form-row">';

			$html.= Core\HTML::tag( 'label', [ 'class' => 'form-label' ], $label );
			$html.= sprintf( '&nbsp;%s&nbsp;+&nbsp;%s&nbsp;=&nbsp; ', Core\Number::localize( $one ), Core\Number::localize( $two ) );

			$html.= Core\HTML::tag( 'input', [
				'type'         => 'number',
				'name'         => 'num',
				'autocomplete' => 'off',
				'class'        => 'form-control',   // Bootstrap
			] );

			$html.= Core\HTML::tag( 'input', [
				'type'  => 'hidden',
				'name'  => 'ans',
				'value' => wp_hash( $one + $two ),
			] );

		return $html.'</p>';
	}

	private function check_math()
	{
		$salted  = isset( $_POST['num'] ) ? wp_hash( (int) $_POST['num'] ) : FALSE;
		$correct = isset( $_POST['ans'] ) ? $_POST['ans'] : FALSE;

		if ( FALSE === $correct ) {

			Logger::siteFAILED( 'LOGIN-MATH', 'not properly configured'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

			wp_die( _x( '<strong>This site is not properly configured.</strong> Please ask this site\'s administrator to review for information on how to resolve this issue.', 'Modules: Login', 'gnetwork' ) );

		} else if ( FALSE === $salted || $salted != $correct ) {

			if ( FALSE === $salted )
				Logger::siteFAILED( 'LOGIN-MATH', 'not posting answer'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );
			else
				Logger::siteFAILED( 'LOGIN-MATH', 'failed to correctly answer'.sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

			wp_die( _x( '<strong>You failed to correctly answer the math problem.</strong> This is used to combat spam. Please use your browser\'s back button to return to the login form, press the "refresh" button to generate a new math problem, and try to log in again.', 'Modules: Login', 'gnetwork' ), 403 );
		}

		return TRUE;
	}

	public function authenticate( $null, $username, $password )
	{
		if ( WordPress\IsIt::rest() || WordPress\IsIt::xmlRPC() )
			return $null;

		if ( isset( $_POST['log'] ) || isset( $_POST['woocommerce-login-nonce'] ) )
			$this->check_math();

		return $null;
	}

	public function lostpassword_post( $errors )
	{
		if ( isset( $_POST['user_login'] ) )
			$this->check_math();
	}

	public function register_post( $sanitized_user_login, $user_email, $errors )
	{
		if ( ! isset( $_POST['user_email'] ) )
			return;

		$this->check_math();
	}

	public function wp_logout( $user_id )
	{
		if ( $this->options['login_log'] )
			Logger::siteNOTICE( 'LOGGED-OUT', sprintf( '%s', get_user_by( 'id', $user_id )->user_login ) );
	}

	public function wp_login( $user_login, $user )
	{
		if ( $this->options['login_log'] )
			Logger::siteNOTICE( 'LOGGED-IN', sprintf( '%s', $user_login ) );

		if ( $this->options['store_lastlogin'] )
			update_user_meta( $user->ID, 'lastlogin', current_time( 'mysql', TRUE ) );

		if ( get_user_meta( $user->ID, 'disable_user', TRUE ) )
			WordPress\Redirect::doWP( add_query_arg( [ 'disabled' => '' ], WordPress\URL::login( '', TRUE ) ) );
	}

	// TODO: custom notice
	public function login_message( $message )
	{
		if ( isset( $_GET['disabled'] ) )
			$message.= Core\HTML::wrap( $this->filters( 'login_disabled', _x( 'Your account is disabled by an administrator.', 'Modules: Login', 'gnetwork' ) ), 'message -danger' );

		return $message;
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
			'retrieve_password_email_failure', // the email could not be sent. your site may not be correctly configured to send emails.
		];

		foreach ( $errors->get_error_codes() as $error )
			if ( in_array( $error, $log ) )
				Logger::siteFAILED( 'LOGIN-ERRORS', str_replace( '_', ' ', $error ).sprintf( ': %s', self::req( 'log', '(EMPTY)' ) ) );

		$code = $errors->get_error_code();

		if ( in_array( $code, [ 'invalid_username', 'incorrect_password', 'invalid_email', 'invalidcombo' ] ) ) {
			$errors->remove( $code );
			$errors->add( 'invalid_username', vsprintf( '%1$s <a href="%3$s">%2$s</a>', [
				_x( '<strong>ERROR</strong>: Invalid login data.', 'Modules: Login: Ambiguous Error', 'gnetwork' ),
				_x( 'Lost your password?', 'Modules: Login: Ambiguous Error', 'gnetwork' ),
				esc_url( wp_lostpassword_url() ),
			] ) );
		}

		return $errors;
	}

	public function login_footer_logged_in()
	{
		if ( ! empty( $GLOBALS['interim_login'] ) )
			return;

		if ( ! is_user_logged_in() )
			return;

		echo '<div class="gnetwork-wrap -footer -already">';
			_ex( 'You are already logged in.', 'Modules: Login', 'gnetwork' );
		echo '</div>';
	}

	public function login_footer_badge()
	{
		if ( ! empty( $GLOBALS['interim_login'] ) )
			return;

		$this->render_badge();
	}

	public function render_badge()
	{
		if ( GNETWORK_DISABLE_CREDITS )
			return;

		echo '<div class="gnetwork-wrap -footer -badge">';

			if ( $credits = WordPress\Site::customFile( 'credits-badge.png' ) )
				echo Core\HTML::img( $credits );

			else
				echo Utilities::creditsBadge();

		echo '</div>';
	}

	public function lost_password_html_link( $html_link )
	{
		return Utilities::prepDescription( $this->options['reset_message'], TRUE, FALSE );
	}

	public static function getLoginStyleLink( $style = NULL, $text = FALSE )
	{
		if ( is_null( $style ) )
			$style = Utilities::customStyleSheet( 'login.css', FALSE );

		if ( $style )
			return Core\HTML::tag( 'a', [
				'href'   => $style,
				'title'  => _x( 'Full URL to the current login style file', 'Modules: Login', 'gnetwork' ),
				'target' => '_blank',
			], ( $text ? _x( 'Login Style', 'Modules: Login', 'gnetwork' ) : Core\HTML::getDashicon( 'admin-customizer' ) ) );

		return FALSE;
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

// Originally based on : Simple Login Lockdown v1.1
// BY: chrisguitarguy http://www.pwsausa.org/
// SEE: https://github.com/chrisguitarguy/simple-login-lockdown

class gNetworkLockDown extends gNetworkModuleCore
{

	protected $option_key = 'lockdown';
	protected $network    = TRUE;

	private $locked_prefix = 'gnld_locked_';
	private $failed_prefix = 'gnld_failed_';

	protected function setup_actions()
	{
		$this->register_menu( 'lockdown',
			_x( 'Lockdown', 'LockDown Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( ! $this->options['record_attempts'] )
			return;

		add_filter( 'authenticate', array( $this, 'authenticate' ), 1 );
		add_action( 'wp_login', array( $this, 'wp_login' ) );
		add_action( 'wp_login_failed', array( $this, 'wp_login_failed' ) );
		add_filter( 'shake_error_codes', array( $this, 'shake_error_codes' ) );
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'record_attempts',
					'type'        => 'enabled',
					'title'       => _x( 'Record & Lockdown', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Select to record failed attempts and lockdown after the limit is reached.', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'trust_proxied_ip',
					'type'        => 'enabled',
					'title'       => _x( 'Trust Proxy Data', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Do we trust forwarded IP adresses?', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'failed_limit',
					'type'        => 'select',
					'title'       => _x( 'Login Attempt Limit', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'What is the maximum number of failed login attempts?', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '4',
					'values'      => self::range( 4, 20, 2 ),
				),
				array(
					'field'       => 'locked_expiration',
					'type'        => 'select',
					'title'       => _x( 'Login Lockdown Time', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'How long should the user be locked out?', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '60',
					'values'      => array(
						'30'   => _x( '30 Minutes', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
						'60'   => _x( '60 Minutes', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
						'120'  => _x( '2 Hours', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
						'180'  => _x( '3 Hours', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
						'240'  => _x( '4 Hours', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
						'480'  => _x( '8 Hours', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
						'1440' => _x( '24 Hours', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'locked_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Locked Message', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Locked message on login page.', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text',
				),
			),
		);
	}

	public function default_options()
	{
		return array(
			'record_attempts'   => '0',
			'failed_expiration' => '60',
			'locked_expiration' => '60', // FIXME: better be more than 4 hours?
			'failed_limit'      => '4',
			'trust_proxied_ip'  => '0',
			'locked_notice'     => _x( '<strong>LOCKED OUT</strong>: Too many login attempts from one IP address! Please take a break and try again.', 'LockDown Module', GNETWORK_TEXTDOMAIN ),
		);
	}

	private function get_ip()
	{
		if ( $this->options['trust_proxied_ip'] )
			return array_shift( array_map( 'trim', explode( ',', self::IP() ) ) );

		if ( getenv( 'HTTP_FORWARDED' ) )
			return getenv( 'HTTP_FORWARDED' );

		return '';
	}

	private function locked( $ip )
	{
		return (bool) get_site_transient( $this->locked_prefix.$ip );
	}

	private function failed( $ip )
	{
		$failed = get_site_transient( $this->failed_prefix.$ip );
		if ( $failed )
			return absint( $failed );
		return 0;
	}

	private function clear( $ip )
	{
		 delete_site_transient( $this->locked_prefix.$ip );
		 delete_site_transient( $this->failed_prefix.$ip );
	}

	private function fail( $ip )
	{
		$failed = $this->failed( $ip ) + 1;
		set_site_transient( $this->failed_prefix.$ip, $failed, $this->options['failed_expiration'] * 60 );
		return $failed;
	}

	private function lock( $ip )
	{
		set_site_transient( $this->locked_prefix.$ip, TRUE, $this->options['locked_expiration'] * 60 );
	}

	// make sure auth cookie really get cleared (for this session too)
	private function cookies()
	{
		wp_clear_auth_cookie();

		if ( ! empty( $_COOKIE[AUTH_COOKIE] ) )
			$_COOKIE[AUTH_COOKIE] = '';

		if ( ! empty( $_COOKIE[SECURE_AUTH_COOKIE] ) )
			$_COOKIE[SECURE_AUTH_COOKIE] = '';

		if ( ! empty( $_COOKIE[LOGGED_IN_COOKIE] ) )
			$_COOKIE[LOGGED_IN_COOKIE] = '';
	}

	public function authenticate( $user )
	{
		$ip = $this->get_ip();

		if ( empty( $ip ) )
			return $user;

		if ( ! $this->locked( $ip ) )
			return $user;

		do_action( 'gnetwork_lockdown_attempt', $ip );

		remove_action( 'wp_login_failed', array( $this, 'wp_login_failed' ) );
		remove_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );

		return new WP_Error( 'gnetwork_lockdown_locked', $this->options['locked_notice'] );
	}

	public function wp_login()
	{
		$ip = $this->get_ip();

		if ( empty( $ip ) )
			return;

		$this->clear( $ip );
	}

	public function wp_login_failed()
	{
		$ip = $this->get_ip();

		if ( empty( $ip ) )
			return;

		if ( apply_filters( 'gnetwork_lockdown_allow_ip', FALSE, $ip ) )
			return;

		$this->cookies();
		$failed = $this->fail( $ip );

		if ( $failed > $this->options['failed_limit'] ) {
			$this->clear( $ip );
			$this->lock( $ip );
			do_action( 'gnetwork_lockdown_locked', $ip );
		}
	}

	public function shake_error_codes( $error_codes )
	{
		$error_codes[] = 'gnetwork_lockdown_locked';
		return $error_codes;
	}
}

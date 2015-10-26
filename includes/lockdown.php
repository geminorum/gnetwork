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
			__( 'Login Lockdown', GNETWORK_TEXTDOMAIN ),
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
					'field'   => 'record_attempts',
					'type'    => 'enabled',
					'title'   => __( 'Record & Lockdown', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Select to record failed attempts and lockdown after the limit is reached.', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'trust_proxied_ip',
					'type'    => 'enabled',
					'title'   => __( 'Trust Proxy Data', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Do we trust forwarded IP adresses?', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'failed_limit',
					'type'    => 'select',
					'title'   => __( 'Login Attempt Limit', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'What is the maximum number of failed login attempts?', GNETWORK_TEXTDOMAIN ),
					'default' => '4',
					'values'  => gNetworkUtilities::range( 4, 20, 2 ),
				),
				array(
					'field'   => 'locked_expiration',
					'type'    => 'select',
					'title'   => __( 'Login Lockdown Time', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'How long should the user be locked out?', GNETWORK_TEXTDOMAIN ),
					'default' => '60',
					'values'  => array(
						'30'   => __( '30 Minutes', GNETWORK_TEXTDOMAIN ),
						'60'   => __( '60 Minutes', GNETWORK_TEXTDOMAIN ),
						'120'  => __( '2 Hours'   , GNETWORK_TEXTDOMAIN ),
						'180'  => __( '3 Hours'   , GNETWORK_TEXTDOMAIN ),
						'240'  => __( '4 Hours'   , GNETWORK_TEXTDOMAIN ),
						'480'  => __( '8 Hours'   , GNETWORK_TEXTDOMAIN ),
						'1440' => __( '24 Hours'  , GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'locked_notice',
					'type'        => 'textarea',
					'title'       => __( 'Locked Message', GNETWORK_TEXTDOMAIN ),
					'desc'        => __( 'Locked message on login page.', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text code',
				),
			),
		);
	}

	public function default_options()
	{
		return array(
			'record_attempts'   => '0',
			'failed_expiration' => '60',
			'locked_expiration' => '60', // must be more than 4 hours
			'failed_limit'      => '4',
			'trust_proxied_ip'  => '0',
			'locked_notice'     => __( '<strong>LOCKED OUT:</strong> Too many login attempts from one IP address! Please take a break and try again.', GNETWORK_TEXTDOMAIN ),
		);
	}

	private function get_ip()
	{
		if ( $this->options['trust_proxied_ip'] )
			return array_shift( array_map( 'trim', explode( ',', gNetworkUtilities::IP() ) ) );

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

	function shake_error_codes( $error_codes )
	{
		$error_codes[] = 'gnetwork_lockdown_locked';
		return $error_codes;
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\WordPress;

class Lockdown extends gNetwork\Module
{
	protected $key = 'lockdown';

	private $locked_prefix = 'gnld_locked_';
	private $failed_prefix = 'gnld_failed_';

	protected function setup_actions()
	{
		if ( ! $this->options['record_attempts'] )
			return;

		// Originally based on : Simple Login Lockdown v1.1
		// BY: chrisguitarguy http://www.pwsausa.org/
		// SEE: https://github.com/chrisguitarguy/simple-login-lockdown

		$this->filter( 'authenticate', 1, 1 );
		$this->action( 'wp_login' );
		$this->action( 'wp_login_failed' );
		$this->filter( 'shake_error_codes' ); // FIXME: must be on login only?
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Lockdown', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'record_attempts'   => '0',
			'failed_expiration' => '60',
			'locked_expiration' => '60', // FIXME: better be more than 4 hours?
			'failed_limit'      => '4',
			'trust_proxied_ip'  => '0',
			'locked_notice'     => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'record_attempts',
					'title'       => _x( 'Record & Lockdown', 'Modules: Lockdown: Settings', 'gnetwork' ),
					'description' => _x( 'Select to record failed attempts and lockdown after the limit is reached.', 'Modules: Lockdown: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'trust_proxied_ip',
					'title'       => _x( 'Trust Proxy Data', 'Modules: Lockdown: Settings', 'gnetwork' ),
					'description' => _x( 'Do we trust forwarded IP adresses?', 'Modules: Lockdown: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'failed_limit',
					'type'        => 'select',
					'title'       => _x( 'Login Attempt Limit', 'Modules: Lockdown: Settings', 'gnetwork' ),
					'description' => _x( 'What is the maximum number of failed login attempts?', 'Modules: Lockdown: Settings', 'gnetwork' ),
					'default'     => '4',
					'values'      => Core\Arraay::range( 4, 20, 2 ),
				],
				[
					'field'       => 'locked_expiration',
					'type'        => 'select',
					'title'       => _x( 'Login Lockdown Time', 'Modules: Lockdown: Settings', 'gnetwork' ),
					'description' => _x( 'How long should the user be locked out?', 'Modules: Lockdown: Settings', 'gnetwork' ),
					'default'     => '60',
					'values'      => Settings::minutesOptions(),
				],
				[
					'field'       => 'locked_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Locked Message', 'Modules: Lockdown: Settings', 'gnetwork' ),
					'description' => _x( 'Locked message on login page.', 'Modules: Lockdown: Settings', 'gnetwork' ),
					'default'     => _x( '<strong>LOCKED OUT</strong>: Too many login attempts from one IP address! Please take a break and try again.', 'Modules: Lockdown: Settings', 'gnetwork' ),
				],
			],
		];
	}

	private function get_ip()
	{
		if ( $this->options['trust_proxied_ip'] )
			return array_shift( array_map( 'trim', explode( ',', Core\HTTP::IP() ) ) );

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
		if ( $failed = get_site_transient( $this->failed_prefix.$ip ) )
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

		remove_action( 'wp_login_failed', [ $this, 'wp_login_failed' ] );
		remove_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );

		return new Core\Error( 'gnetwork_lockdown_locked', $this->options['locked_notice'] );
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

		if ( $this->filters( 'allow_ip', FALSE, $ip ) )
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

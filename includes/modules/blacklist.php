<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class BlackList extends ModuleCore
{

	protected $key     = 'blacklist';
	protected $network = TRUE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		if ( ! is_admin() && $this->options['check_ip'] )
			add_action( 'init', array( $this, 'init' ), -10 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Black List', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function init()
	{
		if ( ! is_user_logged_in() && $this->blacklisted() )
			wp_die( $this->options['blacklisted_notice'] );
	}

	public function default_options()
	{
		return array(
			'check_ip'           => '0',
			'blacklisted_ips'    => '',
			'blacklisted_notice' => '',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'check_ip',
					'title'       => _x( 'Check Addresses', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Select to check logged-out visitor\'s IP againts your list of IPs', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'blacklisted_ips',
					'type'        => 'textarea',
					'title'       => _x( 'IP Addresses', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Comma seperated IP\'s range or individual IP needs to block. ex: <code>1.6.0.0 - 1.7.255.255,1.8.0.0,1.8.0.1</code>', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'large-text', 'code-text' ),
				),
				array(
					'field'       => 'blacklisted_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Blacklisted Message', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Locked message on WordPress die page', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'you\'re blacklisted!',
					'field_class' => array( 'large-text', 'code-text' ),
				),
			),
		);
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( class_exists( __NAMESPACE__.'\\Debug' ) )
			Debug::summaryIPs( _x( 'Your IP Summary', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ) );
		else
			printf( _x( 'Your IP: <code title="%s">%s</code>', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ), HTTP::IP(), $_SERVER['REMOTE_ADDR'] );
	}

	private function blacklisted()
	{
		if ( ! trim( $this->options['blacklisted_ips'] ) )
			return FALSE;

		$groups = explode( ',', $this->options['blacklisted_ips'] );
		$long   = ip2long( $_SERVER['REMOTE_ADDR'] );

		foreach ( $groups as $group ) {
			if ( FALSE === strpos( $group, '-' ) ) {
				if ( $long == ip2long( trim( $group ) ) )
					return TRUE;
			} else {
				$range = array_map( 'trim', explode( '-', $group ) );
				if ( $long >= ip2long( $range[0] )
					&& $long <= ip2long( $range[1] ) )
						return TRUE;
			}
		}

		return FALSE;
	}
}

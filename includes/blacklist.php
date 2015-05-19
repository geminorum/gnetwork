<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBlackList extends gNetworkModuleCore
{

	var $_network    = true;
	var $_option_key = 'blacklist';
	var $_ajax       = true;
	// var $_dev        = false;

	public function setup_actions()
	{
		if ( ! is_admin() && $this->options['check_ip'] )
			add_action( 'init', array( &$this, 'init' ), 1 );

		gNetworkNetwork::registerMenu( 'blacklist',
			__( 'Black List', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);
	}

	public function init()
	{
		if ( ! is_user_logged_in() && $this->blacklisted() )
			wp_die( $this->options['blacklisted_notice'] );
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field' => 'check_ip',
					'type' => 'enabled',
					'title' => __( 'Check Addresses', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Select to check logged-out visitor\'s IP againts your list of IPs', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field' => 'blacklisted_ips',
					'type' => 'textarea',
					'title' => __( 'IP Addresses', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Comma seperated IP\'s range or individual IP needs to block. ex: <code>1.6.0.0 - 1.7.255.255,1.8.0.0,1.8.0.1</code>', GNETWORK_TEXTDOMAIN ),
					'class' => 'large-text code',
				),
				array(
					'field' => 'blacklisted_notice',
					'type' => 'textarea',
					'title' => __( 'Blacklisted Message', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Locked message on login page.', GNETWORK_TEXTDOMAIN ),
					'class' => 'large-text',
				),
				array(
					'field' => 'debug',
					'type' => 'debug',
				),
			),
		);
	}

	public function default_options()
    {
        return array(
            'check_ip' => '0',
            'blacklisted_ips' => '',
            'blacklisted_notice' => '', //__( '', GNETWORK_TEXTDOMAIN ),
        );
    }

	public function settings( $sub = null )
	{
		if ( 'blacklist' == $sub ) {
			$this->update( $sub );
			add_action( 'gnetwork_network_settings_sub_blacklist', array( &$this, 'settings_html' ), 10, 2 );
			$this->register_settings();
		}
	}

	private function blacklisted()
	{
		if ( ! trim( $this->options['blacklisted_ips'] ) )
			return false;

		$groups = explode( ',', $this->options['blacklisted_ips'] );
		$long = ip2long( $_SERVER['REMOTE_ADDR'] );

		foreach( $groups as $group ) {
			if ( false === strpos( $group, '-' ) ) {
				if ( $long == ip2long( trim( $group ) ) )
					return true;
			} else {
				$range = array_map('trim', explode( '-', $group ) );
				if ( $long >= ip2long( $range[0] )
					&& $long <= ip2long( $range[1] ) )
						return true;
			}
		}

		return false;
	}
}

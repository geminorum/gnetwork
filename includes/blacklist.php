<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBlackList extends gNetworkModuleCore
{

	var $_network    = TRUE;
	var $_option_key = 'blacklist';
	var $_ajax       = TRUE;

	protected function setup_actions()
	{
		if ( ! is_admin() && $this->options['check_ip'] )
			add_action( 'init', array( $this, 'init' ), 1 );

		$this->register_menu( 'blacklist',
			__( 'Black List', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
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
					'field'   => 'check_ip',
					'type'    => 'enabled',
					'title'   => __( 'Check Addresses', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Select to check logged-out visitor\'s IP againts your list of IPs', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'       => 'blacklisted_ips',
					'type'        => 'textarea',
					'title'       => __( 'IP Addresses', GNETWORK_TEXTDOMAIN ),
					'desc'        => __( 'Comma seperated IP\'s range or individual IP needs to block. ex: <code>1.6.0.0 - 1.7.255.255,1.8.0.0,1.8.0.1</code>', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text code',
				),
				array(
					'field'       => 'blacklisted_notice',
					'type'        => 'textarea',
					'title'       => __( 'Blacklisted Message', GNETWORK_TEXTDOMAIN ),
					'desc'        => __( 'Locked message on login page.', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text',
				),
			),
		);
	}

	public function default_options()
	{
		return array(
			'check_ip'           => '0',
			'blacklisted_ips'    => '',
			'blacklisted_notice' => '',
		);
	}

	private function blacklisted()
	{
		if ( ! trim( $this->options['blacklisted_ips'] ) )
			return FALSE;

		$groups = explode( ',', $this->options['blacklisted_ips'] );
		$long = ip2long( $_SERVER['REMOTE_ADDR'] );

		foreach ( $groups as $group ) {
			if ( FALSE === strpos( $group, '-' ) ) {
				if ( $long == ip2long( trim( $group ) ) )
					return TRUE;
			} else {
				$range = array_map('trim', explode( '-', $group ) );
				if ( $long >= ip2long( $range[0] )
					&& $long <= ip2long( $range[1] ) )
						return TRUE;
			}
		}

		return FALSE;
	}
}

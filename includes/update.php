<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkUpdate extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = TRUE;
	protected $front_end  = FALSE;
	protected $ajax       = TRUE;

	protected function setup_actions()
	{
		add_action( 'admin_init', array( $this, 'admin_init_late' ), 100 );

		// add_filter( 'automatic_updater_disabled', '__return_true' );
		// add_filter( 'auto_update_core', '__return_false' );
	}

	public function admin_init_late()
	{
		// hide the update WordPress reminder from all users that are not assumed Administrators (cannot upgrade plugins).
		// from : http://wordpress.org/extend/plugins/hide-update-reminder/
		if ( ! current_user_can( 'update_plugins' ) ) {
			remove_action( 'admin_notices', 'update_nag', 3 );
			remove_action( 'network_admin_notices', 'update_nag', 3 );
		}

		// remove the wordpress update notification for all users except admin
		if ( ! current_user_can( 'manage_options' ) ) {
			remove_all_actions( 'wp_version_check' );
			add_filter( 'pre_option_update_core', '__return_null' );
			add_filter( 'pre_site_transient_update_core', '__return_null' );
		}
	}
}

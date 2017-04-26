<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;

class Update extends \geminorum\gNetwork\ModuleCore
{

	protected $key   = 'update';
	protected $front = FALSE;
	protected $ajax  = TRUE;

	protected function setup_actions()
	{
		$this->action( 'admin_init', 0, 100 );

		// add_filter( 'automatic_updater_disabled', '__return_true' );
		// add_filter( 'auto_update_core', '__return_false' );

		// disable asynchronous and automatic background translation updates
		// @REF: https://make.wordpress.org/core/2014/09/05/language-chooser-in-4-0/
		add_filter( 'async_update_translation', '__return_false' );
		add_filter( 'auto_update_translation', '__return_false' );
	}

	public function admin_init()
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

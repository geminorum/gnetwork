<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCleanup extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = false;

	public function setup_actions()
	{
		add_action( 'plugins_loaded'    , array( &$this, 'plugins_loaded'     ), 10 );
		add_action( 'wp_default_scripts', array( &$this, 'wp_default_scripts' ), 9  );

		add_filter( 'wpcf7_load_css', '__return_false', 15 );
	}

	public function plugins_loaded()
	{
		// added by: Search Everything
		// http://wordpress.org/plugins/search-everything/
		remove_action( 'wp_enqueue_scripts', 'se_enqueue_styles' );
		remove_action( 'wp_head', 'se_global_head' );
	}

	// http://www.paulund.co.uk/remove-jquery-migrate-file-wordpress
	public function wp_default_scripts( &$scripts )
	{
		if( is_admin() )
			return;

		$scripts->remove( 'jquery' );
		$scripts->add( 'jquery', false, array( 'jquery-core' ), '1.11.1' );
	}
}

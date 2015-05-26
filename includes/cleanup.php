<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCleanup extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = false;

	public function setup_actions()
	{
		add_action( 'plugins_loaded'    , array( &$this, 'plugins_loaded'     ), 10 );
		add_action( 'init'              , array( &$this, 'init'               ), 12 );
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

	// TODO: add option and/or global constant
	// from : Disable Emojis v1.5
	// https://wordpress.org/plugins/disable-emojis/
	public function init()
	{
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		add_filter( 'tiny_mce_plugins', array( &$this, 'tiny_mce_plugins'               ) );
	}

	public function tiny_mce_plugins( $plugins )
	{
		if ( is_array( $plugins ) )
			return array_diff( $plugins, array( 'wpemoji' ) );
		return array();
	}
}

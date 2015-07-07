<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCleanup extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	protected function setup_actions()
	{
		add_action( 'plugins_loaded' , array( &$this, 'plugins_loaded' ), 10 );
		add_action( 'init' , array( &$this, 'init' ), 12 );
		add_action( 'wp_default_scripts', array( &$this, 'wp_default_scripts' ), 9 );

		add_action( 'admin_menu', array( &$this, 'admin_menu_late' ), 999 );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ), 999 );

		add_action( 'wp_network_dashboard_setup', array( &$this, 'wp_dashboard_setup' ), 20 );
		add_action( 'wp_user_dashboard_setup', array( &$this, 'wp_dashboard_setup' ), 20 );
		add_action( 'wp_dashboard_setup', array( &$this, 'wp_dashboard_setup' ), 20 );

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
		$scripts->add( 'jquery', FALSE, array( 'jquery-core' ), '1.11.1' );
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

	// http://justintadlock.com/archives/2011/06/13/removing-menu-pages-from-the-wordpress-admin
	public function admin_menu_late()
	{
		if ( ! self::cuc( 'update_plugins' ) ) {
			remove_menu_page( 'link-manager.php' );
			remove_submenu_page( 'themes.php', 'theme-editor.php' );
		}

		if ( is_multisite() ) {
			remove_menu_page( 'bruteprotect-config' ); // BruteProtect notice
		}
	}

	public function admin_enqueue_scripts()
	{
		if ( defined( 'BRUTEPROTECT_VERSION' ) )
			wp_dequeue_style( 'bruteprotect-css' ); // BruteProtect global css!!
	}

	// TODO : http://code.tutsplus.com/articles/quick-tip-customising-and-simplifying-the-wordpress-admin-for-your-clients--wp-28526
	// https://gist.github.com/chrisguitarguy/1377965
	public function wp_dashboard_setup()
	{
		$screen = get_current_screen();

		// Removes the "Right Now" widget that tells you post/comment counts and what theme you're using.
		// remove_meta_box( 'dashboard_right_now', $screen, 'normal' );

		// Removes the recent comments widget
		// remove_meta_box( 'dashboard_recent_comments', $screen, 'normal' );

		// Removes the incoming links widget.
		// remove_meta_box( 'dashboard_incoming_links', $screen, 'normal' );

		// Removes the plugins widgets that displays the most popular, newest, and recently updated plugins
		remove_meta_box( 'dashboard_plugins', $screen, 'normal' );

		// Removes the quick press widget that allows you post right from the dashboard
		// remove_meta_box( 'dashboard_quick_press', $screen, 'side' );

		// Removes the widget containing the list of recent drafts
		// remove_meta_box( 'dashboard_recent_drafts', $screen, 'side' );

		// Removes the "WordPress Blog" widget
		remove_meta_box( 'dashboard_primary', $screen, 'side' );

		// Removes the "Other WordPress News" widget
		remove_meta_box( 'dashboard_secondary', $screen, 'side' );
	}
}

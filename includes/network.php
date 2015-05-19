<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkNetwork extends gNetworkModuleCore
{

	var $_option_key = false;
	var $_network    = true;
	var $menus       = array();

	public function setup_actions()
	{
		if ( ! is_multisite() )
			return;

		if ( is_admin() ) {

			//add_filter( 'all_plugins', array( & $this, 'all_plugins' ) );
			//add_action( 'load-index.php', array( & $this, 'load_index_php' ) ); // SPEED CAUTIONS
			add_action( 'network_admin_menu', array( & $this, 'network_admin_menu' ) );
		} else {

			add_filter( 'blog_redirect_404', '__return_false' ); // prevent: maybe_redirect_404()

		}

		add_action( 'wpmu_new_blog', array( & $this, 'wpmu_new_blog' ), 12, 6 );

		if ( GNETWORK_ADMIN_COLUMN_ID ) {
			add_filter( 'wpmu_blogs_columns', array( & $this, 'wpmu_blogs_columns' ) );
			add_action( 'manage_sites_custom_column', array( & $this, 'manage_blogs_custom_column' ), 10, 2 );
			add_action( 'manage_blogs_custom_column', array( & $this, 'manage_blogs_custom_column' ), 10, 2 );
		}

		if ( GNETWORK_LARGE_NETWORK_IS )
			add_filter( 'wp_is_large_network', array( & $this, 'wp_is_large_network' ), 10, 3 );
	}

	function wp_is_large_network( $is, $using, $count )
	{
		if ( 'users' == $using )
			return $count > GNETWORK_LARGE_NETWORK_IS;
		return $is;
	}

	function network_admin_menu()
	{
		add_submenu_page( 'plugins.php',
			__( 'Active', GNETWORK_TEXTDOMAIN ),
			__( 'Active', GNETWORK_TEXTDOMAIN ),
			'manage_network',
			'plugins.php?plugin_status=active'
		);

		add_submenu_page( 'plugins.php',
			__( 'Upload', GNETWORK_TEXTDOMAIN ),
			__( 'Upload', GNETWORK_TEXTDOMAIN ),
			'manage_network',
			'plugin-install.php?tab=upload'
		);

		add_submenu_page( 'themes.php',
			__( 'Upload', GNETWORK_TEXTDOMAIN ),
			__( 'Upload', GNETWORK_TEXTDOMAIN ),
			'manage_network_themes',
			'theme-install.php?upload'
		);

		$hook = add_menu_page(
			__( 'gNetwork Extras', GNETWORK_TEXTDOMAIN ),
			_x( 'Extras', 'Network Menu Title', GNETWORK_TEXTDOMAIN ),
			'manage_network_options',
			'gnetwork',
			array( & $this, 'settings_page' ),
			'dashicons-screenoptions',
			120
		);

		add_action( 'load-'.$hook, array( & $this, 'network_settings_load' ) );

		foreach( $this->menus as $sub => $args ) {
			add_submenu_page( 'gnetwork',
				sprintf( __( 'gNetwork Extras: %s', GNETWORK_TEXTDOMAIN ), $args['title'] ),
				$args['title'],
				$args['cap'],
				'gnetwork&sub='.$sub,
				array( & $this, 'settings_page' )
			);
		}

		global $submenu;
		$submenu['gnetwork'][0][0] = __( 'Overview', GNETWORK_TEXTDOMAIN );
    }

	public static function registerMenu( $sub, $title = null, $callback = false, $capability = 'manage_network_options' )
	{
		if ( ! is_network_admin() || self::isAJAX() )
			return;

		global $gNetwork;

		$gNetwork->network->menus[$sub] = array(
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		);

		if ( $callback ) // && is_callable( $callback ) )
			add_action( 'gnetwork_network_settings', $callback );
	}

	public static function settingsURL( $full = true )
	{
		$relative = 'admin.php?page=gnetwork';

		if ( $full )
			return network_admin_url( $relative );

		return $relative;
	}

	public function network_settings_load()
	{
		global $submenu_file;

		if ( isset( $_REQUEST['sub'] ) ) {
			$sub = $_REQUEST['sub'];
			$submenu_file = 'gnetwork&sub='.$sub;
		} else {
			$sub = null;
		}

		do_action( 'gnetwork_network_settings', $sub );

		// ALL DEPRECATED
		do_action( 'gnetwork_network_settings_load', $sub );
		do_action( 'gnetwork_network_settings_save', $sub );
		do_action( 'gnetwork_network_settings_register', $sub );
		do_action( 'gnetwork_network_settings_help', $sub );
	}

    private function subs()
	{
		$subs = array(
			'overview' => __( 'Overview', GNETWORK_TEXTDOMAIN ),
			//'general' => __( 'General', GNETWORK_TEXTDOMAIN ),
		);

		foreach( $this->menus as $sub => $args )
			$subs[$sub] = $args['title'];

		if ( is_super_admin() )
			$subs['console'] = __( 'Console', GNETWORK_TEXTDOMAIN );

		return $subs;
	}

	public function settings_page()
	{
        $settings_uri = self::settingsURL( false );
		$sub = isset( $_REQUEST['sub'] ) ? trim( $_REQUEST['sub'] ) : 'overview';
		$subs = apply_filters( 'gnetwork_network_settings_subs', $this->subs() );

		$messages = apply_filters( 'gnetwork_network_settings_messages', array(
			'resetting' => gNetworkUtilities::notice( __( 'Resetting Settings.', GNETWORK_TEXTDOMAIN ), 'updated fade', false ),
			'updated' => gNetworkUtilities::notice( __( 'Settings updated.', GNETWORK_TEXTDOMAIN ), 'updated fade', false ),
			'error' => gNetworkUtilities::notice( __( 'Error while saving settings.', GNETWORK_TEXTDOMAIN ), 'error', false ),
		) );

		echo '<div class="wrap">';
			self::sideNotification();
			printf( '<h2>%s</h2>', __( 'gNetwork Extras', GNETWORK_TEXTDOMAIN ) );

			gNetworkUtilities::headerNav( $settings_uri, $sub, $subs );

			if ( isset( $_REQUEST['message'] ) ) {

				if ( isset( $messages[$_REQUEST['message']] ) )
					echo $messages[$_REQUEST['message']];
				else
					gNetworkUtilities::notice( $_REQUEST['message'] );

				$_SERVER['REQUEST_URI'] = remove_query_arg( 'message', $_SERVER['REQUEST_URI'] );
			}

			if ( file_exists( GNETWORK_DIR.'admin/network.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'admin/network.'.$sub.'.php' );
			else
				do_action( 'gnetwork_network_settings_sub_'.$sub, $settings_uri, $sub );

		echo '<div class="clear"></div></div>';
	}

	// http://wpengineer.com/2470/hide-welcome-panel-for-wordpress-multisite/
	function load_index_php()
	{
		if ( 2 === (int) get_user_meta( get_current_user_id(), 'show_welcome_panel', true ) )
			update_user_meta( get_current_user_id(), 'show_welcome_panel', 0 );
	}

	// ALSO SEE: http://stackoverflow.com/a/10372861
	function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta )
	{
		switch_to_blog( $blog_id );

		$new_blog_options = apply_filters( 'gnetwork_new_blog_options', array(
			'blogdescription'        => '',
			'permalink_structure'    => '/entries/%post_id%',
			'default_comment_status' => 'closed',
			'default_ping_status'    => 'closed',
		) );

		foreach ( $new_blog_options as $new_blog_option_key => $new_blog_option )
			update_option( $new_blog_option_key, $new_blog_option );

		$post = get_post( 1 );
		wp_transition_post_status( 'draft', $post->post_status, $post );
		$page = get_post( 2 );
		wp_transition_post_status( 'draft', $page->post_status, $page );
		// TODO : disble comments on first page

		restore_current_blog();
		refresh_blog_details( $blog_id );
	}

	// BASED ON : https://gist.github.com/franz-josef-kaiser/6730571
	// by : Franz Josef Kaiser <wecodemore@gmail.com>
	function wpmu_blogs_columns( $columns )
	{
		if ( 1 === GNETWORK_ADMIN_COLUMN_ID )
			return array_merge( array( 'id' => __( 'ID', GNETWORK_TEXTDOMAIN ) ), $columns );

		$columns['id'] = __( 'ID', GNETWORK_TEXTDOMAIN );
		return $columns;
	}

	function manage_blogs_custom_column( $column_name, $blog_id )
	{
		if ( 'id' === $column_name )
			echo $blog_id;
		return $column_name;
	}

	// http://teleogistic.net/2013/02/selectively-deprecating-wordpress-plugins-from-dashboard-plugins/
	// https://gist.github.com/boonebgorges/5057165
	/**
	 * Prevent specific plugins from being activated (or, in some cases, deactivated).
	 * Plugins that are to be deprecated should be added to the $disabled_plugins array.
	 * Plugins that should be un-deactivatable should be added to the $undeactivatable_plugins array
	 */
	function all_plugins( $plugins )
	{
		// Allow the super admin to see all plugins, by adding the URL param
		// show_all_plugins=1
		if ( is_super_admin() && ! empty( $_GET['show_all_plugins'] ) ) {
			return $plugins;
		}

		// The following plugins are disabled
		$disabled_plugins = array(
			'cforms/cforms.php',
			'wpng-calendar/wpng-calendar.php'
		);

		// By default, allow all disabled plugins to appear if they are
		// already active on the current site. This lets administrators
		// disable them. However, if you want a given plugin to be unlisted
		// even when enabled, add it to this array
		$undeactivatable_plugins = array(
			'cforms/cforms.php',
		);

		foreach ( $disabled_plugins as $disabled_plugin ) {
			if ( array_key_exists( $disabled_plugin, $plugins ) &&
				 ( in_array( $disabled_plugin, $undeactivatable_plugins ) || ! is_plugin_active( $disabled_plugin ) )
			) {
				unset( $plugins[ $disabled_plugin ] );
			}
		}

		return $plugins;
	}

}

// http://wp.tutsplus.com/tutorials/widgets/how-to-build-custom-dashboard-widgets/
// http://wplift.com/spring-clean-how-to-tidy-up-your-wordpress-site-make-it-faster
// http://wordpress.org/plugins/wpmu-new-blog-defaults/

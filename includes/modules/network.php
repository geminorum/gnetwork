<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Network extends ModuleCore
{

	protected $key = 'network';

	public $menus = array();

	protected function setup_actions()
	{
		if ( ! is_multisite() )
			throw new Exception( 'Only on Multisite!' );

		if ( is_admin() ) {

			// add_filter( 'all_plugins', array( $this, 'all_plugins' ) );
			// add_action( 'load-index.php', array( $this, 'load_index_php' ) ); // SPEED CAUTIONS

			add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );

			add_filter( 'wpmu_blogs_columns', array( $this, 'wpmu_blogs_columns' ), 20 );
			add_action( 'manage_sites_custom_column', array( $this, 'manage_sites_custom_column' ), 10, 2 );

		} else {

			add_filter( 'blog_redirect_404', '__return_false' ); // prevent: maybe_redirect_404()
		}

		add_action( 'wpmu_new_blog', array( $this, 'wpmu_new_blog' ), 12, 6 );

		if ( GNETWORK_LARGE_NETWORK_IS )
			add_filter( 'wp_is_large_network', array( $this, 'wp_is_large_network' ), 10, 3 );
	}

	public function wp_is_large_network( $is, $using, $count )
	{
		if ( 'users' == $using )
			return $count > GNETWORK_LARGE_NETWORK_IS;

		return $is;
	}

	public function network_admin_menu()
	{
		do_action( 'gnetwork_setup_menu', 'network' );

		add_submenu_page( 'plugins.php',
			_x( 'Active', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Active', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			'manage_network',
			'plugins.php?plugin_status=active'
		);

		add_submenu_page( 'plugins.php',
			_x( 'Upload', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Upload', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			'manage_network',
			'plugin-install.php?tab=upload'
		);

		$hook = add_menu_page(
			_x( 'gNetwork Extras', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Extras', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			'manage_network_options',
			$this->base,
			array( $this, 'settings_page' ),
			'dashicons-screenoptions',
			120
		);

		add_action( 'load-'.$hook, array( $this, 'network_settings_load' ) );

		foreach ( $this->menus as $sub => $args ) {
			add_submenu_page( $this->base,
				sprintf( _x( 'gNetwork Extras: %s', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
				$args['title'],
				$args['cap'],
				$this->base.'&sub='.$sub,
				array( $this, 'settings_page' )
			);
		}

		global $submenu;
		$submenu[$this->base][0][0] = _x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_network_options' )
	{
		if ( ! is_network_admin() || WordPress::isAJAX() )
			return;

		gNetwork()->network->menus[$sub] = array(
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		);

		if ( $callback ) // && is_callable( $callback ) )
			add_action( 'gnetwork_network_settings', $callback );
	}

	public function network_settings_load()
	{
		global $submenu_file;

		if ( isset( $_REQUEST['sub'] ) ) {
			$sub = $_REQUEST['sub'];
			$submenu_file = $this->base.'&sub='.$sub;
		} else {
			$sub = NULL;
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
			'overview' => _x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
		);

		foreach ( $this->menus as $sub => $args )
			$subs[$sub] = $args['title'];

		if ( is_super_admin() ) {
			$subs['phpinfo'] = _x( 'PHP Info', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );
			$subs['console'] = _x( 'Console', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );
		}

		return $subs;
	}

	public function settings_page()
	{
		$uri  = Settings::networkURL( FALSE );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'settings_subs', $this->subs() );

		echo '<div class="wrap gnetwork-admin-settings-wrap settings-network sub-'.$sub.'">';

			$messages = $this->filters( 'settings_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' );
			else
				$this->actions( 'settings_sub_'.$sub, $uri, $sub );

		echo '<div class="clear"></div></div>';
	}

	public static function getLogo( $wrap = FALSE, $fallback = TRUE, $logo = NULL )
	{
		$html = '';

		if ( ! is_null( $logo ) ) {

			$html .= HTML::tag( 'img', array(
				'src' => $logo,
				'alt' => GNETWORK_NAME,
			) );

		} else if ( file_exists( WP_CONTENT_DIR.'/'.GNETWORK_LOGO ) ) {

			$html .= HTML::tag( 'img', array(
				'src' => WP_CONTENT_URL.'/'.GNETWORK_LOGO,
				'alt' => GNETWORK_NAME,
			) );

		} else if ( $fallback ) {
			$html .= GNETWORK_NAME;
		}

		if ( ! $html )
			return '';

		$html = HTML::tag( 'a', array(
			'href'  => GNETWORK_BASE,
			'title' => GNETWORK_NAME,
		), $html );

		if ( $wrap )
			$html = HTML::tag( $wrap, array(
				'class' => 'logo',
			), $html );

		return $html;
	}

	// http://wpengineer.com/2470/hide-welcome-panel-for-wordpress-multisite/
	public function load_index_php()
	{
		if ( 2 === (int) get_user_meta( get_current_user_id(), 'show_welcome_panel', TRUE ) )
			update_user_meta( get_current_user_id(), 'show_welcome_panel', 0 );
	}

	// TODO: http://stackoverflow.com/a/10372861
	public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta )
	{
		switch_to_blog( $blog_id );

		if ( $site_user_id = WordPress::getSiteUserID() )
			add_user_to_blog( $blog_id, $site_user_id, GNETWORK_SITE_USER_ROLE );

		$new_blog_options = $this->filters( 'new_blog_options', array(
			'blogdescription'        => '',
			'permalink_structure'    => '/entries/%post_id%',
			'default_comment_status' => 'closed',
			'default_ping_status'    => 'closed',
		) );

		foreach ( $new_blog_options as $new_blog_option_key => $new_blog_option )
			update_option( $new_blog_option_key, $new_blog_option );

		wp_update_post( array( 'ID' => 1, 'post_status' => 'draft' ) );
		wp_update_post( array( 'ID' => 2, 'post_status' => 'draft' ) );
		wp_set_comment_status( 1, 'trash' );

		$new_blog_plugins = $this->filters( 'new_blog_plugins', array(
			'geditorial/geditorial.php'     => TRUE,
			'gpersiandate/gpersiandate.php' => TRUE,
		) );

		foreach ( $new_blog_plugins as $new_blog_plugin => $new_blog_plugin_silent )
			activate_plugin( $new_blog_plugin, '', FALSE, $new_blog_plugin_silent );

		restore_current_blog();
		refresh_blog_details( $blog_id );
	}

	public function wpmu_blogs_columns( $columns )
	{
		return array_merge( $columns, array( 'gnetwork-network-id' => _x( 'ID', 'Modules: Network: Column', GNETWORK_TEXTDOMAIN ) ) );
	}

	public function manage_sites_custom_column( $column_name, $blog_id )
	{
		if ( 'gnetwork-network-id' != $column_name )
			return;

		echo '<div class="gnetwork-admin-wrap-column -network -id">';
			echo esc_html( $blog_id );
		echo '</div>';
	}

	// http://teleogistic.net/2013/02/selectively-deprecating-wordpress-plugins-from-dashboard-plugins/
	// https://gist.github.com/boonebgorges/5057165
	/**
	 * Prevent specific plugins from being activated (or, in some cases, deactivated).
	 * Plugins that are to be deprecated should be added to the $disabled_plugins array.
	 * Plugins that should be un-deactivatable should be added to the $undeactivatable_plugins array
	 */
	public function all_plugins( $plugins )
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
				 ( in_array( $disabled_plugin, $undeactivatable_plugins )
					|| ! is_plugin_active( $disabled_plugin ) )
			) {
				unset( $plugins[ $disabled_plugin ] );
			}
		}

		return $plugins;
	}
}

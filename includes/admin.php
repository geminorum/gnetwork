<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkAdmin extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;
	protected $front_end  = FALSE;

	public $menus   = array();
	public $tinymce = array();

	protected function setup_actions()
	{
		// FIXME: is this necessary on frontend?
		add_action( 'init', array( $this, 'init_late' ), 999 );

		// add_action( 'admin_init', array( $this, 'admin_init_early' ), 1 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_menu', array( $this, 'admin_menu_late' ), 999 );

		// add_action( 'wp_network_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );
		// add_action( 'wp_user_dashboard_setup',    array( $this, 'wp_dashboard_setup' ), 20 );
		// add_action( 'wp_dashboard_setup',         array( $this, 'wp_dashboard_setup' ), 20 );

		add_action( 'export_wp', array( $this, 'export_wp' ), 1 );

		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 9999 );
		add_filter( 'update_footer', array( $this, 'update_footer' ), 9999 );

		add_filter( 'manage_pages_columns', array( $this, 'manage_pages_columns' ) );
		add_filter( 'post_date_column_time' , array( $this, 'post_date_column_time' ), 10, 4 );

		if ( GNETWORK_ADMIN_COLUMN_ID ) {
			add_filter( 'manage_pages_columns', array( $this, 'manage_posts_columns_id' ), 12 );
			add_filter( 'manage_posts_columns', array( $this, 'manage_posts_columns_id' ), 12 );
			add_action( 'manage_posts_custom_column', array( $this, 'custom_column_id' ), 5, 2 );
			add_action( 'manage_pages_custom_column', array( $this, 'custom_column_id' ), 5, 2 );
		}

		if ( GNETWORK_ADMIN_COLOUR )
			add_action( 'user_register', array( $this, 'user_register' ) );

		// WORKING but DISABLED
		// add_filter( 'custom_menu_order', '__return_true' );
		// add_filter( 'menu_order', array( $this, 'menu_order' ) );

		// IT MESSES WITH CUSTOM COLUMNS!!
		// add_filter( 'posts_fields', array( $this, 'posts_fields' ), 0, 2 );
	}

	public function init_late()
	{
		if ( 'true' == get_user_option( 'rich_editing' ) && count( $this->tinymce ) ) {
			add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );
		}
	}

	// DISABLED
	public function admin_init_early()
	{
		if ( current_user_can( 'update_plugins' ) ) {
			@ini_set( 'memory_limit', '256M' );
			@ini_set( 'upload_max_size', '64M' );
			@ini_set( 'post_max_size', '64M' );
			@ini_set( 'max_execution_time', '300' );
		}
	}

	public function admin_menu()
	{
		if ( self::cuc( 'manage_options' ) ) {

			$hook = add_menu_page(
				__( 'gNetwork Extras', GNETWORK_TEXTDOMAIN ),
				_x( 'Extras', 'Admin Module: Menu Title', GNETWORK_TEXTDOMAIN ),
				'manage_options',
				'gnetwork',
				array( $this, 'admin_settings_page' ),
				'dashicons-screenoptions',
				120
			);

			foreach ( $this->menus as $sub => $args ) {
				add_submenu_page( 'gnetwork',
					sprintf( __( 'gNetwork Extras: %s', GNETWORK_TEXTDOMAIN ), $args['title'] ),
					$args['title'],
					$args['cap'],
					'gnetwork&sub='.$sub,
					array( $this, 'admin_settings_page' )
				);
			}

		} else {

			$hook = add_submenu_page( 'index.php',
				__( 'gNetwork Extras', GNETWORK_TEXTDOMAIN ),
				_x( 'Extras', 'Admin Module: Menu Title', GNETWORK_TEXTDOMAIN ),
				'read',
				'gnetwork',
				array( $this, 'admin_settings_page' )
			);
		}

		add_action( 'load-'.$hook, array( $this, 'admin_settings_load' ) );

		add_submenu_page( 'plugins.php',
			__( 'Active', GNETWORK_TEXTDOMAIN ),
			__( 'Active', GNETWORK_TEXTDOMAIN ),
			'activate_plugins',
			'plugins.php?plugin_status=active'
		);
	}

	public function admin_menu_late()
	{
		global $submenu;
		$submenu['gnetwork'][0][0] = __( 'Overview', GNETWORK_TEXTDOMAIN );
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_options' )
	{
		if ( ! is_admin() || self::isAJAX() )
			return;

		global $gNetwork;

		$gNetwork->admin->menus[$sub] = array(
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		);

		if ( $callback ) // && is_callable( $callback ) )
			add_action( 'gnetwork_admin_settings', $callback );
	}

	public static function registerTinyMCE( $plugin, $filepath )
	{
		global $gNetwork;

		$gNetwork->admin->tinymce[$plugin] = GNETWORK_URL.$filepath;
	}

	public static function settingsURL( $full = TRUE )
	{
		$relative = self::cuc( 'manage_options' ) ? 'admin.php?page=gnetwork' : 'index.php?page=gnetwork';

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public function admin_settings_load()
	{
		global $submenu_file;

		if ( isset( $_REQUEST['sub'] ) ) {
			$sub          = $_REQUEST['sub'];
			$submenu_file = 'gnetwork&sub='.$sub;
		} else {
			$sub = NULL;
		}

		do_action( 'gnetwork_admin_settings', $sub );

		// FIXME: ALL DEPRECATED / MUST DROP
		do_action( 'gnetwork_admin_settings_load', $sub );
		do_action( 'gnetwork_admin_settings_save', $sub );
		do_action( 'gnetwork_admin_settings_help', $sub );
	}

	private function subs()
	{
		$subs = array();

		// if ( self::cuc( 'manage_options' ) )
			$subs['overview'] = __( 'Overview', GNETWORK_TEXTDOMAIN );

		foreach ( $this->menus as $sub => $args )
			if ( self::cuc( $args['cap'] ) )
				$subs[$sub] = $args['title'];

		if ( is_super_admin() )
			$subs['console'] = __( 'Console', GNETWORK_TEXTDOMAIN );

		return $subs;
	}

	public function admin_settings_page()
	{
		$uri  = self::settingsURL( FALSE );
		$sub  = self::settingsSub( 'overview' );
		$subs = apply_filters( 'gnetwork_admin_settings_subs', $this->subs() );

		echo '<div class="wrap gnetwork-admin-settings-wrap settings-admin sub-'.$sub.'">';

		if ( 'overview' == $sub
			|| ( 'console' == $sub && is_super_admin() )
			|| ( isset( $this->menus[$sub] ) && self::cuc( $this->menus[$sub]['cap'] ) ) ) {

			$messages = apply_filters( 'gnetwork_admin_settings_messages', array(
				'resetting' => self::updated( __( 'Resetting Settings.', GNETWORK_TEXTDOMAIN ) ),
				'updated'   => self::updated( __( 'Settings updated.', GNETWORK_TEXTDOMAIN ) ),
				'error'     => self::error( __( 'Error while saving settings.', GNETWORK_TEXTDOMAIN ) ),
			), $sub );

			self::settingsTitle();
			gNetworkUtilities::headerNav( $uri, $sub, $subs );
			self::settingsMessage( $messages );

			if ( file_exists( GNETWORK_DIR.'admin/admin.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'admin/admin.'.$sub.'.php' );
			else
				do_action( 'gnetwork_admin_settings_sub_'.$sub, $uri, $sub );

		} else {

			_e( 'Cheatin&#8217; uh?' );
		}

		echo '<div class="clear"></div></div>';
	}

	public function mce_buttons( $buttons )
	{
		array_push( $buttons, '|' );

		foreach ( $this->tinymce as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		foreach ( $this->tinymce as $plugin => $filepath )
			$plugin_array[$plugin] = $filepath;

		return $plugin_array;
	}

	public function export_wp()
	{
		@set_time_limit( 0 );

		defined( 'GNETWORK_IS_WP_EXPORT' ) or define( 'GNETWORK_IS_WP_EXPORT', TRUE );
	}

	public function admin_print_styles()
	{
		gNetworkUtilities::linkStyleSheet( GNETWORK_URL.'assets/css/admin.all.css' );
		gNetworkUtilities::customStyleSheet( 'admin.css' );

		if ( GNETWORK_ADMIN_JS_ENHANCEMENTS )
			wp_enqueue_script( 'gnetwork-admin', GNETWORK_URL.'assets/js/admin.all.min.js', array( 'jquery' ), GNETWORK_VERSION, TRUE );
	}

	public function admin_footer_text()
	{
		if ( isset( $_GET['noheader'] ) )
			return '';

		return gnetwork_powered();
	}

	public function update_footer( $content )
	{
		if ( isset( $_GET['noheader'] ) )
			return '';

		if ( ! current_user_can( 'update_core' ) )
			$content = '<span class="gnetwork-admin-wrap footer-version" title="'
				.sprintf( __( 'Version %s' ), apply_filters( 'string_format_i18n', $GLOBALS['wp_version'] ) )
				.'">'.__( 'CODE IS POETRY', GNETWORK_TEXTDOMAIN ).'</span>';

		return $content;
	}

	// FIXME : style this!!
	public function wp_dashboard_setup()
	{
		// FIXME: handle comma seperated
		if ( defined( 'GNETWORK_ADMIN_WIDGET_RSS' )
			&& constant( 'GNETWORK_ADMIN_WIDGET_RSS' ) ) {

			add_meta_box( 'abetterplanet_widget',
				_x( 'Network Feed', 'Admin Module: Dashboard Widget Title', GNETWORK_TEXTDOMAIN ),
				array( $this, 'widget_network_rss' ),
				'dashboard', 'normal', 'high' );
		}
	}

	public function widget_network_rss()
	{
		// FIXME: handle comma seperated
		//public function return_1600( $seconds ) { return 1600; }
		//add_filter( 'wp_feed_cache_transient_lifetime' , 'return_1600' );
		$rss = fetch_feed( constant( 'GNETWORK_ADMIN_WIDGET_RSS' ) );
		//remove_filter( 'wp_feed_cache_transient_lifetime' , 'return_1600' );

		if ( ! is_wp_error( $rss ) ) {
			// Figure out how many total items there are, but limit it to 3.
			$maxitems = $rss->get_item_quantity( 8 );
			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $rss->get_items( 0, $maxitems );

			if ( ! empty( $maxitems ) ) {
				?> <div class="rss-widget"><ul>
				<?php foreach ( $rss_items as $item ) { ?>
					<li><a class="rsswidget" href='<?php echo $item->get_permalink(); ?>'><?php echo $item->get_title(); ?></a> <span class="rss-date"><?php echo date_i18n( 'j F Y', $item->get_date( 'U' ) ); ?></span></li>
				<?php } ?></ul></div> <?php
			}
		}
	}

	// http://www.wpcode.net/remove-comment-dashboard.html/
	public function manage_pages_columns( $defaults )
	{
		unset( $defaults['comments'] );
		return $defaults;
	}

	// Display Post and Page IDs in the Admin
	// http://wpmu.org/daily-tip-how-to-display-post-and-page-ids-in-the-wordpress-admin/
	// TODO: http://wordpress.org/extend/plugins/reveal-ids-for-wp-admin-25/
	public function manage_posts_columns_id( $defaults )
	{
		if ( 1 === GNETWORK_ADMIN_COLUMN_ID )
			return array_merge( array( 'gn_post_id' => __( 'ID', GNETWORK_TEXTDOMAIN ) ), $defaults );

		$defaults['gn_post_id'] = __( 'ID', GNETWORK_TEXTDOMAIN );
		return $defaults;
	}

	public function custom_column_id( $column_name, $id )
	{
		if ( $column_name === 'gn_post_id' )
			echo $id;
	}

	// Move Pages above Media
	// http://wp.tutsplus.com/tutorials/creative-coding/customizing-the-wordpress-admin-custom-admin-menus/
	public function menu_order( $menu_order )
	{
		return array(
			'index.php',
			'edit.php',
			'edit.php?post_type=page',
			'upload.php',
		);
	}

	// http://wpmu.org/how-to-show-the-time-for-a-scheduled-wordpress-post/
	// Custom public function to add time to the date / time column for future posts
	public function post_date_column_time( $h_time, $post, $column_name = 'date', $mode = 'excerpt' )
	{
		if ( 'future' == $post->post_status )
			$h_time .= '<br />'.get_post_time( 'g:i a', FALSE, $post, TRUE );

		return $h_time;
	}

	// http://www.wpbeginner.com/wp-tutorials/how-to-set-default-admin-color-scheme-for-new-users-in-wordpress/
	public function user_register( $user_id )
	{
		wp_update_user( array(
			'ID'          => $user_id,
			'admin_color' => GNETWORK_ADMIN_COLOUR, // 'sunrise'
		) );
	}

	// http://unserkaiser.com/blog/2013/07/03/speed-up-wordpress-post-list-screens/
	// https://gist.github.com/franz-josef-kaiser/5917688
	// Faster Admin Post Lists
	// Reduces the queried fields inside WP_Query for WP_Post_List_Table screens
	// Author: Franz Josef Kaiser <wecodemore@gmail.com> / http://unserkaiser.com
	public function posts_fields( $fields, $query )
	{
		if ( ! is_admin()
			|| ! $query->is_main_query()
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| ( defined( 'DOING_CRON' ) && DOING_CRON ) )
				return $fields;

		$p = $GLOBALS['wpdb']->posts;
		return implode( ",", array(
			"{$p}.ID",
			"{$p}.post_title",
			"{$p}.post_date",
			"{$p}.post_author",
			"{$p}.post_name",
			"{$p}.comment_status",
			"{$p}.ping_status",
			"{$p}.post_password",
		) );
	}
}

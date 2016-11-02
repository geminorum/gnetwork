<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Admin extends ModuleCore
{

	protected $key     = 'admin';
	protected $network = FALSE;
	protected $front   = FALSE;

	public $menus = array();

	protected function setup_actions()
	{
		// add_action( 'admin_init', array( $this, 'admin_init_early' ), 1 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_menu', array( $this, 'admin_menu_late' ), 999 );

		add_action( 'export_wp', array( $this, 'export_wp' ), 1 );

		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 9999 );
		add_filter( 'update_footer', array( $this, 'update_footer' ), 9999 );

		add_filter( 'manage_pages_columns', array( $this, 'manage_pages_columns' ) );
		add_filter( 'post_date_column_time', array( $this, 'post_date_column_time' ), 10, 4 );

		if ( GNETWORK_ADMIN_COLUMN_ID ) {
			add_filter( 'manage_pages_columns', array( $this, 'manage_posts_columns_id' ), 12 );
			add_filter( 'manage_posts_columns', array( $this, 'manage_posts_columns_id' ), 12 );
			add_action( 'manage_posts_custom_column', array( $this, 'custom_column_id' ), 5, 2 );
			add_action( 'manage_pages_custom_column', array( $this, 'custom_column_id' ), 5, 2 );
		}

		if ( GNETWORK_ADMIN_COLOUR )
			add_action( 'user_register', array( $this, 'user_register' ) );

		// FIXME: WORKING but DISABLED
		// add_filter( 'custom_menu_order', '__return_true' );
		// add_filter( 'menu_order', array( $this, 'menu_order' ) );

		// FIXME: IT MESSES WITH CUSTOM COLUMNS!!
		// add_filter( 'posts_fields', array( $this, 'posts_fields' ), 0, 2 );
	}

	// FIXME: DISABLED
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
		do_action( 'gnetwork_setup_menu', 'admin' );

		if ( WordPress::cuc( 'manage_options' ) ) {

			$hook = add_menu_page(
				_x( 'gNetwork Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				_x( 'Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				'manage_options',
				$this->base,
				array( $this, 'admin_settings_page' ),
				'dashicons-screenoptions',
				120
			);

			foreach ( $this->menus as $sub => $args ) {
				add_submenu_page( $this->base,
					sprintf( _x( 'gNetwork Extras: %s', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
					$args['title'],
					$args['cap'],
					$this->base.'&sub='.$sub,
					array( $this, 'admin_settings_page' )
				);
			}

		} else {

			$hook = add_submenu_page( 'index.php',
				_x( 'gNetwork Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				_x( 'Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				'read',
				$this->base,
				array( $this, 'admin_settings_page' )
			);
		}

		add_action( 'load-'.$hook, array( $this, 'admin_settings_load' ) );

		add_submenu_page( 'plugins.php',
			_x( 'Active', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Active', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
			'activate_plugins',
			'plugins.php?plugin_status=active'
		);
	}

	public function admin_menu_late()
	{
		$GLOBALS['submenu'][$this->base][0] = array(
			_x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			'read',
			$this->base,
			_x( 'gNetwork Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
		);
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_options' )
	{
		if ( ! is_admin() || WordPress::isAJAX() )
			return;

		gNetwork()->admin->menus[$sub] = array(
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		);

		if ( $callback ) // && is_callable( $callback ) )
			add_action( 'gnetwork_admin_settings', $callback );
	}

	public static function registerTinyMCE( $plugin, $filepath, $row = 1 )
	{
		if ( isset( gNetwork()->editor ) )
			gNetwork()->editor->tinymce[$row][$plugin] = $filepath ? GNETWORK_URL.$filepath : FALSE;
	}

	public function admin_settings_load()
	{
		if ( ( $sub = isset( $_REQUEST['sub'] ) ? $_REQUEST['sub'] : NULL ) )
			$GLOBALS['submenu_file'] = $this->base.'&sub='.$sub;

		do_action( 'gnetwork_admin_settings', $sub );
	}

	private function subs()
	{
		$subs = array();

		// if ( WordPress::cuc( 'manage_options' ) )
			$subs['overview'] = _x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );

		foreach ( $this->menus as $sub => $args )
			if ( WordPress::cuc( $args['cap'] ) )
				$subs[$sub] = $args['title'];

		if ( is_super_admin() )
			$subs['console'] = _x( 'Console', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );

		return $subs;
	}

	public function admin_settings_page()
	{
		$uri  = Settings::adminURL( FALSE );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'settings_subs', $this->subs() );

		echo '<div class="wrap gnetwork-admin-settings-wrap settings-admin sub-'.$sub.'">';

		if ( 'overview' == $sub
			|| ( 'console' == $sub && is_super_admin() )
			|| ( isset( $this->menus[$sub] ) && WordPress::cuc( $this->menus[$sub]['cap'] ) ) ) {

			$messages = $this->filters( 'settings_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' );
			else
				$this->actions( 'settings_sub_'.$sub, $uri, $sub );

		} else {

			Settings::cheatin();
		}

		echo '<div class="clear"></div></div>';
	}

	public function export_wp()
	{
		@set_time_limit( 0 );

		defined( 'GNETWORK_IS_WP_EXPORT' ) or define( 'GNETWORK_IS_WP_EXPORT', TRUE );
	}

	public function admin_print_styles()
	{
		Utilities::linkStyleSheet( GNETWORK_URL.'assets/css/admin.all.css' );
		Utilities::customStyleSheet( 'admin.css' );

		if ( GNETWORK_ADMIN_JS_ENHANCEMENTS )
			Utilities::enqueueScript( 'admin.all' );
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
				.sprintf( _x( 'Version %s', 'Modules: Admin', GNETWORK_TEXTDOMAIN ), apply_filters( 'string_format_i18n', $GLOBALS['wp_version'] ) )
				.'">'._x( 'CODE IS POETRY', 'Modules: Admin', GNETWORK_TEXTDOMAIN ).'</span>';

		return $content;
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
			return array_merge( array( 'gn_post_id' => _x( 'ID', 'Modules: Admin: Column Blog ID', GNETWORK_TEXTDOMAIN ) ), $defaults );

		$defaults['gn_post_id'] = _x( 'ID', 'Modules: Admin: Column Blog ID', GNETWORK_TEXTDOMAIN );
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

	public function post_date_column_time( $h_time, $post, $column_name = 'date', $mode = 'excerpt' )
	{
		if ( 'excerpt' !== $mode
			&& 'future' == $post->post_status )
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

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\WordPress;

class Admin extends gNetwork\Module
{

	protected $key     = 'admin';
	protected $network = FALSE;
	protected $front   = FALSE;

	public $menus = [];

	protected function setup_actions()
	{
		if ( ! WordPress::mustRegisterUI( FALSE ) )
			return;

		if ( is_blog_admin() ) {
			$this->action( 'admin_menu', 0, 12 );
			$this->action( 'admin_menu', 0, 999, 'late' );
		}

		$this->action( 'admin_print_styles' );
		$this->filter( 'admin_footer_text', 1, 9999 );
		$this->filter( 'update_footer', 1, 9999 );
	}

	public function admin_menu()
	{
		do_action( $this->base.'_setup_menu', 'admin' );

		if ( WordPress::cuc( 'manage_options' ) ) {

			$hook = add_menu_page(
				_x( 'Network Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				_x( 'Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				'manage_options',
				$this->base,
				[ $this, 'settings_page' ],
				'dashicons-screenoptions',
				120
			);

			foreach ( $this->menus as $sub => $args ) {
				add_submenu_page( $this->base,
					sprintf( _x( 'gNetwork Extras: %s', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
					$args['title'],
					$args['cap'],
					$this->base.'&sub='.$sub,
					[ $this, 'settings_page' ]
				);
			}

		} else {

			$hook = add_submenu_page( 'index.php',
				_x( 'Network Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				_x( 'Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				'read',
				$this->base,
				[ $this, 'settings_page' ]
			);
		}

		add_action( 'load-'.$hook, [ $this, 'settings_load' ] );

		add_submenu_page( 'plugins.php',
			_x( 'Active', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Active', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
			'activate_plugins',
			'plugins.php?plugin_status=active'
		);
	}

	public function admin_menu_late()
	{
		$GLOBALS['submenu'][$this->base][0] = [
			_x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			'read',
			$this->base,
			_x( 'Network Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_options' )
	{
		if ( ! is_blog_admin() )
			return;

		gNetwork()->admin->menus[$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

		if ( $callback ) // && is_callable( $callback ) )
			add_action( 'gnetwork_admin_settings', $callback );
	}

	public static function registerTinyMCE( $plugin, $filepath, $row = 1 )
	{
		if ( isset( gNetwork()->editor ) )
			gNetwork()->editor->tinymce[$row][$plugin] = $filepath ? GNETWORK_URL.$filepath : FALSE;
	}

	public function settings_load()
	{
		if ( ( $sub = isset( $_REQUEST['sub'] ) ? $_REQUEST['sub'] : NULL ) )
			$GLOBALS['submenu_file'] = $this->base.'&sub='.$sub;

		do_action( $this->base.'_admin_settings', $sub );
	}

	private function subs()
	{
		$subs = [];

		$subs['overview'] = _x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );

		foreach ( $this->menus as $sub => $args )
			if ( WordPress::cuc( $args['cap'] ) )
				$subs[$sub] = $args['title'];

		if ( WordPress::isSuperAdmin() )
			$subs['console'] = _x( 'Console', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );

		return $subs;
	}

	public function settings_page()
	{
		$uri  = Settings::adminURL( FALSE );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'settings_subs', $this->subs() );

		Settings::wrapOpen( $sub, $this->base, 'settings' );

		if ( 'overview' == $sub
			|| ( 'console' == $sub && WordPress::isSuperAdmin() )
			|| ( isset( $this->menus[$sub] ) && WordPress::cuc( $this->menus[$sub]['cap'] ) ) ) {

			$messages = $this->filters( 'settings_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' );

			else if ( ! $this->actions( 'settings_sub_'.$sub, $uri, $sub ) )
				Settings::cheatin();

		} else {

			Settings::cheatin();
		}

		Settings::wrapClose();
	}

	public function admin_print_styles()
	{
		Utilities::linkStyleSheet( 'admin.all.css' );
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
}

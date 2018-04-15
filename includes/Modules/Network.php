<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Network extends gNetwork\Module
{

	protected $key = 'network';

	protected function setup_actions()
	{
		if ( ! is_multisite() )
			throw new Exception( 'Only on Multisite!' );

		if ( is_network_admin() ) {
			$this->action( 'network_admin_menu' );
			$this->action( 'current_screen' );
		}
	}

	public function network_admin_menu()
	{
		do_action( $this->base.'_setup_menu', 'network' );

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
			_x( 'Network Extras', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Extras', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			'manage_network_options',
			$this->base,
			[ $this, 'settings_page' ],
			'dashicons-screenoptions',
			120
		);

		$tools = add_submenu_page( 'network-tools',
			_x( 'Network Tools', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Extras', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
			'manage_network_options',
			$this->base.'-tools',
			[ $this, 'tools_page' ]
		);

		add_action( 'load-'.$hook, [ $this, 'settings_load' ] );
		add_action( 'load-'.$tools, [ $this, 'tools_load' ] );

		foreach ( $this->get_menus() as $priority => $group )
			foreach ( $group as $sub => $args )
				add_submenu_page( $this->base,
					sprintf( _x( 'gNetwork Extras: %s', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
					$args['title'],
					$args['cap'],
					$this->base.'&sub='.$sub,
					[ $this, 'settings_page' ]
				);

		$GLOBALS['submenu'][$this->base][0] = [
			_x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			'manage_network_options',
			$this->base,
			_x( 'Network Extras', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function menuURL( $full = TRUE, $context = 'settings' )
	{
		if ( 'tools' == $context )
			$relative = 'admin.php?page='.static::BASE.'-tools';
		else
			$relative = 'admin.php?page='.static::BASE;

		return $full ? network_admin_url( $relative ) : $relative;
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_network_options', $priority = 10 )
	{
		if ( ! is_network_admin() )
			return;

		gNetwork()->network->menus['settings'][intval( $priority )][$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

		if ( $callback )
			add_action( 'gnetwork_network_settings', $callback );
	}

	public static function registerTool( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_network_options', $priority = 10 )
	{
		if ( ! is_network_admin() )
			return;

		gNetwork()->network->menus['tools'][intval( $priority )][$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

		if ( $callback )
			add_action( 'gnetwork_network_tools', $callback );
	}

	public function settings_load()
	{
		$sub = Settings::sub( 'overview' );

		if ( 'overview' !== $sub )
			$GLOBALS['submenu_file'] = $this->base.'&sub='.$sub;

		do_action( $this->base.'_network_settings', $sub );
	}

	public function tools_load()
	{
		do_action( $this->base.'_network_tools', Settings::sub( 'overview' ) );
	}

	public function settings_page()
	{
		$uri  = self::menuURL( FALSE );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'settings_subs', $this->get_subs() );

		Settings::wrapOpen( $sub );

		if ( $this->cucSub( $sub ) ) {

			$messages = $this->filters( 'settings_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( 'overview' == $sub )
				$this->settings_overview( $uri );

			else if ( 'console' == $sub )
				@require_once( GNETWORK_DIR.'includes/Layouts/console.'.$this->key.'.php' );

			else if ( ! $this->actions( 'settings_sub_'.$sub, $uri, $sub ) )
				Settings::cheatin();

		} else {

			Settings::cheatin();
		}

		Settings::wrapClose();
	}

	public function tools_page()
	{
		$uri  = self::menuURL( FALSE, 'tools' );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'tools_subs', $this->get_subs( 'tools' ) );

		Settings::wrapOpen( $sub, 'tools' );

		if ( $this->cucSub( $sub, 'tools' ) ) {

			$messages = $this->filters( 'tools_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( 'overview' == $sub )
				$this->tools_overview( $uri );

			else if ( ! $this->actions( 'tools_sub_'.$sub, $uri, $sub ) )
				Settings::cheatin();

		} else {

			Settings::cheatin();
		}

		Settings::wrapClose();
	}

	protected function settings_overview( $uri )
	{
		gnetwork_update_notice();

		gnetwork_github_readme();
	}

	public function current_screen( $screen )
	{
		if ( 'sites-network' == $screen->base ) {

			$this->filter( 'wpmu_blogs_columns', 1, 20 );
			$this->action( 'manage_sites_custom_column', 2 );

			add_filter( 'bulk_actions-'.$screen->id, [ $this, 'bulk_actions' ] );
			add_filter( 'network_sites_updated_message_'.$this->hook( 'admin', 'email' ), [ $this, 'updated_message' ] );
			$this->action( 'wpmuadminedit' );
		}
	}

	public function bulk_actions( $actions )
	{
		return array_merge( $actions, [ 'resetadminemail' => _x( 'Reset Admin Email', 'Modules: Network: Bulk Action', GNETWORK_TEXTDOMAIN ) ] );
	}

	public function wpmuadminedit()
	{
		if ( ( empty( $_POST['action'] ) || 'resetadminemail' != $_POST['action'] )
			&& ( empty( $_POST['action2'] ) || 'resetadminemail' != $_POST['action2'] ) )
				return;

		check_admin_referer( 'bulk-sites' );

		$blogs = self::req( 'allblogs', [] );

		if ( empty( $blogs ) )
			return;

		$email = get_site_option( 'admin_email' );

		foreach ( $blogs as $blog_id )
			update_blog_option( $blog_id, 'admin_email', $email );

		WordPress::redirectReferer( [
			'updated' => $this->hook( 'admin', 'email' ),
			'count'   => count( $blogs ),
		] );
	}

	public function updated_message( $msg )
	{
		$message = _x( '%s site(s) admin email reset to <code>%s</code>', 'Modules: Network: Message', GNETWORK_TEXTDOMAIN );
		return sprintf( $message, Number::format( self::req( 'count', 0 ) ), get_site_option( 'admin_email' ) );
	}

	public static function getLogo( $wrap = FALSE, $fallback = TRUE, $logo = NULL )
	{
		if ( ! is_null( $logo ) )
			$html = HTML::img( $logo, '-logo-img', GNETWORK_NAME );

		else if ( file_exists( WP_CONTENT_DIR.'/'.GNETWORK_LOGO ) )
			$html = HTML::img( WP_CONTENT_URL.'/'.GNETWORK_LOGO, '-logo-img', GNETWORK_NAME );

		else if ( $fallback )
			$html = GNETWORK_NAME;

		else
			return '';

		$html = HTML::tag( 'a', [
			'href'  => GNETWORK_BASE,
			'title' => GNETWORK_NAME,
		], $html );

		return $wrap ? HTML::tag( $wrap, [ 'class' => 'logo' ], $html ) : $html;
	}

	public function wpmu_blogs_columns( $columns )
	{
		return array_merge( $columns, [ 'gnetwork-network-id' => _x( 'ID', 'Modules: Network: Column', GNETWORK_TEXTDOMAIN ) ] );
	}

	public function manage_sites_custom_column( $column_name, $blog_id )
	{
		if ( 'gnetwork-network-id' != $column_name )
			return;

		echo '<div class="gnetwork-admin-wrap-column -network -id">';
			echo HTML::escape( $blog_id );
		echo '</div>';
	}
}
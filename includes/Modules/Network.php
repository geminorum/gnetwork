<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Network extends gNetwork\Module
{

	protected $key = 'network';

	private $ssl_support = FALSE;

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

		$tools = add_submenu_page( 'index.php',
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
			$relative = 'index.php?page='.static::BASE.'-tools';
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
			add_action( static::BASE.'_network_settings', $callback );
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
			add_action( static::BASE.'_network_tools', $callback );
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

			Settings::sideOpen( NULL, $uri, $sub, $subs );
			Settings::message( $this->filters( 'settings_messages', Settings::messages(), $sub ) );

			if ( 'overview' == $sub )
				$this->settings_overview( $uri );

			else if ( 'console' == $sub )
				@require_once( GNETWORK_DIR.'includes/Layouts/console.'.$this->key.'.php' );

			else if ( ! $this->actions( 'settings_sub_'.$sub, $uri, $sub ) )
				Settings::cheatin();

			Settings::sideClose();

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

			Settings::headerTitle();
			HTML::headerNav( $uri, $sub, $subs );
			Settings::message( $this->filters( 'tools_messages', Settings::messages(), $sub ) );

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

	protected function tools_overview( $uri )
	{
		if ( class_exists( __NAMESPACE__.'\\Debug' ) ) {
			HTML::h3( _x( 'System Report', 'Modules: Network', GNETWORK_TEXTDOMAIN ) );
			Debug::displayReport();
		}
	}

	public function current_screen( $screen )
	{
		if ( 'sites-network' == $screen->base ) {

			$this->ssl_support = gNetwork()->option( 'ssl_support', 'site', FALSE );

			$this->filter( 'wpmu_blogs_columns', 1, 20 );
			$this->action( 'manage_sites_custom_column', 2 );

			$this->action( 'wpmuadminedit' );

			add_filter( 'bulk_actions-'.$screen->id, [ $this, 'bulk_actions' ] );
			add_filter( 'network_sites_updated_message_resetadminemail', [ $this, 'updated_message_resetadminemail' ] );

			if ( $this->ssl_support ) {
				add_filter( 'network_sites_updated_message_enablesitessl', [ $this, 'updated_message_enable' ] );
				add_filter( 'network_sites_updated_message_disablesitessl', [ $this, 'updated_message_disable' ] );
			}
		}
	}

	public function bulk_actions( $actions )
	{
		$new = [ 'resetadminemail' => _x( 'Reset Admin Email', 'Modules: Network: Bulk Action', GNETWORK_TEXTDOMAIN ) ];

		if ( $this->ssl_support ) {
			$new['enablesitessl']  = _x( 'Enable SSL', 'Modules: Network: Bulk Action', GNETWORK_TEXTDOMAIN );
			$new['disablesitessl'] = _x( 'Disable SSL', 'Modules: Network: Bulk Action', GNETWORK_TEXTDOMAIN );
		}

		return array_merge( $actions, $new );
	}

	public function wpmuadminedit()
	{
		if ( ! empty( $_POST['action'] ) )
			$action = $_POST['action'];

		else if ( ! empty( $_POST['action2'] ) )
			$action = $_POST['action2'];

		else
			return;

		if ( ! in_array( $action, [ 'resetadminemail', 'enablesitessl', 'disablesitessl' ] ) )
			return;

		check_admin_referer( 'bulk-sites' );

		$blogs = self::req( 'allblogs', [] );

		if ( empty( $blogs ) )
			return;

		$count = 0;

		if ( 'resetadminemail' == $action ) {
			$email = get_network_option( NULL, 'admin_email' );

			foreach ( $blogs as $blog_id )
				if ( update_blog_option( $blog_id, 'admin_email', $email ) )
					$count++;

		} else if ( $this->ssl_support ) {

			$switch = 'enablesitessl' == $action
				? [ 'http://', 'https://' ]
				: [ 'https://', 'http://' ];

			foreach ( $blogs as $blog_id ) {

				switch_to_blog( $blog_id );

				update_option( 'siteurl', str_replace( $switch[0], $switch[1], get_option( 'siteurl' ) ) );
				update_option( 'home', str_replace( $switch[0], $switch[1], get_option( 'home' ) ) );

				$count++;
			}

			restore_current_blog();
		}

		WordPress::redirectReferer( [
			'updated' => $action,
			'count'   => $count,
		] );
	}

	public function updated_message_resetadminemail( $msg )
	{
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'count', $_SERVER['REQUEST_URI'] );
		$message = _x( '%s site(s) admin email reset to <code>%s</code>', 'Modules: Network: Message', GNETWORK_TEXTDOMAIN );
		return sprintf( $message, Number::format( self::req( 'count', 0 ) ), get_network_option( NULL, 'admin_email' ) );
	}

	public function updated_message_enable( $msg )
	{
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'count', $_SERVER['REQUEST_URI'] );
		return Utilities::getCounted( self::req( 'count', 0 ), _x( '%s site(s) SSL Enabled', 'Modules: Network: Message', GNETWORK_TEXTDOMAIN ) );
	}

	public function updated_message_disable( $msg )
	{
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'count', $_SERVER['REQUEST_URI'] );
		return Utilities::getCounted( self::req( 'count', 0 ), _x( '%s site(s) SSL Disabled', 'Modules: Network: Message', GNETWORK_TEXTDOMAIN ) );
	}

	public function wpmu_blogs_columns( $columns )
	{
		$columns = Arraay::insert( $columns, [
			$this->classs( 'ssl' ) => _x( 'SSL', 'Modules: Network: Column', GNETWORK_TEXTDOMAIN ),
		], 'blogname', 'before' );

		return array_merge( $columns, [ $this->classs( 'id' ) => _x( 'ID', 'Modules: Network: Column', GNETWORK_TEXTDOMAIN ) ] );
	}

	public function manage_sites_custom_column( $column_name, $blog_id )
	{
		if ( $this->classs( 'ssl' ) == $column_name ) {

			Utilities::htmlSSLfromURL( get_blog_option( $blog_id, 'siteurl' ) );

		} else if ( $this->classs( 'id' ) == $column_name ) {

			echo '<div class="'.static::BASE.'-admin-wrap-column -network -id">';
				echo HTML::escape( $blog_id );
			echo '</div>';
		}
	}
}

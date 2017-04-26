<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Network extends \geminorum\gNetwork\ModuleCore
{

	protected $key = 'network';

	public $menus = array();

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
			array( $this, 'settings_page' ),
			'dashicons-screenoptions',
			120
		);

		add_action( 'load-'.$hook, array( $this, 'settings_load' ) );

		foreach ( $this->menus as $sub => $args ) {
			add_submenu_page( $this->base,
				sprintf( _x( 'gNetwork Extras: %s', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
				$args['title'],
				$args['cap'],
				$this->base.'&sub='.$sub,
				array( $this, 'settings_page' )
			);
		}

		$GLOBALS['submenu'][$this->base][0] = array(
			_x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			'manage_network_options',
			$this->base,
			_x( 'Network Extras', 'Modules: Network: Page Menu', GNETWORK_TEXTDOMAIN ),
		);
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_network_options' )
	{
		if ( ! is_network_admin() )
			return;

		gNetwork()->network->menus[$sub] = array(
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		);

		if ( $callback ) // && is_callable( $callback ) )
			add_action( 'gnetwork_network_settings', $callback );
	}

	public function settings_load()
	{
		if ( ( $sub = isset( $_REQUEST['sub'] ) ? $_REQUEST['sub'] : NULL ) )
			$GLOBALS['submenu_file'] = $this->base.'&sub='.$sub;

		do_action( $this->base.'_network_settings', $sub );
	}

	private function subs()
	{
		$subs = array(
			'overview' => _x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
		);

		foreach ( $this->menus as $sub => $args )
			$subs[$sub] = $args['title'];

		if ( WordPress::isSuperAdmin() ) {
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

		Settings::wrapOpen( $sub, $this->base, 'settings' );

			$messages = $this->filters( 'settings_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' );

			else if ( ! $this->actions( 'settings_sub_'.$sub, $uri, $sub ) )
				Settings::cheatin();

		Settings::wrapClose();
	}

	public function current_screen( $screen )
	{
		if ( 'sites-network' == $screen->base ) {

			$this->filter( 'wpmu_blogs_columns', 1, 20 );
			$this->action( 'manage_sites_custom_column', 2 );

			$this->filter( 'bulk_actions-'.$screen->id, 1, 10, 'bulk_actions' );
			$this->filter( 'network_sites_updated_message_'.$this->hook( 'admin', 'email' ), 1, 10, 'updated_message' );
			$this->action( 'wpmuadminedit' );
		}
	}

	public function bulk_actions( $actions )
	{
		return array_merge( $actions, array( 'resetadminemail' => _x( 'Reset Admin Email', 'Modules: Network: Bulk Action', GNETWORK_TEXTDOMAIN ) ) );
	}

	public function wpmuadminedit()
	{
		if ( ( empty( $_POST['action'] ) || 'resetadminemail' != $_POST['action'] )
			&& ( empty( $_POST['action2'] ) || 'resetadminemail' != $_POST['action2'] ) )
				return;

		check_admin_referer( 'bulk-sites' );

		$blogs = self::req( 'allblogs', array() );

		if ( ! count( $blogs ) )
			return;

		$email = get_site_option( 'admin_email' );

		foreach ( $blogs as $blog_id )
			update_blog_option( $blog_id, 'admin_email', $email );

		WordPress::redirectReferer( array(
			'updated' => $this->hook( 'admin', 'email' ),
			'count'   => count( $blogs ),
		) );
	}

	public function updated_message( $msg )
	{
		$message = _x( '%s site(s) admin email reset to <code>%s</code>', 'Modules: Network: Message', GNETWORK_TEXTDOMAIN );
		return sprintf( $message, Number::format( self::req( 'count', 0 ) ), get_site_option( 'admin_email' ) );
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
}

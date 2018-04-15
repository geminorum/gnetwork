<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Admin extends gNetwork\Module
{

	protected $key     = 'admin';
	protected $network = FALSE;
	protected $front   = FALSE;

	protected function setup_actions()
	{
		if ( ! WordPress::mustRegisterUI() )
			return;

		$this->filter( 'admin_body_class' );

		if ( is_blog_admin() ) {
			$this->filter( 'admin_title', 2 );
			$this->action( 'admin_menu', 0, 12 );
			$this->action( 'admin_menu', 0, 999, 'late' );
			$this->action( 'admin_enqueue_scripts', 0, 999 );

			// hides network-active plugins alongside plugins active for the current site
			$this->filter_false( 'show_network_active_plugins' );
		}

		$this->action( 'admin_print_styles', 0, 999 );
		$this->filter( 'admin_footer_text', 1, 9999 );
		$this->filter( 'update_footer', 1, 9999 );
	}

	public function admin_body_class( $classes )
	{
		if ( gNetwork()->option( 'admin_chosen', 'blog' ) )
			$classes.= ' enhancement-chosen-enabled';

		if ( gNetwork()->option( 'thrift_mode', 'blog' ) )
			$classes.= ' thrift-mode';

		if ( ! gNetwork()->option( 'user_locale', 'profile' ) )
			$classes.= ' hide-userlocale-option';

		if ( ! gNetwork()->adminbar->show_adminbar() )
			$classes.= ' hide-adminbar-option';

		if ( WordPress::isSuperAdmin() )
			$classes.= ' current-user-superadmin';

		return $classes;
	}

	public function admin_title( $admin_title, $title )
	{
		return sprintf( _x( '%1$s &lsaquo; %2$s &#8212; Content Management', 'Modules: Admin: HTML Title', GNETWORK_TEXTDOMAIN ), $title, get_bloginfo( 'name', 'display' ) );
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

			foreach ( $this->get_menus() as $priority => $group )
				foreach ( $group as $sub => $args )
					add_submenu_page( $this->base,
						sprintf( _x( 'gNetwork Extras: %s', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
						$args['title'],
						$args['cap'],
						$this->base.'&sub='.$sub,
						[ $this, 'settings_page' ]
					);

			$tools = add_submenu_page( 'tools.php',
				_x( 'Network Tools', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				_x( 'Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				'edit_others_posts',
				$this->base.'-tools',
				[ $this, 'tools_page' ]
			);

		} else {

			$hook = add_submenu_page( 'index.php',
				_x( 'Network Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				_x( 'My Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				'read',
				$this->base,
				[ $this, 'settings_page' ]
			);

			$tools = add_submenu_page( 'tools.php',
				_x( 'Network Tools', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				_x( 'Extras', 'Modules: Admin: Page Menu', GNETWORK_TEXTDOMAIN ),
				'edit_others_posts',
				$this->base.'-tools',
				[ $this, 'tools_page' ]
			);
		}

		add_action( 'load-'.$hook, [ $this, 'settings_load' ] );
		add_action( 'load-'.$tools, [ $this, 'tools_load' ] );

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

		// @REF: http://justintadlock.com/?p=3320
		if ( ! WordPress::cuc( 'update_plugins' ) )
			remove_submenu_page( 'themes.php', 'theme-editor.php' );
	}

	public static function menuURL( $full = TRUE, $context = 'settings' )
	{
		if ( 'tools' == $context )
			$relative = 'tools.php?page='.static::BASE.'-tools';
		else
			$relative = WordPress::cuc( 'manage_options' )
				? 'admin.php?page='.static::BASE
				: 'index.php?page='.static::BASE;

		return $full ? get_admin_url( NULL, $relative ) : $relative;
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_options', $priority = 10 )
	{
		if ( ! is_blog_admin() )
			return;

		gNetwork()->admin->menus['settings'][intval( $priority )][$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

		if ( $callback )
			add_action( 'gnetwork_admin_settings', $callback );
	}

	public static function registerTool( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_options', $priority = 10 )
	{
		if ( ! is_blog_admin() )
			return;

		gNetwork()->admin->menus['tools'][intval( $priority )][$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

		if ( $callback )
			add_action( 'gnetwork_admin_tools', $callback );
	}

	public static function registerTinyMCE( $plugin, $filepath, $row = 1, $context = 'post' )
	{
		global $pagenow;

		if ( ! isset( gNetwork()->editor ) )
			return FALSE;

		switch ( $context ) {

			case 'post':

				if ( ! is_admin() )
					return FALSE;

				if ( 'post.php' != $pagenow && 'post-new.php' != $pagenow )
					return FALSE;

			break;
			case 'term':

				if ( ! is_admin() )
					return FALSE;

				if ( ! array_key_exists( 'taxonomy', $_REQUEST ) )
					return FALSE;

			break;
			case 'widget':

				if ( 'widgets.php' != $pagenow )
					return FALSE;

			break;
			case 'admin':

				if ( ! is_admin() )
					return FALSE;

			break;
			case 'front':

				if ( is_admin() )
					return FALSE;

			break;
			default:
			case 'all':
		}

		gNetwork()->editor->tinymce[$row][$plugin] = $filepath ? GNETWORK_URL.$filepath : FALSE;

		return TRUE;
	}

	public function settings_load()
	{
		$sub = Settings::sub( 'overview' );

		if ( 'overview' !== $sub )
			$GLOBALS['submenu_file'] = $this->base.'&sub='.$sub;

		do_action( $this->base.'_admin_settings', $sub );
	}

	public function tools_load()
	{
		do_action( $this->base.'_admin_tools', Settings::sub( 'overview' ) );
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
		HTML::h3( _x( 'Current Site Overview', 'Modules: Admin: Site Overview', GNETWORK_TEXTDOMAIN ) );

		HTML::desc( _x( 'Below you can find various information about current site and contents.', 'Modules: Admin: Site Overview', GNETWORK_TEXTDOMAIN ) );

		$tabs = [];

		if ( class_exists( __NAMESPACE__.'\\ShortCodes' )
			&& current_user_can( 'edit_posts' ) )
			$tabs['shortcodes'] = [
				'title'  => _x( 'Available Shortcodes', 'Modules: Admin: Site Overview', GNETWORK_TEXTDOMAIN ),
				'cb'     => [ __NAMESPACE__.'\\ShortCodes', 'available' ],
				'active' => TRUE,
			];

		if ( class_exists( __NAMESPACE__.'\\Locale' )
			&& current_user_can( 'manage_options' ) )
			$tabs['loadedmos'] = [
				'title' => _x( 'Loaded Localizations', 'Modules: Admin: Site Overview', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __NAMESPACE__.'\\Locale', 'loadedMOs' ],
			];

		if ( class_exists( __NAMESPACE__.'\\Authors' )
			&& current_user_can( 'list_users' ) )
			$tabs['userroles'] = [
				'title' => _x( 'Roles and Capabilities', 'Modules: Admin: Site Overview', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __NAMESPACE__.'\\Authors', 'userRoles' ],
			];

		if ( class_exists( __NAMESPACE__.'\\Debug' )
			&& current_user_can( 'manage_options' ) ) {

			$tabs['caching'] = [
				'title' => _x( 'Stats of the Caching', 'Modules: Admin: Site Overview', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __NAMESPACE__.'\\Debug', 'cacheStats' ],
			];

			$tabs['upload'] = [
				'title' => _x( 'File & Upload', 'Modules: Admin: Site Overview', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __NAMESPACE__.'\\Debug', 'summaryUpload' ],
			];
		}

		if ( class_exists( __NAMESPACE__.'\\Media' )
			&& current_user_can( 'edit_others_posts' ) )
			$tabs['imagesizes'] = [
				'title' => _x( 'Registered Image Sizes', 'Modules: Admin: Site Overview', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __NAMESPACE__.'\\Media', 'registeredImageSizes' ],
			];

		HTML::tabsList( apply_filters( 'gnetwork_admin_overview', $tabs ) );
	}

	public function admin_enqueue_scripts()
	{
		if ( gNetwork()->option( 'admin_chosen', 'blog' ) ) {

			$script = 'jQuery(function($) {
				$("select.gnetwork-do-chosen, .postbox:not(#submitdiv) .inside select, .tablenav select").chosen({
					rtl: "rtl" === $("html").attr("dir"),
					no_results_text: "'._x( 'No results match', 'Modules: Admin: Chosen', GNETWORK_TEXTDOMAIN ).'",
					disable_search_threshold: 10
				}).addClass("gnetwork-chosen");
			});';

			wp_add_inline_script( Utilities::enqueueScriptVendor( 'chosen.jquery', [ 'jquery' ], '1.8.2' ), $script );
		}

		// FIXME: DROP THIS
		// BruteProtect global css!!
		if ( defined( 'BRUTEPROTECT_VERSION' ) )
			wp_dequeue_style( 'bruteprotect-css' );
	}

	public function admin_print_styles()
	{
		Utilities::linkStyleSheet( 'admin.all' );
		Utilities::customStyleSheet( 'admin.css' );

		if ( GNETWORK_ADMIN_JS_ENHANCEMENTS )
			wp_localize_script( Utilities::enqueueScript( 'admin.all' ), 'gNetwork', $this->localize_script() );
	}

	protected function localize_script()
	{
		return [
			'metabox_controls_collapse' => HTML::escape( _x( 'Collapse All', 'Modules: Admin: Localize Script', GNETWORK_TEXTDOMAIN ) ),
			'metabox_controls_expand'   => HTML::escape( _x( 'Expand All', 'Modules: Admin: Localize Script', GNETWORK_TEXTDOMAIN ) ),
			'reset_button_text'         => HTML::escape( _x( 'Reset', 'Modules: Admin: Localize Script', GNETWORK_TEXTDOMAIN ) ),
			'reset_button_disabled'     => isset( $_GET['filter_action'] ) ? '' : 'disabled',
		];
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

		if ( current_user_can( 'update_core' ) )
			return $content;

		$content = _x( 'CODE IS POETRY', 'Modules: Admin', GNETWORK_TEXTDOMAIN );

		if ( $branding = gNetwork()->option( 'text_slogan', 'branding' ) )
			return $content = $branding;

		return HTML::wrap( $content, '-slogan', FALSE );
	}
}
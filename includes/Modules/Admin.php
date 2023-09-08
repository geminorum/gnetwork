<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Admin extends gNetwork\Module
{

	protected $key     = 'admin';
	protected $network = FALSE;
	protected $front   = FALSE;

	private $dark_mode = 0;

	protected function setup_actions()
	{
		if ( ! WordPress::mustRegisterUI() )
			return;

		$this->filter( 'admin_body_class' );

		if ( is_blog_admin() ) {

			$this->filter( 'admin_title', 2 );
			$this->filter( 'tablepress_admin_menu_entry_name' );

			$this->action( 'admin_menu', 0, 12 );
			$this->action( 'admin_menu', 0, 999, 'late' );
			$this->action( 'admin_enqueue_scripts', 0, 999 );

			// hides network-active plugins alongside plugins active for the current site
			if ( 'active' != self::req( 'plugin_status' ) )
				$this->filter_false( 'show_network_active_plugins' );
		}

		$this->action( 'doing_dark_mode' );
		$this->action( 'admin_print_styles', 0, 999 );
		$this->filter( 'admin_footer_text', 1, 9999 );
		$this->filter( 'update_footer', 1, 9999 );
	}

	public function admin_body_class( $classes )
	{
		if ( function_exists( 'get_network' ) )
			$classes.= ' network-'.HTML::sanitizeClass( URL::prepTitle( str_replace( '.', '-', get_network()->domain ) ) );

		if ( gNetwork()->option( 'admin_chosen', 'blog' ) )
			$classes.= ' enhancement-chosen-enabled';

		if ( gNetwork()->option( 'thrift_mode', 'blog' ) )
			$classes.= ' thrift-mode';

		if ( ! gNetwork()->option( 'user_locale', 'profile' ) )
			$classes.= ' hide-userlocale-option';

		if ( gNetwork()->module( 'adminbar' ) && ! gNetwork()->adminbar->show_adminbar() )
			$classes.= ' hide-adminbar-option';

		if ( class_exists( __NAMESPACE__.'\\Restricted' ) && Restricted::isRestricted() )
			$classes.= ' hide-admin-menu';

		if ( WordPress::isSuperAdmin() )
			$classes.= ' current-user-superadmin';

		return $classes;
	}

	public function admin_title( $admin_title, $title )
	{
		/* translators: %1$s: page title, %2$s: site name */
		return sprintf( _x( '%1$s &lsaquo; %2$s &#8212; Content Management', 'Modules: Admin: HTML Title', 'gnetwork' ), $title, get_bloginfo( 'name', 'display' ) );
	}

	public function tablepress_admin_menu_entry_name()
	{
		return _x( 'Tables', 'Modules: Admin: Page Menu', 'gnetwork' );
	}

	public function admin_menu()
	{
		do_action( $this->hook_base( 'setup_menu' ), 'admin' );

		if ( WordPress::cuc( 'manage_options' ) ) {

			$hook = add_menu_page(
				_x( 'Network Extras', 'Modules: Admin: Page Menu', 'gnetwork' ),
				_x( 'Extras', 'Modules: Admin: Page Menu', 'gnetwork' ),
				'manage_options',
				$this->base,
				[ $this, 'settings_page' ],
				'dashicons-screenoptions',
				120
			);

			if ( ! gNetwork()->option( 'thrift_mode', 'blog' ) )
				foreach ( $this->get_menus() as $priority => $group )
					foreach ( $group as $sub => $args )
						add_submenu_page( $this->base,
							/* translators: %s: menu title */
							sprintf( _x( 'gNetwork Extras: %s', 'Modules: Admin: Page Menu', 'gnetwork' ), $args['title'] ),
							$args['title'],
							$args['cap'],
							$this->base.'&sub='.$sub,
							[ $this, 'settings_page' ]
						);

			$tools = add_submenu_page( 'tools.php',
				_x( 'Network Tools', 'Modules: Admin: Page Menu', 'gnetwork' ),
				_x( 'Extras', 'Modules: Admin: Page Menu', 'gnetwork' ),
				'edit_posts', // core's for tools.php
				$this->base.'-tools',
				[ $this, 'tools_page' ]
			);

		} else {

			$hook = add_submenu_page( 'index.php',
				_x( 'Network Extras', 'Modules: Admin: Page Menu', 'gnetwork' ),
				_x( 'My Extras', 'Modules: Admin: Page Menu', 'gnetwork' ),
				'read',
				$this->base,
				[ $this, 'settings_page' ]
			);

			$tools = add_submenu_page( 'tools.php',
				_x( 'Network Tools', 'Modules: Admin: Page Menu', 'gnetwork' ),
				_x( 'Extras', 'Modules: Admin: Page Menu', 'gnetwork' ),
				'edit_posts', // core's for tools.php
				$this->base.'-tools',
				[ $this, 'tools_page' ]
			);
		}

		add_action( 'load-'.$hook, [ $this, 'settings_load' ] );
		add_action( 'load-'.$tools, [ $this, 'tools_load' ] );

		add_submenu_page( 'plugins.php',
			_x( 'Active Plugins', 'Modules: Admin: Page Menu', 'gnetwork' ),
			_x( 'Active Plugins', 'Modules: Admin: Page Menu', 'gnetwork' ),
			'activate_plugins',
			'plugins.php?plugin_status=active',
			'',
			1
		);
	}

	public function admin_menu_late()
	{
		$GLOBALS['submenu'][$this->base][0] = [
			_x( 'Overview', 'Modules: Menu Name', 'gnetwork' ),
			'read',
			$this->base,
			_x( 'Network Extras', 'Modules: Admin: Page Menu', 'gnetwork' ),
		];

		// NOTE: Sensei LMS menu title
		// if ( ! empty( $GLOBALS['menu'][50][2] ) && 'sensei' == $GLOBALS['menu'][50][2] )
		// 	$GLOBALS['menu'][50][0] = _x( 'Sensei', 'Modules: Admin: Page Menu', 'gnetwork' );

		// @REF: http://justintadlock.com/?p=3320
		if ( ! WordPress::cuc( 'update_plugins' ) )
			remove_submenu_page( 'themes.php', 'theme-editor.php' );
	}

	public static function menuURL( $full = TRUE, $context = 'settings', $scheme = 'admin', $network = NULL )
	{
		if ( 'tools' == $context )
			$relative = 'tools.php?page='.static::BASE.'-tools';
		else
			$relative = WordPress::cuc( 'manage_options' )
				? 'admin.php?page='.static::BASE
				: 'index.php?page='.static::BASE;

		return $full
			? get_admin_url( NULL, $relative, $scheme )
			: $relative;
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_options', $priority = 10 )
	{
		if ( ! is_blog_admin() )
			return;

		gNetwork()->admin->menus['settings'][( (int) $priority )][$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

		if ( $callback )
			add_action( static::BASE.'_admin_settings', $callback );
	}

	public static function registerTool( $sub, $title = NULL, $callback = FALSE, $capability = 'manage_options', $priority = 10 )
	{
		if ( ! is_blog_admin() )
			return;

		gNetwork()->admin->menus['tools'][( (int) $priority )][$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

		if ( $callback )
			add_action( static::BASE.'_admin_tools', $callback );
	}

	public static function registerTinyMCE( $plugin, $filepath, $row = 1, $context = 'post' )
	{
		global $pagenow;

		if ( ! gNetwork()->module( 'editor' ) )
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

		do_action( $this->hook_base( 'admin_settings' ), $sub );
	}

	public function tools_load()
	{
		do_action( $this->hook_base( 'admin_tools' ), Settings::sub( 'overview' ) );
	}

	public function settings_page()
	{
		$uri  = self::menuURL( FALSE );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'settings_subs', $this->get_subs() );

		Settings::wrapOpen( $sub );

		if ( $this->cucSub( $sub ) ) {

			Settings::headerTitle( NULL, 'version' );
			HTML::headerNav( $uri, $sub, $subs );
			Settings::message( $this->filters( 'settings_messages', Settings::messages(), $sub ) );

			if ( 'overview' == $sub )
				$this->settings_overview( $uri );

			else if ( 'console' == $sub )
				@require_once GNETWORK_DIR.'includes/Layouts/console.'.$this->key.'.php';

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

			Settings::sideOpen( NULL, $uri, $sub, $subs, FALSE );
			Settings::message( $this->filters( 'tools_messages', Settings::messages(), $sub ) );

			if ( 'overview' == $sub )
				$this->tools_overview( $uri );

			else if ( ! $this->actions( 'tools_sub_'.$sub, $uri, $sub ) )
				Settings::cheatin();

			Settings::sideClose();

		} else {

			Settings::cheatin();
		}

		Settings::wrapClose();
	}

	protected function tools_overview( $uri )
	{
		$tabs     = [];
		$sitemeta = function_exists( 'is_site_meta_supported' ) && is_site_meta_supported();

		HTML::h2( _x( 'Site Reports', 'Modules: Admin: Site Overview', 'gnetwork' ) );

		if ( class_exists( __NAMESPACE__.'\\Locale' )
			&& current_user_can( 'manage_options' ) )
			$tabs['loadedmos'] = [
				'title' => _x( 'Loaded Localizations', 'Modules: Admin: Site Overview', 'gnetwork' ),
				'cb'    => [ __NAMESPACE__.'\\Locale', 'loadedMOs' ],
			];

		if ( class_exists( __NAMESPACE__.'\\Authors' )
			&& current_user_can( 'list_users' ) )
			$tabs['userroles'] = [
				'title' => _x( 'Roles and Capabilities', 'Modules: Admin: Site Overview', 'gnetwork' ),
				'cb'    => [ __NAMESPACE__.'\\Authors', 'userRoles' ],
			];

		if ( class_exists( __NAMESPACE__.'\\Media' )
			&& current_user_can( gNetwork()->option( 'tools_accesscap', 'media', 'edit_others_posts' ) ) )
			$tabs['imagesizes'] = [
				'title' => _x( 'Registered Image Sizes', 'Modules: Admin: Site Overview', 'gnetwork' ),
				'cb'    => [ __NAMESPACE__.'\\Media', 'registeredImageSizes' ],
			];

		if ( $sitemeta && WordPress::isSuperAdmin() )
			$tabs['sitemeta'] = [
				'title' => _x( 'Site Meta', 'Modules: Admin: Site Overview', 'gnetwork' ),
				'cb'    => static function() {
					HTML::tableSide( get_metadata( 'blog', get_current_blog_id() ) );
				},
			];

		if ( HTML::tabsList( $this->filters( 'overview', $tabs ), [ 'active' => TRUE ] ) )
			Scripts::enqueueMasonry();
	}

	protected function settings_overview( $uri )
	{
		HTML::h2( _x( 'Current Site Overview', 'Modules: Admin: Site Overview', 'gnetwork' ) );
		HTML::desc( _x( 'Below you can find various information about current site and contents.', 'Modules: Admin: Site Overview', 'gnetwork' ) );
		echo '<hr />';

		echo '<div class="masonry-grid">';

		if ( current_user_can( 'edit_posts' ) ) {

			self::summaryShortcodes();
		}

		if ( class_exists( __NAMESPACE__.'\\Comments' )
			&& current_user_can( 'moderate_comments' ) ) {

			self::summaryComments();
		}

		if ( class_exists( __NAMESPACE__.'\\Authors' )
			&& current_user_can( 'list_users' ) ) {

			self::summaryAuthors();
		}

		if ( class_exists( __NAMESPACE__.'\\Media' )
			&& current_user_can( gNetwork()->option( 'tools_accesscap', 'media', 'edit_others_posts' ) ) ) {

			self::summaryAttachments();
		}

		if ( class_exists( __NAMESPACE__.'\\Themes' )
			&& current_user_can( 'edit_theme_options' ) ) {

			self::summaryjQuery();
		}

		if ( class_exists( __NAMESPACE__.'\\Debug' )
			&& current_user_can( 'manage_options' ) ) {

			Debug::currentTime();
			Debug::summaryUpload();
			Debug::cacheStats();
		}

		$this->actions( 'currents' );

		echo '</div>';

		Scripts::enqueueMasonry();
	}

	public static function summaryShortcodes()
	{
		echo '<div class="-wrap card -floated">';
		HTML::h2( _x( 'Available Shortcodes', 'Modules: Admin: Site Overview', 'gnetwork' ) );

			echo HTML::listCode( array_keys( $GLOBALS['shortcode_tags'] ), '<code>[%2$s]</code>' );
		echo '</div>';
	}

	public static function summaryComments()
	{
		echo '<div class="-wrap card -floated">';
		HTML::h2( _x( 'Comment Counts', 'Modules: Admin: Site Overview', 'gnetwork' ) );

			Comments::commentSummary();
		echo '</div>';
	}

	public static function summaryAuthors()
	{
		echo '<div class="-wrap card -floated">';
		HTML::h2( _x( 'Authors & Roles', 'Modules: Admin: Site Overview', 'gnetwork' ) );

			Authors::summarySiteUser();
		echo '</div>';
	}

	public static function summaryAttachments()
	{
		echo '<div class="-wrap card -floated">';
		HTML::h2( _x( 'Attachment Mime-Types', 'Modules: Admin: Site Overview', 'gnetwork' ) );

			Media::summaryAttachments();
		echo '</div>';
	}

	public static function summaryjQuery()
	{
		echo '<div class="-wrap card -floated" dir="ltr">';
		HTML::h2( _x( 'jQuery Versions', 'Modules: Admin: Site Overview', 'gnetwork' ) );

			echo Optimize::summaryjQuery();
		echo '</div>';
	}

	public function admin_enqueue_scripts()
	{
		if ( gNetwork()->option( 'admin_chosen', 'blog' )
			&& ! WordPress::isBlockEditor() ) {

			$script = 'jQuery(function($) {
				$("select.gnetwork-do-chosen, .postbox:not(#submitdiv) .inside select:not(.no-chosen):not(.postform), .tablenav select:not(#bulk-action-selector-top):not(#bulk-action-selector-bottom)").chosen({
					rtl: "rtl" === $("html").attr("dir"),
					no_results_text: "'._x( 'No results match', 'Modules: Admin: Chosen', 'gnetwork' ).'",
					disable_search_threshold: 10
				}).addClass("gnetwork-chosen");
			});';

			wp_add_inline_script( Scripts::enqueueScriptVendor( 'chosen.jquery', [ 'jquery' ], '1.8.7' ), $script );
		}
	}

	// @REF: https://github.com/danieltj27/Dark-Mode/wiki/Help:-Plugin-Compatibility-Guide
	public function doing_dark_mode( $user_id )
	{
		$this->dark_mode = $user_id;

		// @REF: https://github.com/johnbillion/query-monitor/issues/366
		self::define( 'QM_DARK_MODE', TRUE );
	}

	public function admin_print_styles()
	{
		if ( WordPress::isIFrame() )
			Utilities::linkStyleSheet( 'admin.iframe' );
		else
			Utilities::linkStyleSheet( 'admin.all' );

		if ( is_rtl() )
			HTML::linkStyleSheet( GNETWORK_URL.'assets/css/admin.rtl.css', GNETWORK_VERSION );

		if ( $this->dark_mode )
			// @SEE: https://github.com/WordPress/gutenberg/pull/28233
			HTML::linkStyleSheet( GNETWORK_URL.'assets/css/admin.darkmode.css', GNETWORK_VERSION );

		Utilities::customStyleSheet( 'admin.css' );

		if ( is_admin_bar_showing() ) {
			Utilities::linkStyleSheet( 'adminbar.all' );

			if ( gNetwork()->option( 'adminbar_styles', 'branding' ) )
				gNetwork()->branding->do_adminbar_styles();

			else
				Utilities::customStyleSheet( 'adminbar.css' );
		}

		if ( GNETWORK_ADMIN_JS_ENHANCEMENTS )
			wp_localize_script( Scripts::enqueueScript( 'admin.all' ), 'gNetwork', $this->localize_script() );
	}

	protected function localize_script()
	{
		return [
			'metabox_controls_collapse' => HTML::escape( _x( 'Collapse All', 'Modules: Admin: Localize Script', 'gnetwork' ) ),
			'metabox_controls_expand'   => HTML::escape( _x( 'Expand All', 'Modules: Admin: Localize Script', 'gnetwork' ) ),
			'reset_button_text'         => HTML::escape( _x( 'Reset', 'Modules: Admin: Localize Script', 'gnetwork' ) ),
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

		$content = _x( 'CODE IS POETRY', 'Modules: Admin', 'gnetwork' );

		if ( $branding = gNetwork()->option( 'text_slogan', 'branding' ) )
			return $content = $branding;

		return HTML::wrap( $content, '-slogan', FALSE );
	}
}

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Module extends Core\Base
{

	const BASE   = 'gnetwork';
	const MODULE = FALSE;

	public $options   = [];
	public $providers = [];

	public $menus = [
		'settings' => [],
		'tools'    => [],
	];

	protected $base = 'gnetwork';
	protected $key  = NULL;

	protected $network = TRUE;
	protected $user    = NULL;
	protected $front   = TRUE;

	protected $beta       = FALSE;
	protected $ajax       = FALSE;
	protected $cron       = FALSE;
	protected $installing = FALSE;
	protected $cli        = NULL;
	protected $dev        = NULL;
	protected $xmlrpc     = NULL;
	protected $iframe     = NULL;

	protected $rest_api_version = 'v1';

	protected $priority_current_screen = 10;

	protected $scripts_printed  = FALSE;
	protected $scripts_nojquery = [];

	protected $scripts = [];
	protected $buttons = [];
	protected $errors  = [];

	protected $counter = 0;

	public function __construct( $base = NULL, $slug = NULL )
	{
		if ( is_null( $this->key ) )
			$this->key = strtolower( str_ireplace( __NAMESPACE__.'\\Modules\\', '', get_class( $this ) ) );

		if ( ! GNETWORK_BETA_FEATURES && $this->beta )
			throw new Exception( 'Beta Feature!' );

		if ( ! $this->ajax && WordPress::isAJAX() )
			throw new Exception( 'Not on AJAX Calls!' );

		if ( ! $this->cron && WordPress::isCRON() )
			throw new Exception( 'Not on CRON Calls!' );

		// @SEE: https://core.trac.wordpress.org/ticket/23197
		if ( ! $this->installing && wp_installing() && 'wp-activate.php' !== WordPress::pageNow() )
			throw new Exception( 'Not while WP is Installing!' );

		if ( ! is_null( $this->dev ) ) {
			if ( WordPress::isDev() ) {
				if ( FALSE === $this->dev )
					throw new Exception( 'Not on Develepment Environment!' );
			} else {
				if ( TRUE === $this->dev )
					throw new Exception( 'Only on Develepment Environment!' );
			}
		}

		if ( ! is_null( $this->cli ) ) {
			if ( WordPress::isCLI() ) {
				if ( FALSE === $this->cli )
					throw new Exception( 'Not on CLI!' );
			} else {
				if ( TRUE === $this->cli )
					throw new Exception( 'Only on CLI!' );
			}
		}

		if ( ! is_null( $this->xmlrpc ) ) {
			if ( WordPress::isXMLRPC() ) {
				if ( FALSE === $this->xmlrpc )
					throw new Exception( 'Not on XML-RPC!' );
			} else {
				if ( TRUE === $this->xmlrpc )
					throw new Exception( 'Only on XML-RPC!' );
			}
		}

		if ( ! is_null( $this->iframe ) ) {
			if ( WordPress::isIFrame() ) {
				if ( FALSE === $this->iframe )
					throw new Exception( 'Not on iFrame!' );
			} else {
				if ( TRUE === $this->iframe )
					throw new Exception( 'Only on iFrame!' );
			}
		}

		if ( ! $this->front && ! is_admin() )
			throw new Exception( 'Not on Frontend!' );

		if ( ! is_null( $this->user ) && is_multisite() ) {
			if ( is_user_admin() ) {
				if ( FALSE === $this->user )
					throw new Exception( 'Not on User Admin!' );
			} else {
				if ( TRUE === $this->user )
					throw new Exception( 'Only on User Admin!' );
			}
		}

		if ( ! is_null( $base ) )
			$this->base = $base;

		if ( ! $this->setup_checks() )
			throw new Exception( 'Failed to pass setup checks!' );

		if ( method_exists( $this, 'default_settings' ) )
			$this->options = $this->init_options();

		if ( WordPress::isAJAX() && method_exists( $this, 'setup_ajax' ) )
			$this->setup_ajax( $_REQUEST );

		if ( FALSE === $this->setup_actions() )
			return;

		if ( $this->get_option( 'load_providers' ) )
			$this->setup_providers();

		if ( ! WordPress::mustRegisterUI() )
			return;

		if ( method_exists( $this, 'setup_menu' ) )
			add_action( $this->base.'_setup_menu', [ $this, 'setup_menu' ] );

		if ( method_exists( $this, 'setup_screen' ) )
			add_action( 'current_screen', [ $this, 'setup_screen' ], $this->priority_current_screen );

		if ( method_exists( $this, 'setup_dashboard' ) && $this->get_option( 'dashboard_widget', TRUE ) )
			add_action( 'wp_dashboard_setup', [ $this, 'setup_dashboard' ] );

		// only on cron enabled modules
		if ( $this->cron && method_exists( $this, 'schedule_actions' ) )
			add_action( 'admin_init', [ $this, 'schedule_actions' ] );
	}

	protected function setup_checks()
	{
		return TRUE;
	}

	protected function setup_actions()
	{
		// WILL BE OVERRIDDEN
	}

	protected function setup_providers()
	{
		$providers = $this->filters( 'providers', $this->get_bundled_providers() );

		if ( empty( $providers ) )
			return FALSE;

		$this->_init_providers( $providers );

		if ( is_admin() )
			$this->filter_module( 'dashboard', 'pointers', 1, 10, 'providers' );

		return TRUE;
	}

	public function get_menu_url( $sub = NULL, $admin = 'admin', $context = 'settings', $extra = [], $scheme = 'admin', $network = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		if ( is_null( $admin ) )
			$admin = $this->is_network() ? 'network' : 'admin';

		switch ( $admin ) {
			case 'admin'  : $url = Modules\Admin::menuURL( TRUE, $context, $scheme, $network ); break;
			case 'network': $url = Modules\Network::menuURL( TRUE, $context, $scheme, $network ); break;
			case 'user'   : $url = Modules\User::menuURL( TRUE, $context, $scheme, $network ); break;
		}

		return add_query_arg( array_merge( [ 'sub' => $sub ], $extra ), $url );
	}

	// we call 'setup_menu' action only if `WordPress::mustRegisterUI()`
	public function register_menu( $title = NULL, $sub = NULL, $priority = 10, $capability = NULL, $callback = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		if ( is_null( $callback ) )
			$callback = [ $this, 'settings' ];

		if ( $this->is_network() ) {

			if ( is_null( $capability ) )
				$capability = 'manage_network_options';

			Modules\Network::registerMenu( $sub, $title, $callback, $capability, $priority );

		} else {

			if ( is_null( $capability ) )
				$capability = array_key_exists( 'menus_accesscap', $this->options )
					? $this->options['menus_accesscap']
					: 'manage_options';

			Modules\Admin::registerMenu( $sub, $title, $callback, $capability, $priority );
		}

		// no need for user menu
	}

	public function register_tool( $title = NULL, $sub = NULL, $priority = 10, $capability = NULL, $callback = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		if ( is_null( $callback ) )
			$callback = [ $this, 'tools' ];

		if ( $this->is_network() ) {

			if ( is_null( $capability ) )
				$capability = 'manage_network_options';

			Modules\Network::registerTool( $sub, $title, $callback, $capability, $priority );

		} else {

			if ( is_null( $capability ) )
				$capability = array_key_exists( 'tools_accesscap', $this->options )
					? $this->options['tools_accesscap']
					: 'manage_options';

			Modules\Admin::registerTool( $sub, $title, $callback, $capability, $priority );
		}

		// no need for user menu
	}

	protected function get_menus( $context = 'settings' )
	{
		$menus = $this->menus[$context];
		ksort( $menus, SORT_NUMERIC );
		return $menus;
	}

	protected function get_subs( $context = 'settings' )
	{
		$subs = [];

		$subs['overview'] = _x( 'Overview', 'Module Core: Menu Name', 'gnetwork' );

		foreach ( $this->get_menus( $context ) as $priority => $group )
			foreach ( $group as $sub => $args )
				if ( WordPress::cuc( $args['cap'] ) )
					$subs[$sub] = $args['title'];

		if ( 'settings' == $context && WordPress::isSuperAdmin() )
			$subs['console'] = _x( 'Console', 'Module Core: Menu Name', 'gnetwork' );

		return $subs;
	}

	public function cucSub( $sub, $context = 'settings' )
	{
		if ( 'overview' == $sub )
			return TRUE;

		if ( in_array( $sub, [ 'console' ] ) )
			return WordPress::isSuperAdmin();

		foreach ( $this->menus[$context] as $priority => $group )
			if ( array_key_exists( $sub, $group ) )
				return WordPress::cuc( $group[$sub]['cap'] );

		return FALSE;
	}

	// falls back if no network
	public function is_network()
	{
		return is_multisite() ? $this->network : FALSE;
	}

	protected function action( $hooks, $args = 1, $priority = 10, $suffix = FALSE, $base = FALSE )
	{
		$hooks = (array) $hooks;

		if ( $method = self::sanitize_hook( ( $suffix ? $hooks[0].'_'.$suffix : $hooks[0] ) ) )
			foreach ( $hooks as $hook )
				add_action( ( $base ? $base.'_'.$hook : $hook ), [ $this, $method ], $priority, $args );
	}

	protected function filter( $hooks, $args = 1, $priority = 10, $suffix = FALSE, $base = FALSE )
	{
		$hooks = (array) $hooks;

		if ( $method = self::sanitize_hook( ( $suffix ? $hooks[0].'_'.$suffix : $hooks[0] ) ) )
			foreach ( $hooks as $hook )
				add_filter( ( $base ? $base.'_'.$hook : $hook ), [ $this, $method ], $priority, $args );
	}

	// USAGE: $this->action_module( 'network', 'saved', 5 );
	protected function action_module( $module, $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_action( $this->base.'_'.$module.'_'.$hook, [ $this, $method ], $priority, $args );
	}

	// USAGE: $this->action_self( 'saved', 5 );
	protected function action_self( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_action( $this->base.'_'.$this->key.'_'.$hook, [ $this, $method ], $priority, $args );
	}

	// USAGE: $this->filter_self( 'prepare', 4 );
	protected function filter_self( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $this->base.'_'.$this->key.'_'.$hook, [ $this, $method ], $priority, $args );
	}

	// USAGE: $this->filter_module( 'network', 'prepare', 4 );
	protected function filter_module( $module, $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_filter( $this->base.'_'.$module.'_'.$hook, [ $this, $method ], $priority, $args );
	}

	// @REF: https://gist.github.com/markjaquith/b752e3aa93d2421285757ada2a4869b1
	protected function filter_once( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $hook, function() use ( $method ) {
				static $ran = FALSE;

				$params = func_get_args();

				if ( $ran )
					return $params[0];

				$ran = TRUE;

				return call_user_func_array( [ $this, $method ], $params );
			}, $priority, $args );
	}

	// USAGE: $this->filter_true( 'disable_months_dropdown' );
	protected function filter_true( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return TRUE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_false( 'disable_months_dropdown' );
	protected function filter_false( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return FALSE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_zero( 'option_blog_public' );
	protected function filter_zero( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return 0;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_empty_string( 'option_blog_public' );
	protected function filter_empty_string( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return '';
		}, $priority, 1 );
	}

	// USAGE: $this->filter_empty_array( 'option_blog_public' );
	protected function filter_empty_array( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return [];
		}, $priority, 1 );
	}

	// USAGE: $this->filter_append( 'body_class', 'foo' );
	protected function filter_append( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) use ( $items ) {
			foreach ( (array) $items as $value )
				$first[] = $value;
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_set( 'shortcode_atts_gallery', [ 'columns' => 4 ] );
	protected function filter_set( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) use ( $items ) {
			foreach ( $items as $key => $value )
				$first[$key] = $value;
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_unset( 'shortcode_atts_gallery', [ 'columns' ] );
	protected function filter_unset( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) use ( $items ) {
			foreach ( (array) $items as $key )
				unset( $first[$key] );
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_string( 'parent_file', 'options-general.php' );
	protected function filter_string( $hook, $string, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) use ( $string ) {
			return $string;
		}, $priority, 1 );
	}

	protected static function sanitize_hook( $hook )
	{
		return trim( str_ireplace( [ '-', '.', '/' ], '_', $hook ) );
	}

	protected static function sanitize_base( $hook )
	{
		return trim( str_ireplace( [ '_', '.' ], '-', $hook ) );
	}

	protected function hook()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( self::sanitize_hook( $arg ) );

		return $this->base.'_'.$this->key.$suffix;
	}

	protected function classs()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( self::sanitize_base( $arg ) );

		return $this->base.'-'.self::sanitize_base( $this->key ).$suffix;
	}

	protected function hash()
	{
		$string = '';

		foreach ( func_get_args() as $arg )
			$string.= maybe_serialize( $arg );

		return md5( $this->base.$this->key.$string );
	}

	protected function hashwithsalt()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix.= maybe_serialize( $arg );

		return wp_hash( $this->base.$this->key.$suffix );
	}

	protected function actions()
	{
		$args = func_get_args();

		if ( count( $args ) < 1 )
			return FALSE;

		$args[0] = $this->hook( $args[0] );

		call_user_func_array( 'do_action', $args );

		return has_action( $args[0] );
	}

	protected function filters()
	{
		$args = func_get_args();

		if ( count( $args ) < 2 )
			return FALSE;

		$args[0] = $this->hook( $args[0] );

		return call_user_func_array( 'apply_filters', $args );
	}

	// `has_filter()` / `has_action()`
	protected function hooked( $hook, $suffix = FALSE, $function_to_check = FALSE )
	{
		if ( $tag = $this->hook( $hook, $suffix ) )
			return has_filter( $tag, $function_to_check );

		return FALSE;
	}

	// USAGE: add_filter( 'body_class', self::_array_append( 'foo' ) );
	protected static function _array_append( $item )
	{
		return function( $array ) use ( $item ) {
			$array[] = $item;
			return $array;
		};
	}

	// USAGE: add_filter( 'shortcode_atts_gallery', self::_array_set( 'columns', 4 ) );
	protected static function _array_set( $key, $value )
	{
		return function( $array ) use ( $key, $value ) {
			$array[$key] = $value;
			return $array;
		};
	}

	public function default_options()
	{
		return [];
	}

	protected function options_key()
	{
		return $this->base.'_'.$this->key;
	}

	// OVERRIDED BY CHILD PLUGINS
	public static function base()
	{
		return gNetwork()->base;
	}

	protected function init_options( $sanitize = true ) {

		$network = $this->base . 'OptionsNetwork';
		$blog    = $this->base . 'OptionsBlog';

		if ( empty( $GLOBALS[$network] ) ) {
			$GLOBALS[$network] = get_network_option( null, $this->base . '_site', [] );
		}

		if ( empty( $GLOBALS[$blog] ) ) {
			$GLOBALS[$blog] = get_option( $this->base . '_blog', [] );
		}

		if ( $this->is_network() ) {
			$options = isset( $GLOBALS[$network][$this->key] ) ? $GLOBALS[$network][$this->key] : [];
		} else {
			$options = isset( $GLOBALS[$blog][$this->key] ) ? $GLOBALS[$blog][$this->key] : [];
		}

		return $sanitize ? $this->settings_sanitize( $options, $this->default_options() ) : $options;
	}

	public function settings_sanitize( $options, $defaults = NULL )
	{
		if ( is_null( $defaults ) )
			$defaults = $this->default_options();

		foreach ( $defaults as $key => $val )
			if ( ! isset( $options[$key] ) )
				$options[$key] = $defaults[$key];

		return $options;
	}

	// used for non-existant options
	public function get_option( $name, $default = FALSE )
	{
		return array_key_exists( $name, $this->options ) ? $this->options[$name] : $default;
	}

	// used for empty options
	public function get_option_fallback( $name, $fallback )
	{
		return empty( $this->options[$name] ) ? $fallback : $this->options[$name];
	}

	// check if it's '0' then $disabled
	// otherwise returns $default
	// use only for strings
	public function default_option( $name, $default = FALSE, $disabled = '' )
	{
		if ( ! isset( $this->options[$name] ) )
			return $default;

		if ( '0' === $this->options[$name] )
			return $disabled;

		if ( empty( $this->options[$name] ) )
			return $default;

		return $this->options[$name];
	}

	// update options at once
	public function update_options( $options = NULL, $reset = FALSE )
	{
		if ( is_null( $options ) )
			$options = $this->options;

		if ( $this->is_network() )
			$stored = get_network_option( NULL, $this->base.'_site', [] );
		else
			$stored = get_option( $this->base.'_blog', [] );

		if ( $reset || empty( $options ) )
			unset( $stored[$this->key] );
		else
			$stored[$this->key] = $options;

		if ( $this->is_network() )
			return update_network_option( NULL, $this->base.'_site', $stored );
		else
			return update_option( $this->base.'_blog', $stored, TRUE );
	}

	// for out of context manipulations
	public function update_option( $key, $value )
	{
		if ( $this->is_network() )
			$stored = get_network_option( NULL, $this->base.'_site', [] );
		else
			$stored = get_option( $this->base.'_blog', [] );

		$stored[$this->key][$key] = $value;

		if ( $this->is_network() )
			return update_network_option( NULL, $this->base.'_site', $stored );
		else
			return update_option( $this->base.'_blog', $stored, TRUE );
	}

	public function delete_options()
	{
		return $this->update_options( NULL, TRUE );
	}

	// FIXME: move this to cleanup
	// used to cleanup old options
	public function delete_options_legacy( $options_key = NULL )
	{
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( $this->is_network() )
			return delete_network_option( NULL, $options_key );
		else
			return delete_option( $options_key );
	}

	protected function menu_hook( $sub = NULL, $context = 'settings', $admin = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		if ( is_null( $admin ) )
			$admin = $this->is_network() ? 'network' : 'admin';

		return $this->base.'_'.$admin.'_'.$context.'_sub_'.$sub;
	}

	// DEFAULT METHOD: settings hook handler
	public function settings( $sub = NULL, $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( TRUE === $key || $key == $sub ) {

			$this->settings_actions( $sub );
			$this->settings_update( $sub );

			add_action( $this->menu_hook( $sub ), [ $this, 'render_settings' ], 10, 2 );

			if ( $this->register_settings() )
				$this->settings_buttons( $sub );

			$this->settings_setup( $sub ); // must be after `register_settings()`

			$this->register_help( $sub );
		}
	}

	// DEFAULT METHOD: tools hook handler
	public function tools( $sub = NULL, $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( TRUE === $key || $key == $sub ) {

			$this->tools_actions( $sub );

			add_action( $this->menu_hook( $sub, 'tools' ), [ $this, 'render_tools' ], 10, 2 );

			$this->tools_buttons( $sub );
			$this->tools_setup( $sub );

			$this->register_help( $sub, 'tools' );
		}
	}

	// DEFAULT METHOD: used for settings page only hooks
	protected function settings_setup( $sub = NULL ) {}

	// DEFAULT METHOD: used for tools page only hooks
	protected function tools_setup( $sub = NULL ) {}

	// DEFAULT METHOD: used for settings overview sub only
	protected function settings_overview( $uri ) {}

	// DEFAULT METHOD: used for tools overview sub only
	protected function tools_overview( $uri ) {}

	// DEFAULT METHOD
	// CAUTION: the action method responsible for checking the nonce
	protected function settings_actions( $sub = NULL )
	{
		if ( empty( $_REQUEST['action'] ) )
			return;

		$action = sanitize_key( $_REQUEST['action'] );

		if ( ! method_exists( $this, 'settings_action_'.$action ) )
			return;

		call_user_func_array( [ $this, 'settings_action_'.$action ], [ $sub ] );
	}

	// DEFAULT METHOD
	protected function tools_actions( $sub = NULL ) {}

	// DEFAULT METHOD: setting sub html
	public function render_settings( $uri, $sub = 'general' )
	{
		$this->render_form_start( $uri, $sub );

		if ( method_exists( $this, 'settings_before' ) )
			$this->settings_before( $sub, $uri );

		do_settings_sections( $this->base.'_'.$sub );

		if ( method_exists( $this, 'settings_after' ) )
			$this->settings_after( $sub, $uri );

		$this->render_form_buttons( $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: tools sub html
	public function render_tools( $uri, $sub = 'general' )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'tools' );

			if ( $this->render_tools_html( $uri, $sub ) )
				$this->render_form_buttons( $sub );

			$this->render_tools_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub, 'bulk', 'tools' );
	}

	// DEFAULT METHOD: used for tools default sub html
	protected function render_tools_html( $uri, $sub = 'general' ) {}
	protected function render_tools_html_after( $uri, $sub = 'general' ) {}

	protected function render_form_start( $uri, $sub = 'general', $action = 'update', $context = 'settings', $check = TRUE )
	{
		$sidebox = $check && method_exists( $this, $context.'_sidebox' );

		echo '<form enctype="multipart/form-data"';
			echo ' class="'.HTML::prepClass( $this->base.'-form', '-form', ( $sidebox ? ' has-sidebox' : '' ) ).'"'; // WPCS: XSS ok;

			if ( 'ajax' == $action ) // @SEE: `$this->check_referer_ajax()`
				echo 'data-nonce="'.wp_create_nonce( $this->base.'_'.$sub.'-'.$context ).'"'; // WPCS: XSS ok;

			echo ' method="post" action="">';

			if ( in_array( $context, [ 'settings', 'tools' ] ) )
				$this->render_form_fields( $sub, $action, $context );

			if ( $check && $sidebox ) {
				echo '<div class="'.HTML::prepClass( '-sidebox', '-sidebox-'.$context, '-sidebox-'.$sub ).'">'; // WPCS: XSS ok;
					call_user_func_array( [ $this, $context.'_sidebox' ], [ $sub, $uri, $context ] );
				echo '</div>';
			}
	}

	protected function render_form_end( $uri, $sub = 'general', $action = 'update', $context = 'settings', $check = TRUE )
	{
		echo '</form>';

		// if ( 'settings' == $context && WordPress::isDev() )
		// 	self::dump( $this->options );
	}

	public function register_button( $key, $value = NULL, $type = FALSE, $atts = [] )
	{
		$this->buttons[] = [
			'key'   => $key,
			'value' => $value,
			'type'  => $type,
			'atts'  => $atts,
		];
	}

	protected function render_form_buttons( $sub = NULL, $wrap = '', $buttons = NULL )
	{
		if ( FALSE !== $wrap )
			echo $this->wrap_open_buttons( $wrap );

		if ( is_null( $buttons ) )
			$buttons = $this->buttons;

		foreach ( $buttons as $button )
			Settings::submitButton( $button['key'], $button['value'], $button['type'], $button['atts'] );

		if ( FALSE !== $wrap )
			echo '</p>';
	}

	protected function render_form_fields( $sub, $action = 'update', $context = 'settings' )
	{
		HTML::inputHidden( 'base', $this->base );
		HTML::inputHidden( 'key', $this->key );
		HTML::inputHidden( 'context', $context );
		HTML::inputHidden( 'sub', $sub );
		HTML::inputHidden( 'action', $action );

		wp_nonce_field( $this->base.'_'.$sub.'-'.$context ); // @SEE: `$this->check_referer()`
	}

	protected function nonce_field( $context = 'settings', $key = NULL, $name = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $name ) )
			$name = '_'.$this->base.'-'.$key.'-'.$context; // OLD: '_wpnonce'

		return wp_nonce_field( $this->base.'-'.$key.'-'.$context, $name, FALSE, TRUE );
	}

	protected function nonce_check( $context = 'settings', $key = NULL, $name = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $name ) )
			$name = '_'.$this->base.'-'.$key.'-'.$context; // OLD: '_wpnonce'

		return check_admin_referer( $this->base.'-'.$key.'-'.$context, $name );
	}

	protected function settings_update( $sub )
	{
		if ( ! empty( $_POST ) && 'update' == $_POST['action'] ) {

			$this->check_referer( $sub, 'settings' );

			if ( isset( $_POST['reset'] ) )
				$message = $this->reset_settings() ? 'resetting' : 'error';

			else if ( isset( $_POST['submit'] ) )
				$message = $this->save_settings() ? 'updated' : 'error';

			else
				return FALSE;

			WordPress::redirectReferer( $message );
		}
	}

	protected function check_referer( $sub, $context )
	{
		return check_admin_referer( $this->base.'_'.$sub.'-'.$context );
	}

	protected function check_referer_ajax( $sub, $context, $key = 'nonce' )
	{
		return check_ajax_referer( $this->base.'_'.$sub.'-'.$context, $key );
	}

	public function reset_settings( $options_key = NULL )
	{
		$this->delete_options_legacy( $options_key );
		return $this->update_options( NULL, TRUE );
	}

	// DEFAULT METHOD
	// CAUTION: caller must check the nonce
	// FIXME: use filter arg for sanitize
	// @SEE: http://codex.wordpress.org/Data_Validation#Input_Validation
	protected function save_settings( $options_key = NULL )
	{
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( isset( $_POST[$options_key] ) && is_array( $_POST[$options_key] ) ) {

			$settings = apply_filters( $options_key.'_default_settings', $this->prep_settings() );
			$options  = apply_filters( $options_key.'_default_options', $this->default_options() );

			foreach ( $options as $setting => $default ) {

				if ( isset( $_POST[$options_key][$setting] ) ) {

					$type = empty( $settings[$setting]['type'] ) ? NULL : $settings[$setting]['type'];

					if ( is_array( $_POST[$options_key][$setting] ) ) {

						if ( 'text' == $type ) {

							// multiple texts
							foreach ( $_POST[$options_key][$setting] as $key => $value )
								if ( $string = trim( self::unslash( $value ) ) )
									$options[$setting][sanitize_key( $key )] = $string;

						} else {

							// multiple checkboxes
							$options[$setting] = array_keys( $_POST[$options_key][$setting] );
						}

					} else {
						// other options
						$options[$setting] = trim( self::unslash( $_POST[$options_key][$setting] ) );
					}

					// skip defaults
					if ( $options[$setting] == $default )
						unset( $options[$setting] );
				}
			}

			$this->delete_options_legacy( $options_key );
			return $this->update_options( $options, FALSE );
		}

		return FALSE;
	}

	protected function prep_settings()
	{
		$settings = [];

		if ( method_exists( $this, 'default_settings' ) ) {
			foreach ( $this->default_settings( TRUE ) as $section ) {
				foreach ( $section as $key => $field ) {
					if ( $args = $this->get_settings_field( $key, $field ) ) {
						$settings[$args['field']] = $args;
					}
				}
			}
		}

		return $settings;
	}

	public function register_settings()
	{
		if ( ! method_exists( $this, 'default_settings' ) )
			return FALSE;

		$page = $this->options_key();

		$settings = apply_filters( $page.'_default_settings', $this->default_settings() );

		if ( empty( $settings ) )
			return FALSE;

		foreach ( $settings as $section_suffix => $fields ) {

			if ( is_array( $fields ) ) {

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$section_callback = [ $this, 'settings_section'.$section_suffix ];
				else
					$section_callback = '__return_false';

				$callback = apply_filters( $page.'_settings_section', $section_callback, $section_suffix );

				$section = $page.$section_suffix;
				add_settings_section( $section, FALSE, $callback, $page );

				foreach ( $fields as $key => $field ) {

					$args = $this->get_settings_field( $key, $field );

					if ( FALSE === $args )
						continue;

					$this->add_settings_field( array_merge( $args, [
						'page'    => $page,
						'section' => $section,
					] ) );
				}
			}
		}

		// register settings on the settings page only
		add_action( 'admin_print_footer_scripts', [ $this, 'print_scripts' ], 99 );

		return TRUE;
	}

	protected function get_settings_field( $key, $field )
	{
		if ( FALSE === $field )
			return FALSE;

		if ( is_array( $field ) )
			return $field;

		$settings = __NAMESPACE__.'\\Settings';

		// passing as custom variable
		if ( is_string( $key ) && method_exists( $settings, 'getSetting_'.$key ) )
			return call_user_func_array( [ $settings, 'getSetting_'.$key ], [ $field ] );

		if ( method_exists( $settings, 'getSetting_'.$field ) )
			return call_user_func( [ $settings, 'getSetting_'.$field ] );

		return FALSE;
	}

	public function settings_section_misc()
	{
		Settings::fieldSection( _x( 'Miscellaneous', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_styling()
	{
		Settings::fieldSection( _x( 'Styling', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_admin()
	{
		Settings::fieldSection( _x( 'Administration', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_front()
	{
		Settings::fieldSection( _x( 'Front-end', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_overrides()
	{
		Settings::fieldSection( _x( 'Overrides', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_services()
	{
		Settings::fieldSection( _x( 'Services', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_economics()
	{
		Settings::fieldSection( _x( 'Economics', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_redirects()
	{
		Settings::fieldSection( _x( 'Redirects', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_login()
	{
		Settings::fieldSection( _x( 'Login', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_signup()
	{
		Settings::fieldSection( _x( 'Sign-up', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_dashboard()
	{
		Settings::fieldSection( _x( 'Dashboard', 'Module Core: Settings', 'gnetwork' ) );
	}

	public function settings_section_adminbar()
	{
		Settings::fieldSection( _x( 'Admin-bar', 'Module Core: Settings', 'gnetwork' ) );
	}

	protected function settings_buttons( $sub = NULL )
	{
		$this->register_button( 'submit', NULL, TRUE );
		$this->register_button( 'reset', NULL, 'reset', TRUE );
	}

	protected function tools_buttons( $sub = NULL ) {}

	public function add_settings_field( $atts )
	{
		$args = array_merge( [
			'page'     => $this->options_key(),
			'section'  => $this->options_key().'_general',
			'field_cb' => [ $this, 'do_settings_field' ],
			'field'    => FALSE,
			'title'    => '',
		], $atts );

		if ( ! $args['field'] )
			return;

		if ( 'debug' == $args['field'] ) {

			if ( ! WordPress::isDev() )
				return;

			$args['type'] = 'debug';

			if ( ! $args['title'] )
				$args['title'] = _x( 'Debug', 'Module Core', 'gnetwork' );
		}

		add_settings_field(
			$args['field'],
			$args['title'],
			$args['field_cb'],
			$args['page'],
			$args['section'],
			$args
		);
	}

	public function register_help( $sub = NULL, $context = 'settings' )
	{
		$screen = get_current_screen();

		foreach ( $this->register_help_tabs( $sub, $context ) as $tab )
			$screen->add_help_tab( $tab );

		if ( $sidebar = $this->register_help_sidebar( $sub, $context ) )
			$screen->set_help_sidebar( Settings::helpSidebar( $sidebar ) );

		if ( 'settings' != $context )
			return;

		if ( $sub != $this->key )
			return;

		if ( method_exists( $this, 'get_shortcodes' ) )
			$screen->add_help_tab( [
				'id'      => $this->classs( 'help-shortcodes' ),
				'title'   => _x( 'Extra Shortcodes', 'Module Core: Help Tab Title', 'gnetwork' ),
				'content' => '<p>'._x( 'These are extra shortcodes provided by this module:', 'Module Core: Help Tab Content', 'gnetwork' )
					.'</p>'.HTML::listCode( $this->get_shortcodes(), '<code>[%1$s]</code>' ),
			] );

		if ( $options = $this->init_options( FALSE ) )
			$screen->add_help_tab( [
				'id'       => $this->classs( 'help-options' ),
				'title'    => _x( 'Saved Options', 'Module Core: Help Tab Title', 'gnetwork' ),
				'content'  => HTML::tableCode( $options ),
				'priority' => 999,
			] );
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [];
	}

	protected function register_help_sidebar( $sub = NULL, $context = 'settings' )
	{
		return [];
	}

	public function do_settings_field( $atts = [] )
	{
		Settings::fieldType( array_merge( [
			'defaults'     => $this->default_options(),
			'options'      => $this->options,
			'option_base'  => $this->base,
			'option_group' => $this->key,
		], $atts ), $this->scripts );
	}

	public function print_scripts()
	{
		if ( $this->scripts_printed )
			return;

		if ( count( $this->scripts_nojquery ) )
			HTML::wrapScript( implode( "\n", $this->scripts_nojquery ) );

		if ( count( $this->scripts ) )
			HTML::wrapjQueryReady( implode( "\n", $this->scripts ) );

		$this->scripts_printed = TRUE;
	}

	protected function selector( $prefix = '%1$s-selector-%2$s' )
	{
		$this->counter++;
		return sprintf( $prefix, $this->key, $this->counter );
	}

	protected function register_shortcodes( $option_key = 'register_shortcodes' )
	{
		if ( ! method_exists( $this, 'get_shortcodes' ) )
			return FALSE;

		if ( ! $this->get_option( $option_key, TRUE ) )
			return FALSE;

		foreach ( $this->get_shortcodes() as $shortcode => $method ) {
			remove_shortcode( $shortcode );
			add_shortcode( $shortcode, [ $this, $method ] );
		}

		return TRUE;
	}

	public static function shortcodeWrap( $html, $suffix = FALSE, $args = [], $block = TRUE, $extra = [] )
	{
		if ( is_null( $html ) )
			return $html;

		$before = empty( $args['before'] ) ? '' : $args['before'];
		$after  = empty( $args['after'] )  ? '' : $args['after'];

		if ( empty( $args['wrap'] ) )
			return $before.$html.$after;

		$classes = [ '-wrap', 'gnetwork-wrap-shortcode' ];
		$wrap    = TRUE === $args['wrap'] ? ( $block ? 'div' : 'span' ) : $args['wrap'];

		if ( $suffix )
			$classes[] = 'shortcode-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		if ( ! empty( $args['class'] ) )
			$classes = HTML::attrClass( $classes, $args['class'] );

		if ( $after )
			return $before.HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $html ).$after;

		return HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $before.$html );
	}

	public static function shortcodeTermTitle( $atts, $term = FALSE )
	{
		$args = self::atts( [
			'title'        => NULL, // FALSE to disable
			'title_link'   => NULL, // FALSE to disable
			'title_title'  => '',
			'title_tag'    => 'h3',
			'title_anchor' => 'term-',
		], $atts );

		if ( is_null( $args['title'] ) )
			$args['title'] = $term ? sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) : FALSE;

		if ( $args['title'] ) {
			if ( is_null( $args['title_link'] ) && $term )
				$args['title'] = HTML::tag( 'a', [
					'href'  => get_term_link( $term, $term->taxonomy ),
					'title' => $args['title_title'],
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $args['title_title'],
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = HTML::tag( $args['title_tag'], [
				'id'    => $term ? $args['title_anchor'].$term->term_id : FALSE,
				'class' => '-title',
			], $args['title'] );

		return $args['title'];
	}

	protected function is_request_action( $action, $extra = NULL, $default = FALSE )
	{
		if ( empty( $_REQUEST[$this->base.'_action'] )
			|| $_REQUEST[$this->base.'_action'] != $action )
				return $default;

		else if ( is_null( $extra ) )
			return $_REQUEST[$this->base.'_action'] == $action;

		else if ( ! empty( $_REQUEST[$extra] ) )
			return trim( $_REQUEST[$extra] );

		else
			return $default;
	}

	protected function remove_request_action( $extra = [], $url = NULL )
	{
		if ( is_null( $url ) )
			$url = URL::current();

		if ( is_array( $extra ) )
			$remove = $extra;
		else
			$remove[] = $extra;

		$remove[] = $this->base.'_action';

		return remove_query_arg( $remove, $url );
	}

	protected function _hook_ajax( $auth = TRUE, $hook = NULL, $method = 'ajax' )
	{
		if ( is_null( $hook ) )
			$hook = $this->hook();

		if ( is_null( $auth ) || TRUE === $auth )
			add_action( 'wp_ajax_'.$hook, [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			add_action( 'wp_ajax_nopriv_'.$hook, [ $this, $method ] );
	}

	// DEFAULT FILTER
	public function ajax()
	{
		Ajax::errorWhat();
	}

	protected function _hook_post( $auth = TRUE, $hook = NULL, $method = 'post' )
	{
		if ( ! is_admin() )
			return;

		if ( is_null( $hook ) )
			$hook = $this->hook();

		if ( is_null( $auth ) || TRUE === $auth )
			add_action( 'admin_post_'.$hook, [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			add_action( 'admin_post_nopriv_'.$hook, [ $this, $method ] );
	}

	// DEFAULT FILTER
	public function post()
	{
		wp_die();
	}

	protected function _hook_event( $name, $recurrence = 'monthly' )
	{
		$hook = $this->hook( $name );

		if ( ! wp_next_scheduled( $hook ) )
			return wp_schedule_event( time(), $recurrence, $hook );

		return TRUE;
	}

	protected function wrap( $html, $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<div class="'.HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' )
				.'>'.$html.'</div>'

			: '<span class="'.HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' )
				.'>'.$html.'</span>';
	}

	protected function wrap_open( $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<div class="'.HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>'

			: '<span class="'.HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>';
	}

	protected function wrap_open_buttons( $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<p class="'.HTML::prepClass( 'submit', $this->base.'-wrap', '-wrap-buttons', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>'

			: '<span class="'.HTML::prepClass( 'submit', $this->base.'-wrap', '-wrap-buttons', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>';
	}

	// checks to bail early if metabox/widget is hidden
	protected function check_hidden_metabox( $widget, $after = '' )
	{
		if ( ! in_array( $this->classs( $widget ), get_hidden_meta_boxes( get_current_screen() ) ) )
			return FALSE;

		echo HTML::tag( 'a', [
			'href'  => add_query_arg( 'flush', '' ),
			'class' => [ '-description', '-refresh' ],
		], _x( 'Please refresh the page to generate the data.', 'Module Core', 'gnetwork' ) );

		echo $after; // WPCS: XSS ok;

		return TRUE;
	}

	protected static function metabox_getTitleAction( $action )
	{
		return ' <span class="postbox-title-action"><a href="'.esc_url( $action['url'] ).'" title="'.$action['title'].'">'.$action['link'].'</a></span>';
	}

	protected function metabox_titleActionRefresh( $hook )
	{
		return self::metabox_getTitleAction( [
			'url'   => add_query_arg( 'flush', '' ),
			'title' => _x( 'Click to refresh the content', 'Module Core: Title Action', 'gnetwork' ),
			'link'  => _x( 'Refresh', 'Module Core: Title Action', 'gnetwork' ),
		] );
	}

	protected function metabox_titleActionInfo( $hook )
	{
		if ( ! method_exists( $this, 'get_widget_'.$hook.'_info' ) )
			return '';

		if ( ! $info = call_user_func( [ $this, 'get_widget_'.$hook.'_info' ] ) )
			return '';

		$html = ' <span class="postbox-title-action" data-tooltip="'.Text::wordWrap( $info ).'"';
		$html.= ' data-tooltip-pos="'.( HTML::rtl() ? 'down-left' : 'down-right' ).'"';
		$html.= ' data-tooltip-length="xlarge">'.HTML::getDashicon( 'info' ).'</span>';

		return $html;
	}

	// @REF: `wp_add_dashboard_widget()`
	protected function add_dashboard_widget( $name, $title, $action = FALSE, $extra = [], $callback = NULL, $option_key = 'dashboard_accesscap' )
	{
		if ( array_key_exists( $option_key, $this->options )
			&& ! WordPress::cuc( $this->options[$option_key] ) )
				return FALSE;

		$screen = get_current_screen();
		$hook   = self::sanitize_hook( $name );
		$id     = $this->classs( $name );
		$title  = $this->filters( 'dashboard_widget_title', $title, $name, $option_key );
		$args   = array_merge( [
			'__widget_basename' => $title, // passing title without extra markup
		], $extra );

		if ( is_array( $action ) ) {

			$title.= self::metabox_getTitleAction( $action );

		} else if ( $action ) {

			switch ( $action ) {
				case 'refresh': $title.= $this->metabox_titleActionRefresh( $hook ); break;
				case 'info'   : $title.= $this->metabox_titleActionInfo( $hook );    break;
			}
		}

		if ( is_null( $callback ) )
			$callback = [ $this, 'render_widget_'.$hook ];

		add_meta_box( $id, $title, $callback, $screen, 'normal', 'default', $args );

		add_filter( 'postbox_classes_'.$screen->id.'_'.$id, function( $classes ) use ( $name ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-admin-postbox'.'-'.$name,
				'-'.$this->key,
				'-'.$this->key.'-'.$name,
			] );
		} );

		if ( in_array( $id, get_hidden_meta_boxes( $screen ) ) )
			return FALSE; // prevent scripts

		return TRUE;
	}

	protected function get_bundled_providers()
	{
		return [];
	}

	protected function _init_providers( $providers )
	{
		foreach ( $providers as $provider => $args ) {

			if ( ! empty( $args['path'] ) && is_readable( $args['path'] ) )
				require_once $args['path'];

			if ( empty( $args['class'] ) )
				continue;

			$class = $args['class'];

			try {

				$this->providers[$provider] = new $class( $this->options, $this->base, $provider );

			} catch ( Exception $e ) {

				do_action( 'qm/debug', $e );

				if ( $this->options['debug_providers'] ) {

					$message = $e->getMessage();

					if ( ! in_array( $message, [ 'Not Enabled!', 'Not on AJAX Calls!' ] ) )
						Logger::DEBUG( vsprintf( '%s-DEBUG: provider: %s :: %s', [
							strtoupper( $this->key ),
							$provider,
							$message,
						] ) );
				}
			}
		}
	}

	// DEFAULT FILTER
	public function dashboard_pointers_providers( $items )
	{
		if ( ! WordPress::cuc( $this->options['manage_providers'] ) )
			return $items;

		$menu = $this->get_menu_url( $this->key, NULL );

		foreach ( $this->providers as $name => &$provider ) {

			if ( ! $provider->providerEnabled() )
				continue;

			if ( FALSE === ( $status = $provider->providerStatus() ) )
				continue;

			$items[] = HTML::tag( 'a', [
				'href'  => empty( $status[2] ) ? $menu : $status[2],
				'title' => $provider->providerName(),
				'class' => [ '-provider-status', $status[0] ],
				'data'  => [ 'name' => $name, 'module' => $this->key ],
			], $status[1] );
		}

		return $items;
	}

	public function get_default_provider()
	{
		if ( ! $this->get_option( 'load_providers' ) )
			return FALSE;

		$default = $this->get_option( 'default_provider', 'none' );

		if ( 'none' == $default || ! array_key_exists( $default, $this->providers ) )
			return FALSE;

		if ( ! $this->providers[$default]->providerEnabled() )
			return FALSE;

		return $this->providers[$default];
	}

	public function get_column_icon( $link = FALSE, $icon = 'wordpress-alt', $title = FALSE )
	{
		return HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ?: FALSE,
			'title'  => $title ?: FALSE,
			'class'  => [ '-icon', ( $link ? '-link' : '-info' ) ],
			'target' => $link ? '_blank' : FALSE,
		], HTML::getDashicon( $icon ) );
	}

	protected function register_blocktype( $name, $extra = [], $deps = NULL )
	{
		$args = [ 'editor_script' => Scripts::registerBlock( $name, $deps ) ];

		// if ( ! defined( 'GNETWORK_DISABLE_BLOCK_STYLES' ) || ! GNETWORK_DISABLE_BLOCK_STYLES )
		// 	$args['style'] = Scripts::registerBlockStyle( $name );

		$callback = self::sanitize_hook( 'block_'.$name.'_render_callback' );

		if ( method_exists( $this, $callback ) )
			$args['render_callback'] = [ $this, $callback ];

		$block = register_block_type( $this->base.'/'.$name, array_merge( $args, $extra ) );

		wp_set_script_translations( $args['editor_script'], 'gnetwork', GNETWORK_DIR.'languages' );

		return $block;
	}

	protected function register_blocktypes( $option_key = 'register_blocktypes' )
	{
		// checks for WP 5.0
		if ( ! function_exists( 'register_block_type' ) )
			return FALSE;

		if ( ! method_exists( $this, 'get_blocktypes' ) )
			return FALSE;

		if ( ! $this->get_option( $option_key, TRUE ) )
			return FALSE;

		foreach ( $this->get_blocktypes() as $blocktype ) {

			if ( empty( $blocktype[0] ) )
				continue;

			$extra = empty( $blocktype[1] ) ? [] : $blocktype[1];
			$deps  = empty( $blocktype[2] ) ? NULL : $blocktype[2];

			$this->register_blocktype( $blocktype[0], $extra, $deps );
		}

		return TRUE;
	}

	public static function blockWrap( $html, $suffix = FALSE, $args = [], $block = TRUE, $extra = [] )
	{
		if ( is_null( $html ) )
			return $html;

		$before = empty( $args['before'] ) ? '' : $args['before'];
		$after  = empty( $args['after'] )  ? '' : $args['after'];

		if ( ! array_key_exists( 'wrap', $args ) )
			$args['wrap'] = TRUE;

		if ( empty( $args['wrap'] ) )
			return $before.$html.$after;

		$classes = [ '-wrap', 'gnetwork-wrap-block' ];
		$wrap    = TRUE === $args['wrap'] ? ( $block ? 'div' : 'span' ) : $args['wrap'];

		if ( $suffix )
			$classes[] = 'wp-block-gnetwork-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		if ( ! empty( $args['alignment'] ) )
			$classes[] = 'gnetwork-block-align-'.$args['alignment'];

		if ( ! empty( $args['className'] ) )
			$classes = HTML::attrClass( $classes, $args['className'] );

		if ( $after )
			return $before.HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $html ).$after;

		return HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $before.$html );
	}

	public static function getTablelistPosts( $atts = [], $extra = [], $posttypes = 'any', $perpage = 25 )
	{
		$limit  = self::limit( $perpage );
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$args = array_merge( [
			'posts_per_page'   => $limit,
			'offset'           => $offset,
			'orderby'          => self::orderby( 'ID' ),
			'order'            => self::order( 'DESC' ),
			'post_type'        => $posttypes, // 'any',
			'post_status'      => 'any', // [ 'publish', 'future', 'draft', 'pending' ],
			'suppress_filters' => TRUE,
		], $atts );

		if ( ! empty( $_REQUEST['s'] ) )
			$args['s'] = $extra['s'] = $_REQUEST['s'];

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $extra['type'] = $_REQUEST['type'];

		if ( ! empty( $_REQUEST['author'] ) )
			$args['author'] = $extra['author'] = $_REQUEST['author'];

		if ( ! empty( $_REQUEST['parent'] ) )
			$args['post_parent'] = $extra['parent'] = $_REQUEST['parent'];

		if ( 'attachment' == $args['post_type'] && is_array( $args['post_status'] ) )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = HTML::tablePagination( $query->found_posts, $query->max_num_pages, $limit, $paged, $extra );

		$pagination['orderby'] = $args['orderby'];
		$pagination['order']   = $args['order'];

		return [ $posts, $pagination ];
	}

	public static function filterTablelistSearch( $list = NULL, $name = 's' )
	{
		return HTML::tag( 'input', [
			'type'        => 'search',
			'name'        => $name,
			'value'       => self::req( $name, '' ),
			'class'       => '-search',
			'placeholder' => _x( 'Search', 'Tablelist: Filter', 'gnetwork' ),
		] );
	}

	public static function isTablelistAction( $action, $check_cb = FALSE )
	{
		if ( $action == self::req( 'table_action' ) || isset( $_POST[$action] ) )
			return $check_cb ? (bool) count( self::req( '_cb', [] ) ) : TRUE;

		return FALSE;
	}
}

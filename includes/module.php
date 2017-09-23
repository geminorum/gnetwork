<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Module extends Core\Base
{

	public $options = [];
	public $menus   = [];

	protected $base = 'gnetwork';
	protected $key  = NULL;
	protected $blog = NULL;

	protected $network = TRUE;
	protected $user    = NULL;
	protected $front   = TRUE;

	protected $hidden     = FALSE;
	protected $ajax       = FALSE;
	protected $cron       = FALSE;
	protected $installing = FALSE;
	protected $cli        = NULL;
	protected $dev        = NULL;
	protected $xmlrpc     = NULL;
	protected $iframe     = NULL;

	protected $scripts_printed  = FALSE;
	protected $scripts_nojquery = [];

	protected $scripts = [];
	protected $buttons = [];
	protected $errors  = [];

	protected $counter = 0;

	public function __construct( $base = NULL, $slug = NULL )
	{
		if ( is_null( $this->key ) )
			$this->key = strtolower( str_ireplace( __NAMESPACE__.'\\', '', get_class( $this ) ) );

		if ( ! GNETWORK_HIDDEN_FEATURES && $this->hidden )
			throw new Exception( 'Hidden Feature!' );

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

		if ( ! is_admin() && ! $this->front )
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

		$this->blog = get_current_blog_id();

		if ( ! $this->setup_checks() )
			throw new Exception( 'Failed to pass setup checks!' );

		if ( method_exists( $this, 'default_settings' ) )
			$this->options = $this->init_options();

		if ( WordPress::isAJAX() && method_exists( $this, 'setup_ajax' ) )
			$this->setup_ajax( $_REQUEST );

		if ( FALSE === $this->setup_actions() )
			return;

		if ( ! WordPress::mustRegisterUI() )
			return;

		if ( method_exists( $this, 'setup_menu' ) )
			add_action( $this->base.'_setup_menu', [ $this, 'setup_menu' ] );
	}

	protected function setup_checks()
	{
		return TRUE;
	}

	protected function setup_actions()
	{
		// WILL OVERRIDED
	}

	// we call 'setup_menu' action only if `WordPress::mustRegisterUI()`
	public function register_menu( $title = NULL, $callback = NULL, $sub = NULL, $capability = NULL, $priority = 10 )
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
				$capability = 'manage_options';

			Modules\Admin::registerMenu( $sub, $title, $callback, $capability, $priority );
		}

		// no need for user menu
	}

	public function menus()
	{
		ksort( $this->menus, SORT_NUMERIC );
		return $this->menus;
	}

	public function cucSub( $sub )
	{
		if ( 'overview' == $sub )
			return TRUE;

		if ( in_array( $sub, [ 'console' ] ) )
			return WordPress::isSuperAdmin();

		foreach ( $this->menus as $priority => $group )
			if ( array_key_exists( $sub, $group ) )
				return WordPress::cuc( $group[$sub]['cap'] );

		return FALSE;
	}

	public function get_settings_url( $action = FALSE, $full = FALSE )
	{
		$args = [ 'sub' => $this->key ];

		if ( is_array( $action ) )
			$args = array_merge( $args, $action );

		else if ( FALSE !== $action )
			$args['action'] = $action;

		$url = $this->is_network() ? Settings::networkURL( $full ) : Settings::adminURL( $full );

		return add_query_arg( $args, $url );
	}

	// override this for non network install
	public function is_network()
	{
		return is_multisite() ? $this->network : FALSE;
	}

	protected function action( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_action( $hook, [ $this, $method ], $priority, $args );
	}

	protected function filter( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $hook, [ $this, $method ], $priority, $args );
	}

	// USAGE: $this->filter_module( 'importer', 'saved', 5 );
	protected function action_module( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_action( $this->base.'_'.$module.'_'.$hook, array( $this, $method ), $priority, $args );
	}

	// USAGE: $this->filter_module( 'importer', 'prepare', 4 );
	protected function filter_module( $module, $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_filter( $this->base.'_'.$module.'_'.$hook, array( $this, $method ), $priority, $args );
	}

	// @REF: https://gist.github.com/markjaquith/b752e3aa93d2421285757ada2a4869b1
	protected function filter_once( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $hook, function( $first ) use( $method ) {
				static $ran = FALSE;
				if ( $ran ) return $first;
				$ran = TRUE;
				return call_user_func_array( [ $this, $method ], func_get_args() );
			}, $priority, $args );
	}

	// USAGE: $this->filter_true( 'disable_months_dropdown' );
	protected function filter_true( $hook, $priority = 10 )
	{
		add_filter( $hook, function( $first ){
			return TRUE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_false( 'disable_months_dropdown' );
	protected function filter_false( $hook, $priority = 10 )
	{
		add_filter( $hook, function( $first ){
			return FALSE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_append( 'body_class', 'foo' );
	protected function filter_append( $hook, $item, $priority = 10 )
	{
		add_filter( $hook, function( $first ) use( $item ){
			$first[] = $item;
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_set( 'shortcode_atts_gallery', 'columns', 4 );
	protected function filter_set( $hook, $key, $value, $priority = 10 )
	{
		add_filter( $hook, function( $first ) use( $key, $value ){
			$first[$key] = $value;
			return $first;
		}, $priority, 1 );
	}

	protected static function sanitize_hook( $hook )
	{
		return trim( str_ireplace( [ '-', '.' ], '_', $hook ) );
	}

	protected function hook()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix .= '_'.$arg;

		return $this->base.'_'.$this->key.$suffix;
	}

	protected function classs()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix .= '-'.$arg;

		return $this->base.'-'.$this->key.$suffix;
	}

	protected function hash()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix .= maybe_serialize( $arg );

		return md5( $this->base.$this->key.$suffix );
	}

	protected function hashwithsalt()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix .= maybe_serialize( $arg );

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

	// USAGE: add_filter( 'body_class', self::__array_append( 'foo' ) );
	protected static function __array_append( $item )
	{
		return function( $array ) use ( $item ) {
			$array[] = $item;
			return $array;
		};
	}

	// USAGE: add_filter( 'shortcode_atts_gallery', self::__array_set( 'columns', 4 ) );
	protected static function __array_set( $key, $value )
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

	protected function init_options( $sanitize = TRUE )
	{
		$network = $this->base.'OptionsNetwork';
		$blog    = $this->base.'OptionsBlog';

		global ${$network}, ${$blog};

		if ( empty( ${$network} ) )
			${$network} = get_site_option( $this->base.'_site', [] );

		if ( empty( ${$blog} ) )
			${$blog} = get_option( $this->base.'_blog', [] );

		if ( $this->is_network() )
			$options = isset( ${$network}[$this->key] ) ? ${$network}[$this->key] : [];
		else
			$options = isset( ${$blog}[$this->key] ) ? ${$blog}[$this->key] : [];

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

	// option and it's default
	// it's really moot! since we sanitize options
	public function get_option( $name, $default = FALSE )
	{
		return ( isset( $this->options[$name] ) ? $this->options[$name] : $default ) ;
	}

	// update options at once
	public function update_options( $options = NULL, $reset = FALSE )
	{
		if ( is_null( $options ) )
			$options = $this->options;

		if ( $this->is_network() )
			$saved = get_site_option( $this->base.'_site', [] );
		else
			$saved = get_option( $this->base.'_blog', [] );

		if ( $reset || ! count( $options ) )
			unset( $saved[$this->key] );
		else
			$saved[$this->key] = $options;

		if ( $this->is_network() )
			return update_site_option( $this->base.'_site', $saved ); // FIXME: https://core.trac.wordpress.org/ticket/28290
		else
			return update_option( $this->base.'_blog', $saved, TRUE );
	}

	public function delete_options()
	{
		return $this->update_options( NULL, TRUE );
	}

	// used to cleanup old options
	public function delete_options_legacy( $options_key = NULL )
	{
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( $this->is_network() )
			return delete_site_option( $options_key );
		else
			return delete_option( $options_key );
	}

	protected function settings_hook( $sub = NULL, $force = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		if ( is_null( $force ) )
			$force = $this->is_network() ? 'network' : 'admin';

		return $this->base.'_'.$force.'_settings_sub_'.$sub;
	}

	// DEFAULT METHOD: settings hook handler
	public function settings( $sub = NULL )
	{
		if ( $this->key == $sub ) {

			$this->settings_actions( $sub );
			$this->settings_update( $sub );

			add_action( $this->settings_hook( $sub ), [ $this, 'settings_form' ], 10, 2 );

			$this->register_settings();
			$this->register_settings_buttons( $sub );
			$this->register_settings_help( $sub );
		}
	}

	// DEFAULT METHOD: MAYBE OVERRIDED
	// CAUTION: the action method responsible for checking the nonce
	// NOT USED YET
	protected function settings_actions( $sub = NULL )
	{
		if ( empty( $_REQUEST['action'] ) )
			return;

		$action = sanitize_key( $_REQUEST['action'] );

		if ( ! method_exists( $this, 'settings_action_'.$action ) )
			return;

		call_user_func_array( [ $this, 'settings_action_'.$action ], [ $sub ] );
	}

	public function settings_help() {}

	// DEFAULT METHOD: setting sub html
	public function settings_form( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub );

		if ( method_exists( $this, 'settings_before' ) )
			$this->settings_before( $sub, $uri );

		do_settings_sections( $this->base.'_'.$sub );

		if ( method_exists( $this, 'settings_after' ) )
			$this->settings_after( $sub, $uri );

		$this->settings_buttons( $sub );

		$this->settings_form_after( $uri, $sub );
	}

	protected function settings_form_before( $uri, $sub = 'general', $action = 'update', $check = TRUE )
	{
		$class = $this->base.'-form';

		if ( $check && $sidebox = method_exists( $this, 'settings_sidebox' ) )
			$class .= ' has-sidebox';

		echo '<form class="'.$class.'" method="post" action="">';

			$this->settings_fields( $sub, $action );

			if ( $check && $sidebox ) {
				echo '<div class="settings-sidebox settings-sidebox-'.$sub.'">';
					$this->settings_sidebox( $sub, $uri );
				echo '</div>';
			}
	}

	protected function settings_form_after( $uri, $sub = 'general', $action = 'update', $check = TRUE )
	{
		echo '</form>';
	}

	public function default_buttons( $sub = NULL )
	{
		$this->register_button( 'submit', NULL, TRUE );
		$this->register_button( 'reset', NULL, 'reset', TRUE );
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

	protected function settings_buttons( $sub = NULL, $wrap = '' )
	{
		if ( FALSE !== $wrap )
			echo '<p class="submit '.$this->base.'-wrap-buttons '.$wrap.'">';

		foreach ( $this->buttons as $button )
			Settings::submitButton( $button['key'], $button['value'], $button['type'], $button['atts'] );

		if ( FALSE !== $wrap )
			echo '</p>';
	}

	protected function settings_fields( $sub, $action = 'update', $context = 'settings' )
	{
		HTML::inputHidden( 'base', $this->base );
		HTML::inputHidden( 'key', $this->key );
		HTML::inputHidden( 'context', $context );
		HTML::inputHidden( 'sub', $sub );
		HTML::inputHidden( 'action', $action );

		wp_nonce_field( $this->base.'_'.$sub.'-settings' );
	}

	// FIXME: use filter arg for sanitize
	// @SEE: http://codex.wordpress.org/Data_Validation#Input_Validation
	protected function settings_update( $sub )
	{
		if ( ! empty( $_POST ) && 'update' == $_POST['action'] ) {

			$this->check_referer( $sub );

			if ( isset( $_POST['reset'] ) )
				$message = $this->reset_settings() ? 'resetting' : 'error';

			else if ( isset( $_POST['submit'] ) )
				$message = $this->save_settings() ? 'updated' : 'error';

			else
				return FALSE;

			WordPress::redirectReferer( $message );
		}
	}

	protected function check_referer( $sub )
	{
		check_admin_referer( $this->base.'_'.$sub.'-settings' );
	}

	public function reset_settings( $options_key = NULL )
	{
		$this->delete_options_legacy( $options_key );
		return $this->update_options( NULL, TRUE );
	}

	// DEFAULT METHOD
	// CAUTION: caller must check the nounce
	protected function save_settings( $options_key = NULL )
	{
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( isset( $_POST[$options_key] ) && is_array( $_POST[$options_key] ) ) {

			$options = apply_filters( $options_key.'_default_options', $this->default_options() );

			foreach ( $options as $setting => $default ) {
				if ( isset( $_POST[$options_key][$setting] ) ) {

					// multiple checkboxes
					if ( is_array( $_POST[$options_key][$setting] ) )
						$options[$setting] = count( $_POST[$options_key][$setting] )
							? array_keys( $_POST[$options_key][$setting] )
							: [];

					// other options
					else
						$options[$setting] = trim( stripslashes( $_POST[$options_key][$setting] ) );

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

	public function register_settings()
	{
		if ( ! method_exists( $this, 'default_settings' ) )
			return;

		$page = $this->options_key();

		$settings = apply_filters( $page.'_default_settings', $this->default_settings() );

		if ( ! count( $settings ) )
			return;

		foreach ( $settings as $section_suffix => $fields ) {
			if ( is_array( $fields ) ) {

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$section_callback = [ $this, 'settings_section'.$section_suffix ];
				else
					$section_callback = '__return_false';

				$callback = apply_filters( $page.'_settings_section', $section_callback, $section_suffix );

				$section = $page.$section_suffix;
				add_settings_section( $section, FALSE, $callback, $page );

				foreach ( $fields as $field )
					$this->add_settings_field( array_merge( $field, [
						'page'    => $page,
						'section' => $section,
					] ) );
			}
		}

		// register settings on the settings page only
		add_action( 'admin_print_footer_scripts', [ $this, 'print_scripts' ], 99 );
	}

	protected function register_settings_buttons( $sub = NULL )
	{
		if ( method_exists( $this, 'default_settings' )
			&& count( $this->default_settings() ) )
				$this->default_buttons( $sub );
	}

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
				$args['title'] = _x( 'Debug', 'Module Core', GNETWORK_TEXTDOMAIN );
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

	public function register_settings_help( $sub = NULL )
	{
		$screen = get_current_screen();
		$tabs   = $this->settings_help_tabs( $sub );

		foreach ( $tabs as $tab )
			$screen->add_help_tab( $tab );

		if ( $sub != $this->key )
			return;

		if ( method_exists( $this, 'get_shortcodes' ) )
			$screen->add_help_tab( [
				'id'      => $this->classs( 'help-shortcodes' ),
				'title'   => _x( 'Extra Shortcodes', 'Module Core: Help Tab Title', GNETWORK_TEXTDOMAIN ),
				'content' => '<p>'._x( 'These are extra shortcodes provided by this module:', 'Module Core: Help Tab Content', GNETWORK_TEXTDOMAIN )
					.'</p>'.HTML::listCode( $this->get_shortcodes(), '<code>[%1$s]</code>' ),
			] );

		if ( $options = $this->init_options( FALSE ) )
			$screen->add_help_tab( [
				'id'       => $this->classs( 'help-options' ),
				'title'    => _x( 'Currently Saved Options', 'Module Core: Help Tab Title', GNETWORK_TEXTDOMAIN ),
				'content'  => HTML::tableCode( $options ),
				'priority' => 999,
			] );
	}

	public function settings_help_tabs( $sub = NULL )
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

	protected function shortcodes( $shortcodes = [] )
	{
		foreach ( $shortcodes as $shortcode => $method ) {
			remove_shortcode( $shortcode );
			add_shortcode( $shortcode, [ $this, $method ] );
		}
	}

	public static function shortcodeWrap( $html, $suffix = FALSE, $args = [], $block = TRUE )
	{
		$before = empty( $args['before'] ) ? '' : $args['before'];
		$after  = empty( $args['after'] )  ? '' : $args['after'];

		if ( empty( $args['wrap'] ) )
			return $before.$html.$after;

		$classes = [ 'gnetwork-wrap-shortcode' ];

		if ( $suffix )
			$classes[] = 'shortcode-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		if ( ! empty( $args['class'] ) )
			$classes[] = $args['class'];

		if ( $after )
			return $before.HTML::tag( $block ? 'div' : 'span', [ 'class' => $classes ], $html ).$after;

		return HTML::tag( $block ? 'div' : 'span', [ 'class' => $classes ], $before.$html );
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

	protected function is_action( $action, $extra = NULL, $default = FALSE )
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

	protected function remove_action( $extra = [], $url = NULL )
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

	protected function _hook_ajax( $nopriv = FALSE, $hook = NULL )
	{
		if ( is_null( $hook ) )
			$hook = $this->hook();

		add_action( 'wp_ajax_'.$hook, [ $this, 'ajax' ] );

		if ( $nopriv )
			add_action( 'wp_ajax_nopriv_'.$hook, [ $this, 'ajax' ] );
	}

	// DEFAULT FILTER
	public function ajax()
	{
		Ajax::errorWhat();
	}
}

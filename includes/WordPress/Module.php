<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Module extends Core\Base
{

	protected $base = NULL;
	protected $key  = NULL;
	protected $path = NULL;
	protected $site = NULL;

	public static function module() { return []; }
	protected function setup( $args = [] ) {}

	protected function options_key()
	{
		return $this->hook();
	}

	// NOTE: only has key prefix: `key.arg-with-prefix`
	protected function dotted()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '.'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return $this->key.$suffix;
	}

	// NOTE: only has base prefix: `base-arg1-arg2`
	protected function dashed()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return $this->base.$suffix;
	}

	protected function hook()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( Core\Text::sanitizeHook( $arg ) );

		return $this->base.'_'.$this->key.$suffix;
	}

	// NOTE: same as `hook()` without the `$key`
	protected function hook_base()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( Core\Text::sanitizeHook( $arg ) );

		return $this->base.$suffix;
	}

	// NOTE: same as `hook()` without the `$base`
	protected function hook_key()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( Core\Text::sanitizeHook( $arg ) );

		return $this->key.$suffix;
	}

	protected function classs()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return $this->base.'-'.Core\Text::sanitizeBase( $this->key ).$suffix;
	}

	// NOTE: same as `classs()` without the `$key`
	protected function classs_base()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return $this->base.$suffix;
	}

	// NOTE: same as `classs()` without the `$base`
	protected function classs_key()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return Core\Text::sanitizeBase( $this->key ).$suffix;
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

	protected function stripprefix( $string, $key = NULL, $template = '_%s_' )
	{
		return Core\Text::stripPrefix( $string, sprintf( $template, is_null( $key ) ? $this->key : $key ) );
	}

	protected function action( $hooks, $args = 1, $priority = 10, $suffix = FALSE, $base = FALSE )
	{
		$hooks = (array) $hooks;

		if ( $method = Core\Text::sanitizeHook( ( $suffix ? $hooks[0].'_'.$suffix : $hooks[0] ) ) )
			foreach ( $hooks as $hook )
				add_action( ( $base ? $base.'_'.$hook : $hook ), [ $this, $method ], $priority, $args );
	}

	protected function filter( $hooks, $args = 1, $priority = 10, $suffix = FALSE, $base = FALSE )
	{
		$hooks = (array) $hooks;

		if ( $method = Core\Text::sanitizeHook( ( $suffix ? $hooks[0].'_'.$suffix : $hooks[0] ) ) )
			foreach ( $hooks as $hook )
				add_filter( ( $base ? $base.'_'.$hook : $hook ), [ $this, $method ], $priority, $args );
	}

	/**
	 * Hooks an action for an internal module.
	 *
	 * @example `$this->action_module( 'importer', 'saved', 8 );`
	 *
	 * @param string $module
	 * @param string $hook
	 * @param integer $args
	 * @param integer $priority
	 * @param false|string $suffix
	 * @return void
	 */
	protected function action_module( $module, $hook, $args = 1, $priority = 10, $suffix = '' )
	{
		if ( $method = Core\Text::sanitizeHook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_action( $this->hook_base( $module, $hook ), [ $this, $method ], $priority, $args );
	}

	/**
	 * Hooks a filter for an internal module.
	 *
	 * @example `$this->filter_module( 'importer', 'prepare', 7 );`
	 *
	 * @param string $module
	 * @param string $hook
	 * @param integer $args
	 * @param integer $priority
	 * @param false|string $suffix
	 * @return void
	 */
	protected function filter_module( $module, $hook, $args = 1, $priority = 10, $suffix = '' )
	{
		if ( $method = Core\Text::sanitizeHook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_filter( $this->hook_base( $module, $hook ), [ $this, $method ], $priority, $args );
	}

	/**
	 * Hooks a self action for an internal module.
	 * @example `$this->action_self( 'saved', 8 );`
	 *
	 * @param string $hook
	 * @param int $args
	 * @param int $priority
	 * @param false|string $suffix
	 * @return void
	 */
	protected function action_self( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = Core\Text::sanitizeHook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_action( $this->hook_base( $this->key, $hook ), [ $this, $method ], $priority, $args );
	}

	/**
	 * Hooks a self filter for an internal module.
	 * @example `$this->filter_self( 'prepare', 7 );`
	 *
	 * @param string $hook
	 * @param int $args
	 * @param int $priority
	 * @param false|string $suffix
	 * @return void
	 */
	protected function filter_self( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = Core\Text::sanitizeHook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $this->hook_base( $this->key, $hook ), [ $this, $method ], $priority, $args );
	}

	/**
	 * Adds a callback function to a filter hook and run only once.
	 * This works around the common "filter sandwich" pattern where you have to
	 * remember to call `remove_filter()` again after your call.
	 * @source https://gist.github.com/markjaquith/b752e3aa93d2421285757ada2a4869b1
	 * @also `WordPress\Hook::filterOnce()`
	 *
	 * @param string $hook
	 * @param int $args
	 * @param int $priority
	 * @param false|string $suffix
	 * @return void
	 */
	protected function filter_once( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = Core\Text::sanitizeHook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $hook, function () use ( $method ) {
				static $ran = FALSE;

				$params = func_get_args();

				if ( $ran )
					return $params[0];

				$ran = TRUE;

				return call_user_func_array( [ $this, $method ], $params );
			}, $priority, $args );
	}

	// USAGE: `$this->filter_true( 'disable_months_dropdown' );`
	protected function filter_true( $hook, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) {
			return TRUE;
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_false( 'disable_months_dropdown' );`
	protected function filter_false( $hook, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) {
			return FALSE;
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_true_module( 'meta', 'mainbox_callback' );`
	protected function filter_true_module( $module, $hook, $priority = 10 )
	{
		add_filter( $this->hook_base( $module, $hook ), static function ( $first ) {
			return TRUE;
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_false_module( 'meta', 'mainbox_callback' );`
	protected function filter_false_module( $module, $hook, $priority = 10 )
	{
		add_filter( $this->hook_base( $module, $hook ), static function ( $first ) {
			return FALSE;
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_zero( 'option_blog_public' );`
	protected function filter_zero( $hook, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) {
			return 0;
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_empty_string( 'option_blog_public' );`
	protected function filter_empty_string( $hook, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) {
			return '';
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_empty_array( 'option_blog_public' );`
	protected function filter_empty_array( $hook, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) {
			return [];
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_append( 'body_class', 'foo' );`
	protected function filter_append( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) use ( $items ) {
			foreach ( (array) $items as $value )
				$first[] = $value;
			return $first;
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_append_string( 'admin_body_class', [ 'foo', 'bar' ] );`
	protected function filter_append_string( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) use ( $items ) {
			foreach ( (array) $items as $value )
				$first = sprintf( '%s %s', trim( $first ?: '' ), trim( $value ?: '' ) );
			return $first;
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_set( 'shortcode_atts_gallery', [ 'columns' => 4 ] );`
	protected function filter_set( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) use ( $items ) {
			foreach ( $items as $key => $value )
				$first[$key] = $value;
			return $first;
		}, $priority, 1 );
	}

	// USAGE: `$this->filter_unset( 'shortcode_atts_gallery', [ 'columns' ] );`
	protected function filter_unset( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) use ( $items ) {
			foreach ( (array) $items as $key )
				unset( $first[$key] );
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_string( 'parent_file', 'options-general.php' );
	protected function filter_string( $hook, $string, $priority = 10 )
	{
		add_filter( $hook, static function ( $first ) use ( $string ) {
			return $string;
		}, $priority, 1 );
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

	/**
	 * Checks if any action/filter has been registered for a hook in this module.
	 *
	 * @param string $hook
	 * @param false|string $suffix
	 * @param false|callable $callback
	 * @return bool
	 */
	protected function hooked( $hook, $suffix = FALSE, $callback = FALSE )
	{
		if ( $tag = $this->hook( $hook, $suffix ) )
			return has_filter( $tag, $callback );

		return FALSE;
	}

	public function _return_string_yes() { return 'yes'; }
	public function _return_string_no()  { return 'no'; }

	// USAGE: `add_filter( 'body_class', self::_array_append( 'foo' ) );`
	public static function _array_append( $item )
	{
		return function ( $array ) use ( $item ) {
			$array[] = $item;
			return $array;
		};
	}

	// USAGE: `add_filter( 'shortcode_atts_gallery', self::_array_set( 'columns', 4 ) );`
	public static function _array_set( $key, $value )
	{
		return function ( $array ) use ( $key, $value ) {
			$array[$key] = $value;
			return $array;
		};
	}

	protected function nonce_create( $context = 'settings', $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		return wp_create_nonce( $this->base.'-'.$key.'-'.$context );
	}

	protected function nonce_verify( $context = 'settings', $nonce = NULL, $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $nonce ) )
			$nonce = self::req( '_'.$this->base.'-'.$key.'-'.$context, NULL ); // OLD: $_REQUEST['_wpnonce']

		return wp_verify_nonce( $nonce, $this->base.'-'.$key.'-'.$context );
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

	protected function is_request_action( $action, $extra = NULL, $default = FALSE )
	{
		$key = $this->hook_base( 'action' );

		if ( empty( $_REQUEST[$key] ) || $_REQUEST[$key] != $action )
			return $default;

		else if ( is_null( $extra ) )
			return $_REQUEST[$key] == $action;

		else if ( ! empty( $_REQUEST[$extra] ) )
			return trim( $_REQUEST[$extra] );

		else
			return $default;
	}

	protected function remove_request_action( $extra = [], $url = NULL )
	{
		if ( is_null( $url ) )
			$url = Core\URL::current();

		if ( is_array( $extra ) )
			$remove = $extra;
		else
			$remove[] = $extra;

		$remove[] = $this->hook_base( 'action' );

		return remove_query_arg( $remove, $url );
	}

	protected function get_sub_limit_option( $sub = NULL, $context = 'tools', $default = 25, $key = 'limit', $option = 'per_page' )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		$per_page = (int) get_user_option( $this->hook_base( $sub, $context, $option ) );

		if ( empty( $per_page ) || $per_page < 1 )
			$per_page = $default;

		return (int) self::req( $key, $per_page );
	}

	// NOTE: `add_screen_option()` only accept 2 options: `per_page` and `layout_columns`
	protected function add_sub_screen_option( $sub = NULL, $context = 'tools', $option = TRUE, $default = NULL, $label = NULL )
	{
		if ( FALSE === $option )
			return;

		if ( TRUE === $option )
			$option = 'per_page';

		$sub    = $sub    ?? $this->key;
		$option = $option ?? 'per_page';

		$args = [
			'option' => $this->hook_base( $sub, $context, $option ), // NOTE: must always ends with `per_page`!
			'label'  => $label,
		];

		switch ( $option ) {

			case 'layout_columns':

				add_filter( 'screen_options_show_submit', '__return_true' );

				$args['default'] = $default ?? 2;
				$args['max']     = 2;
				break;

			case 'per_page':
			default:
				$args['default'] = $default ?? 25;
		}

		add_screen_option( $option, $args );
	}

	protected function _hook_ajax( $auth = TRUE, $hook = NULL, $method = 'do_ajax' )
	{
		if ( is_null( $hook ) )
			$hook = $this->hook();

		if ( is_null( $auth ) || TRUE === $auth )
			add_action( 'wp_ajax_'.$hook, [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			add_action( 'wp_ajax_nopriv_'.$hook, [ $this, $method ] );
	}

	// DEFAULT FILTER
	public function do_ajax()
	{
		wp_send_json_error();
	}

	protected function _hook_post( $auth = TRUE, $hook = NULL, $method = 'do_post' )
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
	public function do_post()
	{
		wp_die();
	}

	// TODO: un-schedule on de-activation
	protected function _hook_event( $name, $recurrence = 'monthly' )
	{
		$hook = $this->hook( $name );

		if ( ! wp_next_scheduled( $hook ) )
			return wp_schedule_event( time(), $recurrence, $hook );

		return TRUE;
	}

	protected function hidden( $value, $data = [], $class = '', $id = FALSE )
	{
		echo Core\HTML::wrap( $value, Core\HTML::attrClass( 'hidden', $class ), TRUE, $data, $id );
	}

	protected function wrap( $html, $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		if ( empty( $html ) )
			return '';

		return $block
			? '<div class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' )
				.'>'.$html.'</div>'

			: '<span class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' )
				.'>'.$html.'</span>';
	}

	protected function wrap_open( $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<div class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>'

			: '<span class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>';
	}

	protected function wrap_open_buttons( $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<p class="'.Core\HTML::prepClass( 'submit', $this->base.'-wrap', '-wrap-buttons', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>'

			: '<span class="'.Core\HTML::prepClass( 'submit', $this->base.'-wrap', '-wrap-buttons', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>';
	}

	protected function wrap_open_row( $name = '', $extra = '', $id = FALSE, $hide = FALSE, $tag = 'li' )
	{
		return '<'.Core\HTML::sanitizeTag( $tag ).' class="'.Core\HTML::prepClass(
			'-row',
			'-wrap-row',
			'-'.$this->key,
			$name ? ( '-'.$name ) : '',
			$extra
		).'"'.( $id ? ' id="'.$id.'"' : '' )
		.( $hide ? ' style="display:none"' : '' ).'>';
	}

	// OLD: `get_adminbar_node_class()`
	protected function class_for_adminbar_node( $extra = [], $icononly = FALSE )
	{
		return Core\HTML::prepClass(
			$this->classs_base( 'adminbar', 'node', $icononly ? 'icononly' : '' ),
			sprintf( '-%s', $this->key ),
			$extra
		);
	}

	// `self::dump( ini_get( 'memory_limit' ) );`
	protected function raise_memory_limit( $count = 1, $per = 60, $context = NULL )
	{
		$limit = $count ? ( 300 + ( $per * $count ) ) : 0;

		@set_time_limit( $limit );
		// @ini_set( 'max_execution_time', $limit ); // maybe `-1`
		// @ini_set( 'max_input_time', $limit ); // maybe `-1`

		return wp_raise_memory_limit( $context ?? $this->base );
	}
}

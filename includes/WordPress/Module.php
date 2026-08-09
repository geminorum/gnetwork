<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Module extends Core\Base
{

	protected $base = NULL;
	protected $key  = NULL;
	protected $path = NULL;
	protected $site = NULL;
	protected $icon = NULL; // `Dashicons` only

	public static function factory()
	{
		throw new Core\Exception( 'The Factory is not defined!' );
	}

	public static function module(): array { return []; }
	protected function setup( array $args = [] ): bool { return FALSE; }

	protected function options_key(): string
	{
		return $this->hook();
	}

	// NOTE: only has key prefix: `key.arg-with-prefix`
	protected function dotted(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '.'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return $this->key.$suffix;
	}

	// NOTE: only has base prefix: `base-arg1-arg2`
	protected function dashed(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return $this->base.$suffix;
	}

	protected function hook(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( Core\Text::sanitizeHook( $arg ) );

		return $this->base.'_'.$this->key.$suffix;
	}

	// NOTE: same as `hook()` without the `$key`
	protected function hook_base(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( Core\Text::sanitizeHook( $arg ) );

		return $this->base.$suffix;
	}

	// NOTE: same as `hook()` without the `$base`
	protected function hook_key(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( Core\Text::sanitizeHook( $arg ) );

		return $this->key.$suffix;
	}

	protected function classs(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return $this->base.'-'.Core\Text::sanitizeBase( $this->key ).$suffix;
	}

	// NOTE: same as `classs()` without the `$key`
	protected function classs_base(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return $this->base.$suffix;
	}

	// NOTE: same as `classs()` without the `$base`
	protected function classs_key(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		return Core\Text::sanitizeBase( $this->key ).$suffix;
	}

	protected function salt(): string
	{
		return wp_salt();
	}

	protected function hash(): string
	{
		$string = '';

		foreach ( func_get_args() as $arg )
			$string.= maybe_serialize( $arg );

		return md5( $this->base.$this->key.$string );
	}

	protected function hashwithsalt(): string
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix.= maybe_serialize( $arg );

		return wp_hash( $this->base.$this->key.$suffix );
	}

	protected function stripprefix( string $string, ?string $key = NULL, string $template = '_%s_' ): string
	{
		return Core\Text::stripPrefix( $string, sprintf( $template, $key ?? $this->key ) );
	}

	protected function action( string|array $hooks, int $arguments = 1, int $priority = 10, string $suffix = '', string $base = '' ): bool
	{
		$hooks = (array) $hooks;

		if ( $method = Core\Text::sanitizeHook( self::und( Core\Arraay::valueFirst( $hooks ), $suffix ) ) ) {

			foreach ( $hooks as $hook )
				add_action( self::und( $base, $hook ), [ $this, $method ], $priority, $arguments );

			return TRUE;
		}

		return FALSE;
	}

	protected function filter( string|array $hooks, int $arguments = 1, int $priority = 10, string $suffix = '', string $base = '' ): bool
	{
		$hooks = (array) $hooks;

		if ( $method = Core\Text::sanitizeHook( self::und( Core\Arraay::valueFirst( $hooks ), $suffix ) ) ) {

			foreach ( $hooks as $hook )
				add_filter( self::und( $base, $hook ), [ $this, $method ], $priority, $arguments );

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Hooks an action for an internal module.
	 *
	 * @example `$this->action_module( 'importer', 'posts_saved', 8 );`
	 *
	 * @param string $module
	 * @param string $hook
	 * @param int $arguments
	 * @param int $priority
	 * @param string $suffix
	 * @return bool
	 */
	protected function action_module( string $module, string $hook, int $arguments = 1, int $priority = 10, string $suffix = '' ): bool
	{
		if ( $method = Core\Text::sanitizeHook( self::und( $module, $hook, $suffix ) ) )
			return add_action( $this->hook_base( $module, $hook ), [ $this, $method ], $priority, $arguments );

		return FALSE;
	}

	/**
	 * Hooks a filter for an internal module.
	 *
	 * @example `$this->filter_module( 'importer', 'posts_prepare', 7 );`
	 *
	 * @param string $module
	 * @param string $hook
	 * @param int $arguments
	 * @param int $priority
	 * @param string $suffix
	 * @return bool
	 */
	protected function filter_module( string $module, string $hook, int $arguments = 1, int $priority = 10, string $suffix = '' ): bool
	{
		if ( $method = Core\Text::sanitizeHook( self::und( $module, $hook, $suffix ) ) )
			return add_filter( $this->hook_base( $module, $hook ), [ $this, $method ], $priority, $arguments );

		return FALSE;
	}

	/**
	 * Hooks a self action for an internal module.
	 * @example `$this->action_self( 'posts_saved', 8 );`
	 *
	 * @param string $hook
	 * @param int $arguments
	 * @param int $priority
	 * @param string $suffix
	 * @return bool
	 */
	protected function action_self( string $hook, int $arguments = 1, int $priority = 10, string $suffix = '' ): bool
	{
		if ( $method = Core\Text::sanitizeHook( self::und( $hook, $suffix ) ) )
			return add_action( $this->hook_base( $this->key, $hook ), [ $this, $method ], $priority, $arguments );

		return FALSE;
	}

	/**
	 * Hooks a self filter for an internal module.
	 * @example `$this->filter_self( 'posts_prepare', 7 );`
	 *
	 * @param string $hook
	 * @param int $arguments
	 * @param int $priority
	 * @param string $suffix
	 * @return bool
	 */
	protected function filter_self( $hook, $arguments = 1, $priority = 10, $suffix = '' ): bool
	{
		if ( $method = Core\Text::sanitizeHook( self::und( $hook, $suffix ) ) )
			return add_filter( $this->hook_base( $this->key, $hook ), [ $this, $method ], $priority, $arguments );

		return FALSE;
	}

	/**
	 * Adds a callback function to a filter hook and run only once.
	 * This works around the common "filter sandwich" pattern where you have to
	 * remember to call `remove_filter()` again after your call.
	 * @source https://gist.github.com/markjaquith/b752e3aa93d2421285757ada2a4869b1
	 * @also `WordPress\Hook::filterOnce()`
	 *
	 * @param string $hook
	 * @param int $arguments
	 * @param int $priority
	 * @param string $suffix
	 * @return bool
	 */
	protected function filter_once( $hook, $arguments = 1, $priority = 10, $suffix = '' ): bool
	{
		if ( $method = Core\Text::sanitizeHook( self::und( $hook, $suffix ) ) )
			return add_filter( $hook, function () use ( $method ) {
				static $ran = FALSE;

				$params = func_get_args();

				if ( $ran )
					return $params[0];

				$ran = TRUE;

				return call_user_func_array( [ $this, $method ], $params );
			}, $priority, $arguments );

		return FALSE;
	}

	// USAGE: `$this->filter_true( 'disable_months_dropdown' );`
	protected function filter_true( string $hook, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first ) {
				return TRUE;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_false( 'disable_months_dropdown' );`
	protected function filter_false( string $hook, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first ) {
				return FALSE;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_true_module( 'meta', 'mainbox_callback' );`
	protected function filter_true_module( string $module, string $hook, int $priority = 10 ): true
	{
		return add_filter( $this->hook_base( $module, $hook ),
			static function ( $first ) {
				return TRUE;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_false_module( 'meta', 'mainbox_callback' );`
	protected function filter_false_module( string $module, string $hook, int $priority = 10 ): true
	{
		return add_filter( $this->hook_base( $module, $hook ),
			static function ( $first ) {
				return FALSE;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_zero( 'option_blog_public' );`
	protected function filter_zero( string $hook, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first ) {
				return 0;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_empty_string( 'option_blog_public' );`
	protected function filter_empty_string( string $hook, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first ) {
				return '';
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_empty_array( 'option_blog_public' );`
	protected function filter_empty_array( string $hook, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first ) {
				return [];
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_append( 'body_class', 'foo' );`
	protected function filter_append( string $hook, string|array $items, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first ) use ( $items ) {
				foreach ( (array) $items as $value )
					$first[] = $value;
				return $first;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_append_string( 'admin_body_class', [ 'foo', 'bar' ] );`
	protected function filter_append_string( string $hook, string|array $items, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first )
				use ( $items ) {

				foreach ( (array) $items as $value )
					$first = sprintf( '%s %s', trim( $first ?: '' ), trim( $value ?: '' ) );

				return $first;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_set( 'shortcode_atts_gallery', [ 'columns' => 4 ] );`
	protected function filter_set( string $hook, string|array $items, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first )
				use ( $items ) {

				foreach ( $items as $key => $value )
					$first[$key] = $value;

				return $first;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_if_not_set( 'widget_posts_args', [ 'post_type' => 'page' ] );`
	protected function filter_if_not_set( string $hook, string|array $items, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first )
				use ( $items ) {

				foreach ( $items as $key => $value )
					if ( ! isset( $first[$key] ) )
						$first[$key] = $value;

				return $first;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_if_2_set_1( 'reference_metakey', 'vin', 'vehicle' );`
	protected function filter_if_2_set_1( string $hook, mixed $override, mixed $target, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first, $second )
				use ( $override, $target ) {

				if ( $second === $target )
					return $override;

				return $first;
			}, $priority, 2 );
	}

	// USAGE: `$this->filter_unset( 'shortcode_atts_gallery', [ 'columns' ] );`
	protected function filter_unset( string $hook, string|array $items, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first )
				use ( $items ) {

				foreach ( (array) $items as $key )
					unset( $first[$key] );

				return $first;
			}, $priority, 1 );
	}

	// USAGE: `$this->filter_string( 'parent_file', 'options-general.php' );`
	protected function filter_string( string $hook, string $string, int $priority = 10 ): true
	{
		return add_filter( $hook,
			static function ( $first )
				use ( $string ) {

				return $string;
			}, $priority, 1 );
	}

	protected function actions(): int|bool
	{
		$args = func_get_args();

		if ( count( $args ) < 1 )
			return FALSE;

		$args[0] = $this->hook( $args[0] );

		call_user_func_array( 'do_action', $args );

		return has_action( $args[0] );
	}

	protected function filters(): mixed
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
	 * @param string $suffix
	 * @param false|callable $callback
	 * @return int|bool
	 */
	protected function hooked( string $hook, $suffix = '', false|callable $callback = FALSE ): int|bool
	{
		if ( $tag = $this->hook( $hook, $suffix ) )
			return has_filter( $tag, $callback );

		return FALSE;
	}

	public function _return_string_yes(): string { return 'yes'; }
	public function _return_string_no(): string  { return 'no'; }

	// USAGE: `add_filter( 'body_class', self::_array_append( 'foo' ) );`
	public static function _array_append( mixed $item )
	{
		return function ( $array )
			use ( $item ) {

			$array[] = $item;
			return $array;
		};
	}

	// USAGE: `add_filter( 'shortcode_atts_gallery', self::_array_set( 'columns', 4 ) );`
	public static function _array_set( string $key, mixed $value )
	{
		return function ( $array )
			use ( $key, $value ) {

			$array[$key] = $value;
			return $array;
		};
	}

	protected function nonce_create( ?string $context = NULL, ?string $key = NULL ): string
	{
		return wp_create_nonce( self::dsh(
			$this->base,
			$key ?? $this->key,
			$context ?? 'settings'
		) );
	}

	protected function nonce_verify( ?string $context = NULL, ?string $nonce = NULL, ?string $key = NULL ): int|false
	{
		$key     = $key     ?? $this->key;
		$context = $context ?? 'settings';
		$nonce   = $nonce   ?? self::req( '_'.self::dsh( $this->base, $key, $context ), NULL );  // OLD: `$_REQUEST['_wpnonce']`

		return wp_verify_nonce( $nonce, self::dsh( $this->base, $key, $context ) );
	}

	protected function nonce_field( ?string $context = NULL, ?string $key = NULL, ?string $name = NULL ): string
	{
		$key     = $key     ?? $this->key;
		$context = $context ?? 'settings';
		$name    = $name    ?? '_'.self::dsh( $this->base, $key, $context );  // OLD: `_wpnonce`

		return wp_nonce_field( self::dsh( $this->base, $key, $context ), $name, FALSE, TRUE );
	}

	protected function nonce_check( ?string $context = NULL, ?string $key = NULL, ?string $name = NULL ): int|false
	{
		$key     = $key     ?? $this->key;
		$context = $context ?? 'settings';
		$name    = $name    ?? '_'.self::dsh( $this->base, $key, $context );  // OLD: `_wpnonce`

		return check_admin_referer( self::dsh( $this->base, $key, $context ), $name );
	}

	protected function is_request_action( string $action, ?string $extra = NULL, mixed $default = FALSE ): mixed
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

	protected function remove_request_action( string|array $extra = [], ?string $url = NULL ): string
	{
		$url = $url ?? Core\URL::current();

		if ( is_array( $extra ) )
			$remove = $extra;
		else
			$remove[] = $extra;

		$remove[] = $this->hook_base( 'action' );

		return remove_query_arg( $remove, $url );
	}

	protected function get_sub_limit_option( ?string $sub = NULL, ?string $context = NULL, ?int $default = NULL, string $key = 'limit', string $option = 'per_page' ): int
	{
		$sub      = $sub     ?? $this->key;
		$context  = $context ?? 'tools';
		$per_page = (int) get_user_option( $this->hook_base( $sub, $context, $option ) );

		if ( empty( $per_page ) || $per_page < 1 )
			$per_page = $default ?? 25;

		return (int) self::req( $key, $per_page );
	}

	// NOTE: `add_screen_option()` only accept 2 options: `per_page` and `layout_columns`
	protected function add_sub_screen_option(
		?string $sub = NULL,
		?string $context = NULL,
		null|bool|string $option = TRUE,
		?int $default = NULL,
		?string $label = NULL,
	): void {

		if ( FALSE === $option )
			return;

		if ( TRUE === $option )
			$option = 'per_page';

		$sub     = $sub     ?? $this->key;
		$context = $context ?? 'tools';
		$option  = $option  ?? 'per_page';

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

	protected function _hook_ajax( ?bool $auth = TRUE, ?string $hook = NULL, string $method = 'do_ajax' ): bool
	{
		$hooked = FALSE;

		if ( is_null( $auth ) || TRUE === $auth )
			$hooked = add_action( self::und( 'wp', 'ajax', $hook ?? $this->hook() ), [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			$hooked = add_action( self::und( 'wp', 'ajax', 'nopriv', $hook ?? $this->hook() ), [ $this, $method ] );

		return $hooked;
	}

	// DEFAULT FILTER
	public function do_ajax(): void
	{
		wp_send_json_error();
	}

	protected function _hook_post( ?bool $auth = TRUE, ?string $hook = NULL, string $method = 'do_post' ): bool
	{
		$hooked = FALSE;

		if ( ! is_admin() )
			return $hooked;

		if ( is_null( $auth ) || TRUE === $auth )
			$hooked = add_action( self::und( 'admin', 'post', $hook ?? $this->hook() ), [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			$hooked = add_action( self::und( 'admin', 'post', 'nopriv', $hook ?? $this->hook() ), [ $this, $method ] );

		return $hooked;
	}

	// DEFAULT FILTER
	public function do_post(): void
	{
		wp_die();
	}

	// TODO: un-schedule on deactivation
	protected function _hook_event( string $name, ?string $recurrence = NULL, array $args = [] ): bool
	{
		$hook       = $this->hook( $name );
		$recurrence = $recurrence ?? 'monthly';

		if ( ! wp_next_scheduled( $hook ) )
			return wp_schedule_event( time(), $recurrence, $hook, $args, FALSE );

		return TRUE;
	}

	protected function hidden(
		string $value,
		array $data = [],
		string|array $class = '',
		string $id = '',
	): void	{

		echo Core\HTML::wrap( $value, Core\HTML::attrClass( 'hidden', $class ), TRUE, $data, $id );
	}

	protected function wrap(
		mixed $html,
		string|array $class = '',
		bool $block = TRUE,
		string $id = '',
		bool $hide = FALSE,
	): string {

		if ( empty( $html ) )
			return '';

		$html = (string) $html;

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

	protected function wrap_open(
		string|array $class = '',
		bool $block = TRUE,
		string $id = '',
		bool $hide = FALSE,
	): string {

		return $block
			? '<div class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>'

			: '<span class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>';
	}

	protected function wrap_open_buttons(
		string|array $class = '',
		bool $block = TRUE,
		string $id = '',
		bool $hide = FALSE,
	): string {

		return $block
			? '<p class="'.Core\HTML::prepClass( 'submit', $this->base.'-wrap', '-wrap-buttons', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>'

			: '<span class="'.Core\HTML::prepClass( 'submit', $this->base.'-wrap', '-wrap-buttons', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>';
	}

	protected function wrap_open_row(
		string $name = '',
		string|array $extra = '',
		string $id = '',
		bool $hide = FALSE,
		string $tag = 'li',
	): string {

		return '<'.Core\HTML::sanitizeTag( $tag ).' class="'.Core\HTML::prepClass(
			'-row',
			'-wrap-row',
			'-'.$this->key,
			$name ? ( '-'.$name ) : '',
			$extra
		).'"'.( $id ? ' id="'.$id.'"' : '' )
		.( $hide ? ' style="display:none"' : '' ).'>';
	}

	// `self::dump( ini_get( 'memory_limit' ) );`
	public function raise_memory_limit(
		int $count = 1,
		int $per = 60,
		?string $context = NULL,
	): int|string|false {

		$limit = $count ? ( 300 + ( $per * $count ) ) : 0;

		@set_time_limit( $limit );
		// @ini_set( 'max_execution_time', $limit ); // maybe `-1`
		// @ini_set( 'max_input_time', $limit ); // maybe `-1`

		return wp_raise_memory_limit( $context ?? $this->base );
	}
}

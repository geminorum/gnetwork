<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Plugin extends Core\Base
{
	public $base   = '';
	public $__dir  = '';
	public $__url  = '';
	public $__file = '';
	public $__ver  = '';

	public function __construct() {}

	public static function instance( $dir = NULL, $url = NULL, $file = NULL, $ver = NULL )
	{
		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new static();
			$instance->setup( $dir, $url, $file, $ver );
		}

		return $instance;
	}

	protected function setup( $dir, $url, $file, $ver )
	{
		$this->__dir  = $dir  ?? '';
		$this->__url  = $url  ?? '';
		$this->__file = $file ?? '';
		$this->__ver  = $ver  ?? '';

		$this->defines( $this->early_constants() );

		if ( ! $this->setup_check() ) return FALSE;

		$this->initialize();
		$this->actions();
		$this->setup_loaded();

		add_action( 'plugins_loaded', function () {
			$this->defines( $this->late_constants() );
			$this->textdomains();
		}, 19 ); // child's must run on `20`
	}

	protected function defines( $constants )
	{
		foreach ( $constants as $key => $val )
			defined( $key ) || define( $key, $val );
	}

	public function files( $stack, $base = NULL, $check = TRUE )
	{
		$base = $base ?? $this->__dir;

		foreach ( (array) $stack as $path )

			if ( ! $path )
				continue;

			if ( ! $check )
				require_once $base.'includes/'.$path.'.php';

			else if ( @is_readable( $base.'includes/'.$path.'.php' ) )
				require_once $base.'includes/'.$path.'.php';
	}

	protected function actions() {}
	protected function modules() { return [ [], '' ]; }
	protected function setup_check() { return TRUE; }
	protected function early_constants() { return []; }
	protected function late_constants() { return []; }

	protected function initialize()
	{
		list( $modules, $namespace ) = $this->modules();

		foreach ( $modules as $module ) {

			$class = $namespace.'\\'.$module;
			$slug  = strtolower( $module );

			try {

				$this->{$slug} = new $class( $this->base, $slug );

			} catch ( Core\Exception $e ) {

				// no need to do anything!

				do_action( 'qm/debug', $e );
			}
		}
	}

	protected function setup_loaded()
	{
		if ( $this->base )
			do_action( sprintf( '%s_loaded', $this->base ),
				$this->__dir,
				$this->__url,
				$this->__file,
				$this->__ver
			);
	}

	// NOTE: `custom path` once set by `load_plugin_textdomain()`
	// NOTE: assumes the plugin directory is the same as the `textdomain`
	protected function textdomains()
	{
		load_plugin_textdomain( $this->base, FALSE, sprintf( '%s/languages', $this->base ) );
	}
}

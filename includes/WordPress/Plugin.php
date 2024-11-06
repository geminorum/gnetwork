<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Plugin extends Core\Base
{

	public $base = '';

	public function __construct() {}

	public static function instance()
	{
		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new static();
			$instance->setup();
		}

		return $instance;
	}

	protected function setup()
	{
		$this->defines( $this->constants() );

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

		$this->actions();
		$this->loaded();
	}

	protected function defines( $constants )
	{
		foreach ( $constants as $key => $val )
			defined( $key ) || define( $key, $val );
	}

	protected function files( $stack, $base, $check = TRUE )
	{
		foreach ( (array) $stack as $path )

			if ( ! $check )
				require_once $base.'includes/'.$path.'.php';

			else if ( is_readable( $base.'includes/'.$path.'.php' ) )
				require_once $base.'includes/'.$path.'.php';
	}

	protected function actions() {}
	protected function modules() { return [ [], '' ]; }
	protected function constants() { return []; }
	protected function late_constants() { return []; }

	protected function loaded()
	{
		if ( $this->base )
			do_action( sprintf( '%s_loaded', $this->base ) );
	}
}

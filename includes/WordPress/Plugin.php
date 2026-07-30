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
	public $__hash = '';

	public function __construct() {}

	public static function instance(
		?string $dir  = NULL,
		?string $url  = NULL,
		?string $file = NULL,
		?string $ver  = NULL,
		?string $hash = NULL,
	): static {

		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new static();
			$instance->setup( $dir, $url, $file, $ver, $hash );
		}

		return $instance;
	}

	protected function setup(
		?string $dir  = NULL,
		?string $url  = NULL,
		?string $file = NULL,
		?string $ver  = NULL,
		?string $hash = NULL,
	): void {
		$this->__dir  = $dir  ?? '';
		$this->__url  = $url  ?? '';
		$this->__file = $file ?? '';
		$this->__ver  = $ver  ?? '';
		$this->__hash = $hash ?? '';

		$this->defines( $this->early_constants() );

		if ( ! $this->setup_check() ) return;

		$this->initialize();
		$this->actions();
		$this->setup_loaded();

		add_action( 'plugins_loaded', function () {
			$this->defines( $this->late_constants() );
			$this->textdomains();
		}, 19 ); // child's must run on `20`
	}

	protected function defines( array $constants ): void
	{
		foreach ( $constants as $key => $val )
			defined( $key ) || define( $key, $val );
	}

	public function files( string|array $stack, ?string $base = NULL, bool $check = TRUE ): void
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

	protected function actions(): void {}
	protected function modules(): array { return [ [], '' ]; }
	protected function setup_check(): bool { return TRUE; }
	protected function early_constants(): array { return []; }
	protected function late_constants(): array { return []; }

	protected function initialize(): void
	{
		list( $modules, $namespace ) = $this->modules();

		foreach ( $modules as $module ) {

			$class = $namespace.'\\'.$module;
			$slug  = strtolower( $module );

			try {

				$this->{$slug} = new $class( $this->base, $slug );

			} catch ( \Exception $e ) {

				// no need to do anything!

				do_action( 'qm/debug', $e );
			}
		}
	}

	protected function setup_loaded(): void
	{
		if ( $this->base )
			do_action( self::und( $this->base, 'loaded' ),
				$this->__dir,
				$this->__url,
				$this->__file,
				$this->__ver,
				$this->__hash
			);
	}

	// NOTE: `custom path` once set by `load_plugin_textdomain()`
	// NOTE: assumes the plugin directory is the same as the `textdomain`
	protected function textdomains(): void
	{
		load_plugin_textdomain( $this->base, FALSE, sprintf( '%s/languages', $this->base ) );
	}

	public function get_dir(): string  { return $this->__dir;  }
	public function get_url(): string  { return $this->__url;  }
	public function get_file(): string { return $this->__file; }
	public function get_ver(): string  { return $this->__ver;  }
	public function get_hash(): string { return $this->__hash; }
}

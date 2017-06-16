<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\Exception;
use geminorum\gNetwork\Core\HTML;

class Plugin
{

	public $base = 'gnetwork';

	public static function instance()
	{
		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new Plugin;
			$instance->setup();
		}

		return $instance;
	}

	public function __construct() {}

	private function setup()
	{
		$this->require_core();
		$this->require_plugin();

		$modules = $this->get_modules();
		$this->files( array_keys( $modules ) );

		foreach ( $modules as $module_slug => $module_class ) {

			$class = __NAMESPACE__.'\\Modules\\'.$module_class;
			$slug  = str_ireplace( 'modules/', '', $module_slug );

			if ( $module_class && class_exists( $class ) ) {

				try {
					$this->{$slug} = new $class( $this->base, $slug );

				} catch ( Exception $e ) {

					// echo 'Caught exception: ',  $e->getMessage(), "\n";
					// no need to do anything!
				}
			}
		}

		$this->require_after();

		load_plugin_textdomain( GNETWORK_TEXTDOMAIN, FALSE, 'gnetwork/languages' );

		add_action( 'bp_include', [ $this, 'bp_include' ] );
		add_filter( 'mce_external_languages',[ $this, 'mce_external_languages' ] );
	}

	private function files( $stack, $check = TRUE, $base = GNETWORK_DIR )
	{
		foreach ( (array) $stack as $path )

			if ( ! $check )
				require_once( $base.'includes/'.$path.'.php' );

			else if ( file_exists( $base.'includes/'.$path.'.php' ) )
				require_once( $base.'includes/'.$path.'.php' );
	}

	private function require_core()
	{
		$this->files( [
			'core/base',

			'core/arraay',
			'core/date',
			'core/error',
			'core/exception',
			'core/file',
			'core/html',
			'core/http',
			'core/l10n',
			'core/number',
			'core/orthography',
			'core/text',
			'core/uri',
			'core/url',
			'core/wordpress',
		] );
	}

	private function require_plugin()
	{
		$this->files( [
			'constants',
			'functions',
			'utilities',
			'settings',
			'logger',
			'module',
			'provider',
		] );
	}

	private function require_after()
	{
		$this->files( 'pluggable' );
	}

	private function get_modules()
	{
		$modules = [
			'modules/locale'      => 'Locale',
			'modules/network'     => 'Network',
			'modules/admin'       => 'Admin',
			'modules/site'        => 'Site',
			'modules/blog'        => 'Blog',
			'modules/user'        => 'User',
			'modules/api'         => 'API',
			'modules/adminbar'    => 'AdminBar',
			'modules/dashboard'   => 'Dashboard',
			'modules/authors'     => 'Authors',
			'modules/tracking'    => 'Tracking',
			'modules/maintenance' => 'Maintenance',
			'modules/restricted'  => 'Restricted',
			'modules/editor'      => 'Editor',
			'modules/captcha'     => 'Captcha',
			'modules/opensearch'  => 'OpenSearch',
			'modules/mail'        => 'Mail',
			'modules/sms'         => 'SMS',
			'modules/navigation'  => 'Navigation',
			'modules/themes'      => 'Themes',
			'modules/media'       => 'Media',
			'modules/embed'       => 'Embed',
			'modules/cron'        => 'Cron',
			'modules/login'       => 'Login',
			'modules/lockdown'    => 'Lockdown',
			'modules/blacklist'   => 'BlackList',
			'modules/update'      => 'Update',
			'modules/search'      => 'Search',
			'modules/taxonomy'    => 'Taxonomy',
			'modules/shortcodes'  => 'ShortCodes',
			'modules/comments'    => 'Comments',
			'modules/widgets'     => 'Widgets',
			'modules/bbpress'     => 'bbPress',
			'modules/notify'      => 'Notify',
			'modules/typography'  => 'Typography',
			'modules/debug'       => 'Debug',
			'modules/code'        => 'Code',
			'modules/cleanup'     => 'Cleanup',
			'modules/branding'    => 'Branding',
			'modules/api'         => 'API',
		];

		if ( defined( 'WP_STAGE' ) )
			if ( 'production' == WP_STAGE )
				$modules['modules/bbq'] = 'BBQ';
			else if ( 'development' == WP_STAGE )
				$modules['modules/dev'] = 'Dev';

		return $modules;
	}

	public function bp_include()
	{
		if ( file_exists( GNETWORK_DIR.'includes/modules/buddypress.php' ) ) {
			require_once( GNETWORK_DIR.'includes/modules/buddypress.php' );
			try {
				$this->buddypress = new Modules\BuddyPress( $this->base, 'buddypress' );
			} catch ( Exception $e ) {
				// echo 'Caught exception: ',  $e->getMessage(), "\n";
				// no need to do anything!
			}
		}

		if ( file_exists( GNETWORK_DIR.'includes/misc/buddypress-me.php' ) ) {
			require_once( GNETWORK_DIR.'includes/misc/buddypress-me.php' );
			buddypress()->me = new Misc\BP_Me_Component();
		}
	}

	public function mce_external_languages( $languages )
	{
		if ( file_exists( GNETWORK_DIR.'includes/misc/editor-languages.php' ) )
			$languages['gnetwork'] = GNETWORK_DIR.'includes/misc/editor-languages.php';

		return $languages;
	}

	public function module( $module, $object = FALSE )
	{
		if ( isset( $this->{$module} ) )
			return $object ? $this->{$module} : TRUE;

		return FALSE;
	}

	public function option( $key, $module = 'network', $default = FALSE )
	{
		if ( isset( $this->{$module}->options[$key] ) )
			return $this->{$module}->options[$key];

		return $default;
	}

	public function providers( $type = 'sms', $pre = [] )
	{
		if ( isset( $this->{$type} ) && $this->{$type}->options['load_providers'] )
			foreach ( $this->{$type}->providers as $name => &$provider )
				$pre[$name] = $provider->providerName();

		return $pre;
	}

	public function email()
	{
		if ( isset( $this->email ) )
			return $this->email->get_from_email();

		return FALSE;
	}

	public function na( $wrap = 'code' )
	{
		$na = __( 'N/A', GNETWORK_TEXTDOMAIN );
		return $wrap
			? HTML::tag( $wrap, [ 'title' => __( 'Not Available', GNETWORK_TEXTDOMAIN ) ], $na )
			: $na;
	}
}

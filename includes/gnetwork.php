<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class gNetwork
{
	public $base = 'gnetwork';

	public static function instance()
	{
		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new gNetwork;
			$instance->setup();
		}

		return $instance;
	}

	public function __construct() {}

	private function setup()
	{
		$modules = $this->get_modules();

		foreach ( $modules as $module_slug => $module_class )
			if ( file_exists( GNETWORK_DIR.'includes/'.$module_slug.'.php' ) )
				require_once( GNETWORK_DIR.'includes/'.$module_slug.'.php' );

		foreach ( $modules as $module_slug => $module_class ) {

			$class = __NAMESPACE__.'\\'.$module_class;

			if ( $module_class && class_exists( $class ) ) {

				try {
					$this->{$module_slug} = new $class( $this->base, $module_slug );

				} catch ( Exception $e ) {

					// echo 'Caught exception: ',  $e->getMessage(), "\n";
					// no need to do anything!
				}
			}
		}

		load_plugin_textdomain( GNETWORK_TEXTDOMAIN, FALSE, 'gnetwork/languages' );

		add_action( 'bp_include', array( $this, 'bp_include' ) );
		add_filter( 'mce_external_languages',array( $this, 'mce_external_languages' ) );
	}

	private function get_modules()
	{
		$modules = array(
			'error'        => '',
			'exception'    => '',
			'basecore'     => '',
			'constants'    => '',
			'functions'    => '',
			'utilities'    => '',
			'modulecore'   => '',
			'providercore' => '',

			'locale'      => 'Locale',
			'network'     => 'Network',
			'admin'       => 'Admin',
			'site'        => 'Site',
			'blog'        => 'Blog',
			'adminbar'    => 'AdminBar',
			'dashboard'   => 'Dashboard',
			'users'       => 'Users',
			'tracking'    => 'Tracking',
			'maintenance' => 'Maintenance',
			'restricted'  => 'Restricted',
			'editor'      => 'Editor',
			'captcha'     => 'Captcha',
			'opensearch'  => 'OpenSearch',
			'mail'        => 'Mail',
			'sms'         => 'SMS',
			'navigation'  => 'Navigation',
			'themes'      => 'Themes',
			'media'       => 'Media',
			'cron'        => 'Cron',
			'login'       => 'Login',
			'lockdown'    => 'LockDown',
			'blacklist'   => 'BlackList',
			'update'      => 'Update',
			'search'      => 'Search',
			'taxonomy'    => 'Taxonomy',
			'shortcodes'  => 'ShortCodes',
			'comments'    => 'Comments',
			'widgets'     => 'Widgets',
			'bbpress'     => 'bbPress',
			'notify'      => 'Notify',
			'reference'   => 'Reference',
			'typography'  => 'Typography',
			'debug'       => 'Debug',
			'code'        => 'Code',
			'cleanup'     => 'Cleanup',

			'pluggable' => '',
		);

		if ( defined( 'WP_STAGE' ) )
			if ( 'production' == WP_STAGE )
				$modules['bbq'] = 'BBQ';
			else if ( 'development' == WP_STAGE )
				$modules['dev'] = 'Dev';

		return $modules;
	}

	public function bp_include()
	{
		if ( file_exists( GNETWORK_DIR.'includes/buddypress.php' ) ) {
			require_once( GNETWORK_DIR.'includes/buddypress.php' );
			try {
				$this->buddypress = new BuddyPress( $this->base, 'buddypress' );
			} catch ( Exception $e ) {
				// echo 'Caught exception: ',  $e->getMessage(), "\n";
				// no need to do anything!
			}
		}

		if ( file_exists( GNETWORK_DIR.'includes/misc/buddypress-me.php' ) ) {
			require_once( GNETWORK_DIR.'includes/misc/buddypress-me.php' );
			buddypress()->me = new BP_Me_Component();
		}
	}

	public function mce_external_languages( $languages )
	{
		if ( file_exists( GNETWORK_DIR.'includes/misc/editor-languages.php' ) )
			$languages['gnetwork'] = GNETWORK_DIR.'includes/misc/editor-languages.php';

		return $languages;
	}

	public function option( $key, $module = 'network', $default = FALSE )
	{
		if ( isset( $this->{$module}->options[$key] ) )
			return $this->{$module}->options[$key];

		return $default;
	}

	public function providers( $type = 'sms', $pre = array() )
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
}

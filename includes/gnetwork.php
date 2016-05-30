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
			$slug  = str_ireplace( array( 'modules/', 'misc/' ), '', $module_slug );

			if ( $module_class && class_exists( $class ) ) {

				try {
					$this->{$slug} = new $class( $this->base, $slug );

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
			'core/base'      => '',
			'core/error'     => '',
			'core/exception' => '',
			'core/html'      => '',
			'core/http'      => '',
			'core/file'      => '',
			'core/arraay'    => '',
			'core/text'      => '',
			'core/wordpress' => '',

			'constants'    => '',
			'functions'    => '',
			'utilities'    => '',
			'settings'     => '',
			'modulecore'   => '',
			'providercore' => '',

			'modules/locale'      => 'Locale',
			'modules/network'     => 'Network',
			'modules/admin'       => 'Admin',
			'modules/site'        => 'Site',
			'modules/blog'        => 'Blog',
			'modules/api'         => 'API',
			'modules/adminbar'    => 'AdminBar',
			'modules/dashboard'   => 'Dashboard',
			'modules/users'       => 'Users',
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
			'modules/cron'        => 'Cron',
			'modules/login'       => 'Login',
			'modules/lockdown'    => 'LockDown',
			'modules/blacklist'   => 'BlackList',
			'modules/update'      => 'Update',
			'modules/search'      => 'Search',
			'modules/taxonomy'    => 'Taxonomy',
			'modules/shortcodes'  => 'ShortCodes',
			'modules/comments'    => 'Comments',
			'modules/widgets'     => 'Widgets',
			'modules/bbpress'     => 'bbPress',
			'modules/notify'      => 'Notify',
			'modules/reference'   => 'Reference',
			'modules/typography'  => 'Typography',
			'modules/debug'       => 'Debug',
			'modules/code'        => 'Code',
			'modules/cleanup'     => 'Cleanup',

			'pluggable' => '',
		);

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

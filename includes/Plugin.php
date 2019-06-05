<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

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
		foreach ( $this->constants() as $key => $val )
			defined( $key ) || define( $key, $val );

		foreach ( $this->get_modules() as $module ) {

			$class = __NAMESPACE__.'\\Modules\\'.$module;
			$slug  = strtolower( $module );

			if ( $module && class_exists( $class ) ) {

				try {

					$this->{$slug} = new $class( $this->base, $slug );

				} catch ( Exception $e ) {

					// no need to do anything!
				}
			}
		}

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 20 );
		add_action( 'bp_setup_components', [ $this, 'bp_setup_components' ] );
		add_action( 'bp_include', [ $this, 'bp_include' ] );
		add_action( 'bbp_includes', [ $this, 'bbp_includes' ] );
	}

	private function files( $stack, $check = TRUE, $base = GNETWORK_DIR )
	{
		foreach ( (array) $stack as $path )

			if ( ! $check )
				require_once( $base.'includes/'.$path.'.php' );

			else if ( is_readable( $base.'includes/'.$path.'.php' ) )
				require_once( $base.'includes/'.$path.'.php' );
	}

	private function constants()
	{
		return [
			'GNETWORK_TEXTDOMAIN'   => $this->base,
			'GNETWORK_MAIN_NETWORK' => get_main_network_id(), // set FALSE to disable multi-network enhancements

			// 'GNETWORK_WPLANG'       => 'en_US', // define if necessary
			// 'GNETWORK_WPLANG_ADMIN' => FALSE, // define if necessary

			// 'GNETWORK_TRACKING_GA_ACCOUNT'      => 'UA-XXXXXXXX-X', // define if necessary
			// 'GNETWORK_BLACKLIST_REMOTE_CONTENT' => 'https://gist.githubusercontent.com/{user}/{gist_id}/raw', // define if necessary

			'GNETWORK_SEARCH_REDIRECT'       => FALSE, // set TRUE to redirect all searches to the network search url
			'GNETWORK_SEARCH_URL'            => esc_url( home_url( '/' ) ),
			'GNETWORK_SEARCH_QUERYID'        => 's',
			'GNETWORK_BP_EXCLUDEUSERS'       => FALSE, // comma separated ids of users whom you want to exclude
			'GNETWORK_ADMINBAR'              => TRUE, // disable admin bar for non caps, like: 'edit_others_posts'
			'GNETWORK_ADMINBAR_LOGIN'        => TRUE, // disable admin bar login/register nodes
			'GNETWORK_ADMIN_JS_ENHANCEMENTS' => TRUE, // autoresize textarea and more...
			'GNETWORK_CACHE_TTL'             => 60 * 60 * 12, // 12 hours
			'GNETWORK_REDIRECT_404_URL'      => home_url( '/not-found' ),

			'GNETWORK_NETWORK_NAVIGATION'    => 'network_navigation', // menu on the main site for network navigation
			'GNETWORK_NETWORK_ADMINBAR'      => 'network_adminbar', // menu on the main site for adminbar navigation
			'GNETWORK_NETWORK_USERMENU'      => 'network_usermenu', // menu on the main site for user navigation
			'GNETWORK_NETWORK_EXTRAMENU'     => 'network_extramenu', // menu on the main site for extra navigation
			'GNETWORK_NETWORK_EXTRAMENU_CAP' => 'read', // extra_menu capability

			'GNETWORK_LARGE_NETWORK_IS' => 500, // set to large network value. default wp is 10000 / FALSE to disable the filter
			'GNETWORK_SITE_USER_ID'     => FALSE, // set to default site user id / FALSE to disable // DEPRECATED
			'GNETWORK_SITE_USER_ROLE'   => 'editor', // default role for site user in new blog // DEPRECATED
			'GNETWORK_BODY_CLASS'       => FALSE, // network html body class / FALSE to disable

			'GNETWORK_DISABLE_SSL'       => FALSE,
			'GNETWORK_DISABLE_BBQ'       => FALSE,
			'GNETWORK_DISABLE_RECAPTCHA' => FALSE,
			'GNETWORK_DISABLE_CREDITS'   => FALSE,

			'GNETWORK_BETA_FEATURES' => FALSE,

			'GNETWORK_DEBUG_LOG'    => WP_DEBUG_LOG && TRUE !== WP_DEBUG_LOG ? WP_DEBUG_LOG : WP_CONTENT_DIR.'/debug.log', // FALSE to disable / @REF: https://core.trac.wordpress.org/ticket/18391
			'GNETWORK_ANALOG_LOG'   => WP_CONTENT_DIR.'/analog.log', // FALSE to disable
			'GNETWORK_FAILED_LOG'   => WP_CONTENT_DIR.'/failed.log', // FALSE to disable
			'GNETWORK_MAIL_LOG_DIR' => WP_CONTENT_DIR.'/emaillogs', // FALSE to disable
			'GNETWORK_SMS_LOG_DIR'  => WP_CONTENT_DIR.'/smslogs', // FALSE to disable

			'GNETWORK_DL_REMOTE' => FALSE,
			'GNETWORK_DL_DIR'    => ABSPATH.'repo',
			'GNETWORK_DL_URL'    => network_home_url( 'repo' ),

			// 'GNETWORK_DISABLE_LOCALE_OVERRIDES'  => TRUE,  // cannot set this early!
			// 'GNETWORK_DISABLE_CONTENT_ACTIONS'   => TRUE,  // cannot set this early!
			// 'GNETWORK_DISABLE_JQUERY_MIGRATE'    => TRUE,  // cannot set this early!
			// 'GNETWORK_DISABLE_FRONT_STYLES'      => FALSE, // cannot set this early!
			// 'GNETWORK_DISABLE_BUDDYPRESS_STYLES' => FALSE, // cannot set this early!
			// 'GNETWORK_DISABLE_BBPRESS_STYLES'    => FALSE, // cannot set this early!
			// 'GNETWORK_DISABLE_REFLIST_JS'        => FALSE, // do not include reflist shortcode js // cannot set this early!
			// 'GNETWORK_DISABLE_REFLIST_INSERT'    => FALSE, // do not include reflist shortcode after content  // cannot set this early!

			'GNETWORK_AJAX_ENDPOINT' => admin_url( 'admin-ajax.php' ), // if using .htaccess to rewrite

			'GNETWORK_MEDIA_THUMBS_SEPARATION' => FALSE, // if you want to seperate generated files from originals!!
			'GNETWORK_MEDIA_THUMBS_DIR'        => WP_CONTENT_DIR.'/thumbs',
			'GNETWORK_MEDIA_THUMBS_URL'        => WP_CONTENT_URL.'/thumbs',
			'GNETWORK_MEDIA_THUMBS_CHECK'      => TRUE, // check default wp dir before thumbs / make it disable for newly created sites
			'GNETWORK_MEDIA_OBJECT_SIZES'      => FALSE, // disable all image sizes and enable for each posttypes

			// reset some stuff
			'WP_STAGE'       => 'production',
			// 'NOBLOGREDIRECT' => '%siteurl%', // @SEE: https://core.trac.wordpress.org/ticket/21573
			'SAVEQUERIES'    => FALSE,
			'ERRORLOGFILE'   => WP_CONTENT_DIR.'/dberror.log',

			'FS_CHMOD_DIR'  => ( 0755 & ~ umask() ),
			'FS_CHMOD_FILE' => ( 0644 & ~ umask() ),
		];
	}

	private function constants_late()
	{
		return [
			'GNETWORK_BASE' => network_home_url( '/', $this->ssl() ? 'https' : 'http' ), // comes handy on multi-network
			'GNETWORK_NAME' => is_multisite() ? get_network_option( NULL, 'site_name' ) : get_option( 'blogname' ), // comes handy on multi-network
			'GNETWORK_LOGO' => 'login.png', // default logo image file, must relative to wp-content
		];
	}

	private function get_modules()
	{
		$modules = [
			'Modules/Locale'      => 'Locale',
			'Modules/Network'     => 'Network',
			'Modules/Admin'       => 'Admin',
			'Modules/Site'        => 'Site',
			'Modules/Blog'        => 'Blog',
			'Modules/User'        => 'User',
			// 'Modules/API'         => 'API',
			'Modules/AdminBar'    => 'AdminBar',
			'Modules/Dashboard'   => 'Dashboard',
			'Modules/Authors'     => 'Authors',
			'Modules/Tracking'    => 'Tracking',
			'Modules/Maintenance' => 'Maintenance',
			'Modules/Restricted'  => 'Restricted',
			'Modules/Editor'      => 'Editor',
			'Modules/Captcha'     => 'Captcha',
			'Modules/OpenSearch'  => 'OpenSearch',
			// 'Modules/Support'     => 'Support',
			'Modules/Mail'        => 'Mail',
			'Modules/SMS'         => 'SMS',
			// 'Modules/Bot'         => 'Bot',
			// 'Modules/Remote'      => 'Remote',
			'Modules/Navigation'  => 'Navigation',
			'Modules/Themes'      => 'Themes',
			'Modules/Extend'      => 'Extend',
			// 'Modules/DB'          => 'DB',
			'Modules/Media'       => 'Media',
			'Modules/Embed'       => 'Embed',
			'Modules/Cron'        => 'Cron',
			'Modules/Login'       => 'Login',
			'Modules/Lockdown'    => 'Lockdown',
			'Modules/BlackList'   => 'BlackList',
			'Modules/Update'      => 'Update',
			'Modules/Search'      => 'Search',
			'Modules/Taxonomy'    => 'Taxonomy',
			'Modules/ShortCodes'  => 'ShortCodes',
			'Modules/Comments'    => 'Comments',
			'Modules/Widgets'     => 'Widgets',
			'Modules/Notify'      => 'Notify',
			'Modules/Typography'  => 'Typography',
			'Modules/Debug'       => 'Debug',
			'Modules/Code'        => 'Code',
			'Modules/Cleanup'     => 'Cleanup',
			// 'Modules/Social'      => 'Social',
			'Modules/Branding'    => 'Branding',
			'Modules/API'         => 'API',
			'Modules/Uptime'      => 'Uptime',
			'Modules/GlotPress'   => 'GlotPress',
			'Modules/Profile'     => 'Profile',
			// 'Modules/Roles'       => 'Roles',
			// 'Modules/Rewrite'     => 'Rewrite',
		];

		if ( 'production' == WP_STAGE )
			$modules['Modules/BBQ'] = 'BBQ';

		else if ( 'development' == WP_STAGE )
			$modules['Modules/Dev'] = 'Dev';

		return $modules;
	}

	public function plugins_loaded()
	{
		foreach ( $this->constants_late() as $key => $val )
			defined( $key ) || define( $key, $val );

		$this->files( [ 'Pluggable', 'Functions' ] );

		load_plugin_textdomain( GNETWORK_TEXTDOMAIN, FALSE, 'gnetwork/languages' );

		add_filter( 'mce_external_languages',[ $this, 'mce_external_languages' ] );
	}

	public function bp_include()
	{
		if ( is_readable( GNETWORK_DIR.'includes/Modules/BuddyPress.php' ) ) {

			try {

				$this->buddypress = new Modules\BuddyPress( $this->base, 'buddypress' );

			} catch ( Exception $e ) {

				// echo 'Caught exception: ',  $e->getMessage(), "\n";
				// no need to do anything!
			}
		}
	}

	public function bbp_includes()
	{
		if ( is_readable( GNETWORK_DIR.'includes/Modules/bbPress.php' ) ) {

			try {

				$this->bbpress = new Modules\bbPress( $this->base, 'bbpress' );

			} catch ( Exception $e ) {

				// echo 'Caught exception: ',  $e->getMessage(), "\n";
				// no need to do anything!
			}
		}
	}

	public function bp_setup_components()
	{
		if ( is_readable( GNETWORK_DIR.'includes/Misc/BuddyPressMe.php' ) ) {

			buddypress()->me = new Misc\BuddyPressMe();
		}
	}

	public function mce_external_languages( $languages )
	{
		if ( is_readable( GNETWORK_DIR.'includes/Misc/TinyMceStrings.php' ) )
			$languages['gnetwork'] = GNETWORK_DIR.'includes/Misc/TinyMceStrings.php';

		return $languages;
	}

	public function module( $module, $object = FALSE )
	{
		if ( ! isset( $this->{$module} ) )
			return FALSE;

		if ( ! $object )
			return TRUE;

		return $this->{$module};
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

	public function ssl()
	{
		if ( GNETWORK_DISABLE_SSL )
			return FALSE;

		if ( $this->option( 'ssl_support', 'blog' ) )
			return TRUE;

		if ( $this->option( 'ssl_support', 'site' ) )
			return TRUE;

		return FALSE;
	}

	public function email( $fallback = FALSE )
	{
		if ( isset( $this->email ) )
			return $this->email->get_from_email( $fallback );

		return $fallback;
	}

	// @OLD: `WordPress::getSiteUserID()`
	public function user( $fallback = FALSE )
	{
		if ( $user_id = $this->option( 'site_user_id', 'user' ) )
			return intval( $user_id );

		if ( defined( 'GNETWORK_SITE_USER_ID' ) && GNETWORK_SITE_USER_ID )
			return intval( GNETWORK_SITE_USER_ID );

		if ( function_exists( 'gtheme_get_option' )
			&& ( $user_id = gtheme_get_option( 'default_user', 0 ) ) )
				return intval( $user_id );

		if ( $fallback )
			return intval( get_current_user_id() );

		return 0;
	}

	public function na( $wrap = 'code' )
	{
		return $wrap
			? HTML::tag( $wrap, [ 'title' => __( 'Not Available', GNETWORK_TEXTDOMAIN ) ], __( 'N/A', GNETWORK_TEXTDOMAIN ) )
			: __( 'N/A', GNETWORK_TEXTDOMAIN );
	}
}

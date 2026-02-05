<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

#[\AllowDynamicProperties]
class Plugin extends WordPress\Plugin
{
	public $base = 'gnetwork';

	protected function early_constants()
	{
		return [
			// 'GNETWORK_TEXTDOMAIN'   => $this->base,
			'GNETWORK_MAIN_NETWORK' => get_main_network_id(), // set FALSE to disable multi-network enhancements

			// 'GNETWORK_WPLANG'       => 'en_US', // define if necessary
			// 'GNETWORK_WPLANG_ADMIN' => FALSE, // define if necessary

			// 'GNETWORK_TRACKING_GA_ACCOUNT'      => 'UA-XXXXXXXX-X', // define if necessary
			// 'GNETWORK_BLACKLIST_REMOTE_CONTENT' => 'https://gist.githubusercontent.com/{user}/{gist_id}/raw', // define if necessary

			'GNETWORK_SEARCH_REDIRECT'       => FALSE, // set TRUE to redirect all searches to the network search url
			'GNETWORK_SEARCH_URL'            => home_url( '/' ),
			'GNETWORK_SEARCH_QUERYID'        => 's',
			'GNETWORK_BP_EXCLUDEUSERS'       => FALSE, // comma separated ids of users whom you want to exclude
			'GNETWORK_ADMINBAR'              => TRUE, // disable admin bar for non caps, like: 'edit_others_posts'
			'GNETWORK_ADMINBAR_LOGIN'        => TRUE, // disable admin bar login/register nodes
			'GNETWORK_ADMIN_JS_ENHANCEMENTS' => TRUE, // autoresize textarea and more...
			'GNETWORK_REDIRECT_404_URL'      => home_url( '/not-found' ),

			'GNETWORK_CACHE_DIR' => sprintf( '%s/cache', WP_CONTENT_DIR ),   // FALSE to disable
			'GNETWORK_CACHE_URL' => sprintf( '%s/cache', WP_CONTENT_URL ),
			'GNETWORK_CACHE_TTL' => 60 * 60 * 12,                            // 12 hours

			'GNETWORK_NETWORK_NAVIGATION'    => 'network_navigation',   // The menu on the main site for network navigation
			'GNETWORK_NETWORK_ADMINBAR'      => 'network_adminbar',     // The menu on the main site for admin-bar navigation
			'GNETWORK_NETWORK_USERMENU'      => 'network_usermenu',     // The menu on the main site for user navigation
			'GNETWORK_NETWORK_EXTRAMENU'     => 'network_extramenu',    // The menu on the main site for extra navigation
			'GNETWORK_NETWORK_EXTRAMENU_CAP' => 'read',                 // `extra_menu` capability

			'GNETWORK_LARGE_NETWORK_IS' => 500,        // Sets the large network value. Default for WordPress is `10000` / `FALSE` to disable the filter
			'GNETWORK_SITE_USER_ID'     => FALSE,      // Sets the default site user id / `FALSE` to disable // NOTE: DEPRECATED
			'GNETWORK_SITE_USER_ROLE'   => 'editor',   // The default role for site user in new blog // NOTE: DEPRECATED
			'GNETWORK_BODY_CLASS'       => FALSE,      // The network HTML body class / `FALSE` to disable

			'GNETWORK_DISABLE_SSL'             => FALSE,
			'GNETWORK_DISABLE_BBQ'             => FALSE,
			'GNETWORK_DISABLE_RECAPTCHA'       => FALSE,
			'GNETWORK_DISABLE_USERNAME_CHECKS' => FALSE,
			'GNETWORK_DISABLE_PHP_SESSIONS'    => FALSE,

			'GNETWORK_BETA_FEATURES' => FALSE,

			// @REF: https://core.trac.wordpress.org/ticket/18391
			'GNETWORK_DEBUG_LOG'    => WP_DEBUG_LOG && TRUE !== WP_DEBUG_LOG ? WP_DEBUG_LOG : WP_CONTENT_DIR.'/debug.log', // `FALSE` to disable
			'GNETWORK_ANALOG_LOG'   => WP_CONTENT_DIR.'/analog.log',     // `FALSE` to disable
			'GNETWORK_FAILED_LOG'   => WP_CONTENT_DIR.'/failed.log',     // `FALSE` to disable
			'GNETWORK_SEARCH_LOG'   => WP_CONTENT_DIR.'/search.log',     // `FALSE` to disable
			'GNETWORK_NOTFOUND_LOG' => WP_CONTENT_DIR.'/notfound.log',   // `FALSE` to disable
			'GNETWORK_MAIL_LOG_DIR' => WP_CONTENT_DIR.'/emaillogs',      // `FALSE` to disable
			'GNETWORK_SMS_LOG_DIR'  => WP_CONTENT_DIR.'/smslogs',        // `FALSE` to disable

			'GNETWORK_DL_REMOTE' => FALSE,
			'GNETWORK_DL_DIR'    => ABSPATH.'repo',
			'GNETWORK_DL_URL'    => network_home_url( 'repo' ),

			// 'GNETWORK_DISABLE_LINKIFY_CONTENT'  => TRUE,   // NOTE: cannot set this early!
			// 'GNETWORK_DISABLE_LOCALE_OVERRIDES' => TRUE,   // NOTE: cannot set this early!
			// 'GNETWORK_DISABLE_CONTENT_ACTIONS'  => TRUE,   // NOTE: cannot set this early!
			// 'GNETWORK_DISABLE_JQUERY_MIGRATE'   => TRUE,   // NOTE: cannot set this early!

			// 'GNETWORK_DISABLE_FRONT_STYLES'      => FALSE,   // NOTE: cannot set this early!
			// 'GNETWORK_DISABLE_BLOCK_STYLES'      => FALSE,   // NOTE: cannot set this early!
			// 'GNETWORK_DISABLE_BUDDYPRESS_STYLES' => FALSE,   // NOTE: cannot set this early!
			// 'GNETWORK_DISABLE_BBPRESS_STYLES'    => FALSE,   // NOTE: cannot set this early!

			// 'GNETWORK_DISABLE_REFLIST_JS'     => FALSE,   // do not include `reflist` short-code JS // NOTE: cannot set this early!
			// 'GNETWORK_DISABLE_REFLIST_INSERT' => FALSE,   // do not include `reflist` short-code after content // NOTE: cannot set this early!

			'GNETWORK_MOBILE_EMAIL_TEMPLATE' => '%s@smsdomain.xyz', // FIXME: use main site domain

			'GNETWORK_AJAX_ENDPOINT' => admin_url( 'admin-ajax.php' ), // if using `.htaccess` to rewrite

			'GNETWORK_MEDIA_THUMBS_SEPARATION' => FALSE,                      // If you want to separate generated files from originals!
			'GNETWORK_MEDIA_THUMBS_DIR'        => WP_CONTENT_DIR.'/thumbs',
			'GNETWORK_MEDIA_THUMBS_URL'        => WP_CONTENT_URL.'/thumbs',
			'GNETWORK_MEDIA_THUMBS_CHECK'      => TRUE,                       // Checks default WordPress directory before thumbs / make it disable for newly created sites
			'GNETWORK_MEDIA_OBJECT_SIZES'      => FALSE,                      // Disables all image sizes and enable for each post-type.

			'GNETWORK_USER_IDENTITY_NUMBER'    => 'identity_number', // FALSE to disable
			'GNETWORK_COMMERCE_MOBILE_METAKEY' => 'mobile',

			// reset some stuff
			'WP_STAGE'       => 'production',
			// 'NOBLOGREDIRECT' => '%siteurl%', // @SEE: https://core.trac.wordpress.org/ticket/21573
			'SAVEQUERIES'    => FALSE,
			'ERRORLOGFILE'   => WP_CONTENT_DIR.'/dberror.log',

			'FS_CHMOD_DIR'  => ( 0755 & ~ umask() ),
			'FS_CHMOD_FILE' => ( 0644 & ~ umask() ),
		];
	}

	protected function late_constants()
	{
		return [
			'GNETWORK_BASE' => network_home_url( '/', $this->ssl() ? 'https' : 'http' ), // comes handy on multi-network
			'GNETWORK_NAME' => is_multisite() ? get_network_option( NULL, 'site_name' ) : get_option( 'blogname' ), // comes handy on multi-network
			'GNETWORK_LOGO' => 'login.png', // default logo image file, must relative to wp-content

			'GNETWORK_DISABLE_CREDITS'       => FALSE,
			'GNETWORK_DISABLE_ALL_HELP_TABS' => FALSE,
			'GNETWORK_DISABLE_PRIVACY_TOOLS' => TRUE,

			'GCORE_DEFAULT_COUNTRY_CODE'    => gNetwork()->option( 'base_country', 'site' ) ?: 'IR',
			'GCORE_DEFAULT_PROVINCE_CODE'   => gNetwork()->option( 'base_province', 'site' ) ?: 'TEH',
			// 'GCORE_DEFAULT_PROVINCE_LATLNG' => gNetwork()->option( 'base_province_latlng', 'site' ) ?: '35.688889,51.389722',
			'GCORE_DEFAULT_COUNTRY_PHONE'   => gNetwork()->option( 'base_country_phone', 'site' ) ?: '98',
			'GCORE_DEFAULT_PROVINCE_PHONE'  => gNetwork()->option( 'base_province_phone', 'site' ) ?: '21',
		];
	}

	protected function modules()
	{
		$modules = [
			'Locale',
			'Network',
			'Admin',
			'Site',
			'Blog',
			'User',
			'Rest',
			// 'API',
			'Optimize',
			'AdminBar',
			'NotFound',
			'Dashboard',
			'Authors',
			'Tracking',
			'Maintenance',
			'Restricted',
			'Editor',
			'Feed',
			'Captcha',
			'OpenSearch',
			'Support',
			'Mail',
			'SMS',
			// 'Bot',
			// 'Remote',
			'Navigation',
			'Themes',
			'Extend',
			// 'DB',
			'Media',
			'Mimes',
			'Images',
			'Player',
			'Embed',
			'Cron',
			'Login',
			'Lockdown',
			'Blacklist',
			'Update',
			'Search',
			'Taxonomy',
			'ShortCodes',
			'Comments',
			'Widgets',
			'Notify',
			'Typography',
			'Debug',
			'Code',
			'Cleanup',
			// 'Social',
			'Branding',
			'Legal',
			'Uptime',
			'Commerce',
			'GlotPress',
			'Profile',
			// 'Roles',
			'Rewrite',
		];

		if ( 'production' == WP_STAGE )
			$modules[] = 'BBQ';

		else if ( 'development' == WP_STAGE )
			$modules[] = 'Dev';

		return [ $modules, __NAMESPACE__.'\\Modules' ];
	}

	protected function actions()
	{
		add_filter( 'wp_default_autoload_value', [ $this, 'wp_default_autoload_value' ], 20, 4 );
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 20 );
		add_action( 'bp_setup_components', [ $this, 'bp_setup_components' ] );
		add_action( 'bp_include', [ $this, 'bp_include' ] );
		add_action( 'bbp_includes', [ $this, 'bbp_includes' ] );

		Logger::setup();
	}

	public function wp_default_autoload_value( $autoload, $option, $value, $serialized_value )
	{
		return in_array( $option, [ $this->base.'_blog', $this->base.'_site', TRUE ] ) ? TRUE : $autoload;
	}

	public function plugins_loaded()
	{
		$this->files( [ 'Pluggable', 'Functions' ] );
		add_filter( 'mce_external_languages',[ $this, 'mce_external_languages' ] );
	}

	// NOTE: `custom path` once set by `load_plugin_textdomain()`
	// NOTE: assumes the plugin directory is the same as the `textdomain`
	protected function textdomains()
	{
		parent::textdomains();

		if ( ! is_admin() )
			return;

		$locale = apply_filters( 'plugin_locale', Core\L10n::locale(), $this->base );
		load_textdomain( $this->base.'-admin', $this->__dir."languages/admin-{$locale}.mo", $locale );
	}

	public function bp_include()
	{
		if ( is_readable( GNETWORK_DIR.'includes/Modules/BuddyPress.php' ) ) {

			try {

				$this->buddypress = new Modules\BuddyPress( $this->base, 'buddypress' );

			} catch ( Core\Exception $e ) {

				// echo 'Caught exception: ',  $e->getMessage(), "\n";
				// no need to do anything!
				do_action( 'qm/debug', $e );
			}
		}
	}

	public function bbp_includes()
	{
		if ( is_readable( GNETWORK_DIR.'includes/Modules/bbPress.php' ) ) {

			try {

				$this->bbpress = new Modules\bbPress( $this->base, 'bbpress' );

			} catch ( Core\Exception $e ) {

				// echo 'Caught exception: ',  $e->getMessage(), "\n";
				// no need to do anything!
				do_action( 'qm/debug', $e );
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
			$languages[$this->base] = GNETWORK_DIR.'includes/Misc/TinyMceStrings.php';

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

		if ( $this->option( 'ssl_support', 'site' ) )
			return TRUE;

		if ( $this->option( 'ssl_support', 'blog' ) )
			return TRUE;

		return FALSE;
	}

	public function email( $fallback = FALSE )
	{
		if ( isset( $this->mail ) )
			return $this->mail->get_from_email( $fallback );

		return $fallback;
	}

	public function user( $fallback = FALSE )
	{
		if ( $user_id = $this->option( 'site_user_id', 'user' ) )
			return (int) $user_id ;

		if ( defined( 'GNETWORK_SITE_USER_ID' ) && GNETWORK_SITE_USER_ID )
			return (int) GNETWORK_SITE_USER_ID;

		if ( function_exists( 'gtheme_get_option' )
			&& ( $user_id = gtheme_get_option( 'default_user', 0 ) ) )
				return (int) $user_id;

		if ( $fallback )
			return get_current_user_id();

		return 0;
	}

	public function brand( $what = 'name' )
	{
		switch ( $what ) {
			case 'name'  : $custom = $this->option( 'brand_name', 'branding' ); return $custom ?: GNETWORK_NAME; break;
			case 'url'   : $custom = $this->option( 'brand_url', 'branding' ); return $custom ?: GNETWORK_BASE; break;
			case 'email' : $custom = $this->option( 'brand_email', 'branding' ); return $custom ?: $this->email(); break;

			case 'color' :

				if ( $blog = $this->option( 'theme_color', 'blog' ) )
					return $blog;

				if ( $branding = $this->option( 'theme_color', 'branding' ) )
					return $branding;

				return '#d1d1d1';

			break;
			case 'background' :

				if ( $branding = $this->option( 'webapp_color', 'branding' ) )
					return $branding;

				return '#fff';
		}

		return '';
	}

	public function na( $wrap = 'code' )
	{
		return $wrap
			? Core\HTML::tag( $wrap, [ 'title' => __( 'Not Available', 'gnetwork' ) ], __( 'N/A', 'gnetwork' ) )
			: __( 'N/A', 'gnetwork' );
	}
}

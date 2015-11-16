<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkLocale extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = TRUE;
	protected $ajax       = TRUE;

	public $loaded = array();

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'locale',
			__( 'Locale', GNETWORK_TEXTDOMAIN ),
			FALSE, 'manage_network_options'
		);

		add_filter( 'locale', array( $this, 'locale' ), 1, 1 );

		if ( defined( 'GNETWORK_WPLANG' ) ) {

			add_filter( 'core_version_check_locale', function( $locale ){
				return gNetworkLocale::getDefault( $locale );
			} );

			if ( is_multisite() ) {
				add_filter( 'gnetwork_new_blog_options', array( $this, 'gnetwork_new_blog_options' ) );
			}

			if ( ! is_network_admin() ) {
				add_filter( 'load_textdomain_mofile', array( $this, 'load_textdomain_mofile' ), 12, 2 );
			}
		}
	}

	// HELPER
	public static function changeLocale( $locale = NULL )
	{
		if ( ! self::cuc( 'manage_options' ) )
			return FALSE;

		if ( is_null( $locale ) )
			$locale = self::getDefault();

		if ( 'en_US' == $locale )
			$locale = '';

		return update_option( 'WPLANG', $locale );
	}

	// HELPER
	// TODO: add arg to get by localized names
	public static function available()
	{
		$languages = get_available_languages();

		if ( ! in_array( 'en_US', $languages ) )
			$languages[] = 'en_US';

		return $languages;
	}

	// HELPER
	// http://stackoverflow.com/a/16838443/4864081
	public static function getISO( $locale = NULL )
	{
		if ( is_null( $locale ) )
			$locale = get_locale();

		if ( ! $locale )
			return 'en';

		$lang = explode( '_', $locale );

		return strtolower( $lang[0] );
	}

	public function load_textdomain_mofile( $mofile, $domain )
	{
		$locale = get_locale();
		$this->loaded[$locale][$domain][] = wp_normalize_path ( $mofile );

		$tailored = GNETWORK_DIR.'locale/'.$domain.'-'.$locale.'.mo';
		if ( is_readable( $tailored ) )
			return $tailored;

		return $mofile;
	}

	public static function loadedMOfiles()
	{
		global $gNetwork;

		echo self::html( 'h3', __( 'Loaded MO Files', GNETWORK_TEXTDOMAIN ) );
		self::tableSide( $gNetwork->locale->loaded );

		// self::tableSideWrap( $gNetwork->locale->loaded, __( 'Loaded MO Files', GNETWORK_TEXTDOMAIN ) );
	}

	public function gnetwork_new_blog_options( $new_options )
	{
		if ( 'fa_IR' == constant( 'GNETWORK_WPLANG' ) )
			return array_merge( $new_options, array(
				'timezone_string' => 'Asia/Tehran',
				'date_format'     => 'Y/n/d',
				'time_format'     => 'H:i',
				'start_of_week'   => 6,
				'WPLANG'          => GNETWORK_WPLANG,
			) );

		return $new_options;
	}

	public static function getDefault( $default = 'en_US' )
	{
		return defined( 'GNETWORK_WPLANG' ) && GNETWORK_WPLANG ? GNETWORK_WPLANG : $default;
	}

	public function locale( $locale )
	{
		global $gNetwork, $gNetworkCurrentLocale;

		if ( ! empty( $gNetworkCurrentLocale ) )
			return $gNetworkCurrentLocale;

		if ( is_network_admin() )
			return $gNetworkCurrentLocale = isset( $gNetwork->site ) && $gNetwork->site->options['admin_locale'] ? $gNetwork->site->options['admin_locale'] : 'en_US';

		if ( is_admin() ) {

			if ( isset( $gNetwork->blog ) && $gNetwork->blog->options['admin_locale'] )
				return $gNetworkCurrentLocale = $gNetwork->blog->options['admin_locale'];

			else if ( defined( 'GNETWORK_WPLANG_ADMIN' ) && GNETWORK_WPLANG_ADMIN )
				return $gNetworkCurrentLocale = GNETWORK_WPLANG_ADMIN;

		} else {
			return $gNetworkCurrentLocale = $locale;
		}

		if ( 'en_US' == $locale )
			return $gNetworkCurrentLocale = $locale;

		$black_list = apply_filters( 'gnetwork_locale_blacklist', array(
			'rewrite-rules-inspector'       => 'page',
			'deprecated_log'                => 'post_type',
			'connection-types'              => 'page',
			'regenerate-thumbnails'         => 'page',
			'ThreeWP_Activity_Monitor'      => 'page',
			'wpsupercache'                  => 'page',
			'backup-to-dropbox'             => 'page',
			'backup-to-dropbox-monitor'     => 'page',
			'redirection.php'               => 'page',
			'members-settings'              => 'page',
			'roles'                         => 'page',
			'wp-dbmanager/wp-dbmanager.php' => 'page',
			'ozh_yourls'                    => 'page',
			'regenerate-thumbnails'         => 'page',
			'a8c_developer'                 => 'page',
			'redirection.php'               => 'page',
			'bwp_gxs_stats'                 => 'page', // BWP Google XML Sitemaps
			'bwp_gxs_generator'             => 'page', // BWP Google XML Sitemaps
			'bwp_gxs_google_news'           => 'page', // BWP Google XML Sitemaps
			'limit-login-attempts'          => 'page',
			'p3-profiler'                   => 'page',
			'wp_aeh_errors'                 => 'page',
			'msrtm-website.php'             => 'page', // Multisite Robots.txt Manager
			'breadcrumb-navxt'              => 'page', // Breadcrumb NavXT
			'themecheck'                    => 'page', // Theme Check
			'search-regex.php'              => 'page', // Search Regex
			'ot-theme-options'              => 'page', // OptionTree: Theme Options
			'theme-documentation'           => 'page', // Revera Theme Documentation
			'options-framework'             => 'page', // Revera: Theme Options
		) );

		foreach ( $black_list as $val => $key )
			if ( isset( $_REQUEST[$key] ) && $val == trim( $_REQUEST[$key] ) )
				return $gNetworkCurrentLocale = 'en_US';

		return $gNetworkCurrentLocale = $locale;
	}
}

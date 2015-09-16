<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkLocale extends gNetworkModuleCore
{

	var $_network    = TRUE;
	var $_option_key = FALSE;
	var $_ajax       = TRUE;

	var $loaded = array();

	protected function setup_actions()
	{
		add_filter( 'locale', array( &$this, 'locale' ), 1, 1 );
		add_filter( 'core_version_check_locale', array( &$this, 'core_version_check_locale' ) );

		if ( defined( 'GNETWORK_WPLANG' ) ) {
			if ( is_multisite() ) {
				add_filter( 'gnetwork_new_blog_options', array( &$this, 'gnetwork_new_blog_options' ) );
			}
			if ( ! is_network_admin() ) {
				add_filter( 'load_textdomain_mofile', array( &$this, 'load_textdomain_mofile' ), 12, 2 );
			}
		}
	}

	public function load_textdomain_mofile( $mofile, $domain )
	{
		$locale = get_locale();
		$this->loaded[$locale][$domain][] = $mofile;

		$tailored = GNETWORK_DIR.'locale/'.$domain.'-'.$locale.'.mo';
		if ( is_readable( $tailored ) )
			return $tailored;

		return $mofile;
	}

	public static function loadedMOfiles()
	{
		global $gNetwork;
		gNetworkUtilities::dump( $gNetwork->locale->loaded ); // TODO: use Table Helper
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

	public function core_version_check_locale( $locale )
	{
		return defined( 'GNETWORK_WPLANG' ) ? GNETWORK_WPLANG : $locale;
	}

	public function locale( $locale )
	{
		if ( is_network_admin() )
			return 'en_US';

		if ( is_admin() ) {
			if ( GNETWORK_WPLANG_ADMIN )
				return GNETWORK_WPLANG_ADMIN;
		} else {
			return $locale;
		}

		if ( 'en_US' == $locale )
			return $locale;

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
		) );

		foreach ( $black_list as $val => $key )
			if ( isset( $_REQUEST[$key] ) && $val == trim( $_REQUEST[$key] ) )
				return 'en_US';

		return $locale;
	}
}

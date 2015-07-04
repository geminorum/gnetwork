<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkLocale extends gNetworkModuleCore
{

	var $_network    = TRUE;
	var $_option_key = FALSE;

	var $loaded = array();

	public function setup_actions()
	{
		add_filter( 'locale', array( &$this, 'locale' ), 1, 1 );
		add_filter( 'core_version_check_locale', array( &$this, 'core_version_check_locale' ) );

		if ( defined( 'GNETWORK_WPLANG' ) && 'fa_IR' == constant( 'GNETWORK_WPLANG' ) ) {
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

		$tailored = GNETWORK_DIR.'locale'.DS.$domain.'-'.$locale.'.mo';
		if ( is_readable( $tailored ) )
			return $tailored;

		return $mofile;
	}

	public static function loadedMOfiles()
	{
		global $gNetwork;
		gNetworkUtilities::dump( $gNetwork->locale->loaded );
	}

	public function gnetwork_new_blog_options( $new_options )
	{
		return array_merge( $new_options, array(
			'timezone_string' => 'Asia/Tehran',
			'date_format'     => 'Y/n/d',
			'time_format'     => 'H:i',
			'start_of_week'   => 6,
			'WPLANG'          => GNETWORK_WPLANG,
		) );
	}

	public function core_version_check_locale( $locale )
	{
		return defined( 'GNETWORK_WPLANG' ) ? GNETWORK_WPLANG : $locale;
	}

	// http://wp-snippet.com/snippets/different-admin-and-theme-languages/
	// setup one language for admin and the other for theme
	// must be called before load_theme_textdomain()
	// add_filter( 'locale', 'set_my_locale' );
	public function gnetwork_locale_set_my_locale( $locale )
	{
		$locale = ( is_admin() ) ? "en_US" : "it_IT";
		setlocale( LC_ALL, $local );
		return $locale;
	}

	public function locale( $locale )
	{
		if ( ! is_admin() )
			return $locale;

		if ( 'en_US' == $locale )
			return $locale;

		if( is_network_admin() )
			return 'en_US';

		$black_list = apply_filters( 'gnetwork_locale_blacklist', array(
			'page' => 'rewrite-rules-inspector',
			'post_type' => 'deprecated_log',
			'page' => 'connection-types',
			'page' => 'regenerate-thumbnails',
			'page' => 'ThreeWP_Activity_Monitor',
			'page' => 'wpsupercache',
			'page' => 'backup-to-dropbox',
			'page' => 'backup-to-dropbox-monitor',
			'page' => 'redirection.php',
			'page' => 'members-settings',
			'page' => 'roles',
			'page' => 'wp-dbmanager/wp-dbmanager.php',
			'page' => 'ozh_yourls',
			'page' => 'regenerate-thumbnails',
			'page' => 'a8c_developer',
			'page' => 'redirection.php',
			'page' => 'bwp_gxs_stats', // BWP Google XML Sitemaps
			'page' => 'bwp_gxs_generator', // BWP Google XML Sitemaps
			'page' => 'bwp_gxs_google_news', // BWP Google XML Sitemaps
			'page' => 'limit-login-attempts',
			'page' => 'p3-profiler',
			'page' => 'wp_aeh_errors',
			'page' => 'msrtm-website.php', // Multisite Robots.txt Manager
		) );

		foreach( $black_list as $key => $val )
			if ( isset( $_REQUEST[$key] ) && $val == trim( $_REQUEST[$key] ) )
				return 'en_US';

		return $locale;


		// global $gnetwork_local;
		// if ( ! empty( $gnetwork_local ) )
		// 	return $gnetwork_local;
		//
		// $parsed = parse_url( $_SERVER['REQUEST_URI'] );
		// //gnetwork_dump( $parsed ); die();
		// if ( isset( $parsed['query'] ) ) {
		// 	foreach( $black_list as $key => $val ) {
		// 	$b = $key.'='.$val;
		// 		gnetwork_dump( $b );
		// 		if ( $parsed['query'] == $key.'='.$val ) {
		// 			$gnetwork_local = 'en_US';
		// 			return $gnetwork_local;
		// 		}
		// 	}
		// }
		//
		//
		//
		// 		gnetwork_dump( $parsed ); die();
		// //gnetwork_dump( $_GET['page'] ); die();
		//
		// foreach( $black_list as $key => $val ) {
		// 	//$b = $_GET[$key].'-'.$key;
		// 	//gnetwork_dump( $b );
		// 	if ( isset( $_GET[$key] ) && $val == $_GET[$key] ) {
		// 	}
		// }
		//
		// $gnetwork_local = $locale;
		// return $locale;
		//
	}
}

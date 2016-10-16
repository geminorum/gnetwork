<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Locale extends ModuleCore
{

	protected $key  = 'locale';
	protected $ajax = TRUE;

	public $loaded = array();

	protected function setup_actions()
	{
		add_filter( 'locale', array( $this, 'locale' ), 1, 1 );

		if ( defined( 'GNETWORK_WPLANG' ) ) {

			add_filter( 'core_version_check_locale', function( $locale ){
				return Locale::getDefault( $locale );
			} );

			if ( is_multisite() )
				add_filter( 'gnetwork_network_new_blog_options', array( $this, 'new_blog_options' ) );

			add_filter( 'load_textdomain_mofile', array( $this, 'load_textdomain_mofile' ), 12, 2 );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Locale', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function settings( $sub = NULL )
	{
		if ( $sub == $this->key )
			add_action( 'gnetwork_admin_settings_sub_'.$sub, array( $this, 'settings_form' ), 10, 2 );
	}

	public function settings_form( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', FALSE );

			Settings::fieldSection( _x( 'Loaded MO Files', 'Modules: Locale', GNETWORK_TEXTDOMAIN ) );
			HTML::tableSide( $this->loaded );

		$this->settings_form_after( $uri, $sub );
	}

	public static function changeLocale( $locale = NULL )
	{
		if ( ! WordPress::cuc( 'manage_options' ) )
			return FALSE;

		if ( is_null( $locale ) )
			$locale = self::getDefault();

		if ( 'en_US' == $locale )
			$locale = '';

		return update_option( 'WPLANG', $locale );
	}

	// TODO: add arg to get by localized names
	public static function available()
	{
		$languages = get_available_languages();

		if ( ! in_array( 'en_US', $languages ) )
			$languages[] = 'en_US';

		return $languages;
	}

	// @REF: http://stackoverflow.com/a/16838443/4864081
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

		if ( 'en_US' == $locale )
			return $mofile;

		$this->loaded[$locale][$domain][] = wp_normalize_path( $mofile );

		$tailored = wp_normalize_path( GNETWORK_DIR.'locale/'.$domain.'-'.$locale.'.mo' );
		if ( is_readable( $tailored ) ) {
			$this->loaded[$locale][$domain][] = $tailored;
			return $tailored;
		}

		return $mofile;
	}

	public function new_blog_options( $new_options )
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
		global $gNetworkCurrentLocale;

		if ( ! empty( $gNetworkCurrentLocale ) )
			return $gNetworkCurrentLocale;

		if ( is_network_admin() )
			return $gNetworkCurrentLocale = gNetwork()->option( 'admin_locale', 'site', 'en_US' );

		// frontend AJAX calls are mistakend for admin calls
		if ( WordPress::isAJAX() && FALSE === strpos( wp_get_referer(), '/wp-admin/' ) )
			return $gNetworkCurrentLocale = $locale;

		if ( is_admin() || FALSE !== strpos( $_SERVER['REQUEST_URI'], '/wp-includes/js/tinymce/' ) ) {

			if ( $blog = gNetwork()->option( 'admin_locale', 'blog' ) )
				return $gNetworkCurrentLocale = $blog;

			else if ( defined( 'GNETWORK_WPLANG_ADMIN' ) && GNETWORK_WPLANG_ADMIN )
				return $gNetworkCurrentLocale = GNETWORK_WPLANG_ADMIN;

		} else {
			return $gNetworkCurrentLocale = $locale;
		}

		if ( 'en_US' == $locale )
			return $gNetworkCurrentLocale = $locale;

		$black_list = apply_filters( 'gnetwork_locale_blacklist', array(
			'deprecated_log'                => 'post_type',
			'rewrite-rules-inspector'       => 'page',
			'connection-types'              => 'page',
			'regenerate-thumbnails'         => 'page',
			'wpsupercache'                  => 'page',
			'members-settings'              => 'page',
			'roles'                         => 'page',
			'regenerate-thumbnails'         => 'page',
			'a8c_developer'                 => 'page',
			'redirection.php'               => 'page', // Redirection
			'bwp_gxs_generator'             => 'page', // BWP Google XML Sitemaps
			'bwp_gxs_extensions'            => 'page', // BWP Google XML Sitemaps
			'bwp_gxs_generator_advanced'    => 'page', // BWP Google XML Sitemaps
			'bwp_gxs_stats'                 => 'page', // BWP Google XML Sitemaps
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
			'domainmapping'                 => 'page', // WordPress MU Domain Mapping
			'wp-db-backup'                  => 'page', // WP-DB-Backup // https://wordpress.org/plugins/wp-db-backup/
			'odb_settings_page'             => 'page', // Optimize Database after Deleting Revisions // https://wordpress.org/plugins/rvg-optimize-database/
			'rvg-optimize-database'         => 'page', // Optimize Database after Deleting Revisions // https://wordpress.org/plugins/rvg-optimize-database/
			'github-updater'                => 'page', // [GitHub Updater](https://github.com/afragen/github-updater)

			// [AddThis Website Tools](https://wordpress.org/plugins/addthis-all/)
			'addthis_registration'      => 'page',
			'addthis_advanced_settings' => 'page',

			// [Google Analytics Dashboard for WP](https://wordpress.org/plugins/google-analytics-dashboard-for-wp/)
			'gadash_settings'          => 'page',
			'gadash_backend_settings'  => 'page',
			'gadash_frontend_settings' => 'page',
			'gadash_tracking_settings' => 'page',
			'gadash_errors_debugging'  => 'page',

			// [bonny/WordPress-Simple-History: Track user changes in WordPress admin](https://github.com/bonny/WordPress-Simple-History)
			// [See admin changes on your WordPress site with Simple History](https://simple-history.com/)
			'simple_history_page'               => 'page',
			'simple_history_settings_menu_slug' => 'page',

			// [Search Everything](https://wordpress.org/plugins/search-everything/)
			'extend_search' => 'page',

			// [Edit Flow](https://wordpress.org/plugins/edit-flow/)
			'ef-settings'                    => 'page',
			'ef-calendar-settings'           => 'page',
			'ef-custom-status-settings'      => 'page',
			'ef-dashboard-settings'          => 'page',
			'ef-editorial-comments-settings' => 'page',
			'ef-editorial-metadata-settings' => 'page',
			'ef-notifications-settings'      => 'page',
			'ef-user-groups-settings'        => 'page',

			// [WP-DBManager](https://wordpress.org/plugins/wp-dbmanager/)
			'wp-dbmanager/wp-dbmanager.php'      => 'page',
			'wp-dbmanager/database-manager.php'  => 'page',
			'wp-dbmanager/database-backup.php'   => 'page',
			'wp-dbmanager/database-manage.php'   => 'page',
			'wp-dbmanager/database-optimize.php' => 'page',
			'wp-dbmanager/database-repair.php'   => 'page',
			'wp-dbmanager/database-empty.php'    => 'page',
			'wp-dbmanager/database-run.php'      => 'page',
		) );

		foreach ( $black_list as $val => $key )
			if ( isset( $_REQUEST[$key] ) && $val == trim( $_REQUEST[$key] ) )
				return $gNetworkCurrentLocale = 'en_US';

		return $gNetworkCurrentLocale = $locale;
	}
}

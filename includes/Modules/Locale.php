<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\WordPress;

class Locale extends gNetwork\Module
{

	protected $key  = 'locale';
	protected $ajax = TRUE;

	public $loaded = [];

	protected function setup_actions()
	{
		$this->filter( 'locale', 1, 1 );

		if ( defined( 'GNETWORK_WPLANG' ) ) {

			add_filter( 'core_version_check_locale', function( $locale ){
				return Locale::getDefault( $locale );
			} );

			if ( is_multisite() )
				add_filter( 'gnetwork_network_new_blog_options', [ $this, 'new_blog_options' ] );

			$this->filter( 'load_textdomain_mofile', 2, 12 );
		}
	}

	public static function loadedMOs()
	{
		HTML::tableSide( gNetwork()->locale->loaded );
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

	public function load_textdomain_mofile( $mofile, $domain )
	{
		$locale = get_locale();

		if ( 'en_US' == $locale )
			return $mofile;

		$this->loaded[$locale][$domain][] = File::normalize( $mofile );

		$tailored = File::normalize( GNETWORK_DIR.'assets/locale/'.$domain.'-'.$locale.'.mo' );

		if ( ! is_readable( $tailored ) )
			return $mofile;

		$this->loaded[$locale][$domain][] = $tailored;

		return $tailored;
	}

	public function new_blog_options( $new_options )
	{
		if ( 'fa_IR' == constant( 'GNETWORK_WPLANG' ) )
			return array_merge( $new_options, [
				'timezone_string' => 'Asia/Tehran',
				'date_format'     => 'Y/n/d',
				'time_format'     => 'H:i',
				'start_of_week'   => 6,
				'WPLANG'          => GNETWORK_WPLANG,
			] );

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

		if ( WordPress::isAJAX() ) {

			$referer = HTTP::referer();

			if ( FALSE !== strpos( $referer, '/wp-admin/network/' ) )
				return $gNetworkCurrentLocale = gNetwork()->option( 'admin_locale', 'site', 'en_US' );

			// frontend AJAX calls are mistaken for admin calls
			if ( FALSE === strpos( $referer, '/wp-admin/' ) )
				return $gNetworkCurrentLocale = $locale;
		}

		if ( is_network_admin() )
			return $gNetworkCurrentLocale = $this->whiteListNetworkAdmin( $locale, gNetwork()->option( 'admin_locale', 'site', 'en_US' ) );

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

		return $gNetworkCurrentLocale = $this->blackListAdmin( $locale );
	}

	private function blackListAdmin( $current, $base = 'en_US' )
	{
		$list = $this->filters( 'blacklist', [
			'rewrite-rules-inspector'    => 'page',
			'connection-types'           => 'page',
			'regenerate-thumbnails'      => 'page',
			'wpsupercache'               => 'page',
			'members-settings'           => 'page',
			'a8c_developer'              => 'page',
			'redirection.php'            => 'page', // Redirection
			'bwp_gxs_generator'          => 'page', // BWP Google XML Sitemaps
			'bwp_gxs_extensions'         => 'page', // BWP Google XML Sitemaps
			'bwp_gxs_generator_advanced' => 'page', // BWP Google XML Sitemaps
			'bwp_gxs_stats'              => 'page', // BWP Google XML Sitemaps
			'p3-profiler'                => 'page',
			'wp_aeh_errors'              => 'page',
			'msrtm-website.php'          => 'page', // Multisite Robots.txt Manager
			'breadcrumb-navxt'           => 'page', // Breadcrumb NavXT
			'themecheck'                 => 'page', // Theme Check
			'search-regex.php'           => 'page', // Search Regex
			'ot-theme-options'           => 'page', // OptionTree: Theme Options
			'theme-documentation'        => 'page', // Revera Theme Documentation
			'options-framework'          => 'page', // Revera: Theme Options
			'domainmapping'              => 'page', // WordPress MU Domain Mapping
			'wp-db-backup'               => 'page', // WP-DB-Backup // https://wordpress.org/plugins/wp-db-backup/
			'odb_settings_page'          => 'page', // Optimize Database after Deleting Revisions // https://wordpress.org/plugins/rvg-optimize-database/
			'rvg-optimize-database'      => 'page', // Optimize Database after Deleting Revisions // https://wordpress.org/plugins/rvg-optimize-database/
			'extend_search'              => 'page', // [Search Everything](https://wordpress.org/plugins/search-everything/)
			'export-user-data'           => 'page', // [Export User Data](https://wordpress.org/plugins/export-user-data/)
			'exploit-scanner'            => 'page', // [Exploit Scanner](https://wordpress.org/plugins/exploit-scanner/)
			'vip-scanner'                => 'page', // [VIP Scanner](https://wordpress.org/plugins/vip-scanner/)
			'antivirus'                  => 'page', // [AntiVirus](https://wordpress.org/plugins/antivirus/)
			'mapcap'                     => 'page', // [Map Cap](https://wordpress.org/plugins/map-cap/)
			'akismet-key-config'         => 'page', // Akismet
			'cache-enabler'              => 'page', // [Cache Enabler](https://wordpress.org/plugins/cache-enabler/)
			'rest_api_console'           => 'page', // [REST API Console](https://wordpress.org/plugins/rest-api-console/)
			'rest-oauth1-apps'           => 'page',
			'so-widgets-plugins'         => 'page',
			'siteorigin_panels'          => 'page',
			'custom-contact-forms'       => 'page', // https://github.com/tlovett1/custom-contact-forms
			'php-compatibility-checker'  => 'page', // [PHP Compatibility Checker](https://wordpress.org/plugins/php-compatibility-checker/)
			'rlrsssl_really_simple_ssl'  => 'page', // [Really Simple SSL](https://wordpress.org/plugins/really-simple-ssl/)
			'onesignal-push'             => 'page', // [OneSignal](https://wordpress.org/plugins/onesignal-free-web-push-notifications/)

			'user-submitted-posts/user-submitted-posts.php' => 'page', // [User Submitted Posts](https://wordpress.org/plugins/user-submitted-posts/)

			// [EWWW Image Optimizer](https://wordpress.org/plugins/ewww-image-optimizer/)
			'ewww-image-optimizer/ewww-image-optimizer.php' => 'page',
			'ewww-image-optimizer-bulk'                     => 'page',
			'ewww-image-optimizer-dynamic-debug'            => 'page',
			'ewww-image-optimizer-queue-debug'              => 'page',

			// [Members](https://github.com/justintadlock/members)
			'roles'    => 'page',
			'role-new' => 'page',

			// [AddThis Website Tools](https://wordpress.org/plugins/addthis-all/)
			'addthis_registration'      => 'page',
			'addthis_advanced_settings' => 'page',

			// [Google Analytics Dashboard for WP](https://wordpress.org/plugins/google-analytics-dashboard-for-wp/)
			'gadwp_settings'          => 'page',
			'gadwp_backend_settings'  => 'page',
			'gadwp_frontend_settings' => 'page',
			'gadwp_tracking_settings' => 'page',
			'gadwp_errors_debugging'  => 'page',

			// [bonny/WordPress-Simple-History: Track user changes in WordPress admin](https://github.com/bonny/WordPress-Simple-History)
			// [See admin changes on your WordPress site with Simple History](https://simple-history.com/)
			'simple_history_page'               => 'page',
			'simple_history_settings_menu_slug' => 'page',

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

			// [Broken Link Checker](https://wordpress.org/plugins/broken-link-checker/)
			'view-broken-links'     => 'page',
			'link-checker-settings' => 'page',

			// [TablePress](https://wordpress.org/plugins/tablepress/)
			// 'tablepress'         => 'page',
			'tablepress_add'     => 'page',
			'tablepress_import'  => 'page',
			'tablepress_export'  => 'page',
			'tablepress_options' => 'page',
			'tablepress_about'   => 'page',

			// [Read Offline](https://wordpress.org/plugins/read-offline/)
			'read_offline_options' => 'page',
			'read_offline_pdf'     => 'page',
			'read_offline_epub'    => 'page',
			'read_offline_mobi'    => 'page',
			'read_offline_print'   => 'page',

			// [Profile Builder](https://wordpress.org/plugins/profile-builder/)
			'profile-builder-basic-info'         => 'page',
			'profile-builder-general-settings'   => 'page',
			'profile-builder-admin-bar-settings' => 'page',
			'manage-fields'                      => 'page',
			'profile-builder-add-ons'            => 'page',

			'keyring' => 'page',

			// [Safe Redirect Manager](https://wordpress.org/plugins/safe-redirect-manager/)
			'redirect_rule' => 'post_type',

			'amp-options'           => 'page',
			'amp-analytics-options' => 'page',

			// [Gravity Forms](https://github.com/wp-premium/gravityforms)
			'gf_settings'      => 'page',
			'gf_export'        => 'page',
			'gf_addons'        => 'page',
			'gf_system_status' => 'page',
			'gf_help'          => 'page',

		], $current );

		foreach ( array_unique( $list ) as $value => $key )
			if ( isset( $_REQUEST[$key] )
				&& isset( $list[$_REQUEST[$key]] )
					&& array_key_exists( $_REQUEST[$key], $list ) )
						return $base;

		return $current;
	}

	private function whiteListNetworkAdmin( $current, $base = 'en_US' )
	{
		$list = $this->filters( 'whitelist', [
			'bp-tools' => 'page',
		], $current );

		foreach ( array_unique( $list ) as $value => $key )
			if ( isset( $_REQUEST[$key] )
				&& isset( $list[$_REQUEST[$key]] )
					&& array_key_exists( $_REQUEST[$key], $list ) )
						return $current;

		return $base;
	}
}

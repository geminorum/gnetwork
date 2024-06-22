<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
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

			$this->filter( 'core_version_check_locale' );

			if ( is_multisite() )
				$this->filter_module( 'network', 'new_blog_options' );

			if ( ! defined( 'GNETWORK_DISABLE_LOCALE_OVERRIDES' ) ) {
				$this->filter( 'load_textdomain_mofile', 2, 12 );
				$this->filter( 'load_script_translation_file', 3, 12 );
			}
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

	// TODO: get by localized names
	public static function available()
	{
		$languages = get_available_languages();

		if ( ! in_array( 'en_US', $languages ) )
			$languages[] = 'en_US';

		return $languages;
	}

	public function core_version_check_locale( $locale )
	{
		return self::getDefault( $locale );
	}

	// TODO: must convert to regex
	private function get_bypassed_domains( $context = NULL )
	{
		return (array) $this->filters( 'bypassed_domains', [
			'gnetwork',
			'geditorial',
			'geditorial-admin',
			'gpersiandate',
			'gpeople',
			'gplugin',
			'gmember',
			'gletter',
			'gtheme',
			// 'kowsarsync',
		], $context );
	}

	private function bypass_domain( $domain, $context = NULL )
	{
		if ( empty( $domain ) || 'default' == $domain )
			return FALSE;

		if ( in_array( $domain, $this->get_bypassed_domains( $context ) ) )
			return TRUE;

		if ( Text::starts( $domain, 'geditorial-' ) )
			return TRUE;

		return FALSE;
	}

	// @SEE: `pre_load_textdomain` filter: https://make.wordpress.org/core/2023/07/14/i18n-improvements-in-6-3/
	// @SEE: `pre_load_script_translations` filter
	public function load_textdomain_mofile( $mofile, $domain )
	{
		static $filtered = [];

		$locale = determine_locale();

		if ( 'en_US' == $locale || $this->bypass_domain( $domain, 'mofile' ) )
			return $mofile;

		if ( 'default' == $domain ) {

			if ( Text::has( $mofile, 'admin-network' ) )
				$path = GNETWORK_DIR.'assets/locale/core/dist/admin-network-'.$locale.'.mo';

			else if ( Text::has( $mofile, 'admin' ) )
				$path = GNETWORK_DIR.'assets/locale/core/dist/admin-'.$locale.'.mo';

			else if ( Text::has( $mofile, 'continents-cities' ) )
				$path = GNETWORK_DIR.'assets/locale/core/dist/continents-cities-'.$locale.'.mo';

			else
				$path = GNETWORK_DIR.'assets/locale/core/dist/'.$locale.'.mo';

		} else {

			if ( isset( $filtered[$locale][$domain] ) )
				return $filtered[$locale][$domain] ?: $mofile;

			$path = GNETWORK_DIR.'assets/locale/'.$domain.'-'.$locale.'.mo';
		}

		$this->loaded[$locale][$domain][] = File::normalize( $mofile );

		$target = File::normalize( $path );

		if ( ! is_readable( $target ) ) {

			// avoid caching the default paths
			$filtered[$locale][$domain] = FALSE;
			return $mofile;
		}

		$this->loaded[$locale][$domain][] = $target;

		return $filtered[$locale][$domain] = $target;
	}

	public function load_script_translation_file( $file, $handle, $domain )
	{
		static $filtered = [];

		if ( ! $file )
			return $file;

		$locale = determine_locale();

		if ( 'en_US' == $locale )
			return $file;

		if ( ! empty( $filtered[$locale][$domain][$handle] ) )
			return $filtered[$locale][$domain][$handle];

		$this->loaded[$locale][$domain][$handle][] = $normalized = File::normalize( $file );

		if ( 'default' == $domain )
			$target = GNETWORK_DIR.'assets/locale/core/dist'.str_ireplace( File::normalize( WP_LANG_DIR ), '', $normalized );

		else if ( $this->bypass_domain( $domain, 'script' ) )
			return $file; // do nothing! NOTE: must not cache this here!

		else
			$target = GNETWORK_DIR.'assets/locale/'.File::basename( $normalized );

		if ( ! is_readable( $target ) )
			return $filtered[$locale][$domain][$handle] = $file;

		$this->loaded[$locale][$domain][$handle][] = File::normalize( $target );

		return $filtered[$locale][$domain][$handle] = $target;
	}

	public function network_new_blog_options( $new_options )
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

		if ( is_network_admin() ) {

			$target = gNetwork()->option( 'admin_locale', 'site', 'en_US' );

			if ( $target == $locale )
				return $gNetworkCurrentLocale = $this->blackListNetworkAdmin( $locale, 'en_US' );

			else
				return $gNetworkCurrentLocale = $this->whiteListNetworkAdmin( $locale, $target );
		}

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
		if ( $current == $base )
			return $current;

		if ( in_array( WordPress::pageNow(), [
			'about.php',
			'credits.php',
			'freedoms.php',
			'privacy.php',
			'site-health.php',
			'authorize-application.php',
		] ) )
			return $base;

		$list = $this->filters( 'blacklist', [
			'connection-types'           => 'page',
			'regenerate-thumbnails'      => 'page',
			'wpsupercache'               => 'page',
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
			'icit-profiler'              => 'page', // [WP Performance Profiler](https://github.com/khromov/wp-performance-profiler)
			'simpleWpSitemapSettings'    => 'page', // [Simple Wp Sitemap](https://wordpress.org/plugins/simple-wp-sitemap/)
			'health-check'               => 'page', // [Health Check & Troubleshooting](https://wordpress.org/plugins/health-check/)
			'wpcf7-integration'          => 'page', // [Contact Form 7](https://contactform7.com/)
			'wp-htaccess-editor'         => 'page', // [Htaccess Editor](https://wordpress.org/plugins/wp-htaccess-editor/)
			'add-from-server'            => 'page', // [Add From Server](https://wordpress.org/plugins/add-from-server/)
			'advanced-cron-manager'      => 'page',
			'sb-instagram-feed'          => 'page',
			'pdfemb_list_options'        => 'page',
			'theme-sniffer'              => 'page', // [Theme Sniffer](https://wordpress.org/plugins/theme-sniffer/)
			'wp-jquery-update-test'      => 'page', // [Test jQuery Updates](https://wordpress.org/plugins/wp-jquery-update-test/)


			// [Rank Math](https://wordpress.org/plugins/seo-by-rank-math/)
			'rank-math'                 => 'page',
			'rank-math-analytics'       => 'page',
			'rank-math-options-general' => 'page',
			'rank-math-options-titles'  => 'page',
			'rank-math-options-sitemap' => 'page',
			'rank-math-role-manager'    => 'page',
			'rank-math-404-monitor'     => 'page',
			'rank-math-redirections'    => 'page',
			'rank-math-seo-analysis'    => 'page',
			'rank-math-status'          => 'page',

			// [Official MailerLite Sign Up Forms](https://wordpress.org/plugins/official-mailerlite-sign-up-forms/)
			'mailerlite_main'     => 'page',
			'mailerlite_settings' => 'page',

			'tinymce-custom-styles/tinymce-custom-styles.php' => 'page', // [TinyMCE Custom Styles](https://wordpress.org/plugins/tinymce-custom-styles/)

			'user-submitted-posts/user-submitted-posts.php' => 'page', // [User Submitted Posts](https://wordpress.org/plugins/user-submitted-posts/)

			'ssl-insecure-content-fixer'       => 'page',
			'ssl-insecure-content-fixer-tests' => 'page',

			// [EWWW Image Optimizer](https://wordpress.org/plugins/ewww-image-optimizer/)
			'ewww-image-optimizer/ewww-image-optimizer.php' => 'page',
			'ewww-image-optimizer-bulk'                     => 'page',
			'ewww-image-optimizer-dynamic-debug'            => 'page',
			'ewww-image-optimizer-queue-debug'              => 'page',

			// [Members](https://github.com/justintadlock/members)
			'members-settings' => 'page',
			// 'roles'            => 'page',
			// 'role-new'         => 'page',

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
			// 'simple_history_page'               => 'page',
			// 'simple_history_settings_menu_slug' => 'page',

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

			// [WP-Sweep](https://wordpress.org/plugins/wp-sweep/)
			'wp-sweep/admin.php' => 'page',

			// [Broken Link Checker](https://wordpress.org/plugins/broken-link-checker/)
			'view-broken-links'     => 'page',
			'link-checker-settings' => 'page',

			// [TablePress](https://wordpress.org/plugins/tablepress/)
			// 'tablepress'         => 'page',
			// 'tablepress_add'     => 'page',
			// 'tablepress_import'  => 'page',
			// 'tablepress_export'  => 'page',
			// 'tablepress_options' => 'page',
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

			'bps_form' => 'post_type',

			// [Gravity Forms](https://github.com/wp-premium/gravityforms)
			'gf_settings'      => 'page',
			'gf_export'        => 'page',
			'gf_addons'        => 'page',
			'gf_system_status' => 'page',
			'gf_help'          => 'page',

			// [PublishPress](https://publishpress.com/)
			'pp-modules-settings' => 'page',
			'pp-addons'           => 'page',

			// [WooCommerce](https://woocommerce.com/)
			'wc-status'        => 'page',
			'wc-addons'        => 'page',
			// 'wc-settings'      => 'page',
			'action-scheduler' => 'page',

			'wp-shortpixel'       => 'page',
			'wp-short-pixel-bulk' => 'page',

			'enable-media-replace/enable-media-replace.php' => 'page',

			'sensei_updates'    => 'page',
			'sensei-extensions' => 'page',

			'stop-wp-emails-going-to-spam-settings' => 'page',

			'mycred-about'    => 'page',
			'mycred-setup'    => 'page',
			'mycred-hooks'    => 'page',
			'mycred-addons'   => 'page',
			'mycred-settings' => 'page',

			// [Slim SEO](https://wordpress.org/plugins/slim-seo/)
			'slim-seo' => 'page',

			// [YITH WooCommerce Wishlist](https://wordpress.org/plugins/yith-woocommerce-wishlist/)
			'yith_wcwl_panel' => 'page',

			// [LiteSpeed Cache](https://wordpress.org/plugins/litespeed-cache/)
			'litespeed'           => 'page',
			'litespeed-presets'   => 'page',
			'litespeed-general'   => 'page',
			'litespeed-cache'     => 'page',
			'litespeed-img_optm'  => 'page',
			'litespeed-db_optm'   => 'page',
			'litespeed-page_optm' => 'page',
			'litespeed-crawler'   => 'page',
			'litespeed-toolbox'   => 'page',
			'litespeed-cdn'       => 'page',

			// [Performance Lab](https://wordpress.org/plugins/performance-lab/)
			'perflab-modules' => 'page',

			// https://github.com/bueltge/wordpress-admin-style
			'WordPress_Admin_Style' => 'page',

			// [Media Library Folders](https://wordpress.org/plugins/media-library-plus/)
			'mlf-folders8'        => 'page',
			'mlf-thumbnails'      => 'page',
			'mlf-image-seo'       => 'page',
			'mlf-settings8'       => 'page',
			'mlf-support8'        => 'page',
			'mlf--upgrade-to-pro' => 'page',

			'idehweb-lwp'              => 'page',
			'idehweb-lwp-styles'       => 'page',
			'idehweb-lwp-localization' => 'page',

			'perflab-server-timing' => 'page',

			// [WP Table Pixie](https://wordpress.org/plugins/wp-table-pixie/)
			'wp-table-pixie' => 'page',

		], $current );

		return $this->check_request( $list, $current, $base );
	}

	private function whiteListNetworkAdmin( $current, $base = 'en_US' )
	{
		if ( $current == $base )
			return $current;

		$list = $this->filters( 'whitelist', [
			'bp-tools' => 'page',
		], $current );

		return $this->check_request( $list, $base, $current );
	}

	private function blackListNetworkAdmin( $current, $base = 'en_US' )
	{
		if ( $current == $base )
			return $current;

		if ( in_array( WordPress::pageNow(), [
			'theme-editor.php',
			'plugin-editor.php',
		] ) )
			return $base;

		$list = $this->filters( 'whitelist', [
			'wp_beta_tester'             => 'page',
			'ssl-insecure-content-fixer' => 'page',
			'wp-jquery-update-test'      => 'page', // [Test jQuery Updates](https://wordpress.org/plugins/wp-jquery-update-test/)

			// [BackWPup](https://backwpup.com/)
			'backwpup'         => 'page',
			'backwpupjobs'     => 'page',
			'backwpupeditjob'  => 'page',
			'backwpuplogs'     => 'page',
			'backwpupbackups'  => 'page',
			'backwpupsettings' => 'page',
			'backwpupabout'    => 'page',
		], $current );

		return $this->check_request( $list, $current, $base );
	}

	private function check_request( $list, $locale, $target )
	{
		foreach ( array_unique( $list ) as $value => $key )
			if ( isset( $_REQUEST[$key] )
				&& isset( $list[$_REQUEST[$key]] )
					&& array_key_exists( $_REQUEST[$key], $list ) )
						return $target;

		return $locale;
	}
}

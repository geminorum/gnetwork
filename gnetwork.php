<?php defined( 'ABSPATH' ) or die( 'Restricted access' );
/*
Plugin Name: gNetwork
Plugin URI: http://gmeinorum.ir/wordpress/gnetwork
Description: Network Helper
Version: 0.2.31
Author: geminorum
Author URI: http://geminorum.ir/
Network: true
TextDomain: gnetwork
DomainPath: /languages
GitHub Plugin URI: https://github.com/geminorum/gnetwork
GitHub Branch: master
Requires WP: 4.4
Requires PHP: 5.3
*/

define( 'GNETWORK_VERSION', '0.2.31' );
define( 'GNETWORK_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNETWORK_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( WP_CONTENT_DIR.'/gnetwork-custom.php' ) )
	require_once( WP_CONTENT_DIR.'/gnetwork-custom.php' );

function gnetwork_init() {
	global $gNetwork;

	$modules = array(
		'basecore'    => '',
		'constants'   => '',
		'functions'   => '',
		'utilities'   => '',
		'modulecore'  => '',
		'providercore' => '',
		'die'         => '',
		'pluggable'   => '',
		'network'     => 'gNetworkNetwork',
		'admin'       => 'gNetworkAdmin',
		'site'        => 'gNetworkSite',
		'blog'        => 'gNetworkBlog',
		'dashboard'   => 'gNetworkDashboard',
		'adminbar'    => 'gNetworkAdminBar',
		'users'       => 'gNetworkUsers',
		'tracking'    => 'gNetworkTracking',
		'maintenance' => 'gNetworkMaintenance',
		'restricted'  => 'gNetworkRestricted',
		'editor'      => 'gNetworkEditor',
		'captcha'     => 'gNetworkCaptcha',
		'opensearch'  => 'gNetworkOpenSearch',
		'mail'        => 'gNetworkMail',
		'sms'         => 'gNetworkSMS',
		'navigation'  => 'gNetworkNavigation',
		// 'activation'  => 'gNetworkActivation', // WORKING, NEEDS Final CHECK
		'locale'      => 'gNetworkLocale',
		'themes'      => 'gNetworkThemes',
		'media'       => 'gNetworkMedia',
		'cron'        => 'gNetworkCron',
		'login'       => 'gNetworkLogin',
		'lockdown'    => 'gNetworkLockDown',
		'blacklist'   => 'gNetworkBlackList',
		'update'      => 'gNetworkUpdate',
		'search'      => 'gNetworkSearch',
		'taxonomy'    => 'gNetworkTaxonomy',
		'shortcodes'  => 'gNetworkShortCodes',
		'comments'    => 'gNetworkComments',
		'widgets'     => 'gNetworkWidgets',
		'bbpress'     => 'gNetworkbbPress',
		'notify'      => 'gNetworkNotify',
		'reference'   => 'gNetworkReference',
		'typography'  => 'gNetworkTypography',
		'debug'       => 'gNetworkDebug',
		'code'        => 'gNetworkCode',
		'cleanup'     => 'gNetworkCleanup',
	);

	if ( defined( 'WP_STAGE' ) ) {
		if ( 'production' == WP_STAGE ) {
			$modules['bbq'] = 'gNetworkBBQ';
		} else if ( 'development' == WP_STAGE ){
			$modules['dev'] = 'gNetworkDev';
		}
	}

	foreach ( $modules as $module_slug => $module_class )
		if ( file_exists( GNETWORK_DIR.'includes/'.$module_slug.'.php' ) )
			require_once( GNETWORK_DIR.'includes/'.$module_slug.'.php' );

	if ( ! is_object( $gNetwork ) )
		$gNetwork = new stdClass();

	foreach ( $modules as $module_slug => $module_class ) {
		if ( $module_class && class_exists( $module_class ) ) {
			try {
				$gNetwork->{$module_slug} = new $module_class();
			} catch ( \Exception $e ) {
				// do nothing!
			}
		}
	}

	load_plugin_textdomain( GNETWORK_TEXTDOMAIN, FALSE, 'gnetwork/languages' );

	add_action( 'bp_include', 'gnetwork_bp_include' );

	if ( file_exists( GNETWORK_DIR.'includes/mce-languages.php' ) ) {
		add_filter( 'mce_external_languages', function( $languages ){
			$languages['gnetwork'] = GNETWORK_DIR.'includes/mce-languages.php';
			return $languages;
		} );
	}
}

function gnetwork_bp_include() {
	global $gNetwork;

	if ( file_exists( GNETWORK_DIR.'includes/buddypress.php' ) ) {
		require_once( GNETWORK_DIR.'includes/buddypress.php' );
		try {
			$gNetwork->buddypress = new gNetworkBuddyPress();
		} catch ( \Exception $e ) {
			// do nothing!
		}
	}

	if ( file_exists( GNETWORK_DIR.'includes/buddypress.me.php' ) ) {
		require_once( GNETWORK_DIR.'includes/buddypress.me.php' );
		buddypress()->me = new gNetwork_BP_Me_Component();
	}
}

if ( file_exists( GNETWORK_DIR.'assets/vendor/autoload.php' ) ) {
	require_once( GNETWORK_DIR.'assets/vendor/autoload.php' );

	gnetwork_init();
}

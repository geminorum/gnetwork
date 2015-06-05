<?php
/*
Plugin Name: gNetwork
Plugin URI: http://gmeinorum.ir/wordpress/gnetwork
Description: Network Helper
Version: 0.2.6
Author: geminorum
Author URI: http://geminorum.ir/
Network: true
TextDomain: gnetwork
DomainPath: /languages
GitHub Plugin URI: https://github.com/geminorum/gnetwork
GitHub Branch: develop
*/

define( 'GNETWORK_VERSION', '0.2.6' );
define( 'GNETWORK_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNETWORK_URL', plugin_dir_url( __FILE__ ) );

defined( 'DS' ) or define( 'DS', DIRECTORY_SEPARATOR );

if ( file_exists( WP_CONTENT_DIR.DS.'gnetwork-custom.php' ) )
	require_once( WP_CONTENT_DIR.DS.'gnetwork-custom.php' );

function gnetwork_init() {
	global $gNetwork;

	$modules = array(
		'constants'   => '',
		'functions'   => '',
		'utilities'   => '',
		'modulecore'  => '',

		'network'     => 'gNetworkNetwork',
		'admin'       => 'gNetworkAdmin',
		'adminbar'    => 'gNetworkAdminBar',
		'users'       => 'gNetworkUsers',

		'tracking'    => 'gNetworkTracking',
		'maintenance' => 'gNetworkMaintenance',
		'restricted'  => 'gNetworkRestricted',
		'editor'      => 'gNetworkEditor',
		'captcha'     => 'gNetworkCaptcha',
		'opensearch'  => 'gNetworkOpenSearch',
		'mail'        => 'gNetworkMail',
		'navigation'  => 'gNetworkNavigation',
		// 'activation'  => 'gNetworkActivation', // WORKING, NEEDS Final CHECK and seeing the Original
		'locale'      => 'gNetworkLocale',
		'themes'      => 'gNetworkThemes',
		'media'       => 'gNetworkMedia',

		'redirect'    => 'gNetworkRedirect',
		'login'       => 'gNetworkLogin',
		'lockdown'    => 'gNetworkLockDown',
		'blacklist'   => 'gNetworkBlackList',

		// 'backup'      => 'gNetworkBackup',
		'update'      => 'gNetworkUpdate',
		// 'limitlogin' => 'gNetworkLimitLogin',

		'die'         => '',
		'pluggable'   => '',


		// 'options' => 'gNetworkOptions', // NOT FINISHED!!
		// 'screen' => 'gNetworkScreen', // DRAFT

		// 'p2' => 'gNetworkP2', // must rename to attachment or move to media module

		// 'url' => 'gNetworkURL',
		'search'     => 'gNetworkSearch',

		'taxonomy'   => 'gNetworkTaxonomy',
		// 'meta' => 'gNetworkMeta', // must clean the mess!
		'shortcodes' => 'gNetworkShortCodes',
		'comments'   => 'gNetworkComments',
		'widgets'    => 'gNetworkWidgets',
		'bbpress'    => 'gNetworkbbPress',
		// 'edd'        => 'gNetworkEDD', // WORKING, BUT DISABLED : Must review
		// 'gshop'      => 'gNetworkgShop', // WORKING, BUT DISABLED : Must review
		'gpeople'    => 'gNetworkgPeople',

		// 'barcode'    => 'gNetworkgBarCode', // TEST
		// 'thumb' => 'gNetworkgThumb', // TEST/DRAFT

		'notify'     => 'gNetworkNotify',
		'reference'  => 'gNetworkReference',
		'typography' => 'gNetworkTypography',
		// 'highlight'  => 'gNetworkHighLight', // DRAFT

		// 'sms'        => 'gNetworkSMS', // WORKING, BUT DISABLED : Must review
		// 'geo'        => 'gNetworkGeo', // PLANNING!
		'debug'      => 'gNetworkDebug',
		'code'       => 'gNetworkCode',
		// 'files'      => 'gNetworkFiles', // DRAFT
		'cleanup'    => 'gNetworkCleanup',
		// 'shortener'  => 'gNetworkShortener', // UNFINISHED
		// 'pot'        => 'gNetworkPOT',
		// 'htaccess'   => 'gNetworkHTAccess',
		// 'signup' => 'gNetworkSignUp',
	);

	if ( defined( 'WP_STAGE' ) ) {
		if ( 'production' == WP_STAGE ) {
			$modules['bbq'] = 'gNetworkBBQ';
		} else if ( 'development' == WP_STAGE ){
			$modules['dev'] = 'gNetworkDev';

			// BETA FEATURES
			// $modules['mustache'] = 'gNetworkMustache'; // WORKING
			// $modules['invoice'] = 'gNetworkInvoice'; // PLANNING

		}
	}

	foreach ( $modules as $module_slug => $module_class )
		if ( file_exists( GNETWORK_DIR.'includes'.DS.$module_slug.'.php' ) )
			require_once( GNETWORK_DIR.'includes'.DS.$module_slug.'.php' );

	if ( ! is_object( $gNetwork ) )
		$gNetwork = new stdClass();

	foreach ( $modules as $module_slug => $module_class )
		if ( $module_class && class_exists( $module_class ) )
			$gNetwork->{$module_slug} = new $module_class();

	load_plugin_textdomain( GNETWORK_TEXTDOMAIN, false, 'gnetwork/languages' );

	add_action( 'bp_include', 'gnetwork_bp_include' );

	// http://plugins.svn.wordpress.org/link-manager/trunk/link-manager.php
	// http://core.trac.wordpress.org/ticket/21307
	add_filter( 'pre_option_link_manager_enabled', '__return_false' );

	// http://wordpress.org/extend/plugins/disable-post-format-ui/
	add_filter( 'enable_post_format_ui', '__return_false' );

	// http://wpengineer.com/2484/xml-rpc-enabled-by-default-in-wordpress-3-5/
	// add_filter( 'xmlrpc_enabled', '__return_false' );

	// http://stephanis.info/2014/08/13/on-jetpack-and-auto-activating-modules
	add_filter( 'jetpack_get_default_modules', '__return_empty_array' );

	if ( file_exists( GNETWORK_DIR.'includes'.DS.'mce-languages.php' ) ) {
		add_filter( 'mce_external_languages', function( $languages ){
			$languages['gnetwork'] = GNETWORK_DIR.'includes'.DS.'mce-languages.php';
			return $languages;
		} );
	}
}

function gnetwork_bp_include() {
	global $gNetwork;

	if ( file_exists( GNETWORK_DIR.'includes'.DS.'buddypress.php' ) ) {
		require_once( GNETWORK_DIR.'includes'.DS.'buddypress.php' );
		$gNetwork->buddypress = new gNetworkBuddyPress();
	}

	if ( file_exists( GNETWORK_DIR.'includes'.DS.'buddypress.me.php' ) ) {
		require_once( GNETWORK_DIR.'includes'.DS.'buddypress.me.php' );
		buddypress()->me = new gNetwork_BP_Me_Component();
	}
}

gnetwork_init();

<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

/*
Plugin Name: gNetwork
Plugin URI: http://gmeinorum.ir/wordpress/gnetwork
Description: Network Helper
Version: 3.6.7
License: GPLv3+
Author: geminorum
Author URI: http://geminorum.ir/
Network: true
Text Domain: gnetwork
Domain Path: /languages
GitHub Plugin URI: https://github.com/geminorum/gnetwork
GitHub Branch: master
Release Asset: true
Requires WP: 4.7
Requires PHP: 5.4
*/

define( 'GNETWORK_VERSION', '3.6.7' );
define( 'GNETWORK_MIN_PHP', '5.4.0' );
define( 'GNETWORK_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNETWORK_URL', plugin_dir_url( __FILE__ ) );
define( 'GNETWORK_FILE', basename( GNETWORK_DIR ) . '/' . basename( __FILE__ ) );

if ( file_exists( WP_CONTENT_DIR . '/gnetwork-custom.php' ) ) {
	require_once WP_CONTENT_DIR . '/gnetwork-custom.php';
}

if ( version_compare( GNETWORK_MIN_PHP, PHP_VERSION, '>=' ) ) {

	echo '<div class="notice notice-warning notice-alt is-dismissible"><p dir="ltr">';
		printf( '<b>gNetwork</b> requires PHP %s or higher. Please contact your hosting provider to update your site.', GNETWORK_MIN_PHP ); // WPCS: XSS ok.
	echo '</p></div>';

	return false;

} elseif ( file_exists( GNETWORK_DIR . 'assets/vendor/autoload.php' ) ) {

	require_once GNETWORK_DIR . 'assets/vendor/autoload.php';
	// require_once GNETWORK_DIR . 'includes/plugin.php';

	function gNetwork() {
		return \geminorum\gNetwork\Plugin::instance();
	}

	gNetwork();

} else {

	$gnetwork_notice = function() {
		echo '<div class="notice notice-warning notice-alt is-dismissible"><p dir="ltr">';
		printf( '<b>gNetwork</b> is not installed correctly. go grab the latest package <a href="%s" target="_blank">here</a>.', 'https://github.com/geminorum/gnetwork/releases/latest' );
		echo '</p></div>';
	};

	if ( ! is_multisite() ) {
		add_action( 'admin_notices', $gnetwork_notice );
	} elseif ( is_network_admin() ) {
		add_action( 'network_admin_notices', $gnetwork_notice );
	}

	unset( $gnetwork_notice );
}

<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

/*
Plugin Name: gNetwork
Plugin URI: http://gmeinorum.ir/wordpress/gnetwork
Description: Network Helper
Version: 3.6.6
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

define( 'GNETWORK_VERSION', '3.6.6' );
define( 'GNETWORK_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNETWORK_URL', plugin_dir_url( __FILE__ ) );
define( 'GNETWORK_FILE', basename( GNETWORK_DIR ).'/'.basename( __FILE__ ) );

if ( file_exists( WP_CONTENT_DIR.'/gnetwork-custom.php' ) )
	require_once( WP_CONTENT_DIR.'/gnetwork-custom.php' );

if ( file_exists( GNETWORK_DIR.'assets/vendor/autoload.php' ) ) {
	require_once( GNETWORK_DIR.'assets/vendor/autoload.php' );

	require_once( GNETWORK_DIR.'includes/plugin.php' );

	function gNetwork() {
		return \geminorum\gNetwork\Plugin::instance();
	}

	gNetwork();

} else if ( is_network_admin() ) {

	add_action( 'network_admin_notices', function(){
		echo '<div class="notice notice-warning notice-alt is-dismissible"><p>';
			printf( '<b>gNetwork</b> is not installed correctly. go grab the latest package <a href="%s" target="_blank">here</a>.', 'https://github.com/geminorum/gnetwork/releases/latest' ) ;
		echo '</p></div>';
	} );
}

<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

/*
Plugin Name: gNetwork
Plugin URI: http://gmeinorum.ir/wordpress/gnetwork
Description: Network Helper
Version: 3.5.2
License: GPLv3+
Author: geminorum
Author URI: http://geminorum.ir/
Network: true
TextDomain: gnetwork
DomainPath: /languages
GitHub Plugin URI: https://github.com/geminorum/gnetwork
GitHub Branch: master
Release Asset: true
Requires WP: 4.5
Requires PHP: 5.3
*/

define( 'GNETWORK_VERSION', '3.5.2' );
define( 'GNETWORK_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNETWORK_URL', plugin_dir_url( __FILE__ ) );
define( 'GNETWORK_FILE', basename( GNETWORK_DIR ).'/'.basename( __FILE__ ) );

if ( file_exists( WP_CONTENT_DIR.'/gnetwork-custom.php' ) )
	require_once( WP_CONTENT_DIR.'/gnetwork-custom.php' );

if ( file_exists( GNETWORK_DIR.'assets/vendor/autoload.php' ) ) {
	require_once( GNETWORK_DIR.'assets/vendor/autoload.php' );

	require_once( GNETWORK_DIR.'includes/gnetwork.php' );

	function gNetwork() {
		return \geminorum\gNetwork\gNetwork::instance();
	}

	// back comp
	global $gNetwork;

	$gNetwork = gNetwork();

} else if ( is_network_admin() ) {

	add_action( 'network_admin_notices', function(){
		echo '<div class="notice notice-warning notice-alt is-dismissible"><p>';
			printf( '<b>gNetwork</b> is not installed correctly. go grab the latest package <a href="%s" target="_blank">here</a>.', 'https://github.com/geminorum/gnetwork/releases/latest' ) ;
		echo '</p></div>';
	} );
}

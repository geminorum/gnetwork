<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

/*
Plugin Name: gNetwork
Plugin URI: http://gmeinorum.ir/wordpress/gnetwork
Description: Network Helper
Version: 3.3.2
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

define( 'GNETWORK_VERSION', '3.3.2' );
define( 'GNETWORK_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNETWORK_URL', plugin_dir_url( __FILE__ ) );

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
}

<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// ensure this plugin is actually being uninstalled
defined( 'WP_UNINSTALL_PLUGIN' ) || exit();

return; // working but in case of removing plugin for correct install, all options will be gone!

if ( is_multisite() ) {

	global $wpdb;

	if ( $blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A ) ) {

		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			delete_option( 'gnetwork_blog' );
		}

		restore_current_blog();
	}

} else {
	delete_option( 'gnetwork_blog' );
}

delete_network_option( NULL, 'gnetwork_site' );

<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

// ensure this plugin is actually being uninstalled
defined( 'WP_UNINSTALL_PLUGIN' ) or exit();

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

delete_site_option( 'gnetwork_site' );

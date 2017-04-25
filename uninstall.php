<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

// ensure this plugin is actually being uninstalled
defined( 'WP_UNINSTALL_PLUGIN' ) or exit();

delete_option( 'gnetwork_blog' ); // FIXME: it just for main blog!
delete_site_option( 'gnetwork_site' );

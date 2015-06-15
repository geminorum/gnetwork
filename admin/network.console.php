<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

gNU::superAdminOnly();

global $gNetworkOptionsNetwork, $gNetworkOptionsBlog;
gNU::dump( $gNetworkOptionsNetwork );
gNU::dump( $gNetworkOptionsBlog );

gNU::dump( get_site_option( 'gnetwork_site', array() ) );
gNU::dump( get_option( 'gnetwork_blog', array() ) );

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

gNU::superAdminOnly();

global $gnetworkOptionsNetwork, $gnetworkOptionsBlog;
gNU::dump( $gnetworkOptionsNetwork );
gNU::dump( $gnetworkOptionsBlog );

gNU::dump( get_site_option( 'gnetwork_site', array() ) );
gNU::dump( get_option( 'gnetwork_blog', array() ) );

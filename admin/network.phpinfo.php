<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

gNetworkUtilities::superAdminOnly();

if ( class_exists( 'gNetworkDebug' ) )
	gNetworkDebug::phpinfo();

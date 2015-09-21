<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

if ( class_exists( 'gNetworkLocale' )
	&& current_user_can( 'manage_options' ) )
		gNetworkLocale::loadedMOfiles();

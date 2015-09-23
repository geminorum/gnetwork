<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

if ( class_exists( 'gNetworkCRON' )
	&& current_user_can( 'manage_options' ) )
		gNetworkCRON::cronInfo();

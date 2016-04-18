<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

if ( class_exists( __NAMESPACE__.'\\Locale' )
	&& current_user_can( 'manage_options' ) )
		Locale::loadedMOfiles();

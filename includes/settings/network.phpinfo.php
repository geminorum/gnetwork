<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

Utilities::superAdminOnly();

if ( class_exists( __NAMESPACE__.'\\Debug' ) )
	Debug::phpinfo();

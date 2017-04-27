<?php namespace geminorum\gNetwork\Settings;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\WordPress;

WordPress::superAdminOnly();

if ( class_exists( 'geminorum\\gNetwork\\Modules\\Debug' ) )
	\geminorum\gNetwork\Modules\Debug::phpinfo();

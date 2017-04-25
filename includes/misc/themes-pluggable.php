<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

if ( ! function_exists( 'untitled_posted_on' ) ) : function untitled_posted_on() {
	\geminorum\gNetwork\Themes::postedOn();
} endif;

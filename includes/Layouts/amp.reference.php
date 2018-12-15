<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

echo gNetwork()->shortcodes->shortcode_reflist( [
	'wrap'   => FALSE,
	'number' => TRUE, // amp not allows inline styles
], NULL, 'reflist' );

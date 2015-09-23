<?php defined( 'ABSPATH' ) or exit;

// https://www.gavick.com/blog/wordpress-tinymce-custom-buttons#tc2-section1

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH.WPINC.'/class-wp-editor.php' );

$strings = class_exists( 'gNetworkUtilities' ) ? gNetworkUtilities::getTinyMceStrings( _WP_Editors::$mce_locale ) : '';

<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// https://www.gavick.com/blog/wordpress-tinymce-custom-buttons#tc2-section1

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH.WPINC.'/class-wp-editor.php' );

$strings = class_exists( 'geminorum\\gNetwork\\Scripts' ) ? \geminorum\gNetwork\Scripts::getTinyMceStrings( \_WP_Editors::$mce_locale ) : '';

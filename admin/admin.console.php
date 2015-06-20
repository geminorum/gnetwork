<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

gNU::superAdminOnly();

global $gnetworkOptionsNetwork, $gnetworkOptionsBlog;
gNU::dump( $gnetworkOptionsNetwork );
gNU::dump( $gnetworkOptionsBlog );

gNU::dump( get_site_option( 'gnetwork_site', array() ) );
gNU::dump( get_option( 'gnetwork_blog', array() ) );

$text   = 'این یک متن فارسی است';
$accent = 'كَتَبَ عَلىٰ نَفسِهِ الرَّحمَةَ';
$name   = 'ارنست هِمینگ‌وی';

// require GPEOPLE_DIR.'assets/libs/urlify/URLify.php';
// echo URLify::filter( 'Lo siento, no hablo español.', 255, 'fa' );
// echo URLify::filter( $text, 255, 'fa' );
// echo URLify::downcode( $text );

// echo iconv( 'UTF-8', 'UTF-8//TRANSLIT', $accent );
// echo preg_replace( '/\p{Mn}/u', '', Normalizer::normalize( $accent, Normalizer::FORM_KD ) ); // http://stackoverflow.com/a/3542752
echo preg_replace( '/\p{Mn}/u', '', Normalizer::normalize( $name, Normalizer::FORM_KD ) ); // http://stackoverflow.com/a/3542752

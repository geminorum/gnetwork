<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

if ( ! function_exists( 'gnetwork_powered' ) ) :
	function gnetwork_powered( $rtl = NULL ){
		return '<a href="http://wordpress.org/" title="WP powered"><img src="'.GNETWORK_URL.'assets/images/wpmini-grey.png" alt="wp" /></a>';
	}
endif;

if ( ! function_exists( 'gnetwork_copyright' ) ) :
	function gnetwork_copyright( $rtl = NULL ){
		return __( 'Built on <a href="http://wordpress.org/" title="Semantic Personal Publishing Platform">WordPress</a> and tea', GNETWORK_TEXTDOMAIN );
	}
endif;

if ( ! function_exists( 'gnetwork_credits' ) ) :
	function gnetwork_credits( $rtl = NULL, $admin = NULL ){
		if ( is_null( $rtl ) )
			$rtl = is_rtl();

		if ( is_null( $admin ) )
			$admin = is_admin();

		if ( $admin )
			return gnetwork_powered( $rtl );
		else
			return gnetwork_copyright( $rtl );
	}
endif;

if ( ! function_exists( '__donot_cache_page' ) ) : function __donot_cache_page() {
	defined( 'DONOTCACHEPAGE' ) or define( 'DONOTCACHEPAGE', TRUE );
} endif;

if ( ! function_exists( '__gpersiandate_skip' ) ) : function __gpersiandate_skip() {
	defined( 'GPERSIANDATE_SKIP' ) or define( 'GPERSIANDATE_SKIP', TRUE );
} endif;

if ( ! function_exists( 'gnetwork_log' ) ) : function gnetwork_log( $data, $table = 0 ) {
	gNetworkUtilities::log( $data, $table );
} endif;

if ( ! function_exists( 'gnetwork_dump' ) ) : function gnetwork_dump( $var, $htmlSafe = TRUE ) {
	gNetworkUtilities::dump( $var, $htmlSafe );
} endif;

if ( ! function_exists( 'gnetwork_trace' ) ) : function gnetwork_trace( $old = TRUE ) {
	gNetworkUtilities::trace( $old );
} endif;

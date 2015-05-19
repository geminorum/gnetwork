<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

if ( ! function_exists( 'gnetwork_powered' ) ) :
	function gnetwork_powered( $rtl = null ){
		return '<a href="http://wordpress.org/" title="WP powered"><img src="'.GNETWORK_URL.'assets/images/wpmini-grey.png" alt="wp" /></a>';
	}
endif;

if ( ! function_exists( 'gnetwork_copyright' ) ) :
	function gnetwork_copyright( $rtl = null ){
		return __( 'Built on <a href="http://wordpress.org/" title="Semantic Personal Publishing Platform">WordPress</a> and tea', GNETWORK_TEXTDOMAIN );
	}
endif;

if ( ! function_exists( 'gnetwork_credits' ) ) :
	function gnetwork_credits( $rtl = null, $admin = null ){
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

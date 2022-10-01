<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

if ( ! function_exists( 'woocommerce_result_count' )
	&& gNetwork()->commerce->get_option( 'hide_result_count' ) ) :

	function woocommerce_result_count() {} // override the function
endif;


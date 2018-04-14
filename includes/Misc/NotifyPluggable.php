<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

if ( ! function_exists( 'wp_new_user_notification' ) ) :
function wp_new_user_notification( $user_id, $deprecated = NULL, $notify = '' ) {
	return gNetwork()->notify->wp_new_user_notification( $user_id, $deprecated, $notify );
} endif;

if ( ! function_exists( 'wp_password_change_notification' ) ) :
function wp_password_change_notification( $user ) {
	return gNetwork()->notify->wp_password_change_notification( $user );
} endif;

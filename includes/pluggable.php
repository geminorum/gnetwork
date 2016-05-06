<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

// Allow WordPress to use bcrypt for passwords.
// https://gist.github.com/chrisguitarguy/4122483
if ( ! function_exists( 'wp_hash_password' ) ):
	function wp_hash_password( $password ) {
		global $wp_hasher;

		if ( empty( $wp_hasher ) ) {
			require_once( ABSPATH.WPINC.'/class-phpass.php');

			// second arg: make password non-portable (eg. allow bcrypt)
			$wp_hasher = new \PasswordHash( 16, FALSE );
		}

		return $wp_hasher->HashPassword( trim( $password ) );
	}
endif;

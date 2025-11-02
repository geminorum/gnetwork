<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Extend extends Core\Base
{
	// @REF: `is_plugin_active()`
	public static function isPluginActive( $plugin, $network_check = TRUE )
	{
		if ( in_array( $plugin, (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), TRUE ) )
			return TRUE;

		if ( $network_check && self::isPluginActiveForNetwork( $plugin ) )
			return TRUE;

		return FALSE;
	}

	// @REF: `is_plugin_active_for_network()`
	public static function isPluginActiveForNetwork( $plugin, $network = NULL )
	{
		if ( is_multisite() )
			return (bool) in_array( $plugin, (array) get_network_option( $network, 'active_sitewide_plugins' ), TRUE );

		return FALSE;
	}

	// @REF: https://wordpress.org/support/topic/how-to-change-plugins-load-order/
	// @USAGE: `add_action( 'activated_plugin', function () {} );`
	public static function pluginFirst( $plugin )
	{
		if ( empty( $plugin ) )
			return;

		// Ensure path to this file is via main `wp-plugin` path
		// `$wp_path_to_this_file = preg_replace( '/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR."/$2", __FILE__ );`
		// `$plugin = plugin_basename( trim( $wp_path_to_this_file ) );`

		$active = get_option( 'active_plugins' );

		// If it's `0` it's the first plugin already, no need to continue
		if ( $key = array_search( $plugin, $active ) ) {
			array_splice( $active, $key, 1 );
			array_unshift( $active, $plugin );
			update_option( 'active_plugins', $active );
		}
	}
}

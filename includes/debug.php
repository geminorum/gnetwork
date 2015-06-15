<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkDebug extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	public function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'debug',
			__( 'Debug', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' ), 'delete_others_posts'
		);

		add_action( 'debug_bar_panels', function( $panels ) {
			require_once GNETWORK_DIR.'includes/debugbar-panel.php';
			$panels[] = new Debug_Bar_gNetwork();
			$panels[] = new Debug_Bar_gNetworkMeta();
			return $panels;
		} );

		// add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
		// add_action( 'wp_before_admin_bar_render', 'supercache_admin_bar_render' );

		add_action( 'wp_footer', array( &$this, 'wp_footer' ), 999 );

		if ( 'production' == WP_STAGE ) {
			if ( WP_DEBUG_LOG && ! WP_DEBUG_DISPLAY ) {

				add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
				add_filter( 'deprecated_function_trigger_error', '__return_false' );
				add_filter( 'deprecated_file_trigger_error', '__return_false' );
				add_filter( 'deprecated_argument_trigger_error', '__return_false' );

				add_action( 'http_api_debug', array( &$this, 'http_api_debug' ), 10, 3 );

				// akismet will log all the http_reqs!!
				add_filter( 'akismet_debug_log', '__return_false' );
			}
		} else if ( 'development' == WP_STAGE ) {
			add_action( 'pre_get_posts', array( &$this, 'pre_get_posts' ), 99 );
		}
	}

	public function settings( $sub = NULL )
	{
		if ( 'debug' == $sub ) {

			if ( isset( $_POST['purge_transient'] )
				|| isset( $_POST['purge_transient_all'] ) ) {
					check_admin_referer( 'gnetwork_'.$sub.'-options' );
					$this->purge_transient_data( false, isset( $_POST['purge_transient'] ) );
					self::redirect_referer( 'transientpurged' );
			}

			// $this->settings_update( $sub );
			// $this->register_settings();
			$this->register_button( 'purge_transient', __( 'Purge Expired Transient Data', GNETWORK_TEXTDOMAIN ) );
			$this->register_button( 'purge_transient_all', __( 'Purge All Transient Data', GNETWORK_TEXTDOMAIN ) );

			add_action( 'gnetwork_admin_settings_sub_debug', array( &$this, 'settings_html' ), 10, 2 );
		}
	}

	// https://wordpress.org/plugins/stop-query-posts/
	public function pre_get_posts( $query )
	{
		if ( $query === $GLOBALS['wp_query'] && ! $query->is_main_query() )
			_doing_it_wrong( 'query_posts', 'You should <a href="http://wordpress.tv/2012/06/15/andrew-nacin-wp_query/">not use query_posts</a>.', null );
	}

	public static function versions()
	{
		global $wp_version,
			$wp_db_version,
			$tinymce_version,
			$required_php_version,
			$required_mysql_version;

		$versions = array(
			'wp_version'             => _x( 'WordPress', 'Debug Version Strings', GNETWORK_TEXTDOMAIN ),
			'wp_db_version'          => _x( 'WordPress DB revision', 'Debug Version Strings', GNETWORK_TEXTDOMAIN ),
			'tinymce_version'        => _x( 'TinyMCE', 'Debug Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_php_version'   => _x( 'Required PHP', 'Debug Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_mysql_version' => _x( 'Required MySQL', 'Debug Version Strings', GNETWORK_TEXTDOMAIN ),
		);

		echo '<table><tbody>';
		foreach ( $versions as $key => $val )
			echo sprintf( '<tr><td style="width:185px">%1$s</td><td><code>%2$s</code></td></tr>', $val, $$key );
		echo '</tbody></table>';
	}

	public static function codeTable( $array )
	{
		echo '<table><tbody>';
		foreach ( $array as $key => $val )
			echo sprintf( '<tr><td style="width:185px">%1$s</td><td><code>%2$s</code></td></tr>', $key, $val );
		echo '</tbody></table>';
	}

	public static function cacheStats()
	{
		global $wp_object_cache;
		$wp_object_cache->stats();
	}

	public static function pluginPaths()
	{
		$paths = array(
			'DS'      => DS,
			'ABSPATH' => ABSPATH,
			'DIR'     => GNETWORK_DIR,
			'URL'     => GNETWORK_URL,
		);

		self::codeTable( $paths );
	}

	// TODO: make it to the debugbar panel
	// BASED on: https://gist.github.com/ocean90/3751658
	/**
	 *  - Time
	 *  - DB Queries
	 *  - Memory Usage
	 *  - Cache Hts/Misses
	 *  - Active Plugins
	 */
	public static function infoAdvanced()
	{
		$text  = 'Time : '.timer_stop( 0 ).' | ';
		$text .= 'DB Queries: '.$GLOBALS['wpdb']->num_queries.' | ';
		$text .= 'Memory: '.number_format( ( memory_get_peak_usage() / 1024 / 1024 ), 1, ',', '' ).'/'.ini_get( 'memory_limit' ).' | ';

		$ch = empty( $GLOBALS['wp_object_cache']->cache_hits ) ? 0 : $GLOBALS['wp_object_cache']->cache_hits;
		$cm = empty( $GLOBALS['wp_object_cache']->cache_misses ) ? 0 : $GLOBALS['wp_object_cache']->cache_misses;
		$text .= 'Cache Hits: '.$ch.' | Cache Misses: '.$cm.' | ';

		$text .= 'Active Plugins: '.count( get_option( 'active_plugins' ) );

		return $text;
	}

	public function plugins_loaded()
	{
		if ( function_exists( 'supercache_admin_bar_render' ) )
			remove_action( 'wp_before_admin_bar_render', 'supercache_admin_bar_render' );
	}

	public function supercache_admin_bar_render()
	{
		global $wp_admin_bar, $wp_cache_not_logged_in;

		if ( ! is_user_logged_in() || ! $wp_cache_not_logged_in )
			return FALSE;

		if ( function_exists( 'current_user_can' )
			&& FALSE == current_user_can( 'delete_others_posts' ) )
				return FALSE;

		$wp_admin_bar->add_menu( array(
			'parent' => '',
			'id'     => 'delete-cache',
			'title'  => __( 'Delete Cache', 'wp-super-cache' ),
			'meta'   => array( 'title' => __( 'Delete cache of the current page', 'wp-super-cache' ) ),
			'href'   => wp_nonce_url( admin_url( 'index.php?action=delcachepage&path=' . urlencode( preg_replace( '/[ <>\'\"\r\n\t\(\)]/', '', $_SERVER[ 'REQUEST_URI' ] ) ) ), 'delete-cache' )
		) );
	}

	public function wp_footer()
	{
		$stat = gNetworkUtilities::stat();
		echo "\n\t<!-- {$stat} -->\n";
	}

	// BASED on : Cron Debug Log v0.1
	// https://wordpress.org/plugins/cron-debug-log/
	public function http_api_debug( $response, $type, $transport = FALSE )
	{
		if( ! $transport || 'response' != $type )
			return;

		if( is_wp_error( $response ) )
			$response = $response->get_error_message();

		if( is_array( $response )
			&& is_array( $response['response'] )
			&& ! empty( $response['response']['code'] )
			&& '200' == $response['response']['code'] )
				return;

		$time = current_time( 'mysql' );
		error_log( print_r( compact( 'time', 'response', 'type' ), TRUE ) );
	}

	// https://core.trac.wordpress.org/ticket/20316
	// http://wordpress.stackexchange.com/a/6652
	private function purge_transient_data( $site = FALSE, $time = FALSE )
	{
		global $wpdb, $_wp_using_ext_object_cache;

		if( $_wp_using_ext_object_cache )
			return;

		if ( $site ) {
			$table = $wpdb->sitemeta;
			$key = 'meta_key';
			$val = 'meta_value';
		} else {
			$table = $wpdb->options;
			$key = 'option_name';
			$val = 'option_value';
		}

		if ( $time ) {
			$timestamp = isset ( $_SERVER['REQUEST_TIME'] ) ? intval( $_SERVER['REQUEST_TIME'] ) : time();
			$query = "SELECT {$key} FROM {$table} WHERE {$key} LIKE '_transient_timeout%' AND {$val} < {$timestamp};";
		} else {
			$query = "SELECT {$key} FROM {$table} WHERE {$key} LIKE '_transient_timeout%'";
		}

		foreach( $wpdb->get_col( $query ) as $transient ) {
			$name = str_replace( '_transient_timeout_', '', $transient );
			if ( $site ) {
				delete_site_transient( $name );
			} else {
				delete_transient( $name );
			}
		}
	}

	// DRAFT
	// https://gist.github.com/markoheijnen/6157779
	// Custom error handler for catching MySQL errors
	function wp_set_error_handler()
	{
		if ( defined( 'E_DEPRECATED' ) )
				$errcontext = E_WARNING | E_DEPRECATED;
			else
				$errcontext = E_WARNING;

		set_error_handler( function( $errno, $errstr, $errfile ) {
			if ( 'wp-db.php' !== basename( $errfile ) ) {
				if ( preg_match( '/^(mysql_[a-zA-Z0-9_]+)/', $errstr, $matches ) ) {
					_doing_it_wrong( $matches[1], __( 'Please talk to the database using $wpdb' ), '3.7' );

					return apply_filters( 'wpdb_drivers_raw_mysql_call_trigger_error', TRUE );
				}
			}

			return apply_filters( 'wp_error_handler', false, $errno, $errstr, $errfile );
		}, $errcontext );
	}
}

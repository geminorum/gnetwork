<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkDebug extends gNetworkModuleCore
{

	protected $menu_key = 'debug';
	protected $network  = TRUE;

	protected function setup_actions()
	{
		gNetworkNetwork::registerMenu( 'debug',
			_x( 'Debug Logs', 'Debug Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		add_action( 'debug_bar_panels', function( $panels ) {
			require_once GNETWORK_DIR.'includes/debugbar-panel.php';
			$panels[] = new Debug_Bar_gNetwork();
			$panels[] = new Debug_Bar_gNetworkMeta();
			return $panels;
		} );

		add_action( 'wp_footer', array( $this, 'wp_footer' ), 999 );

		if ( 'production' == WP_STAGE ) {

			if ( ! WP_DEBUG_DISPLAY ) {
				add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
				add_filter( 'deprecated_function_trigger_error', '__return_false' );
				add_filter( 'deprecated_file_trigger_error', '__return_false' );
				add_filter( 'deprecated_argument_trigger_error', '__return_false' );
			}

			if ( WP_DEBUG_LOG ) {
				add_action( 'http_api_debug', array( $this, 'http_api_debug' ), 10, 5 );
				add_filter( 'wp_login_errors', array( $this, 'wp_login_errors' ), 10, 2 );
			}

			// akismet will log all the http_reqs!!
			add_filter( 'akismet_debug_log', '__return_false' );
		}
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['clear_error_log'] ) ) {
			$this->check_referer( $sub );
			self::redirect_referer( ( unlink( GNETWORK_DEBUG_LOG ) ? 'purged' : 'error' ) );
		}
	}

	public function settings_html( $uri, $sub = 'general' )
	{
		echo '<form class="gnetwork-form" method="post" action="">';

			$this->settings_fields( $sub, 'bulk' );

			// TODO: add limit input

			if ( self::displayErrorLogs() )
				$this->settings_buttons( $sub );

		echo '</form>';
	}

	protected function register_settings_buttons()
	{
		$this->register_button( 'clear_error_log', _x( 'Clear Log', 'Debug Module', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
	}

	private static function displayErrorLogs( $limit = 100, $length = 300 )
	{
		if ( file_exists( GNETWORK_DEBUG_LOG ) ) {

			if ( $errors = self::fileGetLastLines( GNETWORK_DEBUG_LOG, $limit ) ) {

				echo self::html( 'h3', sprintf( _x( 'The Last %s Errors, in Reverse Order', 'Debug Module: Error Box', GNETWORK_TEXTDOMAIN ), number_format_i18n( count( $errors ) ) ) );
				echo '<div class="error-box"><ol>';

				foreach ( $errors as $error ) {

					if ( ! trim( $error ) )
						continue;

					echo '<li>';

					$line = preg_replace_callback( '/\[([^\]]+)\]/', function( $matches ){
						return '<b><span title="'.human_time_diff( strtotime( $matches[1] ) ).'">['.$matches[1].']</span></b>';
					}, trim ( $error ), 1 );

					echo strlen( $line ) > $length ? substr( $line, 0, $length ).' [&hellip;]' : $line;

					echo '</li>';
				}

				echo '</ol></div><p>'.sprintf( _x( 'File Size: %s', 'Debug Module: Error Box', GNETWORK_TEXTDOMAIN ), self::fileGetSize( GNETWORK_DEBUG_LOG ) ).'</p>';

			} else {
				echo '<p>'._x( 'No errors currently logged.', 'Debug Module: Error Box', GNETWORK_TEXTDOMAIN ).'</p>';
			}

		} else {
			echo '<p>'._x( 'There was a problem reading the error log file.', 'Debug Module: Error Box', GNETWORK_TEXTDOMAIN ).'</p>';
			return FALSE;
		}

		return TRUE;
	}

	public static function versions()
	{
		global $wp_version,
			$wp_db_version,
			$tinymce_version,
			$required_php_version,
			$required_mysql_version;

		$versions = array(
			'wp_version'             => _x( 'WordPress', 'Debug Module: Version Strings', GNETWORK_TEXTDOMAIN ),
			'wp_db_version'          => _x( 'WordPress DB revision', 'Debug Module: Version Strings', GNETWORK_TEXTDOMAIN ),
			'tinymce_version'        => _x( 'TinyMCE', 'Debug Module: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_php_version'   => _x( 'Required PHP', 'Debug Module: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_mysql_version' => _x( 'Required MySQL', 'Debug Module: Version Strings', GNETWORK_TEXTDOMAIN ),
		);

		echo '<table class="base-table-code"><tbody>';
		foreach ( $versions as $key => $val )
			echo sprintf( '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>', $val, $$key );
		echo '</tbody></table>';
	}

	public static function gPlugin()
	{
		if ( class_exists( 'gPlugin' ) ) {
			$info = gPlugin::get_info();
			self::tableCode( $info[1] );
			self::tableSide( $info[0] );
		}
	}

	public static function cacheStats()
	{
		global $wp_object_cache;
		$wp_object_cache->stats();
	}

	public static function initialConstants()
	{
		$paths = array(
			'WP_MEMORY_LIMIT'     => WP_MEMORY_LIMIT,
			'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
			'WP_DEBUG'            => WP_DEBUG,
			'SCRIPT_DEBUG'        => SCRIPT_DEBUG,
			'WP_CONTENT_DIR'      => WP_CONTENT_DIR,
			'WP_CACHE'            => WP_CACHE,
		);

		self::tableCode( $paths );
	}

	public static function pluginPaths()
	{
		$paths = array(
			'DIRECTORY_SEPARATOR' => DIRECTORY_SEPARATOR,
			'ABSPATH'             => ABSPATH,
			'DIR'                 => GNETWORK_DIR,
			'URL'                 => GNETWORK_URL,
		);

		self::tableCode( $paths );
	}

	public static function wpUploadDIR()
	{
		$upload_dir = wp_upload_dir();
		unset( $upload_dir['error'], $upload_dir['subdir'] );
		self::tableCode( $upload_dir );
	}

	// FIXME: DRAFT
	public static function getServer()
	{
		return array(
			array(
				'name'  => 'server',
				'title' => _x( 'Server', 'Debug Module: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => array(
					'SERVER_SOFTWARE'  => _x( 'Software', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_NAME'      => _x( 'Name', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_ADMIN'     => _x( 'Admin', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_PROTOCOL'  => _x( 'Protocol', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_PORT'      => _x( 'Port', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_SIGNATURE' => _x( 'Signature', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_ADDR'      => _x( 'Address', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
				),
			),
			array(
				'name'  => 'request',
				'title' => _x( 'Request', 'Debug Module: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => array(
					'REQUEST_TIME'       => _x( 'Time', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_TIME_FLOAT' => _x( 'Time (Float)', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_METHOD'     => _x( 'Method', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_URI'        => _x( 'URI', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
				),
			),
			array(
				'name'  => 'script',
				'title' => _x( 'Script', 'Debug Module: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => array(
					'SCRIPT_NAME'     => _x( 'Name', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_FILENAME' => _x( 'Filename', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_URL'      => _x( 'URL', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_URI'      => _x( 'URI', 'Debug Module: Server Vars', GNETWORK_TEXTDOMAIN ),
				),
			),
		);
	}

	// FIXME: it's not good
	public static function dumpServer()
	{
		$server = $_SERVER;

		unset(
			$server['RAW_HTTP_COOKIE'],
			$server['HTTP_COOKIE'],
			$server['PATH']
		);

		if ( ! empty( $server['SERVER_SIGNATURE'] ) )
			$server['SERVER_SIGNATURE'] = strip_tags( $server['SERVER_SIGNATURE'] );

		// FIXME: use self::getDateDefaultFormat()
		$server['REQUEST_TIME_FLOAT'] = date( 'l, j F, Y - H:i:s T', $server['REQUEST_TIME_FLOAT'] ).' ('.$server['REQUEST_TIME_FLOAT'] .')';
		$server['REQUEST_TIME']       = date( 'l, j F, Y - H:i:s T', $server['REQUEST_TIME'] ).' ('.$server['REQUEST_TIME'] .')';

		self::tableCode( $server );
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

	public function wp_footer()
	{
		$stat = self::stat();
		echo "\n\t<!-- {$stat} -->\n";
	}

	public function http_api_debug( $response, $context, $class, $args, $url )
	{
		if ( is_wp_error( $response ) ) {
			self::log( 'HTTP API RESPONSE', array(
				'url'     => $url,
				'class'   => $class,
				// 'args'    => $args,
			), $response );
		}
	}

	public function wp_login_errors( $errors, $redirect_to )
	{
		if ( in_array( 'test_cookie', $errors->get_error_codes() ) ) {
			self::log( 'TEST COOCKIE', array(
				'message' => $errors->get_error_message( 'test_cookie' ),
			), $errors );
		}

		return $errors;
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

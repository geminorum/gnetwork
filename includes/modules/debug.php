<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Debug extends ModuleCore
{

	protected $key  = 'debug';
	protected $ajax = TRUE;

	private $http_calls = array();

	protected function setup_actions()
	{
		if ( WordPress::mustRegisterUI() )
			add_action( 'core_upgrade_preamble', array( $this, 'core_upgrade_preamble' ), 20 );

		add_filter( 'debug_bar_panels', array( $this, 'debug_bar_panels' ) );
		add_action( 'wp_footer', array( $this, 'wp_footer' ), 999 );

		add_filter( 'wp_die_handler', function( $function ){
			return array( __NAMESPACE__.'\\Debug', 'wp_die_handler' );
		} );

		$this->action( 'http_api_debug', 5 );

		if ( 'production' == WP_STAGE ) {

			if ( ! WP_DEBUG_DISPLAY ) {
				add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
				add_filter( 'deprecated_function_trigger_error', '__return_false' );
				add_filter( 'deprecated_file_trigger_error', '__return_false' );
				add_filter( 'deprecated_argument_trigger_error', '__return_false' );
			}

			// akismet will log all the http_reqs!!
			add_filter( 'akismet_debug_log', '__return_false', 20 );
		}
	}

	public function setup_menu( $context )
	{
		if ( GNETWORK_DEBUG_LOG )
			$this->register_menu(
				_x( 'Errors', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
				array( $this, 'settings' ), 'errorlogs'
			);

		if ( GNETWORK_ANALOG_LOG )
			$this->register_menu(
				_x( 'Logs', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
				( GNETWORK_DEBUG_LOG ? FALSE : array( $this, 'settings' ) ), 'analoglogs'
			);
	}

	public function settings( $sub = NULL )
	{
		if ( 'errorlogs' == $sub
			|| 'analoglogs' == $sub ) {

			if ( isset( $_POST['clear_logs'] ) ) {

				$this->check_referer( $sub );

				if ( GNETWORK_DEBUG_LOG && 'errorlogs' == $sub )
					WordPress::redirectReferer( ( @unlink( GNETWORK_DEBUG_LOG ) ? 'purged' : 'error' ) );

				else if ( GNETWORK_ANALOG_LOG && 'analoglogs' == $sub )
					WordPress::redirectReferer( ( @unlink( GNETWORK_ANALOG_LOG ) ? 'purged' : 'error' ) );
			}

			add_action( $this->settings_hook( $sub ), array( $this, 'settings_form' ), 10, 2 );

			$this->register_settings_buttons( $sub );
		}
	}

	public function settings_form( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', FALSE );

			// TODO: add limit/length input

			if ( self::displayLogs( ( 'analoglogs' == $sub ? GNETWORK_ANALOG_LOG : GNETWORK_DEBUG_LOG ) ) )
				$this->settings_buttons( $sub );

		$this->settings_form_after( $uri, $sub );
	}

	protected function register_settings_buttons( $sub = NULL )
	{
		$this->register_button( 'clear_logs', _x( 'Clear Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ), TRUE );

		// TODO: add download action/button
		// TODO: add shortcut button to update page
	}

	private static function displayLogs( $file )
	{
		if ( $file && file_exists( $file ) ) {

			if ( ! $file_size = File::getSize( $file ) )
				return FALSE;

			if ( $errors = File::getLastLines( $file, self::limit( 100 ) ) ) {

				$length = self::req( 'length', 300 );

				echo '<h3 class="error-box-header">';
					printf( _x( 'The Last %s Logs, in reverse order', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), Number::format( count( $errors ) ) );

				echo '</h3><div class="error-box"><ol>';

				foreach ( $errors as $error ) {

					if ( ! ( $line = trim( strip_tags( $error ) ) ) )
						continue;

					if ( strlen( $line ) > $length )
						$line = substr( $line, 0, $length ).' <span title="'.esc_attr( $line ).'">[&hellip;]</span>';

					$line = Utilities::highlightTime( $line, 1 );
					$line = Utilities::highlightIP( $line );

					echo '<li>'.$line.'</li>';
				}

				echo '</ol></div><p class="error-box-footer description">'.sprintf( _x( 'File Size: %s', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), $file_size ).'</p>';

			} else {
				self::warning( _x( 'No information currently logged.', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), TRUE );
				return FALSE;
			}

		} else {
			self::error( _x( 'There was a problem reading the log file.', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), TRUE );
			return FALSE;
		}

		return TRUE;
	}

	public function get_http_calls()
	{
		return $this->http_calls;
	}

	public static function versions()
	{
		global $wp_version,
			$wp_db_version,
			$tinymce_version,
			$required_php_version,
			$required_mysql_version;

		$versions = array(
			'wp_version'             => _x( 'WordPress', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'wp_db_version'          => _x( 'WordPress DB revision', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'tinymce_version'        => _x( 'TinyMCE', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_php_version'   => _x( 'Required PHP', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_mysql_version' => _x( 'Required MySQL', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
		);

		echo '<table class="base-table-code"><tbody>';
		foreach ( $versions as $key => $val )
			echo sprintf( '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>', $val, ${$key} );
		echo '</tbody></table>';
	}

	public static function gPlugin()
	{
		if ( class_exists( 'gPlugin' ) ) {

			$info = \gPlugin::get_info();

			HTML::tableCode( $info[1], TRUE );
			HTML::tableSide( $info[0] );

		} else {
			HTML::desc( _x( 'No Instance of gPlugin found.', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
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
			'WP_POST_REVISIONS'   => WP_POST_REVISIONS,
			'EMPTY_TRASH_DAYS'    => EMPTY_TRASH_DAYS,
			'AUTOSAVE_INTERVAL'   => AUTOSAVE_INTERVAL,
		);

		HTML::tableCode( $paths );
	}

	public static function pluginPaths()
	{
		$paths = array(
			'ABSPATH'       => ABSPATH,
			'DIR'           => GNETWORK_DIR,
			'URL'           => GNETWORK_URL,
			'FILE'          => GNETWORK_FILE,
			'DL_DIR'        => GNETWORK_DL_DIR,
			'DL_URL'        => GNETWORK_DL_URL,
			'DEBUG_LOG'     => GNETWORK_DEBUG_LOG,
			'ANALOG_LOG'    => GNETWORK_ANALOG_LOG,
			'MAIL_LOG_DIR'  => GNETWORK_MAIL_LOG_DIR,
			'AJAX_ENDPOINT' => GNETWORK_AJAX_ENDPOINT,
		);

		HTML::tableCode( $paths );
	}

	public static function currentTime( $format = 'Y-m-d H:i:s' )
	{
		$times = array(
			'date_i18n()'                     => date_i18n( $format ),
			'date_i18n() UTC'                 => date_i18n( $format, FALSE, TRUE ),
			'date_default_timezone_get()'     => date_default_timezone_get(),
			'date(\'e\')'                     => date( 'e' ),
			'date(\'T\')'                     => date( 'T' ),
			'ini_get(\'date.timezone\')'      => ini_get( 'date.timezone' ),
			'get_option(\'gmt_offset\')'      => get_option( 'gmt_offset' ),
			'get_option(\'timezone_string\')' => get_option( 'timezone_string' ),
			'REQUEST_TIME_FLOAT'              => date( $format, $_SERVER['REQUEST_TIME_FLOAT'] ),
			'REQUEST_TIME'                    => date( $format, $_SERVER['REQUEST_TIME'] ),
		);

		HTML::tableCode( $times );
	}

	public static function summaryIPs( $caption = FALSE )
	{
		$summary = array();

		foreach ( array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		) as $key )
			if ( isset( $_SERVER[$key] ) )
				$summary[$key] = gnetwork_ip_lookup( $_SERVER[$key] );

		HTML::tableCode( $summary, FALSE, $caption );
	}

	public static function wpUploadDIR()
	{
		$upload_dir = wp_upload_dir();
		unset( $upload_dir['error'], $upload_dir['subdir'] );
		HTML::tableCode( $upload_dir );
	}

	// FIXME: DRAFT
	public static function getServer()
	{
		return array(
			array(
				'name'  => 'server',
				'title' => _x( 'Server', 'Modules: Debug: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => array(
					'SERVER_SOFTWARE'  => _x( 'Software', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_NAME'      => _x( 'Name', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_ADMIN'     => _x( 'Admin', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_PROTOCOL'  => _x( 'Protocol', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_PORT'      => _x( 'Port', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_SIGNATURE' => _x( 'Signature', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_ADDR'      => _x( 'Address', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
				),
			),
			array(
				'name'  => 'request',
				'title' => _x( 'Request', 'Modules: Debug: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => array(
					'REQUEST_TIME'       => _x( 'Time', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_TIME_FLOAT' => _x( 'Time (Float)', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_METHOD'     => _x( 'Method', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_URI'        => _x( 'URI', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
				),
			),
			array(
				'name'  => 'script',
				'title' => _x( 'Script', 'Modules: Debug: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => array(
					'SCRIPT_NAME'     => _x( 'Name', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_FILENAME' => _x( 'Filename', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_URL'      => _x( 'URL', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_URI'      => _x( 'URI', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
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
			$server['PATH'],
			$server['HTTP_CLIENT_IP'],
			$server['HTTP_X_FORWARDED_FOR'],
			$server['HTTP_X_FORWARDED'],
			$server['HTTP_FORWARDED_FOR'],
			$server['HTTP_FORWARDED'],
			$server['REMOTE_ADDR']
		);

		if ( ! empty( $server['SERVER_SIGNATURE'] ) )
			$server['SERVER_SIGNATURE'] = strip_tags( $server['SERVER_SIGNATURE'] );

		$server['REQUEST_TIME_FLOAT'] = date( 'l, j F, Y - H:i:s T', $server['REQUEST_TIME_FLOAT'] ).' ('.$server['REQUEST_TIME_FLOAT'] .')';
		$server['REQUEST_TIME']       = date( 'l, j F, Y - H:i:s T', $server['REQUEST_TIME'] ).' ('.$server['REQUEST_TIME'] .')';

		HTML::tableCode( $server );
	}

	private static function get_phpinfo()
	{
		if ( self::isFuncDisabled( 'phpinfo' ) )
			return FALSE;

		ob_start();
		@phpinfo();

		if ( ! $info = ob_get_clean() )
			return FALSE;

		$dom = new \domDocument;
		$dom->loadHTML( $info );

		$body = $dom->documentElement->lastChild;
		return $dom->saveHTML( $body );
	}

	public static function phpinfo()
	{
		if ( $phpinfo = self::get_phpinfo() ) {
			echo '<div class="-phpinfo-wrap">';
				echo $phpinfo;
			echo '</div>';
		} else {
			echo '<div class="-phpinfo-disabled description">';
				_ex( '<code>phpinfo()</code> has been disabled.', 'Modules: Debug', GNETWORK_TEXTDOMAIN );
			echo '</div>';
		}
	}

	public static function summaryPHP()
	{
		HTML::desc( sprintf( _x( 'Current PHP version: <code>%s</code>', 'Modules: Debug', GNETWORK_TEXTDOMAIN ), phpversion() ) );

		HTML::listCode( self::getPHPExtensions(),
			'<code title="%2$s">%1$s</code>',
			'<span class="description -success">'._x( 'Loaded Extensions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ).':</span>'
		);

		HTML::listCode( self::getPHPMissingExtensions(),
			'<code title="%2$s">%1$s</code>',
			'<span class="description -danger">'._x( 'Missing Extensions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ).':</span>'
		);
	}

	public static function getPHPExtensions()
	{
		$extensions = array();

		foreach ( get_loaded_extensions() as $ext ) {

			if ( 'core' == strtolower( $ext ) )
				continue;

			if ( $ver = phpversion( $ext ) )
				$extensions[$ext] = 'v'.esc_attr( $ver );
			else
				$extensions[$ext] = '';
		}

		return $extensions;
	}

	public static function getPHPMissingExtensions()
	{
		$extensions = array(
			'intl'      => 'Internationalization',
			'zip'       => 'Zip',
			'curl'      => 'cURL',
			'json'      => 'JSON',
			'xml'       => 'XML',
			'libxml'    => 'libXML',
			'openssl'   => 'OpenSSL',
			'PDO'       => 'PDO',
			'mbstring'  => 'Mbstring',
			'tokenizer' => 'Tokenizer',
		);

		foreach ( $extensions as $ext => $why )
			if ( extension_loaded( $ext ) )
				unset( $extensions[$ext] );

		return $extensions;
	}

	public function debug_bar_panels( $panels )
	{
		if ( file_exists( GNETWORK_DIR.'includes/misc/debug-debugbar.php' ) ) {
			require_once( GNETWORK_DIR.'includes/misc/debug-debugbar.php' );
			$panels[] = new Debug_Bar_gNetwork();
		}

		if ( file_exists( GNETWORK_DIR.'includes/misc/debug-debugbar-meta.php' ) ) {
			require_once( GNETWORK_DIR.'includes/misc/debug-debugbar-meta.php' );
			$panels[] = new Debug_Bar_gNetworkMeta();
		}

		return $panels;
	}

	public function wp_footer()
	{
		$stat = self::stat();
		echo "\n\t<!-- {$stat} -->\n";
	}

	public function http_api_debug( $response, $context, $class, $args, $url )
	{
		if ( self::isError( $response ) )
			Logger::ERROR( 'HTTP-API: '.$class.': '.$response->get_error_message().' - '.$url );

		if ( WordPress::isSuperAdmin() )
			$this->http_calls[] = array(
				'class' => $class,
				'url'   => $url,
			);
	}

	public function core_upgrade_preamble()
	{
		if ( ! GNETWORK_DEBUG_LOG && ! GNETWORK_ANALOG_LOG )
			return;

		HTML::h2( _x( 'Extras', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		echo '<p class="gnetwork-admin-wrap debug-update-core">';

			if ( GNETWORK_DEBUG_LOG )
				echo HTML::tag( 'a', array(
					'class' => 'button button-secondary',
					'href'  => Settings::subURL( 'errorlogs' ),
				), _x( 'Check Errors', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			if ( GNETWORK_DEBUG_LOG && GNETWORK_ANALOG_LOG )
				echo '&nbsp;&nbsp;';

			if ( GNETWORK_ANALOG_LOG )
				echo HTML::tag( 'a', array(
					'class' => 'button button-secondary',
					'href'  => Settings::subURL( 'analoglogs' ),
				), _x( 'Check Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		echo '</p>';
	}

	public static function wp_die_handler( $message, $title = '', $args = array() )
	{
		$r = wp_parse_args( $args, array(
			'response' => 500,
		) );

		$have_gettext = function_exists( '__' );

		if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {

			if ( empty( $title ) ) {
				$error_data = $message->get_error_data();
				if ( is_array( $error_data ) && isset( $error_data['title'] ) )
					$title = $error_data['title'];
			}

			$errors = $message->get_error_messages();

			switch ( count( $errors ) ) {
				case 0: $message = ''; break;
				case 1: $message = "<p>{$errors[0]}</p>"; break;
				default: $message = "<ul>\n\t\t<li>".join( "</li>\n\t\t<li>", $errors )."</li>\n\t</ul>";
			}

		} else if ( is_string( $message ) ) {
			$message = "<p>$message</p>";
		}

		if ( isset( $r['back_link'] ) && $r['back_link'] ) {
			$back_text = $have_gettext ? __( '&laquo; Back' ) : '&laquo; Back';
			$message .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
		}

		if ( ! did_action( 'admin_head' ) ) {

			if ( ! headers_sent() ) {
				status_header( $r['response'] );
				nocache_headers();
				header( 'Content-Type: text/html; charset=utf-8' );
			}

			if ( empty( $title ) )
				$title = $have_gettext ? __( 'WordPress &rsaquo; Error' ) : 'WordPress &rsaquo; Error';

			$text_direction = 'ltr';

			if ( isset( $r['text_direction'] ) && 'rtl' == $r['text_direction'] )
				$text_direction = 'rtl';

			else if ( function_exists( 'is_rtl' ) && is_rtl() )
				$text_direction = 'rtl';

			echo '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" ';

			if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) )
				language_attributes();

			else
				echo 'dir="'.$text_direction.'"';

			echo '><head>';
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
			echo '<meta name="viewport" content="width=device-width">';

			if ( function_exists( 'wp_no_robots' ) )
				wp_no_robots();

			echo '<title>'.$title.'</title>';

			Utilities::linkStyleSheet( GNETWORK_URL.'assets/css/die.all.css' );
			Utilities::customStyleSheet( 'die.css' );

			echo '</head><body id="error-page">';
		} // ! did_action( 'admin_head' )

		echo $message;
		echo '</body></html>';

		die();
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

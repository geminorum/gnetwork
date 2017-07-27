<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Debug extends gNetwork\Module
{

	protected $key  = 'debug';
	protected $ajax = TRUE;

	private $http_calls = [];

	protected function setup_actions()
	{
		if ( WordPress::mustRegisterUI() )
			$this->action( 'core_upgrade_preamble', 1, 20 );

		$this->filter( 'debug_bar_panels' );
		$this->action( 'wp_footer', 1, 999 );

		add_filter( 'wp_die_handler', function( $function ){
			return [ __NAMESPACE__.'\\Debug', 'wp_die_handler' ];
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
		$this->register_menu(
			_x( 'System Report', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ], 'systemreport', NULL, 5
		);

		$this->register_menu(
			_x( 'Remote Tests', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			FALSE, 'remotetests'
		);

		if ( GNETWORK_DEBUG_LOG )
			$this->register_menu(
				_x( 'Errors', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
				FALSE, 'errorlogs', NULL, 20
			);

		if ( GNETWORK_ANALOG_LOG )
			$this->register_menu(
				_x( 'Logs', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
				FALSE, 'analoglogs', NULL, 20
			);
	}

	public function settings( $sub = NULL )
	{
		if ( 'systemreport' == $sub
			|| 'remotetests' == $sub ) {

			add_action( $this->settings_hook( $sub ), [ $this, 'settings_form' ], 10, 2 );

		} else if ( 'errorlogs' == $sub
			|| 'analoglogs' == $sub ) {

			if ( isset( $_POST['clear_logs'] ) ) {

				$this->check_referer( $sub );

				if ( GNETWORK_DEBUG_LOG && 'errorlogs' == $sub )
					WordPress::redirectReferer( ( @unlink( GNETWORK_DEBUG_LOG ) ? 'purged' : 'error' ) );

				else if ( GNETWORK_ANALOG_LOG && 'analoglogs' == $sub )
					WordPress::redirectReferer( ( @unlink( GNETWORK_ANALOG_LOG ) ? 'purged' : 'error' ) );

			} else if ( isset( $_POST['download_logs'] ) ) {

				if ( GNETWORK_DEBUG_LOG && 'errorlogs' == $sub )
					File::download( GNETWORK_DEBUG_LOG, File::prepName( 'debug.log' ) );

				else if ( GNETWORK_ANALOG_LOG && 'analoglogs' == $sub )
					File::download( GNETWORK_ANALOG_LOG, File::prepName( 'analog.log' ) );

				WordPress::redirectReferer( 'wrong' );
			}

			add_action( $this->settings_hook( $sub ), [ $this, 'settings_form' ], 10, 2 );

			$this->register_settings_buttons( $sub );
		}
	}

	public function settings_form( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', FALSE );

		if ( 'systemreport' == $sub ) {

			HTML::h3( _x( 'System Report', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ) );

			HTML::desc( _x( 'Below you can find various raw information about current server and WordPress installation.', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ) );

			HTML::tabsList( [
				'php' => [
					'title'  => _x( 'PHP', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'     => [ __CLASS__, 'summaryPHP' ],
					'active' => TRUE,
				],
				'wordpress' => [
					'title' => _x( 'WordPress', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'versions' ],
				],
				'time' => [
					'title' => _x( 'Time', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'currentTime' ],
				],
				'ip' => [
					'title' => _x( 'IP', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => function(){ self::summaryIPs(); },
				],
				'constants' => [
					'title' => _x( 'Constants', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'initialConstants' ],
				],
				'paths' => [
					'title' => _x( 'Paths', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'pluginPaths' ],
				],
				'upload' => [
					'title' => _x( 'Upload', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'wpUploadDIR' ],
				],
				'server' => [
					'title' => _x( 'SERVER', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'dumpServer' ],
				],
				'gplugin' => [
					'title' => _x( 'gPlugin', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'gPlugin' ],
				],
				'htaccess' => [
					'title' => _x( 'htaccess', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'htaccessSummary' ],
				],
				'custom' => [
					'title' => _x( 'Custom', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'customSummary' ],
				],
				'phpinfo' => [
					'title' => _x( 'PHP Info', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
					'cb'    => [ __CLASS__, 'phpinfo' ],
				],
			] );

		} else if ( 'remotetests' == $sub ) {

			// TODO: display remote tests summary

		} else {

			// TODO: add limit/length input

			if ( self::displayLogs( ( 'analoglogs' == $sub ? GNETWORK_ANALOG_LOG : GNETWORK_DEBUG_LOG ) ) )
				$this->settings_buttons( $sub );
		}

		$this->settings_form_after( $uri, $sub );
	}

	protected function register_settings_buttons( $sub = NULL )
	{
		$this->register_button( 'clear_logs', _x( 'Clear Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
		$this->register_button( 'download_logs', _x( 'Download Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
	}

	private static function displayLogs( $file )
	{
		if ( $file && file_exists( $file ) ) {

			if ( ! $file_size = File::getSize( $file ) )
				return FALSE;

			if ( $errors = File::getLastLines( $file, self::limit( 100 ) ) ) {

				$length = self::req( 'length', 300 );

				HTML::h3( sprintf( _x( 'The Last %s Logs, in reverse order', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), Number::format( count( $errors ) ) ), 'error-box-header' );

				echo '<div class="error-box"><ol>';

				foreach ( $errors as $error ) {

					if ( ! ( $line = trim( strip_tags( $error ) ) ) )
						continue;

					if ( strlen( $line ) > $length )
						$line = substr( $line, 0, $length ).' <span title="'.esc_attr( $line ).'">[&hellip;]</span>';

					$line = Utilities::highlightTime( $line, 1 );
					$line = Utilities::highlightIP( $line );

					echo '<li>'.$line.'</li>';
				}

				echo '</ol></div>';
				HTML::desc( sprintf( _x( 'File Size: %s', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), $file_size ), TRUE, 'error-box-footer' );

			} else {
				echo gNetwork()->na();
				return FALSE;
			}

		} else {
			echo HTML::error( _x( 'There was a problem reading the log file.', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ) );
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

		$versions = [
			'wp_version'             => _x( 'WordPress', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'wp_db_version'          => _x( 'WordPress DB revision', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'tinymce_version'        => _x( 'TinyMCE', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_php_version'   => _x( 'Required PHP', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_mysql_version' => _x( 'Required MySQL', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
		];

		echo '<table class="base-table-code"><tbody>';
		foreach ( $versions as $key => $val )
			echo sprintf( '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>', $val, ${$key} );
		echo '</tbody></table>';
	}

	public static function gPlugin()
	{
		if ( class_exists( 'gPlugin' ) ) {

			$info = \gPlugin::get_info();

			echo HTML::tableCode( $info[1], TRUE );
			HTML::tableSide( $info[0] );

		} else {
			HTML::desc( _x( 'No Instance of gPlugin found.', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
		}
	}

	public static function htaccessSummary()
	{
		HTML::preCode( File::getContents( trailingslashit( get_home_path() ).'.htaccess' ) );
	}

	// FIXME: DRAFT: not used
	public static function htaccessAppend( $block, $content )
	{
		return insert_with_markers( trailingslashit( get_home_path() ).'.htaccess', $block, $content );
	}

	public static function customSummary()
	{
		if ( file_exists( WP_CONTENT_DIR.'/gnetwork-custom.php' ) )
			HTML::preCode( File::getContents( WP_CONTENT_DIR.'/gnetwork-custom.php' ) );
		else
			HTML::desc( _x( 'No custom file found.', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
	}

	public static function cacheStats()
	{
		global $wp_object_cache;
		$wp_object_cache->stats();
	}

	public static function initialConstants()
	{
		$paths = [
			'WP_MEMORY_LIMIT'     => WP_MEMORY_LIMIT,
			'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
			'WP_DEBUG'            => WP_DEBUG,
			'SCRIPT_DEBUG'        => SCRIPT_DEBUG,
			'WP_CONTENT_DIR'      => WP_CONTENT_DIR,
			'WP_CACHE'            => WP_CACHE,
			'WP_POST_REVISIONS'   => WP_POST_REVISIONS,
			'EMPTY_TRASH_DAYS'    => EMPTY_TRASH_DAYS,
			'AUTOSAVE_INTERVAL'   => AUTOSAVE_INTERVAL,
		];

		echo HTML::tableCode( $paths );
	}

	public static function pluginPaths()
	{
		$paths = [
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
		];

		echo HTML::tableCode( $paths );
	}

	public static function currentTime()
	{
		$format = 'Y-m-d H:i:s';

		$times = [
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
		];

		echo HTML::tableCode( $times );
	}

	public static function summaryIPs( $caption = FALSE )
	{
		$summary = [];

		foreach ( [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		] as $key )
			if ( isset( $_SERVER[$key] ) )
				$summary[$key] = gnetwork_ip_lookup( $_SERVER[$key] );

		echo HTML::tableCode( $summary, FALSE, $caption );
	}

	public static function wpUploadDIR()
	{
		$upload_dir = wp_upload_dir();
		unset( $upload_dir['error'], $upload_dir['subdir'] );
		echo HTML::tableCode( $upload_dir );
	}

	// FIXME: DRAFT
	public static function getServer()
	{
		return [
			[
				'name'  => 'server',
				'title' => _x( 'Server', 'Modules: Debug: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => [
					'SERVER_SOFTWARE'  => _x( 'Software', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_NAME'      => _x( 'Name', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_ADMIN'     => _x( 'Admin', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_PROTOCOL'  => _x( 'Protocol', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_PORT'      => _x( 'Port', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_SIGNATURE' => _x( 'Signature', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SERVER_ADDR'      => _x( 'Address', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
				],
			],
			[
				'name'  => 'request',
				'title' => _x( 'Request', 'Modules: Debug: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => [
					'REQUEST_TIME'       => _x( 'Time', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_TIME_FLOAT' => _x( 'Time (Float)', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_METHOD'     => _x( 'Method', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'REQUEST_URI'        => _x( 'URI', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
				],
			],
			[
				'name'  => 'script',
				'title' => _x( 'Script', 'Modules: Debug: Server Vars Group', GNETWORK_TEXTDOMAIN ),
				'keys'  => [
					'SCRIPT_NAME'     => _x( 'Name', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_FILENAME' => _x( 'Filename', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_URL'      => _x( 'URL', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
					'SCRIPT_URI'      => _x( 'URI', 'Modules: Debug: Server Vars', GNETWORK_TEXTDOMAIN ),
				],
			],
		];
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

		echo HTML::tableCode( $server );
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

		echo HTML::listCode( self::getPHPExtensions(),
			'<code title="%2$s">%1$s</code>',
			'<span class="description -success">'._x( 'Loaded Extensions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ).':</span>'
		);

		echo HTML::listCode( self::getPHPMissingExtensions(),
			'<code title="%2$s">%1$s</code>',
			'<span class="description -danger">'._x( 'Missing Extensions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ).':</span>'
		);
	}

	public static function getPHPExtensions()
	{
		$extensions = [];

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
		$extensions = [
			'intl'      => 'Internationalization Functions',
			'zip'       => 'Zip',
			'curl'      => 'Client URL Library',
			'json'      => 'JavaScript Object Notation',
			'xml'       => 'XML Parser',
			'libxml'    => 'libXML',
			'openssl'   => 'OpenSSL',
			'PDO'       => 'PHP Data Objects',
			'mbstring'  => 'Multibyte String',
			'tokenizer' => 'Tokenizer',
			'mcrypt'    => 'Mcrypt',
			'pcre'      => 'Perl Compatible Regular Expressions',
			'imagick'   => 'Image Processing (ImageMagick)',
			'gmagick'   => 'Gmagick',
		];

		foreach ( $extensions as $ext => $why )
			if ( extension_loaded( $ext ) )
				unset( $extensions[$ext] );

		return $extensions;
	}

	public function debug_bar_panels( $panels )
	{
		if ( file_exists( GNETWORK_DIR.'includes/misc/debug-debugbar.php' ) ) {
			require_once( GNETWORK_DIR.'includes/misc/debug-debugbar.php' );
			$panels[] = new \geminorum\gNetwork\Misc\Debug_Bar_gNetwork();
		}

		if ( file_exists( GNETWORK_DIR.'includes/misc/debug-debugbar-meta.php' ) ) {
			require_once( GNETWORK_DIR.'includes/misc/debug-debugbar-meta.php' );
			$panels[] = new \geminorum\gNetwork\Misc\Debug_Bar_gNetworkMeta();
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
			$this->http_calls[] = [
				'class' => $class,
				'url'   => $url,
			];
	}

	public function core_upgrade_preamble()
	{
		if ( ! GNETWORK_DEBUG_LOG && ! GNETWORK_ANALOG_LOG )
			return;

		HTML::h2( _x( 'Extras', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		echo '<p class="gnetwork-admin-wrap debug-update-core">';

			if ( GNETWORK_DEBUG_LOG )
				echo HTML::tag( 'a', [
					'class' => 'button button-secondary',
					'href'  => Settings::subURL( 'errorlogs' ),
				], _x( 'Check Errors', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			if ( GNETWORK_DEBUG_LOG && GNETWORK_ANALOG_LOG )
				echo '&nbsp;&nbsp;';

			if ( GNETWORK_ANALOG_LOG )
				echo HTML::tag( 'a', [
					'class' => 'button button-secondary',
					'href'  => Settings::subURL( 'analoglogs' ),
				], _x( 'Check Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		echo '</p>';
	}

	public static function wp_die_handler( $message, $title = '', $args = [] )
	{
		$r = self::args( $args, [
			'response' => 500,
		] );

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

			Utilities::linkStyleSheet( 'die.all.css' );
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

			return apply_filters( 'wp_error_handler', FALSE, $errno, $errstr, $errfile );
		}, $errcontext );
	}
}

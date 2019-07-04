<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Debug extends gNetwork\Module
{

	protected $key        = 'debug';
	protected $ajax       = TRUE;
	protected $cron       = TRUE;
	protected $installing = TRUE;

	private $http_calls = [];

	protected function setup_actions()
	{
		if ( WordPress::mustRegisterUI() )
			$this->action( 'core_upgrade_preamble', 1, 20 );

		$this->filter( 'debug_bar_panels' );
		$this->action( 'wp_footer', 1, 999 );

		add_filter( 'wp_die_handler', function() {
			return [ $this, 'wp_die_handler' ];
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

		// @REF: https://core.trac.wordpress.org/ticket/22430#comment:4
		remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );

		$this->action( 'shutdown', 1, 99999 );
	}

	public function setup_menu( $context )
	{
		$this->register_tool(
			_x( 'Remote Tests', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			NULL, 'remotetests'
		);

		if ( ! is_multisite() )
			Admin::registerTool( 'systemreport', _x( 'System Report', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );

		if ( GNETWORK_DEBUG_LOG )
			$this->register_tool(
				_x( 'Error Logs', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
				FALSE, 'errorlogs', NULL, 20
			);

		if ( GNETWORK_ANALOG_LOG )
			$this->register_tool(
				_x( 'System Logs', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
				FALSE, 'analoglogs', NULL, 20
			);

		if ( GNETWORK_FAILED_LOG )
			$this->register_tool(
				_x( 'Failed Logs', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
				FALSE, 'failedlogs', NULL, 20
			);
	}

	public function tools( $sub = NULL, $key = NULL )
	{
		if ( in_array( $sub, [ 'systemreport', 'remotetests', 'errorlogs', 'analoglogs', 'failedlogs' ] ) )
			parent::tools( $sub, TRUE );
	}

	protected function tools_buttons( $sub = NULL )
	{
		if ( in_array( $sub, [ 'errorlogs', 'analoglogs', 'failedlogs' ] ) ) {
			$this->register_button( 'clear_logs', _x( 'Clear Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
			$this->register_button( 'download_logs', _x( 'Download Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
		}
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( isset( $_POST['clear_logs'] ) ) {

			$this->check_referer( $sub );

			if ( GNETWORK_DEBUG_LOG && 'errorlogs' == $sub )
				WordPress::redirectReferer( ( @unlink( GNETWORK_DEBUG_LOG ) ? 'purged' : 'error' ) );

			else if ( GNETWORK_ANALOG_LOG && 'analoglogs' == $sub )
				WordPress::redirectReferer( ( @unlink( GNETWORK_ANALOG_LOG ) ? 'purged' : 'error' ) );

			else if ( GNETWORK_FAILED_LOG && 'failedlogs' == $sub )
				WordPress::redirectReferer( ( @unlink( GNETWORK_FAILED_LOG ) ? 'purged' : 'error' ) );

		} else if ( isset( $_POST['download_logs'] ) ) {

			if ( GNETWORK_DEBUG_LOG && 'errorlogs' == $sub )
				File::download( GNETWORK_DEBUG_LOG, File::prepName( 'debug.log' ) );

			else if ( GNETWORK_ANALOG_LOG && 'analoglogs' == $sub )
				File::download( GNETWORK_ANALOG_LOG, File::prepName( 'analog.log' ) );

			else if ( GNETWORK_FAILED_LOG && 'failedlogs' == $sub )
				File::download( GNETWORK_FAILED_LOG, File::prepName( 'failed.log' ) );

			WordPress::redirectReferer( 'wrong' );
		}
	}

	public function render_tools( $uri, $sub = 'general' )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'tools' );

		if ( 'systemreport' == $sub ) {

			self::displayReport();

		} else if ( 'remotetests' == $sub ) {

			if ( self::displayTests() )
				$this->render_form_buttons( $sub );

		} else {

			$map = [
				'errorlogs'  => GNETWORK_DEBUG_LOG,
				'analoglogs' => GNETWORK_ANALOG_LOG,
				'failedlogs' => GNETWORK_FAILED_LOG,
			];

			if ( self::displayLogs( $map[$sub] ) )
				$this->render_form_buttons( $sub );
		}

		$this->render_form_end( $uri, $sub );
	}

	public static function displayReport()
	{
		HTML::desc( _x( 'Below you can find various raw information about current server and WordPress installation.', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ) );

		HTML::tabsList( [
			'currents' => [
				'title'  => _x( 'Currents', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
				'cb'     => [ __CLASS__, 'summaryCurrents' ],
				'active' => TRUE,
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
			'wpconfig' => [
				'title' => _x( 'WP-Config', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __CLASS__, 'wpconfigSummary' ],
			],
			'custom' => [
				'title' => _x( 'Network Custom', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __CLASS__, 'customSummary' ],
			],
			'bp_custom' => [
				'title' => _x( 'BuddyPress Custom', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __CLASS__, 'bpCustomSummary' ],
			],
			'phpinfo' => [
				'title' => _x( 'PHP Info', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __CLASS__, 'phpinfo' ],
			],
			'phpfuncs' => [
				'title' => _x( 'PHP Functions', 'Modules: Debug: System Report', GNETWORK_TEXTDOMAIN ),
				'cb'    => [ __CLASS__, 'phpFunctions' ],
			],
		] );

		Scripts::enqueueScriptVendor( 'prism' );
		Scripts::enqueueMasonry();
	}

	// FIXME!
	private static function displayTests()
	{
		HTML::h3( _x( 'Website Remote Tests', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
		HTML::desc( _x( 'No tests available, yet!', 'Modules: Debug', GNETWORK_TEXTDOMAIN ), TRUE, '-empty' );
	}

	// TODO: add limit/length input
	private static function displayLogs( $file )
	{
		if ( $file && is_readable( $file ) ) {

			if ( ! $file_size = File::getSize( $file ) )
				return FALSE;

			if ( $logs = File::getLastLines( $file, self::limit( 100 ) ) ) {

				$length = self::req( 'length', FALSE );

				HTML::h3( sprintf( _x( 'The Last %s Logs, in reverse order', 'Modules: Debug: Log Box', GNETWORK_TEXTDOMAIN ), Number::format( count( $logs ) ) ), 'log-box-header' );

				echo '<div class="log-box"><ol>';

				foreach ( $logs as $log ) {

					if ( ! ( $line = trim( strip_tags( $log ) ) ) )
						continue;

					if ( $length && Text::strLen( $line ) > $length )
						$line = Text::subStr( $line, 0, $length ).' <span title="'.HTML::escape( $line ).'">[&hellip;]</span>';

					$line = Utilities::highlightTime( $line, 1 );
					$line = Utilities::highlightIP( $line );

					echo HTML::tag( 'li', $line );
				}

				echo '</ol></div>';
				HTML::desc( sprintf( _x( 'File Size: %s', 'Modules: Debug: Log Box', GNETWORK_TEXTDOMAIN ), HTML::wrapLTR( $file_size ) ), TRUE, 'log-box-footer' );

			} else {
				echo gNetwork()->na();
				return FALSE;
			}

		} else {
			echo HTML::error( _x( 'There was a problem reading the logs.', 'Modules: Debug: Log Box', GNETWORK_TEXTDOMAIN ) );
			return FALSE;
		}

		return TRUE;
	}

	public function get_http_calls()
	{
		return $this->http_calls;
	}

	public static function summaryWordPress()
	{
		$versions = [
			'wp_version'             => _x( 'WordPress', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'wp_db_version'          => _x( 'WordPress DB revision', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'tinymce_version'        => _x( 'TinyMCE', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_php_version'   => _x( 'Required PHP', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_mysql_version' => _x( 'Required MySQL', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
		];

		echo '<div class="-wrap card -floated" dir="ltr">';
		HTML::h2( _x( 'Core Versions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		echo '<table class="base-table-code"><tbody>';
		foreach ( $versions as $key => $val )
			echo sprintf( '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>', $val, $GLOBALS[$key] );
		echo '</tbody></table>';

		echo '</div>';
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
		echo '<pre data-prism="yes" class="language-apacheconf line-numbers" dir="ltr"><code class="language-apacheconf">';
			echo HTML::escapeTextarea( File::getContents( trailingslashit( get_home_path() ).'.htaccess' ) );
		echo '</code></pre>';
	}

	// FIXME: DRAFT: not used
	public static function htaccessAppend( $block, $content )
	{
		return insert_with_markers( trailingslashit( get_home_path() ).'.htaccess', $block, $content );
	}

	public static function wpconfigSummary()
	{
		if ( $wpconfig = WordPress::getConfigPHP() )
			echo '<pre data-prism="yes" class="language-php line-numbers" dir="ltr"><code class="language-php">'
				.HTML::escapeTextarea( File::getContents( $wpconfig ) ).'</code></pre>';
		else
			HTML::desc( _x( 'Can not find the config file!', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
	}

	public static function customSummary()
	{
		if ( file_exists( WP_CONTENT_DIR.'/gnetwork-custom.php' ) )
			echo '<pre data-prism="yes" class="language-php line-numbers" dir="ltr"><code class="language-php">'
				.HTML::escapeTextarea( File::getContents( WP_CONTENT_DIR.'/gnetwork-custom.php' ) ).'</code></pre>';
		else
			HTML::desc( _x( 'No custom file found.', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
	}

	public static function bpCustomSummary()
	{
		if ( file_exists( WP_PLUGIN_DIR.'/bp-custom.php' ) )
			echo '<pre data-prism="yes" class="language-php line-numbers" dir="ltr"><code class="language-php">'
				.HTML::escapeTextarea( File::getContents( WP_PLUGIN_DIR.'/bp-custom.php' ) ).'</code></pre>';
		else
			HTML::desc( _x( 'No bp custom file found.', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
	}

	public static function cacheStats()
	{
		echo '<div class="-wrap card -floated" dir="ltr">';
		HTML::h2( _x( 'Stats of the Cache', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			$GLOBALS['wp_object_cache']->stats();
		echo '</div>';
	}

	public static function initialConstants()
	{
		$paths = [
			'WP_MEMORY_LIMIT'     => WP_MEMORY_LIMIT,
			'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
			'WP_LOCAL_DEV'        => @constant( 'WP_LOCAL_DEV' ),
			'WP_DEBUG'            => WP_DEBUG,
			'WP_DEBUG_DISPLAY'    => WP_DEBUG_DISPLAY,
			'WP_DEBUG_LOG'        => WP_DEBUG_LOG,
			'SCRIPT_DEBUG'        => SCRIPT_DEBUG,
			'CONCATENATE_SCRIPTS' => @constant( 'CONCATENATE_SCRIPTS' ),
			'COMPRESS_SCRIPTS'    => @constant( 'COMPRESS_SCRIPTS' ),
			'COMPRESS_CSS'        => @constant( 'COMPRESS_CSS' ),
			'WP_CACHE'            => WP_CACHE,
			'WP_CONTENT_DIR'      => WP_CONTENT_DIR,
			'WP_POST_REVISIONS'   => WP_POST_REVISIONS,
			'EMPTY_TRASH_DAYS'    => EMPTY_TRASH_DAYS,
			'AUTOSAVE_INTERVAL'   => AUTOSAVE_INTERVAL,
		];

		echo '<div class="-wrap card -floated" dir="ltr">';
		HTML::h2( _x( 'Initial Constants', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			echo HTML::tableCode( $paths );
		echo '</div>';
	}

	public static function pluginPaths()
	{
		$paths = [
			'ABSPATH'       => ABSPATH,
			'DIR'           => GNETWORK_DIR,
			'URL'           => GNETWORK_URL,
			'BASE'          => GNETWORK_BASE,
			'FILE'          => GNETWORK_FILE,
			'DL_DIR'        => GNETWORK_DL_DIR,
			'DL_URL'        => GNETWORK_DL_URL,
			'DEBUG_LOG'     => GNETWORK_DEBUG_LOG,
			'ANALOG_LOG'    => GNETWORK_ANALOG_LOG,
			'FAILED_LOG'    => GNETWORK_FAILED_LOG,
			'MAIL_LOG_DIR'  => GNETWORK_MAIL_LOG_DIR,
			'AJAX_ENDPOINT' => GNETWORK_AJAX_ENDPOINT,
		];

		echo '<div class="-wrap card -floated" dir="ltr">';
		HTML::h2( _x( 'Plugin Paths', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			echo HTML::tableCode( $paths );
		echo '</div>';
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

		echo '<div class="-wrap card -floated" dir="ltr">';
		HTML::h2( _x( 'Current Time', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			echo HTML::tableCode( $times );
		echo '</div>';
	}

	public static function summaryIPs( $caption = FALSE, $card = TRUE )
	{
		$summary = [];
		$keys    = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $keys as $key )
			if ( isset( $_SERVER[$key] ) )
				$summary[$key] = gnetwork_ip_lookup( $_SERVER[$key] );

		if ( $card ) {

			echo '<div class="-wrap card -floated" dir="ltr">';
			HTML::h2( $caption ?: _x( 'IPs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

				echo HTML::tableCode( $summary );
			echo '</div>';

		} else {

			// old fashion way!
			echo HTML::tableCode( $summary, FALSE, $caption );
		}
	}

	public static function summarySSL()
	{
		$summary = [];
		$keys    = [
			'HTTPS',
			'SERVER_PORT',
			'HTTP_CLOUDFRONT_FORWARDED_PROTO',
			'HTTP_CF_VISITOR',
			'HTTP_X_FORWARDED_PROTO',
			'HTTP_X_FORWARDED_SSL',
		];

		foreach ( $keys as $key )
			if ( isset( $_SERVER[$key] ) )
				$summary[$key] = HTML::escape( $_SERVER[$key] );

		echo '<div class="-wrap card -floated" dir="ltr">';
		HTML::h2( _x( 'SSL', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			echo HTML::tableCode( $summary );
		echo '</div>';
	}

	public static function summaryUpload()
	{
		$info = [
			'wp_max_upload_size()'     => File::formatSize( wp_max_upload_size() ).' = '.wp_max_upload_size(),
			'option: max_file_size'    => get_option( 'max_file_size' ),
			'ini: upload_max_filesize' => ini_get( 'upload_max_filesize' ).' = '.wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) ),
			'ini: post_max_size'       => ini_get( 'post_max_size' ).' = '.wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ),
			'ms_files_rewriting'       => get_option( 'ms_files_rewriting' ),
			'option: upload_path'      => get_option( 'upload_path' ),
			'option: upload_url_path'  => get_site_option( 'upload_url_path' ),
		];

		$upload = wp_upload_dir();
		unset( $upload['error'], $upload['subdir'] );

		foreach ( $upload as $key => $val )
			$info['wp_upload: '.$key] = $val;

		echo '<div class="-wrap card -floated" dir="ltr">';
		HTML::h2( _x( 'File & Upload', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			echo HTML::tableCode( $info );
		echo '</div>';
	}

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
		if ( $phpinfo = self::get_phpinfo() )
			echo HTML::wrap( $phpinfo, '-phpinfo' );

		else
			HTML::desc( sprintf( _x( '%s has been disabled.', 'Modules: Debug', GNETWORK_TEXTDOMAIN ), '<code>phpinfo()</code>' ), TRUE, '-empty -phpinfo' );
	}

	public static function summaryCurrents()
	{
		echo '<div class="masonry-grid">';

			self::systemVersions();
			self::summaryWordPress();
			self::summaryIPs();
			self::summarySSL();
			self::initialConstants();
			self::pluginPaths();

		echo '</div>';
	}

	public static function systemVersions()
	{
		echo '<div class="-wrap card -floated -currents" dir="ltr">';
		HTML::h2( _x( 'System Versions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		HTML::desc( sprintf( _x( 'Current MySQL version: %s', 'Modules: Debug', GNETWORK_TEXTDOMAIN ), '<code>'.$GLOBALS['wpdb']->db_version().'</code>' ) );

		echo '<hr />';

		HTML::desc( sprintf( _x( 'Current PHP version: %s', 'Modules: Debug', GNETWORK_TEXTDOMAIN ), '<code>'.phpversion().'</code>' ) );

		echo HTML::listCode( self::getPHPExtensions(),
			'<code title="%2$s">%1$s</code>',
			'<span class="description -success">'._x( 'Loaded Extensions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ).':</span>'
		);

		echo HTML::listCode( self::getPHPMissingExtensions(),
			'<code title="%2$s">%1$s</code>',
			'<span class="description -danger">'._x( 'Missing Extensions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ).':</span>'
		);

		// echo '<hr />';
		//
		// HTML::desc( _x( 'Current Image Tools:', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );
		//
		// $path      = '/usr/local/bin/';
		// $tools     = [ 'optipng', 'pngquant', 'cwebp', 'jpegoptim' ];
		// $available = [];
		//
		// foreach ( $tools as $tool )
		// 	$available[strtoupper($tool)] = is_readable( $path.$tool ) ? $path.$tool : FALSE;
		//
		// echo HTML::tableCode( $available );

		echo '</div>';
	}

	public static function getPHPExtensions()
	{
		$extensions = [];

		foreach ( get_loaded_extensions() as $ext ) {

			if ( 'core' == strtolower( $ext ) )
				continue;

			if ( $ver = phpversion( $ext ) )
				$extensions[$ext] = 'v'.HTML::escape( $ver );
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

	public static function phpFunctions()
	{
		$list = [];

		foreach ( get_loaded_extensions() as $ext )
			$list[$ext] = get_extension_funcs( $ext );

		HTML::tableSide( $list );
	}

	public function debug_bar_panels( $panels )
	{
		if ( is_readable( GNETWORK_DIR.'includes/Misc/DebugMetaPanel.php' ) ) {
			require_once( GNETWORK_DIR.'includes/Misc/DebugMetaPanel.php' );
			$panels[] = new \geminorum\gNetwork\Misc\DebugMetaPanel();
		}

		if ( is_readable( GNETWORK_DIR.'includes/Misc/DebugExtrasPanel.php' ) ) {
			require_once( GNETWORK_DIR.'includes/Misc/DebugExtrasPanel.php' );
			$panels[] = new \geminorum\gNetwork\Misc\DebugExtrasPanel();
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
			Logger::siteFAILED( 'HTTP-API', $class.': '.$response->get_error_message().' - '.esc_url( $url ) );

		if ( WordPress::isSuperAdmin() )
			$this->http_calls[] = [
				'url'    => $url,
				'method' => empty( $args['method'] ) ? 'UNKNOWN' : $args['method'],
			];
	}

	public function core_upgrade_preamble()
	{
		if ( ! GNETWORK_DEBUG_LOG && ! GNETWORK_ANALOG_LOG && ! GNETWORK_FAILED_LOG )
			return;

		HTML::h2( _x( 'Extras', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		echo '<p class="gnetwork-admin-wrap debug-update-core">';

			if ( GNETWORK_DEBUG_LOG )
				echo HTML::tag( 'a', [
					'class' => 'button button-secondary button-small',
					'href'  => $this->get_menu_url( 'errorlogs', 'network', 'tools' ),
				], _x( 'Check Error Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			if ( GNETWORK_DEBUG_LOG && GNETWORK_ANALOG_LOG )
				echo '&nbsp;&nbsp;';

			if ( GNETWORK_ANALOG_LOG )
				echo HTML::tag( 'a', [
					'class' => 'button button-secondary button-small',
					'href'  => $this->get_menu_url( 'analoglogs', 'network', 'tools' ),
				], _x( 'Check System Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

			if ( GNETWORK_ANALOG_LOG && GNETWORK_FAILED_LOG )
				echo '&nbsp;&nbsp;';

			if ( GNETWORK_FAILED_LOG )
				echo HTML::tag( 'a', [
					'class' => 'button button-secondary button-small',
					'href'  => $this->get_menu_url( 'failedlogs', 'network', 'tools' ),
				], _x( 'Check Failed Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		echo '</p>';
	}

	// @REF: `_default_wp_die_handler()`
	public function wp_die_handler( $message, $title = '', $args = [] )
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

			// if it's already not wrapped
			if ( '<' !== Text::subStr( trim( $message ), 0, 1 ) )
				$message = Text::autoP( $message );
		}

		if ( isset( $r['back_link'] ) && $r['back_link'] ) {
			$back_text = $have_gettext ? __( '&laquo; Back' ) : '&laquo; Back';
			$message.= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
		}

		if ( empty( $title ) )
			$title = sprintf( '%d %s', $r['response'], HTTP::getStatusDesc( $r['response'] ) );

		if ( ! did_action( 'admin_head' ) ) {

			if ( ! headers_sent() ) {
				header( 'Content-Type: text/html; charset=utf-8' );
				status_header( $r['response'] );
				nocache_headers();
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

			Utilities::linkStyleSheet( 'die.all' );
			Utilities::customStyleSheet( 'die.css' );

			echo '</head><body id="error-page">';
		} // ! did_action( 'admin_head' )

		echo $message;
		echo '</body></html>';

		die;
	}

	public function shutdown()
	{
		$GLOBALS['wpdb']->close();
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
			if ( 'wp-db.php' !== File::basename( $errfile ) ) {
				if ( preg_match( '/^(mysql_[a-zA-Z0-9_]+)/', $errstr, $matches ) ) {
					_doing_it_wrong( $matches[1], __( 'Please talk to the database using $wpdb' ), '3.7' );

					return apply_filters( 'wpdb_drivers_raw_mysql_call_trigger_error', TRUE );
				}
			}

			return apply_filters( 'wp_error_handler', FALSE, $errno, $errstr, $errfile );
		}, $errcontext );
	}
}

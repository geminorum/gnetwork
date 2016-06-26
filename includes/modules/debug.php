<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Debug extends ModuleCore
{
	protected $key = 'debug';

	protected function setup_actions()
	{
		if ( is_admin() )
			add_action( 'core_upgrade_preamble', array( $this, 'core_upgrade_preamble' ), 20 );

		add_filter( 'debug_bar_panels', array( $this, 'debug_bar_panels' ) );
		add_action( 'wp_footer', array( $this, 'wp_footer' ), 999 );

		add_filter( 'wp_die_handler', function( $function ){
			return array( __NAMESPACE__.'\\Debug', 'wp_die_handler' );
		} );

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

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'Debug Logs', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
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

			// TODO: add limit/length input

			if ( self::displayErrorLogs() )
				$this->settings_buttons( $sub );

		echo '</form>';
	}

	protected function register_settings_buttons()
	{
		$this->register_button( 'clear_error_log', _x( 'Clear Log', 'Modules: Debug', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
	}

	private static function displayErrorLogs()
	{
		if ( file_exists( GNETWORK_DEBUG_LOG ) ) {

			if ( ! $file_size = File::getSize( GNETWORK_DEBUG_LOG ) )
				return FALSE;

			if ( $errors = File::getLastLines( GNETWORK_DEBUG_LOG, self::limit( 100 ) ) ) {

				$length = self::req( 'length', 300 );

				echo '<h3 class="error-box-header">';
					printf( _x( 'The Last %s Errors, in reverse order', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), number_format_i18n( count( $errors ) ) );

				echo '</h3><div class="error-box"><ol>';

				foreach ( $errors as $error ) {

					if ( ! trim( $error ) )
						continue;

					echo '<li>';

					$line = preg_replace_callback( '/\[([^\]]+)\]/', function( $matches ){
						return '<b><span title="'.human_time_diff( strtotime( $matches[1] ) ).'">['.$matches[1].']</span></b>';
					}, trim ( $error ), 1 );

					echo Text::strLen( $line ) > $length ? Text::subStr( $line, 0, $length ).' [&hellip;]' : $line;

					echo '</li>';
				}

				echo '</ol></div><p class="error-box-footer description">'.sprintf( _x( 'File Size: %s', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), $file_size ).'</p>';

			} else {
				self::warning( _x( 'No errors currently logged.', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), TRUE );
				return FALSE;
			}

		} else {
			self::error( _x( 'There was a problem reading the error log file.', 'Modules: Debug: Error Box', GNETWORK_TEXTDOMAIN ), TRUE );
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
			'wp_version'             => _x( 'WordPress', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'wp_db_version'          => _x( 'WordPress DB revision', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'tinymce_version'        => _x( 'TinyMCE', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_php_version'   => _x( 'Required PHP', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
			'required_mysql_version' => _x( 'Required MySQL', 'Modules: Debug: Version Strings', GNETWORK_TEXTDOMAIN ),
		);

		echo '<table class="base-table-code"><tbody>';
		foreach ( $versions as $key => $val )
			echo sprintf( '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>', $val, $$key );
		echo '</tbody></table>';
	}

	public static function gPlugin()
	{
		if ( class_exists( 'gPlugin' ) ) {
			$info = \gPlugin::get_info();
			HTML::tableCode( $info[1] );
			HTML::tableSide( $info[0] );
		} else {
			echo '<p class="description">'._x( 'No Instance of gPlugin found.', 'Modules: Debug', GNETWORK_TEXTDOMAIN ).'</p>';
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

		HTML::tableCode( $paths );
	}

	public static function pluginPaths()
	{
		$paths = array(
			'ABSPATH'       => ABSPATH,
			'DIR'           => GNETWORK_DIR,
			'URL'           => GNETWORK_URL,
			'DL_DIR'        => GNETWORK_DL_DIR,
			'DL_URL'        => GNETWORK_DL_URL,
			'MAIL_LOG_DIR'  => GNETWORK_MAIL_LOG_DIR,
			'AJAX_ENDPOINT' => GNETWORK_AJAX_ENDPOINT,

		);

		HTML::tableCode( $paths );
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
			$server['PATH']
		);

		if ( ! empty( $server['SERVER_SIGNATURE'] ) )
			$server['SERVER_SIGNATURE'] = strip_tags( $server['SERVER_SIGNATURE'] );

		// FIXME: use Utilities::getDateDefaultFormat()
		$server['REQUEST_TIME_FLOAT'] = date( 'l, j F, Y - H:i:s T', $server['REQUEST_TIME_FLOAT'] ).' ('.$server['REQUEST_TIME_FLOAT'] .')';
		$server['REQUEST_TIME']       = date( 'l, j F, Y - H:i:s T', $server['REQUEST_TIME'] ).' ('.$server['REQUEST_TIME'] .')';

		HTML::tableCode( $server );
	}

	public static function phpinfo()
	{
		if ( self::isFuncDisabled( 'phpinfo' ) ) {

			echo '<div class="gnetwork-phpinfo-disabled description">';
				_ex( '<code>phpinfo()</code> has been disabled.', 'Modules: Debug', GNETWORK_TEXTDOMAIN );
			echo '</div>';

		} else {

			$dom = new \domDocument;

			ob_start();
			phpinfo();

			$dom->loadHTML( ob_get_clean() );
			$body = $dom->documentElement->lastChild;
			echo '<div class="gnetwork-phpinfo-wrap">';
				echo $dom->saveHTML( $body );
			echo '</div>';
		}
	}

	public static function phpversion()
	{
		echo '<p class="description">'.sprintf( _x( 'Current PHP version: <code>%s</code>', 'Modules: Debug', GNETWORK_TEXTDOMAIN ), phpversion() ).'</p>';

		HTML::listCode( self::getPHPExtensions(), NULL, '<span class="description">'._x( 'Loaded Extensions', 'Modules: Debug', GNETWORK_TEXTDOMAIN ).':</span>' );
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
			self::log( 'HTTP API RESPONSE: '.$class, $response->get_error_message(), $url );
	}

	public function wp_login_errors( $errors, $redirect_to )
	{
		if ( in_array( 'test_cookie', $errors->get_error_codes() ) )
			self::log( 'TEST COOCKIE', $errors->get_error_message( 'test_cookie' ) ); // FIXME: generate static message

		return $errors;
	}

	public function core_upgrade_preamble()
	{
		echo '<div class="gnetwork-admin-wrap debug-update-core">';

			echo HTML::tag( 'a', array(
				'class' => 'button',
				'href'  => Settings::subURL( 'debug' ),
			), _x( 'Check Debug Logs', 'Modules: Debug', GNETWORK_TEXTDOMAIN ) );

		echo '</div>';
	}

	public static function wp_die_handler( $message, $title = '', $args = array() )
	{
		$r = wp_parse_args( $args, array(
			'response' => 500,
		) );

		$have_gettext = function_exists( '__' );

		if ( self::isError( $message ) ) {

			if ( empty( $title ) ) {
				$error_data = $message->get_error_data();
				if ( is_array( $error_data ) && isset( $error_data['title'] ) )
					$title = $error_data['title'];
			}

			$errors = $message->get_error_messages();
			switch ( count( $errors ) ) :
			case 0 :
				$message = '';
				break;
			case 1 :
				$message = "<p>{$errors[0]}</p>";
				break;
			default :
				$message = "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
				break;
			endswitch;
		} elseif ( is_string( $message ) ) {
			$message = "<p>$message</p>";
		}

		if ( isset( $r['back_link'] ) && $r['back_link'] ) {
			$back_text = $have_gettext? __('&laquo; Back') : '&laquo; Back';
			$message .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
		}

		if ( ! did_action( 'admin_head' ) ) :
			if ( ! headers_sent() ) {
				status_header( $r['response'] );
				nocache_headers();
				header( 'Content-Type: text/html; charset=utf-8' );
			}

			if ( empty($title) )
				$title = $have_gettext ? __('WordPress &rsaquo; Error') : 'WordPress &rsaquo; Error';

			$text_direction = 'ltr';
			if ( isset( $r['text_direction'] ) && 'rtl' == $r['text_direction'] )
				$text_direction = 'rtl';
			elseif ( function_exists( 'is_rtl' ) && is_rtl() )
				$text_direction = 'rtl';

?><!DOCTYPE html>
<!-- Ticket #11289, IE bug fix: always pad the error page with enough characters such that it is greater than 512 bytes, even after gzip compression abcdefghijklmnopqrstuvwxyz1234567890aabbccddeeffgghhiijjkkllmmnnooppqqrrssttuuvvwwxxyyzz11223344556677889900abacbcbdcdcededfefegfgfhghgihihjijikjkjlklkmlmlnmnmononpopoqpqprqrqsrsrtstsubcbcdcdedefefgfabcadefbghicjkldmnoepqrfstugvwxhyz1i234j567k890laabmbccnddeoeffpgghqhiirjjksklltmmnunoovppqwqrrxsstytuuzvvw0wxx1yyz2z113223434455666777889890091abc2def3ghi4jkl5mno6pqr7stu8vwx9yz11aab2bcc3dd4ee5ff6gg7hh8ii9j0jk1kl2lmm3nnoo4p5pq6qrr7ss8tt9uuvv0wwx1x2yyzz13aba4cbcb5dcdc6dedfef8egf9gfh0ghg1ihi2hji3jik4jkj5lkl6kml7mln8mnm9ono
-->
<html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) ) language_attributes(); else echo "dir='$text_direction'"; ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<?php
		echo '<title>'.$title.'</title>';
		Utilities::linkStyleSheet( GNETWORK_URL.'assets/css/die.all.css' );
		Utilities::customStyleSheet( 'die.css' );

	?>
</head>
<body id="error-page" class="<?php echo $text_direction; ?>">
<?php endif; // ! did_action( 'admin_head' ) ?>
	<?php echo $message; ?>
</body>
</html>
<?php
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

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Utilities extends Base
{

	public static function creditsBadge()
	{
		return '<a href="http://geminorum.ir" title="it\'s a geminorum project"><img src="'
			.GNETWORK_URL.'assets/images/itsageminorumproject-lightgrey.svg" alt="" /></a>';
	}

	public static function updateNotice( $plugin = GNETWORK_FILE )
	{
		$updates = get_plugin_updates();

		if ( ! empty( $updates[$plugin] ) )
			self::info( sprintf(
				_x( 'A new version of %s is available. Please update to version %s to ensure compatibility with your WordPress.', 'Utilities: Update Notice', GNETWORK_TEXTDOMAIN ),
				HTML::link( $updates[$plugin]->Name, $updates[$plugin]->PluginURI, TRUE ),
				$updates[$plugin]->update->new_version
			), TRUE );

		else
			return FALSE;
	}

	public static function getFeeds( $filter = TRUE )
	{
		$feeds = array( 'rdf', 'rss', 'rss2', 'atom', 'json' );

		return $filter ? apply_filters( 'gnetwork_get_feeds', $feeds ) : $feeds;
	}

	public static function highlightTime( $string, $limit = -1 )
	{
		$pattern = '/\[([^\]]+)\]/';

		return preg_replace_callback( $pattern, function( $matches ) {
			return '<b><span title="'.esc_attr( self::humanTimeAgo( strtotime( $matches[1] ) ) ).'">['.$matches[1].']</span></b>';
		}, $string, $limit );
	}

	public static function highlightIP( $string, $limit = -1 )
	{
		// @REF: http://regexr.com/35833
		$pattern = "/((((25[0-5])|(2[0-4]\d)|([01]?\d?\d)))\.){3}((((25[0-5])|(2[0-4]\d)|([01]?\d?\d))))/i";

		return preg_replace_callback( $pattern, function( $matches ) {
			return gnetwork_ip_lookup( $matches[0] );
		}, $string, $limit );
	}

	public static function htmlHumanTime( $timestamp )
	{
		$time = strtotime( $timestamp );
		return '<span class="-time" title="'
			.self::humanTimeAgo( $time, current_time( 'timestamp', FALSE ) ).'">'
			.self::humanTimeDiffRound( $time )
		.'</span>';
	}

	public static function humanTimeAgo( $from, $to = '' )
	{
		return sprintf( _x( '%s ago', 'Utilities: Human Time Ago', GNETWORK_TEXTDOMAIN ), human_time_diff( $from, $to ) );
	}

	public static function humanTimeDiffRound( $local, $round = DAY_IN_SECONDS, $format = NULL, $now = NULL )
	{
		$now = is_null( $now ) ? current_time( 'timestamp', FALSE ) : '';

		if ( FALSE === $round )
			return self::humanTimeAgo( $local, $now );

		$diff = $now - $local;

		if ( $diff > 0 && $diff < $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $format ) )
			$format = _x( 'Y/m/d', 'Utilities: Human Time Diff Round', GNETWORK_TEXTDOMAIN );

		return date_i18n( $format, $local, FALSE );
	}

	public static function humanTimeDiff( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = array(
				'now'    => _x( 'Now', 'Utilities: Human Time Diff', GNETWORK_TEXTDOMAIN ),
				'_s_ago' => _x( '%s ago', 'Utilities: Human Time Diff', GNETWORK_TEXTDOMAIN ),
				'in__s'  => _x( 'in %s', 'Utilities: Human Time Diff', GNETWORK_TEXTDOMAIN ),

				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_years'   => _nx_noop( '%s year', '%s years', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
			);

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::humanTimeDiff( $timestamp, $now, $strings );
	}

	// not used yet!
	public static function moment( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = array(
				'now'            => _x( 'Now', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'just_now'       => _x( 'Just now', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'one_minute_ago' => _x( 'One minute ago', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'_s_minutes_ago' => _x( '%s minutes ago', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'one_hour_ago'   => _x( 'One hour ago', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'_s_hours_ago'   => _x( '%s hours ago', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'yesterday'      => _x( 'Yesterday', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'_s_days_ago'    => _x( '%s days ago', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'_s_weeks_ago'   => _x( '%s weeks ago', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'last_month'     => _x( 'Last month', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'last_year'      => _x( 'Last year', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'in_a_minute'    => _x( 'in a minute', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'in__s_minutes'  => _x( 'in %s minutes', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'in_an_hour'     => _x( 'in an hour', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'in__s_hours'    => _x( 'in %s hours', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'tomorrow'       => _x( 'Tomorrow', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'next_week'      => _x( 'next week', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'in__s_weeks'    => _x( 'in %s weeks', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'next_month'     => _x( 'next month', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'format_l'       => _x( 'l', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
				'format_f_y'     => _x( 'F Y', 'Utilities: Date: Moment', GNETWORK_TEXTDOMAIN ),
			);

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::moment( $timestamp, $now, $strings );
	}

	public static function getDateEditRow( $mysql_time, $wrap_class = FALSE )
	{
		$html = '';

		$date = _x( 'm/d/Y', 'Utilities: Date Edit Row', GNETWORK_TEXTDOMAIN );
		$time = _x( 'H:i', 'Utilities: Date Edit Row', GNETWORK_TEXTDOMAIN );
		$full = _x( 'l, M j, Y @ H:i', 'Utilities: Date Edit Row', GNETWORK_TEXTDOMAIN );

		$html .= '<span class="-date-date" title="'.esc_attr( mysql2date( $time, $mysql_time ) ).'">'.mysql2date( $date, $mysql_time ).'</span>';
		$html .= '&nbsp;(<span class="-date-diff" title="'.esc_attr( mysql2date( $full, $mysql_time ) ).'">'.self::humanTimeDiff( $mysql_time ).'</span>)';

		return $wrap_class ? '<span class="'.$wrap_class.'">'.$html.'</span>' : $html;
	}

	// @SEE: http://www.phpformatdate.com/
	public static function getDateDefaultFormat( $options = FALSE, $date_format = NULL, $time_format = NULL, $joiner = ' @' )
	{
		if ( ! $options )
			return _x( 'l, j F, Y - H:i:s', 'Utilities: Default Datetime Format', GNETWORK_TEXTDOMAIN );

		if ( is_null( $date_format ) )
			$date_format = get_option( 'date_format' );

		if ( is_null( $time_format ) )
			$time_format = get_option( 'time_format' );

		return $date_format.$joiner.$time_format;
	}

	public static function getPostTitle( $post, $fallback = NULL )
	{
		$title = apply_filters( 'the_title', $post->post_title, $post->ID );

		if ( ! empty( $title ) )
			return $title;

		if ( FALSE === $fallback )
			return '';

		if ( is_null( $fallback ) )
			return _x( '(untitled)', 'Utilities: Post Title', GNETWORK_TEXTDOMAIN );

		return $fallback;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache()
	{
		global $gNetworkMustache;

		if ( ! empty( $gNetworkMustache ) )
			return $gNetworkMustache;

		$gNetworkMustache = new \Mustache_Engine(array(
			'template_class_prefix'  => '__MyTemplates_',
			'cache'                  => GNETWORK_DIR.'assets/views/cache', // get_temp_dir().'mustache',
			'cache_file_mode'        => FS_CHMOD_FILE,
			'cache_lambda_templates' => TRUE,
			'loader'                 => new \Mustache_Loader_FilesystemLoader( GNETWORK_DIR.'assets/views' ),
			'partials_loader'        => new \Mustache_Loader_FilesystemLoader( GNETWORK_DIR.'assets/views/partials' ),
			// 'logger'                 => new Mustache_Logger_StreamLogger('php://stderr'),
			// 'strict_callables'       => TRUE,
			// 'pragmas'                => [Mustache_Engine::PRAGMA_FILTERS],
			// 'helpers' => array(
			// 	'i18n' => function( $text ){
			// 		// do something translatey here...
			// 	},
			// ),
			'escape' => function( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		));

		return $gNetworkMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	public static function renderMustache( $part, $data = array(), $echo = TRUE )
	{
		$mustache = self::getMustache();
		$template = $mustache->loadTemplate( $part );
		$html     = $template->render( $data );

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public static function joinItems( $items )
	{
		return
			_x( '&rdquo;', 'Utilities: Join Items Helper', GNETWORK_TEXTDOMAIN )
			.join( _x( '&ldquo; and &rdquo;', 'Utilities: Join Items Helper', GNETWORK_TEXTDOMAIN ),
				array_filter( array_merge( array(
					join( _x( '&ldquo;, &rdquo;', 'Utilities: Join Items Helper', GNETWORK_TEXTDOMAIN ),
					array_slice( $items, 0, -1 ) ) ),
					array_slice( $items, -1 ) ) ) )
			._x( '&ldquo;', 'Utilities: Join Items Helper', GNETWORK_TEXTDOMAIN ).'.';
	}

	public static function getLayout( $name, $require = FALSE, $no_cache = FALSE )
	{
		$layout = locate_template( $name );

		if ( ! $layout && file_exists( WP_CONTENT_DIR.'/'.$name.'.php' ) )
			$layout = WP_CONTENT_DIR.'/'.$name.'.php';

		if ( ! $layout )
			$layout = GNETWORK_DIR.'includes/layouts/'.$name.'.php';

		if ( $no_cache )
			WordPress::doNotCache();

		if ( $require && $layout )
			require_once( $layout );
		else
			return $layout;
	}

	// override to use plugin version
	public static function linkStyleSheet( $css, $version = GNETWORK_VERSION, $media = 'all' )
	{
		HTML::linkStyleSheet( GNETWORK_URL.'assets/css/'.$css, $version, $media );
	}

	public static function customStyleSheet( $css, $link = TRUE, $version = GNETWORK_VERSION )
	{
		$file = WordPress::customFile( $css, FALSE );

		if ( $link && $file )
			HTML::linkStyleSheet( $file, $version );

		return $file;
	}

	public static function enqueueScript( $asset, $dep = array( 'jquery' ), $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/js' )
	{
		$handle  = strtolower( 'gnetwork-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( $handle, $base.$path.'/'.$asset.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function enqueueScriptVendor( $asset, $dep = array(), $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/js/vendor' )
	{
		return self::enqueueScript( $asset, $dep, $version, $base, $path );
	}

	public static function enqueueTimeAgo()
	{
		$callback = array( 'gPersianDateTimeAgo', 'enqueue' );

		if ( ! is_callable( $callback ) )
			return FALSE;

		return call_user_func( $callback );
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( 'gnetwork_tinymce_strings', array() );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.gnetwork", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	// FIXME: DROP THIS
	// DEPRECATED: use `gnetwork_github_readme()`
	public static function githubREADME( $repo = 'geminorum/gnetwork', $wrap = TRUE )
	{
		self::__dep( 'gnetwork_github_readme()' );
		gnetwork_github_readme( $repo, $wrap );
	}

	// FIXME: WTF ?!
	// http://www.webdesignerdepot.com/2012/08/wordpress-filesystem-api-the-right-way-to-operate-with-local-files/
	// http://ottopress.com/2011/tutorial-using-the-wp_filesystem/

	/**
	 * Initialize Filesystem object
	 *
	 * @param str $form_url - URL of the page to display request form
	 * @param str $method - connection method
	 * @param str $context - destination folder
	 * @param array $fields - fileds of $_POST array that should be preserved between screens
	 * @return bool/str - false on failure, stored text on success
	 **/
	public static function initWPFS( $form_url, $method, $context, $fields = NULL )
	{
		global $wp_filesystem;

		// first attempt to get credentials
		if ( FALSE === ( $creds = request_filesystem_credentials( $form_url, $method, FALSE, $context, $fields ) ) )
			// if we comes here - we don't have credentials so the request for them is displaying no need for further processing
			return FALSE;

		// now we got some credentials - try to use them
		if ( ! WP_Filesystem( $creds ) ) {

			// incorrect connection data - ask for credentials again, now with error message
			request_filesystem_credentials( $form_url, $method, TRUE, $context );
			return FALSE;
		}

		// filesystem object successfully initiated
		return TRUE;
	}

	/**
	 * Perform writing into file
	 *
	 * @param str $form_url - URL of the page to display request form
	 * @return bool/str - false on failure, stored text on success
	 **/
	public static function writeWPFS( $form_url )
	{
		global $wp_filesystem;

		$args = self::atts( array(
			'form_url' => '',
			'referer'  => 'filesystem_demo_screen',
			'content'  => sanitize_text_field( $_POST['demotext'] ),
			'method'   => '', // leave this empty to perform test for 'direct' writing
			'context'  => WP_CONTENT_DIR.'/gnetwork', // target folder
			'filename' => 'test.txt',
		), $atts );

		check_admin_referer( 'filesystem_demo_screen' );

		$demotext    = sanitize_text_field( $_POST['demotext'] ); // sanitize the input
		$form_fields = array( 'demotext' ); // fields that should be preserved across screens
		$method      = ''; // leave this empty to perform test for 'direct' writing
		$context     = WP_PLUGIN_DIR.'/filesystem-demo'; // target folder
		$form_url    = wp_nonce_url( $form_url, 'filesystem_demo_screen' ); // page url with nonce value

		if ( ! self::initWPFS( $form_url, $method, $context, $form_fields ) )
			return FALSE; // stop further processign when request form is displaying

		// now $wp_filesystem could be used
		// get correct target file first
		$target_dir  = $wp_filesystem->find_folder( $context );
		$target_file = trailingslashit( $target_dir ).'test.txt';

		// write into file
		if ( ! $wp_filesystem->put_contents( $target_file, $demotext, FS_CHMOD_FILE ) )
			return new Error( 'writing_error', 'Error when writing file' ); // return error object

		return $demotext;
	}

	/**
	 * Read text from file
	 *
	 * @param str $form_url - URL of the page where request form will be displayed
	 * @return bool/str - false on failure, stored text on success
	 **/
	public static function readWPFS( $form_url )
	{
		global $wp_filesystem;

		$demotext = '';

		$form_url = wp_nonce_url( $form_url, 'filesystem_demo_screen' );
		$method   = ''; // leave this empty to perform test for 'direct' writing
		$context  = WP_PLUGIN_DIR.'/filesystem-demo'; // target folder

		if ( ! self::initWPFS( $form_url, $method, $context ) )
			return FALSE; // stop further processing when request forms displaying

		// now $wp_filesystem could be used
		// get correct target file first
		$target_dir  = $wp_filesystem->find_folder( $context );
		$target_file = trailingslashit( $target_dir ).'test.txt';

		// read the file
		if ( $wp_filesystem->exists( $target_file ) ) { // check for existence

			$demotext = $wp_filesystem->get_contents( $target_file );
			if ( ! $demotext )
				return new Error( 'reading_error', 'Error when reading file' ); // return error object
		}

		return $demotext;
	}

	// @API: https://developers.google.com/chart/infographics/docs/qr_codes
	// @EXAMPLE: https://createqrcode.appspot.com/
	// @SEE: https://github.com/endroid/QrCode
	// @SEE: https://github.com/aferrandini/PHPQRCode
	public static function getGoogleQRCode( $data, $atts = array() )
	{
		$args = self::atts( array(
			'tag'        => TRUE,
			'size'       => 150,
			'encoding'   => 'UTF-8',
			'correction' => 'H', // 'L', 'M', 'Q', 'H'
			'margin'     => 0,
			'url'        => 'https://chart.googleapis.com/chart',
		), $atts );

		$src = add_query_arg( array(
			'cht'  => 'qr',
			'chs'  => $args['size'].'x'.$args['size'],
			'chl'  => urlencode( $data ),
			'chld' => $args['correction'].'|'.$args['margin'],
			'choe' => $args['encoding'],
		), $args['url'] );

		if ( ! $args['tag'] )
			return $src;

		return HTML::tag( 'img', array(
			'src'    => $src,
			'width'  => $args['size'],
			'height' => $args['size'],
			'alt'    => strip_tags( $data ),
		) );
	}

	// @SEE: https://core.trac.wordpress.org/ticket/24661
	// @SEE: https://core.trac.wordpress.org/ticket/22363
	// @SEE: https://core.trac.wordpress.org/ticket/35951
	// @SEE: https://core.trac.wordpress.org/ticket/30130
	// FIXME: check: `URLify::add_chars()`
	public static function URLifyDownCode( $string, $locale = NULL )
	{
		$iso = class_exists( __NAMESPACE__.'\\Locale' ) ? Locale::getISO( $locale ) : $locale;

		return \URLify::downcode( $string, $iso );
	}

	public static function IPinfo()
	{
		global $gNetworkIPinfo;

		if ( empty( $gNetworkIPinfo ) )
			$gNetworkIPinfo = new \DavidePastore\Ipinfo\Ipinfo();

		return $gNetworkIPinfo;
	}

	// @REF: https://github.com/DavidePastore/ipinfo
	public static function getIPinfo( $ip = NULL )
	{
		$ipinfp = self::IPinfo();

		if ( is_null( $ip ) )
			return $ipinfp->getYourOwnIpDetails();

		return $ipinfp->getFullIpDetails( $ip );
	}

	public static function log( $error = '[Unknown]', $message = FALSE, $extra = FALSE, $path = GNETWORK_DEBUG_LOG )
	{
		if ( ! $path )
			return;

		$log = '';

		$log .= '['.gmdate( 'd-M-Y H:i:s e' ).'] '; // [03-Feb-2015 21:20:19 UTC]
		$log .= $error.' ';
		$log .= HTTP::IP( TRUE );
		$log .= $message ? ' :: '.strip_tags( $message ) : '';
		$log .= $extra ? ' :: '.$extra : '';

		error_log( $log."\n", 3, $path );
	}
}

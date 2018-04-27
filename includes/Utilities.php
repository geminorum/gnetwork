<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\Date;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Utilities extends Core\Base
{

	const BASE = 'gnetwork';

	public static function creditsBadge()
	{
		return '<a href="https://geminorum.ir" title="it\'s a geminorum project"><img src="'
			.GNETWORK_URL.'assets/images/itsageminorumproject-lightgrey.svg" alt="" /></a>';
	}

	public static function updateNotice( $plugin = GNETWORK_FILE )
	{
		$updates = get_plugin_updates();

		if ( ! empty( $updates[$plugin] ) )
			echo HTML::info( sprintf(
				_x( 'A new version of %s is available. Please update to version %s to ensure compatibility with your WordPress.', 'Utilities: Update Notice', GNETWORK_TEXTDOMAIN ),
				HTML::link( $updates[$plugin]->Name, $updates[$plugin]->PluginURI, TRUE ),
				$updates[$plugin]->update->new_version
			) );

		else
			return FALSE;
	}

	public static function getFeeds( $filter = TRUE )
	{
		$feeds = [ 'rdf', 'rss', 'rss2', 'atom', 'json' ];

		return $filter ? apply_filters( self::BASE.'_get_feeds', $feeds ) : $feeds;
	}

	public static function highlightTime( $string, $limit = -1 )
	{
		$pattern = '/\[([^\]]+)\]/';

		return preg_replace_callback( $pattern, function( $matches ) {
			return '<b><span title="'.HTML::escape( self::humanTimeAgo( strtotime( $matches[1] ) ) ).'">['.$matches[1].']</span></b>';
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

	public static function htmlHumanTime( $timestamp, $flip = FALSE )
	{
		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$now = current_time( 'timestamp', FALSE );

		if ( $flip )
			return '<span class="-date-diff" title="'
					.HTML::escape( self::dateFormat( $timestamp, 'fulltime' ) ).'">'
					.self::humanTimeDiff( $timestamp, $now )
				.'</span>';

		return '<span class="-time" title="'
			.HTML::escape( self::humanTimeAgo( $timestamp, $now ) ).'">'
			.self::humanTimeDiffRound( $timestamp, NULL, self::dateFormats( 'default' ), $now )
		.'</span>';
	}

	public static function humanTimeAgo( $from, $to = '' )
	{
		return sprintf( _x( '%s ago', 'Utilities: Human Time Ago', GNETWORK_TEXTDOMAIN ), human_time_diff( $from, $to ) );
	}

	public static function humanTimeDiffRound( $local, $round = NULL, $format = NULL, $now = NULL )
	{
		if ( is_null( $now ) )
			$now = current_time( 'timestamp', FALSE );

		if ( FALSE === $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $round ) )
			$round = Date::DAY_IN_SECONDS;

		$diff = $now - $local;

		if ( $diff > 0 && $diff < $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $format ) )
			$format = self::dateFormats( 'default' );

		return date_i18n( $format, $local, FALSE );
	}

	public static function humanTimeDiff( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'    => _x( 'Now', 'Utilities: Human Time Diff', GNETWORK_TEXTDOMAIN ),
				'_s_ago' => _x( '%s ago', 'Utilities: Human Time Diff', GNETWORK_TEXTDOMAIN ),
				'in__s'  => _x( 'in %s', 'Utilities: Human Time Diff', GNETWORK_TEXTDOMAIN ),

				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_years'   => _nx_noop( '%s year', '%s years', 'Utilities: Human Time Diff: Noop', GNETWORK_TEXTDOMAIN ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::humanTimeDiff( $timestamp, $now, $strings );
	}

	public static function htmlFromSeconds( $seconds, $round = FALSE )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'sep' => _x( ', ', 'Utilities: From Seconds: Seperator', GNETWORK_TEXTDOMAIN ),

				'noop_seconds' => _nx_noop( '%s second', '%s seconds', 'Utilities: From Seconds: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Utilities: From Seconds: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Utilities: From Seconds: Noop', GNETWORK_TEXTDOMAIN ),
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Utilities: From Seconds: Noop', GNETWORK_TEXTDOMAIN ),
			];

		return Date::htmlFromSeconds( $seconds, $round, $strings );
	}

	// not used yet!
	public static function moment( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
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
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::moment( $timestamp, $now, $strings );
	}

	public static function getDateEditRow( $timestamp, $class = FALSE )
	{
		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$formats = self::dateFormats( FALSE );

		$html = '<span class="-date-date" title="'.HTML::escape( date_i18n( $formats['timeonly'], $timestamp ) );
		$html.= '" data-time="'.date( 'c', $timestamp ).'">'.date_i18n( $formats['default'], $timestamp ).'</span>';

		$html.= '&nbsp;(<span class="-date-diff" title="';
		$html.= HTML::escape( date_i18n( $formats['fulltime'], $timestamp ) ).'">';
		$html.= self::humanTimeDiff( $timestamp ).'</span>)';

		return $class ? '<span class="'.$class.'">'.$html.'</span>' : $html;
	}

	public static function getModifiedEditRow( $post, $class = FALSE )
	{
		$timestamp = strtotime( $post->post_modified );
		$formats   = self::dateFormats( FALSE );

		$html = '<span class="-date-modified" title="'.HTML::escape( date_i18n( $formats['default'], $timestamp ) );
		$html.='" data-time="'.date( 'c', $timestamp ).'">'.self::humanTimeDiff( $timestamp ).'</span>';

		$edit_last = get_post_meta( $post->ID, '_edit_last', TRUE );

		if ( $edit_last && $post->post_author != $edit_last )
			$html.= '&nbsp;(<span class="-edit-last">'.WordPress::getAuthorEditHTML( $post->post_type, $edit_last ).'</span>)';

		return $class ? '<span class="'.$class.'">'.$html.'</span>' : $html;
	}

	public static function htmlCurrent( $format = NULL, $class = FALSE, $title = FALSE )
	{
		return Date::htmlCurrent( ( is_null( $format ) ? self::dateFormats( 'datetime' ) : $format ), $class, $title );
	}

	public static function dateFormat( $timestamp, $context = 'default' )
	{
		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		return date_i18n( self::dateFormats( $context ), $timestamp );
	}

	// @SEE: http://www.phpformatdate.com/
	public static function dateFormats( $context = 'default' )
	{
		static $formats;

		if ( empty( $formats ) )
			$formats = apply_filters( 'custom_date_formats', [
				'fulltime'  => _x( 'l, M j, Y @ H:i', 'Date Format', GNETWORK_TEXTDOMAIN ),
				'datetime'  => _x( 'M j, Y @ G:i', 'Date Format', GNETWORK_TEXTDOMAIN ),
				'dateonly'  => _x( 'l, F j, Y', 'Date Format', GNETWORK_TEXTDOMAIN ),
				'timedate'  => _x( 'H:i - F j, Y', 'Date Format', GNETWORK_TEXTDOMAIN ),
				'timeampm'  => _x( 'g:i a', 'Date Format', GNETWORK_TEXTDOMAIN ),
				'timeonly'  => _x( 'H:i', 'Date Format', GNETWORK_TEXTDOMAIN ),
				'monthday'  => _x( 'n/j', 'Date Format', GNETWORK_TEXTDOMAIN ),
				'default'   => _x( 'm/d/Y', 'Date Format', GNETWORK_TEXTDOMAIN ),
				'wordpress' => get_option( 'date_format' ),
			] );

		if ( FALSE === $context )
			return $formats;

		if ( isset( $formats[$context] ) )
			return $formats[$context];

		return $formats['default'];
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

	public static function prepTitle( $text )
	{
		if ( ! $text )
			return '';

		$text = apply_filters( 'the_title', $text, 0 );
		$text = apply_filters( 'string_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return trim( $text );
	}

	public static function prepDescription( $text, $shortcode = TRUE )
	{
		if ( ! $text )
			return '';

		if ( $shortcode )
			$text = do_shortcode( $text, TRUE );

		$text = apply_filters( 'html_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return wpautop( $text );
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache()
	{
		global $gNetworkMustache;

		if ( ! empty( $gNetworkMustache ) )
			return $gNetworkMustache;

		$gNetworkMustache = new \Mustache_Engine( [
			'template_class_prefix'  => '__MyTemplates_',
			'cache'                  => GNETWORK_DIR.'assets/views/cache', // get_temp_dir().'mustache',
			'cache_file_mode'        => FS_CHMOD_FILE,
			'cache_lambda_templates' => TRUE,
			'loader'                 => new \Mustache_Loader_FilesystemLoader( GNETWORK_DIR.'assets/views' ),
			'partials_loader'        => new \Mustache_Loader_FilesystemLoader( GNETWORK_DIR.'assets/views/partials' ),
			// 'logger'                 => new Mustache_Logger_StreamLogger('php://stderr'),
			// 'strict_callables'       => TRUE,
			// 'pragmas'                => [Mustache_Engine::PRAGMA_FILTERS],
			// 'helpers' => [
			// 	'i18n' => function( $text ){
			// 		// do something translatey here...
			// 	},
			// ],
			'escape' => function( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		] );

		return $gNetworkMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	public static function renderMustache( $part, $data = [], $echo = TRUE )
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
				array_filter( array_merge( [
					join( _x( '&ldquo;, &rdquo;', 'Utilities: Join Items Helper', GNETWORK_TEXTDOMAIN ),
					array_slice( $items, 0, -1 ) ) ],
					array_slice( $items, -1 ) ) ) )
			._x( '&ldquo;', 'Utilities: Join Items Helper', GNETWORK_TEXTDOMAIN ).'.';
	}

	public static function getJoined( $items, $before = '', $after = '', $empty = '' )
	{
		if ( count( $items ) )
			return $before.join( _x( ', ', 'Utilities: Item Seperator', GNETWORK_TEXTDOMAIN ), $items ).$after;

		return $empty;
	}

	public static function getSeperated( $string, $delimiters = NULL, $delimiter = '|' )
	{
		if ( is_array( $string ) )
			return $string;

		if ( is_null( $delimiters ) )
			$delimiters = [ '/', '،', '؛', ';', ',' ];

		return explode( $delimiter, str_ireplace( $delimiters, $delimiter, $string ) );
	}

	public static function trimChars( $text, $length = 45, $append = '&nbsp;&hellip;' )
	{
		$append = '<span title="'.HTML::escape( $text ).'">'.$append.'</span>';

		return Text::trimChars( $text, $length, $append );
	}

	public static function getCounted( $count, $template = '%s' )
	{
		return sprintf( $template, '<span class="-count" data-count="'.$count.'">'.Number::format( $count ).'</span>' );
	}

	public static function getLayout( $name, $require = FALSE, $no_cache = FALSE )
	{
		$layout = locate_template( $name );

		if ( ! $layout && file_exists( WP_CONTENT_DIR.'/'.$name.'.php' ) )
			$layout = WP_CONTENT_DIR.'/'.$name.'.php';

		if ( ! $layout )
			$layout = GNETWORK_DIR.'includes/Layouts/'.$name.'.php';

		if ( $no_cache )
			WordPress::doNotCache();

		if ( $require && $layout )
			require_once( $layout );
		else
			return $layout;
	}

	public static function linkStyleSheet( $css, $version = GNETWORK_VERSION, $media = 'all' )
	{
		HTML::linkStyleSheet( GNETWORK_URL.'assets/css/'.$css.( is_rtl() ? '-rtl' : '' ).'.css', $version, $media );
	}

	public static function customStyleSheet( $css, $link = TRUE, $version = GNETWORK_VERSION )
	{
		$file = WordPress::customFile( $css, FALSE );

		if ( $link && $file )
			HTML::linkStyleSheet( $file, $version );

		return $file;
	}

	public static function enqueueScript( $asset, $dep = [ 'jquery' ], $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/js' )
	{
		$handle  = strtolower( self::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( $handle, $base.$path.'/'.$asset.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function enqueueScriptVendor( $asset, $dep = [], $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/js/vendor' )
	{
		return self::enqueueScript( $asset, $dep, $version, $base, $path );
	}

	public static function enqueueTimeAgo()
	{
		$callback = [ 'gPersianDateTimeAgo', 'enqueue' ];

		if ( ! is_callable( $callback ) )
			return FALSE;

		return call_user_func( $callback );
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( self::BASE.'_tinymce_strings', [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.gnetwork", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	public static function mdExtra( $markdown )
	{
		global $gNetworkParsedownExtra;

		if ( ! class_exists( 'ParsedownExtra' ) )
			return $markdown;

		if ( empty( $gNetworkParsedownExtra ) )
			$gNetworkParsedownExtra = new \ParsedownExtra();

		return $gNetworkParsedownExtra->text( $markdown );
	}

	// @SEE: https://core.trac.wordpress.org/ticket/24661
	// @SEE: https://core.trac.wordpress.org/ticket/22363
	// @SEE: https://core.trac.wordpress.org/ticket/35951
	// @SEE: https://core.trac.wordpress.org/ticket/30130
	// FIXME: check: `URLify::add_chars()`
	public static function URLifyDownCode( $string, $locale = NULL )
	{
		return \URLify::downcode( $string, self::getISO639( $locale ) );
	}

	public static function URLifyFilter( $string, $length = 60, $locale = NULL )
	{
		return \URLify::filter( $string, $length, self::getISO639( $locale ), TRUE, FALSE );
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

	// @REF: https://github.com/hbattat/verifyEmail
	public static function verifyEmail( $email = NULL, $from = NULL, $port = 25 )
	{
		$verifier = new \hbattat\VerifyEmail( $email, $from, $port );
		$results  = $verifier->verify();

		if ( WordPress::isDev() )
			self::__log( $verifier->get_debug() );

		return $results;
	}

	public static function log( $error = '[Unknown]', $message = FALSE, $extra = FALSE, $path = GNETWORK_DEBUG_LOG )
	{
		if ( ! $path )
			return;

		$log = '['.gmdate( 'd-M-Y H:i:s e' ).'] '; // [03-Feb-2015 21:20:19 UTC]
		$log.= $error.' ';
		$log.= HTTP::IP( TRUE );
		$log.= $message ? ' :: '.strip_tags( $message ) : '';
		$log.= $extra ? ' :: '.$extra : '';

		error_log( $log."\n", 3, $path );
	}

	// @REF: http://stackoverflow.com/a/16838443
	// @REF: https://en.wikipedia.org/wiki/ISO_639
	public static function getISO639( $locale = NULL )
	{
		if ( is_null( $locale ) )
			$locale = get_locale();

		if ( ! $locale )
			return 'en';

		$lang = explode( '_', $locale );

		return strtolower( $lang[0] );
	}

	public static function redirect404()
	{
		if ( $custom = gNetwork()->option( 'page_404', 'blog' ) )
			$location = get_page_link( $custom );
		else
			$location = home_url( '/not-found' );

		WordPress::redirect( $location, 303 );
	}
}

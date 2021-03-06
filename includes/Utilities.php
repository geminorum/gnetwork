<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\Date;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\Text;
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
				/* translators: %1$s: plugin name, %2$s: version number */
				_x( 'A new version of %1$s is available. Please update to version %2$s to ensure compatibility with your WordPress.', 'Utilities: Update Notice', 'gnetwork' ),
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
		/* translators: %s: time string */
		return sprintf( _x( '%s ago', 'Utilities: Human Time Ago', 'gnetwork' ), human_time_diff( $from, $to ) );
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
				'now'    => _x( 'Now', 'Utilities: Human Time Diff', 'gnetwork' ),
				/* translators: %s: time string */
				'_s_ago' => _x( '%s ago', 'Utilities: Human Time Diff', 'gnetwork' ),
				/* translators: %s: time string */
				'in__s'  => _x( 'in %s', 'Utilities: Human Time Diff', 'gnetwork' ),

				/* translators: %s: number of minutes */
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: %s: number of hours */
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: %s: number of days */
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: %s: number of weeks */
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: %s: number of months */
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: %s: number of years */
				'noop_years'   => _nx_noop( '%s year', '%s years', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
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
				'sep' => _x( ', ', 'Utilities: From Seconds: Seperator', 'gnetwork' ),

				/* translators: %s: number of seconds */
				'noop_seconds' => _nx_noop( '%s second', '%s seconds', 'Utilities: From Seconds: Noop', 'gnetwork' ),
				/* translators: %s: number of minutes */
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Utilities: From Seconds: Noop', 'gnetwork' ),
				/* translators: %s: number of hours */
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Utilities: From Seconds: Noop', 'gnetwork' ),
				/* translators: %s: number of days */
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Utilities: From Seconds: Noop', 'gnetwork' ),
			];

		return Date::htmlFromSeconds( $seconds, $round, $strings );
	}

	// not used yet!
	public static function moment( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'            => _x( 'Now', 'Utilities: Date: Moment', 'gnetwork' ),
				'just_now'       => _x( 'Just now', 'Utilities: Date: Moment', 'gnetwork' ),
				'one_minute_ago' => _x( 'One minute ago', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: %s: number of minutes */
				'_s_minutes_ago' => _x( '%s minutes ago', 'Utilities: Date: Moment', 'gnetwork' ),
				'one_hour_ago'   => _x( 'One hour ago', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: %s: number of hours */
				'_s_hours_ago'   => _x( '%s hours ago', 'Utilities: Date: Moment', 'gnetwork' ),
				'yesterday'      => _x( 'Yesterday', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: %s: number of days */
				'_s_days_ago'    => _x( '%s days ago', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: %s: number of weeks */
				'_s_weeks_ago'   => _x( '%s weeks ago', 'Utilities: Date: Moment', 'gnetwork' ),
				'last_month'     => _x( 'Last month', 'Utilities: Date: Moment', 'gnetwork' ),
				'last_year'      => _x( 'Last year', 'Utilities: Date: Moment', 'gnetwork' ),
				'in_a_minute'    => _x( 'in a minute', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: %s: number of minutes */
				'in__s_minutes'  => _x( 'in %s minutes', 'Utilities: Date: Moment', 'gnetwork' ),
				'in_an_hour'     => _x( 'in an hour', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: %s: number of hours */
				'in__s_hours'    => _x( 'in %s hours', 'Utilities: Date: Moment', 'gnetwork' ),
				'tomorrow'       => _x( 'Tomorrow', 'Utilities: Date: Moment', 'gnetwork' ),
				'next_week'      => _x( 'next week', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: %s: number of weeks */
				'in__s_weeks'    => _x( 'in %s weeks', 'Utilities: Date: Moment', 'gnetwork' ),
				'next_month'     => _x( 'next month', 'Utilities: Date: Moment', 'gnetwork' ),
				'format_l'       => _x( 'l', 'Utilities: Date: Moment', 'gnetwork' ),
				'format_f_y'     => _x( 'F Y', 'Utilities: Date: Moment', 'gnetwork' ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::moment( $timestamp, $now, $strings );
	}

	public static function getDateEditRow( $timestamp, $class = FALSE )
	{
		if ( empty( $timestamp ) )
			return self::htmlEmpty();

		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$formats = self::dateFormats( FALSE );

		$html = '<span class="-date-date" title="'.HTML::escape( date_i18n( $formats['timeonly'], $timestamp ) );
		$html.= '" data-time="'.date( 'c', $timestamp ).'">'.date_i18n( $formats['default'], $timestamp ).'</span>';

		$html.= '&nbsp;(<span class="-date-diff" title="';
		$html.= HTML::escape( date_i18n( $formats['fulltime'], $timestamp ) ).'">';
		$html.= self::humanTimeDiff( $timestamp ).'</span>)';

		return $class ? '<span class="'.HTML::prepClass( $class ).'">'.$html.'</span>' : $html;
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

		return $class ? '<span class="'.HTML::prepClass( $class ).'">'.$html.'</span>' : $html;
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
				'fulltime'  => _x( 'l, M j, Y @ H:i', 'Date Format', 'gnetwork' ),
				'datetime'  => _x( 'M j, Y @ G:i', 'Date Format', 'gnetwork' ),
				'dateonly'  => _x( 'l, F j, Y', 'Date Format', 'gnetwork' ),
				'timedate'  => _x( 'H:i - F j, Y', 'Date Format', 'gnetwork' ),
				'timeampm'  => _x( 'g:i a', 'Date Format', 'gnetwork' ),
				'timeonly'  => _x( 'H:i', 'Date Format', 'gnetwork' ),
				'monthday'  => _x( 'n/j', 'Date Format', 'gnetwork' ),
				'default'   => _x( 'm/d/Y', 'Date Format', 'gnetwork' ),
				'wordpress' => get_option( 'date_format' ),
			] );

		if ( FALSE === $context )
			return $formats;

		return empty( $formats[$context] )
			? $formats['default']
			: $formats[$context];
	}

	public static function getPostTitle( $post, $fallback = NULL )
	{
		$title = apply_filters( 'the_title', $post->post_title, $post->ID );

		if ( ! empty( $title ) )
			return $title;

		if ( FALSE === $fallback )
			return '';

		if ( is_null( $fallback ) )
			return _x( '(untitled)', 'Utilities: Post Title', 'gnetwork' );

		return $fallback;
	}

	public static function kses( $text, $context = 'none', $allowed = NULL )
	{
		if ( is_null( $allowed ) ) {

			if ( 'text' == $context )
				$allowed = [
					'a'       => [ 'class' => TRUE, 'title' => TRUE, 'href' => TRUE ],
					'abbr'    => [ 'class' => TRUE, 'title' => TRUE ],
					'acronym' => [ 'class' => TRUE, 'title' => TRUE ],
					'code'    => [ 'class' => TRUE ],
					'em'      => [ 'class' => TRUE ],
					'strong'  => [ 'class' => TRUE ],
					'i'       => [ 'class' => TRUE ],
					'b'       => [ 'class' => TRUE ],
					'span'    => [ 'class' => TRUE ],
					'br'      => [],
				];

			else if ( 'html' == $context )
				$allowed = wp_kses_allowed_html();

			else if ( 'none' == $context )
				$allowed = [];
		}

		return apply_filters( static::BASE.'_kses', wp_kses( $text, $allowed ), $allowed, $context );
	}

	public static function prepTitle( $text, $post_id = 0 )
	{
		if ( ! $text )
			return '';

		$text = apply_filters( 'the_title', $text, $post_id );
		$text = apply_filters( 'string_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return trim( $text );
	}

	public static function prepDescription( $text, $shortcode = TRUE, $autop = TRUE )
	{
		if ( ! $text )
			return '';

		if ( $shortcode )
			$text = do_shortcode( $text, TRUE );

		$text = apply_filters( 'html_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return $autop ? wpautop( $text ) : $text;
	}

	public static function prepContact( $value, $title = NULL )
	{
		if ( is_email( $value ) )
			$prepared = HTML::mailto( $value, $title );

		else if ( URL::isValid( $value ) )
			$prepared = HTML::link( $title, URL::untrail( $value ) );

		else if ( is_numeric( str_ireplace( [ '+', '-', '.' ], '', $value ) ) )
			$prepared = HTML::tel( $value, FALSE, $title );

		else
			$prepared = HTML::escape( $value );

		return apply_filters( static::BASE.'_prep_contact', $prepared, $value, $title );
	}

	public static function htmlEmpty( $class = '', $title_attr = NULL )
	{
		return is_null( $title_attr )
			? '<span class="-empty '.$class.'">&mdash;</span>'
			: sprintf( '<span title="%s" class="'.HTML::prepClass( '-empty', $class ).'">&mdash;</span>', $title_attr );
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache( $base = GNETWORK_DIR )
	{
		global $gNetworkMustache;

		if ( ! empty( $gNetworkMustache ) )
			return $gNetworkMustache;

		$gNetworkMustache = new \Mustache_Engine( [
			'template_class_prefix' => '__'.static::BASE.'_',
			'cache_file_mode'        => FS_CHMOD_FILE,
			// 'cache'                  => $base.'assets/views/cache',
			'cache'                  => get_temp_dir(),

			'loader'          => new \Mustache_Loader_FilesystemLoader( $base.'assets/views' ),
			'partials_loader' => new \Mustache_Loader_FilesystemLoader( $base.'assets/views/partials' ),
			'escape'          => function( $value ) {
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
			_x( '&rdquo;', 'Utilities: Join Items Helper', 'gnetwork' )
			.implode( _x( '&ldquo; and &rdquo;', 'Utilities: Join Items Helper', 'gnetwork' ),
				array_filter( array_merge( [
					implode( _x( '&ldquo;, &rdquo;', 'Utilities: Join Items Helper', 'gnetwork' ),
					array_slice( $items, 0, -1 ) ) ],
					array_slice( $items, -1 ) ) ) )
			._x( '&ldquo;', 'Utilities: Join Items Helper', 'gnetwork' ).'.';
	}

	public static function getJoined( $items, $before = '', $after = '', $empty = '' )
	{
		if ( $items && count( $items ) )
			return $before.implode( _x( ', ', 'Utilities: Item Seperator', 'gnetwork' ), $items ).$after;

		return $empty;
	}

	public static function getSeparated( $string, $delimiters = NULL, $limit = NULL, $delimiter = '|' )
	{
		if ( is_array( $string ) )
			return $string;

		if ( is_null( $delimiters ) )
			$delimiters = [
				'/',
				'،',
				'؛',
				';',
				',',
				// '-',
				// '_',
				'|',
			];

		$string = str_ireplace( $delimiters, $delimiter, $string );

		$seperated = is_null( $limit )
			? explode( $delimiter, $string )
			: explode( $delimiter, $string, $limit );

		return array_unique( array_filter( $seperated, 'trim' ) );
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
		$content = WP_CONTENT_DIR.'/'.$name.'.php';
		$plugin  = GNETWORK_DIR.'includes/Layouts/'.$name.'.php';
		$layout  = locate_template( 'system-layouts/'.$name );

		if ( ! $layout && is_readable( $content ) )
			$layout = $content;

		if ( ! $layout && is_readable( $plugin ) )
			$layout = $plugin;

		if ( $no_cache && $layout )
			WordPress::doNotCache();

		if ( $require && $layout )
			require_once $layout;
		else
			return $layout;
	}

	public static function linkStyleSheet( $css, $version = GNETWORK_VERSION, $media = 'all', $echo = TRUE )
	{
		return HTML::linkStyleSheet( GNETWORK_URL.'assets/css/'.$css.( is_rtl() ? '-rtl' : '' ).'.css', $version, $media, $echo );
	}

	public static function customStyleSheet( $css, $link = TRUE, $version = GNETWORK_VERSION )
	{
		$file = WordPress::customFile( $css, FALSE );

		if ( $link && $file )
			HTML::linkStyleSheet( $file, $version );

		return $file;
	}

	public static function mdExtra( $markdown )
	{
		global $gNetworkParsedownExtra;

		if ( ! class_exists( 'ParsedownExtra' ) || empty( $markdown ) )
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
			self::_log( $verifier->get_debug() );

		return $results;
	}

	// @RFEF: https://github.com/10up/Async-Transients
	public static function getTransient( $key, $callback, $args = [] )
	{
		return \TenUp\AsyncTransients\get_async_transient( static::BASE.'-'.$key, $callback, $args );
	}

	// USAGE: function callback( $args ) { $data = NULL; Utilities::setTransient( $key, $data ); }
	public static function setTransient( $key, $data, $ttl = GNETWORK_CACHE_TTL )
	{
		return \TenUp\AsyncTransients\set_async_transient( static::BASE.'-'.$key, $data, $ttl );
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

	// FIXME: move to Core\L10n
	// @REF: https://en.wikipedia.org/wiki/ISO_639
	// @REF: http://stackoverflow.com/a/16838443
	// @REF: `bp_core_register_common_scripts()`
	// @REF: https://make.wordpress.org/polyglots/handbook/translating/packaging-localized-wordpress/working-with-the-translation-repository/#repository-file-structure
	public static function getISO639( $locale = NULL )
	{
		if ( is_null( $locale ) )
			$locale = get_locale();

		if ( ! $locale )
			return 'en';

		$ISO639 = str_replace( '_', '-', strtolower( $locale ) );
		return substr( $ISO639, 0, strpos( $ISO639, '-' ) );
	}

	public static function redirectHome()
	{
		WordPress::redirect( get_home_url(), 303 );
	}

	public static function redirect404()
	{
		if ( $custom = gNetwork()->option( 'page_404', 'notfound' ) )
			$location = get_page_link( $custom );
		else
			$location = GNETWORK_REDIRECT_404_URL;

		WordPress::redirect( $location, 303 );
	}

	public static function htmlSSLfromURL( $url )
	{
		if ( Text::has( $url, 'https://' ) ) {
			echo HTML::getDashicon( 'lock', _x( 'SSL Enabled', 'Utilities: Title', 'gnetwork' ), '-success' );
			return TRUE;
		}

		echo HTML::getDashicon( 'unlock', _x( 'SSL Disabled', 'Utilities: Title', 'gnetwork' ), '-danger' );
		return FALSE;
	}

	public static function buttonImportRemoteContent( $remote, $target, $enqueue = TRUE )
	{
		if ( ! $remote )
			return '';

		Scripts::enqueueScript( 'api.remote.content' );

		$data   = [ 'action' => 'import-remote-content', 'remote' => $remote, 'target' => $target ];
		$label  = HTML::getDashicon( 'download' ).' '._x( 'Import', 'Utilities: Remote Content', 'gnetwork' ).'&nbsp;';
		$title  = _x( 'Import from a remote content.', 'Utilities: Remote Content', 'gnetwork' );
		$button = HTML::button( $label, '#', $title, TRUE, $data );

		$icon = HTML::tag( 'a', [
			'href'   => $remote,
			'target' => '_blank',
			'class'  => '-icon-wrap',
			'data'  => [
				'tooltip'     => _x( 'See the remote content.', 'Utilities: Remote Content', 'gnetwork' ),
				'tooltip-pos' => HTML::rtl() ? 'left' : 'right',
			],
		], HTML::getDashicon( 'external' ) );

		return $button.' '.$icon;
	}

	public static function buttonDataLogs( $constant, $option = NULL )
	{
		if ( ! $constant ) {

			HTML::desc( _x( 'Logging data disabled by constant.', 'Utilities', 'gnetwork' ) );

		} else if ( $option ) {

			if ( ! is_dir( $constant ) || ! wp_is_writable( $constant ) ) {

				HTML::desc( _x( 'Log folder not exists or writable.', 'Utilities', 'gnetwork' ) );

				echo '<p class="submit -wrap-buttons">';
					Settings::submitButton( 'create_log_folder', _x( 'Create Log Folder', 'Utilities', 'gnetwork' ), 'small' );
				echo '</p>';

			} else {

				/* translators: %s: log folder path */
				HTML::desc( sprintf( _x( 'Log folder exists and writable on: %s', 'Utilities', 'gnetwork' ), HTML::tag( 'code', $constant ) ) );

				if ( ! file_exists( $constant.'/.htaccess' ) )
					/* translators: %s: .htaccess */
					HTML::desc( sprintf( _x( 'Warning: %s not found!', 'Utilities', 'gnetwork' ), '<code>.htaccess</code>' ) );
			}

		} else {

			HTML::desc( _x( 'Data logs are disabled.', 'Utilities', 'gnetwork' ), TRUE, '-empty' );
		}
	}

	public static function emptyDataLogs( $path )
	{
		if ( ! is_dir( $path ) || ! wp_is_writable( $path ) )
			echo HTML::error( _x( 'Log folder not exists or writable.', 'Utilities', 'gnetwork' ) );

		else
			echo HTML::warning( _x( 'No Logs!', 'Utilities', 'gnetwork' ) );
	}

	// @SOURCE: http://stackoverflow.com/a/14744288
	public static function getDataLogs( $path, $limit, $paged = 1, $ext = 'json', $old = NULL )
	{
		if ( ! $path )
			return [ [], [] ];

		$files = glob( File::normalize( $path.'/*.'.$ext ) );

		if ( empty( $files ) )
			return [ [], [] ];

		$i    = 0;
		$logs = [];

		usort( $files, function( $a, $b ) {
			return filemtime( $b ) - filemtime( $a );
		} );

		$pages  = ceil( count( $files ) / $limit );
		$offset = ( $paged - 1 ) * $limit;
		$filter = array_slice( $files, $offset, $limit );

		foreach ( $filter as $log ) {

			if ( $i == $limit )
				break;

			if ( ! is_null( $old ) && filemtime( $log ) < $old )
				continue;

			if ( $data = json_decode( File::getContents( $log ), TRUE ) )
				$logs[] = array_merge( [
					'file' => basename( $log, '.json' ),
					'size' => filesize( $log ),
					'date' => filemtime( $log ),
				], $data );

			$i++;
		}

		$pagination = HTML::tablePagination( count( $files ), $pages, $limit, $paged );

		return [ $logs, $pagination ];
	}
}

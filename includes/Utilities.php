<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

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
			echo Core\HTML::info( sprintf(
				/* translators: `%1$s`: plugin name, `%2$s`: version number */
				_x( 'A new version of %1$s is available. Please update to version %2$s to ensure compatibility with your WordPress.', 'Utilities: Update Notice', 'gnetwork' ),
				Core\HTML::link( $updates[$plugin]->Name, $updates[$plugin]->PluginURI, TRUE ),
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

		return preg_replace_callback( $pattern, static function ( $matches ) {
			return '<b><span title="'.Core\HTML::escape( self::humanTimeAgo( strtotime( $matches[1] ) ) ).'">['.$matches[1].']</span></b>';
		}, $string, $limit );
	}

	public static function highlightIP( $string, $limit = -1 )
	{
		// @REF: http://regexr.com/35833
		$pattern = "/((((25[0-5])|(2[0-4]\d)|([01]?\d?\d)))\.){3}((((25[0-5])|(2[0-4]\d)|([01]?\d?\d))))/i";

		return preg_replace_callback( $pattern, static function ( $matches ) {
			return gnetwork_ip_lookup( $matches[0] );
		}, $string, $limit );
	}

	public static function htmlHumanTime( $timestamp, $flip = FALSE )
	{
		if ( ! $timestamp )
			return $timestamp;

		if ( ! Core\Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$now = current_time( 'timestamp', FALSE );

		if ( $flip )
			return '<span class="-date-diff" title="'
					.Core\HTML::escape( self::dateFormat( $timestamp, 'fulltime' ) ).'">'
					.self::humanTimeDiff( $timestamp, $now )
				.'</span>';

		return '<span class="-time" title="'
			.Core\HTML::escape( self::humanTimeAgo( $timestamp, $now ) ).'">'
			.self::humanTimeDiffRound( $timestamp, NULL, self::dateFormats( 'default' ), $now )
		.'</span>';
	}

	public static function humanTimeAgo( $from, $to = '' )
	{
		return sprintf(
			/* translators: `%s`: time string */
			_x( '%s ago', 'Utilities: Human Time Ago', 'gnetwork' ),
			human_time_diff( $from, $to )
		);
	}

	public static function humanTimeDiffRound( $local, $round = NULL, $format = NULL, $now = NULL )
	{
		if ( is_null( $now ) )
			$now = current_time( 'timestamp', FALSE );

		if ( FALSE === $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $round ) )
			$round = Core\Date::DAY_IN_SECONDS;

		$diff = $now - $local;

		if ( $diff > 0 && $diff < $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $format ) )
			$format = self::dateFormats( 'default' );

		return Core\Date::get( $format, $local );
	}

	public static function humanTimeDiff( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'    => _x( 'Now', 'Utilities: Human Time Diff', 'gnetwork' ),
				/* translators: `%s`: time string */
				'_s_ago' => _x( '%s ago', 'Utilities: Human Time Diff', 'gnetwork' ),
				/* translators: `%s`: time string */
				'in__s'  => _x( 'in %s', 'Utilities: Human Time Diff', 'gnetwork' ),

				/* translators: `%s`: number of minutes */
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: `%s`: number of hours */
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: `%s`: number of days */
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: `%s`: number of weeks */
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: `%s`: number of months */
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
				/* translators: `%s`: number of years */
				'noop_years'   => _nx_noop( '%s year', '%s years', 'Utilities: Human Time Diff: Noop', 'gnetwork' ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Core\Date::humanTimeDiff( $timestamp, $now, $strings );
	}

	public static function htmlFromSeconds( $seconds, $round = FALSE )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'sep' => WordPress\Strings::separator(),

				/* translators: `%s`: number of seconds */
				'noop_seconds' => _nx_noop( '%s second', '%s seconds', 'Utilities: From Seconds: Noop', 'gnetwork' ),
				/* translators: `%s`: number of minutes */
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Utilities: From Seconds: Noop', 'gnetwork' ),
				/* translators: `%s`: number of hours */
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Utilities: From Seconds: Noop', 'gnetwork' ),
				/* translators: `%s`: number of days */
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Utilities: From Seconds: Noop', 'gnetwork' ),
			];

		return Core\Date::htmlFromSeconds( $seconds, $round, $strings );
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
				/* translators: `%s`: number of minutes */
				'_s_minutes_ago' => _x( '%s minutes ago', 'Utilities: Date: Moment', 'gnetwork' ),
				'one_hour_ago'   => _x( 'One hour ago', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: `%s`: number of hours */
				'_s_hours_ago'   => _x( '%s hours ago', 'Utilities: Date: Moment', 'gnetwork' ),
				'yesterday'      => _x( 'Yesterday', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: `%s`: number of days */
				'_s_days_ago'    => _x( '%s days ago', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: `%s`: number of weeks */
				'_s_weeks_ago'   => _x( '%s weeks ago', 'Utilities: Date: Moment', 'gnetwork' ),
				'last_month'     => _x( 'Last month', 'Utilities: Date: Moment', 'gnetwork' ),
				'last_year'      => _x( 'Last year', 'Utilities: Date: Moment', 'gnetwork' ),
				'in_a_minute'    => _x( 'in a minute', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: `%s`: number of minutes */
				'in__s_minutes'  => _x( 'in %s minutes', 'Utilities: Date: Moment', 'gnetwork' ),
				'in_an_hour'     => _x( 'in an hour', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: `%s`: number of hours */
				'in__s_hours'    => _x( 'in %s hours', 'Utilities: Date: Moment', 'gnetwork' ),
				'tomorrow'       => _x( 'Tomorrow', 'Utilities: Date: Moment', 'gnetwork' ),
				'next_week'      => _x( 'next week', 'Utilities: Date: Moment', 'gnetwork' ),
				/* translators: `%s`: number of weeks */
				'in__s_weeks'    => _x( 'in %s weeks', 'Utilities: Date: Moment', 'gnetwork' ),
				'next_month'     => _x( 'next month', 'Utilities: Date: Moment', 'gnetwork' ),
				'format_l'       => _x( 'l', 'Utilities: Date: Moment', 'gnetwork' ),
				'format_f_y'     => _x( 'F Y', 'Utilities: Date: Moment', 'gnetwork' ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Core\Date::moment( $timestamp, $now, $strings );
	}

	public static function getDateEditRow( $timestamp, $class = FALSE )
	{
		if ( empty( $timestamp ) )
			return self::htmlEmpty();

		if ( ! Core\Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$formats = self::dateFormats( FALSE );

		$html = '<span class="-date-date" title="'.Core\HTML::escape( Core\Date::get( $formats['timeonly'], $timestamp ) );
		$html.= '" data-time="'.date( 'c', $timestamp ).'">'.Core\Date::get( $formats['default'], $timestamp ).'</span>';

		$html.= '&nbsp;(<span class="-date-diff" title="';
		$html.= Core\HTML::escape( Core\Date::get( $formats['fulltime'], $timestamp ) ).'">';
		$html.= self::humanTimeDiff( $timestamp ).'</span>)';

		return $class ? '<span class="'.Core\HTML::prepClass( $class ).'">'.$html.'</span>' : $html;
	}

	public static function getModifiedEditRow( $post, $class = FALSE )
	{
		if ( empty( $post->post_modified ) )
			return self::htmlEmpty();

		$timestamp = strtotime( $post->post_modified );
		$formats   = self::dateFormats( FALSE );

		$html = '<span class="-date-modified" title="'.Core\HTML::escape( Core\Date::get( $formats['default'], $timestamp ) );
		$html.='" data-time="'.date( 'c', $timestamp ).'">'.self::humanTimeDiff( $timestamp ).'</span>';

		$edit_last = get_post_meta( $post->ID, '_edit_last', TRUE );

		if ( $edit_last && $post->post_author != $edit_last )
			$html.= '&nbsp;(<span class="-edit-last">'.WordPress\PostType::authorEditMarkup( $post->post_type, $edit_last ).'</span>)';

		return $class ? '<span class="'.Core\HTML::prepClass( $class ).'">'.$html.'</span>' : $html;
	}

	public static function htmlCurrent( $format = NULL, $class = FALSE, $title = FALSE )
	{
		return Core\Date::htmlCurrent( ( is_null( $format ) ? self::dateFormats( 'datetime' ) : $format ), $class, $title );
	}

	public static function dateFormat( $timestamp, $context = 'default' )
	{
		if ( ! Core\Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		return Core\Date::get( self::dateFormats( $context ), $timestamp );
	}

	// @SEE: http://www.phpformatdate.com/
	public static function dateFormats( $context = 'default' )
	{
		static $formats;

		if ( empty( $formats ) )
			$formats = apply_filters( static::BASE.'_custom_date_formats', [
				'age'      => _x( 'm/d/Y', 'Date Format: `age`', 'gnetwork' ),
				'dateonly' => _x( 'l, F j, Y', 'Date Format: `dateonly`', 'gnetwork' ),
				'datetime' => _x( 'M j, Y @ G:i', 'Date Format: `datetime`', 'gnetwork' ),
				'default'  => _x( 'm/d/Y', 'Date Format: `default`', 'gnetwork' ),
				'fulltime' => _x( 'l, M j, Y @ H:i', 'Date Format: `fulltime`', 'gnetwork' ),
				'monthday' => _x( 'n/j', 'Date Format: `monthday`', 'gnetwork' ),
				'print'    => _x( 'j/n/Y', 'Date Format: `print`', 'gnetwork' ),
				'timeampm' => _x( 'g:i a', 'Date Format: `timeampm`', 'gnetwork' ),
				'timedate' => _x( 'H:i - F j, Y', 'Date Format: `timedate`', 'gnetwork' ),
				'timeonly' => _x( 'H:i', 'Date Format: `timeonly`', 'gnetwork' ),

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
			$text = apply_shortcodes( $text, TRUE );

		$text = apply_filters( 'geditorial_markdown_to_html', $text, $autop );
		$text = apply_filters( 'html_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return $autop ? wpautop( $text ) : $text;
	}

	public static function prepContact( $value, $title = NULL )
	{
		if ( Core\Email::is( $value ) )
			$prepared = Core\HTML::mailto( $value, FALSE, $title );

		else if ( Core\URL::isValid( $value ) )
			$prepared = Core\HTML::link( $title, Core\URL::untrail( $value ) );

		else if ( is_numeric( str_ireplace( [ '+', '-', '.' ], '', $value ) ) )
			$prepared = Core\HTML::tel( $value, FALSE, $title );

		else
			$prepared = Core\HTML::escape( $value );

		return apply_filters( static::BASE.'_prep_contact', $prepared, $value, $title );
	}

	public static function htmlEmpty( $class = '', $title_attr = NULL )
	{
		return is_null( $title_attr )
			? '<span class="-empty '.$class.'">&mdash;</span>'
			: sprintf( '<span title="%s" class="'.Core\HTML::prepClass( '-empty', $class ).'">&mdash;</span>', $title_attr );
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache( $base = GNETWORK_DIR )
	{
		global $gNetworkMustache;

		if ( ! empty( $gNetworkMustache ) )
			return $gNetworkMustache;

		$gNetworkMustache = new \Mustache\Engine( [
			'template_class_prefix' => sprintf( '__%s_', static::BASE ),

			'cache_file_mode' => FS_CHMOD_FILE,
			'cache'           => get_temp_dir(),   // $base.'assets/views/cache',

			'loader'          => new \Mustache\Loader\FilesystemLoader( $base.'assets/views' ),
			'partials_loader' => new \Mustache\Loader\FilesystemLoader( $base.'assets/views/partials' ),
			'escape'          => static function ( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		] );

		return $gNetworkMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	// TODO: drop mustache for gNetwork
	public static function renderMustache( $part, $data = [], $verbose = TRUE )
	{
		$mustache = self::getMustache();
		$template = $mustache->loadTemplate( $part );
		$html     = $template->render( $data );

		if ( ! $verbose )
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
			return $before.implode( WordPress\Strings::separator(), $items ).$after;

		return $empty;
	}

	public static function getCounted( $count, $template = '%s' )
	{
		return sprintf( $template, '<span class="-count" data-count="'.$count.'">'.Core\Number::format( $count ).'</span>' );
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
			WordPress\Site::doNotCache();

		if ( $require && $layout )
			require_once $layout;
		else
			return $layout;
	}

	public static function linkStyleSheet( $css, $version = GNETWORK_VERSION, $media = 'all', $verbose = TRUE )
	{
		return Core\HTML::linkStyleSheet( GNETWORK_URL.'assets/css/'.$css.( is_rtl() ? '-rtl' : '' ).'.css', $version, $media, $verbose );
	}

	public static function customStyleSheet( $css, $link = TRUE, $version = GNETWORK_VERSION )
	{
		$file = WordPress\Site::customFile( $css, FALSE );

		if ( $link && $file )
			Core\HTML::linkStyleSheet( $file, $version );

		return $file;
	}

	// @source https://github.com/ergebnis/front-matter/blob/main/src/YamlParser.php
	private const FRONTMATTER_PATTERN = "{^(?P<frontMatterWithDelimiters>(?:---)[\r\n|\n]*(?P<frontMatterWithoutDelimiters>.*?)[\r\n|\n]+(?:---)[\r\n|\n]{0,1})(?P<bodyMatter>.*)$}s";

	public static function stripFrontMatter( $text )
	{
		if ( empty( $text ) )
			return $text;

		if ( ! preg_match( static::FRONTMATTER_PATTERN, (string) $text, $matches ) )
			return $text;

		return str_replace( $matches['frontMatterWithDelimiters'], '', $text );
	}

	public static function mdExtra( $markdown, $autop = TRUE, $strip_frontmatter = TRUE )
	{
		global $gNetworkParsedownExtra;

		if ( empty( $markdown ) || ! class_exists( 'ParsedownExtra' ) )
			return $strip_frontmatter ? self::stripFrontMatter( $markdown ) : $markdown;

		if ( empty( $gNetworkParsedownExtra ) )
			/**
			 * @package `erusev/parsedown-extra`
			 * @source https://github.com/erusev/parsedown-extra
			 * @docs https://parsedown.org/extra/
			 */
			$gNetworkParsedownExtra = new \ParsedownExtra();

		if ( $strip_frontmatter )
			$markdown = self::stripFrontMatter( $markdown );

		return $autop
			? $gNetworkParsedownExtra->text( $markdown )
			// @REF: https://github.com/erusev/parsedown/issues/43#issuecomment-40753665
			: $gNetworkParsedownExtra->line( $markdown );
	}

	// @SEE: https://core.trac.wordpress.org/ticket/24661
	// @SEE: https://core.trac.wordpress.org/ticket/22363
	// @SEE: https://core.trac.wordpress.org/ticket/35951
	// @SEE: https://core.trac.wordpress.org/ticket/30130
	// FIXME: check: `URLify::add_chars()`
	public static function URLifyDownCode( $string, $locale = NULL )
	{
		return $string ? @\URLify::downcode( $string, Core\L10n::getISO639( $locale ) ) : $string;
	}

	public static function URLifyFilter( $string, $length = 60, $locale = NULL )
	{
		return $string ? @\URLify::filter( $string, $length, Core\L10n::getISO639( $locale ), TRUE, FALSE ) : $string;
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

	// @SEE: https://github.com/FGRibreau/mailchecker
	// @REF: https://github.com/hbattat/verifyEmail
	public static function verifyEmail( $email = NULL, $from = NULL, $port = 25 )
	{
		$verifier = new \hbattat\VerifyEmail( $email, $from, $port );
		$results  = $verifier->verify();

		if ( WordPress\IsIt::dev() )
			self::_log( $verifier->get_debug() );

		return $results;
	}

	// Caution: As of 12 April 2024, this project is archived and no longer being actively maintained.
	// USAGE: `function callback( $args ) { $data = NULL; Utilities::setTransient( $key, $data ); }`
	// @REF: https://github.com/10up/Async-Transients
	// @package `10up/async-transients`
	public static function getTransient( $key, $callback, $args = [] )
	{
		return \TenUp\AsyncTransients\get_async_transient( static::BASE.'-'.$key, $callback, $args );
	}

	// @package `10up/async-transients`
	public static function setTransient( $key, $data, $ttl = GNETWORK_CACHE_TTL )
	{
		return \TenUp\AsyncTransients\set_async_transient( static::BASE.'-'.$key, $data, $ttl );
	}

	public static function log( $error = '[Unknown]', $message = FALSE, $extra = FALSE, $path = GNETWORK_DEBUG_LOG )
	{
		if ( ! $path )
			return;

		$log = '['.gmdate( 'd-M-Y H:i:s e' ).'] '; // `[03-Feb-2015 21:20:19 UTC]`
		$log.= $error.' ';
		$log.= Core\HTTP::IP( TRUE );
		$log.= $message ? ' :: '.strip_tags( $message ) : '';
		$log.= $extra ? ' :: '.$extra : '';

		error_log( $log."\n", 3, $path );
	}

	public static function redirectHome()
	{
		WordPress\Redirect::doWP( get_home_url(), 303 );
	}

	public static function redirect404()
	{
		if ( $custom = gNetwork()->option( 'page_404', 'notfound' ) )
			$location = get_page_link( $custom );
		else
			$location = GNETWORK_REDIRECT_404_URL;

		WordPress\Redirect::doWP( $location, 303 );
	}

	public static function htmlSSLfromURL( $url )
	{
		if ( Core\Text::starts( $url, 'https://' ) ) {
			echo Core\HTML::getDashicon( 'lock', _x( 'SSL Enabled', 'Utilities: Title', 'gnetwork' ), '-success' );
			return TRUE;
		}

		echo Core\HTML::getDashicon( 'unlock', _x( 'SSL Disabled', 'Utilities: Title', 'gnetwork' ), '-danger' );
		return FALSE;
	}

	public static function buttonImportRemoteContent( $remote, $target, $enqueue = TRUE )
	{
		if ( ! $remote )
			return '';

		Scripts::enqueueScript( 'api.remote.content' );

		$title  = _x( 'Import from a remote content.', 'Utilities: Remote Content', 'gnetwork' );
		$label  = sprintf( '%s %s&nbsp;', Core\HTML::getDashicon( 'download' ), _x( 'Import', 'Utilities: Remote Content', 'gnetwork' ) );
		$button = Core\HTML::button( $label, '#', $title, TRUE, [
			'action' => 'import-remote-content',
			'remote' => $remote,
			'target' => $target,
		] );

		$icon = Core\HTML::tag( 'a', [
			'href'   => $remote,
			'target' => '_blank',
			'class'  => '-icon-wrap',
			'data'  => [
				'tooltip'     => _x( 'See the remote content.', 'Utilities: Remote Content', 'gnetwork' ),
				'tooltip-pos' => Core\HTML::rtl() ? 'left' : 'right',
			],
		], Core\HTML::getDashicon( 'external' ) );

		return $button.' '.$icon;
	}

	public static function buttonDataLogs( $constant, $option = NULL )
	{
		if ( ! $constant ) {

			Core\HTML::desc( _x( 'Logging data disabled by constant.', 'Utilities', 'gnetwork' ) );

		} else if ( $option ) {

			if ( ! is_dir( $constant ) || ! Core\File::writable( $constant ) ) {

				Core\HTML::desc( _x( 'Log folder not exists or writable.', 'Utilities', 'gnetwork' ) );

				echo '<p class="submit -wrap-buttons">';
					Settings::submitButton( 'create_log_folder', _x( 'Create Log Folder', 'Utilities', 'gnetwork' ), 'small' );
				echo '</p>';

			} else {

				/* translators: `%s`: log folder path */
				Core\HTML::desc( sprintf( _x( 'Log folder exists and writable on: %s', 'Utilities', 'gnetwork' ), Core\HTML::tag( 'code', $constant ) ) );

				if ( ! file_exists( $constant.'/.htaccess' ) )
					/* translators: `%s`: `.htaccess` */
					Core\HTML::desc( sprintf( _x( 'Warning: %s not found!', 'Utilities', 'gnetwork' ), '<code>.htaccess</code>' ) );
			}

		} else {

			Core\HTML::desc( _x( 'Data logs are disabled.', 'Utilities', 'gnetwork' ), TRUE, '-empty' );
		}
	}

	public static function emptyDataLogs( $path )
	{
		if ( ! is_dir( $path ) || ! Core\File::writable( $path ) )
			echo Core\HTML::error( _x( 'Log folder not exists or writable.', 'Utilities', 'gnetwork' ) );

		else
			echo Core\HTML::warning( _x( 'No Logs!', 'Utilities', 'gnetwork' ) );
	}

	// @SOURCE: http://stackoverflow.com/a/14744288
	public static function getDataLogs( $path, $limit, $paged = 1, $ext = 'json', $old = NULL )
	{
		if ( ! $path )
			return [ [], [] ];

		$files = glob( Core\File::normalize( $path.'/*.'.$ext ) );

		if ( empty( $files ) )
			return [ [], [] ];

		$i    = 0;
		$logs = [];

		usort( $files, static function ( $a, $b ) {
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

			if ( $data = json_decode( Core\File::getContents( $log ), TRUE ) )
				$logs[] = array_merge( [
					'file' => Core\File::basename( $log, '.json' ),
					'size' => Core\File::size( $log ),
					'date' => filemtime( $log ),
				], $data );

			$i++;
		}

		$pagination = Core\HTML::tablePagination( count( $files ), $pages, $limit, $paged );

		return [ $logs, $pagination ];
	}

	public static function getCacheDIR( $sub, $base = NULL )
	{
		if ( ! GNETWORK_CACHE_DIR )
			return FALSE;

		$base = $base ?? self::BASE;
		$path = GNETWORK_CACHE_DIR.( $base ? '/'.$base.'/' : '/' ).$sub;
		$path = Core\File::untrail( Core\File::normalize( $path ) );

		if ( @file_exists( $path ) )
			return $path;

		if ( ! wp_mkdir_p( $path ) )
			return FALSE;

		// FIXME: check if the folder is writable
		Core\File::putIndexHTML( $path, GNETWORK_DIR.'index.html' );
		Core\File::putDoNotBackup( $path );

		return $path;
	}

	public static function getCacheURL( $sub, $base = NULL )
	{
		if ( ! GNETWORK_CACHE_DIR ) // correct, we check for path constant
			return FALSE;

		$base = $base ?? self::BASE;

		return Core\URL::untrail( GNETWORK_CACHE_URL.( $base ? '/'.$base.'/' : '/' ).$sub );
	}

	public static function getQRCode( $data, $type = 'text', $size = 300, $tag = FALSE, $cache = TRUE, $sub = 'qrcodes', $base = NULL )
	{
		if ( ! GNETWORK_CACHE_DIR )
			$cache = FALSE;

		switch ( $type ) {
			case 'url'    : $prepared = Core\DataCode::prepDataURL( $data ); break;
			case 'email'  : $prepared = Core\DataCode::prepDataEmail( is_array( $data ) ? $data : [ 'email' => $data ] ); break;
			case 'phone'  : $prepared = Core\DataCode::prepDataPhone( $data ); break;
			case 'sms'    : $prepared = Core\DataCode::prepDataSMS( is_array( $data ) ? $data : [ 'mobile' => $data ] ); break;
			case 'contact': $prepared = Core\DataCode::prepDataContact( is_array( $data ) ? $data : [ 'name' => $data ] ); break;
			default       : $prepared = Core\Text::trim( $data ); break;
		}

		if ( ! $cache )
			return Core\DataCode::getQRCode( $prepared, [ 'tag' => $tag, 'size' => $size ] );

		$file = sprintf( '%s-%s.svg', md5( maybe_serialize( $prepared ) ), $size );
		$url  = Core\URL::trail( self::getCacheURL( $sub, $base ) ).$file;
		$path = self::getCacheDIR( $sub, $base );

		if ( ! Core\File::exists( $file, $path ) ) {

			if ( ! Core\DataCode::cacheQRCode( Core\File::join( $path, $file ), $prepared, [ 'size' => $size ] ) )
				return $tag ? '' : FALSE;
		}

		return $tag ? Core\HTML::tag( 'img', [
			'src'      => $url,
			'width'    => $size,
			'height'   => $size,
			'alt'      => '',
			'decoding' => 'async',
			'loading'  => 'lazy',
		] ) : $url;
	}

	public static function getAspectRatioClass( $ratio = NULL, $fallback = '4x3', $mainclass = '-responsive' )
	{
		$supported = [
			'64x27',
			'16x9',
			'4x3',
			'1x1',
		];

		if ( empty( $ratio ) )
			$suffix = $fallback;

		else if ( in_array( $ratio, $supported, TRUE ) )
			$suffix = $ratio;

		return sprintf( '%s -ratio%s', $mainclass, $suffix );
	}

	// @REF: https://github.com/donatj/PhpUserAgent
	public static function uaInfo()
	{
		$uaInfo = \donatj\UserAgent\parse_user_agent();

		return [
			'platform'        => $uaInfo[donatj\UserAgent\PLATFORM],
			'browser'         => $uaInfo[donatj\UserAgent\BROWSER],
			'browser_version' => $uaInfo[donatj\UserAgent\BROWSER_VERSION],
		];
	}
}

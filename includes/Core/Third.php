<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Third extends Base
{

	// TODO: move to `Misc\ThirdParty`

	public static function getHandleURL( $string, $service, $prefix = '@' )
	{
		$url = $string;

		// Bail if already is a link.
		if ( URL::isValid( $url ) )
			return $url;

		switch ( $service ) {

			case 'twitter':

				$base = 'https://twitter.com/intent/user?screen_name=';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'tiktok':

				$base = 'https://www.tiktok.com/@';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'instagram':

				$base = 'https://www.instagram.com/';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'telegram':

				$base = 'https://t.me/';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'facebook':

				$base = 'https://www.facebook.com/';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'youtube':

				$base = 'https://www.youtube.com/@';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'aparat':

				$base = 'https://www.aparat.com/';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'behkhaan':

				$base = 'https://behkhaan.ir/profile/';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'fidibo':

				$base = 'https://fidibo.com/publishers/';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'eitaa':

				$base = 'https://eitaa.com/';
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;

			case 'wikipedia':

				// https://en.wikipedia.org/w/index.php?title=Style_(manner_of_address)
				$base = sprintf( 'https://%s.wikipedia.org/wiki/', L10n::getISO639() );
				$url  = self::getHandle( $string, TRUE, $base, $prefix );
				break;
		}

		return $url ? URL::untrail( $url ) : $url;
	}

	// @REF: https://gist.github.com/boonebgorges/5537311
	public static function getHandle( $string, $url = FALSE, $base = '', $prefix = '@' )
	{
		$parts = URL::parse( $string );

		if ( empty( $parts['host'] ) )
			$handle = 0 === strpos( $string, '@' ) ? substr( $string, 1 ) : $string;
		else
			$handle = trim( $parts['path'], '/\\' );

		return $url ? URL::trail( $base.$handle ) : $prefix.$handle;
	}

	public static function htmlHandle( $string, $service )
	{
		return HTML::link( HTML::wrapLTR( self::getHandle( $string ) ), self::getHandle( $string, TRUE, $service ) );
	}

	public static function htmlTwitterIntent( $string, $thickbox = FALSE )
	{
		$handle = self::getHandle( $string );
		$url    = self::getHandleURL( $string, 'twitter' );

		if ( $thickbox ) {

			$args  = [
				'href'    => add_query_arg( [ 'TB_iframe' => '1' ], $url ),
				'title'   => HTML::wrapLTR( $handle ),
				'class'   => '-twitter thickbox',
				'onclick' => 'return false;',
			];

			if ( function_exists( 'add_thickbox' ) )
				add_thickbox();

		} else {

			$args = [ 'href' => $url, 'class' => '-twitter' ];
		}

		return HTML::tag( 'a', $args, HTML::wrapLTR( $handle ) );
	}

	/**
	 * Take the input of a "Twitter" field, decide whether it's a handle
	 * or a URL, and generate a URL
	 *
	 * @source: https://gist.github.com/boonebgorges/5537311
	 *
	 * @param string $string provided twitter token
	 * @param boolean $url convert token to profile link
	 * @param string $base prefix if the URL
	 * @return string handle or the URL
	 */
	public static function getTwitter( $string, $url = FALSE, $base = 'https://twitter.com/' )
	{
		self::_dep( 'Third::getHandle()' );

		$parts = URL::parse( $string );

		if ( empty( $parts['host'] ) )
			$handle = 0 === strpos( $string, '@' ) ? substr( $string, 1 ) : $string;
		else
			$handle = trim( $parts['path'], '/\\' );

		return $url ? URL::trail( $base.$handle ) : '@'.$handle;
	}

	// @REF: https://developers.google.com/google-apps/calendar/
	// @SOURCE: https://wordpress.org/plugins/gcal-events-list/
	public static function getGoogleCalendarEvents( $atts )
	{
		$args = self::atts( [
			'calendar_id' => FALSE,
			'api_key'     => '',
			'time_min'    => '',
			'max_results' => 5,
		], $atts );

		if ( ! $args['calendar_id'] )
			return FALSE;

		$time = $args['time_min'] && Date::isInFormat( $args['time_min'] ) ? $args['time_min'] : date( 'Y-m-d' );

		$url = 'https://www.googleapis.com/calendar/v3/calendars/'
			.urlencode( $args['calendar_id'] )
			.'/events?key='.$args['api_key']
			.'&maxResults='.$args['max_results']
			.'&orderBy=startTime'
			.'&singleEvents=true'
			.'&timeMin='.$time.'T00:00:00Z';

		return HTTP::getJSON( $url, [], FALSE );
	}

	// @API: https://developers.google.com/chart/infographics/docs/qr_codes
	// @EXAMPLE: https://createqrcode.appspot.com/
	// @SEE: https://github.com/endroid/QrCode
	// @SEE: https://github.com/aferrandini/PHPQRCode
	public static function getGoogleQRCode( $data, $atts = [] )
	{
		$args = self::atts( [
			'tag'        => TRUE,
			'size'       => 150,
			'encoding'   => 'UTF-8',
			'correction' => 'H', // 'L', 'M', 'Q', 'H'
			'margin'     => 0,
			'url'        => 'https://chart.googleapis.com/chart',
		], $atts );

		$src = add_query_arg( [
			'cht'  => 'qr',
			'chs'  => $args['size'].'x'.$args['size'],
			'chl'  => urlencode( $data ),
			'chld' => $args['correction'].'|'.$args['margin'],
			'choe' => $args['encoding'],
		], $args['url'] );

		if ( ! $args['tag'] )
			return $src;

		return HTML::tag( 'img', [
			'src'    => $src,
			'width'  => $args['size'],
			'height' => $args['size'],
			'alt'    => strip_tags( $data ),
		] );
	}

	// @REF: https://generatewp.com/easy-custom-mobile-chrome-address-bar-colors-wordpress/
	// @REF: `rest_parse_hex_color()`
	public static function htmlThemeColor( $color )
	{
		if ( ! $color )
			return;

		if ( ! preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color, $matches ) )
			return;

		echo '<meta name="theme-color" content="'.$color.'" />'."\n";
		echo '<meta name="msapplication-navbutton-color" content="'.$color.'">'."\n";
		// echo '<meta name="apple-mobile-web-app-capable" content="yes">'."\n"; // DEPRECATED
		echo '<meta name="mobile-web-app-capable" content="yes">'."\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'."\n";
	}
}

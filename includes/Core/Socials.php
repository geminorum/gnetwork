<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Socials extends Base
{
	public static function getHandleURL( $string, $service, $prefix = '@' )
	{
		$url = $string;

		// Bail if already is a link.
		if ( URL::isValid( $url ) )
			return $url;

		switch ( $service ) {

			case 'x':
			case 'twitter':

				$base = 'https://x.com/intent/user?screen_name=';
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
		$parts = parse_url( $string );

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
	#[\Deprecated('USE `Core\Socials::getHandle()`')]
	public static function getTwitter( $string, $url = FALSE, $base = 'https://x.com/' )
	{
		$parts = parse_url( $string );

		if ( empty( $parts['host'] ) )
			$handle = 0 === strpos( $string, '@' ) ? substr( $string, 1 ) : $string;
		else
			$handle = trim( $parts['path'], '/\\' );

		return $url ? URL::trail( $base.$handle ) : '@'.$handle;
	}
}

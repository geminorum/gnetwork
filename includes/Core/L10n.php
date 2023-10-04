<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class L10n extends Base
{

	public static function locale( $site = FALSE )
	{
		return $site ? get_locale() : determine_locale();
	}

	public static function getNooped( $singular, $plural )
	{
		return array( 'singular' => $singular, 'plural' => $plural, 'context' => NULL, 'domain' => NULL );
	}

	public static function sprintfNooped( $nooped, $count )
	{
		return sprintf( translate_nooped_plural( $nooped, $count ), Number::format( $count ) );
	}

	/**
	 * Retrieves current locale base in ISO-639.
	 *
	 * @REF: https://en.wikipedia.org/wiki/ISO_639
	 * @REF: http://stackoverflow.com/a/16838443
	 * @REF: `bp_core_register_common_scripts()`
	 * @REF: https://make.wordpress.org/polyglots/handbook/translating/packaging-localized-wordpress/working-with-the-translation-repository/#repository-file-structure
	 *
	 * @param  string|null $locale
	 * @return string      $iso639
	 */
	public static function getISO639( $locale = NULL )
	{
		if ( is_null( $locale ) )
			$locale = self::locale();

		if ( ! $locale )
			return 'en';

		$dashed = str_replace( '_', '-', strtolower( $locale ) );
		return substr( $dashed, 0, strpos( $dashed, '-' ) );
	}
}

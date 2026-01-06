<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class L10n extends Base
{

	public static function rtl()
	{
		return function_exists( 'is_rtl' ) ? is_rtl() : FALSE;
	}

	/**
	 * Retrieves the current locale.
	 *
	 * @param bool $site
	 * @return string
	 */
	public static function locale( $site = FALSE )
	{
		return $site ? get_locale() : determine_locale();
	}

	public static function sanitize( $locale = NULL )
	{
		if ( '' === $locale || 'en' === $locale )
			return 'en_US';

		if ( 'fa' === $locale )
			return 'fa_IR';

		// if ( 'ar' === $locale )
		// 	return 'ar_'; // WTF?!

		if ( $locale && ! is_null( $locale ) )
			return $locale;

		return self::locale();
	}

	public static function calendar( $locale = NULL, $fallback = 'gregorian' )
	{
		switch ( $locale ?? self::locale() ) {

			case 'fa':
			case 'fa_AF':
			case 'fa_IR': return 'persian';

			case 'ar': return 'islamic';

			case '': // empty means English!
			case 'en': return 'gregorian';
		}

		return $fallback;
	}

	// FIXME: UNFINISHED!
	// numeric formatting information
	public static function localeconv( $field = FALSE, $fallback = NULL )
	{
		$locale = localeconv();

		// returns all fields
		if ( FALSE === $field )
			return $locale;

		if ( array_key_exists( $field, $locale ) )
			return $locale[$field];

		else if ( ! is_null( $fallback ) )
			return $fallback;

		$info = '';

		switch ( $field ) {

			case 'decimal_point': $info = mb_convert_encoding( '&#1643;', 'UTF-8', 'HTML-ENTITIES' ); break; // Decimal point character
			case 'thousands_sep': $info = mb_convert_encoding( '&#1644;', 'UTF-8', 'HTML-ENTITIES' ); break; // Thousands separator
			case 'grouping': $info = []; break; // Array containing numeric groupings
			case 'int_curr_symbol': $info = 'ریال'; break; // International currency symbol (i.e. USD)
			case 'currency_symbol': $info = mb_convert_encoding( '&#65020;', 'UTF-8', 'HTML-ENTITIES' ); break; // Local currency symbol (i.e. $)
			case 'mon_decimal_point': $info = mb_convert_encoding( '&#1643;', 'UTF-8', 'HTML-ENTITIES' ); break; break; // Monetary decimal point character
			case 'mon_thousands_sep': $info = mb_convert_encoding( '&#1644;', 'UTF-8', 'HTML-ENTITIES' ); break; // Monetary thousands separator
			case 'mon_grouping': $info = []; break; // Array containing monetary groupings
			case 'positive_sign': $info = ''; break; // Sign for positive values
			case 'negative_sign': $info = '-'; break; // Sign for negative values
			case 'int_frac_digits': $info = ''; break; // International fractional digits
			case 'frac_digits': $info = ''; break; // Local fractional digits
			case 'p_cs_precedes': $info = ''; break; // `true` if currency_symbol precedes a positive value, `false` if it succeeds one
			case 'p_sep_by_space': $info = ''; break; // `true` if a space separates currency_symbol from a positive value, `false` otherwise
			case 'n_cs_precedes': $info = ''; break; // `true` if currency_symbol precedes a negative value, `false` if it succeeds one
			case 'n_sep_by_space': $info = ''; break; // `true` if a space separates currency_symbol from a negative value, `false` otherwise
			case 'p_sign_posn': $info = ''; break;
				// `0` - Parentheses surround the quantity and currency_symbol
				// `1` - The sign string precedes the quantity and currency_symbol
				// `2` - The sign string succeeds the quantity and currency_symbol
				// `3` - The sign string immediately precedes the currency_symbol
				// `4` - The sign string immediately succeeds the currency_symbol
			case 'n_sign_posn': $info = ''; break;
				// `0` - Parentheses surround the quantity and currency_symbol
				// `1` - The sign string precedes the quantity and currency_symbol
				// `2` - The sign string succeeds the quantity and currency_symbol
				// `3` - The sign string immediately precedes the currency_symbol
				// `4` - The sign string immediately succeeds the currency_symbol
		}

		return $info;
	}

	/**
	 * Retrieves current locale base in `ISO-639`.
	 * @SEE: https://www.iso.org/iso-3166-country-codes.html
	 *
	 * @REF: https://en.wikipedia.org/wiki/ISO_639
	 * @REF: http://stackoverflow.com/a/16838443
	 * @REF: `bp_core_register_common_scripts()`
	 * @REF: https://make.wordpress.org/polyglots/handbook/translating/packaging-localized-wordpress/working-with-the-translation-repository/#repository-file-structure
	 *
	 * @param string $locale
	 * @return string
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

	public static function getNooped( $singular, $plural )
	{
		return [
			'singular' => $singular,
			'plural'   => $plural,
			'context'  => NULL,
			'domain'   => NULL,
		];
	}

	public static function sprintfNooped( $nooped, $count )
	{
		return sprintf( translate_nooped_plural( $nooped, $count ), Number::format( $count ) );
	}

	/**
	 * Pluralizes a word in English.
	 * @source https://www.grammarly.com/blog/plural-nouns/
	 * TODO: support multiple words
	 *
	 * @param string $singular
	 * @return string
	 */
	public static function pluralize( $singular )
	{
		if ( ! $word = strtolower( trim( $singular ) ) )
			return $singular;

		$irregulars = [
			'addendum' => 'addenda',
			'analysis' => 'analyses',
			'locus'    => 'loci',
			'louse'    => 'lice',
			'oasis'    => 'oases',
			'ovum'     => 'ova',
			'child'    => 'children',
			'goose'    => 'geese',
			'man'      => 'men',
			'woman'    => 'women',
			'tooth'    => 'teeth',
			'foot'     => 'feet',
			'mouse'    => 'mice',
			'person'   => 'people',
			'sheep'    => 'sheep',
			'series'   => 'series',
			'species'  => 'species',
			'deer'     => 'deer',
		];

		if ( array_key_exists( $word, $irregulars ) )
			return $irregulars[$word];

		// $len = strlen( $word );
		$one = substr( $word, -1 );
		$two = substr( $word, -2 );

		switch ( $one ) {

			// @SEE: https://gist.github.com/effone/1e54f364559bf919af3be97b7f9d94af

			case 'y':

				$plural = in_array( $two, [ 'ay', 'ey', 'iy', 'oy', 'uy' ], TRUE )
					? sprintf( '%ss', $word )
					: sprintf( '%sies', substr( $word, 0, -1 ) );

				break;

			case 'h':

				$plural = in_array( $two, [ 'sh', 'ch' ], TRUE )
					? sprintf( '%ses', $word )
					: sprintf( '%ss', $word );

				break;

			case 'n':

				// WTF: `lessons`
				// $plural = in_array( $two, [ 'on' ], TRUE )
				// 	? sprintf( '%sa', substr( $word, 0, -2 ) )
				// 	: sprintf( '%ss', $word );

				$plural = sprintf( '%ss', $word );

				break;

			case 'o':

				$plural = in_array( $word, [ 'photo', 'piano', 'halo', 'gas', 'video' ], TRUE )
					? sprintf( '%ss', $word )
					: sprintf( '%ses', $word );

				break;

			case 's':

				// If the singular noun ends in -us, the plural ending is frequently -i.
				// cactus -> cacti
				// focus -> foci

				// WTF: `statuses`
				// $plural = in_array( $two, [ 'us' ], TRUE )
				// 	? sprintf( '%si', substr( $word, 0, -2 ) )
				// 	: sprintf( '%ses', $word );

				$plural = sprintf( '%ses', $word );

				break;

			case 'x':
			case 'z':

				$plural = sprintf( '%ses', $word );
				break;

			default:

				$plural = sprintf( '%ss', $word );
		}

		return $plural;
	}
}

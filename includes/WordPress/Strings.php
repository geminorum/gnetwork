<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Strings extends Core\Base
{

	/**
	 * Retrieves the list item separator based on the locale.
	 * NOTE: wrapper for `wp_get_list_item_separator()` @since WP 6.0.0
	 *
	 * @return string $separator
	 */
	public static function separator()
	{
		if ( function_exists( 'wp_get_list_item_separator' ) )
			return wp_get_list_item_separator();

		return __( ', ' );
	}

	// @OLD: `Helper::getStringsFromName()`
	public static function getNameForms( $name )
	{
		if ( ! $name )
			return FALSE;

		if ( ! is_array( $name ) )
			$name = [
				'singular' => $name,
				'plural'   => Core\L10n::pluralize( $name ),
			];

		if ( array_key_exists( 'domain', $name ) )
			$strings = [
				_nx( $name['singular'], $name['plural'], 2, $name['context'], $name['domain'] ),
				_nx( $name['singular'], $name['plural'], 1, $name['context'], $name['domain'] ),
			];

		else
			$strings = [
				$name['plural'],
				$name['singular'],
			];

		$strings[2] = Core\Text::strToLower( $strings[0] );
		$strings[3] = Core\Text::strToLower( $strings[1] );

		$strings[4] = '%s';

		return $strings;
	}

	public static function isEmpty( $string, $empties = NULL )
	{
		if ( self::empty( $string ) )
			return TRUE;

		if ( ! is_string( $string ) )
			return FALSE;

		$trimmed = Core\Text::trim( $string );

		if ( '' === $trimmed )
			return TRUE;

		if ( is_null( $empties ) )
			$empties = [
				"'", "''", "'''", "''''", "'''''", "''''''",
				'"', '""', '"""', '""""', '"""""', '""""""',
				'0', '00', '000', '0000', '00000', '000000','0000000','00000000','000000000','0000000000','00000000000','000000000000',
				'!', '!!', '!!!', '!!!!', '!!!!!', '!!!!!!','!!!!!!!','!!!!!!!!','!!!!!!!!!','!!!!!!!!!!','!!!!!!!!!!!','!!!!!!!!!!!!',
				'?', '??', '???', '????', '?????', '??????','???????','????????','?????????','??????????','???????????','????????????',
				'*', '**', '***', '****', '*****', '******',
				'…', '……', '………', '…………', '……………', '………………',
				'.', '..', '...', '....', '.....', '......',
				'-', '--', '---', '----', '-----', '------',
				'–', '––', '–––', '––––', '–––––', '––––––',
				'—', '——', '———', '————', '—————', '——————',
				'0000/00/00', '0000-00-00', '00/00/00', '00-00-00',
				'<p></p>',
				'<body><p></p></body>',
				'<body></body>',
				'<body> </body>',
				'null', 'NULL', 'Null',
				'false', 'FALSE', 'False',
				'zero', 'ZERO', 'Zero',
				'none', 'NONE', 'None',
				'ندارد', 'نامعلوم', 'هيچكدام', '؟',
			];

		foreach ( (array) $empties as $empty )
			if ( $empty === $trimmed )
				return TRUE;

		return FALSE;
	}

	public static function filterEmpty( $strings, $empties = NULL )
	{
		return array_filter( $strings, static function ( $value ) use ( $empties ) {

			if ( self::isEmpty( $value, $empties ) )
				return FALSE;

			return ! empty( $value );
		} );
	}

	public static function trimChars( $text, $length = 45, $append = '&nbsp;&hellip;' )
	{
		$append = '<span title="'.Core\HTML::escape( $text ).'">'.$append.'</span>';

		return Core\Text::trimChars( $text, $length, $append );
	}

	/**
	 * Separates given string by set of delimiters into an array.
	 *
	 * @param string $string
	 * @param string|array $delimiters
	 * @param int $limit
	 * @param string $delimiter
	 * @return array
	 */
	public static function getSeparated( $string, $delimiters = NULL, $limit = NULL, $delimiter = '|' )
	{
		if ( '0' === $string || 0 === $string )
			return [ '0' ];

		if ( empty( $string ) )
			return [];

		if ( is_array( $string ) )
			return Core\Arraay::prepString( $string );

		if ( is_null( $delimiters ) )
			$delimiters = [
				// '/',
				'،',
				'؛',
				';',
				',',
				// '-',
				// '_',
				'|',
			];

		else if ( $delimiters && is_string( $delimiters ) )
			$delimiters = Core\Arraay::prepSplitters( $delimiters, $delimiter );

		if ( ! empty( $delimiters ) )
			$string = str_ireplace( $delimiters, $delimiter, $string );

		$separated = is_null( $limit )
			? explode( $delimiter, $string )
			: explode( $delimiter, $string, $limit );

		return Core\Arraay::prepString( $separated );
	}

	public static function getJoined( $items, $before = '', $after = '', $empty = '', $separator = NULL )
	{
		if ( is_null( $separator ) )
			$separator = self::separator();

		if ( $items && count( $items ) )
			return $before.implode( $separator, $items ).$after;

		return $empty;
	}

	public static function joinWithLast( $parts, $between, $last )
	{
		return implode( $last, array_filter( array_merge( [ implode( $between, array_slice( $parts, 0, -1 ) ) ], array_slice( $parts, -1 ) ) ) );
	}

	public static function getPiped( $items, $before = '', $after = '', $empty = '', $separator = NULL )
	{
		return self::getJoined( $items, $before, $after, $empty, $separator ?? '|' );
	}

	public static function getCounted( $count, $template = '%s' )
	{
		if ( TRUE === $template )
			$template = ' <span class="-count-wrap">(%s)</span>';

		else if ( is_null( $template ) )
			$template = '%s';

		else if ( empty( $template ) )
			return '';

		return sprintf( $template, '<span class="-count" data-count="'.$count.'">'.Core\Number::format( $count ).'</span>' );
	}

	// @SOURCE: P2
	public static function excerptedTitle( $content, $word_count )
	{
		$content = Core\Text::stripTags( $content );
		$words   = preg_split(
			'/([\s_;?!\/\(\)\[\]{}<>\r\n\t"]|\.$|(?<=\D)[:,.\-]|[:,.\-](?=\D))/',
			$content,
			$word_count + 1,
			PREG_SPLIT_NO_EMPTY
		);

		if ( count( $words ) > $word_count ) {

			// remove remainder of words
			array_pop( $words );

			$content = implode( ' ', $words );
			$content.= '…';

		} else {

			$content = implode( ' ', $words );
		}

		return Core\Text::stripTags( $content );
	}

	/**
	 * Strips all HTML tags including script and style.
	 *
	 * @source `Yoast\WP\SEO\Helpers\String_Helper::strip_all_tags()`
	 *
	 * @param string $text The text to strip the tags from.
	 * @return string The processed string.
	 */
	public static function stripAllTags( $text )
	{
		return \wp_strip_all_tags( $text );
	}

	/**
	 * Standardize whitespace in a string.
	 *
	 * Replace line breaks, carriage returns, tabs with a space, then remove double spaces.
	 *
	 * @source `Yoast\WP\SEO\Helpers\String_Helper::standardize_whitespace()`
	 *
	 * @param string $text Text input to standardize.
	 * @return string
	 */
	public static function standardizeWhitespace( $text )
	{
		return \trim( \str_replace( '  ', ' ', \str_replace( [ "\t", "\n", "\r", "\f" ], ' ', $text ) ) );
	}

	/**
	 * First strip out registered and enclosing short-codes using native WordPress `strip_shortcodes()` function.
	 * Then strip out the short-codes with a filthy regex, because people don't properly register their short-codes.
	 *
	 * @source `Yoast\WP\SEO\Helpers\String_Helper::strip_shortcode()`
	 *
	 * @param string $text Input string that might contain short-codes.
	 * @return string String without short-codes.
	 */
	public static function stripShortCode( $text )
	{
		return \preg_replace( '`\[[^\]]+\]`s', '', \strip_shortcodes( $text ) );
	}

	public static function cleanupChars( $string, $html = FALSE )
	{
		if ( self::empty( $string ) )
			return $string;

		if ( ! class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			return apply_filters( 'string_format_i18n', $string );

		// return $html
		// 	? \geminorum\gNetwork\Core\Orthography::cleanupPersianHTML( $string )
		// 	: \geminorum\gNetwork\Core\Orthography::cleanupPersian( $string );

		return \geminorum\gNetwork\Core\Orthography::cleanupPersianChars( $string );
	}

	/**
	 * Filters text content and strips out disallowed HTML.
	 * NOTE: wrapper for `wp_kses()`
	 *
	 * @param string $text
	 * @param string $context
	 * @param array $allowed
	 * @return string
	 */
	public static function kses( $text, $context = 'none', $allowed = NULL )
	{
		if ( '' === $text )
			return $text;

		if ( is_null( $allowed ) ) {

			if ( 'text' == $context )
				/**
				 * Allows all most inline elements and strips all
				 * block level elements except `blockquote`
				 */
				$allowed = wp_kses_allowed_html( 'data' );

			else if ( 'html' == $context )
				/**
				 * Very permissive: allows pretty much all HTML to pass.
				 * Same as what's normally applied to `the_content` by default.
				 */
				$allowed = wp_kses_allowed_html( 'post' );

			else if ( 'none' == $context )
				$allowed = [];
		}

		return Core\Text::trim( wp_kses( $text, $allowed ) );
	}

	public static function ksesArray( $array, $context = 'none', $allowed = NULL )
	{
		foreach ( $array as $key => $value )
			$array[$key] = self::kses( $value, $context, $allowed );

		return $array;
	}

	public static function prepTitle( $text, $post_id = 0 )
	{
		if ( ! $text )
			return '';

		$text = apply_filters( 'the_title', $text, $post_id );
		$text = apply_filters( 'string_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return Core\Text::trim( $text );
	}

	public static function prepDescription( $text, $shortcode = TRUE, $autop = TRUE )
	{
		if ( ! $text )
			return '';

		if ( $shortcode )
			$text = ShortCode::apply( $text, TRUE );

		$text = apply_filters( 'geditorial_markdown_to_html', $text, $autop );
		$text = apply_filters( 'html_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return $autop ? wpautop( $text ) : $text;
	}

	// TODO: move to `Misc\PersianAddress`
	public static function prepAddress( $data, $context = 'display', $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		if ( ! $data = Core\Text::normalizeWhitespace( self::cleanupChars( $data ) ) )
			return $fallback;

		$data = trim( $data, '.-|…' );
		$data = str_ireplace( [ '_', '|', '–', '—'  ], '-', $data );
		$data = sprintf( ' %s ', $data ); // padding with space

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			$data = Core\Number::translate( $data );

			if ( class_exists( 'geminorum\\gEditorial\\Misc\\NumbersInPersian' ) )
				$data = geminorum\gEditorial\Misc\NumbersInPersian::textOrdinalToNumbers( $data, 100 );

			$prefixes = [
				'پلاک'   => 'پ',
				'خیابان' => 'خ',
				'بلوک'   => 'ب',
				'کوچه'   => 'ک',
				'فرعی'   => 'فرعی',
				'بلوار'  => 'بلوار',
				'بن بست' => 'بن‌بست',
			];

			foreach ( $prefixes as $from => $to ) {

				$pattern = sprintf( '/%s[\s]?([0-9۰-۹]+)/mu', preg_quote( $from ) );

				$data = preg_replace_callback( $pattern,
					static function ( $matches ) use ( $to ) {
						return sprintf( ' %s%s ', $to, Core\Text::trim( $matches[1] ) ); // padding with space
					}, $data );
			}

			$data = Core\Number::translatePersian( $data );
		}

		$data = preg_replace( '/\s+([\,\،])/mu', '$1', $data );
		$data = preg_replace( '/\s+([\-])/mu', '$1', $data );
		$data = preg_replace( '/([\-])\s+/mu', '$1', $data );

		return Core\Text::normalizeWhitespace( $data );
	}
}

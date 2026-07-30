<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Strings extends Core\Base
{

	/**
	 * Retrieves the list item separator based on the locale.
	 * NOTE: wrapper for `wp_get_list_item_separator()` @since WP 6.0.0
	 *
	 * @return string
	 */
	public static function separator(): string
	{
		if ( function_exists( 'wp_get_list_item_separator' ) )
			return wp_get_list_item_separator();

		return __( ', ' );
	}

	// @OLD: `Helper::getStringsFromName()`
	public static function getNameForms( mixed $name ): false|array
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

	/**
	 * Checks if the given input has truthy value.
	 *
	 * @param mixed $input
	 * @param array|string $reference
	 * @return bool
	 */
	public static function isTruthy( mixed $input, array|string|null $reference = NULL ): bool
	{
		if ( self::empty( $input ) )
			return FALSE;

		if ( TRUE === $input || 1 === $input )
			return TRUE;

		if ( ! $trimmed = Core\Text::trim( (string) $input ) )
			return FALSE;

		if ( is_null( $reference ) )
			$reference = [
				'TRUE',
				'true',
				'True',
				'1',
			];

		return in_array( $trimmed, (array) $reference, TRUE );
	}

	public static function isEmpty( string $string, array|string|null $reference = NULL ): bool
	{
		if ( self::empty( $string ) )
			return TRUE;

		if ( ! is_string( $string ) )
			return FALSE;

		$trimmed = Core\Text::trim( $string );

		if ( '' === $trimmed )
			return TRUE;

		if ( is_null( $reference ) )
			$reference = [
				'{}', '{{}}',
				'[]', '[[]]',
				'<>', '<<>>',
				'()', '(())',
				'«»', '««»»',
				'“”', '““””',
				':',
				';',
				',',
				'|',
				'&',
				'،',
				'؛',
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
				'0000/00/00', '0000-00-00', '0000\\00\\00', '00/00/00', '00-00-00', '00\\00\\00',
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

		foreach ( (array) $reference as $empty )
			if ( $empty === $trimmed )
				return TRUE;

		return FALSE;
	}

	public static function filterEmpty( array $strings, array|string|null $reference = NULL ): array
	{
		return array_filter( $strings,
			static function ( $value )
				use ( $reference ) {

				return ! self::isEmpty( $value, $reference );
			}
		);
	}

	public static function trimChars( string $text, int $length = 45, string $append = '&nbsp;&hellip;' ): string
	{
		$append = '<span title="'.Core\HTML::escape( $text ).'">'.$append.'</span>';

		return Core\Text::trimChars( $text, $length, $append );
	}

	/**
	 * Separates given string by set of delimiters into an array.
	 *
	 * @param mixed $string
	 * @param string|array $delimiters
	 * @param int $limit
	 * @param string $delimiter
	 * @return array
	 */
	public static function getSeparated( mixed $string, string|array|null $delimiters = NULL, ?int $limit = NULL, ?string $delimiter = NULL ): array
	{
		if ( '0' === $string || 0 === $string )
			return [ '0' ];

		if ( empty( $string ) )
			return [];

		if ( is_array( $string ) )
			return Core\Arraay::prepString( $string );

		$delimiter = $delimiter ?? '|';

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

	public static function getJoined( array $items, string $before = '', string $after = '', string $empty = '', ?string $separator = NULL ): string
	{
		if ( $items && count( $items ) )
			return $before.implode( $separator ?? self::separator(), $items ).$after;

		return $empty;
	}

	public static function joinWithLast( array $parts, string $between, string $last ): string
	{
		return implode( $last, array_filter( array_merge( [ implode( $between, array_slice( $parts, 0, -1 ) ) ], array_slice( $parts, -1 ) ) ) );
	}

	public static function getPiped( array $items, string $before = '', string $after = '', string $empty = '', ?string $separator = NULL ): string
	{
		return self::getJoined( $items, $before, $after, $empty, $separator ?? '|' );
	}

	public static function getCounted( int $count, string|bool|null $template = '%s', string $title = '' ): string
	{
		if ( TRUE === $template )
			$template = ' <span class="-count-wrap">(%s)</span>';

		else if ( is_null( $template ) )
			$template = '%s';

		else if ( empty( $template ) )
			return '';

		$values = [
			'-count',
			$count,
			Core\Number::format( $count ),
			Core\HTML::escape( $title ?: '' ),
		];

		$html = $title
			? vsprintf( '<span class="%1$s" data-count="%2$s" title="%4$s">%3$s</span>', $values )
			: vsprintf( '<span class="%1$s" data-count="%2$s">%3$s</span>', $values );

		return sprintf( $template, $html );
	}

	// @SOURCE: P2
	public static function excerptedTitle( string $content, int $word_count ): string
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

	public static function cleanupChars( string $input, bool $html = FALSE ): string
	{
		if ( ! $input = Core\Text::force( $input ) )
			return '';

		if ( ! class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			return apply_filters( 'string_format_i18n', $input );

		// return $html
		// 	? \geminorum\gNetwork\Core\Orthography::cleanupPersianHTML( $input )
		// 	: \geminorum\gNetwork\Core\Orthography::cleanupPersian( $input );

		return \geminorum\gNetwork\Core\Orthography::cleanupPersianChars( $input );
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
	public static function kses( string $text, string $context = 'none', ?array $allowed = NULL ): string
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

	public static function ksesArray( array $array, string $context = 'none', ?array $allowed = NULL ): array
	{
		foreach ( $array as $key => $value )
			$array[$key] = self::kses( $value, $context, $allowed );

		return $array;
	}

	/**
	 * Balances tags of string using a modified stack.
	 * NOTE: wrapper for `force_balance_tags()`
	 * NOTE: ignores the `use_balanceTags` option
	 *
	 * @param string $html
	 * @return string
	 */
	public static function balanceTags( string $html ): string
	{
		if ( self::isEmpty( $html ) )
			return '';

		return force_balance_tags( $html );
	}

	/**
	 * Generates a title attribute by name and description.
	 *
	 * @param string $name
	 * @param string $description
	 * @return string
	 */
	public static function makeTitleAttribute( ?string $name, ?string $description = NULL ): string
	{
		$text = '';

		if ( $name )
			$text.= apply_filters( 'string_format_i18n', $name );

		if ( ! $description )
			return $text;

		if ( ! $stripped = Core\Text::stripTags( $description ) )
			return $text;

		if ( ! $text )
			return $stripped;

		return sprintf( '%s :: %s', $text, $stripped );
	}

	/**
	 * Generates item labels based on given keys.
	 * @old `Ortho::keyLabels()`
	 *
	 * @param array $keys
	 * @param array $extra
	 * @return array
	 */
	public static function makeLabelsByKeys( array $keys, ?array $extra = NULL ): array
	{
		$words = $extra ?? [
			'nbsp',
			'html',
			'eol',
			'rlm',
			'zwnj',
			'zwj',
			'uri',
		];

		$list  = [];
		$upper = array_map( 'strtoupper', $words );

		foreach ( (array) $keys as $key )
			$list[$key] = str_ireplace( $words, $upper, Core\Text::titleCase( str_replace( '_', ' ', $key ) ) );

		return $list;
	}

	public static function prepTitle( string $text, int $post_id = 0 ): string
	{
		if ( ! $text )
			return '';

		$text = apply_filters( 'the_title', $text, $post_id );
		$text = apply_filters( 'string_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return Core\Text::trim( $text );
	}

	public static function prepDescription( string $text, bool $shortcode = TRUE, bool $autop = TRUE ): string
	{
		if ( ! $text )
			return '';

		if ( $shortcode )
			$text = ShortCode::apply( $text, TRUE );

		$text = self::balanceTags( $text );
		$text = apply_filters( 'geditorial_markdown_to_html', $text, $autop );
		$text = apply_filters( 'html_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return $autop ? wpautop( $text ) : Core\Text::trim( $text );
	}

	public static function stripByProp( array $data, string $key, array $list, string $subkey ): array
	{
		if ( ! empty( $data[$key] ) && ! empty( $list ) ) {

			foreach ( $data[$key] as $offset => $meta )
				if ( in_array( $meta[$subkey], $list, TRUE ) )
					unset( $data[$key][$offset] );

			// NOTE: mustache.JS needs not object but array!
			if ( ! empty( $data[$key] ) )
				$data[$key] = array_values( $data[$key] );
		}

		return $data;
	}

	public static function stripEmptyValues( array $data, string $key, string $subkey ): array
	{
		if ( ! empty( $data[$key] ) ) {

			foreach ( $data[$key] as $offset => $meta )
				if ( empty( $meta[$subkey] ) )
					unset( $data[$key][$offset] );

			// NOTE: mustache.JS needs not object but array!
			if ( ! empty( $data[$key] ) )
				$data[$key] = array_values( $data[$key] );
		}

		return $data;
	}
}

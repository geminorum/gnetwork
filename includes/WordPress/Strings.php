<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Strings extends Core\Base
{

	/**
	 * Retrieves the list item separator based on the locale.
	 * wrapper for `wp_get_list_item_separator()` @since WP 6.0.0
	 *
	 * @return string $separator
	 */
	public static function separator()
	{
		if ( function_exists( 'wp_get_list_item_separator' ) )
			return wp_get_list_item_separator();

		return __( ', ' ); // _x( ', ', 'Strings: Item Seperator', 'gnetwork' );
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
	 * @param null|string|array $delimiters
	 * @param null|int $limit
	 * @param string $delimiter
	 * @return array $separated
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
}

<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

/**
 * Further Readings:
 * - https://php.watch/versions/8.2/utf8_encode-utf8_decode-deprecated
 * - https://php.watch/versions/8.2/mbstring-qprint-base64-uuencode-html-entities-deprecated
 */

/**
 * - Since PHP 8.1.0 `ENT_COMPAT` changed to `ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401`
 * - It seems that `ENT_XML1` and `ENT_XHTML` are identical when decoding.
 */

class Coding extends Core\Base
{
	/**
	 * Converts all applicable characters to HTML entities.
	 * NOTE: wrapper for `htmlentities()`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function entityEncode( mixed $input ): string
	{
		return @htmlentities(
			Text::force( $input ),
			ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
			'UTF-8',
		);
	}

	/**
	 * Converts HTML entities to their corresponding characters.
	 * NOTE: same as `\WP_HTML_Decoder::decode_attribute( $input );`
	 * NOTE: wrapper for `html_entity_decode()`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function entityDecode( mixed $input ): string
	{
		return @html_entity_decode(
			Core\Text::force( $input ),
			ENT_QUOTES,
			'UTF-8',
		);
	}

	/**
	 * Converts special characters to HTML entities.
	 * Will convert double-quotes and leave single-quotes alone.
	 * NOTE: wrapper for `htmlspecialchars()`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function entityEncodeCOMPAT( mixed $input ): string
	{
		return @htmlspecialchars(
			Core\Text::force( $input ),
			ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
			'UTF-8',
		);
	}

	/**
	 * Converts special characters to HTML entities.
	 * Will convert both double and single quotes.
	 * NOTE: wrapper for `htmlspecialchars()`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function entityEncodeQUOTES( mixed $input ): string
	{
		return @htmlspecialchars(
			Core\Text::force( $input ),
			ENT_QUOTES | ENT_SUBSTITUTE,
			'UTF-8',
		);
	}
}

<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Text extends Base
{

	// like wp but without check for func_overload
	// @SOURCE: `seems_utf8()`
	public static function seemsUTF8( $string )
	{
		$length = strlen( $string );

		for ( $i = 0; $i < $length; $i++ ) {

			$c = ord( $string[$i] );

			if ( $c < 0x80 )
				$n = 0; // 0bbbbbbb

			else if ( ( $c & 0xE0 ) == 0xC0 )
				$n = 1; // 110bbbbb

			else if ( ( $c & 0xF0 ) == 0xE0 )
				$n = 2; // 1110bbbb

			else if ( ( $c & 0xF8 ) == 0xF0 )
				$n = 3; // 11110bbb

			else if ( ( $c & 0xFC ) == 0xF8 )
				$n = 4; // 111110bb

			else if ( ( $c & 0xFE ) == 0xFC )
				$n = 5; // 1111110b

			else
				return FALSE; // does not match any model

			for ( $j = 0; $j < $n; $j++ ) // n bytes matching 10bbbbbb follow ?
				if ( ( ++$i == $length )
					|| ( ( ord( $string[$i] ) & 0xC0 ) != 0x80 ) )
						return FALSE;
		}

		return TRUE;
	}

	// @SOURCE: `normalize_whitespace()`
	public static function normalizeWhitespace( $string )
	{
		// return preg_replace( '!\s+!', ' ', $string );
		// return preg_replace( '/\s\s+/', ' ', $string );

		return preg_replace(
			array( '/\n+/', '/[ \t]+/' ),
			array( "\n", ' ' ),
			str_replace( "\r", "\n", trim( $string ) )
		);
	}

	// @REF: http://stackoverflow.com/a/3226746/4864081
	public static function normalizeWhitespaceUTF8( $string, $check = FALSE )
	{
		if ( $check && ! self::seemsUTF8( $string ) )
			return self::normalizeWhitespace( $string );

		return preg_replace( '/[\p{Z}\s]{2,}/u', ' ', $string );
	}

	// @REF: _cleanup_image_add_caption()
	// remove any line breaks from inside the tags
	public static function noLineBreak( $string )
	{
		return preg_replace( '/[\r\n\t]+/', ' ', $string );
	}

	public static function stripWidthHeight( $string )
	{
		return preg_replace( '/(width|height)="\d*"\s/', '', $string );
	}

	public static function has( $haystack, $needles, $operator = 'OR' )
	{
		if ( ! is_array( $needles ) )
			return FALSE !== stripos( $haystack, $needles );

		if ( 'OR' == $operator ) {
			foreach ( $needles as $needle )
				if ( FALSE !== stripos( $haystack, $needle ) )
					return TRUE;

			return FALSE;
		}

		$has = FALSE;

		foreach ( $needles as $needle )
			if ( FALSE !== stripos( $haystack, $needle ) )
				$has = TRUE;

		return $has;
	}

	// @SEE: `mb_convert_case()`
	public static function strToLower( $string, $encoding = 'UTF-8' )
	{
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $string, $encoding ) : strtolower( $string );
	}

	public static function strLen( $string, $encoding = 'UTF-8' )
	{
		return function_exists( 'mb_strlen' ) ? mb_strlen( $string, $encoding ) : strlen( $string );
	}

	public static function subStr( $string, $start = 0, $length = 1, $encoding = 'UTF-8' )
	{
		return function_exists( 'mb_substr' ) ? mb_substr( $string, $start, $length, $encoding ) : substr( $string, $start, $length );
	}

	public static function internalEncoding( $encoding = 'UTF-8' )
	{
		if ( function_exists( 'mb_internal_encoding' ) )
			return mb_internal_encoding( $encoding );

		return FALSE;
	}

	/**
	 * TODO: SEE:
	 * https://github.com/GaryJones/Simple-PHP-CSS-Minification/
	 * [Finding Comments in Source Code Using Regular Expressions – Stephen Ostermiller](http://blog.ostermiller.org/find-comment)
	 *
	 * @REF: http://www.catswhocode.com/blog/3-ways-to-compress-css-files-using-php
	 */
	public static function minifyCSS( $buffer )
	{
		$buffer = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer ); // comments
		$buffer = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $buffer ); // remove tabs, spaces, newlines, etc.
		$buffer = preg_replace( '/\s+/', ' ', $buffer ); // normalize whitespace
		$buffer = preg_replace( '/;(?=\s*})/', '', $buffer ); // remove ; before }
		$buffer = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $buffer ); // remove space after , : ; { } */ >
		$buffer = preg_replace( '/ (,|;|\{|}|\(|\)|>)/', '$1', $buffer ); // remove space before , ; { } ( ) >
		$buffer = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $buffer ); // strips leading 0 on decimal values (converts 0.5px into .5px)
		$buffer = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $buffer ); // strips units if value is 0 (converts 0px to 0)
		$buffer = preg_replace( '/0 0 0 0/', '0', $buffer ); // converts all zeros value into short-hand
		$buffer = preg_replace( '/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $buffer ); // shortern 6-character hex color codes to 3-character where possible

		$buffer = preg_replace( '/\x{FEFF}/u', '', $buffer ); // remove utf8 bom

		return trim( $buffer );
	}

	// @REF: http://php.net/manual/en/function.ob-start.php#71953
	// @REF: http://stackoverflow.com/a/6225706
	// @REF: https://coderwall.com/p/fatjmw/compressing-html-output-with-php
	public static function minifyHTML( $buffer )
	{
		$buffer = str_replace( array( "\n", "\r", "\t" ), '', $buffer );

		$buffer = preg_replace(
			array( '/<!--(.*)-->/Uis', "/[[:blank:]]+/" ),
			array( '', ' ' ),
		$buffer );

		$buffer = preg_replace( array(
			'/\>[^\S ]+/s', // strip whitespaces after tags, except space
			'/[^\S ]+\</s', // strip whitespaces before tags, except space
			'/(\s)+/s' // shorten multiple whitespace sequences
		), array(
			'>',
			'<',
			'\\1'
		), $buffer );

		return trim( $buffer );
	}

	// @REF: http://davidwalsh.name/word-wrap-mootools-php
	// @REF: https://css-tricks.com/preventing-widows-in-post-titles/
	public static function wordWrap( $text, $min = 2 )
	{
		$return = $text;

		if ( strlen( trim( $text ) ) ) {
			$arr = explode( ' ', trim( $text ) );

			if ( count( $arr ) >= $min ) {
				$arr[count( $arr ) - 2] .= '&nbsp;'.$arr[count( $arr ) - 1];
				array_pop( $arr );
				$return = implode( ' ', $arr );
			}
		}

		return $return;
	}

	// @SOURCE: http://bavotasan.com/2012/trim-characters-using-php/
	public static function trimChars( $text, $length = 45, $append = '&hellip;' )
	{
		$length = (int) $length;
		$text   = trim( strip_tags( $text ) );

		if ( strlen( $text ) > $length ) {

			$text  = substr( $text, 0, $length + 1 );
			$words = preg_split( "/[\s]|&nbsp;/", $text, -1, PREG_SPLIT_NO_EMPTY );

			preg_match( "/[\s]|&nbsp;/", $text, $lastchar, 0, $length );

			if ( empty( $lastchar ) )
				array_pop( $words );

			$text = implode( ' ', $words ).$append;
		}

		return $text;
	}

	public static function firstSentence( $text )
	{
		// looks for three punctuation characters: . (period), ! (exclamation), or ? (question mark), followed by a space
		$strings = preg_split( '/(\.|!|\?)\s/', strip_tags( $text ), 2, PREG_SPLIT_DELIM_CAPTURE );

		// [0] is the first sentence and [1] is the punctuation character at the end
		if ( ! empty( $strings[0] )
			&& ! empty( $strings[1] ) )
				$text = $strings[0] . $strings[1];

		return $text;
	}

	/*
		@REF: https://gist.github.com/geminorum/fe2a9ba25db5cf2e5ad6718423d00f8a

		Original Title Case script (c) John Gruber <daringfireball.net>
		Javascript port (c) David Gouch <individed.com>
		PHP port of the above by Kroc Camen <camendesign.com>
	*/
	public static function titleCase( $title )
	{
		// remove HTML, storing it for later
		//       HTML elements to ignore    | tags  | entities
		$regx = '/<(code|var)[^>]*>.*?<\/\1>|<[^>]+>|&\S+;/';
		preg_match_all( $regx, $title, $html, PREG_OFFSET_CAPTURE );
		$title = preg_replace( $regx, '', $title );

		// find each word (including punctuation attached)
		preg_match_all( '/[\w\p{L}&`\'‘’"“\.@:\/\{\(\[<>_]+-? */u', $title, $m1, PREG_OFFSET_CAPTURE );

		foreach ( $m1[0] as &$m2 ) {

			// shorthand these- "match" and "index"
			list( $m, $i ) = $m2;

			// correct offsets for multi-byte characters (`PREG_OFFSET_CAPTURE` returns *byte*-offset)
			// we fix this by recounting the text before the offset using multi-byte aware `strlen`
			$i = mb_strlen( substr( $title, 0, $i ), 'UTF-8' );

			// find words that should always be lowercase…
			// (never on the first word, and never if preceded by a colon)
			$m = $i > 0 && mb_substr( $title, max( 0, $i - 2 ), 1, 'UTF-8' ) !== ':' && preg_match(
				'/^(a(nd?|s|t)?|b(ut|y)|en|for|i[fn]|o[fnr]|t(he|o)|vs?\.?|via)[ \-]/i', $m
			) ?	//…and convert them to lowercase
				mb_strtolower ($m, 'UTF-8')

			// else: brackets and other wrappers
			: (	preg_match( '/[\'"_{(\[‘“]/u', mb_substr( $title, max( 0, $i - 1 ), 3, 'UTF-8' ) )
			?	//convert first letter within wrapper to uppercase
				mb_substr( $m, 0, 1, 'UTF-8' ).
				mb_strtoupper( mb_substr( $m, 1, 1, 'UTF-8' ), 'UTF-8' ).
				mb_substr( $m, 2, mb_strlen( $m, 'UTF-8' ) - 2, 'UTF-8' )

			// else: do not uppercase these cases
			: (	preg_match( '/[\])}]/', mb_substr( $title, max( 0, $i - 1 ), 3, 'UTF-8' ) ) ||
				preg_match( '/[A-Z]+|&|\w+[._]\w+/u', mb_substr( $m, 1, mb_strlen( $m, 'UTF-8' ) - 1, 'UTF-8' ) )
			?	$m
				// if all else fails, then no more fringe-cases; uppercase the word
			:	mb_strtoupper( mb_substr( $m, 0, 1, 'UTF-8' ), 'UTF-8' ).
				mb_substr( $m, 1, mb_strlen( $m, 'UTF-8' ), 'UTF-8' )
			));

			// resplice the title with the change (`substr_replace` is not multi-byte aware)
			$title = mb_substr( $title, 0, $i, 'UTF-8' ).$m.
					 mb_substr( $title, $i + mb_strlen( $m, 'UTF-8' ), mb_strlen( $title, 'UTF-8' ), 'UTF-8' )
			;
		}

		// restore the HTML
		foreach ( $html[0] as &$tag )
			$title = substr_replace( $title, $tag[0], $tag[1], 0 );

		return $title;
	}

	/**
	 * Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
	 * All rights reserved.
	 * License: http://www.opensource.org/licenses/bsd-license.php
	 *
	 * Strip punctuation characters from UTF-8 text.
	 *
	 * Characters stripped from the text include characters in the following
	 * Unicode categories:
	 *
	 * 	Separators
	 * 	Control characters
	 *	Formatting characters
	 *	Surrogates
	 *	Open and close quotes
	 *	Open and close brackets
	 *	Dashes
	 *	Connectors
	 *	Numer separators
	 *	Spaces
	 *	Other punctuation
	 *
	 * Exceptions are made for punctuation characters that occur withn URLs
	 * (such as [ ] : ; @ & ? and others), within numbers (such as . , % # '),
	 * and within words (such as - and ').
	 *
	 * Parameters:
	 * 	text		the UTF-8 text to strip
	 *
	 * Return values:
	 * 	the stripped UTF-8 text.
	 *
	 * See also:
	 * 	http://nadeausoftware.com/articles/2007/9/php_tip_how_strip_punctuation_characters_web_page
	 */
	public static function stripPunctuation( $text )
	{
		$urlbrackets    = '\[\]\(\)';
		$urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
		$urlspaceafter  = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
		$urlall         = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;

		$specialquotes = '\'"\*<>';

		$fullstop      = '\x{002E}\x{FE52}\x{FF0E}';
		$comma         = '\x{002C}\x{FE50}\x{FF0C}';
		$arabsep       = '\x{066B}\x{066C}';
		$numseparators = $fullstop . $comma . $arabsep;

		$numbersign    = '\x{0023}\x{FE5F}\x{FF03}';
		$percent       = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
		$prime         = '\x{2032}\x{2033}\x{2034}\x{2057}';
		$nummodifiers  = $numbersign . $percent . $prime;

		return preg_replace(
			array(
			// Remove separator, control, formatting, surrogate,
			// open/close quotes.
				'/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
			// Remove other punctuation except special cases
				'/\p{Po}(?<![' . $specialquotes .
					$numseparators . $urlall . $nummodifiers . '])/u',
			// Remove non-URL open/close brackets, except URL brackets.
				'/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
			// Remove special quotes, dashes, connectors, number
			// separators, and URL characters followed by a space
				'/[' . $specialquotes . $numseparators . $urlspaceafter .
					'\p{Pd}\p{Pc}]+((?= )|$)/u',
			// Remove special quotes, connectors, and URL characters
			// preceded by a space
				'/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
			// Remove dashes preceded by a space, but not followed by a number
				'/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
			// Remove consecutive spaces
				'/ +/',
			),
			' ',
			$text );
	}

	public static function utf8StripBOM( $string )
	{
		return preg_replace( '/\x{FEFF}/u', '', $string );
	}

	// @SOURCE: http://web.archive.org/web/20110215015142/http://www.phpwact.org/php/i18n/charsets#checking_utf-8_for_well_formedness
	// @SEE: http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php#54805
	// @SEE: `wp_check_invalid_utf8()`
	public static function utf8Compliant( $string )
	{
		if ( 0 === strlen( $string ) )
			return TRUE;

		// If even just the first character can be matched, when the /u
		// modifier is used, then it's valid UTF-8. If the UTF-8 is somehow
		// invalid, nothing at all will match, even if the string contains
		// some valid sequences
		return ( 1 === @preg_match( '/^.{1}/us', $string ) );
	}

	// @SOURCE: http://web.archive.org/web/20110215015142/http://www.phpwact.org/php/i18n/charsets#htmlspecialchars
	// @SOURCE: `_wp_specialchars()`
	// converts a number of special characters into their HTML entities
	// specifically deals with: &, <, >, ", and '
	public static function utf8SpecialChars( $string, $flags = ENT_COMPAT )
	{
		$string = (string) $string;

		if ( 0 === strlen( $string ) )
			return '';

		if ( preg_match( '/[&<>"\']/', $string ) )
			$string = @htmlspecialchars( $string, $flags, 'UTF-8' );

		return $string;
	}

	// @SOURCE: http://php.net/manual/en/function.ord.php#109812
	// As ord() doesn't work with utf-8,
	// and if you do not have access to mb_* functions
	public static function utf8Ord( $string, &$offset )
	{
		$code = ord( substr( $string, $offset, 1 ) );

		if ( $code >= 128 ) { // otherwise 0xxxxxxx

			if ( $code < 224 )
				$bytesnumber = 2; // 110xxxxx
			else if ( $code < 240 )
				$bytesnumber = 3; // 1110xxxx
			else if ( $code < 248 )
				$bytesnumber = 4; // 11110xxx

			$codetemp = $code - 192 - ( $bytesnumber > 2 ? 32 : 0 ) - ( $bytesnumber > 3 ? 16 : 0 );

			for ( $i = 2; $i <= $bytesnumber; $i++ ) {
				$offset++;

				$code2    = ord( substr( $string, $offset, 1 ) ) - 128; // 10xxxxxx
				$codetemp = $codetemp * 64 + $code2;
			}

			$code = $codetemp;
		}

		$offset += 1;

		if ( $offset >= strlen( $string ) )
			$offset = -1;

		return $code;
	}

	public static function wordCountUTF8( $html, $normalize = TRUE )
	{
		if ( ! $html )
			return 0;

		if ( $normalize ) {

			$html = preg_replace( array(
				'@<script[^>]*?>.*?</script>@si',
				'@<style[^>]*?>.*?</style>@siU',
				'@<embed[^>]*?.*?</embed>@siu',
				'@<![\s\S]*?--[ \t\n\r]*>@',
				'/<blockquote.*?>(.*)?<\/blockquote>/im',
				'/<figure.*?>(.*)?<\/figure>/im',
			), '', $html );

			$html = strip_tags( $html );

			// FIXME: convert back html entities

			$html = str_replace( array(
				"&nbsp;",
				"&mdash;",
				"&ndash;",
			), ' ', $html );

			$html = str_replace( array(
				"&zwnj;",
				"\xE2\x80\x8C", // Zero Width Non-Joiner U+200C
				"\xE2\x80\x8F", // Right-To-Left Mark U+200F
				"\xE2\x80\x8E", // Right-To-Left Mark U+200E
				"\xEF\xBB\xBF", // UTF8 Bom
			), '', $html );

			$html = strip_shortcodes( $html );

			$html = self::noLineBreak( $html );
			$html = self::stripPunctuation( $html );
			$html = self::normalizeWhitespaceUTF8( $html, TRUE );

			$html = trim( $html );
		}

		if ( ! $html )
			return 0;

		// http://php.net/manual/en/function.str-word-count.php#85579
		// return preg_match_all( "/\\p{L}[\\p{L}\\p{Mn}\\p{Pd}'\\x{2019}]*/u", $html, $matches );

		// http://php.net/manual/en/function.str-word-count.php#107363
		return count( preg_split( '~[^\p{L}\p{N}\']+~u', $html ) );
	}

	// @SEE: [wp_strip_all_tags()](https://developer.wordpress.org/reference/functions/wp_strip_all_tags/)
	public static function stripHTMLforEmail( $html )
	{
		$html = preg_replace( array(
			'@<head[^>]*?>.*?</head>@siu',
			'@<style[^>]*?>.*?</style>@siu',
			'@<script[^>]*?.*?</script>@siu',
			'@<object[^>]*?.*?</object>@siu',
			'@<embed[^>]*?.*?</embed>@siu',
			'@<noscript[^>]*?.*?</noscript>@siu',
			'@<noembed[^>]*?.*?</noembed>@siu',
			'@\t+@siu',
			'@\n+@siu'
		), '', $html );

		$html = preg_replace( '@</?((div)|(h[1-9])|(/tr)|(p)|(pre))@iu', "\n\$0", $html );
		$html = preg_replace( '@</((td)|(th))@iu', " \$0", $html );

		return trim( strip_tags( $html ) );
	}

	// @SOURCE: http://php.net/manual/en/function.preg-replace-callback.php#96899
	public static function hex2str( $string )
	{
		return preg_replace_callback( '#\%[a-zA-Z0-9]{2}#', function( $hex ) {
			$hex = substr( $hex[0], 1 );
			$str = '';
			for ( $i = 0; $i < strlen( $hex ); $i += 2 )
				$str .= chr( hexdec( substr( $hex, $i, 2 ) ) );
			return $str;
		}, (string) $string );
	}

	// @SOURCE: http://php.net/manual/en/function.preg-replace-callback.php#91950
	// USAGE: echo replaceWords( $list, $str, function($v) { return "<strong>{$v}</strong>"; });
	public static function replaceWords( $list, $line, $callback )
	{
		$patterns = '/(^|[^\\w\\-])('.implode( '|', array_map( 'preg_quote', $list ) ).')($|[^\\w\\-])/mi';
		return preg_replace_callback( $patterns, function( $v ) use ( $callback ) {
			return $v[1].$callback($v[2]).$v[3];
		}, $line );
	}

	// @SOURCE: http://snipplr.com/view/3618/
	public static function closeHTMLTags( $html )
	{
		// put all opened tags into an array
		preg_match_all( "#<([a-z]+)( .*)?(?!/)>#iU", $html, $result );
		$openedtags = $result[1];

		// put all closed tags into an array
		preg_match_all( "#</([a-z]+)>#iU", $html, $result );

		$closedtags = $result[1];
		$len_opened = count( $openedtags );

		// all tags are closed
		if ( $len_opened == count( $closedtags ) )
			return $html;

		$openedtags = array_reverse( $openedtags );

		// close tags
		for ( $i = 0; $i < $len_opened; $i++ )
			if ( ! in_array( $openedtags[$i], $closedtags ) )
				$html .= '</'.$openedtags[$i].'>';
			else
				unset( $closedtags[array_search( $openedtags[$i], $closedtags )] );

		return $html;
	}

	// OLD: `genRandomKey()`
	// ALT: `wp_generate_password()`
	public static function hash( $salt )
	{
		$chr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$len = 32;
		$key = '';

		for ( $i = 0; $i < $len; $i++ )
			$key .= $chr[( rand( 0, ( strlen( $chr ) - 1 ) ) )];

		return md5( $salt.$key );
	}

	// @SOURCE: `_deep_replace()`
	public static function deepStrip( $search, $string )
	{
		$string = (string) $string;

		$count = 1;
		while ( $count )
			$string = str_replace( $search, '', $string, $count );

		return $string;
	}

	// @REF: https://en.wikipedia.org/wiki/Control_character
	// @REF: https://en.wikipedia.org/wiki/Unicode_control_characters
	// @SEE: `wp_kses_no_null()`
	public static function stripControlChars( $string )
	{
		// remove control chars, the first 32 ascii characters and \x7F
		// @REF: http://stackoverflow.com/a/1497928/4864081
		$string = preg_replace( '/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $string );
		// $string = preg_replace('/[\p{Cc}]/', '', $string );

		// removes any instance of the '\0' string
		$string = preg_replace( '/\\\\+0+/', '', $string );

		return $string;
	}
}

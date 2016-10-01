<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Text extends Base
{

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

	public static function getDomain( $string )
	{
		// FIXME: strip all the path
		// SEE: http://stackoverflow.com/questions/569137/how-to-get-domain-name-from-url

		if ( FALSE !== strpos( $string, '.' ) ) {
			$domain = explode( '.', $string );
			$domain = $domain[0];
		}

		return strtolower( $domain );
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

	// @SOURCE: [Checking UTF-8 for Well Formedness](http://www.phpwact.org/php/i18n/charsets#checking_utf-8_for_well_formedness)
	// @SEE: http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php#54805
	// @SEE: WP core's : `seems_utf8()`
	public static function utf8Compliant( $string )
	{
		if ( 0 === strlen( $string ) )
			return TRUE;

		// If even just the first character can be matched, when the /u
		// modifier is used, then it's valid UTF-8. If the UTF-8 is somehow
		// invalid, nothing at all will match, even if the string contains
		// some valid sequences
		return ( 1 == preg_match( '/^.{1}/us', $string, $ar ) );
	}

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
		return preg_replace_callback('#\%[a-zA-Z0-9]{2}#', function( $hex ){
			$hex = substr( $hex[0], 1 );
			$str = '';
			for( $i=0; $i < strlen( $hex ); $i += 2 )
				$str .= chr( hexdec( substr( $hex, $i, 2 ) ) );
			return $str;
		}, (string) $string );
	}

	// @SOURCE: http://php.net/manual/en/function.preg-replace-callback.php#91950
	// USAGE: echo replaceWords( $list, $str, function($v) { return "<strong>{$v}</strong>"; });
	public static function replaceWords( $list, $line, $callback )
	{
		$patterns = '/(^|[^\\w\\-])('.implode( '|', array_map( 'preg_quote', $list ) ).')($|[^\\w\\-])/mi';
		return preg_replace_callback( $patterns, function($v) use ($callback) {
			return $v[1].$callback($v[2]).$v[3];
		}, $line );
	}
}

<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Strings extends Core\Base
{

	public static function isEmpty( $string, $empties = NULL )
	{
		if ( ! is_string( $string ) )
			return FALSE;

		$trimmed = trim( $string );

		if ( '' === $trimmed )
			return TRUE;

		if ( is_null( $empties ) )
			$empties = [
				'.', '..', '...',
				'-', '--', '---',
				'–', '––', '–––',
				'—', '——', '———',
			];

		foreach ( (array) $empties as $empty )
			if ( $empty === $trimmed )
				return TRUE;

		return FALSE;
	}

	public static function filterEmpty( $strings, $empties = NULL )
	{
		return array_filter( $strings, static function( $value ) use ( $empties ) {

			if ( self::isEmpty( $value, $empties ) )
				return FALSE;

			return ! empty( $value );
		} );
	}

	public static function trimChars( $text, $length = 45, $append = '&nbsp;&hellip;' )
	{
		$append = '<span title="'.HTML::escape( $text ).'">'.$append.'</span>';

		return Core\Text::trimChars( $text, $length, $append );
	}

	public static function getSeparated( $string, $delimiters = NULL, $limit = NULL, $delimiter = '|' )
	{
		if ( is_array( $string ) )
			return $string;

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

		$string = str_ireplace( $delimiters, $delimiter, $string );

		$seperated = is_null( $limit )
			? explode( $delimiter, $string )
			: explode( $delimiter, $string, $limit );

		return Core\Arraay::prepString( $seperated );
	}
}
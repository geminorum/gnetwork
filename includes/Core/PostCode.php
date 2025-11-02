<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class PostCode extends Base
{

	// @SEE: https://github.com/persian-tools/persian-tools/pull/403/files

	// OLD: `Core\Validation::sanitizePostCode()`
	public static function sanitize( $input, $default = '', $field = [], $context = 'save' )
	{
		if ( self::empty( $input ) )
			return $default;

		$sanitized = str_ireplace( [ '-', ' ' ], '', $input );
		$sanitized = Number::translate( Text::trim( $sanitized ) );

		if ( ! self::is( $sanitized ) )
			return $default;

		return $sanitized;
	}

	// OLD: `Core\Validation::isPostCode()`
	public static function is( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			return self::isIranPostCode( $input );

		// @SOURCE: `WC_Validation::is_postcode()`
		if ( 0 < strlen( trim( preg_replace( '/[\s\-A-Za-z0-9]/', '', $input ) ) ) )
			return FALSE;

		return TRUE;
	}

	public static function getHTMLPattern()
	{
		return FALSE; // FIXME
	}

	// @REF: https://github.com/VahidN/DNTPersianUtils.Core/blob/master/src/DNTPersianUtils.Core/Validators/IranCodesUtils.cs#L13
	// @REF: https://www.dotnettips.info/newsarchive/details/14187
	public static function isIranPostCode( $input )
	{
		$sanitized = str_ireplace( [ '-', ' ' ], '', $input );
		$sanitized = Number::translate( Text::trim( $sanitized ) );

		if ( ! preg_match( '/^\d{10}$/', $sanitized ) )
			return FALSE;

		if ( Number::repeated( $input, 10 ) )
			return FALSE;

		return (bool) preg_match( '/(?!(\d)\1{3})[13-9]{4}[1346-9][013-9]{5}/', $sanitized );
	}
}

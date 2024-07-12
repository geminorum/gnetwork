<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Validation extends Base
{

	public static function sanitizePostCode( $input )
	{
		$sanitized = Number::translate( Text::trim( $input ) );

		if ( ! self::isPostCode( $sanitized ) )
			return '';

		return $sanitized;
	}

	public static function isPostCode( $input )
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

	// @REF: https://github.com/VahidN/DNTPersianUtils.Core/blob/master/src/DNTPersianUtils.Core/Validators/IranCodesUtils.cs#L13
	// @REF: https://www.dotnettips.info/newsarchive/details/14187
	public static function isIranPostCode( $input )
	{
		return (bool) preg_match( '/(?!(\d)\1{3})[13-9]{4}[1346-9][013-9]{5}/',
			trim( str_ireplace( [ '-', ' ' ], '', Number::translate( Text::trim( $input ) ) ) ) );
	}

	public static function getMobileHTMLPattern()
	{
		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			return '[0-9۰-۹+]{11,}';

		return '[0-9+]{11,}';
	}

	public static function sanitizePhoneNumber( $input )
	{
		$sanitized = Number::translate( Text::trim( $input ) );

		if ( ! self::isPhoneNumber( $sanitized ) )
			return '';

		$sanitized = trim( str_ireplace( [
			' ',
			'.',
			'-',
			'#',
			'|',
			'(',
			')',
		], '', $sanitized ) );

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( preg_match( '/^0\d{10}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', ltrim( $sanitized, '0' ) );

			else if ( preg_match( '/^[1-9]{1}\d{7}$/', $sanitized ) )
				$sanitized = sprintf( '+9821%s', $sanitized ); // WTF: Tehran prefix!
		}

		return $sanitized;
	}

	public static function isPhoneNumber( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		if ( ! Phone::is( $input ) )
			return FALSE;

		return TRUE;
	}

	public static function sanitizeMobileNumber( $input )
	{
		$sanitized = Number::translate( Text::trim( $input ) );

		if ( ! self::isMobileNumber( $sanitized ) )
			return '';

		$sanitized = trim( str_ireplace( [
			' ',
			'.',
			'-',
			'#',
		], '', $sanitized ) );

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( preg_match( '/^9\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', $sanitized );

			else if ( preg_match( '/^09\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', ltrim( $sanitized, '0' ) );
		}

		return $sanitized;
	}

	public static function isMobileNumber( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		if ( ! Phone::is( $input ) )
			return FALSE;

		return TRUE;
	}

	public static function getIdentityNumberHTMLPattern()
	{
		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			return '[0-9۰-۹]{10}';

		return '[0-9]{10}';
	}

	public static function isIdentityNumber( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		if ( ! Phone::is( $input ) )
			return FALSE;

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			return self::isIranNationalCode( $input );

		return TRUE;
	}

	public static function sanitizeIdentityNumber( $input )
	{
		$sanitized = Number::translate( Text::trim( $input ) );

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			$sanitized = Number::zeroise( $sanitized, 10 );

		if ( ! self::isIdentityNumber( $sanitized ) )
			return '';

		return $sanitized;
	}

	// @REF: https://fandogh.github.io/codemeli/codemeli.html
	// @REF: https://gist.github.com/ebraminio/5292017#gistcomment-3435493
	public static function isIranNationalCode( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		if ( ! preg_match( '/^\d{10}$/', $input ) )
			return FALSE;

		if ( FALSE !== array_search( $input, array_map( function ( $i ) {
			return str_repeat( $i, 10 );
		}, range( 0, 9 ) ) ) )
			return FALSE;

		$chk = (int) $input[9];
		$sum = array_sum( array_map( function ( $x ) use ( $input ) {
			return ( (int) $input[$x] ) * ( 10 - $x );
		}, range( 0, 8 ) ) ) % 11;

		if ( ( $sum < 2 && $chk == $sum ) || ( $sum >= 2 && $chk + $sum == 11 ) )
			return TRUE;

		return FALSE;
	}

	public static function getIBANHTMLPattern()
	{
		return FALSE; // FIXME!
	}

	// @SEE: https://fa.wikipedia.org/wiki/%D8%B4%D8%A8%D8%A7
	// @SEE: https://gist.github.com/mhf-ir/c17374fae395a57c9f8e5fe7a92bbf23
	public static function sanitizeIBAN( $input )
	{
		$sanitized = Number::translate( Text::trim( $input ) );

		if ( ! self::isIBAN( $sanitized ) )
			return '';

		return $sanitized;
	}

	public static function isIBAN( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			// @REF: https://barnamenevis.org/showthread.php?512577
			if ( ! preg_match( '/^IR[0-9]{24}$/i', $input ) )
				return FALSE;
		}

		if ( ! self::checkIBAN( $input ) )
			return FALSE;

		return TRUE;
	}

	// @SOURCE: https://3v4l.org/fDgfo
	// @REF: https://stackoverflow.com/questions/20983339/validate-iban-php#comment119408175_32612548
	// @SEE: https://en.wikipedia.org/wiki/International_Bank_Account_Number#Validating_the_IBAN
	public static function checkIBAN( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		// normalize input: remove spaces and make uppercase
		$input = strtoupper( str_replace( ' ', '', $input ) );

		if ( ! preg_match( '/^([A-Z]{2})(\d{2})([A-Z\d]{1,30})$/', $input, $segments ) )
			return FALSE;

		[, $country, $check, $account ] = $segments;

		$digits = str_split( strtr( $account.$country, array_combine( range( 'A', 'Z' ), range( 10, 35 ) ) ).'00' );
		$first  = array_shift( $digits );

		$checksum = array_reduce( $digits, static function ( $carry, $int ) {
			$carry = ( $carry * 10 + (int) $int ) % 97;
			return $carry;
		}, (int) $first );

		return ( 98 - $checksum ) == $check;
	}

	public static function sanitizeCardNumber( $input )
	{
		$sanitized = Number::translate( Text::trim( $input ) );

		if ( ! self::isCardNumber( $sanitized ) )
			return '';

		return $sanitized;
	}

	// https://github.com/persian-tools/php-persian-tools/blob/master/src/Traits/VerifyCardNumber.php
	public static function isCardNumber( $input )
    {
		if ( self::empty( $input ) )
			return FALSE;

		if ( 16 !== strlen( $input )
			|| 0 === intval( substr( $input, 1, 11 ) )
			|| 0 === intval( substr( $input, 10 ) ) )
                return FALSE;

        $sum = 0;

        for ( $i = 0; $i < 16; $i++ ) {
            $even  = $i % 2 == 0 ? 2 : 1;
            $sub   = intval( $input[$i] ) * $even;
            $sum  += $sub > 9 ? $sub - 9 : $sub;
        }

        return $sum % 10 == 0;
    }
}

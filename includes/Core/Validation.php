<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Validation extends Base
{

	public static function isPostCode( $input )
	{
		if ( empty( $input ) )
			return FALSE;

		// @SOURCE: `WC_Validation::is_postcode()`
		if ( 0 < strlen( trim( preg_replace( '/[\s\-A-Za-z0-9]/', '', $input ) ) ) )
			return FALSE;

		return TRUE; // FIXME!
	}

	public static function getMobileHTMLPattern()
	{
		if ( defined( 'GNETWORK_WPLANG' ) && 'fa_IR' == constant( 'GNETWORK_WPLANG' ) )
			return '[0-9۰-۹+]{11,}';

		return '[0-9+]{11,}';
	}

	public static function isMobileNumber( $input )
	{
		if ( empty( $input ) )
			return FALSE;

		// @SOURCE: `WC_Validation::is_phone()`
		if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)\.]/', '', $input ) ) ) )
			return FALSE;

		return TRUE; // FIXME!
	}

	public static function getSSNHTMLPattern()
	{
		if ( defined( 'GNETWORK_WPLANG' ) && 'fa_IR' == constant( 'GNETWORK_WPLANG' ) )
			return '[0-9۰-۹]{10}';

		return '[0-9]{10}';
	}

	public static function isSSN( $input )
	{
		if ( empty( $input ) )
			return FALSE;

		// @SOURCE: `WC_Validation::is_phone()`
		if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)\.]/', '', $input ) ) ) )
			return FALSE;

		if ( defined( 'GNETWORK_WPLANG' ) && 'fa_IR' == constant( 'GNETWORK_WPLANG' ) )
			return self::isIranNationalCode( $input );

		return TRUE; // FIXME!
	}

	// @REF: https://fandogh.github.io/codemeli/codemeli.html
	// @REF: https://gist.github.com/ebraminio/5292017#gistcomment-3435493
	public static function isIranNationalCode( $input )
	{
		if ( ! preg_match( '/^\d{10}$/', $input ) )
			return FALSE;

		// if ( FALSE !== array_search( $input, [ '0000000000', '1111111111', '2222222222', '3333333333', '4444444444', '5555555555', '6666666666', '7777777777', '8888888888', '9999999999' ] ) )
		// 	return FALSE;

		$chk = (int) $input[9];
		$sum = array_sum( array_map( function( $x ) use ( $input ) {
			return ( (int) $input[$x] ) * ( 10 - $x );
		}, range( 0, 8 ) ) ) % 11;

		if ( ( $sum < 2 && $chk == $sum ) || ( $sum >= 2 && $chk + $sum == 11 ) )
			return TRUE;

		return FALSE;
	}
}

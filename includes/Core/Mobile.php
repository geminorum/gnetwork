<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Mobile extends Base
{

	// TODO: must convert to `DataType`

	/**
	 * Validates a phone number using a regular expression.
	 *
	 * @param string $data Mobile number to validate.
	 * @param string $country The country code the mobile is being validated for, or null if unknown.
	 * @return bool
	 */
	public static function is( $data, $country = NULL )
	{
		if ( self::empty( $data ) )
			return FALSE;

		// @source `WC_Validation::is_phone()`
		if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)\.]/', '', $data ) ) ) )
			return FALSE;

		// all zeros!
		if ( ! intval( $data ) )
			return FALSE;

		return TRUE;
	}

	public static function sanitize( mixed $input, mixed $default = '', ?array $field = [], ?string $context = 'save' ): mixed
	{
		return Phone::sanitize( $input, $default, $field, $context );
	}

	/**
	 * Prepares a value as mobile number for the given context.
	 *
	 * @param string $value
	 * @param array $field
	 * @param string $context
	 * @param string $icon
	 * @return string
	 */
	public static function prep( mixed $value, ?array $field = [], ?string $context = 'display', mixed $icon = NULL ): string
	{
		if ( self::empty( $value ) )
			return '';

		$raw   = $value;
		$title = empty( $field['title'] ) ? NULL : $field['title'];

		// tries to sanitize with fallback
		if ( ! $value = self::sanitize( $value ) )
			$value = $raw;

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( Text::starts( $value, '+98' ) )
				$value = '0'.Text::stripPrefix( $value, '+98' );

			$value = Number::localize( $value );
		}

		switch ( $context ) {
			case 'raw'   : return $raw;
			case 'edit'  : return $raw;
			case 'print' : return $value;
			case 'input' : return Number::translate( $value );
			case 'export': return Number::translate( $value );
			case 'icon'  : return Link::tel( $raw, $title ?: $value, $icon ?? HTML::getDashicon( 'smartphone' ), self::is( $raw ) ? '-is-valid' : '-is-not-valid' );
			case 'admin' :
			     default : return Link::tel( $raw, $title ?: FALSE, $value, self::is( $raw ) ? '-is-valid' : '-is-not-valid' );
		}

		return $value;
	}

	public static function getHTMLPattern()
	{
		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			return '[0-9۰-۹+]{11,}';
			// @REF: https://codepen.io/Hi-mohammad/pen/KKGYWQR
			// return '09(1[0-9]|3[1-9]|2[1-9])-?[0-9]{3}-?[0-9]{4}';

		return '[0-9+]{11,}';
	}
}

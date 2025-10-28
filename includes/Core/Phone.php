<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Phone extends Base
{

	// TODO: must convert to `DataType`
	// @SEE: https://github.com/brick/phonenumber

	/**
	 * Validates a phone number using a regular expression.
	 *
	 * @source `WC_Validation::is_phone()`
	 *
	 * @param string $data Phone number to validate.
	 * @return bool
	 */
	public static function is( $data )
	{
		if ( self::empty( $data ) )
			return FALSE;

		if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)\.]/', '', $data ) ) ) )
			return FALSE;

		// all zeros!
		if ( ! intval( $data ) )
			return FALSE;

		return TRUE;
	}

	public static function sanitize( $input, $default = '', $field = [], $context = 'save' )
	{
		if ( self::empty( $input ) )
			return $default;

		$sanitized = Number::translate( Text::trim( $input ) );
		$sanitized = preg_replace( '/^tel\:([\+\d]+)$/i', '$1', $sanitized );

		if ( ! self::is( $sanitized ) )
			return $default;

		$sanitized = trim( str_ireplace( [
			' ',
			'.',
			'-',
			'#',
			'|',
			'(',
			')',
		], '', $sanitized ) );

		if ( Number::repeated( $input, 11 ) )
			return $default;

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( strlen( $sanitized ) > 13 )
				return $default;

			$province_prefix = self::const( 'GCORE_DEFAULT_PROVINCE_PHONE', '21' );
			$province_length = strlen( $province_prefix );

			// under 10 digits and starts with `9`
			if ( preg_match( '/^9\d{0,8}$/', $sanitized ) )
				return $default;

			// 10 digits and starts with `9`
			if ( preg_match( '/^9\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', $sanitized );

			// 11 digits and starts with `09`
			else if ( preg_match( '/^09\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', ltrim( $sanitized, '0' ) );

			// 10 digits and starts with province prefix
			else if ( preg_match( "/^$province_prefix\d{".( 10 - $province_length )."}$/", $sanitized ) )
				$sanitized = sprintf( '+98%s', $sanitized );

			// 11 digits and starts with `0`
			else if ( preg_match( '/^0\d{10}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', ltrim( $sanitized, '0' ) );

			// 10 digits and starts with non `0`
			else if ( preg_match( '/^[1-9]{1}\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', $sanitized );

			// 8 digits and starts with non `0`
			else if ( preg_match( '/^[1-9]{1}\d{7}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s%s', $province_prefix, $sanitized );

			// NOTE: invalidate likes of `+989120000000`/`+981111111111`
			if ( 13 === strlen( $sanitized ) && Number::repeated( substr( $sanitized, -7 ), 7 ) )
				return $default;
		}

		return $sanitized;
	}

	/**
	 * Prepares a value as phone number for the given context.
	 *
	 * @param string $value
	 * @param array $field
	 * @param string $context
	 * @param string $icon
	 * @return string
	 */
	public static function prep( $value, $field = [], $context = 'display', $icon = NULL )
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
			case 'icon'  : return HTML::tel( $raw, $title ?: $value, $icon ?? HTML::getDashicon( 'phone' ), self::is( $raw ) ? '-is-valid' : '-is-not-valid' );
			case 'admin' :
			     default : return HTML::tel( $raw, $title ?: FALSE, $value, self::is( $raw ) ? '-is-valid' : '-is-not-valid' );
		}

		return $value;
	}

	/**
	 * Tries to discover if given criteria is supported.
	 *
	 * @param string $criteria
	 * @return string|false
	 */
	public static function discovery( $criteria )
	{
		if ( ! $sanitized = self::sanitize( $criteria ) )
			return FALSE;

		// // only numbers
		// if ( ! Number::is( $sanitized ) )
		// 	return FALSE;

		// // only between 10-13 digits
		// if ( ! preg_match( '/^\d{10,13}$/', $sanitized ) )
		// 	return FALSE;

		return $sanitized;
	}

	// @REF: https://www.abstractapi.com/guides/validate-phone-number-javascript
	// @SEE: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/tel
	public static function getHTMLPattern()
	{
		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			return '[0-9۰-۹]{3}-[0-9۰-۹]{3}-[0-9۰-۹]{4}';

		// @REF: https://www.material-tailwind.com/docs/html/input-phone
		// `maxlength="16"`
		// return '^\+\d{1,3}\s\d{1,4}-\d{1,4}-\d{4}$';

		return '[0-9]{3}-[0-9]{3}-[0-9]{4}';
	}

	/**
	 * Convert plaintext phone number to clickable phone number.
	 *
	 * Remove formatting and allow `+`.
	 * Example and specs: https://developer.mozilla.org/en/docs/Web/HTML/Element/a#Creating_a_phone_link
	 *
	 * @source `wc_make_phone_clickable()`
	 *
	 * @param string $text Content to convert phone number.
	 * @return string Content with converted phone number.
	 */
	public static function clickable( $text )
	{
		$number = Text::trim( preg_replace( '/[^\d|\+]/', '', $text ) );

		return $number ? '<a href="tel:'.esc_attr( $number ).'">'.esc_html( $text ).'</a>' : '';
	}

	public static function prepMobileForUsername( $text )
	{
		if ( ! ( $text = Text::trim( $text ) ) )
			return '';

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			$text = preg_replace( '/^\+98(\d{10})$/', '$1', $text );
			$text = preg_replace( '/^98(\d{10})$/', '$1', $text );
		}

		$text = preg_replace( '/^0(\d{10})$/', '$1', $text );

		if ( preg_replace( '/\d{10}/', '', $text ) )
			return '';

		return trim( $text );
	}
}

<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Phone extends Base
{

	/**
	 * Validates a phone number using a regular expression.
	 *
	 * @source `WC_Validation::is_phone()`
	 *
	 * @param  string $text Phone number to validate.
	 * @return bool
	 */
	public static function is( $text )
	{
		if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)\.]/', '', $text ) ) ) )
			return FALSE;

		// all zeros!
		if ( ! intval( $text ) )
			return FALSE;

		return TRUE;
	}

	public static function sanitize( $input )
	{
		$sanitized = Number::translate( Text::trim( $input ) );
		$sanitized = preg_replace( '/^tel\:([\+\d]+)$/i', '$1', $sanitized );

		if ( ! self::is( $sanitized ) )
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

		if ( Number::repeated( $input, 11 ) )
			return '';

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( strlen( $sanitized ) > 13 )
				return '';

			$province_prefix = self::const( 'GCORE_DEFAULT_PROVINCE_PHONE', '21' );
			$province_length = strlen( $province_prefix );

			// under 10 digits and starts with `9`
			if ( preg_match( '/^9\d{0,8}$/', $sanitized ) )
				return '';

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
				return '';
		}

		return $sanitized;
	}

	/**
	 * Convert plaintext phone number to clickable phone number.
	 *
	 * Remove formatting and allow "+".
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

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
		$number = trim( preg_replace( '/[^\d|\+]/', '', $text ) );

		return $number ? '<a href="tel:'.esc_attr( $number ).'">'.esc_html( $text ).'</a>' : '';
	}

	public static function prepMobileForUsername( $text )
	{
		if ( ! ( $text = trim( $text ) ) )
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

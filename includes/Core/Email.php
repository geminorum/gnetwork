<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Email extends Base
{

	// TODO: must convert to `DataType`

	/**
	 * Verifies that an email is valid.
	 * NOTE: wrapper for WordPress core `is_email()`
	 *
	 * @param string $input
	 * @return bool
	 */
	public static function is( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		return (bool) is_email( Text::trim( $input ) );
	}

	/**
	 * Strips out all characters that are not allowable in an email.
	 * NOTE: wrapper for WordPress core `sanitize_email()`
	 *
	 * @param string $input
	 * @return string
	 */
	public static function sanitize( $input, $default = '', $field = [], $context = 'save' )
	{
		if ( self::empty( $input ) )
			return $default;

		return sanitize_email( Text::trim( $input ) );
	}

	/**
	 * Prepares a value as email address for the given context.
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

		switch ( $context ) {
			case 'raw'   : return $raw;
			case 'edit'  : return $raw;
			case 'input' : return $value;
			case 'export': return $value;
			case 'print' : return HTML::wrapLTR( trim( $raw ) );
			case 'icon'  : return HTML::mailto( $value, $title, $icon ?? HTML::getDashicon( 'email-alt' ), self::is( $value ) ? '-is-valid' : '-is-not-valid' );
			case 'admin' :
			     default : return HTML::mailto( $value, $title, NULL, self::is( $value ) ? '-is-valid' : '-is-not-valid' );
		}

		return $value;
	}

	public static function getHTMLPattern()
	{
		return FALSE; // FIXME!
	}

	public static function toUsername( $email, $strict = TRUE )
	{
		return preg_replace( '/\s+/', '', sanitize_user( preg_replace( '/([^@]*).*/', '$1', $email ), $strict ) );
	}
}

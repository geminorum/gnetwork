<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Email extends Base
{

	// TODO: must convert to `DataType`

	/**
	 * Verifies that an email is valid.
	 * NOTE: wrapper for WordPress core `is_email()`
	 *
	 * @param mixed $input
	 * @return bool
	 */
	public static function is( mixed $input ): bool
	{
		if ( self::empty( $input ) )
			return FALSE;

		return (bool) is_email( Text::trim( $input ) );
	}

	/**
	 * Strips out all characters that are not allowable in an email.
	 * NOTE: wrapper for WordPress core `sanitize_email()`
	 *
	 * @param mixed $input
	 * @param mixed $default
	 * @param array $field
	 * @param string $context
	 * @return mixed
	 */
	public static function sanitize( mixed $input, mixed $default = '', ?array $field = [], ?string $context = 'save' ): mixed
	{
		if ( self::empty( $input ) )
			return $default;

 		// Strip surrounding whitespace.
		$input = Text::trim( $input );

		// Extract the address from `Display Name <username@domain>` format.
		if ( 1 === preg_match( '/<([^>]+)>$/', $input, $matches ) )
			$input = $matches[1];

		/**
		 * Strip soft hyphens and whitespace adjacent to structural separators (dots and @),
		 * e.g. copy-paste artifacts like `info@example\u{00AD}.com` or `info@example .com`.
		 *
		 * In some cases, e.g. autocorrect, some older software has been seen to add the
		 * space for unrecognized `TLD`. This rejoins the parts for proper examination.
		 * @source `sanitize_email()`
		 */
		$input = preg_replace( '/[\x{00AD}\s]*([.@])[\x{00AD}\s]*/u', '$1', $input ) ?? $input;

		return sanitize_email( Text::trim( $input ) );
	}

	/**
	 * Prepares a value as email address for the given context.
	 *
	 * @param mixed $value
	 * @param array $field
	 * @param string $context
	 * @param mixed $icon
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

		switch ( $context ) {
			case 'raw'   : return $raw;
			case 'edit'  : return $raw;
			case 'input' : return $value;
			case 'export': return $value;
			case 'print' : return HTML::wrapLTR( trim( $raw ) );
			case 'icon'  : return Link::mailto( $value, $title, $icon ?? HTML::getDashicon( 'email-alt' ), self::is( $value ) ? '-is-valid' : '-is-not-valid' );
			case 'admin' :
			     default : return Link::mailto( $value, $title, NULL, self::is( $value ) ? '-is-valid' : '-is-not-valid' );
		}
	}

	public static function getHTMLPattern(): string|false
	{
		return FALSE; // FIXME!
	}

	public static function toUsername( string $email, bool $strict = TRUE ): string
	{
		return preg_replace( '/\s+/', '', sanitize_user( preg_replace( '/([^@]*).*/', '$1', $email ), $strict ) );
	}
}

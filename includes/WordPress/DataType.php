<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

// @SEE: https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Values_and_Units/CSS_data_types

abstract class DataType extends Core\Base
{
	// abstract public static function is( mixed $input ): bool;
	// abstract public static function validate( mixed $data ): int;
	// abstract public static function extract( mixed $input ): false|array;
	// abstract public static function prep( mixed $value, ?array $field = [], ?string $context = 'display', mixed $icon = NULL ): string;
	// abstract public static function sanitize( mixed $input, mixed $default = '', ?array $field = [], ?string $context = 'save' ): mixed;
	// abstract public static function discovery( $criteria );
	// abstract public static function getHTMLPattern(): false|string;

	public static function is( mixed $input ): bool
	{
		if ( self::empty( $input ) )
			return FALSE;

		return (bool) self::validate( $input );
	}

	public static function validate( mixed $data ): int
	{
		return self::is( $data ) ? 1 : 0;
	}

	public static function extract( mixed $input ): false|array
	{
		return FALSE;
	}

	/**
	 * Prepares a value for the given context.
	 *
	 * @param  mixed       $value
	 * @param  null|array  $field
	 * @param  null|string $context
	 * @param  mixed       $icon
	 * @return string
	 */
	public static function prep( mixed $value, ?array $field = [], ?string $context = 'display', mixed $icon = NULL ): string
	{
		return (string) $value;
	}

	public static function sanitize( mixed $input, mixed $default = '', ?array $field = [], ?string $context = 'save' ): mixed
	{
		if ( ! $input = Core\Text::force( $input ) )
			return $default;

		$sanitized = Core\Text::trim( Core\Number::translate( $input ) );

		return $sanitized ?: $default;
	}

	/**
	 * Tries to discover if given criteria is supported.
	 *
	 * @param mixed $criteria
	 * @return false|string
	 */
	public static function discovery( mixed $criteria ): false|string
	{
		return self::sanitize( $criteria );
	}

	public static function getHTMLPattern(): false|string
	{
		return FALSE;
	}
}

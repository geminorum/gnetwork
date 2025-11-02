<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

// @SEE: https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Values_and_Units/CSS_data_types

abstract class DataType extends Core\Base
{
	// abstract public static function is( $data );
	// abstract public static function validate( $data );
	// abstract public static function extract( $data );

	/**
	 * Prepares a value for the given context.
	 *
	 * @param string $value
	 * @param array $field
	 * @param string $context
	 * @param string $icon
	 * @return string
	 */
	// abstract public static function prep( $value, $args = [], $context = 'display', $icon = NULL );
	// abstract public static function sanitize( $input, $default = '', $field = [], $context = 'save' );
	// abstract public static function discovery( $criteria );
	// abstract public static function getHTMLPattern();
	// abstract public static function pattern();

	public static function is( $data )
	{
		if ( self::empty( $data ) )
			return FALSE;

		return TRUE;
	}

	public static function validate( $data )
	{
		return self::is( $data );
	}

	public static function extract( $data )
	{
		return $data;
	}

	/**
	 * Prepares a value for the given context.
	 *
	 * @param string $value
	 * @param array $field
	 * @param string $context
	 * @param string $icon
	 * @return string
	 */
	public static function prep( $value, $args = [], $context = 'display', $icon = NULL )
	{
		return $value;
	}

	public static function sanitize( $input, $default = '', $field = [], $context = 'save' )
	{
		$sanitized = Text::trim( $input );
		$sanitized = Number::translate( $sanitized );

		return $sanitized;
	}

	public static function discovery( $criteria )
	{
		return self::sanitize( $criteria );
	}

	public static function pattern()
	{
		return FALSE;
	}
}

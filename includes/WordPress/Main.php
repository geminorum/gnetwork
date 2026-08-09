<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Main extends Core\Base
{
	const BASE   = '';
	const MODULE = FALSE;

	public static function setup(): void {}

	public static function factory()
	{
		throw new Core\Exception( 'The Factory is not defined!' );
	}

	protected static function hook(): string
	{
		$string = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$string.= '_'.strtolower( Core\Text::sanitizeHook( $arg ) );

		if ( static::MODULE )
			$string = '_'.static::MODULE.$string;

		if ( static::BASE )
			$string = '_'.static::BASE.$string;

		return trim( $string, '_' );
	}

	protected static function classs(): string
	{
		$string = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$string.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		if ( static::MODULE )
			$string = '-'.static::MODULE.$string;

		if ( static::BASE )
			$string = '-'.static::BASE.$string;

		return trim( $string, '-' );
	}

	protected static function hash(): string
	{
		$string = '';

		foreach ( func_get_args() as $arg )
			$string.= maybe_serialize( $arg );

		if ( static::MODULE )
			$string = static::MODULE.$string;

		if ( static::BASE )
			$string = static::BASE.$string;

		return md5( $string );
	}

	/**
	 * Retrieves the constant value for given module.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @param string $module
	 * @return mixed
	 */
	protected static function constant( string $key, mixed $default = FALSE, ?string $module = NULL ): mixed
	{
		return static::factory()->constant( $module ?? static::MODULE, $key, $default );
	}

	/**
	 * Calls the callbacks that have been added to given filter hook.
	 *
	 * @param string $hook
	 * @param mixed $arguments
	 * @return mixed
	 */
	protected static function filters( string $hook, ...$arguments ): mixed
	{
		return apply_filters( self::und(
			static::BASE,
			static::MODULE,
			$hook
		), ...$arguments );
	}

	/**
	 * Calls the callbacks that have been added to given action hook.
	 *
	 * @param string $hook
	 * @param mixed $arguments
	 * @return true
	 */
	protected static function actions( string $hook, ...$arguments ): true
	{
		do_action( self::und(
			static::BASE,
			static::MODULE,
			$hook
		), ...$arguments );

		return TRUE;
	}

	protected static function path( ?string $context = NULL, ?string $module = NULL, mixed $fallback = FALSE ): null|false|string
	{
		if ( ! $module = $module ?? static::MODULE )
			return $fallback;

		return static::factory()->module( $module )->get_module_path( $context );
	}

	public static function bailWithError( array $results, string $code, ?string $message = NULL, ?string $error_key = NULL ): array
	{
		$results[( $error_key ?? 'error' )] = new Core\Error( $code, $message ?? '' );

		return $results;
	}

	protected static function getString( string $string, string|false $subgroup = 'post', string $group = 'titles', string|false|null $fallback = FALSE, ?string $module = NULL ): null|false|string
	{
		if ( ! $module = $module ?? static::MODULE )
			return $fallback;

		return static::factory()->module( $module )->get_string( $string, $subgroup, $group, $fallback );
	}

	protected static function getPostMeta( int $post_id, string|false $field = FALSE, mixed $default = [], ?string $metakey = NULL, ?string $module = NULL ): mixed
	{
		if ( ! $module = $module ?? static::MODULE )
			return $default;

		return FALSE === $field
			? static::factory()->module( $module )->get_postmeta_legacy( $post_id, $default )
			: static::factory()->module( $module )->get_postmeta_field( $post_id, $field, $default, $metakey );
	}

	protected static function posttypes( string|array|null $posttypes = NULL, bool $check = FALSE, ?string $module = NULL ): array
	{
		if ( ! $module = $module ?? static::MODULE )
			return [];

		if ( $check && ! static::factory()->enabled( $module ) )
			return [];

		return static::factory()->module( $module )->posttypes( $posttypes );
	}
}

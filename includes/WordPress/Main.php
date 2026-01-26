<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Main extends Core\Base
{
	const BASE   = '';
	const MODULE = FALSE;

	public static function setup() {}

	public static function factory()
	{
		throw new Core\Exception( 'The Factory is not defined!' );
	}

	protected static function hook()
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

	protected static function classs()
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

	protected static function hash()
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

	protected static function constant( $key, $default = FALSE, $module = NULL )
	{
		return static::factory()->constant( $module ?? static::MODULE, $key, $default );
	}

	protected static function actions( $hook, ...$args )
	{
		return do_action( sprintf( '%s%s_%s',
			static::BASE,
			static::MODULE ? sprintf( '_%s', static::MODULE ) : '',
			$hook
		), ...$args );
	}

	protected static function filters( $hook, ...$args )
	{
		return apply_filters( sprintf( '%s%s_%s',
			static::BASE,
			static::MODULE ? sprintf( '_%s', static::MODULE ) : '',
			$hook
		), ...$args );
	}

	protected static function path( $context = NULL, $module = NULL, $fallback = FALSE )
	{
		if ( ! $module = $module ?? static::MODULE )
			return $fallback;

		return static::factory()->module( $module )->get_module_path( $context );
	}

	protected static function getString( $string, $posttype = 'post', $group = 'titles', $fallback = FALSE, $module = NULL )
	{
		if ( ! $module = $module ?? static::MODULE )
			return $fallback;

		return static::factory()->module( $module )->get_string( $string, $posttype, $group, $fallback );
	}

	protected static function getPostMeta( $post_id, $field = FALSE, $default = [], $metakey = NULL, $module = NULL )
	{
		if ( ! $module = $module ?? static::MODULE )
			return $default;

		return FALSE === $field
			? static::factory()->module( $module )->get_postmeta_legacy( $post_id, $default )
			: static::factory()->module( $module )->get_postmeta_field( $post_id, $field, $default, $metakey );
	}

	protected static function posttypes( $posttypes = NULL, $check = FALSE, $module = NULL )
	{
		if ( ! $module = $module ?? static::MODULE )
			return [];

		if ( $check && ! static::factory()->enabled( $module ) )
			return [];

		return static::factory()->module( $module )->posttypes( $posttypes );
	}
}

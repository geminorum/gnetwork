<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class UserMeta extends Core\Base
{
	public static function get( string $key = '', mixed $object = NULL, mixed $default = NULL ): mixed
	{
		if ( ! $user = User::get( $object ) )
			return FALSE;

		if ( FALSE === ( $value = get_user_meta( $user->ID, $key, TRUE ) ) )
			return $default;

		return $value;
	}

	public static function delete( string $key = '', mixed $object = NULL, mixed $default = '' ): bool
	{
		if ( ! $key )
			return FALSE;

		if ( ! $user = User::get( $object ) )
			return FALSE;

		return (bool) delete_metadata( 'user', $user->ID, $key, $default, TRUE );
	}

	public static function deleteAll( string $key, mixed $value ): bool
	{
		if ( ! $key )
			return FALSE;

		return (bool) delete_metadata( 'user', NULL, $key, $value, TRUE );
	}

	public static function store( string $key, mixed $value, mixed $object = NULL, mixed $default = FALSE ): bool
	{
		if ( ! $key )
			return FALSE;

		if ( ! $user = User::get( $object ) )
			return FALSE;

		if ( $value === $default )
			return (bool) delete_user_meta( $user->ID, $key );

		return (bool) update_user_meta( $user->ID, $key, $value );
	}
}

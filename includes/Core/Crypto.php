<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Crypto extends Base
{

	// OLD: `genRandomKey()`
	// OLD: `Core\Text::hash()`
	// ALT: `wp_generate_password()`
	public static function hash( string $salt ): string
	{
		$chr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$len = 32;
		$key = '';

		for ( $i = 0; $i < $len; $i++ )
			$key.= $chr[( rand( 0, ( strlen( $chr ) - 1 ) ) )];

		return md5( $salt.$key );
	}

	/**
	 * Generates limited Hash string.
	 * @author Kyle Coots
	 * @source https://stackoverflow.com/a/15193543
	 *
	 * Allow you to create a unique hash with a maximum value of 32.
	 * Hash Gen uses PHP `substr`, `md5`, `uniqid`, and rand to generate a unique
	 * id or hash and allow you to have some added functionality.
	 *
	 * You can also supply a hash to be prefixed or appended
	 * to the hash. `hash` is by default appended to the hash
	 * unless the param `prefix` is set to prefix[true].
	 *
	 * @OLD: `Core\Text::hashLimited()`
	 *
	 * @param int $start
	 * @param int $end
	 * @param bool $hash
	 * @param bool $prefix
	 * @return string
	 */
	public static function hashLimited( ?int $start = NULL, ?int $end = 0, bool $hash = FALSE, bool $prefix = FALSE ): string
	{
		if ( isset( $start, $end ) && FALSE === $hash ) {

			// `start` IS set NO `hash`

			$md_hash  = substr( md5( uniqid( rand(), TRUE ) ), $start, $end );
			$new_hash = $md_hash;

		} else if ( isset( $start, $end ) && FALSE !== $hash && FALSE === $prefix ) {

			// `start` IS set WITH `hash` NOT prefixing

			$md_hash  = substr( md5( uniqid( rand(), TRUE ) ), $start, $end );
			$new_hash = $md_hash.$hash;

		} else if ( ! isset( $start, $end ) && FALSE !== $hash && FALSE === $prefix ) {

			// `start` NOT set WITH `hash` NOT prefixing

			$md_hash  = md5( uniqid( rand(), TRUE ) );
			$new_hash = $md_hash.$hash;

		} else if ( isset( $start, $end ) && FALSE !== $hash && TRUE === $prefix ) {

			// `start` IS set WITH `hash` IS prefixing

			$md_hash  = substr( md5( uniqid( rand(), TRUE ) ), $start, $end );
			$new_hash = $hash.$md_hash;

		} else if ( ! isset( $start, $end ) && FALSE !== $hash && TRUE === $prefix ) {

			// `start` NOT set WITH `hash` IS prefixing

			$md_hash  = md5( uniqid( rand(), TRUE ) );
			$new_hash = $hash.$md_hash;

		} else {

			$new_hash = md5( uniqid( rand(), TRUE ) );
		}

		return $new_hash;
	}

	/**
	 * Generates combinations from given options.
	 * @source https://codereview.stackexchange.com/a/62198
	 *
	 * @param array $options
	 * @param int $count
	 * @return array
	 */
	public static function getCombinations( array $options, int $count ): array
	{
		$results = [];

		self::_genCombinations( $results, [], $options, $count, 0 );

		return $results;
	}

	private static function _genCombinations( array &$solutions, array $solution, array $options, int $count, int $call ): void
	{
		if ( $count === count( $solution ) )
			$solutions[] = $solution;

		if ( count( $solution ) < $count ) {

			for ( $i = $call; $i < count( $options ); $i++ ) {
				$solution[] = $options[$i];
				self::_genCombinations( $solutions, $solution, $options, $count, $i + 1 );
				array_pop( $solution );
			}
		}
	}

	// @REF: https://github.com/kasparsd/numeric-shortlinks
	const BIJECTION_DIC  = 'abcdefghijklmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789';
	const BIJECTION_BASE = 57; // `strlen( BIJECTION_DIC )`

	public static function encodeBijection( int $id ): string
	{
		$slug = [];
		$dic  = static::BIJECTION_DIC;

		while ( $id > 0 ) {

			$key = $id % static::BIJECTION_BASE;

			$slug[] = $dic[$key];

			$id = floor( $id / static::BIJECTION_BASE );
		}

		return implode( '', array_reverse( $slug ) );
	}

	public static function decodeBijection( string $slug ): int|string
	{
		$id  = 0;
		$dic = static::BIJECTION_DIC;

		foreach ( Text::strSplit( trim( $slug ) ) as $char ) {

			$pos = strpos( $dic, $char );

			if ( FALSE === $pos )
				return $slug;

			$id = $id * static::BIJECTION_BASE + $pos;
		}

		return $id ?: $slug;
	}

	// https://gist.github.com/DevWael/1742e12a2d442840f8ba91cfe78a5fb6
	public static function generateUUID()
	{
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

			// 32 bits for `time_low`
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for `time_mid`
			mt_rand( 0, 0xffff ),

			// 16 bits for `time_hi_and_version`,
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for `clk_seq_hi_res`,
			// 8 bits for `clk_seq_low`,
			// two most significant bits holds zero and one for variant `DCE 1.1`
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for `node`
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
}

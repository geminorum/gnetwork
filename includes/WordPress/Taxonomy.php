<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Taxonomy extends Core\Base
{

	public static function getTermParents( $term_id, $taxonomy )
	{
		$parents = [];
		$up      = TRUE;

		while ( $up ) {

			$term = get_term( (int) $term_id, $taxonomy );

			if ( $term->parent )
				$parents[] = (int) $term->parent;

			else
				$up = FALSE;

			$term_id = $term->parent;
		}

		return count( $parents ) ? $parents : FALSE;
	}

	public static function getTargetTerm( $target, $taxonomy )
	{
		$target = trim( $target );

		if ( is_numeric( $target ) ) {

			if ( $term = term_exists( (int) $target, $taxonomy ) )
				return get_term( $term['term_id'], $taxonomy );

			else
				return FALSE; // dont insert numbers as new term!

		} else if ( $term = term_exists( $target, $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( apply_filters( 'string_format_i18n', $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( Core\Text::formatName( $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( Core\Text::reFormatName( $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );
		}

		// avoid filtering the new term
		$term = wp_insert_term( $target, $taxonomy );

		if ( self::isError( $term ) )
			return FALSE;

		return get_term( $term['term_id'], $taxonomy );
	}
}

<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class HTML extends Core\Base
{

	// same as `html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );`
	public static function entityDecode( $text )
	{
		return \WP_HTML_Decoder::decode_attribute( $text );
	}

	public static function extractRootText( $html )
	{
		if ( ! $html )
			return '';

		$processor = new \WP_HTML_Tag_Processor( $html );
		$parts     = [];
		$depth     = 0;

		while ( $processor->next_token() ) {

			$token_type = $processor->get_token_type();

			if ( '#text' === $token_type ) {

				if ( 0 === $depth )
					$parts[] = $processor->get_modifiable_text();

				continue;
			}

			if ( '#tag' !== $token_type )
				continue;

			if ( $processor->is_tag_closer() ) {

				if ( $depth > 0 )
					--$depth;

				continue;
			}

			$token_name = $processor->get_tag();

			if ( $token_name && ! \WP_HTML_Processor::is_void( $token_name ) )
				++$depth;
		}

		return trim( implode( '', $parts ) );
	}

	public static function stripTags( $html )
	{
		if ( ! $html )
			return '';

		$processor = new \WP_HTML_Tag_Processor( $html );
		$text      = '';

		while ( $processor->next_token() ) {

			if ( '#text' === $processor->get_token_name() )
				$text.= $processor->get_modifiable_text();

			$text = $html;

			while ( preg_match( '/<[^>]*>/', $text ) )
				$text = preg_replace( '/<[^>]*>.*?<\/[^>]*>|<[^>]*\/>|<[^>]*>/s', '', $text );
		}

		return $text;
	}

	public static function setAtts( $html, $attributes )
	{
		if ( ! $html )
			return '';

		$processor = new \WP_HTML_Tag_Processor( $html );

		if ( $processor->next_tag() ) {

			foreach ( $attributes as $attribute_key => $attribute_value )
				$processor->set_attribute( $attribute_key, $attribute_value );

			$html = $processor->get_updated_html();
		}

		return $html;
	}
}

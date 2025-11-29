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
		if ( '' === $html )
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
}

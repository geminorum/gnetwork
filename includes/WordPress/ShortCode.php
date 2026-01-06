<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class ShortCode extends Core\Base
{

	const NAME_INPUT_PATTERN = '[-a-zA-Z0-9_]{3,}';

	public static function exists( $tag )
	{
		return empty( $tag ) ? FALSE : shortcode_exists( $tag );
	}

	// @SEE: https://konstantin.blog/2013/dont-do_shortcode/
	public static function apply( $text, $ignore_html = FALSE )
	{
		return empty( $text ) ? '' : apply_shortcodes( $text, $ignore_html );
	}

	/**
	 * Calls a short-code by its tag name.
	 * @source https://wpbitz.com/dont-use-do_shortcode/
	 *
	 * Directly executes a short-code's callback function using the short-code's
	 * tag name. Can execute a function even if it's in an object class.
	 * Simply pass the short-code's tag and an array of any attributes.
	 *
	 * @global array $shortcode_tags
	 * @param string $shortcode The short-code tag name.
	 * @param array $atts The attributes (optional).
	 * @param array The short-code content (NULL by default).
	 *
	 * @return string|bool False on failure, the result of the short-code on success.
	 */
	public static function tag( $shortcode, $atts = [], $content = NULL )
	{
		global $shortcode_tags;

		if ( isset( $shortcode_tags[$shortcode] ) && is_callable( $shortcode_tags[$shortcode] ) )
			return call_user_func( $shortcode_tags[$shortcode], $atts, $content, $shortcode );

		return $content;
	}

	// NOTE: like `Core\HTML::tag()`
	public static function build( $tag, $atts = [], $content = NULL )
	{
		$args = '';

		foreach ( $atts as $key => $value )
			$args.= sprintf( ' %s="%s"', $key, $value );

		if ( $content )
			return sprintf( '[%1$s%2$s]%3$s[/%4$s]', $tag, $args, $content, $tag );

		return sprintf( '[%1$s%2$s /]', $tag, $args );
	}

	public static function wrap( $html, $suffix = FALSE, $args = [], $block = TRUE, $extra = [], $base = '' )
	{
		if ( is_null( $html ) )
			return $html;

		$before = empty( $args['before'] ) ? '' : $args['before'];
		$after  = empty( $args['after'] )  ? '' : $args['after'];

		if ( empty( $args['wrap'] ) )
			return $before.$html.$after;

		$classes = [ '-wrap' ];
		$wrap    = TRUE === $args['wrap'] ? ( $block ? 'div' : 'span' ) : $args['wrap'];

		if ( $base )
			$classes[] = sprintf( '%s-wrap-shortcode', $base );

		if ( $suffix )
			$classes[] = 'shortcode-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		if ( ! empty( $args['class'] ) )
			$classes = Core\HTML::attrClass( $classes, $args['class'] );

		if ( $after )
			return $before.Core\HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $html ).$after;

		return Core\HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $before.$html );
	}
}

<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class ShortCode extends Core\Base
{

	const NAME_INPUT_PATTERN = '[-a-zA-Z0-9_]{3,}';

	/**
	 * Combines user attributes with known attributes and fill in defaults when needed.
	 * NOTE: wrapper for `shortcode_atts()` with string handling
	 *
	 * @param string|array|null $input
	 * @param array $defaults
	 * @param string $shortcode
	 * @param string $single_fallback
	 * @return array
	 */
	public static function attributes( string|array|null $input, array $defaults, string $shortcode = '', $single_fallback = '' ): array
	{
		$attributes = [];

		if ( $single_fallback && is_string( $input ) )
			$attributes[$single_fallback] = $input;

		else if ( is_array( $input ) )
			$attributes = $input;

		return shortcode_atts(
			$attributes ?? [],
			$defaults,
			$shortcode,
		);
	}

	public static function exists( string $tag ): bool
	{
		return empty( $tag ) ? FALSE : shortcode_exists( $tag );
	}

	// @SEE: https://konstantin.blog/2013/dont-do_shortcode/
	public static function apply( string $text, bool $ignore_html = FALSE ): string
	{
		return empty( $text ) ? '' : (string) apply_shortcodes( $text, $ignore_html );
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
	 *
	 * @param string $shortcode
	 * @param array $attributes
	 * @param string $content
	 * @return mixed
	 */
	public static function tag( string $shortcode, array $attributes = [], ?string $content = NULL ): mixed
	{
		global $shortcode_tags;

		if ( isset( $shortcode_tags[$shortcode] ) && is_callable( $shortcode_tags[$shortcode] ) )
			return call_user_func( $shortcode_tags[$shortcode], $attributes, $content, $shortcode );

		return $content;
	}

	// NOTE: like `Core\HTML::tag()`
	public static function build( string $tag, array $atts = [], ?string $content = NULL )
	{
		$args = '';

		foreach ( $atts as $key => $value )
			$args.= sprintf( ' %s="%s"', $key, $value );

		if ( $content )
			return sprintf( '[%1$s%2$s]%3$s[/%4$s]', $tag, $args, $content, $tag );

		return sprintf( '[%1$s%2$s /]', $tag, $args );
	}

	public static function wrap(
		?string $html,
		string $suffix = '',
		array $args = [],
		bool $block = TRUE,
		string|array $extra = [],
		string $base = '',
	): ?string {

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

	/**
	 * First strip out registered and enclosing short-codes using native
	 * WordPress `strip_shortcodes()` function. Then strip out the short-codes
	 * with a filthy regex, because people don't properly register
	 * their short-codes.
	 *
	 * @old `WordPress\Strings::stripShortCode()`
	 * @source `Yoast\WP\SEO\Helpers\String_Helper::strip_shortcode()`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function strip( mixed $input ): string
	{
		if ( ! $input = Core\Text::force( $input ) )
			return '';

		return preg_replace( '`\[[^\]]+\]`s', '', \strip_shortcodes( $input ) );
	}
}

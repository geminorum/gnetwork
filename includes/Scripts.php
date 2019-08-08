<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Scripts extends Core\Base
{

	const BASE = 'gnetwork';

	public static function enqueueScript( $asset, $dep = [ 'jquery' ], $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/js' )
	{
		$handle  = strtolower( self::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( $handle, $base.$path.'/'.$asset.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function enqueueScriptVendor( $asset, $dep = [], $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/js/vendor' )
	{
		return self::enqueueScript( $asset, $dep, $version, $base, $path );
	}

	public static function enqueueTimeAgo()
	{
		$callback = [ 'gPersianDateTimeAgo', 'enqueue' ];

		if ( ! is_callable( $callback ) )
			return FALSE;

		return call_user_func( $callback );
	}

	public static function enqueueColorPicker()
	{
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	public static function enqueueCodeEditor()
	{
		wp_enqueue_script( 'code-editor' );
		wp_enqueue_style( 'code-editor' );
	}

	public static function enqueueThickBox()
	{
		if ( function_exists( 'add_thickbox' ) )
			add_thickbox();
	}

	public static function enqueueMasonry( $selector = '.card', $grid = '.masonry-grid' )
	{
		$script = 'jQuery(function($) {
			$("'.$grid.'").masonry({
				itemSelector: "'.$selector.'",
				isOriginLeft: ! ( "rtl" === $("html").attr("dir") ),
				percentPosition: true
			});
		});';

		wp_enqueue_script( 'masonry' );
		wp_add_inline_script( 'masonry', $script );
	}

	public static function enqueueGithubMarkdown()
	{
		wp_enqueue_style( static::BASE.'-github-markdown', GNETWORK_URL.'assets/css/markdown.all'.( is_rtl() ? '-rtl' : '' ).'.css', [], GNETWORK_VERSION );
	}

	public static function pkgAutosize( $ver = '4.0.2' )
	{
		$handle = static::BASE.'-autosize';

		wp_enqueue_script( $handle, '//cdn.jsdelivr.net/npm/autosize@'.$ver.'/dist/autosize.min.js', [], NULL, TRUE );
		wp_add_inline_script( $handle, "autosize(document.querySelectorAll('textarea'));" );

		return $handle;
	}

	// @REF: https://github.com/bvanderhoof/gist-embed
	public static function pkgGistEmbed( $ver = '1.0.3' )
	{
		$handle = static::BASE.'-gist-embed';

		wp_enqueue_script( $handle, 'https://cdn.jsdelivr.net/gh/bvanderhoof/gist-embed@'.$ver.'/dist/gist-embed.min.js', [], NULL, TRUE );

		return $handle;
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( self::BASE.'_tinymce_strings', [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.'.self::BASE.'", '.wp_json_encode( $strings ).');'."\n" : '';
	}
}

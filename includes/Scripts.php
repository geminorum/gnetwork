<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Scripts extends Core\Base
{

	const BASE = 'gnetwork';

	public static function inlineScript( $asset, $script, $dep = [ 'jquery' ] )
	{
		if ( empty( $script ) )
			return;

		$handle = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );

		// @REF: https://core.trac.wordpress.org/ticket/44551
		// @REF: https://wordpress.stackexchange.com/a/311279
		wp_register_script( $handle, '', $dep, '', TRUE );
		wp_enqueue_script( $handle ); // must register then enqueue
		wp_add_inline_script( $handle, $script );
	}

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

	// @REF: https://fontawesome.com/how-to-use/customizing-wordpress/snippets/setup-cdn-webfont
	public static function enqueueStyleCDN( $url, $integrity = NULL, $actions = NULL )
	{
		$matched = 1 === preg_match( '|/([^/]+?)\.css$|', $url, $matches )
			? $matches[1]
			: md5( $url );

		$handle = sprintf( '%s-cdn-%s', self::BASE, $matched );

		if ( is_null( $actions ) )
			$actions = [
				'wp_enqueue_scripts',
				'admin_enqueue_scripts',
				'login_enqueue_scripts',
			];

		foreach ( (array) $actions as $action )
			add_action( $action, function() use ( $url, $handle ) {
				wp_enqueue_style( $handle, $url, [], NULL );
			} );

		if ( $integrity )
			add_filter( 'style_loader_tag', function( $html, $registered ) use ( $handle, $integrity ) {
				return $registered == $handle
					? preg_replace( '/\/>$/', sprintf( 'integrity="%s" crossorigin="anonymous" />', $integrity ), $html, 1 )
					: $html;
			}, 10, 2 );

		return $handle;
	}

	public static function registerBlock( $asset, $dep = NULL, $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/blocks' )
	{
		$dep     = is_null( $dep ) ? [ 'wp-blocks', 'wp-i18n', 'wp-components', 'wp-editor' ] : (array) $dep;
		$handle  = strtolower( self::BASE.'-block-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( $handle, $base.$path.'/'.$asset.'/build/index'.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function registerBlockStyle( $asset, $dep = NULL, $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/css' )
	{
		$dep    = is_null( $dep ) ? [] : (array) $dep;
		$handle = strtolower( self::BASE.'-block-'.str_replace( '.', '-', $asset ) );

		wp_register_style( $handle, $base.$path.'/block.'.$asset.'.css', $dep, $version );
		wp_style_add_data( $handle, 'rtl', 'replace' );

		return $handle;
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
		wp_enqueue_style( static::BASE.'-github-markdown', GNETWORK_URL.'assets/css/markdown.all.css', [], GNETWORK_VERSION );
		wp_style_add_data( static::BASE.'-github-markdown', 'rtl', 'replace' );
	}

	public static function pkgAutosize( $ver = '4.0.2' )
	{
		$handle = static::BASE.'-autosize';

		wp_enqueue_script( $handle, '//cdn.jsdelivr.net/npm/autosize@'.$ver.'/dist/autosize.min.js', [], NULL, TRUE );
		wp_add_inline_script( $handle, "autosize(document.querySelectorAll('textarea'));" );

		return $handle;
	}

	// @REF: https://github.com/bvanderhoof/gist-embed
	public static function pkgGistEmbed( $ver = '1.0.4' )
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

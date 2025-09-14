<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

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
			add_action( $action, static function() use ( $url, $handle ) {
				wp_enqueue_style( $handle, $url, [], NULL );
			} );

		if ( $integrity )
			add_filter( 'style_loader_tag', static function ( $html, $registered ) use ( $handle, $integrity ) {
				return $registered == $handle
					? preg_replace( '/\/>$/', sprintf( 'integrity="%s" crossorigin="anonymous" />', $integrity ), $html, 1 )
					: $html;
			}, 10, 2 );

		return $handle;
	}

	public static function registerBlockAsset( $asset, $version = GNETWORK_VERSION, $base_url = GNETWORK_URL, $base_path = GNETWORK_DIR, $dir = 'assets/blocks' )
	{
		$handle = strtolower( self::BASE.'-block-'.str_replace( '.', '-', $asset ) );
		$info   = $base_path.$dir.'/'.$asset.'/build/index.asset.php';
		$path   = $base_path.$dir.'/'.$asset.'/build/index.js';
		$url    = $base_url. $dir.'/'.$asset.'/build/index.js';

		$args = self::atts( [
			'dependencies' => [ 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-polyfill' ],
			'version'      => Core\WordPress::isDev() ? filemtime( $path ) : $version,
		], is_readable( $info ) ? require( $info ) : [] );

		wp_register_script( $handle, $url, $args['dependencies'], $args['version'] );

		return $handle;
	}

	// NOT USED
	public static function registerBlock( $asset, $dep = NULL, $version = GNETWORK_VERSION, $base = GNETWORK_URL, $path = 'assets/blocks' )
	{
		$dep     = is_null( $dep ) ? [ 'wp-blocks', 'wp-components', 'wp-editor' ] : (array) $dep;
		$handle  = strtolower( self::BASE.'-block-'.str_replace( '.', '-', $asset ) );
		$variant = ''; // ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min'; // NOTE: WP-Scripts builds are minified

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

	// @REF: https://wordpress.org/plugins/media-playback-speed/
	// @SEE: https://stackoverflow.com/questions/68051131/how-to-add-playback-speed-button-to-my-html-audio-player
	public static function enqueuePlaybackSpeed( $rates = NULL )
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		$enqueued = self::enqueueScript( 'all.player.playbackspeed', [] );

		add_action( 'wp_footer',
			static function () use ( $rates ) {

				echo '<script type="text/template" id="playback-buttons-template">';

				foreach ( $rates ?? [ '0.5', '1', '1.5', '2' ] as $offset => $args ) {

					if ( ! $args )
						continue;

					$item = [];

					if ( ! is_array( $args ) )
						$item['rate'] = $args;

					else
						$item = $args;

					if ( ! array_key_exists( 'rate', $item ) )
						$item['rate'] = $offset;

					if ( ! array_key_exists( 'title', $item ) )
						$item['title'] = sprintf(
							/* translators: %s: playback speed rate */
							_x( 'Playback Speed %s&times;', 'Scripts: Playback Speed Title', 'gnetwork' ),
							Core\Number::localize( $item['rate'] )
						);

					if ( ! array_key_exists( 'label', $item ) )
						$item['label'] = sprintf(
							/* translators: %s: playback speed rate */
							_x( '%s&times;', 'Scripts: Playback Speed Label', 'gnetwork' ),
							// Core\Number::localize( $item['rate'] )
							$item['rate']
						);

					$classes = [ 'playback-rate-button' ];

					if ( '1' == $item['rate'] ) {
						$classes[] = 'mejs-active';
						$classes[] = 'active-playback-rate';
					}

					$html = Core\HTML::tag( 'button', [
						'type'       => 'button',
						'class'      => $classes,
						'title'      => $item['title'],
						'aria-label' => $item['title'],
						'tabindex'   => 0,
						'data'       => [
							'value' => $item['rate'],
						],
					], $item['label'] );

					echo Core\HTML::wrap( $html, 'mejs-button blank-button' );
				}

				echo '</script>';

			}, 0, 99 );

		return $enqueued;
	}

	public static function enqueueCircularPlayer( $selector = '.mediPlayer', $ver = '0.0.3' )
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		$enqueued = self::enqueueScriptVendor( 'jquery.mediaPlayer', [ 'jquery' ], $ver );
		wp_add_inline_script( $enqueued, 'jQuery(function($){$("'.( $selector ?: '.mediPlayer' ).'").mediaPlayer();});' );

		return $enqueued;
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

	public static function pkgAutosize( $ver = '6.0.1' )
	{
		$handle = static::BASE.'-autosize';

		wp_enqueue_script( $handle, '//cdn.jsdelivr.net/npm/autosize@'.$ver.'/dist/autosize.min.js', [], NULL, TRUE );
		wp_add_inline_script( $handle, "autosize(document.querySelectorAll('textarea'));" );

		return $handle;
	}

	// @REF: https://github.com/bvanderhoof/gist-embed
	public static function pkgGistEmbed( $version = '1.0.4' )
	{
		$handle = static::BASE.'-gist-embed';

		wp_enqueue_script( $handle, 'https://cdn.jsdelivr.net/gh/bvanderhoof/gist-embed@'.$version.'/dist/gist-embed.min.js', [], NULL, TRUE );

		return $handle;
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( self::BASE.'_tinymce_strings', [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.'.self::BASE.'", '.Core\HTML::encode( $strings ).');'."\n" : '';
	}
}

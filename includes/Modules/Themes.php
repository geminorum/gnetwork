<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Themes extends gNetwork\Module
{

	protected $key     = 'themes';
	protected $network = FALSE;
	protected $xmlrpc  = FALSE;
	protected $iframe  = FALSE;
	protected $ajax    = TRUE;

	private $rtl   = NULL;
	private $theme = NULL;

	protected function setup_actions()
	{
		if ( ! $this->options['disable_themes']
			&& ! is_network_admin()
			&& ! is_user_admin() ) {

			$this->action( 'after_setup_theme' );

			if ( is_readable( GNETWORK_DIR.'includes/Misc/ThemesPluggable.php' ) )
				require_once( GNETWORK_DIR.'includes/Misc/ThemesPluggable.php' );
		}

		if ( is_admin() ) {

			// FIXME: NOT WORKING : when trying to enable each theme
			// $this->filter( 'allowed_themes' );

			$this->filter( 'theme_scandir_exclusions' );

			if ( ! WordPress::isDev() )
				$this->action( 'wp_default_scripts', 1, 12, 'admin' );

		} else {

			if ( $this->options['header_code'] && $this->options['header_html'] )
				$this->action( 'wp_head', 0, 9999 );

			if ( $this->options['footer_code'] && $this->options['footer_html'] )
				$this->action( 'wp_footer', 0, 9999 );

			$this->filter( 'amp_post_template_data', 2 );
			$this->action( 'amp_post_template_css' );

			$this->action( 'wp_default_scripts', 1, 12 );

			if ( $this->options['content_actions'] )
				$this->filter( 'the_content', 1, 999 );

			$this->filter( 'body_class', 2, 5 );
			$this->filter( 'post_class', 3, 5 );

			add_filter( 'the_generator', '__return_null', 98 );

			// FIXME: WORKING but for front-end, needs custom styles within this plugin
			// $this->filter_true( 'sensei_disable_styles' );
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Themes', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'disable_themes'  => '1',
			'content_actions' => '0',
			'hidden_title'    => '0',
			'body_class'      => GNETWORK_BODY_CLASS,
			'jquery_cdn'      => '0',
			'jquery_latest'   => '0',
			'jquery_bottom'   => '0',
			'header_code'     => '0',
			'header_html'     => '',
			'footer_code'     => '0',
			'footer_html'     => '',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'disable_themes',
					'type'        => 'disabled',
					'title'       => _x( 'Theme Enhancements', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Enhances supported active themes with styles and extra goodies.', 'Modules: Themes: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'content_actions',
					'title'       => _x( 'Content Actions', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Applies extra hooks before and after post content on front-end.', 'Modules: Themes: Settings', 'gnetwork' ),
					'constant'    => 'GNETWORK_DISABLE_CONTENT_ACTIONS',
				],
				[
					'field'       => 'hidden_title',
					'title'       => _x( 'Hidden Title', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Supports hidden titles on front-end.', 'Modules: Themes: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'body_class',
					'type'        => 'text',
					'title'       => _x( 'Body Class', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Appends as extra HTML body class on all pages on front-end.', 'Modules: Themes: Settings', 'gnetwork' ),
					'field_class' => [ 'regular-text', 'code-text' ],
					'default'     => GNETWORK_BODY_CLASS,
				],
			],
			'_scripts' => [
				[
					'field'       => 'jquery_cdn',
					'title'       => _x( 'jQuery from CDN', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Replace WordPress jQuery with CDN.', 'Modules: Themes: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://code.jquery.com' ),
				],
				[
					'field'       => 'jquery_latest',
					'title'       => _x( 'jQuery Latest', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Replace WordPress jQuery with the latest version from CDN.', 'Modules: Themes: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'jquery_bottom',
					'title'       => _x( 'jQuery on Bottom', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Prints jQuery in footer on front-end.', 'Modules: Themes: Settings', 'gnetwork' ),
				],
			],
			'_insert' => [
				[
					'field'       => 'header_code',
					'title'       => _x( 'Header Code', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Appends the following code to the end of head in HTML, on front-end.', 'Modules: Themes: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'header_html',
					'type'        => 'textarea-code-editor',
					'title'       => _x( 'In Header', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Accepts raw HTML.', 'Modules: Themes: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'footer_code',
					'title'       => _x( 'Footer Code', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Appends the following code to the end of body in HTML, on front-end.', 'Modules: Themes: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'footer_html',
					'type'        => 'textarea-code-editor',
					'title'       => _x( 'In Footer', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Accepts raw HTML.', 'Modules: Themes: Settings', 'gnetwork' ),
				],
			],
		];

		return $settings;
	}

	public function settings_sidebox( $sub, $uri )
	{
		echo self::summaryjQuery();
	}

	public static function summaryjQuery( $caption = FALSE )
	{
		$latest = self::getjQueryVersions( TRUE );
		$core   = self::getjQueryVersions( FALSE );

		return HTML::tableCode( [
			_x( 'Latest jQuery Stable',   'Modules: Themes: jQuery', 'gnetwork' ) => $latest[0],
			_x( 'Latest jQuery Migrate',  'Modules: Themes: jQuery', 'gnetwork' ) => $latest[1],
			_x( 'WordPress jQuery Stable',  'Modules: Themes: jQuery', 'gnetwork' ) => $core[0],
			_x( 'WordPress jQuery Migrate', 'Modules: Themes: jQuery', 'gnetwork' ) => $core[1],
		], FALSE, $caption );
	}

	protected function settings_setup( $sub = NULL )
	{
		Scripts::enqueueCodeEditor();
	}

	public function setup_screen( $screen )
	{
		if ( $this->options['hidden_title']
			&& 'post' == $screen->base
			&& post_type_supports( $screen->post_type, 'title' ) ) {

			$this->action( 'save_post', 3, 20 );
			$this->action( 'page_attributes_misc_attributes', 1, 12 );
		}
	}

	public function save_post( $post_id, $post, $update )
	{
		if ( empty( $_POST[$this->classs( 'hidden-title-present' )] ) )
			return;

		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) )
			return;

		if ( empty( $_POST[$this->classs( 'hidden-title' )] ) )
			delete_post_meta( $post_id, '_hidden_title' );
		else
			update_post_meta( $post_id, '_hidden_title', TRUE );
	}

	public function page_attributes_misc_attributes( $post )
	{
		$name = $this->classs( 'hidden-title' );

		echo '<div class="-wrap field-wrap -checkbox">';

			echo '<input name="'.$name.'-present" type="hidden" value="1" />';

			echo '<label for="'.$name.'" class="selectit">';
			echo '<input name="'.$name.'" type="checkbox" id="'.$name.'" ';
			checked( get_post_meta( $post->ID, '_hidden_title', TRUE ) );
			echo ' /> '._x( 'Hide Title on Front-end', 'Modules: Themes', 'gnetwork' );

		echo '</label></div>';
	}

	// array of excluded directories and files while scanning theme folder.
	public function theme_scandir_exclusions( $exclusions )
	{
		return array_merge( $exclusions, [ 'vendor', 'bower_components', 'node_modules' ] );
	}

	public function wp_default_scripts_admin( &$scripts )
	{
		if ( empty( $scripts->registered['jquery'] ) )
			return;

		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, [ 'jquery-migrate' ] );
	}

	public function wp_default_scripts( &$scripts )
	{
		if ( SCRIPT_DEBUG )
			return;

		$bottom  = $this->options['jquery_bottom'] ? 1 : NULL;
		$disable = ( ! defined( 'GNETWORK_DISABLE_JQUERY_MIGRATE' ) || GNETWORK_DISABLE_JQUERY_MIGRATE );
		$remote  = $this->options['jquery_cdn'] || $this->options['jquery_latest'];

		if ( ! $bottom && ! $disable && ! $remote )
			return;

		list( $jquery_ver, $migrate_ver ) = self::getjQueryVersions( $this->options['jquery_latest'] );

		$jquery_url = $remote
			? 'https://code.jquery.com/jquery-'.$jquery_ver.'.min.js'
			: includes_url( 'js/jquery/jquery.js' );

		$migrate_url = $remote
			? 'https://code.jquery.com/jquery-migrate-'.$migrate_ver.'.min.js'
			: includes_url( 'js/jquery/jquery-migrate.min.js' );

		$scripts->remove( [ 'jquery', 'jquery-core', 'jquery-migrate' ] );
		$scripts->add( 'jquery-core', $jquery_url, FALSE, ( $remote ? NULL : $jquery_ver ), $bottom );

		$deps = [ 'jquery-core' ];

		if ( ! $disable ) {
			$scripts->add( 'jquery-migrate', $migrate_url, FALSE, ( $remote ? NULL : $migrate_ver ), $bottom );
			$deps[] = 'jquery-migrate';
		}

		$scripts->add( 'jquery', FALSE, $deps, ( $remote ? NULL : $jquery_ver ), $bottom );
	}

	// 8/17/2020, 11:25:50 PM
	// 5.6-alpha-48683-src
	private static function getjQueryVersions( $latest = FALSE )
	{
		return $latest
			? [ '3.5.1', '3.3.1' ]
			: [ '1.12.4', '1.4.1' ];
	}

	public function wp_head()
	{
		echo $this->options['header_html'];
	}

	public function wp_footer()
	{
		echo $this->options['footer_html'];
	}

	public function amp_post_template_data( $data, $post )
	{
		$data['font_urls'] = []; // unset default font

		return $data;
	}

	public function amp_post_template_css( $amp_template )
	{
		?>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
		footer ol#references { margin: 16px 16px 0; padding-top: 16px; border-top: 1px solid #c2c2c2; list-style: none; }
		.-wrap.shortcode-asterisks { margin: 16px 0; text-align: center; }
		.amp-wp-article-header .overtitle, .amp-wp-article-header .subtitle { margin: 8px 0; }
		.amp-wp-article-content .-lead { font-size: .89em; color: gray; }
		.amp-wp-article-header + .amp-wp-article-header { margin-top: 0; }
		.amp-wp-article-featured-image { text-align: center; }
		.amp-wp-article-featured-image .wp-caption-text { padding-top: 0; }
		<?php

		if ( ! is_rtl() )
			return;

		?>
		.amp-wp-article-header .amp-wp-meta:last-of-type { text-align: left; }
		.amp-wp-article-header .amp-wp-meta:first-of-type { text-align: right; }
		.back-to-top { left: 16px; right: auto; }
		.amp-wp-footer p { margin: 0 0 0 85px; }
		blockquote { border-right: 2px solid gray; border-left: none; }
		<?php
	}

	public function after_setup_theme()
	{
		$this->rtl = is_rtl();

		if ( $this->isTheme( 'publish' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'publish', TRUE );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'publish', $this->rtl );
				} );
			}

			remove_action( 'publish_credits', 'publish_footer_credits' );
			remove_filter( 'infinite_scroll_credit', 'publish_get_footer_credits' );
			$this->action( 'publish_credits' );

		} else if ( $this->rtl && $this->isTheme( 'hueman' ) ) {

			add_action( 'wp_enqueue_scripts', function(){
				Themes::enqueueStyle( 'hueman', TRUE );

				wp_deregister_script( 'flexslider' );
				// we need the correct handle, so no `Scripts::enqueueScriptVendor()`
				wp_enqueue_script( 'flexslider', GNETWORK_URL.'assets/js/vendor/jquery.flexslider-rtl.min.js', [ 'jquery' ], '2.6.1', TRUE );

			}, 12 );

			add_filter( 'the_excerpt', function( $text ){
				return $text.Themes::continueReading();
			}, 5 );

		} else if ( $this->rtl && $this->isTheme( 'tribes' ) ) {

			add_action( 'wp_head', function(){
				Themes::linkStyleSheet( 'tribes-rtl' );
			}, 20 );

			add_filter( 'mce_css', function( $url ){
				return Themes::appendMCECSS( $url, 'tribes', $this->rtl );
			} );

		} else if ( $this->isTheme( 'semicolon' ) ) {

			remove_action( 'embed_head', 'locale_stylesheet', 30 );

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', function(){
					wp_dequeue_style( [ 'semicolon', 'semicolon-colors', 'semicolon-pt-serif', 'semicolon-open-sans' ] );
					Themes::enqueueStyle( 'semicolon', TRUE );
				}, 12 );
			}

			add_action( 'semicolon_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->rtl && $this->isTheme( 'omega' ) ) {

			add_action( 'wp_enqueue_scripts', function(){
				Themes::enqueueStyle( 'omega', TRUE );
				remove_action( 'omega_footer', 'omega_footer_insert' );
				add_action( 'omega_footer', [ $this, 'twentysomething_credits' ] );
			}, 20 );

		} else if ( $this->isTheme( 'hyde' ) ) {

			add_action( 'wp_enqueue_scripts', function(){
				wp_deregister_style( 'hyde-google-fonts' );
				Themes::enqueueStyle( 'hyde' );
			}, 20 );

		} else if ( $this->isTheme( 'houston' ) ) {

			add_action( 'wp_enqueue_scripts', function(){
				Themes::enqueueStyle( 'houston' );
			}, 20 );

		} else if ( $this->isTheme( 'p2-breathe' ) ) {

			// @HOME: https://wpcom-themes.svn.automattic.com/p2-breathe/
			// used with: https://github.com/Automattic/o2

			if ( $this->rtl ) {

				// the plugin forgets!
				load_plugin_textdomain( 'o2' );

				remove_action( 'init', 'breathe_fonts' );

				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'p2-breathe', TRUE );
				}, 20 );
			}

			add_action( 'breathe_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'p2', 'gp2' ) ) {
			// @HOME: http://p2theme.com/
			// @DEMO: https://p2demo.wordpress.com/
			// @REPO: https://wordpress.org/themes/p2/

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'p2-rtl' );
					// wp_enqueue_style( 'p2-rtl', GNETWORK_URL.'assets/css/themes/p2-rtl.css', [], GNETWORK_VERSION );
					// wp_enqueue_style( 'p2-print-style-rtl', GNETWORK_URL.'assets/css/themes/p2-rtl-print.css', [ 'p2-rtl' ], GNETWORK_VERSION, 'print' );
				}, 99 );
			}

			$this->filter( 'prologue_poweredby_link' );

		// FALLBACK: for gP2 child theme: https://github.com/geminorum/gp2/
		} else if ( $this->isTheme( 'p2' ) ) {

			$this->filter( 'prologue_poweredby_link' );

		} else if ( $this->isTheme( 'revera' ) ) {

			defined( 'GNETWORK_DISABLE_JQUERY_MIGRATE' )
				|| define( 'GNETWORK_DISABLE_JQUERY_MIGRATE', FALSE );

			if ( $this->rtl ) {
				add_theme_support( 'post-thumbnails' );

				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'revera', TRUE );

					// wp_deregister_script( 'flexslider' );
					// Scripts::enqueueScriptVendor( 'jquery.flexslider-rtl', [ 'jquery' ], '2.6.1' );

				}, 20 );

				add_filter( 'the_excerpt', function( $text ){
					return $text.Themes::continueReading();
				}, 5 );
			}

		} else if ( $this->isTheme( 'ari' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'ari', TRUE );
				}, 20 );
			}

			// ari does not work well with custom posttypes
			add_filter( 'post_class', function( $classes ){
				$classes[] = 'post';
				return $classes;
			} );

			add_action( 'get_footer', function(){
				echo '<style>#footer.clearfix {display:none;}</style>';
				echo '<div id="footer" style="display:block;">'.gnetwork_credits( $this->rtl, FALSE ).'</div>';
			} );

		} else if ( $this->isTheme( 'easy-docs' ) ) {

			add_action( 'wp_enqueue_scripts', function(){
				Themes::enqueueStyle( 'easy-docs' );
			}, 20 );

		} else if ( $this->isTheme( 'rams' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', function(){
					remove_action( 'wp_print_styles', 'rams_load_style' );
					wp_enqueue_style( 'rams_style', get_stylesheet_uri() );
					Themes::enqueueStyle( 'rams', TRUE );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'rams', $this->rtl );
				} );
			}

		} else if ( $this->isTheme( 'didi-lite' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'didi-lite', TRUE );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'didi-lite', $this->rtl );
				} );
			}

		} else if ( $this->isTheme( 'atlantic' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'atlantic-rtl' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'atlantic', $this->rtl );
				} );
			}

		} else if ( $this->isTheme( 'aster' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'aster-rtl' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'aster', $this->rtl );
				} );
			}

		} else if ( $this->isTheme( 'chosen' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'chosen-rtl' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'chosen', $this->rtl );
				} );
			}

			add_filter( 'ct_chosen_footer_text', function( $footer_text ){
				return gnetwork_credits( $this->rtl, FALSE );
			} );

		} else if ( $this->isTheme( 'untitled' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'untitled', TRUE );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'untitled', $this->rtl );
				} );
			}

			add_action( 'untitled_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'storefront' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'storefront-rtl' );
				}, 20 );
			}

			remove_action( 'storefront_footer', 'storefront_credit', 20 );
			add_action( 'storefront_footer', [ $this, 'underscores_credits' ], 20 );

			$this->filter( [
				'storefront_recent_products_args',
				'storefront_featured_products_args',
				'storefront_popular_products_args',
				'storefront_on_sale_products_args',
				'storefront_best_selling_products_args',
			], 1, 9 );

			add_filter( 'woocommerce_subcategory_count_html', '__return_null' );

		} else if ( $this->isTheme( 'twentyeleven' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'twentyeleven-rtl' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'twentyeleven', $this->rtl );
				} );
			}

			add_action( 'twentyeleven_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentytwelve' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'twentytwelve-rtl' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'twentytwelve', $this->rtl );
				} );
			}

			add_action( 'twentytwelve_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentythirteen' ) ) {

			add_action( 'twentythirteen_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentyfourteen' ) ) {

			add_action( 'twentyfourteen_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentyfifteen' ) ) {

			if ( $this->rtl ) {

				remove_action( 'embed_head', 'locale_stylesheet', 30 );

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'twentyfifteen-rtl' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'twentyfifteen', $this->rtl );
				} );
			}

			add_action( 'twentyfifteen_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentysixteen' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'twentysixteen-rtl' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'twentysixteen', $this->rtl );
				} );
			}

			add_action( 'twentysixteen_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentyseventeen' ) ) {

			if ( $this->rtl ) {

				remove_action( 'embed_head', 'locale_stylesheet', 30 );

				add_action( 'wp_head', function(){
					Themes::linkStyleSheet( 'twentyseventeen-rtl' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'twentyseventeen', $this->rtl );
				} );

				add_action( 'get_template_part_template-parts/footer/site', [ $this, 'underscores_credits' ] );
			}
		}
	}

	public function isTheme( $template, $not_stylesheet = NULL )
	{
		if ( is_null( $this->theme ) )
			$this->theme = wp_get_theme();

		if ( ! is_null( $not_stylesheet ) )
			return ( $template == $this->theme->template && $not_stylesheet != $this->theme->stylesheet );

		return ( $template == $this->theme->template || $template == $this->theme->stylesheet );
	}

	public function allowed_themes( $themes )
	{
		if ( ! WordPress::isSuperAdmin() )
			return $themes;

		$allowed = [];

		foreach ( wp_get_themes() as $theme )
			$allowed[$theme->get_stylesheet()] = TRUE;

		return $allowed;
	}

	public function the_content( $content )
	{
		if ( defined( 'GNETWORK_DISABLE_CONTENT_ACTIONS' )
			&& GNETWORK_DISABLE_CONTENT_ACTIONS )
				return $content;

		$before = $after = '';

		if ( has_action( $this->hook( 'content_before' ) ) ) {
			ob_start();
				$this->actions( 'content_before', $content );
			$before = trim( ob_get_clean() );
		}

		if ( has_action( $this->hook( 'content_after' ) ) ) {
			ob_start();
				$this->actions( 'content_after', $content );
			$after = trim( ob_get_clean() );
		}

		return HTML::wrap( $before, 'gnetwork-wrap-actions content-before' )
			.$content
		.HTML::wrap( $after, 'gnetwork-wrap-actions content-after' );
	}

	public static function continueReading()
	{
		return vsprintf( ' <a href="%1$s" aria-label="%3$s" class="%4$s">%2$s</a>', [
			esc_url( apply_filters( 'the_permalink', get_permalink(), NULL ) ),
			_x( 'Read more&nbsp;<span class="excerpt-link-hellip">&hellip;</span>', 'Modules: Themes', 'gnetwork' ),
			/* translators: %s: post title */
			sprintf( _x( 'Continue reading &ldquo;%s&rdquo; &hellip;', 'Modules: Themes', 'gnetwork' ), get_the_title() ),
			'excerpt-link',
		] );
	}

	public static function getByLine( $before = '<span class="byline">', $after = '</span>' )
	{
		/* translators: %s: author name */
		$text   = _x( 'by %s', 'Modules: Themes', 'gnetwork' );
		/* translators: %s: author name */
		$title  = _x( 'View all posts by %s', 'Modules: Themes', 'gnetwork' );
		$format = '<span class="author vcard"><a class="url fn n" href="%3$s" title="%2$s" rel="author">%1$s</a></span>';
		$author = get_the_author();

		return $before.sprintf( $text, vsprintf( $format, [
			HTML::escape( $author ),
			HTML::escape( sprintf( $title, $author ) ),
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		] ) ).$after;
	}

	public static function postedOn( $before = '', $after = '', $byline = TRUE )
	{
		$format = '<a href="%1$s" title="%2$s" rel="bookmark">'
			.'<time class="entry-date published" datetime="%3$s">%4$s</time></a>';

		echo $before;

		vprintf( $format, [
			esc_url( apply_filters( 'the_permalink', get_permalink(), NULL ) ),
			HTML::escape( get_the_time() ),
			HTML::escape( get_the_date( 'c' ) ),
			HTML::escape( get_the_date() ),
		] );

		if ( $byline )
			echo ' '.self::getByLine();

		echo $after;
	}

	public function body_class( $classes, $class )
	{
		if ( $this->options['body_class'] )
			$classes[] = trim( $this->options['body_class'] );

		if ( function_exists( 'get_network' ) )
			$classes[] = 'network-'.HTML::sanitizeClass( URL::prepTitle( str_replace( '.', '-', get_network()->domain ) ) );

		$classes[] = 'locale-'.HTML::sanitizeClass( strtolower( str_replace( '_', '-', get_locale() ) ) );

		if ( is_user_logged_in() )
			$classes[] = 'locale-user-'.HTML::sanitizeClass( strtolower( str_replace( '_', '-', get_user_locale() ) ) );

		if ( is_singular() && ( $post = get_post() ) ) {

			if ( ! trim( $post->post_title ) )
				$classes[] = '-singular-empty-title';
		}

		return $classes;
	}

	public function post_class( $classes, $class, $post_id )
	{
		$classes[] = 'entry';

		if ( ! $post = get_post( $post_id ) )
			return $classes;

		if ( $this->options['hidden_title']
			&& get_post_meta( $post_id, '_hidden_title', TRUE ) )
				$classes[] = '-hidden-title';

		if ( ! trim( $post->post_title ) )
			$classes[] = '-empty-title';

		return $classes;
	}

	public static function appendMCECSS( $url, $theme, $rtl = FALSE )
	{
		$file = $rtl ? $theme.'-rtl.css' : $theme.'.css';

		if ( ! empty( $url ) )
			$url.= ',';

		return $url.GNETWORK_URL.'assets/css/tinymce/'.$file;
	}

	public static function enqueueStyle( $theme, $rtl = FALSE )
	{
		wp_enqueue_style( 'gnetwork-themes-'.$theme, GNETWORK_URL.'assets/css/themes/'.$theme.( $rtl ? '-rtl' : '' ).'.css', [], GNETWORK_VERSION );
	}

	// with no RTL check
	public static function linkStyleSheet( $css, $version = GNETWORK_VERSION, $media = 'all', $echo = TRUE )
	{
		return HTML::linkStyleSheet( GNETWORK_URL.'assets/css/themes/'.$css.'.css', $version, $media, $echo );
	}

	public function publish_credits()
	{
		echo '<br />'.gnetwork_credits( $this->rtl, FALSE );
	}

	public function prologue_poweredby_link( $html )
	{
		return '<span class="alignleft"'.( $this->rtl ? 'style="direction:rtl !important;"' : 'style="padding-right:5px;"' ).'>'
			.gnetwork_credits( $this->rtl, FALSE ).'</span>';
	}

	// @REF: http://stackoverflow.com/a/15196985
	public function twentysomething_credits()
	{
		echo '<style>#colophon .site-info {visibility:collapse;}</style>'
			.'<span style="visibility:visible;">'
				.gnetwork_credits( $this->rtl, FALSE )
			.'</span>';
	}

	public function underscores_credits()
	{
		echo '<div class="site-info" style="display:block;">';
			echo gnetwork_credits( $this->rtl, FALSE );
		echo '</div>';
	}

	// force storefront defaults to woocomerce settings on customizer
	public function storefront_recent_products_args( $args )
	{
		$limit = function_exists( 'wc_get_default_products_per_row' )
			? wc_get_default_products_per_row()
			: $args['limit'];

		$args['limit']   = $limit;
		$args['columns'] = $limit;

		return $args;
	}
}

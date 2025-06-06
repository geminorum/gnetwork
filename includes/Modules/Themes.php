<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\WordPress;

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
		$this->action( 'after_switch_theme', 2 );

		if ( ! $this->options['disable_themes']
			&& ! is_network_admin()
			&& ! is_user_admin() ) {

			$this->action( 'after_setup_theme' );

			if ( is_readable( GNETWORK_DIR.'includes/Misc/ThemesPluggable.php' ) )
				require_once GNETWORK_DIR.'includes/Misc/ThemesPluggable.php';
		}

		if ( $this->options['disable_patterns'] ) {

			// @REF: https://www.wpexplorer.com/how-to-disable-wordpress-gutenberg-block-patterns/
			$this->filter_false( 'should_load_remote_block_patterns' );
			add_action( 'init', static function () { remove_theme_support( 'core-block-patterns' ); }, 999 );
			add_action( 'admin_init', static function () { remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' ); }, 999 );
		}

		if ( ! is_admin() ) {

			if ( $this->options['header_code'] && $this->options['header_html'] )
				$this->action( 'wp_head', 0, 9999, 'html' );

			if ( $this->options['footer_code'] && $this->options['footer_html'] )
				$this->action( 'wp_footer', 0, 9999, 'html' );

			$this->filter( 'amp_post_template_data', 2 );
			$this->action( 'amp_post_template_css' );

			if ( $this->options['content_actions'] )
				$this->filter( 'the_content', 1, 999 );

			$this->filter( 'body_class', 2, 5 );
			$this->filter( 'post_class', 3, 5 );

			// FIXME: WORKING but for front-end, needs custom styles within this plugin
			// $this->filter_true( 'sensei_disable_styles' );
		}

		// @REF: https://make.wordpress.org/core/2021/07/01/block-styles-loading-enhancements-in-wordpress-5-8/
		// NOTE: Editor module not loading on front
		$this->filter_true( 'should_load_separate_core_block_assets' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Themes', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'disable_themes'   => '1',
			'content_actions'  => '0',
			'hidden_title'     => '0',
			'body_class'       => GNETWORK_BODY_CLASS,
			'disable_patterns' => '1',
			'header_code'      => '0',
			'header_html'      => '',
			'footer_code'      => '0',
			'footer_html'      => '',
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
			'_supports' => [
				[
					'field'       => 'disable_patterns',
					'type'        => 'disabled',
					'title'       => _x( 'Theme Patterns', 'Modules: Themes: Settings', 'gnetwork' ),
					'description' => _x( 'Block Patterns are a feature in WordPress that are part of the Gutenberg editor.', 'Modules: Themes: Settings', 'gnetwork' ),
					'default'     => '1',
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

	// TODO: add `Hide the Image`
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

	public function wp_head_html()
	{
		echo $this->options['header_html'];
	}

	public function wp_footer_html()
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

	public function after_switch_theme( $old_name, $old_theme )
	{
		$current    = wp_get_theme();
		$template   = $current->get_template();
		$stylesheet = $current->get_stylesheet();
		$supported  = $this->_get_supported_themes();

		if ( $this->options['disable_themes'] && in_array( $stylesheet, $supported ) )
			$this->update_option( 'disable_themes', '0' );

		else if ( $this->options['disable_themes'] && in_array( $template, $supported ) )
			$this->update_option( 'disable_themes', '0' );

		else if ( ! $this->options['disable_themes'] && ! in_array( $stylesheet, $supported ) )
			$this->update_option( 'disable_themes', '1' );

		else if ( ! $this->options['disable_themes'] && ! in_array( $template, $supported ) )
			$this->update_option( 'disable_themes', '1' );
	}

	private function _get_supported_themes()
	{
		return [
			'publish',
			'hueman',
			'tribes',
			'semicolon',
			'omega',
			'hyde',
			'houston',
			'revera',
			'ari',
			'easy-docs',
			'rams',
			'didi-lite',
			'atlantic',
			'aster',
			'chosen',
			'untitled',
			'astra',
			'storefront',
			'twentyeleven',
			'twentytwelve',
			'twentythirteen',
			'twentyfourteen',
			'twentyfifteen',
			'twentysixteen',
			'twentyseventeen',
		];
	}

	public function after_setup_theme()
	{
		$this->rtl = is_rtl();

		if ( $this->isTheme( 'publish' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', static function () {
					Themes::enqueueStyle( 'publish', TRUE );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'publish', $this->rtl );
				} );
			}

			remove_action( 'publish_credits', 'publish_footer_credits' );
			remove_filter( 'infinite_scroll_credit', 'publish_get_footer_credits' );
			$this->action( 'publish_credits' );

		} else if ( $this->rtl && $this->isTheme( 'hueman' ) ) {

			add_action( 'wp_enqueue_scripts', static function () {
				Themes::enqueueStyle( 'hueman', TRUE );

				wp_deregister_script( 'flexslider' );
				// we need the correct handle, so no `Scripts::enqueueScriptVendor()`
				wp_enqueue_script( 'flexslider', GNETWORK_URL.'assets/js/vendor/jquery.flexslider-rtl.min.js', [ 'jquery' ], '2.6.1', TRUE );

			}, 12 );

			add_filter( 'the_excerpt', static function ( $text ) {
				return $text.Themes::continueReading();
			}, 5 );

		} else if ( $this->rtl && $this->isTheme( 'tribes' ) ) {

			add_action( 'wp_head', static function () {
				Themes::linkStyleSheet( 'tribes-rtl' );
			}, 20 );

			add_filter( 'mce_css', function ( $url ) {
				return Themes::appendMCECSS( $url, 'tribes', $this->rtl );
			} );

		} else if ( $this->isTheme( 'semicolon' ) ) {

			remove_action( 'embed_head', 'locale_stylesheet', 30 );

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', static function () {
					wp_dequeue_style( [ 'semicolon', 'semicolon-colors', 'semicolon-pt-serif', 'semicolon-open-sans' ] );
					Themes::enqueueStyle( 'semicolon', TRUE );
				}, 12 );
			}

			add_action( 'semicolon_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->rtl && $this->isTheme( 'omega' ) ) {

			add_action( 'wp_enqueue_scripts', function () {
				Themes::enqueueStyle( 'omega', TRUE );
				remove_action( 'omega_footer', 'omega_footer_insert' );
				add_action( 'omega_footer', [ $this, 'twentysomething_credits' ] );
			}, 20 );

		} else if ( $this->isTheme( 'hyde' ) ) {

			add_action( 'wp_enqueue_scripts', static function () {
				wp_deregister_style( 'hyde-google-fonts' );
				Themes::enqueueStyle( 'hyde' );
			}, 20 );

		} else if ( $this->isTheme( 'houston' ) ) {

			add_action( 'wp_enqueue_scripts', static function () {
				Themes::enqueueStyle( 'houston' );
			}, 20 );

		} else if ( $this->isTheme( 'p2-breathe' ) ) {

			// @HOME: https://wpcom-themes.svn.automattic.com/p2-breathe/
			// used with: https://github.com/Automattic/o2

			if ( $this->rtl ) {

				// the plugin forgets!
				load_plugin_textdomain( 'o2' );

				remove_action( 'init', 'breathe_fonts' );

				add_action( 'wp_enqueue_scripts', static function () {
					Themes::enqueueStyle( 'p2-breathe', TRUE );
				}, 20 );
			}

			add_action( 'breathe_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'p2', 'gp2' ) ) {
			// @HOME: http://p2theme.com/
			// @DEMO: https://p2demo.wordpress.com/
			// @REPO: https://wordpress.org/themes/p2/

			if ( $this->rtl ) {

				add_action( 'wp_head', static function () {
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

			self::define( 'GNETWORK_DISABLE_JQUERY_MIGRATE', FALSE );

			if ( $this->rtl ) {
				add_theme_support( 'post-thumbnails' );

				add_action( 'wp_enqueue_scripts', static function () {
					Themes::enqueueStyle( 'revera', TRUE );

					// wp_deregister_script( 'flexslider' );
					// Scripts::enqueueScriptVendor( 'jquery.flexslider-rtl', [ 'jquery' ], '2.6.1' );

				}, 20 );

				add_filter( 'the_excerpt', static function ( $text ) {
					return $text.Themes::continueReading();
				}, 5 );
			}

		} else if ( $this->isTheme( 'ari' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', static function () {
					Themes::enqueueStyle( 'ari', TRUE );
				}, 20 );
			}

			// ari does not work well with custom posttypes
			add_filter( 'post_class', static function ( $classes ) {
				$classes[] = 'post';
				return $classes;
			} );

			add_action( 'get_footer', function () {
				echo '<style>#footer.clearfix {display:none;}</style>';
				echo '<div id="footer" style="display:block;">'.gnetwork_credits( $this->rtl, FALSE ).'</div>';
			} );

		} else if ( $this->isTheme( 'easy-docs' ) ) {

			add_action( 'wp_enqueue_scripts', static function () {
				Themes::enqueueStyle( 'easy-docs' );
			}, 20 );

		} else if ( $this->isTheme( 'rams' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', static function () {
					remove_action( 'wp_print_styles', 'rams_load_style' );
					wp_enqueue_style( 'rams_style', get_stylesheet_uri() );
					Themes::enqueueStyle( 'rams', TRUE );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'rams', $this->rtl );
				} );
			}

		} else if ( $this->isTheme( 'didi-lite' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', static function () {
					Themes::enqueueStyle( 'didi-lite', TRUE );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'didi-lite', $this->rtl );
				} );
			}

		} else if ( $this->isTheme( 'atlantic' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'atlantic-rtl' );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'atlantic', $this->rtl );
				} );
			}

		} else if ( $this->isTheme( 'aster' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'aster-rtl' );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'aster', $this->rtl );
				} );
			}

		} else if ( $this->isTheme( 'chosen' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'chosen-rtl' );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'chosen', $this->rtl );
				} );
			}

			add_filter( 'ct_chosen_footer_text', function ( $footer_text ) {
				return gnetwork_credits( $this->rtl, FALSE );
			} );

		} else if ( $this->isTheme( 'untitled' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', static function () {
					Themes::enqueueStyle( 'untitled', TRUE );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'untitled', $this->rtl );
				} );
			}

			add_action( 'untitled_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'astra' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'astra-rtl' );
				}, 20 );
			}

			// $this->filter_false( 'ast_footer_bar_display' );
			// $this->filter_false( 'astra_get_option_footer-copyright-editor' );

		} else if ( $this->isTheme( 'storefront' ) ) {

			// TODO: https://gist.github.com/bekarice/63a0196ef010d0e30407

			if ( $this->rtl ) {

				add_action( 'wp_enqueue_scripts', static function () {
					wp_dequeue_style( [ 'storefront-fonts' ] );
				}, 12 );

				add_action( 'wp_head', static function () {
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
			// only adds `font-family: "Comic Sans MS", sans-serif;`
			// add_filter( 'storefront_make_me_cute', '__return_true' );

		} else if ( $this->isTheme( 'twentyeleven' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'twentyeleven-rtl' );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'twentyeleven', $this->rtl );
				} );
			}

			add_action( 'twentyeleven_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentytwelve' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'twentytwelve-rtl' );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
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

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'twentyfifteen-rtl' );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'twentyfifteen', $this->rtl );
				} );
			}

			add_action( 'twentyfifteen_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentysixteen' ) ) {

			if ( $this->rtl ) {

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'twentysixteen-rtl' );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'twentysixteen', $this->rtl );
				} );
			}

			add_action( 'twentysixteen_credits', [ $this, 'twentysomething_credits' ] );

		} else if ( $this->isTheme( 'twentyseventeen' ) ) {

			if ( $this->rtl ) {

				remove_action( 'embed_head', 'locale_stylesheet', 30 );

				add_action( 'wp_head', static function () {
					Themes::linkStyleSheet( 'twentyseventeen-rtl' );
				}, 20 );

				add_filter( 'mce_css', function ( $url ) {
					return Themes::appendMCECSS( $url, 'twentyseventeen', $this->rtl );
				} );

				add_action( 'get_template_part_template-parts/footer/site', [ $this, 'underscores_credits' ] );
			}
		}
	}

	public function isTheme( $theme, $except_stylesheet = NULL )
	{
		if ( is_null( $this->theme ) )
			$this->theme = wp_get_theme();

		$template   = $this->theme->get_template();
		$stylesheet = $this->theme->get_stylesheet();

		if ( ! is_null( $except_stylesheet ) )
			return ( $theme == $template && $except_stylesheet != $stylesheet );

		return ( $theme == $template || $theme == $stylesheet );
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

		return Core\HTML::wrap( $before, 'gnetwork-wrap-actions content-before' )
			.$content
		.Core\HTML::wrap( $after, 'gnetwork-wrap-actions content-after' );
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
			Core\HTML::escape( $author ),
			Core\HTML::escape( sprintf( $title, $author ) ),
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
			Core\HTML::escape( get_the_time() ),
			Core\HTML::escape( get_the_date( 'c' ) ),
			Core\HTML::escape( get_the_date() ),
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
			$classes[] = 'network-'.Core\HTML::sanitizeClass( Core\URL::prepTitle( str_replace( '.', '-', get_network()->domain ) ) );

		$classes[] = 'locale-'.Core\HTML::sanitizeClass( strtolower( str_replace( '_', '-', get_locale() ) ) );

		if ( is_user_logged_in() )
			$classes[] = 'locale-user-'.Core\HTML::sanitizeClass( strtolower( str_replace( '_', '-', get_user_locale() ) ) );

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
	public static function linkStyleSheet( $css, $version = GNETWORK_VERSION, $media = 'all', $verbose = TRUE )
	{
		return Core\HTML::linkStyleSheet( GNETWORK_URL.'assets/css/themes/'.$css.'.css', $version, $media, $verbose );
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

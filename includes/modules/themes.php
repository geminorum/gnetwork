<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\WordPress;

class Themes extends \geminorum\gNetwork\ModuleCore
{

	protected $key     = 'themes';
	protected $network = FALSE;
	protected $xmlrpc  = FALSE;
	protected $iframe  = FALSE;

	private $rtl   = NULL;
	private $theme = NULL;

	protected function setup_actions()
	{
		if ( ! $this->options['disable_themes'] ) {
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );

			if ( file_exists( GNETWORK_DIR.'includes/misc/themes-pluggable.php' ) )
				require_once( GNETWORK_DIR.'includes/misc/themes-pluggable.php' );
		}

		if ( is_admin() ) {

			// FIXME: NOT WORKING : when trying to enable each theme
			// add_filter( 'allowed_themes', array( $this, 'allowed_themes' ) );

			$this->filter( 'theme_scandir_exclusions' );

		} else {

			add_action( 'wp_default_scripts', array( $this, 'wp_default_scripts' ), 9 );

			if ( $this->options['content_actions'] )
				add_filter( 'the_content', array( $this, 'the_content' ), 999 );

			add_action( 'wp_head', array( $this, 'wp_head' ), 12 );
			add_filter( 'the_generator', '__return_null', 98 );
			add_filter( 'body_class', array( $this, 'body_class' ), 5, 2 );
			add_filter( 'post_class', array( $this, 'post_class' ), 5, 3 );

			add_action( 'bp_dtheme_credits', array( $this, 'bp_dtheme_credits' ) );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Themes', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'jquery_cdn'      => '0',
			'jquery_bottom'   => '0',
			'disable_themes'  => '0',
			'content_actions' => '1',
			'body_class'      => GNETWORK_BODY_CLASS,
		);
	}

	public function default_settings()
	{
		$settings = array(
			'_general' => array(
				array(
					'field'       => 'jquery_cdn',
					'title'       => _x( 'jQuery from CDN', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Replace WordPress jQuery with CDN', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'jquery_bottom',
					'title'       => _x( 'jQuery on Bottom', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Prints jQuery in footer on front-end', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'disable_themes',
					'title'       => _x( 'Theme Enhancements', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Extra styles and more for suported themes', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Settings::reverseEnabled(),
				),
				array(
					'field'       => 'content_actions',
					'title'       => _x( 'Content Actions', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Extra hooks before and after post content', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				),
				array(
					'field'       => 'body_class',
					'type'        => 'text',
					'title'       => _x( 'Body Class', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This will be added as HTML body class to all pages on front-end', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'code-text' ),
					'default'     => GNETWORK_BODY_CLASS,
				),
			),
		);

		return $settings;
	}

	// array of excluded directories and files while scanning theme folder.
	public function theme_scandir_exclusions( $exclusions )
	{
		return array_merge( $exclusions, [ 'vendor', 'bower_components', 'node_modules' ] );
	}

	public function wp_default_scripts( &$scripts )
	{
		if ( SCRIPT_DEBUG )
			return;

		$bottom  = $this->options['jquery_bottom'] ? 1 : NULL;
		$disable = ( ! defined( 'GNETWORK_DISABLE_JQUERY_MIGRATE' ) || GNETWORK_DISABLE_JQUERY_MIGRATE );

		if ( ! $bottom && ! $disable )
			return;

		// v4.8-alpha-40359
		// 4/2/2017, 5:43:50 AM
		$jquery_ver  = '1.12.4';
		$migrate_ver = '1.4.1';

		$jquery_url = $this->options['jquery_cdn']
			? '//code.jquery.com/jquery-'.$jquery_ver.'.min.js'
			: '/wp-includes/js/jquery/jquery.js';

		$migrate_url = $this->options['jquery_cdn']
			? '//code.jquery.com/jquery-migrate-'.$migrate_ver.'.min.js'
			: '/wp-includes/js/jquery/jquery-migrate.min.js';

		$scripts->remove( 'jquery', 'jquery-core', 'jquery-migrate' );
		$scripts->add( 'jquery-core', $jquery_url, FALSE, $jquery_ver, $bottom );

		$deps = array( 'jquery-core' );

		if ( ! $disable ) {
			$scripts->add( 'jquery-migrate', $migrate_url, FALSE, $migrate_ver, $bottom );
			$deps[] = 'jquery-migrate';
		}

		$scripts->add( 'jquery', FALSE, $deps, $jquery_ver, $bottom );
	}

	public function after_setup_theme()
	{
		$this->rtl = is_rtl();

		if ( $this->isTheme( 'publish' ) ) {
			// https://github.com/kovshenin/publish
			// https://kovshenin.com/themes/publish/
			// https://wordpress.org/themes/publish/

			remove_action( 'publish_credits', 'publish_footer_credits' );
			remove_filter( 'infinite_scroll_credit', 'publish_get_footer_credits' );

			add_action( 'publish_credits', array( $this, 'publish_credits' ) );
			add_action( 'wp_enqueue_scripts', function(){
				Themes::enqueueStyle( 'publish' );
			}, 20 );

			add_filter( 'mce_css', function( $url ){
				return Themes::appendMCECSS( $url, 'publish' );
			} );

		} else if ( $this->isTheme( 'hueman' ) ) { // v2.2.3
			// HOME: http://alxmedia.se/themes/hueman/
			// DEMO: http://demo.alxmedia.se/hueman/
			// REPO: https://github.com/AlxMedia/hueman

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'hueman', TRUE );

					wp_deregister_script( 'flexslider' );
					Utilities::enqueueScriptVendor( 'jquery.flexslider-rtl', array( 'jquery' ), '2.6.1' );

				}, 12 );

				add_filter( 'the_excerpt', function( $text ){
					return $text.Themes::continueReading();
				}, 5 );
			}

		} else if ( $this->isTheme( 'tribes' ) ) { // v1.06

			// HOME: https://www.competethemes.com/tribes/
			// DEMO: https://www.competethemes.com/tribes-live-demo/

			if ( $this->rtl ) {
				add_action( 'wp_head', function(){
					Utilities::linkStyleSheet( 'themes.tribes-rtl.css' );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'tribes' );
				} );
			}

		} else if ( $this->isTheme( 'semicolon' ) ) { // v0.9
			// HOME: https://kovshenin.com/themes/semicolon/
			// DEMO: http://semicolon.kovshenin.com/
			// REPO: https://wordpress.org/themes/semicolon

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					wp_deregister_style( 'semicolon' );
					Themes::enqueueStyle( 'semicolon', TRUE );
				}, 12 );
			}

		} else if ( $this->isTheme( 'hyde' ) ) {
			// REPO: https://github.com/tim-online/wordpress-hyde-theme
			// HOME: http://hyde.getpoole.com/

			add_action( 'wp_enqueue_scripts', function(){
				Themes::enqueueStyle( 'hyde' );
			}, 20 );

		} else if ( $this->isTheme( 'houston' ) ) {

			add_action( 'wp_enqueue_scripts', function(){
				Themes::enqueueStyle( 'houston' );
			}, 20 );

		} else if ( $this->isTheme( 'p2', 'gp2' ) ) {
			// @HOME: http://p2theme.com/
			// @DEMO: https://p2demo.wordpress.com/
			// @REPO: https://wordpress.org/themes/p2/

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Utilities::linkStyleSheet( 'themes.p2-rtl.css' );
					// wp_enqueue_style( 'p2-rtl', GNETWORK_URL.'assets/css/themes.p2-rtl.css', array(), GNETWORK_VERSION );
					// wp_enqueue_style( 'p2-print-style-rtl', GNETWORK_URL.'assets/css/themes.p2-rtl-print.css', array( 'p2-rtl' ), GNETWORK_VERSION, 'print' );
				}, 99 );
			}

			add_filter( 'prologue_poweredby_link', array( $this, 'prologue_poweredby_link' ) );

		// FALLBACK: for gP2 child theme: https://github.com/geminorum/gp2/
		} else if ( $this->isTheme( 'p2' ) ) {

			add_filter( 'prologue_poweredby_link', array( $this, 'prologue_poweredby_link' ) );

		} else if ( $this->isTheme( 'revera' ) ) {
			// DEMO: http://demo.fabthemes.com/revera/
			// HOME: http://www.fabthemes.com/revera/

			defined( 'GNETWORK_DISABLE_JQUERY_MIGRATE' ) or define( 'GNETWORK_DISABLE_JQUERY_MIGRATE', FALSE );

			if ( $this->rtl ) {
				add_theme_support( 'post-thumbnails' );

				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'revera', TRUE );

					// wp_deregister_script( 'flexslider' );
					// Utilities::enqueueScriptVendor( 'jquery.flexslider-rtl', array( 'jquery' ), '2.6.1' );

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

		} else if ( $this->isTheme( 'easy-docs' ) ) {
			// HOME: http://shakenandstirredweb.com/theme/easy-docs
			// DEMO: http://support.shakenandstirredweb.com/shaken-grid/

			add_action( 'wp_enqueue_scripts', function(){
				Themes::enqueueStyle( 'easy-docs' );
			}, 20 );

		} else if ( $this->isTheme( 'rams' ) ) {
			// HOME: http://www.andersnoren.se/themes/rams/
			// DEMO: http://andersnoren.se/themes/rams/

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'rams', TRUE );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'rams' );
				} );
			}

		} else if ( $this->isTheme( 'didi-lite' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'didi-lite', TRUE );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'didi-lite' );
				} );
			}

		} else if ( $this->isTheme( 'untitled' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'untitled', TRUE );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'untitled' );
				} );
			}

			add_action( 'untitled_credits', array( $this, 'untitled_credits' ) );

		} else if ( $this->isTheme( 'twentytwelve' ) ) {

			add_action( 'twentytwelve_credits', array( $this, 'twentytwelve_credits' ) );

		} else if ( $this->isTheme( 'twentyfifteen' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					Themes::enqueueStyle( 'twentyfifteen', TRUE );
				}, 20 );

				add_filter( 'mce_css', function( $url ){
					return Themes::appendMCECSS( $url, 'twentyfifteen' );
				} );
			}

			add_action( 'twentyfifteen_credits', array( $this, 'twentytwelve_credits' ) );
		}
	}

	public function wp_head()
	{
		if ( defined( 'GNETWORK_DISABLE_FRONT_STYLES' )
			&& GNETWORK_DISABLE_FRONT_STYLES )
				return;

		Utilities::linkStyleSheet( 'front.all.css' );
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

		$allowed = array();
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
			$before = ob_get_clean();

			if ( trim( $before ) )
				$before = '<div class="gnetwork-wrap-actions content-before">'.$before.'</div>';
		}

		if ( has_action( $this->hook( 'content_after' ) ) ) {
			ob_start();
				$this->actions( 'content_after', $content );
			$after = ob_get_clean();

			if ( trim( $after ) )
				$after = '<div class="gnetwork-wrap-actions content-after">'.$after.'</div>';
		}

		// global $pages, $page;
		// $after .= 'page:'.$page;
		// $after .= self::dump( $pages, TRUE, FALSE );

		return $before.$content.$after;
	}

	public static function continueReading()
	{
		return vsprintf( ' <a href="%1$s" aria-label="%3$s" class="%4$s">%2$s</a>', array(
			get_permalink(),
			_x( 'Read more&nbsp;<span class="excerpt-link-hellip">&hellip;</span>', 'Modules: Themes', GNETWORK_TEXTDOMAIN ),
			sprintf( _x( 'Continue reading &ldquo;%s&rdquo; &hellip;', 'Modules: Themes', GNETWORK_TEXTDOMAIN ), get_the_title() ),
			'excerpt-link',
		) );
	}

	public static function getByLine( $before = '<span class="byline">', $after = '</span>' )
	{
		$text   = _x( 'by %s', 'Modules: Themes', GNETWORK_TEXTDOMAIN );
		$title  = _x( 'View all posts by %s', 'Modules: Themes', GNETWORK_TEXTDOMAIN );
		$format = '<span class="author vcard"><a class="url fn n" href="%3$s" title="%2$s" rel="author">%1$s</a></span>';
		$author = get_the_author();

		return $before.sprintf( $text, vsprintf( $format, array(
			esc_html( $author ),
			esc_attr( sprintf( $title, $author ) ),
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		) ) ).$after;
	}

	public static function postedOn( $before = '', $after = '', $byline = TRUE )
	{
		$format = '<a href="%1$s" title="%2$s" rel="bookmark">'
			.'<time class="entry-date published" datetime="%3$s">%4$s</time></a>';

		echo $before;

		vprintf( $format, array(
			esc_url( get_permalink() ),
			esc_attr( get_the_time() ),
			esc_attr( get_the_date( 'c' ) ),
			esc_html( get_the_date() ),
		) );

		if ( $byline )
			echo ' '.self::getByLine();

		echo $after;
	}

	public function body_class( $classes, $class )
	{
		if ( $this->options['body_class'] )
			$classes[] = trim( $this->options['body_class'] );

		$classes[] = 'locale-'.sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );
		$classes[] = 'locale-user-'.sanitize_html_class( strtolower( str_replace( '_', '-', get_user_locale() ) ) );

		return $classes;
	}

	public function post_class( $classes, $class, $post_id )
	{
		$classes[] = 'entry';

		return $classes;
	}

	public static function appendMCECSS( $url, $theme )
	{
		$file = is_rtl() ? 'editor.'.$theme.'-rtl.css' : 'editor.'.$theme.'.css';

		if ( ! empty( $url ) )
			$url .= ',';

		return $url.GNETWORK_URL.'assets/css/'.$file;
	}

	public static function enqueueStyle( $theme, $rtl = FALSE )
	{
		wp_enqueue_style( 'gnetwork-themes-'.$theme, GNETWORK_URL.'assets/css/themes.'.$theme.( $rtl ? '-rtl' : '' ).'.css', array(), GNETWORK_VERSION );
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

	// @REF: http://stackoverflow.com/a/15196985/4864081
	public function untitled_credits()
	{
		echo '<style>#colophon .site-info {visibility:collapse;}</style>'
			.'<span style="visibility:visible;">'
				.gnetwork_credits( $this->rtl, FALSE )
			.'</span>';
	}

	public function twentytwelve_credits()
	{
		echo '<style>#colophon .site-info > a {display:none;}</style><span style="display:block !important;">'
			.gnetwork_credits( $this->rtl, FALSE ).'</span>';
	}

	public function bp_dtheme_credits()
	{
		echo '<p style="font: 11px/12px Tahoma,Arial,Verdana,sans-serif;margin-bottom:0px;direction:rtl;">';
		echo gnetwork_credits( $this->rtl, FALSE );
		echo '</p>';
	}
}

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Themes extends ModuleCore
{

	protected $key     = 'themes';
	protected $network = FALSE;

	private $rtl   = NULL;
	private $theme = NULL;

	protected function setup_actions()
	{
		if ( ! $this->options['disable_themes'] )
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );

		if ( is_admin() ) {

			// FIXME: NOT WORKING : when trying to enable each theme
			// add_filter( 'allowed_themes', array( $this, 'allowed_themes' ) );

		} else {

			if ( $this->options['content_actions'] )
				add_filter( 'the_content', array( $this, 'the_content' ), 999 );

			if ( $this->options['jquery_bottom'] )
				add_action( 'wp_default_scripts', array( $this, 'wp_default_scripts' ) );

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
					'field'       => 'jquery_bottom',
					'title'       => _x( 'jQuery on Bottom', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Prints jQuery in Footer on Front-End', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'disable_themes',
					'title'       => _x( 'Theme Enhancements', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Extra Styles and More for Suported Themes', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => array(
						__( 'Enabled' , GNETWORK_TEXTDOMAIN ),
						__( 'Disabled', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'content_actions',
					'title'       => _x( 'Content Actions', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Extra Hooks Before and After Post Content', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				),
				array(
					'field'       => 'body_class',
					'type'        => 'text',
					'title'       => _x( 'Body Class', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This Will Be Added as HTML Body Class to All Pages on Front-End', 'Modules: Themes: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'code-text' ),
					'default'     => GNETWORK_BODY_CLASS,
				),
			),
		);

		return $settings;
	}

	public function wp_default_scripts( &$scripts )
	{
		$scripts->add_data( 'jquery', 'group', 1 );
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

				wp_enqueue_style( 'gnetwork-themes-publish',
					GNETWORK_URL.'assets/css/themes.publish.css',
					array(),
					GNETWORK_VERSION );

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

					wp_enqueue_style( 'gnetwork-themes-hueman',
						GNETWORK_URL.'assets/css/themes.hueman-rtl.css',
						array(),
						GNETWORK_VERSION );

					wp_deregister_script( 'flexslider' );
					wp_enqueue_script( 'flexslider',
						GNETWORK_URL.'assets/js/jquery.flexslider-rtl.min.js',
						array( 'jquery' ),
						GNETWORK_VERSION,
						FALSE );

				}, 12 );

				add_filter( 'the_excerpt', function( $text ){
					return $text.Themes::continueReading();
				}, 5 );
			}

		} else if ( $this->isTheme( 'semicolon' ) ) { // v0.9
			// HOME: https://kovshenin.com/themes/semicolon/
			// DEMO: http://semicolon.kovshenin.com/
			// REPO: https://wordpress.org/themes/semicolon

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){

					wp_deregister_style( 'semicolon' );
					wp_enqueue_style( 'semicolon',
						GNETWORK_URL.'assets/css/themes.semicolon-rtl.css',
						array(),
						GNETWORK_VERSION );

				}, 12 );
			}

		} else if ( $this->isTheme( 'hyde' ) ) {
			// REPO: https://github.com/tim-online/wordpress-hyde-theme
			// HOME: http://hyde.getpoole.com/

			add_action( 'wp_enqueue_scripts', function(){
				wp_enqueue_style( 'gnetwork-themes-hyde', GNETWORK_URL.'assets/css/themes.hyde.css', array(), GNETWORK_VERSION );
			}, 20 );

		} else if ( $this->isTheme( 'houston' ) ) {

			add_action( 'wp_enqueue_scripts', function(){
				wp_enqueue_style( 'gnetwork-themes-houston', GNETWORK_URL.'assets/css/themes.houston.css', array(), GNETWORK_VERSION );
			}, 20 );

		} else if ( $this->isTheme( 'p2', 'gp2' ) ) {
			// @HOME: http://p2theme.com/
			// @DEMO: https://p2demo.wordpress.com/
			// @REPO: https://wordpress.org/themes/p2/

			if ( $this->rtl ) {

				add_action( 'wp_head', function(){
					Utilities::linkStyleSheet( GNETWORK_URL.'assets/css/themes.p2-rtl.css' );
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
					wp_enqueue_style( 'gnetwork-themes-revera', GNETWORK_URL.'assets/css/themes.revera-rtl.css', array(), GNETWORK_VERSION );

					// wp_deregister_script( 'flexslider' );
					// wp_enqueue_script( 'flexslider',
					// 	GNETWORK_URL.'assets/js/jquery.flexslider-rtl.min.js',
					// 	array( 'jquery' ),
					// 	GNETWORK_VERSION,
					// 	FALSE );

				}, 20 );

				add_filter( 'the_excerpt', function( $text ){
					return $text.Themes::continueReading();
				}, 5 );
			}

		} else if ( $this->isTheme( 'ari' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					wp_enqueue_style( 'gnetwork-themes-ari', GNETWORK_URL.'assets/css/themes.ari-rtl.css', array(), GNETWORK_VERSION );
				}, 20 );
			}

		} else if ( $this->isTheme( 'easy-docs' ) ) {
			// HOME: http://shakenandstirredweb.com/theme/easy-docs
			// DEMO: http://support.shakenandstirredweb.com/shaken-grid/

			add_action( 'wp_enqueue_scripts', function(){
				wp_enqueue_style( 'gnetwork-themes-easy-docs', GNETWORK_URL.'assets/css/themes.easy-docs.css', array(), GNETWORK_VERSION );
			}, 20 );

		} else if ( $this->isTheme( 'twentytwelve' ) ) {

			add_action( 'twentytwelve_credits', array( $this, 'twentytwelve_credits' ) );

		} else if ( $this->isTheme( 'twentyfifteen' ) ) {

			if ( $this->rtl ) {
				add_action( 'wp_enqueue_scripts', function(){
					wp_enqueue_style( 'gnetwork-themes-twentyfifteen', GNETWORK_URL.'assets/css/themes.twentyfifteen-rtl.css', array(), GNETWORK_VERSION );
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

		Utilities::linkStyleSheet( GNETWORK_URL.'assets/css/front.all.css' );
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
		if ( ! is_super_admin() )
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
				do_action( $this->hook( 'content_before' ), $content );
			$before = ob_get_clean();

			if ( trim( $before ) )
				$before = '<div class="gnetwork-wrap-actions content-before">'.$before.'</div>';
		}

		if ( has_action( $this->hook( 'content_after' ) ) ) {
			ob_start();
				do_action( $this->hook( 'content_after' ), $content );
			$after = ob_get_clean();

			if ( trim( $after ) )
				$after = '<div class="gnetwork-wrap-actions content-after">'.$after.'</div>';
		}

		return $before.$content.$after;
	}

	public static function continueReading()
	{
		return ' '.sprintf(
			_x( '<a %1$s href="%1$s" title="Continue reading &ldquo;%2$s&rdquo; &hellip;" class="%3$s" >%4$s</a>', 'Modules: Themes', GNETWORK_TEXTDOMAIN ),
			get_permalink(),
			get_the_title(),
			'excerpt-link',
			_x( 'Read more&nbsp;<span class="excerpt-link-hellip">&hellip;</span>', 'Modules: Themes', GNETWORK_TEXTDOMAIN )
		);
	}

	public function body_class( $classes, $class )
	{
		if ( $this->options['body_class'] )
			$classes[] = trim( $this->options['body_class'] );

		$classes[] = 'locale-'.sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );

		return $classes;
	}

	public function post_class( $classes, $class, $post_id )
	{
		$classes[] = 'entry';

		return $classes;
	}

	public function publish_credits()
	{
		echo '<br />'.gnetwork_credits( $this->rtl, FALSE );
	}

	public static function appendMCECSS( $url, $theme )
	{
		$file = is_rtl() ? 'editor.'.$theme.'-rtl.css' : 'editor.'.$theme.'.css';

		if ( ! empty( $url ) )
			$url .= ',';

		return $url.GNETWORK_URL.'assets/css/'.$file;
	}

	public function prologue_poweredby_link( $html )
	{
		return '<span class="alignleft"'.( $this->rtl ? 'style="direction:rtl !important;"' : 'style="padding-right:5px;"' ).'>'
			.gnetwork_credits( $this->rtl, FALSE ).'</span>';
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

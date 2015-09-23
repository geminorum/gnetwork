<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkThemes extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = 'themes';

	var $_rtl          = NULL;
	var $_active_theme = NULL;

	protected function setup_actions()
	{
		add_filter( 'the_generator', '__return_null', 98 );

		add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ) );
		add_action( 'wp_head', array( &$this, 'wp_head' ), 12 );
		add_filter( 'body_class', array( &$this, 'body_class' ), 5, 2 );

		// NOT WORKING : when trying to enable each theme
		// add_filter( 'allowed_themes', array( &$this, 'allowed_themes' ) );

		add_action( 'bp_dtheme_credits', array( &$this, 'bp_dtheme_credits' ) );
	}

	public function after_setup_theme()
	{
		if ( $this->is( 'publish' ) ) {
			// https://github.com/kovshenin/publish
			// https://kovshenin.com/themes/publish/
			// https://wordpress.org/themes/publish/

			remove_action( 'publish_credits', 'publish_footer_credits' );
			remove_filter( 'infinite_scroll_credit', 'publish_get_footer_credits' );

			add_action( 'publish_credits', array( &$this, 'publish_credits' ) );
			add_action( 'wp_enqueue_scripts', function(){

				wp_enqueue_style( 'gnetwork-themes-publish',
					GNETWORK_URL.'assets/css/themes.publish.css',
					array(),
					GNETWORK_VERSION );

			}, 20 );

			add_filter( 'mce_css', function( $url ){
				return gNetworkThemes::appendMCECSS( $url, 'publish' );
			} );

		} else if ( $this->is( 'hueman' ) ) { // v2.2.3
			// HOME: http://alxmedia.se/themes/hueman/
			// DEMO: http://demo.alxmedia.se/hueman/
			// REPO: https://github.com/AlxMedia/hueman

			if ( is_rtl() ) {
				add_action( 'wp_enqueue_scripts', function(){

					wp_enqueue_style( 'gnetwork-themes-hueman',
						GNETWORK_URL.'assets/css/themes.hueman-rtl.css',
						array(),
						GNETWORK_VERSION );

					wp_deregister_script( 'flexslider' );
					wp_enqueue_script( 'flexslider',
						GNETWORK_URL.'assets/js/jquery.flexslider-rtl-min.js',
						array( 'jquery' ),
						GNETWORK_VERSION,
						FALSE );

				}, 12 );

				add_filter( 'the_excerpt', function( $text ){
					return $text.self::continueReading();
				}, 5 );
			}

		} else if ( $this->is( 'semicolon' ) ) { // v0.9
			// HOME: https://kovshenin.com/themes/semicolon/
			// DEMO: http://semicolon.kovshenin.com/
			// REPO: https://wordpress.org/themes/semicolon

			if ( is_rtl() ) {
				add_action( 'wp_enqueue_scripts', function(){

					wp_deregister_style( 'semicolon' );
					wp_enqueue_style( 'semicolon',
						GNETWORK_URL.'assets/css/themes.semicolon-rtl.css',
						array(),
						GNETWORK_VERSION );

				}, 12 );
			}

		} else if ( $this->is( 'hyde' ) ) {
			// REPO: https://github.com/tim-online/wordpress-hyde-theme
			// HOME: http://hyde.getpoole.com/

			add_action( 'wp_enqueue_scripts', function(){
				wp_enqueue_style( 'gnetwork-themes-hyde', GNETWORK_URL.'assets/css/themes.hyde.css', array(), GNETWORK_VERSION );
			}, 20 );

		} else if ( $this->is( 'houston' ) ) {

			add_action( 'wp_enqueue_scripts', function(){
				wp_enqueue_style( 'gnetwork-themes-houston', GNETWORK_URL.'assets/css/themes.houston.css', array(), GNETWORK_VERSION );
			}, 20 );

		} else if ( $this->is( 'p2' ) ) {

			add_filter( 'prologue_poweredby_link', array( &$this, 'prologue_poweredby_link' ) );

		} else if ( $this->is( 'revera' ) ) {
			// DEMO: http://demo.fabthemes.com/revera/
			// HOME: http://www.fabthemes.com/revera/

			defined( 'GNETWORK_DISABLE_JQUERY_MIGRATE' ) or define( 'GNETWORK_DISABLE_JQUERY_MIGRATE', FALSE );

			if ( is_rtl() ) {
				add_theme_support( 'post-thumbnails' );

				add_action( 'wp_enqueue_scripts', function(){
					wp_enqueue_style( 'gnetwork-themes-revera', GNETWORK_URL.'assets/css/themes.revera-rtl.css', array(), GNETWORK_VERSION );

					// wp_deregister_script( 'flexslider' );
					// wp_enqueue_script( 'flexslider',
					// 	GNETWORK_URL.'assets/js/jquery.flexslider-rtl-min.js',
					// 	array( 'jquery' ),
					// 	GNETWORK_VERSION,
					// 	FALSE );

				}, 20 );
			}

		} else if ( $this->is( 'ari' ) ) {

			if ( is_rtl() ) {
				add_action( 'wp_enqueue_scripts', function(){
					wp_enqueue_style( 'gnetwork-themes-ari', GNETWORK_URL.'assets/css/themes.ari-rtl.css', array(), GNETWORK_VERSION );
				}, 20 );
			}

		} else if ( $this->is( 'easy-docs' ) ) {
			// HOME: http://shakenandstirredweb.com/theme/easy-docs
			// DEMO: http://support.shakenandstirredweb.com/shaken-grid/

			add_action( 'wp_enqueue_scripts', function(){
				wp_enqueue_style( 'gnetwork-themes-easy-docs', GNETWORK_URL.'assets/css/themes.easy-docs.css', array(), GNETWORK_VERSION );
			}, 20 );

		} else if ( $this->is( 'twentytwelve' ) ) {

			add_action( 'twentytwelve_credits', array( &$this, 'twentytwelve_credits' ) );
		}
	}

	public function wp_head()
	{
		if ( defined( 'GNETWORK_DISABLE_FRONT_STYLES' ) && GNETWORK_DISABLE_FRONT_STYLES )
			return;

		gNetworkUtilities::linkStyleSheet( GNETWORK_URL.'assets/css/front.all.css' );
	}

	// helper
	public function is( $theme )
	{
		if ( is_null( $this->_active_theme ) )
			$this->_active_theme = wp_get_theme();

		return ( $theme == $this->_active_theme->template || $theme == $this->_active_theme->stylesheet );
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

	// HELPER:
	public function continueReading()
	{
		return ' '.sprintf(
			__( '<a %1$s href="%1$s" title="Continue reading &ldquo;%2$s&rdquo; &hellip;" class="%3$s" >%4$s</a>', GNETWORK_TEXTDOMAIN ),
			get_permalink(),
			get_the_title(),
			'excerpt-link',
			__( 'Read more&nbsp;<span class="excerpt-link-hellip">&hellip;</span>', GNETWORK_TEXTDOMAIN )
		);
	}

	public function body_class( $classes, $class )
	{
		if ( GNETWORK_BODY_CLASS )
			$classes[] = GNETWORK_BODY_CLASS;

		$classes[] = 'locale-'.get_locale();

		return $classes;
	}

	public function publish_credits()
	{
		echo '<br />'.gnetwork_credits( is_rtl(), FALSE );
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
		return '<span class="alignleft"'.( is_rtl() ? 'style="direction:rtl !important;"' : 'style="padding-right:5px;"' ).'>'
			.gnetwork_credits( is_rtl(), FALSE ).'</span>';
	}

	public function twentytwelve_credits()
	{
		echo '<style>#colophon .site-info > a {display:none;}</style><span style="display:block !important;">'
			.gnetwork_credits( is_rtl(), FALSE ).'</span>';
	}

	public function bp_dtheme_credits()
	{
		echo '<p style="font: 11px/12px Tahoma,Arial,Verdana,sans-serif;margin-bottom:0px;direction:rtl;">';
		echo gnetwork_credits( is_rtl(), FALSE );
		echo '</p>';
	}
}

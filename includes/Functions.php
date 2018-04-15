<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

if ( ! function_exists( 'gnetwork_github' ) ) :
	function gnetwork_github( $atts = [], $content = NULL ) {

		if ( gNetwork()->module( 'code' ) )
			return gNetwork()->code->shortcode_github_readme( $atts, $content );

		return $content;
	}
endif;

if ( ! function_exists( 'gnetwork_github_readme' ) ) :
	function gnetwork_github_readme( $repo = 'geminorum/gnetwork', $wrap = TRUE, $fallback = NULL ) {

		if ( gNetwork()->module( 'code' ) ) {

			$html = gNetwork()->code->shortcode_github_readme( [
				'repo'    => $repo,
				'context' => 'overview',
			], is_null( $fallback )
				? sprintf( 'Cannot Connect to <a href="https://github.com/%s">%s</a> on GitHub.com', $repo, $repo )
				: $fallback
			);

			if ( $html ) echo $wrap ? '<div class="-wrap gnetwork-overview-wrap">'.$html.'</div>' : $html;
		}
	}
endif;

if ( ! function_exists( 'gnetwork_ip_lookup' ) ) :
	function gnetwork_ip_lookup( $ip ) {

		if ( ! $ip )
			return $ip;

		if ( $service = gNetwork()->option( 'lookup_ip_service', 'site', 'https://redirect.li/map/?ip=%s' ) )
			return \geminorum\gNetwork\Core\HTML::tag( 'a', [
				'href'   => sprintf( $service, $ip ),
				'class'  => '-ip-lookup',
				'target' => '_blank',
			], $ip );

		return $ip;
	}
endif;

if ( ! function_exists( 'gnetwork_update_notice' ) ) :
	function gnetwork_update_notice( $plugin = GNETWORK_FILE ) {

		if ( class_exists( '\geminorum\gNetwork\Utilities' ) )
			return \geminorum\gNetwork\Utilities::updateNotice( $plugin );
	}
endif;

if ( ! function_exists( 'gnetwork_register_imagesize' ) ) :
	function gnetwork_register_imagesize( $name, $atts = [] ) {

		if ( class_exists( '\geminorum\gNetwork\Media' ) )
			return \geminorum\gNetwork\Media::registerImageSize( $name, $atts );
	}
endif;

if ( ! function_exists( 'gnetwork_powered' ) ) :
	function gnetwork_powered( $rtl = NULL ) {
		$default = '<a class="-powered" href="https://wordpress.org/" title="WP powered"><span class="dashicons dashicons-wordpress-alt"></span></a>';
		$custom  = gNetwork()->option( 'text_powered', 'branding' );
		return $custom ? $custom : $default;
	}
endif;

if ( ! function_exists( 'gnetwork_copyright' ) ) :
	function gnetwork_copyright( $rtl = NULL ) {

		if ( $blog = gNetwork()->option( 'text_copyright', 'blog' ) )
			return $blog;

		if ( $branding = gNetwork()->option( 'text_copyright', 'branding' ) )
			return $branding;

		// &#8220;Time is Priceless, Tea is Not!&#8221;
		return __( 'Built on <a href="http://wordpress.org/" title="Semantic Personal Publishing Platform">WordPress</a> and tea!', GNETWORK_TEXTDOMAIN );
	}
endif;

if ( ! function_exists( 'gnetwork_credits' ) ) :
	function gnetwork_credits( $rtl = NULL, $admin = NULL ) {
		if ( is_null( $rtl ) )
			$rtl = is_rtl();

		if ( is_null( $admin ) )
			$admin = is_admin();

		if ( $admin )
			return gnetwork_powered( $rtl );
		else
			return gnetwork_copyright( $rtl );
	}
endif;

if ( ! function_exists( '__donot_cache_page' ) ) : function __donot_cache_page() {
	\geminorum\gNetwork\Core\WordPress::doNotCache();
} endif;

if ( ! function_exists( '__gpersiandate_skip' ) ) : function __gpersiandate_skip() {
	defined( 'GPERSIANDATE_SKIP' ) or define( 'GPERSIANDATE_SKIP', TRUE );
} endif;

if ( ! function_exists( 'gnetwork_dump' ) ) : function gnetwork_dump( $var, $htmlSafe = TRUE ) {
	\geminorum\gNetwork\Utilities::dump( $var, $htmlSafe );
} endif;

if ( ! function_exists( 'gnetwork_trace' ) ) : function gnetwork_trace( $old = TRUE ) {
	\geminorum\gNetwork\Utilities::trace( $old );
} endif;

if ( ! function_exists( 'get_gmeta' ) ) : function get_gmeta( $field, $args = [] ) {
	if ( is_callable( [ 'geminorum\\gEditorial\\Templates\\Meta', 'getMetaField' ] ) )
		return \geminorum\gEditorial\Templates\Meta::getMetaField( $field, $args );
} endif;
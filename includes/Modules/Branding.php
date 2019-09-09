<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Branding extends gNetwork\Module
{

	protected $key = 'branding';

	protected function setup_actions()
	{
		if ( $this->options['siteicon_fallback'] && is_multisite() )
			$this->filter( 'get_site_icon_url', 3 );

		if ( $this->options['webapp_manifest'] && ! is_admin() && is_main_site() ) {
			$this->action( 'parse_request', 1, 1 );
			$this->filter( 'redirect_canonical', 2 );
		}

		add_action( 'network_credits', function(){
			gnetwork_credits();
		} );

		$this->action_module( 'maintenance', 'template_before', 0, 5 );
		$this->action_module( 'restricted', 'template_before', 0, 5 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Branding', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'brand_name'         => '',
			'brand_url'          => '',
			'content_replace'    => '0',
			'network_sitelogo'   => '',
			'network_siteicon'   => '',
			'theme_color'        => '',
			'siteicon_fallback'  => '0',
			'webapp_manifest'    => '0',
			'webapp_shortname'   => '',
			'webapp_longname'    => '',
			'webapp_description' => '',
			'webapp_color'       => '',
			'text_copyright'     => '',
			'text_powered'       => '',
			'text_slogan'        => '',
		];
	}

	public function default_settings()
	{
		$settings = [];

		$name = get_bloginfo( 'name', 'display' );

		$settings['_general'][] = [
			'field'       => 'brand_name',
			'type'        => 'text',
			'title'       => _x( 'Brand Name', 'Modules: Branding: Settings', 'gnetwork' ),
			'description' => _x( 'Will be used as default brand name. Leave empty to use default', 'Modules: Branding: Settings', 'gnetwork' ),
			'placeholder' => GNETWORK_NAME,
		];

		$settings['_general'][] = [
			'field'       => 'brand_url',
			'type'        => 'url',
			'title'       => _x( 'Brand URL', 'Modules: Branding: Settings', 'gnetwork' ),
			'description' => _x( 'Will be used as default brand URL. Leave empty to use default', 'Modules: Branding: Settings', 'gnetwork' ),
			'placeholder' => GNETWORK_BASE,
		];

		if ( is_multisite() ) {

			// will use blog setting if no multisite
			$settings['_general'][] = [
				'field'       => 'theme_color',
				'type'        => 'color',
				'title'       => _x( 'Theme Color', 'Modules: Branding: Settings', 'gnetwork' ),
				'description' => _x( 'Defines color of the mobile browser address bar. Leave empty to disable.', 'Modules: Branding: Settings', 'gnetwork' ),
			];
		}

		$settings['_general'][] = [
			'field'       => 'network_sitelogo',
			'type'        => 'url',
			'title'       => _x( 'SVG Network Logo', 'Modules: Branding: Settings', 'gnetwork' ),
			'description' => _x( 'Displays as network wide site logo. Leave empty to disable.', 'Modules: Branding: Settings', 'gnetwork' ),
		];

		$settings['_general'][] = [
			'field'       => 'network_siteicon',
			'type'        => 'url',
			'title'       => _x( 'SVG Network Icon', 'Modules: Branding: Settings', 'gnetwork' ),
			'description' => _x( 'Displays as network wide site icon. Leave empty to disable.', 'Modules: Branding: Settings', 'gnetwork' ),
		];

		// only works if SSL enabled
		if ( WordPress::isSSL() || WordPress::isDev() ) {

			$settings['_webapp'] = [
				[
					'field'       => 'webapp_manifest',
					'title'       => _x( 'Manifest', 'Modules: Branding: Settings', 'gnetwork' ),
					'description' => _x( 'Provides the ability to save a site bookmark to a device\'s home screen.', 'Modules: Branding: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( $this->url_manifest( FALSE ), NULL, 'external' ),
				],
				[
					'field'       => 'webapp_shortname',
					'type'        => 'text',
					'title'       => _x( 'Short Name', 'Modules: Branding: Settings', 'gnetwork' ),
					'description' => _x( 'A short name for use as the text on the users home screen.', 'Modules: Branding: Settings', 'gnetwork' ),
					'default'     => $name,
					'field_class' => 'medium-text',
				],
				[
					'field'       => 'webapp_longname',
					'type'        => 'text',
					'title'       => _x( 'Name', 'Modules: Branding: Settings', 'gnetwork' ),
					'description' => _x( 'A name for use in the Web App Install banner.', 'Modules: Branding: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'webapp_description',
					'type'        => 'text',
					'title'       => _x( 'Description', 'Modules: Branding: Settings', 'gnetwork' ),
					'description' => _x( 'A description for use in the Web App Manifest.', 'Modules: Branding: Settings', 'gnetwork' ),
					'field_class' => 'large-text',
				],
				[
					'field'       => 'webapp_color',
					'type'        => 'color',
					'title'       => _x( 'Background Color', 'Modules: Branding: Settings', 'gnetwork' ),
					'description' => _x( 'Defines the expected &ldquo;background color&rdquo; for the website. Leave empty to use theme color.', 'Modules: Branding: Settings', 'gnetwork' ),
				],
			];
		}

		$settings['_texts'] = [
			[
				'field'       => 'text_copyright',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Copyright Notice', 'Modules: Branding: Settings', 'gnetwork' ),
				'description' => _x( 'Displays as copyright notice on the footer on the front-end. Leave empty to use default.', 'Modules: Branding: Settings', 'gnetwork' ),
			],
			[
				'field'       => 'text_powered',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Powered Notice', 'Modules: Branding: Settings', 'gnetwork' ),
				'description' => _x( 'Displays as powered notice on the footer of on the admin. Leave empty to use default.', 'Modules: Branding: Settings', 'gnetwork' ),
			],
			[
				'field'       => 'text_slogan',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Site Slogan', 'Modules: Branding: Settings', 'gnetwork' ),
				'description' => _x( 'Displays as site slogan on the footer of on the admin. Leave empty to use default.', 'Modules: Branding: Settings', 'gnetwork' ),
			],
		];

		$settings['_misc'][] = [
			'field'       => 'content_replace',
			'title'       => _x( 'Content Replace', 'Modules: Branding: Settings', 'gnetwork' ),
			'description' => _x( 'Tries to linkify brand name on the content. Must enable &ldquo;General Typography&rdquo; setting on each site.', 'Modules: Branding: Settings', 'gnetwork' ),
		];

		if ( is_multisite() ) {

			// no use when no multisite!
			$settings['_misc'][] = [
				'field'       => 'siteicon_fallback',
				'title'       => _x( 'Network Site Icon', 'Modules: Branding: Settings', 'gnetwork' ),
				'description' => _x( 'Falls back into main site icon on the network.', 'Modules: Branding: Settings', 'gnetwork' ),
			];
		}

		return $settings;
	}

	public function settings_section_webapp()
	{
		Settings::fieldSection(
			_x( 'Web App', 'Modules: Branding: Settings', 'gnetwork' ),
			/* translators: %s: link url */
			sprintf( _x( 'Web app manifests provide the ability to save a site bookmark to a device\'s home screen. <a href="%s">Read More</a>', 'Modules: Branding: Settings', 'gnetwork' ),
				'https://developer.mozilla.org/en-US/docs/Web/Manifest' )
		);
	}

	public function settings_section_texts()
	{
		Settings::fieldSection(
			_x( 'Notices', 'Modules: Branding: Settings', 'gnetwork' )
		);
	}

	protected function settings_setup( $sub = NULL )
	{
		Scripts::enqueueColorPicker();
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( $this->options['network_sitelogo'] ) {

			echo HTML::img( $this->options['network_sitelogo'] );
			HTML::desc( _x( 'Main Site Logo', 'Modules: Branding', 'gnetwork' ) );

		} else if ( $logo = get_custom_logo() ) {

			echo $logo;
			HTML::desc( _x( 'Main Site Logo', 'Modules: Branding', 'gnetwork' ) );
		}

		if ( $this->options['network_siteicon'] ) {

			echo HTML::img( $this->options['network_siteicon'] );
			HTML::desc( _x( 'Main Site Icon', 'Modules: Branding', 'gnetwork' ) );

		} else if ( $icon = get_site_icon_url( 64 ) ) {

			echo HTML::img( $icon );
			HTML::desc( _x( 'Main Site Icon', 'Modules: Branding', 'gnetwork' ) );
		}
	}

	public function do_link_tag()
	{
		// opensignal will include the tag
		if ( ! defined( 'ONESIGNAL_PLUGIN_URL' ) )
			echo '<link rel="manifest" href="'.$this->url_manifest().'" />'."\n";
	}

	public function parse_request( $request )
	{
		if ( 'manifest.json' == $request->request )
			$this->render_manifest();
	}

	public function redirect_canonical( $redirect_url, $requested_url )
	{
		if ( 'manifest.json' == substr( $requested_url, -7 ) )
			return FALSE;

		return $redirect_url;
	}

	public function url_manifest( $escape = TRUE )
	{
		$url = get_bloginfo( 'url', 'display' ).'/manifest.json';
		return $escape ? esc_url( $url ) : $url;
	}

	private function render_manifest()
	{
		$data = [
			'start_url'  => get_bloginfo( 'url' ),
			'display'    => 'minimal-ui', // FIXME: add radio select
			'short_name' => $this->options['webapp_shortname'],
			'name'       => $this->options['webapp_longname'],
		];

		if ( $this->options['theme_color'] )
			$data['theme_color'] = $this->options['theme_color'];

		if ( $this->options['webapp_color'] )
			$data['background_color'] = $this->options['webapp_color'];

		else if ( $this->options['theme_color'] )
			$data['background_color'] = $this->options['theme_color'];

		if ( $this->options['webapp_description'] )
			$data['description'] = $this->options['webapp_description'];

		if ( is_rtl() )
			$data['dir'] = 'rtl';

		$iso = Utilities::getISO639();

		if ( 'en' != $iso )
			$data['lang'] = $iso;

		// $sizes = [ 48, 96, 192 ]; // Google
		$sizes = [ 32, 192, 180, 270, 512 ]; // WordPress

		if ( $this->options['network_siteicon'] ) {

			foreach ( $sizes as $size )
				$data['icons'][] = [
					'src'   => $this->options['network_siteicon'],
					'type'  => 'image/svg+xml',
					'sizes' => sprintf( '%sx%s', $size, $size ),
				];

		} else if ( $icon = get_option( 'site_icon' ) ) {

			$type = get_post_mime_type( $icon );

			foreach ( $sizes as $size )
				$data['icons'][] = [
					'src'   => wp_get_attachment_image_url( $icon, [ $size, $size ] ),
					'type'  => $type,
					'sizes' => sprintf( '%sx%s', $size, $size ),
				];
		}

		// @REF: https://github.com/SayHelloGmbH/progressive-wordpress#manifest
		$data = apply_filters( 'web_app_manifest', $data );

		nocache_headers();

		header( 'Content-Type: application/manifest+json; charset='.get_option( 'charset' ) );
		echo wp_json_encode( $data );

		exit();
	}

	// @SOURCE: https://github.com/kraftbj/default-site-icon
	public function get_site_icon_url( $url, $size, $blog_id )
	{
		if ( '' !== $url )
			return $url;

		$site = get_site_icon_url( $size, FALSE, get_main_site_id() );

		return FALSE === $site ? '' : $site;
	}

	public function maintenance_template_before()
	{
		if ( $this->options['network_sitelogo'] )
			echo HTML::img( $this->options['network_sitelogo'] );

		else
			echo self::getLogo( FALSE, FALSE );
	}

	public function restricted_template_before()
	{
		if ( $this->options['network_sitelogo'] )
			echo HTML::img( $this->options['network_sitelogo'] );

		else
			echo self::getLogo( FALSE, FALSE );
	}

	// FIXME: get from site logo / not used yet
	public static function getLogo( $wrap = FALSE, $fallback = TRUE, $logo = NULL )
	{
		$brand_name = gNetwork()->brand( 'name' );
		$brand_url  = gNetwork()->brand( 'url' );

		if ( ! is_null( $logo ) )
			$html = HTML::img( $logo, '-logo-img', $brand_name );

		else if ( file_exists( WP_CONTENT_DIR.'/'.GNETWORK_LOGO ) )
			$html = HTML::img( WP_CONTENT_URL.'/'.GNETWORK_LOGO, '-logo-img', $brand_name );

		else if ( $fallback )
			$html = $brand_name;

		else
			return '';

		$html = HTML::tag( 'a', [
			'href'  => $brand_url,
			'title' => $brand_name,
		], $html );

		return $wrap ? HTML::tag( $wrap, [ 'class' => 'logo' ], $html ) : $html;
	}
}

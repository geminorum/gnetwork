<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;

class Branding extends gNetwork\Module
{

	protected $key = 'branding';

	protected function setup_actions()
	{
		if ( $this->options['siteicon_fallback'] && is_multisite() )
			$this->filter( 'get_site_icon_url', 3 );

		if ( $this->options['webapp_manifest'] && ! is_admin() ) {
			$this->action( 'wp_head' );
			$this->action( 'parse_request', 1, 1 );
			$this->filter( 'redirect_canonical', 2 );
		}

		add_action( 'network_credits', function(){
			gnetwork_credits();
		} );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Branding', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
	}

	public function default_options()
	{
		return [
			'theme_color'       => '',
			'siteicon_fallback' => '0',
			'webapp_manifest'   => '0',
			'webapp_shortname'  => '',
			'webapp_longname'   => '',
			'text_copyright'    => '',
			'text_powered'      => '',
			'text_slogan'       => '',
		];
	}

	public function default_settings()
	{
		$name = get_bloginfo( 'name', 'display' );

		return [
			'_general' => [
				[
					'field'       => 'theme_color',
					'type'        => 'color',
					'title'       => _x( 'Theme Color', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Defines color of the mobile browser address bar. Leave empty to disable.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'siteicon_fallback',
					'title'       => _x( 'Network Site Icon', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Falls back into main site icon on the network.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
			'_manifest' => [
				[
					'field'       => 'webapp_manifest',
					'title'       => _x( 'Web App Manifest', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Provides the ability to save a site bookmark to a device\'s home screen.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://developers.google.com/web/fundamentals/web-app-manifest/' ),
				],
				[
					'field'       => 'webapp_shortname',
					'type'        => 'text',
					'title'       => _x( 'Web App Short Name', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A short name for use as the text on the users home screen.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => $name,
					'field_class' => 'medium-text',
				],
				[
					'field'       => 'webapp_longname',
					'type'        => 'text',
					'title'       => _x( 'Web App Name', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'A name for use in the Web App Install banner.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
			'_texts' => [
				[
					'field'       => 'text_copyright',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Copyright Notice', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays as copyright notice on the footer on the front-end. Leave empty to use default.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'text_powered',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Powered Notice', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays as powered notice on the footer of on the admin. Leave empty to use default.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'text_slogan',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Site Slogan', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays as site slogan on the footer of on the admin. Leave empty to use default.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
		];
	}

	protected function settings_setup( $sub = NULL )
	{
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	public function wp_head()
	{
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
			'start_url'   => get_bloginfo( 'url' ),
			'short_name'  => $this->options['webapp_shortname'],
			'name'        => $this->options['webapp_longname'],
			'theme_color' => $this->options['theme_color'],
		];

		nocache_headers();

		header( 'Content-Type: application/json; charset='.get_option( 'charset' ) );
		echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE );

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
}

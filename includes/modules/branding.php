<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;

class Branding extends gNetwork\Module
{

	protected $key = 'branding';

	protected function setup_actions()
	{
		if ( $this->options['siteicon_fallback'] && is_multisite() )
			$this->filter( 'get_site_icon_url', 3 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Branding', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
	}

	public function default_options()
	{
		return [
			'siteicon_fallback' => '0',
			'text_copyright'    => '',
			'text_powered'      => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field' => 'siteicon_fallback',
					'title'       => _x( 'Network Site Icon', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Falls back into main site icon on the network.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'text_copyright',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Copyright Notice', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays as copyright notice on the footer on the front-end.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'Built on <a href="http://wordpress.org/" title="Semantic Personal Publishing Platform">WordPress</a> and tea!', 'Modules: Branding: Copyright Text', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'text_powered',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Powered Notice', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays as powered notice on the footer of on the admin.', 'Modules: Branding: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '<a class="-powered" href="http://wordpress.org/" title="WP powered"><span class="dashicons dashicons-wordpress-alt"></span></a>',
				],
			],
		];
	}

	// @SOURCE: https://github.com/kraftbj/default-site-icon
	public function get_site_icon_url( $url, $size, $blog_id )
	{
		if ( '' !== $url )
			return $url;

		$network = get_network();
		$site    = get_site_icon_url( $size, FALSE, $network->blog_id );

		return FALSE === $site ? '' : $site;
	}
}

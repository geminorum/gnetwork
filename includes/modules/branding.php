<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;

class Branding extends gNetwork\Module
{

	protected $key = 'branding';

	protected function setup_actions()
	{
		if ( is_multisite() )
			$this->filter( 'get_site_icon_url', 3 );
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

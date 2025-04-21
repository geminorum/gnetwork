<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

class GlotPress extends gNetwork\Module
{

	protected $key     = 'glotpress';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( ! Core\WordPress::isPluginActive( 'glotpress/glotpress.php' ) )
			return FALSE;

		if ( is_admin() )
			return;

		$this->action( 'init', 0, 12 );
		$this->filter( 'gp_home_title' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Translate', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'home_title' => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'home_title',
					'type'        => 'text',
					'title'       => _x( 'Home Title', 'Modules: GlotPress: Settings', 'gnetwork' ),
					'description' => _x( 'Appears as home title on front-end of GlotPress.', 'Modules: GlotPress: Settings', 'gnetwork' ),
					'default'     => _x( 'GlotPress', 'Modules: GlotPress: Home Title', 'gnetwork' ),
				],
			],
		];
	}

	public function init()
	{
		wp_deregister_style( 'gp-base' );
		wp_register_style( 'gp-base', GNETWORK_URL.'assets/css/front.glotpress'.( is_rtl() ? '-rtl' : '' ).'.css', [], GNETWORK_VERSION );
	}

	public function gp_home_title( $title )
	{
		return $this->options['home_title']
			? trim( $this->options['home_title'] )
			: _x( 'GlotPress', 'Modules: GlotPress: Home Title', 'gnetwork' );
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\Exception;

class GlotPress extends gNetwork\Module
{

	protected $key     = 'glotpress';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( is_admin() )
			return;

		$this->action( 'plugins_loaded' );
	}

	public function plugins_loaded()
	{
		if ( ! defined( 'GP_VERSION' ) )
			return;

		$this->action( 'init', 0, 12 );
		$this->filter( 'gp_home_title' ); // TODO: add setting for custom title
	}

	public function init()
	{
		wp_deregister_style( 'gp-base' );
		wp_register_style( 'gp-base', GNETWORK_URL.'assets/css/glotpress.all'.( is_rtl() ? '-rtl' : '' ).'.css', [], GNETWORK_VERSION );
	}

	public function gp_home_title( $title )
	{
		return _x( 'GlotPress', 'Modules: GlotPress: Home Title', GNETWORK_TEXTDOMAIN );
	}
}

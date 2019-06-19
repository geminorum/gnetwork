<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;

class bbPress extends gNetwork\Module
{
	protected $key     = 'bbpress';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->filter_set( 'bbp_after_get_the_content_parse_args', [ 'media_buttons' => TRUE ] );

		if ( ! is_admin() ) {

			$this->action( 'wp_enqueue_scripts' );
		}
	}

	public function wp_enqueue_scripts()
	{
		if ( defined( 'GNETWORK_DISABLE_BBPRESS_STYLES' )
			&& GNETWORK_DISABLE_BBPRESS_STYLES )
				return;

		if ( is_bbpress() )
			wp_enqueue_style( static::BASE.'-bbpress', GNETWORK_URL.'assets/css/front.bbpress'.( is_rtl() ? '-rtl' : '' ).'.css', [], GNETWORK_VERSION );
	}
}

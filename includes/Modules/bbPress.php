<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

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

		if ( ! is_bbpress() )
			return;

		wp_enqueue_style( static::BASE.'-bbpress', GNETWORK_URL.'assets/css/front.bbpress.css', [], GNETWORK_VERSION );
		wp_style_add_data( static::BASE.'-bbpress', 'rtl', 'replace' );
	}
}

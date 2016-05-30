<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class bbPress extends ModuleCore
{
	protected $key     = 'bbpress';
	protected $network = FALSE;

	protected function setup_actions()
	{
		add_filter( 'bbp_after_get_the_content_parse_args', function( $args ){
			$args['media_buttons'] = TRUE;
			return $args;
		} );
	}
}

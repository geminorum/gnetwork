<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkbbPress extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	protected function setup_actions()
	{
		add_filter( 'bbp_after_get_the_content_parse_args', function( $args ){
			$args['media_buttons'] = TRUE; // http://bavotasan.com/2014/add-media-upload-button-to-bbpress/
			return $args;
		} );
	}
}

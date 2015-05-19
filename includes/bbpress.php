<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkbbPress extends gNetworkModuleCore
{

	var $_network   = false;
	var $_option_key = false;

	public function setup_actions()
	{
		add_filter( 'bbp_after_get_the_content_parse_args', array( &$this, 'bbp_after_get_the_content_parse_args' ) );
	}

	// http://bavotasan.com/2014/add-media-upload-button-to-bbpress/
	// With this snippet in place, moderators will now have the ability to upload using the built-in media uploader. There may be a way to also allow regular users to have access to the button but I haven�t delved deeper into that since I didn�t require that option.
	function bbp_after_get_the_content_parse_args( $args )
	{
		$args['media_buttons'] = true;
		return $args;
	}

}

// http://bavotasan.com/2014/override-the-default-bbpress-stylesheet/
// http://wordpress.org/plugins/bbpress-forum-redirect/
// http://wordpress.org/plugins/bbp-last-post/

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkDebugBar extends gNetworkModuleCore 
{
	
	var $_option_key = false;
	var $_network    = false;
	
	public function setup_actions()
	{
		add_action( 'debug_bar_panels', array( &$this, 'debug_bar_panels' ) );
	}

	public function debug_bar_panels( $panels )
	{
		require_once GNETWORK_DIR.'includes/debugbar-panel.php';
		
		$panels[] = new gNetwork_Debug_Bar_Panel();
		
		return $panels;
	} 
}

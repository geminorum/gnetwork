<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkLogin extends gNetworkModuleCore
{

	var $_network    = TRUE;
	var $_option_key = FALSE;

	protected function setup_actions()
	{
		add_filter( 'login_headerurl', function( $login_header_url ){
			return GNETWORK_BASE;
		}, 1000 );

		add_filter( 'login_headertitle', function( $login_header_title ){
			return GNETWORK_NAME;
		}, 1000 );

		add_action( 'login_head', array( &$this, 'login_head' ) );
	}

	public function login_head()
	{
		gNetworkUtilities::linkStyleSheet( GNETWORK_URL.'assets/css/login.all.css' );
		gNetworkUtilities::customStyleSheet( 'login.css' );
	}
}

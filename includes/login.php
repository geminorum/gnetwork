<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkLogin extends gNetworkModuleCore
{

	var $_network    = true;
	var $_option_key = false;

	public function setup_actions()
	{
		add_filter( 'login_headerurl', function( $login_header_url ){
			return constant( 'GNETWORK_BASE' );
		}, 1000 );

		add_filter( 'login_headertitle', function( $login_header_title ){
			return constant( 'GNETWORK_NAME' );
		}, 1000 );

		add_action( 'login_head', array( & $this, 'login_head' ) );
		add_filter( 'login_footer', array( & $this, 'login_footer' ) );
	}

	public function login_head()
	{
		gNetworkUtilities::linkStyleSheet( GNETWORK_URL.'assets/css/login.all.css' );
		gNetworkUtilities::customStyleSheet( 'login.css' );
	}

	// Originally from: http://wordpress.org/extend/plugins/always-remember-me/
	// JS that checks the checkbox
	public function login_footer()
	{
echo <<<JS
<script type="text/javascript">
document.getElementById('rememberme').checked = true;
</script>
JS;
	}
}

// TODO : rewrite the error message
// will hide the wrong parameter when someone try to login in your blog. So your visitors can�t know if they mistake the username or password
//add_filter( 'login_errors', '__return_null' );
//add_filter( 'login_messages', '__return_null' );

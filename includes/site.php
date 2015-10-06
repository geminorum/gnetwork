<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkSite extends gNetworkModuleCore
{

	protected $option_key = 'global';
	protected $network    = TRUE;

	protected function setup_actions()
	{
		$this->register_menu( 'global',
			__( 'Global', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( $this->options['login_remember'] )
			add_filter( 'login_footer', array( $this, 'login_footer_remember' ) );
	}

	public function default_options()
	{
		return array(
			'login_remember' => 0,
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'   => 'login_remember',
					'type'    => 'enabled',
					'title'   => __( 'Login Remember', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Always checked Remember Me checkbox', GNETWORK_TEXTDOMAIN ),
					'default' => 0,
				),
			),
		);
	}

	public function login_footer_remember()
	{
echo <<<JS
<script type="text/javascript">
	document.getElementById('rememberme').checked = true;
</script>
JS;
	}
}

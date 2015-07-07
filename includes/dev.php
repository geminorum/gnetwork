<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkDev extends gNetworkModuleCore
{

	var $_option_key = FALSE;
	var $_network    = FALSE;
	var $_ajax       = TRUE;

	protected function setup_actions()
	{
		add_filter( 'http_request_args', array( &$this, 'http_request_args' ), 12, 2 );
		add_filter( 'https_local_ssl_verify', '__return_false' );
		add_filter( 'https_ssl_verify'      , '__return_false' );

		// add_filter( 'embed_oembed_html',            array( & $this, 'embed_oembed_html'    ), 1,  4 );
		// add_filter( 'get_avatar',                   array( & $this, 'get_avatar'           ), 1,  5 );

		// add_action( 'template_redirect', array( & $this, 'template_redirect' ) );
		// add_filter( 'login_url', array( & $this, 'login_url' ), 10, 2 );
	}

	public function http_request_args( $r, $url )
	{
		$r['sslverify'] = FALSE;
		return $r;
	}

	// block oEmbeds from displaying.
	public function embed_oembed_html( $html, $url, $attr, $post_ID )
	{
		return sprintf( '<div class="loading-placeholder gnetwork-dev-placeholder"><p>%s</p></div>',
			sprintf( __( 'Airplane Mode is enabled. oEmbed blocked for %1$s.', GNETWORK_TEXTDOMAIN ), esc_url( $url ) ) );
	}

	// replace all instances of gravatar with a local image file to remove the call to remote service.
	public function get_avatar( $avatar, $id_or_email, $size, $default, $alt )
	{
		$image = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		return "<img alt='{$alt}' src='{$image}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' style='background:#eee;' />";
	}
}

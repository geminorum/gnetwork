<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Core\HTML;

class NotFound extends gNetwork\Module
{

	protected $key     = 'notfound';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( is_admin() )
			return;

		if ( ! GNETWORK_NOTFOUND_LOG )
			return;

		$this->action( 'template_redirect', 0, 99999 );
	}

	public function template_redirect()
	{
		if ( is_404() )
			Logger::siteNotFound( '404', HTML::escapeURL( $_SERVER['REQUEST_URI'] ) );
	}
}

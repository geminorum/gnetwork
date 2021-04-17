<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;

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

		$this->filter( 'pre_handle_404', 2, 99999 );
	}

	public function pre_handle_404( $preempt, $wp_query )
	{
		if ( $pagename = $wp_query->get( 'pagename' ) )
			Logger::siteNotFound( 'HANDLE-404', sprintf( 'PAGENAME: %s', $pagename ) );

		else if ( $name = $wp_query->get( 'name' ) )
			Logger::siteNotFound( 'HANDLE-404', sprintf( 'NAME: %s', $name ) );

		// FIXME: check other vars!

		return $preempt;
	}
}

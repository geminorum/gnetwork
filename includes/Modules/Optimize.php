<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\URL;

class Optimize extends gNetwork\Module
{

	protected $key     = 'optimize';
	protected $network = FALSE;

	private $prefetch_domains = [];

	protected function setup_actions()
	{
		$this->action_self( 'dns_prefetch_domains' );

		if ( is_admin() ) {

			$this->action( 'admin_head', 0, 1 );

		} else {

			$this->action( 'wp_head', 0, 1 );
		}
	}

	public function dns_prefetch_domains( $domains = [] )
	{
		foreach ( (array) $domains as $domain )
			if ( ! empty( $domain ) )
				$this->prefetch_domains[] = URL::trail( $domain );
	}

	// @REF: https://developer.mozilla.org/en-US/docs/Web/Performance/dns-prefetch
	private function _do_dns_prefetch_links()
	{
		foreach ( Arraay::prepString( $this->prefetch_domains ) as $domain )
			printf( '<link rel="dns-prefetch" href="%s" />'."\n", $domain );
	}

	// MAYBE: move to admin module
	public function admin_head()
	{
		$this->_do_dns_prefetch_links();
	}

	// MAYBE: move to themes module
	public function wp_head()
	{
		$this->_do_dns_prefetch_links();
	}
}

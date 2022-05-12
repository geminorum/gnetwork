<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\URL;

class Optimize extends gNetwork\Module
{

	protected $key     = 'optimize';
	protected $network = FALSE;

	private $preconnect_domains = [];
	private $prefetch_domains   = [];

	protected function setup_actions()
	{
		$this->action_self( 'preconnect_domains' );
		$this->action_self( 'dns_prefetch_domains' );
	}

	public function preconnect_domains( $domains = [] )
	{
		foreach ( (array) $domains as $domain )
			if ( ! empty( $domain ) )
				$this->preconnect_domains[] = URL::trail( $domain );
	}

	public function dns_prefetch_domains( $domains = [] )
	{
		foreach ( (array) $domains as $domain )
			if ( ! empty( $domain ) )
				$this->prefetch_domains[] = URL::trail( $domain );
	}

	// @REF: https://developer.mozilla.org/en-US/docs/Web/Performance/dns-prefetch
	public function do_html_head()
	{
		// The `preconnect` includes DNS resolution, as well as establishing the
		// TCP connection, and performing the TLS handshake.
		foreach ( Arraay::prepString( $this->preconnect_domains ) as $domain )
			printf( '<link rel="preconnect" href="%s" crossorigin>'."\n", $domain );

		// The `dns-prefetch` only performs a DNS lookup.
		foreach ( Arraay::prepString( $this->prefetch_domains ) as $domain )
			printf( '<link rel="dns-prefetch" href="%s">'."\n", $domain );
	}
}

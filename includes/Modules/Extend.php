<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;

class Extend extends gNetwork\Module
{

	protected $key = 'extend';

	protected function setup_actions()
	{
		$this->action( 'activated_plugin', 2, 99 );
		$this->action( 'deactivated_plugin', 2, 99 );
	}

	public function activated_plugin( $plugin, $network_wide )
	{
		Logger::siteNOTICE( 'PLUGIN-ACTIVATED', $plugin.( $network_wide ? '|NETWORK-WIDE' : '' ) );
	}

	public function deactivated_plugin( $plugin, $network_deactivating )
	{
		Logger::siteNOTICE( 'PLUGIN-DEACTIVATED', $plugin.( $network_deactivating ? '|NETWORK-WIDE' : '' ) );
	}
}

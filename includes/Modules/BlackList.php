<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;

class BlackList extends gNetwork\Module
{

	protected $key  = 'blacklist';
	protected $ajax = TRUE;

	protected function setup_actions()
	{
		if ( ! is_admin() && $this->options['check_ip'] )
			$this->action( 'init', 0, -10 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Black List', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
	}

	public function init()
	{
		if ( ! is_user_logged_in() && $this->blacklisted() )
			wp_die( $this->options['blacklisted_notice'], 403 );
	}

	public function default_options()
	{
		return [
			'check_ip'           => '0',
			'blacklisted_ips'    => '',
			'blacklisted_notice' => 'you\'re blacklisted, dude!',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'check_ip',
					'title'       => _x( 'Check Addresses', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Enables checking logged-out visitor\'s IP against your blacklist.', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'blacklisted_ips',
					'type'        => 'textarea',
					'title'       => _x( 'IP Addresses', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => sprintf( _x( "Comma or line-seperated IP Ranges or individual IPs to block.\nex: %s", 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
						'<code>1.6.0.0-1.7.255.255, 1.2.3/24, 1.2.3.4/255.255.255.0, 1.8.0.0, 1.8.0.1</code>' ),
					'field_class' => [ 'regular-text', 'code-text' ],
					'after'       => defined( 'GNETWORK_BLACKLIST_IP_GIST' ) ? Settings::fieldAfterButton( Utilities::buttonImportRemoteContent( GNETWORK_BLACKLIST_IP_GIST, $this->classs().'-blacklisted_ips' ) ) : '',
				],
				[
					'field'       => 'blacklisted_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Blacklisted Message', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays on the dead page for the blacklisted addresses.', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'you\'re blacklisted, dude!',
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( class_exists( __NAMESPACE__.'\\Debug' ) )
			Debug::summaryIPs( _x( 'Your IP Summary', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ) );
		else
			HTML::desc( sprintf( _x( 'Your IP: <code title="%s">%s</code>', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ), HTTP::IP(), $_SERVER['REMOTE_ADDR'] ) );
	}

	private function blacklisted()
	{
		if ( ! trim( $this->options['blacklisted_ips'] ) )
			return FALSE;

		$blocks = explode( ',', str_replace( "\n", ',', $this->options['blacklisted_ips'] ) );

		foreach ( $blocks as $block )
			if ( HTTP::IPinRange( $_SERVER['REMOTE_ADDR'], trim( $block ) ) )
				return TRUE;

		return FALSE;
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Blacklist extends gNetwork\Module
{
	protected $key  = 'blacklist';
	protected $ajax = TRUE;
	protected $cron = TRUE;

	protected function setup_actions()
	{
		if ( ! $this->options['check_ip'] )
			return;

		if ( ! is_admin() )
			$this->action( 'init', 0, -10 );

		if ( defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' ) && is_main_site() )
			add_action( $this->hook( 'resync_remote' ), [ $this, 'resync_remote' ] );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Blacklist', 'Modules: Menu Name', 'gnetwork' ) );
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
					'title'       => _x( 'Check Addresses', 'Modules: Blacklist: Settings', 'gnetwork' ),
					'description' => _x( 'Enables checking logged-out visitor\'s IP against your blacklist.', 'Modules: Blacklist: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'blacklisted_ips',
					'type'        => 'textarea',
					'title'       => _x( 'IP Addresses', 'Modules: Blacklist: Settings', 'gnetwork' ),
					'description' => sprintf(
						/* translators: `%s`: IP range example */
						_x( "Comma or line-seperated IP Ranges or individual IPs to block.\nex: %s", 'Modules: Blacklist: Settings', 'gnetwork' ),
						Core\HTML::code( '1.6.0.0-1.7.255.255, 1.2.3/24, 1.2.3.4/255.255.255.0, 1.8.0.0, 1.8.0.1' )
					),
					'field_class' => [ 'regular-text', 'code-text', 'textarea-autosize' ],
					'after'       => defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' )
						? Settings::fieldAfterButton( Utilities::buttonImportRemoteContent(
							GNETWORK_BLACKLIST_REMOTE_CONTENT, $this->classs().'-blacklisted_ips' ) ) : '',
				],
				[
					'field'       => 'blacklisted_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Blacklisted Message', 'Modules: Blacklist: Settings', 'gnetwork' ),
					'description' => _x( 'Displays on the dead page for the blacklisted addresses.', 'Modules: Blacklist: Settings', 'gnetwork' ),
					'default'     => 'you\'re blacklisted, dude!',
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( class_exists( __NAMESPACE__.'\\Debug' ) )
			Debug::summaryIPs( _x( 'Your IP Summary', 'Modules: Blacklist: Settings', 'gnetwork' ), FALSE );
		else
			Core\HTML::desc( sprintf(
				/* translators: `%1$s`: Final IP, `%2$s`: Remote IP */
				_x( 'Your IP: <code title="%1$s">%2$s</code>', 'Modules: Blacklist: Settings', 'gnetwork' ),
				Core\HTTP::IP(),
				$_SERVER['REMOTE_ADDR']
			) );

		if ( $this->options['check_ip'] && defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' ) ) {

			echo '<hr />';

			Core\HTML::desc( _x( 'Your site is scheduled for regularly blacklist updates from the pre-configured remote source.', 'Modules: Blacklist: Settings', 'gnetwork' ) );
		}
	}

	public function schedule_actions()
	{
		if ( ! $this->options['check_ip'] )
			return;

		if ( defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' ) && is_main_site() )
			$this->_hook_event( 'resync_remote', 'monthly' );
	}

	public function resync_remote()
	{
		if ( ! $this->options['check_ip'] )
			return;

		if ( ! defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' ) )
			return;

		if ( ! $content = Core\HTTP::getHTML( GNETWORK_BLACKLIST_REMOTE_CONTENT ) )
			return Logger::FAILED( 'BLACKLIST: Problem getting remote content' );

		if ( $content === $this->options['blacklisted_ips'] )
			return;

		if ( ! $this->update_option( 'blacklisted_ips', trim( $content ) ) )
			Logger::WARNING( 'BLACKLIST: Problem updating remote content' );
	}

	private function blacklisted()
	{
		if ( ! trim( $this->options['blacklisted_ips'] ) )
			return FALSE;

		$blocks  = explode( ',', str_replace( "\n", ',', $this->options['blacklisted_ips'] ) );
		$current = Core\HTTP::normalizeIP( $_SERVER['REMOTE_ADDR'] );

		foreach ( $blocks as $block ) {

			if ( Core\HTTP::IPinRange( $current, trim( $block ) ) ) {

				Logger::siteFAILED( 'BLACKLIST: Blacklisted', $current );

				return TRUE;
			}
		}

		return FALSE;
	}
}

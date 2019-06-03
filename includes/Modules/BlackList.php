<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;

class BlackList extends gNetwork\Module
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
					'field_class' => [ 'regular-text', 'code-text', 'textarea-autosize' ],
					'after'       => defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' )
						? Settings::fieldAfterButton( Utilities::buttonImportRemoteContent(
							GNETWORK_BLACKLIST_REMOTE_CONTENT, $this->classs().'-blacklisted_ips' ) ) : '',
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
			Debug::summaryIPs( _x( 'Your IP Summary', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ), FALSE );
		else
			HTML::desc( sprintf( _x( 'Your IP: <code title="%s">%s</code>', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ), HTTP::IP(), $_SERVER['REMOTE_ADDR'] ) );

		if ( $this->options['check_ip'] && defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' ) ) {

			echo '<hr />';

			HTML::desc( _x( 'Your site is scheduled for weekly blacklist updates from the remote source.', 'Modules: BlackList: Settings', GNETWORK_TEXTDOMAIN ) );
		}
	}

	public function schedule_actions()
	{
		if ( ! $this->options['check_ip'] )
			return;

		if ( defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' ) && is_main_site() )
			$this->_hook_event( 'resync_remote', 'weekly' );
	}

	public function resync_remote()
	{
		if ( ! $this->options['check_ip'] )
			return;

		if ( ! defined( 'GNETWORK_BLACKLIST_REMOTE_CONTENT' ) )
			return;

		if ( ! $content = HTTP::getHTML( GNETWORK_BLACKLIST_REMOTE_CONTENT ) )
			return Logger::WARNING( 'BLACKLIST: Problem getting remote content' );

		if ( $this->update_option( 'blacklisted_ips', trim( $content ) ) )
			Logger::INFO( 'BLACKLIST: Remote content updated' );
		else
			Logger::WARNING( 'BLACKLIST: Problem updating remote content' );
	}

	private function blacklisted()
	{
		if ( ! trim( $this->options['blacklisted_ips'] ) )
			return FALSE;

		$blocks  = explode( ',', str_replace( "\n", ',', $this->options['blacklisted_ips'] ) );
		$current = HTTP::normalizeIP( $_SERVER['REMOTE_ADDR'] );

		foreach ( $blocks as $block ) {

			if ( HTTP::IPinRange( $current, trim( $block ) ) ) {

				Logger::siteNOTICE( 'BLACKLIST: Blacklisted', $current );

				return TRUE;
			}
		}

		return FALSE;
	}
}

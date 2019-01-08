<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;

class Extend extends gNetwork\Module
{

	protected $key = 'extend';

	protected function setup_actions()
	{
		$this->action( 'activated_plugin', 2, 99 );
		$this->action( 'deactivated_plugin', 2, 99 );
	}

	public function setup_menu( $context )
	{
		Network::registerTool( $this->key,
			_x( 'Themes', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'tools' ]
		);
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		$data    = [];
		$network = get_current_network_id();
		$themes  = wp_get_themes();

		foreach ( get_sites( [ 'network_id' => $network ] ) as $site ) {

			switch_to_blog( $site->blog_id );

			$_site = [
				'_site'      => $site,
				'blogname'   => get_option( 'blogname' ),
				'stylesheet' => get_option( 'stylesheet' ),
				'template'   => get_option( 'template' ),
			];

			if ( isset( $themes[$_site['stylesheet']] ) )
				$_site['stylesheet'] = $themes[$_site['stylesheet']]->name;

			if ( isset( $themes[$_site['template']] ) )
				$_site['template'] = $themes[$_site['template']]->name;

			$data[$site->blog_id] = $_site;
		}

		restore_current_blog();

		return HTML::tableList( [
			'site' => [
				'title'    => _x( 'Site', 'Modules: Extend', GNETWORK_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ) {
					return '<code title="'.HTML::escape( $row['blogname'] ).'">'.
						URL::untrail( $row['_site']->domain.$row['_site']->path ).
					'</code>';
				},
			],

			'stylesheet' => [ 'title' => _x( 'Theme', 'Modules: Extend', GNETWORK_TEXTDOMAIN ) ],
			'template'   => [ 'title' => _x( 'Template', 'Modules: Extend', GNETWORK_TEXTDOMAIN ) ],

		], $data, [
			'title' => HTML::tag( 'h3', _x( 'Overview of Current Active Themes', 'Modules: Extend', GNETWORK_TEXTDOMAIN ) ),
			'empty' => HTML::warning( _x( 'No site found!', 'Modules: Extend', GNETWORK_TEXTDOMAIN ), FALSE ),
		] );
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

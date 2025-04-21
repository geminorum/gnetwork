<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Extend extends gNetwork\Module
{
	protected $key = 'extend';

	protected function setup_actions()
	{
		$this->action( 'activated_plugin', 2, 99 );
		$this->action( 'deactivated_plugin', 2, 99 );

		if ( ! is_multisite() )
			return FALSE; // disable menus
	}

	public function setup_menu( $context )
	{
		$this->register_tool( _x( 'Extend', 'Modules: Menu Name', 'gnetwork' ) );
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		$data    = [];
		$network = get_current_network_id();
		$themes  = wp_get_themes();

		foreach ( get_sites( [ 'network_id' => $network ] ) as $site ) {

			WordPress\SwitchSite::to( $site->blog_id );

			$_site = [
				'_site'      => $site,
				'blogname'   => get_option( 'blogname' ),
				'stylesheet' => get_option( 'stylesheet' ),
				'template'   => get_option( 'template' ),
				'plugins'    => get_option( 'active_plugins' ),
				'editorial'  => get_option( 'geditorial_options', [] ),
			];

			if ( isset( $themes[$_site['stylesheet']] ) )
				$_site['stylesheet'] = $themes[$_site['stylesheet']]->name;

			if ( isset( $themes[$_site['template']] ) )
				$_site['template'] = $themes[$_site['template']]->name;

			if ( ! empty( $_site['editorial'] ) )
				$_site['editorial'] = array_keys( array_filter( Core\Arraay::pluck( $_site['editorial'], 'enabled' ) ) );

			$data[$site->blog_id] = $_site;

			WordPress\SwitchSite::lap();
		}

		WordPress\SwitchSite::restore();

		return Core\HTML::tableList( [
			'site' => [
				'title'    => _x( 'Site', 'Modules: Extend', 'gnetwork' ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					return '<code title="'.Core\HTML::escape( $row['blogname'] ).'">'.
						Core\URL::untrail( $row['_site']->domain.$row['_site']->path ).
					'</code>';
				},
			],

			'stylesheet' => [ 'title' => _x( 'Theme', 'Modules: Extend', 'gnetwork' ) ],
			'template'   => [ 'title' => _x( 'Template', 'Modules: Extend', 'gnetwork' ) ],

			'plugins' => [
				'title'    => _x( 'Plugins', 'Modules: Extend', 'gnetwork' ),
				'class'    => '-extend-plugins -has-list -has-list-ltr',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					$list = [];

					foreach ( (array) $value as $path ) {
						$plugin = explode( '/', $path );
						$list[] = sprintf( '<code>%s</code>', array_shift( $plugin ) );
					}

					return $list ? Core\HTML::rows( $list ) : Utilities::htmlEmpty();
				},
			],

			'editorial' => [
				'title'    => _x( 'Editorial', 'Modules: Extend', 'gnetwork' ),
				'class'    => '-extend-editorial -has-list -has-list-ltr',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					return empty( $value )
						? Utilities::htmlEmpty()
						: Core\HTML::rows( array_map( [ 'geminorum\\gNetwork\\Core\\HTML', 'code' ], $value ) );
				},
			],

		], $data, [
			'title' => Core\HTML::tag( 'h3', _x( 'Overview of Current Active Theme and Plugins', 'Modules: Extend', 'gnetwork' ) ),
			'empty' => Core\HTML::warning( _x( 'No site found!', 'Modules: Extend', 'gnetwork' ), FALSE ),
		] );
	}

	public function activated_plugin( $plugin, $network_wide )
	{
		Logger::siteNOTICE( 'PLUGIN-ACTIVATED-'.( $network_wide ? 'NETWORK' : 'SITE' ), $plugin );
	}

	public function deactivated_plugin( $plugin, $network_deactivating )
	{
		Logger::siteNOTICE( 'PLUGIN-DEACTIVATED-'.( $network_deactivating ? 'NETWORK' : 'SITE' ), $plugin );
	}
}

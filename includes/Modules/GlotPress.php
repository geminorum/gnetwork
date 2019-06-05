<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\WordPress;

class GlotPress extends gNetwork\Module
{

	protected $key     = 'glotpress';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( ! WordPress::isPluginActive( 'glotpress/glotpress.php' ) )
			return FALSE;

		if ( is_admin() )
			return;

		$this->action( 'init', 0, 12 );
		$this->filter( 'gp_home_title' );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Translate', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'home_title' => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'home_title',
					'type'        => 'text',
					'title'       => _x( 'Home Title', 'Modules: GlotPress: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Appears as home title on front-end of GlotPress.', 'Modules: GlotPress: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'GlotPress', 'Modules: GlotPress: Home Title', GNETWORK_TEXTDOMAIN ),
				],
			],
		];
	}

	public function init()
	{
		wp_deregister_style( 'gp-base' );
		wp_register_style( 'gp-base', GNETWORK_URL.'assets/css/glotpress.all'.( is_rtl() ? '-rtl' : '' ).'.css', [], GNETWORK_VERSION );
	}

	public function gp_home_title( $title )
	{
		return $this->options['shetab_card_notes']
			? trim( $this->options['shetab_card_notes'] )
			: _x( 'GlotPress', 'Modules: GlotPress: Home Title', GNETWORK_TEXTDOMAIN );
	}
}

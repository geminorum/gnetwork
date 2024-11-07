<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core;

class Optimize extends gNetwork\Module
{

	protected $key     = 'optimize';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'plugins_loaded', 0, 1 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Optimize', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'jquery_enhanced' => '0',
			'jquery_cdn'      => '0',
			'jquery_latest'   => '0',
			'jquery_bottom'   => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_jquery' => [
				[
					'field'       => 'jquery_enhanced',
					'title'       => _x( 'jQuery Enhancements', 'Modules: Optimize: Settings', 'gnetwork' ),
					'description' => _x( 'Enhances use of jQuery by WordPress on front-end and administration.', 'Modules: Optimize: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'jquery_cdn',
					'title'       => _x( 'jQuery from CDN', 'Modules: Optimize: Settings', 'gnetwork' ),
					'description' => _x( 'Replace WordPress jQuery with CDN.', 'Modules: Optimize: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://code.jquery.com' ),
				],
				[
					'field'       => 'jquery_latest',
					'title'       => _x( 'jQuery Latest', 'Modules: Optimize: Settings', 'gnetwork' ),
					'description' => _x( 'Replace WordPress jQuery with the latest version.', 'Modules: Optimize: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'jquery_bottom',
					'title'       => _x( 'jQuery on Bottom', 'Modules: Optimize: Settings', 'gnetwork' ),
					'description' => _x( 'Prints jQuery in footer on front-end.', 'Modules: Optimize: Settings', 'gnetwork' ),
				],
			],
		];
	}

	public function plugins_loaded()
	{
		$this->_cleanup_options();
		$this->_cleanup_hooks();

		if ( is_admin() ) {

			if ( $this->options['jquery_enhanced'] )
				$this->action( 'wp_default_scripts', 1, 12, 'admin' );

		} else {

			if ( $this->options['jquery_enhanced'] )
				$this->action( 'wp_default_scripts', 1, 12, 'front' );
		}

		if ( $this->options['jquery_enhanced'] && $this->options['jquery_cdn'] )
			$this->filter( 'wp_resource_hints', 2 );
	}

	private function _cleanup_options()
	{
		add_filter( 'pre_option_hack_file', '__return_null' );
	}

	private function _cleanup_hooks()
	{
		foreach ( [
			'rss2_head',
			'commentsrss2_head',
			'rss_head',
			'rdf_header',
			'atom_head',
			'comments_atom_head',
			'opml_head',
			'app_head',
			] as $action )
				remove_action( $action, 'the_generator' );

		remove_action( 'wp_head', 'locale_stylesheet' );
		remove_action( 'embed_head', 'locale_stylesheet', 30 );
		remove_action( 'wp_head', 'wp_generator' );

		// completely remove the version number from pages and feeds
		add_filter( 'the_generator', '__return_null', 99 );

		remove_filter( 'comment_text', 'make_clickable', 9 );
		remove_filter( 'comment_text', 'capital_P_dangit', 31 );

		// to avoid the URL rewriting logic from running unnecessarily
		// @REF: https://make.wordpress.org/core/2021/02/22/improved-https-detection-and-migration-in-wordpress-5-7/
		remove_filter( 'the_content', 'wp_replace_insecure_home_url' );
		remove_filter( 'the_excerpt', 'wp_replace_insecure_home_url' );
		remove_filter( 'widget_text_content', 'wp_replace_insecure_home_url' );
		remove_filter( 'wp_get_custom_css', 'wp_replace_insecure_home_url' );

		foreach ( [
			'the_content',
			'the_title',
			'wp_title',
			'document_title',
			] as $filter )
				remove_filter( $filter, 'capital_P_dangit', 11 );

		foreach ( [
			'publish_post',
			'publish_page',
			'wp_ajax_save-widget',
			'wp_ajax_widgets-order',
			'customize_save_after',
			'rest_after_save_widget',
			'rest_delete_widget',
			'rest_save_sidebar',
			] as $action )
				remove_action( $action, '_delete_option_fresh_site', 0 );
	}

	// 2024-08-21: `6.7-alpha-58576-src`
	private static function getjQueryVersions()
	{
		return [
			'core'    => [ '3.7.1', '3.7.1' ],
			'slim'    => [ NULL,    '3.7.1' ],
			'migrate' => [ '3.4.1', '3.5.2' ],
		];
	}

	public function wp_resource_hints(  $urls, $relation_type )
	{
		return in_array( $relation_type, [ 'preconnect', 'dns-prefetch' ], TRUE )
			? array_merge( $urls, [ 'href' =>'https://code.jquery.com/', 'crossorigin' ] )
			: $urls;
	}

	public function wp_default_scripts_admin( &$scripts )
	{
		if ( empty( $scripts->registered['jquery'] ) )
			return;

		if ( defined( 'GNETWORK_DISABLE_JQUERY_MIGRATE' ) && ! GNETWORK_DISABLE_JQUERY_MIGRATE )
			return;

		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, [ 'jquery-migrate' ] );
	}

	public function wp_default_scripts_front( &$scripts )
	{
		$variant  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$disable  = ( ! defined( 'GNETWORK_DISABLE_JQUERY_MIGRATE' ) || GNETWORK_DISABLE_JQUERY_MIGRATE );
		$bottom   = $this->options['jquery_bottom'] ? 1 : NULL;
		$remote   = $this->options['jquery_cdn'] ? 1 : 0;
		$latest   = $this->options['jquery_latest'] ? 1 : 0;
		$versions = self::getjQueryVersions();

		$core = $remote
			? sprintf( 'https://code.jquery.com/jquery-%s%s.js', $versions['core'][$latest], $variant )
			: GNETWORK_URL.'assets/js/vendor/jquery'.$variant.'.js';

		$scripts->remove( [ 'jquery', 'jquery-core', 'jquery-migrate' ] );
		$scripts->add( 'jquery-core', $core, FALSE, ( $remote ? NULL : $versions['core'][$latest] ), $bottom );

		$deps = [ 'jquery-core' ];

		if ( ! $disable ) {

			$migrate = $remote
				? sprintf( 'https://code.jquery.com/jquery-migrate-%s.min.js', $versions['migrate'][$latest] )
				: GNETWORK_URL.'assets/js/vendor/jquery-migrate'.$variant.'.js';

			$scripts->add( 'jquery-migrate', $migrate, FALSE, $versions['migrate'][$latest], $bottom );
			$deps[] = 'jquery-migrate';
		}

		$scripts->add( 'jquery', FALSE, $deps, $versions['core'][$latest], $bottom );
	}

	public function settings_sidebox( $sub, $uri )
	{
		echo self::summaryjQuery();
	}

	public static function summaryjQuery( $caption = FALSE )
	{
		$versions = self::getjQueryVersions();

		return Core\HTML::tableCode( [
			_x( 'Latest jQuery Stable', 'Modules: Optimize: jQuery', 'gnetwork' )     => $versions['core'][1],
			_x( 'Latest jQuery Migrate', 'Modules: Optimize: jQuery', 'gnetwork' )    => $versions['migrate'][1],
			_x( 'WordPress jQuery Stable', 'Modules: Optimize: jQuery', 'gnetwork' )  => $versions['core'][0],
			_x( 'WordPress jQuery Migrate', 'Modules: Optimize: jQuery', 'gnetwork' ) => $versions['migrate'][0],
		], FALSE, $caption );
	}
}

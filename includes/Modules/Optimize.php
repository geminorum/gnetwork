<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;

class Optimize extends gNetwork\Module
{
	// NOTE: general/network optimizations

	protected $key = 'optimize';

	protected function setup_actions()
	{
		$this->action( 'plugins_loaded', 0, 1 );
	}

	public function plugins_loaded()
	{
		$this->_cleanup_options();
		$this->_cleanup_hooks();
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
}


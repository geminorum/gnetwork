<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress\Media as WPMedia;
use geminorum\gNetwork\WordPress\Taxonomy as WPTaxonomy;

class Rest extends gNetwork\Module
{

	protected $key     = 'rest';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( $this->options['disable_rest_api'] ) {

			$this->filter( 'rest_authentication_errors', 1, 999 );

		} else {

			$this->action( 'rest_api_init', 0, 20 );
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Rest', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'disable_rest_api'    => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'disable_rest_api',
					'type'        => 'disabled',
					'title'       => _x( 'Rest API', 'Modules: Rest: Settings', 'gnetwork' ),
					'description' => _x( 'Whether REST API services are enabled on this site.', 'Modules: Rest: Settings', 'gnetwork' ),
				],
			],
		];
	}

	private function get_supported_posttypes( $posttypes = NULL )
	{
		$excluded = [
			'attachment',
			'inbound_message',
			'amp_validated_url',
			'guest-author',      // Co-Authors Plus
			'bp-email',
			'wp_block',
			'shop_order',        // WooCommerce
			'shop_coupon',       // WooCommerce
		];

		if ( is_null( $posttypes ) )
			$posttypes = get_post_types( [ 'show_in_rest' => TRUE ] );

		return array_diff_key( $posttypes, array_flip( $excluded ) );
	}

	public function rest_authentication_errors( $null )
	{
		return new Error( 'rest_disabled', 'The REST API is disabled on this site.', [ 'status' => 503 ] );
	}

	public function rest_api_init()
	{
		$this->_init_terms_rendered();
		$this->_init_thumbnail_data();
	}

	private function _init_terms_rendered()
	{
		$posttypes = $this->get_supported_posttypes();
		$posttypes = $this->filters( 'terms_rendered_posttypes', $posttypes );

		register_rest_field( $posttypes, 'terms_rendered', [
			'get_callback' => [ $this, 'terms_rendered_get_callback' ],
		] );
	}

	private function _init_thumbnail_data()
	{
		$posttypes = get_post_types_by_support( 'thumbnail' );
		$posttypes = $this->get_supported_posttypes( $posttypes );
		$posttypes = $this->filters( 'thumbnail_data_posttypes', $posttypes );

		register_rest_field( $posttypes, 'thumbnail_data', [
			'get_callback' => [ $this, 'thumbnail_data_get_callback' ],
		] );
	}

	public function terms_rendered_get_callback( $post, $attr, $request, $object_type )
	{
		$rendered = [];
		$ignored  = $this->filters( 'terms_rendered_ignored', [ 'post_format' ], $post, $object_type );

		foreach ( get_object_taxonomies( $object_type, 'objects' ) as $taxonomy ) {

			// @REF: `is_taxonomy_viewable()`
			if ( ! $taxonomy->publicly_queryable )
				continue;

			if ( in_array( $taxonomy->name, $ignored, TRUE ) )
				continue;

			$html = Utilities::getJoined( WPTaxonomy::getTheTermList( $taxonomy->name, $post['id'] ) );

			$rendered[$taxonomy->rest_base] = $this->filters( 'terms_rendered_html', $html, $taxonomy, $post, $object_type );
		}

		return $rendered;
	}

	public function thumbnail_data_get_callback( $post, $attr, $request, $object_type )
	{
		return WPMedia::prepAttachmentData( $this->filters( 'thumbnail_id', get_post_meta( $post['id'], '_thumbnail_id', TRUE ), $post ) );
	}
}

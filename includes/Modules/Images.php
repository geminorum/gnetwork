<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;

class Images extends gNetwork\Module
{

	protected $key     = 'images';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( '' !== $this->options['bigsize_threshold'] )
			$this->filter( 'big_image_size_threshold', 4, 8 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Images', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'bigsize_threshold' => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'bigsize_threshold',
					'type'        => 'text',
					'title'       => _x( 'Size Threshold', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => _x( 'Filters the “BIG image” threshold value in pixels. `0` for disabling the scaling.', 'Modules: Images: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/' ),
					'field_class' => [ 'small-text', 'code' ],
					'placeholder' => '2560',
				],
			],
		];
	}

	public function big_image_size_threshold( $threshold, $imagesize, $file, $attachment_id )
	{
		if ( '0' === $this->options['bigsize_threshold'] )
			return FALSE;

		return intval( $this->options['bigsize_threshold'] ) ?: $threshold;
	}
}

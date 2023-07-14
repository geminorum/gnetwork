<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;

class Images extends gNetwork\Module
{

	protected $key     = 'images';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		$this->filter( 'wp_editor_set_quality', 2, 12 );

		if ( ! empty( $this->options['output_format'] ) )
			$this->filter( 'image_editor_output_format', 3, 12 );

		if ( $this->options['skip_exifmeta'] )
			$this->filter( 'wp_update_attachment_metadata', 2, 12 );

		if ( $this->options['edit_thumb_sep'] )
			$this->filter_true( 'image_edit_thumbnails_separately' );

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
			'skip_exifmeta'     => '1',
			'edit_thumb_sep'    => '0',
			'bigsize_threshold' => '',
			'quality_jpeg'      => 60,
			'quality_webp'      => 75,
			'output_format'     => [],
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'skip_exifmeta',
					'title'       => _x( 'Strip EXIF', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => _x( 'Skips storing unused EXIF metadata for image attachments.', 'Modules: Images: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'edit_thumb_sep',
					'title'       => _x( 'Edit Thumbnails Separately', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => _x( 'Shows the settings in the Image Editor that allow selecting to edit only the thumbnail of an image.', 'Modules: Images: Settings', 'gnetwork' ),
				],
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
			'_quality' => [
				[
					'field'       => 'quality_jpeg',
					'type'        => 'number',
					'title'       => _x( 'JPEG Quality', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => _x( 'Sets the compression quality setting used for JPEG images.', 'Modules: Images: Settings', 'gnetwork' ),
					'placeholder' => 90,
					'default'     => 60,
					'min_attr'    => 1,
					'max_attr'    => 100,
				],
				[
					'field'       => 'quality_webp',
					'type'        => 'number',
					'title'       => _x( 'WebP Quality', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => _x( 'Sets the compression quality setting used for WebP images.', 'Modules: Images: Settings', 'gnetwork' ),
					'placeholder' => 90,
					'default'     => 75,
					'min_attr'    => 1,
					'max_attr'    => 100,
				],
			],
			'_format' => [
				[
					'field'       => 'output_format',
					'type'        => 'checkboxes',
					'title'       => _x( 'Output Format', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => _x( 'Sets WebP as default format for selected image sub-sizes.', 'Modules: Images: Settings', 'gnetwork' ),
					// 'after'       => Settings::fieldAfterIcon( 'https://caniuse.com/webp' ),
					'values'      => [
						'jpeg'   => 'JPEG',
						'gif'    => 'GIF',
						'png'    => 'PNG',
						'bmp'    => 'BMP',
						'tiff'   => 'TIFF',
						'avif'   => 'AVIF',
						'jpegxl' => 'JPEG XL',
					],
				],
			],
		];
	}

	public function wp_editor_set_quality( $quality, $mime_type )
	{
		switch ( $mime_type ) {
			case 'image/jpeg': return $this->options['quality_jpeg'] ?: $quality;
			case 'image/webp': return $this->options['quality_webp'] ?: $quality;
		}

		return $quality;
	}

	// @REF: https://core.trac.wordpress.org/ticket/52867
	// @REF: https://github.com/adamsilverstein/modern-images-wp
	public function image_editor_output_format( $map, $filename, $mime_type )
	{
		foreach ( $this->options['output_format'] as $format )
			$map['image/'.$format] = 'image/webp';

		return $map;
	}

	public function wp_update_attachment_metadata( $data, $post_id )
	{
		unset( $data['image_meta'] );
		return $data;
	}

	public function big_image_size_threshold( $threshold, $imagesize, $file, $attachment_id )
	{
		if ( '0' === $this->options['bigsize_threshold'] )
			return FALSE;

		return intval( $this->options['bigsize_threshold'] ) ?: $threshold;
	}
}

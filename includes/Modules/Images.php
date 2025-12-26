<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\WordPress;

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

		if ( is_admin() )
			return;

		if ( $this->options['default_css_class'] )
			$this->filter( 'the_content', 1, 21, 'css_class' );

		if ( 'ignore_all' !== $this->options['content_paragraps'] )
			$this->filter( 'the_content', 1, 22, 'paragraps' );
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
			'default_css_class' => 'img-fluid',
			'content_paragraps' => 'replace_with_figure',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'skip_exifmeta',
					'title'       => _x( 'Strip EXIF', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => sprintf(
						/* translators: `%s`: `EXIF` placeholder */
						_x( 'Skips storing unused %s metadata for image attachments.', 'Modules: Images: Settings', 'gnetwork' ),
						Core\HTML::code( 'EXIF' )
					),
					'default' => '1',
				],
			],
			'_content' => [
				[
					'field'       => 'default_css_class',
					'type'        => 'text',
					'title'       => _x( 'Default CSS Class', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => sprintf(
						/* translators: `%s`: CSS placeholder */
						_x( 'Sets the default %s class for images without the attribute.', 'Modules: Images: Settings', 'gnetwork' ),
						Core\HTML::code( 'CSS' )
					),
					'field_class' => [ 'regular-text', 'code-text' ],
					'placeholder' => 'img-fluid',
				],
				[
					'field'       => 'content_paragraps',
					'type'        => 'radio',
					'title'       => _x( 'Content Paragraps', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => _x( 'Handles images within paragraph tags on the content.', 'Modules: Images: Settings', 'gnetwork' ),
					'default'     => 'replace_with_figure',
					'values'      => [
						'ignore_all'          => _x( 'Ignores the paragraps and leave them as are!', 'Modules: Images: Settings', 'gnetwork' ),
						'replace_with_figure' => sprintf(
							/* translators: `%s`: HTML tag placeholder */
							_x( 'Replaces the paragraps with %s tags.', 'Modules: Images: Settings', 'gnetwork' ),
							Core\HTML::code( 'figure' )
						),
						'replace_with_div' => sprintf(
							/* translators: `%s`: HTML tag placeholder */
							_x( 'Replaces the paragraps with %s tags.', 'Modules: Images: Settings', 'gnetwork' ),
							Core\HTML::code( 'div' )
						),
						'remove_surrounding' => _x( 'Removes the surrounding paragraps and keep the images.', 'Modules: Images: Settings', 'gnetwork' ),
					],
				],
			],
			'_editor' => [
				[
					'field'       => 'edit_thumb_sep',
					'title'       => _x( 'Edit Thumbnails Separately', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => _x( 'Shows the settings in the Image Editor that allow selecting to edit only the thumbnail of an image.', 'Modules: Images: Settings', 'gnetwork' ),
				],
			],
			'_size_and_quality' => [
				[
					'field'       => 'bigsize_threshold',
					'type'        => 'text',
					'title'       => _x( 'Size Threshold', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => sprintf(
						/* translators: `%s`: zero placeholder */
						_x( 'Filters the “BIG image” threshold value in pixels. %s for disabling the scaling.', 'Modules: Images: Settings', 'gnetwork' ),
						Core\HTML::code( '0' )
					),
					'after'       => Settings::fieldAfterIcon( 'https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/' ),
					'field_class' => [ 'small-text', 'code-text' ],
					'placeholder' => '2560',
				],
				[
					'field'       => 'quality_jpeg',
					'type'        => 'number',
					'title'       => _x( 'JPEG Quality', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => sprintf(
						/* translators: `%s`: image type placeholder */
						_x( 'Sets the compression quality setting used for %s images.', 'Modules: Images: Settings', 'gnetwork' ),
						Core\HTML::code( 'JPEG' )
					),
					'placeholder' => 90,
					'default'     => 60,
					'min_attr'    => 1,
					'max_attr'    => 100,
				],
				[
					'field'       => 'quality_webp',
					'type'        => 'number',
					'title'       => _x( 'WebP Quality', 'Modules: Images: Settings', 'gnetwork' ),
					'description' => sprintf(
						/* translators: `%s`: image type placeholder */
						_x( 'Sets the compression quality setting used for %s images.', 'Modules: Images: Settings', 'gnetwork' ),
						Core\HTML::code( 'WebP' )
					),
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
					'description' => sprintf(
						/* translators: `%s`: `WebP` placeholder */
						_x( 'Sets %s as default format for selected image sub-sizes.', 'Modules: Images: Settings', 'gnetwork' ),
						Core\HTML::code( 'WebP' )
					),
					// 'after'  => Settings::fieldAfterIcon( 'https://caniuse.com/webp' ), // `checkboxes` type does not support `after` styles yet!
					'dir'    => 'ltr',
					'values' => [
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

	public function the_content_css_class( $content )
	{
		return Core\Text::addImageClass(
			$content,
			$this->options['default_css_class']
		);
	}

	public function the_content_paragraps( $content )
	{
		switch ( $this->options['content_paragraps'] ) {
			case 'replace_with_figure': $content = Core\Text::replaceImageP( $content, 'figure' ); break;
			case 'replace_with_div'   : $content = Core\Text::replaceImageP( $content, 'div' );    break;
			case 'remove_surrounding' : $content = Core\Text::replaceImageP( $content, FALSE );    break;
		}

		return $content;
	}
}

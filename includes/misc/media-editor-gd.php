<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Image_Editor_GD extends \WP_Image_Editor_GD
{

	public function generate_filename( $suffix = NULL, $dest_path = NULL, $extension = NULL )
	{
		if ( is_null( $dest_path ) )
			$dest_path = Media::getSizesDestPath( $this->file );

		return parent::generate_filename( $suffix, $dest_path, $extension );
	}
}

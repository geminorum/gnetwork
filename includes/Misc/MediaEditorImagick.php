<?php namespace geminorum\gNetwork\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Modules\Media;

class MediaEditorImagick extends \WP_Image_Editor_Imagick
{

	public function generate_filename( $suffix = NULL, $dest_path = NULL, $extension = NULL )
	{
		if ( is_null( $dest_path ) )
			$dest_path = Media::getSizesDestPath( $this->file );

		return parent::generate_filename( $suffix, $dest_path, $extension );
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetwork_Image_Editor_Imagick extends WP_Image_Editor_Imagick
{
	
	public function generate_filename( $suffix = NULL, $dest_path = NULL, $extension = NULL )
	{
		if ( is_null( $dest_path ) )
			$dest_path = gNetworkMedia::getSizesDestPath( $this->file );

		return parent::generate_filename( $suffix, $dest_path, $extension );
	}
}

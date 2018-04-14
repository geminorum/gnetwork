<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Image extends Base
{

	// @SOURCE: https://wordpress.org/plugins/image-rotation-fixer/
	public static function rotation( $source )
	{
		$source      = str_replace( get_bloginfo( 'url' ), ABSPATH, $source );
		$sourceFile  = explode( '/', $source );
		$filename    = $sourceFile[5];
		$destination = $source;

		$size = getimagesize( $source );

		$width  = $size[0];
		$height = $size[1];

		$sourceImage      = imagecreatefromjpeg( $source );
		$destinationImage = imagecreatetruecolor( $width, $height );

		imagecopyresampled( $destinationImage, $sourceImage, 0, 0, 0, 0, $width, $height, $width, $height );

		$exif = exif_read_data( $source );

		$ort = $exif['Orientation'];

		switch ( $ort ) {

			case 2:
				self::flip( $dimg );

			break;
			case 3:

				$destinationImage = imagerotate( $destinationImage, 180, -1 );

			break;
			case 4:

				self::flip( $dimg );

			break;
			case 5:

				self::flip( $destinationImage );
				$destinationImage = imagerotate( $destinationImage, -90, -1 );

			break;
			case 6:

				$destinationImage = imagerotate( $destinationImage, -90, -1 );

			break;
			case 7:

				self::flip( $destinationImage );
				$destinationImage = imagerotate( $destinationImage, -90, -1 );

			break;
			case 8:

				$destinationImage = imagerotate( $destinationImage, 90, -1 );
		}

		return imagejpeg( $destinationImage, $destination, 100 );
	}

	public static function flip( &$image )
	{
		$x = $y = 0;
		$height = $width = NULL;

		if ( $width < 1 )
			$width  = imagesx( $image );

		if ( $height < 1 )
			$height = imagesy( $image );

		if ( function_exists( 'imageistruecolor' ) && imageistruecolor( $image ) )
			$tmp = imagecreatetruecolor( 1, $height );
		else
			$tmp = imagecreate( 1, $height );

		$x2 = $x + $width - 1;

		for ( $i = (int) floor( ( $width - 1 ) / 2 ); $i >= 0; $i-- ) {
			imagecopy( $tmp, $image, 0, 0, $x2 - $i, $y, 1, $height );
			imagecopy( $image, $image, $x2 - $i, $y, $x + $i, $y, 1, $height );
			imagecopy( $image, $tmp, $x + $i,  $y, 0, 0, 1, $height );
		}

		imagedestroy( $tmp );

		return TRUE;
	}
}

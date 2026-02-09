<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Image extends Base
{

	/**
	 * Gets the size of an image given the location path or URL.
	 * NOTE: wrapper for `wp_getimagesize()` @since WP 5.7.0
	 *
	 * @param string $filename
	 * @param array $image_info
	 * @return array|false
	 */
	public static function size( $filename, &$image_info = NULL )
	{
		if ( function_exists( 'wp_getimagesize' ) )
			return wp_getimagesize( $filename, $image_info );

		return getimagesize( $filename, $image_info );
	}

	/**
	 * Fixes the rotation of JPEG images using EXIF extension.
	 * Adopted from: Image Rotation Fixer 1.0 By `Mert Yazıcıoğlu`
	 * @source https://github.com/merty/image-rotation-fixer
	 *
	 * @param string $filepath
	 * @return bool
	 */
	public static function rotationJPEG( $filepath )
	{
		if ( empty( $filepath ) )
			return FALSE;

		if ( ! function_exists( 'exif_read_data' ) )
			return FALSE;

		if ( ! $exif = exif_read_data( $filepath ) )
			return FALSE;

		if ( empty( $exif['Orientation'] ) )
			return FALSE;

		if ( ! $size = self::size( $filepath ) )
			return FALSE;

		$width  = $size[0];
		$height = $size[1];

		$source = imagecreatefromjpeg( $filepath );
		$dest   = imagecreatetruecolor( $width, $height );

		imagecopyresampled(
			$dest,
			$source,
			0,
			0,
			0,
			0,
			$width,
			$height,
			$width,
			$height
		);

		switch ( $exif['Orientation'] ) {

			case 2:

				self::_flipJPEG( $dimg );
				break;

			case 3:

				$dest = imagerotate( $dest, 180, -1 );
				break;

			case 4:

				self::_flipJPEG( $dimg );
				break;

			case 5:

				self::_flipJPEG( $dest );
				$dest = imagerotate( $dest, -90, -1 );
				break;

			case 6:

				$dest = imagerotate( $dest, -90, -1 );
				break;

			case 7:

				self::_flipJPEG( $dest );
				$dest = imagerotate( $dest, -90, -1 );
				break;

			case 8:

				$dest = imagerotate( $dest, 90, -1 );
		}

		return imagejpeg( $dest, $filepath, 100 );
	}

	private static function _flipJPEG( &$image )
	{
		$x      = $y     = 0;
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

		for ( $i = (int) floor( ( $width - 1 ) / 2); $i >= 0; $i-- ) {
			imagecopy( $tmp, $image, 0, 0, $x2 - $i, $y, 1, $height );
			imagecopy( $image, $image, $x2 - $i, $y, $x + $i, $y, 1, $height );
			imagecopy( $image, $tmp, $x + $i,  $y, 0, 0, 1, $height );
		}

		if ( PHP_VERSION_ID < 80000 )
			imagedestroy( $tmp );

		return TRUE;
	}

	public static function imageWatermark( $image_path, $watermark_path, $new_image_path = '', $margin_right = 0, $margin_bottom = 10 )
	{
		if ( FALSE === ( $image = imagecreatefromjpeg( $image_path ) ) )
			$image = @imagecreatefrompng( $image_path );

		if ( FALSE === $image )
			return FALSE;

		if ( FALSE === ( $wmark = @imagecreatefrompng( $watermark_path ) ) )
			$wmark = @imagecreatefromjpeg( $image_path );

		if ( FALSE === $wmark )
			return FALSE;

		// $sx = imagesx($wmark);
		// $sy = imagesy($wmark);

		imagecopy(
			$image,
			$wmark,
			//imagesx($image) - $sx - $margin_right,
			//imagesy($image) - $sy - $margin_bottom,
			$margin_right,
			$margin_bottom,
			0,
			0,
			imagesx( $wmark ),
			imagesy( $wmark )
		);

		if ( ! empty( $new_image_path ) )
			$created = imagejpeg( $image, $new_image_path );

		else
			$created = imagejpeg( $image, $image_path );

		if ( PHP_VERSION_ID < 80000 )
			imagedestroy( $image );

		return $created;
	}

	public static function textWatermark( $image_path, $wm_text, $fontcolor = NULL, $fontsize = NULL, $xposition = NULL, $yposition = NULL )
	{
		if ( FALSE === ( $image = imagecreatefromjpeg( $image_path ) ) )
			$image = @imagecreatefrompng( $image_path );

		if ( FALSE === $image )
			return FALSE;

		$fontcolor = $fontcolor ?? [ 255, 255, 255 ];

		if ( ! is_array( $fontcolor ) )
			$fontcolor = Color::hex2rgb( $fontcolor );

		// $_fontcolor = imagecolorallocate( $image, 255, 255, 255 );

		imagestring(
			$image,
			$fontsize ?? 5,
			$xposition ?? 10,
			$yposition ?? 10,
			$wm_text,
			imagecolorallocate( $image, ...$fontcolor )
		);

		$created = imagejpeg( $image, $image_path );

		if ( PHP_VERSION_ID < 80000 )
			imagedestroy( $image );

		return $created;
	}

	public static function remoteSize( $url )
	{
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );

		$data = curl_exec( $ch );

		$http_status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$curl_errno  = curl_errno( $ch );

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
			curl_close( $ch );

		if ( $http_status != 200 ) {
			echo 'http status[' . $http_status . '] errno [' . $curl_errno . ']';
			return [0,0];
		}

		$image = imagecreatefromstring( $data );
		$dims = [ imagesx( $image ), imagesy( $image ) ];

		if ( PHP_VERSION_ID < 80000 )
			imagedestroy($image);

		return $dims;
	}
}

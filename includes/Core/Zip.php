<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Zip extends Base
{

	/**
	 * Zip a folder (include itself).
	 * Usage: `Zip::zipDir( '/path/to/sourceDir', '/path/to/out.zip' );`
	 *
	 * @REF: http://php.net/manual/en/class.ziparchive.php#110719
	 *
	 * @param string $sourcePath Path of directory to be zip.
	 * @param string $outZipPath Path of output zip file.
	*/
	public static function zipDir( $sourcePath, $outZipPath )
	{
		$pathinfo   = pathinfo( $sourcePath );
		$parentPath = $pathinfo['dirname'];
		$dirName    = $pathinfo['basename'];

		$z = new \ZipArchive();

		$z->open( $outZipPath, \ZIPARCHIVE::CREATE );
		$z->addEmptyDir( $dirName );

		self::folderToZip( $sourcePath, $z, strlen( "$parentPath/" ) );

		$z->close();
	}

	/**
	 * Add files and sub-directories in a folder to zip file.
	 *
	 * @param string $folder
	 * @param ZipArchive $zipFile
	 * @param int $exclusiveLength Number of text to be exclusive from the filepath.
	*/
	private static function folderToZip( $folder, &$zipFile, $exclusiveLength )
	{
		$handle = opendir( $folder );

		while ( FALSE !== $f = readdir( $handle ) ) {

			if ( $f != '.' && $f != '..' ) {

				$filePath = "$folder/$f";

				// Removes prefix from file path before add to zip.
				$localPath = substr( $filePath, $exclusiveLength );

				if ( is_file( $filePath ) ) {

					$zipFile->addFile( $filePath, $localPath );

				} else if ( is_dir( $filePath ) ) {

					// add sub-directory
					$zipFile->addEmptyDir( $localPath );

					self::folderToZip( $filePath, $zipFile, $exclusiveLength );
				}
			}
		}

		closedir( $handle );
	}

	// `ZipArchive` status as a human readable string
	// @REF: http://php.net/manual/en/class.ziparchive.php#108601
	public static function statusString( $status )
	{
		switch ( (int) $status ) {

			case \ZipArchive::ER_OK          : return 'N No error';
			case \ZipArchive::ER_MULTIDISK   : return 'N Multi-disk zip archives not supported';
			case \ZipArchive::ER_RENAME      : return 'S Renaming temporary file failed';
			case \ZipArchive::ER_CLOSE       : return 'S Closing zip archive failed';
			case \ZipArchive::ER_SEEK        : return 'S Seek error';
			case \ZipArchive::ER_READ        : return 'S Read error';
			case \ZipArchive::ER_WRITE       : return 'S Write error';
			case \ZipArchive::ER_CRC         : return 'N CRC error';
			case \ZipArchive::ER_ZIPCLOSED   : return 'N Containing zip archive was closed';
			case \ZipArchive::ER_NOENT       : return 'N No such file';
			case \ZipArchive::ER_EXISTS      : return 'N File already exists';
			case \ZipArchive::ER_OPEN        : return 'S Can\'t open file';
			case \ZipArchive::ER_TMPOPEN     : return 'S Failure to create temporary file';
			case \ZipArchive::ER_ZLIB        : return 'Z Zlib error';
			case \ZipArchive::ER_MEMORY      : return 'N Malloc failure';
			case \ZipArchive::ER_CHANGED     : return 'N Entry has been changed';
			case \ZipArchive::ER_COMPNOTSUPP : return 'N Compression method not supported';
			case \ZipArchive::ER_EOF         : return 'N Premature EOF';
			case \ZipArchive::ER_INVAL       : return 'N Invalid argument';
			case \ZipArchive::ER_NOZIP       : return 'N Not a zip archive';
			case \ZipArchive::ER_INTERNAL    : return 'N Internal error';
			case \ZipArchive::ER_INCONS      : return 'N Zip archive inconsistent';
			case \ZipArchive::ER_REMOVE      : return 'S Can\'t remove file';
			case \ZipArchive::ER_DELETED     : return 'N Entry has been deleted';

			default: return sprintf( 'Unknown status %s', $status );
		}
	}
}

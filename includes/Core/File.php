<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class File extends Base
{

	/**
	 * Checks whether a file or directory exists.
	 * NOTE: wraps `file_exists()` and uses current install absolute path.
	 * @SEE: `clearstatcache()`
	 *
	 * @param string $path
	 * @param string $base
	 * @return bool
	 */
	public static function exists( $path, $base = ABSPATH )
	{
		if ( empty( $path ) )
			return FALSE;

		return @file_exists( $base.$path );
	}

	/**
	 * Tells whether a file exists and is readable.
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function readable( $path )
	{
		if ( empty( $path ) )
			return FALSE;

		return @is_readable( $path );
	}

	/**
	 * Tells whether the filename is writable.
	 * NOTE: wraps the `wp_is_writable()` with fallback to `is_writable()`
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function writable( $path )
	{
		if ( empty( $path ) )
			return FALSE;

		if ( function_exists( 'wp_is_writable' ) )
			return wp_is_writable( $path );

		return @is_writable( $path );
	}

	/**
	 * Retrieves the filetype from the filename.
	 * NOTE: wrapper for `wp_check_filetype()`
	 *
	 * @param string $filename
	 * @param array $mimes
	 * @return array
	 */
	public static function type( $filename, $mimes = NULL )
	{
		return wp_check_filetype( $filename, $mimes );
	}

	/**
	 * Normalizes a filesystem path.
	 *
	 * On windows systems, replaces backslashes with forward slashes
	 * and forces upper-case drive letters.
	 * Allows for two leading slashes for Windows network shares, but
	 * ensures that all other duplicate slashes are reduced to a single.
	 *
	 * @source: `wp_normalize_path()`
	 *
	 * @param string $path
	 * @return string
	 */
	public static function normalize( $path )
	{
		if ( empty( $path ) )
			return '';

		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );

		if ( ':' === substr( $path, 1, 1 ) )
			$path = ucfirst( $path );

		return $path;
	}

	/**
	 * Returns trailing name component of path.
	 * I18N friendly version of `basename()`
	 * if the filename ends in suffix this will also be cut off
	 * @source `wp_basename()`
	 *
	 * @param string $path
	 * @param string $suffix
	 * @return string
	 */
	public static function basename( $path, $suffix = '' )
	{
		if ( empty( $path ) )
			return '';

		return urldecode( basename( str_replace( [ '%2F', '%5C' ], '/', urlencode( $path ) ), $suffix ) );
	}

	/**
	 * Retrieves filename from given path or URL.
	 *
	 * @param string $path
	 * @param array $mimes
	 * @return string
	 */
	public static function filename( $path, $mimes = NULL )
	{
		if ( empty( $path ) )
			return '';

		$type = self::type( self::basename( $path ), $mimes ?? wp_get_mime_types() );
		return self::basename( $path, empty( $type['ext'] ) ? '' : ('.'. $type['ext'] ) );
	}

	// @SOURCE: `wp_tempnam()`
	public static function tempName( $name = '', $dir = '' )
	{
		if ( empty( $dir ) )
			$dir = get_temp_dir();

		if ( empty( $name ) || in_array( $name, [ '.', '/', '\\' ], TRUE ) )
			$name = uniqid();

		// use the basename of the given file without the extension
		// as the name for the temporary directory
		$temp = preg_replace( '|\.[^.]*$|', '', basename( $name ) );

		// If the folder is false, use its parent directory name instead.
		if ( ! $temp )
			return self::tempName( dirname( $name ), $dir );

		// Suffix some random data to avoid filename conflicts.
		$temp.= '-'.wp_generate_password( 6, FALSE );
		$temp.= '.tmp';
		$temp = $dir.wp_unique_filename( $dir, $temp );

		$fp = @fopen( $temp, 'x' );

		if ( ! $fp && self::writable( $dir ) && file_exists( $temp ) )
			return self::tempName( $name, $dir );

		if ( $fp )
			fclose( $fp );

		return $temp;
	}

	/**
	 * Joins two filesystem paths together.
	 * @source `path_join()`
	 *
	 * @param string $base
	 * @param string $path
	 * @return string
	 */
	public static function join( $base, $path )
	{
		return self::isAbsolute( $path )
			? $path
			: rtrim( $base, '/' ).'/'.ltrim( $path, '/' );
	}

	/**
	 * Tests if a given filesystem path is absolute.
	 * For example, '/foo/bar', or 'c:\windows'.
	 * @source `path_is_absolute()`
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function isAbsolute( $path )
	{
		// Check to see if the path is a stream and check to see if its an actual
		// path or file as `realpath()` does not support stream wrappers.
		if ( wp_is_stream( $path ) && ( is_dir( $path ) || is_file( $path ) ) )
			return TRUE;

		// This is definitive if true but fails if $path does not exist or contains
		// a symbolic link.
		if ( realpath( $path ) === $path )
			return TRUE;

		if ( strlen( $path ) === 0 || '.' === $path[0] )
			return FALSE;

		// Windows allows absolute paths like this.
		if ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) )
			return TRUE;

		// Normalized Windows paths for local filesystem and network shares (forward slashes).
		if ( preg_match( '#(^[a-zA-Z]+:/|^//[\w!@\#\$%\^\(\)\-\'{}\.~]{1,15})#', $path ) )
			return TRUE;

		// A path starting with / or \ is absolute; anything else is relative.
		return ( '/' === $path[0] || '\\' === $path[0] );
	}

	// http://stackoverflow.com/a/4994188
	// core has `sanitize_file_name()` but with certain mime types
	public static function escFilename( $path )
	{
		if ( empty( $path ) )
			return '';

		// everything to lower and no spaces begin or end
		$path = strtolower( trim( $path ) );

		// adding - for spaces and union characters
		$path = str_replace( [ ' ', '&', '\r\n', '\n', '+', ',' ], '-', $path );

		// delete and replace rest of special chars
		$path = preg_replace( [ '/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/' ], [ '', '-', '' ], $path );

		return $path;
	}

	// ORIGINALLY BASED ON: Secure Folder wp-content/uploads v1.2
	// BY: Daniel Satria : http://ruanglaba.com
	// puts index.html on given folder and subs
	public static function putIndexHTML( $base, $index )
	{
		copy( $index, $base.'/index.html' );

		if ( $dir = opendir( $base ) )
			while ( FALSE !== ( $file = readdir( $dir ) ) )
				if ( is_dir( $base.'/'.$file ) && $file != '.' && $file != '..' )
					self::putIndexHTML( $base.'/'. $file, $index );

		closedir( $dir );
	}

	/**
	 * Puts `.donotbackup` on a given folder.
	 *
	 * @param string $path
	 * @param bool $check_folder
	 * @param bool $force_overwrite
	 * @return bool
	 */
	public static function putDoNotBackup( $path, $check_folder = TRUE, $force_overwrite = FALSE )
	{
		if ( ! $force_overwrite && @file_exists( $path.'/.donotbackup' ) )
			return TRUE;

		$content = 'This directory (and its sub-directories) will be excluded from the backup.';

		return self::putContents( '.donotbackup', $content, $path, FALSE, $check_folder );
	}

	/**
	 * Puts `.htaccess` deny from all on a given folder.
	 *
	 * @param string $path
	 * @param bool $check_folder
	 * @param bool $force_overwrite
	 * @return bool
	 */
	public static function putHTAccessDeny( $path, $check_folder = TRUE, $force_overwrite = FALSE )
	{
		if ( ! $force_overwrite && @file_exists( $path.'/.htaccess' ) )
			return TRUE;

		$content = '<Files ~ ".*\..*">'.PHP_EOL.
				'<IfModule mod_access.c>'.PHP_EOL.
					'Deny from all'.PHP_EOL.
				'</IfModule>'.PHP_EOL.
				'<IfModule !mod_access_compat>'.PHP_EOL.
					'<IfModule mod_authz_host.c>'.PHP_EOL.
						'Deny from all'.PHP_EOL.
					'</IfModule>'.PHP_EOL.
				'</IfModule>'.PHP_EOL.
				'<IfModule mod_access_compat>'.PHP_EOL.
					'Deny from all'.PHP_EOL.
				'</IfModule>'.PHP_EOL.
			'</Files>';

		return self::putContents( '.htaccess', $content, $path, FALSE, $check_folder );
	}

	/**
	 * Reads entire file into a string.
	 * NOTE: wrapper for `file_get_contents()`
	 *
	 * TODO: use `$wp_filesystem`
	 * @REF: https://github.com/markjaquith/feedback/issues/33
	 * @REF: `$wp_filesystem->get_contents()`
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function getContents( $filename )
	{
		if ( empty( $filename ) )
			return '';

		return @file_get_contents( $filename );
	}

	// wrapper for file_put_contents()
	public static function putContents( $filename, $contents, $path = NULL, $append = TRUE, $check_folder = FALSE )
	{
		$dir = FALSE;

		if ( is_null( $path ) ) {

			$dir = WP_CONTENT_DIR;

		} else if ( $check_folder ) {

			$dir = wp_mkdir_p( $path );

			if ( TRUE === $dir )
				$dir = $path;

		} else if ( self::writable( $path ) ) {

			$dir = $path;
		}

		if ( ! $dir )
			return $dir;

		return file_put_contents(
			self::join( $dir, $filename ),
			$contents.PHP_EOL,
			$append ? FILE_APPEND : 0
		);
	}

	/**
	 * Reads the last n lines of a file without reading through all of it.
	 * @source http://stackoverflow.com/a/6451391
	 *
	 * @param string $path
	 * @param int $count
	 * @param int $block_size
	 * @return array
	 */
	public static function getLastLines( $path, $count, $block_size = 512 )
	{
		// we will always have a fragment of a non-complete line
		// keep this in here till we have our next entire line.
		$leftover = '';

		$lines  = [];
		$handle = fopen( $path, 'r' );

		// go to the end of the file
		fseek( $handle, 0, SEEK_END );

		do {

			// need to know whether we can actually go back
			$can_read = $block_size; // $block_size in bytes

			if ( ftell( $handle ) < $block_size )
				$can_read = ftell( $handle );

			if ( ! $can_read )
				break;

			// go back as many bytes as we can
			// read them to $data and then move the file pointer
			// back to where we were.
			fseek( $handle, -$can_read, SEEK_CUR );
			$data = fread( $handle, $can_read );
			$data.= $leftover;
			fseek( $handle, -$can_read, SEEK_CUR );

			// Splits lines by `\n`. Then reverse them,
			// now the last line is most likely not a complete
			// line which is why we do not directly add it, but
			// append it to the data read the next time.
			$split_data = array_reverse( explode( "\n", $data ) );
			$new_lines  = array_slice( $split_data, 0, -1 );
			$lines      = array_merge( $lines, $new_lines );
			$leftover   = $split_data[count( $split_data ) - 1];

		} while ( count( $lines ) < $count && 0 != ftell( $handle ) );

		if ( 0 === ftell( $handle ) )
			$lines[] = $leftover;

		fclose( $handle );

		// Usually, we will read too many lines, correct that here.
		return array_slice( $lines, 0, $count );
	}

	// @REF: https://gist.github.com/eusonlito/5099936
	public static function getFolderSize( $path, $format = TRUE )
	{
		$size = 0;

		foreach ( glob( rtrim( $path, '/' ).'/*', GLOB_NOSORT ) as $each )
			$size += is_file( $each ) ? filesize( $each ) : self::getFolderSize( $each, FALSE );

		return $format ? self::formatSize( $size ) : $size;
	}

	/**
	 * Determines the file size without any acrobatics.
	 * @source http://stackoverflow.com/a/6674672
	 *
	 * @param string $path
	 * @param bool $format
	 * @return int|string
	 */
	public static function getSize( $path, $format = TRUE )
	{
		$size = 0;

		if ( $path && ( $fh = fopen( $path, 'r+' ) ) ) {
			$stat = fstat( $fh );
			fclose( $fh );
			$size = $stat['size'];
		}

		return $format ? self::formatSize( $size ) : $size;
	}

	/**
	 * Wrapper for PHP `filesize()` with filters and casting the result as an integer.
	 * @source `wp_filesize()` @since WP 6.0
	 *
	 * @param string $path
	 * @return int
	 */
	public static function size( $path )
	{
		if ( function_exists( 'wp_filesize' ) )
			return wp_filesize( $path );

		if ( ! $path || ! file_exists( $path ) )
			return 0;

		return (int) @filesize( $path );
	}

	/**
	 * Converts a number of bytes to the largest unit the bytes will fit into.
	 *
	 * It is easier to read `1 KB` than `1024 bytes` and `1 MB` than
	 * `1048576 bytes`. Converts number of bytes to human readable number by
	 * taking the number of that unit that the bytes will go into it.
	 *
	 * Please note that integers in PHP are limited to 32 bits, unless they are
	 * on 64 bit architecture, then they have 64 bit size. If you need to place
	 * the larger size then what PHP integer type will hold, then use a string.
	 * It will be converted to a double, which should always have 64 bit length.
	 *
	 * Technically the correct unit names for powers of 1024 are `KiB`, `MiB` etc.
	 *
	 * NOTE: WordPress core function without `number_format_i18n()`
	 * @source `size_format()`
	 *
	 * @param int $bytes
	 * @param int $decimals
	 * @return string
	 */
	public static function formatSize( $bytes, $decimals = 0 )
	{
		if ( 0 === $bytes )
			return number_format( 0, $decimals ).' B';

		$quant = [
			'YB' => 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
			'ZB' => 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
			'EB' => 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
			'PB' => 1024 * 1024 * 1024 * 1024 * 1024,
			'TB' => 1024 * 1024 * 1024 * 1024,
			'GB' => 1024 * 1024 * 1024,
			'MB' => 1024 * 1024,
			'KB' => 1024,
			'B'  => 1,
		];

		foreach ( $quant as $unit => $mag )
			if ( (float) $bytes >= $mag )
				return number_format( $bytes / $mag, $decimals ).' '.$unit;

		return FALSE;
	}

	// @REF: https://www.php.net/manual/en/function.disk-free-space.php#103382
	// @SEE: https://en.wikipedia.org/wiki/Binary_prefix
	// @SEE: https://en.wikipedia.org/wiki/International_System_of_Units#Prefixes
	// @USAGE: `File::prefixSI( disk_free_space( '.' ) )`
	public static function prefixSI( $bytes, $poweroftwo = TRUE )
	{
		if ( $poweroftwo ) {

			$base   = 1024;
			$prefix = [ 'B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB' ];

		} else {

			$base   = 1000;
			$prefix = [ 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ];
		}

		$class = min( (int) log( $bytes, $base ), count( $prefix ) - 1 );

		return sprintf( '%1.2f', $bytes / pow( $base, $class ) ).' '.$prefix[$class];
	}

	public static function remove( $files )
	{
		$count = 0;

		foreach ( (array) $files as $file )
			if ( @unlink( self::normalize( $file ) ) )
				$count++;

		return $count;
	}

	// FIXME: TEST THIS
	// @SOURCE: http://stackoverflow.com/a/11267139
	public static function removeDir( $dir )
	{
		foreach ( glob( "{$dir}/*" ) as $file )
			if ( is_dir( $file ) )
				self::removeDir( $file );
			else
				unlink( $file );

		rmdir( $dir );
	}

	protected static function emptyDir( $path, $put_access_deny = FALSE )
	{
		if ( ! $path )
			return FALSE;

		try {

			// @SOURCE: http://stackoverflow.com/a/4594268
			foreach ( new \DirectoryIterator( $path ) as $file )
				if ( ! $file->isDot() )
					unlink( $file->getPathname() );

		} catch ( Exception $e ) {

			self::_log( $e->getMessage().': '.sprintf( '%s', $path ) );
		}

		return $put_access_deny ? self::putHTAccessDeny( $path, FALSE ) : TRUE;
	}

	// output up to 5MB is kept in memory, if it becomes bigger
	// it will automatically be written to a temporary file
	// @REF: http://php.net/manual/en/function.fputcsv.php#74118
	public static function toCSV( $data, $maxmemory = NULL )
	{
		if ( is_null( $maxmemory ) )
			$maxmemory =  5 * 1024 * 1024; // 5MB

		$handle = fopen( 'php://temp/maxmemory:'.$maxmemory, 'r+' );

		foreach ( $data as $fields ) {

			// @SEE: https://github.com/parsecsv/parsecsv-for-php/issues/167
			fputcsv( $handle, $fields );
		}

		rewind( $handle );

		$csv = stream_get_contents( $handle );

		fclose( $handle );

		return $csv;
	}

	// @REF: https://www.hashbangcode.com/article/remove-last-line-file-php
	public static function processCSVbyLine( $file, $callback, $args = [] )
	{
		if ( ! is_callable( $callback ) )
			return FALSE;

		$rows = file( $file ); // read the file into an array

		if ( empty( $rows ) || count( $rows ) < 2 )
			return FALSE;

		$row = trim( array_pop( $rows ) );

		if ( empty( $row ) )
			return FALSE;

		$raw  = str_getcsv( $row );
		$data = array_combine( str_getcsv( $rows[0] ), $raw );

		try {

			if ( ! $results = call_user_func_array( $callback, [ $data, $raw, $row, $args ] ) )
				return $results;

		} catch ( \Exception $e ) {

			return [ $data, $raw, $row, $e->getMessage() ]; // avoid trimming the source
		}

		$handle = fopen( $file, 'w' );

		fputs( $handle, join( '', $rows ) );
		fclose( $handle );

		unset( $file, $rows );

		return TRUE;
	}

	// NOTE: WTF: this method has no use for any kind of data!
	// @REF: https://www.hashbangcode.com/article/remove-last-line-file-php
	public static function processCSVbyLine_STUPID( $file, $callback )
	{
		$handle = fopen( $file, 'r' );
		$size   = filesize( $file );
		$break  = FALSE;
		$start  = FALSE;
		$bite   = 50; // number of bytes to look at

		// put pointer to the end of the file
		fseek( $handle, 0, SEEK_END );

		while ( FALSE === $break && FALSE === $start ) {

			$pos = ftell( $handle ); // get the current file position

			if ( $pos < $bite ) {

				// if the position is less than a bite then go to the start of the file
				rewind( $handle );

			} else {

				// move back $bite characters into the file
				fseek( $handle, -$bite, SEEK_CUR );
			}

			// read $bite characters of the file into a string
			$string = fread( $handle, $bite )
				or die ( "Can't read from file " . $file . "." ); // FIXME

			// if we happen to have read to the end of the file then we need to ignore
			// the last line as this will be a newline character
			if ( $pos + $bite >= $size)
				$string = substr_replace( $string, '', -1 );

			// since we `fread()` forward into the file we need to back up $bite characters
			if ( $pos < $bite )
				// if the position is less than a bite then go to the start of the file
				rewind( $handle );
			else
				// move back $bite characters into the file
				fseek( $handle, -$bite, SEEK_CUR );

			// is there a line break in the string we read?
			if ( is_integer( $lb = strrpos( $string, "\n" ) ) ) {
				// set $break to true so that we break out of the loop
				$break = TRUE;
				// the last line in the file is right after the line break
				$line_end = ftell( $handle ) + $lb + 1;

				self::_log( $line_end );
			}

			self::_log( $string );

			// break out of the loop if we are at the beginning of the file
			if ( 0 == ftell( $handle ) )
				$start = TRUE;
		}

		if ( TRUE === $break ) {

			// if we have found a line break then read the file into a string
			// to writing without the last line
			rewind( $handle );
			$file_minus_lastline = fread( $handle, $line_end );

			fclose( $handle );

			// open the file in write mode and truncate it
			$handle = fopen( $file, 'w+' );
			fputs( $handle, $file_minus_lastline );
			fclose( $handle );

		} else {

			// close the file, nothing else to do.
			fclose( $handle );
		}
	}

	// @REF: https://paulund.co.uk/html5-download-attribute
	public static function download( $path, $name = NULL, $mime = 'application/octet-stream' )
	{
		if ( ! self::readable( $path ) )
			return FALSE;

		if ( ! is_file( $path ) )
			return FALSE;

		if ( is_null( $name ) )
			$name = basename( $path );

		// @ini_set( 'zlib.output_compression', 'Off' );
		// @ini_set( 'zlib.output_handler', '' );
		// @ini_set( 'output_buffering', 'Off' );
		// @ini_set( 'output_handler', '' );

		header( 'Content-Description: File Transfer' );
		header( 'Pragma: public' ); // required
		header( 'Expires: 0' ); // no cache
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', FALSE );
		header( 'Content-Type: '.$mime );
		header( 'Content-Length: '.self::size( $path ) );
		header( 'Content-Disposition: attachment; filename="'.$name.'"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Connection: close' );

		// @ob_clean();
		// @flush();

		readfile( $path );

		exit;
	}

	public static function requireData( $path, $fallback = FALSE )
	{
		return self::readable( $path )
			? require( $path )
			: $fallback;
	}

	/**
	 * Stores an array in a file to access as an array later.
	 * @source https://stackoverflow.com/a/55421531
	 *
	 * @param string $filename
	 * @param array $data
	 * @param string $path
	 * @return bool
	 */
	public static function storeData( $filename, $data, $path = NULL )
	{
		return self::putContents(
			$filename,
			sprintf( '<?php return %s;', var_export( $data, TRUE ) ),
			$path
		);
	}

	/**
	 * Prepares a filename with the site name and current date.
	 *
	 * @param string $suffix
	 * @param string $prefix
	 * @param false|string $date_format
	 * @return string
	 */
	public static function prepName( $suffix = NULL, $prefix = NULL, $date_format = NULL )
	{
		$filename = '';

		if ( $prefix )
			$filename.= $prefix.'-';

		$filename.= WordPress::currentSiteName();

		if ( FALSE !== $date_format )
			$filename.= '-'.wp_date( $date_format ?? 'Ymd' );

		if ( $suffix )
			$filename.= '-'.$suffix;

		return $filename;
	}

	// @REF: https://www.tutorialsmade.com/find-string-get-line-number-file-using-php/
	public static function find_line_number_by_string( $filename, $search, $case_sensitive = FALSE )
	{
		$line_number = '';

		if ( $file_handler = fopen( $filename, "r" ) ) {

			$i = 0;

			while ( $line = fgets( $file_handler ) ) {

				$i++;

				// case-sensitive is false by default
				if ( $case_sensitive == false ) {
					$search = strtolower( $search );  //convert file and search string
					$line   = strtolower( $line );    //to lowercase
				}

				// find the string and store it in an array
				if ( strpos( $line, $search ) !== false ) {
					$line_number .=  $i.",";
				}
			}

			fclose( $file_handler );

		} else {

			return "File not exists, Please check the file path or filename";
		}

		return $line_number ? substr( $line_number, 0, -1 ) : "No match found";
	}

	/**
	 * Deletes BOM from an UTF-8 file.
	 *
	 * @param string $file
	 * @param bool $error
	 * @return true|object
	 */
	public static function stripBOM( $file, $error = FALSE )
	{
		if ( FALSE === ( $handle = fopen( $file, 'rb' ) ) )
			return $error ? new Error( 'Failed to open file.' ) : FALSE;

		$bytes = fread( $handle, 3 );

		if ( $bytes == pack( 'CCC', 0xef, 0xbb, 0xbf ) ) {

			fclose( $handle );

			if ( FALSE === ( $contents = file_get_contents( $file ) ) )
				return $error ? new Error( 'Failed to get file contents.' ) : FALSE;

			$contents = substr( $contents, 3 );

			if ( FALSE === file_put_contents( $file, $contents ) )
				return $error ? new Error( 'Failed to put file contents.' ) : FALSE;

		} else {

			fclose( $handle );
		}

		return TRUE;
    }
}

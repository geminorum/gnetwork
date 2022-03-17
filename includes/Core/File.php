<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class File extends Base
{

	// normalize a filesystem path
	// on windows systems, replaces backslashes with forward slashes
	// and forces upper-case drive letters.
	// allows for two leading slashes for Windows network shares, but
	// ensures that all other duplicate slashes are reduced to a single.
	// @SOURCE: `wp_normalize_path()`
	public static function normalize( $path )
	{
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );

		if ( ':' === substr( $path, 1, 1 ) )
			$path = ucfirst( $path );

		return $path;
	}

	// i18n friendly version of `basename()`
	// if the filename ends in suffix this will also be cut off
	// @SOURCE: `wp_basename()`
	public static function basename( $path, $suffix = '' )
	{
		return urldecode( basename( str_replace( array( '%2F', '%5C' ), '/', urlencode( $path ) ), $suffix ) );
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

		// If the folder is falsey, use its parent directory name instead.
		if ( ! $temp )
			return self::tempName( dirname( $name ), $dir );

		// Suffix some random data to avoid filename conflicts.
		$temp.= '-'.wp_generate_password( 6, FALSE );
		$temp.= '.tmp';
		$temp = $dir.wp_unique_filename( $dir, $temp );

		$fp = @fopen( $temp, 'x' );

		if ( ! $fp && is_writable( $dir ) && file_exists( $temp ) )
			return self::tempName( $name, $dir );

		if ( $fp )
			fclose( $fp );

		return $temp;
	}

	// join two filesystem paths together
	// @SOURCE: `path_join()`
	public static function join( $base, $path )
	{
		return self::isAbsolute( $path ) ? $path : rtrim( $base, '/' ).'/'.ltrim( $path, '/' );
	}

	// test if a give filesystem path is absolute
	// for example, '/foo/bar', or 'c:\windows'
	// @SOURCE: `path_is_absolute()`
	public static function isAbsolute( $path )
	{
		// this is definitive if true but fails if $path does not exist or contains a symbolic link
		if ( $path == realpath( $path ) )
			return TRUE;

		if ( 0 == strlen( $path ) || '.' == $path[0] )
			return FALSE;

		// windows allows absolute paths like this
		if ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) )
			return TRUE;

		// a path starting with / or \ is absolute; anything else is relative
		return ( '/' == $path[0] || '\\' == $path[0] );
	}

	// http://stackoverflow.com/a/4994188
	// core has `sanitize_file_name()` but with certain mime types
	public static function escFilename( $path )
	{
		// everything to lower and no spaces begin or end
		$path = strtolower( trim( $path ) );

		// adding - for spaces and union characters
		$path = str_replace( array( ' ', '&', '\r\n', '\n', '+', ',' ), '-', $path );

		// delete and replace rest of special chars
		$path = preg_replace( array( '/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/' ), array( '', '-', '' ), $path );

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

	// puts .htaccess deny from all on a given folder
	public static function putHTAccessDeny( $path, $check_folder = TRUE )
	{
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

	// wrapper for `file_get_contents()`
	// TODO: use `$wp_filesystem`
	// @REF: https://github.com/markjaquith/feedback/issues/33
	// @REF: `$wp_filesystem->get_contents()`
	public static function getContents( $filename )
	{
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

		} else if ( wp_is_writable( $path ) ) {

			$dir = $path;
		}

		if ( ! $dir )
			return $dir;

		if ( $append )
			return file_put_contents( self::join( $dir, $filename ), $contents.PHP_EOL, FILE_APPEND );

		return file_put_contents( self::join( $dir, $filename ), $contents.PHP_EOL );
	}

	// @SOURCE: http://stackoverflow.com/a/6451391
	// read the last n lines of a file without reading through all of it
	public static function getLastLines( $path, $count, $block_size = 512 )
	{
		$lines = array();

		// we will always have a fragment of a non-complete line
		// keep this in here till we have our next entire line.
		$leftover = '';

		$fh = fopen( $path, 'r' );

		// go to the end of the file
		fseek( $fh, 0, SEEK_END );

		do {

			// need to know whether we can actually go back
			$can_read = $block_size; // $block_size in bytes

			if ( ftell( $fh ) < $block_size )
				$can_read = ftell( $fh );

			// go back as many bytes as we can
			// read them to $data and then move the file pointer
			// back to where we were.
			fseek( $fh, -$can_read, SEEK_CUR );
			$data = fread( $fh, $can_read );
			$data.= $leftover;
			fseek( $fh, -$can_read, SEEK_CUR );

			// split lines by \n. Then reverse them,
			// now the last line is most likely not a complete
			// line which is why we do not directly add it, but
			// append it to the data read the next time.
			$split_data = array_reverse( explode( "\n", $data ) );
			$new_lines  = array_slice( $split_data, 0, -1 );
			$lines      = array_merge( $lines, $new_lines );
			$leftover   = $split_data[count( $split_data ) - 1];
		}

		while ( count( $lines ) < $count && 0 != ftell( $fh ) );

		if ( 0 == ftell( $fh ) )
			$lines[] = $leftover;

		fclose( $fh );

		// usually, we will read too many lines, correct that here.
		return array_slice( $lines, 0, $count );
	}

	// @SOURCE: http://stackoverflow.com/a/6674672
	// determines the file size without any acrobatics
	public static function getSize( $path, $format = TRUE )
	{
		$fh   = fopen( $path, 'r+' );
		$stat = fstat( $fh );
		fclose( $fh );

		return $format ? self::formatSize( $stat['size'] ) : $stat['size'];
	}

	// wrapper for `wp_filesize` @since WP 6.0
	public static function size( $path )
	{
		if ( function_exists( 'wp_filesize' ) )
			return wp_filesize( $path );

		return (int) @filesize( $path );
	}

	// WP core `size_format()` function without `number_format_i18n()`
	public static function formatSize( $bytes, $decimals = 0 )
	{
		$quant = array(
			'TB' => 1024 * 1024 * 1024 * 1024,
			'GB' => 1024 * 1024 * 1024,
			'MB' => 1024 * 1024,
			'KB' => 1024,
			'B'  => 1,
		);

		if ( 0 === $bytes )
			return number_format( 0, $decimals ).' B';

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

	// @REF: https://paulund.co.uk/html5-download-attribute
	public static function download( $path, $name = NULL, $mime = 'application/octet-stream' )
	{
		if ( ! is_readable( $path ) )
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

	public static function prepName( $suffix = NULL, $prefix = NULL )
	{
		$name = '';

		if ( $prefix )
			$name.= $prefix.'-';

		$name.= WordPress::currentSiteName().'-'.current_time( 'Y-m-d' );

		if ( $suffix )
			$name.= '-'.$suffix;

		return $name;
	}
}

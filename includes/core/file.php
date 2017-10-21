<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

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
		$content = '<Files ~ ".*\..*">'.PHP_EOL.'order allow,deny'.PHP_EOL.'deny from all'.PHP_EOL.'</Files>';

		return self::putContents( '.htaccess', $content, $path, FALSE, $check_folder );
	}

	// wrapper for file_get_contents()
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

	// @SOURCE: http://stackoverflow.com/a/6451391/4864081
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
			$data .= $leftover;
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

	// @SOURCE: http://stackoverflow.com/a/6674672/4864081
	// determines the file size without any acrobatics
	public static function getSize( $path, $format = TRUE )
	{
		$fh   = fopen( $path, 'r+' );
		$stat = fstat( $fh );
		fclose( $fh );

		return $format ? self::formatSize( $stat['size'] ) : $stat['size'];
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

		foreach ( $quant as $unit => $mag )
			if ( doubleval( $bytes ) >= $mag )
				return number_format( $bytes / $mag, $decimals ).' '.$unit;

		return FALSE;
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
	// @SOURCE: http://stackoverflow.com/a/11267139/4864081
	public static function removeDir( $dir )
	{
		foreach ( glob( "{$dir}/*" ) as $file )
			if ( is_dir( $file ) )
				self::removeDir( $file );
			else
				unlink( $file );

		rmdir( $dir );
	}

	// output up to 5MB is kept in memory, if it becomes bigger
	// it will automatically be written to a temporary file
	// @REF: http://php.net/manual/en/function.fputcsv.php#74118
	public static function toCSV( $data, $maxmemory = NULL )
	{
		if ( is_null( $maxmemory ) )
			$maxmemory =  5 * 1024 * 1024; // 5MB

		$handle = fopen( 'php://temp/maxmemory:'.$maxmemory, 'r+' );

		foreach( $data as $fields )
			fputcsv( $handle, $fields );

		rewind( $handle );

		$csv = stream_get_contents( $handle );

		fclose( $handle );

		return $csv;
	}

	// @REF: http://www.paulund.co.uk/html5-download-attribute
	public static function download( $path, $name = NULL, $mime = 'application/octet-stream' )
	{
		if ( ! file_exists( $path ) )
			return FALSE;

		if ( ! is_file( $path ) )
			return FALSE;

		if ( is_null( $name ) )
			$name = basename( $path );

		header( 'Content-Description: File Transfer' );
		header( 'Pragma: public' ); // required
		header( 'Expires: 0' ); // no cache
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', FALSE );
		header( 'Content-Type: '.$mime );
		header( 'Content-Length: '.filesize( $path ) );
		header( 'Content-Disposition: attachment; filename="'.$name.'"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Connection: close' );

		@ob_clean();
		flush();

		readfile( $path );

		exit();
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

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class File extends Base
{

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
			return file_put_contents( path_join( $dir, $filename ), $contents.PHP_EOL, FILE_APPEND );

		return file_put_contents( path_join( $dir, $filename ), $contents.PHP_EOL );
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
			$name .= $prefix.'-';

		$name .= WordPress::currentBlog().'-'.current_time( 'Y-m-d' );

		if ( $suffix )
			$name .= '-'.$suffix;

		return $name;
	}
}

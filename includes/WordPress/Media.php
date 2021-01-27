<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Media extends Core\Base
{

	// @REF: `wp_import_handle_upload()`
	public static function handleImportUpload( $name = 'import' )
	{
		if ( ! isset( $_FILES[$name] ) )
			return FALSE;

		$_FILES[$name]['name'].= '.txt';

		$upload = wp_handle_upload( $_FILES[$name], [ 'test_form' => FALSE, 'test_type' => FALSE ] );

		if ( isset( $upload['error'] ) )
			return FALSE; // $upload;

		$id = wp_insert_attachment( [
			'post_title'     => Core\File::basename( $upload['file'] ),
			'post_content'   => $upload['url'],
			'post_mime_type' => $upload['type'],
			'guid'           => $upload['url'],
			'context'        => 'import',
			'post_status'    => 'private',
		], $upload['file'] );

		// schedule a cleanup for one day from now in case of failed import or missing `wp_import_cleanup()` call
		wp_schedule_single_event( time() + DAY_IN_SECONDS, 'importer_scheduled_cleanup', [ $id ] );

		return [ 'file' => $upload['file'], 'id' => $id ];
	}

	public static function handleSideload( $file, $post, $desc = NULL, $data = [] )
	{
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH.'wp-admin/includes/image.php';
			require_once ABSPATH.'wp-admin/includes/file.php';
			require_once ABSPATH.'wp-admin/includes/media.php';
		}

		return media_handle_sideload( $file, $post, $desc, $data );
	}

	public static function sideloadImageData( $name, $data, $post = 0, $extra = [] )
	{
		if ( ! $temp = Core\File::tempName( $name ) )
			return FALSE; // new WP_Error( 'http_no_file', __( 'Could not create Temporary file.' ) );

		if ( ! file_put_contents( $temp, $data ) )
			return FALSE;

		$file = [ 'name' => $name, 'tmp_name' => $temp ];

		$attachment = self::handleSideload( $file, $post, NULL, $extra );

		// if error storing permanently, unlink
		if ( is_wp_error( $attachment ) ) {
			@unlink( $file['tmp_name'] );
			return $attachment;
		}

		return $attachment;
	}

	// @REF: `media_sideload_image()`
	public static function sideloadImageURL( $url, $post = 0, $extra = [] )
	{
		if ( empty( $url ) )
			return FALSE;

		// filters the list of allowed file extensions when sideloading an image from a URL @since 5.6.0
		$extensions = apply_filters( 'image_sideload_extensions', [ 'jpg', 'jpeg', 'jpe', 'png', 'gif' ], $url );

		// set variables for storage, fix file filename for query strings
		preg_match( '/[^\?]+\.(' . implode( '|', array_map( 'preg_quote', $extensions ) ) . ')\b/i', $url, $matches );

		if ( ! $matches )
			return FALSE; // new WP_Error( 'image_sideload_failed', __( 'Invalid image URL.' ) );

		// download file to temp location
		$file = [ 'tmp_name' => download_url( $url ) ];

		// if error storing temporarily, return the error
		if ( is_wp_error( $file['tmp_name'] ) )
			return $file['tmp_name'];

		$file['name'] = Core\File::basename( $matches[0] );

		// do the validation and storage stuff
		$attachment = self::handleSideload( $file, $post, NULL, $extra );

		// if error storing permanently, unlink
		if ( is_wp_error( $attachment ) ) {
			@unlink( $file['tmp_name'] );
			return $attachment;
		}

		// store the original attachment source in meta
		add_post_meta( $attachment, '_source_url', $url );

		return $attachment;
	}
}

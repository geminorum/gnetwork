<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Media extends Core\Base
{

	public static function upload( $post = FALSE )
	{
		if ( FALSE === $post )
			return wp_upload_dir( NULL, FALSE, FALSE );

		if ( ! $post = get_post( $post ) )
			return wp_upload_dir( NULL, TRUE, FALSE );

		if ( 'page' === $post->post_type )
			return wp_upload_dir( NULL, TRUE, FALSE );

		return wp_upload_dir( ( substr( $post->post_date, 0, 4 ) > 0 ? $post->post_date : NULL ), TRUE, FALSE );
	}

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
		$extensions = apply_filters( 'image_sideload_extensions', [ 'jpg', 'jpeg', 'jpe', 'png', 'gif', 'webp', 'avif' ], $url );

		// set variables for storage, fix file filename for query strings
		preg_match( '/[^\?]+\.(' . implode( '|', array_map( 'preg_quote', $extensions ) ) . ')\b/i', $url, $matches );

		if ( ! $matches )
			return FALSE; // new \WP_Error( 'image_sideload_failed', __( 'Invalid image URL.' ) );

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

	public static function getUploadDirectory( $sub = '', $create = FALSE, $htaccess = TRUE )
	{
		$upload = wp_upload_dir( NULL, FALSE, FALSE );

		if ( ! $sub )
			return $upload['basedir'];

		$folder = Core\File::join( $upload['basedir'], $sub );

		if ( $create ) {

			if ( ! is_dir( $folder ) || ! Core\File::writable( $folder ) ) {

				if ( $htaccess )
					Core\File::putHTAccessDeny( $folder, TRUE );
				else
					wp_mkdir_p( $folder );

			} else if ( $htaccess && ! file_exists( $folder.'/.htaccess' ) ) {

				Core\File::putHTAccessDeny( $folder, FALSE );
			}

			if ( ! Core\File::writable( $folder ) )
				return FALSE;
		}

		return $folder;
	}

	public static function getUploadURL( $sub = '' )
	{
		$upload = wp_upload_dir( NULL, FALSE, FALSE );
		$base   = Core\WordPress::isSSL() ? str_ireplace( 'http://', 'https://', $upload['baseurl'] ) : $upload['baseurl'];
		return $sub ? $base.'/'.$sub : $base;
	}

	public static function getAttachments( $post_id, $mime_type = 'image' )
	{
		return get_children( array(
			'post_mime_type' => $mime_type,
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'numberposts'    => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
	}

	// TODO: get title if html is empty
	public static function htmlAttachmentShortLink( $id, $html, $extra = '', $rel = 'attachment' )
	{
		return Core\HTML::tag( 'a', [
			'href'  => Core\WordPress::getPostShortLink( $id ),
			'rel'   => $rel,
			'class' => Core\HTML::attrClass( $extra, '-attachment' ),
			'data'  => [ 'id' => $id ],
		], $html );
	}

	public static function isCustom( $attachment_id )
	{
		if ( ! $attachment_id )
			return FALSE;

		if ( get_post_meta( $attachment_id, '_wp_attachment_is_custom_header', TRUE ) )
			return 'custom_header';

		if ( get_post_meta( $attachment_id, '_wp_attachment_is_custom_background', TRUE ) )
			return 'custom_background';

		if ( get_post_meta( $attachment_id, '_wp_attachment_is_term_image', TRUE ) )
			return 'term_image';

		if ( $attachment_id == get_option( 'site_icon' ) )
			return 'site_icon';

		if ( $attachment_id == get_theme_mod( 'custom_logo' ) )
			return 'custom_logo';

		if ( $attachment_id == get_theme_mod( 'site_logo' ) )
			return 'site_logo';

		return FALSE;
	}

	// PDF: 'application/pdf'
	// MP3: 'audio/mpeg'
	// CSV: 'application/vnd.ms-excel'
	public static function selectAttachment( $selected = 0, $mime = NULL, $name = 'attach_id', $empty = '' )
	{
		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'numberposts'    => -1,
			'post_status'    => NULL,
			'post_mime_type' => $mime,
			'post_parent'    => NULL,
		) );

		if ( empty( $attachments ) ) {
			echo $empty;
			return FALSE;
		}

		echo Core\HTML::dropdown(
			Core\Arraay::reKey( $attachments, 'ID' ),
			array(
				'name'       => $name,
				'none_title' => Settings::showOptionNone(),
				'class'      => '-attachment',
				'selected'   => $selected,
				'prop'       => 'post_title',
			)
		);
	}

	/**
	 * Retrieves post-ids with text containing given attachment.
	 * NOTE: searches only for portion of the attached file
	 * like: `2021/10/filename` where `filename.ext` is the filename
	 *
	 * @param  int  $attachment_id
	 * @return array $post_ids
	 */
	public static function searchAttachment( $attachment_id )
	{
		if ( ! $file = get_post_meta( $attachment_id, '_wp_attached_file', TRUE ) )
			return [];

		$filetype = Core\File::type( Core\File::basename( $file ) );
		$pathfile = Core\File::join( dirname( $file ), Core\File::basename( $file, '.'.$filetype['ext'] ) );

		return PostType::getIDsBySearch( $pathfile );
	}

	// @REF: https://pippinsplugins.com/retrieve-attachment-id-from-image-url/
	// NOTE: doesn't really work if the guid gets out of sync
	// or if the URL you have is for a cropped image.
	public static function getAttachmentByURL( $url )
	{
		global $wpdb;

		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid='%s';", $url ) );

		return empty( $attachment ) ? NULL : $attachment[0];
	}

	public static function getAttachmentImageAlt( $attachment_id, $fallback = '', $raw = FALSE )
	{
		if ( empty( $attachment_id ) )
			return $fallback;

		if ( $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', TRUE ) )
			return $raw ? $alt : trim( strip_tags( $alt ) );

		return $fallback;
	}

	// @REF: https://wordpress.stackexchange.com/a/315447
	// @SEE: `wp_prepare_attachment_for_js()`
	public static function prepAttachmentData( $attachment_id )
	{
		if ( ! $attachment_id )
			return [];

		$uploads  = self::upload();
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$prepared = [
			'title'     => get_the_title( $attachment_id ),
			'alt'       => self::getAttachmentImageAlt( $attachment_id, NULL, TRUE ),
			'caption'   => wp_get_attachment_caption( $attachment_id ),
			'mime_type' => get_post_mime_type( $attachment_id ),
			'url'       => $uploads['baseurl'].'/'.$metadata['file'],
			'width'     => empty( $metadata['width'] ) ? NULL : $metadata['width'],
			'height'    => empty( $metadata['height'] ) ? NULL : $metadata['height'],
			'filesize'  => empty( $metadata['filesize'] ) ? NULL : $metadata['filesize'],
			'sizes'     => [],
		];

		if ( ! empty( $metadata['sizes'] ) )
			foreach ( $metadata['sizes'] as $size => $info )
				$prepared['sizes'][$size] = $uploads['baseurl'].'/'.dirname( $metadata['file'] ).'/'.$info['file'];

		return $prepared;
	}

	// @SOURCE: `bp_attachments_get_mime_type()`
	// NOTE: checks against all mimetypes, not just only allowed by WordPress!
	public static function getMimeType( $path )
	{
		$type = Core\File::type( $path, wp_get_mime_types() );
		$mime = $type['type'];

		if ( FALSE === $mime && is_dir( $path ) )
			$mime = 'directory';

		return $mime;
	}

	public static function getAttachmentFileSize( $attachment_id, $format = FALSE, $template = NULL )
	{
		// $filesize = filesize( get_attached_file( $attachment_id ), 2 );
		$filesize = Core\File::getSize( get_attached_file( $attachment_id ), FALSE );

		return $format
			? sprintf( $template ?? '<span class="-filesize">%s</span>', Core\HTML::wrapLTR( Core\File::formatSize( $filesize ) ) )
			: $filesize;
	}

	public static function emptyAttachmentImageMeta( $attachment_id )
	{
		if ( ! $attachment_id )
			return FALSE;

		if ( ! $metadata = wp_get_attachment_metadata( $attachment_id ) )
			return TRUE;

		unset( $metadata['image_meta'] );

		return wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	public static function disableThumbnailGeneration()
	{
		add_filter( 'intermediate_image_sizes', '__return_empty_array', 99 );
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 99 );
	}
}

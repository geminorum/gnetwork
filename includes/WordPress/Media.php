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

	/**
	 * Retrieves available mime types for given post-type in media library.
	 * @ref `wp_count_attachments()`
	 *
	 * @return array
	 */
	public static function availableMIMETypes( $posttype = 'attachment', $excludes = NULL )
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT DISTINCT post_mime_type
			FROM {$wpdb->posts}
			WHERE post_type = '%s'
			AND post_status != 'trash'
			ORDER BY post_mime_type ASC
		", $posttype );

		return $wpdb->get_col( $query );
	}

	/**
	 * Retrieves file extension given a mime-type.
	 *
	 * @param string $mime_type
	 * @param null|array $extensions
	 * @return string
	 */
	public static function getExtension( $mime_type, $extensions = NULL, $uppercase = TRUE )
	{
		if ( is_null( $extensions ) )
			$extensions = wp_get_mime_types();

		if ( FALSE === ( $key = array_search( $mime_type, $extensions ) ) )
			return FALSE;

		$parts = explode( '|', $key );

		return $uppercase ? strtoupper( $parts[0] ) : $parts[0];
	}

	// WP default sizes from options
	public static function defaultImageSizes()
	{
		static $sizes = NULL;

		if ( ! is_null( $sizes ) )
			return $sizes;

		$sizes = [
			'thumbnail' => [
				'n' => __( 'Thumbnail' ),
				'w' => get_option( 'thumbnail_size_w' ),
				'h' => get_option( 'thumbnail_size_h' ),
				'c' => get_option( 'thumbnail_crop' ),
			],
			'medium' => [
				'n' => __( 'Medium' ),
				'w' => get_option( 'medium_size_w' ),
				'h' => get_option( 'medium_size_h' ),
				'c' => 0,
			],
			// 'medium_large' => [
			// 	'n' => __( 'Medium Large' ),
			// 	'w' => get_option( 'medium_large_size_w' ),
			// 	'h' => get_option( 'medium_large_size_h' ),
			// 	'c' => 0,
			// ],
			'large' => [
				'n' => __( 'Large' ),
				'w' => get_option( 'large_size_w' ),
				'h' => get_option( 'large_size_h' ),
				'c' => 0,
			],
		];

		return $sizes;
	}

	// Core duplicate with post-type/taxonomy/title
	// @REF: `add_image_size()`
	public static function registerImageSize( $name, $atts = [] )
	{
		global $_wp_additional_image_sizes;

		$args = self::atts( [
			'n' => __( 'Untitled' ),
			'w' => 0,
			'h' => 0,
			'c' => 0,
			'p' => [ 'post' ],                                 // posttype: TRUE: all/array: posttypes/FALSE: none
			't' => FALSE,                                      // taxonomy: TRUE: all/array: taxes/FALSE: none
			'f' => empty( $atts['s'] ) ? FALSE : $atts['s'],   // featured
		], $atts );

		$_wp_additional_image_sizes[$name] = [
			'width'     => absint( $args['w'] ),
			'height'    => absint( $args['h'] ),
			'crop'      => $args['c'],
			'post_type' => $args['p'],
			'taxonomy'  => $args['t'],
			'title'     => $args['n'],
			'thumbnail' => $args['f'],
		];
	}

	// this must be core's
	// call this late on 'after_setup_theme' hook
	public static function themeThumbnails( $posttypes )
	{
		global $_wp_theme_features;

		$feature = 'post-thumbnails';

		if ( isset( $_wp_theme_features[$feature] ) ) {

			// registered for all types
			if ( TRUE === $_wp_theme_features[$feature] ) {

				// WORKING: but if it is true, it's true!
				// $posttypes[] = 'post';
				// $_wp_theme_features[$feature] = [ $posttypes ];

			} else if ( is_array( $_wp_theme_features[$feature][0] ) ) {
				$_wp_theme_features[$feature][0] = array_merge( $_wp_theme_features[$feature][0], $posttypes );
			}

		} else {
			$_wp_theme_features[$feature] = [ $posttypes ];
		}
	}

	// OLD: `getRegisteredImageSizes()`
	public static function getPosttypeImageSizes( $posttype = 'post', $fallback = FALSE )
	{
		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( (array) $_wp_additional_image_sizes as $name => $args ) {

			if ( array_key_exists( 'post_type', $args ) ) {

				if ( is_array( $args['post_type'] ) ) {

					if ( in_array( $posttype, $args['post_type'] ) )
						$sizes[$name] = $args;

					else if ( is_string( $fallback ) && in_array( $fallback, $args['post_type'] ) )
						$sizes[$name] = $args;

				} else if ( $args['post_type'] ) {

					$sizes[$name] = $args;
				}

			} else if ( TRUE === $fallback ) {

				$sizes[$name] = $args;
			}
		}

		return $sizes;
	}

	public static function getTaxonomyImageSizes( $taxonomy = 'category', $fallback = FALSE )
	{
		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( (array) $_wp_additional_image_sizes as $name => $args ) {

			if ( array_key_exists( 'taxonomy', $args ) ) {

				if ( is_array( $args['taxonomy'] ) ) {

					if ( in_array( $taxonomy, $args['taxonomy'] ) )
						$sizes[$name] = $args;

					else if ( is_string( $fallback ) && in_array( $fallback, $args['taxonomy'] ) )
						$sizes[$name] = $args;

				} else if ( $args['taxonomy'] ) {
					$sizes[$name] = $args;
				}

			} else if ( TRUE === $fallback ) {

				$sizes[$name] = $args;
			}
		}

		return $sizes;
	}

	// @REF: `wp_import_handle_upload()`
	// NOTE: for upload see `settings_render_upload_field()`
	public static function handleImportUpload( $name = 'import', $cleanup = TRUE )
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

		if ( $cleanup ) {

			// Schedules a cleanup for one day from now in case of failed
			// import or missing `wp_import_cleanup()` call.
			wp_schedule_single_event( time() + DAY_IN_SECONDS, 'importer_scheduled_cleanup', [ $id ] );
		}

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
			return FALSE; // `new WP_Error( 'http_no_file', __( 'Could not create Temporary file.' ) );`

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

		// Filters the list of allowed file extensions when sideloading an image from a URL. @since WP 5.6.0
		$extensions = apply_filters( 'image_sideload_extensions', [ 'jpg', 'jpeg', 'jpe', 'png', 'gif', 'webp', 'avif' ], $url );

		// Sets variables for storage, fix file filename for query strings.
		preg_match( '/[^\?]+\.(' . implode( '|', array_map( 'preg_quote', $extensions ) ) . ')\b/i', $url, $matches );

		if ( ! $matches )
			return FALSE; // `new \WP_Error( 'image_sideload_failed', __( 'Invalid image URL.' ) );`

		// download file to temp location
		$file = [ 'tmp_name' => download_url( $url ) ];

		// if error storing temporarily, return the error
		if ( is_wp_error( $file['tmp_name'] ) )
			return $file['tmp_name'];

		$file['name'] = Core\File::basename( $matches[0] );

		// Do the validation and storage stuff.
		$attachment = self::handleSideload( $file, $post, NULL, $extra );

		// If error storing permanently, unlink.
		if ( is_wp_error( $attachment ) ) {
			@unlink( $file['tmp_name'] );
			return $attachment;
		}

		// Stores the original attachment source in meta.
		add_post_meta( $attachment, '_source_url', $url );

		return $attachment;
	}

	public static function getUploadDirectory( $sub = '', $create = FALSE, $htaccess = TRUE, $donotbackup = FALSE )
	{
		$upload = wp_upload_dir( NULL, FALSE, FALSE );

		if ( ! $sub )
			return $upload['basedir'];

		$folder = Core\File::join( $upload['basedir'], $sub );

		if ( $create ) {

			if ( ! is_dir( $folder ) || ! Core\File::writable( $folder ) ) {

				if ( ! wp_mkdir_p( $folder ) )
					return FALSE;

				if ( $htaccess )
					Core\File::putHTAccessDeny( $folder, FALSE );

				if ( $donotbackup )
					Core\File::putDoNotBackup( $folder, FALSE );

			} else {

				if ( $htaccess && ! Core\File::exists( '.htaccess', $folder ) )
					Core\File::putHTAccessDeny( $folder, FALSE );

				if ( $donotbackup && ! Core\File::exists( '.donotbackup', $folder ) )
					Core\File::putDoNotBackup( $folder, FALSE );
			}

			if ( ! Core\File::writable( $folder ) )
				return FALSE;
		}

		return $folder;
	}

	public static function getUploadURL( $sub = '' )
	{
		$upload = wp_upload_dir( NULL, FALSE, FALSE );
		$base   = IsIt::ssl() ? str_ireplace( 'http://', 'https://', $upload['baseurl'] ) : $upload['baseurl'];
		return $sub ? $base.'/'.$sub : $base;
	}

	// NOTE: DEPRECATED: USE: `WordPress\Attachment::list()`
	public static function getAttachments( $post_id, $mime_type = 'image' )
	{
		self::_dev_dep( 'WordPress\Attachment::list()' );
		return Attachment::list( $post_id, $mime_type );
	}

	// TODO: get title if HTML is empty
	public static function htmlAttachmentShortLink( $id, $html, $extra = '', $rel = 'attachment' )
	{
		return Core\HTML::tag( 'a', [
			'href'  => Post::shortlink( $id ),
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

		if ( get_post_meta( $attachment_id, '_wp_attachment_context', TRUE ) )
			return 'custom_context';

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

	// `PDF`: `application/pdf`
	// `MP3`: `audio/mpeg`
	// `CSV`: `application/vnd.ms-excel`
	public static function selectAttachment( $selected = 0, $mime = NULL, $name = 'attach_id', $empty = '' )
	{
		$attachments = get_posts( [
			'post_type'      => 'attachment',
			'numberposts'    => -1,
			'post_status'    => NULL,
			'post_mime_type' => $mime,
			'post_parent'    => NULL,
		] );

		if ( empty( $attachments ) ) {
			echo $empty;
			return FALSE;
		}

		echo Core\HTML::dropdown(
			Core\Arraay::reKey( $attachments, 'ID' ),
			array(
				'name'       => $name,
				'none_title' => __( 'Select an item' ), // Settings::showOptionNone(),
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
	 * @param int $attachment_id
	 * @return array
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
	// NOTE: doesn't really work if the `guid` gets out of sync
	// or if the URL you have is for a cropped image.
	public static function getAttachmentByURL( $url )
	{
		global $wpdb;

		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid='%s';", $url ) );

		return empty( $attachment ) ? NULL : $attachment[0];
	}

	// @REF: https://wpscholar.com/blog/get-attachment-id-from-wp-image-url/
	// @SEE: `attachment_url_to_postid()`: Notably this will not work on image
	// sizes, core version only searches "main" attached file.
	public static function getAttachmentByURL_ALT( $url )
	{
		$upload = self::upload();

		// Is URL in uploads directory?
		if ( FALSE === strpos( $url, $upload['baseurl'] . '/' ) )
			return 0;

		$file  = Core\File::basename( $url );
		$query = new \WP_Query( [
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'fields'      => 'ids',
			'meta_query'  => [ [
				'value'   => $file,
				'compare' => 'LIKE',
				'key'     => '_wp_attachment_metadata',
			] ],
		] );

		if ( ! $query->have_posts() )
			return 0;

		foreach ( $query->posts as $post_id ) {

			$meta = wp_get_attachment_metadata( $post_id );

			$original = Core\File::basename( $meta['file'] );
			$cropped  = Core\Arraay::pluck( $meta['sizes'], 'file' );

			if ( $original === $file || in_array( $file, $cropped ) )
				return $post_id;
		}

		return 0;
	}

	public static function getAttachmentImageAlt( $attachment_id, $fallback = '', $raw = FALSE )
	{
		if ( empty( $attachment_id ) )
			return $fallback;

		if ( $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', TRUE ) )
			return $raw ? $alt : Core\Text::stripTags( $alt );

		return $fallback;
	}

	public static function getAttachmentImageDefaultSize( $perent_posttype = NULL, $perent_taxonomy = NULL, $fallback = 'thumbnail' )
	{
		$size     = NULL;
		$sizes    = wp_get_additional_image_sizes();
		$template = $fallback ? ( '%s-'.$fallback ) : '%s-thumbnail';
		$posttype = $perent_posttype ? sprintf( $template, $perent_posttype ) : FALSE;
		$taxonomy = $perent_taxonomy ? sprintf( $template, $perent_taxonomy ) : FALSE;

		if ( $posttype && isset( $sizes[$posttype] ) )
			$size = $posttype;

		else if ( $taxonomy && isset( $sizes[$taxonomy] ) )
			$size = $taxonomy;

		$size = apply_filters( 'geditorial_get_thumbnail_default_size', $size, $perent_posttype, $perent_taxonomy, $fallback );

		return $size ?: $fallback;
	}

	// NOTE: DEPRECATED: use `WordPress\Media::getAttachmentSrc()`
	public static function htmlAttachmentSrc( $attachment_id, $size = NULL, $fallback = '' )
	{
		self::_dev_dep( 'WordPress\Media::getAttachmentSrc()' );
		return self::getAttachmentSrc( $attachment_id, $size, $fallback );
	}

	public static function getAttachmentSrc( $attachment_id, $size = NULL, $fallback = '' )
	{
		$img = NULL;
		$src = $fallback;

		if ( is_null( $size ) )
			$size = self::getAttachmentImageDefaultSize();

		if ( ! empty( $attachment_id ) ) {

			if ( $img = wp_get_attachment_image_src( $attachment_id, $size ) )
				$src = $img[0];
		}

		return apply_filters( 'geditorial_get_thumbnail_src', $src, $attachment_id, $img, $fallback );
	}

	public static function htmlAttachmentImage( $attachment_id, $size = NULL, $link = TRUE, $data = [], $class = '-attachment-image' )
	{
		if ( empty( $attachment_id ) )
			return '';

		if ( ! $src = self::getAttachmentSrc( $attachment_id, $size, FALSE ) )
			return '';

		if ( empty( $data['attachment'] ) )
			$data['attachment'] = $attachment_id;

		$image = Core\HTML::tag( 'img', [
			'src'      => $src,
			'alt'      => self::getAttachmentImageAlt( $attachment_id ),
			'data'     => $data,
			'class'    => $class,
			'loading'  => 'lazy',
			'decoding' => 'async',
		] );

		return $link ? Core\HTML::tag( 'a', [
			'href'   => wp_get_attachment_url( $attachment_id ),
			'title'  => get_the_title( $attachment_id ),
			'data'    => $data,
			'class'  => 'thickbox',
			'target' => '_blank',
		], $image ) : $image;
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
	// NOTE: checks against all mime-types, not just only allowed by WordPress!
	public static function getMimeType( $path )
	{
		$type = Core\File::type( $path );
		$mime = $type['type'];

		if ( FALSE === $mime && is_dir( $path ) )
			$mime = 'directory';

		return $mime;
	}

	// NOTE: DEPRECATED
	public static function deleteAttachmentThumbnails( $attachment_id )
	{
		self::_dev_dep( 'WordPress\Media::deleteImageSizes()' );
		return self::deleteImageSizes( $attachment_id );
	}

	// @REF: `wp_delete_attachment_files()
	public static function deleteImageSizes( $attachment_id )
	{
		if ( ! $attachment_id )
			return FALSE;

		$attachment = get_post( $attachment_id );

		if ( 'attachment' !== $attachment->post_type )
			return FALSE;

		$deleted      = TRUE;
		$uploads      = self::upload();
		$metadata     = wp_get_attachment_metadata( $attachment_id );
		$backup_sizes = get_post_meta( $attachment->ID, '_wp_attachment_backup_sizes', TRUE );
		$file         = get_attached_file( $attachment_id );

		// Removes intermediate and backup images if there are any
		if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {

			$intermediate_dir = path_join( $uploads['basedir'], dirname( $file ) );

			foreach ( $metadata['sizes'] as $size => $sizeinfo ) {

				$intermediate_file = str_replace( wp_basename( $file ), $sizeinfo['file'], $file );

				if ( ! empty( $intermediate_file ) ) {

					$intermediate_file = path_join( $uploads['basedir'], $intermediate_file );

					if ( ! wp_delete_file_from_directory( $intermediate_file, $intermediate_dir ) )
						$deleted = FALSE;
				}
			}

			unset( $metadata['sizes'] );
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		if ( is_array( $backup_sizes ) ) {

			$del_dir = path_join( $uploads['basedir'], dirname( $metadata['file'] ) );

			foreach ( $backup_sizes as $size ) {

				$del_file = path_join( dirname( $metadata['file'] ), $size['file'] );

				if ( ! empty( $del_file ) ) {

					$del_file = path_join( $uploads['basedir'], $del_file );

					if ( ! wp_delete_file_from_directory( $del_file, $del_dir ) )
						$deleted = FALSE;
				}
			}

			delete_post_meta( $attachment->ID, '_wp_attachment_backup_sizes' );
		}

		if ( is_multisite() && is_string( $file ) && ! empty( $file ) )
			clean_dirsize_cache( $file );

		clean_post_cache( $attachment );

		return $deleted;
	}

	public static function getAttachmentFileSize( $attachment_id, $format = FALSE, $template = NULL, $fallback = '' )
	{
		if ( ! $filesize = Core\File::size( get_attached_file( $attachment_id ) ) )
			return $fallback;

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

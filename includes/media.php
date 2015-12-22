<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkMedia extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;
	protected $ajax       = TRUE;

	private $posttype_sizes = array();

	protected function setup_actions()
	{
		add_filter( 'upload_mimes', array( $this, 'upload_mimes' ) );

		if ( is_admin() ) {

			// based on: http://wordpress.org/plugins/media-item-url/
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10 );
			add_filter( 'media_row_actions', array( $this, 'media_row_actions' ), 50, 3 );

		} else {

			add_filter( 'single_post_title', array( $this, 'single_post_title' ), 9, 2 );
		}

		if ( GNETWORK_MEDIA_DISABLE_META )
			add_filter( 'wp_read_image_metadata', '__return_empty_array', 12, 3 );

		if ( GNETWORK_MEDIA_OBJECT_SIZES ) {
			// http://wordpress.stackexchange.com/a/36196
			add_filter( 'intermediate_image_sizes', '__return_empty_array', 99 );
			// add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 99 );
			add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_generate_attachment_metadata' ), 10, 2 );
		}

		// THIS IS CREAZY!!
		if ( GNETWORK_MEDIA_SEPERATION ) {
			add_filter( 'wp_image_editors', array( $this, 'wp_image_editors' ), 5, 1 );
			add_filter( 'image_downsize', array( $this, 'image_downsize' ), 5, 3 );
		}
	}

	// -- append attachment to a post
	// https://github.com/syamilmj/Aqua-Resizer/blob/master/aq_resizer.php
	public function wp_generate_attachment_metadata( $metadata, $attachment_id )
	{
		if ( ! isset( $metadata['file'] ) )
			return $metadata;

		if ( isset( $metadata['sizes'] ) && count( $metadata['sizes'] ) )
			return $metadata;

		$parent_type = apply_filters( 'gnetwork_media_object_sizes_parent', NULL, $attachment_id, $metadata );

		if ( FALSE === $parent_type ) {
			return $metadata;

		} else if ( is_null( $parent_type ) ) {

			$parent = get_post( wp_get_post_parent_id( $attachment_id ) );
			if ( ! $parent )
				return $metadata;

			$parent_type = $parent->post_type;
		}

		$sizes = $this->get_sizes( $parent_type );

		if ( ! count( $sizes ) )
			return $metadata;

		$uploads = wp_upload_dir();
		$editor  = wp_get_image_editor( path_join( $uploads['basedir'], $metadata['file'] ) );

		if ( ! is_wp_error( $editor ) )
			$metadata['sizes'] = $editor->multi_resize( $sizes );

		if ( self::isDev() )
			error_log( print_r( compact( 'parent_type', 'sizes', 'metadata', 'uploads' ), TRUE ) );

		return $metadata;
	}

	private function get_sizes( $post_type = 'post', $key = 'post_type' )
	{
		if ( isset( $this->posttype_sizes[$post_type] ) )
			return $this->posttype_sizes[$post_type];

		global $_wp_additional_image_sizes;

		$sizes = array();

		foreach ( $_wp_additional_image_sizes as $name => $size )
			if ( isset( $size[$key] ) && in_array( $post_type, $size[$key] ) )
				$sizes[$name] = $size;
			else if ( 'post' == $post_type ) // fallback
				$sizes[$name] = $size;

		$this->posttype_sizes[$post_type] = $sizes;

		return $sizes;
	}

	// FIXME: DEPRECATED
	// this must be wp core future!!
	// core duplication with post_type : add_image_size()
	public static function addImageSize( $name, $width = 0, $height = 0, $crop = FALSE, $post_type = array( 'post' ) )
	{
		global $_wp_additional_image_sizes;

		$_wp_additional_image_sizes[$name] = array(
			'width'     => absint( $width ),
			'height'    => absint( $height ),
			'crop'      => $crop,
			'post_type' => $post_type,
		);
	}

	public static function registerImageSize( $name, $atts = array() )
	{
		global $_wp_additional_image_sizes;

		$args = self::atts( array(
			'n' => _x( 'Undefined Image Size', 'Media Module', GNETWORK_TEXTDOMAIN ),
			'w' => 0,
			'h' => 0,
			'c' => 0,
			'p' => array( 'post' ),
		), $atts );

		$_wp_additional_image_sizes[$name] = array(
			'width'     => absint( $args['w'] ),
			'height'    => absint( $args['h'] ),
			'crop'      => $args['c'],
			'post_type' => $args['p'],
			'title'     => $args['n'],
		);
	}

	public function image_downsize( $false, $post_id, $size )
	{
		if ( $data = image_get_intermediate_size( $post_id, $size ) ) {

			$upload_dir = wp_upload_dir();
			$img_url    = wp_get_attachment_url( $post_id );
			$img_url    = str_replace( wp_basename( $img_url ), $data['file'], $img_url );

			if ( GNETWORK_MEDIA_SIZES_CHECK && file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $img_url ) ) )
				return $false;

			$result = array(
				str_replace( $upload_dir['baseurl'], trailingslashit( GNETWORK_MEDIA_SIZES_URL ).get_current_blog_id(), $img_url ),
				$data['width'],
				$data['height'],
				TRUE,
			);

			// if ( self::isDev() )
			// 	error_log( print_r( compact( 'size', 'data', 'path', 'img_url', 'result', 'upload_dir' ), TRUE ) );

			return $result;
		}

		return $false;
	}

	public static function getSizesDestPath( $file )
	{
		$upload_dir = wp_upload_dir();
		$info       = pathinfo( $file );
		$folder     = str_replace( $upload_dir['basedir'], '', $info['dirname'] );
		$path       = path_join( GNETWORK_MEDIA_SIZES_DIR, get_current_blog_id() ).$folder;

		if ( self::isDev() )
			error_log( print_r( compact( 'info', 'upload_dir', 'folder', 'path' ), TRUE ) );

		if ( wp_mkdir_p( $path ) )
			return $path;

		return NULL;
	}

	public function wp_image_editors( $implementations )
	{
		require_once ABSPATH.WPINC.'/class-wp-image-editor.php';
		require_once ABSPATH.WPINC.'/class-wp-image-editor-gd.php';
		require_once ABSPATH.WPINC.'/class-wp-image-editor-imagick.php';

		require_once GNETWORK_DIR.'includes/media-editor-gd.php';
		require_once GNETWORK_DIR.'includes/media-editor-imagick.php';

		return array(
			'gNetwork_Image_Editor_Imagick',
			'gNetwork_Image_Editor_GD',
		);
	}

	public static function get_thumbs( $attachment_id )
	{
		$thumbs = array();

		if ( $file = get_post_meta( $attachment_id, '_wp_attached_file', TRUE ) ) { // '2015/05/filename.jpg'

			$wpupload = wp_upload_dir();
			$filename = wp_basename( $file );
			$filetype = wp_check_filetype( $filename );
			// $filepath = wp_normalize_path( str_replace( $filename, '', $file ) );
			$filepath = dirname( $file );

			$pattern_gn = path_join( GNETWORK_MEDIA_SIZES_DIR, get_current_blog_id() ).'/'.path_join( $filepath, wp_basename( $file, '.'.$filetype['ext'] ) ).'-[0-9]*x[0-9]*.'.$filetype['ext'];
			$pattern_wp = $wpupload['basedir'].'/'.path_join( $filepath, wp_basename( $file, '.'.$filetype['ext'] ) ).'-[0-9]*x[0-9]*.'.$filetype['ext'];

			$thumbs_gn = glob( $pattern_gn );
			if ( is_array( $thumbs_gn ) && count( $thumbs_gn ) )
				$thumbs += $thumbs_gn;

			$thumbs_wp = glob( $pattern_wp );
			if ( is_array( $thumbs_wp ) && count( $thumbs_wp ) )
				$thumbs += $thumbs_wp;
		}

		return $thumbs;
	}

	// -- 'delete_attachment' // to remove the thumbs
	public static function clean_attachment( $attachment_id )
	{
		$meta = wp_get_attachment_metadata( $attachment_id );
		$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', TRUE );
		$file = get_attached_file( $attachment_id );
		$thumbs = self::get_thumbs( $attachment_id );

		// Update attachment file path based on attachment ID.
		// update_attached_file( $attachment_id, $file )

		// _wp_relative_upload_path()

		gnetwork_dump( $meta );
		gnetwork_dump( $backup_sizes );
		gnetwork_dump( $file );
		gnetwork_dump( $thumbs );
	}

	/*
	-- https://wordpress.org/plugins/force-regenerate-thumbnails/
	-- see: wp_delete_attachment()
	**/

	// FIXME: after unattachment we must delete all thumbs / also: after attach must regenerate all sizes for the post_type
	// JUST A COPY : http://ahmadassaf.com/blog/web-development/wordpress/how-to-delete-attachments-assigned-to-wordpress-post-when-deleted/
	function delete_posts_before_delete_post($id){
		$subposts = get_children(array(
			'post_parent' => $id,
			'post_type'   => 'any',
			'numberposts' => -1,
			'post_status' => 'any'
			));

		if (is_array($subposts) && count($subposts) > 0){
			$uploadpath = wp_upload_dir();

			foreach($subposts as $subpost){

				$_wp_attached_file = get_post_meta($subpost->ID, '_wp_attached_file', TRUE );

				$original = basename($_wp_attached_file);
				$pos = strpos(strrev($original), '.');
				if (strpos($original, '.') !== FALSE){
					$ext = explode('.', strrev($original));
					$ext = strrev($ext[0]);
				} else {
					$ext = explode('-', strrev($original));
					$ext = strrev($ext[0]);
				}

				$pattern  = $uploadpath['basedir'].'/'.dirname($_wp_attached_file).'/'.basename( $original, '.'.$ext).'-[0-9]*x[0-9]*.'.$ext;
				$original = $uploadpath['basedir'].'/'.dirname($_wp_attached_file).'/'.basename( $original, '.'.$ext).'.'.$ext;

				if (getimagesize($original)){
					$thumbs = glob($pattern);
					if (is_array($thumbs) && count($thumbs) > 0){
						foreach($thumbs as $thumb)
							unlink($thumb);
					}
				}

				wp_delete_attachment( $subpost->ID, TRUE );
			}
		}
	} // add_action('before_delete_post', 'delete_posts_before_delete_post');

	public function single_post_title( $post_title, $post )
	{
		if ( 'attachment' == $post->post_type ) {
			if ( $alt = get_post_meta( $post->ID, '_wp_attachment_image_alt', TRUE ) )
				return $alt;

			if ( $post->post_excerpt )
				return wp_trim_words( $post->post_excerpt, 7, ' '.'[&hellip;]' );
		}

		return $post_title;
	}

	// UNFINISHED
	// https://kovshenin.com/2012/native-image-sizing-on-the-fly-with-wordpress/
	// https://gist.github.com/kovshenin/1984363
	/**
	 * Image shortcode callback
	 *
	 * Enables the [kovshenin_image] shortcode, pseudo-TimThumb but creates resized and cropped image files
	 * from existing media library entries. Usage:
	 * [kovshenin_image src="http://example.org/wp-content/uploads/2012/03/image.png" width="100" height="100"]
	 *
	 * @uses image_make_intermediate_size
	 */
	public function shortcode_children( $atts, $content = NULL, $tag = '' )
	{
		global $wpdb;

		$args = shortcode_atts( array(
			'src'    => '',
			'width'  => '',
			'height' => '',
		), $atts, $tag );

		if ( empty( $args['src'] ) )
			return $content;

		// Sanitize
		$height = absint( $height );
		$width = absint( $width );
		$src = esc_url( strtolower( $src ) );
		$needs_resize = TRUE;

		$upload_dir = wp_upload_dir();
		$base_url = strtolower( $upload_dir['baseurl'] );

		// Let's see if the image belongs to our uploads directory.
		if ( substr( $src, 0, strlen( $base_url ) ) != $base_url ) {
			return "Error: external images are not supported.";
		}

		// Look the file up in the database.
		$file = str_replace( trailingslashit( $base_url ), '', $src );
		$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s LIMIT 1;", '%"' . like_escape( $file ) . '"%' ) );

		// If an attachment record was not found.
		if ( ! $attachment_id ) {
			return "Error: attachment not found.";
		}

		// Look through the attachment meta data for an image that fits our size.
		$meta = wp_get_attachment_metadata( $attachment_id );
		foreach ( $meta['sizes'] as $key => $size ) {
			if ( $size['width'] == $width && $size['height'] == $height ) {
				$src = str_replace( basename( $src ), $size['file'], $src );
				$needs_resize = false;
				break;
			}
		}

		// If an image of such size was not found, we can create one.
		if ( $needs_resize ) {
			$attached_file = get_attached_file( $attachment_id );
			$resized = image_make_intermediate_size( $attached_file, $width, $height, TRUE );
			if ( ! is_wp_error( $resized ) ) {

				// Let metadata know about our new size.
				$key = sprintf( 'resized-%dx%d', $width, $height );
				$meta['sizes'][$key] = $resized;
				$src = str_replace( basename( $src ), $resized['file'], $src );
				wp_update_attachment_metadata( $attachment_id, $meta );

				// Record in backup sizes so everything's cleaned up when attachment is deleted.
				$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', TRUE );
				if ( ! is_array( $backup_sizes ) ) $backup_sizes = array();
				$backup_sizes[$key] = $resized;
				update_post_meta( $attachment_id, '_wp_attachment_backup_sizes', $backup_sizes );
			}
		}

		// Generate the markup and return.
		$width = ( $width ) ? 'width="' . absint( $width ) . '"' : '';
		$height = ( $height ) ? 'height="' . absint( $height ) . '"' : '';
		return sprintf( '<img src="%s" %s %s />', esc_url( $src ), $width, $height );
	}

	public function admin_enqueue_scripts( $hook )
	{
		if ( $hook == 'upload.php' )
			wp_enqueue_script( 'gnetwork-media', GNETWORK_URL.'assets/js/admin.media.min.js', array( 'jquery' ), GNETWORK_VERSION, TRUE );
	}

	public function media_row_actions( $actions, $post, $detached )
	{

		$media_url = wp_get_attachment_url( $post->ID );
		$media_label = self::get_media_type_label( $post->ID );

		$media_link = '<a class="media-url-click" href="'.esc_url( $media_url ).'">'.esc_html( $media_label ).'</a>';

		$media_link .= '<div class="media-url-box">';
		$media_link .= '<input type="text" class="widefat media-url-field" value="'.esc_url( $media_url ).'" readonly>';
		$media_link .= '</div>';

		$actions['media-url'] = $media_link;

		return $actions;
	}

	public static function get_media_type_label( $post_id )
	{
		$type = get_post_mime_type( $post_id );

		switch ( $type ) {

			case 'image/jpeg' :
			case 'image/png' :
			case 'image/gif' :
				$label = _x( 'View Image URL', 'Media Module', GNETWORK_TEXTDOMAIN );
				break;

			case 'video/mpeg' :
			case 'video/mp4' :
			case 'video/webm' :
			case 'video/ogg' :
			case 'video/quicktime':
				$label = _x( 'View Video URL', 'Media Module', GNETWORK_TEXTDOMAIN );
				break;

			case 'text/csv' :
			case 'text/xml' :
				$label = _x( 'View Data File URL', 'Media Module', GNETWORK_TEXTDOMAIN );
				break;

			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' :
			case 'application/vnd.ms-excel' :
				$label = _x( 'View Spreadsheet URL', 'Media Module', GNETWORK_TEXTDOMAIN );
				break;

			case 'application/pdf' :
			case 'application/rtf' :
			case 'application/msword' :
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' :
				$label = _x( 'View Document URL', 'Media Module', GNETWORK_TEXTDOMAIN );
				break;

			case 'text/html' :
				$label = _x( 'View HTML file URL', 'Media Module', GNETWORK_TEXTDOMAIN );
				break;

			default:
				$label = _x( 'View Item URL', 'Media Module', GNETWORK_TEXTDOMAIN );
		}

		return apply_filters( 'gnetwork_media_type_label', $label, $type, $post_id );
	}

	public function upload_mimes( $mimes )
	{
		return array_merge( $mimes, array(
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'ppt'  => 'application/vnd.ms-powerpoint',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'  => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'csv'  => 'text/csv',
			'xml'  => 'text/xml',
			'webm' => 'video/webm',
			'flv'  => 'video/x-flv',
			'ac3'  => 'audio/ac3',
			'mpa'  => 'audio/MPA',
			'mp4'  => 'video/mp4',
			'mpg4' => 'video/mp4',
			'flv'  => 'video/x-flv',
			'svg'  => 'image/svg+xml',
		) );
	}
}

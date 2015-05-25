<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkMedia extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = false;
	var $_ajax       = true;

	public function setup_actions()
	{
		add_filter( 'upload_mimes', array( &$this, 'upload_mimes' ) );

		// based on: http://wordpress.org/plugins/media-item-url/
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ), 10 );
		add_filter( 'media_row_actions', array( &$this, 'media_row_actions' ), 50, 3 );


		// THIS IS CREAZY!!
		if ( GNETWORK_MEDIA_SEPERATION ) {
			add_filter( 'image_downsize', array( &$this, 'image_downsize' ), 5, 3 );
			add_filter( 'wp_image_editors', array( &$this, 'wp_image_editors' ), 5, 1 );
		}
	}

	public function image_downsize( $false, $post_id, $size )
	{
		if ( $data = image_get_intermediate_size( $post_id, $size ) ) {

			$upload_dir = wp_upload_dir();
			$img_url = wp_get_attachment_url( $post_id );
			$img_url = str_replace( wp_basename( $img_url ), $data['file'], $img_url );

			//error_log( print_r( compact( 'data', 'path', 'upload_dir' ), true ) );

			if ( GNETWORK_MEDIA_SIZES_CHECK && isset( $data['path'] ) && file_exists( $upload_dir['basedir'].DS.$data['path'] ) )
				return $false;

			return array(
				str_replace( $upload_dir['baseurl'], trailingslashit( GNETWORK_MEDIA_SIZES_URL ).get_current_blog_id(), $img_url ),
				$data['width'],
				$data['height'],
				true,
			);
		}

		return $false;
	}

	public static function getSizesDestPath( $file )
	{
		$info = pathinfo( $file );
		$upload_dir = wp_upload_dir();
		$folder = str_replace( $upload_dir['basedir'], '', $info['dirname'] );
		$path = path_join( GNETWORK_MEDIA_SIZES_DIR, get_current_blog_id() ).$folder;

		//error_log( print_r( compact( 'info', 'upload_dir', 'folder', 'path' ), true ) );

		if ( wp_mkdir_p( $path ) )
			return $path;

		return null;
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
	public function shortcode_children( $atts, $content = null, $tag = '' )
	{
		global $wpdb;

		$args = shortcode_atts( array(
			'src' => '',
			'width' => '',
			'height' => '',
		), $atts, $tag );

		if ( empty( $args['src'] ) )
			return $content;

		// Sanitize
		$height = absint( $height );
		$width = absint( $width );
		$src = esc_url( strtolower( $src ) );
		$needs_resize = true;

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
		foreach( $meta['sizes'] as $key => $size ) {
			if ( $size['width'] == $width && $size['height'] == $height ) {
				$src = str_replace( basename( $src ), $size['file'], $src );
				$needs_resize = false;
				break;
			}
		}

		// If an image of such size was not found, we can create one.
		if ( $needs_resize ) {
			$attached_file = get_attached_file( $attachment_id );
			$resized = image_make_intermediate_size( $attached_file, $width, $height, true );
			if ( ! is_wp_error( $resized ) ) {

				// Let metadata know about our new size.
				$key = sprintf( 'resized-%dx%d', $width, $height );
				$meta['sizes'][$key] = $resized;
				$src = str_replace( basename( $src ), $resized['file'], $src );
				wp_update_attachment_metadata( $attachment_id, $meta );

				// Record in backup sizes so everything's cleaned up when attachment is deleted.
				$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
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
		if ( $hook !== 'upload.php' )
			return;

		wp_enqueue_script( 'gmember-media', GNETWORK_URL.'assets/js/admin.media.js' , array( 'jquery' ), GNETWORK_VERSION, true );
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
		// fetch our item MIME type
		$type = get_post_mime_type( $post_id );


		// filter through my types and return the label based on that
		switch ( $type ) {
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
				$label	= __( 'View Image URL', GNETWORK_TEXTDOMAIN );
				break;

			case 'video/mpeg':
			case 'video/mp4':
			case 'video/webm':
			case 'video/ogg':
			case 'video/quicktime':
				$label	= __( 'View Video URL', GNETWORK_TEXTDOMAIN );
				break;

			case 'text/csv':
			case 'text/xml':
				$label	= __( 'View Data File URL', GNETWORK_TEXTDOMAIN );
				break;

			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
			case 'application/vnd.ms-excel':
				$label	= __( 'View Spreadsheet URL', GNETWORK_TEXTDOMAIN );
				break;

			case 'application/pdf':
			case 'application/rtf':
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				$label	= __( 'View Document URL', GNETWORK_TEXTDOMAIN );
				break;

			case 'text/html':
				$label	= __( 'View HTML file URL', GNETWORK_TEXTDOMAIN );
				break;

			default:
				$label	= __( 'View Item URL', GNETWORK_TEXTDOMAIN );
		}

		// pass through filter to catch whatever else may be out there
		//$label = apply_filters( 'gnetwork_media_type_label', $label, $type );

		// send it back
		return $label;

	}

	// http://codex.wordpress.org/Uploading_Files
	// http://wordpress.org/plugins/allow-wordpowerpoint-file-uploads/
	// http://plugins.svn.wordpress.org/allow-wordpowerpoint-file-uploads/trunk/allow-word-powerpoint-file-uploads.php
	public function upload_mimes ( $mimes )
	{
		return array_merge( $mimes, array(
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'ppt'  => 'application/vnd.ms-powerpoint',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'  => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

			'csv' => 'text/csv',
			'xml' => 'text/xml',

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

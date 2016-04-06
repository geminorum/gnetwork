<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkMedia extends gNetworkModuleCore
{

	protected $menu_key = 'media';
	protected $network  = FALSE;
	protected $ajax     = TRUE;

	private $posttype_sizes = array();

	protected function setup_actions()
	{
		add_filter( 'upload_mimes', array( $this, 'upload_mimes' ) );

		if ( is_admin() ) {

			if ( GNETWORK_MEDIA_OBJECT_SIZES )
				$this->register_menu( 'media',
					_x( 'Media', 'Media Module: Menu Name', GNETWORK_TEXTDOMAIN ),
					array( $this, 'settings' )
				);

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
			// FIXME: also 'delete_attachment' // to remove the thumbs
		}
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

			$this->check_referer( $sub );

			if ( isset( $_POST['clean_attachments'], $_POST['_cb'] ) ) {

				$count = 0;

				foreach ( $_POST['_cb'] as $post_id )
					if ( $this->clean_attachments( $post_id ) )
						$count++;

			} else {
				self::redirect_referer( array(
					'message' => 'wrong',
					'limit'   => self::limit(),
					'paged'   => self::paged(),
				) );
			}

			self::redirect_referer( array(
				'message' => 'cleaned',
				'count'   => $count,
				'limit'   => self::limit(),
				'paged'   => self::paged(),
			) );
		}
	}

	public function settings_html( $uri, $sub = 'general' )
	{
		echo '<form class="gnetwork-form" method="post" action="">';

			$this->settings_fields( $sub, 'bulk' );

			if ( self::tablePostInfo() )
				$this->settings_buttons( $sub );

		echo '</form>';
	}

	protected function register_settings_buttons()
	{
		$this->register_button( 'clean_attachments', _x( 'Clean Attachments', 'Media Module', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
	}

	protected static function getPostArray()
	{
		$limit  = self::limit();
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$args = array(
			'posts_per_page'   => $limit,
			'offset'           => $offset,
			'orderby'          => self::orderby( 'ID' ),
			'order'            => self::order( 'asc' ),
			'post_type'        => 'any',
			'post_status'      => array( 'publish', 'future', 'draft', 'pending' ),
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		);

		$query = new WP_Query;
		$posts = $query->query( $args );

		$pagination = array(
			'total'    => $query->found_posts,
			'pages'    => $query->max_num_pages,
			'limit'    => $limit,
			'paged'    => $paged,
			'next'     => FALSE,
			'previous' => FALSE,
		);

		if ( $pagination['pages'] > 1 ) {
			if ( $paged != 1 )
				$pagination['previous'] = $paged - 1;

			if ( $paged != $pagination['pages'] )
				$pagination['next'] = $paged + 1;
		}

		return array( $posts, $pagination );
	}

	private static function tablePostInfo()
	{
		list( $posts, $pagination ) = self::getPostArray();

		return self::tableList( array(
			'_cb' => 'ID',
			'ID'  => _x( 'ID', 'Media Module', GNETWORK_TEXTDOMAIN ),

			'date' => array(
				'title'    => _x( 'Date', 'Media Module', GNETWORK_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					return date_i18n( 'j M Y', strtotime( $row->post_date ) );
				},
			),

			'type' => array(
				'title' => _x( 'Type', 'Media Module', GNETWORK_TEXTDOMAIN ),
				'args'  => array(
					'post_types' => self::getPostTypes( 'singular_name' ),
				),
				'callback' => function( $value, $row, $column, $index ){
					return isset( $column['args']['post_types'][$row->post_type] ) ? $column['args']['post_types'][$row->post_type] : $row->post_type;
				},
			),

			'post' => array(
				'title' => _x( 'Post', 'Media Module', GNETWORK_TEXTDOMAIN ),
				'args'  => array(
					'url'   => get_bloginfo( 'url' ),
					'admin' => admin_url( 'post.php' ),
				),
				'callback' => function( $value, $row, $column, $index ){

					$edit = add_query_arg( array(
						'action' => 'edit',
						'post'   => $row->ID,
					), $column['args']['admin'] );

					$view = add_query_arg( array(
						'p' => $row->ID,
					), $column['args']['url'] );

					$terms = get_the_term_list( $row->ID, 'post_tag', '<br />', ', ', '' );
					return $row->post_title.' <small>( <a href="'.$edit.'" target="_blank">Edit</a> | <a href="'.$view.'" target="_blank">View</a> )</small><br /><small>'.$terms.'</small>';
				},
			),

			'media' => array(
				'title' => _x( 'Media', 'Media Module', GNETWORK_TEXTDOMAIN ),
				'args'  => array(
					'wpuploads' => wp_upload_dir(),
				),
				'callback' => function( $value, $row, $column, $index ){
					$links = array();

					foreach ( gNetworkMedia::getAttachments( $row->ID ) as $attachment ) {
						$attached = get_post_meta( $attachment->ID, '_wp_attached_file', TRUE );
						$links[] = '<a target="_blank" href="'.$column['args']['wpuploads']['baseurl'].'/'.$attached.'">'.$attached.'</a>';
					}

					return count( $links ) ? ( '<div dir="ltr">'.implode( '<br />', $links ).'</div>' ) : '';
				},
			),
		), $posts, array(
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => self::html( 'h3', _x( 'Overview of posts with attachments', 'Media Module', GNETWORK_TEXTDOMAIN ) ),
			'empty'      => self::warning( _x( 'No Posts!', 'Media Module', GNETWORK_TEXTDOMAIN ) ),
			'pagination' => $pagination,
		) );
	}

	// FIXME: also must run on: appending attachment to a post
	// @SEE: https://github.com/syamilmj/Aqua-Resizer/blob/master/aq_resizer.php
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

		$wpupload = wp_upload_dir();
		$editor   = wp_get_image_editor( path_join( $wpupload['basedir'], $metadata['file'] ) );

		if ( ! is_wp_error( $editor ) )
			$metadata['sizes'] = $editor->multi_resize( $sizes );

		if ( self::isDev() )
			error_log( print_r( compact( 'parent_type', 'sizes', 'metadata', 'wpupload' ), TRUE ) );

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
			else if ( ! isset( $size[$key] ) && 'post' == $post_type )
				$sizes[$name] = $size;

		$this->posttype_sizes[$post_type] = $sizes;

		return $sizes;
	}

	// DEPRECATED: core duplication with post_type : add_image_size()
	public static function addImageSize( $name, $width = 0, $height = 0, $crop = FALSE, $post_type = array( 'post' ) )
	{
		self::__dep();

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

			$wpupload = wp_upload_dir();
			$img_url  = wp_get_attachment_url( $post_id );
			$img_url  = str_replace( wp_basename( $img_url ), $data['file'], $img_url );

			if ( GNETWORK_MEDIA_SIZES_CHECK && file_exists( str_replace( $wpupload['baseurl'], $wpupload['basedir'], $img_url ) ) )
				return $false;

			$result = array(
				str_replace( $wpupload['baseurl'], trailingslashit( GNETWORK_MEDIA_SIZES_URL ).get_current_blog_id(), $img_url ),
				$data['width'],
				$data['height'],
				TRUE,
			);

			if ( self::isDev() )
				error_log( print_r( compact( 'size', 'data', 'path', 'img_url', 'result', 'wpupload' ), TRUE ) );

			return $result;
		}

		return $false;
	}

	public static function getSizesDestPath( $file )
	{
		$wpupload = wp_upload_dir();
		$info     = pathinfo( $file );
		$folder   = str_replace( $wpupload['basedir'], '', $info['dirname'] );
		$path     = path_join( GNETWORK_MEDIA_SIZES_DIR, get_current_blog_id() ).$folder;

		if ( self::isDev() )
			error_log( print_r( compact( 'info', 'wpupload', 'folder', 'path' ), TRUE ) );

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

	public function get_thumbs( $attachment_id )
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

	public function url_thumbs( $thumbs, $wpupload )
	{
		$urls = array();

		foreach ( $thumbs as $thumb )
			$urls[] = str_replace( $wpupload['basedir'], $wpupload['baseurl'], wp_normalize_path( $thumb ) );

		return $urls;
	}

	public function delete_thumbs( $thumbs )
	{
		$count = 0;

		foreach ( $thumbs as $thumb )
			if ( @unlink( wp_normalize_path( $thumb ) ) )
				$count++;

		return $count;
	}

	// FIXME: WORKING DRAFT: not used yet!
	public function reset_meta_sizes( $attachment_id )
	{
		$meta = wp_get_attachment_metadata( $attachment_id );

		if ( ! isset( $meta['sizes'] ) )
			return TRUE;

		$meta['sizes'] = array();
		// FIXME: remove EXIF too!

		delete_post_meta( $attachment_id, '_wp_attachment_backup_sizes' );
		update_post_meta( $attachment_id, '_wp_attachment_metadata', $meta );

		return TRUE;
	}

	public static function getAttachments( $post_id )
	{
		return get_children( array(
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'any',
			'numberposts'    => -1,
		) );
	}

	public function clean_attachments( $post_id )
	{
		global $wpdb;

		$clean = $moved = array();

		foreach ( self::getAttachments( $post_id ) as $attachment ) {

			// self::dump( $attachment );
			// self::dump( get_post_meta( $attachment->ID ) );
			// self::dump( get_post_custom( $attachment->ID ) );
			// self::dump( $this->get_thumbs( $attachment->ID ) );

			if ( $attached_file = get_post_meta( $attachment->ID, '_wp_attached_file', TRUE ) ) {
				if ( ! str_replace( wp_basename( $attached_file ), '', $attached_file ) ) {
					$clean[$attachment->ID] = $attached_file;
				}
			}
		}

		if ( ! count( $clean ) )
			return FALSE;

		$post = get_post( $post_id );
		$wpupload = wp_upload_dir( ( substr( $post->post_date, 0, 4 ) > 0 ? $post->post_date : NULL ) );

		preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post->post_content, $matches );

		foreach ( $clean as $clean_id => $clean_file ) {

			// $clean_upload = media_sideload_image( $wpupload['baseurl'].'/'.$clean_file, $post_id, NULL, 'src' );

			$clean_path = path_join( $wpupload['basedir'], $clean_file );
			$moved_path = path_join( $wpupload['path'], $clean_file );

			if ( file_exists( $clean_path ) && @rename( $clean_path, $moved_path ) ) {

				$thumbs_path = $this->get_thumbs( $clean_id );
				$thumbs_url = $this->url_thumbs( $thumbs_path, $wpupload );

				$thumbs_url[] = $wpupload['baseurl'].'/'.$clean_file; // also the original

				foreach ( $thumbs_url as $thumb_url ) {
					foreach ( $matches[1] as $offset => $url ) {
						if ( $thumb_url == $url ) {
							$wpdb->query( $wpdb->prepare( "
								UPDATE $wpdb->posts SET post_content = REPLACE(post_content, '%s', '%s') WHERE ID = %d
							", $url, ( $wpupload['url'].'/'.wp_basename( $url ) ), $post_id ) );
						}
					}
				}

				$this->delete_thumbs( $thumbs_path );

				$meta = wp_generate_attachment_metadata( $clean_id, $moved_path );
				wp_update_attachment_metadata( $clean_id, $meta );

				$wpdb->query( $wpdb->prepare( "
					UPDATE $wpdb->posts SET guid = %s WHERE ID = %d
				", esc_url_raw( $wpupload['url'].'/'.$clean_file ), $clean_id ) );

				update_attached_file( $clean_id, $moved_path );

				$moved[$clean_id] = $wpupload['subdir'].'/'.$clean_file;
			}
		}

		return count( $moved );
	}

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

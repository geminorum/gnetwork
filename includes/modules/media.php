<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Media extends ModuleCore
{

	protected $key     = 'media';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	private $posttype_sizes = array();

	protected function setup_actions()
	{
		add_action( 'init', array( $this, 'init_late' ), 999 );
		add_filter( 'upload_mimes', array( $this, 'upload_mimes' ) );
		// add_filter( 'sanitize_file_name', array( $this, 'sanitize_file_name' ), 12, 2 );

		if ( is_admin() ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_filter( 'media_row_actions', array( $this, 'media_row_actions' ), 50, 3 );
			add_action( 'admin_action_bulk_clean_attachments', array( $this, 'admin_action_bulk' ) );
			add_action( 'admin_action_-1', array( $this, 'admin_action_bulk' ) );

		} else {

			add_filter( 'single_post_title', array( $this, 'single_post_title' ), 9, 2 );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Media', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function init_late()
	{
		if ( $this->filters( 'disable_meta', GNETWORK_MEDIA_DISABLE_META, $this->blog ) ) {

			add_filter( 'wp_read_image_metadata', '__return_empty_array', 12, 4 );
		// } else {
		// 	add_filter( 'wp_read_image_metadata', 'wp_read_image_metadata', 12, 4 );
		}

		if ( $this->filters( 'object_sizes', GNETWORK_MEDIA_OBJECT_SIZES, $this->blog ) ) {

			add_filter( 'intermediate_image_sizes', '__return_empty_array', 99 );
			// add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 99 );
			add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_generate_attachment_metadata' ), 10, 2 );
			add_action( 'clean_attachment_cache', array( $this, 'clean_attachment_cache' ), 10, 1 );
		}

		if ( $this->filters( 'thumbs_separation', GNETWORK_MEDIA_THUMBS_SEPARATION, $this->blog ) ) {

			add_filter( 'wp_image_editors', array( $this, 'wp_image_editors' ), 5, 1 );
			add_filter( 'image_downsize', array( $this, 'image_downsize' ), 5, 3 );
			add_action( 'delete_attachment', array( $this, 'delete_attachment' ), 10, 1 );
		}
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

			$this->check_referer( $sub );

			if ( isset( $_POST['clean_attachments'], $_POST['_cb'] ) ) {

				$count = 0;

				foreach ( $_POST['_cb'] as $post_id )

					if ( wp_attachment_is_image( $post_id )
						&& $this->clean_attachment( $post_id ) )
							$count++;

					else if ( $this->clean_attachments( $post_id ) )
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

		} else {
			parent::settings_actions( $sub );
		}
	}

	public function settings_form( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub, 'bulk' );

			if ( self::tablePostInfo() )
				$this->settings_buttons( $sub );

			// TODO: add clean all attachments button, hence : regenerate-thumbnails

		$this->settings_form_after( $uri, $sub );
	}

	protected function register_settings_buttons()
	{
		$this->register_button( 'clean_attachments', _x( 'Clean Attachments', 'Modules: Media', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
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

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $_REQUEST['type'];

		if ( 'attachment' == $args['post_type'] )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = array(
			'total'    => $query->found_posts,
			'pages'    => $query->max_num_pages,
			'limit'    => $limit,
			'paged'    => $paged,
			'all'      => FALSE,
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

		$wpuploads = wp_get_upload_dir();

		return HTML::tableList( array(
			'_cb' => 'ID',
			'ID'  => _x( 'ID', 'Modules: Media', GNETWORK_TEXTDOMAIN ),

			'date' => array(
				'title'    => _x( 'Date', 'Modules: Media', GNETWORK_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					return Utilities::humanTimeDiffRound( strtotime( $row->post_date ) );
				},
			),

			'type' => array(
				'title' => _x( 'Type', 'Modules: Media', GNETWORK_TEXTDOMAIN ),
				'args'  => array(
					'post_types' => WordPress::getPostTypes( 2 ),
				),
				'callback' => function( $value, $row, $column, $index ){
					return isset( $column['args']['post_types'][$row->post_type] ) ? $column['args']['post_types'][$row->post_type] : $row->post_type;
				},
			),

			'post' => array(
				'title' => _x( 'Post', 'Modules: Media', GNETWORK_TEXTDOMAIN ),
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
				'title' => _x( 'Media', 'Modules: Media', GNETWORK_TEXTDOMAIN ),
				'args'  => array(
					'wpuploads' => $wpuploads,
				),
				'callback' => function( $value, $row, $column, $index ){

					// TODO: check for attachment type & get the parent: use wp icons
					// TODO: list images in the content of the post/parent

					$links = array();

					foreach ( WordPress::getAttachments( $row->ID ) as $attachment ) {
						$attached = get_post_meta( $attachment->ID, '_wp_attached_file', TRUE );
						$links[] = '<a target="_blank" href="'.$column['args']['wpuploads']['baseurl'].'/'.$attached.'">'.$attached.'</a>';
					}

					return count( $links ) ? ( '<div dir="ltr">'.implode( '<br />', $links ).'</div>' ) : '&mdash;';
				},
			),

			'meta' => array(
				'title' => _x( 'Thumbnail', 'Modules: Media', GNETWORK_TEXTDOMAIN ),
				'args'  => array(
					'wpuploads' => $wpuploads,
				),
				'callback' => function( $value, $row, $column, $index ){

					if ( $attachment_id = get_post_meta( $row->ID, '_thumbnail_id', TRUE ) ) {
						$attached = get_post_meta( $attachment_id, '_wp_attached_file', TRUE );
						return '<div dir="ltr"><a target="_blank" href="'.$column['args']['wpuploads']['baseurl'].'/'.$attached.'">'.$attached.'</a></div>';
					}

					return '&mdash;';
				},
			),
		), $posts, array(
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of posts with attachments', 'Modules: Media', GNETWORK_TEXTDOMAIN ) ),
			'empty'      => self::warning( _x( 'No Posts!', 'Modules: Media', GNETWORK_TEXTDOMAIN ) ),
			'pagination' => $pagination,
		) );
	}

	public function clean_attachment_cache( $attachment_id )
	{
		$this->clean_attachment( $attachment_id, TRUE );
	}

	// @SEE: https://github.com/syamilmj/Aqua-Resizer/blob/master/aq_resizer.php
	public function wp_generate_attachment_metadata( $metadata, $attachment_id )
	{
		if ( ! isset( $metadata['file'] ) )
			return $metadata;

		if ( isset( $metadata['sizes'] ) && count( $metadata['sizes'] ) )
			return $metadata;

		if ( $this->attachment_is_custom( $attachment_id ) )
			return $metadata;

		$parent_type = $this->filters( 'object_sizes_parent', NULL, $attachment_id, $metadata );

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

		$wpupload = wp_get_upload_dir();
		$editor   = wp_get_image_editor( path_join( $wpupload['basedir'], $metadata['file'] ) );

		if ( ! self::isError( $editor ) )
			$metadata['sizes'] = $editor->multi_resize( $sizes );

		if ( WordPress::isDev() )
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

	public function attachment_is_custom( $attachment_id )
	{
		if ( get_post_meta( $attachment_id, '_wp_attachment_is_custom_header', TRUE ) )
			return TRUE;

		if ( get_post_meta( $attachment_id, '_wp_attachment_is_custom_background', TRUE ) )
			return TRUE;

		if ( $attachment_id == get_option( 'site_icon' ) )
			return TRUE;

		if ( $attachment_id == get_theme_mod( 'site_logo' ) )
			return TRUE;

		return FALSE;
	}

	// FIXME: DEPRECATED: core duplication with post_type : add_image_size()
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
			'n' => _x( 'Undefined Image Size', 'Modules: Media', GNETWORK_TEXTDOMAIN ),
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

	public function delete_attachment( $attachment_id )
	{
		$this->clean_attachment( $attachment_id, FALSE, TRUE );
	}

	public function image_downsize( $false, $post_id, $size )
	{
		if ( $data = image_get_intermediate_size( $post_id, $size ) ) {

			$wpupload = wp_get_upload_dir();
			$img_url  = wp_get_attachment_url( $post_id );
			$img_url  = str_replace( wp_basename( $img_url ), $data['file'], $img_url );

			if ( GNETWORK_MEDIA_THUMBS_CHECK && file_exists( str_replace( $wpupload['baseurl'], $wpupload['basedir'], $img_url ) ) )
				return $false;

			$result = array(
				str_replace( $wpupload['baseurl'], trailingslashit( GNETWORK_MEDIA_THUMBS_URL ).$this->blog, $img_url ),
				$data['width'],
				$data['height'],
				TRUE,
			);

			if ( WordPress::isDev() )
				error_log( print_r( compact( 'size', 'data', 'path', 'img_url', 'result', 'wpupload' ), TRUE ) );

			return $result;
		}

		return $false;
	}

	public static function getSizesDestPath( $file )
	{
		$wpupload = wp_get_upload_dir();
		$info     = pathinfo( $file );
		$folder   = str_replace( $wpupload['basedir'], '', $info['dirname'] );
		$path     = path_join( GNETWORK_MEDIA_THUMBS_DIR, $this->blog ).$folder;

		if ( WordPress::isDev() )
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

		require_once GNETWORK_DIR.'includes/misc/media-editor-gd.php';
		require_once GNETWORK_DIR.'includes/misc/media-editor-imagick.php';

		return array(
			__NAMESPACE__.'\\Image_Editor_Imagick',
			__NAMESPACE__.'\\Image_Editor_GD',
		);
	}

	// FIXME: ALSO SEE: https://core.trac.wordpress.org/changeset/38113
	public function get_thumbs( $attachment_id )
	{
		$thumbs = array();

		if ( $file = get_post_meta( $attachment_id, '_wp_attached_file', TRUE ) ) { // '2015/05/filename.jpg'

			$wpupload = wp_get_upload_dir();
			$filename = wp_basename( $file );
			$filetype = wp_check_filetype( $filename );
			// $filepath = wp_normalize_path( str_replace( $filename, '', $file ) );
			$filepath = dirname( $file );

			$pattern_gn = path_join( GNETWORK_MEDIA_THUMBS_DIR, $this->blog ).'/'.path_join( $filepath, wp_basename( $file, '.'.$filetype['ext'] ) ).'-[0-9]*x[0-9]*.'.$filetype['ext'];
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

	// FIXME: WORKING DRAFT
	// NOTE: probably no need
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

	public function clean_attachment( $attachment_id, $regenerate = TRUE, $force = FALSE )
	{
		if ( $force || ! $this->attachment_is_custom( $attachment_id ) ) {

			$thumbs = $this->get_thumbs( $attachment_id );
			$delete = $this->delete_thumbs( $thumbs );

			if ( $regenerate ) {
				$file   = get_attached_file( $attachment_id, TRUE );
				$meta   = wp_generate_attachment_metadata( $attachment_id, $file );
				$update = wp_update_attachment_metadata( $attachment_id,$meta );
			}
		}

		if ( WordPress::isDev() )
			error_log( print_r( compact( 'attachment_id', 'thumbs', 'delete', 'file', 'meta', 'update' ), TRUE ) );
	}

	public function clean_attachments( $post_id )
	{
		global $wpdb;

		$clean = $moved = array();

		foreach ( WordPress::getAttachments( $post_id ) as $attachment ) {
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


	public function admin_enqueue_scripts( $hook_suffix )
	{
		if ( 'upload.php' != $hook_suffix )
			return;

		Utilities::enqueueScript( 'admin.media' );

		add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts' ), 99 );
	}

	public function admin_print_scripts()
	{
?><script type="text/javascript">
	jQuery(document).ready(function($){
		$('select[name^="action"] option:last-child').before('<option value="bulk_clean_attachments"><?php echo esc_attr_x( 'Clean Attachments', 'Modules: Media: Bulk Action', GNETWORK_TEXTDOMAIN ); ?></option>');
	});
</script><?php
	}

	public function admin_action_bulk()
	{
		if ( empty( $_REQUEST['action'] )
			|| ( 'bulk_clean_attachments' != $_REQUEST['action']
				&& 'bulk_clean_attachments' != $_REQUEST['action2'] ) )
					return;

		if ( empty( $_REQUEST['media'] )
			|| ! is_array( $_REQUEST['media'] ) )
				return;

		check_admin_referer( 'bulk-media' );

		self::redirect( $this->get_settings_url( array(
			'action' => 'clean',
			'type'   => 'attachment',
			'id'     => maybe_serialize( implode( ',', array_map( 'intval', $_REQUEST['media'] ) ) ),
		), TRUE ) );
	}

	public function media_row_actions( $actions, $post, $detached )
	{
		$url = wp_get_attachment_url( $post->ID );

		if ( wp_attachment_is( 'image', $post->ID ) )
			$actions['media-clean'] = HTML::tag( 'a', array(
				'target' => '_blank',
				'class'  => 'media-clean-attachment',
				'href'   => $this->get_settings_url( array(
					'action' => 'clean',
					'type'   => 'attachment',
					'id'     => $post->ID,
				) ),
				'data' => array(
					'id'     => $post->ID,
					'action' => 'clean_attachment',
				),
			), _x( 'Clean', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) );

		$link = HTML::tag( 'a', array(
			'target' => '_blank',
			'class'  => 'media-url-click media-url-attachment',
			'href'   => $url,
			'data'   => array(
				'id'     => $post->ID,
				'action' => 'get_url',
			),
		), $this->get_media_type_label( $post->ID ) );

		$link .= '<div class="media-url-box"><input type="text" class="widefat media-url-field" value="'.esc_url( $url ).'" readonly></div>';

		$actions['media-url'] = $link;

		return $actions;
	}

	protected function get_media_type_label( $post_id, $mime_type = NULL )
	{
		if ( is_null( $mime_type ) )
			$mime_type = get_post_mime_type( $post_id );

		switch ( $mime_type ) {

			case 'image/jpeg' :
			case 'image/png' :
			case 'image/gif' :
				$label = _x( 'View Image URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );
				break;

			case 'video/mpeg' :
			case 'video/mp4' :
			case 'video/webm' :
			case 'video/ogg' :
			case 'video/quicktime':
				$label = _x( 'View Video URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );
				break;

			case 'text/csv' :
			case 'text/xml' :
				$label = _x( 'View Data File URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );
				break;

			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' :
			case 'application/vnd.ms-excel' :
				$label = _x( 'View Spreadsheet URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );
				break;

			case 'application/pdf' :
			case 'application/rtf' :
			case 'application/msword' :
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' :
				$label = _x( 'View Document URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );
				break;

			case 'text/html' :
				$label = _x( 'View HTML file URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );
				break;

			default:
				$label = _x( 'View Item URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );
		}

		return $this->filters( 'mime_type_label', $label, $mime_type, $post_id );
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

	// FIXME: WORKING BUT DISABLED:
	// TODO: waiting for: https://core.trac.wordpress.org/ticket/22363
	public function sanitize_file_name( $filename, $filename_raw )
	{
		$info = pathinfo( $filename );
		$ext  = empty( $info['extension'] ) ? '' : '.'.$info['extension'];
		$name = basename( $filename, $ext );

		$name = Utilities::URLifyDownCode( $name );
		return Text::strToLower( $name ).$ext;
	}

	// FIXME: WORKING BUT DISABLED: there will be notices!
	// TODO: add core tiket!
	public function wp_read_image_metadata( $meta, $file, $sourceImageType, $iptc )
	{
		return Arraay::stripDefaults( $meta, array(
			'aperture'          => 0,
			'credit'            => '',
			'camera'            => '',
			'caption'           => '',
			'created_timestamp' => 0,
			'copyright'         => '',
			'focal_length'      => 0,
			'iso'               => 0,
			'shutter_speed'     => 0,
			'title'             => '',
			'orientation'       => 0,
			'keywords'          => array(),
		) );
	}
}

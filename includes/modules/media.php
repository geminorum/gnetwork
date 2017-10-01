<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Ajax;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Media extends gNetwork\Module
{

	protected $key     = 'media';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	private $posttype_sizes = [];

	protected function setup_actions()
	{
		$this->action( 'init', 0, 999, 'late' );
		$this->filter( 'upload_mimes' );

		$this->filter( 'sanitize_file_name', 2, 12 );
		$this->filter( 'image_send_to_editor', 8 );
		$this->filter( 'media_send_to_editor', 3, 12 );

		if ( $this->options['skip_exifmeta'] )
			$this->filter( 'wp_update_attachment_metadata', 2, 12 );

		if ( is_admin() ) {

			if ( WordPress::mustRegisterUI( FALSE ) )
				$this->action( 'current_screen' );

			$this->filter( 'post_mime_types' );

		} else {

			$this->filter( 'single_post_title', 2, 9 );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Media', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);

		Admin::registerMenu( 'images',
			_x( 'Images', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			FALSE, 'edit_others_posts'
		);
	}

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function default_options()
	{
		return [
			'skip_exifmeta' => '1',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'skip_exifmeta',
					'title'       => _x( 'Strip EXIF', 'Modules: Media: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Skips unused EXIF metadata for image attachments.', 'Modules: Media: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
			],
		];
	}

	public function init_late()
	{
		if ( $this->filters( 'object_sizes', GNETWORK_MEDIA_OBJECT_SIZES, $this->blog ) ) {

			add_filter( 'intermediate_image_sizes', '__return_empty_array', 99 );
			// add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 99 );
			$this->filter( 'wp_generate_attachment_metadata', 2, 10, 'posttype' );
			$this->action( 'clean_attachment_cache' );
		}

		if ( $this->filters( 'thumbs_separation', GNETWORK_MEDIA_THUMBS_SEPARATION, $this->blog ) ) {

			$this->filter( 'wp_image_editors', 1, 5 );
			$this->filter( 'image_downsize', 3, 5 );
			$this->action( 'delete_attachment' );
		}

		// support for taxonomy sizes
		// must be after object_sizes filter: `wp_generate_attachment_metadata_posttype`
		$this->filter( 'wp_generate_attachment_metadata', 2, 12, 'taxonomy' );

		// fires after images attached to terms
		// WARNING: no prefix is not a good idea!
		$this->action( 'clean_term_attachment_cache' );
	}

	public function current_screen( $screen )
	{
		if ( 'upload' == $screen->base ) {

			if ( WordPress::cuc( 'edit_others_posts' ) ) {
				add_filter( 'bulk_actions-'.$screen->id, [ $this, 'bulk_actions' ] );
				add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'handle_bulk_actions' ], 10, 3 );
			}

			$this->filter( 'media_row_actions', 3, 50 );

			Utilities::enqueueScript( 'admin.media' );

		} else if ( 'post' == $screen->base ) {

			$this->filter( 'admin_post_thumbnail_size', 3, 9 );
		}
	}

	public function settings( $sub = NULL )
	{
		if ( 'images' == $sub ) {

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

				} else if ( isset( $_POST['cache_in_content'], $_POST['_cb'] ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->cache_in_content( $post_id ) )
							$count++;

				} else {

					WordPress::redirectReferer( [
						'message' => 'wrong',
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );
				}

				WordPress::redirectReferer( [
					'message' => 'cleaned',
					'count'   => $count,
					'limit'   => self::limit(),
					'paged'   => self::paged(),
				] );
			}

			$this->register_button( 'clean_attachments', _x( 'Clean Attachments', 'Modules: Media', GNETWORK_TEXTDOMAIN ) );
			$this->register_button( 'cache_in_content', _x( 'Cache In Content', 'Modules: Media', GNETWORK_TEXTDOMAIN ) );

			add_action( $this->settings_hook( $sub ), [ $this, 'settings_form_images' ], 10, 2 );

			add_thickbox();
			Utilities::enqueueScript( 'admin.images' );

		} else {
			parent::settings( $sub );
		}
	}

	public static function registeredImageSizes()
	{
		global $_wp_additional_image_sizes;

		if ( empty( $_wp_additional_image_sizes ) )
			HTML::desc( _x( 'No additional image size registered.', 'Modules: Media', GNETWORK_TEXTDOMAIN ) );
		else
			HTML::tableSide( $_wp_additional_image_sizes );
	}

	public function settings_form_images( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub, 'bulk' );

			if ( $this->tablePostInfo() )
				$this->settings_buttons( $sub );

			// TODO: add clean all attachments button, hence : regenerate-thumbnails

		$this->settings_form_after( $uri, $sub );
	}

	protected static function getPostArray()
	{
		$extra  = [];
		$limit  = self::limit();
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$args = [
			'posts_per_page'   => $limit,
			'offset'           => $offset,
			'orderby'          => self::orderby( 'ID' ),
			'order'            => self::order( 'asc' ),
			'post_type'        => 'any',
			'post_status'      => [ 'publish', 'future', 'draft', 'pending' ],
			'suppress_filters' => TRUE,
		];

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $extra['type'] = $_REQUEST['type'];

		if ( 'attachment' == $args['post_type'] )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = HTML::tablePagination( $query->found_posts, $query->max_num_pages, $limit, $paged, $extra );

		return [ $posts, $pagination ];
	}

	private function tablePostInfo()
	{
		list( $posts, $pagination ) = self::getPostArray();

		return HTML::tableList( [
			'_cb' => 'ID',
			'ID'  => _x( 'ID', 'Modules: Media: Column Title', GNETWORK_TEXTDOMAIN ),

			'date' => [
				'title'    => _x( 'Date', 'Modules: Media: Column Title', GNETWORK_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					return Utilities::humanTimeDiffRound( strtotime( $row->post_date ) );
				},
			],

			'type' => [
				'title'    => _x( 'Type', 'Modules: Media: Column Title', GNETWORK_TEXTDOMAIN ),
				'args'     => [ 'post_types' => WordPress::getPostTypes( 2 ) ],
				'callback' => function( $value, $row, $column, $index ){
					return isset( $column['args']['post_types'][$row->post_type] )
						? $column['args']['post_types'][$row->post_type]
						: $row->post_type;
				},
			],

			'title' => [
				'title' => _x( 'Title', 'Modules: Media: Column Title', GNETWORK_TEXTDOMAIN ),
				'args'  => [
					'url'   => get_bloginfo( 'url' ),
					'admin' => admin_url( 'post.php' ),
					'ajax'  => admin_url( 'admin-ajax.php' ),
				],
				'callback' => function( $value, $row, $column, $index ){
					return Utilities::getPostTitle( $row )
						.get_the_term_list( $row->ID, 'post_tag',
							'<div><small>', ', ', '</small></div>' );
				},
				'actions' => function( $value, $row, $column, $index ){

					$args = [
						'action'  => $this->hook(),
						'post_id' => $row->ID,
						'nonce'   => wp_create_nonce( $this->hook( $row->ID ) ),
					];

					return [

						'edit' => HTML::tag( 'a', [
							'href'   => add_query_arg( [ 'action' => 'edit', 'post' => $row->ID ], $column['args']['admin'] ),
							'class'  => '-link -row-link -row-link-edit',
							'target' => '_blank',
						], _x( 'Edit', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) ),

						'view' => HTML::tag( 'a', [
							'href'   => add_query_arg( [ 'p' => $row->ID ], $column['args']['url'] ),
							'class'  => '-link -row-link -row-link-view',
							'target' => '_blank',
						], _x( 'View', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) ),

						'clean' => HTML::tag( 'a', [
							'href'  => add_query_arg( array_merge( $args, [ 'what' => 'clean_post' ] ), $column['args']['ajax'] ),
							'class' => '-link -row-ajax -row-ajax-clean',
							'data'  => [ 'spinner' => _x( 'Cleaning &hellip;', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) ],
						], _x( 'Clean', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) ),

						'cache' => HTML::tag( 'a', [
							'href'   => add_query_arg( array_merge( $args, [ 'what' => 'cache_post' ] ), $column['args']['ajax'] ),
							'class'  => '-link -row-ajax -row-ajax-cache',
							'data' => [ 'spinner' => _x( 'Caching &hellip;', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) ],
						], _x( 'Cache', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) ),
					];
				},
			],

			'attached' => [
				'title'    => _x( 'Attached Media', 'Modules: Media: Column Title', GNETWORK_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){

					// TODO: check for all attachment types, use wp icons

					$links = [];

					$thumbnail_id  = get_post_meta( $row->ID, '_thumbnail_id', TRUE );
					$gtheme_images = get_post_meta( $row->ID, '_gtheme_images', TRUE );
					$gtheme_terms  = get_post_meta( $row->ID, '_gtheme_images_terms', TRUE );

					foreach ( WordPress::getAttachments( $row->ID ) as $attachment ) {

						$html = HTML::tag( 'a', [
							'href'   => wp_get_attachment_url( $attachment->ID ),
							'class'  => 'thickbox',
							'target' => '_blank',
						], get_post_meta( $attachment->ID, '_wp_attached_file', TRUE ) );

						$html .= ' &ndash; '.$attachment->ID;

						if ( $meta = wp_get_attachment_metadata( $attachment->ID ) ) {

							if ( isset( $meta['sizes'] ) && $count = count( $meta['sizes'] ) )
								$html .= ' &ndash; sizes: '.$count;
						}

						if ( $thumbnail_id == $attachment->ID )
							$html .= ' &ndash; <b>thumbnail</b>';

						if ( $gtheme_images && in_array( $attachment->ID, $gtheme_images ) )
							$html .= ' &ndash; tagged: '.array_search( $attachment->ID, $gtheme_images );

						if ( $gtheme_terms && in_array( $attachment->ID, $gtheme_terms ) )
							$html .= ' &ndash; for term: '.array_search( $attachment->ID, $gtheme_terms );

						$links[] = $html;
					}

					return '<div dir="ltr">'.( count( $links ) ? implode( '<br />', $links ) : '&mdash;' ).'</div>';
				},
			],

			'content' => [
				'title'    => _x( 'In Content', 'Modules: Media: Column Title', GNETWORK_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){

					preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $row->post_content, $matches );

					if ( ! count( $matches[1] ) )
						return '<div dir="ltr">&mdash;</div>';

					$links     = [];
					$externals = URL::checkExternals( $matches[1] );

					$status   = _x( 'Status Code', 'Modules: Media: Title Attr', GNETWORK_TEXTDOMAIN );
					$external = sprintf( '<small><code title="%s">%s</code></small>&nbsp;',
						_x( 'External Resource', 'Modules: Media: Title Attr', GNETWORK_TEXTDOMAIN ),
						_x( 'Ex', 'Modules: Media: External Resource', GNETWORK_TEXTDOMAIN ) );

					if ( FALSE === ( $checked = HTTP::checkURLs( $matches[1] ) ) )
						$checked = array_fill_keys( $matches[1], NULL );

					foreach ( $checked as $src => $code ) {

						$link = '';

						if ( isset( $externals[$src] ) && $externals[$src] )
							$link .= $external;

						if ( ! is_null( $code ) )
							$link .= sprintf( '<small><code title="%s" style="color:%s">%s</code></small>&nbsp;', $status, ( $code > 400 ? 'red' : 'green' ), $code );

						$links[] = $link.HTML::tag( 'a', [
								'href'   => $src,
								'class'  => $code > 400 ? '-error' : 'thickbox',
								'target' => '_blank',
							], URL::prepTitle( $src ) );
					}

					return '<div dir="ltr">'.( count( $links ) ? implode( '<br />', $links ) : '&mdash;' ).'</div>';
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of posts with attachments', 'Modules: Media', GNETWORK_TEXTDOMAIN ) ),
			'empty'      => HTML::warning( _x( 'No Posts!', 'Modules: Media', GNETWORK_TEXTDOMAIN ) ),
			'pagination' => $pagination,
		] );
	}

	public function clean_attachment_cache( $attachment_id )
	{
		$this->clean_attachment( $attachment_id, TRUE );
	}

	// @SEE: https://github.com/syamilmj/Aqua-Resizer/blob/master/aq_resizer.php
	public function wp_generate_attachment_metadata_posttype( $metadata, $attachment_id )
	{
		// only images have 'file' key
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

			if ( ! $parent = get_post( wp_get_post_parent_id( $attachment_id ) ) )
				return $metadata;

			$parent_type = $parent->post_type;
		}

		$sizes = $this->get_posttype_sizes( $parent_type );

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

	private function get_posttype_sizes( $posttype = 'post', $fallback = 'post' )
	{
		if ( isset( $this->posttype_sizes[$posttype] ) )
			return $this->posttype_sizes[$posttype];

		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( (array) $_wp_additional_image_sizes as $name => $size ) {

			if ( array_key_exists( 'post_type', $size ) ) {

				if ( is_array( $size['post_type'] ) ) {

					if ( in_array( $posttype, $size['post_type'] ) )
						$sizes[$name] = $size;

					else if ( $fallback && in_array( $fallback, $size['post_type'] ) )
						$sizes[$name] = $size;

				} else if ( $size['post_type'] ) {
					$sizes[$name] = $size;
				}

			} else if ( TRUE === $fallback ) {

				$sizes[$name] = $size;
			}
		}

		return $this->posttype_sizes[$posttype] = $sizes;
	}

	public function clean_term_attachment_cache( $attachment_id )
	{
		if ( $attachment_id && get_post( $attachment_id ) )
			$this->clean_attachment( $attachment_id, TRUE );
	}

	public function wp_generate_attachment_metadata_taxonomy( $metadata, $attachment_id )
	{
		// only images have 'file' key
		if ( ! isset( $metadata['file'] ) )
			return $metadata;

		// only for term images
		if ( ! $taxonomy = get_post_meta( $attachment_id, '_wp_attachment_is_term_image', TRUE ) )
			return $metadata;

		$sizes = $this->get_taxonomy_sizes( $taxonomy );

		if ( ! isset( $metadata['sizes'] ) ) {
			$metadata['sizes'] = [];
		} else {

			// no need to resize already
			foreach( $sizes as $size => $args )
				if ( array_key_exists( $size, $metadata['sizes'] ) )
					unset( $sizes[$size] );
		}

		if ( ! count( $sizes ) )
			return $metadata;

		$wpupload = wp_get_upload_dir();
		$editor   = wp_get_image_editor( path_join( $wpupload['basedir'], $metadata['file'] ) );

		if ( ! self::isError( $editor ) )
			$metadata['sizes'] = array_merge( $metadata['sizes'], $editor->multi_resize( $sizes ) );

		if ( ! count( $metadata['sizes'] ) )
			unset( $metadata['sizes'] );

		if ( WordPress::isDev() )
			error_log( print_r( compact( 'taxonomy', 'sizes', 'metadata', 'wpupload' ), TRUE ) );

		return $metadata;
	}

	private function get_taxonomy_sizes( $taxonomy = 'category', $fallback = FALSE )
	{
		if ( isset( $this->taxonomy_sizes[$taxonomy] ) )
			return $this->taxonomy_sizes[$taxonomy];

		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( (array) $_wp_additional_image_sizes as $name => $size ) {

			if ( array_key_exists( 'taxonomy', $size ) ) {

				if ( is_array( $size['taxonomy'] ) ) {

					if ( in_array( $taxonomy, $size['taxonomy'] ) )
						$sizes[$name] = $size;

					else if ( $fallback && in_array( $fallback, $size['taxonomy'] ) )
						$sizes[$name] = $size;

				} else if ( $size['taxonomy'] ) {
					$sizes[$name] = $size;
				}

			} else if ( TRUE === $fallback ) {

				$sizes[$name] = $size;
			}
		}

		return $this->taxonomy_sizes[$posttype] = $sizes;
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

	// core dup with posttype/taxonomy/title
	// @REF: `add_image_size()`
	public static function registerImageSize( $name, $atts = [] )
	{
		global $_wp_additional_image_sizes;

		$args = self::atts( [
			'n' => __( 'Untitled' ),
			'w' => 0,
			'h' => 0,
			'c' => 0,
			'p' => [ 'post' ], // posttype: TRUE: all/array: posttypes/FALSE: none
			't' => FALSE, // taxonomy: TRUE: all/array: taxes/FALSE: none
			'f' => empty( $atts['s'] ) ? FALSE : $atts['s'], // featured
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

			$result = [
				str_replace( $wpupload['baseurl'], trailingslashit( GNETWORK_MEDIA_THUMBS_URL ).$this->blog, $img_url ),
				$data['width'],
				$data['height'],
				TRUE,
			];

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

		return [
			'geminorum\\gNetwork\\Misc\\Image_Editor_Imagick',
			'geminorum\\gNetwork\\Misc\\Image_Editor_GD',
		];
	}

	// FIXME: ALSO SEE: https://core.trac.wordpress.org/changeset/38113
	public function get_thumbs( $attachment_id )
	{
		$thumbs = [];

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
		$urls = [];

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

		$meta['sizes'] = [];

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
				$update = wp_update_attachment_metadata( $attachment_id, $meta );
			}

			if ( WordPress::isDev() )
				error_log( print_r( compact( 'attachment_id', 'thumbs', 'delete', 'file', 'meta', 'update' ), TRUE ) );

			return TRUE;
		}

		return FALSE;
	}

	public function clean_attachments( $post_id )
	{
		global $wpdb;

		$clean = $moved = [];

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

			// move file to correct location
			if ( file_exists( $clean_path ) && @rename( $clean_path, $moved_path ) ) {

				$thumbs_path = $this->get_thumbs( $clean_id );
				$thumbs_url = $this->url_thumbs( $thumbs_path, $wpupload );

				$thumbs_url[] = $wpupload['baseurl'].'/'.$clean_file; // also the original

				foreach ( $thumbs_url as $thumb_url ) {
					foreach ( $matches[1] as $offset => $url ) {
						if ( $thumb_url == $url ) {
							$wpdb->query( $wpdb->prepare( "
								UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s ) WHERE ID = %d
							", $url, ( $wpupload['url'].'/'.wp_basename( $url ) ), $post_id ) );
						}
					}
				}

				$this->delete_thumbs( $thumbs_path );

				$meta = wp_generate_attachment_metadata( $clean_id, $moved_path );
				wp_update_attachment_metadata( $clean_id, $meta );

				$wpdb->query( $wpdb->prepare( "
					UPDATE {$wpdb->posts} SET guid = %s WHERE ID = %d
				", esc_url_raw( $wpupload['url'].'/'.$clean_file ), $clean_id ) );

				update_attached_file( $clean_id, $moved_path );

				$moved[$clean_id] = $wpupload['subdir'].'/'.$clean_file;
			}
		}

		return count( $moved );
	}

	// FIXME: WTF?!
	public function cache_in_content( $post_id )
	{
		return TRUE;
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

	public function bulk_actions( $actions )
	{
		return array_merge( $actions, [ 'cleanattachments' => _x( 'Clean Attachments', 'Modules: Media: Bulk Action', GNETWORK_TEXTDOMAIN ) ] );
	}

	public function handle_bulk_actions( $redirect_to, $doaction, $post_ids )
	{
		if ( 'cleanattachments' != $doaction )
			return $redirect_to;

		check_admin_referer( 'bulk-media' );

		$parents = [];

		foreach ( $post_ids as $post_id )
			if ( $attachment = get_post( $post_id ) )
				$parents[] = $attachment->post_parent;

		if ( ! count( $parents ) )
			return $redirect_to;

		$ids = maybe_serialize( implode( ',', array_map( 'intval', array_unique( $parents ) ) ) );

		return $this->get_settings_url( [ 'sub' => 'images', 'id'  => $ids ] );
	}

	public function media_row_actions( $actions, $post, $detached )
	{
		$url = wp_get_attachment_url( $post->ID );

		if ( WordPress::cuc( 'edit_others_posts' )
			&& wp_attachment_is( 'image', $post->ID ) ) {

			$actions['media-clean'] = HTML::tag( 'a', [
				'target' => '_blank',
				'class'  => [ 'media-clean-attachment', ( $post->post_parent ? '' : '-disabled' ) ],
				'href'   => $post->post_parent ? $this->get_settings_url( [ 'sub' => 'images', 'id' => $post->post_parent ] ) : '#',
				'data'   => [
					'id'      => $post->ID,
					'parent'  => $post->post_parent,
					'nonce'   => wp_create_nonce( $this->hook( $post->ID ) ),
					'spinner' => _x( 'Cleaning &hellip;', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ),
				],
			], _x( 'Clean', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) );
		}

		$link = HTML::tag( 'a', [
			'target' => '_blank',
			'class'  => 'media-url-click media-url-attachment',
			'href'   => $url,
			'data'   => [
				'id'     => $post->ID,
				'action' => 'get_url',
			],
		], $this->get_media_type_label( $post->ID ) );

		$link .= '<div class="media-url-box"><input type="text" class="widefat media-url-field" value="'.esc_url( $url ).'" readonly></div>';

		$actions['media-url'] = $link;

		return $actions;
	}

	public function ajax()
	{
		$post = self::unslash( $_REQUEST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'clean_attachment':

				if ( empty( $post['attachment'] ) )
					Ajax::errorMessage();

				Ajax::checkReferer( $this->hook( $post['attachment'] ) );

				if ( ! $this->clean_attachment( $post['attachment'] ) )
					Ajax::errorMessage();

				Ajax::success( _x( 'Cleaned', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) );

			break;
			case 'clean_post':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::checkReferer( $this->hook( $post['post_id'] ) );

				if ( ! $this->clean_attachments( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::success( _x( 'Cleaned', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) );

			break;
			case 'cache_post':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::checkReferer( $this->hook( $post['post_id'] ) );

				if ( ! $this->cache_in_content( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::success( _x( 'Cached', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN ) );
		}

		Ajax::errorWhat();
	}

	protected function get_media_type_label( $post_id, $mime_type = NULL )
	{
		if ( is_null( $mime_type ) )
			$mime_type = get_post_mime_type( $post_id );

		switch ( $mime_type ) {

			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':

				$label = _x( 'View Image URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );

			break;
			case 'video/mpeg':
			case 'video/mp4':
			case 'video/webm':
			case 'video/ogg':
			case 'video/quicktime':

				$label = _x( 'View Video URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );

			break;
			case 'text/csv':
			case 'text/xml':

				$label = _x( 'View Data File URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );

			break;
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
			case 'application/vnd.ms-excel':

				$label = _x( 'View Spreadsheet URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );

			break;
			case 'application/pdf':
			case 'application/rtf':
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':

				$label = _x( 'View Document URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );

			break;
			case 'text/html':

				$label = _x( 'View HTML file URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );

			break;
			default:

				$label = _x( 'View Item URL', 'Modules: Media: Row Action', GNETWORK_TEXTDOMAIN );
		}

		return $this->filters( 'mime_type_label', $label, $mime_type, $post_id );
	}

	public function upload_mimes( $mimes )
	{
		return array_merge( $mimes, [
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'ppt'  => 'application/vnd.ms-powerpoint',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'  => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'csv'  => 'text/csv',
			'xml'  => 'text/xml',
			'md'   => 'text/markdown',
			'webm' => 'video/webm',
			'flv'  => 'video/x-flv',
			'ac3'  => 'audio/ac3',
			'mpa'  => 'audio/MPA',
			'mp4'  => 'video/mp4',
			'mpg4' => 'video/mp4',
			'flv'  => 'video/x-flv',
			'svg'  => 'image/svg+xml',
		] );
	}

	public function image_send_to_editor( $html, $id, $caption, $title, $align, $url, $size, $alt )
	{
		if ( strpos( $url, 'attachment_id' )
			|| get_attachment_link( $id ) == $url )
				$url = WordPress::getPostShortLink( $id );

		return HTML::tag( 'a', [
			'href'  => $url,
			'rel'   => 'attachment',
			'class' => '-attachment',
			'data'  => [ 'id' => $id ],
		], get_image_tag( $id, $alt, '', $align, $size ) );
	}

	public function media_send_to_editor( $html, $id, $attachment )
	{
		if ( 'image' === substr( get_post( $id )->post_mime_type, 0, 5 ) )
			return $html;

		if ( empty( $attachment['url'] ) )
			return $html;

		if ( get_attachment_link( $id ) != $attachment['url'] )
			return $html;

		$html = isset( $attachment['post_title'] ) ? $attachment['post_title'] : '';

		return HTML::tag( 'a', [
			'href'  => WordPress::getPostShortLink( $id ),
			'rel'   => 'attachment',
			'class' => '-attachment',
			'data'  => [ 'id' => $id ],
		], $html );
	}

	public function wp_update_attachment_metadata( $data, $post_id )
	{
		unset( $data['image_meta'] );
		return $data;
	}

	// tries to set correct size for thumbnail metabox
	public function admin_post_thumbnail_size( $size, $thumbnail_id, $post )
	{
		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		if ( isset( $_wp_additional_image_sizes[$post->post_type.'-thumbnail'] ) )
			return $post->post_type.'-thumbnail';

		return $size;
	}

	public function post_mime_types( $post_mime_types )
	{
		return array_merge( $post_mime_types, [
			'text' => [
				_x( 'Text', 'Modules: Media: Post Mime Type', GNETWORK_TEXTDOMAIN ),
				_x( 'Manage Texts', 'Modules: Media: Post Mime Type', GNETWORK_TEXTDOMAIN ),
				_nx_noop( 'Text <span class="count">(%s)</span>', 'Texts <span class="count">(%s)</span>', 'Modules: Media: Post Mime Type', GNETWORK_TEXTDOMAIN ),
			],
			'application' => [
				_x( 'Application', 'Modules: Media: Post Mime Type', GNETWORK_TEXTDOMAIN ),
				_x( 'Manage Applications', 'Modules: Media: Post Mime Type', GNETWORK_TEXTDOMAIN ),
				_nx_noop( 'Application <span class="count">(%s)</span>', 'Applications <span class="count">(%s)</span>', 'Modules: Media: Post Mime Type', GNETWORK_TEXTDOMAIN ),
			],
		] );
	}

	// FIXME: waiting on: https://core.trac.wordpress.org/ticket/22363
	public function sanitize_file_name( $filename, $filename_raw )
	{
		if ( ! seems_utf8( $filename ) )
			return $filename;

		$info = pathinfo( $filename );
		$ext  = empty( $info['extension'] ) ? '' : '.'.$info['extension'];
		$name = basename( $filename, $ext );

		$name = Utilities::URLifyDownCode( $name );
		return Text::strToLower( $name ).$ext;
	}
}

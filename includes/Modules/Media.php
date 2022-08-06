<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Ajax;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\L10n;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\Strings;
use geminorum\gNetwork\WordPress\PostType as WPPostType;
use geminorum\gNetwork\WordPress\Media as WPMedia;

class Media extends gNetwork\Module
{

	protected $key     = 'media';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	private $posttype_sizes = [];

	protected function setup_actions()
	{
		$this->action( 'init' );
		$this->action( 'init', 0, 999, 'late' );

		$this->filter( 'upload_mimes' );
		$this->filter( 'wp_editor_set_quality', 2, 12 );
		// $this->filter( 'wp_check_filetype_and_ext', 4, 12 );

		if ( ! empty( $this->option['output_format'] ) )
			$this->filter( 'image_editor_output_format', 3, 12 );

		if ( function_exists( 'normalizer_normalize' ) )
			$this->filter( 'wp_handle_upload_prefilter', 1, 1 );

		$this->filter( 'sanitize_file_name', 2, 12 );
		$this->filter( 'image_send_to_editor', 9 );
		$this->filter( 'media_send_to_editor', 3, 12 );

		if ( $this->options['skip_exifmeta'] )
			$this->filter( 'wp_update_attachment_metadata', 2, 12 );

		if ( is_admin() ) {

			if ( $this->options['dashboard_widget'] )
				$this->action( 'post-plupload-upload-ui', 0, 12 );

			$this->filter( 'post_mime_types' );

		} else {

			$this->filter( 'single_post_title', 2, 9 );
		}

		add_filter( $this->hook( 'get_circular_player' ), [ $this, 'get_circular_player' ], 10, 4 );
		add_action( $this->hook( 'enqueue_circular_player' ), [ $this, 'enqueue_circular_player' ] );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Media', 'Modules: Menu Name', 'gnetwork' ) );
		$this->register_tool( _x( 'Images', 'Modules: Menu Name', 'gnetwork' ), 'images' );
	}

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function default_options()
	{
		return [
			'tools_accesscap'     => 'edit_others_posts',
			'skip_exifmeta'       => '1',
			'quality_jpeg'        => 60,
			'quality_webp'        => 75,
			'output_format'       => [],
			'dashboard_widget'    => '0',
			'dashboard_accesscap' => 'edit_others_posts',
			'dashboard_intro'     => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'tools_accesscap',
					'type'        => 'cap',
					'title'       => _x( 'Tools Access', 'Modules: Media: Settings', 'gnetwork' ),
					'description' => _x( 'Selected and above can access the image tools.', 'Modules: Media: Settings', 'gnetwork' ),
					'default'     => 'edit_others_posts',
				],
				[
					'field'       => 'skip_exifmeta',
					'title'       => _x( 'Strip EXIF', 'Modules: Media: Settings', 'gnetwork' ),
					'description' => _x( 'Skips storing unused EXIF metadata for image attachments.', 'Modules: Media: Settings', 'gnetwork' ),
					'default'     => '1',
				],
			],
			'_quality' => [
				[
					'field'       => 'quality_jpeg',
					'type'        => 'number',
					'title'       => _x( 'JPEG Quality', 'Modules: Media: Settings', 'gnetwork' ),
					'description' => _x( 'Sets the compression quality setting used for JPEG images.', 'Modules: Media: Settings', 'gnetwork' ),
					'placeholder' => 90,
					'default'     => 60,
					'min_attr'    => 1,
					'max_attr'    => 100,
				],
				[
					'field'       => 'quality_webp',
					'type'        => 'number',
					'title'       => _x( 'WebP Quality', 'Modules: Media: Settings', 'gnetwork' ),
					'description' => _x( 'Sets the compression quality setting used for WebP images.', 'Modules: Media: Settings', 'gnetwork' ),
					'placeholder' => 90,
					'default'     => 75,
					'min_attr'    => 1,
					'max_attr'    => 100,
				],
			],
			'_format' => [
				[
					'field'       => 'output_format',
					'type'        => 'checkboxes',
					'title'       => _x( 'Output Format', 'Modules: Media: Settings', 'gnetwork' ),
					'description' => _x( 'Sets WebP as default format for selected image sub-sizes.', 'Modules: Media: Settings', 'gnetwork' ),
					// 'after'       => Settings::fieldAfterIcon( 'https://caniuse.com/webp' ),
					'values'      => [
						'jpeg'   => 'JPEG',
						'gif'    => 'GIF',
						'png'    => 'PNG',
						'bmp'    => 'BMP',
						'tiff'   => 'TIFF',
						'avif'   => 'AVIF',
						'jpegxl' => 'JPEG XL',
					],
				],
			],
			'_uploader' => [
				'dashboard_widget',
				'dashboard_accesscap' => 'edit_others_posts',
				'dashboard_intro',
			],
		];
	}

	public function settings_section_uploader()
	{
		Settings::fieldSection(
			_x( 'Large File Uploader', 'Modules: Media: Settings', 'gnetwork' ),
			_x( 'Simple javascript powered widget to upload large files.', 'Modules: Media: Settings', 'gnetwork' )
		);
	}

	public function settings_sidebox( $sub, $uri )
	{
		self::summaryAttachments();
	}

	// FIXME: get mime names
	public static function summaryAttachments()
	{
		echo HTML::tableCode( wp_count_attachments() );
	}

	public function init()
	{
		// support for taxonomy sizes
		// must be after object_sizes filter: `wp_generate_attachment_metadata_posttype`
		$this->filter( 'wp_generate_attachment_metadata', 2, 12, 'taxonomy' );

		// fires after images attached to terms
		// WARNING: no prefix is not a good idea!
		$this->action( 'clean_term_attachment_cache' );

		if ( is_main_site() )
			$this->filter_true( 'pre_site_option_upload_space_check_disabled' );
	}

	public function init_late()
	{
		if ( $this->filters( 'object_sizes', GNETWORK_MEDIA_OBJECT_SIZES, get_current_blog_id() ) ) {

			$this->filter_empty_array( 'intermediate_image_sizes', 99 );
			// $this->filter_empty_array( 'intermediate_image_sizes_advanced', 99 );
			$this->filter( 'wp_generate_attachment_metadata', 2, 10, 'posttype' );
			$this->action( 'clean_attachment_cache' );
		}

		if ( $this->filters( 'thumbs_separation', GNETWORK_MEDIA_THUMBS_SEPARATION, get_current_blog_id() ) ) {

			$this->filter( 'wp_image_editors', 1, 5 );
			$this->filter( 'image_downsize', 3, 5 );
			$this->action( 'delete_attachment' );
		}
	}

	public function setup_screen( $screen )
	{
		if ( 'upload' == $screen->base ) {

			if ( WordPress::cuc( $this->options['tools_accesscap'] ) ) {
				add_filter( 'bulk_actions-'.$screen->id, [ $this, 'bulk_actions' ] );
				add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'handle_bulk_actions' ], 10, 3 );
			}

			$this->filter( 'media_row_actions', 3, 50 );

			Scripts::enqueueScript( 'admin.media' );

		} else if ( 'post' == $screen->base ) {

			$this->filter( 'admin_post_thumbnail_size', 3, 9 );
		}
	}

	public function setup_dashboard()
	{
		if ( ! current_user_can( 'upload_files' ) )
			return;

		if ( $this->add_dashboard_widget( 'uploader', _x( 'Large File Uploader', 'Modules: Media: Widget Title', 'gnetwork' ), 'info' ) )
			Scripts::enqueueScript( 'admin.media.uploader' );
	}

	public function post_plupload_upload_ui()
	{
		if ( WordPress::cuc( $this->options['dashboard_accesscap'] ) )
			/* translators: %1$s: link markup start, %2$s: link markup end */
			HTML::desc( sprintf( _x( 'Alternatively, you can use %1$sLarge File Uploader%2$s widget on the dashoard.', 'Modules: Media', 'gnetwork' ),
				'<a href="'.HTML::escapeURL( get_dashboard_url() ).'">', '</a>' ) );
	}

	public function tools( $sub = NULL, $key = NULL )
	{
		parent::tools( $sub, 'images' );
	}

	protected function tools_buttons( $sub = NULL )
	{
		$this->register_button( 'clean_attachments', _x( 'Clean Attachments', 'Modules: Media', 'gnetwork' ) );
		$this->register_button( 'sync_attachments', _x( 'Sync Attachments', 'Modules: Media', 'gnetwork' ) );
		$this->register_button( 'cache_in_content', _x( 'Cache In Content', 'Modules: Media', 'gnetwork' ) );
		$this->register_button( 'ssl_correction', _x( 'Correct SSL', 'Modules: Media', 'gnetwork' ) );
		$this->register_button( 'purge_meta', _x( 'Purge Meta', 'Modules: Media', 'gnetwork' ) );
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( 'images' == $sub ) {

			if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub, 'tools' );

				if ( self::isTablelistAction( 'sync_attachments', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->sync_attachments( $post_id ) )
							$count++;

					WordPress::redirectReferer( [
						'message' => 'synced',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else if ( self::isTablelistAction( 'clean_attachments', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )

						if ( wp_attachment_is_image( $post_id )
							&& $this->clean_attachment( $post_id ) )
								$count++;

						else if ( $this->clean_attachments( $post_id ) )
							$count++;

					WordPress::redirectReferer( [
						'message' => 'cleaned',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else if ( self::isTablelistAction( 'cache_in_content', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->cache_in_content( $post_id ) )
							$count++;

					WordPress::redirectReferer( [
						'message' => 'imported',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else if ( self::isTablelistAction( 'ssl_correction', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->ssl_correction( $post_id ) )
							$count++;

					WordPress::redirectReferer( [
						'message' => 'converted',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else if ( self::isTablelistAction( 'purge_meta', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->purge_meta( $post_id ) )
							$count++;

					WordPress::redirectReferer( [
						'message' => 'purged',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else {

					WordPress::redirectReferer( [
						'message' => 'wrong',
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );
				}
			}

			Scripts::enqueueThickBox();
			Scripts::enqueueScript( 'admin.media.images' );
		}
	}

	public static function registeredImageSizes()
	{
		global $_wp_additional_image_sizes;

		if ( empty( $_wp_additional_image_sizes ) )
			HTML::desc( _x( 'No additional image size registered.', 'Modules: Media', 'gnetwork' ) );
		else
			HTML::tableSide( $_wp_additional_image_sizes );
	}

	// @SEE: `get_post_types_by_support()`
	private function _get_posttypes_with_attachments( $context = NULL )
	{
		return $this->filters( 'attachment_posttypes', array_diff( array_keys( get_post_types( [ 'public' => TRUE ] ) ), [ 'attachment' ] ), $context );
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		list( $posts, $pagination ) = self::getTablelistPosts( [], [], $this->_get_posttypes_with_attachments( 'tools' ) );

		$pagination['before'][] = self::filterTablelistSearch();

		return HTML::tableList( [
			'_cb' => 'ID',
			'ID'  => _x( 'ID', 'Modules: Media: Column Title', 'gnetwork' ),

			'date' => [
				'title'    => _x( 'Date', 'Modules: Media: Column Title', 'gnetwork' ),
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					return Utilities::humanTimeDiffRound( strtotime( $row->post_date ) );
				},
			],

			'type' => [
				'title'    => _x( 'Type', 'Modules: Media: Column Title', 'gnetwork' ),
				'args'     => [ 'post_types' => WPPostType::get( 2 ) ],
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					return isset( $column['args']['post_types'][$row->post_type] )
						? $column['args']['post_types'][$row->post_type]
						: $row->post_type;
				},
			],

			'title' => [
				'title' => _x( 'Title', 'Modules: Media: Column Title', 'gnetwork' ),
				'args'  => [
					'url'   => get_bloginfo( 'url' ),
					'admin' => admin_url( 'post.php' ),
					'ajax'  => admin_url( 'admin-ajax.php' ),
				],
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					return Utilities::getPostTitle( $row )
						.get_the_term_list( $row->ID, 'post_tag',
							'<div><small>', Strings::separator(), '</small></div>' );
				},
				'actions' => function( $value, $row, $column, $index, $key, $args ) {
					$query = [
						'post_id' => $row->ID,
						'action'  => $this->hook(),
						'nonce'   => wp_create_nonce( $this->hook( $row->ID ) ),
					];

					return [
						'edit' => HTML::tag( 'a', [
							'href'   => add_query_arg( [ 'action' => 'edit', 'post' => $row->ID ], $column['args']['admin'] ),
							'class'  => '-link -row-link -row-link-edit',
							'target' => '_blank',
						], _x( 'Edit', 'Modules: Media: Row Action', 'gnetwork' ) ),

						'view' => HTML::tag( 'a', [
							'href'   => add_query_arg( [ 'p' => $row->ID ], $column['args']['url'] ),
							'class'  => '-link -row-link -row-link-view',
							'target' => '_blank',
						], _x( 'View', 'Modules: Media: Row Action', 'gnetwork' ) ),

						'clean' => HTML::tag( 'a', [
							'href'  => add_query_arg( array_merge( $query, [ 'what' => 'clean_post' ] ), $column['args']['ajax'] ),
							'class' => '-link -row-ajax -row-ajax-clean',
							'data'  => [ 'spinner' => _x( 'Cleaning &hellip;', 'Modules: Media: Row Action', 'gnetwork' ) ],
						], _x( 'Clean', 'Modules: Media: Row Action', 'gnetwork' ) ),

						'sync' => HTML::tag( 'a', [
							'href'  => add_query_arg( array_merge( $query, [ 'what' => 'sync_post' ] ), $column['args']['ajax'] ),
							'class' => '-link -row-ajax -row-ajax-sync',
							'data'  => [ 'spinner' => _x( 'Syncing &hellip;', 'Modules: Media: Row Action', 'gnetwork' ) ],
						], _x( 'Sync', 'Modules: Media: Row Action', 'gnetwork' ) ),

						'cache' => HTML::tag( 'a', [
							'href'  => add_query_arg( array_merge( $query, [ 'what' => 'cache_post' ] ), $column['args']['ajax'] ),
							'class' => '-link -row-ajax -row-ajax-cache',
							'data'  => [ 'spinner' => _x( 'Caching &hellip;', 'Modules: Media: Row Action', 'gnetwork' ) ],
						], _x( 'Cache', 'Modules: Media: Row Action', 'gnetwork' ) ),

						'correct' => HTML::tag( 'a', [
							'href'  => add_query_arg( array_merge( $query, [ 'what' => 'correct_post' ] ), $column['args']['ajax'] ),
							'class' => '-link -row-ajax -row-ajax-correct',
							'data'  => [ 'spinner' => _x( 'Correcting SSL &hellip;', 'Modules: Media: Row Action', 'gnetwork' ) ],
						], _x( 'Correct SSL', 'Modules: Media: Row Action', 'gnetwork' ) ),
					];
				},
			],

			'attached' => [
				'title'    => _x( 'Attached Media', 'Modules: Media: Column Title', 'gnetwork' ),
				'class'    => '-attached-media -has-list -has-list-ltr',
				'args'     => [ 'wpupload' => wp_get_upload_dir() ],
				'callback' => function( $value, $row, $column, $index, $key, $args ) {

					// TODO: check for all attachment types, use wp icons
					// TODO: attachment title as attr
					$attachments = $this->get_attachment_urls( $row->ID, 'image', $column['args']['wpupload'] );

					if ( empty( $attachments ) )
						return Utilities::htmlEmpty();

					$list     = [];
					$original = _x( 'Original Size', 'Modules: Media: Title Attr', 'gnetwork' );
					$sizes    = _x( 'Number of Sizes', 'Modules: Media: Title Attr', 'gnetwork' );
					$checked  = HTTP::checkURLs( Arraay::column( $attachments, 'url' ) );

					$thumbnail_id  = get_post_meta( $row->ID, '_thumbnail_id', TRUE );
					$gtheme_images = get_post_meta( $row->ID, '_gtheme_images', TRUE );
					$gtheme_terms  = get_post_meta( $row->ID, '_gtheme_images_terms', TRUE );

					foreach ( $attachments as $attachment ) {

						$meta = wp_get_attachment_metadata( $attachment['ID'] );
						$code = $checked && isset( $checked[$attachment['url']] ) ? $checked[$attachment['url']] : NULL;

						$html = HTTP::htmlStatus( $code );

						$html.= HTML::tag( 'a', [
							'href'   => $attachment['url'],
							'class'  => 200 === $code ? 'thickbox' : '-error',
							'target' => '_blank',
						], $attachment['file'] );

						$html.= ' &ndash;'.$attachment['ID'];

						if ( ! empty( $meta['original_image'] ) )
							$html.= sprintf( ' &ndash;<span title="%s">%s</span>', $original, HTML::tag( 'a', [
								'href'   => File::join( dirname( $attachment['url'] ), $meta['original_image'] ),
								'class'  => 'original',
								'target' => '_blank',
							], 'O' ) );

						if ( $thumbnail_id == $attachment['ID'] )
							$html.= ' &ndash;<b>thumbnail</b>';

						if ( $gtheme_images && in_array( $attachment['ID'], $gtheme_images ) )
							$html.= ' &ndash;tag:<b>'.array_search( $attachment['ID'], $gtheme_images ).'</b>';

						if ( $gtheme_terms && in_array( $attachment['ID'], $gtheme_terms ) )
							$html.= ' &ndash;term:<b>'.array_search( $attachment['ID'], $gtheme_terms ).'</b>';

						if ( $meta && isset( $meta['sizes'] ) && ( $count = count( $meta['sizes'] ) ) )
							$html.= sprintf( ' &ndash;<span class="-sizes" title="%s">%s&times;</span>', $sizes, $count );

						$list[] = $html;
					}

					return $list ? HTML::renderList( $list ) : Utilities::htmlEmpty();
				},
			],

			'content' => [
				'title'    => _x( 'In Content', 'Modules: Media: Column Title', 'gnetwork' ),
				'class'    => '-media-in-content -has-list -has-list-ltr',
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {

					// @SEE: `wp_extract_urls()`
					preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $row->post_content, $matches );

					if ( empty( $matches[1] ) )
						return Utilities::htmlEmpty();

					$list     = [];
					$externals = URL::checkExternals( $matches[1] );

					$external = sprintf( '<small><code class="-external-resource" title="%s">%s</code></small>',
						_x( 'External Resource', 'Modules: Media: Title Attr', 'gnetwork' ),
						_x( 'Ex', 'Modules: Media: External Resource', 'gnetwork' ) );

					if ( FALSE === ( $checked = HTTP::checkURLs( $matches[1] ) ) )
						$checked = array_fill_keys( $matches[1], NULL );

					foreach ( $checked as $src => $code ) {

						$link = HTTP::htmlStatus( $code );

						if ( isset( $externals[$src] ) && $externals[$src] )
							$link.= $external;

						$list[] = $link.' '.HTML::tag( 'a', [
							'href'   => $src,
							'class'  => 200 === $code ? 'thickbox' : '-error',
							'target' => '_blank',
						], URL::prepTitle( $src ) );
					}

					return $list ? HTML::renderList( $list ) : Utilities::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Posts with Attachments', 'Modules: Media', 'gnetwork' ) ),
			'empty'      => HTML::warning( _x( 'No Posts!', 'Modules: Media', 'gnetwork' ) ),
			'pagination' => $pagination,
		] );
	}

	public function get_attachment_urls( $parent_id, $mime_type = 'image', $wpupload = NULL )
	{
		if ( is_null( $wpupload ) )
			$wpupload = wp_get_upload_dir();

		$list = [];

		foreach ( WPMedia::getAttachments( $parent_id, $mime_type ) as $attachment ) {

			if ( ! $file = get_post_meta( $attachment->ID, '_wp_attached_file', TRUE ) )
				continue;

			if ( Text::has( $file, $wpupload['basedir'] ) )
				$url = str_replace( $wpupload['basedir'], $wpupload['baseurl'], $file );

			else
				$url = $wpupload['baseurl']."/$file";

			$list[] = [
				'ID'   => $attachment->ID,
				'file' => $file,
				'url'  => apply_filters( 'wp_get_attachment_url', $url, $attachment->ID ),
			];
		}

		return $list;
	}

	// FIXME: switch to `wp_media_attach_action` action @since WP 5.5.0
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

		$fallback = $this->filters( 'object_sizes_fallback_posttype', FALSE, $parent_type, $attachment_id, $metadata );
		$sizes    = $this->get_posttype_sizes( $parent_type, $fallback );

		if ( empty( $sizes ) )
			return $metadata;

		$wpupload = wp_get_upload_dir();
		$editor   = wp_get_image_editor( File::join( $wpupload['basedir'], $metadata['file'] ) );

		if ( ! self::isError( $editor ) )
			$metadata['sizes'] = $editor->multi_resize( $sizes );

		if ( WordPress::isDev() ) {
			self::_log( $parent_type, $metadata );
		}

		return $metadata;
	}

	private function get_posttype_sizes( $posttype = 'post', $fallback = FALSE )
	{
		if ( ! $fallback && isset( $this->posttype_sizes[$posttype] ) )
			return $this->posttype_sizes[$posttype];

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

		if ( ! $fallback )
			$this->posttype_sizes[$posttype] = $sizes;

		return $sizes;
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

		$fallback = $this->filters( 'object_sizes_fallback_taxonomy', FALSE, $taxonomy, $attachment_id, $metadata );
		$sizes    = $this->get_taxonomy_sizes( $taxonomy, $fallback );

		if ( ! isset( $metadata['sizes'] ) ) {
			$metadata['sizes'] = [];
		} else {

			// no need to resize already
			foreach ( $sizes as $size => $args )
				if ( array_key_exists( $size, $metadata['sizes'] ) )
					unset( $sizes[$size] );
		}

		if ( empty( $sizes ) )
			return $metadata;

		$wpupload = wp_get_upload_dir();
		$editor   = wp_get_image_editor( File::join( $wpupload['basedir'], $metadata['file'] ) );

		if ( ! self::isError( $editor ) )
			$metadata['sizes'] = array_merge( $metadata['sizes'], $editor->multi_resize( $sizes ) );

		if ( empty( $metadata['sizes'] ) )
			unset( $metadata['sizes'] );

		if ( WordPress::isDev() ) {
			self::_log( $taxonomy, $metadata, $wpupload );
		}

		return $metadata;
	}

	private function get_taxonomy_sizes( $taxonomy = 'category', $fallback = FALSE )
	{
		if ( ! $fallback && isset( $this->taxonomy_sizes[$taxonomy] ) )
			return $this->taxonomy_sizes[$taxonomy];

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

		if ( ! $fallback )
			$this->taxonomy_sizes[$taxonomy] = $sizes;

		return $sizes;
	}

	public function attachment_is_custom( $attachment_id )
	{
		if ( get_post_meta( $attachment_id, '_wp_attachment_is_custom_header', TRUE ) )
			return TRUE;

		if ( get_post_meta( $attachment_id, '_wp_attachment_is_custom_background', TRUE ) )
			return TRUE;

		if ( get_post_meta( $attachment_id, '_wp_attachment_context', TRUE ) )
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
			$img_url  = str_replace( File::basename( $img_url ), $data['file'], $img_url );

			if ( GNETWORK_MEDIA_THUMBS_CHECK && file_exists( str_replace( $wpupload['baseurl'], $wpupload['basedir'], $img_url ) ) )
				return $false;

			return [
				str_replace( $wpupload['baseurl'], trailingslashit( GNETWORK_MEDIA_THUMBS_URL ).get_current_blog_id(), $img_url ),
				$data['width'],
				$data['height'],
				TRUE,
			];
		}

		return $false;
	}

	public static function getSizesDestPath( $file )
	{
		$wpupload = wp_get_upload_dir();
		$info     = pathinfo( $file );
		$folder   = str_replace( $wpupload['basedir'], '', $info['dirname'] );
		$path     = File::join( GNETWORK_MEDIA_THUMBS_DIR, get_current_blog_id() ).$folder;

		if ( wp_mkdir_p( $path ) )
			return $path;

		return NULL;
	}

	public function wp_image_editors( $implementations )
	{
		require_once ABSPATH.WPINC.'/class-wp-image-editor.php';
		require_once ABSPATH.WPINC.'/class-wp-image-editor-gd.php';
		require_once ABSPATH.WPINC.'/class-wp-image-editor-imagick.php';

		require_once GNETWORK_DIR.'includes/Misc/MediaEditorImagick.php';
		require_once GNETWORK_DIR.'includes/Misc/MediaEditorGD.php';

		return [
			'geminorum\\gNetwork\\Misc\\MediaEditorImagick',
			'geminorum\\gNetwork\\Misc\\MediaEditorGD',
		];
	}

	// @SEE: `IMAGE_EDIT_OVERWRITE`
	// @SEE: `wp_delete_attachment_files()`
	// NOTE: maybe look into stored sizes in meta
	private function get_attachment_thumbs( $attachment_id, $wpupload = NULL )
	{
		$thumbs = [];

		if ( ! $file = get_post_meta( $attachment_id, '_wp_attached_file', TRUE ) )
			return $thumbs;

		if ( is_null( $wpupload ) )
			$wpupload = wp_get_upload_dir();

		$filetype = wp_check_filetype( File::basename( $file ) );
		$pathfile = File::join( dirname( $file ), File::basename( $file, '.'.$filetype['ext'] ) );

		if ( $this->filters( 'thumbs_separation', GNETWORK_MEDIA_THUMBS_SEPARATION, get_current_blog_id() ) ) {

			$thumbs_dir = File::join( GNETWORK_MEDIA_THUMBS_DIR, get_current_blog_id() );
			$gn_thumbs  = glob( $thumbs_dir.'/'.$pathfile.'-[0-9]*x[0-9]*.'.$filetype['ext'] );

			if ( ! empty( $gn_thumbs ) )
				$thumbs += $gn_thumbs;
		}

		$wp_thumbs = glob( $wpupload['basedir'].'/'.$pathfile.'-[0-9]*x[0-9]*.'.$filetype['ext'] );

		if ( ! empty( $wp_thumbs ) )
			$thumbs += $wp_thumbs;

		return $thumbs;
	}

	public function clean_attachment( $attachment_id, $regenerate = TRUE, $force = FALSE )
	{
		if ( $force || ! $this->attachment_is_custom( $attachment_id ) ) {

			File::remove( $this->get_attachment_thumbs( $attachment_id ) );

			if ( $regenerate ) {
				$file   = get_attached_file( $attachment_id, TRUE );
				$meta   = wp_generate_attachment_metadata( $attachment_id, $file );
				$update = wp_update_attachment_metadata( $attachment_id, $meta );
			}

			return TRUE;
		}

		return FALSE;
	}

	public function clean_attachments( $post_id, $force = FALSE )
	{
		$count = 0;

		foreach ( WPMedia::getAttachments( $post_id ) as $attachment )
			if ( $this->clean_attachment( $attachment->ID, TRUE, $force ) )
				$count++;

		return $count;
	}

	public function sync_attachments( $post )
	{
		global $wpdb;

		if ( ! $post = get_post( $post ) )
			return FALSE;

		$clean = $moved = [];

		foreach ( WPMedia::getAttachments( $post->ID ) as $attachment ) {
			if ( $attached_file = get_post_meta( $attachment->ID, '_wp_attached_file', TRUE ) ) {
				if ( ! str_replace( File::basename( $attached_file ), '', $attached_file ) ) {
					$clean[$attachment->ID] = $attached_file;
				}
			}
		}

		if ( empty( $clean ) )
			return TRUE;

		$wpupload = WPMedia::upload( $post );

		preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post->post_content, $matches );

		foreach ( $clean as $clean_id => $clean_file ) {

			// $clean_upload = media_sideload_image( URL::trail( $wpupload['baseurl'] ).$clean_file, $post->ID, NULL, 'src' );

			$clean_path = File::join( $wpupload['basedir'], $clean_file );
			$moved_path = File::join( $wpupload['path'], $clean_file );

			// move file to correct location
			if ( file_exists( $clean_path ) && @rename( $clean_path, $moved_path ) ) {

				$paths = $this->get_attachment_thumbs( $clean_id, $wpupload );
				$urls  = [];

				foreach ( $paths as $path )
					$urls[] = str_replace( $wpupload['basedir'], $wpupload['baseurl'], File::normalize( $path ) );

				// also the original
				$urls[] = URL::trail( $wpupload['baseurl'] ).$clean_file;

				foreach ( $urls as $url ) {
					foreach ( $matches[1] as $matched ) {
						if ( $url == $matched ) {
							$wpdb->query( $wpdb->prepare( "
								UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s ) WHERE ID = %d
							", $matched, ( URL::trail( $wpupload['url'] ).File::basename( $matched ) ), $post->ID ) );
						}
					}
				}

				File::remove( $paths );

				$meta = wp_generate_attachment_metadata( $clean_id, $moved_path );
				wp_update_attachment_metadata( $clean_id, $meta );

				$wpdb->query( $wpdb->prepare( "
					UPDATE {$wpdb->posts} SET guid = %s WHERE ID = %d
				", sanitize_url( URL::trail( $wpupload['url'] ).$clean_file ), $clean_id ) );

				update_attached_file( $clean_id, $moved_path );

				$moved[$clean_id] = URL::trail( $wpupload['subdir'] ).$clean_file;
			}
		}

		return count( $moved );
	}

	// FIXME: WTF?!
	public function cache_in_content( $post_id )
	{
		return TRUE;
	}

	// FIXME: WTF?!
	public function ssl_correction( $post_id )
	{
		return TRUE;
	}

	public function purge_meta( $post_id )
	{
		delete_post_meta( $post_id, '_thumbnail_id' );
		delete_post_meta( $post_id, '_gtheme_images' );
		delete_post_meta( $post_id, '_gtheme_images_terms' );

		return TRUE;
	}

	public function single_post_title( $post_title, $post )
	{
		if ( 'attachment' == $post->post_type ) {

			if ( $alt = WPMedia::getAttachmentImageAlt( $post->ID, FALSE ) )
				return $alt;

			if ( $caption = wp_get_attachment_caption( $post->ID ) )
				return wp_trim_words( trim( strip_tags( $caption ) ), 7, ' '.'[&hellip;]' );
		}

		return $post_title;
	}

	public function bulk_actions( $actions )
	{
		return array_merge( $actions, [ 'cleanattachments' => _x( 'Clean Attachments', 'Modules: Media: Bulk Action', 'gnetwork' ) ] );
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

		if ( empty( $parents ) )
			return $redirect_to;

		$ids = maybe_serialize( implode( ',', array_map( 'intval', array_unique( $parents ) ) ) );

		return $this->get_menu_url( 'images', NULL, 'tools', [ 'id' => $ids ] );
	}

	public function media_row_actions( $actions, $post, $detached )
	{
		$url = wp_get_attachment_url( $post->ID );

		if ( WordPress::cuc( $this->options['tools_accesscap'] )
			&& wp_attachment_is( 'image', $post->ID ) ) {

			$actions['media-clean'] = HTML::tag( 'a', [
				'target' => '_blank',
				'class'  => [ 'media-clean-attachment', ( $post->post_parent ? '' : '-disabled' ) ],
				'href'   => $post->post_parent ? $this->get_menu_url( 'images', NULL, 'tools', [ 'id' => $post->post_parent ] ) : '#',
				'data'   => [
					'id'      => $post->ID,
					'parent'  => $post->post_parent,
					'nonce'   => wp_create_nonce( $this->hook( $post->ID ) ),
					'spinner' => _x( 'Cleaning &hellip;', 'Modules: Media: Row Action', 'gnetwork' ),
				],
			], _x( 'Clean', 'Modules: Media: Row Action', 'gnetwork' ) );
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

		$link.= '<div class="media-url-box"><input type="text" class="widefat media-url-field" value="'.esc_url( $url ).'" readonly></div>';

		$actions['media-url'] = $link;

		return $actions;
	}

	public function ajax()
	{
		$post = self::unslash( $_REQUEST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'upload_chaunk':

				Ajax::checkReferer( $this->classs( 'file-upload' ) );

				$stored = $this->store_chaunk( $post['file'], $post['file_data'], (int) $post['chunk'] );

				if ( TRUE === $stored )
					Ajax::success();

				Ajax::error( $stored );

			break;
			case 'upload_complete':

				Ajax::checkReferer( $this->classs( 'file-upload' ) );

				$completed = $this->complete_upload( $post['file'] );

				if ( $completed[0] )
					Ajax::success( $completed[1] );
				else
					Ajax::errorMessage( $completed[1] );

			break;
			case 'clean_attachment':

				if ( empty( $post['attachment'] ) )
					Ajax::errorMessage();

				Ajax::checkReferer( $this->hook( $post['attachment'] ) );

				if ( ! $this->clean_attachment( $post['attachment'] ) )
					Ajax::errorMessage();

				Ajax::success( _x( 'Cleaned', 'Modules: Media: Row Action', 'gnetwork' ) );

			break;
			case 'clean_post':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::checkReferer( $this->hook( $post['post_id'] ) );

				$count = $this->clean_attachments( $post['post_id'] );

				if ( FALSE === $count )
					Ajax::errorMessage();

				if ( TRUE === $count || 0 === $count )
					Ajax::success( _x( 'Already Cleaned!', 'Modules: Media: Row Action', 'gnetwork' ) );

				Ajax::success( _x( 'Cleaned!', 'Modules: Media: Row Action', 'gnetwork' ) );

			break;
			case 'sync_post':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::checkReferer( $this->hook( $post['post_id'] ) );

				$count = $this->sync_attachments( $post['post_id'] );

				if ( FALSE === $count )
					Ajax::errorMessage();

				if ( TRUE === $count || 0 === $count )
					Ajax::success( _x( 'Already Synced!', 'Modules: Media: Row Action', 'gnetwork' ) );

				Ajax::success( _x( 'Synced!', 'Modules: Media: Row Action', 'gnetwork' ) );

			break;
			case 'cache_post':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::checkReferer( $this->hook( $post['post_id'] ) );

				if ( ! $this->cache_in_content( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::success( _x( 'Cached', 'Modules: Media: Row Action', 'gnetwork' ) );

			break;
			case 'correct_post':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::checkReferer( $this->hook( $post['post_id'] ) );

				if ( ! $this->ssl_correction( $post['post_id'] ) )
					Ajax::errorMessage();

				Ajax::success( _x( 'SSL Corrected', 'Modules: Media: Row Action', 'gnetwork' ) );
		}

		Ajax::errorWhat();
	}

	// @REF: https://github.com/deliciousbrains/wp-dbi-file-uploader
	// @REF: https://deliciousbrains.com/?p=26646
	public function render_widget_uploader()
	{
		HTML::desc( $this->options['dashboard_intro'], TRUE, '-intro' );

		$html = '<form>'.Ajax::spinner();
		$html.= '<div id="'.$this->classs( 'file-progress' ).'" class="-messages">';
		$html.= _x( 'Please select a file and click &#8220;Upload&#8221; to continue.', 'Modules: Media', 'gnetwork' );
		$html.= '</div>';

		$html.= '<div><label for="'.$this->classs( 'file-upload' ).'" class="button button-small">';
		$html.= _x( 'Select File', 'Modules: Media', 'gnetwork' ).'</label>';

		$html.= HTML::tag( 'input', [
			'id'    => $this->classs( 'file-upload' ),
			'type'  => 'file',
			'style' => 'display:none',
		] );

		$html.= HTML::tag( 'input', [
			'id'    => $this->classs( 'file-submit' ),
			'type'  => 'submit',
			'class' => [ 'button', 'button-small', 'button-primary' ],
			'value' => _x( 'Upload', 'Modules: Media', 'gnetwork' ),
			'data'  => [
				'nonce'    => wp_create_nonce( $this->classs( 'file-upload' ) ),
				'locale'   => L10n::locale(),
				/* translators: %s: progress precent */
				'progress' => _x( 'Uploading File - %s%', 'Modules: Media', 'gnetwork' ),
				'complete' => _x( 'Upload Complete!', 'Modules: Media', 'gnetwork' ),
			],
			'disabled' => TRUE,
		] );

		$html.= '</div><code id="'.$this->classs( 'file-name' ).'" class="-filename" style="display:none"></code></form>';

		echo $this->wrap( $html, '-widget-form' );
	}

	protected function get_widget_uploader_info()
	{
		return _x( 'You can access uploaded files via Media Library.', 'Modules: Media', 'gnetwork' );
	}

	// FIXME: must check filetype for non super admins / and delete file if not allowed
	// FIXME: maybe use `wp_check_filetype_and_ext()`
	// @REF: `media_handle_upload()`
	private function complete_upload( $filename, $metadata = FALSE )
	{
		$wpupload = WPMedia::upload();

		$file = sanitize_file_name( $filename );
		$type = wp_check_filetype( $file );

		$path = File::join( $wpupload['path'], $file );
		$url  = str_replace( $wpupload['basedir'], $wpupload['baseurl'], $file );

		$ext   = pathinfo( $filename, PATHINFO_EXTENSION );
		$name  = File::basename( $filename, ".$ext" );
		$title = sanitize_text_field( $name );

		$id = wp_insert_attachment( [
			'guid'           => $url,
			'post_title'     => $title,
			'post_mime_type' => $type['type'],
		], $path, 0, TRUE );

		if ( self::isError( $id ) )
			return [ FALSE, $id->get_error_message() ];

		if ( $metadata )
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $path ) );

		return [ TRUE, HTML::link( _x( 'View Uploaded File', 'Modules: Media', 'gnetwork' ), WordPress::getPostEditLink( $id ), TRUE ) ];
	}

	private function store_chaunk( $file, $data, $chunk = 0 )
	{
		if ( FALSE === ( $decoded = $this->decode_chunk( $data ) ) )
			return _x( 'Something is wrong with data!', 'Modules: Media', 'gnetwork' );

		$wpupload = WPMedia::upload();

		if ( FALSE !== $wpupload['error'] )
			return _x( 'Can not access upload folders!', 'Modules: Media', 'gnetwork' );

		$path = File::join( $wpupload['path'], sanitize_file_name( $file ) );

		if ( 0 === $chunk && file_exists( $path ) )
			return _x( 'The file is already exists in upload folder!', 'Modules: Media', 'gnetwork' );

		if ( ! file_put_contents( $path, $decoded, FILE_APPEND ) )
			return _x( 'Can not put contents into file!', 'Modules: Media', 'gnetwork' );

		return TRUE;
	}

	private function decode_chunk( $data )
	{
		$parts = explode( ';base64,', $data );

		if ( ! is_array( $parts ) || ! isset( $parts[1] ) )
			return FALSE;

		return base64_decode( $parts[1] ) ?: FALSE;
	}

	private function get_media_type_label( $post_id, $mime_type = NULL )
	{
		if ( is_null( $mime_type ) )
			$mime_type = get_post_mime_type( $post_id );

		switch ( $mime_type ) {

			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
			case 'image/webp':
			case 'image/svg+xml':

				$label = _x( 'View Image URL', 'Modules: Media: Row Action', 'gnetwork' );

			break;
			case 'video/mpeg':
			case 'video/mp4':
			case 'video/webm':
			case 'video/ogg':
			case 'video/quicktime':

				$label = _x( 'View Video URL', 'Modules: Media: Row Action', 'gnetwork' );

			break;
			case 'text/csv':
			case 'text/xml':

				$label = _x( 'View Data File URL', 'Modules: Media: Row Action', 'gnetwork' );

			break;
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
			case 'application/vnd.ms-excel':

				$label = _x( 'View Spreadsheet URL', 'Modules: Media: Row Action', 'gnetwork' );

			break;
			case 'application/pdf':
			case 'application/rtf':
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':

				$label = _x( 'View Document URL', 'Modules: Media: Row Action', 'gnetwork' );

			break;
			case 'text/html':

				$label = _x( 'View HTML file URL', 'Modules: Media: Row Action', 'gnetwork' );

			break;
			default:

				$label = _x( 'View Item URL', 'Modules: Media: Row Action', 'gnetwork' );
		}

		return $this->filters( 'mime_type_label', $label, $mime_type, $post_id );
	}

	// FIXME: add based on current user: needs testing: for svg,json
	// FIXME: add general option to select mimes
	// FIXME: add administrative option to select mimes: svg,json
	// @SEE: https://core.trac.wordpress.org/ticket/40175
	// @SEE: https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
	public function upload_mimes( $mimes )
	{
		return array_merge( $mimes, [
			'pptx'      => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'ppt'       => 'application/vnd.ms-powerpoint',
			'doc'       => 'application/msword',
			'docx|docm' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'       => 'application/vnd.ms-excel', // @SEE: https://core.trac.wordpress.org/ticket/39550#comment:156
			'xlsx'      => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'csv'       => 'text/csv',
			'json'      => 'text/plain', // 'application/json', // @REF: https://stackoverflow.com/a/477819/
			'xml'       => 'text/xml',
			'md'        => 'text/markdown',
			'webm'      => 'video/webm',
			'flv'       => 'video/x-flv',
			'ac3'       => 'audio/ac3',
			'mpa'       => 'audio/MPA',
			'mp4|mpg4'  => 'video/mp4',
			'flv'       => 'video/x-flv',
			'svg|svgz'  => 'image/svg+xml',
			'psd'       => 'image/vnd.adobe.photoshop',
			'mobi'      => 'application/x-mobipocket-ebook', // 'application/octet-stream'
			'epub'      => 'application/epub+zip', // 'application/octet-stream'
			'bib'       => 'application/x-bibtex', // 'text/plain', // @REF: http://fileformats.archiveteam.org/wiki/BibTeX
		] );
	}

	public function wp_editor_set_quality( $quality, $mime_type )
	{
		switch ( $mime_type ) {
			case 'image/jpeg': return $this->options['quality_jpeg'] ?: $quality;
			case 'image/webp': return $this->options['quality_webp'] ?: $quality;
		}

		return $quality;
	}

	// @REF: https://core.trac.wordpress.org/ticket/52867
	// @REF: https://github.com/adamsilverstein/modern-images-wp
	public function image_editor_output_format( $map, $filename, $mime_type )
	{
		foreach ( $this->option['output_format'] as $format )
			$map['image/'.$format] = 'image/webp';

		return $map;
	}

	// @REF: https://gist.github.com/rmpel/e1e2452ca06ab621fe061e0fde7ae150
	// @SEE: https://core.trac.wordpress.org/ticket/45615
	// @SEE: https://make.wordpress.org/core/2018/12/13/backwards-compatibility-breaks-in-5-0-1/
	// WORKING but DISABLED
	public function wp_check_filetype_and_ext( $data, $file, $filename, $mimes )
	{
		if ( extension_loaded( 'fileinfo' ) ) {

			// with the php-extension, a CSV file is issues type text/plain
			// so we fix that back to text/csv by trusting the file extension
			$finfo     = finfo_open( FILEINFO_MIME_TYPE );
			$real_mime = finfo_file( $finfo, $file );
			finfo_close( $finfo );

			if ( 'text/plain' === $real_mime
				&& preg_match( '/\.(csv)$/i', $filename ) ) {

				$data['ext']  = 'csv';
				$data['type'] = 'text/csv';
			}

		} else {

			// without the php-extension, we probably don't have the issue
			// at all, but just to be sure
			if ( preg_match( '/\.(csv)$/i', $filename ) ) {

				$data['ext']  = 'csv';
				$data['type'] = 'text/csv';
			}
		}

		return $data;
	}

	// overrides the attachment url with short-link
	public function image_send_to_editor( $html, $id, $caption, $title, $align, $url, $size, $alt, $rel = '' )
	{
		if ( strpos( $url, 'attachment_id' ) || $url == get_attachment_link( $id ) )
			return WPMedia::htmlAttachmentShortLink( $id,
				get_image_tag( $id, $alt, '', $align, $size ), '-image-link' );

		return $html;
	}

	public function media_send_to_editor( $html, $id, $data )
	{
		if ( ! $attachment = get_post( $id ) )
			return $html;

		// if no link provided, check for shortcode supported types
		if ( empty( $data['url'] ) ) {

			// TODO: must use filter system

			// FIXME: not working!!
			if ( 'text/csv' == $attachment->post_mime_type )
				return '[csv id="'.$id.'"]'
					.WPMedia::htmlAttachmentShortLink( $id, $html ).'[/csv]';

			// WORKING BUT DISABLED: shortcode not supported, yet!
			// if ( 'application/epub+zip' == $attachment->post_mime_type )
			// 	return '[epub id="'.$id.'"]'
			// 		.WPMedia::htmlAttachmentShortLink( $id, $html ).'[/epub]';

			if ( 'application/pdf' == $attachment->post_mime_type )
				return '[pdf url="'.wp_get_attachment_url( $id ).'"]'
					.WPMedia::htmlAttachmentShortLink( $id, $html ).'[/pdf]';

			// bail if no link
			return $html;
		}

		// we use another hook for images
		if ( 'image' === substr( $attachment->post_mime_type, 0, 5 ) )
			return $html;

		// bail if it's custom link
		if ( trim( $data['url'] ) != get_attachment_link( $id ) )
			return $html;

		// core media js is failing to set title for video/audio
		$html = isset( $data['post_title'] ) ? $data['post_title'] : strip_tags( $html );

		return WPMedia::htmlAttachmentShortLink( $id, $html );
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

	// @SEE: https://core.trac.wordpress.org/ticket/38195
	public function post_mime_types( $post_mime_types )
	{
		return array_merge( $post_mime_types, [
			'text' => [
				_x( 'Text', 'Modules: Media: Post Mime Type', 'gnetwork' ),
				_x( 'Manage Texts', 'Modules: Media: Post Mime Type', 'gnetwork' ),
				/* translators: %s: media texts count */
				_nx_noop( 'Text <span class="count">(%s)</span>', 'Texts <span class="count">(%s)</span>', 'Modules: Media: Post Mime Type', 'gnetwork' ),
			],
			'application' => [
				_x( 'Application', 'Modules: Media: Post Mime Type', 'gnetwork' ),
				_x( 'Manage Applications', 'Modules: Media: Post Mime Type', 'gnetwork' ),
				/* translators: %s: media applications count */
				_nx_noop( 'Application <span class="count">(%s)</span>', 'Applications <span class="count">(%s)</span>', 'Modules: Media: Post Mime Type', 'gnetwork' ),
			],
		] );
	}

	// ADOPTED FROM: Filename Normalizer v1.0.1 by required
	// @SOURCE: https://github.com/wearerequired/filename-normalizer
	public function wp_handle_upload_prefilter( $file )
	{
		if ( ! normalizer_is_normalized( $file['name'] ) )
			$file['name'] = normalizer_normalize( $file['name'] );

		return $file;
	}

	// FIXME: waiting on: https://core.trac.wordpress.org/ticket/22363
	// FIXME: use https://wordpress.org/plugins/wpartisan-filename-sanitizer/
	// https://wpartisan.me/tutorials/rename-clean-wordpress-media-filenames
	public function sanitize_file_name( $filename, $filename_raw )
	{
		if ( ! seems_utf8( $filename ) )
			return $filename;

		$info = pathinfo( $filename );
		$ext  = empty( $info['extension'] ) ? '' : '.'.$info['extension'];

		// $name = preg_replace( '/'.$ext.'$/', '', $filename );
		$name = File::basename( $filename, $ext );
		$name = Text::formatSlug( $name );
		$name = trim( remove_accents( $name ) );

		$name = Utilities::URLifyDownCode( $name );
		// $name = Utilities::URLifyFilter( trim( $name ) );

		return Text::strToLower( $name ).$ext;
	}

	public function get_circular_player( $html, $source, $size = NULL, $mimetype = NULL )
	{
		return do_shortcode( '[circular-player src="'.$source.'" size="'.( $size ?: '' ).'" /]' );
	}

	public function enqueue_circular_player( $selector = '.mediPlayer' )
	{
		Scripts::enqueueCircularPlayer( $selector );
	}
}

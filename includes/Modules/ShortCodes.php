<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class ShortCodes extends gNetwork\Module
{

	protected $key     = 'shortcodes';
	protected $network = FALSE;

	private $ref_ids    = [];
	private $ref_list   = FALSE;
	private $people     = [];
	private $people_tax = 'post_tag'; // 'people';

	protected function setup_actions()
	{
		add_action( 'init', [ $this, 'init_early' ], 8 );
		add_action( 'init', [ $this, 'init_late' ], 12 );
		add_action( 'wp_footer', [ $this, 'print_scripts' ], 20 );

		$this->action( 'register_shortcode_ui' );
	}

	public function init_early()
	{
		if ( defined( 'GPEOPLE_PEOPLE_TAXONOMY' ) )
			$this->people_tax = GPEOPLE_PEOPLE_TAXONOMY;

		else if ( defined( 'GNETWORK_GPEOPLE_TAXONOMY' ) )
			$this->people_tax = GNETWORK_GPEOPLE_TAXONOMY;

		// fallback short-codes
		add_shortcode( 'book', [ $this, 'shortcode_return_content' ] );
		add_shortcode( 'person', [ $this, 'shortcode_person' ] );
	}

	public function init_late()
	{
		$this->register_blocktypes();
		$this->register_shortcodes();

		if ( is_admin() ) {

			add_action( 'gnetwork_tinymce_strings', [ $this, 'tinymce_strings' ] );

			Admin::registerTinyMCE( 'gnetworkref', 'assets/js/tinymce/ref', 2 );
			// Admin::registerTinyMCE( 'gnetworkemail', 'assets/js/tinymce/email', 2 );
			// Admin::registerTinyMCE( 'gnetworksearch', 'assets/js/tinymce/search', 2 );
			// Admin::registerTinyMCE( 'gnetworkgpeople', 'assets/js/tinymce/gpeople', 2 );

		} else {

			$this->filter( [
				// no need for editorial
				'gnetwork_prep_contact',
				'gtheme_prep_contact',
			], 3, 12 );


			if ( ! defined( 'GNETWORK_DISABLE_REFLIST_INSERT' ) || ! GNETWORK_DISABLE_REFLIST_INSERT ) {

				add_action( 'gnetwork_themes_content_after', [ $this, 'content_after_reflist' ], 5 );

				$this->filter( 'amp_post_article_footer_meta', 1, 9 );
				$this->filter( 'amp_post_template_file', 3 );
			}

			$this->filter( 'headings_toc', 1, 9999, FALSE, 'geditorial' );
		}
	}

	protected function get_blocktypes()
	{
		return [
			[
				'post-title',
				[
					'attributes' => [
						'post' => [
							'default' => '',
							'type'    => 'string',
						],
						'link' => [
							'default' => TRUE,
							'type'    => 'boolean',
						],
						'wrap' => [
							'default' => '',
							'type'    => 'string',
						],
						'alignment' => [
							'default' => 'none',
							'type'    => 'string',
						],
					],
				],
			],
		];
	}

	protected function get_shortcodes()
	{
		return [
			'children'    => 'shortcode_children',
			'siblings'    => 'shortcode_siblings',
			'in-term'     => 'shortcode_in_term',
			'all-terms'   => 'shortcode_all_terms',
			'back'        => 'shortcode_back',
			'button'      => 'shortcode_button',
			'image'       => 'shortcode_image',
			'iframe'      => 'shortcode_iframe',
			'thickbox'    => 'shortcode_thickbox',
			'email'       => 'shortcode_email',
			'tel'         => 'shortcode_tel',
			'sms'         => 'shortcode_sms',
			'google-form' => 'shortcode_google_form',
			'markdown'    => 'shortcode_markdown',
			'raw'         => 'shortcode_raw',
			'pdf'         => 'shortcode_pdf',
			'csv'         => 'shortcode_csv',
			'bloginfo'    => 'shortcode_bloginfo',
			'ref'         => 'shortcode_ref',
			'reflist'     => 'shortcode_reflist',
			'ref-m'       => 'shortcode_ref_manual',
			'reflist-m'   => 'shortcode_reflist_manual',
			'qrcode'      => 'shortcode_qrcode',
			'search'      => 'shortcode_search',
			// 'last-edited' => 'shortcode_last_edited', // DEPRECATED: use Editorial `Modified` Module
			// 'lastupdate'  => 'shortcode_last_edited', // DEPRECATED: use Editorial `Modified` Module
			'redirect'    => 'shortcode_redirect',
			'menu'        => 'shortcode_menu',
			'post-title'  => 'shortcode_post_title',
			// 'post-excerpt' => 'shortcode_post_excerpt', // TODO
			// 'post-content'  => 'shortcode_post_content', // TODO: https://gist.github.com/wpscholar/84376bb44afabdfa9d93e83b0c87abb8
			'post-link'   => 'shortcode_permalink',
			'permalink'   => 'shortcode_permalink',
		];
	}

	// @REF: https://github.com/wp-shortcake/shortcake/wiki/Registering-Shortcode-UI
	public function register_shortcode_ui()
	{
		shortcode_ui_register_for_shortcode( 'ref', [
			'label'         => Core\HTML::escape( _x( 'Reference', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
			'listItemImage' => 'dashicons-editor-quote',
			'inner_content' => [
				'label'       => Core\HTML::escape( _x( 'Reference', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
				'description' => Core\HTML::escape( _x( 'Make a reference to an external source.', 'Modules: ShortCodes: UI: Description', 'gnetwork-admin' ) ),
			],
			'attrs' => [
				[
					'label'  => Core\HTML::escape( _x( 'External Resource', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
					'attr'   => 'url',
					'type'   => 'text',
					'encode' => TRUE,
					'meta'   => [
						'placeholder' => Core\URL::home( 'about-this' ),
						'dir'         => 'ltr',
					],
				],
				[
					'label' => Core\HTML::escape( _x( 'External Resource Hover', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
					'attr'  => 'url_title',
					'type'  => 'text',
					'meta'  => [
						'placeholder' => Core\HTML::escape( _x( 'Read more about it', 'Modules: ShortCodes: UI: Placeholder', 'gnetwork-admin' ) ),
					],
				],
			],
		] );

		shortcode_ui_register_for_shortcode( 'email', [
			'label'         => Core\HTML::escape( _x( 'Email', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
			'listItemImage' => 'dashicons-email-alt',
			'inner_content' => [
				'label'       => Core\HTML::escape( _x( 'Email Address', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
				'description' => Core\HTML::escape( _x( 'Full email address to appear as link and cloaked against spam bots.', 'Modules: ShortCodes: UI: Description', 'gnetwork-admin' ) ),
				'meta'        => [ 'dir' => 'ltr' ],
			],
			'attrs' => [
				[
					'label' => Core\HTML::escape( _x( 'Display Text', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
					'attr'  => 'content',
					'type'  => 'text',
					'meta'  => [ 'placeholder' => Core\HTML::escape( _x( 'Email Me', 'Modules: ShortCodes: UI: Placeholder', 'gnetwork-admin' ) ) ],
				],
				[
					'label' => Core\HTML::escape( _x( 'Email Subject', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
					'attr'  => 'subject',
					'type'  => 'text',
					'meta'  => [ 'placeholder' => Core\HTML::escape( _x( 'About something important', 'Modules: ShortCodes: UI: Placeholder', 'gnetwork-admin' ) ) ],
				],
				[
					'label' => Core\HTML::escape( _x( 'Link Hover', 'Modules: ShortCodes: UI: Label', 'gnetwork-admin' ) ),
					'attr'  => 'title',
					'type'  => 'text',
					'meta'  => [ 'placeholder' => Core\HTML::escape( _x( 'Jump right into it!', 'Modules: ShortCodes: UI: Placeholder', 'gnetwork-admin' ) ) ],
				],
			],
		] );
	}

	public function tinymce_strings( $strings )
	{
		$new = [
			'gnetworkref-title' => _x( 'Cite This', 'TinyMCE Strings: Ref', 'gnetwork-admin' ),
			'gnetworkref-attr'  => _x( 'Cite This (Ctrl+Q)', 'TinyMCE Strings: Ref', 'gnetwork-admin' ),
			'gnetworkref-text'  => _x( 'Ref Text', 'TinyMCE Strings: Ref', 'gnetwork-admin' ),
			'gnetworkref-url'   => _x( 'Ref URL', 'TinyMCE Strings: Ref', 'gnetwork-admin' ),

			'gnetworkemail-title'   => _x( 'Email', 'TinyMCE Strings: Email', 'gnetwork-admin' ),
			'gnetworkemail-attr'    => _x( 'Email (Ctrl+E)', 'TinyMCE Strings: Email', 'gnetwork-admin' ),
			'gnetworkemail-email'   => _x( 'Full Email', 'TinyMCE Strings: Email', 'gnetwork-admin' ),
			'gnetworkemail-text'    => _x( 'Display Text', 'TinyMCE Strings: Email', 'gnetwork-admin' ),
			'gnetworkemail-subject' => _x( 'Email Subject', 'TinyMCE Strings: Email', 'gnetwork-admin' ),
			'gnetworkemail-hover'   => _x( 'Link Hover', 'TinyMCE Strings: Email', 'gnetwork-admin' ),

			'gnetworksearch-title' => _x( 'Search', 'TinyMCE Strings: Search', 'gnetwork-admin' ),
			'gnetworksearch-attr'  => _x( 'Search (Ctrl+3)', 'TinyMCE Strings: Search', 'gnetwork-admin' ),
			'gnetworksearch-text'  => _x( 'Display Text', 'TinyMCE Strings: Search', 'gnetwork-admin' ),
			'gnetworksearch-query' => _x( 'Override Criteria', 'TinyMCE Strings: Search', 'gnetwork-admin' ),

			'gnetworkgpeople-title' => _x( 'People', 'TinyMCE Strings: People', 'gnetwork-admin' ),
			'gnetworkgpeople-attr'  => _x( 'People', 'TinyMCE Strings: People', 'gnetwork-admin' ),
			'gnetworkgpeople-name'  => _x( 'Name', 'TinyMCE Strings: People', 'gnetwork-admin' ),
		];

		return array_merge( $strings, $new );
	}

	public function gnetwork_prep_contact( $prepared, $value, $title = NULL )
	{
		if ( is_email( $value ) )
			return $this->shortcode_email( [
				'email'    => $value,
				'content'  => $title,
				'fallback' => FALSE,
				'wrap'     => FALSE,
			] );

		return $prepared;
	}

	public function shortcode_return_content( $atts, $content = NULL, $tag = '' )
	{
		return $content;
	}

	public function shortcode_children( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => get_queried_object_id(),
			'type'    => 'page',
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! is_singular( $args['type'] ) )
			return $content;

		$children = wp_list_pages( [
			'child_of'     => $args['id'],
			'post_type'    => $args['type'],
			'echo'         => FALSE,
			'depth'        => 1,
			'title_li'     => '',
			'item_spacing' => 'discard',
			'sort_column'  => 'menu_order, post_title',
		] );

		if ( ! $children )
			return $content;

		return self::shortcodeWrap( '<ul>'.$children.'</ul>', 'children', $args );
	}

	public function shortcode_siblings( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'parent'  => NULL,
			'type'    => 'page',
			'ex'      => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! is_singular( $args['type'] ) )
			return $content;

		if ( is_null( $args['parent'] ) ) {
			$object = get_queried_object();
			if ( $object && isset( $object->post_parent ) )
				$args['parent'] = $object->post_parent;
		}

		if ( ! $args['parent'] )
			return $content;

		if ( is_null( $args['ex'] ) )
			$args['ex'] = get_queried_object_id();

		$siblings = wp_list_pages( [
			'child_of'     => $args['parent'],
			'post_type'    => $args['type'],
			'exclude'      => $args['ex'],
			'echo'         => FALSE,
			'depth'        => 1,
			'title_li'     => '',
			'item_spacing' => 'discard',
			'sort_column'  => 'menu_order, post_title',
		] );

		if ( ! $siblings )
			return $content;

		return self::shortcodeWrap( '<ul>'.$siblings.'</ul>', 'siblings', $args );
	}

	// FIXME: move to gEditorial Terms (using api)
	// USAGE: [in-term tax="category" slug="uncategorized" order="menu_order" /]
	// EDITED: 4/5/2016, 5:03:30 PM
	public function shortcode_in_term( $atts = [], $content = NULL, $tag = '' )
	{
		global $post;

		$args = shortcode_atts( [
			'id'            => FALSE,
			'slug'          => FALSE,
			'tax'           => 'category',
			'type'          => 'post',
			'title'         => NULL, // FALSE to disable
			'title_link'    => NULL, // FALSE to disable
			'title_title'   => '',
			'title_tag'     => 'h3',
			'title_anchor'  => 'term-',
			'list'          => 'ul',
			'limit'         => '-1',
			'future'        => 'off',
			'li_link'       => TRUE,
			'li_before'     => '',
			'li_title'      => '', // use %s for post title
			'li_anchor'     => 'post-',
			'order_before'  => FALSE,
			'order_sep'     => ' - ',
			'order_zeroise' => FALSE,
			'orderby'       => 'date',
			'order'         => 'ASC',
			'cb'            => FALSE,
			'context'       => NULL,
			'wrap'          => TRUE,
			'before'        => '',
			'after'         => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		// if ( ! is_singular( $args['type'] ) )
		// 	return $content;

		$error = $term = FALSE;
		$html  = $tax_query = '';

		$key   = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, 'gnetwork-term' );

		if ( FALSE !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = FALSE;

		if ( $args['id'] && $args['id'] ) {

			if ( $term = get_term_by( 'id', $args['id'], $args['tax'] ) )
				$tax_query = [ [
					'taxonomy' => $args['tax'],
					'field'    => 'term_id',
					'terms'    => [ $args['id'] ],
				] ];

			else
				$error = TRUE;

		} else if ( $args['slug'] && $args['slug'] ) {

			if ( $term = get_term_by( 'slug', $args['slug'], $args['tax'] ) )
				$tax_query = [ [
					'taxonomy' => $args['tax'],
					'field'    => 'slug',
					'terms'    => [ $args['slug'] ],
				] ];

			else
				$error = TRUE;

		} else if ( $post->post_type == $args['type'] ) {

			$terms = get_the_terms( $post, $args['tax'] );

			if ( $terms && ! self::isError( $terms ) ) {

				foreach ( $terms as $term )
					$term_list[] = $term->term_id;

				$tax_query = [ [
					'taxonomy' => $args['tax'],
					'field'    => 'term_id',
					'terms'    => $term_list,
				] ];

			} else {
				$error = TRUE;
			}
		}

		if ( $error )
			return $content;

		$args['title'] = self::shortcodeTermTitle( $args, $term );

		if ( 'on' == $args['future'] )
			$post_status = [ 'publish', 'future', 'draft' ];
		else
			$post_status = [ 'publish' ];

		$query_args = [
			'tax_query'              => $tax_query,
			'posts_per_page'         => $args['limit'],
			'orderby'                => $args['orderby'],
			'order'                  => $args['order'],
			'post_type'              => $args['type'],
			'post_status'            => $post_status,
			'suppress_filters'       => TRUE,
			'no_found_rows'          => TRUE, // counts posts, remove if pagination required
			'update_post_term_cache' => FALSE, // grabs terms
			'update_post_meta_cache' => FALSE, // grabs post meta
		];

		$query = new \WP_Query;
		$posts = $query->query( $query_args );

		if ( count( $posts ) ) {
			foreach ( $posts as $post ) {

				$list  = '';
				setup_postdata( $post );

				if ( $args['cb'] ) {
					$list = call_user_func_array( $args['cb'], [ $post, $args ] );

				} else {

					$title = get_the_title( $post->ID );
					$order = $args['order_before'] ? Core\Number::localize( $args['order_zeroise'] ? zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order ).$args['order_sep'] : '';

					if ( 'publish' == $post->post_status && $args['li_link'] )
						$list = $args['li_before'].Core\HTML::tag( 'a', [
							'href'  => apply_filters( 'the_permalink', get_permalink( $post ), $post ),
							'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
							'class' => '-link',
						], $order.$title );

					else
						$list = $args['li_before'].Core\HTML::tag( 'span', [
							'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
							'class' => $args['li_link'] ? '-future' : FALSE,
						], $order.$title );

					// TODO: add excerpt/content of the post
					// TODO: add show/more js
				}

				$html.= Core\HTML::tag( 'li', [
					'id'    => $args['li_anchor'].$post->ID,
					'class' => '-item',
				], $list );
			}

			$html = Core\HTML::tag( $args['list'], [ 'class' => '-list' ], $html );

			if ( $args['title'] )
				$html = $args['title'].$html;

			$html = self::shortcodeWrap( $html, 'in-term', $args );

			wp_reset_postdata();
			wp_cache_set( $key, $html, 'gnetwork-term' );

			return $html;
		}

		return $content;
	}

	// FIXME: move to `gEditorial` Terms (using api)
	// FIXME: working draft
	// EDITED: 4/5/2016, 5:01:31 PM
	public function shortcode_all_terms( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => get_queried_object_id(),
			'tax'     => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = get_post( $args['id'] ) )
			return $content;

		$html = '';

		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {

			if ( ! is_taxonomy_viewable( $taxonomy ) )
				continue;

			if ( $args['tax'] && ! in_array( $taxonomy->name, explode( ',', $args['tax'] ) ) )
				continue;

			if ( $terms = get_the_terms( $post, $taxonomy->name ) ) {

				$html.= '<h3>'.$taxonomy->label.'</h3><ul class="-tax">';

				foreach ( $terms as $term )
					$html.= vsprintf( '<li class="-term"><a href="%1$s">%2$s</a></li>', [
						esc_url( get_term_link( $term, $term->taxonomy ) ),
						sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy->name, 'display' ),
					] );

				$html.= '</ul>';
			}
		}

		return self::shortcodeWrap( $html, 'all-terms', $args );
	}

	// NOTE: DEPRECATED: not registered here and it's for BACK-COMPATIBILITY
	public function shortcode_last_edited( $atts = [], $content = NULL, $tag = '' )
	{
		if ( ! function_exists( 'gEditorial' ) )
			return $content;

		if ( ! gEditorial()->enabled( 'modified' ) )
			return $content;

		return gEditorial()->module( 'modified' )->post_modified_shortcode( $atts, $content, $tag );
	}

	public function shortcode_menu( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'menu'            => '',
			'container'       => '',
			'container_class' => '',
			'container_id'    => '',
			'menu_class'      => 'menu',
			'menu_id'         => '',
			'link_before'     => '',
			'link_after'      => '',
			'depth'           => 0,
			'walker'          => '',
			'theme_location'  => '',

			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$menu = array_merge( $args, [
			'echo'         => FALSE,
			'fallback_cb'  => FALSE,
			'item_spacing' => 'discard',
			'before'       => '',
			'after'        => '',
		] );

		return self::shortcodeWrap( wp_nav_menu( $menu ), 'menu', $args );
	}

	public function block_post_title_render_callback( $attributes, $content )
	{
		if ( empty( $attributes['post'] ) )
			$attributes['post'] = self::req( 'post_id', get_post() );

		$attributes['link'] = (bool) $attributes['link'];

		$html = $this->shortcode_post_title( array_merge( $attributes, [ 'wrap' => FALSE ] ) );

		return self::blockWrap( $html, 'post-title', $attributes, FALSE );
	}

	public function shortcode_post_title( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'post'    => NULL,
			'link'    => TRUE,
			'context' => NULL,
			'wrap'    => empty( $atts['tag'] ) ? TRUE : $atts['tag'], // back-comp
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = get_post( $args['post'] ) )
			return $content;

		if ( empty( $post->post_title ) )
			return $content;

		$html = Utilities::prepTitle( $post->post_title, $post->ID );

		if ( TRUE === $args['link'] )
			$html = Core\HTML::link( $html, apply_filters( 'the_permalink', get_permalink( $post ), $post ) );

		else if ( $args['link'] )
			$html = Core\HTML::link( $html, $args['link'] );

		return self::shortcodeWrap( $html, 'post-title', $args, FALSE );
	}

	public function shortcode_permalink( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'post'      => NULL,
			'slug'      => FALSE,
			'type'      => 'page', // only with slug
			'text'      => FALSE,
			'title'     => FALSE,
			'class'     => FALSE,
			'params'    => FALSE,
			'newwindow' => FALSE,
			'context'   => NULL,
			'wrap'      => TRUE,
			'before'    => '',
			'after'     => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$post = NULL;

		if ( $args['slug'] )
			$post = get_page_by_path( trim( $args['slug'] ), OBJECT, $args['type'] );

		if ( ! $post )
			$post = get_post( $args['post'] );

		if ( ! $post )
			return $content;

		if ( $args['text'] )
			$text = Core\Text::trim( $args['text'] );

		else if ( trim( $content ) )
			$text = Core\Text::wordWrap( $content );

		else if ( ! empty( $post->post_title ) )
			$text = Utilities::prepTitle( $post->post_title, $post->ID );

		else
			return $content;

		$html = Core\HTML::tag( 'a', [
			'href'   => apply_filters( 'the_permalink', get_permalink( $post ), $post ).( $args['params'] ? '?'.$args['params'] : '' ),
			'title'  => $args['title'] ?: FALSE,
			'class'  => $args['class'] ?: FALSE,
			'target' => $args['newwindow'] ? '_blank' : FALSE,
			'data'   => [ 'id' => $post->ID ],
		], $text );

		unset( $args['class'] );

		return self::shortcodeWrap( $html, 'permalink', $args, FALSE );
	}

	// TODO: more cases
	public function shortcode_back( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => get_queried_object_id(),
			'to'      => 'parent',
			'html'    => _x( 'Back', 'Modules: ShortCodes: Defaults', 'gnetwork' ),
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $args['to'] )
			return $content;

		$html = FALSE;

		switch ( $args['to'] ) {

			case 'parent':

				if ( $post = get_post( $args['id'] ) ) {

					if ( $parent = get_post( $post->post_parent ) ) {

						$html = Core\HTML::tag( 'a', [
							'href'        => apply_filters( 'the_permalink', get_permalink( $parent ), $parent ),
							'title'       => get_the_title( $parent ),
							'class'       => 'parent',
							'data-toggle' => 'tooltip',
							'rel'         => 'parent',
						], $args['html'] );

					} else {

						$html = Core\HTML::tag( 'a', [
							'href'        => home_url( '/' ),
							'title'       => _x( 'Home', 'Modules: ShortCodes: Defaults', 'gnetwork' ),
							'class'       => 'home',
							'data-toggle' => 'tooltip',
							'rel'         => 'home',
						], $args['html'] );
					}
				}

			break;
			case 'home':

				$html = Core\HTML::tag( 'a', [
					'href'        => home_url( '/' ),
					'title'       => _x( 'Home', 'Modules: ShortCodes: Defaults', 'gnetwork' ),
					'class'       => 'home',
					'data-toggle' => 'tooltip',
					'rel'         => 'home',
				], $args['html'] );
		}

		return $html ? self::shortcodeWrap( $html, 'back', $args ) : $content;
	}

	public function shortcode_button( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'url'       => FALSE,
			'genericon' => FALSE,
			'class'     => '',
			'context'   => NULL,
			'wrap'      => TRUE,
			'before'    => '',
			'after'     => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = '';

		$classes = Core\HTML::attrClass( '-button', 'button', $args['class'] );

		if ( $args['genericon'] )
			$html.= Core\HTML::getDashicon( $args['genericon'] );

		if ( $content )
			$html.= ' '.trim( $content );

		if ( $args['url'] )
			$html = Core\HTML::tag( 'a', [
				'href'  => $args['url'],
				'class' => $classes,
			], $html );

		else
			$html = Core\HTML::tag( 'button', [
				'class' => $classes,
			], $html );

		unset( $args['class'] );

		return self::shortcodeWrap( $html, 'button', $args, FALSE );
	}

	public function shortcode_image( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'        => FALSE,      // attachment id
			'src'       => FALSE,      // raw url
			'link'      => NULL,       // `page`/`parent`/`image`/`FALSE`
			'size'      => 'medium',
			'width'     => FALSE,
			'height'    => FALSE,
			'style'     => FALSE,
			'alignment' => FALSE,      // `center`/`left`/`right`/`none`
			'img_class' => FALSE,
			'figure'    => NULL,       // null for if has caption
			'caption'   => NULL,       // null for getting from attachment
			'alt'       => NULL,       // null for getting from attachment
			'rel'       => FALSE,
			'load'      => 'lazy',
			'context'   => NULL,
			'wrap'      => TRUE,
			'before'    => '',
			'after'     => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$src = FALSE;

		if ( $args['id'] && ( $attachment = get_post( $args['id'] ) ) ) {

			if ( wp_attachment_is_image( $attachment ) ) {

				if ( $image = wp_get_attachment_image_src( $attachment->ID, $args['size'], FALSE ) )
					$src = $image[0];

				if ( $src && is_null( $args['alt'] ) && ( $alt = WordPress\Media::getAttachmentImageAlt( $attachment->ID ) ) )
					$args['alt'] = $alt;

				if ( $src && is_null( $args['caption'] ) && ( $caption = wp_get_attachment_caption( $attachment->ID ) ) )
					$args['caption'] = $caption;

				if ( $src ) {

					if ( 'full' === $args['link'] )
						$args['link'] = wp_get_attachment_image_src( $attachment->ID, 'full', FALSE );

					else if ( 'image' === $args['link'] )
						$args['link'] = wp_get_attachment_url( $attachment->ID );

					else if ( 'parent' === $args['link'] && $attachment->post_parent )
						$args['link'] = get_permalink( $attachment->post_parent );

					else if ( 'page' === $args['link'] && $attachment->post_parent )
						$args['link'] = get_attachment_link( $attachment );

				} else if ( in_array( $args['link'], [ 'full', 'image', 'parent', 'page' ], TRUE ) ) {

					// $arg['link'] = FALSE; // NOTE: allows for custom links
				}
			}
		}

		if ( ! $src && $args['src'] ) {

			$src = $args['src'];
		}

		if ( ! $src )
			return $content;

		$html = Core\HTML::tag( 'img', [
			'src'     => $src,
			'alt'     => $args['alt'],
			'rel'     => $args['rel'],
			'width'   => $args['width'],
			'height'  => $args['height'],
			'loading' => $args['load'],
			'style'   => $args['style'],
			'class'   => Core\HTML::attrClass(
				'img-fluid',
				$args['figure'] ? 'figure-img' : ( $args['alignment'] ? sprintf( 'align%s', $args['alignment'] ) : '' ),
				$args['img_class']
			),
		] );

		if ( $args['link'] && ! in_array( $args['link'], [ 'full', 'image', 'parent', 'page' ], TRUE ) )
			$html = Core\HTML::link( $html, $args['link'] );

		if ( is_null( $args['figure'] ) && $args['caption'] )
			$args['figure'] = TRUE;

		if ( $args['figure'] ) {

			// NOTE: `$content` is fallback
			if ( $args['caption'] )
				$html.= '<figcaption class="figure-caption">'.$args['caption'].'</figcaption>';

			$html = '<figure class="'.Core\HTML::prepClass(
				'figure',
				$args['figure'],
				$args['alignment'] ? sprintf( 'align%s', $args['alignment'] ) : ''
			).'">'.$html.'</figure>';
		}

		return self::shortcodeWrap( $html, 'image', $args );
	}

	public function shortcode_iframe( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'url'     => FALSE,
			'width'   => '100%',
			'height'  => '520',
			'scroll'  => 'auto',
			'load'    => 'lazy',
			'style'   => 'width:100% !important;',
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] || WordPress\IsIt::xml() || WordPress\IsIt::rest() )
			return NULL;

		if ( ! $args['url'] )
			return $content;

		if ( ! in_array( $args['scroll'], [ 'auto', 'yes', 'no' ] ) )
			$args['scroll'] = 'no';

		if ( ! $content )
			$content = _x( 'Loading &hellip;', 'Modules: ShortCodes: Defaults', 'gnetwork' );

		$html = Core\HTML::tag( 'iframe', [
			'frameborder' => '0',
			'src'         => $args['url'],
			'style'       => $args['style'],
			'scrolling'   => $args['scroll'],
			'loading'     => $args['load'],
			'height'      => $args['height'],
			'width'       => $args['width'],
		], $content );

		return self::shortcodeWrap( $html, 'iframe', $args );
	}

	// @REF: https://codex.wordpress.org/Javascript_Reference/ThickBox
	public function shortcode_thickbox( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'url'     => FALSE,
			'title'   => NULL,
			'width'   => NULL,
			'height'  => NULL,
			'class'   => '',
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $args['url'] )
			return $content;

		$query = [ 'TB_iframe' => '1' ];

		if ( $args['width'] )
			$query['width'] = $args['width'];

		if ( $args['height'] )
			$query['height'] = $args['height'];

		if ( ! $content )
			$content = _x( 'More Info', 'Modules: ShortCodes: Defaults: ThickBox', 'gnetwork' );

		$html = Core\HTML::tag( 'a', [
			'href'    => add_query_arg( $query, $args['url'] ),
			'title'   => $args['title'],
			'class'   => Core\HTML::attrClass( 'thickbox', $args['class'] ),
			'onclick' => 'return false;',
		], $content );

		unset( $args['class'] );

		if ( ! WordPress\IsIt::xml() && ! WordPress\IsIt::rest() )
			Scripts::enqueueThickBox();

		return self::shortcodeWrap( $html, 'thickbox', $args, FALSE );
	}

	// [email subject="Email Subject"]you@you.com[/email]
	// http://www.cubetoon.com/2008/how-to-enter-line-break-into-mailto-body-command/
	// https://css-tricks.com/snippets/html/mailto-links/
	public function shortcode_email( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'subject'  => FALSE,
			'title'    => FALSE,
			'email'    => FALSE, // override
			'content'  => FALSE, // override
			'fallback' => TRUE,
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$text  = $args['content'] ? trim( $args['content'] ) : trim( $content ?: '' );
		$email = $args['email'] && is_email( $args['email'] ) ? trim( $args['email'] ) : trim( $content ?: '' );

		if ( ! $email && $args['fallback'] )
			$email = gNetwork()->email();

		if ( ! $email )
			return $content;

		if ( ! $text )
			$text = $email;

		$html = '<a class="email" href="'.antispambot( "mailto:".$email.( $args['subject'] ? '?subject='.urlencode( $args['subject'] ) : '' ) )
				.'"'.( $args['title'] ? ' data-toggle="tooltip" title="'.Core\HTML::escape( $args['title'] ).'"' : '' ).'>'
				.( $email == $text ? antispambot( $email ) : $text ).'</a>';

		return self::shortcodeWrap( $html, 'email', $args, FALSE );
	}

	// @REF: http://stackoverflow.com/a/13662220
	public function shortcode_tel( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'number'  => NULL,
			'title'   => FALSE,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( $args['number'] )
			$number = $args['number'];

		else if ( $content )
			$number = $content;

		else // what about default site tel
			return $content;

		if ( ! $content )
			$content = $number;

		$html = Core\HTML::tel( $number, $args['title'], Core\Number::localize( $content ) );

		return self::shortcodeWrap( $html, 'tel', $args, FALSE );
	}

	// @REF: http://stackoverflow.com/a/19126326
	// @TEST: http://bradorego.com/test/sms.html
	public function shortcode_sms( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'number'  => NULL,
			'body'    => FALSE,
			'title'   => FALSE,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( $args['number'] )
			$number = $args['number'];

		else if ( $content )
			$number = $content;

		else
			return $content;

		if ( ! $content )
			$content = $number;

		$html = '<a class="sms" href="'.Core\HTML::prepURLforSMS( $number )
				.( $args['body'] ? '?body='.rawurlencode( $args['body'] )
				.'" data-sms-body="'.Core\HTML::escape( $args['body'] ) : '' )
				.'"'.( $args['title'] ? ' data-toggle="tooltip" title="'.Core\HTML::escape( $args['title'] )
				.'"' : '' ).' data-sms-number="'.Core\HTML::escape( $number ).'">'
				.Core\HTML::wrapLTR( Core\Number::localize( $content ) ).'</a>';

		return self::shortcodeWrap( $html, 'sms', $args, FALSE );
	}

	public function shortcode_qrcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'size'    => NULL,
			'data'    => NULL,
			'type'    => NULL,
			'url'     => FALSE,
			'email'   => FALSE,
			'phone'   => FALSE,
			'sms'     => FALSE,
			'contact' => FALSE,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$type = $args['type'] ?? 'text';
		$data = $args['data'] ?? Core\Text::trim( $content );

		$supported = [
			'url'     => [ '\geminorum\gNetwork\Core\URL',    'sanitize' ],
			'email'   => [ '\geminorum\gNetwork\Core\Email',  'sanitize' ],
			'phone'   => [ '\geminorum\gNetwork\Core\Phone',  'sanitize' ],
			'sms'     => [ '\geminorum\gNetwork\Core\Mobile', 'sanitize' ],
			'contact' => [ '\geminorum\gNetwork\Core\Text',   'trim'     ],
		];

		foreach ( $supported as $datatype => $sanitizer ) {
			if ( ! empty( $args[$datatype] ) ) {
				$type = $datatype;
				$data = is_array( $args[$datatype] )
					? $args[$datatype]
					: call_user_func_array( $sanitizer, [ $args[$datatype] ] );
			}
		}

		if ( ! $data )
			return $content;

		$markup = Utilities::getQRCode(
			$data,
			$type,
			$args['size'] ?? 300,
			TRUE
		);

		if ( ! $markup )
			return $content;

		return self::shortcodeWrap(
			$markup,
			'qrcode',
			$args
		);
	}

	public function shortcode_search( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'for'     => FALSE, // override
			'url'     => FALSE, // override
			/* translators: `%s`: search criteria */
			'title'   => _x( 'Search this site for &ldquo;%s&rdquo;', 'Modules: ShortCodes: Defaults', 'gnetwork' ),
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $content )
			return $content;

		$text = trim( strip_tags( $content ) );
		$for  = $args['for'] ? trim( $args['for'] ) : $text;

		$html = Core\HTML::tag( 'a', [
			'href'  => WordPress\URL::search( $for, $args['url'] ),
			'title' => sprintf( $args['title'], $for ),
		], $text );

		return self::shortcodeWrap( $html, 'search', $args, FALSE );
	}

	public function shortcode_google_form( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'key'      => NULL,
			// 'template' => 'https://spreadsheets.google.com/embeddedform?formkey=%s',
			'template' => 'https://docs.google.com/forms/d/e/%s/viewform?embedded=true',
			'width'    => '760', // google form def
			'height'   => '500', // google form def
			'scroll'   => 'auto',
			'style'    => '',
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( WordPress\IsIt::xml() || WordPress\IsIt::rest() )
			return $content;

		if ( ! $args['key'] )
			return $content;

		return self::shortcode_iframe( array_merge( $args, [
			'url' => sprintf( $args['template'], $args['key'] ),
		] ), $content, $tag );
	}

	public function shortcode_markdown( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => FALSE,
			'url'     => FALSE,
			'path'    => FALSE,     // without `ABSPATH`
			'base'    => ABSPATH,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$markdown = FALSE;

		if ( $args['id'] && ( $path = get_attached_file( $args['id'] ) ) )
			$markdown = Core\File::getContents( $path );

		else if ( $args['path'] && Core\File::exists( $args['path'], $args['base'] ) )
			$markdown = Core\File::getContents( $args['base'].$args['path'] );

		else if ( $args['url'] )
			$markdown = Core\File::getContents( $args['url'] );

		if ( ! $markdown )
			return $content;

		return self::shortcodeWrap( Utilities::mdExtra( $markdown ), 'markdown', $args );
	}

	public function shortcode_raw( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => FALSE,
			'url'     => FALSE,
			'path'    => FALSE,     // without `ABSPATH`
			'base'    => ABSPATH,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$text = FALSE;

		if ( $args['id'] && ( $path = get_attached_file( $args['id'] ) ) )
			$text = Core\File::getContents( $path );

		else if ( $args['path'] && Core\File::exists( $args['path'], $args['base'] ) )
			$text = Core\File::getContents( $args['base'].$args['path'] );

		else if ( $args['url'] )
			$text = Core\File::getContents( $args['url'] );

		if ( ! $text )
			return $content;

		return self::shortcodeWrap( Core\HTML::tag( 'pre', $text ), 'raw', $args );
	}

	// TODO: download option
	// @REF: https://github.com/pipwerks/PDFObject
	public function shortcode_pdf( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'       => FALSE,
			'url'      => FALSE,
			'title'    => NULL,  // NULL to fallback to attachment title
			'width'    => FALSE, // default is the full width
			'height'   => FALSE, // '960px',
			'view'     => FALSE, // 'FitV', // 'FitH',
			/* translators: `%s`: download URL */
			'fallback' => _x( 'It appears you don\'t have Adobe Reader or PDF support in this web browser. <a href="%s">Click here to download the PDF</a>.', 'Modules: ShortCodes: Defaults', 'gnetwork' ),
			/* translators: `%s`: download URL */
			'feedlink' => _x( '<a href="%s">Click here to download the PDF</a>.', 'Modules: ShortCodes: Defaults', 'gnetwork' ),
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $args['id'] && ! $args['url'] )
			return $content;

		if ( ! $args['url'] && $args['id'] )
			$args['url'] = wp_get_attachment_url( $args['id'] );

		if ( is_null( $args['title'] ) && $args['id'] )
			$args['title'] = get_the_title( $args['id'] );

		else if ( is_null( $args['title'] ) )
			$args['title'] = _x( 'PDF Document', 'Modules: ShortCodes: Defaults', 'gnetwork' );

		if ( WordPress\IsIt::xml() || WordPress\IsIt::rest() ) {

			if ( $content )
				return $content;

			if ( ! $args['feedlink'] )
				return NULL;

			return '<p class="-feedlink">'.sprintf( $args['feedlink'], $args['url'] ).'</p>';
		}

		$options = [ 'fallbackLink' => '<p class="-fallback">'.sprintf( $args['fallback'], $args['url'] ).'</p>' ];

		foreach ( [ 'width', 'height', 'view', 'title' ] as $option )
			if ( $args[$option] )
				$options[$option] = $args[$option];

		$selector = $this->selector( 'pdfobject-%2$s' );
		$this->scripts_nojquery[$selector] = 'PDFObject.embed("'.$args['url'].'", "#'.$selector.'",'.Core\HTML::encode( $options ).');';

		Scripts::enqueueScriptVendor( 'pdfobject', [], '2.3.1' );
		return self::shortcodeWrap( '<div id="'.$selector.'"></div>', 'pdf', $args );
	}

	public function shortcode_csv( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'           => FALSE,
			'url'          => FALSE,
			'columns'      => NULL,
			'string_view'  => _x( 'View Resource', 'Modules: ShortCodes: Defaults', 'gnetwork' ), // FALSE to disable
			'string_empty' => _x( 'Resource is empty!', 'Modules: ShortCodes: Defaults', 'gnetwork' ), // FALSE to disable
			'cb_title'     => NULL,
			'cb_data'      => NULL,
			'class_table'  => 'table',
			'context'      => NULL,
			'wrap'         => TRUE,
			'before'       => '',
			'after'        => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $args['id'] && ! $args['url'] )
			return $content;

		if ( WordPress\IsIt::xml() || WordPress\IsIt::rest() ) {

			if ( $content )
				return $content;

			if ( ! $args['string_view'] )
				return NULL;

			return $args['id']
				? WordPress\Media::htmlAttachmentShortLink( $args['id'], $args['string_view'] )
				: Core\HTML::link( $args['string_view'], $args['url'] );
		}

		$key = $this->hash( 'csv', $args );

		if ( WordPress\IsIt::flush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			$titles = $data = [];

			if ( $args['id'] ) {

				if ( $file = get_attached_file( $args['id'] ) ) {

					$csv = new \ParseCsv\Csv();
					$csv->auto( Core\File::normalize( $file ) );

					$titles = $args['columns'] ? explode( ',', $args['columns'] ) : $csv->titles;
					$data   = $csv->data;

				} else {

					return $content ?: ( $args['string_view'] ? WordPress\Media::htmlAttachmentShortLink( $args['id'], $args['string_view'] ) : NULL );
				}

			} else {

				if ( $string = Core\HTTP::getContents( $args['url'] ) ) {

					$csv = new \ParseCsv\Csv();
					$csv->parse( $string );

					$titles = $args['columns'] ? explode( ',', $args['columns'] ) : $csv->titles;
					$data   = $csv->data;

				} else {

					return $content ?: ( $args['string_view'] ? Core\HTML::link( $args['string_view'], $args['url'] ) : NULL );
				}
			}

			if ( empty( $data ) )
				return $args['string_empty'] ? Core\HTML::wrap( $args['string_empty'], '-empty' ) : NULL;

			$title_callback = $args['cb_title'] && is_callable( $args['cb_title'] ) ? $args['cb_title'] : [ $this, 'default_csv_callback' ];
			$data_callback  = $args['cb_data']  && is_callable( $args['cb_data'] )  ? $args['cb_data']  : [ $this, 'default_csv_callback' ];

			$html = '<table class="'.Core\HTML::prepClass( $args['class_table'] ).'">';

			if ( count( $titles ) ) {

				$html.= '<thead><tr>';
				foreach ( $titles as $title )
					$html.= sprintf( '<th>%s</th>', call_user_func_array( $title_callback, [ $title, TRUE, '&nbsp;' ] ) );
				$html.= '</tr></thead><tbody>';

				foreach ( $data as $row ) {
					$html.= '<tr>';
					foreach ( $titles as $title )
						$html.= sprintf( '<td>%s</td>', call_user_func_array( $data_callback, [ $row[$title], $title, '&nbsp;' ] ) );
					$html.= '</tr>';
				}

			} else {

				$html.= '<tbody>';

				foreach ( $data as $row ) {
					$html.= '<tr>';
					foreach ( $row as $cell )
						$html.= sprintf( '<td>%s</td>', call_user_func_array( $data_callback, [ $cell, FALSE, '&nbsp;' ] ) );
					$html.= '</tr>';
				}
			}

			$html.= '</tbody></table>';
			$html = Core\Text::minifyHTML( $html );

			set_transient( $key, $html, GNETWORK_CACHE_TTL );
		}

		return self::shortcodeWrap( $html, 'csv', $args );
	}

	public function default_csv_callback( $data, $title = NULL, $fallback = '' )
	{
		return $data ? Core\HTML::escape( apply_filters( 'html_format_i18n', $data ) ) : $fallback;
	}

	public function shortcode_redirect( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'duration' => '',
			'location' => '',
			/* translators: `%s`: redirect URL */
			'message'  => _x( 'Please wait while you are redirected. Or <a href="%s">click here</a> if you do not want to wait.', 'Modules: ShortCodes: Defaults', 'gnetwork' ),
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( empty( $args['location'] ) )
			return $content;

		$html = '<meta http-equiv="refresh" content="'.$args['duration'].';url='.esc_url( $args['location'] ).'">';
		$html.= sprintf( $args['message'], esc_url( $args['location'] ) );

		return self::shortcodeWrap( $html, 'redirect', $args );
	}

	// @EXAMPLE: `[bloginfo key='name']`
	public function shortcode_bloginfo( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'key'     => '',      // @SEE: https://codex.wordpress.org/Template_Tags/bloginfo
			'class'   => '',      // OR: `key-%s`
			'context' => NULL,
			'wrap'    => FALSE,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $info = get_bloginfo( $args['key'] ) )
			return $content;

		if ( $args['wrap'] )
			$info = '<span class="-wrap shortcode-bloginfo -key-'.$args['key'].' '.sprintf( $args['class'], $args['key'] ).'">'.$info.'</span>';

		else if ( $args['class'] )
			$info = '<span class="'.Core\HTML::prepClass( sprintf( $args['class'], $args['key'] ) ).'">'.$info.'</span>';

		return $info;
	}

	// TODO: suffix post id from current post
	// TODO: cache MD5 of ref content for comparison
	// MAYBE: run the short-code manually before core on post_content
	public function shortcode_ref( $atts = [], $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() )
			return NULL;

		if ( WordPress\IsIt::xml() || WordPress\IsIt::rest() ) {
			$this->ref_ids[] = FALSE; // for the notice
			return NULL;
		}

		$args = shortcode_atts( [
			'url'       => FALSE,
			// 'url_text'  => is_rtl() ? '[&#8620;]' : '[&#8619;]',
			'url_text'  => is_rtl()
				? '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 2a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1zM0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm10.096 8.803a.5.5 0 1 0 .707-.707L6.707 6h2.768a.5.5 0 1 0 0-1H5.5a.5.5 0 0 0-.5.5v3.975a.5.5 0 0 0 1 0V6.707z"/></svg>'   // @source https://icons.getbootstrap.com/icons/arrow-up-left-square/
				: '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 2a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1zM0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm5.854 8.803a.5.5 0 1 1-.708-.707L9.243 6H6.475a.5.5 0 1 1 0-1h3.975a.5.5 0 0 1 .5.5v3.975a.5.5 0 1 1-1 0V6.707z"/></svg>', // @source https://icons.getbootstrap.com/icons/arrow-up-right-square/
			'url_title' => _x( 'External Resource', 'Shortcodes Module: Defaults', 'gnetwork' ),
			'template'  => '&#8207;[%s]&#8206;',
			'combine'   => FALSE, // combine identical notes
			'class'     => 'ref-anchor',
			'context'   => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$key = $ref = $title = $url = FALSE;

		if ( $content ) {
			$content = Utilities::kses( $content, 'text' );
			$content = apply_filters( 'html_format_i18n', $content );
			$title   = Core\Text::trim( strip_tags( $content ) );
			$ref     = Core\Text::wordWrap( trim( $content ) );
		}

		if ( $args['url'] )
			$url = Core\HTML::tag( 'a', [
				'class'       => 'reference-external',
				'data-toggle' => 'tooltip',
				'href'        => $args['url'],
				'title'       => $args['url_title'],
			], $args['url_text'] );

		if ( $url )
			$ref = $ref ? sprintf( '%s&nbsp;%s', $ref, $url ) : $url;

		if ( ! $ref )
			return NULL;

		if ( $args['combine'] ) {

			// TODO: optional: only check for the previous note
			foreach ( $this->ref_ids as $number => $text )
				if ( $text == $ref )
					$key = $number;
		}

		if ( ! $key ) {
			$key = count( $this->ref_ids ) + 1;
			$this->ref_ids[$key] = $ref;
		}

		$html = Core\HTML::tag( 'a', [
			'href'        => '#citenote-'.$key,
			'title'       => $title,
			'class'       => 'cite-scroll',
			'data-toggle' => 'tooltip',
		], sprintf( $args['template'], Core\Number::localize( $key ) ) );

		return '&#xfeff;'.'<sup class="ref reference '.$args['class'].'" id="citeref-'.$key.'" data-ref="'.$key.'">'.$html.'</sup>'.' '; // plus extra space
	}

	public function shortcode_reflist( $atts = [], $content = NULL, $tag = '' )
	{
		if ( $this->ref_list )
			return NULL;

		if ( ! is_singular() || empty( $this->ref_ids ) )
			return NULL;

		if ( WordPress\IsIt::xml() || WordPress\IsIt::rest() ) {
			$this->ref_list = TRUE;
			return Core\HTML::tag( 'p', _x( 'See the footnotes on the site.', 'Shortcodes Module: Defaults', 'gnetwork' ) );
		}

		$args = shortcode_atts( [
			'title'        => $this->filters( 'reflist_title', '', $atts, $content, $tag ),
			'columns'      => FALSE, // '20em' // @REF: http://en.wikipedia.org/wiki/Help:Footnotes#Reference_lists:_columns
			'number'       => FALSE,
			'number_after' => '.&nbsp;',
			'back'         => TRUE,
			// 'back_text'    => is_rtl() ? '[&#10532;]' : '[&#10531;]', // '[&#8618;]' : '[&#8617;]',
			'back_text'    => '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 2a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1zM0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm8.5 9.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707z"/></svg>', // @source https://icons.getbootstrap.com/icons/arrow-up-square/
			'back_title'   => _x( 'Back to Text', 'Shortcodes Module: Defaults', 'gnetwork' ),
			'context'      => NULL,
			'wrap'         => TRUE,
			'before'       => '',
			'after'        => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = '';

		foreach ( $this->ref_ids as $number => $text ) {

			if ( ! $text )
				continue;

			$html.= '<li data-ref="'.$number.'" id="citenote-'.$number.'" '.( $args['number'] ? '' : ' class="-anchor mb-1"' ).'>';

			if ( $args['number'] )
				$html.= '<span class="-number -anchor ref-number">'.Core\Number::localize( $number ).$args['number_after'].'</span>';

			if ( $args['back'] )
				$html.= ' '.Core\HTML::tag( 'a', [
					'href'        => '#citeref-'.$number,
					'title'       => $args['back_title'],
					'class'       => 'cite-scroll -back',
					'data-toggle' => 'tooltip',
				], $args['back_text'] ).' ';

			$html.= '<span class="-text ref-text"><span class="citation">'.$text.'</span></span>';
			$html.= '</li>';
		}

		$liststyle = 'persian' === Core\L10n::calendar() ? 'persian' : 'decimal';

		$html = $args['title'].'<ol id="references" class="-anchor"'
			.( $args['number'] ? '' : ( ' style="list-style-type:'.$liststyle.'"' ) ).'>'.$html.'</ol>';

		if ( ! defined( 'GNETWORK_DISABLE_REFLIST_JS' ) || ! GNETWORK_DISABLE_REFLIST_JS )
			Scripts::enqueueScript( 'front.cite' );

		$this->ref_list = TRUE;

		$extra = $args['columns'] ? [
			'style' => '-moz-column-width: '.$args['columns'].'; -webkit-column-width: '.$args['columns'].'; column-width: '.$args['columns'].';'
		] : [];

		return self::shortcodeWrap( $html, 'reflist', $args, TRUE, $extra );
	}

	public function content_after_reflist( $content )
	{
		if ( ! $this->ref_list )
			echo $this->shortcode_reflist( [], NULL, 'reflist' );
	}

	public function amp_post_article_footer_meta( $parts )
	{
		return array_merge( [ $this->classs( 'reference' ) ], $parts );
	}

	public function amp_post_template_file( $file, $type, $post )
	{
		if ( $this->classs( 'reference' ) !== $type )
			return $file;

		if ( $this->ref_list )
			return $file;

		return Utilities::getLayout( 'amp.reference' );
	}

	// Appends ref to table of contents on gEditorial Headings
	public function headings_toc( $toc )
	{
		if ( empty( $toc ) || count( $toc ) < 2 )
			return $toc;

		if ( count( $this->ref_ids ) )
			$toc[] = $this->filters( 'reflist_toc', [
				'slug'  => 'references',
				'title' => _x( 'References', 'Shortcodes Module: Defaults', 'gnetwork' ),
				'niche' => '3',
				'page'  => $GLOBALS['page'],
			], $toc );

		return $toc;
	}

	// FIXME: check this!
	public function shortcode_ref_manual( $atts = [], $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() || WordPress\IsIt::xml() || WordPress\IsIt::rest() )
			return NULL;

		// [ref-m id="0" caption="Caption Title"]
		// [ref-m 0 "Caption Title"]
		if ( isset( $atts['id'] ) ) {

			$args = shortcode_atts( [
				'id'            => 0,
				'title'         => _x( 'See the Footnote', 'Shortcodes Module: Defaults', 'gnetwork' ),
				'class'         => 'ref-anchor',
				'format_number' => TRUE,
				'context'       => NULL,
			], $atts, $tag );

			if ( FALSE === $args['context'] )
				return NULL;

		} else { // [ref-m 0]

			$args['id']            = isset( $atts[0] ) ? $atts[0] : FALSE;
			$args['title']         = isset( $atts[1] ) ? $atts[1] : _x( 'See the Footnote', 'Shortcodes Module: Defaults', 'gnetwork' );
			$args['class']         = isset( $atts[2] ) ? $atts[2] : 'ref-anchor';
			$args['format_number'] = isset( $atts[3] ) ? $atts[3] : TRUE;
		}

		if ( FALSE === $args['id'] )
			return NULL;

		return '&#xfeff;'.'<sup id="citeref-'.$args['id'].'-m" class="reference '.$args['class'].'" title="'.trim( strip_tags( $args['title'] ) ).'" ><a href="#citenote-'.$args['id'].'-m" class="cite-scroll">['.( $args['format_number'] ? Core\Number::localize( $args['id'] ) : $args['id'] ).']</a></sup>';
	}

	// FIXME: check this!
	public function shortcode_reflist_manual( $atts = [], $content = NULL, $tag = '' )
	{
		if ( WordPress\IsIt::xml() || WordPress\IsIt::rest() )
			return NULL;

		// [reflist-m id="0" caption="Caption Title"]
		// [reflist-m 0 "Caption Title"]
		if ( isset( $atts['id'] ) ) {

			$args = shortcode_atts( [
				'id'            => 0,
				'title'         => _x( 'See the Footnote', 'Shortcodes Module: Defaults', 'gnetwork' ),
				'class'         => 'ref-anchor',
				'format_number' => TRUE,
				'back'          => is_rtl() ? '[&#8618;]' : '[&#8617;]', //'&uarr;',
				'context'       => NULL,
				'wrap'          => TRUE,
			], $atts, $tag );

			if ( FALSE === $args['context'] )
				return NULL;

		} else { // [reflist-m 0]
			$args['id']            = $atts[0];
			$args['title']         = isset( $atts[1] ) ? $atts[1] : _x( 'See the Footnote', 'Shortcodes Module: Defaults', 'gnetwork' );
			$args['class']         = isset( $atts[2] ) ? $atts[2] : 'ref-anchor';
			$args['format_number'] = isset( $atts[3] ) ? $atts[3] : TRUE;
			$args['back']          = isset( $atts[4] ) ? $atts[4] : ( is_rtl() ? '[&#8618;]' : '[&#8617;]' );
			$args['after_number']  = isset( $atts[4] ) ? $atts[4] : '. ';
			$args['wrap']          = TRUE;
		}

		if ( ! defined( 'GNETWORK_DISABLE_REFLIST_JS' ) || ! GNETWORK_DISABLE_REFLIST_JS )
			Scripts::enqueueScript( 'front.cite' );

		return '<span>'.( $args['format_number'] ? Core\Number::localize( $args['id'] ) : $args['id'] ).$args['after_number']
				.'<span class="ref-backlink"><a href="#citeref-'.$args['id'].'-m" class="cite-scroll">'.$args['back']
				.'</a></span><span class="ref-text"><span class="citation" id="citenote-'.$args['id'].'-m">&nbsp;</span></span></span>';
	}

	public function shortcode_person( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => FALSE,
			'name'    => FALSE,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( $args['name'] )
			$person = trim( $args['name'] );

		else if ( trim( $content ) )
			$person = trim( $content );

		else
			return $content;

		if ( ! array_key_exists( $person, $this->people ) ) {
			$term = get_term_by( 'name', $person, $this->people_tax );

			if ( ! $term )
				return $content;

			$name = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );

			// FIXME: must cache the term, not HTML
			$this->people[$person] = Core\HTML::tag( 'a', [
				'href'  => get_term_link( $term, $term->taxonomy ),
				'title' => $content == $name ? FALSE : $name,
				'class' => 'reference-people',
				'data'  => [
					'person' => $term->term_id,
					'toggle' => 'tooltip',
				],
			], ( $content ? trim( strip_tags( $content ) ) : $name ) );
		}

		return self::shortcodeWrap( $this->people[$person], 'person', $args, FALSE );
	}
}

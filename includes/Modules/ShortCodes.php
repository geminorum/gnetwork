<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Date;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\Third;
use geminorum\gNetwork\Core\WordPress;

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

		// fallback shortcodes
		add_shortcode( 'book', [ $this, 'shortcode_return_content' ] );
		add_shortcode( 'person', [ $this, 'shortcode_person' ] );
	}

	public function init_late()
	{
		$this->shortcodes( $this->get_shortcodes() );

		if ( is_admin() ) {

			add_action( 'gnetwork_tinymce_strings', [ $this, 'tinymce_strings' ] );

			Admin::registerTinyMCE( 'gnetworkref', 'assets/js/tinymce/ref', 2 );
			Admin::registerTinyMCE( 'gnetworkemail', 'assets/js/tinymce/email', 2 );
			Admin::registerTinyMCE( 'gnetworksearch', 'assets/js/tinymce/search', 2 );
			Admin::registerTinyMCE( 'gnetworkgpeople', 'assets/js/tinymce/gpeople', 2 );

		} else {

			add_filter( 'gnetwork_prep_contact', [ $this, 'prep_contact' ], 12, 3 );
			add_filter( 'geditorial_prep_contact', [ $this, 'prep_contact' ], 12, 3 );
			add_filter( 'gtheme_prep_contact', [ $this, 'prep_contact' ], 12, 3 );


			if ( ! defined( 'GNETWORK_DISABLE_REFLIST_INSERT' ) || ! GNETWORK_DISABLE_REFLIST_INSERT ) {

				add_action( 'gnetwork_themes_content_after', [ $this, 'content_after_reflist' ], 5 );

				$this->filter( 'amp_post_article_footer_meta', 1, 9 );
				$this->filter( 'amp_post_template_file', 3 );
			}
		}
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
			'iframe'      => 'shortcode_iframe',
			'thickbox'    => 'shortcode_thickbox',
			'email'       => 'shortcode_email',
			'tel'         => 'shortcode_tel',
			'sms'         => 'shortcode_sms',
			'google-form' => 'shortcode_google_form',
			'pdf'         => 'shortcode_pdf',
			'csv'         => 'shortcode_csv',
			'bloginfo'    => 'shortcode_bloginfo',
			'audio'       => 'shortcode_audio',
			'audio-go'    => 'shortcode_audio_go',
			'ref'         => 'shortcode_ref',
			'reflist'     => 'shortcode_reflist',
			'ref-m'       => 'shortcode_ref_manual',
			'reflist-m'   => 'shortcode_reflist_manual',
			'qrcode'      => 'shortcode_qrcode',
			'search'      => 'shortcode_search',
			'last-edited' => 'shortcode_last_edited',
			'lastupdate'  => 'shortcode_last_edited',
			'redirect'    => 'shortcode_redirect',
			'menu'        => 'shortcode_menu',
			'post-title'  => 'shortcode_post_title',
			'post-link'   => 'shortcode_permalink',
			'permalink'   => 'shortcode_permalink',
		];
	}

	// @REF: https://github.com/wp-shortcake/shortcake/wiki/Registering-Shortcode-UI
	public function register_shortcode_ui()
	{
		shortcode_ui_register_for_shortcode( 'ref', [
			'label'         => HTML::escape( _x( 'Reference', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
			'listItemImage' => 'dashicons-editor-quote',
			'inner_content' => [
				'label'       => HTML::escape( _x( 'Reference', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
				'description' => HTML::escape( _x( 'Make a reference to an external source.', 'Modules: ShortCodes: UI: Description', 'gnetwork' ) ),
			],
			'attrs' => [
				[
					'label'  => HTML::escape( _x( 'External Resource', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
					'attr'   => 'url',
					'type'   => 'text',
					'encode' => TRUE,
					'meta'   => [
						'placeholder' => 'http://example.com/about-this',
						'dir'         => 'ltr',
					],
				],
				[
					'label' => HTML::escape( _x( 'External Resource Hover', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
					'attr'  => 'url_title',
					'type'  => 'text',
					'meta'  => [
						'placeholder' => HTML::escape( _x( 'Read more about it', 'Modules: ShortCodes: UI: Placeholder', 'gnetwork' ) ),
					],
				],
			],
		] );

		shortcode_ui_register_for_shortcode( 'email', [
			'label'         => HTML::escape( _x( 'Email', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
			'listItemImage' => 'dashicons-email-alt',
			'inner_content' => [
				'label'       => HTML::escape( _x( 'Email Address', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
				'description' => HTML::escape( _x( 'Full email address to appear as link and cloaked against spam bots.', 'Modules: ShortCodes: UI: Description', 'gnetwork' ) ),
				'meta'        => [ 'dir' => 'ltr' ],
			],
			'attrs' => [
				[
					'label' => HTML::escape( _x( 'Display Text', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
					'attr'  => 'content',
					'type'  => 'text',
					'meta'  => [ 'placeholder' => HTML::escape( _x( 'Email Me', 'Modules: ShortCodes: UI: Placeholder', 'gnetwork' ) ) ],
				],
				[
					'label' => HTML::escape( _x( 'Email Subject', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
					'attr'  => 'subject',
					'type'  => 'text',
					'meta'  => [ 'placeholder' => HTML::escape( _x( 'About something important', 'Modules: ShortCodes: UI: Placeholder', 'gnetwork' ) ) ],
				],
				[
					'label' => HTML::escape( _x( 'Link Hover', 'Modules: ShortCodes: UI: Label', 'gnetwork' ) ),
					'attr'  => 'title',
					'type'  => 'text',
					'meta'  => [ 'placeholder' => HTML::escape( _x( 'Jump right into it!', 'Modules: ShortCodes: UI: Placeholder', 'gnetwork' ) ) ],
				],
			],
		] );
	}

	public function tinymce_strings( $strings )
	{
		$new = [
			'gnetworkref-title' => _x( 'Cite This', 'TinyMCE Strings: Ref', 'gnetwork' ),
			'gnetworkref-attr'  => _x( 'Cite This (Ctrl+Q)', 'TinyMCE Strings: Ref', 'gnetwork' ),
			'gnetworkref-text'  => _x( 'Ref Text', 'TinyMCE Strings: Ref', 'gnetwork' ),
			'gnetworkref-url'   => _x( 'Ref URL', 'TinyMCE Strings: Ref', 'gnetwork' ),

			'gnetworkemail-title'   => _x( 'Email', 'TinyMCE Strings: Email', 'gnetwork' ),
			'gnetworkemail-attr'    => _x( 'Email (Ctrl+E)', 'TinyMCE Strings: Email', 'gnetwork' ),
			'gnetworkemail-email'   => _x( 'Full Email', 'TinyMCE Strings: Email', 'gnetwork' ),
			'gnetworkemail-text'    => _x( 'Display Text', 'TinyMCE Strings: Email', 'gnetwork' ),
			'gnetworkemail-subject' => _x( 'Email Subject', 'TinyMCE Strings: Email', 'gnetwork' ),
			'gnetworkemail-hover'   => _x( 'Link Hover', 'TinyMCE Strings: Email', 'gnetwork' ),

			'gnetworksearch-title' => _x( 'Search', 'TinyMCE Strings: Search', 'gnetwork' ),
			'gnetworksearch-attr'  => _x( 'Search (Ctrl+3)', 'TinyMCE Strings: Search', 'gnetwork' ),
			'gnetworksearch-text'  => _x( 'Display Text', 'TinyMCE Strings: Search', 'gnetwork' ),
			'gnetworksearch-query' => _x( 'Override Criteria', 'TinyMCE Strings: Search', 'gnetwork' ),

			'gnetworkgpeople-title' => _x( 'People', 'TinyMCE Strings: People', 'gnetwork' ),
			'gnetworkgpeople-attr'  => _x( 'People', 'TinyMCE Strings: People', 'gnetwork' ),
			'gnetworkgpeople-name'  => _x( 'Name', 'TinyMCE Strings: People', 'gnetwork' ),
		];

		return array_merge( $strings, $new );
	}

	public function prep_contact( $prepared, $value, $title = NULL )
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
	// USAGE: [in-term tax="category" slug="ungategorized" order="menu_order" /]
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

			$terms = get_the_terms( $post->ID, $args['tax'] );

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
					$order = $args['order_before'] ? Number::format( $args['order_zeroise'] ? zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order ).$args['order_sep'] : '';

					if ( 'publish' == $post->post_status && $args['li_link'] )
						$list = $args['li_before'].HTML::tag( 'a', [
							'href'  => apply_filters( 'the_permalink', get_permalink( $post ), $post ),
							'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
							'class' => '-link',
						], $order.$title );

					else
						$list = $args['li_before'].HTML::tag( 'span', [
							'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
							'class' => $args['li_link'] ? '-future' : FALSE,
						], $order.$title );

					// TODO: add excerpt/content of the post
					// TODO: add show/more js
				}

				$html.= HTML::tag( 'li', [
					'id'    => $args['li_anchor'].$post->ID,
					'class' => '-item',
				], $list );
			}

			$html = HTML::tag( $args['list'], [ 'class' => '-list' ], $html );

			if ( $args['title'] )
				$html = $args['title'].$html;

			$html = self::shortcodeWrap( $html, 'in-term', $args );

			wp_reset_postdata();
			wp_cache_set( $key, $html, 'gnetwork-term' );

			return $html;
		}

		return $content;
	}

	// FIXME: move to gEditorial Terms (using api)
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

			// TODO: use `is_taxonomy_viewable()` since WP 5.0.0
			if ( ! $taxonomy->public )
				continue;

			if ( $args['tax'] && ! in_array( $taxonomy->name, explode( ',', $args['tax'] ) ) )
				continue;

			if ( $terms = get_the_terms( $post->ID, $taxonomy->name ) ) {

				$html.= '<h3>'.$taxonomy->label.'</h3><ul class="-tax">';

				foreach ( $terms as $term )
					$html.= vsprintf( '<li class="-term"><a href="%1$s">%2$s</a></li>', [
						esc_url( get_term_link( $term->slug, $taxonomy->name ) ),
						sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy->name, 'display' ),
					] );

				$html.= '</ul>';
			}
		}

		return self::shortcodeWrap( $html, 'all-terms', $args );
	}

	/**
	 * [last-edited format="l, F j, Y"] : 'Friday, January 11, 2012'
	 * [last-edited format="G:i a (T)"] : '7:02 pm (EST)'
	 * [last-edited format="l, F j, Y \a\t G:i A"] : 'Friday, January 11, 2012 at 7:02 PM'
	 * [last-edited before="Last update:"] : 'Last update: Jan-11-2012'
	 * [last-edited format="l, F j, Y" before="<span>This page hasn't been modified since" after="!</span>"] : '<span>This page hasn't been modified since Friday, January 11, 2012!</span>'
	 */
	public function shortcode_last_edited( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'       => get_queried_object_id(),
			'format'   => Utilities::dateFormats( 'dateonly' ),
			'title'    => 'timeago',
			'round'    => FALSE,
			'link'     => FALSE,
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = get_post( $args['id'] ) )
			return $content;

		$gmt   = strtotime( $post->post_modified_gmt );
		$local = strtotime( $post->post_modified );

		if ( 'timeago' == $args['title'] )
			$title = Scripts::enqueueTimeAgo()
				? FALSE
				: Utilities::humanTimeDiffRound( $local, $args['round'] );
		else
			$title = $args['title'];

		$html = Date::htmlDateTime( $local, $gmt, $args['format'], $title );

		if ( $args['link'] )
			$html = HTML::link( $html, $args['link'] );

		return self::shortcodeWrap( $html, 'last-edited', $args, FALSE );
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

	public function shortcode_post_title( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'post'    => NULL,
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
			$text = trim( $args['text'] );

		else if ( trim( $content ) )
			$text = Text::wordWrap( $content );

		else if ( ! empty( $post->post_title ) )
			$text = Utilities::prepTitle( $post->post_title, $post->ID );

		else
			return $content;

		$html = HTML::tag( 'a', [
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

						$html = HTML::tag( 'a', [
							'href'        => apply_filters( 'the_permalink', get_permalink( $parent ), $parent ),
							'title'       => get_the_title( $parent ),
							'class'       => 'parent',
							'data-toggle' => 'tooltip',
							'rel'         => 'parent',
						], $args['html'] );

					} else {

						$html = HTML::tag( 'a', [
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

				$html = HTML::tag( 'a', [
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

		$classes = HTML::attrClass( 'button', $args['class'] );

		if ( $args['genericon'] )
			$html.= HTML::getDashicon( $args['genericon'] );

		if ( $content )
			$html.= ' '.trim( $content );

		if ( $args['url'] )
			$html = HTML::tag( 'a', [
				'href'  => $args['url'],
				'class' => $classes,
			], $html );

		else
			$html = HTML::tag( 'button', [
				'class' => $classes,
			], $html );

		unset( $args['class'] );

		return self::shortcodeWrap( $html, 'button', $args, FALSE );
	}

	public function shortcode_iframe( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'url'     => FALSE,
			'width'   => '100%',
			'height'  => '520',
			'scroll'  => 'auto',
			'style'   => 'width:100% !important;',
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] || WordPress::isXML() || WordPress::isREST() )
			return NULL;

		if ( ! $args['url'] )
			return $content;

		if ( ! in_array( $args['scroll'], [ 'auto', 'yes', 'no' ] ) )
			$args['scroll'] = 'no';

		if ( ! $content )
			$content = _x( 'Loading &hellip;', 'Modules: ShortCodes: Defaults', 'gnetwork' );

		$html = HTML::tag( 'iframe', [
			'frameborder' => '0',
			'src'         => $args['url'],
			'style'       => $args['style'],
			'scrolling'   => $args['scroll'],
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

		$html = HTML::tag( 'a', [
			'href'    => add_query_arg( $query, $args['url'] ),
			'title'   => $args['title'],
			'class'   => HTML::attrClass( 'thickbox', $args['class'] ),
			'onclick' => 'return false;',
		], $content );

		unset( $args['class'] );

		if ( ! WordPress::isXML() && ! WordPress::isREST() )
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

		$text  = $args['content'] ? trim( $args['content'] ) : trim( $content );
		$email = $args['email'] && is_email( $args['email'] ) ? trim( $args['email'] ) : trim( $content );

		if ( ! $email && $args['fallback'] )
			$email = gNetwork()->email();

		if ( ! $email )
			return $content;

		if ( ! $text )
			$text = $email;

		$html = '<a class="email" href="'.antispambot( "mailto:".$email.( $args['subject'] ? '?subject='.urlencode( $args['subject'] ) : '' ) )
				.'"'.( $args['title'] ? ' data-toggle="tooltip" title="'.HTML::escape( $args['title'] ).'"' : '' ).'>'
				.( $email == $text ? antispambot( $email ) : $text ).'</a>';

		return self::shortcodeWrap( $html, 'email', $args, FALSE );
	}

	// @REF: http://stackoverflow.com/a/13662220
	// @SEE http://code.tutsplus.com/tutorials/mobile-web-quick-tip-phone-number-links--mobile-7667
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

		$html = HTML::tel( $number, $args['title'], Number::format( $content ) );
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

		$html = '<a class="sms" href="'.HTML::sanitizeSMSNumber( $number )
				.( $args['body'] ? '?body='.rawurlencode( $args['body'] )
				.'" data-sms-body="'.HTML::escape( $args['body'] ) : '' )
				.'"'.( $args['title'] ? ' data-toggle="tooltip" title="'.HTML::escape( $args['title'] )
				.'"' : '' ).' data-sms-number="'.HTML::escape( $number ).'">'
				.HTML::wrapLTR( Number::format( $content ) ).'</a>';

		return self::shortcodeWrap( $html, 'sms', $args, FALSE );
	}

	// WORKING DRAFT
	// FIXME: add def atts / wrap
	public function shortcode_qrcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $content ? Third::getGoogleQRCode( trim( $content ), $atts ) : $content;
	}

	// TODO: also [search-form] to include current theme search form
	public function shortcode_search( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'for'     => FALSE, // override
			'url'     => FALSE, // override
			/* translators: %s: search criteria */
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

		$html = HTML::tag( 'a', [
			'href'  => WordPress::getSearchLink( $for, $args['url'] ),
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

		if ( WordPress::isXML() || WordPress::isREST() )
			return $content;

		if ( ! $args['key'] )
			return $content;

		return self::shortcode_iframe( array_merge( $args, [
			'url' => sprintf( $args['template'], $args['key'] ),
		] ), $content, $tag );
	}

	// TODO: download option
	// @REF: https://github.com/pipwerks/PDFObject
	public function shortcode_pdf( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'       => FALSE,
			'url'      => FALSE,
			'width'    => FALSE, // default is full width
			'height'   => FALSE, // '960px',
			'view'     => FALSE, // 'FitV',  //'FitH',
			/* translators: %s: download url */
			'fallback' => _x( 'It appears you don\'t have Adobe Reader or PDF support in this web browser. <a href="%s">Click here to download the PDF</a>.', 'Modules: ShortCodes: Defaults', 'gnetwork' ),
			/* translators: %s: download url */
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

		if ( WordPress::isXML() || WordPress::isREST() ) {

			if ( $content )
				return $content;

			if ( ! $args['feedlink'] )
				return NULL;

			return '<p class="-feedlink">'.sprintf( $args['feedlink'], $args['url'] ).'</p>';
		}

		$options = [ 'fallbackLink' => '<p class="-fallback">'.sprintf( $args['fallback'], $args['url'] ).'</p>' ];

		foreach ( [ 'width', 'height', 'view' ] as $option )
			if ( $args[$option] )
				$options[$option] = $args[$option];

		$selector = $this->selector( 'pdfobject-%2$s' );
		$this->scripts_nojquery[$selector] = 'PDFObject.embed("'.$args['url'].'", "#'.$selector.'",'.wp_json_encode( $options ).');';

		Scripts::enqueueScriptVendor( 'pdfobject', [], '2.1.1' );
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
			'context'      => NULL,
			'wrap'         => TRUE,
			'before'       => '',
			'after'        => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $args['id'] && ! $args['url'] )
			return $content;

		if ( WordPress::isXML() || WordPress::isREST() ) {

			if ( $content )
				return $content;

			if ( ! $args['string_view'] )
				return NULL;

			return $args['id']
				? WordPress::htmlAttachmentShortLink( $args['id'], $args['string_view'] )
				: HTML::link( $args['string_view'], $args['url'] );
		}

		$key = $this->hash( 'csv', $args );

		if ( WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			$titles = $data = [];

			if ( $args['id'] ) {

				if ( $file = get_attached_file( $args['id'] ) ) {

					$csv = new \ParseCsv\Csv();
					$csv->auto( File::normalize( $file ) );

					$titles = $args['columns'] ? explode( ',', $args['columns'] ) : $csv->titles;
					$data   = $csv->data;

				} else {
					return $content ?: ( $args['string_view'] ? WordPress::htmlAttachmentShortLink( $args['id'], $args['string_view'] ) : NULL );
				}

			} else {

				if ( $string = HTTP::getContents( $args['url'] ) ) {

					$csv = new \ParseCsv\Csv();
					$csv->parse( $string );

					$titles = $args['columns'] ? explode( ',', $args['columns'] ) : $csv->titles;
					$data   = $csv->data;

				} else {
					return $content ?: ( $args['string_view'] ? HTML::link( $args['string_view'], $args['url'] ) : NULL );
				}
			}

			if ( empty( $data ) )
				return $args['string_empty'] ? HTML::wrap( $args['string_empty'], '-empty' ) : NULL;

			$html = '<table>';

			if ( count( $titles ) ) {

				$html.= '<thead><tr>';
				foreach ( $titles as $title )
					$html.= '<th>'.( $title ? HTML::escape( apply_filters( 'html_format_i18n', $title ) ) : '&nbsp;' ).'</th>';
				$html.= '</tr></thead><tbody>';

				foreach ( $data as $row ) {
					$html.= '<tr>';
					foreach ( $titles as $title )
						$html.= '<td>'.( isset( $row[$title] ) ? HTML::escape( apply_filters( 'html_format_i18n', $row[$title] ) ) : '&nbsp;' ).'</td>';
					$html.= '</tr>';
				}

			} else {

				$html.= '<tbody>';

				foreach ( $data as $row ) {
					$html.= '<tr>';
					foreach ( $row as $cell )
						$html.= '<td>'.( $cell ? HTML::escape( apply_filters( 'html_format_i18n', $cell ) ) : '&nbsp;' ).'</td>';
					$html.= '</tr>';
				}
			}

			$html.= '</tbody></table>';
			$html = Text::minifyHTML( $html );

			set_transient( $key, $html, GNETWORK_CACHE_TTL );
		}

		return self::shortcodeWrap( $html, 'csv', $args );
	}

	public function shortcode_redirect( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'duration' => '',
			'location' => '',
			/* translators: %s: redirect url */
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

	// EXAMPLE: [bloginfo key='name']
	public function shortcode_bloginfo( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'key'     => '', // SEE: https://codex.wordpress.org/Template_Tags/bloginfo
			'class'   => '', // OR: 'key-%s'
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
			$info = '<span class="'.sprintf( $args['class'], $args['key'] ).'">'.$info.'</span>';

		return $info;
	}

	// [audio-go to="60"]Go to 60 second mark and play[/audio-go]
	// http://bavotasan.com/2015/working-with-wordpress-and-mediaelement-js/
	public function shortcode_audio_go( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'to'       => '0',
			'instance' => '0',
			/* translators: %s: number of seconds */
			'title'    => _x( 'Go to %s second mark and play', 'Shortcodes Module: Defaults', 'gnetwork' ),
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( WordPress::isXML() || WordPress::isREST() )
			return $content;

		$title = sprintf( $args['title'], $args['to'] );
		$html  = $content ? trim( $content ) : $title;
		$html  = '<a href="#" class="audio-go-to-time" title="'.HTML::escape( $title ).'" data-time="'.$args['to'].'" data-instance="'.$args['instance'].'">'.$html.'</a>';

		Scripts::enqueueScript( 'front.audio-go' );

		return self::shortcodeWrap( $html, 'audio-go', $args, FALSE );
	}

	// wrapper for default core audio shortcode
	public function shortcode_audio( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'download' => FALSE,
			'filename' => FALSE, // http://davidwalsh.name/download-attribute
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( WordPress::isXML() || WordPress::isREST() )
			return $content;

		if ( $html = wp_audio_shortcode( $atts, $content ) ) {

			if ( $args['download'] && $src = self::getAudioSource( $atts ) )
				$html.= '<div class="-download"><a href="'.$src.'"'
					.( $args['filename'] ? ' download="'.$args['filename'].'"' : '' )
					.'>'.$args['download'].'</a></div>';

			return self::shortcodeWrap( $html, 'audio', $args );
		}

		return $content;
	}

	// helper
	public static function getAudioSource( $atts = [] )
	{
		$sources = [
			'src',
			'source',
			'mp3',
			'mp3remote',
			'wma',
			'wmaremote',
			'wma',
			'wmaremote',
			'wmv',
			'wmvremote',
		];

		foreach ( $sources as $source )
			if ( ! empty( $atts[$source] ) )
				return $atts[$source];

		return FALSE;
	}

	public function shortcode_ref( $atts = [], $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() )
			return NULL;

		if ( WordPress::isXML() || WordPress::isREST() ) {
			$this->ref_ids[] = FALSE; // for the notice
			return NULL;
		}

		$args = shortcode_atts( [
			'url'       => FALSE,
			'url_text'  => is_rtl() ? '[&#8620;]' : '[&#8619;]',
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
			$title   = trim( strip_tags( $content ) );
			$ref     = Text::wordWrap( trim( $content ) );
		}

		if ( $args['url'] )
			$url = HTML::tag( 'a', [
				'class'       => 'reference-external',
				'data-toggle' => 'tooltip',
				'href'        => $args['url'],
				'title'       => $args['url_title'],
			], $args['url_text'] );

		if ( $ref && $url )
			$ref = $ref.'&nbsp;'.$url;
		else if ( $url )
			$ref = $url;

		if ( ! $ref )
			return NULL;

		if ( $args['combine'] ) {

			// TODO: must only check for the previous note
			foreach ( $this->ref_ids as $number => $text )
				if ( $text == $ref )
					$key = $number;
		}

		if ( ! $key ) {
			$key = count( $this->ref_ids ) + 1;
			$this->ref_ids[$key] = $ref;
		}

		$html = HTML::tag( 'a', [
			'href'        => '#citenote-'.$key,
			'title'       => $title,
			'class'       => 'cite-scroll',
			'data-toggle' => 'tooltip',
		], sprintf( $args['template'], Number::format( $key ) ) );

		return '&#xfeff;'.'<sup class="ref reference '.$args['class'].'" id="citeref-'.$key.'" data-ref="'.$key.'">'.$html.'</sup>';
	}

	public function shortcode_reflist( $atts = [], $content = NULL, $tag = '' )
	{
		if ( $this->ref_list )
			return NULL;

		if ( ! is_singular() || empty( $this->ref_ids ) )
			return NULL;

		if ( WordPress::isXML() || WordPress::isREST() ) {
			$this->ref_list = TRUE;
			return '<p>'._x( 'See the footnotes on the site.', 'Shortcodes Module: Defaults', 'gnetwork' ).'</p>';
		}

		$args = shortcode_atts( [
			'title'        => $this->filters( 'reflist_title', '', $atts, $content, $tag ),
			'columns'      => FALSE, // '20em' // @REF: http://en.wikipedia.org/wiki/Help:Footnotes#Reference_lists:_columns
			'number'       => FALSE,
			'number_after' => '.&nbsp;',
			'back'         => TRUE,
			'back_text'    => is_rtl() ? '[&#10532;]' : '[&#10531;]', // '[&#8618;]' : '[&#8617;]',
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

			$html.= '<li data-ref="'.$number.'" id="citenote-'.$number.'" '.( $args['number'] ? '' : ' class="-anchor"' ).'>';

			if ( $args['number'] )
				$html.= '<span class="-number -anchor ref-number">'.Number::format( $number ).$args['number_after'].'</span>';

			$html.= '<span class="-text ref-text"><span class="citation">'.$text.'</span></span>';

			if ( $args['back'] )
				$html.= ' '.HTML::tag( 'a', [
					'href'        => '#citeref-'.$number,
					'title'       => $args['back_title'],
					'class'       => 'cite-scroll -back',
					'data-toggle' => 'tooltip',
				], $args['back_text'] );

			$html.= '</li>';
		}

		$html = $args['title'].'<ol id="references" class="-anchor"'
			.( $args['number'] ? '' : ' style="list-style-type:decimal"' ).'>'.$html.'</ol>';

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

	// FIXME: check this!
	public function shortcode_ref_manual( $atts = [], $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() || WordPress::isXML() || WordPress::isREST() )
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

		return '&#xfeff;'.'<sup id="citeref-'.$args['id'].'-m" class="reference '.$args['class'].'" title="'.trim( strip_tags( $args['title'] ) ).'" ><a href="#citenote-'.$args['id'].'-m" class="cite-scroll">['.( $args['format_number'] ? Number::format( $args['id'] ) : $args['id'] ).']</a></sup>';
	}

	// FIXME: check this!
	public function shortcode_reflist_manual( $atts = [], $content = NULL, $tag = '' )
	{
		if ( WordPress::isXML() || WordPress::isREST() )
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

		return '<span>'.( $args['format_number'] ? Number::format( $args['id'] ) : $args['id'] ).$args['after_number']
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

			// FIXME: must cache the term, not html
			$this->people[$person] = HTML::tag( 'a', [
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

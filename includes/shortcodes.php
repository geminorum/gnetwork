<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkShortCodes extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;

	private $flash_ids  = array();
	private $pdf_ids    = array();
	private $ref_ids    = array();
	private $ref_list   = FALSE;
	private $people     = array();
	private $people_tax = 'post_tag'; // 'people';

	protected function setup_actions()
	{
		add_action( 'init', array( $this, 'init_early' ), 8 );
		add_action( 'init', array( $this, 'init_late' ), 12 );
		add_action( 'wp_footer', array( $this, 'wp_footer' ), 20 );

		if ( class_exists( 'gNetworkAdmin' ) ) {
			add_action( 'gnetwork_tinymce_strings', array( $this, 'tinymce_strings' ) );
			gNetworkAdmin::registerTinyMCE( 'gnetworkref', 'assets/js/tinymce.ref', 1 );
			gNetworkAdmin::registerTinyMCE( 'gnetworkemail', 'assets/js/tinymce.email', 1 );
			gNetworkAdmin::registerTinyMCE( 'gnetworksearch', 'assets/js/tinymce.search', 2 );
			gNetworkAdmin::registerTinyMCE( 'gnetworkgpeople', 'assets/js/tinymce.gpeople', 2 );
		}
	}

	public function init_early()
	{
		if ( defined( 'GPEOPLE_PEOPLE_TAXONOMY' ) )
			$this->people_tax = GPEOPLE_PEOPLE_TAXONOMY;

		else if ( defined( 'GNETWORK_GPEOPLE_TAXONOMY' ) )
			$this->people_tax = GNETWORK_GPEOPLE_TAXONOMY;

		// fallback shortcodes
		add_shortcode( 'book', array( $this, 'shortcode_return_content' ) );
		add_shortcode( 'person', array( $this, 'shortcode_person' ) );
	}

	public function shortcode_return_content( $atts, $content = NULL, $tag = '' )
	{
		return $content;
	}

	public function init_late()
	{
		$this->shortcodes( array(
			'children'     => 'shortcode_children',
			'siblings'     => 'shortcode_siblings',
			'in-term'      => 'shortcode_in_term',
			'all-terms'    => 'shortcode_all_terms',
			'back'         => 'shortcode_back',
			'iframe'       => 'shortcode_iframe',
			'email'        => 'shortcode_email',
			'tel'          => 'shortcode_tel',
			'sms'          => 'shortcode_sms',
			'googlegroups' => 'shortcode_googlegroups',
			'pdf'          => 'shortcode_pdf',
			'bloginfo'     => 'shortcode_bloginfo',
			'audio'        => 'shortcode_audio',
			'audio-go'     => 'shortcode_audio_go',
			'flash'        => 'shortcode_flash',
			'ref'          => 'shortcode_ref',
			'reflist'      => 'shortcode_reflist',
			'ref-m'        => 'shortcode_ref_manual',
			'reflist-m'    => 'shortcode_reflist_manual',
			'qrcode'       => 'shortcode_qrcode',
			'search'       => 'shortcode_search',
		) );

		if ( ! defined( 'GNETWORK_DISABLE_REFLIST_INSERT' ) || ! GNETWORK_DISABLE_REFLIST_INSERT )
			add_filter( 'the_content', array( $this, 'the_content' ), 20 );
	}

	public static function available()
	{
		global $shortcode_tags;

		self::listCode( $shortcode_tags, '<code>[%1$s]</code>' );
	}

	public function tinymce_strings( $strings )
	{
		$new = array(
            'gnetworkref-title' => _x( 'Cite This', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkref-attr'  => _x( 'Cite This (Ctrl+Q)', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkref-text'  => _x( 'Ref Text', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkref-url'   => _x( 'Ref URL', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),

            'gnetworkemail-title'   => _x( 'Email', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkemail-attr'    => _x( 'Email (Ctrl+E)', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkemail-email'   => _x( 'Full Email', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkemail-text'    => _x( 'Display Text', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkemail-subject' => _x( 'Email Subject', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkemail-hover'   => _x( 'Link Hover', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),

            'gnetworksearch-title' => _x( 'Search', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworksearch-attr'  => _x( 'Search (Ctrl+3)', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworksearch-text'  => _x( 'Display Text', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworksearch-query' => _x( 'Override Criteria', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),

            'gnetworkgpeople-title' => _x( 'People', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkgpeople-attr'  => _x( 'People', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
            'gnetworkgpeople-name'  => _x( 'Name', 'ShortCode Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
		);

		return array_merge( $strings, $new );
	}

	public function shortcode_children( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'      => get_queried_object_id(),
			'type'    => 'page',
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! is_singular( $args['type'] ) )
			return $content;

		$children = wp_list_pages( array(
			'child_of'    => $args['id'],
			'post_type'   => $args['type'],
			'echo'        => FALSE,
			'depth'       => 1,
			'title_li'    => '',
			'sort_column' => 'menu_order, post_title',
		) );

		if ( ! $children )
			return $content;

		return self::shortcodeWrap( '<ul>'.$children.'</ul>', 'children', $args );
	}

	public function shortcode_siblings( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'parent'  => NULL,
			'type'    => 'page',
			'ex'      => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

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

		$siblings = wp_list_pages( array(
			'child_of'    => $args['parent'],
			'post_type'   => $args['type'],
			'exclude'     => $args['ex'],
			'echo'        => FALSE,
			'depth'       => 1,
			'title_li'    => '',
			'sort_column' => 'menu_order, post_title',
		) );

		if ( ! $siblings )
			return $content;

		return self::shortcodeWrap( '<ul>'.$siblings.'</ul>', 'siblings', $args );
	}

	// USAGE: [in-term tax="category" slug="ungategorized" order="menu_order" /]
	// EDITED: 4/5/2016, 5:03:30 PM
	public function shortcode_in_term( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
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
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		// if ( ! is_singular( $args['type'] ) )
		// 	return $content;

		$error = $term = FALSE;
		$html = $tax_query = '';

		$key = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, 'gnetwork-term' );
		if ( FALSE !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = FALSE;

		if ( $args['id'] && $args['id'] ) {

			if ( $term = get_term_by( 'id', $args['id'], $args['tax'] ) )
				$tax_query = array( array(
					'taxonomy' => $args['tax'],
					'field'    => 'term_id',
					'terms'    => array( $args['id'] ),
				) );

			else
				$error = TRUE;

		} else if ( $args['slug'] && $args['slug'] ) {

			if ( $term = get_term_by( 'slug', $args['slug'], $args['tax'] ) )
				$tax_query = array( array(
					'taxonomy' => $args['tax'],
					'field'    => 'slug',
					'terms'    => array( $args['slug'] ),
				) );

			else
				$error = TRUE;

		} else if ( $post->post_type == $args['type'] ) {

			$terms = get_the_terms( $post->ID, $args['tax'] );

			if ( $terms && ! is_wp_error( $terms ) ) {

				foreach ( $terms as $term )
					$term_list[] = $term->term_id;

				$tax_query = array( array(
					'taxonomy' => $args['tax'],
					'field'    => 'term_id',
					'terms'    => $term_list,
				) );

			} else {
				$error = TRUE;
			}
		}

		if ( $error )
			return $content;

		$args['title'] = self::shortcodeTermTitle( $args, $term );

		if ( 'on' == $args['future'] )
			$post_status = array( 'publish', 'future', 'draft' );
		else
			$post_status = array( 'publish' );

		$query_args = array(
			'tax_query'        => $tax_query,
			'posts_per_page'   => $args['limit'],
			'orderby'          => $args['orderby'],
			'order'            => $args['order'],
			'post_type'        => $args['type'],
			'post_status'      => $post_status,
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		);

		$query = new WP_Query;
		$posts = $query->query( $query_args );

		if ( count( $posts ) ) {
			foreach ( $posts as $post ) {

				$list  = '';
				setup_postdata( $post );

				if ( $args['cb'] ) {
					$list = call_user_func_array( $args['cb'], array( $post, $args ) );

				} else {

					$title = get_the_title( $post->ID );
					$order = $args['order_before'] ? number_format_i18n( $args['order_zeroise'] ? zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order ).$args['order_sep'] : '';

					if ( 'publish' == $post->post_status && $args['li_link'] )
						$list = $args['li_before'].self::html( 'a', array(
							'href'  => get_permalink( $post->ID ),
							'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
							'class' => '-link',
						), $order.$title );

					else
						$list = $args['li_before'].self::html( 'span', array(
							'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
							'class' => $args['li_link'] ? '-future' : FALSE,
						), $order.$title );

					// TODO: add excerpt/content of the post
					// TODO: add show/more js
				}

				$html .= self::html( 'li', array(
					'id'    => $args['li_anchor'].$post->ID,
					'class' => '-item',
				), $list );
			}

			$html = self::html( $args['list'], array( 'class' => '-list' ), $html );

			if ( $args['title'] )
				$html = $args['title'].$html;

			$html = self::shortcodeWrap( $html, 'in-term', $args );

			wp_reset_postdata();
			wp_cache_set( $key, $html, 'gnetwork-term' );

			return $html;
		}

		return $content;
	}

	// FIXME: working draft
	// EDITED: 4/5/2016, 5:01:31 PM
	public function shortcode_all_terms( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'      => get_queried_object_id(),
			'tax'     => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = '';

		$post = get_post( $args['id'] );

		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {

			if ( ! $taxonomy->public )
				continue;

			if ( $args['tax'] && ! in_array( $taxonomy->name, explode( ',', $args['tax'] ) ) )
				continue;

			if ( $terms = get_the_terms( $post->ID, $taxonomy->name ) ) {

				$html .= '<h2>'.$taxonomy->label.'</h2><ul>';

				foreach ( $terms as $term )
					$html .= sprintf( '<li><a href="%1$s">%2$s</a></li>',
						esc_url( get_term_link( $term->slug, $taxonomy->name ) ),
						esc_html( $term->name )
					);

				$html .= '</ul>';
			}
		}

		return self::shortcodeWrap( $html, 'all-terms', $args );
	}

	// TODO: more cases
	public function shortcode_back( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'      => get_queried_object_id(),
			'to'      => 'parent',
			'html'    => _x( 'Back', 'ShortCode Module: back: default html', GNETWORK_TEXTDOMAIN ),
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $args['to'] )
			return $content;

		$html = FALSE;

		switch ( $args['to'] ) {

			case 'parent' :

				$post = get_post( $args['id'] );
				if ( $post ) {
					if ( $post->post_parent ) {
						$html = self::html( 'a', array(
							'href'        => get_permalink( $post->post_parent ),
							'title'       => get_the_title( $post->post_parent ),
							'class'       => 'parent',
							'data-toggle' => 'tooltip',
							'rel'         => 'parent',
						), $args['html'] );
					} else {
						$html = self::html( 'a', array(
							'href'        => home_url( '/' ),
							'title'       => _x( 'Home', 'ShortCode Module: back: home title attr', GNETWORK_TEXTDOMAIN ),
							'class'       => 'home',
							'data-toggle' => 'tooltip',
							'rel'         => 'home',
						), $args['html'] );
					}
				}

			break;

			case 'home' :

				$html = self::html( 'a', array(
					'href'        => home_url( '/' ),
					'title'       => _x( 'Home', 'ShortCode Module: back: home title attr', GNETWORK_TEXTDOMAIN ),
					'class'       => 'home',
					'data-toggle' => 'tooltip',
					'rel'         => 'home',
				), $args['html'] );

			break;

			case 'grand-parent' :

			break;

		}

		if ( $html )
			return self::shortcodeWrap( $html, 'back', $args );

		return $content;
	}

	public function shortcode_iframe( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'url'     => FALSE,
			'width'   => '100%',
			'height'  => '520',
			'scroll'  => 'auto',
			'style'   => 'width:100% !important;',
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( ! $args['url'] )
			return NULL;

		if ( ! in_array( $args['scroll'], array( 'auto', 'yes', 'no' ) ) )
			$args['scroll'] = 'no';

		$html = self::html( 'iframe', array(
			'frameborder' => '0',
			'src'         => $args['url'],
			'style'       => $args['style'],
			'scrolling'   => $args['scroll'],
			'height'      => $args['height'],
			'width'       => $args['width'],
		), NULL );

		return self::shortcodeWrap( $html, 'iframe', $args );
	}

	// [email subject="Email Subject"]you@you.com[/email]
	// http://www.cubetoon.com/2008/how-to-enter-line-break-into-mailto-body-command/
	// https://css-tricks.com/snippets/html/mailto-links/
	public function shortcode_email( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'subject' => FALSE,
			'title'   => FALSE,
			'email'   => FALSE, // override
			'content' => FALSE, // override
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

        $text  = $args['content'] ? trim( $args['content'] ) : trim( $content );
        $email = $args['email'] && is_email( $args['email'] ) ? trim( $args['email'] ) : trim( $content );

		if ( ! $email )
			$email = gNetworkNetwork::getEmail();

		if ( ! $email )
			return $text;

		$html = '<a class="email" href="'.antispambot( "mailto:".$email.( $args['subject'] ? '?subject='.urlencode( $args['subject'] ) : '' ) )
				.'"'.( $args['title'] ? ' data-toggle="tooltip" title="'.esc_attr( $args['title'] ).'"' : '' ).'>'
				.( $email == $text ? antispambot( $email ) : $text ).'</a>';

		return self::shortcodeWrap( $html, 'email', $args, FALSE );
	}

	// @REF: http://stackoverflow.com/a/13662220
	// @SEE http://code.tutsplus.com/tutorials/mobile-web-quick-tip-phone-number-links--mobile-7667
	public function shortcode_tel( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'number'  => NULL,
			'title'   => FALSE,
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

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

		$html = '<a class="tel" href="tel:'.str_ireplace( array( '-', ' ' ), '', $number )
				.'"'.( $args['title'] ? ' data-toggle="tooltip" title="'.esc_attr( $args['title'] ).'"' : '' )
				.' data-tel-number="'.esc_attr( $number ).'">'
				.'&#8206;'.apply_filters( 'string_format_i18n', $content ).'&#8207;</a>';

		return self::shortcodeWrap( $html, 'tel', $args, FALSE );
	}

	// @REF: http://stackoverflow.com/a/19126326/4864081
	// @TEST: http://bradorego.com/test/sms.html
	public function shortcode_sms( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'number'  => NULL,
			'body'    => FALSE,
			'title'   => FALSE,
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

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

		$html = '<a class="sms" href="sms:'.str_ireplace( array( '-', ' ' ), '', $number )
				.( $args['body'] ? '?body='.rawurlencode( $args['body'] )
				.'" data-sms-body="'.esc_attr( $args['body'] ) : '' )
				.'"'.( $args['title'] ? ' data-toggle="tooltip" title="'.esc_attr( $args['title'] )
				.'"' : '' ).' data-sms-number="'.esc_attr( $number ).'">'
				.'&#8206;'.apply_filters( 'string_format_i18n', $content ).'&#8207;</a>';

		return self::shortcodeWrap( $html, 'sms', $args, FALSE );
	}

	// WORKING DRAFT
	// FIXME: add def atts / wrap
	public function shortcode_qrcode( $atts, $content = NULL, $tag = '' )
	{
		if ( $content )
			return self::getGoogleQRCode( trim( $content ), $atts );

		return $content;
	}

	// TODO: also [search-form] to include current theme search form
	public function shortcode_search( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
            'for'     => FALSE, // override
            'url'     => FALSE, // override
            'title'   => _x( 'Search this site for “%s”', 'ShortCode Module: search: link title attr', GNETWORK_TEXTDOMAIN ),
            'context' => NULL,
            'wrap'    => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $content )
			return $content;

        $text = trim( strip_tags( $content ) );
        $for  = $args['for'] ? trim( $args['for'] ) : $text;

		$html = self::html( 'a', array(
            'href'  => self::getSearchLink( $for, $args['url'] ),
            'title' => sprintf( $args['title'], $for ),
		), $text );

		return self::shortcodeWrap( $html, 'search', $args, FALSE );
	}

	// TODO: rewrite this
	public function shortcode_googlegroups( $atts, $content = NULL, $tag = '' )
	{
		self::__dep();

		$args = shortcode_atts( array(
			'title_wrap' => 'h3',
			'id'         => constant( 'GNETWORK_GOOGLE_GROUP_ID' ),
			'logo'       => 'color',
			'logo_style' => 'border:none;box-shadow:none;',
			'hl'         => constant( 'GNETWORK_GOOGLE_GROUP_HL' ),
			'context'    => NULL,
			'wrap'       => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( FALSE == $args['id'] )
			return NULL;

		// form from : http://socket.io/
		$html = '<form action="http://groups.google.com/group/'.$args['id'].'/boxsubscribe?hl='.$args['hl'].'" id="google-subscribe">';
		$html .= '<a href="http://groups.google.com/group/'.$args['id'].'?hl='.$args['hl'].'"><img src="'.GNETWORK_URL.'assets/images/google_groups_'.$args['logo'].'.png" style="'.$args['logo_style'].'" alt="Google Groups"></a>';
		// <span id="google-members-count">(4889 members)</span>
		$html .= '<div id="google-subscribe-input">'._x( 'Email:', 'ShortCode Module: Google Groups Subscribe', GNETWORK_TEXTDOMAIN );
		$html .= ' <input type="text" name="email" id="google-subscribe-email" data-cip-id="google-subscribe-email" />';
		$html .= ' <input type="hidden" name="hl" value="'.$args['hl'].'" />';
		$html .= ' <input type="submit" name="go" value="'._x( 'Subscribe', 'ShortCode Module: Google Groups Subscribe', GNETWORK_TEXTDOMAIN ).'" /></div></form>';

		return $html;
	}

	// TODO: rewrite this
	// http://pdfobject.com
	// https://github.com/pipwerks/PDFObject
	// TODO : get the standard PDF dimensions for A4
	public function shortcode_pdf( $atts, $content = NULL, $tag = '' )
	{
		self::__dep();

		$args = shortcode_atts( array(
			'url'       => FALSE, // comma seperated multiple url to show multiple pdf // UNFINISHED
			'width'     => '100%', // '840px',
			'height'    => '960px',
			'rand'      => FALSE, // if multiple url then use random
			'navpanes'  => '1',
			'statusbar' => '0',
			'view'      => 'FitH',
			'pagemode'  => 'thumbs',
			'rtl'       => ( is_rtl() ? 'yes' : 'no' ),
			'download'  => FALSE,
			'context'   => NULL,
			'wrap'      => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( ! $args['url'] )
			return NULL;

		if ( $args['rand'] && FALSE !== strpos( $args['url'], ',' ) ) {
			$url = explode( ',', $args['url'] );
			$key = rand( 0, ( count( $url ) - 1 ) );
			$args['url'] = $url[$key];
		}

		$fallback = apply_filters( 'gnetwork_shortcode_pdf_fallback', sprintf( _x( 'It appears you don\'t have Adobe Reader or PDF support in this web browser. <a href="%s">Click here to download the PDF</a>', 'Shortcodes Module: PDF Fallback', GNETWORK_TEXTDOMAIN ), $args['url'] ) );

		$key = count( $this->pdf_ids ) + 1;
		$id = 'gNetworkPDF'.$key;

		// https://github.com/pipwerks/PDFObject
		$this->pdf_ids[$key] = ' var '.$id.' = new PDFObject({url:"'.$args['url']
			.'",id:"'.$id
			.'",width:"'.$args['width']
			.'",height:"'.$args['height']
			.'",pdfOpenParams:{navpanes:'.$args['navpanes']
				.',statusbar:'.$args['statusbar']
				.',view:"'.$args['view']
				.'",pagemode:"'.$args['pagemode']
			.'"}}).embed("'.$id.'div"); ';

		// $this->pdf_ids[$key] = ' var '.$id.' = new PDFObject({url:"'.$args['url'].'",id:"'.$id.'",pdfOpenParams:{navpanes:'.$args['navpanes'].',statusbar:'.$args['statusbar'].',view:"'.$args['view'].'",pagemode:"'.$args['pagemode'].'"}}).embed("'.$id.'div"); ';

		gNetworkUtilities::enqueueScript( 'lib.pdfobject' );

		return '<div id="'.$id.'div">'.$fallback.'</div>';
	}

	// EXAMPLE: [bloginfo key='name']
	public function shortcode_bloginfo( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'key'     => '', // SEE: http://codex.wordpress.org/Template_Tags/bloginfo
			'class'   => '', // OR: 'key-%s'
			'context' => NULL,
			'wrap'    => FALSE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$info = get_bloginfo( $args['key'] );

		if ( $args['wrap'] )
			$info = '<span class="gnetwork-wrap-shortcode -bloginfo -key-'.$args['key'].' '.sprintf( $args['class'], $args['key'] ).'">'.$info.'</span>';

		else if ( $args['class'] )
			$info = '<span class="'.sprintf( $args['class'], $args['key'] ).'">'.$info.'</span>';

		return $info;
	}

	// http://wordpress.org/extend/plugins/kimili-flash-embed/other_notes/
	// http://yoast.com/articles/valid-flash-embedding/
	public function shortcode_flash( $atts, $content = NULL, $tag = '' )
	{
		self::__dep();

		$args = shortcode_atts( array(
			'swf'       => FALSE, // comma seperated multiple url to show multiple flash // UNFINISHED
			'width'     => '800',
			'height'    => '600',
			'rand'      => FALSE, // if multiple url then use random
			'loop'      => 'no',
			'autostart' => 'no',
			'titles'    => '',
			'artists'   => '',
			'duration'  => '',
			'rtl'       => ( is_rtl() ? 'yes' : 'no' ),
			'download'  => FALSE,
			'context'   => NULL,
			'wrap'      => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( ! $args['swf'] )
			return NULL;

		if ( $args['rand'] && FALSE !== strpos( $args['swf'], ',' ) ) {
			$swf = explode( ',', $args['swf'] );
			$key = rand( 0, ( count( $swf ) - 1 ) );
			$args['swf'] = $swf[$key];
		}

		$key = count( $this->flash_ids ) + 1;
		$id  = 'flash-object-'.$key;

		$this->flash_ids[$key] = $id;

		wp_enqueue_script( 'swfobject' );

		return '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$args['width'].'" height="'.$args['height'].'" id="'.$id.'">
<param name="movie" value="'.$args['swf'].'" />
<param name="quality" value="high" />
<!--[if !IE]>-->
<object type="application/x-shockwave-flash" data="'.$args['swf'].'" width="'.$args['width'].'" height="'.$args['height'].'">
<!--<![endif]-->
	<center><a href="'.GNETWORK_GETFLASHPLAYER_URL.'">
		<img src="'.GNETWORK_URL.'assets/images/get_flash_player.gif" alt="Get Adobe Flash player" />
	</a></center>
<!--[if !IE]>-->
</object>
<!--<![endif]-->
</object>';
	}

	// [audio-go to="60"]Go to 60 second mark and play[/audio-go]
	// http://bavotasan.com/2015/working-with-wordpress-and-mediaelement-js/
	public function shortcode_audio_go( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'to'       => '0',
			'instance' => '0',
			'context'  => NULL,
			'wrap'     => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( is_feed() )
			return $content;

		$title = sprintf( _x( 'Go to %s second mark and play', 'Shortcodes Module: Audio Go Shortcode', GNETWORK_TEXTDOMAIN ), $args['to'] );
		$html  = $content ? trim( $content ) : $title;
		$html  = '<a href="#" class="audio-go-to-time" title="'.esc_attr( $title ).'" data-time="'.$args['to'].'" data-instance="'.$args['instance'].'">'.$html.'</a>';

		gNetworkUtilities::enqueueScript( 'front.audio-go' );

		return self::shortcodeWrap( $html, 'audio-go', $args, FALSE );
	}

	// wrapper for default core audio shortcode
	public function shortcode_audio( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'download' => FALSE,
			'filename' => FALSE, // http://davidwalsh.name/download-attribute
			'context'  => NULL,
			'wrap'     => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( $html = wp_audio_shortcode( $atts, $content ) ) {

			if ( $args['download'] && $src = self::getAudioSource( $atts ) )
				$html .= '<div class="download"><a href="'.$src.'"'
					.( $args['filename'] ? ' download="'.$args['filename'].'"' : '' )
					.'>'.$args['download'].'</a></div>';

			return self::shortcodeWrap( $html, 'audio', $args );
		}

		return $content;
	}

	// helper
	public static function getAudioSource( $atts )
	{
		$args = self::atts( array(
			'src'       => FALSE,
			'source'    => FALSE,
			'mp3'       => FALSE,
			'mp3remote' => FALSE,
			'wma'       => FALSE,
			'wmaremote' => FALSE,
			'wma'       => FALSE,
			'wmaremote' => FALSE,
			'wmv'       => FALSE,
			'wmvremote' => FALSE,
		), $atts );

		if ( ! $args['src'] ) {
			$args['src'] = $args['source'];
			if ( ! $args['src'] ) {
				$args['src'] = $args['mp3'];
				if ( ! $args['src'] ) {
					$args['src'] = $args['mp3remote'];
					if ( ! $args['src'] ) {
						$args['src'] = $args['wma'];
						if ( ! $args['src'] ) {
							$args['src'] = $args['wmaremote'];
							if ( ! $args['src'] ) {
								$args['src'] = $args['wmv'];
								if ( ! $args['src'] ) {
									$args['src'] = $args['wmvremote'];
									if ( ! $args['src'] ) {
										return FALSE;
									}
								}
							}
						}
					}
				}
			}
		}

		return $args['src'];
	}

	public function wp_footer()
	{
		// this is for onload, so cannot use wrapJS
		if ( count( $this->pdf_ids ) ) {
			echo '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
			echo 'window.onload = function(){'."\n";
			foreach ( $this->pdf_ids as $id )
				echo $id."\n";
			echo '};';
			echo "\n".'/* ]]> */'."\n".'</script>';
		}

		if ( count( $this->flash_ids ) ) {
			echo '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
			foreach ( $this->flash_ids as $id )
				echo 'swfobject.registerObject("'.$id.'", "9.0.0");'."\n";
			echo "\n".'/* ]]> */'."\n".'</script>';
		}
	}

	// http://en.wikipedia.org/wiki/Help:Footnotes
	public function shortcode_ref( $atts, $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() || is_feed() )
			return NULL;

		$args = shortcode_atts( array(
			'url'           => FALSE,
			'url_title'     => _x( 'See More', 'Shortcodes Module: Ref Shortcode', GNETWORK_TEXTDOMAIN ),
			'url_icon'      => 'def',
			'class'         => 'ref-anchor',
			'format_number' => TRUE,
			'rtl'           => is_rtl(),
			'context'       => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $url = FALSE;

		if ( $content )
			$html = trim( strip_tags( $content ) );

		if ( 'def' == $args['url_icon'] )
			$args['url_icon'] = $args['rtl'] ? '&larr;' : '&rarr;';

		if ( $args['url'] )
			$url = self::html( 'a', array(
				'class'       => 'refrence-external',
				'data-toggle' => 'tooltip',
				'href'        => $args['url'],
				'title'       => $args['url_title'],
			), $args['url_icon'] );

		if ( $html && $url )
			$html = $html.'&nbsp;'.$url;
		else if ( $url )
			$html = $url;

		if ( ! $html )
			return NULL;

		$key = count( $this->ref_ids ) + 1;
		$this->ref_ids[$key] = $html;

		$html = self::html( 'a', array(
			'class'       => 'cite-scroll', // FIXME: add default styles
			'data-toggle' => 'tooltip',
			'href'        => '#citenote-'.$key,
			'title'       => trim( strip_tags( apply_filters( 'string_format_i18n', $content ) ) ),
		), '&#8207;['.( $args['format_number'] ? number_format_i18n( $key ) : $key ).']&#8206;' );

		return '<sup class="ref reference '.$args['class'].'" id="citeref-'.$key.'">'.$html.'</sup>';
	}

	// TODO: add column : http://en.wikipedia.org/wiki/Help:Footnotes#Reference_lists:_columns
	public function shortcode_reflist( $atts, $content = NULL, $tag = '' )
	{
		if ( $this->ref_list || is_feed() ) // FIXME: add notice in feed to read ref on the blog
			return NULL;

		if ( ! is_singular() || ! count( $this->ref_ids ) )
			return NULL;

		$args = shortcode_atts( array(
			'class'         => 'ref-list',
			'number'        => TRUE,
			'after_number'  => '.&nbsp;',
			'format_number' => TRUE,
			'back'          => '[&#8617;]', // '[^]', // '[&uarr;]',
			'back_title'    => _x( 'Back to Text', 'Shortcodes Module: RefList Shortcode', GNETWORK_TEXTDOMAIN ),
			'context'       => NULL,
			'wrap'          => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = '';
		foreach ( $this->ref_ids as $key => $text ) {

			if ( ! $text )
				continue;

			$item  = '<span class="ref-number">';
			$item .= ( $args['number'] ? ( $args['format_number'] ? number_format_i18n( $key ) : $key ).$args['after_number'] : '' );

			$item .= self::html( 'a', array(
				'class'       => 'cite-scroll',
				// 'data-toggle' => 'tooltip',
				'href'        => '#citeref-'.$key,
				'title'       => $args['back_title'],
			), $args['back'] );

			$html .= '<li>'.$item.'</span> <span class="ref-text"><span class="citation" id="citenote-'.$key.'">'.$text.'</span></span></li>';
		}

		$html = self::html( ( $args['number'] ? 'ul' : 'ol' ), array(
			'class' => $args['class'],
		), apply_filters( 'gnetwork_cite_reflist_before', '', $args ).$html );

		if ( ! defined( 'GNETWORK_DISABLE_REFLIST_JS' ) || ! GNETWORK_DISABLE_REFLIST_JS )
			gNetworkUtilities::enqueueScript( 'front.cite' );

		$this->ref_list = TRUE;

		return self::shortcodeWrap( $html, 'reflist', $args );
	}

	public function the_content( $content )
	{
		if ( ! is_singular()
			|| ! count( $this->ref_ids )
			|| $this->ref_list )
				return $content;

		remove_filter( 'the_content', array( $this, 'the_content' ), 20 );
		return $content.apply_filters( 'the_content',
			$this->shortcode_reflist( array(), NULL, 'reflist' ) );
	}

	// FIXME: check this!
	public function shortcode_ref_manual( $atts, $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() || is_feed() )
			return NULL;

		// [ref-m id="0" caption="Caption Title"]
		// [ref-m 0 "Caption Title"]
		if ( isset( $atts['id'] ) ) {
			$args = shortcode_atts( array(
				'id'            => 0,
				'title'         => _x( 'See the footnote', 'Shortcodes Module: Ref Manual Shortcode', GNETWORK_TEXTDOMAIN ),
				'class'         => 'ref-anchor',
				'format_number' => TRUE,
				'context'       => NULL,
				), $atts, $tag );

				if ( FALSE === $args['context'] )
					return NULL;

		} else { // [ref-m 0]
			$args['id'] = isset( $atts[0] ) ? $atts[0] : FALSE;
			$args['title'] = isset( $attrs[1] ) ? $atts[1] : _x( 'See the footnote', 'Shortcodes Module: Ref Manual Shortcode', GNETWORK_TEXTDOMAIN );
			$args['class'] = isset( $attrs[2] ) ? $atts[2] : 'ref-anchor';
			$args['format_number'] = isset( $attrs[3] ) ? $atts[3] : TRUE;
		}

		if ( FALSE === $args['id'] )
			return NULL;

		return '<sup id="citeref-'.$args['id'].'-m" class="reference '.$args['class'].'" title="'.trim( strip_tags( $args['title'] ) ).'" ><a href="#citenote-'.$args['id'].'-m" class="cite-scroll">['.( $args['format_number'] ? number_format_i18n( $args['id'] ) : $args['id'] ).']</a></sup>';
	}

	// FIXME: check this!
	public function shortcode_reflist_manual( $atts, $content = NULL, $tag = '' )
	{
		if ( is_feed() )
			return NULL;

		// [reflist-m id="0" caption="Caption Title"]
		// [reflist-m 0 "Caption Title"]
		if ( isset( $atts['id'] ) ) {
			$args = shortcode_atts( array(
				'id'            => 0,
				'title'         => _x( 'See the footnote', 'Shortcodes Module: RefList Manual Shortcode', GNETWORK_TEXTDOMAIN ),
				'class'         => 'ref-anchor',
				'format_number' => TRUE,
				'back'          => '[&#8617;]', //'&uarr;',
				'context'       => NULL,
				'wrap'          => TRUE,
			), $atts, $tag );

			if ( FALSE === $args['context'] )
				return NULL;

		} else { // [reflist-m 0]
			$args['id']            = $atts[0];
			$args['title']         = isset( $attrs[1] ) ? $atts[1] : _x( 'See the footnote', 'Shortcodes Module: RefList Manual Shortcode', GNETWORK_TEXTDOMAIN );
			$args['class']         = isset( $attrs[2] ) ? $atts[2] : 'ref-anchor';
			$args['format_number'] = isset( $attrs[3] ) ? $atts[3] : TRUE;
			$args['back']          = isset( $attrs[4] ) ? $atts[4] : '[&#8617;]';
			$args['after_number']  = isset( $attrs[4] ) ? $atts[4] : '. ';
			$args['wrap']          = TRUE;
		}

		gNetworkUtilities::enqueueScript( 'front.cite' );

		return '<span>'.( $args['format_number'] ? number_format_i18n( $args['id'] ) : $args['id'] ).$args['after_number']
				.'<span class="ref-backlink"><a href="#citeref-'.$args['id'].'-m" class="cite-scroll">'.$args['back']
				.'</a></span><span class="ref-text"><span class="citation" id="citenote-'.$args['id'].'-m">&nbsp;</span></span></span>';
	}

	public function shortcode_person( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'      => FALSE,
			'name'    => FALSE,
			'class'   => 'refrence-people',
			'context' => NULL,
			'wrap'    => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( $args['name'] )
			$person = trim( $args['name'] );
		else if ( is_null( $content ) )
			return NULL;
		else
			$person = trim( strip_tags( $content ) );

		if ( ! array_key_exists( $person, $this->people ) ) {
			$term = get_term_by( 'name', $person, $this->people_tax );

			if ( ! $term )
				return $content;

			// FIXME: must cache the term, not html
			$this->people[$person] = self::html( 'a', array(
				'href'        => get_term_link( $term, $term->taxonomy ),
				'title'       => sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
				'data-toggle' => 'tooltip',
				'class'       => array(
					$args['class'],
					'person-'.$term->slug,
					'tooltip',
				),
			), ( $content ? trim( strip_tags( $content ) ) : $term->name ) );
		}

		return self::shortcodeWrap( $this->people[$person], 'person', $args, FALSE );
	}
}

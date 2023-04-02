<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\PostType as WPPostType;

class Typography extends gNetwork\Module
{

	protected $key     = 'typography';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 12 );

		if ( $this->options['title_sanitize'] ) {
			$this->filter( 'sanitize_title', 3, 1 );
			$this->filter( 'pre_term_slug', 2, 1 );
			$this->filter_module( 'taxonomy', 'term_rewrite_slug', 3, 8 );
		}

		if ( is_admin() )
			return;

		$this->filter( 'the_content', 1, 1, 'early' );

		if ( $this->options['title_titlecase']
			|| $this->options['title_wordwrap'] )
				$this->filter( 'the_title' );

		if ( $this->options['widget_wordwrap'] )
			$this->filter( 'widget_title' );

		if ( $this->options['general_typography']
			|| $this->options['arabic_typography']
			|| $this->options['persian_typography']
			|| $this->options['linkify_content'] )
				$this->filter( 'the_content', 1, 1000, 'late' );

		add_filter( $this->hook( 'general' ), [ $this, 'general_typography' ] ); // force apply
		add_filter( $this->hook( 'arabic' ), [ $this, 'arabic_typography' ] ); // force apply
		add_filter( $this->hook( 'persian' ), [ $this, 'persian_typography' ] ); // force apply
		add_filter( $this->hook( 'linkify' ), [ $this, 'linkify_content' ] ); // force apply
		add_filter( $this->hook(), [ $this, 'the_content_late' ] ); // only applies enabled
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Typography', 'Modules: Menu Name', 'gnetwork' ) );
		$this->register_tool( _x( 'Titles', 'Modules: Menu Name', 'gnetwork' ), 'titles' );
	}

	public function default_options()
	{
		return [
			'tools_accesscap'     => 'edit_others_posts',
			'register_blocktypes' => '0',
			'register_shortcodes' => '0',
			'editor_buttons'      => '0',
			'title_sanitize'      => '1',
			'title_titlecase'     => '0',
			'title_wordwrap'      => '0',
			'widget_wordwrap'     => '0',
			'general_typography'  => '0',
			'arabic_typography'   => '0',
			'persian_typography'  => '0',
			'linkify_content'     => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'tools_accesscap',
					'type'        => 'cap',
					'title'       => _x( 'Tools Access', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Selected and above can access the typography tools.', 'Modules: Typography: Settings', 'gnetwork' ),
					'default'     => 'edit_others_posts',
				],
				[
					'field'       => 'linkify_content',
					'title'       => _x( 'Linkify Content', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Tries to linkify hash-tags on post contents.', 'Modules: Typography: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'persian_typography',
					'title'       => _x( 'Persian Typography', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Applies Persian typography on post contents.', 'Modules: Typography: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'arabic_typography',
					'title'       => _x( 'Arabic Typography', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Applies Arabic typography on post contents.', 'Modules: Typography: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'general_typography',
					'title'       => _x( 'General Typography', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Applies general typography on post contents.', 'Modules: Typography: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'title_sanitize',
					'title'       => _x( 'Extra Title Sanitization', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Tries to additional sanitization checks on slugs from titles.', 'Modules: Typography: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'title_titlecase',
					'title'       => _x( 'Titles in Title Case', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Tries to make post titles properly-cased.', 'Modules: Typography: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://gist.github.com/geminorum/fe2a9ba25db5cf2e5ad6718423d00f8a' ),
				],
				[
					'field'       => 'title_wordwrap',
					'title'       => _x( 'Word Wrapper for Post Titles', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Prevents widow words in the end of post titles.', 'Modules: Typography: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://davidwalsh.name/word-wrap-mootools-php' ),
				],
				[
					'field'       => 'widget_wordwrap',
					'title'       => _x( 'Word Wrapper for Widget Titles', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Prevents widow words in the end of widget titles.', 'Modules: Typography: Settings', 'gnetwork' ),
				],
				'register_blocktypes',
				'register_shortcodes',
				'editor_buttons',
			],
		];
	}

	public function tools( $sub = NULL, $key = NULL )
	{
		parent::tools( $sub, 'titles' );
	}

	protected function tools_buttons( $sub = NULL )
	{
		$this->register_button( 'format_i18n', _x( 'Format I18n', 'Modules: Typography', 'gnetwork' ) );
		$this->register_button( 'downcode_slugs', _x( 'DownCode Slugs', 'Modules: Typography', 'gnetwork' ) );
		$this->register_button( 'rewrite_slugs', _x( 'Rewrite Slugs', 'Modules: Typography', 'gnetwork' ) );
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( 'titles' == $sub ) {

			if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub, 'tools' );

				if ( self::isTablelistAction( 'rewrite_slugs', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( ! $post = get_post( $post_id ) )
							continue;

						$updated = wp_update_post( [
							'ID'        => $post->ID,
							'post_name' => Text::formatSlug( $post->post_title ),
						] );

						if ( ! $updated || self::isError( $updated ) )
							continue;

						$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'changed',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else if ( self::isTablelistAction( 'downcode_slugs', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( ! $post = get_post( $post_id ) )
							continue;

						$updated = wp_update_post( [
							'ID'        => $post->ID,
							'post_name' => Utilities::URLifyDownCode( Text::formatSlug( $post->post_title ) ),
						] );

						if ( ! $updated || self::isError( $updated ) )
							continue;

						$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'changed',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else if ( self::isTablelistAction( 'format_i18n', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( ! $post = get_post( $post_id ) )
							continue;

						$updated = wp_update_post( [
							'ID'         => $post->ID,
							'post_title' => apply_filters( 'string_format_i18n', $post->post_title ),
						] );

						if ( ! $updated || self::isError( $updated ) )
							continue;

						$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'changed',
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
		}
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		list( $posts, $pagination ) = self::getTablelistPosts();

		$pagination['before'][] = self::filterTablelistSearch();

		return HTML::tableList( [
			'_cb'  => 'ID',
			'ID'   => _x( 'ID', 'Modules: Typography: Column Title', 'gnetwork' ),
			'type' => [
				'title'    => _x( 'Type', 'Modules: Typography: Column Title', 'gnetwork' ),
				'args'     => [ 'post_types' => WPPostType::get( 2 ) ],
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					return isset( $column['args']['post_types'][$row->post_type] )
						? $column['args']['post_types'][$row->post_type]
						: $row->post_type;
				},
			],
			'slug' => [
				'title'    => _x( 'Slug', 'Modules: Typography: Column Title', 'gnetwork' ),
				// 'class'    => '-ltr',
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					// TODO: must warn for customized slugs
					// TODO: title attr for more info
					return HTML::tag( 'code', urldecode( $row->post_name ) )
						.'<br />'.HTML::tag( 'code', urldecode( Text::formatSlug( Number::intval( $row->post_title, FALSE ) ) ) );
						// .'<br />'.HTML::tag( 'code', urldecode( sanitize_title( Number::intval( $row->post_title, FALSE ) ) ) );
				},
			],
			'title' => [
				'title'    => _x( 'Title', 'Modules: Typography: Column Title', 'gnetwork' ),
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					return Utilities::getPostTitle( $row );
				},
				'actions' => static function( $value, $row, $column, $index, $key, $args ) {
					$list = [];

					if ( current_user_can( 'edit_post', $row->ID ) )
						$list['edit'] = HTML::tag( 'a', [
							'href'   => WordPress::getPostEditLink( $row->ID ),
							'class'  => '-link -row-link -row-link-edit',
							'data'   => [ 'id' => $row->ID, 'row' => 'edit' ],
							'target' => '_blank',
						], _x( 'Edit', 'Modules: Typography: Row Action', 'gnetwork' ) );

					$list['view'] = HTML::tag( 'a', [
						'href'   => WordPress::getPostShortLink( $row->ID ),
						'class'  => '-link -row-link -row-link-view',
						'data'   => [ 'id' => $row->ID, 'row' => 'view' ],
						'target' => '_blank',
					], _x( 'View', 'Modules: Typography: Row Action', 'gnetwork' ) );

					return $list;
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Titles and Slugs', 'Modules: Typography', 'gnetwork' ) ),
			'empty'      => HTML::warning( _x( 'No Posts!', 'Modules: Typography', 'gnetwork' ) ),
			'pagination' => $pagination,
		] );
	}

	// TODO: wordwrap headings in content / lookout for link in titles!
	// TODO: ؟! -> ?!
	// @SEE: `wp_replace_in_html_tags()`
	public function general_typography( $content )
	{
		$content = str_ireplace( [
			'<p>***</p>',
			'<p><strong>***</strong></p>',
			'<p style="text-align:center">***</p>',
			'<p style="text-align:center"><strong>***</strong></p>',
		], $this->shortcode_three_asterisks(), $content );

		// FIXME: DRAFT for date: not tested!
		// @REF: http://stackoverflow.com/a/3337480
		// $content = preg_replace( "/(^| )([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4})( |$)/is", "$1<span class=\"date\">$2</span>$3", $content );

		// FIXME: DRAFT for phone: not tested!
		// @REF: https://www.regextester.com/1950
		// $content = preg_replace( "/(^| )(([+]{1}[0-9]{2}|0)[0-9]{9})( |$)/is", "$1[tel]$2[/tel]$3", $content );

		return $content;
	}

	// FIXME: use <abbr> and full def: https://developer.mozilla.org/en/docs/Web/HTML/Element/abbr
	// رضوان ﷲ علیهما
	// رضوان‌ﷲ علیه
	// قدّس سره
	// صلی ﷲ علیه و علی آله
	public function arabic_typography( $content )
	{
		$content = preg_replace( "/[\s\t]+(?:(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\)))/i", "$1", $content ); // clean space/tab before
		// $content = preg_replace( "/(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\))(?![^<]*>|[^<>]*<\/)/ix", '&#xfeff;'."<sup><abbr>$1</abbr></sup>", $content ); // @REF: http://stackoverflow.com/a/18622606
		$content = preg_replace( "/(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\))(?![^<]*>)/ix", '&#xfeff;'."<sup><abbr>$1</abbr></sup>", $content ); // same as above but works in html tags

		$content = preg_replace( "/\(علیهم السلام\)/i", '&#xfeff;'."<sup>(علیهم السلام)</sup>", $content );
		$content = preg_replace( "/\(علیهم‌السلام\)/i", '&#xfeff;'."<sup>(علیهم السلام)</sup>", $content );
		$content = preg_replace( "/\(علیه السلام\)/i", '&#xfeff;'."<sup>(علیه السلام)</sup>", $content );
		$content = preg_replace( "/\(علیه‌السلام\)/i", '&#xfeff;'."<sup>(علیه السلام)</sup>", $content );
		$content = preg_replace( "/\(علیها السلام\)/i", '&#xfeff;'."<sup>(علیها السلام)</sup>", $content );
		$content = preg_replace( "/\(علیهاالسلام\)/i", '&#xfeff;'."<sup>(علیها السلام)</sup>", $content );

		$content = str_ireplace( [
			'آیة‌الله',
			'آیت الله',
		], 'آیت‌الله', $content ); // no space

		$content = str_ireplace( [
			'آیت الله العظمی',
			'آیت الله‌العظمی',
			'آیت‌الله العظمی',
		], 'آیت‌الله‌العظمی', $content ); // no space

		$content = str_ireplace( [
			'حجه الاسلام',
			'حجه‌الاسلام',
			'حجةالاسلام',
			'حجة‌الاسلام',
			'حجت الاسلام',
		], 'حجت‌الاسلام', $content ); // no space

		$content = str_ireplace( [
			'ثقةالاسلام',
			'ثقة‌الاسلام',
			'ثقه الاسلام',
			'ثقه‌الاسلام',
		], 'ثقة الاسلام', $content ); // with space

		$content = str_ireplace( [
			// 'اللَّه',
			'الله',
		],'ﷲ', $content );

		return $content;
	}

	// TODO: check for number with precentage sign
	public function persian_typography( $content )
	{
		$content = str_ireplace( '&#8220;', '&#xAB;', $content );
		$content = str_ireplace( '&#8221;', '&#xBB;', $content );

		return $content;
	}

	public function linkify_content( $content )
	{
		if ( gNetwork()->option( 'linkify_hashtags', 'search' )
			&& ! self::const( 'GNETWORK_DISABLE_LINKIFY_CONTENT' ) ) {

			$content = Text::replaceSymbols( '#', $content, static function( $matched, $string ) {
				return HTML::link( str_replace( '_', ' ', $matched ), WordPress::getSearchLink( $matched ) );
			} );

			// telegram hash-tag links!
			$content = preg_replace_callback( '/<a href="\/\/search_hashtag\?hashtag=(.*?)">#(.*?)<\/a>/miu', static function( $matched ) {
				return HTML::link( '#'.str_replace( '_', ' ', $matched[2] ), WordPress::getSearchLink( '#'.$matched[2] ) );
			}, $content );
		}

		if ( gNetwork()->option( 'content_replace', 'branding' ) ) {

			$brand_name = gNetwork()->brand( 'name' );
			$brand_url  = gNetwork()->brand( 'url' );

			$content = Text::replaceWords( [ $brand_name ], $content, static function( $matched ) use ( $brand_url ) {
				return '<em>'.HTML::link( $matched, $brand_url ).'</em>';
			} );
		}

		return $content;
	}

	public function init()
	{
		if ( $this->options['editor_buttons'] ) {

			add_action( 'gnetwork_tinymce_strings', [ $this, 'tinymce_strings' ] );

			Admin::registerTinyMCE( 'gnetworkquote', 'assets/js/tinymce/quote', 1 );
			Admin::registerTinyMCE( 'gnetworkasterisks', 'assets/js/tinymce/asterisks', 2 );
		}

		$this->register_blocktypes();
		$this->register_shortcodes();
	}

	protected function get_blocktypes()
	{
		return [
			[
				'asterisks',
			]
		];
	}

	protected function get_shortcodes()
	{
		return [
			'wiki'            => 'shortcode_wiki',
			'wiki-en'         => 'shortcode_wiki',
			'wiki-fa'         => 'shortcode_wiki',
			'bismillah'       => 'shortcode_bismillah',
			'basmala'         => 'shortcode_bismillah',
			'three-asterisks' => 'shortcode_three_asterisks',
			'nst'             => 'shortcode_numeral_section_title',
			'ltr'             => 'shortcode_ltr',
			'pad'             => 'shortcode_pad',
			'spacer'          => 'shortcode_spacer',
			'clearleft'       => 'shortcode_clearleft',
			'clearright'      => 'shortcode_clearright',
			'clearboth'       => 'shortcode_clearboth',
		];
	}

	public function tinymce_strings( $strings )
	{
		$new = [
			'gnetworkasterisks-title' => _x( 'Asterisks', 'TinyMCE Strings: Asterisks', 'gnetwork' ),

			'gnetworkquote-title'    => _x( 'Quote This', 'TinyMCE Strings: Quote', 'gnetwork' ),
			'gnetworkquote-attr'     => _x( 'Quote This', 'TinyMCE Strings: Quote', 'gnetwork' ),
			'gnetworkquote-text'     => _x( 'Quote Text', 'TinyMCE Strings: Quote', 'gnetwork' ),
			'gnetworkquote-cite'     => _x( 'Cite Text', 'TinyMCE Strings: Quote', 'gnetwork' ),
			'gnetworkquote-url'      => _x( 'Cite URL', 'TinyMCE Strings: Quote', 'gnetwork' ),
			'gnetworkquote-align'    => _x( 'Quote Align', 'TinyMCE Strings: Quote', 'gnetwork' ),
			'gnetworkquote-epigraph' => _x( 'Epigraph', 'TinyMCE Strings: Quote', 'gnetwork' ),
			'gnetworkquote-rev'      => _x( 'Reverse', 'TinyMCE Strings: Quote', 'gnetwork' ),
		];

		return array_merge( $strings, $new );
	}

	// @SEE: https://wordpress.stackexchange.com/a/51809
	public function sanitize_title( $title, $raw_title = '', $context = 'display' )
	{
		return 'save' == $context && seems_utf8( $raw_title )
			? Text::formatSlug( $raw_title )
			: $title;
	}

	public function pre_term_slug( $value, $taxonomy )
	{
		return Text::formatSlug( $value );
	}

	public function taxonomy_term_rewrite_slug( $name, $term, $taxonomy )
	{
		return Text::formatSlug( $name );
	}

	public function the_content_early( $content )
	{
		$content = str_ireplace(
			'<p style="text-align: center;">***</p>',
			$this->shortcode_three_asterisks(),
		$content );

		$content = str_ireplace( ' [ref', '[ref', $content );

		return $content;
	}

	public function the_content_late( $content )
	{
		if ( $this->options['linkify_content'] )
			$content = $this->filters( 'linkify', $content );

		if ( $this->options['general_typography'] )
			$content = $this->filters( 'general', $content );

		if ( $this->options['arabic_typography'] )
			$content = $this->filters( 'arabic', $content );

		if ( $this->options['persian_typography'] )
			$content = $this->filters( 'persian', $content );

		return $content;
	}

	// @SEE: https://wordpress.org/plugins/wikilinker/
	public function shortcode_wiki( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'slug'    => NULL,
			'lang'    => NULL,
			'domain'  => 'wikipedia.org/wiki',
			'scheme'  => 'https',
			'title'   => _x( 'View Wikipedia page', 'Modules: Typography: Shortcode Defaults', 'gnetwork' ),
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( $args['slug'] )
			$slug = trim( $args['slug'] );
		else if ( $content )
			$slug = trim( $content );
		else
			return $content;

		if ( $args['lang'] )
			$lang = trim( $args['lang'] ).'.';
		else if ( 'wiki-fa' == $tag )
			$lang = 'fa.';
		else if ( 'wiki-en' == $tag )
			$lang = 'en.';
		else
			$lang = '';

		$url = $args['scheme'].'://'.$lang.$args['domain'].'/'.urlencode( str_ireplace( ' ', '_', $slug ) );

		$html = '<a href="'.esc_url( $url ).'" class="wiki wikipedia"'
				.( $args['title'] ? ' data-toggle="tooltip" title="'.HTML::escape( $args['title'] ).'"' : '' )
				.'>'.trim( $content ).'</a>';

		return self::shortcodeWrap( $html, 'wikipedia', $args, FALSE );
	}

	// @REF: https://unicode-table.com/en/FDFD/
	public function shortcode_bismillah( $atts = [], $content = NULL, $tag = '' )
	{
		return self::shortcodeWrap( html_entity_decode( '&#65021;', ENT_QUOTES, 'UTF-8' ), 'bismillah', [ 'wrap' => TRUE ] );
	}

	// @SOURCE: http://writers.stackexchange.com/a/3304
	// @SOURCE: http://en.wikipedia.org/wiki/Asterisk
	public function shortcode_three_asterisks( $atts = [], $content = NULL, $tag = '' )
	{
		return self::shortcodeWrap( '&#x274b;&nbsp;&#x274b;&nbsp;&#x274b;', 'asterisks', [ 'wrap' => TRUE ] );
	}

	public function shortcode_numeral_section_title( $atts = [], $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() )
			return NULL;

		return '<h3 class="numeral-section-title">'.$content.'</h3>';
	}

	// FIXME: use entities in tel short code
	public function shortcode_ltr( $atts = [], $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) )
			return $content;

		return '<span class="ltr" dir="ltr">'.apply_shortcodes( $content, TRUE ).'</span>';
	}

	public function shortcode_pad( $atts = [], $content = NULL, $tag = '' )
	{
		if ( isset( $atts['space'] ) ) {

			$args = shortcode_atts( [
				'space'   => 3,
				'class'   => 'typography-pad',
				'context' => NULL,
			], $atts, $tag );

			if ( FALSE === $args['context'] )
				return NULL;

		} else {

			$args = [
				'space' => empty( $atts[0] ) ? 3 : $atts[0],
				'class' => empty( $atts[1] ) ? 'typography-pad' : $atts[1],
			];
		}

		if ( ! $args['space'] )
			return NULL;

		$html = '';

		for ( $i = 1; $i <= $args['space']; $i++ )
			$html.= '<span></span>';

		return HTML::tag( 'span', [
			'class' => $args['class'],
		], $html );
	}

	public function shortcode_spacer( $atts = [], $content = NULL, $tag = '' )
	{
		if ( isset( $atts['space'] ) ) {

			$args = shortcode_atts( [
				'space'   => 10,
				'class'   => 'typography-spacer',
				'id'      => FALSE,
				'context' => NULL,
			], $atts, $tag );

			if ( FALSE === $args['context'] )
				return NULL;
		} else {

			$args = [
				'space' => empty( $atts[0] ) ? 3 : $atts[0],
				'class' => empty( $atts[1] ) ? 'typography-pad' : $atts[1],
			];
		}

		$args['style'] = 'height:'.absint( $args['space'] ).'px;margin:0;padding:0;';
		unset( $args['space'], $args['context'] );

		return HTML::tag( 'div', $args, NULL );
	}

	public function shortcode_clearleft( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'class'   => 'typography-clearleft',
			'id'      => FALSE,
			'span'    => FALSE,
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$tag = $args['span'] ? 'span' : 'div';
		$args['style'] = 'clear:left;';
		unset( $args['span'], $args['context'] );

		return HTML::tag( $tag, $args, NULL );
	}

	public function shortcode_clearright( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'class'   => 'typography-clearright',
			'id'      => FALSE,
			'span'    => FALSE,
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$tag = $args['span'] ? 'span' : 'div';
		$args['style'] = 'clear:right;';
		unset( $args['span'], $args['context'] );

		return HTML::tag( $tag, $args, NULL );
	}

	public function shortcode_clearboth( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'class'   => 'typography-clearboth',
			'id'      => FALSE,
			'span'    => FALSE,
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$tag = $args['span'] ? 'span' : 'div';
		$args['style'] = 'clear:both;';
		unset( $args['span'], $args['context'] );

		return HTML::tag( $tag, $args, NULL );
	}

	public function the_title( $title )
	{
		if ( ! $title )
			return $title;

		if ( $this->options['title_titlecase'] )
			$title = Text::titleCase( $title );

		if ( $this->options['title_wordwrap'] && ! WordPress::isREST() )
			$title = Text::wordWrap( $title );

		return $title;
	}

	public function widget_title( $title )
	{
		return empty( $title ) ? $title : Text::wordWrap( $title );
	}
}

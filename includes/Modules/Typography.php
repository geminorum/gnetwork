<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

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

		if ( $this->options['remove_empty_p']
			|| $this->options['remove_double_spaces'] )
				$this->filter( 'the_content', 1, 20 );

		if ( $this->options['title_titlecase']
			|| $this->options['title_wordwrap'] )
				$this->filter( 'the_title', 2 );

		if ( $this->options['widget_wordwrap'] )
			$this->filter( 'widget_title' );

		if ( $this->options['general_typography']
			|| $this->options['arabic_typography']
			|| $this->options['persian_typography']
			|| $this->options['linkify_content'] )
				$this->filter( 'the_content', 1, 1000, 'late' );

		add_filter( $this->hook( 'general' ), [ $this, 'general_typography' ] );  // force apply
		add_filter( $this->hook( 'arabic' ), [ $this, 'arabic_typography' ] );    // force apply
		add_filter( $this->hook( 'persian' ), [ $this, 'persian_typography' ] );  // force apply
		add_filter( $this->hook( 'linkify' ), [ $this, 'linkify_content' ] );     // force apply
		add_filter( $this->hook(), [ $this, 'the_content_late' ] );               // only applies enabled
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Typography', 'Modules: Menu Name', 'gnetwork' ) );
		$this->register_tool( _x( 'Titles', 'Modules: Menu Name', 'gnetwork' ), 'titles' );
	}

	public function default_options()
	{
		return [
			'tools_accesscap'      => 'edit_others_posts',
			'register_blocktypes'  => '0',
			'register_shortcodes'  => '0',
			'editor_buttons'       => '0',
			'title_sanitize'       => '1',
			'title_titlecase'      => '0',
			'title_wordwrap'       => '0',
			'widget_wordwrap'      => '0',
			'general_typography'   => '0',
			'arabic_typography'    => '0',
			'persian_typography'   => '0',
			'linkify_content'      => '0',
			'remove_empty_p'       => '1',                   // FIXME: add UI
			'remove_double_spaces' => '0',                   // FIXME: add UI
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
				[
					'field'       => 'remove_empty_p',
					'title'       => _x( 'Empty Paragraphs', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Tries to remove empty paragraph tags on the content.', 'Modules: Typography: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'remove_double_spaces',
					'title'       => _x( 'Double Spaces', 'Modules: Typography: Settings', 'gnetwork' ),
					'description' => _x( 'Tries to replace consecutive spaces with single one on the content.', 'Modules: Typography: Settings', 'gnetwork' ),
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
							'post_name' => Core\Text::formatSlug( $post->post_title ),
						] );

						if ( ! $updated || self::isError( $updated ) )
							continue;

						$count++;
					}

					WordPress\Redirect::doReferer( [
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
							'post_name' => Utilities::URLifyDownCode( Core\Text::formatSlug( $post->post_title ) ),
						] );

						if ( ! $updated || self::isError( $updated ) )
							continue;

						$count++;
					}

					WordPress\Redirect::doReferer( [
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

					WordPress\Redirect::doReferer( [
						'message' => 'changed',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else {

					WordPress\Redirect::doReferer( [
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

		return Core\HTML::tableList( [
			'_cb' => 'ID',
			'ID'  => _x( 'ID', 'Modules: Typography: Column Title', 'gnetwork' ),

			'date' => [
				'title'    => _x( 'Date', 'Modules: Typography: Column Title', 'gnetwork' ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					return Utilities::humanTimeDiffRound( strtotime( $row->post_date ) );
				},
			],

			'type' => [
				'title'    => _x( 'Type', 'Modules: Typography: Column Title', 'gnetwork' ),
				'args'     => [ 'post_types' => WordPress\PostType::get( 2 ) ],
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					return isset( $column['args']['post_types'][$row->post_type] )
						? $column['args']['post_types'][$row->post_type]
						: $row->post_type;
				},
			],
			'slug' => [
				'title'    => _x( 'Slug', 'Modules: Typography: Column Title', 'gnetwork' ),
				// 'class'    => '-ltr',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					// TODO: must warn for customized slugs
					// TODO: title attribute for more info
					return Core\HTML::code( urldecode( $row->post_name ) )
						.'<br />'.Core\HTML::code( urldecode( Core\Text::formatSlug( $row->post_title ) ) );
						// .'<br />'.Core\HTML::code( urldecode( sanitize_title( Number::translate( $row->post_title ) ) ) );
				},
			],
			'title' => [
				'title'    => _x( 'Title', 'Modules: Typography: Column Title', 'gnetwork' ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					return Utilities::getPostTitle( $row );
				},
				'actions' => static function ( $value, $row, $column, $index, $key, $args ) {
					$list = [];

					if ( current_user_can( 'edit_post', $row->ID ) )
						$list['edit'] = Core\HTML::tag( 'a', [
							'href'   => WordPress\Post::edit( $row ),
							'class'  => '-link -row-link -row-link-edit',
							'data'   => [ 'id' => $row->ID, 'row' => 'edit' ],
							'target' => '_blank',
						], _x( 'Edit', 'Modules: Typography: Row Action', 'gnetwork' ) );

					$list['view'] = Core\HTML::tag( 'a', [
						'href'   => WordPress\Post::shortlink( $row ),
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
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Titles and Slugs', 'Modules: Typography', 'gnetwork' ) ),
			'empty'      => Core\HTML::warning( _x( 'No Posts!', 'Modules: Typography', 'gnetwork' ) ),
			'pagination' => $pagination,
		] );
	}

	// TODO: `wordwrap` headings in content / lookout for link in titles!
	// TODO: ؟! -> ?!
	// @SEE: `wp_replace_in_html_tags()`
	public function general_typography( $content )
	{
		$content = str_ireplace( [
			'<p>***</p>',
			'<p><strong>***</strong></p>',
			'<p style="text-align: center;">***</p>',
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

	// TODO: check for number with percentage sign
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

			$content = Core\Text::replaceSymbols( '#', $content,
				static function ( $matched, $string ) {
					return Core\HTML::tag( 'a', [
						'href'  => WordPress\URL::search( $matched ),
						'class' => [ '-link', '-hashtag' ]
					], str_replace( '_', ' ', $matched ) );
				} );

			// telegram hash-tag links!
			$content = preg_replace_callback(
				'/<a href="\/\/search_hashtag\?hashtag=(.*?)">#(.*?)<\/a>/miu',
				static function ( $matched ) {
					return Core\HTML::link(
						sprintf( '#%s', str_replace( '_', ' ', $matched[2] ) ),
						WordPress\URL::search( '#'.$matched[2] )
					);
				},
				$content
			);
		}

		if ( gNetwork()->option( 'content_replace', 'branding' ) ) {

			$brand_name = gNetwork()->brand( 'name' );
			$brand_url  = gNetwork()->brand( 'url' );

			$content = Core\Text::replaceWords( [ $brand_name ], $content,
				static function ( $matched ) use ( $brand_url ) {
					return '<em>'.Core\HTML::link( $matched, $brand_url ).'</em>';
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
		return 'save' === $context && Core\Text::containsUTF8( $raw_title )
			? Core\Text::formatSlug( $raw_title )
			: $title;
	}

	public function pre_term_slug( $value, $taxonomy )
	{
		return Core\Text::formatSlug( $value );
	}

	public function taxonomy_term_rewrite_slug( $name, $term, $taxonomy )
	{
		return Core\Text::formatSlug( $name );
	}

	public function the_content_early( $content )
	{
		// $content = str_ireplace(
		// 	'<p style="text-align: center;">***</p>',
		// 	$this->shortcode_three_asterisks(),
		// $content );

		$content = str_ireplace( ' [ref', '[ref', $content );

		return $content;
	}

	public function the_content( $content )
	{
		if ( $this->options['remove_empty_p'] )
			$content = Core\Text::noEmptyP( $content );

		if ( $this->options['remove_double_spaces'] )
			$content = Core\Text::singleWhitespaceUTF8( $content );

		return $content;
	}

	public function the_content_late( $content )
	{
		if ( self::const( 'GTHEME_IS_SYSTEM_PAGE' ) )
			return $content;

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
				.( $args['title'] ? ' data-toggle="tooltip" title="'.Core\HTML::escape( $args['title'] ).'"' : '' )
				.'>'.trim( $content ).'</a>';

		return self::shortcodeWrap( $html, 'wikipedia', $args, FALSE );
	}

	// @REF: https://unicode-table.com/en/FDFD/
	public function shortcode_bismillah( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'markup'  => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		return self::shortcodeWrap(
			$args['markup'] ?? html_entity_decode( '&#65021;', ENT_QUOTES, 'UTF-8' ),
			'bismillah',
			$args
		);
	}

	// @SOURCE: http://writers.stackexchange.com/a/3304
	// @SOURCE: http://en.wikipedia.org/wiki/Asterisk
	// @SEE: https://spec.commonmark.org/0.31.2/#thematic-breaks
	// `***`/`---`/`___`
	// `+++`/`===`
	public function shortcode_three_asterisks( $atts = [], $content = NULL, $tag = '' )
	{
		return self::shortcodeWrap( '&#x274b;&nbsp;&#x274b;&nbsp;&#x274b;', 'asterisks', [ 'wrap' => TRUE ] );
	}

	public function shortcode_numeral_section_title( $atts = [], $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() )
			return NULL;

		return vsprintf( '<%4$s class="%3$s" title="%2$s">%1$s</%4$s>', [
			Core\Number::localize( $content ),
			Core\HTML::escape( Core\Number::toOrdinal( $content ) ),
			'numeral-section-title',
			'h3',
		] );
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

		return Core\HTML::tag( 'span', [
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

		return Core\HTML::tag( 'div', $args, NULL );
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

		return Core\HTML::tag( $tag, $args, NULL );
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

		return Core\HTML::tag( $tag, $args, NULL );
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

		return Core\HTML::tag( $tag, $args, NULL );
	}

	public function the_title( $title, $post_id )
	{
		if ( ! $title )
			return $title;

		if ( $this->options['title_titlecase'] )
			$title = Core\Text::titleCase( $title );

		if ( ! $this->options['title_wordwrap'] )
			return $title;

		if ( WordPress\IsIt::rest() )
			return $title;

		if ( function_exists( 'wc_get_product' ) && wc_get_product( $post_id ) )
			return $title;

		return Core\Text::wordWrap( $title );
	}

	public function widget_title( $title )
	{
		return empty( $title ) ? $title : Core\Text::wordWrap( $title );
	}
}

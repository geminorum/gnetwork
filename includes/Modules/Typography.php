<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;

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
			|| $this->options['persian_typography'] )
				$this->filter( 'the_content', 1, 1000, 'late' );

		add_filter( $this->hook( 'general' ), [ $this, 'general_typography' ] ); // force apply
		add_filter( $this->hook( 'arabic' ), [ $this, 'arabic_typography' ] ); // force apply
		add_filter( $this->hook( 'persian' ), [ $this, 'persian_typography' ] ); // force apply
		add_filter( $this->hook(), [ $this, 'the_content_late' ] ); // only applies enabled
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Typography', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
	}

	public function default_options()
	{
		return [
			'register_shortcodes' => '0',
			'editor_buttons'      => '0',
			'title_sanitize'      => '0',
			'title_titlecase'     => '0',
			'title_wordwrap'      => '0',
			'widget_wordwrap'     => '0',
			'general_typography'  => '0',
			'arabic_typography'   => '0',
			'persian_typography'  => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'persian_typography',
					'title'       => _x( 'Persian Typography', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Applies Persian typography on post contents.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'arabic_typography',
					'title'       => _x( 'Arabic Typography', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Applies Arabic typography on post contents.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'general_typography',
					'title'       => _x( 'General Typography', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Applies general typography on post contents.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'title_sanitize',
					'title'       => _x( 'Extra Title Sanitization', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Tries to additional sanitization checks on slugs from titles.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'title_titlecase',
					'title'       => _x( 'Titles in Title Case', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Tries to make post titles properly-cased.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://gist.github.com/geminorum/fe2a9ba25db5cf2e5ad6718423d00f8a' ),
				],
				[
					'field'       => 'title_wordwrap',
					'title'       => _x( 'Word Wrapper for Post Titles', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Prevents widow words in the end of post titles.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://davidwalsh.name/word-wrap-mootools-php' ),
				],
				[
					'field'       => 'widget_wordwrap',
					'title'       => _x( 'Word Wrapper for Widget Titles', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Prevents widow words in the end of widget titles.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
				'register_shortcodes',
				'editor_buttons',
			],
		];
	}

	// TODO: wordwrap headings in content / lookout for link in titles!
	// TODO: ؟! -> ?!
	// @SEE: `wp_replace_in_html_tags()`
	public function general_typography( $content )
	{
		if ( gNetwork()->option( 'linkify_hashtags', 'search' ) ) {

			$content = Text::replaceSymbols( '#', $content, function( $matched, $string ) {
				return HTML::link( str_replace( '_', ' ', $matched ), WordPress::getSearchLink( $matched ) );
			} );

			// telegram hash-tag links!
			$content = preg_replace_callback( '/<a href="\/\/search_hashtag\?hashtag=(.*?)">#(.*?)<\/a>/miu', function( $matched ) {
				return HTML::link( '#'.str_replace( '_', ' ', $matched[2] ), WordPress::getSearchLink( '#'.$matched[2] ) );
			}, $content );
		}

		if ( gNetwork()->option( 'content_replace', 'branding' ) ) {

			if ( ! $brand_name = gNetwork()->option( 'brand_name', 'branding' ) )
				$brand_name = GNETWORK_NAME;

			if ( ! $brand_url = gNetwork()->option( 'brand_url', 'branding' ) )
				$brand_url = GNETWORK_BASE;

			$content = Text::replaceWords( [ $brand_name ], $content, function( $matched ) use ( $brand_url ) {
				return '<em>'.HTML::link( $matched, $brand_url ).'</em>';
			} );
		}

		$content = str_ireplace( [
			'<p>***</p>',
			'<p><strong>***</strong></p>',
			'<p style="text-align:center">***</p>',
			'<p style="text-align:center"><strong>***</strong></p>',
		], $this->shortcode_three_asterisks(), $content );

		// FIXME: DRAFT for date: not tested!
		// @REF: http://stackoverflow.com/a/3337480
		// $content = preg_replace( "/(^| )([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4})( |$)/is", "<span class=\"date\">$2</span>", $content );

		return $content;
	}

	// FIXME: use <abbr> and full def: https://developer.mozilla.org/en/docs/Web/HTML/Element/abbr
	// رضوان ﷲ علیهما
	// رضوان‌ﷲ علیه
	// قدّس سره
	public function arabic_typography( $content )
	{
		$content = preg_replace( "/[\s\t]+(?:(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\)))/i", "$1", $content ); // clean space/tab before
		// $content = preg_replace( "/(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\))(?![^<]*>|[^<>]*<\/)/ix", '&#xfeff;'."<sup><abbr>$1</abbr></sup>", $content ); // @REF: http://stackoverflow.com/a/18622606
		$content = preg_replace( "/(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\))(?![^<]*>)/ix", '&#xfeff;'."<sup><abbr>$1</abbr></sup>", $content ); // same as above but works in html tags

		$content = preg_replace( "/\(علیهم السلام\)/i", '&#xfeff;'."<sup>(علیهم السلام)</sup>", $content );
		$content = preg_replace( "/\(علیهم‌السلام\)/i", '&#xfeff;'."<sup>(علیهم السلام)</sup>", $content );
		$content = preg_replace( "/\(علیه السلام\)/i", '&#xfeff;'."<sup>(علیه السلام)</sup>", $content );
		$content = preg_replace( "/\(علیه‌السلام\)/i", '&#xfeff;'."<sup>(علیه السلام)</sup>", $content );

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

	public function persian_typography( $content )
	{
		$content = str_ireplace( '&#8220;', '&#xAB;', $content );
		$content = str_ireplace( '&#8221;', '&#xBB;', $content );

		return $content;
	}

	public function init()
	{
		if ( $this->options['editor_buttons'] ) {

			add_action( 'gnetwork_tinymce_strings', [ $this, 'tinymce_strings' ] );

			Admin::registerTinyMCE( 'gnetworkquote', 'assets/js/tinymce/quote', 1 );
			Admin::registerTinyMCE( 'gnetworkasterisks', 'assets/js/tinymce/asterisks', 2 );
		}

		if ( $this->options['register_shortcodes'] )
			$this->shortcodes( $this->get_shortcodes() );
	}

	protected function get_shortcodes()
	{
		return [
			'three-asterisks' => 'shortcode_three_asterisks',
			'nst'             => 'shortcode_numeral_section_title',
			'ltr'             => 'shortcode_ltr',
			'pad'             => 'shortcode_pad',
			'wiki'            => 'shortcode_wiki',
			'wiki-en'         => 'shortcode_wiki',
			'wiki-fa'         => 'shortcode_wiki',
		];
	}

	public function tinymce_strings( $strings )
	{
		$new = [
			'gnetworkasterisks-title' => _x( 'Asterisks', 'TinyMCE Strings: Asterisks', GNETWORK_TEXTDOMAIN ),

			'gnetworkquote-title'    => _x( 'Quote This', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-attr'     => _x( 'Quote This', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-text'     => _x( 'Quote Text', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-cite'     => _x( 'Cite Text', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-url'      => _x( 'Cite URL', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-align'    => _x( 'Quote Align', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-epigraph' => _x( 'Epigraph', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-rev'      => _x( 'Reverse', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
		];

		return array_merge( $strings, $new );
	}

	// @SEE: https://wordpress.stackexchange.com/a/51809
	public function sanitize_title( $title, $raw_title = '', $context = 'display' )
	{
		if ( 'save' != $context )
			return $title;

		if ( seems_utf8( $raw_title ) ) {

			$new_title = trim( $raw_title );

			// remove more than one ZWNJs
			$new_title = preg_replace( "/(\x{200C})+/u", "\xE2\x80\x8C", $new_title );

			// remove arabic/persian accents
			$new_title = preg_replace( "/[\x{0618}-\x{061A}\x{064B}-\x{065F}]+/u", '', $new_title );

			$new_title = str_ireplace( [
				"\xD8\x8C", // `،` // Arabic Comma
				"\xD8\x9B", // `؛` // Arabic Semicolon
				"\xD9\x94", // `ٔ` // Arabic Hamza Above
				"\xD9\xAC", // `٬` // Arabic Thousands Separator
				"\xD8\x8D", // `؍` // Arabic Date Separator

				"\xC2\xAB",     // `«`
				"\xC2\xBB",     // `»`
				"\xE2\x80\xA6", // `…` // Horizontal Ellipsis
			], '', $new_title );

			$new_title = str_ireplace( [
				"\xE2\x80\x8C\x20", // zwnj + space
				"\x20\xE2\x80\x8C", // space + znwj
			], ' ', $new_title );

			$title = remove_accents( $new_title );
		}

		// messes with zwnj
		// $title = Text::stripPunctuation( $title );

		return $title;
	}

	public function pre_term_slug( $value, $taxonomy )
	{
		return $this->sanitize_title( $value, $value, 'save' );
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
			'title'   => _x( 'View Wikipedia page', 'Modules: Typography: Shortcode Defaults', GNETWORK_TEXTDOMAIN ),
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

		return '<span class="ltr" dir="ltr">'.do_shortcode( $content, TRUE ).'</span>';
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

			$args['space'] = isset( $atts[0] ) ? $atts[0] : 3;
			$args['class'] = isset( $atts[1] ) ? $atts[1] : 'typography-pad';
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

	public function the_title( $title )
	{
		if ( ! $title )
			return $title;

		if ( $this->options['title_titlecase'] )
			$title = Text::titleCase( $title );

		if ( $this->options['title_wordwrap'] )
			$title = Text::wordWrap( $title );

		return $title;
	}

	public function widget_title( $title )
	{
		return empty( $title ) ? $title : Text::wordWrap( $title );
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;

class Typography extends gNetwork\Module
{

	protected $key     = 'typography';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 12 );

		if ( is_admin() )
			return;

		$this->filter( 'the_content' );

		if ( $this->options['title_titlecase']
			|| $this->options['title_wordwrap'] )
				$this->filter( 'the_title' );

		if ( $this->options['arabic_typography']
			|| $this->options['persian_typography'] )
				$this->filter( 'the_content', 1, 1000, 'late' );

		add_filter( $this->hook( 'arabic' ), [ $this, 'arabic_typography' ] );
		add_filter( $this->hook( 'persian' ), [ $this, 'persian_typography' ] );
		add_filter( $this->hook(), [ $this, 'the_content_late' ] );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Typography', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'register_shortcodes' => '0',
			'editor_buttons'      => '0',
			'title_titlecase'     => '0',
			'title_wordwrap'      => '0',
			'arabic_typography'   => '0',
			'persian_typography'  => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'register_shortcodes',
					'title'       => _x( 'Extra Shortcodes', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Registers extra typography shortcodes.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'editor_buttons',
					'title'       => _x( 'Editor Buttons', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays extra typography buttons on post content editor.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'title_titlecase',
					'title'       => _x( 'Titles in Title Case', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Tries to make post titles properly-cased.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://gist.github.com/geminorum/fe2a9ba25db5cf2e5ad6718423d00f8a' ),
				],
				[
					'field'       => 'title_wordwrap',
					'title'       => _x( 'Word Wrapper for Titles', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Prevents widow words in the end of post titles.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://davidwalsh.name/word-wrap-mootools-php' ),
				],
				[
					'field'       => 'arabic_typography',
					'title'       => _x( 'Arabic Typography', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Applies Arabic typography on post contents.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'persian_typography',
					'title'       => _x( 'Persian Typography', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Applies Persian typography on post contents.', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
		];
	}

	// TODO: آیت الله العظمی // no space
	// TODO: حجت الاسلام // no space
	// TODO: ثقة الاسلام // with space
	// FIXME: use <abbr> and full def: https://developer.mozilla.org/en/docs/Web/HTML/Element/abbr
	public function arabic_typography( $content )
	{
		$content = preg_replace( "/[\s\t]+(?:(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\)))/", "$1", $content ); // clean space/tab before
		$content = preg_replace( "/(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\))(?![^<]*>|[^<>]*<\/)/ix", "<sup><abbr>$1</abbr></sup>", $content ); // @REF: http://stackoverflow.com/a/18622606/4864081

		$content = preg_replace( "/\(علیهم السلام\)/i", "<sup>(علیهم السلام)</sup>", $content );
		$content = preg_replace( "/\(علیهم‌السلام\)/i", "<sup>(علیهم السلام)</sup>", $content );
		$content = preg_replace( "/\(علیه السلام\)/i", "<sup>(علیه السلام)</sup>", $content );
		$content = preg_replace( "/\(علیه‌السلام\)/i", "<sup>(علیه السلام)</sup>", $content );

		// FIXME: DRAFT for date: not tested!
		// @REF: http://stackoverflow.com/a/3337480/4864081
		// $content = preg_replace( "/(^| )([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4})( |$)/is", "<span class=\"date\">$2</span>", $content );

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

	public function the_content( $content )
	{
		$content = str_ireplace(
			'<p style="text-align: center;">***</p>',
			$this->shortcode_three_asterisks(),
		$content );

		return $content;
	}

	public function the_content_late( $content )
	{
		if ( $this->options['arabic_typography'] )
			$content = $this->filters( 'arabic', $content );

		if ( $this->options['persian_typography'] )
			$content = $this->filters( 'persian', $content );

		return $content;
	}

	public function shortcode_wiki( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'slug'    => NULL,
			'lang'    => NULL,
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

		$url = 'https://'.$lang.'wikipedia.org/wiki/'.urlencode( str_ireplace( ' ', '_', $slug ) );

		$html = '<a href="'.esc_url( $url ).'" class="wiki wikipedia"'
				.( $args['title'] ? ' data-toggle="tooltip" title="'.esc_attr( $args['title'] ).'"' : '' )
				.'>'.trim( $content ).'</a>';

		return self::shortcodeWrap( $html, 'wikipedia', $args, FALSE );
	}

	// @SOURCE: http://writers.stackexchange.com/a/3304
	// @SOURCE: http://en.wikipedia.org/wiki/Asterisk
	public function shortcode_three_asterisks( $atts = [], $content = NULL, $tag = '' )
	{
		return self::shortcodeWrap( '&#x274b;&nbsp;&#x274b;&nbsp;&#x274b;', 'asterisks', [ 'wrap' => TRUE ] );
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
			$html .= '<span></span>';

		return HTML::tag( 'span', [
			'class' => $args['class'],
		], $html );
	}

	public function the_title( $title )
	{
		if ( $this->options['title_titlecase'] )
			$title = Text::titleCase( $title );

		if ( $this->options['title_wordwrap'] )
			$title = Text::wordWrap( $title );

		return $title;
	}
}

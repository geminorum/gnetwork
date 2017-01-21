<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Typography extends ModuleCore
{

	protected $key     = 'typography';
	protected $network = FALSE;

	protected function setup_actions()
	{
		add_action( 'init', array( $this, 'init' ), 12 );

		if ( $this->options['editor_buttons'] ) {

			add_action( 'gnetwork_tinymce_strings', array( $this, 'tinymce_strings' ) );

			Admin::registerTinyMCE( 'gnetworkquote', 'assets/js/tinymce/quote', 1 );
			Admin::registerTinyMCE( 'gnetworkasterisks', 'assets/js/tinymce/asterisks', 2 );
		}

		if ( is_admin() )
			return;

		$this->filter( 'the_content' );

		if ( $this->options['title_titlecase']
			|| $this->options['title_wordwrap'] )
				add_filter( 'the_title', array( $this, 'the_title' ) );

		if ( $this->options['arabic_typography']
			|| $this->options['persian_typography'] )
				add_filter( 'the_content', array( $this, 'the_content_late' ), 1000 );

		add_filter( $this->hook( 'arabic' ), array( $this, 'arabic_typography' ) );
		add_filter( $this->hook( 'persian' ), array( $this, 'persian_typography' ) );
		add_filter( $this->hook(), array( $this, 'the_content_late' ) );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Typography', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'editor_buttons'     => '0',
			'title_titlecase'    => '0',
			'title_wordwrap'     => '0',
			'arabic_typography'  => '0',
			'persian_typography' => '0',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'editor_buttons',
					'title'       => _x( 'Editor Buttons', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Extra Typography Buttons for Post Editor', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'title_titlecase',
					'title'       => _x( 'Titles in Title Case', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Properly-Cased Post Titles', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://gist.github.com/geminorum/fe2a9ba25db5cf2e5ad6718423d00f8a' ),
				),
				array(
					'field'       => 'title_wordwrap',
					'title'       => _x( 'Word Wrapper for Titles', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Preventing Widows in Post Titles', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://davidwalsh.name/word-wrap-mootools-php' ),
				),
				array(
					'field'       => 'arabic_typography',
					'title'       => _x( 'Arabic Typography', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Apply Arabic Typography to Post Contents', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'persian_typography',
					'title'       => _x( 'Persian Typography', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Apply Persian Typography to Post Contents', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
				),
			),
		);
	}

	// TODO: آیت الله العظمی // no space
	// TODO: حجت الاسلام // no space
	// TODO: ثقة الاسلام // with space
	// FIXME: use <abbr> and full def: https://developer.mozilla.org/en/docs/Web/HTML/Element/abbr
	public function arabic_typography( $content )
	{
		$content = preg_replace( "/[\s\t]+(?:(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\)))/", "$1", $content ); // clean space/tab before
		$content = preg_replace( "/(\(ره\)|\(س\)|\(ص\)|\(ع\)|\(عج\))(?![^<]*>|[^<>]*<\/)/ix", "<sup><abbr>$1</abbr></sup>", $content ); // @REF: http://stackoverflow.com/a/18622606/4864081

		$content = preg_replace("/\(علیهم السلام\)/i", "<sup>(علیهم السلام)</sup>", $content );
		$content = preg_replace("/\(علیهم‌السلام\)/i", "<sup>(علیهم السلام)</sup>", $content );
		$content = preg_replace("/\(علیه السلام\)/i", "<sup>(علیه السلام)</sup>", $content );
		$content = preg_replace("/\(علیه‌السلام\)/i", "<sup>(علیه السلام)</sup>", $content );

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
		$this->shortcodes( array(
			'three-asterisks' => 'shortcode_three_asterisks',
			'ltr'             => 'shortcode_ltr',
			'pad'             => 'shortcode_pad',
			'wiki'            => 'shortcode_wiki',
			'wiki-en'         => 'shortcode_wiki',
			'wiki-fa'         => 'shortcode_wiki',
		) );
	}

	public function tinymce_strings( $strings )
	{
		$new = array(
			'gnetworkasterisks-title' => _x( 'Asterisks', 'TinyMCE Strings: Asterisks', GNETWORK_TEXTDOMAIN ),

			'gnetworkquote-title' => _x( 'Quote This', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-attr'  => _x( 'Quote This', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-text'  => _x( 'Quote Text', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-cite'  => _x( 'Cite Text', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-url'   => _x( 'Cite URL', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-align' => _x( 'Quote Align', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-intro' => _x( 'Intro Quote', 'TinyMCE Strings: Quote', GNETWORK_TEXTDOMAIN ),
		);

		return array_merge( $strings, $new );
	}

	public function the_content( $content )
	{
		$content = str_ireplace(
			'<p style="text-align: center;">***</p>',
			$this->shortcode_three_asterisks( array() ),
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

	public function shortcode_wiki( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'slug'    => NULL,
			'lang'    => NULL,
			'title'   => _x( 'View Wikipedia page', 'Modules: Typography: Shortcode Defaults', GNETWORK_TEXTDOMAIN ),
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		), $atts, $tag );

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
	public function shortcode_three_asterisks( $atts, $content = NULL, $tag = '' )
	{
		return self::shortcodeWrap( '&#x274b;&nbsp;&#x274b;&nbsp;&#x274b;', 'asterisks', array( 'wrap' => TRUE ) );
	}

	// FIXME: use entities in tel short code
	public function shortcode_ltr( $atts, $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) )
			return $content;

		return '<span class="ltr" dir="ltr">'.do_shortcode( $content, TRUE ).'</span>';
	}

	public function shortcode_pad( $atts, $content = NULL, $tag = '' )
	{
		if ( isset( $atts['space'] ) ) {

			$args = shortcode_atts( array(
				'space'   => 3,
				'class'   => 'typography-pad',
				'context' => NULL,
			), $atts, $tag );

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

		return HTML::tag( 'span', array(
			'class' => $args['class'],
		), $html );
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

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

			Admin::registerTinyMCE( 'gnetworkquote', 'assets/js/tinymce.quote', 1 );
			Admin::registerTinyMCE( 'gnetworkasterisks', 'assets/js/tinymce.asterisks', 2 );
		}

		if ( $this->options['title_titlecase'] )
			add_filter( 'the_title', array( $this, 'the_title' ) );
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
			'editor_buttons'  => '0',
			'title_titlecase' => '0',
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
					'title'       => _x( 'Title to Title Case', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Properly-Cased Title of Posts', 'Modules: Typography: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( Settings::getMoreInfoIcon( 'https://gist.github.com/geminorum/fe2a9ba25db5cf2e5ad6718423d00f8a' ) ),
				),
			),
		);
	}

	public function init()
	{
		$this->shortcodes( array(
			'three-asterisks' => 'shortcode_three_asterisks',
			'ltr'             => 'shortcode_ltr',
			'wiki'            => 'shortcode_wiki',
			'wiki-en'         => 'shortcode_wiki',
			'wiki-fa'         => 'shortcode_wiki',
		) );
	}

	public function tinymce_strings( $strings )
	{
		$new = array(
			'gnetworkasterisks-title' => _x( 'Asterisks', 'Modules: TinyMCE', GNETWORK_TEXTDOMAIN ),

			'gnetworkquote-title' => _x( 'Quote This', 'Modules: TinyMCE', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-attr'  => _x( 'Quote This', 'Modules: TinyMCE', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-text'  => _x( 'Quote Text', 'Modules: TinyMCE', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-cite'  => _x( 'Cite Text', 'Modules: TinyMCE', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-url'   => _x( 'Cite URL', 'Modules: TinyMCE', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-align' => _x( 'Quote Align', 'Modules: TinyMCE', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-intro' => _x( 'Intro Quote', 'Modules: TinyMCE', GNETWORK_TEXTDOMAIN ),
		);

		return array_merge( $strings, $new );
	}

	public function shortcode_wiki( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'slug'    => NULL,
			'lang'    => NULL,
			'title'   => FALSE,
			'context' => NULL,
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
				.'>'.$content.'</a>';

		return self::shortcodeWrap( $html, 'wikipedia', $args, FALSE );
	}

	// @SOURCE: http://writers.stackexchange.com/a/3304
	// @SOURCE: http://en.wikipedia.org/wiki/Asterisk
	public function shortcode_three_asterisks( $atts, $content = NULL, $tag = '' )
	{
		return self::shortcodeWrap( '&#x274b;&nbsp;&#x274b;&nbsp;&#x274b;', 'asterisks', $atts );
	}

	// FIXME: use entities in tel short code
	public function shortcode_ltr( $atts, $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) )
			return $content;

		return '<span class="ltr" dir="ltr">'.do_shortcode( $content, TRUE ).'</span>';
	}

	public function the_title( $title )
	{
		return Text::titleCase( $title );
	}
}
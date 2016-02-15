<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkTypography extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;

	protected function setup_actions()
	{
		add_action( 'init', array( $this, 'init' ), 12 );

		if ( class_exists( 'gNetworkAdmin' ) ) {
			add_action( 'gnetwork_tinymce_strings', array( $this, 'tinymce_strings' ) );
			gNetworkAdmin::registerTinyMCE( 'gnetworkquote', 'assets/js/tinymce.quote', 1 );
			gNetworkAdmin::registerTinyMCE( 'gnetworkasterisks', 'assets/js/tinymce.asterisks', 2 );
		}
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
			'gnetworkasterisks-title' => _x( 'Asterisks', 'Typography Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),

			'gnetworkquote-title' => _x( 'Quote This', 'Typography Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-attr'  => _x( 'Quote This', 'Typography Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-text'  => _x( 'Quote Text', 'Typography Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-cite'  => _x( 'Cite Text', 'Typography Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-url'   => _x( 'Cite URL', 'Typography Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-align' => _x( 'Quote Align', 'Typography Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkquote-intro' => _x( 'Intro Quote', 'Typography Module: TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
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

		return '<span class="ltr" dir="ltr">'.$content.'</span>';
	}
}

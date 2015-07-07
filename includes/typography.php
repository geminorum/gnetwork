<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkTypography extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	protected function setup_actions()
	{
		add_action( 'init', array( &$this, 'init' ), 12 );
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

	public function shortcode_wiki( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'slug'    => NULL,
			'lang'    => NULL,
			'context' => NULL,
		), $atts, $tag );

		if ( false === $args['context'] )
			return null;

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

		return '<a href="'.esc_url( $url ).'" class="gnetwork-wiki wikipedia">'.$content.'</a>';
	}

	// http://writers.stackexchange.com/a/3304
	// http://en.wikipedia.org/wiki/Asterisk
	public function shortcode_three_asterisks( $atts, $content = NULL, $tag = '' )
	{
		return '<div class="gnetwork-wrap-shortcode shortcode-three-asterisks three-asterisks">&#x274b;&nbsp;&#x274b;&nbsp;&#x274b;</div>';
	}

	// FIXME: use entities in tel short code
	public function shortcode_ltr( $atts, $content = NULL, $tag = '' )
	{
		if ( is_null( $content ) )
			return $content;

		return '<span class="ltr" dir="ltr">'.$content.'</span>';
	}
}

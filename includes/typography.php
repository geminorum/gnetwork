<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkTypography extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = false;

	public function setup_actions()
	{
		add_action( 'init', array( & $this, 'init' ), 12 );
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

	public function shortcode_wiki( $atts, $content = null, $tag = '' )
	{
		if ( is_null( $content ) )
			return $content;

		$content = trim( $content );

		if ( 'wiki-fa' == $tag )
			$local = 'fa.';
		else if ( 'wiki-en' == $tag )
			$local = 'en.';
		else
			$local = '';

		return '<a src="http://'.$local.'wikipedia.org/wiki/'.esc_url( $content ).'" class="gnetwork-wiki wikipedia">'.$content.'</a>';
	}

	// http://writers.stackexchange.com/a/3304
	// http://en.wikipedia.org/wiki/Asterisk
	public function shortcode_three_asterisks( $atts, $content = null, $tag = '' )
	{
		return '<div class="gnetwork-wrap-shortcode shortcode-three-asterisks three-asterisks">&#10059;&nbsp;&#10059;&nbsp;&#10059;</div>';
	}

	public function shortcode_ltr( $atts, $content = null, $tag = '' )
	{
		if ( is_null( $content ) )
			return $content;

		return '<span class="ltr" dir="ltr">'.$content.'</span>';
	}
}

// http://en.wikipedia.org/wiki/Quotation_mark
// http://en.wikipedia.org/wiki/Guillemet
// http://en.wikipedia.org/wiki/Book:Typographical_symbols
// http://dictionary.reference.com/help/faq/language/g61.html

// https://wordpress.org/plugins/wp-typography/

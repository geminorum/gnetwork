<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Code extends gNetwork\Module
{
	protected $key     = 'code';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 12 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Code', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'register_shortcodes' => '0',
			'editor_buttons'      => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				'register_shortcodes',
				'editor_buttons',
			],
		];
	}

	public function init()
	{
		if ( $this->options['editor_buttons'] ) {
			add_action( 'gnetwork_tinymce_strings', [ $this, 'tinymce_strings' ] );
			Admin::registerTinyMCE( 'gnetworkprismjs', 'assets/js/tinymce/prismjs', 2 );
		}

		$this->register_shortcodes();
	}

	protected function get_shortcodes()
	{
		return [
			'github-readme' => 'shortcode_github_readme',
			'github-gist'   => 'shortcode_github_gist',
			'textarea'      => 'shortcode_textarea',
			'shields-io'    => 'shortcode_shields_io',
			'prismjs'       => 'shortcode_prismjs',
		];
	}

	public function tinymce_strings( $strings )
	{
		$new = [
			'gnetworkprismjs-title'  => _x( 'PrismJS', 'TinyMCE Strings: PrismJS', 'gnetwork' ),
			'gnetworkprismjs-window' => _x( 'PrismJS: Syntax Highlighter', 'TinyMCE Strings: PrismJS', 'gnetwork' ),
			'gnetworkprismjs-input'  => _x( 'The Code', 'TinyMCE Strings: PrismJS', 'gnetwork' ),
			'gnetworkprismjs-lang'   => _x( 'Language', 'TinyMCE Strings: PrismJS', 'gnetwork' ),
			'gnetworkprismjs-height' => _x( 'Max Height', 'TinyMCE Strings: PrismJS', 'gnetwork' ),
			'gnetworkprismjs-file'   => _x( 'File Name', 'TinyMCE Strings: PrismJS', 'gnetwork' ),
		];

		return array_merge( $strings, $new );
	}

	// TODO: use github conversion api instead of ParsedownExtra
	// @REF: https://developer.github.com/v3/
	public function shortcode_github_readme( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'repo'    => 'geminorum/gnetwork',
			'branch'  => 'master',
			'type'    => 'readme', // 'readme', 'changelog', 'markdown', 'wiki'
			'file'    => 'readme', // markdown page without .md
			'page'    => 'Home', // wiki page / default is `Home`
			'trim'    => 0,
			'class'   => 'markdown-body', // @REF: https://github.com/sindresorhus/github-markdown-css
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] || WordPress::isXML() )
			return NULL;

		$key = $this->hash( 'githubreadme', $args );

		if ( WordPress::isFlush() )
			delete_site_transient( $key );

		if ( FALSE === ( $html = get_site_transient( $key ) ) ) {

			$args['repo'] = str_replace( 'https://github.com/', '', URL::untrail( $args['repo'] ) );

			switch ( $args['type'] ) {
				case 'wiki'     : $url = 'https://raw.githubusercontent.com/wiki/'.$args['repo'].'/'.$args['page'].'.md'; break;
				case 'markdown' : $url = 'https://raw.githubusercontent.com/'.$args['repo'].'/'.$args['branch'].'/'.$args['file']; break;
				case 'changelog': $url = 'https://raw.githubusercontent.com/'.$args['repo'].'/'.$args['branch'].'/CHANGES.md'; break;
				case 'readme'   :
				default         : $url = 'https://api.github.com/repos/'.$args['repo'].'/readme'; break;
			}

			// $headers = [
			// 	'Authorization' => 'token '.$token,
			// 	'Accept'        => 'application/vnd.github.v3.raw',
			// ];

			if ( in_array( $args['type'], [ 'wiki', 'markdown', 'changelog' ] ) )
				$md = HTTP::getHTML( $url );

			else if ( $json = HTTP::getJSON( $url ) )
				$md = base64_decode( $json['content'] );

			else
				$md = FALSE;

			if ( $md ) {

				if ( $args['trim'] )
					$md = implode( "\n", array_slice( explode( "\n", $md ), intval( $args['trim'] ) ) );

				$html = Utilities::mdExtra( $md );

				if ( 'wiki' == $args['type'] )
					$html = self::convertGitHubWikiLinks( $html, $args['repo'] );

				$html = self::convertGitHubLinks( $html, $args['repo'], $args['branch'] );
				$html = Text::minifyHTML( $html );

				set_site_transient( $key, $html, GNETWORK_CACHE_TTL );

			} else {

				$html = $content;
			}
		}

		return self::shortcodeWrap( $html, 'github-readme', $args, TRUE, [ 'data-github-repo' => $args['repo'] ] );
	}

	public static function convertGitHubWikiLinks( $html, $repo )
	{
		$pattern = '/\[\[(.*?)\]\]/u';

		return preg_replace_callback( $pattern, function( $match ) use( $repo ){

			$slug = $text = $match[1];

			if ( Text::has( $text, '|' ) )
				list( $text, $slug ) = explode( '|', $text, 2 );

			$slug = preg_replace( '/\s+/', '-', $slug );

			return '<a href="https://github.com/'.$repo.'/wiki/'.rawurlencode( $slug ).'" class="-github-link -github-wikilink" data-repo="'.$repo.'" target="_blank">'.$text.'</a>';

		}, $html );
	}

	// @SOURCE: http://www.the-art-of-web.com/php/parse-links/
	public static function convertGitHubLinks( $html, $repo, $branch = 'master' )
	{
		$pattern = "/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU";

		$files = [
			'contributing.md',
			'changes.md',
			'readme.md',
			'readme.txt',
		];

		return preg_replace_callback( $pattern, function( $matches ) use( $files, $repo, $branch ){

			if ( in_array( strtolower( $matches[2] ), $files ) )
				return '<a href="https://github.com/'.$repo.'/blob/'.$branch.'/'.$matches[2].'" class="-github-link" data-repo="'.$repo.'">'.$matches[3].'</a>';

			return $matches[0];
		}, $html );
	}

	// @REF: https://github.com/bvanderhoof/gist-embed
	// @REF: http://blairvanderhoof.com/gist-embed/
	public function shortcode_github_gist( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'                => FALSE,
			'hide-line-numbers' => FALSE,
			'hide-footer'       => TRUE,
			'file'              => FALSE,
			'line'              => FALSE, // EXAMPLES: '2', '2-4', '1,3-4', '2,3,4', '1,3-5,7-'
			'highlight'         => FALSE, // EXAMPLES: '2', '2-4', '1,3-4', '2,3,4', '1,3-5,7-'
			'loading'           => FALSE,
			'context'           => NULL,
			'wrap'              => TRUE,
			'before'            => '',
			'after'             => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] || WordPress::isXML() )
			return NULL;

		if ( FALSE == $args['id'] )
			return $content;

		$html = HTML::tag( 'code', [
			'data' => [
				'gist-id'                => $args['id'],
				'gist-hide-line-numbers' => $args['hide-line-numbers'] ? 'true' : FALSE,
				'gist-hide-footer'       => $args['hide-footer'] ? 'true' : FALSE,
				'gist-file'              => $args['file'] ? $args['file'] : FALSE,
				'gist-line'              => $args['line'] ? $args['line'] : FALSE,
				'gist-highlight-line'    => $args['highlight'] ? $args['highlight'] : FALSE,
				'gist-show-loading'      => $args['loading'] ? FALSE : 'false',
			],
		], NULL );

		Scripts::pkgGistEmbed();

		return self::shortcodeWrap( $html, 'github-gist', $args, TRUE, [ 'data-github-gist' => $args['id'] ] );
	}

	public function shortcode_textarea( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'js'       => TRUE,
			'readonly' => TRUE,
			'class'    => 'large-text',
			'context'  => NULL,
			'wrap'     => TRUE,
		], $atts, $tag );

		if ( FALSE === $args['context'] || WordPress::isXML() )
			return NULL;

		if ( ! $content )
			return NULL;

		$html = HTML::tag( 'textarea', [
			'class'    => $args['class'],
			'readonly' => $args['readonly'],
			'onclick'  => $args['js'] ? 'this.focus();this.select()' : FALSE,
		], HTML::escapeTextarea( $content ) );

		unset( $args['class'] );

		return self::shortcodeWrap( $html, 'textarea', $args );
	}

	// @SEE: http://shields.io/
	public function shortcode_shields_io( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'link'      => 'https://geminorum.ir',
			'subject'   => "it's a",
			'status'    => 'geminorum project',
			'color'     => 'lightgrey',
			'style'     => 'flat-square', // 'plastic', 'flat', 'flat-square', 'social'
			'provider'  => 'https://img.shields.io/badge/',
			'extension' => 'svg',
			'context'   => NULL,
			'wrap'      => TRUE,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$char  = [ '-', '_', ' ' ];
		$with  = [ '--', '__', '_' ];
		$badge = str_ireplace( $char, $with, $args['subject'] ).'-'.str_ireplace( $char, $with, $args['status'] ).'-'.$args['color'];
		$html  = '<img class="-badge" src="'.$args['provider'].$badge.'.'.$args['extension'].'?style='.$args['style'].'" />';

		if ( $args['link'] )
			$html = HTML::tag( 'a', [
				'href'  => $args['link'],
				'title' => $args['subject'].' '.$args['status'],
			], $html );

		return self::shortcodeWrap( $html, 'shields-io', $args, FALSE );
	}

	// also works with empty content
	public function shortcode_prismjs( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'language' => 'php',
			'height'   => '',
			'filename' => '',
			'template' => '<pre data-prism="yes" class="line-numbers" data-filename="%s" style="max-height:%s"><code class="language-%s">%s</code></pre>',
			'context'  => NULL,
			'wrap'     => TRUE,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		Scripts::enqueueScriptVendor( 'prism' );
		Scripts::enqueueScript( 'front.prism' );

		if ( ! $content )
			return '<!-- prismjs enqueued -->';

		$html = sprintf( $args['template'], $args['filename'], $args['height'], $args['language'], $content );

		return self::shortcodeWrap( $html, 'prismjs', $args );
	}
}

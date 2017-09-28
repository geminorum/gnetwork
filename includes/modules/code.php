<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
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
		Admin::registerMenu( $this->key,
			_x( 'Code', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
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
				[
					'field'       => 'register_shortcodes',
					'title'       => _x( 'Extra Shortcodes', 'Modules: Code: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Registers extra coding shortcodes.', 'Modules: Code: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'editor_buttons',
					'title'       => _x( 'Editor Buttons', 'Modules: Code: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays extra coding buttons on post content editor.', 'Modules: Code: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
		];
	}

	public function init()
	{
		if ( $this->options['editor_buttons'] ) {
			add_action( 'gnetwork_tinymce_strings', [ $this, 'tinymce_strings' ] );
			Admin::registerTinyMCE( 'gnetworkprismjs', 'assets/js/tinymce/prismjs', 2 );
		}

		if ( $this->options['register_shortcodes'] )
			$this->shortcodes( $this->get_shortcodes() );
	}

	protected function get_shortcodes()
	{
		return [
			'github'        => 'shortcode_github',
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
			'gnetworkprismjs-title'  => _x( 'PrismJS', 'TinyMCE Strings: PrismJS', GNETWORK_TEXTDOMAIN ),
			'gnetworkprismjs-window' => _x( 'PrismJS: Syntax Highlighter', 'TinyMCE Strings: PrismJS', GNETWORK_TEXTDOMAIN ),
			'gnetworkprismjs-input'  => _x( 'The Code', 'TinyMCE Strings: PrismJS', GNETWORK_TEXTDOMAIN ),
			'gnetworkprismjs-lang'   => _x( 'Language', 'TinyMCE Strings: PrismJS', GNETWORK_TEXTDOMAIN ),
			'gnetworkprismjs-height' => _x( 'Max Height', 'TinyMCE Strings: PrismJS', GNETWORK_TEXTDOMAIN ),
			'gnetworkprismjs-file'   => _x( 'File Name', 'TinyMCE Strings: PrismJS', GNETWORK_TEXTDOMAIN ),
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
			'type'    => 'readme', // 'readme', 'markdown', 'wiki'
			'file'    => '/readme', // markdown page without .md
			'page'    => 'Home', // wiki page / default is `Home`
			'trim'    => 0,
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		$html = $content;
		$this->github_repo = $args['repo'];

		$key = $this->hash( 'githubreadme', $args );

		if ( WordPress::isFlush() )
			delete_site_transient( $key );

		if ( FALSE === ( $html = get_site_transient( $key ) ) ) {

			switch ( $args['type'] ) {
				case 'wiki'    : $url = 'https://raw.githubusercontent.com/wiki/'.$args['repo'].'/'.$args['page'].'.md'; break;
				case 'markdown': $url = 'https://raw.githubusercontent.com/'.$args['repo'].'/'.$args['branch'].'/'.$args['file']; break;
				case 'readme'  :
				default        : $url = 'https://api.github.com/repos/'.$args['repo'].'/readme'; break;
			}

			if ( in_array( $args['type'], [ 'wiki', 'markdown' ] ) )
				$md = HTTP::getHTML( $url );

			else if ( $json = HTTP::getJSON( $url ) )
				$md = base64_decode( $json->content );

			else
				$md = FALSE;

			if ( $md ) {

				if ( $args['trim'] )
					$md = implode( "\n", array_slice( explode( "\n", $md ), intval( $args['trim'] ) ) );

				$parsedown = new \ParsedownExtra();
				$html = $parsedown->text( $md );

				$html = self::covertGitHubLinks( $html, $args['repo'], $args['branch'] );
				$html = Text::minifyHTML( $html );

				set_site_transient( $key, $html, GNETWORK_CACHE_TTL );
			}
		}

		return '<div class="gnetwork-wrap-shortcode shortcode-github-readme" data-github-repo="'.$args['repo'].'">'.$html.'</div>';
	}

	// TODO: support wikilinks: `[[Wiki Page]]`
	// @SOURCE: http://www.the-art-of-web.com/php/parse-links/
	public static function covertGitHubLinks( $html, $repo, $branch = 'master' )
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

	// @REF: https://github.com/JoelSutherland/GitHub-jQuery-Repo-Widget
	public function shortcode_github( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'repo'    => 'geminorum/gnetwork',
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		Utilities::enqueueScriptVendor( 'jquery.githubRepoWidget', [ 'jquery' ], '20150102' );

		return '<div class="gnetwork-wrap-shortcode shortcode-github github-widget" data-repo="'.$args['repo'].'"></div>';
	}

	// @SOURCE: https://github.com/blairvanderhoof/gist-embed
	// @SOURCE: http://blairvanderhoof.com/gist-embed/
	public function shortcode_github_gist( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'                => FALSE,
			'hide-line-numbers' => FALSE,
			'hide-footer'       => TRUE,
			'file'              => FALSE,
			'line'              => FALSE, // EXAMPLES: '2', '2-4', '1,3-4', '2,3,4'
			'highlight'         => FALSE, // EXAMPLES: '2', '2-4', '1,3-4', '2,3,4'
			'loading'           => FALSE,
			'context'           => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
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

		Utilities::enqueueScriptVendor( 'jquery.gist-embed', [ 'jquery' ], '2.6' );

		return '<div class="gnetwork-wrap-shortcode shortcode-github-gist" data-github-gist="'.$args['id'].'">'.$html.'</div>';
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

		if ( FALSE === $args['context'] || is_feed() )
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
			'link'      => 'http://geminorum.ir',
			'subject'   => "it's a",
			'status'    => 'geminorum project',
			'color'     => 'lightgrey',
			'style'     => 'flat-square', // 'plastic', 'flat', 'flat-square', 'social'
			'provider'  => 'http://img.shields.io/badge/',
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

		Utilities::enqueueScriptVendor( 'prism' );
		Utilities::enqueueScript( 'front.prism' );

		if ( ! $content )
			return '<!-- prismjs enqueued -->';

		$html = sprintf( $args['template'], $args['filename'], $args['height'], $args['language'], $content );

		return self::shortcodeWrap( $html, 'prismjs', $args );
	}
}

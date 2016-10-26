<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Code extends ModuleCore
{
	protected $key     = 'code';
	protected $network = FALSE;

	protected function setup_actions()
	{
		add_action( 'init', array( $this, 'init' ), 12 );
	}

	public function init()
	{
		$this->shortcodes( array(
			'github'        => 'shortcode_github',
			'github-readme' => 'shortcode_github_readme',
			'github-gist'   => 'shortcode_github_gist',
			'textarea'      => 'shortcode_textarea',
			'shields-io'    => 'shortcode_shields_io',
		) );

		// FIXME: NOT WORKING: gist id is now diffrent from this pattern
		// FIXME: add option to enable this
		// add_filter( 'the_content', array( $this, 'the_content_gist_shortcode' ), 9 );
	}

	// Originally based on : GitHub README v0.1.0
	// by Jason Stallings : http://jason.stallin.gs
	// https://github.com/octalmage/github-readme
	// https://wordpress.org/plugins/github-readme/
	// @REF: [GitHub API v3 | GitHub Developer Guide](https://developer.github.com/v3/)
	public function shortcode_github_readme( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'repo'    => 'geminorum/gnetwork',
			'trim'    => 0,
			'type'    => 'readme', // 'readme', 'markdown', 'wiki'
			'file'    => '/readme', // markdown page
			'branch'  => 'master', // markdown branch
			'page'    => '', // wiki page
			'context' => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		$html = $content;
		$this->github_repo = $args['repo'];

		$key = 'gnetwork_code_githubreadme_'.$args['repo'].'_'.$args['type'].'_'.$args['trim'];

		if ( WordPress::isFlush() )
			delete_site_transient( $key );

		if ( FALSE === ( $html = get_site_transient( $key ) ) ) {

			switch ( $args['type'] ) {
				default :
				case 'readme'   : $url = 'https://api.github.com/repos/'.$args['repo'].'/readme'; break;
				case 'markdown' : $url = 'https://raw.githubusercontent.com/'.$args['repo'].'/'.$args['branch'].'/'.$args['file']; break;
				case 'wiki'     : $url = 'https://raw.githubusercontent.com/wiki/'.$args['repo'].'/'.$args['page'].'.md'; break;
			}

			if ( $json = HTTP::getJSON( $url ) ) {

				$md = base64_decode( $json->content );
				if ( $args['trim'] )
					$md = implode( "\n", array_slice( explode( "\n", $md ), intval( $args['trim'] ) ) );

				// FIXME: use github conversion api instead of ParsedownExtra

				$parsedown = new \ParsedownExtra();
				$html = $parsedown->text( $md );

				// @SOURCE: http://www.the-art-of-web.com/php/parse-links/
				$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
				$html = preg_replace_callback( "/$regexp/siU", array( $this, 'github_readme_link_cb' ), $html );

				set_site_transient( $key, $html , 12 * HOUR_IN_SECONDS );
			}
		}

		if ( WordPress::isDev() )
			delete_site_transient( $key );

		return '<div class="gnetwork-wrap-shortcode shortcode-github-readme" data-github-repo="'.$args['repo'].'">'.$html.'</div>';
	}

	public function github_readme_link_cb( $matchs )
	{
		$files =  array(
			'contributing.md',
			'changes.md',
			'readme.md',
			'readme.txt',
		);

		if ( in_array( strtolower( $matchs['2'] ), $files ) )
			return '<a href="https://github.com/'.$this->github_repo.'/blob/master/'.$matchs[2].'">'.$matchs[3].'</a>';

		return $matchs[0];
	}

	// @REF: https://github.com/JoelSutherland/GitHub-jQuery-Repo-Widget
	public function shortcode_github( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'repo'    => 'geminorum/gnetwork',
			'context' => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		Utilities::enqueueScriptVendor( 'jquery.githubRepoWidget', array( 'jquery' ), '20150102' );

		return '<div class="gnetwork-wrap-shortcode shortcode-github github-widget" data-repo="'.$args['repo'].'"></div>';
	}

	// https://github.com/blairvanderhoof/gist-embed
	// http://blairvanderhoof.com/gist-embed/
	public function shortcode_github_gist( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'                => FALSE,
			'hide-line-numbers' => FALSE,
			'hide-footer'       => TRUE,
			'file'              => FALSE,
			'line'              => FALSE, // EXAMPLES: '2', '2-4', '1,3-4', '2,3,4'
			'highlight'         => FALSE, // EXAMPLES: '2', '2-4', '1,3-4', '2,3,4'
			'loading'           => FALSE,
			'context'           => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( FALSE == $args['id'] )
			return $content;

		$html = HTML::tag( 'code', array(
			'data' => array(
				'gist-id'                => $args['id'],
				'gist-hide-line-numbers' => $args['hide-line-numbers'] ? 'true' : FALSE,
				'gist-hide-footer'       => $args['hide-footer'] ? 'true' : FALSE,
				'gist-file'              => $args['file'] ? $args['file'] : FALSE,
				'gist-line'              => $args['line'] ? $args['line'] : FALSE,
				'gist-highlight-line'    => $args['highlight'] ? $args['highlight'] : FALSE,
				'gist-show-loading'      => $args['loading'] ? FALSE : 'false',
			),
		), NULL );

		Utilities::enqueueScriptVendor( 'jquery.gist-embed' );

		return '<div class="gnetwork-wrap-shortcode shortcode-github-gist" data-github-gist="'.$args['id'].'">'.$html.'</div>';
	}

	// autoreplace gist links to shortcodes
	// [Detect Gists and Embed Them | CSS-Tricks](https://css-tricks.com/snippets/wordpress/detect-gists-and-embed-them/)
	public function the_content_gist_shortcode( $content )
	{
		return preg_replace( '/https:\/\/gist.github.com\/([\d]+)[\.js\?]*[\#]*file[=|_]+([\w\.]+)(?![^<]*<\/a>)/i', '', $content );
	}

	public function shortcode_textarea( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'js'       => TRUE,
			'readonly' => TRUE,
			'class'    => 'large-text',
			'context'  => NULL,
			'wrap'     => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( ! $content )
			return NULL;

		$html = HTML::tag( 'textarea', array(
			'class'    => $args['class'],
			'readonly' => $args['readonly'],
			'onclick'  => $args['js'] ? 'this.focus();this.select()' : FALSE,
		), HTML::escapeTextarea( $content ) );

		return self::shortcodeWrap( $html, 'textarea', $args );
	}

	// @SEE: http://shields.io/
	public function shortcode_shields_io( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'link'      => 'http://geminorum.ir',
			'subject'   => "it's a",
			'status'    => 'geminorum project',
			'color'     => 'lightgrey',
			'style'     => 'flat-square', // 'plastic', 'flat', 'flat-square', 'social'
			'provider'  => 'http://img.shields.io/badge/',
			'extension' => 'svg',
			'context'   => NULL,
			'wrap'      => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$char  = array( '-', '_', ' ' );
		$with  = array( '--', '__', '_' );
		$badge = str_ireplace( $char, $with, $args['subject'] ).'-'.str_ireplace( $char, $with, $args['status'] ).'-'.$args['color'];
		$html  = '<img class="-badge" src="'.$args['provider'].$badge.'.'.$args['extension'].'?style='.$args['style'].'" />';

		if ( $args['link'] )
			$html = HTML::tag( 'a', array(
				'href'  => $args['link'],
				'title' => $args['subject'].' '.$args['status'],
			), $html );

		return self::shortcodeWrap( $html, 'shields-io', $args, FALSE );
	}
}

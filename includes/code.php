<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCode extends gNetworkModuleCore
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
			'github' => 'shortcode_github',
			'github-readme' => 'shortcode_github_readme',
		) );
	}

	// Originally based on : GitHub README v0.0.3
	// by Jason Stallings : http://jason.stallin.gs
	// https://github.com/octalmage/github-readme
	// https://wordpress.org/plugins/github-readme/
	public function shortcode_github_readme( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'repo' => 'geminorum/gnetwork',
			'trim' => 0,
		), $atts, $tag );

		$html = $content;
		$this->github_repo = $args['repo'];

		$key = 'gnetwork_code_githubreadme_'.$args['repo'].'_'.$args['trim'];


		if ( gNetworkUtilities::isFlush() )
			delete_site_transient( $key );

		if ( false === ( $html = get_site_transient( $key ) ) ) {

			if ( $json = self::getJSON( 'https://api.github.com/repos/'.$args['repo'].'/readme' ) ) {

				$md = base64_decode( $json->content );
				if ( $args['trim'] )
					$md = implode( "\n", array_slice( explode( "\n", $md ), intval( $args['trim'] ) ) );

				// TODO: must use : http://parsedown.org/demo

				if ( ! class_exists( 'MarkdownExtra' ) )
				//if ( ! class_exists( 'Markdown' ) )
					//require_once( GNETWORK_DIR.'assets/libs/php-markdown/Michelf/Markdown.inc.php' );
					require_once( GNETWORK_DIR.'assets/libs/php-markdown/Michelf/MarkdownExtra.inc.php' );

				//defined( 'GPERSIANDATE_SKIP' ) or define( 'GPERSIANDATE_SKIP', true );

				//$html = \Michelf\Markdown::defaultTransform( $md );
				$html = \Michelf\MarkdownExtra::defaultTransform( $md );

				// http://www.the-art-of-web.com/php/parse-links/
				$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
				$html = preg_replace_callback( "/$regexp/siU", array( $this, 'github_readme_link_cb' ), $html );

				set_site_transient( $key, $html , 12 * HOUR_IN_SECONDS );
			}
		}

		if ( gNetworkUtilities::isDev() )
			delete_site_transient( $key );

		return '<div class="gnetwork-wrap gnetwork-github-readme" data-github-repo="'.$args['repo'].'">'.$html.'</div>';
	}

	public function github_readme_link_cb( $matchs )
	{
		if ( in_array( strtolower( $matchs['2'] ), array(
			'changes.md',
			'readme.md',
			'readme.txt',
		) ) )
			return '<a href="https://github.com/'.$this->github_repo.'/blob/master/'.$matchs[2].'">'.$matchs[3].'</a>';

		return $matchs[0];
	}

	// Originally based on : GitHub Shortcode v0.1
	// by Jason Stallings
	// http://json.sx/projects/github-shortcode/
	// https://wordpress.org/plugins/github-shortcode/
	public function shortcode_github( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'repo' => 'geminorum/gnetwork',
		), $atts, $tag );

		wp_enqueue_script( 'gnetwork-github-repowidget', GNETWORK_URL.'/assets/js/jquery.github-repowidget.min.js', array( 'jquery' ), '20150130', true );
		return "<div class=\"github-widget\" data-repo=\"".$args['repo']."\"></div>";
	}
}

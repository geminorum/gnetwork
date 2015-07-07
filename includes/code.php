<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCode extends gNetworkModuleCore
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
			'github'        => 'shortcode_github',
			'github-readme' => 'shortcode_github_readme',
			'github-gist'   => 'shortcode_github_gist',
		) );

		// NOT WORKING: gist id is now diffrent from this pattern
		// FIXME: add option to enable this
		// add_filter( 'the_content', array( &$this, 'the_content_gist_shortcode' ), 9 );
	}

	// Originally based on : GitHub README v0.1.0
	// by Jason Stallings : http://jason.stallin.gs
	// https://github.com/octalmage/github-readme
	// https://wordpress.org/plugins/github-readme/
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

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $content;
		$this->github_repo = $args['repo'];

		$key = 'gnetwork_code_githubreadme_'.$args['repo'].'_'.$args['type'].'_'.$args['trim'];

		if ( gNetworkUtilities::isFlush() )
			delete_site_transient( $key );

		if ( FALSE === ( $html = get_site_transient( $key ) ) ) {

			switch ( $args['type'] ) {
				default :
				case 'readme'   : $url = 'https://api.github.com/repos/'.$args['repo'].'/readme'; break;
				case 'markdown' : $url = 'https://raw.githubusercontent.com/'.$args['repo'].'/'.$args['branch'].'/'.$args['file']; break;
				case 'wiki'     : $url = 'https://raw.githubusercontent.com/wiki/'.$args['repo'].'/'.$args['page'].'.md'; break;
			}

			if ( $json = self::getJSON( $url ) ) {

				$md = base64_decode( $json->content );
				if ( $args['trim'] )
					$md = implode( "\n", array_slice( explode( "\n", $md ), intval( $args['trim'] ) ) );

				// TODO: must use : http://parsedown.org/demo

				// if ( ! class_exists( 'Markdown' ) )
				// 	require_once( GNETWORK_DIR.'assets/libs/php-markdown/Michelf/Markdown.inc.php' );

				if ( ! class_exists( 'MarkdownExtra' ) )
					require_once( GNETWORK_DIR.'assets/libs/php-markdown/Michelf/MarkdownExtra.inc.php' );

				// __gpersiandate_skip();

				// $html = \Michelf\Markdown::defaultTransform( $md );
				$html = \Michelf\MarkdownExtra::defaultTransform( $md );

				// http://www.the-art-of-web.com/php/parse-links/
				$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
				$html = preg_replace_callback( "/$regexp/siU", array( $this, 'github_readme_link_cb' ), $html );

				set_site_transient( $key, $html , 12 * HOUR_IN_SECONDS );
			}
		}

		if ( gNetworkUtilities::isDev() )
			delete_site_transient( $key );

		return '<div class="gnetwork-wrap-shortcode github-readme" data-github-repo="'.$args['repo'].'">'.$html.'</div>';
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

	// Originally based on : GitHub Shortcode v0.1
	// by Jason Stallings
	// http://json.sx/projects/github-shortcode/
	// https://wordpress.org/plugins/github-shortcode/
	public function shortcode_github( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'repo'    => 'geminorum/gnetwork',
			'context' => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		wp_enqueue_script( 'gnetwork-code-githubrepowidget', GNETWORK_URL.'assets/js/jquery.github-repowidget.min.js', array( 'jquery' ), '20150130', TRUE );

		return '<div class="gnetwork-wrap-shortcode github" data-github-repo="'.$args['repo'].'"></div>';
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

		if ( FALSE === $args['context'] )
			return NULL;

		if ( FALSE == $args['id'] )
			return $content;

		$html = gNetworkUtilities::html( 'code', array(
			'data-gist-id'                => $args['id'],
			'data-gist-hide-line-numbers' => $args['hide-line-numbers'] ? 'true' : FALSE,
			'data-gist-hide-footer'       => $args['hide-footer'] ? 'true' : FALSE,
			'data-gist-file'              => $args['file'] ? $args['file'] : FALSE,
			'data-gist-line'              => $args['line'] ? $args['line'] : FALSE,
			'data-gist-highlight-line'    => $args['highlight'] ? $args['highlight'] : FALSE,
			'data-gist-show-loading'      => $args['loading'] ? FALSE : 'false',
		), NULL );

		wp_enqueue_script( 'gnetwork-code-gistembed', GNETWORK_URL.'assets/js/jquery.gist-embed.min.js', array( 'jquery' ), '2.1', TRUE );

		return '<div class="gnetwork-wrap-shortcode github-gist" data-github-gist="'.$args['id'].'">'.$html.'</div>';
	}

	// ORIGINAL METHOD
	// https://css-tricks.com/snippets/wordpress/detect-gists-and-embed-them/
	public function shortcode_github_gist_OLD( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'      => FALSE,
			'file'    => FALSE,
			'context' => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( FALSE == $args['id'] )
			return $content;

		$html = sprintf( '<script src="https://gist.github.com/%s.js%s"></script>',
			$args['id'],
			$args['file'] ? '?file='.$args['file'] : ''
		);

		return '<div class="gnetwork-wrap-shortcode github-gist" data-github-gist="'.$args['id'].'">'.$html.'</div>';
	}

	// autoreplace gist links to shortcodes
	public function the_content_gist_shortcode( $content )
	{
		return preg_replace( '/https:\/\/gist.github.com\/([\d]+)[\.js\?]*[\#]*file[=|_]+([\w\.]+)(?![^<]*<\/a>)/i', '', $content );
	}
}

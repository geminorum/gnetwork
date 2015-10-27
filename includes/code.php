<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCode extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;

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
			'github-repo'   => 'shortcode_github_repo',
		) );

		// FIXME: NOT WORKING: gist id is now diffrent from this pattern
		// FIXME: add option to enable this
		// add_filter( 'the_content', array( $this, 'the_content_gist_shortcode' ), 9 );
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

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		$html = $content;
		$this->github_repo = $args['repo'];

		$key = 'gnetwork_code_githubreadme_'.$args['repo'].'_'.$args['type'].'_'.$args['trim'];

		if ( self::isFlush() )
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

				$parsedown = new ParsedownExtra();
				$html = $parsedown->text( $md );

				// @SOURCE: http://www.the-art-of-web.com/php/parse-links/
				$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
				$html = preg_replace_callback( "/$regexp/siU", array( $this, 'github_readme_link_cb' ), $html );

				set_site_transient( $key, $html , 12 * HOUR_IN_SECONDS );
			}
		}

		if ( self::isDev() )
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

		if ( FALSE === $args['context'] || is_feed() )
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

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( FALSE == $args['id'] )
			return $content;

		$html = self::html( 'code', array(
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

		wp_enqueue_script( 'gnetwork-code-gistembed', GNETWORK_URL.'assets/js/jquery.gist-embed.min.js', array( 'jquery' ), '2.1', TRUE );

		return '<div class="gnetwork-wrap-shortcode github-gist" data-github-gist="'.$args['id'].'">'.$html.'</div>';
	}

	// autoreplace gist links to shortcodes
	// [Detect Gists and Embed Them | CSS-Tricks](https://css-tricks.com/snippets/wordpress/detect-gists-and-embed-them/)
	public function the_content_gist_shortcode( $content )
	{
		return preg_replace( '/https:\/\/gist.github.com\/([\d]+)[\.js\?]*[\#]*file[=|_]+([\w\.]+)(?![^<]*<\/a>)/i', '', $content );
	}

	// ALSO SEE: https://github.com/bradthomas127/gitpress-repo
	// LIB REPO: https://github.com/darcyclarke/Repo.js
	public function shortcode_github_repo( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'username' => 'geminorum',
			'name'     => 'gnetwork',
			'branch'   => FALSE,
			'context'  => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		$key = 'github-repo-'.( count( $this->scripts ) + 1 );
		$this->scripts[$key] = "$('#".$key."').repo({user:'".$args['username']."',name:'".$args['name']."'".( $args['branch'] ? ", branch:'".$args['branch']."'" : "" )."});";

		self::wrapJS( $this->scripts[$key], FALSE );
		wp_enqueue_script( 'repo-js', GNETWORK_URL.'assets/libs/repo.js/repo.min.js', array( 'jquery' ), GNETWORK_VERSION, TRUE );

		return '<div id="'.$key.'" class="gnetwork-wrap-shortcode github-repo"></div>';
	}
}

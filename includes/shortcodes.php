<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkShortCodes extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	var $_flash_ids  = array();
	var $_pdf_ids    = array();
	var $_ref_ids    = array();
	var $_ref_list   = FALSE;

	protected function setup_actions()
	{
		add_action( 'init', array( &$this, 'init_early' ), 8 );
		add_action( 'init', array( &$this, 'init_late' ), 12 );
		add_action( 'gnetwork_tinymce_strings', array( &$this, 'tinymce_strings' ) );
	}

	// fallback shortcodes
	public function init_early()
	{
		add_shortcode( 'book', array( &$this, 'shortcode_return_content' ) );
	}

	public function shortcode_return_content( $atts, $content = null, $tag = '' )
	{
		return $content;
	}

	public function init_late()
	{
		$this->shortcodes( array(
			// 'accordion'    => 'shortcode_accordion',
			// 'github-repo'  => 'shortcode_github_repo',
			'children'     => 'shortcode_children',
			'siblings'     => 'shortcode_siblings',
			'back'         => 'shortcode_back',
			'iframe'       => 'shortcode_iframe',
			'email'        => 'shortcode_email',
			'tel'          => 'shortcode_tel',
			'googlegroups' => 'shortcode_googlegroups',
			'pdf'          => 'shortcode_pdf',
			'bloginfo'     => 'shortcode_bloginfo',
			'audio'        => 'shortcode_audio',
			'flash'        => 'shortcode_flash',
			'ref'          => 'shortcode_ref',
			'reflist'      => 'shortcode_reflist',
			'ref-m'        => 'shortcode_ref_manual',
			'reflist-m'    => 'shortcode_reflist_manual',
			// 'repo-video'   => 'shortcode_repo_video',
		) );

		if ( ! defined( 'GNETWORK_DISABLE_REFLIST_INSERT' ) || ! GNETWORK_DISABLE_REFLIST_INSERT )
			add_filter( 'the_content', array( &$this, 'the_content' ), 20 );

		add_action( 'wp_footer', array( &$this, 'wp_footer' ), 20 );

		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( &$this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( &$this, 'mce_buttons' ) );
		}
	}

	// http://www.paulund.co.uk/get-list-of-all-available-shortcodes
	public static function available()
	{
		global $shortcode_tags;

		echo '<ul>';
		foreach ( $shortcode_tags as $code => $callback )
			echo '<li><code>['.$code.']</code></li>';
		echo '</ul>';
	}

	public function tinymce_strings( $strings )
	{
		$new = array(
			'gnetworkcite-title'     => _x( 'Cite This', 'TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkcite-url'       => _x( 'URL', 'TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkgemail-title'   => _x( 'Email', 'TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkgemail-subject' => _x( 'Subject', 'TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkgpeople-title'  => _x( 'People', 'TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
			'gnetworkgpeople-name'   => _x( 'Name', 'TINYMCE Strings', GNETWORK_TEXTDOMAIN ),
		);

		return array_merge( $strings, $new );
	}

	// http://stephanieleary.com/2010/06/listing-child-pages-with-a-shortcode/
	public function shortcode_children( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'      => get_queried_object_id(),
			'type'    => 'page',
			'context' => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! is_singular( $args['type'] ) )
			return $content;

		$children = wp_list_pages( array(
			'child_of'    => $args['id'],
			'post_type'   => $args['type'],
			'echo'        => FALSE,
			'depth'       => 1,
			'title_li'    => '',
			'sort_column' => 'menu_order, post_title',
		) );

		if ( ! $children )
			return $content;

		return '<div class="gnetwork-wrap-shortcode shortcode-children"><ul>'.$children.'</ul></div>';
	}

	public function shortcode_siblings( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'parent'  => null,
			'type'    => 'page',
			'ex'      => null,
			'context' => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! is_singular( $args['type'] ) )
			return $content;

		if ( is_null( $args['parent'] ) ) {
			$object = get_queried_object();
			if ( $object && isset( $object->post_parent ) )
				$args['parent'] = $object->post_parent;
		}

		if ( ! $args['parent'] )
			return $content;

		if ( is_null( $args['ex'] ) )
			$args['ex'] = get_queried_object_id();

		$siblings = wp_list_pages( array(
			'child_of'    => $args['parent'],
			'post_type'   => $args['type'],
			'exclude'     => $args['ex'],
			'echo'        => false,
			'depth'       => 1,
			'title_li'    => '',
			'sort_column' => 'menu_order, post_title',
		) );

		if ( ! $siblings )
			return $content;

		return '<div class="gnetwork-wrap-shortcode shortcode-siblings"><ul>'.$siblings.'</ul></div>';
	}

	// TODO: finish cases
	public function shortcode_back( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'   => get_queried_object_id(),
			'to'   => 'parent',
			'html' => _x( 'Back', 'Shortcodes: back: default html', GNETWORK_TEXTDOMAIN ),
			'context' => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! $args['to'] )
			return $content;

		$html = FALSE;

		switch ( $args['to'] ) {

			case 'parent' :

				$post = get_post( $args['id'] );
				if ( $post ) {
					if ( $post->post_parent ) {
						$html = gNetworkUtilities::html( 'a', array(
							'href'  => get_permalink( $post->post_parent ),
							'title' => get_the_title( $post->post_parent ),
							'class' => 'parent',
							'rel'   => 'parent',
						), $args['html'] );
					} else {
						$html = gNetworkUtilities::html( 'a', array(
							'href'  => home_url( '/' ),
							'title' => _x( 'Home', 'Shortcodes: back: home title attr', GNETWORK_TEXTDOMAIN ),
							'class' => 'home',
							'rel'   => 'home',
						), $args['html'] );
					}
				}

			break;

			case 'home' :

				$html = gNetworkUtilities::html( 'a', array(
					'href'  => home_url( '/' ),
					'title' => _x( 'Home', 'Shortcodes: back: home title attr', GNETWORK_TEXTDOMAIN ),
					'class' => 'home',
					'rel'   => 'home',
				), $args['html'] );

			break;

			case 'grand-parent' :

			break;

		}

		if ( $html )
			return '<div class="gnetwork-wrap-shortcode shortcode-back">'.$html.'</div>';

		return $content;
	}

	public function shortcode_iframe( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'url'     => FALSE,
			'width'   => '100%',
			'height'  => '520',
			'scroll'  => 'auto',
			'style'   => 'width:100%!important;',
			'context' => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! $args['url'] )
			return null;

		if ( ! in_array( $args['scroll'], array( 'auto', 'yes', 'no' ) ) )
			$args['scroll'] = 'no';

		$html = gNetworkUtilities::html( 'iframe', array(
			'frameborder' => '0',
			'src'         => $args['url'],
			'style'       => $args['style'],
			'scrolling'   => $args['scroll'],
			'height'      => $args['height'],
			'width'       => $args['width'],
		), null );

		return '<div class="gnetwork-wrap-shortcode shortcode-iframe">'.$html.'</div>';
	}

	// DRAFT
	public function shortcode_accordion( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'title_wrap' => 'h3',
			'context'    => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		// http://www.jacklmoore.com/notes/jquery-accordion-tutorial/
		// http://www.jacklmoore.com/demo/accordion.html

		// http://tympanus.net/Blueprints/NestedAccordion/
		// http://tympanus.net/codrops/2013/03/29/nested-accordion/

	}

	// [email]you@you.com[/email]
	// http://bavotasan.com/2012/shortcode-to-encode-email-in-wordpress-posts/
	// http://www.cubetoon.com/2008/how-to-enter-line-break-into-mailto-body-command/
	// https://css-tricks.com/snippets/html/mailto-links/
	public function shortcode_email( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'subject' => FALSE,
			'title'   => FALSE,
			'context' => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! $content ) // what about default site email
			return $content;

		$html = '<a class="email" href="'.antispambot( "mailto:".$content.( $args['subject'] ? '?subject='.urlencode( $args['subject'] ) : '' ) )
				.'"'.( $args['title'] ? ' title="'.esc_attr( $args['title'] ).'"' : '' ).'>'
				.antispambot( $content ).'</a>';

		return '<span class="gnetwork-wrap-shortcode shortcode-email">'.$html.'</span>';
	}

	// http://stackoverflow.com/a/13662220
	// http://code.tutsplus.com/tutorials/mobile-web-quick-tip-phone-number-links--mobile-7667
	public function shortcode_tel( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'title'   => FALSE,
			'context' => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! $content ) // what about default site email
			return $content;

		$html = '<a class="tel" href="tel://'.$content
				.'"'.( $args['title'] ? ' title="'.esc_attr( $args['title'] ).'"' : '' ).'>'
				.'&#8206;'.apply_filters( 'string_format_i18n', $content ).'&#8207;</a>';

		return '<span class="gnetwork-wrap-shortcode shortcode-tel">'.$html.'</span>';
	}

	public function shortcode_googlegroups( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'title_wrap' => 'h3',
			'id'         => constant( 'GNETWORK_GOOGLE_GROUP_ID' ),
			'logo'       => 'color',
			'logo_style' => 'border:none;box-shadow:none;',
			'hl'         => constant( 'GNETWORK_GOOGLE_GROUP_HL' ),
			'context'    => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( FALSE == $args['id'] )
			return null;

		// form from : http://socket.io/
		$html = '<form action="http://groups.google.com/group/'.$args['id'].'/boxsubscribe?hl='.$args['hl'].'" id="google-subscribe">';
		$html .= '<a href="http://groups.google.com/group/'.$args['id'].'?hl='.$args['hl'].'"><img src="'.GNETWORK_URL.'assets/images/google_groups_'.$args['logo'].'.png" style="'.$args['logo_style'].'" alt="Google Groups"></a>';
		// <span id="google-members-count">(4889 members)</span>
		$html .= '<div id="google-subscribe-input">'._x( 'Email:', 'google groups subscribe', GNETWORK_TEXTDOMAIN );
		$html .= ' <input type="text" name="email" id="google-subscribe-email" data-cip-id="google-subscribe-email" />';
		$html .= ' <input type="hidden" name="hl" value="'.$args['hl'].'" />';
		$html .= ' <input type="submit" name="go" value="'._x( 'Subscribe', 'google groups subscribe', GNETWORK_TEXTDOMAIN ).'" /></div></form>';

		return $html;
	}

	var $_github_repos = array();

	// TODO: must move to code module
	// ALSO SEE: https://github.com/bradthomas127/gitpress-repo
	// LIB REPO: https://github.com/darcyclarke/Repo.js
	public function shortcode_github_repo( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'username' => FALSE,
			'name'     => FALSE,
			'branch'   => FALSE,
			'context'  => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( $args['username'] && $args['name'] ) {
			$key = 'repo_'.( count( $this->_github_repos ) + 1 );
			$this->_github_repos[$key] = "$('#".$key."').repo({user:'".$args['username']."',name:'".$args['name']."'".( $args['username'] ? ", branch:'".$key."'" : "" )."});";
			wp_enqueue_script( 'repo-js', GNETWORK_URL.'assets/libs/repo.js/repo.min.js', array( 'jquery' ), GNETWORK_VERSION, true );
			return '<div id="'.$key.'" class="gnetwork-github"></div>';
		}

		return $content;
	}

	// http://pdfobject.com
	public function shortcode_pdf( $atts, $content = null, $tag = '' )
	{
		// TODO : get the standard PDF dimensions for A4

		$args = shortcode_atts( array(
			'url'       => FALSE, // comma seperated multiple url to show multiple pdf // UNFINISHED
			'width'     => '100%', // '840px',
			'height'    => '960px',
			'rand'      => FALSE, // if multiple url then use random
			'navpanes'  => '1',
			'statusbar' => '0',
			'view'      => 'FitH',
			'pagemode'  => 'thumbs',
			'rtl'       => ( is_rtl() ? 'yes' : 'no' ),
			'download'  => FALSE,
			'context'   => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! $args['url'] )
			return null;

		if ( $args['rand'] && FALSE !== strpos( $args['url'], ',' ) ) {
			$url = explode( ',', $args['url'] );
			$key = rand( 0, ( count( $url ) - 1 ) );
			$args['url'] = $url[$key];
		}

		$fallback = apply_filters( 'gnetwork_shortcode_pdf_fallback', sprintf( __( 'It appears you don\'t have Adobe Reader or PDF support in this web browser. <a href="%s">Click here to download the PDF</a>', GNETWORK_TEXTDOMAIN ), $args['url'] ) );

		$key = count( $this->_pdf_ids ) + 1;
		$id = 'gNetworkPDF'.$key;

		// https://github.com/pipwerks/PDFObject
		$this->_pdf_ids[$key] = ' var '.$id.' = new PDFObject({url:"'.$args['url']
			.'",id:"'.$id
			.'",width:"'.$args['width']
			.'",height:"'.$args['height']
			.'",pdfOpenParams:{navpanes:'.$args['navpanes']
				.',statusbar:'.$args['statusbar']
				.',view:"'.$args['view']
				.'",pagemode:"'.$args['pagemode']
			.'"}}).embed("'.$id.'div"); ';

		// $this->_pdf_ids[$key] = ' var '.$id.' = new PDFObject({url:"'.$args['url'].'",id:"'.$id.'",pdfOpenParams:{navpanes:'.$args['navpanes'].',statusbar:'.$args['statusbar'].',view:"'.$args['view'].'",pagemode:"'.$args['pagemode'].'"}}).embed("'.$id.'div"); ';

		wp_enqueue_script( 'pdfobject', GNETWORK_URL.'assets/js/lib.pdfobject.min.js', array(), GNETWORK_VERSION, true );
		return '<div id="'.$id.'div">'.$fallback.'</div>';
	}

	// Bloginfo Shortcode
	// http://css-tricks.com/snippets/wordpress/bloginfo-shortcode/
	// http://codex.wordpress.org/Template_Tags/bloginfo
	//
	// [bloginfo key='name']
	// <img src="[bloginfo key='template_url']/images/logo.jpg" alt="[bloginfo key='name'] logo" />
	public function shortcode_bloginfo( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'key'     => '',
			'wrap'    => FALSE,
			'class'   => 'blog-info blog-info-%s',
			'context' => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

	   return get_bloginfo( $args['key'] );
	}

	// http://wordpress.org/extend/plugins/kimili-flash-embed/other_notes/
	// http://yoast.com/articles/valid-flash-embedding/
	public function shortcode_flash( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'swf'       => FALSE, // comma seperated multiple url to show multiple flash // UNFINISHED
			'width'     => '800',
			'height'    => '600',
			'rand'      => FALSE, // if multiple url then use random
			'loop'      => 'no',
			'autostart' => 'no',
			'titles'    => '',
			'artists'   => '',
			'duration'  => '',
			'rtl'       => ( is_rtl() ? 'yes' : 'no' ),
			'download'  => FALSE,
			'context'   => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! $args['swf'] )
			return null;

		if ( $args['rand'] && FALSE !== strpos( $args['swf'], ',' ) ) {
			$swf = explode( ',', $args['swf'] );
			$key = rand( 0, ( count( $swf ) - 1 ) );
			$args['swf'] = $swf[$key];
		}

		$key = count( $this->_flash_ids ) + 1;
		$id = 'gNetworkFlash_'.$key;
		$this->_flash_ids[$key] = $id;

		wp_enqueue_script( 'swfobject' );

		return '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$args['width'].'" height="'.$args['height'].'" id="'.$id.'">
<param name="movie" value="'.$args['swf'].'" />
<param name="quality" value="high" />
<!--[if !IE]>-->
<object type="application/x-shockwave-flash" data="'.$args['swf'].'" width="'.$args['width'].'" height="'.$args['height'].'">
<!--<![endif]-->
	<center><a href="'.GNETWORK_GETFLASHPLAYER_URL.'">
		<img src="'.GNETWORK_URL.'assets/images/get_flash_player.gif" alt="Get Adobe Flash player" />
	</a></center>
<!--[if !IE]>-->
</object>
<!--<![endif]-->
</object>';
	}

	// Converts [audio source="file.mp3"] into [audio:file.mp3]
	// http://wpaudioplayer.com/frequently-asked-questions/
	public function shortcode_audio( $atts, $content = null, $tag = '' )
	{
		if ( ! class_exists( 'AudioPlayer' ) )
			return $content;

		$args = apply_filters( 'gnetwork_shortcode_audio_atts', shortcode_atts( array(
			'source'    => FALSE,
			'mp3'       => FALSE,
			'mp3remote' => FALSE,
			'wma'       => FALSE,
			'wmaremote' => FALSE,
			'wma'       => FALSE,
			'wmaremote' => FALSE,
			'wmv'       => FALSE,
			'wmvremote' => FALSE,
			'loop'      => 'no',
			'autostart' => 'no',
			'titles'    => get_the_title(),
			'artists'   => '',
			'width'     => '100%',
			'duration'  => '',
			'rtl'       => ( is_rtl() ? 'yes' : 'no' ),
			'download'  => FALSE,
			'context'   => null,
		), $atts ) );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		if ( ! $args['source'] ) {
			$args['source'] = $args['mp3'];
			if ( ! $args['source'] ) {
				$args['source'] = $args['mp3remote'];
				if ( ! $args['source'] ) {
					$args['source'] = $args['wma'];
					if ( ! $args['source'] ) {
						$args['source'] = $args['wmaremote'];
						if ( ! $args['source'] ) {
							$args['source'] = $args['wmv'];
							if ( ! $args['source'] ) {
								$args['source'] = $args['wmvremote'];
								if ( ! $args['source'] ) {
									return $content;
								}
							}
						}
					}
				}
			}
		}

		global $AudioPlayer;
		//return $AudioPlayer->processContent($source);
		$html = $AudioPlayer->getPlayer( $args['source'], $args );

		// USE THIS: http://davidwalsh.name/download-attribute
		if ( $args['download'] )
			$html .= '<div class="download-media"><a href="'.$args['source'].'">'.apply_filters( 'gnetwork_shortcode_audio_download', $args['download'] ).'</a></div>';

		return $html;
	}

	public function wp_footer()
	{
		if ( count( $this->_github_repos ) )
			echo self::wrapJS( implode( "\n", $this->_github_repos ) );

		// this is for onload, so cannot use wrapJS
		if ( count( $this->_pdf_ids ) ) {
			echo '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
			echo 'window.onload = function(){'."\n";
			foreach( $this->_pdf_ids as $id )
				echo $id."\n";
			echo '};';
			echo "\n".'/* ]]> */'."\n".'</script>';
		}

		if ( count( $this->_flash_ids ) ) {
			echo '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
			foreach( $this->_flash_ids as $id )
				echo 'swfobject.registerObject("'.$id.'", "9.0.0");'."\n";
			echo "\n".'/* ]]> */'."\n".'</script>';
		}
	}

	public static function wrapJS( $script = '' )
	{
		return '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n".'jQuery(document).ready(public function($) {'."\n"
				.$script.
				'});'."\n".'/* ]]> */'."\n".'</script>';
	}

	// http://en.wikipedia.org/wiki/Help:Footnotes
	public function shortcode_ref( $atts, $content = null, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() )
			return null;

		$args = shortcode_atts( array(
			'url'           => FALSE,
			'url_title'     => __( 'See More', GNETWORK_TEXTDOMAIN ),
			'url_icon'      => 'def',
			'class'         => 'ref-anchor',
			'format_number' => true,
			'rtl'           => is_rtl(),
			'context'       => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		$html = $url = FALSE;

		if ( $content )
			$html = trim( strip_tags( $content ) );

		if ( 'def' == $args['url_icon'] )
			$args['url_icon'] = $args['rtl'] ? '&larr;' : '&rarr;';

		if ( $args['url'] )
			$url = gNetworkUtilities::html( 'a', array(
				'class' => 'refrence-external',
				'href'  => $args['url'],
				'title' => $args['url_title'],
			), $args['url_icon'] );

		if ( $html && $url )
			$html = $html.'&nbsp;'.$url;
		else if ( $url )
			$html = $url;

		if ( ! $html )
			return null;

		$key = count( $this->_ref_ids ) + 1;
		$this->_ref_ids[$key] = $html;

		$html = gNetworkUtilities::html( 'a', array(
			'class' => 'cite-scroll',
			'href'  => '#citenote-'.$key,
			'title' => trim( strip_tags( $content ) ),
		), '&#8207;['.( $args['format_number'] ? number_format_i18n( $key ) : $key ).']&#8206;' );

		return '<sup class="ref reference '.$args['class'].'" id="citeref-'.$key.'">'.$html.'</sup>';
	}

	// TODO: add column : http://en.wikipedia.org/wiki/Help:Footnotes#Reference_lists:_columns
	public function shortcode_reflist( $atts, $content = null, $tag = '' )
	{
		if ( $this->_ref_list )
			return null;

		if ( ! is_singular() || ! count( $this->_ref_ids ) )
			return null;

		$args = shortcode_atts( array(
			'class'         => 'ref-list',
			'number'        => true,
			'after_number'  => '- ',
			'format_number' => true,
			'back'          => '[&#8617;]', //'[^]', // '[&uarr;]',
			'context'       => null,
		), $atts, $tag );

		if ( FALSE === $args['context'] ) // bailing
			return null;

		$html = '';
		foreach ( $this->_ref_ids as $key => $text ) {

			if ( ! $text )
				continue;

			$item  = '<span class="ref-number">';
			$item .= ( $args['number'] ? ( $args['format_number'] ? number_format_i18n( $key ) : $key ).$args['after_number'] : '' );

			$item .= gNetworkUtilities::html( 'a', array(
				'class' => 'cite-scroll',
				'href'  => '#citeref-'.$key,
				'title' => trim( strip_tags( $content ) ),
			), $args['back'] );

			$html .= '<li>'.$item.'</span> <span class="ref-text"><span class="citation" id="citenote-'.$key.'">'.$text.'</span></span></li>';
		}

		$html = gNetworkUtilities::html( ( $args['number'] ? 'ul' : 'ol' ), array(
			'class' => $args['class'],
		), apply_filters( 'gnetwork_cite_reflist_before', '', $args ).$html );

		if ( ! defined( 'GNETWORK_DISABLE_REFLIST_JS' ) || ! GNETWORK_DISABLE_REFLIST_JS )
			wp_enqueue_script( 'gnetwork-cite', GNETWORK_URL.'assets/js/front.cite.min.js', array( 'jquery' ), GNETWORK_VERSION, true );

		$this->_ref_list = true;

		return '<div class="gnetwork-wrap-shortcode shortcode-reflist">'.$html.'</div>';
	}

	public function the_content( $content )
	{
		if ( ! is_singular()
			|| ! count( $this->_ref_ids )
			|| $this->_ref_list )
				return $content;

		remove_filter( 'the_content', array( &$this, 'the_content' ), 20 );
		return $content.apply_filters( 'the_content',
			$this->shortcode_reflist( array(), null, 'reflist' ) );
	}

	public function mce_buttons( $buttons )
	{
		array_push( $buttons, '|', 'gnetworkcite', 'gnetworkgemail' );
		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		$plugin_array['gnetworkcite']   = GNETWORK_URL.'assets/js/tinymce.cite.js';
		$plugin_array['gnetworkgemail'] = GNETWORK_URL.'assets/js/tinymce.email.js';

		return $plugin_array;
	}

	// FIXME: check this!
	public function shortcode_ref_manual( $atts, $content = null, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() )
			return null;

		// [ref-m id="0" caption="Caption Title"]
		// [ref-m 0 "Caption Title"]
		if ( isset( $atts['id'] ) ) {
			$args = shortcode_atts( array(
				'id'            => 0,
				'title'         => __( 'See the footnote', GNETWORK_TEXTDOMAIN ),
				'class'         => 'ref-anchor',
				'format_number' => true,
				'context'       => null,
				), $atts, $tag );

				if ( false === $args['context'] ) // bailing
					return null;

		} else { //[ref-m 0]
			$args['id'] = isset( $atts[0] ) ? $atts[0] : false;
			$args['title'] = isset( $attrs[1] ) ? $atts[1] : __( 'See the footnote', GNETWORK_TEXTDOMAIN );
			$args['class'] = isset( $attrs[2] ) ? $atts[2] : 'ref-anchor';
			$args['format_number'] = isset( $attrs[3] ) ? $atts[3] : true;
		}

		if ( false === $args['id'] )
			return null;

		return '<sup id="citeref-'.$args['id'].'-m" class="reference '.$args['class'].'" title="'.trim( strip_tags( $args['title'] ) ).'" ><a href="#citenote-'.$args['id'].'-m" class="cite-scroll">['.( $args['format_number'] ? number_format_i18n( $args['id'] ) : $args['id'] ).']</a></sup>';
	}

	// FIXME: check this!
	public function shortcode_reflist_manual( $atts, $content = null, $tag = '' )
	{
		// [reflist-m id="0" caption="Caption Title"]
		// [reflist-m 0 "Caption Title"]
		if ( isset( $atts['id'] ) ) {
			$args = shortcode_atts( array(
				'id'            => 0,
				'title'         => __( 'See the footnote', GNETWORK_TEXTDOMAIN ),
				'class'         => 'ref-anchor',
				'format_number' => true,
				'back'          => '[&#8617;]', //'&uarr;',
				'context'       => null,
				), $atts, $tag );

				if ( false === $args['context'] ) // bailing
					return null;

		} else { //[reflist-m 0]
			$args['id']            = $atts[0];
			$args['title']         = isset( $attrs[1] ) ? $atts[1] : __( 'See the footnote', GNETWORK_TEXTDOMAIN );
			$args['class']         = isset( $attrs[2] ) ? $atts[2] : 'ref-anchor';
			$args['format_number'] = isset( $attrs[3] ) ? $atts[3] : true;
			$args['back']          = isset( $attrs[4] ) ? $atts[4] : '[&#8617;]';
			$args['after_number']  = isset( $attrs[4] ) ? $atts[4] : '. ';
		}

		wp_enqueue_script( 'gnetwork-cite', GNETWORK_URL.'assets/js/front.cite.js', array( 'jquery' ), GNETWORK_VERSION, true );

		return '<span>'.( $args['format_number'] ? number_format_i18n( $args['id'] ) : $args['id'] ).$args['after_number']
				.'<span class="ref-backlink"><a href="#citeref-'.$args['id'].'-m" class="cite-scroll">'.$args['back']
				.'</a></span><span class="ref-text"><span class="citation" id="citenote-'.$args['id'].'-m">&nbsp;</span></span></span>';
	}

	// FIXME: UNFINISHED
	public function shortcode_repo_video( $atts, $content = null, $tag = '' )
	{
		//if ( is_singular() ) return null;

		// [repo-video id="0" caption="Caption Title"]
		// [repo-video 0 "Caption Title"]
		if ( isset( $atts['slug'] ) ) {

			$args = shortcode_atts( array(
				'path'     => false,
				// 'cat'      => __( 'See the footnote', GNETWORK_TEXTDOMAIN ),
				'class'    => '',
				'rtl'      => is_rtl(),
				'download' => true,
				'context'  => null,
			), $atts, $tag );

			if ( false === $args['context'] ) // bailing
				return null;

		} else { //[repo 0]

			$args['slug']          = $atts[0];
			$args['cat']           = isset( $attrs[1] ) ? $atts[1] : __( 'See the footnote', GNETWORK_TEXTDOMAIN );
			$args['class']         = isset( $attrs[2] ) ? $atts[2] : 'ref-anchor';
			$args['format_number'] = isset( $attrs[3] ) ? $atts[3] : true;
			$args['back']          = isset( $attrs[4] ) ? $atts[4] : '[&#8617;]';
			$args['after_number']  = isset( $attrs[4] ) ? $atts[4] : '. ';
		}

		if ( ! $args['slug'] )
			return null;

		$default_types = wp_get_video_extensions();
		// http://codex.wordpress.org/Video_Shortcode
		$defaults_atts = array(
			'src'      => '',
			'poster'   => '',
			'loop'     => '',
			'autoplay' => '',
			'preload'  => 'metadata',
			'width'    => 640,
			'height'   => 360,
		);

		foreach ( $default_types as $type )
			$defaults_atts[$type] = '';

		$atts = shortcode_atts( $defaults_atts, $attr, 'video' );
		// wp_video_shortcode( $attr )
	}
}

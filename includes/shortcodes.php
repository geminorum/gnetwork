<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkShortCodes extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = false;

	var $_flash_ids  = array();
	var $_pdf_ids    = array();
	var $_ref_ids    = array();
	var $_ref_list   = false;

	public function setup_actions()
	{
		add_action( 'init', array( & $this, 'init_early' ), 8 );
		add_action( 'init', array( & $this, 'init_late' ), 12 );
	}

	// fallback shortcodes
	public function init_early()
	{
		add_shortcode( 'book', array( & $this, 'shortcode_return_content' ) );
	}

	public function shortcode_return_content( $atts, $content = null, $tag = '' )
	{
		return $content;
	}

	public function init_late()
	{
		$this->shortcodes( array(
			// 'accordion'    => 'shortcode_accordion',
			'children'     => 'shortcode_children',
			'siblings'     => 'shortcode_siblings',
			'back'         => 'shortcode_back',
			'iframe'       => 'shortcode_iframe',
			'email'        => 'shortcode_email',
			'googlegroups' => 'shortcode_googlegroups',
			// 'github-repo'  => 'shortcode_github_repo',
			'pdf'          => 'shortcode_pdf',
			'bloginfo'     => 'shortcode_bloginfo',
			'audio'        => 'shortcode_audio',
			'flash'        => 'shortcode_flash',
			'ref'          => 'shortcode_ref',
			'reflist'      => 'shortcode_reflist',
			'ref-m'        => 'shortcode_ref_manual',
			'reflist-m'    => 'shortcode_reflist_manual',
			'repo-video'   => 'shortcode_repo_video',
		) );

		add_filter( 'the_content', array( & $this, 'the_content' ), 20 );
		add_action( 'wp_footer', array( & $this, 'wp_footer' ), 20 );

		if ( ! current_user_can( 'edit_posts' )
			&& ! current_user_can( 'edit_pages' ) )
			return;

		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( & $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( & $this, 'mce_buttons' ) );
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

	// http://stephanieleary.com/2010/06/listing-child-pages-with-a-shortcode/
	public function shortcode_children( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id' => get_queried_object_id(),
			'type' => 'page',
		), $atts, $tag );

		if ( ! is_singular( $args['type'] ) )
			return $content;

		$children = wp_list_pages( array(
			'child_of'    => $args['id'],
			'post_type'   => $args['type'],
			'echo'        => false,
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
			'parent' => null,
			'type'   => 'page',
			'ex'     => null,
		), $atts, $tag );

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
		), $atts, $tag );

		if ( ! $args['to'] )
			return $content;

		$html = false;

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

	// DRAFT
	// http://code.tutsplus.com/tutorials/developing-plugins-for-your-wordpress-theme-framework--cms-21934
	// add_action( 'wptp_sidebar', 'wptp_list_subpages' );
	public function shortcode_subpages( $atts, $content = null, $tag = '' )
	{
	  // don't run on the main blog page
		if ( is_page() && ! is_home() ) {

			// run the wptp_check_for_page_tree public function to fetch top level page
			$ancestor = self::check_for_page_tree();

			// set the arguments for children of the ancestor page
			$args = array(
				'child_of' => $ancestor,
				'depth'    => '-1',
				'title_li' => '',
			);

			// set a value for get_pages to check if it's empty
			$list_pages = get_pages( $args );

			// check if $list_pages has values
			if ( $list_pages ) {

				// open a list with the ancestor page at the top
				?>
				<ul class="page-tree">
					<?php // list ancestor page ?>
					<li class="ancestor">
						<a href="<?php echo get_permalink( $ancestor ); ?>"><?php echo get_the_title( $ancestor ); ?></a>
					</li>

					<?php
					// use wp_list_pages to list subpages of ancestor or current page
					wp_list_pages( $args );;

					// close the page-tree list
					?>
				</ul>

			<?php
			}
		}
	}

	public static function check_for_page_tree()
	{
		if ( is_page() ) {
			global $post;

			// next check if the page has parents
			if ( $post->post_parent ) {
				$parents = array_reverse( get_post_ancestors( $post->ID ) ); // fetch the list of ancestors
				return $parents[0]; // get the top level ancestor
			}

			return $post->ID; // return the id  - this will be the topmost ancestor if there is one, or the current page if not
		}
	}

	// http://urbangiraffe.com/plugins/pageview/
	public function shortcode_iframe( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'url'    => false,
			'width'  => '100%',
			'height' => '520',
			'scroll' => 'auto',
			'style'  => 'width:100%!important;',
		), $atts, $tag );

		if ( ! $args['url'] )
			return null;

		if ( ! in_array( $args['scroll'], array( 'auto', 'yes', 'no' ) ) )
			$args['scroll'] = 'no';

		return '<iframe src="'.$args['url'].'" frameborder="0" style="'.$args['style'].'" scrolling="'.$args['scroll'].'" height="'.$args['height'].'" width="'.$args['width'].'"></iframe>';

	}

	public function shortcode_accordion( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'title_wrap' => 'h3',
		), $atts, $tag );

		// http://www.jacklmoore.com/notes/jquery-accordion-tutorial/
		// http://www.jacklmoore.com/demo/accordion.html

		// http://tympanus.net/Blueprints/NestedAccordion/
		// http://tympanus.net/codrops/2013/03/29/nested-accordion/

	}

	// [email]you@you.com[/email]
	// http://bavotasan.com/2012/shortcode-to-encode-email-in-wordpress-posts/
	public function shortcode_email( $atts, $content )
	{
		return '<a href="'.antispambot( "mailto:".$content ).'" class="email">'.antispambot( $content ).'</a>';
	}

	public function shortcode_googlegroups( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'title_wrap' => 'h3',
			'id'         => constant( 'GNETWORK_GOOGLE_GROUP_ID' ),
			'logo'       => 'color',
			'logo_style' => 'border:none;box-shadow:none;',
			'hl'         => constant( 'GNETWORK_GOOGLE_GROUP_HL' ),
		), $atts, $tag );

		if ( false == $args['id'] )
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
			'username' => false,
			'name'     => false,
			'branch'   => false,
		), $atts, $tag );

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
			'url'       => false, // comma seperated multiple url to show multiple pdf // UNFINISHED
			'width'     => '100%', // '840px',
			'height'    => '960px',
			'rand'      => false, // if multiple url then use random
			'navpanes'  => '1',
			'statusbar' => '0',
			'view'      => 'FitH',
			'pagemode'  => 'thumbs',
			'rtl'       => ( is_rtl() ? 'yes' : 'no' ),
			'download'  => false,
		), $atts, $tag );

		if ( ! $args['url'] )
			return null;

		if ( $args['rand'] && false !== strpos( $args['url'], ',' ) ) {
			$url = explode( ',', $args['url'] );
			$key = rand( 0, ( count( $url ) - 1 ) );
			$args['url'] = $url[$key];
		}

		$fallback = apply_filters( 'gnetwork_shortcode_pdf_fallback', sprintf( __( 'It appears you don\'t have Adobe Reader or PDF support in this web browser. <a href="%s">Click here to download the PDF</a>', GNETWORK_TEXTDOMAIN ), $args['url'] ) );

		$key = count( $this->_pdf_ids ) + 1;
		$id = 'gNetworkPDF'.$key;

		// https://github.com/pipwerks/PDFObject
		$this->_pdf_ids[$key] = ' var '.$id.' = new PDFObject({url:"'.$args['url'].'",id:"'.$id.'",width:"'.$args['width'].'",height:"'.$args['height'].'",pdfOpenParams:{navpanes:'.$args['navpanes'].',statusbar:'.$args['statusbar'].',view:"'.$args['view'].'",pagemode:"'.$args['pagemode'].'"}}).embed("'.$id.'div"); ';
		//$this->_pdf_ids[$key] = ' var '.$id.' = new PDFObject({url:"'.$args['url'].'",id:"'.$id.'",pdfOpenParams:{navpanes:'.$args['navpanes'].',statusbar:'.$args['statusbar'].',view:"'.$args['view'].'",pagemode:"'.$args['pagemode'].'"}}).embed("'.$id.'div"); ';

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
			'key'   => '',
			'wrap'  => false,
			'class' => 'blog-info blog-info-%s',
		), $atts, $tag );

	   return get_bloginfo( $args['key'] );
	}


	// http://wordpress.org/extend/plugins/kimili-flash-embed/other_notes/
	// http://yoast.com/articles/valid-flash-embedding/
	public function shortcode_flash( $atts, $content = null, $tag = '' )
	{
		$args = shortcode_atts( array(
			'swf'       => false, // comma seperated multiple url to show multiple flash // UNFINISHED
			'width'     => '800',
			'height'    => '600',
			'rand'      => false, // if multiple url then use random
			'loop'      => 'no',
			'autostart' => 'no',
			'titles'    => '',
			'artists'   => '',
			'duration'  => '',
			'rtl'       => ( is_rtl() ? 'yes' : 'no' ),
			'download'  => false,
		), $atts, $tag );

		if ( ! $args['swf'] )
			return null;

		if ( $args['rand'] && false !== strpos( $args['swf'], ',' ) ) {
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
			'source'    => false,
			'mp3'       => false,
			'mp3remote' => false,
			'wma'       => false,
			'wmaremote' => false,
			'wma'       => false,
			'wmaremote' => false,
			'wmv'       => false,
			'wmvremote' => false,
			'loop'      => 'no',
			'autostart' => 'no',
			'titles'    => get_the_title(),
			'artists'   => '',
			'width'     => '100%',
			'duration'  => '',
			'rtl'       => ( is_rtl() ? 'yes' : 'no' ),
			'download'  => false,
		), $atts ) );

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

	public function shortcode_ref( $atts, $content = null, $tag = '' )
	{
		if ( is_null( $content ) || ! is_singular() )
			return null;

		$args = shortcode_atts( array(
			'url'           => false,
			'class'         => 'ref-anchor',
			'format_number' => true,
			'rtl'           => ( is_rtl() ? 'yes' : 'no' ),
			'ext'           => 'def',
		), $atts, $tag );

		$html = $url = false;

		if ( $content )
			$html = trim( strip_tags( $content ) );

		if ( $args['url'] )
			$url = '<a class="refrence-external" href="'.esc_url( $args['url'] ).'">'
				.( 'def' == $args['ext'] ? ( $args['rtl'] == 'yes' ? '&larr;' : '&rarr;' ) : $args['ext'] ).'</a>';

		if ( $html && $url )
			$html = $html.' '.$url;
		else if ( $url )
			$html = $url;

		if ( ! $html )
			return null;

		$key = count( $this->_ref_ids )+1;
		$this->_ref_ids[$key] = $html;

		return '<sup id="citeref-'.$key.'" class="reference '.$args['class'].'" title="'.trim( strip_tags( $content ) ).'" ><a href="#citenote-'.$key.'" class="cite-scroll">['.( $args['format_number'] ? number_format_i18n( $key ) : $key ).']</a></sup>';
	}

	public function shortcode_reflist( $atts, $content = null, $tag = '' )
	{
		if ( $this->_ref_list )
			return null;

		if ( ! is_singular() || ! count( $this->_ref_ids ) )
			return null;

		$args = shortcode_atts( array(
			'class'         => 'ref-list',
			'number'        => true,
			'after_number'  => '. ',
			'format_number' => true,
			'back'          => '[&#8617;]', //'&uarr;',
			'rtl'           => ( is_rtl() ? 'yes' : 'no' ),
		), $atts, $tag );

		$list = ( $args['number'] ? 'ul' : 'ol' );

		$html = '<div class="reflist '.$args['class'].'">';
		$html .= apply_filters( 'gnetwork_cite_reflist_before', '', $args );
		$html .= '<'.$list.'>';
		foreach( $this->_ref_ids as $key => $text )
			if ( $text )
			$html .='<li>'.( $args['number'] ? ( $args['format_number'] ? number_format_i18n( $key ) : $key ).$args['after_number'] : '' )
				.'<span class="ref-backlink"><a href="#citeref-'.$key.'" class="cite-scroll">'.$args['back']
				.'</a></span> <span class="ref-text"><span class="citation" id="citenote-'.$key.'">'
				.$text.'</span></span></li>';
		$html .= '</'.$list.'></div>';

		$this->_ref_list = true;
		wp_enqueue_script( 'gnetwork-cite', GNETWORK_URL.'assets/js/front.cite.js', array( 'jquery' ), GNETWORK_VERSION, true );

		return $html;
	}

	public function the_content( $content )
	{
		if ( ! is_singular()
			|| ! count( $this->_ref_ids )
			|| $this->_ref_list )
				return $content;

		remove_filter( 'the_content', array( & $this, 'the_content' ), 20 );
		return $content.apply_filters( 'the_content',
			$this->shortcode_reflist( array(), null, 'reflist' ) );
	}

	public function mce_buttons( $buttons )
	{
		array_push( $buttons, '|', 'gnetworkcite' );
		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		$plugin_array['gnetworkcite'] = GNETWORK_URL.'assets/js/tinymce.cite.js';
		return $plugin_array;
	}

	// NOT USED YET
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
				), $atts, $tag );
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

	// NOT USED YET
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
				), $atts, $tag );
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

	// UNFINISHED
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
			), $atts, $tag );

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

// READ THIS : http://www.wpbeginner.com/wp-tutorials/how-to-find-and-remove-unused-shortcodes-from-wordpress-posts/

// http://wordpress.org/plugins/simple-footnotes/
// http://www.paulund.co.uk/improve-shortcodes

// http://code.tutsplus.com/tutorials/creating-a-shortcode-for-responsive-video--wp-32469
// https://codex.wordpress.org/Function_Reference/wp_video_shortcode
// https://codex.wordpress.org/Video_Shortcode
// http://bavotasan.com/2011/shortcode-for-html5-video-tag-in-wordpress/
// http://wpsnipp.com/index.php/public functions-php/display-youtube-video-with-embed-shortcode/

// https://github.com/mozilla/pdf.js/blob/master/examples/helloworld/index.html

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkTracking extends gNetworkModuleCore
{

	var $_network    = TRUE;
	var $_option_key = 'tracking';

	var $_ga_outbound   = FALSE;
	var $_gp_platformjs = FALSE;
	var $_ignore        = NULL;

	protected function setup_actions()
	{
		$this->register_menu( 'tracking',
			__( 'Tracking', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);

		add_action( 'init', array( &$this, 'init' ), 8 );
		add_action( 'wp_head', array( &$this, 'wp_head' ), 999 );
		add_action( 'wp_footer', array( &$this, 'wp_footer' ), 9 );
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'   => 'ignore_user',
					'type'    => 'roles',
					'title'   => __( 'Ignore Users', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Selected and above will be ignored', GNETWORK_TEXTDOMAIN ),
					'default' => 'edit_others_posts',
				),
				array(
					'field'   => 'ga_domain',
					'type'    => 'text',
					'title'   => __( 'GA Domain Name', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Enter your domain name: <code>example.com</code>, Or just <code>auto</code>', GNETWORK_TEXTDOMAIN ),
					'default' => 'auto',
				),
				array(
					'field'   => 'ga_account',
					'type'    => 'text',
					'title'   => __( 'GA Account', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Enter your Google Analytics account number: <code>UA-XXXXX-X</code>', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'ga_userid',
					'type'    => 'enabled',
					'title'   => __( 'GA Track UserID', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Track usernames in Google Analytics', GNETWORK_TEXTDOMAIN ),
					'default' => '1',
				),
				array(
					'field'   => 'ga_outbound',
					'type'    => 'enabled',
					'title'   => __( 'GA Track Outbounds', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Track outbound links in Google Analytics', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'quantcast',
					'type'    => 'text',
					'title'   => __( 'Quantcast', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Enter your Quantcast account number: <code>x-XXXXXXXXXXXX-</code>', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'plus_publisher',
					'type'    => 'text',
					'title'   => __( 'GP Publisher ID', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Enter your Google+ publisher number: <code>XXXXXXXXXXXXXXXXXXXXX</code>', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
			),
		);
	}

	public function default_options()
	{
		return array(
			'ga_account'     => '',
			'ga_domain'      => 'auto',
			'ga_userid'      => '1',
			'ga_outbound'    => '0',
			'quantcast'      => '',
			'plus_publisher' => '',
			'ignore_user'    => 'edit_others_posts',
		);
	}

	public function ignore()
	{
		if ( ! is_null( $this->_ignore ) )
			return $this->_ignore;

		$this->_ignore = FALSE;

		if ( gNetworkUtilities::isDev() )
			$this->_ignore = TRUE;
		else if ( self::cuc( $this->options['ignore_user'] ) )
			$this->_ignore = TRUE;

		return $this->_ignore;
	}

	public function init()
	{
		$this->shortcodes( array(
			'google-plus-badge' => 'shortcode_google_plus_badge',
		) );
	}

	public function shortcode_google_plus_badge( $atts, $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'id'      => FALSE,
			'href'    => FALSE,
			'width'   => '300',
			'rel'     => 'publisher',
			'context' => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( ! $args['id'] && ! empty( $this->options['plus_publisher'] ) )
			$args['id'] = $this->options['plus_publisher'];

		if ( $args['id'] )
			$args['href'] = sprintf( 'https://plus.google.com/%s', $args['id'] );

		if ( ! $args['href'] )
			return $content;

		$this->_gp_platformjs = TRUE;

		$html = gNetworkUtilities::html( 'div', array(
			'class'      => 'g-page',
			'data-width' => $args['width'],
			'data-href'  => $args['href'],
			'data-rel'   => $args['rel'],
		), NULL );

		return '<div class="gnetwork-wrap-shortcode shortcode-googleplus-badge">'.$html.'</div>';
	}

	public function wp_head()
	{
		if ( $this->ignore() )
			return;

		if ( ! empty( $this->options['plus_publisher'] ) )
			echo "\t".'<link href="https://plus.google.com/'.$this->options['plus_publisher'].'" rel="publisher" />'."\n";

		if ( empty( $this->options['ga_domain'] ) || empty( $this->options['ga_account'] ) )
			return;

		$ga = "ga('create', '".esc_js( $this->options['ga_account'] )."', '".esc_js( $this->options['ga_domain'] )."');"."\n";

		if ( $this->options['ga_userid'] && is_user_logged_in() )
			$ga .= "ga('set', '&uid', '".esc_js( wp_get_current_user()->user_login )."');"."\n";

		$ga .= "ga('send', 'pageview');";

?><script type="text/javascript">
/* <![CDATA[ */
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	<?php echo $ga; ?>
/* ]]> */
</script><?php

		if ( $this->options['ga_outbound'] ) {
			$this->_ga_outbound = TRUE;
			wp_enqueue_script( 'jquery' );
		}
	}

	public function wp_footer()
	{
		// http://www.sitepoint.com/track-outbound-links-google-analytics/
		if ( $this->_ga_outbound ) {
			?><script type="text/javascript">
/* <![CDATA[ */
	(function($){"use strict";var baseURI=window.location.host;$("body").on("click",function(e){if(e.isDefaultPrevented()||typeof ga!=="function")return;var link=$(e.target).closest("a");if(link.length!=1||baseURI==link[0].host)return;e.preventDefault();var href=link[0].href;ga('send',{'hitType':'event','eventCategory':'outbound','eventAction':'link','eventLabel':href,'hitCallback':loadPage});setTimeout(loadPage,1000);function loadPage(){document.location=href;}});})(jQuery);
/* ]]> */
</script> <?php
		}

		if ( ! empty( $this->options['quantcast'] ) ) {

?><script type="text/javascript">
/* <![CDATA[ */
var _qevents = _qevents || [];

(function() {
var elem = document.createElement('script');
elem.src = (document.location.protocol == "https:" ? "https://secure" : "http://edge") + ".quantserve.com/quant.js";
elem.async = true;
elem.type = "text/javascript";
var scpt = document.getElementsByTagName('script')[0];
scpt.parentNode.insertBefore(elem, scpt);
})();

_qevents.push({
qacct:"<?php echo $this->options['quantcast']; ?>"
});
/* ]]> */
</script>

<noscript>
<div style="display:none;">
<img src="//pixel.quantserve.com/pixel/<?php echo $this->options['quantcast']; ?>.gif" border="0" height="1" width="1" alt="Quantcast"/>
</div>
</noscript><?php

		}

		// https://developers.google.com/+/web/api/supported-languages

		if ( $this->_gp_platformjs ) {
?><script type="text/javascript">
/* <![CDATA[ */
	window.___gcfg = {lang: 'fa'};

	(function() {
		var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		po.src = 'https://apis.google.com/js/platform.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	})();
/* ]]> */
</script><?php
		}
	}
}

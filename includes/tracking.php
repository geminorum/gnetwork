<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkTracking extends gNetworkModuleCore
{

	var $_network = true;
	var $_option_key = 'tracking';

	var $_ga_outbound = false;
	var $_ignore = null;

	public function setup_actions()
	{
		gNetworkNetwork::registerMenu( 'tracking',
			__( 'Tracking', GNETWORK_TEXTDOMAIN ),
			array( & $this, 'settings' )
		);

		add_action( 'wp_head',   array( & $this, 'wp_head'   ), 999 );
		add_action( 'wp_footer', array( & $this, 'wp_footer' ), 9 );
	}

	public function settings( $sub = NULL )
	{
		if ( 'tracking' == $sub ) {
			$this->settings_update( $sub );
			add_action( 'gnetwork_network_settings_sub_tracking', array( & $this, 'settings_html' ), 10, 2 );
			$this->register_settings();
		}
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field' => 'ignore_user',
					'type' => 'roles',
					'title' => __( 'Ignore Users', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Selected and above will be ignored', GNETWORK_TEXTDOMAIN ),
					'default' => 'edit_others_posts',
				),
				array(
					'field' => 'ga_domain',
					'type' => 'text',
					'title' => __( 'GA Domain Name', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Enter your domain name: <code>example.com</code>', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field' => 'ga_account',
					'type' => 'text',
					'title' => __( 'GA Account', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Enter your Google Analytics account number: <code>UA-XXXXX-X</code>', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field' => 'ga_outbound',
					'type' => 'enabled',
					'title' => __( 'GA Track Outbounds', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Track outbound links in Google Analytics', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field' => 'quantcast',
					'type' => 'text',
					'title' => __( 'Quantcast', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Enter your Quantcast account number: <code>x-XXXXXXXXXXXX-</code>', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field' => 'plus_publisher',
					'type' => 'text',
					'title' => __( 'GP Publisher ID', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Enter your Google+ publisher number: <code>XXXXXXXXXXXXXXXXXXXXX</code>', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field' => 'debug',
					'type' => 'debug',
				),
			),
		);
	}

	public function default_options()
	{
		return array(
			'ga_account' => '',
			'ga_domain' => '',
			'ga_outbound' => '0',
			'quantcast' => '',
			'plus_publisher' => '',
			'ignore_user' => 'edit_others_posts',
		);
	}

	public function ignore()
	{
		if ( ! is_null( $this->_ignore ) )
			return $this->_ignore;

		$this->_ignore = false;

		if ( gNetworkUtilities::isDev() )
			$this->_ignore = true;
		else if ( self::cuc( $this->options['ignore_user'] ) )
			$this->_ignore = true;

		return $this->_ignore;
	}

	public function wp_head()
	{
		if ( $this->ignore() )
			return;

		if ( ! empty( $this->options['plus_publisher'] ) )
			echo "\t".'<link href="https://plus.google.com/'.$this->options['plus_publisher'].'" rel="publisher" />'."\n";

		if ( empty( $this->options['ga_domain'] ) || empty( $this->options['ga_account'] ) )
			return;

?><script type="text/javascript">
/* <![CDATA[ */
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', '<?php echo $this->options['ga_account']; ?>', '<?php echo $this->options['ga_domain']; ?>');
	  ga('send', 'pageview');
/* ]]> */
</script> <?php

		if ( $this->options['ga_outbound'] ) {
			$this->_ga_outbound = true;
			wp_enqueue_script( 'jquery' );
		}
	}

	public function wp_footer()
	{
		if ( $this->ignore() )
			return;

		// http://www.sitepoint.com/track-outbound-links-google-analytics/
		if ( $this->_ga_outbound ) {
			?><script type="text/javascript">
/* <![CDATA[ */
	(function($){"use strict";var baseURI=window.location.host;$("body").on("click",function(e){if(e.isDefaultPrevented()||typeof ga!=="function")return;var link=$(e.target).closest("a");if(link.length!=1||baseURI==link[0].host)return;e.preventDefault();var href=link[0].href;ga('send',{'hitType':'event','eventCategory':'outbound','eventAction':'link','eventLabel':href,'hitCallback':loadPage});setTimeout(loadPage,1000);function loadPage(){document.location=href;}});})(jQuery);
/* ]]> */
</script> <?php
		}

		if ( empty( $this->options['quantcast'] ) )
			return;

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
}

// http://www.sitepoint.com/upgrading-universal-analytics-guide/
// http://digwp.com/2012/06/add-google-analytics-wordpress/
// https://developers.google.com/analytics/devguides/collection/gajs/
// https://developers.google.com/analytics/devguides/collection/gajs/asyncMigrationExamples

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Tracking extends gNetwork\Module
{

	protected $key = 'tracking';

	private $ga_outbound   = FALSE;
	private $gp_platformjs = FALSE;
	private $ignore        = NULL;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 8 );
		$this->action( 'wp_head', 0, 999 );
		$this->action( 'login_head', 0, 999 );
		$this->action( 'wp_footer', 0, 99 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Tracking', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'primary_domain' => '',
			'ga_account'     => '',
			'ga_beacon'      => '',
			'ga_domain'      => 'auto',
			'ga_userid'      => '1',
			'ga_outbound'    => '0',
			'quantcast'      => '',
			'plus_publisher' => '',
			'twitter_site'   => '',
			'ignore_user'    => 'edit_others_posts',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'ignore_user',
					'type'        => 'cap',
					'title'       => _x( 'Ignore Users', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Selected and above will be ignored', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'edit_others_posts',
				],
				[
					'field'       => 'primary_domain',
					'type'        => 'text',
					'title'       => _x( 'Primary Domain Name', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network primary domain name', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => str_ireplace( [ 'http://', 'https://' ], '', home_url() ),
					'dir'         => 'ltr',
					'placeholder' => 'example.com',
				],
				[
					'field'       => 'ga_domain',
					'type'        => 'text',
					'title'       => _x( 'GA Domain Name', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network Google Analytics domain name or just <code>auto</code>', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'auto',
					'dir'         => 'ltr',
					'placeholder' => 'example.com',
				],
				[
					'field'       => 'ga_account',
					'type'        => 'text',
					'title'       => _x( 'GA Account', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network Google Analytics account number', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'dir'         => 'ltr',
					'placeholder' => 'UA-XXXXX-X',
				],
				[
					'field'       => 'ga_beacon',
					'type'        => 'text',
					'title'       => _x( 'GA Beacon', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network Google Analytics Beacon account number', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://github.com/igrigorik/ga-beacon' ),
					'dir'         => 'ltr',
					'placeholder' => 'UA-XXXXX-X',
				],
				[
					'field'       => 'ga_userid',
					'title'       => _x( 'GA Track UserID', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Track usernames in Google Analytics', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
					// TODO: add info link
				],
				[
					'field'       => 'ga_outbound',
					'title'       => _x( 'GA Track Outbounds', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Track outbound links in Google Analytics', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'quantcast',
					'type'        => 'text',
					'title'       => _x( 'Quantcast', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network Quantcast P-Code', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://www.quantcast.com/' ),
					'dir'         => 'ltr',
					'placeholder' => 'x-XXXXXXXXXXXX-',
				],
				[
					'field'       => 'plus_publisher',
					'type'        => 'text',
					'title'       => _x( 'GP Publisher ID', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network Google+ publisher id', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'dir'         => 'ltr',
					'placeholder' => 'XXXXXXXXXXXXXXXXXXXXX',
				],
				[
					'field'       => 'twitter_site',
					'type'        => 'text',
					'title'       => _x( 'Twitter Account', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network site twitter account', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'dir'         => 'ltr',
					'placeholder' => 'username'
				],
			],
		];
	}

	public function ignore()
	{
		if ( ! is_null( $this->ignore ) )
			return $this->ignore;

		$this->ignore = FALSE;

		if ( WordPress::isDev() )
			$this->ignore = TRUE;

		else if ( WordPress::cuc( $this->options['ignore_user'] ) )
			$this->ignore = TRUE;

		return $this->ignore;
	}

	public function init()
	{
		$this->shortcodes( [
			'google-plus-badge' => 'shortcode_google_plus_badge',
			'ga-beacon'         => 'shortcode_ga_beacon',
		] );
	}

	public function shortcode_google_plus_badge( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => FALSE,
			'href'    => FALSE,
			'width'   => '300',
			'rel'     => 'publisher',
			'context' => NULL,
			'wrap'    => TRUE,
		], $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( ! $args['id'] && ! empty( $this->options['plus_publisher'] ) )
			$args['id'] = $this->options['plus_publisher'];

		if ( $args['id'] )
			$args['href'] = sprintf( 'https://plus.google.com/%s', $args['id'] );

		if ( ! $args['href'] )
			return $content;

		$this->gp_platformjs = TRUE;

		$html = HTML::tag( 'div', [
			'class'      => 'g-page',
			'data-width' => $args['width'],
			'data-href'  => $args['href'],
			'data-rel'   => $args['rel'],
		], NULL );

		if ( $args['wrap'] )
			return '<div class="gnetwork-wrap-shortcode -googleplus-badge gnetwork-wrap-iframe">'.$html.'</div>';

		return $html;
	}

	public function shortcode_ga_beacon( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'server'  => 'https://ga-beacon.appspot.com/',
			'beacon'  => $this->options['ga_beacon'],
			'domain'  => URL::domain( $this->options['primary_domain'] ),
			'page'    => '',
			'badge'   => 'pixel', // 'flat' / 'flat-gif'
			'alt'     => 'Analytics',
			'context' => NULL,
			'wrap'    => FALSE,
			'md'      => NULL, // markdown // FIXME
		], $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		$src = URL::trail( $args['server'] ).$args['beacon'].'/'.$args['domain'].'/'.$args['page'];

		if ( $args['badge'] )
			$src .= '?'. $args['badge'];

		$html = HTML::tag( 'img', [
			'src' => $src,
			'alt' => $args['alt'],
		] );

		if ( $args['wrap'] )
			return '<div class="gnetwork-wrap-shortcode -beacon">'.$html.'</div>';

		return $html;
	}

	private function ga()
	{
		if ( empty( $this->options['ga_domain'] )
			|| empty( $this->options['ga_account'] ) )
				return FALSE;

		if ( ! $account = gNetwork()->option( 'ga_override', 'blog' ) )
			$account = $this->options['ga_account'];

		return "ga('create', '".esc_js( $account )."', '".esc_js( $this->options['ga_domain'] )."');"."\n";
	}

	private function ga_code( $ga )
	{
?><script type="text/javascript">
/* <![CDATA[ */
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	<?php echo $ga."\n"; ?>
/* ]]> */
</script><?php
	}

	public function wp_head()
	{
		if ( $this->ignore() )
			return;

		if ( ! empty( $this->options['plus_publisher'] ) )
			echo "\t".'<link href="https://plus.google.com/'.$this->options['plus_publisher'].'" rel="publisher" />'."\n";

		if ( ! empty( $this->options['twitter_site'] ) )
			echo "\t".'<meta name="twitter:site" content="@'.$this->options['twitter_site'].'" />'."\n";

		if ( ! ( $ga = $this->ga() ) )
			return;

		if ( $this->options['ga_userid'] && is_user_logged_in() )
			$ga .= "\t"."ga('set', '&uid', '".esc_js( wp_get_current_user()->user_login )."');"."\n";

		$ga .= "\t"."ga('send', 'pageview');";

		$this->ga_code( $ga );

		if ( $this->options['ga_outbound'] ) {
			$this->ga_outbound = TRUE;
			wp_enqueue_script( 'jquery' );
		}
	}

	public function login_head()
	{
		if ( $ga = $this->ga() ) {

			$ga .= "ga('send', {hitType: 'pageview', title:'login', page: location.pathname});";

			$this->ga_code( $ga );
		}
	}

	public function wp_footer()
	{
		if ( $this->ignore() )
			return;

		// http://www.sitepoint.com/track-outbound-links-google-analytics/
		if ( $this->ga_outbound ) {
			?><script type="text/javascript">
/* <![CDATA[ */
	(function($){"use strict";var baseURI=window.location.host;$("body").on("click",function(e){if(e.isDefaultPrevented()||typeof ga!=="function")return;var link=$(e.target).closest("a");if(link.length!=1||baseURI==link[0].host)return;e.preventDefault();var href=link[0].href;ga('send',{'hitType':'event','eventCategory':'outbound','eventAction':'link','eventLabel':href,'hitCallback':loadPage});setTimeout(loadPage,1000);function loadPage(){document.location=href;}});})(jQuery);
/* ]]> */
</script><?php
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

		// SEE: https://developers.google.com/+/web/api/supported-languages
		$iso = class_exists( __NAMESPACE__.'\\Locale' ) ? Locale::getISO() : 'en';

		// FIXME: make this responsive / use jquery
		// http://technumero.com/internet/customize-google-plus-badge-website-wordpress-blog/2773
		// http://stackoverflow.com/a/20316430
		// document.getElementsByClassName('g-page')[0].setAttribute('data-width', document.getElementById('google-badge').clientWidth);

		if ( $this->gp_platformjs ) {
?><script type="text/javascript">
/* <![CDATA[ */
	window.___gcfg = {lang: '<?php echo $iso; ?>'};

	(function() {
		var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		po.src = 'https://apis.google.com/js/platform.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	})();
/* ]]> */
</script><?php
		}
	}

	// TODO: helper for tracking on 503/403 pages

	// HELPER
	public static function getContact( $class = 'contact', $fallback = FALSE )
	{
		if ( $twitter = gNetwork()->option( 'twitter_site', 'tracking', $fallback ) )
			$html = HTML::tag( 'a',[
				'href'  => 'https://twitter.com/intent/user?screen_name='.$twitter,
				'title' => _x( 'Follow Us', 'Modules: Tracking', GNETWORK_TEXTDOMAIN ),
				'rel'   => 'follow',
				'dir'   => 'ltr',
			], '@'.$twitter );
		else
			return '';

		if ( $class )
			$html = HTML::tag( 'div', [
				'class' => $class,
			], $html );

		return $html;
	}
}

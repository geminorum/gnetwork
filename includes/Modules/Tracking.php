<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Third;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Tracking extends gNetwork\Module
{

	protected $key = 'tracking';

	private $ga_outbound = FALSE;
	private $ignore      = NULL;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 8 );
		$this->action( 'wp_head', 0, 999 );
		$this->action( 'login_head', 0, 999 );
		$this->action( 'wp_footer', 0, 99 );

		$this->filter( 'amp_post_template_analytics' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Tracking', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
	}

	public function default_options()
	{
		return [
			'register_shortcodes' => '0',
			'primary_domain'      => '',
			'ga_account'          => '',
			'ga_beacon'           => '',
			'ga_domain'           => 'auto',
			'ga_userid'           => '1',
			'ga_outbound'         => '0',
			'quantcast'           => '',
			'twitter_site'        => '',
			'ignore_user'         => 'edit_others_posts',
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
					'disabled'    => defined( 'GNETWORK_TRACKING_GA_ACCOUNT' ),
				],
				[
					'field'       => 'ga_account',
					'type'        => 'text',
					'title'       => _x( 'GA Account', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network Google Analytics account number', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'dir'         => 'ltr',
					'placeholder' => 'UA-XXXXX-X',
					'constant'    => 'GNETWORK_TRACKING_GA_ACCOUNT',
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
					'after'       => Settings::fieldAfterIcon( 'https://support.google.com/analytics/topic/6009743' ),
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
					'field'       => 'twitter_site',
					'type'        => 'text',
					'title'       => _x( 'Twitter Account', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network site twitter account', 'Modules: Tracking: Settings', GNETWORK_TEXTDOMAIN ),
					'dir'         => 'ltr',
					'placeholder' => 'username'
				],
				'register_shortcodes',
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
		if ( $this->options['register_shortcodes'] )
			$this->shortcodes( $this->get_shortcodes() );
	}

	protected function get_shortcodes()
	{
		return [
			'ga-beacon' => 'shortcode_ga_beacon',
		];
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
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$src = URL::trail( $args['server'] ).$args['beacon'].'/'.$args['domain'].'/'.$args['page'];

		if ( $args['badge'] )
			$src.= '?'. $args['badge'];

		$html = HTML::tag( 'img', [
			'src' => $src,
			'alt' => $args['alt'],
		] );

		if ( $args['wrap'] )
			return '<span class="-wrap shortcode-beacon">'.$html.'</span>';

		return $html;
	}

	private function get_ga_account()
	{
		if ( ! $blog = gNetwork()->option( 'ga_override', 'blog' ) )
			return trim( $blog );

		if ( defined( 'GNETWORK_TRACKING_GA_ACCOUNT' ) )
			return GNETWORK_TRACKING_GA_ACCOUNT;

		if ( ! empty( $this->options['ga_account'] ) )
			return trim( $this->options['ga_account'] );

		return FALSE;
	}

	private function ga()
	{
		if ( ! $account = $this->get_ga_account() )
			return FALSE;

		if ( defined( 'GNETWORK_TRACKING_GA_ACCOUNT' )
			|| empty( $this->options['ga_domain'] ) )
				$domain = 'auto';
		else
			$domain = esc_js( $this->options['ga_domain'] );

		return "ga('create', '".esc_js( $account )."', '".$domain."');"."\n";
	}

	private function ga_code( $ga )
	{
		$analytics = "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');";

		HTML::wrapScript( $analytics.$ga );
	}

	public function wp_head()
	{
		if ( ! empty( $this->options['twitter_site'] ) )
			echo "\t".'<meta name="twitter:site" content="'.Third::getTwitter( $this->options['twitter_site'] ).'" />'."\n";

		if ( $this->ignore() )
			return;

		if ( ! ( $ga = $this->ga() ) )
			return;

		if ( $this->options['ga_userid'] && is_user_logged_in() )
			$ga.= "\t"."ga('set', '&uid', '".esc_js( wp_get_current_user()->user_login )."');"."\n";

		$ga.= "\t"."ga('send', 'pageview');";

		// @REF: https://wp.me/p1wYQJ-5Xv
		if ( defined( 'WPCF7_VERSION' ) )
			$ga.= "\n\t"."document.addEventListener('wpcf7mailsent',function(e){ga('send','event','Contact Form','sent');},false);";

		$this->ga_code( $ga );

		if ( $this->options['ga_outbound'] ) {
			$this->ga_outbound = TRUE;
			wp_enqueue_script( 'jquery' );
		}
	}

	public function login_head()
	{
		if ( $ga = $this->ga() ) {

			$ga.= "ga('send',{hitType:'pageview',title:'login',page:location.pathname});";

			$this->ga_code( $ga );
		}
	}

	public function wp_footer()
	{
		if ( $this->ignore() )
			return;

		if ( $this->ga_outbound ) {

			// @REF: https://www.sitepoint.com/?p=84248
			HTML::wrapScript( '(function($){"use strict";var baseURI=window.location.host;$("body").on("click",function(e){if(e.isDefaultPrevented()||typeof ga!=="function"){return;};var link=$(e.target).closest("a");if(link.length!=1||baseURI==link[0].host){return;};e.preventDefault();var href=link[0].href;ga("send",{"hitType":"event","eventCategory":"outbound","eventAction":"link","eventLabel":href,"hitCallback":loadPage});setTimeout(loadPage,1000);function loadPage(){document.location=href;};});})(jQuery);' );
		}

		if ( ! empty( $this->options['quantcast'] ) ) {

			$quantcast = 'var _qevents=_qevents||[];(function(){var elem=document.createElement("script");elem.src=(document.location.protocol=="https:"?"https://secure":"http://edge")+".quantserve.com/quant.js";elem.async=true;elem.type="text/javascript";var scpt=document.getElementsByTagName("script")[0];scpt.parentNode.insertBefore(elem,scpt);})();';

			HTML::wrapScript( $quantcast.'_qevents.push({qacct:"'.esc_js( $this->options['quantcast'] ).'"});' );

			echo '<noscript><div style="display:none;"><img src="//pixel.quantserve.com/pixel/'.$this->options['quantcast'].'.gif" border="0" height="1" width="1" alt="Quantcast"/></div></noscript>';
		}
	}

	// @REF: https://github.com/Automattic/amp-wp/wiki/Analytics
	public function amp_post_template_analytics( $analytics )
	{
		if ( ! $account = $this->get_ga_account() )
			return $analytics;

		$analytics[$this->base.'-googleanalytics'] = [
			'type'        => 'googleanalytics',
			'attributes'  => [],
			'config_data' => [
				'vars'     => [ 'account' => $account ],
				'triggers' => [
					'trackPageview' => [
						'on'      => 'visible',
						'request' => 'pageview',
					],
				],
			],
		];

		return $analytics;
	}

	public static function getContact( $class = 'contact', $fallback = FALSE )
	{
		if ( ! $twitter = gNetwork()->option( 'twitter_site', 'tracking', $fallback ) )
			return '';

		$handle = Third::getTwitter( $twitter );

		$html = HTML::tag( 'a',[
			'href'  => 'https://twitter.com/intent/user?screen_name='.substr( $handle, 1 ),
			'title' => _x( 'Follow Us', 'Modules: Tracking', GNETWORK_TEXTDOMAIN ),
			'rel'   => 'follow',
			'dir'   => 'ltr',
		], $handle );

		return $class ? HTML::wrap( $html, $class ) : $html;
	}
}

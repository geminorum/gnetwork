<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Browser;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Third;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Tracking extends gNetwork\Module
{

	protected $key = 'tracking';

	private $ignore = NULL;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 8 );
		$this->action( 'wp_head', 0, 999 );
		$this->action( 'login_head', 0, 999 );

		$this->filter( 'amp_post_template_analytics' );
		$this->action_module( 'maintenance', 'template_after', 0, 15 );
		$this->action_module( 'restricted', 'template_after', 0, 15 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Tracking', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'register_shortcodes' => '0',
			'primary_domain'      => '',
			'ga_account'          => '',
			'ga_beacon'           => '',
			'ga_userid'           => '1',
			'ga_outbound'         => '0',
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
					'title'       => _x( 'Ignore Users', 'Modules: Tracking: Settings', 'gnetwork' ),
					'description' => _x( 'Selected and above will be ignored from tracking.', 'Modules: Tracking: Settings', 'gnetwork' ),
					'default'     => 'edit_others_posts',
				],
				[
					'field'       => 'primary_domain',
					'type'        => 'text',
					'title'       => _x( 'Primary Domain Name', 'Modules: Tracking: Settings', 'gnetwork' ),
					'description' => _x( 'Determines current network primary domain name.', 'Modules: Tracking: Settings', 'gnetwork' ),
					'default'     => str_ireplace( [ 'http://', 'https://' ], '', home_url() ),
					'dir'         => 'ltr',
					'placeholder' => 'example.com',
				],
				[
					'field'       => 'ga_account',
					'type'        => 'text',
					'title'       => _x( 'GA Account', 'Modules: Tracking: Settings', 'gnetwork' ),
					'description' => _x( 'Determines current network Google Analytics account ID.', 'Modules: Tracking: Settings', 'gnetwork' ),
					'dir'         => 'ltr',
					'placeholder' => 'UA-XXXXXXXX-X',
					'constant'    => 'GNETWORK_TRACKING_GA_ACCOUNT',
				],
				[
					'field'       => 'ga_beacon',
					'type'        => 'text',
					'title'       => _x( 'GA Beacon', 'Modules: Tracking: Settings', 'gnetwork' ),
					'description' => _x( 'Determines current network Google Analytics Beacon account ID.', 'Modules: Tracking: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://github.com/igrigorik/ga-beacon' ),
					'dir'         => 'ltr',
					'placeholder' => 'UA-XXXXXXXX-X',
				],
				[
					'field'       => 'ga_userid',
					'title'       => _x( 'GA Track UserID', 'Modules: Tracking: Settings', 'gnetwork' ),
					'description' => _x( 'Tracks registered users on Google Analytics.', 'Modules: Tracking: Settings', 'gnetwork' ),
					'default'     => '1',
					'after'       => Settings::fieldAfterIcon( 'https://support.google.com/analytics/topic/6009743' ),
				],
				[
					'field'       => 'ga_outbound',
					'title'       => _x( 'GA Track Outbounds', 'Modules: Tracking: Settings', 'gnetwork' ),
					'description' => _x( 'Tracks outbound links on Google Analytics.', 'Modules: Tracking: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'twitter_site',
					'type'        => 'text',
					'title'       => _x( 'Twitter Account', 'Modules: Tracking: Settings', 'gnetwork' ),
					'description' => _x( 'Determines current network site twitter account.', 'Modules: Tracking: Settings', 'gnetwork' ),
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
		$this->register_shortcodes();
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
		if ( $blog = gNetwork()->option( 'ga_override', 'blog' ) )
			return trim( $blog );

		if ( defined( 'GNETWORK_TRACKING_GA_ACCOUNT' ) )
			return GNETWORK_TRACKING_GA_ACCOUNT;

		if ( ! empty( $this->options['ga_account'] ) )
			return trim( $this->options['ga_account'] );

		return FALSE;
	}

	// @REF: https://developers.google.com/analytics/devguides/collection/gtagjs/migration
	private function render_gtag( $account, $track_outbound = FALSE, $extra = '', $config = [] )
	{
		// @SEE: assets/js/inline/tracking.code.js
		// $script = 'function gtag(){"object"==typeof window.dataLayer&&"function"==typeof window.dataLayer.push&&window.dataLayer.push(arguments)}function gtagCallback(t,a){function n(){e||(e=!0,t())}var e=!1;return setTimeout(n,a||1e3),n}gtag("js",new Date);';
		$script = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);};function gtagCallback(callback,timeout){var called=false;function fn(){if(!called){called=true;callback();}};setTimeout(fn,timeout||1000);return fn;};gtag("js",new Date());';

		if ( count( $config ) )
			$script.= "gtag('config','".esc_js( $account )."',".wp_json_encode( $config ).");";

		else
			$script.= "gtag('config','".esc_js( $account )."');";

		if ( $track_outbound ) {

			// closest v3.0.1 - https://github.com/jonathantneal/closest
			// @REF: https://unpkg.com/element-closest/browser
			$polyfill = '!function(e){var t=e.Element.prototype;"function"!=typeof t.matches&&(t.matches=t.msMatchesSelector||t.mozMatchesSelector||t.webkitMatchesSelector||function(e){for(var t=(this.document||this.ownerDocument).querySelectorAll(e),o=0;t[o]&&t[o]!==this;)++o;return Boolean(t[o])}),"function"!=typeof t.closest&&(t.closest=function(e){for(var t=this;t&&1===t.nodeType;){if(t.matches(e))return t;t=t.parentNode}return null})}(window);';

			// @SEE: /assets/js/inline/tracking.outbound.js
			$outbound = '!function(){document.addEventListener("click",function(t){if("function"==typeof gtag&&!t.isDefaultPrevented){var e=t.target.closest("a");e&&window.location.host!==e.host&&(t.preventDefault(),gtag("event","click",{event_category:"outbound",event_label:e.href,transport_type:"beacon",event_callback:gtagCallback(function(){document.location=e.href})}))}},!1)}();';

			if ( Browser::isIE() )
				$script.= $polyfill;

			$script.= $outbound;
		}

		echo '<script async src="https://www.googletagmanager.com/gtag/js?id='.$account.'"></script>';

		HTML::wrapScript( $script.$extra );
	}

	public function wp_head()
	{
		if ( ! empty( $this->options['twitter_site'] ) )
			echo '<meta name="twitter:site" content="'.Third::getHandle( $this->options['twitter_site'] ).'" />'."\n";

		if ( $this->ignore() )
			return;

		if ( ! $account = $this->get_ga_account() )
			return;

		$extra = '';

		if ( $this->options['ga_userid'] && is_user_logged_in() )
			$extra.= "gtag('set',{'user_id':'".esc_js( wp_get_current_user()->user_login )."'});";

		if ( defined( 'WPCF7_VERSION' ) )
			// @SEE: assets/js/inline/tracking.wpcf7.js
			$extra.= 'document.addEventListener("wpcf7mailsent",function(){gtag("event","contact",{transport_type:"beacon"})});';

		// TODO: add more events: @SEE: https://developers.google.com/analytics/devguides/collection/gtagjs/sending-data

		$this->render_gtag( $account, $this->options['ga_outbound'], $extra );
	}

	public function login_head()
	{
		if ( $this->ignore() )
			return;

		if ( ! $account = $this->get_ga_account() )
			return;

		// @SEE: assets/js/inline/tracking.login.js
		$extra = '!function(){document.addEventListener("DOMContentLoaded",function(){var t=document.getElementById("loginform");t.addEventListener("submit",function(n){n.preventDefault(),gtag("event","login",{transport_type:"beacon",event_callback:gtagCallback(function(){t.submit()})})})})}();';

		$this->render_gtag( $account, FALSE, $extra );
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

	public function maintenance_template_after()
	{
		echo self::getContact( 'contact small' );

		if ( $this->ignore() )
			return;

		if ( ! $account = $this->get_ga_account() )
			return;

		// FIXME: check for correct event
		$extra = '';

		$this->render_gtag( $account, FALSE, $extra );
	}

	public function restricted_template_after()
	{
		echo self::getContact( 'contact small' );

		if ( $this->ignore() )
			return;

		if ( ! $account = $this->get_ga_account() )
			return;

		// FIXME: check for correct event
		$extra = '';

		$this->render_gtag( $account, FALSE, $extra );
	}

	public static function getContact( $class = 'contact', $fallback = FALSE )
	{
		if ( ! $twitter = gNetwork()->option( 'twitter_site', 'tracking', $fallback ) )
			return '';

		$handle = Third::getHandle( $twitter );

		$html = HTML::tag( 'a',[
			'href'  => 'https://twitter.com/intent/user?screen_name='.substr( $handle, 1 ),
			'title' => _x( 'Follow Us', 'Modules: Tracking', 'gnetwork' ),
			'rel'   => 'follow',
		], HTML::wrapLTR( $handle ) );

		return $class ? HTML::wrap( $html, $class, FALSE ) : $html;
	}
}

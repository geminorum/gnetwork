<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Restricted extends gNetwork\Module
{

	protected $key     = 'restricted';
	protected $network = FALSE;

	private $bouncer = FALSE;

	protected function setup_actions()
	{
		if ( 'none' != $this->options['restricted_site'] )
			$this->bouncer = new RestrictedBouncer( $this->options );

		$this->action( 'init', 0, 2 );

		if ( ! is_admin() )
			return;

		if ( 'none' != $this->options['restricted_site'] )
			$this->filter_module( 'dashboard', 'pointers' );

		// TODO: support front-end profile
		add_action( 'load-profile.php', [ $this, 'load_profile' ] );
		add_action( 'load-user-edit.php', [ $this, 'load_profile' ] );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Restricted', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function init()
	{
		if ( ! is_user_logged_in() )
			return;

		if ( ! WordPress::cuc( $this->options['restricted_admin'] ) ) {

			if ( is_admin() ) {

				if ( 'open' == $this->options['restricted_profile']
					&& WordPress::pageNow( 'profile.php' ) ) {

						// do nothing

				} else {

					Utilities::redirectHome();
				}
			}

			$this->remove_menus();

		} else if ( ! WordPress::cuc( $this->options['restricted_site'] ) ) {

			$this->remove_menus();
		}
	}

	public function default_options()
	{
		return [
			'restricted_site'    => 'none',
			'restricted_admin'   => 'none',
			'restricted_profile' => 'open',
			'restricted_feed'    => 'open',
			'redirect_page'      => '0',
			'restricted_notice'  => '',
			'restricted_access'  => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'restricted_site',
					'type'        => 'cap',
					'title'       => _x( 'Site Restriction', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Only this role and above can access to the site.', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'none',
				],
				[
					'field'       => 'restricted_admin',
					'type'        => 'cap',
					'title'       => _x( 'Admin Restriction', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Only this role and above can access to the site\'s admin pages.', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'none',
				],
				[
					'field'       => 'restricted_profile',
					'type'        => 'select',
					'title'       => _x( 'Admin Profile', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether admin profile can be accessed if the site is in restricted mode.', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'open',
					'values'      => [
						'open'   => _x( 'Open', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
						'closed' => _x( 'Restricted', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					],
				],
				[
					'field'       => 'restricted_feed',
					'type'        => 'select',
					'title'       => _x( 'Feed Restriction', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether default feeds available if the site is in restricted mode.', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'open',
					'values'      => [
						'open'   => _x( 'Open', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
						'closed' => _x( 'Restricted', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					],
				],
				[
					'field'       => 'redirect_page',
					'type'        => 'page',
					'title'       => _x( 'Restricted Page', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Redirects not authorized users to this page. If not selected will redirect to the login page.', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'none_title'  => _x( '&mdash; Login Page &mdash;', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'none_value'  => '0',
				],
				[
					'field'       => 'restricted_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Restricted Notice', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => sprintf( _x( 'Displays on top of the site login page. Use %1$s for the role, and %2$s for the page.', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ), '<code>%1$s</code>', '<code>%2$s</code>' ),
					'default'     => _x( '<p>This site is restricted to users with %1$s access level. Please visit <a href="%2$s">here</a> to request access.</p>', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'restricted_access',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Restricted Access', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => sprintf( _x( 'Displays on 403 status page for logged-in users. Use %1$s for the role, and %2$s for the page.', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ), '<code>%1$s</code>', '<code>%2$s</code>' ),
					'default'     => _x( '<p>You do not have %1$s access level. Please visit <a href="%2$s">here</a> to request access.</p>', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		$template = Utilities::getLayout( 'status.403' );

		HTML::desc( sprintf( _x( 'Current Template: %s', 'Modules: Restricted: Settings', GNETWORK_TEXTDOMAIN ),
			'<code>'.HTML::link( $template, URL::fromPath( $template ), TRUE ).'</code>' ) );
	}

	private function remove_menus()
	{
		AdminBar::removeMenus( [
			'site-name',
			'my-sites',
			'blog-'.get_current_blog_id(),
			'edit',
			'new-content',
			'comments',
		] );
	}

	public function dashboard_pointers( $items )
	{
		$can = WordPress::cuc( 'manage_options' );

		$items[] = HTML::tag( $can ? 'a' : 'span', [
			'href'  => $can ? $this->get_menu_url( 'restricted' ) : FALSE,
			'title' => sprintf( _x( 'This site is restricted to users with %s access level.', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ), Settings::getUserCapList( $this->options['restricted_site'] ) ),
			'class' => '-restricted',
		], _x( 'Site is Restricted', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ) );

		return $items;
	}

	public function load_profile()
	{
		if ( is_network_admin()
			|| is_user_admin() )
				return;

		add_filter( 'show_user_profile', [ $this, 'edit_user_profile' ] );
		add_action( 'edit_user_profile', [ $this, 'edit_user_profile' ] );
		add_action( 'personal_options_update', [ $this, 'edit_user_profile_update' ] );
		add_action( 'edit_user_profile_update', [ $this, 'edit_user_profile_update' ] );
	}

	public function edit_user_profile( $profileuser )
	{
		if ( 'none' == $this->options['restricted_site']
			&& ! WordPress::isDev() )
				return;

		$feedkey = RestrictedBouncer::getUserFeedKey( $profileuser->ID, FALSE );
		$urls    = self::getFeeds( $feedkey );

		Settings::fieldSection(
			_x( 'Private Feeds', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
			_x( 'Used to access restricted site feeds.', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
			'h2'
		);

		echo '<table class="form-table">';

			$this->do_settings_field( [
				'field'       => 'restricted_feed_key',
				'type'        => 'text',
				'cap'         => 'read',
				'title'       => _x( 'Access Key', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'The key will be used on all restricted site feed URLs.', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				'placeholder' => _x( 'Access key not found', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'default'     => $feedkey ?: '',
				'disabled'    => TRUE,
				'wrap'        => TRUE,
			] );

			$operations = [ 'none' => Settings::showOptionNone() ];

			if ( $feedkey ) {
				$operations['reset']  = _x( 'Reset your access key', 'Modules: Restricted', GNETWORK_TEXTDOMAIN );
				$operations['remove'] = _x( 'Remove your access key', 'Modules: Restricted', GNETWORK_TEXTDOMAIN );
			} else {
				$operations['generate'] = _x( 'Generate new access key', 'Modules: Restricted', GNETWORK_TEXTDOMAIN );
			}

			$this->do_settings_field( [
				'field'       => 'feed_operations',
				'type'        => 'select',
				'cap'         => 'read',
				'title'       => _x( 'Key Operations', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Select an operation to work with your private feed access key.', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				'default'     => 'none',
				'values'      => $operations,
				'wrap'        => TRUE,
			] );

			if ( $feedkey ) {

				$this->do_settings_field( [
					'field'  => 'restricted_feed_url',
					'type'   => 'custom',
					'cap'    => 'read',
					'title'  => _x( 'Your Feed', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
					'values' => '<code><a href="'.$urls['rss2'].'" target="_blank">'.$urls['rss2'].'</a></code>',
					'wrap'   => TRUE,
				] );

				$this->do_settings_field( [
					'field'  => 'restricted_feed_comments_url',
					'type'   => 'custom',
					'cap'    => 'read',
					'title'  => _x( 'Your Comments Feed', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
					'values' => '<code><a href="'.$urls['comments_rss2_url'].'" target="_blank">'.$urls['comments_rss2_url'].'</a></code>',
					'wrap'   => TRUE,
				] );
			}

		echo '</table>';
	}

	public function edit_user_profile_update( $user_id )
	{
		if ( isset( $_POST['gnetwork_restricted']['feed_operations'] )
			&& 'none' != $_POST['gnetwork_restricted']['feed_operations']
			&& strlen( $_POST['gnetwork_restricted']['feed_operations'] ) > 0 ) {

			switch ( $_POST['gnetwork_restricted']['feed_operations'] ) {

				case 'remove':

					delete_user_meta( $user_id, 'feed_key' );

				break;
				case 'reset':
				case 'generate':

					$feedkey = RestrictedBouncer::getUserFeedKey( $user_id, FALSE, TRUE );
			}
		}
	}

	public static function is()
	{
		return ( ! WordPress::cuc( gNetwork()->option( 'restricted_site', 'restricted', 'none' ) ) );
	}

	public static function getFeeds( $feed_key = FALSE, $check = TRUE )
	{
		if ( ! $feed_key && $check )
			$feed_key = RestrictedBouncer::getUserFeedKey( FALSE, FALSE );

		return [
			'rss2'              => ( $feed_key ? add_query_arg( 'feedkey', $feed_key, get_feed_link( 'rss2' ) ) : get_feed_link( 'rss2' ) ),
			'comments_rss2_url' => ( $feed_key ? add_query_arg( 'feedkey', $feed_key, get_feed_link( 'comments_rss2' ) ) : get_feed_link( 'comments_rss2' ) ),
		];
	}

	public static function get403Logout( $class = 'logout' )
	{
		$html = HTML::tag( 'a', [
			'href'  => GNETWORK_BASE,
			'title' => GNETWORK_NAME,
		], _x( 'Home Page', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ) );

		if ( is_user_logged_in() ) {
			$html.= ' / '.HTML::tag( 'a', [
				'href' => wp_logout_url(),
				'title' => _x( 'Logout of this site', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
			], _x( 'Log Out', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ) );
		}

		if ( $class )
			$html = HTML::tag( 'div', [
				'class' => $class,
			], $html );

		return $html;
	}

	public static function get403Message( $class = 'message' )
	{
		if ( gNetwork()->option( 'restricted_access', 'restricted' ) )
			$html = self::getNotice(
				gNetwork()->option( 'restricted_access', 'restricted', '' ),
				gNetwork()->option( 'restricted_site', 'restricted', 'none' ),
				gNetwork()->option( 'redirect_page', 'restricted', '0' ),
				FALSE );
		else
			$html = _x( 'You do not have sufficient access level.', 'Modules: Restricted', GNETWORK_TEXTDOMAIN );

		if ( $class )
			$html = HTML::tag( 'div', [
				'class' => $class,
			], $html );

		return $html;
	}

	public static function getNotice( $notice, $role, $page = FALSE, $register = TRUE )
	{
		return sprintf( $notice,
			Settings::getUserCapList( $role ),
			( $page ? get_page_link( $page )
				: ( $register ? WordPress::registerURL( 'site' ) : '#' ) ) );
	}
}

class RestrictedBouncer extends \geminorum\gNetwork\Core\Base
{

	protected $options = [];
	protected $key     = FALSE;
	protected $valid   = FALSE;
	protected $access  = FALSE;

	public function __construct( $options )
	{
		$this->options = $options;

		if ( is_admin() ) {

			add_action( 'admin_init', [ $this, 'admin_init' ], 1 );

		} else {

			if ( 'open' != $this->options['restricted_feed'] )
				add_action( 'init', [ $this, 'init' ], 1 );

			add_action( 'template_redirect', [ $this, 'template_redirect' ], 1 );

			if ( ! empty( $options['restricted_notice'] ) )
				add_filter( 'login_message', [ $this, 'login_message' ] );

			add_filter( 'rest_authentication_errors', [ $this, 'rest_authentication_errors' ], 999 );
		}

		// block search engines and robots
		add_filter( 'robots_txt', [ $this, 'robots_txt' ] );
		add_filter( 'option_blog_public', '__return_zero', 20 );
	}

	public function init()
	{
		$this->key = self::getUserFeedKey();

		if ( $this->key && is_user_logged_in() )
			add_filter( 'feed_link', [ $this, 'feed_link' ], 12, 2 );

		$feedkey = isset( $_GET['feedkey'] ) ? trim( $_GET['feedkey'] ) : FALSE;

		if ( ! $feedkey ) {

			// no feed key, do nothing
			// restriction comes along automagically!
			// see `template_redirect`

		} else if ( is_user_logged_in() ) {

			if ( $feedkey == $this->key )
				$this->valid = TRUE;

			if ( 'logged_in_user' == $this->options['restricted_site']
				|| current_user_can( $this->options['restricted_site'] ) )
					$this->access = TRUE;

		} else {

			global $wpdb;

			$user_id = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_value = %s", $feedkey ) );

			if ( ! empty( $user_id ) ) {

				$this->valid = TRUE;

				if ( 'logged_in_user' == $this->options['restricted_site'] )
					$this->access = TRUE;

				else if ( user_can( intval( $user_id ), $this->options['restricted_site'] ) )
					$this->access = TRUE;
			}
		}
	}

	public function admin_init()
	{
		add_filter( 'privacy_on_link_title', function( $title ) {
			return _x( 'Your site is restricted to public', 'Modules: Restricted: At a Glance', GNETWORK_TEXTDOMAIN );
		}, 20 );

		add_filter( 'privacy_on_link_text', function( $content ) {
			return _x( 'Public Access Discouraged', 'Modules: Restricted: At a Glance', GNETWORK_TEXTDOMAIN );
		}, 20 );

		if ( WordPress::cuc( $this->options['restricted_admin'] ) )
			return;

		if ( 'open' == $this->options['restricted_profile']
			&& WordPress::pageNow( 'profile.php' ) ) {

			// do nothing

		} else if ( $this->options['redirect_page'] ) {

			WordPress::redirect( get_page_link( $this->options['redirect_page'] ), 302 );

		} else {

			Utilities::getLayout( 'status.403', TRUE, TRUE );
			die();
		}
	}

	public function feed_link( $output, $feed )
	{
		return $this->key ? add_query_arg( 'feedkey', $this->key, $output ) : $output;
	}

	private function check_feed_access()
	{
		if ( $this->valid && $this->access ) {

			return;

		} else if ( $this->valid ) {

			// key is valid but no access
			// redirect to request access page

			self::temp_feed(
				_x( 'Key Valid but no Access', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				_x( 'Your Key is valid but you have no access to this site.', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				( $this->options['redirect_page'] ? get_page_link( $this->options['redirect_page'] ) : FALSE ) );

		} else if ( is_user_logged_in() ) {

			// user have access but the key is invalid
			// TODO: add notice;

			return;

		} else {

			// key is not valid and no access
			// redirect to request access page

			self::temp_feed(
				_x( 'No key, no Access', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				_x( 'You have to have a key to access this site\'s feed', 'Modules: Restricted', GNETWORK_TEXTDOMAIN ),
				( $this->options['redirect_page'] ? get_page_link( $this->options['redirect_page'] ) : FALSE ) );
		}
	}

	private function temp_feed( $title = '', $desc = '', $link = FALSE )
	{
		if ( ! $link )
			$link = get_bloginfo_rss( 'url' );

		header( "Content-Type: application/xml; ".get_option( 'blog_charset' ) );

		if ( $layout = Utilities::getLayout( 'feed.temp' ) )
			require_once( $layout ); // accessing $title/$desc/$link

		die();
	}

	public static function getUserFeedKey( $user_id = FALSE, $generate = TRUE, $reset = FALSE )
	{
		if ( ! $user_id && ! is_user_logged_in() )
			return FALSE;

		if ( ! $user_id )
			$user_id = get_current_user_id();

		$feedkey = get_user_meta( $user_id, 'feed_key', TRUE );

		if ( $reset || ( $generate && ( empty( $feedkey ) || FALSE == $feedkey ) ) ) {
			$feedkey = self::genFeedKey();
			update_user_meta( $user_id, 'feed_key', $feedkey );
		}

		return $feedkey;
	}

	private static function genFeedKey()
	{
		$data = $GLOBALS['userdata']->user_login.wp_generate_password( 12, TRUE, TRUE );
		return hash_hmac( 'md5', $data, wp_salt( 'auth' ) );
	}

	public function template_redirect()
	{
		// using BuddyPress and on the register page
		if ( function_exists( 'bp_is_current_component' )
			&& ( bp_is_current_component( 'register' )
				|| bp_is_current_component( 'activate' ) ) )
					return;

		if ( 'closed' == $this->options['restricted_feed'] && is_feed() )
			return $this->check_feed_access();

		if ( $this->options['redirect_page']
			&& is_page( intval( $this->options['redirect_page'] ) ) )
				return;

		if ( is_user_logged_in() ) {

			if ( 'logged_in_user' == $this->options['restricted_site'] )
				return;

			if ( current_user_can( $this->options['restricted_site'] ) )
				return;

			if ( $this->options['redirect_page'] ) {

				WordPress::redirect( get_page_link( $this->options['redirect_page'] ), 403 );

			} else {

				Utilities::getLayout( 'status.403', TRUE, TRUE );
				die();
			}
		}

		if ( ! is_front_page() && ! is_home() )
			WordPress::redirectLogin( URL::current() );

		if ( $this->options['redirect_page'] )
			WordPress::redirect( get_page_link( $this->options['redirect_page'] ), 403 );

		WordPress::redirectLogin( URL::current() );
	}

	public function login_message()
	{
		echo '<div id="login_error">';

		echo Restricted::getNotice(
			$this->options['restricted_notice'],
			$this->options['restricted_site'],
			$this->options['redirect_page']
		);

		echo '</div><style>#backtoblog{display:none;}</style>';
	}

	public function robots_txt( $output )
	{
		return $output.'Disallow: /'."\n";
	}

	public function rest_authentication_errors( $null )
	{
		if ( current_user_can( $this->options['restricted_site'] ) )
			return $null;

		$notice = Restricted::getNotice(
			$this->options['restricted_notice'],
			$this->options['restricted_site'],
			$this->options['redirect_page']
		);

		return new Error( 'restricted', $notice, [ 'status' => 403 ] );
	}
}

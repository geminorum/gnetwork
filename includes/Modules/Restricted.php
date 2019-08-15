<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Restricted extends gNetwork\Module
{

	protected $key     = 'restricted';
	protected $network = FALSE;

	private $status_code = 403;
	private $feed_key    = NULL;

	protected function setup_actions()
	{
		if ( is_admin() ) {

			if ( 'none' != $this->options['access_admin'] )
				$this->action( 'admin_init', 0, 1 );

			if ( 'none' != $this->options['access_site'] ) {

				$this->filter( 'privacy_on_link_title', 1, 20 );
				$this->filter( 'privacy_on_link_text', 1, 20 );

				$this->filter_module( 'dashboard', 'pointers' );

				if ( ! is_network_admin() && ! is_user_admin() ) {

					add_action( 'load-profile.php', [ $this, 'load_profile' ] );
					add_action( 'load-user-edit.php', [ $this, 'load_profile' ] );
				}
			}

		} else {

			if ( 'none' != $this->options['access_site'] ) {

				$this->action( 'init', 0, 1 );
				$this->filter( 'rest_authentication_errors', 1, 999 );
			}
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Restricted', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'access_site'        => 'none', // restricted_site
			'access_admin'       => 'none', // restricted_admin
			'access_profile'     => 'open', // restricted_profile
			'redirect_to_page'   => '0', // redirect_page
			'restricted_notice'  => '', // login_message
			'restricted_message' => '', // restricted_access
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'access_site',
					'type'        => 'cap',
					'title'       => _x( 'Site Restriction', 'Modules: Restricted: Settings', 'gnetwork' ),
					'description' => _x( 'Only this role and above can access to the site.', 'Modules: Restricted: Settings', 'gnetwork' ),
					'default'     => 'none',
				],
				[
					'field'       => 'access_admin',
					'type'        => 'cap',
					'title'       => _x( 'Admin Restriction', 'Modules: Restricted: Settings', 'gnetwork' ),
					'description' => _x( 'Only this role and above can access to the site\'s admin pages.', 'Modules: Restricted: Settings', 'gnetwork' ),
					'default'     => 'none',
				],
				[
					'field'       => 'access_profile',
					'type'        => 'select',
					'title'       => _x( 'Admin Profile', 'Modules: Restricted: Settings', 'gnetwork' ),
					'description' => _x( 'Whether admin profile can be accessed if the site is in restricted mode.', 'Modules: Restricted: Settings', 'gnetwork' ),
					'default'     => 'open',
					'values'      => [
						'open'   => _x( 'Open', 'Modules: Restricted: Settings', 'gnetwork' ),
						'closed' => _x( 'Restricted', 'Modules: Restricted: Settings', 'gnetwork' ),
					],
				],
				[
					'field'       => 'redirect_to_page',
					'type'        => 'page',
					'title'       => _x( 'Restricted Page', 'Modules: Restricted: Settings', 'gnetwork' ),
					'description' => _x( 'Redirects not authorized users to this page. If not selected will redirect to the login page.', 'Modules: Restricted: Settings', 'gnetwork' ),
					'none_title'  => _x( '&mdash; Login Page &mdash;', 'Modules: Restricted: Settings', 'gnetwork' ),
					'none_value'  => '0',
				],
				[
					'field'       => 'restricted_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Restricted Notice', 'Modules: Restricted: Settings', 'gnetwork' ),
					/* translators: %1$s: `%1$s` placeholder, %2$s: `%2$s` placeholder */
					'description' => sprintf( _x( 'Displays on top of the site login page. Use %1$s for the role, and %2$s for the page.', 'Modules: Restricted: Settings', 'gnetwork' ), '<code>%1$s</code>', '<code>%2$s</code>' ),
					/* translators: %1$s: `%1$s` placeholder, %2$s: `%2$s` placeholder */
					'default'     => _x( '<p>This site is restricted to users with %1$s access level. Please visit <a href="%2$s">here</a> to request access.</p>', 'Modules: Restricted: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'restricted_message',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Restricted Message', 'Modules: Restricted: Settings', 'gnetwork' ),
					/* translators: %1$s: `%1$s` placeholder, %2$s: `%2$s` placeholder */
					'description' => sprintf( _x( 'Displays on 403 status page for logged-in users. Use %1$s for the role, and %2$s for the page.', 'Modules: Restricted: Settings', 'gnetwork' ), '<code>%1$s</code>', '<code>%2$s</code>' ),
					/* translators: %1$s: `%1$s` placeholder, %2$s: `%2$s` placeholder */
					'default'     => _x( '<p>You do not have %1$s access level. Please visit <a href="%2$s">here</a> to request access.</p>', 'Modules: Restricted: Settings', 'gnetwork' ),
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( $layout = Utilities::getLayout( 'status.403' ) ) {

			/* translators: %s: restricted page path */
			HTML::desc( sprintf( _x( 'Current Layout: %s', 'Modules: Restricted: Settings', 'gnetwork' ),
				'<code>'.HTML::link( File::normalize( $layout ), URL::fromPath( $layout ), TRUE ).'</code>' ) );

		} else {

			HTML::desc( _x( 'There are no layouts available. We will use an internal instead.', 'Modules: Restricted: Settings', 'gnetwork' ) );
		}
	}

	public static function is()
	{
		return ( ! WordPress::cuc( gNetwork()->option( 'access_site', 'restricted', 'none' ) ) );
	}

	public static function enabled()
	{
		return ( 'none' != gNetwork()->option( 'access_site', 'restricted', 'none' ) );
	}

	private function get_user_feedkey( $user_id = FALSE, $generate = TRUE, $reset = FALSE )
	{
		if ( ! $user_id && ! is_user_logged_in() )
			return FALSE;

		if ( ! $user_id )
			$user_id = get_current_user_id();

		$feedkey = get_user_meta( $user_id, 'feed_key', TRUE );

		if ( $reset || ( $generate && ( empty( $feedkey ) || FALSE == $feedkey ) ) ) {

			$feedkey = $this->generate_user_feedkey();

			update_user_meta( $user_id, 'feed_key', $feedkey );
		}

		return $feedkey;
	}

	private function generate_user_feedkey()
	{
		$login = $GLOBALS['userdata']->user_login;
		$pass  = wp_generate_password( 12, TRUE, TRUE );

		return hash_hmac( 'md5', $login.$pass, wp_salt( 'auth' ) );
	}

	private function prep_notice( $notice, $role, $page = FALSE, $register = TRUE )
	{
		if ( $page )
			$link = get_page_link( $page );

		else if ( $register )
			$link = WordPress::registerURL( 'site' );

		else
			$link = '#';

		return sprintf( $notice, Settings::getUserCapList( $role ), $link );
	}

	public function get_restricted_message()
	{
		return $this->prep_notice(
			$this->options['restricted_message'],
			$this->options['access_site'],
			$this->options['redirect_to_page'],
			FALSE
		);
	}

	public function get_restricted_notice()
	{
		return $this->prep_notice(
			$this->options['restricted_notice'],
			$this->options['access_site'],
			$this->options['redirect_to_page']
		);
	}

	private function render_restricted_layout( $current_user = 0 )
	{
		if ( $layout = Utilities::getLayout( 'status.'.$this->status_code ) )
			require_once( $layout );

		else if ( $callback = $this->filters( 'default_template', [ $this, 'default_template' ] ) )
			call_user_func( $callback );

		die();
	}

	// using BuddyPress and on the register page
	public function is_bp_component()
	{
		if ( ! function_exists( 'bp_is_current_component' ) )
			return FALSE;

		if ( bp_is_current_component( 'register' ) )
			return TRUE;

		if ( bp_is_current_component( 'activate' ) )
			return TRUE;

		return FALSE;
	}

	public function admin_init()
	{
		$this->filter( 'feed_link', 2, 12 );

		if ( WordPress::cuc( $this->options['access_admin'] ) )
			return;

		if ( 'open' == $this->options['access_profile']
			&& WordPress::pageNow( 'profile.php' ) ) {

			// do nothing

			AdminBar::removeMenus( [
				'site-name',
				'my-sites',
				'blog-'.get_current_blog_id(),
				'edit',
				'new-content',
				'comments',
			] );

		} else if ( $this->options['redirect_to_page'] ) {

			WordPress::redirect( get_page_link( $this->options['redirect_to_page'] ), 302 );

		} else {

			Utilities::redirectHome();
		}
	}

	// non-admin only
	public function init()
	{
		$this->filter( 'feed_link', 2, 12 );

		if ( WordPress::cuc( $this->options['access_site'] ) )
			return;

		// blocks search engines and robots
		$this->filter( 'robots_txt' );
		$this->filter_zero( 'option_blog_public', 20 );

		$this->filter( 'login_message' );
		// $this->filter( 'status_header', 4 );
		$this->filter( 'rest_authentication_errors', 1, 999 );

		$this->action( 'template_redirect', 0, 1 );
	}

	public function feed_link( $output, $feed )
	{
		if ( is_null( $this->feed_key ) )
			$this->feed_key = $this->get_user_feedkey();

		return $this->feed_key
			? add_query_arg( 'feedkey', $this->feed_key, $output )
			: $output;
	}

	public function robots_txt( $output )
	{
		return $output.'Disallow: /'."\n";
	}

	public function status_header( $status_header, $header, $text, $protocol )
	{
		return $protocol.' '.$this->status_code.' '.HTTP::getStatusDesc( $this->status_code );
	}

	public function rest_authentication_errors( $null )
	{
		return new Error( 'restricted', $this->get_restricted_notice(), [ 'status' => $this->status_code ] );
	}

	public function login_message()
	{
		if ( ! empty( $options['restricted_notice'] ) ) {

			echo '<div id="login_error">';
				echo $this->get_restricted_notice();
			echo '</div>';
		}

		echo '<style>#backtoblog{display:none;}</style>';
	}

	public function privacy_on_link_title( $title )
	{
		return _x( 'Your site is restricted to public', 'Modules: Restricted: At a Glance', 'gnetwork' );
	}

	public function privacy_on_link_text( $content )
	{
		return _x( 'Public Access Discouraged', 'Modules: Restricted: At a Glance', 'gnetwork' );
	}

	public function dashboard_pointers( $items )
	{
		$can = WordPress::cuc( 'manage_options' );

		$items[] = HTML::tag( $can ? 'a' : 'span', [
			'href'  => $can ? $this->get_menu_url( 'restricted' ) : FALSE,
			/* translators: %s: access role */
			'title' => sprintf( _x( 'This site is restricted to users with %s access level.', 'Modules: Restricted', 'gnetwork' ), Settings::getUserCapList( $this->options['access_site'] ) ),
			'class' => '-restricted',
		], _x( 'Site is Restricted', 'Modules: Restricted', 'gnetwork' ) );

		return $items;
	}

	public function template_redirect()
	{
		if ( is_feed() || 'json' === get_query_var( 'feed' ) ) {

			if ( $this->is_restricted_feed( trim( self::req( 'feedkey' ) ) ) ) {

				foreach ( Utilities::getFeeds() as $feed )
					add_action( 'do_feed_'.$feed, [ $this, 'do_feed_restricted' ], 1, 2 );
			}

			return;
		}

		if ( $this->is_bp_component() )
			return;

		if ( $this->options['redirect_to_page'] && is_page( intval( $this->options['redirect_to_page'] ) ) )
			return;

		$current_user = get_current_user_id();

		if ( $current_user && WordPress::cuc( $this->options['access_site'] ) )
			return;

		if ( ! $current_user && ! is_front_page() && ! is_home() )
			WordPress::redirectLogin( URL::current() );

		if ( $this->options['redirect_to_page'] )
			WordPress::redirect( get_page_link( $this->options['redirect_to_page'] ), 303 );

		if ( $current_user )
			$this->render_restricted_layout( $current_user );

		WordPress::redirectLogin();
	}

	private function is_restricted_feed( $feedkey )
	{
		global $wpdb;

		if ( empty( $feedkey ) )
			return TRUE;

		$query = $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_value = %s", $feedkey );
		$user  = $wpdb->get_results( $query );

		if ( empty( $user ) )
			return TRUE;

		// valid key is sufficient
		if ( '_member_of_network' == $this->options['access_site'] )
			return FALSE;

		if ( '_member_of_site' == $this->options['access_site'] )
			return ! is_user_member_of_blog( $user );

		if ( user_can( $user, $this->options['access_site'] ) )
			return FALSE;

		return TRUE;
	}

	public function do_feed_restricted( $is_comment_feed, $feed )
	{
		if ( 'json' == $feed ) {

			// TODO: use `_json_wp_die_handler()` since WP 5.2.0

			$json = [
				'code'    => $this->status_code,
				'message' => _x( 'You are not authorized to access this site\'s feed.', 'Modules: Restricted', 'gnetwork' ),
				'data'    => [ 'status' => $this->status_code ]
			];

			if ( ! headers_sent() ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				status_header( $this->status_code );
				nocache_headers();
			}

			echo wp_json_encode( $json );

		} else {

			// TODO: use `_xml_wp_die_handler()` since WP 5.2.0

			$message = htmlspecialchars( _x( 'You are not authorized to access this site\'s feed.', 'Modules: Restricted', 'gnetwork' ) );
			$title   = htmlspecialchars( HTTP::getStatusDesc( $this->status_code ) );

			$xml = <<<EOD
<error>
	<code>{$this->status_code}</code>
	<title><![CDATA[{$title}]]></title>
	<message><![CDATA[{$message}]]></message>
	<data>
		<status>{$this->status_code}</status>
	</data>
</error>

EOD;

			if ( ! headers_sent() ) {
				header( 'Content-Type: text/xml; charset=utf-8' );
				status_header( $this->status_code );
				nocache_headers();
			}

			echo $xml;
		}

		die();
	}

	// TODO: support front-end profiles
	public function load_profile()
	{
		add_action( 'show_user_profile', [ $this, 'edit_user_profile' ] );
		add_action( 'edit_user_profile', [ $this, 'edit_user_profile' ] );
		add_action( 'personal_options_update', [ $this, 'edit_user_profile_update' ] );
		add_action( 'edit_user_profile_update', [ $this, 'edit_user_profile_update' ] );
	}

	public function edit_user_profile( $profileuser )
	{
		$feedkey = $this->get_user_feedkey( $profileuser->ID, FALSE );

		Settings::fieldSection(
			_x( 'Private Feeds', 'Modules: Restricted', 'gnetwork' ),
			_x( 'Used to access restricted site feeds.', 'Modules: Restricted', 'gnetwork' ),
			'h2'
		);

		echo '<table class="form-table">';

			$this->do_settings_field( [
				'field'       => 'restricted_feedkey',
				'type'        => 'text',
				'cap'         => 'read',
				'title'       => _x( 'Feed Access Key', 'Modules: Restricted', 'gnetwork' ),
				'description' => _x( 'The key will be used on all restricted feed URLs.', 'Modules: Restricted', 'gnetwork' ),
				'placeholder' => _x( 'Feed access key not found.', 'Modules: Restricted', 'gnetwork' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'default'     => $feedkey ?: '',
				'disabled'    => TRUE,
				'wrap'        => TRUE,
			] );

			$operations = [ 'none' => Settings::showOptionNone() ];

			if ( $feedkey ) {
				$operations['reset']  = _x( 'Reset your access key', 'Modules: Restricted', 'gnetwork' );
				$operations['remove'] = _x( 'Remove your access key', 'Modules: Restricted', 'gnetwork' );
			} else {
				$operations['generate'] = _x( 'Generate new access key', 'Modules: Restricted', 'gnetwork' );
			}

			$this->do_settings_field( [
				'field'       => 'restricted_operations',
				'name_attr'   => 'restricted_operations',
				'type'        => 'select',
				'cap'         => 'read',
				'title'       => _x( 'Key Operations', 'Modules: Restricted', 'gnetwork' ),
				'description' => _x( 'Select an operation to work with your private feed access key.', 'Modules: Restricted', 'gnetwork' ),
				'default'     => 'none',
				'values'      => $operations,
				'wrap'        => TRUE,
			] );

			if ( $feedkey ) {

				$default       = get_default_feed();
				$posts_feed    = get_feed_link( $default );
				$comments_feed = get_feed_link( 'comments_'.$default );

				$this->do_settings_field( [
					'field'  => 'restricted_posts_feed',
					'type'   => 'custom',
					'cap'    => 'read',
					'title'  => _x( 'Posts Feed for You', 'Modules: Restricted', 'gnetwork' ),
					'values' => '<code><a href="'.HTML::escapeURL( $posts_feed ).'" target="_blank">'.$posts_feed.'</a></code>',
					'wrap'   => TRUE,
				] );

				$this->do_settings_field( [
					'field'  => 'restricted_comments_feed',
					'type'   => 'custom',
					'cap'    => 'read',
					'title'  => _x( 'Comments Feed for You', 'Modules: Restricted', 'gnetwork' ),
					'values' => '<code><a href="'.HTML::escapeURL( $comments_feed ).'" target="_blank">'.$comments_feed.'</a></code>',
					'wrap'   => TRUE,
				] );
			}

		echo '</table>';
	}

	public function edit_user_profile_update( $user_id )
	{
		switch ( self::req( 'restricted_operations' ) ) {

			case 'remove':

				delete_user_meta( $user_id, 'feed_key' );

			break;
			case 'reset':
			case 'generate':

				$this->get_user_feedkey( $user_id, FALSE, TRUE );
		}
	}

	public static function get403Logout( $class = 'logout' )
	{
		$html = HTML::tag( 'a', [
			'href'  => GNETWORK_BASE,
			'title' => GNETWORK_NAME,
		], _x( 'Home Page', 'Modules: Restricted', 'gnetwork' ) );

		if ( is_user_logged_in() ) {

			if ( is_user_member_of_blog() ) {
				$html.= ' / '.HTML::tag( 'a', [
					'href'  => admin_url( 'profile.php' ),
					'title' => _x( 'View and update your profile', 'Modules: Restricted', 'gnetwork' ),
				], _x( 'Your Profile', 'Modules: Restricted', 'gnetwork' ) );
			}

			$html.= ' / '.HTML::tag( 'a', [
				'href'  => wp_logout_url(),
				'title' => _x( 'Log-out of this site', 'Modules: Restricted', 'gnetwork' ),
			], _x( 'Log Out', 'Modules: Restricted', 'gnetwork' ) );
		}

		return $class ? HTML::wrap( $html, $class ) : $html;
	}

	public static function get403Message( $class = 'message' )
	{
		if ( ! $html = gNetwork()->restricted->get_restricted_message() )
			$html = _x( 'You do not have sufficient access level.', 'Modules: Restricted', 'gnetwork' );

		return $class ? HTML::wrap( $html, $class ) : $html;
	}

	public function default_template()
	{
		$content_title   = $head_title = $this->status_code;
		$content_desc    = HTTP::getStatusDesc( $this->status_code );
		$content_message = self::get403Message( FALSE );
		$content_menu    = self::get403Logout( FALSE );

		// $retry = $this->options['retry_after']; // minutes
		$rtl   = is_rtl();

		if ( function_exists( 'nocache_headers' ) )
			nocache_headers();

		if ( function_exists( 'status_header' ) )
			status_header( $this->status_code );

		@header( "Content-Type: text/html; charset=utf-8" );
		// @header( "Retry-After: ".( $retry * 60 ) );

		if ( $header = Utilities::getLayout( 'system.header' ) )
			require_once( $header ); // to expose scope vars

		$this->actions( 'template_before' );

		HTML::h1( $content_title );
		HTML::h3( $content_desc );

		echo $rtl ? '<div dir="rtl">' : '<div>';
			echo Text::autoP( $content_message );
			echo $content_menu;
		echo '</div>';

		$this->actions( 'template_after' );

		if ( $footer = Utilities::getLayout( 'system.footer' ) )
			require_once( $footer ); // to expose scope vars
	}
}

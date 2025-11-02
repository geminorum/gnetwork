<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Maintenance extends gNetwork\Module
{
	protected $key     = 'maintenance';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( is_admin() ) {

			if ( 'none' != $this->options['access_admin'] )
				$this->action( 'admin_init', 0, 2 );

			if ( 'none' != $this->options['access_site'] )
				$this->filter_module( 'dashboard', 'pointers' );

		} else {

			if ( 'none' != $this->options['access_site'] )
				$this->action( 'init', 0, 2 );
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Maintenance', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'access_site'   => 'none', // maintenance_site
			'access_admin'  => 'none', // maintenance_admin
			'admin_notice'  => '',
			'login_message' => '',
			'status_code'   => '503',
			'retry_after'   => '10',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'access_site',
					'type'        => 'cap',
					'title'       => _x( 'Site Maintenance', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'description' => _x( 'Only this role and above can access to the site.', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'default'     => 'none',
				],
				[
					'field'       => 'access_admin',
					'type'        => 'cap',
					'title'       => _x( 'Admin Maintenance', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'description' => _x( 'Only this role and above can access to the site\'s admin pages.', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'default'     => 'none',
				],
				[
					'field'       => 'admin_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Admin Notice', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'description' => _x( 'Displays as admin notice while site is on maintenance mode.', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'default'     => _x( 'The Maintenance Mode is active.', 'Modules: Maintenance', 'gnetwork' ),
				],
				[
					'field'       => 'login_message',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Login Message', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'description' => _x( 'Displays as login message while site is on maintenance mode.', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'default'     => _x( 'The site is unavailable for scheduled maintenance.', 'Modules: Maintenance', 'gnetwork' ),
				],
				[
					'field'       => 'status_code',
					'type'        => 'select',
					'title'       => _x( 'Status Code', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'description' => _x( 'Defines HTTP status header code while the site is in maintenance mode.', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes' ),
					'dir'         => 'ltr',
					'default'     => '503',
					'values'      => Settings::statusOptions( [ 307, 403, 404, 406, 410, 451, 500, 501, 503 ] ),
				],
				[
					'field'       => 'retry_after',
					'type'        => 'select',
					'title'       => _x( 'Retry After', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'description' => _x( 'Defines HTTP status header retry after.', 'Modules: Maintenance: Settings', 'gnetwork' ),
					'default'     => '10',
					'values'      => Settings::minutesOptions(),
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( is_main_site() ) {

			echo $this->wrap_open_buttons();

				Settings::submitButton( 'store_maintenance_php', sprintf(
					/* translators: `%s`: `maintenance.php` placeholder */
					_x( 'Store as %s', 'Modules: Maintenance', 'gnetwork' ),
					'maintenance.php'
				), 'small', [
					'title' => _x( 'Tries to store available layout as WordPress core maintenance template.', 'Modules: Maintenance', 'gnetwork' ),
				] );

			echo '</p>';
		}

		if ( $layout = $this->get_maintenance_layout() ) {

			Core\HTML::desc( sprintf(
				/* translators: `%s`: maintenance page path */
				_x( 'Current Layout: %s', 'Modules: Maintenance: Settings', 'gnetwork' ),
				Core\HTML::tag( 'code', Core\HTML::link( Core\File::normalize( $layout ), Core\URL::fromPath( $layout ), TRUE ) )
			) );

		} else {

			Core\HTML::desc( _x( 'There are no layouts available. We will use an internal instead.', 'Modules: Maintenance: Settings', 'gnetwork' ) );
		}
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['store_maintenance_php'] )
			&& is_main_site() ) {

			$this->check_referer( $sub, 'settings' );

			ob_start();

				$this->render_maintenance_layout();

			$created = Core\File::putContents( 'maintenance.php', ob_get_clean(), WP_CONTENT_DIR, FALSE );

			WordPress\Redirect::doReferer( ( FALSE === $created ? 'wrong' : 'maked' ) );
		}
	}

	// non-admin only
	public function init()
	{
		// feeds are always unavailable
		foreach ( Utilities::getFeeds() as $feed )
			add_action( 'do_feed_'.$feed, [ $this, 'do_feed_feed' ], 1, 1 );

		if ( WordPress\User::cuc( $this->options['access_site'] ) )
			return;

		$this->action( 'template_redirect' );
		$this->filter( 'status_header', 4 );
		$this->filter( 'rest_authentication_errors', 1, 999 );
		$this->filter_empty_string( 'login_site_html_link' );

		if ( $this->options['login_message'] )
			$this->filter( 'login_message' );
	}

	public function admin_init()
	{
		if ( $this->options['admin_notice'] )
			$this->action( 'admin_notices' );

		if ( ! WordPress\User::cuc( $this->options['access_admin'] ) )
			Utilities::redirectHome();
	}

	public function do_feed_feed()
	{
		nocache_headers();

		status_header( $this->options['status_code'] );

		Core\HTTP::headerRetryInMinutes( $this->options['retry_after'] );

		echo '<?xml version="1.0" encoding="UTF-8"?><status>'.Core\HTTP::getStatusDesc( $this->options['status_code'] ).'</status>';

		die();
	}

	public function status_header( $status_header, $header, $text, $protocol )
	{
		return $protocol.' '.$this->options['status_code'].' '.Core\HTTP::getStatusDesc( $this->options['status_code'] );
	}

	public function admin_notices()
	{
		echo Core\HTML::warning( $this->options['admin_notice'] );
	}

	public function dashboard_pointers( $items )
	{
		$can = WordPress\User::cuc( 'manage_options' );

		$items[] = Core\HTML::tag( $can ? 'a' : 'span', [
			'href'  => $can ? $this->get_menu_url( 'maintenance' ) : FALSE,
			'title' => _x( 'The Maintenance Mode is active.', 'Modules: Maintenance', 'gnetwork' ),
			'class' => '-maintenance',
		], _x( 'In Maintenance!', 'Modules: Maintenance', 'gnetwork' ) );

		return $items;
	}

	public function login_message( $message )
	{
		return Core\HTML::wrap( Core\Text::autoP( $this->options['login_message'] ), 'message -warning' ).$message;
	}

	public function rest_authentication_errors( $null )
	{
		return new Core\Error( 'maintenance', $this->options['login_message'], [ 'status' => $this->options['status_code'] ] );
	}

	public function template_redirect()
	{
		$this->render_maintenance_layout();
		die();
	}

	private function render_maintenance_layout()
	{
		if ( $layout = $this->get_maintenance_layout() )
			require_once $layout;

		else if ( $callback = $this->filters( 'default_template', [ $this, 'default_template' ] ) )
			call_user_func( $callback );
	}

	private function get_maintenance_layout()
	{
		if ( $layout = Utilities::getLayout( 'status.'.$this->options['status_code'] ) )
			return $layout;

		// skip double check for `status.503.php`
		if ( '503' != $this->options['status_code'] && ( $layout = Utilities::getLayout( 'status.503' ) ) )
			return $layout;

		// WORKING but Disabled: `maintenance.php` lacks customizations
		// default core template for maintenance
		// if ( $layout = Utilities::getLayout( 'maintenance' ) )
		// 	return $layout;

		return FALSE;
	}

	public static function is()
	{
		return ( ! WordPress\User::cuc( gNetwork()->option( 'access_site', 'maintenance', 'none' ) ) );
	}

	public static function enabled()
	{
		return ( 'none' != gNetwork()->option( 'access_site', 'maintenance', 'none' ) );
	}

	public static function get503Message( $class = 'message', $fallback = NULL )
	{
		if ( is_null( $fallback ) )
			$fallback = _x( 'The site is unavailable for scheduled maintenance.',
				'Modules: Maintenance: Default 503 Message', 'gnetwork' );

		$html = gNetwork()->option( 'login_message', 'maintenance', $fallback );

		return $class ? Core\HTML::wrap( $html, $class ) : $html;
	}

	public function default_template()
	{
		$content_title   = $head_title = $this->options['status_code'];
		$content_desc    = Core\HTTP::getStatusDesc( $this->options['status_code'] );
		$content_message = self::get503Message( FALSE );
		$content_menu    = ''; // FIXME
		$head_callback   = '';
		$body_class      = '';

		$retry = $this->options['retry_after']; // minutes
		$rtl   = is_rtl();

		if ( function_exists( 'nocache_headers' ) )
			nocache_headers();

		if ( function_exists( 'status_header' ) )
			status_header( $this->options['status_code'] );

		@header( "Content-Type: text/html; charset=utf-8" );
		@header( "Retry-After: ".( $retry * 60 ) );

		if ( $header = Utilities::getLayout( 'system.header' ) )
			require_once $header; // to expose scope vars

		$this->actions( 'template_before' );

		Core\HTML::h1( $content_title );
		Core\HTML::h3( $content_desc );

		echo $rtl ? '<div dir="rtl">' : '<div>';
			echo Core\Text::autoP( $content_message );
			echo $content_menu;
		echo '</div>';

		$this->actions( 'template_after' );

		if ( $footer = Utilities::getLayout( 'system.footer' ) )
			require_once $footer; // to expose scope vars
	}
}

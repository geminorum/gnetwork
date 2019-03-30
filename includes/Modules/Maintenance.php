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

class Maintenance extends gNetwork\Module
{

	protected $key     = 'maintenance';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( is_admin() ) {

			if ( 'none' != $this->options['maintenance_admin'] )
				$this->action( 'admin_init', 0, 2 );

			if ( 'none' != $this->options['maintenance_site'] )
				$this->filter_module( 'dashboard', 'pointers' );

		} else {

			if ( 'none' != $this->options['maintenance_site'] )
				$this->action( 'init', 0, 2 );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Maintenance', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'maintenance_site'  => 'none',
			'maintenance_admin' => 'none',
			'admin_notice'      => '',
			'login_message'     => '',
			'status_code'       => '503',
			'retry_after'       => '10',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'maintenance_site',
					'type'        => 'cap',
					'title'       => _x( 'Site Maintenance', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Only this role and above can access to the site.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'none',
				],
				[
					'field'       => 'maintenance_admin',
					'type'        => 'cap',
					'title'       => _x( 'Admin Maintenance', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Only this role and above can access to the site\'s admin pages.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'none',
				],
				[
					'field'       => 'admin_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Admin Notice', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays as admin notice while site is on maintenance mode.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'The Maintenance Mode is active.', 'Modules: Maintenance', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'login_message',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Login Message', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays as login message while site is on maintenance mode.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'The site is unavailable for scheduled maintenance.', 'Modules: Maintenance', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'status_code',
					'type'        => 'select',
					'title'       => _x( 'Status Code', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Defines HTTP status header code while the site is in maintenance mode.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes' ),
					'dir'         => 'ltr',
					'default'     => '503',
					'values'      => [
						'307' => '307 Temporary Redirect',
						'403' => '403 Forbidden',
						'404' => '404 Not Found',
						'406' => '406 Not Acceptable',
						'410' => '410 Gone',
						'451' => '451 Unavailable For Legal Reasons',
						'500' => '500 Internal Server Error',
						'501' => '501 Not Implemented',
						'503' => '503 Service Unavailable',
					],
				],
				[
					'field'       => 'retry_after',
					'type'        => 'select',
					'title'       => _x( 'Retry After', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Defines HTTP status header retry after.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '10',
					'values'      => Settings::minutesOptions(),
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		echo $this->wrap_open_buttons();

			Settings::submitButton( 'store_maintenance_php', _x( 'Store as maintenance.php', 'Modules: Maintenance', GNETWORK_TEXTDOMAIN ), 'small', [
				'title' => _x( 'Tries to store available layout as WordPress core maintenance.php', 'Modules: Maintenance', GNETWORK_TEXTDOMAIN ),
			] );

		echo '</p>';

		if ( $layout = $this->get_maintenance_layout() ) {

			HTML::desc( sprintf( _x( 'Current Layout: %s', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
				'<code>'.HTML::link( File::normalize( $layout ), URL::fromPath( $layout ), TRUE ).'</code>' ) );

		} else {

			HTML::desc( _x( 'There are no layouts available. We will use an internal instead.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ) );
		}
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['store_maintenance_php'] ) ) {

			$this->check_referer( $sub );

			ob_start();

				$this->render_maintenance_layout();

			$content = ob_get_clean();
			$created = File::putContents( 'maintenance.php', $content, WP_CONTENT_DIR, FALSE );

			WordPress::redirectReferer( ( FALSE === $created ? 'wrong' : 'maked' ) );
		}
	}

	// non-admin only
	public function init()
	{
		// feeds are always unavailable
		foreach ( Utilities::getFeeds() as $feed )
			add_action( 'do_feed_'.$feed, [ $this, 'do_feed_feed' ], 1, 1 );

		if ( WordPress::cuc( $this->options['maintenance_site'] ) )
			return;

		$this->action( 'template_redirect' );
		$this->filter( 'status_header', 4 );
		$this->filter( 'rest_authentication_errors', 1, 999 );

		if ( $this->options['login_message'] )
			$this->filter( 'login_message' );
	}

	public function admin_init()
	{
		if ( $this->options['admin_notice'] )
			$this->action( 'admin_notices' );

		if ( ! WordPress::cuc( $this->options['maintenance_admin'] ) )
			Utilities::redirectHome();
	}

	public function do_feed_feed()
	{
		nocache_headers();

		status_header( $this->options['status_code'] );

		HTTP::headerRetryInMinutes( $this->options['retry_after'] );

		echo '<?xml version="1.0" encoding="UTF-8"?><status>'.HTTP::getStatusDesc( $this->options['status_code'] ).'</status>';

		die();
	}

	public function status_header( $status_header, $header, $text, $protocol )
	{
		return $protocol.' '.$this->options['status_code'].' '.HTTP::getStatusDesc( $this->options['status_code'] );
	}

	public function admin_notices()
	{
		echo HTML::warning( $this->options['admin_notice'] );
	}

	public function dashboard_pointers( $items )
	{
		$can = WordPress::cuc( 'manage_options' );

		$items[] = HTML::tag( $can ? 'a' : 'span', [
			'href'  => $can ? $this->get_menu_url( 'maintenance' ) : FALSE,
			'title' => _x( 'The Maintenance Mode is active.', 'Modules: Maintenance', GNETWORK_TEXTDOMAIN ),
			'class' => '-maintenance',
		], _x( 'In Maintenance!', 'Modules: Maintenance', GNETWORK_TEXTDOMAIN ) );

		return $items;
	}

	public function login_message( $message )
	{
		return HTML::wrap( Text::autoP( $this->options['login_message'] ), 'message -warning' ).$message;
	}

	public function rest_authentication_errors( $null )
	{
		return new Error( 'maintenance', $this->options['login_message'], [ 'status' => $this->options['status_code'] ] );
	}

	public function template_redirect()
	{
		$this->render_maintenance_layout();
		die();
	}

	private function render_maintenance_layout()
	{
		if ( $layout = $this->get_maintenance_layout() )
			require_once( $layout );

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
		return ( ! WordPress::cuc( gNetwork()->option( 'maintenance_site', 'maintenance', 'none' ) ) );
	}

	public static function enabled()
	{
		return ( 'none' != gNetwork()->option( 'maintenance_site', 'maintenance', 'none' ) );
	}

	public static function get503Message( $class = 'message', $fallback = NULL )
	{
		if ( is_null( $fallback ) )
			$fallback = _x( 'The site is unavailable for scheduled maintenance.',
				'Modules: Maintenance: Default 503 Message', GNETWORK_TEXTDOMAIN );

		$html = gNetwork()->option( 'login_message', 'maintenance', $fallback );

		return $class ? HTML::wrap( $html, $class ) : $html;
	}

	public function default_template()
	{
		$content_title   = $head_title = $this->options['status_code'];
		$content_desc    = HTTP::getStatusDesc( $this->options['status_code'] );
		$content_message = self::get503Message( FALSE );
		$content_menu    = ''; // FIXME

		$retry = $this->options['retry_after']; // minutes
		$rtl   = is_rtl();

		if ( function_exists( 'nocache_headers' ) )
			nocache_headers();

		if ( function_exists( 'status_header' ) )
			status_header( $this->options['status_code'] );

		@header( "Content-Type: text/html; charset=utf-8" );
		@header( "Retry-After: ".( $retry * 60 ) );

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

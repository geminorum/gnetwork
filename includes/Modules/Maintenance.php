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
				$this->action( 'admin_init' );

			if ( 'none' != $this->options['maintenance_site'] )
				$this->action( 'activity_box_end', 0, 12 );

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
		if ( $template = self::getTemplate() )
			HTML::desc( sprintf( _x( 'Current Template: %s', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
				'<code>'.HTML::link( File::normalize( $template ), URL::fromPath( $template ), TRUE ).'</code>' ) );

		else
			HTML::desc( _x( 'There are no templates available. We will use an internal instead.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ) );
	}

	public function init()
	{
		if ( ! WordPress::cuc( $this->options['maintenance_site'] ) ) {
			$this->action( 'template_redirect' );
			$this->filter( 'status_header', 4 );

			if ( $this->options['login_message'] )
				$this->filter( 'login_message' );

			foreach ( Utilities::getFeeds() as $feed )
				add_action( 'do_feed_'.$feed, [ $this, 'do_feed_feed' ], 1, 1 );

			$this->filter( 'rest_authentication_errors', 1, 999 );
		}
	}

	public function admin_init()
	{
		$this->action( 'admin_notices' );

		if ( 'profile.php' == $GLOBALS['pagenow'] )
			return;

		if ( WordPress::cuc( $this->options['maintenance_admin'] ) )
			return;

		WordPress::redirect( get_edit_profile_url( get_current_user_id() ) );
	}

	public function do_feed_feed()
	{
		nocache_headers();

		status_header( $this->options['status_code'] );

		HTTP::headerRetryInMinutes( $this->options['retry_after'] );

		echo '<?xml version="1.0" encoding="UTF-8"?><status>'.get_status_header_desc( $this->options['status_code'] ).'</status>';

		die();
	}

	public function status_header( $status_header, $header, $text, $protocol )
	{
		if ( current_user_can( 'manage_options' ) )
			return $status_header;

		return $protocol.' '.$this->options['status_code'].' '.get_status_header_desc( $this->options['status_code'] );
	}

	public function admin_notices()
	{
		if ( $this->options['admin_notice'] && ! empty( $this->options['admin_notice'] )  )
			echo HTML::warning( $this->options['admin_notice'] );
	}

	public function activity_box_end()
	{
		echo $this->wrap( '<p>'._x( 'The Maintenance Mode is active.', 'Modules: Maintenance', GNETWORK_TEXTDOMAIN ).'</p>', '-maintenance-mode' );
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
		if ( is_user_logged_in() ) {

			if ( WordPress::cuc( $this->options['maintenance_site'] ) )
				return;

			WordPress::redirect( get_edit_profile_url( get_current_user_id() ) );

		} else {

			if ( $template = self::getTemplate() ) {
				require_once( $template );

			} else {

				// FIXME: probably, Not works for themes because of the fire order
				$default_template = $this->filters( 'default_template', [ $this, 'template_503' ] );
				call_user_func_array( $default_template, [ TRUE ] );
			}

			die();
		}
	}

	public static function getTemplate()
	{
		if ( $override = apply_filters( 'gnetwork_maintenance_forced_template', FALSE ) )
			return $override;

		else if ( ! is_admin() && locate_template( '503.php' ) )
			return locate_template( '503.php' );

		else if ( file_exists( WP_CONTENT_DIR.'/503.php' ) )
			return WP_CONTENT_DIR.'/503.php';

		else if ( file_exists( WP_CONTENT_DIR.'/maintenance.php' ) )
			return WP_CONTENT_DIR.'/maintenance.php';

		return FALSE;
	}

	public static function get503Message( $class = 'message', $fallback = NULL )
	{
		if ( is_null( $fallback ) )
			$fallback = _x( 'The site is unavailable for scheduled maintenance.',
				'Modules: Maintenance: Default 503 Message', GNETWORK_TEXTDOMAIN );

		$html = gNetwork()->option( 'login_message', 'maintenance', $fallback );

		if ( $class )
			$html = HTML::tag( 'div', [
				'class' => $class,
			], $html );

		return $html;
	}

	// FIXME: use self::get503Message()
	public function template_503( $logged_in = FALSE )
	{
		$title   = '503';
		$retry   = '30'; // minutes
		$message = self::get503Message( FALSE );
		$rtl     = is_rtl();

		if ( function_exists( 'nocache_headers' ) )
			nocache_headers();

		if ( function_exists( 'status_header' ) )
			status_header( 503 );

		@header( "Content-Type: text/html; charset=utf-8" );
		@header( "Retry-After: ".( $retry * 60 ) );

?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $title; ?></title>
<style type="text/css">
:root {
  font-size: calc(1vw + 1vh + .5vmin);
}
html, body, .wrap {
  height: 100%;
  margin: 0;
}
body {
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	color: #333;
	background: #f7f7f7;
}
.wrap {
	text-align: center;
	display: -webkit-flex;
	display: flex;
	-webkit-align-items: center;
	align-items: center;
	-webkit-justify-content: center;
	justify-content: center;
}
h1 { color: #d9534f; }
h1, h3 { margin: 0; }
</style>
</head><body>
<div class="wrap"><div>

<h1>503</h1>
<h3>Service Unavailable</h3>

<?php echo $rtl ? '<div dir="rtl">' : '<div>';
echo Text::autoP( $message ); ?>

</div></div></div>
</body></html><?php
	}
}

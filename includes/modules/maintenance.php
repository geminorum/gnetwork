<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Maintenance extends ModuleCore
{

	protected $key     = 'maintenance';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( is_admin() ) {

			if ( 'none' != $this->options['maintenance_admin'] )
				add_action( 'admin_init', array( $this, 'admin_init' ) );

		} else {

			if ( 'none' != $this->options['maintenance_site'] )
				add_action( 'init', array( $this, 'init' ), 2 );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Maintenance', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function init()
	{
		if ( ! WordPress::cuc( $this->options['maintenance_site'] ) ) {
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
			add_filter( 'status_header', array( $this, 'status_header' ), 10, 4 );
			add_filter( 'login_message', array( $this, 'login_message' ) );

			foreach ( Utilities::getFeeds() as $feed )
				add_action( 'do_feed_'.$feed, array( $this, 'do_feed_feed' ), 1, 1 );
		}
	}

	public function admin_init()
	{
		if ( ! WordPress::cuc( $this->options['maintenance_admin'] ) ) {
			add_action( 'admin_init', array( $this, 'template_redirect' ) );
			add_filter( 'status_header', array( $this, 'status_header' ), 10, 4 );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	public function default_options()
	{
		return array(
			'maintenance_site'  => 'none',
			'maintenance_admin' => 'none',
			'admin_notice'      => '',
			'login_message'     => '',
			'status_code'       => '503',
			'retry_after'       => '10',
		);
	}

	public function default_settings()
	{
		$template = self::getTemplate();

		return array(
			'_general' => array(
				array(
					'field'  => 'current_template',
					'type'   => 'custom',
					'title'  => _x( 'Current Template', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'values' => ( $template ? '<p class="description code"><code>'. wp_normalize_path( $template ).'</code></p>' :
						'<p class="description">'._x( 'There are no templates available. We will use an internal instead.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ).'</p>' ),
				),
				array(
					'field'       => 'maintenance_site',
					'type'        => 'cap',
					'title'       => _x( 'Site Maintenance', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Selected and above can access to the site.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'none',
				),
				array(
					'field'       => 'maintenance_admin',
					'type'        => 'cap',
					'title'       => _x( 'Admin Maintenance', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Selected and above can access to the admin.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'none',
				),
				array(
					'field'       => 'admin_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Admin Notice', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'The admin notice while site is on maintenance. Leave empty to disable.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'The Maintenance Mode is active.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'large-text', 'code-text' ),
				),
				array(
					'field'       => 'login_message',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Login Message', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'The login message while site is on maintenance. Leave empty to disable.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'The site is unavailable for scheduled maintenance.', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'large-text', 'code-text' ),
				),
				array(
					'field'       => 'status_code',
					'type'        => 'select',
					'title'       => _x( 'Status Code', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'HTTP status header code', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes' ),
					'dir'         => 'ltr',
					'default'     => '503',
					'values'      => array(
						'307' => '307 Temporary Redirect',
						'403' => '403 Forbidden',
						'404' => '404 Not Found',
						'406' => '406 Not Acceptable',
						'410' => '410 Gone',
						'451' => '451 Unavailable For Legal Reasons',
						'500' => '500 Internal Server Error',
						'501' => '501 Not Implemented',
						'503' => '503 Service Unavailable',
					),
				),
				array(
					'field'       => 'retry_after',
					'type'        => 'select',
					'title'       => _x( 'Retry After', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'HTTP status header retry after', 'Modules: Maintenance: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '10',
					'values'      => Settings::minutesOptions(),
				),
			),
		);
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
			HTML::notice( $this->options['admin_notice'], 'error' );
	}

	public function login_message()
	{
		if ( $this->options['login_message'] && ! empty( $this->options['login_message'] ) )
			echo '<div id="login_error">'.wpautop( $this->options['login_message'] ).'</div>';
			// HTML::notice( $this->options['login_message'], 'error' );
	}

	public function template_redirect()
	{
		if ( is_user_logged_in() ) {

			if ( is_admin() ) {
				global $pagenow;

				if ( 'profile.php' == $pagenow )
					return;

				if ( WordPress::cuc( $this->options['maintenance_admin'] ) )
					return;

			} else if ( WordPress::cuc( $this->options['maintenance_site'] ) ) {
				return;
			}

			wp_redirect( get_edit_profile_url( get_current_user_id() ) );

		} else {

			if ( $template = self::getTemplate() ) {
				require_once( $template );

			} else {

				// FIXME: probably, Not works for themes because of the fire order
				$default_template = $this->filters( 'default_template', array( $this, 'template_503' ) );
				call_user_func_array( $default_template, array( TRUE ) );
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
			$html = HTML::tag( 'div', array(
				'class' => $class,
			), $html );

		return $html;
	}

	// FIXME: use self::get503Message()
	public function template_503( $logged_in = FALSE )
	{
		$protocol = $_SERVER["SERVER_PROTOCOL"];
		if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
			$protocol = 'HTTP/1.0';

		// header( "$protocol 503 Service Unavailable", TRUE, 503 );
		// header( 'Content-Type: text/html; charset=utf-8' );
		// header( 'Retry-After: 600' );

		// http://wptip.me/wordpress-maintenance-mode-without-a-plugin
		$headers = array(
			'Content-Type'  => 'text/html; charset=utf-8',
			'Retry-After'   => '600',
			'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
			'Last-Modified' => gmdate('D, d M Y H:i:s').' GMT',
			'Cache-Control' => 'no-cache, must-revalidate, max-age=0',
			'Pragma'        => 'no-cache',
		);

		header( "HTTP/1.1 503 Service Unavailable", TRUE, 503 );
		HTTP::headers( $headers );

		?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fa">
<head><title>503 Service Unavailable</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
body {
	background-color: #fff;
	color: gray;
	direction: rtl;
}

.bo {
	position: absolute;
	margin: -150px 0pt 0pt -300px;
	width: 600px;
	height: 300px;
	top: 50%;
	left: 50%;
	font: 0.9em tahoma,courier new,monospace;
	text-align: center;
	b1order:solid 1px gray;
	b1order-radius:2px;
}

a {
	text-decoration: none;
}
</style>
</head><body>
<div class="bo">
	<br />
	<br />
	<br />
	<br />
	<br />
	<br />
	<br />
	<h3 dir="ltr">503 Service Unavailable</h3>
</div>
</body></html> <?php
	}
}

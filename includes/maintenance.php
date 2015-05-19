<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkMaintenance extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = 'maintenance';

	public function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'maintenance',
			__( 'Maintenance', GNETWORK_TEXTDOMAIN ),
			array( & $this, 'settings' )
		);

		add_action( 'init', array( & $this, 'init' ), 2 );
		add_action( 'admin_init', array( & $this, 'admin_init' ) );
	}

    public function init()
    {
		if ( ! self::cuc( $this->options['maintenance_site'] ) ) {
			add_action( 'template_redirect', array( & $this, 'template_redirect' ) );
			add_filter( 'status_header', array( & $this, 'status_header' ), 10, 4 );
			add_filter( 'login_message', array( & $this, 'login_message' ) );

			foreach ( array ( 'rdf', 'rss', 'rss2', 'atom' ) as $feed )
				add_action( 'do_feed_'.$feed, create_function( '', 'die( \'<?xml version="1.0" encoding="UTF-8"?><status>Service unavailable</status>\' );' ), 1, 1 );
		}
	}

	public function admin_init()
	{
		if ( ! self::cuc( $this->options['maintenance_admin'] ) ) {
			add_action( 'admin_init', array( & $this, 'template_redirect' ) );
			add_filter( 'status_header', array( & $this, 'status_header' ), 10, 4 );
			add_action( 'admin_notices', array( & $this, 'admin_notices' ) );
		}
	}

	public function settings( $sub = null )
	{
		if ( 'maintenance' == $sub ) {
			$this->update( $sub );
			add_action( 'gnetwork_admin_settings_sub_maintenance', array( & $this, 'settings_html' ), 10, 2 );
			$this->register_settings();
		}
	}

	public function default_settings()
	{
		$template = self::get_template();
		return array(
			'_general' => array(
				array(
					'field' => 'current_template',
					'type' => 'custom',
					'title' => __( 'Current Template', GNETWORK_TEXTDOMAIN ),
					'values' => ( $template ? '<p class="description code"><code>'.$template.'</code></p>' :
						__( 'There are no templates available. We will use an internal instead.', GNETWORK_TEXTDOMAIN ) ),
				),
				array(
					'field' => 'maintenance_site',
					'type' => 'roles',
					'title' => __( 'Site Maintenance', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Selected and above can access to the site.', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field' => 'maintenance_admin',
					'type' => 'roles',
					'title' => __( 'Admin Maintenance', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Selected and above can access to the admin.', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field' => 'admin_notice',
					'type' => 'textarea',
					'title' => __( 'Admin Notice', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'The admin notice while site is on maintenance. Leave empty to disable.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
					'class' => 'large-text code',
				),
				array(
					'field' => 'login_message',
					'type' => 'textarea',
					'title' => __( 'Login Message', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'The login message while site is on maintenance. Leave empty to disable.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
					'class' => 'large-text code',
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
            'maintenance_site' => 'none',
			'maintenance_admin' => 'none',
            'admin_notice' => __( 'The Maintenance Mode is active.', GNETWORK_TEXTDOMAIN ),
            'login_message' => __( 'The site is unavailable for scheduled maintenance.', GNETWORK_TEXTDOMAIN ),
        );
    }

	public function status_header( $status_header, $header, $text, $protocol )
	{
		if ( ! is_user_logged_in()
			|| ! current_user_can( 'manage_options' )  )
				return "$protocol 503 Service Unavailable";
	}

	public function admin_notices()
	{
		if ( $this->options['admin_notice'] && ! empty( $this->options['admin_notice'] )  )
			gNetworkUtilities::notice( $this->options['admin_notice'], 'error' );
	}

	public function login_message()
	{
		if ( $this->options['login_message'] && ! empty( $this->options['login_message'] ) )
			echo '<div id="login_error">'.wpautop( $this->options['login_message'] ).'</div>';
			//gNetworkUtilities::notice( $this->options['login_message'], 'error' );
	}

	public function template_redirect()
	{
		if ( is_user_logged_in() ) {

			if ( is_admin() ) {
				global $pagenow;

				if ( 'profile.php' == $pagenow )
					return;

				if ( self::cuc( $this->options['maintenance_admin'] ) )
					return;

			} else if ( self::cuc( $this->options['maintenance_site'] ) ) {
				return;
			}

			wp_redirect( get_edit_profile_url( get_current_user_id() ) );

		} else {

			$template = self::get_template();
			if ( false !== $template ) {
				require_once( $template );

			} else {

				$default_template = apply_filters( 'gnetwork_maintenance_default_template', array( & $this, 'template_503' ) ); // probably, Not works for themes because of the fire order
				call_user_func_array( $default_template, array( true ) );

			}
			die();

		}
	}

	public static function get_template()
	{
		$forced_template = apply_filters( 'gnetwork_maintenance_forced_template', false );

		if ( false !== $forced_template )
			return $forced_template;

		elseif ( ! is_admin() && locate_template( '503.php' ) )
			return locate_template( '503.php' );

		elseif ( file_exists( WP_CONTENT_DIR.'/503.php' ) )
			return WP_CONTENT_DIR.'/503.php';

		elseif ( file_exists( WP_CONTENT_DIR.'/maintenance.php' ) )
			return WP_CONTENT_DIR.'/maintenance.php';

		return false;
	}

	public function template_503( $logged_in = false )
	{
		$protocol = $_SERVER["SERVER_PROTOCOL"];
		if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
			$protocol = 'HTTP/1.0';

		//header( "$protocol 503 Service Unavailable", true, 503 );
		//header( 'Content-Type: text/html; charset=utf-8' );
		//header( 'Retry-After: 600' );

		// http://wptip.me/wordpress-maintenance-mode-without-a-plugin
		$headers = array(
			'Content-Type'  => 'text/html; charset=utf-8',
			'Retry-After'   => '600',
			'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
			'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
			'Cache-Control' => 'no-cache, must-revalidate, max-age=0',
			'Pragma'        => 'no-cache',
		);

		header( "HTTP/1.1 503 Service Unavailable", true, 503 );
		gNetworkUtilities::headers( $headers );

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

// http://codecanyon.net/item/closed-beta-wordpress-plugin/3395536

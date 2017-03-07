<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Notify extends ModuleCore
{

	protected $key  = 'notify';
	protected $ajax = TRUE;
	protected $cron = TRUE;

	protected function setup_actions()
	{
		add_filter( 'wpmu_welcome_notification', array( $this, 'wpmu_welcome_notification' ), 10, 5 );
		add_filter( 'auto_core_update_send_email', array( $this, 'auto_core_update_send_email' ), 12, 4 );

		if ( file_exists( GNETWORK_DIR.'includes/misc/notify-pluggable.php' ) )
			require_once( GNETWORK_DIR.'includes/misc/notify-pluggable.php' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Notify', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'disable_new_user_admin'  => '1',
			'disable_password_change' => '1',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'disable_new_user_admin',
					'title'       => _x( 'New User', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Notify the blog admin of a newly-registered user', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
					'values'      => Settings::reverseEnabled(),
				),
				array(
					'field'       => 'disable_password_change',
					'title'       => _x( 'Password Reset', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Notify the blog admin of a user changing password', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
					'values'      => Settings::reverseEnabled(),
				),
			),
		);
	}

	// filter whether to bypass the welcome email after site activation.
	public function wpmu_welcome_notification( $blog_id, $user_id, $password, $title, $meta )
	{
		if ( WordPress::isSuperAdmin( $user_id ) )
			return FALSE;

		return $blog_id;
	}

	// FIXME: apparently not working!
	// filter whether to send an email following an automatic background core update.
	// http://codex.wordpress.org/Configuring_Automatic_Background_Updates
	public function auto_core_update_send_email( $true, $type, $core_update, $result )
	{
		if ( in_array( $type, array( 'fail', 'critical' ) ) )
			return TRUE;

		return FALSE;
	}

	// pluggable core function
	// Email login credentials to a newly-registered user.
	// CHANGED: we opt-out notifying the admin
	public function wp_new_user_notification( $user_id, $deprecated = NULL, $notify = '' )
	{
		global $wpdb, $wp_hasher;

		$blog = $this->blogname();
		$user = get_userdata( $user_id );

		if ( ! $this->options['disable_new_user_admin']
			&& 'user' !== $notify ) {

			$switched_locale = switch_to_locale( get_locale() );

			$message  = sprintf( __( 'New user registration on your site %s:' ), $blog )."\r\n\r\n";
			$message .= sprintf( __( 'Username: %s' ), $user->user_login )."\r\n\r\n";
			$message .= sprintf( __( 'Email: %s' ), $user->user_email )."\r\n";

			@wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] New User Registration' ), $blog ), $message );

			if ( $switched_locale )
				restore_previous_locale();
		}

		if ( 'admin' === $notify
			|| ( empty( $deprecated ) && empty( $notify ) ) )
				return;

		$key = wp_generate_password( 20, FALSE );

		do_action( 'retrieve_password_key', $user->user_login, $key );

		if ( empty( $wp_hasher ) )
			$wp_hasher = new \PasswordHash( 8, TRUE );

		$hashed = time().':'.$wp_hasher->HashPassword( $key );
		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

		$switched_locale = switch_to_locale( get_user_locale( $user ) );

		$message  = sprintf( __( 'Username: %s' ), $user->user_login )."\r\n\r\n";
		$message .= __( 'To set your password, visit the following address:' )."\r\n\r\n";
		$message .= '<'.network_site_url( "wp-login.php?action=rp&key=$key&login=".rawurlencode( $user->user_login ), 'login' ).">\r\n\r\n";
		$message .= wp_login_url()."\r\n";

		wp_mail( $user->user_email, sprintf( __( '[%s] Your username and password info' ), $blog ), $message );

		if ( $switched_locale )
			restore_previous_locale();
	}

	// pluggable core function
	// Notify the blog admin of a user changing password, normally via email.
	public function wp_password_change_notification( $user )
	{
		if ( $this->options['disable_password_change'] )
			return;

		if ( 0 === strcasecmp( $user->user_email, get_option( 'admin_email' ) ) )
			return;

		$message = sprintf( __( 'Password changed for user: %s' ), $user->user_login )."\r\n";
		wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] Password Changed' ), $this->blogname() ), $message );
	}

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	public function blogname()
	{
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		// return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	}
}

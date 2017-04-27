<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\WordPress;

class Notify extends gNetwork\Module
{

	protected $key  = 'notify';
	protected $ajax = TRUE;
	protected $cron = TRUE;

	protected function setup_actions()
	{
		$this->filter( 'wpmu_welcome_notification', 5 );
		$this->filter( 'auto_core_update_send_email', 4, 12 );

		if ( file_exists( GNETWORK_DIR.'includes/misc/notify-pluggable.php' ) )
			require_once( GNETWORK_DIR.'includes/misc/notify-pluggable.php' );

		if ( ! is_multisite() )
			return;

		if ( $this->options['signup_blog_subject'] )
			$this->filter( 'wpmu_signup_blog_notification_email', 8, 12 );

		if ( $this->options['signup_blog_message'] )
			$this->filter( 'wpmu_signup_blog_notification_subject', 8, 12 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Notify', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'disable_new_user_admin'  => '1',
			'disable_password_change' => '1',
			'signup_blog_subject'     => '',
			'signup_blog_message'     => '',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'disable_new_user_admin',
					'title'       => _x( 'New User', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Notify the blog admin of a newly-registered user', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
					'values'      => Settings::reverseEnabled(),
				],
				[
					'field'       => 'disable_password_change',
					'title'       => _x( 'Password Reset', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Notify the blog admin of a user changing password', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
					'values'      => Settings::reverseEnabled(),
				],
			],
		];

		if ( is_multisite() )
			$settings['signup'] = [
				[
					'field'       => 'signup_blog_subject',
					'type'        => 'text',
					'title'       => _x( 'New Blog Subject', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Subject of the new blog notification email. Leave empty to use defaults.', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => _x( '[%1$s] Activate %2$s', 'New site notification email subject' ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Network name, <code>%2$s</code>: New site URL' ),
				],
				[
					'field'       => 'signup_blog_message',
					'type'        => 'textarea',
					'title'       => _x( 'New Blog Message', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Message content of the new blog notification email. Leave empty to use defaults.', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => __( "To activate your blog, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login.\n\nAfter you activate, you can visit your site here:\n\n%s" ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Activate URL, <code>%2$s</code>: New site URL, <code>%3$s</code>: Activation Key' ),
				],
			];

		return $settings;
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
		Logger::ALERT( sprintf( 'NOTIFY: automatic background core update: %s', $type ) );

		if ( in_array( $type, [ 'fail', 'critical' ] ) )
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

		Logger::ALERT( sprintf( 'NOTIFY: New user registration: %s', $user->user_login ) );

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

		if ( empty( $wp_hasher ) ) {
			require_once( ABSPATH.WPINC.'/class-phpass.php' );
			$wp_hasher = new \PasswordHash( 8, TRUE );
		}

		$hashed = time().':'.$wp_hasher->HashPassword( $key );
		$wpdb->update( $wpdb->users, [ 'user_activation_key' => $hashed ], [ 'user_login' => $user->user_login ] );

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
		Logger::ALERT( sprintf( 'NOTIFY: Password changed: %s', $user->user_login ) );

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

	public function wpmu_signup_blog_notification_subject( $subject, $domain, $path, $title, $user_login, $user_email, $key, $meta )
	{
		return $this->options['signup_blog_subject'];
	}

	public function wpmu_signup_blog_notification_email( $message, $domain, $path, $title, $user_login, $user_email, $key, $meta )
	{
		return $this->options['signup_blog_message'];
	}
}

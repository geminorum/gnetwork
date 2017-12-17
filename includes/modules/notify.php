<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

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

		if ( $this->options['disable_new_user_admin'] )
			add_filter( 'bp_core_send_user_registration_admin_notification', '__return_false' );

		if ( ! is_multisite() )
			return;

		if ( $this->options['signup_user_subject'] )
			$this->filter( 'wpmu_signup_user_notification_subject', 5, 12 );

		if ( $this->options['signup_user_email'] )
			$this->filter( 'wpmu_signup_user_notification_email', 5, 12 );

		if ( $this->options['signup_blog_subject'] )
			$this->filter( 'wpmu_signup_blog_notification_subject', 8, 12 );

		if ( $this->options['signup_blog_email'] )
			$this->filter( 'wpmu_signup_blog_notification_email', 8, 12 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Notify', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
	}

	public function default_options()
	{
		return [
			'disable_new_user_admin'  => 0,
			'disable_password_change' => 0,
			'signup_user_subject'     => '',
			'signup_user_email'       => '',
			'signup_blog_subject'     => '',
			'signup_blog_email'       => '',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'disable_new_user_admin',
					'title'       => _x( 'New User Email', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Notify the blog admin of a newly-registered user', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Settings::reverseEnabled(),
				],
				[
					'field'       => 'disable_password_change',
					'title'       => _x( 'Password Reset Email', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Notify the blog admin of a user changing password', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Settings::reverseEnabled(),
				],
			],
		];

		if ( is_multisite() )
			$settings['signup'] = [
				[
					'field'       => 'signup_user_subject',
					'type'        => 'text',
					'title'       => _x( 'New User Email Subject', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Subject of the notification email of new user signup. Leave empty to use defaults.', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => _x( '[%1$s] Activate %2$s', 'New user notification email subject' ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Network name, <code>%2$s</code>: User login name' ),
				],
				[
					'field'       => 'signup_user_email',
					'type'        => 'textarea',
					'title'       => _x( 'New User Email Message', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Message content of the notification email for new user sign-up. Leave empty to use defaults.', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => __( "To activate your user, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login." ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Activate URL' ),
					'field_class' => [ 'large-text' ],
				],
				[
					'field'       => 'signup_blog_subject',
					'type'        => 'text',
					'title'       => _x( 'New Blog Email Subject', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Subject of the new blog notification email. Leave empty to use defaults.', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => _x( '[%1$s] Activate %2$s', 'New site notification email subject' ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Network name, <code>%2$s</code>: New site URL' ),
				],
				[
					'field'       => 'signup_blog_email',
					'type'        => 'textarea',
					'title'       => _x( 'New Blog Email Message', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Message content of the new blog notification email. Leave empty to use defaults.', 'Modules: Notify: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => __( "To activate your blog, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login.\n\nAfter you activate, you can visit your site here:\n\n%s" ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Activate URL, <code>%2$s</code>: New site URL, <code>%3$s</code>: Activation Key' ),
					'field_class' => [ 'large-text' ],
				],
			];

		return $settings;
	}

	public function settings_sidebox( $sub, $uri )
	{
		echo $this->wrap_open_buttons( '-sidebox' );

			Settings::submitButton( 'test_signup_user', _x( 'Test Signup User Email', 'Modules: Notify', GNETWORK_TEXTDOMAIN ), 'small', [
				'title' => _x( 'Sends a TEST confirmation request email to a user when they sign up for a new user account.', 'Modules: Notify', GNETWORK_TEXTDOMAIN ),
			] );

			Settings::submitButton( 'test_signup_blog', _x( 'Test Signup Blog Email', 'Modules: Notify', GNETWORK_TEXTDOMAIN ), 'small', [
				'title' => _x( 'Sends a TEST confirmation request email to a user when they sign up for a new site.', 'Modules: Notify', GNETWORK_TEXTDOMAIN ),
			] );

		echo '</p>';
	}

	public function settings( $sub = NULL )
	{
		if ( $this->key == $sub ) {

			if ( isset( $_POST['test_signup_user'] ) ) {

				$this->check_referer( $sub );

				// BuddyPress's
				remove_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );

				$user = wp_get_current_user();

				$results = wpmu_signup_user_notification(
					$user->user_login,
					$user->user_email, // gNetwork()->email(),
					substr( md5( time().wp_rand().$user->user_email ), 0, 16 )
				);

				WordPress::redirectReferer( $results ? 'mailed' : 'error' );

			} else if ( isset( $_POST['test_signup_blog'] ) ) {

				$this->check_referer( $sub );

				// BuddyPress's
				remove_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 6 );

				$user = wp_get_current_user();

				$results = wpmu_signup_blog_notification(
					'example.com',
					'/path',
					'New Site Title',
					$user->user_login,
					$user->user_email, // gNetwork()->email(),
					substr( md5( time().wp_rand().'example.com' ), 0, 16 )
				);

				WordPress::redirectReferer( $results ? 'mailed' : 'error' );

			} else {
				parent::settings( $sub );
			}
		}
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
	// email login credentials to a newly-registered user
	// CHANGED: we opt-out notifying the admin
	public function wp_new_user_notification( $user_id, $deprecated = NULL, $notify = '' )
	{
		global $wpdb, $wp_hasher;

		$site = WordPress::getSiteNameforEmail();
		$user = get_userdata( $user_id );

		Logger::ALERT( sprintf( 'NOTIFY: New user registration: %s', $user->user_login ) );

		if ( ! $this->options['disable_new_user_admin']
			&& 'user' !== $notify ) {

			$switched_locale = switch_to_locale( get_locale() );

			$message = sprintf( __( 'New user registration on your site %s:' ), $site )."\r\n\r\n";
			$message.= sprintf( __( 'Username: %s' ), $user->user_login )."\r\n\r\n";
			$message.= sprintf( __( 'Email: %s' ), $user->user_email )."\r\n";

			$mail = [
				'to'      => get_option( 'admin_email' ),
				'subject' => __( '[%s] New User Registration' ),
				'message' => $message,
				'headers' => '',
			];

			$mail = apply_filters( 'wp_new_user_notification_email_admin', $mail, $user, $site );

			@wp_mail(
				$mail['to'],
				wp_specialchars_decode( sprintf( $mail['subject'], $site ) ),
				$mail['message'],
				$mail['headers']
			);

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

		$message = sprintf( __( 'Username: %s' ), $user->user_login )."\r\n\r\n";
		$message.= __( 'To set your password, visit the following address:' )."\r\n\r\n";
		$message.= '<'.network_site_url( "wp-login.php?action=rp&key=$key&login=".rawurlencode( $user->user_login ), 'login' ).">\r\n\r\n";
		$message.= WordPress::loginURL()."\r\n";

		$mail = [
			'to'      => $user->user_email,
			'subject' => __( '[%s] Your username and password info' ),
			'message' => $message,
			'headers' => '',
		];

		$mail = apply_filters( 'wp_new_user_notification_email', $mail, $user, $blog );

		wp_mail(
			$mail['to'],
			wp_specialchars_decode( sprintf( $mail['subject'], $blog ) ),
			$mail['message'],
			$mail['headers']
		);

		if ( $switched_locale )
			restore_previous_locale();
	}

	// pluggable core function
	// notify the blog admin of a user changing password, normally via email
	public function wp_password_change_notification( $user )
	{
		Logger::ALERT( sprintf( 'NOTIFY: Password changed: %s', $user->user_login ) );

		if ( $this->options['disable_password_change'] )
			return;

		if ( 0 === strcasecmp( $user->user_email, get_option( 'admin_email' ) ) )
			return;

		$message  = sprintf( __( 'Password changed for user: %s' ), $user->user_login )."\r\n";
		$sitename = WordPress::getSiteNameforEmail();

		$mail = [
			'to'      => get_option( 'admin_email' ),
			'subject' => __( '[%s] Password Changed' ),
			'message' => $message,
			'headers' => '',
		];

		$mail = apply_filters( 'wp_password_change_notification_email', $mail, $user, $sitename );

		wp_mail(
			$mail['to'],
			wp_specialchars_decode( sprintf( $mail['subject'], $sitename ) ),
			$mail['message'],
			$mail['headers']
		);
	}

	public function wpmu_signup_user_notification_subject( $subject, $user_login, $user_email, $key, $meta )
	{
		return $this->options['signup_user_subject'];
	}

	// FIXME: it's diffrent on admin user new
	// @SEE: `admin_created_user_email()`
	public function wpmu_signup_user_notification_email( $message, $user_login, $user_email, $key, $meta )
	{
		return $this->options['signup_user_email'];
	}

	public function wpmu_signup_blog_notification_subject( $subject, $domain, $path, $title, $user_login, $user_email, $key, $meta )
	{
		return $this->options['signup_blog_subject'];
	}

	public function wpmu_signup_blog_notification_email( $message, $domain, $path, $title, $user_login, $user_email, $key, $meta )
	{
		return $this->options['signup_blog_email'];
	}
}

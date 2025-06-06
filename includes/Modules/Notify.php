<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\WordPress;

class Notify extends gNetwork\Module
{
	protected $key  = 'notify';
	protected $ajax = TRUE;
	protected $cron = TRUE;

	protected function setup_actions()
	{
		$this->filter( 'wpmu_welcome_notification', 5 );
		$this->filter( 'auto_core_update_send_email', 4, 12 );
		$this->filter( 'send_site_admin_email_change_email', 3, 12 );
		$this->filter( 'send_password_change_email', 3, 12 );
		$this->filter( 'send_email_change_email', 3, 12 );

		if ( is_readable( GNETWORK_DIR.'includes/Misc/NotifyPluggable.php' ) )
			require_once GNETWORK_DIR.'includes/Misc/NotifyPluggable.php';

		if ( $this->options['disable_new_user_admin'] )
			$this->filter_false( 'bp_core_send_user_registration_admin_notification' );

		if ( ! is_multisite() )
			return;

		if ( $this->options['disable_new_site_email'] )
			$this->filter_false( 'send_new_site_email' );

		if ( $this->options['signup_user_subject'] )
			$this->filter( 'wpmu_signup_user_notification_subject', 5, 12 );

		if ( $this->options['signup_user_email'] )
			$this->filter( 'wpmu_signup_user_notification_email', 5, 12 );

		if ( $this->options['signup_blog_subject'] )
			$this->filter( 'wpmu_signup_blog_notification_subject', 8, 12 );

		if ( $this->options['signup_blog_email'] )
			$this->filter( 'wpmu_signup_blog_notification_email', 8, 12 );

		// `wp_send_new_user_notification_to_admin`
		// `wp_send_new_user_notification_to_user`


		// @REF: https://make.wordpress.org/core/2020/07/30/controlling-plugin-and-theme-auto-update-email-notifications-and-site-health-infos-in-wp-5-5/
		// Disable auto-update email notifications for plugins.
		$this->filter_false( 'auto_plugin_update_send_email' );

		// Disable auto-update email notifications for themes.
		$this->filter_false( 'auto_theme_update_send_email' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Notify', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'disable_new_site_email'       => 0,
			'disable_new_user_admin'       => 0,
			'disable_user_password_change' => 0,
			'disable_password_change'      => 0,
			'disable_email_change'         => 0,
			'disable_admin_email_change'   => 1,
			'signup_user_subject'          => '',
			'signup_user_email'            => '',
			'signup_blog_subject'          => '',
			'signup_blog_email'            => '',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'disable_new_user_admin',
					'type'        => 'disabled',
					'title'       => _x( 'New User Email', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Notifies the blog admin of a newly-registered user.', 'Modules: Notify: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'disable_user_password_change',
					'type'        => 'disabled',
					'title'       => _x( 'Password Reset Email', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Notifies the blog admin of a user changed password.', 'Modules: Notify: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'disable_password_change',
					'type'        => 'disabled',
					'title'       => _x( 'User Password Changes', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Notifies the user of his password changed.', 'Modules: Notify: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'disable_email_change',
					'type'        => 'disabled',
					'title'       => _x( 'User Email Changes', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Notifies the user of his email address changed.', 'Modules: Notify: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'disable_admin_email_change',
					'type'        => 'disabled',
					'title'       => _x( 'Admin Email Changes', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Notifies the old site admin of the site admin email address changed.', 'Modules: Notify: Settings', 'gnetwork' ),
					'default'     => '1',
				],
			],
		];

		if ( is_multisite() ) {

			$settings['_multisite'] = [
				[
					'field'       => 'disable_new_site_email',
					'type'        => 'disabled',
					'title'       => _x( 'New Sites Email', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Notifies the network administrator of a newly-registered site.', 'Modules: Notify: Settings', 'gnetwork' ),
				],
			];

			$settings['_signup'] = [
				[
					'field'       => 'signup_user_subject',
					'type'        => 'text',
					'title'       => _x( 'New User Email Subject', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Subject of the notification email of new user signup. Leave empty to use defaults.', 'Modules: Notify: Settings', 'gnetwork' ),
					'placeholder' => _x( '[%1$s] Activate %2$s', 'New user notification email subject' ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Network name, <code>%2$s</code>: User login name' ),
				],
				[
					'field'       => 'signup_user_email',
					'type'        => 'textarea',
					'title'       => _x( 'New User Email Message', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Message content of the notification email for new user sign-up. Leave empty to use defaults.', 'Modules: Notify: Settings', 'gnetwork' ),
					'placeholder' => __( "To activate your user, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login." ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Activate URL' ),
					'field_class' => [ 'large-text', 'textarea-autosize' ],
				],
				[
					'field'       => 'signup_blog_subject',
					'type'        => 'text',
					'title'       => _x( 'New Blog Email Subject', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Subject of the new blog notification email. Leave empty to use defaults.', 'Modules: Notify: Settings', 'gnetwork' ),
					'placeholder' => _x( '[%1$s] Activate %2$s', 'New site notification email subject' ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Network name, <code>%2$s</code>: New site URL' ),
				],
				[
					'field'       => 'signup_blog_email',
					'type'        => 'textarea',
					'title'       => _x( 'New Blog Email Message', 'Modules: Notify: Settings', 'gnetwork' ),
					'description' => _x( 'Message content of the new blog notification email. Leave empty to use defaults.', 'Modules: Notify: Settings', 'gnetwork' ),
					'placeholder' => __( "To activate your blog, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login.\n\nAfter you activate, you can visit your site here:\n\n%s" ),
					'after'       => Settings::fieldAfterText( '<code>%1$s</code>: Activate URL, <code>%2$s</code>: New site URL, <code>%3$s</code>: Activation Key' ),
					'field_class' => [ 'large-text', 'textarea-autosize' ],
				],
			];
		}

		return $settings;
	}

	public function settings_sidebox( $sub, $uri )
	{
		echo $this->wrap_open_buttons();

			Settings::submitButton( 'test_signup_user', _x( 'Test Signup User Email', 'Modules: Notify', 'gnetwork' ), 'small', [
				'title' => _x( 'Sends a TEST confirmation request email to a user when they sign up for a new user account.', 'Modules: Notify', 'gnetwork' ),
			] );

			Settings::submitButton( 'test_signup_blog', _x( 'Test Signup Blog Email', 'Modules: Notify', 'gnetwork' ), 'small', [
				'title' => _x( 'Sends a TEST confirmation request email to a user when they sign up for a new site.', 'Modules: Notify', 'gnetwork' ),
			] );

		echo '</p>';
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['test_signup_user'] ) ) {

			$this->check_referer( $sub, 'settings' );

			// BuddyPress's
			remove_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1 );

			$user   = wp_get_current_user();
			$locale = switch_to_locale( get_network_option( NULL, 'WPLANG' ) );

			$results = wpmu_signup_user_notification(
				$user->user_login,
				$user->user_email, // gNetwork()->email(),
				substr( md5( time().wp_rand().$user->user_email ), 0, 16 )
			);

			Core\WordPress::redirectReferer( $results ? 'mailed' : 'error' );

		} else if ( isset( $_POST['test_signup_blog'] ) ) {

			$this->check_referer( $sub, 'settings' );

			// BuddyPress's
			remove_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1 );

			$user   = wp_get_current_user();
			$locale = switch_to_locale( get_network_option( NULL, 'WPLANG' ) );

			$results = wpmu_signup_blog_notification(
				'example.com',
				'/path',
				'New Site Title',
				$user->user_login,
				$user->user_email, // gNetwork()->email(),
				substr( md5( time().wp_rand().'example.com' ), 0, 16 )
			);

			Core\WordPress::redirectReferer( $results ? 'mailed' : 'error' );
		}
	}

	// filter whether to bypass the welcome email after site activation.
	public function wpmu_welcome_notification( $blog_id, $user_id, $password, $title, $meta )
	{
		if ( Core\WordPress::isSuperAdmin( $user_id ) )
			return FALSE;

		return $blog_id;
	}

	// filter whether to send an email following an automatic background core update.
	// @REF: http://codex.wordpress.org/Configuring_Automatic_Background_Updates
	public function auto_core_update_send_email( $true, $type, $core_update, $result )
	{
		if ( 'success' === $type )
			$version = $core_update->current;

		else if ( $next = get_preferred_from_update_core() )
			$version = $next->current;

		else
			$version = 'UNKNOWN';

		Logger::INFO( sprintf( 'NOTIFY: automatic background core update: %s [v%s]', strtoupper( $type ), $version ) );

		if ( in_array( $type, [ 'fail', 'critical' ] ) )
			return TRUE;

		return FALSE;
	}

	public function send_password_change_email( $true, $user, $userdata )
	{
		Logger::INFO( sprintf( 'NOTIFY: password changed for: %s', $user['user_nicename'] ) );

		return $this->options['disable_password_change'] ? FALSE: $true;
	}

	public function send_email_change_email( $true, $user, $userdata )
	{
		Logger::INFO( sprintf( 'NOTIFY: email changed user %s from %s to %s',
			$user['user_nicename'], $user['user_email'], $userdata['user_email'] ) );

		return $this->options['disable_email_change'] ? FALSE: $true;
	}

	public function send_site_admin_email_change_email( $send, $old_email, $new_email )
	{
		Logger::siteINFO( 'NOTIFY', sprintf( 'admin email changed from %s to %s', $old_email, $new_email ) );

		return $this->options['disable_admin_email_change'] ? FALSE: $send;
	}

	// @SEE: https://wordpress.stackexchange.com/a/236301
	// @SEE: https://smartwp.com/disable-wordpress-new-user-notification-email/
	// pluggable core function
	// email login credentials to a newly-registered user
	// CHANGED: we opt-out notifying the admin
	public function wp_new_user_notification( $user_id, $deprecated = NULL, $notify = '' )
	{
		if ( ! in_array( $notify, array( 'user', 'admin', 'both', '' ), TRUE ) )
			return;

		$site = Core\WordPress::getSiteNameforEmail();
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

		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) )
			return;

		$locale          = get_user_locale( $user );
		$switched_locale = switch_to_locale( $locale );

		$message = sprintf( __( 'Username: %s' ), $user->user_login )."\r\n\r\n";
		$message.= __( 'To set your password, visit the following address:' )."\r\n\r\n";
		$message.= '<'.network_site_url( "wp-login.php?action=rp&key=$key&login=".rawurlencode( $user->user_login ).'&wp_lang='.$locale, 'login' ).">\r\n\r\n";
		$message.= Core\WordPress::loginURL()."\r\n";

		$mail = [
			'to'      => $user->user_email,
			'subject' => __( '[%s] Login Details' ),
			'message' => $message,
			'headers' => '',
		];

		$mail = apply_filters( 'wp_new_user_notification_email', $mail, $user, $site );

		wp_mail(
			$mail['to'],
			wp_specialchars_decode( sprintf( $mail['subject'], $site ) ),
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
		Logger::siteALERT( 'NOTIFY', sprintf( 'Password changed: %s', $user->user_login ) );

		if ( $this->options['disable_user_password_change'] )
			return;

		if ( 0 === strcasecmp( $user->user_email, get_option( 'admin_email' ) ) )
			return;

		$message  = sprintf( __( 'Password changed for user: %s' ), $user->user_login )."\r\n";
		$sitename = Core\WordPress::getSiteNameforEmail();

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

	// FIXME: it's different on admin user new
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

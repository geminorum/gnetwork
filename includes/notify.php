<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkNotify extends gNetworkModuleCore
{

	protected $option_key = 'notify';
	protected $network    = TRUE;

	protected function setup_actions()
	{
		$this->register_menu( 'notify',
			_x( 'Notify', 'Notify Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		add_filter( 'wpmu_welcome_notification', array( $this, 'wpmu_welcome_notification' ), 10, 5 );
		add_filter( 'auto_core_update_send_email', array( $this, 'auto_core_update_send_email' ), 10, 4 );
	}

	public function default_options()
	{
		return array(
			// 'disable_new_user'        => '1',
			'disable_new_user_admin'  => '1',
			'disable_password_change' => '1',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				// array(
				// 	'field'   => 'disable_new_user',
				// 	'type'    => 'enabled',
				// 	'title'   => _x( 'New Users', 'Notify Module', GNETWORK_TEXTDOMAIN ),
				// 	'desc'    => _x( 'Email login credentials to a newly-registered user', 'Notify Module', GNETWORK_TEXTDOMAIN ),
				// 	'default' => '1',
				// 	'values'  => array(
				// 		_x( 'All New Users', 'Notify Module', GNETWORK_TEXTDOMAIN ),
				// 		_x( 'Credential Only', 'Notify Module', GNETWORK_TEXTDOMAIN ),
				// 	),
				// ),
				array(
					'field'   => 'disable_new_user_admin',
					'type'    => 'enabled',
					'title'   => _x( 'New User', 'Notify Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Notify the blog admin of a newly-registered user', 'Notify Module', GNETWORK_TEXTDOMAIN ),
					'default' => '1',
					'values'  => array(
						__( 'Enabled' , GNETWORK_TEXTDOMAIN ),
						__( 'Disabled', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'   => 'disable_password_change',
					'type'    => 'enabled',
					'title'   => _x( 'Password Reset', 'Notify Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Notify the blog admin of a user changing password', 'Notify Module', GNETWORK_TEXTDOMAIN ),
					'default' => '1',
					'values'  => array(
						__( 'Enabled' , GNETWORK_TEXTDOMAIN ),
						__( 'Disabled', GNETWORK_TEXTDOMAIN ),
					),
				),
			),
		);
	}

	// filter whether to bypass the welcome email after site activation.
	public function wpmu_welcome_notification( $blog_id, $user_id, $password, $title, $meta )
	{
		if ( is_super_admin( $user_id ) )
			return FALSE;

		return $blog_id;
	}

	// filter whether to send an email following an automatic background core update.
	// http://codex.wordpress.org/Configuring_Automatic_Background_Updates
	public function auto_core_update_send_email( $true, $type, $core_update, $result )
	{
		if ( in_array( $type, array( 'fail', 'critical' ) ) )
			return TRUE;

		return FALSE;
	}

	public function wp_new_user_notification( $user_id, $deprecated = NULL, $notify = '' )
	{
		global $wpdb, $wp_hasher;

		$user     = get_userdata( $user_id );
		$blogname = $this->blogname();

		if ( ! $this->options['disable_new_user_admin'] ) {

			$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
			$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
			$message .= sprintf(__('Email: %s'), $user->user_email) . "\r\n";

			@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);
		}

		if ( 'admin' === $notify || empty( $notify ) ) {
			return;
		}

		// Generate something random for a password reset key.
		$key = wp_generate_password( 20, FALSE );

		/** This action is documented in wp-login.php */
		do_action( 'retrieve_password_key', $user->user_login, $key );

		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, TRUE );
		}
		$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

		$message = sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		$message .= __('To set your password, visit the following address:') . "\r\n\r\n";
		$message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";

		$message .= wp_login_url() . "\r\n";

		wp_mail($user->user_email, sprintf(__('[%s] Your username and password info'), $blogname), $message);
	}

	// FIXME: OLD
	// pluggable core function
	// Email login credentials to a newly-registered user.
	// CHANGED: we removed notifying the admin
	public function wp_new_user_notification_OLD( $user_id, $plaintext_pass = '' )
	{
		if ( empty( $plaintext_pass ) && $this->options['disable_new_user'] )
			return;

		$blogname = $this->blogname();
		$user = get_userdata( $user_id );

		if ( ! $this->options['disable_new_user_admin'] ) {

			$message  = sprintf( __( 'New user registration on your site %s:' ), $blogname )."\r\n\r\n";
			$message .= sprintf( __( 'Username: %s' ), $user->user_login )."\r\n\r\n";
			$message .= sprintf( __( 'E-mail: %s' ), $user->user_email   )."\r\n";

			@wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] New User Registration' ), $blogname ), $message );
		}

		$message  = sprintf( __( 'Username: %s' ), $user->user_login )."\r\n";
		$message .= sprintf( __( 'Password: %s' ), $plaintext_pass   )."\r\n";
		$message .= wp_login_url()."\r\n";

		wp_mail( $user->user_email, sprintf( __( '[%s] Your username and password' ), $blogname ), $message );
	}

	// pluggable core function
	// Notify the blog admin of a user changing password, normally via email.
	public function wp_password_change_notification( &$user )
	{
		if ( $this->options['disable_password_change'] )
			return;

		// send a copy of password change notification to the admin
		// but check to see if it's the admin whose password we're changing, and skip this
		if ( 0 === strcasecmp( $user->user_email, get_option( 'admin_email' ) ) )
			return;

		$message = sprintf( __( 'Password Lost and Changed for user: %s' ), $user->user_login )."\r\n";
		wp_mail( get_option( 'admin_email' ), sprintf(__('[%s] Password Lost/Changed' ), $this->blogname() ), $message );
	}

	// HELPER
	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	public function blogname()
	{
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}
}

if ( ! function_exists( 'wp_new_user_notification' ) ) :
function wp_new_user_notification( $user_id, $deprecated = NULL, $notify = '' ) {
	global $gNetwork;
	return $gNetwork->notify->wp_new_user_notification( $user_id, $deprecated, $notify );
} endif;

if ( ! function_exists( 'wp_password_change_notification' ) ) :
function wp_password_change_notification( &$user ) {
	global $gNetwork;
	return $gNetwork->notify->wp_password_change_notification( $user );
} endif;

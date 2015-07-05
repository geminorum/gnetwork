<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkNotify extends gNetworkModuleCore
{

	var $_network    = TRUE;
	var $_option_key = 'notify';

	public function setup_actions()
	{
		gNetworkNetwork::registerMenu( 'notify',
			__( 'Notify', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);

		add_filter( 'wpmu_welcome_notification', array( &$this, 'wpmu_welcome_notification' ), 10, 5 );
		add_filter( 'auto_core_update_send_email', array( &$this, 'auto_core_update_send_email' ), 10, 4 );
	}

	public function settings( $sub = NULL )
	{
		if ( 'notify' == $sub ) {
			$this->settings_update( $sub );
			add_action( 'gnetwork_network_settings_sub_notify', array( &$this, 'settings_html' ), 10, 2 );
			$this->register_settings();
		}
	}

	public function default_options()
	{
		return array(
			'disable_new_user'        => '1',
			'disable_new_user_admin'  => '1',
			'disable_password_change' => '1',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'   => 'disable_new_user',
					'type'    => 'enabled',
					'title'   => __( 'New Users', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Email login credentials to a newly-registered user', GNETWORK_TEXTDOMAIN ),
					'default' => '1',
					'values'  => array(
						__( 'All New Users', GNETWORK_TEXTDOMAIN ),
						__( 'Credential Only' , GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'   => 'disable_new_user_admin',
					'type'    => 'enabled',
					'title'   => __( 'Admin Notify', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Also, Email login credentials of a newly-registered user to the blog admin', GNETWORK_TEXTDOMAIN ),
					'default' => '1',
					'values'  => array(
						__( 'Enabled' , GNETWORK_TEXTDOMAIN ),
						__( 'Disabled', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'   => 'disable_password_change',
					'type'    => 'enabled',
					'title'   => __( 'Password Reset', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Notify the blog admin of a user changing password', GNETWORK_TEXTDOMAIN ),
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
		if( in_array( $type, array( 'fail', 'critical' ) ) )
			return TRUE;
			
		return FALSE;
	}

	// pluggable core function
	// Email login credentials to a newly-registered user.
	// CHANGED: we removed notifying the admin
	public function wp_new_user_notification( $user_id, $plaintext_pass = '' )
	{
		if ( empty( $plaintext_pass ) && $this->options['disable_new_user'] )
			return;

		$blogname = $this->blogname();
		$user = get_userdata( $user_id );

		if ( ! $this->options['disable_new_user_admin'] )
		{
			$message  = sprintf( __( 'New user registration on your site %s:' ), $blogname )."\r\n\r\n";
			$message .= sprintf( __( 'Username: %s' ), $user->user_login )."\r\n\r\n";
			$message .= sprintf( __( 'E-mail: %s' ), $user->user_email   )."\r\n";

			@wp_mail(get_option('admin_email'), sprintf( __( '[%s] New User Registration' ), $blogname ), $message );
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
function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {
	global $gNetwork;
	return $gNetwork->notify->wp_new_user_notification( $user_id, $plaintext_pass );
} endif;

if ( ! function_exists( 'wp_password_change_notification' ) ) :
function wp_password_change_notification( &$user ) {
	global $gNetwork;
	return $gNetwork->notify->wp_password_change_notification( $user );
} endif;


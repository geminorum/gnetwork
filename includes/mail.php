<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkMail extends gNetworkModuleCore
{

	var $_network    = TRUE;
	var $_option_key = 'mail';

	protected function setup_actions()
	{
		gNetworkNetwork::registerMenu( 'mail',
			__( 'Mail', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);

		gNetworkNetwork::registerMenu( 'testmail',
			__( 'Test Mail', GNETWORK_TEXTDOMAIN )
		);

		if ( $this->options['log_all'] )
			add_filter( 'wp_mail', array( &$this, 'wp_mail' ), 99 );
		
		add_filter( 'wp_mail_from', array( &$this, 'wp_mail_from' ), 5 );
		add_filter( 'wp_mail_from_name', array( &$this, 'wp_mail_from_name' ), 5 );

		add_action( 'phpmailer_init', array( &$this, 'phpmailer_init' ) );
	}

	public function settings( $sub = NULL )
	{
		if ( 'mail' == $sub && isset( $_POST['create_log_folder'] ) ) {
			$this->check_referer( $sub );
			gNetworkUtilities::putHTAccessDeny( GNETWORK_MAIL_LOG_DIR, TRUE );
			self::redirect();
		} else {
			parent::settings( $sub );
		}
	}

	public function default_options()
	{
		return array(
			'from_email'    => '',
			'from_name'     => '',
			'sender'        => 'FROM',
			'mailer'        => ( defined( 'WPMS_MAILER'    ) ? constant( 'WPMS_MAILER'    ) : 'mail' ), // possible values 'smtp', 'mail', or 'sendmail'
			'smtp_secure'   => ( defined( 'WPMS_SSL'       ) ? constant( 'WPMS_SSL'       ) : 'no' ), // possible values '', 'ssl', 'tls' - note TLS is not STARTTLS
			'smtp_host'     => ( defined( 'WPMS_SMTP_HOST' ) ? constant( 'WPMS_SMTP_HOST' ) : 'localhost' ),
			'smtp_port'     => ( defined( 'WPMS_SMTP_PORT' ) ? constant( 'WPMS_SMTP_PORT' ) : 25 ),
			'smtp_username' => ( defined( 'WPMS_SMTP_USER' ) ? constant( 'WPMS_SMTP_USER' ) : '' ),
			'smtp_password' => ( defined( 'WPMS_SMTP_PASS' ) ? constant( 'WPMS_SMTP_PASS' ) : '' ),
			'log_all'       => '0',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'   => 'from_email',
					'type'    => 'text',
					'title'   => __( 'From Email', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'You can specify the email address that emails should be sent from. If you leave this blank, the default email will be used.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'from_name',
					'type'    => 'text',
					'title'   => __( 'From Name', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from WordPress.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'sender',
					'type'    => 'text',
					'title'   => __( 'Return Path', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Set the return-path email address. Use <code>FROM</code> to match the From Email or Empty to disable.', GNETWORK_TEXTDOMAIN ),
					'default' => 'FROM',
				),
				array(
					'field'   => 'mailer',
					'type'    => 'radio',
					'title'   => __( 'Mailer', GNETWORK_TEXTDOMAIN ),
					'default' => 'mail',
					'values'  => array(
						'mail' => __( 'Use the PHP mail() function to send emails.', GNETWORK_TEXTDOMAIN ),
						'smtp' => __( 'Send all WordPress emails via SMTP.', GNETWORK_TEXTDOMAIN ),
					),
				),
			),
			'_smtp' => array(
				array(
					'field'   => 'smtp_host',
					'type'    => 'text',
					'title'   => __( 'SMTP Host', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'smtp_port',
					'type'    => 'text',
					'title'   => __( 'SMTP Port', GNETWORK_TEXTDOMAIN ),
					'default' => '',
					'style'   => 'width:100px',
				),
				array(
					'field'   => 'smtp_secure',
					'type'    => 'radio',
					'title'   => __( 'Encryption', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'For most servers SSL is the recommended option.', GNETWORK_TEXTDOMAIN ),
					'default' => 'no',
					'values'  => array(
						'no'  => __( 'No encryption.', GNETWORK_TEXTDOMAIN ),
						'ssl' => __( 'Use SSL encryption.', GNETWORK_TEXTDOMAIN ),
						'tls' => __( 'Use TLS encryption. This is not the same as STARTTLS.', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'   => 'smtp_username',
					'type'    => 'text',
					'title'   => __( 'Username', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Empty to disable Authentication.', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
				array(
					'field'   => 'smtp_password',
					'type'    => 'text',
					'title'   => __( 'Password', GNETWORK_TEXTDOMAIN ),
					'default' => '',
				),
			),
			'_log' => array(
				array(
					'field'   => 'log_all',
					'type'    => 'enabled',
					'title'   => _x( 'Log All', 'Enable log all outgoing', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Log all outgoing emails in a secure folder', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
			),
		);
	}

	public function settings_section_smtp()
	{
		echo '<h3>'.__( 'SMTP Settings', GNETWORK_TEXTDOMAIN ).'</h3>';
		echo '<p class="description">';
			_e( 'These options only apply if you have chosen to send mail by SMTP above.', GNETWORK_TEXTDOMAIN );
		echo '</p>';
	}
	
	public function settings_section_log()
	{
		echo '<h3>'.__( 'Log Settings', GNETWORK_TEXTDOMAIN ).'</h3>';
	}

	public function settings_help_tabs()
	{
		return array(
			array(
				'id'      => 'gnetwork-mail-help-gmail',
				'title'   => __( 'Gmail SMTP', GNETWORK_TEXTDOMAIN ),
				'content' => '<p><table><tbody>
				<tr><td style="width:150px">SMTP Host</td><td><code>smtp.gmail.com</code></td></tr>
				<tr><td>SMTP Port</td><td><code>465</code></td></tr>
				<tr><td>Encryption</td><td>SSL</td></tr>
				<tr><td>Username</td><td><em>your.gmail@gmail.com</em></td></tr>
				<tr><td>Password</td><td><em>yourpassword</em></td></tr>
				</tbody></table><br />
				For more information see <a href="http://www.wpbeginner.com/plugins/how-to-send-email-in-wordpress-using-the-gmail-smtp-server/" target="_blank">here</a>.
				</p>',
				'callback' => FALSE,
			),
			array(
				'id'      => 'gnetwork-mail-help-mandrill',
				'title'   => __( 'Mandrill SMTP', GNETWORK_TEXTDOMAIN ),
				'content' => '<p><table><tbody>
				<tr><td style="width:150px">SMTP Host</td><td>smtp.mandrillapp.com</td></tr>
				<tr><td>SMTP Port</td><td>465</td></tr>
				<tr><td>Encryption</td><td>SSL</td></tr>
				<tr><td>Username</td><td><em>your.mandrill.username</em></td></tr>
				<tr><td>Password</td><td><em>any valid API key</em></td></tr>
				</tbody></table><br />
				Get your API key from <a href="https://mandrillapp.com/settings" target="_blank">here</a>.<br />
				For more information see <a href="http://help.mandrill.com/entries/21738447" target="_blank">here</a>.
				</p>',
				'callback' => FALSE,
			),
		);
	}
	
	public function settings_sidebox( $sub, $settings_uri )
	{
		if ( $this->options['log_all'] ) {
		
			if ( is_dir( GNETWORK_MAIL_LOG_DIR ) && wp_is_writable( GNETWORK_MAIL_LOG_DIR ) ) {
				echo '<p>'.sprintf( __( 'Log Folder Exists and Writable: <code>%s</code>', GNETWORK_TEXTDOMAIN ), GNETWORK_MAIL_LOG_DIR ).'</p>';
				
				if ( ! file_exists( GNETWORK_MAIL_LOG_DIR.DS.'.htaccess' ) )
					echo '<p>'.__( 'Warning: <code>.htaccess</code> not found!', GNETWORK_TEXTDOMAIN ).'</p>';
				
			} else {
				echo '<p>'.__( 'Log Folder Not Exists and/or Writable', GNETWORK_TEXTDOMAIN ).'</p>';
				submit_button( __( 'Create Log Folder', GNETWORK_TEXTDOMAIN ), 'secondary', 'create_log_folder' );
			}	
		
		} else {
			echo '<p>'.__( 'Logging Emails Disabled', GNETWORK_TEXTDOMAIN ).'</p>';
		}
	}

	public function wp_mail_from( $email )
	{
		if ( 0 === strpos( $email, 'wordpress@' ) )
			$email = $this->get_from_email( $email );
		return apply_filters( 'gnetwork_mail_from_email', $email );
	}

	public function wp_mail_from_name( $name )
	{
		if ( 0 === strpos( $name, 'WordPress' ) )
			$name = $this->get_from_name( $name );
		return apply_filters( 'gnetwork_mail_from_name', $name );
	}

	public function get_from_email( $email = '' )
	{
		if ( ! empty( $this->options['from_email'] ) )
			return $this->options['from_email'];
		else
			return get_site_option( 'admin_email', $email );

		return $email;
	}

	public function get_from_name( $name = '' )
	{
		if ( ! empty( $this->options['from_name'] ) ) {
			return $this->options['from_name'];
		} else {
			if ( is_multisite() )
				return get_site_option( 'site_name', $name );
			return get_option( 'blogname', $name );
		}
		return $name;
	}

	// http://phpmailer.worxware.com/?pg=properties
	// http://stackoverflow.com/questions/6315052/use-of-phpmailer-class
	public function phpmailer_init( &$phpmailer )
	{
		$phpmailer->Mailer = $this->options['mailer'];

		if ( 'from' == strtolower( $this->options['sender'] ) )
			$phpmailer->Sender = $phpmailer->From;
		else if ( $this->options['sender'] )
			$phpmailer->Sender = $this->options['sender'];

		if ( 'smtp' == $this->options['mailer'] ) {
			$phpmailer->SMTPSecure = ( 'no' == $this->options['smtp_secure'] ? '' : $this->options['smtp_secure'] );
			$phpmailer->Host = $this->options['smtp_host'];
			$phpmailer->Port = $this->options['smtp_port'];

			if ( $this->options['smtp_username'] && $this->options['smtp_password'] ) {
				$phpmailer->SMTPAuth = TRUE;
				$phpmailer->Username = $this->options['smtp_username'];
				$phpmailer->Password = $this->options['smtp_password'];
			}

			// adding important header for mandrill smtp
			$phpmailer->AddCustomHeader( sprintf( '%1$s: %2$s', 'X-MC-Important', 'true'  ) );
			$phpmailer->AddCustomHeader( sprintf( '%1$s: %2$s', 'X-MC-Track'    , 'false' ) );
		}
	}
	
	// $mail = array( 'to', 'subject', 'message', 'headers', 'attachments' );
	public function wp_mail( $mail )
	{
		$to = is_array( $mail['to'] ) ? implode( '-', array_filter( array( 'gNetworkUtilities', 'esc_filename' ), $mail['to'] ) ) : gNetworkUtilities::esc_filename( $mail['to'] );
		
		file_put_contents( GNETWORK_MAIL_LOG_DIR.DS.current_time( 'Ymd-His' ).'-'.$to.'.email', var_export( $mail, TRUE ).PHP_EOL, FILE_APPEND );
		
		return $mail;
	}
	
	public function testmail_form()
	{
		$to = isset( $_POST['gnetwork_mail_testmail_to'] ) ? $_POST['gnetwork_mail_testmail_to'] : $this->get_from_email();
		$message = isset( $_POST['gnetwork_mail_testmail_message'] ) ? $_POST['gnetwork_mail_testmail_message'] : __( 'This is a test email generated by the gNetwork Mail plugin.', GNETWORK_TEXTDOMAIN );
		$subject = isset( $_POST['gnetwork_mail_testmail_subject'] ) ? $_POST['gnetwork_mail_testmail_subject'] : __( 'Test mail to ', GNETWORK_TEXTDOMAIN ).$to;

		echo '<table class="form-table"><tbody>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_to">';
				_e( 'To', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><input type="text" id="gnetwork_mail_testmail_to" name="gnetwork_mail_testmail_to" value="'.$to.'" class="regular-text code" />';
			echo '<p class="description">';
				//_e( 'Type an email address here and then click Send Test to generate a test email.', GNETWORK_TEXTDOMAIN );
			echo '</p></td></tr>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_subject">';
				_e( 'Subject', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><input type="text" id="gnetwork_mail_testmail_subject" name="gnetwork_mail_testmail_subject" value="'.$subject.'" class="regular-text code" />';
			echo '<p class="description">';
				//_e( 'Type an email address here and then click Send Test to generate a test email.', GNETWORK_TEXTDOMAIN );
			echo '</p></td></tr>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_message">';
				_e( 'Message:', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><textarea id="gnetwork_mail_testmail_message" name="gnetwork_mail_testmail_message" cols="45" rows="5" class="large-text" >'.$message.'</textarea>';
			echo '<p class="description">';
				//_e( 'Type an email address here and then click Send Test to generate a test email.', GNETWORK_TEXTDOMAIN );
			echo '</p></td></tr>';
		echo '</tbody></table>';
	}

	public function testmail_send()
	{
		global $phpmailer;

		if ( isset($_POST['action'] )
			&& 'sendtest' == $_POST['action']
			&& isset( $_POST['gnetwork_mail_testmail_to'] ) ) {

			check_admin_referer( 'gnetwork-testmail' );

			// Make sure the PHPMailer class has been instantiated
			// (copied verbatim from wp-includes/pluggable.php)
			// (Re)create it, if it's gone missing
			if ( ! is_object( $phpmailer ) || ! is_a( $phpmailer, 'PHPMailer' ) ) {
				require_once ABSPATH.WPINC.'/class-phpmailer.php';
				require_once ABSPATH.WPINC.'/class-smtp.php';
				$phpmailer = new PHPMailer( TRUE );
			}

			$phpmailer->SMTPDebug = TRUE;

			ob_start();

			$result = wp_mail(
				$_POST['gnetwork_mail_testmail_to'],
				$_POST['gnetwork_mail_testmail_subject'],
				stripslashes( $_POST['gnetwork_mail_testmail_message'] ) );

			// Strip out the language strings which confuse users
			// unset($phpmailer->language);
			// This property became protected in WP 3.2

			$smtp_debug = ob_get_clean();

			echo '<div id="m1essage" class="'.( false === $result ? 'error' : 'updated' ).'"><p><strong>';
				_e( 'Test Message Sent', GNETWORK_TEXTDOMAIN );
			echo '</strong></p><p>';
				_e( 'The result was:', GNETWORK_TEXTDOMAIN );
			echo '</p>';
				gnetwork_dump( $result );
			echo '<p>';
				_e('The SMTP debugging output:', GNETWORK_TEXTDOMAIN );
			echo '</p><pre>';
				echo $smtp_debug;
			echo '</pre><p>';
				_e('The full debugging output:', GNETWORK_TEXTDOMAIN );
			echo '</p>';
				gnetwork_dump( $phpmailer );
			echo '</div>';

			unset( $phpmailer );
		}
	}
}

// Originally Based on : WP Mail SMTP by Callum Macdonald v0.9.4
// http://wordpress.org/plugins/wp-mail-smtp/

// SEE : http://wordpress.org/plugins/wp-smtp/
// SEE : http://wordpress.org/plugins/check-email/

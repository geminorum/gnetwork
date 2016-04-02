<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkMail extends gNetworkModuleCore
{

	protected $option_key = 'mail';
	protected $network    = TRUE;

	protected function setup_actions()
	{
		$this->register_menu( 'mail',
			_x( 'Mail', 'Mail Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		$this->register_menu( 'testmail',
			_x( 'Test Mail', 'Mail Module: Menu Name', GNETWORK_TEXTDOMAIN )
		);

		if ( $this->options['log_all'] ) {
			add_filter( 'wp_mail', array( $this, 'wp_mail' ), 99 );

			$this->register_menu( 'emaillogs',
				_x( 'Email Logs', 'Mail Module: Menu Name', GNETWORK_TEXTDOMAIN )
			);
		}

		add_filter( 'wp_mail_from', array( $this, 'wp_mail_from' ), 5 );
		add_filter( 'wp_mail_from_name', array( $this, 'wp_mail_from_name' ), 5 );
		add_action( 'bp_email', array( $this, 'bp_email' ), 5, 2 );

		add_action( 'phpmailer_init', array( $this, 'phpmailer_init' ) );
	}

	public function settings( $sub = NULL )
	{
		if ( 'mail' == $sub ) {

			if ( isset( $_POST['create_log_folder'] ) ) {

				$this->check_referer( $sub );

				$message = FALSE === self::putHTAccessDeny( GNETWORK_MAIL_LOG_DIR, TRUE ) ? 'error' : 'created';

				self::redirect_referer( $message );

			} else {
				parent::settings( $sub );
			}

		} else if ( 'testmail' == $sub ) {

			// FIXME: move test email here

		} else if ( 'emaillogs' == $sub ) {

			if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub );

				// TODO: add exporting to .eml files
				// http://stackoverflow.com/a/16039103/4864081
				// http://stackoverflow.com/a/8777197/4864081
				// http://www.alexcasamassima.com/2013/02/send-pre-formatted-eml-file-in-php.html
				// https://wiki.zarafa.com/index.php/Eml_vs_msg

				if ( isset( $_POST['deletelogs_all'] ) ) {

					self::redirect_referer( ( FALSE === self::deleteEmailLogs() ? 'error' : 'purged' ) );

				} else if ( isset( $_POST['deletelogs_selected'], $_POST['_cb'] ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $log )
						if ( TRUE === unlink( GNETWORK_MAIL_LOG_DIR.'/'.$log.'.json' ) )
							$count++;

					self::redirect_referer( array(
						'message' => 'deleted',
						'count'   => $count,
					) );

				} else {
					self::redirect_referer( 'wrong' );
				}
			}

			add_action( 'gnetwork_network_settings_sub_emaillogs', array( $this, 'settings_html_emaillogs' ), 10, 2 );

			$this->register_button( 'deletelogs_selected', _x( 'Delete Selected', 'Mail Module', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
			$this->register_button( 'deletelogs_all', _x( 'Delete All', 'Mail Module', GNETWORK_TEXTDOMAIN ), self::getButtonConfirm() );
		}
	}

	public function settings_html_emaillogs( $uri, $sub = 'general' )
	{
		echo '<form class="gnetwork-form" method="post" action="">';

			$this->settings_fields( $sub, 'bulk' );

			if ( self::tableEmailLogs() )
				$this->settings_buttons( $sub );

		echo '</form>';
	}

	public function default_options()
	{
		return array(
			'from_email'    => '',
			'from_name'     => '',
			'sender'        => 'FROM',
			'mailer'        => ( defined( 'WPMS_MAILER' ) ? constant( 'WPMS_MAILER' ) : 'mail' ), // possible values 'smtp', 'mail', or 'sendmail'
			'smtp_secure'   => ( defined( 'WPMS_SSL' ) ? constant( 'WPMS_SSL' ) : 'no' ), // possible values '', 'ssl', 'tls' - note TLS is not STARTTLS
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
					'field'       => 'from_email',
					'type'        => 'text',
					'title'       => _x( 'From Email', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'You can specify the email address that emails should be sent from. Leave blank for default.', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'email-text' ),
				),
				array(
					'field'       => 'from_name',
					'type'        => 'text',
					'title'       => _x( 'From Name', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'You can specify the name that emails should be sent from. Leave blank for WordPress.', 'Mail Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'sender',
					'type'        => 'text',
					'title'       => _x( 'Return Path', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Set the return-path email address. Use <code>FROM</code> to match the From Email or Empty to disable.', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'default'     => 'FROM',
					'field_class' => array( 'regular-text', 'email-text' ),
				),
				array(
					'field'   => 'mailer',
					'type'    => 'radio',
					'title'   => _x( 'Mailer', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'default' => 'mail',
					'values'  => array(
						'mail' => _x( 'Use the PHP mail() function to send emails.', 'Mail Module', GNETWORK_TEXTDOMAIN ),
						'smtp' => _x( 'Send all WordPress emails via SMTP.', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					),
				),
			),
			'_smtp' => array(
				array(
					'field'       => 'smtp_host',
					'type'        => 'text',
					'title'       => _x( 'SMTP Host', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'medium-text', 'ip-text' ),
				),
				array(
					'field' => 'smtp_port',
					'type'  => 'number',
					'title' => _x( 'SMTP Port', 'Mail Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'smtp_secure',
					'type'        => 'radio',
					'title'       => _x( 'Encryption', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'For most servers SSL is the recommended option.', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'default'     => 'no',
					'values'      => array(
						'no'  => _x( 'No encryption.', 'Mail Module: Encryption Option', GNETWORK_TEXTDOMAIN ),
						'ssl' => _x( 'Use SSL encryption.', 'Mail Module: Encryption Option', GNETWORK_TEXTDOMAIN ),
						'tls' => _x( 'Use TLS encryption. This is not the same as STARTTLS.', 'Mail Module: Encryption Option', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'smtp_username',
					'type'        => 'text',
					'title'       => _x( 'Username', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Empty to disable Authentication.', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'code-text' ),
				),
				array(
					'field'       => 'smtp_password',
					'type'        => 'text',
					'title'       => _x( 'Password', 'Mail Module', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'regular-text', 'code-text' ),
				),
			),
			'_log' => array(
				array(
					'field'       => 'log_all',
					'type'        => 'enabled',
					'title'       => _x( 'Log All', 'Mail Module: Enable log all outgoing', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Log all outgoing emails in a secure folder', 'Mail Module', GNETWORK_TEXTDOMAIN ),
				),
			),
		);
	}

	public function settings_section_smtp()
	{
		self::settingsSection(
			_x( 'SMTP Settings', 'Mail Module: Settings Section Title', GNETWORK_TEXTDOMAIN ),
			_x( 'These options only apply if you have chosen to send mail by SMTP above.', 'Mail Module: Settings Section Desc', GNETWORK_TEXTDOMAIN )
		);
	}

	public function settings_section_log()
	{
		self::settingsSection(
			_x( 'Log Settings', 'Mail Module: Settings Section Title', GNETWORK_TEXTDOMAIN )
		);
	}

	public function settings_help_tabs()
	{
		return array(
			array(
				'id'      => 'gnetwork-mail-help-gmail',
				'title'   => _x( 'Gmail SMTP', 'Mail Module: Screen Help Title', GNETWORK_TEXTDOMAIN ),
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
				'title'   => _x( 'Mandrill SMTP', 'Mail Module: Screen Help Title', GNETWORK_TEXTDOMAIN ),
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

	public function settings_sidebox( $sub, $uri )
	{
		if ( $this->options['log_all'] ) {

			if ( is_dir( GNETWORK_MAIL_LOG_DIR ) && wp_is_writable( GNETWORK_MAIL_LOG_DIR ) ) {
				echo '<p>'.sprintf( _x( 'Log Folder Exists and Writable: <code>%s</code>', 'Mail Module', GNETWORK_TEXTDOMAIN ), GNETWORK_MAIL_LOG_DIR ).'</p>';

				if ( ! file_exists( GNETWORK_MAIL_LOG_DIR.'/.htaccess' ) )
					echo '<p>'._x( 'Warning: <code>.htaccess</code> not found!', 'Mail Module', GNETWORK_TEXTDOMAIN ).'</p>';

			} else {
				echo '<p>'._x( 'Log Folder Not Exists and/or Writable', 'Mail Module', GNETWORK_TEXTDOMAIN ).'</p>';
				submit_button( _x( 'Create Log Folder', 'Mail Module', GNETWORK_TEXTDOMAIN ), 'secondary', 'create_log_folder' );
			}

		} else {
			echo '<p>'._x( 'Logging Emails Disabled', 'Mail Module', GNETWORK_TEXTDOMAIN ).'</p>';
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

	public function bp_email( $email_type, $bp_email )
	{
		$email = $this->get_from_email( $email );
		$name  = $this->get_from_name( $name );

		$bp_email->set_from( $email, $name );
		$bp_email->set_reply_to( $email, $name );
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

	// TODO: https://github.com/Seldaek/monolog
	// $mail = array( 'to', 'subject', 'message', 'headers', 'attachments' );
	public function wp_mail( $mail )
	{
		$contents = array_merge( array(
			'timestamp' => current_time( 'mysql' ),
			'blog'      => self::currentBlog(),
			'locale'    => get_locale(),
			// TODO: get smtp server as well
		), self::filterArray( $mail ) );

		if ( is_rtl() )
			$contents['rtl'] = 'true';

		if ( is_array( $contents['to'] ) )
			$to = array_filter( array( 'gNetworkBaseCore', 'escFilename' ), $contents['to'] );
		else
			$to = self::escFilename( $contents['to'] );

		$filename = current_time( 'Ymd-His' ).'-'.$to.'.json';

		if ( FALSE === self::filePutContents( $filename, wp_json_encode( $contents ), GNETWORK_MAIL_LOG_DIR ) )
			self::log( 'EMAIL NOT LOGGED', 'TO: '.$contents['to'] );

		return $mail;
	}

	public function testmail_form()
	{
		$to = isset( $_POST['gnetwork_mail_testmail_to'] ) ? $_POST['gnetwork_mail_testmail_to'] : $this->get_from_email();
		$message = isset( $_POST['gnetwork_mail_testmail_message'] ) ? $_POST['gnetwork_mail_testmail_message'] : _x( 'This is a test email generated by the gNetwork Mail plugin.', 'Mail Module', GNETWORK_TEXTDOMAIN );
		$subject = isset( $_POST['gnetwork_mail_testmail_subject'] ) ? $_POST['gnetwork_mail_testmail_subject'] : _x( 'Test mail to ', 'Mail Module', GNETWORK_TEXTDOMAIN ).$to;

		echo '<table class="form-table"><tbody>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_to">';
				_ex( 'To', 'Mail Module', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><input type="text" id="gnetwork_mail_testmail_to" name="gnetwork_mail_testmail_to" value="'.$to.'" class="regular-text code" />';
			echo '<p class="description">';
				// _ex( 'Type an email address here and then click Send Test to generate a test email.', 'Mail Module', GNETWORK_TEXTDOMAIN );
			echo '</p></td></tr>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_subject">';
				_ex( 'Subject', 'Mail Module', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><input type="text" id="gnetwork_mail_testmail_subject" name="gnetwork_mail_testmail_subject" value="'.$subject.'" class="regular-text code" />';
			echo '<p class="description">';
				// _ex( 'Type an email address here and then click Send Test to generate a test email.', 'Mail Module', GNETWORK_TEXTDOMAIN );
			echo '</p></td></tr>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_message">';
				_ex( 'Message:', 'Mail Module', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><textarea id="gnetwork_mail_testmail_message" name="gnetwork_mail_testmail_message" cols="45" rows="5" class="large-text" >'.$message.'</textarea>';
			echo '<p class="description">';
				// _ex( 'Type an email address here and then click Send Test to generate a test email.', 'Mail Module', GNETWORK_TEXTDOMAIN );
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

			$smtp_debug = ob_get_clean();

			echo '<div id="m1essage" class="'.( FALSE === $result ? 'error' : 'updated' ).'"><p><strong>';
				_e( 'Test Message Sent', GNETWORK_TEXTDOMAIN );
			echo '</strong></p><p>';
				_e( 'The result was:', GNETWORK_TEXTDOMAIN );
			echo '</p>';
				self::dump( $result );
			echo '<p>';
				_e('The SMTP debugging output:', GNETWORK_TEXTDOMAIN );
			echo '</p><pre>';
				echo $smtp_debug;
			echo '</pre><p>';
				_e('The full debugging output:', GNETWORK_TEXTDOMAIN );
			echo '</p>';
				self::tableSide( $phpmailer );
			echo '</div>';

			unset( $phpmailer );
		}
	}

	// @SOURCE: http://stackoverflow.com/a/14744288/4864081
	protected static function getEmailLogs( $limit, $paged = 1, $ext = 'json', $old = NULL )
	{
		$i = 0;
		$logs = array();

		$files = glob( wp_normalize_path( GNETWORK_MAIL_LOG_DIR.'/*.'.$ext ) );

		usort( $files, function( $a, $b ) {
			return filemtime( $b ) - filemtime( $a );
		} );

		$pages  = ceil( count( $files ) / $limit );
		$offset = ( $paged - 1 ) * $limit;
		$filter = array_slice( $files, $offset, $limit );

		foreach ( $filter as $log ) {

			if ( $i == $limit )
				break;

			if ( ! is_null( $old ) && filemtime( $log ) < $old )
				continue;

			$data = json_decode( self::fileGetContents( $log ), TRUE );

			$logs[] = array_merge( array(
				'file' => basename( $log, '.json' ),
				'size' => filesize( $log ),
				'date' => filemtime( $log ),
			), $data );

			$i++;
		}

		$pagination = array(
			'total'    => count( $files ),
			'pages'    => $pages,
			'limit'    => $limit,
			'paged'    => $paged,
			'next'     => FALSE,
			'previous' => FALSE,
		);

		if ( $pagination['pages'] > 1 ) {
			if ( $paged != 1 )
				$pagination['previous'] = $paged - 1;

			if ( $paged != $pagination['pages'] )
				$pagination['next'] = $paged + 1;
		}

		return array( $logs, $pagination );
	}

	protected static function deleteEmailLogs( $ext = 'json' )
	{
		try {

			// @SOURCE: http://stackoverflow.com/a/4594268/4864081
			foreach ( new \DirectoryIterator( GNETWORK_MAIL_LOG_DIR ) as $file )
				if ( ! $file->isDot() )
					unlink( $file->getPathname() );

		} catch ( \Exception $e ) {
			// echo 'Caught exception: '.$e->getMessage().'<br/>';
		}

		return self::putHTAccessDeny( GNETWORK_MAIL_LOG_DIR, FALSE );
	}

	private static function tableEmailLogs()
	{
		list( $logs, $pagination ) = self::getEmailLogs( self::limit(), self::paged() );

		return self::tableList( array(
			'_cb' => 'file',

			'info' => array(
				'title'    => _x( 'Whom, When', 'Mail Module: Email Logs Table Column', GNETWORK_TEXTDOMAIN ),
				'class'    => '-column-info',
				'callback' => function( $value, $row, $column ){
					$info = '';

					if ( isset( $row['timestamp'] ) )
						$info .= '<code title="'
							.human_time_diff( strtotime( $row['timestamp'] ) )
							.'">'.$row['timestamp'].'</code>';

					if ( isset( $row['to'] ) ) {
						if ( is_array( $row['to'] ) ) {
							foreach ( $row['to'] as $to )
								$info .= ' <a href="mailto:'.$to.'">'.$to.'</a>';
						} else {
							$info .= ' <a href="mailto:'.$row['to'].'">'.$row['to'].'</a>';
						}
					}

					if ( isset( $row['headers'] ) ) {
						$info .= '<hr />';
						if ( ! is_array( $row['headers'] ) )
							$row['headers'] = explode( "\n", $row['headers']  );

						foreach ( array_filter( $row['headers'] ) as $header )
							$info .= '<code>'.$header.'</code><br />';
					}

					if ( isset( $row['attachments'] ) ) {
						$info .= '<hr />';
						if ( is_array( $row['attachments'] ) ) {
							foreach ( array_filter( $row['attachments'] ) as $attachment )
								$info .= '<code>'.$attachment.'</code><br />';
						} else if ( $row['attachments'] ) {
							$info .= '<code>'.$row['attachments'].'</code><br />';
						}
					}

					return $info;
				},
			),

			'content' => array(
				'title'    => _x( 'What', 'Mail Module: Email Logs Table Column', GNETWORK_TEXTDOMAIN ),
				'class'    => '-column-content',
				'callback' => function( $value, $row, $column ){
					$content   = '';
					$direction = isset( $row['rtl'] ) ? ' dir="rtl"' : '';

					if ( isset( $row['subject'] ) )
						$content .= '<code>'._x( 'Subject', 'Mail Module: Email Logs Table Prefix', GNETWORK_TEXTDOMAIN ).'</code> <span'
							.$direction.'>'.$row['subject'].'</span><hr />';

					if ( isset( $row['message'] ) )
						$content .= '<div'.$direction.'>'
							.wpautop( make_clickable( $row['message'] ) ).'</div>';

					return $content;
				},
			),
		), $logs, array(
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => self::html( 'h3', sprintf( _x( 'Total %s Email Logs', 'Mail Module', GNETWORK_TEXTDOMAIN ), number_format_i18n( $pagination['total'] ) ) ),
			'empty'      => self::html( 'strong', _x( 'No Logs!', 'Mail Module', GNETWORK_TEXTDOMAIN ) ),
			'pagination' => $pagination,
		) );
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;

class Mail extends gNetwork\Module
{

	protected $key = 'mail';

	protected function setup_actions()
	{
		if ( $this->options['log_all'] ) {
			$this->filter( 'wp_mail', 1, 99 );
			$this->action( 'bp_send_email_success', 2, 99 );
		}

		$this->filter( 'wp_mail_from', 1, 5 );
		$this->filter( 'wp_mail_from_name', 1, 5 );
		$this->action( 'bp_email', 2, 5 );

		$this->action( 'phpmailer_init' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Mail', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );

		$this->register_menu(
			_x( 'Test Mail', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			FALSE, 'testmail'
		);

		if ( GNETWORK_MAIL_LOG_DIR && $this->options['log_all'] )
			$this->register_menu(
				_x( 'Email Logs', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
				FALSE, 'emaillogs'
			);
	}

	public function default_options()
	{
		return [
			'from_email'    => '',
			'from_name'     => '',
			'sender'        => 'FROM',
			'mailer'        => 'mail', // possible values 'smtp', 'mail', or 'sendmail' // WPMS_MAILER
			'smtp_secure'   => 'no', // possible values '', 'ssl', 'tls' - note TLS is not STARTTLS // WPMS_SSL
			'smtp_host'     => 'localhost', // WPMS_SMTP_HOST
			'smtp_port'     => '25', // WPMS_SMTP_PORT
			'smtp_username' => '', // WPMS_SMTP_USER
			'smtp_password' => '', // WPMS_SMTP_PASS
			'log_all'       => '0',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'from_email',
					'type'        => 'email',
					'title'       => _x( 'From Email', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'You can specify the email address that emails should be sent from. Leave blank for default.', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'from_name',
					'type'        => 'text',
					'title'       => _x( 'From Name', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'You can specify the name that emails should be sent from. Leave blank for WordPress.', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'sender',
					'type'        => 'text',
					'title'       => _x( 'Return Path', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Set the return-path email address. Use <code>FROM</code> to match the From Email or Empty to disable.', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'FROM',
					'field_class' => [ 'regular-text', 'email-text' ],
				],
				[
					'field'   => 'mailer',
					'type'    => 'radio',
					'title'   => _x( 'Mailer', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'default' => 'mail',
					'values'  => [
						'mail' => _x( 'Use the PHP mail() function to send emails.', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
						'smtp' => _x( 'Send all WordPress emails via SMTP.', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					],
				],
			],
			'_smtp' => [
				[
					'field'       => 'smtp_host',
					'type'        => 'text',
					'title'       => _x( 'SMTP Host', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'regular-text', 'code' ],
					'default'     => 'localhost',
				],
				[
					'field'       => 'smtp_port',
					'type'        => 'number',
					'title'       => _x( 'SMTP Port', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'small-text', 'code' ],
					'default'     => '25',
				],
				[
					'field'       => 'smtp_secure',
					'type'        => 'radio',
					'title'       => _x( 'Encryption', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'For most servers SSL is the recommended option.', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'no',
					'values'      => [
						'no'  => _x( 'No encryption.', 'Modules: Mail: Encryption Option', GNETWORK_TEXTDOMAIN ),
						'ssl' => _x( 'Use SSL encryption.', 'Modules: Mail: Encryption Option', GNETWORK_TEXTDOMAIN ),
						'tls' => _x( 'Use TLS encryption. This is not the same as STARTTLS.', 'Modules: Mail: Encryption Option', GNETWORK_TEXTDOMAIN ),
					],
				],
				[
					'field'       => 'smtp_username',
					'type'        => 'text',
					'title'       => _x( 'Username', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Empty to disable Authentication.', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'smtp_password',
					'type'        => 'text',
					'title'       => _x( 'Password', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
			],
		];

		if ( GNETWORK_MAIL_LOG_DIR )
			$settings['_log'] = [
				[
					'field'       => 'log_all',
					'title'       => _x( 'Log All', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Log all outgoing emails in a secure folder', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
				],
			];

		return $settings;
	}

	public function settings_section_smtp()
	{
		Settings::fieldSection(
			_x( 'SMTP Settings', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'These options only apply if you have chosen to send mail by SMTP above.', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	public function settings_section_log()
	{
		Settings::fieldSection(
			_x( 'Log Settings', 'Modules: Mail: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	public function settings_help_tabs( $sub = NULL )
	{
		return [
			[
				'id'      => $this->classs( 'help-gmail' ),
				'title'   => _x( 'Gmail SMTP', 'Modules: Mail: Help Tab Title', GNETWORK_TEXTDOMAIN ),
				'content' => '<p><table><tbody>
				<tr><td style="width:150px">SMTP Host</td><td><code>smtp.gmail.com</code></td></tr>
				<tr><td>SMTP Port</td><td><code>465</code></td></tr>
				<tr><td>Encryption</td><td>SSL</td></tr>
				<tr><td>Username</td><td><em>your.gmail@gmail.com</em></td></tr>
				<tr><td>Password</td><td><em>yourpassword</em></td></tr>
				</tbody></table><br />
				For more information see <a href="http://www.wpbeginner.com/plugins/how-to-send-email-in-wordpress-using-the-gmail-smtp-server/" target="_blank">here</a>.</p>',
			],
			[
				'id'      => $this->classs( 'help-pepipost' ),
				'title'   => _x( 'Pepipost SMTP', 'Modules: Mail: Help Tab Title', GNETWORK_TEXTDOMAIN ),
				'content' => '<p><table><tbody>
				<tr><td style="width:150px">SMTP Host</td><td><code>smtp.pepipost.com</code></td></tr>
				<tr><td>SMTP Port</td><td><code>25</code> / <code>587</code> / <code>2525</code></td></tr>
				<tr><td>Encryption</td><td>TLS</td></tr>
				<tr><td>Username</td><td><em>your.pepipost.username</em></td></tr>
				<tr><td>Password</td><td><em>your smtp password</em></td></tr>
				</tbody></table><br />
				Get your API key from <a href="https://app1.pepipost.com/index.php/settings/index" target="_blank">here</a>.<br />
				For more information see <a href="http://support.pepipost.com/knowledge_base/topics/smtp-docs" target="_blank">here</a>.</p>',
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( ! GNETWORK_MAIL_LOG_DIR ) {

			HTML::desc( _x( 'Logging emails disabled by constant.', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );

		} else if ( $this->options['log_all'] ) {

			if ( ! is_dir( GNETWORK_MAIL_LOG_DIR ) || ! wp_is_writable( GNETWORK_MAIL_LOG_DIR ) ) {

				HTML::desc( _x( 'Log folder not exists or writable.', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );

				echo '<p>';
					Settings::submitButton( 'create_log_folder', _x( 'Create Log Folder', 'Modules: Mail', GNETWORK_TEXTDOMAIN ), 'small' );
				echo '</p>';

			} else {

				HTML::desc( sprintf( _x( 'Log folder exists and writable on: <code>%s</code>', 'Modules: Mail', GNETWORK_TEXTDOMAIN ), GNETWORK_MAIL_LOG_DIR ) );

				if ( ! file_exists( GNETWORK_MAIL_LOG_DIR.'/.htaccess' ) )
					HTML::desc( _x( 'Warning: <code>.htaccess</code> not found!', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );
			}

		} else {
			HTML::desc( _x( 'Logging emails disabled.', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );
		}
	}

	public function settings( $sub = NULL )
	{
		if ( $this->key == $sub ) {

			if ( GNETWORK_MAIL_LOG_DIR
				&& isset( $_POST['create_log_folder'] ) ) {

				$this->check_referer( $sub );

				$created = File::putHTAccessDeny( GNETWORK_MAIL_LOG_DIR, TRUE );

				WordPress::redirectReferer( ( FALSE === $created ? 'wrong' : 'maked' ) );

			} else {
				parent::settings( $sub );
			}

		} else if ( 'testmail' == $sub ) {

			// FIXME: move test email here

		} else if ( 'emaillogs' == $sub ) {

			if ( GNETWORK_MAIL_LOG_DIR
				&& ! empty( $_POST )
				&& 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub );

				// TODO: add exporting to .eml files
				// http://stackoverflow.com/a/16039103/4864081
				// http://stackoverflow.com/a/8777197/4864081
				// http://www.alexcasamassima.com/2013/02/send-pre-formatted-eml-file-in-php.html
				// https://wiki.zarafa.com/index.php/Eml_vs_msg

				if ( isset( $_POST['deletelogs_all'] ) ) {

					WordPress::redirectReferer( ( FALSE === self::deleteEmailLogs() ? 'error' : 'purged' ) );

				} else if ( isset( $_POST['deletelogs_selected'], $_POST['_cb'] ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $log )
						if ( TRUE === unlink( GNETWORK_MAIL_LOG_DIR.'/'.$log.'.json' ) )
							$count++;

					WordPress::redirectReferer( [
						'message' => 'deleted',
						'count'   => $count,
					] );

				} else {
					WordPress::redirectReferer( 'wrong' );
				}
			}

			add_action( $this->settings_hook( $sub ), [ $this, 'settings_form_emaillogs' ], 10, 2 );

			$this->register_button( 'deletelogs_selected', _x( 'Delete Selected', 'Modules: Mail', GNETWORK_TEXTDOMAIN ), TRUE );
			$this->register_button( 'deletelogs_all', _x( 'Delete All', 'Modules: Mail', GNETWORK_TEXTDOMAIN ), FALSE, TRUE );
		}
	}

	public function settings_form_emaillogs( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', FALSE );

			if ( self::tableEmailLogs() )
				$this->settings_buttons( $sub );

		$this->settings_form_after( $uri, $sub );
	}

	public function wp_mail_from( $email )
	{
		if ( 0 === strpos( $email, 'wordpress@' ) )
			$email = $this->get_from_email( $email );

		return $this->filters( 'from_email', $email );
	}

	public function wp_mail_from_name( $name )
	{
		if ( 0 === strpos( $name, 'WordPress' ) )
			$name = $this->get_from_name( $name );

		return $this->filters( 'from_name', $name );
	}

	public function bp_email( $email_type, $bp_email )
	{
		$bp_email->set_reply_to( $this->get_from_email(), $this->get_from_name() );
	}

	public function get_from_email( $email = '' )
	{
		if ( $blog = gNetwork()->option( 'from_email', 'blog' ) )
			return $blog;

		if ( ! empty( $this->options['from_email'] ) )
			return $this->options['from_email'];

		return get_site_option( 'admin_email', $email );
	}

	public function get_from_name( $name = '' )
	{
		if ( $blog = gNetwork()->option( 'from_name', 'blog' ) )
			return $blog;

		if ( ! empty( $this->options['from_name'] ) )
			return $this->options['from_name'];

		if ( is_multisite() )
			return get_site_option( 'site_name', $name );

		return get_option( 'blogname', $name );
	}

	// http://phpmailer.worxware.com/?pg=properties
	// http://stackoverflow.com/questions/6315052/use-of-phpmailer-class
	public function phpmailer_init( &$phpmailer )
	{
		$phpmailer->Mailer = $this->options['mailer'];
		$phpmailer->Hostname = WordPress::getHostName();

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
		}
	}

	// $mail = [ 'to', 'subject', 'message', 'headers', 'attachments' ];
	public function wp_mail( $mail )
	{
		if ( ! GNETWORK_MAIL_LOG_DIR )
			return $mail;

		$contents = array_merge( [
			'timestamp' => current_time( 'mysql' ),
			'blog'      => WordPress::currentBlog(),
			'locale'    => get_locale(),
			// TODO: get smtp server as well
		], Arraay::filterArray( $mail ) );

		if ( is_rtl() )
			$contents['rtl'] = 'true';

		if ( is_array( $contents['to'] ) )
			$to = array_filter( [ 'geminorum\\gNetwork\\Core\\File', 'escFilename' ], $contents['to'] );
		else
			$to = File::escFilename( $contents['to'] );

		$filename = current_time( 'Ymd-His' ).'-'.$to.'.json';

		if ( FALSE === File::putContents( $filename, wp_json_encode( $contents ), GNETWORK_MAIL_LOG_DIR ) )
			Logger::CRITICAL( 'EMAIL-LOGS: CAN NOT LOG EMAIL TO: '.$contents['to'] );

		return $mail;
	}

	public function bp_send_email_success( $status, $email )
	{
		$mail = [
			'subject' => $email->get_subject( 'replace-tokens' ),
			'message' => \PHPMailer::normalizeBreaks( $email->get_content_plaintext( 'replace-tokens' ) ),
		];

		foreach ( $email->get_to() as $recipient )
			$mail['to'][] = $recipient->get_address();

		foreach ( $email->get_headers() as $name => $content )
			$mail['headers'][] = sprintf( '%s: %s', $name, $content );

		$this->wp_mail( $mail );
	}

	public function testmail_form()
	{
		$to = isset( $_POST['gnetwork_mail_testmail_to'] ) ? $_POST['gnetwork_mail_testmail_to'] : $this->get_from_email();
		$message = isset( $_POST['gnetwork_mail_testmail_message'] ) ? $_POST['gnetwork_mail_testmail_message'] : _x( 'This is a test email generated by the gNetwork Mail plugin.', 'Modules: Mail', GNETWORK_TEXTDOMAIN );
		$subject = isset( $_POST['gnetwork_mail_testmail_subject'] ) ? $_POST['gnetwork_mail_testmail_subject'] : _x( 'Test mail to ', 'Modules: Mail', GNETWORK_TEXTDOMAIN ).$to;

		echo '<table class="form-table"><tbody>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_to">';
				_ex( 'To', 'Modules: Mail', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><input type="text" id="gnetwork_mail_testmail_to" name="gnetwork_mail_testmail_to" value="'.$to.'" class="regular-text code" />';
				// HTML::desc( _x( 'Type an email address here and then click Send Test to generate a test email.', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );
			echo '</td></tr>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_subject">';
				_ex( 'Subject', 'Modules: Mail', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><input type="text" id="gnetwork_mail_testmail_subject" name="gnetwork_mail_testmail_subject" value="'.$subject.'" class="regular-text code" />';
				// HTML::desc( _x( 'Type an email address here and then click Send Test to generate a test email.', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );
			echo '</td></tr>';
			echo '<tr><th scope="row"><label for="gnetwork_mail_testmail_message">';
				_ex( 'Message:', 'Modules: Mail', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td><textarea id="gnetwork_mail_testmail_message" name="gnetwork_mail_testmail_message" cols="45" rows="5" class="large-text" >'.$message.'</textarea>';
				// HTML::desc( _x( 'Type an email address here and then click Send Test to generate a test email.', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );
			echo '</td></tr>';
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
				$phpmailer = new \PHPMailer( TRUE );
			}

			$phpmailer->SMTPDebug = TRUE;

			ob_start();

			$result = wp_mail(
				$_POST['gnetwork_mail_testmail_to'],
				$_POST['gnetwork_mail_testmail_subject'],
				stripslashes( $_POST['gnetwork_mail_testmail_message'] ) );

			$smtp_debug = ob_get_clean();

			echo '<div id="m1essage" class="'.( FALSE === $result ? 'error' : 'updated' ).'"><p><strong>';
				_ex( 'Test Message Sent', 'Modules: Mail', GNETWORK_TEXTDOMAIN );
			echo '</strong></p><p>';
				_ex( 'The result was:', 'Modules: Mail', GNETWORK_TEXTDOMAIN );
			echo '</p>';
				self::dump( $result );
			echo '<p>';
				_ex('The SMTP debugging output:', 'Modules: Mail', GNETWORK_TEXTDOMAIN );
			echo '</p><pre>';
				echo $smtp_debug;
			echo '</pre><p>';
				_ex('The full debugging output:', 'Modules: Mail', GNETWORK_TEXTDOMAIN );
			echo '</p>';
				HTML::tableSide( $phpmailer );
			echo '</div>';

			unset( $phpmailer );
		}
	}

	// @SOURCE: http://stackoverflow.com/a/14744288/4864081
	protected static function getEmailLogs( $limit, $paged = 1, $ext = 'json', $old = NULL, $path = GNETWORK_MAIL_LOG_DIR )
	{
		if ( ! $path )
			return [ [], [] ];

		$files = glob( File::normalize( $path.'/*.'.$ext ) );

		if ( FALSE === $files || ! count( $files ) )
			return [ [], [] ];

		$i    = 0;
		$logs = [];

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

			if ( $data = json_decode( File::getContents( $log ), TRUE ) )
				$logs[] = array_merge( [
					'file' => basename( $log, '.json' ),
					'size' => filesize( $log ),
					'date' => filemtime( $log ),
				], $data );

			$i++;
		}

		$pagination = HTML::tablePagination( count( $files ), $pages, $limit, $paged );

		return [ $logs, $pagination ];
	}

	protected static function deleteEmailLogs( $path = GNETWORK_MAIL_LOG_DIR )
	{
		if ( ! $path )
			return FALSE;

		try {

			// @SOURCE: http://stackoverflow.com/a/4594268/4864081
			foreach ( new \DirectoryIterator( $path ) as $file )
				if ( ! $file->isDot() )
					unlink( $file->getPathname() );

		} catch ( Exception $e ) {
			// echo 'Caught exception: '.$e->getMessage().'<br/>';
		}

		return File::putHTAccessDeny( $path, FALSE );
	}

	private static function tableEmailLogs()
	{
		list( $logs, $pagination ) = self::getEmailLogs( self::limit(), self::paged() );

		if ( ! count( $logs ) ) {

			if ( ! is_dir( GNETWORK_MAIL_LOG_DIR ) || ! wp_is_writable( GNETWORK_MAIL_LOG_DIR ) )
				echo HTML::error( _x( 'Log folder not exists or writable.', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );

			else
				echo HTML::warning( _x( 'No Logs!', 'Modules: Mail', GNETWORK_TEXTDOMAIN ) );

			return FALSE;
		}

		return HTML::tableList( [
			'_cb' => 'file',

			'info' => [
				'title'    => _x( 'Whom, When', 'Modules: Mail: Email Logs Table Column', GNETWORK_TEXTDOMAIN ),
				'class'    => '-column-info',
				'callback' => function( $value, $row, $column, $index ){
					$info = '';

					if ( isset( $row['to'] ) ) {

						if ( is_array( $row['to'] ) ) {
							foreach ( $row['to'] as $to )
								$info .= HTML::mailto( $to ).' ';

						} else if ( Text::has( $row['to'], ',' ) ) {
							foreach ( explode( ',', $row['to'] ) as $to )
								$info .= HTML::mailto( $to ).' ';

						} else {
							$info .= HTML::mailto( $row['to'] ).' ';
						}
					}

					if ( isset( $row['timestamp'] ) )
						$info .= '&ndash; '.Utilities::htmlHumanTime( $row['timestamp'] );

					if ( isset( $row['headers'] ) ) {
						$info .= '<hr />';
						if ( ! is_array( $row['headers'] ) )
							$row['headers'] = explode( "\n", $row['headers']  );

						foreach ( array_filter( $row['headers'] ) as $header )
							$info .= '<code>'.HTML::escapeTextarea( $header ).'</code><br />';
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
			],

			'content' => [
				'title'    => _x( 'What', 'Modules: Mail: Email Logs Table Column', GNETWORK_TEXTDOMAIN ),
				'class'    => '-column-content',
				'callback' => function( $value, $row, $column, $index ){
					$content   = '';
					$direction = isset( $row['rtl'] ) ? ' dir="rtl"' : '';

					if ( isset( $row['subject'] ) )
						$content .= '<code>'._x( 'Subject', 'Modules: Mail: Email Logs Table Prefix', GNETWORK_TEXTDOMAIN ).'</code> <span'
							.$direction.'>'.$row['subject'].'</span><hr />';

					// FIXME: check headers for text/html and not escape!

					if ( isset( $row['message'] ) )
						$content .= '<div'.$direction.'>'
							.wpautop( make_clickable( HTML::escapeTextarea( $row['message'] ) ) ).'</div>';

					return $content;
				},
			],
		], $logs, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', sprintf( _x( 'Total %s Email Logs', 'Modules: Mail', GNETWORK_TEXTDOMAIN ), Number::format( $pagination['total'] ) ) ),
			'pagination' => $pagination,
		] );
	}
}

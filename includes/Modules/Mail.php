<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

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

	protected $key  = 'mail';
	protected $ajax = TRUE;

	protected function setup_actions()
	{
		if ( GNETWORK_MAIL_LOG_DIR && $this->options['log_all'] ) {
			$this->filter( 'wp_mail', 1, 99 );
			$this->action( 'bp_send_email_success', 2, 99 );
			$this->_hook_post( TRUE, $this->hook( 'logs' ), 'log_actions' );
		}

		$this->filter( 'wp_mail_from', 1, 5 );
		$this->filter( 'wp_mail_from_name', 1, 5 );
		$this->action( 'bp_email', 2, 5 );

		$this->action( 'phpmailer_init' );
		$this->action( 'wp_mail_failed' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Mail', 'Modules: Menu Name', 'gnetwork' ) );
		$this->register_tool( _x( 'Test Mail', 'Modules: Menu Name', 'gnetwork' ), 'testmail', 16 );

		if ( GNETWORK_MAIL_LOG_DIR && $this->options['log_all'] )
			$this->register_tool( _x( 'Email Logs', 'Modules: Menu Name', 'gnetwork' ), 'emaillogs', 15, NULL, FALSE );
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
					'title'       => _x( 'From Email', 'Modules: Mail: Settings', 'gnetwork' ),
					'description' => _x( 'Specifies the email address that emails should be sent from. Leave blank for default.', 'Modules: Mail: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'from_name',
					'type'        => 'text',
					'title'       => _x( 'From Name', 'Modules: Mail: Settings', 'gnetwork' ),
					'description' => _x( 'Specifies the name that emails should be sent from. Leave blank for WordPress.', 'Modules: Mail: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'sender',
					'type'        => 'text',
					'title'       => _x( 'Return Path', 'Modules: Mail: Settings', 'gnetwork' ),
					/* translators: %s: `from` placeholder */
					'description' => sprintf( _x( 'Sets the return-path email address. Use %s to match the From Email or empty to disable.', 'Modules: Mail: Settings', 'gnetwork' ), '<code>FROM</code>' ),
					'default'     => 'FROM',
					'field_class' => [ 'regular-text', 'email-text' ],
				],
				[
					'field'   => 'mailer',
					'type'    => 'radio',
					'title'   => _x( 'Mailer', 'Modules: Mail: Settings', 'gnetwork' ),
					'default' => 'mail',
					'values'  => [
						'mail' => _x( 'Use the PHP mail() function to send emails.', 'Modules: Mail: Settings', 'gnetwork' ),
						'smtp' => _x( 'Sends all WordPress emails via SMTP.', 'Modules: Mail: Settings', 'gnetwork' ),
					],
				],
			],
			'_smtp' => [
				[
					'field'       => 'smtp_host',
					'type'        => 'text',
					'title'       => _x( 'SMTP Host', 'Modules: Mail: Settings', 'gnetwork' ),
					'field_class' => [ 'regular-text', 'code' ],
					'default'     => 'localhost',
				],
				[
					'field'       => 'smtp_port',
					'type'        => 'number',
					'title'       => _x( 'SMTP Port', 'Modules: Mail: Settings', 'gnetwork' ),
					'field_class' => [ 'small-text', 'code' ],
					'default'     => '25',
				],
				[
					'field'       => 'smtp_secure',
					'type'        => 'radio',
					'title'       => _x( 'Encryption', 'Modules: Mail: Settings', 'gnetwork' ),
					'description' => _x( 'For most servers SSL is the recommended option.', 'Modules: Mail: Settings', 'gnetwork' ),
					'default'     => 'no',
					'values'      => [
						'no'  => _x( 'No encryption.', 'Modules: Mail: Encryption Option', 'gnetwork' ),
						'ssl' => _x( 'Use SSL encryption.', 'Modules: Mail: Encryption Option', 'gnetwork' ),
						'tls' => _x( 'Use TLS encryption. This is not the same as STARTTLS.', 'Modules: Mail: Encryption Option', 'gnetwork' ),
					],
				],
				[
					'field'       => 'smtp_username',
					'type'        => 'text',
					'title'       => _x( 'Username', 'Modules: Mail: Settings', 'gnetwork' ),
					'description' => _x( 'Empty to disable Authentication.', 'Modules: Mail: Settings', 'gnetwork' ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'smtp_password',
					'type'        => 'text',
					'title'       => _x( 'Password', 'Modules: Mail: Settings', 'gnetwork' ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
			],
		];

		if ( GNETWORK_MAIL_LOG_DIR )
			$settings['_log'] = [
				[
					'field'       => 'log_all',
					'title'       => _x( 'Log All', 'Modules: Mail: Settings', 'gnetwork' ),
					'description' => _x( 'Logs all out-going emails in a secure folder.', 'Modules: Mail: Settings', 'gnetwork' ),
				],
			];

		return $settings;
	}

	public function settings_section_smtp()
	{
		Settings::fieldSection(
			_x( 'SMTP Settings', 'Modules: Mail: Settings', 'gnetwork' ),
			_x( 'These options only apply if you have chosen to send mail by SMTP above.', 'Modules: Mail: Settings', 'gnetwork' )
		);
	}

	public function settings_section_log()
	{
		Settings::fieldSection(
			_x( 'Log Settings', 'Modules: Mail: Settings', 'gnetwork' )
		);
	}

	protected function register_help_sidebar( $sub = NULL, $context = 'settings' )
	{
		return [
			[
				'title' => 'Troubleshooting PHPMailer',
				'url'   => 'https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting',
			]
		];
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [
			[
				'id'      => $this->classs( 'help-gmail' ),
				'title'   => _x( 'Gmail SMTP', 'Modules: Mail: Help Tab Title', 'gnetwork' ),
				'content' => '<p><table><tbody>
				<tr><td style="width:150px">SMTP Host</td><td><code>smtp.gmail.com</code></td></tr>
				<tr><td>SMTP Port</td><td><code>465</code></td></tr>
				<tr><td>Encryption</td><td>SSL</td></tr>
				<tr><td>Username</td><td><em>your.gmail@gmail.com</em></td></tr>
				<tr><td>Password</td><td><em>yourpassword</em></td></tr>
				</tbody></table><br />
				For more information see <a href="http://www.wpbeginner.com/plugins/how-to-send-email-in-wordpress-using-the-gmail-smtp-server/" target="_blank" rel="noreferrer">here</a>.</p>',
			],
			[
				'id'      => $this->classs( 'help-pepipost' ),
				'title'   => _x( 'Pepipost SMTP', 'Modules: Mail: Help Tab Title', 'gnetwork' ),
				'content' => '<p><table><tbody>
				<tr><td style="width:150px">SMTP Host</td><td><code>smtp.pepipost.com</code></td></tr>
				<tr><td>SMTP Port</td><td><code>25</code> / <code>587</code> / <code>2525</code></td></tr>
				<tr><td>Encryption</td><td>TLS</td></tr>
				<tr><td>Username</td><td><em>your.pepipost.username</em></td></tr>
				<tr><td>Password</td><td><em>your smtp password</em></td></tr>
				</tbody></table><br />
				Get your API key from <a href="https://app.pepipost.com" target="_blank" rel="noreferrer">here</a>.<br />
				For more information see <a href="https://docs.pepipost.com/documentation/smtp-integration/" target="_blank" rel="noreferrer">here</a>.</p>',
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		echo $this->wrap_open_buttons();

		echo HTML::tag( 'a', [
			'class' => 'button button-secondary button-small',
			'href'  => $this->get_menu_url( 'testmail', NULL, 'tools' ),
		], _x( 'Test Mail', 'Modules: Mail', 'gnetwork' ) );

		if ( GNETWORK_MAIL_LOG_DIR && $this->options['log_all'] ) {

			echo '&nbsp;';

			echo HTML::tag( 'a', [
				'class' => 'button button-secondary button-small',
				'href'  => $this->get_menu_url( 'emaillogs', NULL, 'tools' ),
			], _x( 'Email Logs', 'Modules: Mail', 'gnetwork' ) );
		}

		echo '</p>';

		Utilities::buttonDataLogs( GNETWORK_MAIL_LOG_DIR, $this->options['log_all'] );
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( GNETWORK_MAIL_LOG_DIR
			&& isset( $_POST['create_log_folder'] ) ) {

			$this->check_referer( $sub, 'settings' );

			$created = File::putHTAccessDeny( GNETWORK_MAIL_LOG_DIR, TRUE );

			WordPress::redirectReferer( ( FALSE === $created ? 'wrong' : 'maked' ) );
		}
	}

	public function tools( $sub = NULL, $key = NULL )
	{
		if ( in_array( $sub, [ 'testmail', 'emaillogs' ] ) )
			parent::tools( $sub, TRUE );
	}

	protected function tools_buttons( $sub = NULL )
	{
		if ( 'testmail' == $sub ) {

			$this->register_button( 'send_testmail', _x( 'Send Test Mail', 'Modules: Mail', 'gnetwork' ), TRUE );

		} else if ( 'emaillogs' == $sub ) {

			$this->register_button( 'deletelogs_selected', _x( 'Delete Selected', 'Modules: Mail', 'gnetwork' ), TRUE );
			$this->register_button( 'deletelogs_all', _x( 'Delete All', 'Modules: Mail', 'gnetwork' ), FALSE, TRUE );
		}
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( 'emaillogs' == $sub ) {

			if ( GNETWORK_MAIL_LOG_DIR
				&& ! empty( $_POST )
				&& 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub, 'tools' );

				// TODO: add exporting to .eml files
				// http://stackoverflow.com/a/16039103
				// http://stackoverflow.com/a/8777197
				// http://www.alexcasamassima.com/2013/02/send-pre-formatted-eml-file-in-php.html
				// https://wiki.zarafa.com/index.php/Eml_vs_msg

				if ( self::isTablelistAction( 'deletelogs_all' ) ) {

					WordPress::redirectReferer( ( FALSE === File::emptyDir( GNETWORK_MAIL_LOG_DIR, TRUE ) ? 'error' : 'purged' ) );

				} else if ( self::isTablelistAction( 'deletelogs_selected', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $log )
						if ( TRUE === unlink( File::join( GNETWORK_MAIL_LOG_DIR, $log.'.json' ) ) )
							$count++;

					WordPress::redirectReferer( [
						'message' => 'deleted',
						'count'   => $count,
					] );

				} else {

					WordPress::redirectReferer( 'wrong' );
				}
			}
		}
	}

	public function render_tools( $uri, $sub = 'general' )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'tools' );

		if ( 'testmail' == $sub ) {

			if ( $this->tableTestMail() )
				$this->render_form_buttons( $sub );

		} else if ( 'emaillogs' == $sub ) {

			if ( $this->tableEmailLogs() )
				$this->render_form_buttons( $sub );
		}

		$this->render_form_end( $uri, $sub );
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

		return get_network_option( NULL, 'admin_email', $email );
	}

	public function get_from_name( $name = '' )
	{
		if ( $blog = gNetwork()->option( 'from_name', 'blog' ) )
			return $blog;

		if ( ! empty( $this->options['from_name'] ) )
			return $this->options['from_name'];

		if ( is_multisite() )
			return get_network_option( NULL, 'site_name', $name );

		return get_option( 'blogname', $name );
	}

	// http://phpmailer.worxware.com/?pg=properties
	// http://stackoverflow.com/questions/6315052/use-of-phpmailer-class
	public function phpmailer_init( &$phpmailer )
	{
		$phpmailer->Mailer   = $this->options['mailer'];
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

	public function wp_mail_failed( $error )
	{
		Logger::CRITICAL( 'EMAIL-FAILED', $error->get_error_message() );
	}

	// [ 'to', 'subject', 'message', 'headers', 'attachments' ]
	private function do_log_mail( $atts )
	{
		$contents = array_merge( [
			'timestamp' => current_time( 'mysql' ),
			'site'      => WordPress::currentSiteName(),
			'locale'    => get_locale(),
			'user'      => get_current_user_id(),
		], Arraay::filterArray( $atts ) );

		if ( is_rtl() )
			$contents['rtl'] = 'true';

		if ( 'smtp' == $this->options['mailer'] )
			$contents['smtp'] = sprintf( '%s::%s', $this->options['smtp_host'], $this->options['smtp_port'] );

		$recipient = empty( $contents['to'] ) ? 'UNDEFINED' : $contents['to'];

		if ( is_array( $recipient ) )
			$recipient = array_shift( $recipient );

		$filename = File::escFilename( sprintf( '%s-%s', current_time( 'Ymd-His' ), $recipient ) ).'.json';
		$logged   = wp_json_encode( $contents, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		if ( FALSE === File::putContents( $filename, $logged, GNETWORK_MAIL_LOG_DIR ) )
			return Logger::FAILED( sprintf( 'EMAIL-LOGS: can not log email to: %s', $recipient ) );

		Logger::INFO( sprintf( 'EMAIL-LOGS: logged email to: %s', $recipient ) );
	}

	public function wp_mail( $mail )
	{
		$this->do_log_mail( $mail );
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

		$this->do_log_mail( $mail );
	}

	public function log_actions()
	{
		if ( ! WordPress::cuc( $this->is_network() ? 'manage_network_options' : 'manage_options' ) )
			WordPress::cheatin();

		if ( ! $log = self::req( 'log' ) )
			WordPress::redirectReferer( 'wrong' );

		$file = File::join( GNETWORK_MAIL_LOG_DIR, $log.'.json' );

		switch ( self::req( 'what' ) ) {

			case 'download':

				if ( ! File::download( $file, $log.'.json' ) )
					WordPress::redirectReferer( 'wrong' );

			break;
			case 'delete':

				if ( TRUE === unlink( $file ) )
					WordPress::redirectReferer( [ 'message' => 'deleted', 'count' => 1 ] );
		}

		WordPress::redirectReferer( 'wrong' );
	}

	private function tableTestMail()
	{
		$to      = isset( $_POST['send_testmail_to'] ) ? $_POST['send_testmail_to'] : $this->get_from_email();
		/* translators: %s: site name */
		$subject = isset( $_POST['send_testmail_subject'] ) ? $_POST['send_testmail_subject'] : sprintf( _x( '[%s] Test Mail', 'Modules: Mail', 'gnetwork' ), WordPress::getSiteNameforEmail() );
		$message = isset( $_POST['send_testmail_message'] ) ? $_POST['send_testmail_message'] : _x( 'This is a test email generated by the gNetwork Mail plugin.', 'Modules: Mail', 'gnetwork' );

		echo '<table class="form-table"><tbody>';
			echo '<tr><th scope="row"><label for="send_testmail_to">';
				_ex( 'To', 'Modules: Mail', 'gnetwork' );
			echo '</label></th><td><input type="text" id="send_testmail_to" name="send_testmail_to" value="'.$to.'" class="regular-text code" />';
			echo Settings::fieldAfterIcon( 'https://www.mail-tester.com/', 'Test the Spammyness of your Emails' );
			echo '</td></tr>';
			echo '<tr><th scope="row"><label for="send_testmail_subject">';
				_ex( 'Subject', 'Modules: Mail', 'gnetwork' );
			echo '</label></th><td><input type="text" id="send_testmail_subject" name="send_testmail_subject" value="'.$subject.'" class="regular-text" />';
			echo '</td></tr>';
			echo '<tr><th scope="row"><label for="send_testmail_message">';
				_ex( 'Message', 'Modules: Mail', 'gnetwork' );
			echo '</label></th><td><textarea id="send_testmail_message" name="send_testmail_message" cols="45" rows="5" class="large-text">'.$message.'</textarea>';
			echo '</td></tr>';
		echo '</tbody></table>';

		$this->sendTestMail();

		return TRUE;
	}

	private function sendTestMail()
	{
		global $phpmailer;

		if ( isset( $_POST['send_testmail'] )
			&& isset( $_POST['send_testmail_to'] ) ) {

			$this->check_referer( 'testmail', 'tools' );

			$this->action( 'phpmailer_init', 1, 20, 'debug' );

			ob_start();

			$result = wp_mail(
				$_POST['send_testmail_to'],
				$_POST['send_testmail_subject'],
				stripslashes( $_POST['send_testmail_message'] )
			);

			$smtp_debug = ob_get_clean();

			$classes = 'notice-'.( FALSE === $result ? 'error' : 'success' ).' fade inline';
			echo HTML::notice( _x( 'Test message sent.', 'Modules: Mail', 'gnetwork' ), $classes, FALSE );

			HTML::desc( _x( 'The result was:', 'Modules: Mail', 'gnetwork' ) );
			HTML::dump( $result );

			echo '<hr />';

			HTML::desc( _x('The SMTP debugging output:', 'Modules: Mail', 'gnetwork' ) );
			HTML::dump( $smtp_debug );

			echo '<hr />';

			HTML::desc( _x('The full debugging output:', 'Modules: Mail', 'gnetwork' ) );
			HTML::tableSide( $phpmailer );

			unset( $phpmailer );
		}
	}

	public function phpmailer_init_debug( &$phpmailer )
	{
		$phpmailer->SMTPDebug = TRUE;
	}

	private function tableEmailLogs()
	{
		list( $logs, $pagination ) = Utilities::getDataLogs( GNETWORK_MAIL_LOG_DIR, self::limit(), self::paged() );

		if ( empty( $logs ) )
			return Utilities::emptyDataLogs( GNETWORK_MAIL_LOG_DIR );

		return HTML::tableList( [
			'_cb' => 'file',

			'info' => [
				'title'    => _x( 'Whom, When', 'Modules: Mail: Email Logs Table Column', 'gnetwork' ),
				'class'    => '-column-info',
				'callback' => function( $value, $row, $column, $index ){
					$html = '';

					if ( ! empty( $row['to'] ) ) {

						if ( is_array( $row['to'] ) ) {

							foreach ( $row['to'] as $to )
								$html.= HTML::mailto( $to, NULL, 'code' ).' ';

						} else if ( Text::has( $row['to'], ',' ) ) {

							foreach ( explode( ',', $row['to'] ) as $to )
								$html.= HTML::mailto( $to, NULL, 'code' ).' ';

						} else {

							$html.= HTML::mailto( $row['to'], NULL, 'code' ).' ';
						}
					}

					if ( ! empty( $row['timestamp'] ) )
						$html.= '&ndash; '.Utilities::htmlHumanTime( $row['timestamp'] );

					if ( $html )
						$html.= '<hr />';

					if ( ! empty( $row['user'] ) )
						$html.= '<code title="'._x( 'User', 'Modules: Mail: Email Logs Table', 'gnetwork' )
							.'">'.HTML::link( get_user_by( 'id', $row['user'] )->user_login, WordPress::getUserEditLink( $row['user'] ) ).'</code> @ ';

					if ( ! empty( $row['site'] ) )
						$html.= '<code title="'._x( 'Site', 'Modules: Mail: Email Logs Table', 'gnetwork' )
							.'">'.$row['site'].'</code>';

					if ( ! empty( $row['smtp'] ) )
						$html.= ' &#5397; <code title="'._x( 'SMTP', 'Modules: Mail: Email Logs Table', 'gnetwork' )
							.'">'.$row['smtp'].'</code>';

					if ( ! empty( $row['headers'] ) ) {
						$html.= '<hr />';

						if ( ! is_array( $row['headers'] ) )
							$row['headers'] = explode( "\n", $row['headers']  );

						foreach ( array_filter( $row['headers'] ) as $header )
							$html.= '<code class="-header">'.HTML::escapeTextarea( $header ).'</code><br />';
					}

					if ( ! empty( $row['attachments'] ) ) {
						$html.= '<hr />';

						if ( is_array( $row['attachments'] ) ) {

							foreach ( array_filter( $row['attachments'] ) as $attachment )
								$html.= '<code class="-attachment">'.$attachment.'</code><br />';

						} else if ( $row['attachments'] ) {
							$html.= '<code class="-attachment">'.$row['attachments'].'</code><br />';
						}
					}

					return HTML::wrap( $html, '-info' );
				},
				'actions' => function( $value, $row, $column, $index, $key, $args ){

					return [
						'download' => HTML::tag( 'a', [
							'href'  => WordPress::getAdminPostLink( $this->hook( 'logs' ), [ 'log' => $row['file'], 'what' => 'download' ] ),
							'class' => '-link -row-link -row-link-download',
						], _x( 'Download', 'Modules: Mail: Row Action', 'gnetwork' ) ),

						'delete' => HTML::tag( 'a', [
							'href'  => WordPress::getAdminPostLink( $this->hook( 'logs' ), [ 'log' => $row['file'], 'what' => 'delete' ] ),
							'class' => '-link -row-link -row-link-delete',
						], _x( 'Delete', 'Modules: Mail: Row Action', 'gnetwork' ) ),
					];
				},
			],

			'content' => [
				'title'    => _x( 'What', 'Modules: Mail: Email Logs Table Column', 'gnetwork' ),
				'class'    => '-column-content',
				'callback' => function( $value, $row, $column, $index ){
					$content   = '';
					$direction = empty( $row['rtl'] ) ? '' : ' style="direction:rtl"';

					if ( ! empty( $row['subject'] ) )
						$content.= _x( 'Subject', 'Modules: Mail: Email Logs Table Prefix', 'gnetwork' ).': <code'
							.$direction.'>'.$row['subject'].'</code><hr />';

					if ( ! empty( $row['message'] ) ) {

						if ( $this->hasHeader( $row, 'Content-Type: text/html' ) )
							$content.= '<div'.$direction.'>'.$row['message'].'</div>';
						else
							$content.= '<div'.$direction.'>'.wpautop( make_clickable( nl2br( $row['message'] ) ) ).'</div>';
					}

					return $content ?: Utilities::htmlEmpty();
				},
			],
		], $logs, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Email Logs', 'Modules: Mail', 'gnetwork' ) ),
			'pagination' => $pagination,
		] );
	}

	private function hasHeader( $mail, $needle )
	{
		if ( empty( $mail['headers'] ) )
			return FALSE;

		foreach ( (array) $mail['headers'] as $header )
			if ( Text::has( $header, $needle ) )
				return TRUE;

		return FALSE;
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Date;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Cron extends gNetwork\Module
{
	protected $key     = 'cron';
	protected $network = FALSE;
	protected $front   = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		if ( ! is_blog_admin() )
			return;

		$this->action( 'init' );

		add_action( $this->hook( 'run' ), [ $this, 'do_email_admin' ], 10, 2 );

		if ( $this->options['dashboard_widget'] )
			$this->action( 'wp_dashboard_setup' );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'CRON', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);

		Admin::registerMenu( 'scheduled',
			_x( 'Scheduled', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN )
		);
	}

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function default_options()
	{
		return [
			'dashboard_widget'     => '0',
			'dashboard_accesscap'  => 'edit_theme_options',
			'dashboard_intro'      => '',
			'status_email_failure' => '0',
			'status_email_address' => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'dashboard_widget',
					'title'       => _x( 'Dashboard Widget', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds dashboard widget with ability to check WP-Cron is working.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'dashboard_accesscap',
					'type'        => 'cap',
					'title'       => _x( 'Access Level', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Selected and above can view the dashboard widget.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'edit_theme_options',
				],
				[
					'field'       => 'dashboard_intro',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Widget Introduction ', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Message to display before status check form on admin dashbaord widget.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'status_email_failure',
					'title'       => _x( 'Email Failure', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Notifies the administrator status check failure via email.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'status_email_address',
					'type'        => 'email',
					'title'       => _x( 'Admin Email', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Emails send to this address. Leave empty for WordPress admin email.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => empty( $this->options['status_email_address'] ) ? Settings::fieldAfterEmail( get_option( 'admin_email' ) ) : FALSE,
				],
			],
		];
	}

	public function settings( $sub = NULL )
	{
		if ( 'scheduled' == $sub ) {

			if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub );

				if ( isset( $_POST['unschedule'], $_POST['_cb'] ) ) {

					$count = 0;
					$cron = self::getCronArray();

					foreach ( $_POST['_cb'] as $event )
						if ( self::unschedule( intval( $event ), $cron ) )
							$count++;

				} else {
					WordPress::redirectReferer( 'wrong' );
				}

				WordPress::redirectReferer( [
					'message' => 'deleted',
					'count'   => $count,
				] );
			}

			$this->register_button( 'unschedule', _x( 'Unschedule', 'Modules: CRON: Button', GNETWORK_TEXTDOMAIN ), TRUE );

			add_action( $this->settings_hook( $sub ), [ $this, 'settings_form_scheduled' ], 10, 2 );

		} else {
			parent::settings( $sub );
		}
	}

	public function settings_form_scheduled( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub, 'bulk' );

			// TODO: add info on DISABLE_WP_CRON
			// TODO: add info on url/path to wp-cron.php
			// TODO: adding wp-cron-multisite.php / much like emaillogs folder
			// SEE: https://www.lucasrolff.com/wordpress/why-wp-cron-sucks/

			if ( self::tableCronInfo() )
				$this->settings_buttons( $sub );

		$this->settings_form_after( $uri, $sub );
	}

	public function init()
	{
		$this->do_status_check();
	}

	public function wp_dashboard_setup()
	{
		if ( WordPress::cuc( $this->options['dashboard_accesscap'] ) ) {

			wp_add_dashboard_widget(
				$this->classs( 'dashboard' ),
				_x( 'WP-Cron Status Check', 'Modules: CRON: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_status_check' ]
			);

			Utilities::enqueueScript( 'admin.cron.statuscheck' );
		}
	}

	public function ajax()
	{
		if ( ! check_ajax_referer( $this->classs( 'status-check' ), 'nonce', FALSE ) )
			wp_die();

		$this->do_status_check( TRUE );

		wp_send_json( [ 'html' => $this->get_status() ] );
	}

	private function get_status()
	{
		if ( $status = get_option( $this->hook( 'status' ) ) )
			return $status;

		return _x( 'WP-Cron Status Checker has not run yet.', 'Modules: CRON', GNETWORK_TEXTDOMAIN );
	}

	// run the check and update the status
	private function do_status_check( $forced = FALSE )
	{
		if ( ! $forced && get_transient( $this->classs( 'status' ) ) )
			return;

		set_transient( $this->classs( 'status' ), current_time( 'mysql' ), Date::DAY_IN_SECONDS );

		$result = $this->status_check_spawn();

		if ( is_wp_error( $result ) ) {

			if ( in_array( $result->get_error_code(), [ 'cron_disabled', 'cron_alternated' ] ) ) {

				update_option( $this->hook( 'status' ), '<span class="-status -notice">'.$result->get_error_message().'</span>' );

			} else {

				$message = sprintf( _x( '<p>While trying to spawn a call to the WP-Cron system, the following error occurred: %s</p>', 'Modules: CRON', GNETWORK_TEXTDOMAIN ),
					'<br><strong>'.esc_html( $result->get_error_message() ).'</strong>' );

				$message .= _x( '<p>This is a problem with your installation. If you need support, please contact your website host or post to the <a href="https://wordpress.org/support/forum/how-to-and-troubleshooting/">main WordPress support forum</a>.</p>', 'Modules: CRON', GNETWORK_TEXTDOMAIN );

				update_option( $this->hook( 'status' ), '<span class="-status -error">'.$message.'</span>' );
			}

		} else {

			$message = sprintf( _x( 'WP-Cron is working as of %s', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), Utilities::htmlCurrent() );

			update_option( $this->hook( 'status' ), '<span class="-status -success">'.$message.'</span>', FALSE );
		}

		do_action( $this->hook( 'run' ), $result, $forced );
	}

	// gets the status of WP-Cron functionality on the site by performing a test spawn
	private function status_check_spawn()
	{
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON )
			return new Error( 'cron_disabled', sprintf( _x( 'The DISABLE_WP_CRON constant is set to true as of %s. WP-Cron is disabled and will not run.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), Utilities::htmlCurrent() ) );

		if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON )
			return new Error( 'cron_alternated', sprintf( _x( 'The ALTERNATE_WP_CRON constant is set to true as of %s. We cannot determine the status of your WP-Cron system.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), Utilities::htmlCurrent() ) );

		$url  = site_url( 'wp-cron.php?doing_wp_cron='.sprintf( '%.22F', microtime( TRUE ) ) );
		$args = [ 'timeout' => 3, 'blocking' => TRUE ];

		$result = wp_remote_post( $url, $args );

		if ( is_wp_error( $result ) )
			return $result;

		$response = intval( wp_remote_retrieve_response_code( $result ) );

		if ( $response >= 300 )
			return new Error( 'unexpected_http_response_code', sprintf( _x( 'Unexpected HTTP response code: %s', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), $response ) );

		return TRUE;
	}

	// email the admin if the result is bad
	public function do_email_admin( $result, $forced )
	{
		if ( ! $forced && is_wp_error( $result ) && ! in_array( $result->get_error_code(), [ 'cron_disabled', 'cron_alternated' ] ) ) {

			if ( $this->options['status_email_failure'] ) {

				$email   = $this->options['status_email_address'] ? $this->options['status_email_address'] : get_option( 'admin_email' );
				$subject = sprintf( _x( '[%s] WP-Cron Failed!', 'Modules: CRON: Email Subject', GNETWORK_TEXTDOMAIN ), WordPress::getBlogNameforEmail() );

				$message = get_option( $this->hook( 'status' ) );
				$message .= '<p>'._x( 'This message has been sent from by the gNetwork WP-Cron Status Check module.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ).'</p>';
				// FIXME: add footer badge

				$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

				wp_mail( $email, $subject, $message, $headers );
			}

			foreach ( $result->get_error_codes() as $error )
				Logger::WARNING( 'CRON-STATUS: '.str_replace( '_', ' ', $error ) );
		}
	}

	public function widget_status_check()
	{
		HTML::desc( $this->options['dashboard_intro'] );

		echo '<div class="-status-container">'.$this->get_status().'</div>';

		echo '<p><span class="spinner"></span> ';
		echo '<button id="'.$this->classs( 'force-check' ).'" class="button button-small" data-nonce="'.wp_create_nonce( $this->classs( 'status-check' ) ).'">';
		echo _x( 'Check Status Now', 'Modules: CRON', GNETWORK_TEXTDOMAIN ).'</button></p>';

		HTML::desc( _x( 'The WP-Cron system will be automatically checked once every 24 hours. You can also check the status now by clicking the button above.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) );
	}

	protected static function getCronArray()
	{
		// it's private!
		if ( function_exists( '_get_cron_array' ) )
			return _get_cron_array();

		return [];
	}

	protected static function unschedule( $timestamp, $cron )
	{
		if ( array_key_exists( $timestamp, $cron ) ) {
			foreach ( $cron[$timestamp] as $action => $hashes ) {
				foreach ( $hashes as $hash ) {
					wp_unschedule_event( $timestamp, $action, $hash['args'] );
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	private static function tableCronInfo()
	{
		return HTML::tableList( [
			'_cb' => '_index',

			'next' => [
				'title'    => _x( 'Next', 'Modules: CRON', GNETWORK_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					return Utilities::getDateEditRow( $index );
				},
			],

			'tasks' => [
				'title'    => _x( 'Tasks', 'Modules: CRON', GNETWORK_TEXTDOMAIN ),
				'args'     => [ 'schedules' => wp_get_schedules() ],
				'callback' => function( $value, $row, $column, $index ){

					$info = '';

					foreach ( $row as $action => $tasks ) {
						foreach ( $tasks as $hash => $task ) {

							// FIXME: move styles
							$info .= '<div style="line-height:1.8">';

							if ( function_exists( 'has_action' ) )
								$style = ( has_action( $action ) ) ? ' style="color:green;"' : ' style="color:red;"';
							else
								$style = '';

							$info .= '<code'.$style.'>'.$action.'</code>';

							if ( isset( $task['schedule'] )
								&& $task['schedule']
								&& isset( $column['args']['schedules'][$task['schedule']] ) ) {
									$info .= ' <small>'.$column['args']['schedules'][$task['schedule']]['display']. '</small>';
							}

							if ( isset( $task['args'] ) && count( $task['args'] ) )
								foreach ( $task['args'] as $arg_key => $arg_val )
									$info .= $arg_key.': <code>'.$arg_val.'</code>';

							$info .= '</div>';
						}
					}

					return $info;
				},
			],
		], self::getCronArray(), [
			'title' => HTML::tag( 'h3', _x( 'Overview of tasks scheduled for WP-Cron', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) ),
			'empty' => self::warning( _x( 'Nothing scheduled!', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) ),
		] );
	}
}

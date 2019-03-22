<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Ajax;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Date;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;

class Cron extends gNetwork\Module
{
	protected $key     = 'cron';
	protected $network = FALSE;
	protected $front   = FALSE;
	protected $ajax    = TRUE;
	protected $cron    = TRUE;

	protected function setup_actions()
	{
		if ( ! is_blog_admin() )
			return FALSE;

		$this->action_module( 'cron', 'status_check', 2 );

		if ( function_exists( 'wp_get_ready_cron_jobs' ) )
			$this->filter_module( 'dashboard', 'pointers', 1, 4 );

		if ( $this->options['dashboard_widget'] )
			$this->action( 'wp_dashboard_setup' );
		else
			$this->action( 'activity_box_end', 0, 12 );

		if ( $this->options['schedule_revision'] && WP_POST_REVISIONS ) {

			$this->filter( 'cron_schedules', 1, 20 );

			add_action( $this->hook( 'clean_revisions' ), [ $this, 'do_clean_revisions' ], 10, 2 );
		}
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'CRON', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);

		Admin::registerTool( $this->key,
			_x( 'CRON', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'tools' ]
		);
	}

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function default_options()
	{
		return [
			'schedule_revision'    => '0',
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
					'field'       => 'schedule_revision',
					'title'       => _x( 'Clean Revisions', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Schedules a <b>weekly</b> task to delete post revisions.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'disabled'    => ! WP_POST_REVISIONS,
					'after'       => WP_POST_REVISIONS ? FALSE : Settings::fieldAfterText( sprintf( _x( 'Disabled by Constant: %s', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ), '<code>WP_POST_REVISIONS</code>' ) ),
				],
			],
			'_statuscheck' => [
				'dashboard_widget',
				'dashboard_accesscap' => 'edit_theme_options',
				'dashboard_intro',
				[
					'field'       => 'status_email_failure',
					'title'       => _x( 'Email Failure', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Notifies the administrator status check failure via email.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'status_email_address',
					'type'        => 'email',
					'title'       => _x( 'Email Address', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Failure notices will be sent to this address. Leave empty for WordPress admin email.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => empty( $this->options['status_email_address'] ) ? Settings::fieldAfterEmail( get_option( 'admin_email' ) ) : FALSE,
				],
			],
		];
	}

	public function settings_section_statuscheck()
	{
		Settings::fieldSection(
			_x( 'Status Check', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	public function settings_sidebox( $sub, $uri )
	{
		$check = TRUE;

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			HTML::desc( sprintf( _x( 'The %s is set. WP-Cron is disabled and will not run automatically.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ), '<code>DISABLE_WP_CRON</code>' ) );
			$check = FALSE;
		}

		if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
			HTML::desc( sprintf( _x( 'The %s is set. Cannot determine the status of the WP-Cron.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ), '<code>ALTERNATE_WP_CRON</code>' ) );
			$check = FALSE;
		}

		if ( defined( 'WP_CRON_LOCK_TIMEOUT' ) && WP_CRON_LOCK_TIMEOUT )
			HTML::desc( Utilities::getCounted( WP_CRON_LOCK_TIMEOUT, _x( '<code>WP_CRON_LOCK_TIMEOUT</code> is %s Seconds.', 'Modules: CRON: Settings', GNETWORK_TEXTDOMAIN ) ) );

		if ( ! $check )
			return;

		echo '<hr />';

		$this->status_check_box( FALSE );

		Utilities::enqueueScript( 'admin.cron.statuscheck' );
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [
			[
				'id'      => $this->classs( 'help' ),
				'title'   => _x( 'Schedule Intervals', 'Modules: CRON: Help Tab Title', GNETWORK_TEXTDOMAIN ),
				'content' => HTML::tableCode( wp_get_schedules() ),
			],
		];
	}

	protected function tools_actions( $sub = NULL )
	{
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
	}

	protected function tools_buttons( $sub = NULL )
	{
		$this->register_button( 'unschedule', _x( 'Unschedule', 'Modules: CRON: Button', GNETWORK_TEXTDOMAIN ), 'danger' );
		$this->register_button( $this->get_cron_url(), _x( 'Trigger Manually', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), 'link', [ 'target' => '_blank' ] );
	}

	public function schedule_actions()
	{
		$this->do_status_check();

		if ( $this->options['schedule_revision'] && WP_POST_REVISIONS ) {

			$hook = $this->hook( 'clean_revisions' );

			if ( ! wp_next_scheduled( $hook ) )
				wp_schedule_event( time(), 'weekly', $hook );
		}
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

	public function activity_box_end()
	{
		if ( WordPress::cuc( $this->options['dashboard_accesscap'] ) )
			echo $this->wrap( $this->get_status(), '-status-check' );
	}

	public function ajax()
	{
		Ajax::checkReferer( $this->classs( 'status-check' ) );

		$this->do_status_check( TRUE );

		Ajax::success( $this->get_status() );
	}

	public function get_status()
	{
		if ( $status = get_option( $this->hook( 'status' ) ) )
			return Text::autoP( $status );

		return '<p>'._x( 'WP-Cron Status Checker has not run yet.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ).'</p>';
	}

	// run the check and update the status
	private function do_status_check( $forced = FALSE )
	{
		if ( ! $forced ) {

			$timeout = get_option( $this->hook( 'timeout' ) );

			if ( FALSE !== $timeout && $timeout > time() )
				return;
		}

		$result = $this->status_check_spawn();

		if ( self::isError( $result ) ) {

			if ( in_array( $result->get_error_code(), [ 'cron_disabled', 'cron_alternated' ] ) ) {

				update_option( $this->hook( 'status' ), '<span class="-status -notice">'.$result->get_error_message().'</span>', TRUE );

			} else {

				$message = _x( 'While trying to spawn a call to the WP-Cron system, the following error occurred:', 'Modules: CRON', GNETWORK_TEXTDOMAIN );
				$message.= '<br><br><strong>'.HTML::escape( $result->get_error_message() ).'</strong><br><br>';
				$message.= _x( 'This is a problem with your installation.', 'Modules: CRON', GNETWORK_TEXTDOMAIN );

				update_option( $this->hook( 'status' ), '<span class="-status -error">'.$message.'</span>', TRUE );
			}

		} else {

			$message = sprintf( _x( 'WP-Cron is working as of %s', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), Utilities::htmlCurrent() );

			update_option( $this->hook( 'status' ), '<span class="-status -success">'.$message.'</span>', TRUE );
		}

		do_action( $this->hook( 'status_check' ), $result, $forced );

		update_option( $this->hook( 'timeout' ), time() + Date::DAY_IN_SECONDS, TRUE );
	}

	private function get_cron_url()
	{
		return site_url( 'wp-cron.php?doing_wp_cron='.sprintf( '%.22F', microtime( TRUE ) ) );
	}

	// gets the status of WP-Cron functionality on the site by performing a test spawn
	private function status_check_spawn()
	{
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON )
			return new Error( 'cron_disabled', sprintf( _x( 'The %1$s constant is set to true as of %2$s. WP-Cron is disabled and will not run automatically.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), '<code>DISABLE_WP_CRON</code>', Utilities::htmlCurrent() ) );

		if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON )
			return new Error( 'cron_alternated', sprintf( _x( 'The %1$s constant is set to true as of %2$s. We cannot determine the status of the WP-Cron system.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), '<code>ALTERNATE_WP_CRON</code>', Utilities::htmlCurrent() ) );

		$args = [
			'timeout'  => 3,
			'blocking' => TRUE,
		];

		$result = wp_remote_post( $this->get_cron_url(), $args );

		if ( self::isError( $result ) )
			return $result;

		$response = intval( wp_remote_retrieve_response_code( $result ) );

		if ( $response >= 300 )
			return new Error( 'unexpected_http_response_code', sprintf( _x( 'Unexpected HTTP response code: %s', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), $response ) );

		return TRUE;
	}

	// FIXME: add footer badge
	// email the admin if the result is bad
	public function cron_status_check( $result, $forced )
	{
		if ( $forced || ! self::isError( $result ) )
			return;

		if ( in_array( $result->get_error_code(), [ 'cron_disabled', 'cron_alternated' ] ) )
			return;

		if ( $this->options['status_email_failure'] )
			$this->do_email_failure( $this->options['status_email_address'] );

		foreach ( $result->get_error_codes() as $error )
			Logger::siteWARNING( 'CRON-STATUS', str_replace( '_', ' ', $error ) );
	}

	private function do_email_failure( $email = NULL )
	{
		if ( ! $email )
			$email = get_option( 'admin_email' );

		$subject = sprintf( _x( '[%s] WP-Cron Failed!', 'Modules: CRON: Email Subject', GNETWORK_TEXTDOMAIN ), WordPress::getSiteNameforEmail( TRUE ) );

		$message = get_option( $this->hook( 'status' ) );
		$message.= '<p>'.HTML::link( _x( 'View the current cron scheduled tasks', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), $this->get_menu_url( 'cron', 'admin', 'tools' ) ).'</p>';

		if ( HTML::rtl() )
			$message = '<div dir="rtl">'.$message.'</div>';

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		@wp_mail( $email, $subject, $message, $headers );
	}

	public function dashboard_pointers( $items )
	{
		$can = WordPress::cuc( 'manage_options' );

		if ( $ready = count( self::getCronReady() ) )
			$title = Utilities::getCounted( $ready, _nx( '%s Ready Cron-job', '%s Ready Cron-jobs', $ready, 'Modules: CRON', GNETWORK_TEXTDOMAIN ) );

		else
			$title = _x( 'Cron-jobs Done!', 'Modules: CRON', GNETWORK_TEXTDOMAIN );

		$items[] = HTML::tag( $can ? 'a' : 'span', [
			'href'  => $can ? $this->get_menu_url( 'cron', 'admin', 'tools' ) : FALSE,
			'title' => _x( 'Cron-jobs ready to be run.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ),
			'class' => $ready ? '-corn-ready' : '-corn-done',
		], $title );

		return $items;
	}

	public function widget_status_check()
	{
		HTML::desc( $this->options['dashboard_intro'] );

		$this->status_check_box();

		HTML::desc( _x( 'The WP-Cron system will be automatically checked once every 24 hours.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) );
	}

	protected function status_check_box( $link = TRUE )
	{
		echo '<div id="'.$this->classs( 'status-check' ).'">';
		echo '<div class="-status-container">'.$this->get_status().'</div>';

		echo '<p>'.Ajax::spinner();

		echo HTML::tag( 'button', [
			'id'    => $this->classs( 'force-check' ),
			'class' => [ 'button', 'button-small' ],
			'data'  => [ 'nonce' => wp_create_nonce( $this->classs( 'status-check' ) ) ],
		], _x( 'Check Status Now', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) );

		if ( $link && WordPress::cuc( 'manage_options' ) ) {

			echo '&nbsp;&nbsp;'.HTML::tag( 'a', [
				'href'   => $this->get_cron_url(),
				'class'  => [ 'button', 'button-small' ],
				'target' => '_blank',
			], _x( 'Trigger Manually', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) );

			echo '&nbsp;&nbsp;'.HTML::tag( 'a', [
				'href'  => $this->get_menu_url( 'cron', 'admin', 'tools' ),
				'class' => [ 'button', 'button-small' ],
			], _x( 'View Scheduled Tasks', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) );
		}

		echo '</p></div>';
	}

	protected static function getCronReady()
	{
		// @SINCE: WP 5.1
		if ( function_exists( 'wp_get_ready_cron_jobs' ) )
			return wp_get_ready_cron_jobs();

		return [];
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

	protected function render_tools_html( $uri, $sub = 'general' )
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

					$html = '';

					foreach ( $row as $action => $tasks ) {
						foreach ( $tasks as $hash => $task ) {

							$html.= '<div dir="ltr"><code style="color:'.( has_action( $action ) ? 'green' : 'red' ).'">'.$action.'</code>';

							if ( ! empty( $task['schedule'] ) && isset( $column['args']['schedules'][$task['schedule']] ) )
								$html.= ' <small>('.$column['args']['schedules'][$task['schedule']]['display'].')</small>';

							if ( ! empty( $task['args'] ) )
								$html.= ': <code>'.wp_json_encode( $task['args'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ).'</code>';

							$html.= '</div>';
						}
					}

					return $html;
				},
			],
		], self::getCronArray(), [
			'title' => HTML::tag( 'h3', _x( 'Overview of tasks scheduled for WP-Cron', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) ),
			'empty' => HTML::warning( _x( 'Nothing scheduled!', 'Modules: CRON', GNETWORK_TEXTDOMAIN ), FALSE ),
			'after' => [ $this, 'table_list_after' ],
		] );
	}

	public function table_list_after()
	{
		HTML::desc( Utilities::getCounted( count( self::getCronReady() ), _x( 'With %s event(s) ready to be run.', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) ) );
	}

	// adds once weekly to the existing schedules
	public function cron_schedules( $schedules )
	{
		return array_merge( $schedules, [
			'weekly' => [
				'interval' => Date::WEEK_IN_SECONDS,
				'display'  => _x( 'Once Weekly', 'Modules: CRON', GNETWORK_TEXTDOMAIN ),
			],
		] );
	}

	public function do_clean_revisions()
	{
		$revisions = get_posts( [
			'fields'      => 'ids',
			'post_type'   => 'revision',
			'numberposts' => -1
		] );

		foreach ( $revisions as $revision )
			wp_delete_post( $revision );
	}
}

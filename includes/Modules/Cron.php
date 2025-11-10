<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Ajax;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Cron extends gNetwork\Module
{
	protected $key     = 'cron';
	protected $network = FALSE;
	protected $ajax    = TRUE;
	protected $cron    = TRUE;

	protected function setup_actions()
	{
		$this->filter( 'cron_schedules', 1, 20 );
		$this->action_module( 'cron', 'status_check', 2 );

		if ( ! is_blog_admin() )
			return FALSE;

		if ( function_exists( 'wp_get_ready_cron_jobs' ) )
			$this->filter_module( 'dashboard', 'pointers', 1, 4 );

		if ( ! $this->options['dashboard_widget'] )
			$this->action( 'activity_box_end', 0, 12 );

		if ( $this->options['schedule_revision'] )
			add_action( $this->hook( 'clean_revisions' ), [ $this, 'do_clean_revisions' ], 10, 2 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'CRON', 'Modules: Menu Name', 'gnetwork' ) );
		$this->register_tool( _x( 'CRON', 'Modules: Menu Name', 'gnetwork' ) );
	}

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function default_options()
	{
		return [
			'schedule_revision'    => '0',
			'dashboard_widget'     => '1',
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
					'title'       => _x( 'Clean Revisions', 'Modules: CRON: Settings', 'gnetwork' ),
					'description' => _x( 'Schedules a <b>monthly</b> task to delete post revisions.', 'Modules: CRON: Settings', 'gnetwork' ),
					'disabled'    => ! WP_POST_REVISIONS,
					'after'       => WP_POST_REVISIONS ? FALSE : Settings::fieldAfterText( sprintf(
						/* translators: `%s`: constant placeholder */
						_x( 'Disabled by Constant: %s', 'Modules: CRON: Settings', 'gnetwork' ),
						Core\HTML::code( 'WP_POST_REVISIONS' )
					) ),
				],
			],
			'_statuscheck' => [
				'dashboard_widget',
				'dashboard_accesscap' => 'edit_theme_options',
				'dashboard_intro',
				[
					'field'       => 'status_email_failure',
					'title'       => _x( 'Email Failure', 'Modules: CRON: Settings', 'gnetwork' ),
					'description' => _x( 'Notifies the administrator status check failure via email.', 'Modules: CRON: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'status_email_address',
					'type'        => 'email',
					'title'       => _x( 'Email Address', 'Modules: CRON: Settings', 'gnetwork' ),
					'description' => _x( 'Failure notices will be sent to this address. Leave empty for WordPress admin email.', 'Modules: CRON: Settings', 'gnetwork' ),
					'after'       => empty( $this->options['status_email_address'] ) ? Settings::fieldAfterEmail( get_option( 'admin_email' ) ) : FALSE,
				],
			],
		];
	}

	public function settings_section_statuscheck()
	{
		Settings::fieldSection(
			_x( 'Status Check', 'Modules: CRON: Settings', 'gnetwork' )
		);
	}

	public function settings_sidebox( $sub, $uri )
	{
		$check = TRUE;

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			Core\HTML::desc( sprintf(
				/* translators: `%s`: constant placeholder */
				_x( 'The %s is set. WP-Cron is disabled and will not run automatically.', 'Modules: CRON: Settings', 'gnetwork' ),
				Core\HTML::code( 'DISABLE_WP_CRON' )
			) );
			$check = FALSE;
		}

		if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
			Core\HTML::desc( sprintf(
				/* translators: `%s`: constant placeholder */
				_x( 'The %s is set. Cannot determine the status of the WP-Cron.', 'Modules: CRON: Settings', 'gnetwork' ),
				Core\HTML::code( 'ALTERNATE_WP_CRON' )
			) );
			$check = FALSE;
		}

		if ( defined( 'WP_CRON_LOCK_TIMEOUT' ) && WP_CRON_LOCK_TIMEOUT )
			Core\HTML::desc( Utilities::getCounted( WP_CRON_LOCK_TIMEOUT, sprintf(
				/* translators: `%1$s`: constant placeholder, `%2$s`: time-out on seconds */
				_x( '%1$s is %2$s Seconds.', 'Modules: CRON: Settings', 'gnetwork' ),
				Core\HTML::code( 'WP_CRON_LOCK_TIMEOUT' ), '%s'
			) ) );

		if ( ! $check )
			return;

		echo '<hr />';

		$this->status_check_box( FALSE );

		Scripts::enqueueScript( 'admin.cron.statuscheck' );
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [
			[
				'id'      => $this->classs( 'advanced' ),
				'title'   => _x( 'Advanced', 'Modules: CRON: Help Tab Title', 'gnetwork' ),
				'content' => self::buffer( [ $this, '_help_tab_advanced_content' ] ),
			],
			[
				'id'      => $this->classs( 'schedules' ),
				'title'   => _x( 'Schedule Intervals', 'Modules: CRON: Help Tab Title', 'gnetwork' ),
				'content' => Core\HTML::tableCode( wp_get_schedules() ),
			],
		];
	}

	public function _help_tab_advanced_content()
	{
		echo $this->wrap_open( '-help-tab-content' );
			Core\HTML::desc( Core\HTML::code( sprintf( 'wget --delete-after %s', $this->get_cron_url() ) ) );
		echo '</div>';
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

			$this->check_referer( $sub, 'tools' );

			if ( self::isTablelistAction( 'unschedule', TRUE ) ) {

				$count = 0;
				$cron  = self::getCronArray();

				foreach ( $_POST['_cb'] as $event )
					if ( self::unschedule( (int) $event, $cron ) )
						$count++;

			} else {

				WordPress\Redirect::doReferer( 'wrong' );
			}

			WordPress\Redirect::doReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );
		}
	}

	protected function tools_buttons( $sub = NULL )
	{
		$this->register_button( 'unschedule', _x( 'Unschedule', 'Modules: CRON: Button', 'gnetwork' ), 'danger' );
		$this->register_button( $this->get_cron_url(), _x( 'Trigger Manually', 'Modules: CRON', 'gnetwork' ), 'link', [ 'target' => '_blank' ] );
	}

	public function schedule_actions()
	{
		$this->do_status_check();

		if ( $this->options['schedule_revision'] && WP_POST_REVISIONS )
			$this->_hook_event( 'clean_revisions', 'monthly' );
	}

	public function setup_dashboard()
	{
		if ( $this->add_dashboard_widget( 'status-check', _x( 'WP-Cron Status Check', 'Modules: CRON: Widget Title', 'gnetwork' ), 'info' ) )
			Scripts::enqueueScript( 'admin.cron.statuscheck' );
	}

	public function activity_box_end()
	{
		if ( WordPress\User::cuc( $this->options['dashboard_accesscap'] ) )
			echo $this->wrap( $this->get_status(), '-status-check' );
	}

	public function do_ajax()
	{
		Ajax::checkReferer( $this->classs( 'status-check' ) );

		$this->do_status_check( TRUE );

		Ajax::success( $this->get_status() );
	}

	public function get_status()
	{
		if ( $status = get_option( $this->hook( 'status' ) ) )
			return Core\Text::autoP( $status );

		return '<p>'._x( 'WP-Cron Status Checker has not run yet.', 'Modules: CRON', 'gnetwork' ).'</p>';
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

				$message = _x( 'While trying to spawn a call to the WP-Cron system, the following error occurred:', 'Modules: CRON', 'gnetwork' );
				$message.= '<br><br><strong>'.Core\HTML::escape( $result->get_error_message() ).'</strong><br><br>';
				$message.= _x( 'This is a problem with your installation.', 'Modules: CRON', 'gnetwork' );

				update_option( $this->hook( 'status' ), '<span class="-status -error">'.$message.'</span>', TRUE );
			}

		} else {

			$message = sprintf(
				/* translators: `%s`: current time */
				_x( 'WP-Cron is working as of %s', 'Modules: CRON', 'gnetwork' ),
				Utilities::htmlCurrent()
			);

			update_option( $this->hook( 'status' ), '<span class="-status -success">'.$message.'</span>', TRUE );
		}

		do_action( $this->hook( 'status_check' ), $result, $forced );

		update_option( $this->hook( 'timeout' ), time() + Core\Date::DAY_IN_SECONDS, TRUE );
	}

	private function get_cron_url()
	{
		return site_url( 'wp-cron.php?doing_wp_cron='.sprintf( '%.22F', microtime( TRUE ) ) );
	}

	// gets the status of WP-Cron functionality on the site by performing a test spawn
	private function status_check_spawn()
	{
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON )
			return new Core\Error( 'cron_disabled', sprintf(
				/* translators: `%1$s`: constant placeholder, `%2$s`: current time */
				_x( 'The %1$s constant is set to true as of %2$s. WP-Cron is disabled and will not run automatically.', 'Modules: CRON', 'gnetwork' ),
				Core\HTML::code( 'DISABLE_WP_CRON' ),
				Utilities::htmlCurrent()
			) );

		if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON )
			return new Core\Error( 'cron_alternated', sprintf(
				/* translators: `%1$s`: constant placeholder, `%2$s`: current time */
				_x( 'The %1$s constant is set to true as of %2$s. We cannot determine the status of the WP-Cron system.', 'Modules: CRON', 'gnetwork' ),
				Core\HTML::code( 'ALTERNATE_WP_CRON' ),
				Utilities::htmlCurrent()
			) );

		$args = [
			'timeout'  => 3,
			'blocking' => TRUE,
		];

		$result = wp_remote_post( $this->get_cron_url(), $args );

		if ( self::isError( $result ) )
			return $result;

		$response = (int) wp_remote_retrieve_response_code( $result );

		if ( $response >= 300 )
			return new Core\Error( 'unexpected_http_response_code', sprintf(
				/* translators: `%s`: error code */
				_x( 'Unexpected HTTP response code: %s', 'Modules: CRON', 'gnetwork' ),
				$response
			) );

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
			Logger::siteFAILED( 'CRON-STATUS', str_replace( '_', ' ', $error ) );
	}

	private function do_email_failure( $email = NULL )
	{
		if ( ! $email )
			$email = get_option( 'admin_email' );

		$subject = sprintf(
			/* translators: `%s`: site name */
			_x( '[%s] WP-Cron Failed!', 'Modules: CRON: Email Subject', 'gnetwork' ),
			WordPress\Site::nameforEmail( TRUE )
		);

		$message = get_option( $this->hook( 'status' ) );
		$message.= '<p>'.Core\HTML::link( _x( 'View the current cron scheduled tasks', 'Modules: CRON', 'gnetwork' ), $this->get_menu_url( 'cron', 'admin', 'tools' ) ).'</p>';

		if ( Core\L10n::rtl() )
			$message = '<div dir="rtl">'.$message.'</div>';

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		@wp_mail( $email, $subject, $message, $headers );
	}

	public function dashboard_pointers( $items )
	{
		$can = WordPress\User::cuc( 'manage_options' );

		if ( $ready = count( self::getCronReady() ) )
			$title = Utilities::getCounted( $ready,
				/* translators: `%s`: ready actions count */
				_nx( '%s Ready Cron-job', '%s Ready Cron-jobs', $ready, 'Modules: CRON', 'gnetwork' )
			);

		else
			$title = _x( 'Cron-jobs Done!', 'Modules: CRON', 'gnetwork' );

		$items[] = Core\HTML::tag( $can ? 'a' : 'span', [
			'href'  => $can ? $this->get_menu_url( 'cron', 'admin', 'tools' ) : FALSE,
			'title' => _x( 'Cron-jobs ready to be run.', 'Modules: CRON', 'gnetwork' ),
			'class' => $ready ? '-corn-ready' : '-corn-done',
		], $title );

		if ( $can && ( $missing = count( $this->get_missing_actions() ) ) )
			$items[] = Core\HTML::tag( 'a', [
				'href'  => $this->get_menu_url( 'cron', 'admin', 'tools' ),
				'title' => _x( 'Cron-jobs with missing action.', 'Modules: CRON', 'gnetwork' ),
				'class' => '-corn-missing',
			], Utilities::getCounted( $missing,
				/* translators: `%s`: missing actions count */
				_nx( '%s Corn-job Missing Action', '%s Corn-job Missing Actions', $missing, 'Modules: CRON', 'gnetwork' )
			) );

		return $items;
	}

	public function render_widget_status_check()
	{
		Core\HTML::desc( $this->options['dashboard_intro'], TRUE, '-intro' );

		$this->status_check_box();
	}

	protected function get_widget_status_check_info()
	{
		return _x( 'The WP-Cron system will be automatically checked once every 24 hours.', 'Modules: CRON', 'gnetwork' );
	}

	protected function status_check_box( $link = TRUE )
	{
		echo '<div id="'.$this->classs( 'status-check' ).'">';
		echo '<div class="-status-container">'.$this->get_status().'</div>';

		echo $this->wrap_open_buttons();
		echo Ajax::spinner();

		echo Core\HTML::tag( 'button', [
			'id'    => $this->classs( 'force-check' ),
			'class' => [ 'button', 'button-small' ],
			'data'  => [
				'error' => _x( 'There was a problem getting the status of WP Cron.', 'Modules: CRON', 'gnetwork' ),
				'nonce' => wp_create_nonce( $this->classs( 'status-check' ) ),
			],
		], _x( 'Check Status Now', 'Modules: CRON', 'gnetwork' ) );

		if ( $link && WordPress\User::cuc( 'manage_options' ) ) {

			echo '&nbsp;&nbsp;'.Core\HTML::tag( 'a', [
				'href'  => $this->get_menu_url( 'cron', 'admin', 'tools' ),
				'class' => [ 'button', 'button-small' ],
			], _x( 'View Scheduled Tasks', 'Modules: CRON', 'gnetwork' ) );

			echo '&nbsp;&nbsp;'.Core\HTML::tag( 'a', [
				'href'   => $this->get_cron_url(),
				'class'  => [ 'button', 'button-small' ],
				'target' => '_blank',
			], _x( 'Trigger Manually', 'Modules: CRON', 'gnetwork' ) );
		}

		echo '</p></div>';
	}

	protected function get_missing_actions()
	{
		$actions = $missing = [];

		foreach ( self::getCronArray() as $timestamp )
			if ( $timestamp )
				$actions = array_merge( $actions, array_keys( $timestamp ) );

		foreach ( array_unique( $actions ) as $action )
			if ( ! has_action( $action ) )
				$missing[] = $action;

		return $missing;
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

				if ( empty( $hashes ) )
					continue;

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
		return Core\HTML::tableList( [
			'_cb' => '_index',

			'next' => [
				'title'    => _x( 'Next', 'Modules: CRON', 'gnetwork' ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					return Utilities::getDateEditRow( $index );
				},
			],

			'tasks' => [
				'title'    => _x( 'Tasks', 'Modules: CRON', 'gnetwork' ),
				'args'     => [ 'schedules' => wp_get_schedules() ],
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					$html = '';

					foreach ( $row as $action => $tasks ) {

						if ( empty( $tasks ) )
							continue;

						foreach ( $tasks as $hash => $task ) {

							$html.= '<div dir="ltr"><code style="color:'.( has_action( $action ) ? 'green' : 'red' ).'">'.$action.'</code>';

							if ( ! empty( $task['schedule'] ) && isset( $column['args']['schedules'][$task['schedule']] ) )
								$html.= ' <small>('.$column['args']['schedules'][$task['schedule']]['display'].')</small>';

							if ( ! empty( $task['args'] ) )
								$html.= ': <code>'.wp_json_encode( $task['args'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ).'</code>';

							$html.= '</div>';
						}
					}

					return $html;
				},
			],
		], self::getCronArray(), [
			'title' => Core\HTML::tag( 'h3', _x( 'Overview of tasks scheduled for WP-Cron', 'Modules: CRON', 'gnetwork' ) ),
			'empty' => Core\HTML::warning( _x( 'Nothing scheduled!', 'Modules: CRON', 'gnetwork' ), FALSE ),
			'after' => [ $this, 'table_list_after' ],
		] );
	}

	public function table_list_after()
	{
		Core\HTML::desc(
			Utilities::getCounted(
				count( self::getCronReady() ),
				/* translators: `%s`: events count */
				_x( 'With %s event(s) ready to be run.', 'Modules: CRON', 'gnetwork' )
			)
		);
	}

	// adds once monthly to the existing schedules
	public function cron_schedules( $schedules )
	{
		return array_merge( $schedules, [
			'monthly' => [
				'interval' => Core\Date::MONTH_IN_SECONDS,
				'display'  => _x( 'Once Monthly', 'Modules: CRON', 'gnetwork' ),
			],
		] );
	}

	public function do_clean_revisions()
	{
		// NOTE: the constant may late defined!
		if ( ! WP_POST_REVISIONS )
			return;

		$revisions = get_posts( [
			'fields'      => 'ids',
			'post_type'   => 'revision',
			'numberposts' => -1,

			// @REF: https://stackoverflow.com/a/25069538/
			'date_query'  => [
				'column' => 'post_modified_gmt',
				'before' => '-1 week',
			],
		] );

		foreach ( $revisions as $revision )
			wp_delete_post( $revision );
	}
}

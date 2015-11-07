<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCron extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;
	protected $front_end  = FALSE;

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'cron',
			__( 'CRON', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function settings( $sub = NULL )
	{
		if ( 'cron' == $sub ) {
			$this->settings_update( $sub );

			add_action( 'gnetwork_admin_settings_messages', array( $this, 'settings_messages' ), 10, 2 );
			add_action( 'gnetwork_admin_settings_sub_cron', array( $this, 'settings_html' ), 10, 2 );

			$this->register_button( 'unschedule', _x( 'Unschedule', 'Cron Module', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
		}
	}

	protected function settings_update( $sub )
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
				return FALSE;
			}

			self::redirect_referer( array(
				'message' => 'unscheduled',
				'count'   => $count,
			) );
		}
	}

	// HELPER
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

	public function settings_messages( $messages, $sub )
	{
		$messages['unscheduled'] = self::counted( _x( '%s Events Unscheduled!', 'Cron Module', GNETWORK_TEXTDOMAIN ) );
		return $messages;
	}

	public function settings_html( $uri, $sub = 'general' )
	{
		echo '<form class="gnetwork-form" method="post" action="">';

			// TODO: add info on DISABLE_WP_CRON
			// TODO: add info on url/path to wp-cron.php
			// TODO: adding wp-cron-multisite.php / much like emaillogs folder
			// SEE: https://www.lucasrolff.com/wordpress/why-wp-cron-sucks/

			$this->settings_fields( $sub, 'bulk' );

			self::cronInfo();

			$this->settings_buttons( $sub );

		echo '</form>';
	}

	// HELPER
	protected static function getCronArray()
	{
		// b/c it's private!
		if ( function_exists( '_get_cron_array' ) )
			return _get_cron_array();

		return array();
	}

	public static function cronInfo()
	{
		echo self::html( 'h3', __( 'Overview of tasks scheduled for WP-Cron', GNETWORK_TEXTDOMAIN ) );

		$cron = self::getCronArray();

		if ( empty( $cron ) ) {
			echo self::html( 'strong', __( 'Nothing scheduled', GNETWORK_TEXTDOMAIN ) );
			return;
		}

		self::tableList( array(
			'_cb' => '_index',

			'next' => array(
				'title'    => __( 'Next', GNETWORK_TEXTDOMAIN ),
				'class'    => '-column-next',
				'callback' => function( $value, $row, $column, $index ){
					// return date_i18n( self::getDateDefaultFormat(), $index );
					return date_i18n( 'H:i:s - D, j M, Y', $index );
				},
			),

			'tasks' => array(
				'title' => __( 'Tasks', GNETWORK_TEXTDOMAIN ),
				'class' => '-column-tasks',
				'args'  => array(
					'schedules' => wp_get_schedules(),
				),
				'callback' => function( $value, $row, $column, $index ){

					$info = '';

					foreach ( $row as $action => $tasks ) {
						foreach ( $tasks as $hash => $task ) {

							$info .= '<div style="line-height:1.8">';

							if ( function_exists('has_action') )
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
			),
		), $cron );
	}
}

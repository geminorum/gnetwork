<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkCron extends gNetworkModuleCore
{

	protected $menu_key   = 'cron';
	protected $network    = FALSE;
	protected $front_end  = FALSE;

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'cron',
			_x( 'CRON', 'CRON Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	protected function settings_actions( $sub = NULL )
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
				self::redirect_referer( 'wrong' );
			}

			self::redirect_referer( array(
				'message' => 'deleted',
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

	public function settings_html( $uri, $sub = 'general' )
	{
		echo '<form class="gnetwork-form" method="post" action="">';

			// TODO: add info on DISABLE_WP_CRON
			// TODO: add info on url/path to wp-cron.php
			// TODO: adding wp-cron-multisite.php / much like emaillogs folder
			// SEE: https://www.lucasrolff.com/wordpress/why-wp-cron-sucks/

			$this->settings_fields( $sub, 'bulk' );

			if ( self::tableCronInfo() )
				$this->settings_buttons( $sub );

		echo '</form>';
	}

	protected function register_settings_buttons()
	{
		$this->register_button( 'unschedule', _x( 'Unschedule', 'Cron Module', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
	}

	// HELPER
	protected static function getCronArray()
	{
		// b/c it's private!
		if ( function_exists( '_get_cron_array' ) )
			return _get_cron_array();

		return array();
	}

	private static function tableCronInfo()
	{
		return self::tableList( array(
			'_cb' => '_index',

			'next' => array(
				'title'    => _x( 'Next', 'CRON Module', GNETWORK_TEXTDOMAIN ),
				'class'    => '-column-next',
				'callback' => function( $value, $row, $column, $index ){
					return date_i18n( 'H:i:s - D, j M, Y', $index );
				},
			),

			'tasks' => array(
				'title' => _x( 'Tasks', 'CRON Module', GNETWORK_TEXTDOMAIN ),
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
		), self::getCronArray(), array(
			'title' => self::html( 'h3', _x( 'Overview of tasks scheduled for WP-Cron', 'CRON Module', GNETWORK_TEXTDOMAIN ) ),
			'empty' => self::html( 'strong', _x( 'Nothing scheduled!', 'CRON Module', GNETWORK_TEXTDOMAIN ) ),
		) );
	}
}

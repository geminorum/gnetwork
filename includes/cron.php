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
			FALSE, 'manage_network_options'
		);
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
		echo gNetworkUtilities::html( 'h3', __( 'Overview of tasks scheduled for WP-Cron', GNETWORK_TEXTDOMAIN ) );

		$cron = self::getCronArray();

		if ( empty( $cron ) ) {
			echo gNetworkUtilities::html( 'strong', __( 'Nothing scheduled', GNETWORK_TEXTDOMAIN ) );
			return;
		}

		if ( ! class_exists( 'gEditorialHelper' ) ) {
			echo gNetworkUtilities::html( 'p', 'TEMPORARLY: it\'s better to have gEditorial enabled for this!' );
			gNetworkUtilities::tableSide( $cron );

		} else {

			gEditorialHelper::table( array(
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
}

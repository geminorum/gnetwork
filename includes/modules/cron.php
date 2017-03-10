<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Cron extends ModuleCore
{
	protected $key     = 'cron';
	protected $network = FALSE;
	protected $front   = FALSE;

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'CRON', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
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
				WordPress::redirectReferer( 'wrong' );
			}

			WordPress::redirectReferer( array(
				'message' => 'deleted',
				'count'   => $count,
			) );
		}
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

	public function settings_form( $uri, $sub = 'general' )
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

	protected function register_settings_buttons( $sub = NULL )
	{
		$this->register_button( 'unschedule', _x( 'Unschedule', 'Modules: CRON: Button', GNETWORK_TEXTDOMAIN ), TRUE );
	}

	protected static function getCronArray()
	{
		// it's private!
		if ( function_exists( '_get_cron_array' ) )
			return _get_cron_array();

		return array();
	}

	private static function tableCronInfo()
	{
		return HTML::tableList( array(
			'_cb' => '_index',

			'next' => array(
				'title'    => _x( 'Next', 'Modules: CRON', GNETWORK_TEXTDOMAIN ),
				'class'    => '-column-next',
				'callback' => function( $value, $row, $column, $index ){
					return Utilities::getDateEditRow( $index );
				},
			),

			'tasks' => array(
				'title'    => _x( 'Tasks', 'Modules: CRON', GNETWORK_TEXTDOMAIN ),
				'class'    => '-column-tasks',
				'args'     => array( 'schedules' => wp_get_schedules() ),
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
			),
		), self::getCronArray(), array(
			'title' => HTML::tag( 'h3', _x( 'Overview of tasks scheduled for WP-Cron', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) ),
			'empty' => self::warning( _x( 'Nothing scheduled!', 'Modules: CRON', GNETWORK_TEXTDOMAIN ) ),
		) );
	}
}

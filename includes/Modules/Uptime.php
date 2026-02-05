<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Uptime extends gNetwork\Module
{
	protected $key     = 'uptime';
	protected $network = FALSE;

	protected $api_key = '';

	protected function setup_actions()
	{
		if ( defined( 'UPTIMEROBOT_APIKEY' ) && UPTIMEROBOT_APIKEY )
			$this->api_key = UPTIMEROBOT_APIKEY;

		else if ( isset( $this->options['uptimerobot_apikey'] ) )
			$this->api_key = $this->options['uptimerobot_apikey'];
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Uptime', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'dashboard_widget'    => '0',
			'dashboard_accesscap' => 'edit_theme_options',
			'uptimerobot_apikey'  => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'uptimerobot_apikey',
					'type'        => 'text',
					'title'       => _x( 'API Key', 'Modules: Uptime: Settings', 'gnetwork' ),
					'description' => _x( 'Key for communication between this site and UptimeRobot.com', 'Modules: Uptime: Settings', 'gnetwork' ),
					'constant'    => 'UPTIMEROBOT_APIKEY',
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				'dashboard_widget',
				'dashboard_accesscap' => 'edit_theme_options',
			],
		];
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [
			[
				'id'      => $this->classs( 'help' ),
				'title'   => _x( 'Uptime Robot', 'Modules: Uptime: Help Tab Title', 'gnetwork' ),
				'content' => '<p>Uptime Robot monitors your site and alerts you if your sites are down.</p><p>Register and get api key from <a href="https://uptimerobot.com/dashboard.php#mySettings" target="_blank" rel="noreferrer"><i>here</i></a>.</p>',
			],
		];
	}

	public function setup_dashboard()
	{
		if ( empty( $this->api_key ) )
			return FALSE;

		$this->add_dashboard_widget( 'dashboard', _x( 'Uptime Monitor', 'Modules: Uptime: Widget Title', 'gnetwork' ) );
	}

	public function render_widget_dashboard()
	{
		if ( $this->check_hidden_metabox( 'dashboard' ) )
			return;

		$data = $this->get_monitors();

		if ( self::isError( $data ) ) {
			echo Core\HTML::warning( $data->get_error_message(), FALSE, '-full' );
			return;
		}

		foreach ( $data as $monitor ) {

			// no need!
			// Core\HTML::h3( $monitor['friendly_name'], FALSE, $monitor['url'] );

			// wrap to use core styles
			echo '<div id="dashboard_right_now"><div class="main">';
			echo '<table class="base-table-uptime"><tbody>';

			foreach ( $monitor['logs'] as $log )
				echo '<tr><td>'.$this->get_uptimerobot_type( $log['type'] )
					.'</td><td>'.gNetwork\Datetime::htmlHumanTime( $log['datetime'], TRUE )
					.'</td><td>'.gNetwork\Datetime::htmlFromSeconds( $log['duration'], 2 )
					.'</td><td style="direction:ltr;">'.( $log['reason']['code'] < 600 ? Core\HTTP::htmlStatus( $log['reason']['code'] ) : '' ).$log['reason']['detail']
					.'</td></tr>';

			echo '</tbody></table></div>';

			echo '<div class="sub"><table class="base-table-uptime"><tbody><tr><td>';
				/* translators: `%s`: all time uptime ratio */
				echo Utilities::getCounted( $monitor['all_time_uptime_ratio'], _x( 'Uptime Ratio: %s', 'Modules: Uptime', 'gnetwork' ) );
			echo '</td><td>';
				/* translators: `%s`: average response time */
				echo Utilities::getCounted( $monitor['average_response_time'], _x( 'Response Time: %s', 'Modules: Uptime', 'gnetwork' ) );
			echo '</td></tr><tr><td>';

				printf(
					/* translators: `%s`: interval */
					_x( 'Interval: %s', 'Modules: Uptime', 'gnetwork' ),
					gNetwork\Datetime::htmlFromSeconds( $monitor['interval'], 2 )
				);

			echo '</td><td>';
				/* translators: `%s`: response time */
				echo Utilities::getCounted( $monitor['response_times'][0]['value'], _x( 'Last Response Time: %s', 'Modules: Uptime', 'gnetwork' ) );
			echo '</td></tr></tbody></table>';

			echo '</div></div>';
		}
	}

	private function get_uptimerobot_type( $type )
	{
		switch ( $type ) {
			case '1' : return Core\HTML::getDashicon( 'warning', _x( 'Down', 'Modules: Uptime', 'gnetwork' ) );
			case '2' : return Core\HTML::getDashicon( 'marker', _x( 'Up', 'Modules: Uptime', 'gnetwork' ) );
			case '99': return Core\HTML::getDashicon( 'star-empty', _x( 'Paused', 'Modules: Uptime', 'gnetwork' ) );
			case '98': return Core\HTML::getDashicon( 'star-filled', _x( 'Started', 'Modules: Uptime', 'gnetwork' ) );
		}

		return Core\HTML::getDashicon( 'info', 'span', _x( 'Unknown!', 'Modules: Uptime', 'gnetwork' ) );
	}

	// @REF: https://uptimerobot.com/api#getMonitors
	private function get_monitors( $limit = 10 )
	{
		$endpoint = 'https://api.uptimerobot.com/v2/';
		$method   = 'getMonitors';

		$args = [
			'timeout'     => 15,
			'httpversion' => '1.1',

			'body' => [
				'api_key'    => $this->api_key,
				'logs_limit' => $limit,
				'format'     => 'json',
				'timezone'   => 1,
				'logs'       => 1,

				'all_time_uptime_durations' => 1,
				'all_time_uptime_ratio'     => 1,
				'response_times'            => 1,
				'response_times_average'    => 1,
			],
			'headers' => [
				'cache-control' => 'no-cache',
				'Content-Type'  => 'application/x-www-form-urlencoded;charset=UTF-8',
			],
		];

		$response = wp_remote_post( $endpoint.$method, $args );

		if ( self::isError( $response ) )
			return $response;

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 == $status ) {

			$data = json_decode( wp_remote_retrieve_body( $response ), TRUE );

			if ( empty( $data['stat'] ) )
				return new Core\Error( 'unknown_response', _x( 'Something is wrong with UptimeRobot.com API!', 'Modules: Uptime', 'gnetwork' ) );

			else if ( 'fail' == $data['stat'] )
				/* translators: `%s`: error type */
				return new Core\Error( $data['error']['type'], sprintf( _x( 'UptimeRobot.com API: %s', 'Modules: Uptime', 'gnetwork' ), str_replace( '_', ' ', $data['error']['type'] ) ) );

			else if ( 'ok' == $data['stat'] )
				return $data['monitors'];
		}

		return new Core\Error( 'not_ok_status', Core\HTTP::getStatusDesc( $status, _x( 'Unknown!', 'Modules: Uptime', 'gnetwork' ) ) );
	}
}

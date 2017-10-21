<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Date;
use geminorum\gNetwork\Core\Error;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\WordPress;

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

		if ( $this->options['dashboard_widget'] && $this->api_key )
			$this->action( 'wp_dashboard_setup' );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Uptime', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
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
					'title'       => _x( 'API Key', 'Modules: Uptime: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Key for communication between this site and UptimeRobot.com', 'Modules: Uptime: Settings', GNETWORK_TEXTDOMAIN ),
					'constant'    => 'UPTIMEROBOT_APIKEY',
					'field_class' => [ 'regular-text', 'code' ],
					'dir'         => 'ltr',
				],
				'dashboard_widget',
				'dashboard_accesscap',
			],
		];
	}

	public function settings_help_tabs( $sub = NULL )
	{
		return [
			[
				'id'      => $this->classs( 'help' ),
				'title'   => _x( 'Uptime Robot', 'Modules: Uptime: Help Tab Title', GNETWORK_TEXTDOMAIN ),
				'content' => '<p>Uptime Robot monitors your site and alerts you if your sites are down.</p><p>Register and get api key from <a href="https://uptimerobot.com/dashboard.php#mySettings" target="_blank"><i>here</i></a>.</p>',
			],
		];
	}

	public function wp_dashboard_setup()
	{
		if ( WordPress::cuc( $this->options['dashboard_accesscap'] ) )
			wp_add_dashboard_widget(
				$this->classs( 'dashboard' ),
				_x( 'Uptime Monitor', 'Modules: Uptime: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_uptimerobot' ]
			);
	}

	public function widget_uptimerobot()
	{
		$data = $this->get_monitors();

		if ( self::isError( $data ) ) {
			echo HTML::warning( $data->get_error_message(), FALSE );
			return;
		}

		foreach ( $data as $monitor ) {

			// no need!
			// HTML::h3( $monitor['friendly_name'], FALSE, $monitor['url'] );

			// wrap to use core styles
			echo '<div id="dashboard_right_now"><div class="main">';
			echo '<table class="base-table-simple"><tbody>';

			foreach ( $monitor['logs'] as $log )
				echo '<tr><td>'.$this->get_uptimerobot_type( $log['type'] )
					.'</td><td>'.Utilities::htmlHumanTime( $log['datetime'], TRUE )
					.'</td><td>'.Utilities::htmlFromSeconds( $log['duration'], 2 )
					.'</td><td style="direction:ltr;">'.HTTP::htmlStatus( $log['reason']['code'] ).$log['reason']['detail']
					.'</td></tr>';

			echo '</tbody></table></div>';

			echo '<div class="sub"><table class="base-table-simple"><tbody><tr><td>';
				echo Utilities::getCounted( $monitor['all_time_uptime_ratio'], _x( 'Uptime Ratio: %s', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );
			echo '</td><td>';
				echo Utilities::getCounted( $monitor['average_response_time'], _x( 'Response Time: %s', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );
			echo '</td></tr><tr><td>';
				printf( _x( 'Interval: %s', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ), Utilities::htmlFromSeconds( $monitor['interval'], 2 ) );
			echo '</td><td>';
				echo Utilities::getCounted( $monitor['response_times'][0]['value'], _x( 'Last Response Time: %s', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );
			echo '</td></tr></tbody></table>';

			echo '</div></div>';
		}
	}

	private function get_uptimerobot_type( $type )
	{
		switch ( $type ) {
			case '1' : return HTML::getDashicon( 'warning', 'span', _x( 'Down', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );
			case '2' : return HTML::getDashicon( 'marker', 'span', _x( 'Up', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );
			case '99': return HTML::getDashicon( 'star-empty', 'span', _x( 'Paused', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );
			case '98': return HTML::getDashicon( 'star-filled', 'span', _x( 'Started', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );
		}

		return HTML::getDashicon( 'info', 'span', _x( 'Unknown!', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );
	}

	// @REF: https://uptimerobot.com/api#getMonitors
	private function get_monitors()
	{
		$endpoint = 'https://api.uptimerobot.com/v2/';
		$method   = 'getMonitors';

		$args = [
			'timeout'     => 15,
			'httpversion' => '1.1',
			'body'        => [
				'api_key'  => $this->api_key,
				'format'   => 'json',
				'timezone' => 1,
				'logs'     => 1,

				'all_time_uptime_durations' => 1,
				'all_time_uptime_ratio'     => 1,
				'response_times'            => 1,
				'response_times_average'    => 1,
			],
			'headers'     => [
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
				return new Error( 'unknown_response', _x( 'Something is wrong with UptimeRobot.com API!', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) );

			else if ( 'fail' == $data['stat'] )
				return new Error( $data['error']['type'], sprintf( _x( 'UptimeRobot.com API: %s', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ), str_replace( '_', ' ', $data['error']['type'] ) ) );

			else if ( 'ok' == $data['stat'] )
				return $data['monitors'];
		}

		return new Error( 'not_ok_status', HTTP::getStatusDesc( $status, _x( 'Unknown!', 'Modules: Uptime', GNETWORK_TEXTDOMAIN ) ) );
	}
}

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Dashboard extends ModuleCore
{

	protected $key   = 'dashboard';
	protected $front = FALSE;

	protected function setup_actions()
	{
		foreach ( array(
			'wp_network_dashboard_setup',
			'wp_user_dashboard_setup',
			'wp_dashboard_setup',
		) as $action )
			add_action( $action, array( $this, 'wp_dashboard_setup' ), 20 );

		// add_action( 'wp_ajax_dashboard_widgets', array( $this, 'ajax_dashboard_widgets' ), 0 );
		add_action( 'wp_ajax_gnetwork_dashboard', array( $this, 'ajax_dashboard_widgets' ), 0 );
	}

	public function wp_dashboard_setup()
	{
		if ( has_filter( 'gnetwork_dashoboard_external_feeds' ) ) {
			wp_add_dashboard_widget(
				'gnetwork_dashboard_external_feed',
				_x( 'External Feed', 'Dashboard Module: Dashboard Widget Title', GNETWORK_TEXTDOMAIN ),
				array( $this, 'widget_external_feed' )
			);
		}
	}

	public function widget_external_feed()
	{
		$feeds = array();

		foreach ( apply_filters( 'gnetwork_dashoboard_external_feeds', array() ) as $name => $feed )
			$feeds[$name] = array_merge( array(
				'link'         => 'http://geminorum.ir/',
				'url'          => 'http://geminorum.ir/feed',
				'title'        => $name,
				'items'        => 3,
				'show_summary' => 1,
				'show_author'  => 0,
				'show_date'    => 1,
			), $feed );

		wp_dashboard_cached_rss_widget( 'gnetwork_feeds', 'wp_dashboard_primary_output', $feeds );
	}

	public function ajax_dashboard_widgets()
	{
		require_once ABSPATH.'wp-admin/includes/dashboard.php';

		self::logArray( 'AJAX', $_GET );

		$pagenow = $_GET['pagenow'];
		if ( $pagenow === 'dashboard-user' || $pagenow === 'dashboard-network' || $pagenow === 'dashboard' ) {
			set_current_screen( $pagenow );
		}

		switch ( $_GET['widget'] ) {
			case 'gnetwork_dashboard_external_feed':

				$this->widget_external_feed();

			break;
		}

		wp_die();
	}
}

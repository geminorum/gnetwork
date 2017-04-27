<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Dashboard extends gNetwork\Module
{

	protected $key   = 'dashboard';
	protected $front = FALSE;
	protected $ajax  = TRUE;

	protected function setup_actions()
	{
		$this->action( 'current_screen' );

		foreach ( [
			'wp_network_dashboard_setup',
			'wp_user_dashboard_setup',
			'wp_dashboard_setup',
		] as $action )
			add_action( $action, [ $this, 'wp_dashboard_setup' ], 20 );
	}

	protected function setup_ajax( $request )
	{
		add_action( 'wp_ajax_gnetwork_dashboard', [ $this, 'ajax' ] );
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			&& $screen->post_type ) {

				if ( WordPress::cuc( 'manage_options' ) ) {

					ob_start();
						HTML::tableSide( get_all_post_type_supports( $screen->post_type ), FALSE );
					$content = ob_get_clean();

					$screen->add_help_tab( [
						'id'       => 'gnetwork-dashboard-posttype-overview',
						'title'    => _x( 'Post Type Supports', 'Modules: Dashboard: Help Content Title', GNETWORK_TEXTDOMAIN ),
						'content'  => '<p>'.$content.'</p>',
						'priority' => 99,
					] );
			}
		}
	}

	public function wp_dashboard_setup()
	{
		$screen = get_current_screen();

		remove_meta_box( 'dashboard_primary', $screen, 'side' );

		if ( is_multisite() && is_user_admin() && gNetwork()->option( 'dashboard_sites', 'user' ) )
			wp_add_dashboard_widget(
				'gnetwork_dashboard_user_sites',
				_x( 'Your Sites', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_user_sites' ]
			);

		if ( has_filter( $this->hook( 'external_feeds' ) ) ) {
			wp_add_dashboard_widget(
				'gnetwork_dashboard_external_feed',
				_x( 'External Feed', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_external_feed' ]
			);
		}

		if ( is_user_admin() && gNetwork()->option( 'tos_display', 'user' ) )
			wp_add_dashboard_widget(
				'gnetwork_dashboard_tos',
				gNetwork()->option( 'tos_title', 'user',
					_x( 'Terms of Service', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN )
				), [ $this, 'widget_tos' ]
			);
	}

	public function widget_external_feed()
	{
		$feeds = [];

		foreach ( $this->filters( 'external_feeds', [] ) as $name => $feed )
			$feeds[$name] = array_merge( [
				'link'         => 'http://geminorum.ir/',
				'url'          => 'http://geminorum.ir/feed',
				'title'        => $name,
				'items'        => 3,
				'show_summary' => 1,
				'show_author'  => 0,
				'show_date'    => 1,
			], $feed );

		wp_dashboard_cached_rss_widget( 'gnetwork_feeds', 'wp_dashboard_primary_output', $feeds );
	}

	public function ajax()
	{
		require_once( ABSPATH.'wp-admin/includes/dashboard.php' );

		switch ( $_GET['widget'] ) {

			case 'gnetwork_dashboard_external_feed':

				$this->widget_external_feed();

			break;
		}

		wp_die();
	}

	public function widget_tos()
	{
		echo '<div class="gnetwork-admin-wrap-widget -user-tos">';
			echo wpautop( gNetwork()->option( 'tos_text', 'user', gNetwork()->na() ) );
		echo '</div>';
	}

	public function widget_user_sites()
	{
		$blogs = get_blogs_of_user( get_current_user_id() );

		echo '<div class="gnetwork-admin-wrap-widget -user-sites">';

			if ( empty( $blogs ) )
				HTML::desc( gNetwork()->na() );
			else
				echo Site::tableUserSites( $blogs, FALSE );

		echo '</div>';
	}
}

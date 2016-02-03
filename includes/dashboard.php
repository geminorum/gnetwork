<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkDashboard extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = TRUE;
	protected $front_end  = FALSE;

	protected function setup_actions()
	{
		add_action( 'wp_network_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );
		add_action( 'wp_user_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );
		add_action( 'wp_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );
	}

	public function wp_dashboard_setup()
	{
		// FIXME: handle comma seperated
		if ( defined( 'GNETWORK_ADMIN_WIDGET_RSS' )
			&& constant( 'GNETWORK_ADMIN_WIDGET_RSS' ) ) {

			add_meta_box( 'abetterplanet_widget',
				_x( 'Network Feed', 'Admin Module: Dashboard Widget Title', GNETWORK_TEXTDOMAIN ),
				array( $this, 'widget_network_rss' ),
				'dashboard', 'normal', 'high' );
		}
	}

	public function widget_network_rss()
	{
		// FIXME: handle comma seperated
		//public function return_1600( $seconds ) { return 1600; }
		//add_filter( 'wp_feed_cache_transient_lifetime' , 'return_1600' );
		$rss = fetch_feed( constant( 'GNETWORK_ADMIN_WIDGET_RSS' ) );
		//remove_filter( 'wp_feed_cache_transient_lifetime' , 'return_1600' );

		if ( ! is_wp_error( $rss ) ) {
			// Figure out how many total items there are, but limit it to 3.
			$maxitems = $rss->get_item_quantity( 8 );
			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $rss->get_items( 0, $maxitems );

			if ( ! empty( $maxitems ) ) {
				?> <div class="rss-widget"><ul>
				<?php foreach ( $rss_items as $item ) { ?>
					<li><a class="rsswidget" href='<?php echo $item->get_permalink(); ?>'><?php echo $item->get_title(); ?></a> <span class="rss-date"><?php echo date_i18n( 'j F Y', $item->get_date( 'U' ) ); ?></span></li>
				<?php } ?></ul></div> <?php
			}
		}
	}
}

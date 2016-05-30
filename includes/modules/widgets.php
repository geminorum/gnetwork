<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Widgets extends ModuleCore
{

	protected $key     = 'widgets';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
	}

	public function widgets_init()
	{
		$widgets = array(
			GNETWORK_DIR.'includes/widgets/devlegend.php' => __NAMESPACE__.'\\DevLegend_Widget',
			GNETWORK_DIR.'includes/widgets/shortcode.php' => __NAMESPACE__.'\\Shortcode_Widget',
		);

		if ( class_exists( __NAMESPACE__.'\\Tracking' ) ) {
			$widgets[GNETWORK_DIR.'includes/widgets/tracking-gplusbadge.php'] = __NAMESPACE__.'\\Tracking_GPlusBadge_Widget';
			$widgets[GNETWORK_DIR.'includes/widgets/tracking-quantcast.php']  = __NAMESPACE__.'\\Tracking_Quantcast_Widget';
		}

		foreach ( apply_filters( $this->hook(), $widgets ) as $path => $widget ) {

			if ( file_exists( $path ) ) {
				require_once( $path );

				if ( class_exists( $widget ) )
					register_widget( $widget );
			}
		}
	}
}

<?php namespace geminorum\gNetwork\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;

class TrackingQuantcast extends \WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-quantcast-widget',
			_x( 'gNetwork Tracking: Quantcast Widget', 'Widget: Title', 'gnetwork' ),
			[
				'classname'   => 'gnetwork-wrap-widget -quantcast-widget',
				'description' => _x( 'Simple Quantcast Data Badge', 'Widget: Description', 'gnetwork' ),

				'show_instance_in_rest' => TRUE,
			]
		);
	}

	public function widget( $args, $instance )
	{
		if ( $domain = gNetwork()->option( 'primary_domain', 'tracking' ) ) {

			echo $args['before_widget'];

			echo '<div class="gnetwork-wrap-iframe">'.HTML::tag( 'iframe', [
				'frameborder'  => '0',
				'marginheight' => '0',
				'marginwidth'  => '0',
				'height'       => '120',
				'width'        => '160',
				'scrolling'    => 'no',
				'src'          => 'https://widget.quantcast.com/'.$domain.'/10?&timeWidth=1&daysOfData=90',
			], NULL ).'</div>';

			echo $args['after_widget'];
		}
	}
}

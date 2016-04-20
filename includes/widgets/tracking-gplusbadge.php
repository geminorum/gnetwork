<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Tracking_GPlusBadge_Widget extends \WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-gplusbadge-widget',
			_x( 'gNetwork Tracking: Google Plus Badge', 'Widget: Title', GNETWORK_TEXTDOMAIN ),
			array(
				'classname'   => 'gnetwork-wrap-widget -gplusbadge-widget',
				'description' => _x( 'Simple Google Plus Badge', 'Widget: Description', GNETWORK_TEXTDOMAIN )
			) );
	}

	public function form( $instance )
	{
		$html = Utilities::html( 'input', array(
			'type'  => 'number',
			'id'    => $this->get_field_id( 'width' ),
			'name'  => $this->get_field_name( 'width' ),
			'value' => isset( $instance['width'] ) ? $instance['width'] : '300',
			'class' => 'small-text',
			'dir'   => 'ltr',
		) );

		echo '<p>'. Utilities::html( 'label', array(
			'for' => $this->get_field_id( 'width' ),
		), _x( 'Side bar width:', 'Widgets Module', GNETWORK_TEXTDOMAIN ).' '.$html ).'</p>';

		$html = Utilities::html( 'input', array(
			'type'  => 'text',
			'id'    => $this->get_field_id( 'override' ),
			'name'  => $this->get_field_name( 'override' ),
			'value' => isset( $instance['override'] ) ? $instance['override'] : '',
			'class' => 'widefat',
			'dir'   => 'ltr',
		) );

		echo '<p>'. Utilities::html( 'label', array(
			'for' => $this->get_field_id( 'override' ),
		), _x( 'Override Publisher ID:', 'Widgets Module', GNETWORK_TEXTDOMAIN ).' '.$html );

		echo '<br /><span class="description">'._x( 'Leave empty to use site Publisher ID', 'Widgets Module', GNETWORK_TEXTDOMAIN ).'</span>';
		echo '</p>';
	}

	public function widget( $args, $instance )
	{
		$override = isset( $instance['override'] ) ? $instance['override'] : FALSE;

		if ( isset( gNetwork()->tracking ) ) {

			if ( $override || gNetwork()->option( 'plus_publisher', 'tracking' ) ) {

				$html = gNetwork()->tracking->shortcode_google_plus_badge( array(
					'id'      => $override,
					'width'   => isset( $instance['width'] ) ? $instance['width'] : '300',
					'context' => 'widget',
					'wrap'    => FALSE,
				) );

				if ( $html ) {
					echo $args['before_widget'];
						echo '<div class="gnetwork-wrap-iframe">'.$html.'</div>';
					echo $args['after_widget'];
				}
			}
		}
	}
}
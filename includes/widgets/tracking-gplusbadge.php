<?php namespace geminorum\gNetwork\Widgets;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;

class Tracking_GPlusBadge_Widget extends \WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-gplusbadge-widget',
			_x( 'gNetwork Tracking: Google Plus Badge', 'Widget: Title', GNETWORK_TEXTDOMAIN ),
			[
				'classname'   => 'gnetwork-wrap-widget -gplusbadge-widget',
				'description' => _x( 'Simple Google Plus Badge', 'Widget: Description', GNETWORK_TEXTDOMAIN )
			]
		);
	}

	public function form( $instance )
	{
		echo '<div class="gnetwork-admin-wrap-widgetform">';

		$html = HTML::tag( 'input', [
			'type'  => 'number',
			'id'    => $this->get_field_id( 'width' ),
			'name'  => $this->get_field_name( 'width' ),
			'value' => isset( $instance['width'] ) ? $instance['width'] : '300',
			'class' => 'small-text',
			'dir'   => 'ltr',
		] );

		echo '<p>'.HTML::tag( 'label', [
			'for' => $this->get_field_id( 'width' ),
		], _x( 'Side bar width:', 'Widgets Module', GNETWORK_TEXTDOMAIN ).' '.$html ).'</p>';

		$html = HTML::tag( 'input', [
			'type'  => 'text',
			'id'    => $this->get_field_id( 'override' ),
			'name'  => $this->get_field_name( 'override' ),
			'value' => isset( $instance['override'] ) ? $instance['override'] : '',
			'class' => 'widefat',
			'dir'   => 'ltr',
		] );

		echo '<p>'.HTML::tag( 'label', [
			'for' => $this->get_field_id( 'override' ),
		], _x( 'Override Publisher ID:', 'Widgets Module', GNETWORK_TEXTDOMAIN ).' '.$html );

		echo '<br />';

		HTML::desc( _x( 'Leave empty to use site Publisher ID', 'Widgets Module', GNETWORK_TEXTDOMAIN ), FALSE );

		echo '</p>';
		echo '</div>';
	}

	public function widget( $args, $instance )
	{
		$override = isset( $instance['override'] ) ? $instance['override'] : FALSE;

		if ( isset( gNetwork()->tracking ) ) {

			if ( $override || gNetwork()->option( 'plus_publisher', 'tracking' ) ) {

				$html = gNetwork()->tracking->shortcode_google_plus_badge( [
					'id'      => $override,
					'width'   => isset( $instance['width'] ) ? $instance['width'] : '300',
					'context' => 'widget',
					'wrap'    => FALSE,
				] );

				if ( $html ) {
					echo $args['before_widget'];
						echo '<div class="gnetwork-wrap-iframe">'.$html.'</div>';
					echo $args['after_widget'];
				}
			}
		}
	}
}

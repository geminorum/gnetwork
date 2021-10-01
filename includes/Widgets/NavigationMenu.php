<?php namespace geminorum\gNetwork\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;

class NavigationMenu extends \WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-navmenu-widget',
			_x( 'gNetwork Navigation: Menu', 'Widget: Title', 'gnetwork' ),
			[
				'classname'   => 'gnetwork-wrap-widget -navmenu-widget',
				'description' => _x( 'Global Navigation Menus', 'Widget: Description', 'gnetwork' ),

				'show_instance_in_rest' => TRUE,
			]
		);
	}

	public function form( $instance )
	{
		echo '<div class="-wrap -admin-widgetform">';

		if ( isset( gNetwork()->navigation ) ) {

			$current = isset( $instance['location'] ) ? $instance['location'] : '';

			foreach ( gNetwork()->navigation->get_global_menus() as $constant => $desc ) {

				if ( ! defined( $constant ) )
					continue;

				if ( ! $location = constant( $constant ) )
					continue;

				$html = HTML::tag( 'input', [
					'type'    => 'radio',
					'id'      => $this->get_field_id( 'location' ).'-'.$location,
					'name'    => $this->get_field_name( 'location' ),
					'value'   => $location,
					'checked' => $location == $current,
				] );

				HTML::label( $html.'&nbsp;'.$desc, $this->get_field_id( 'location' ).'-'.$location );
			}

		} else {
			echo '<br />';
			HTML::desc( _x( 'Navigation module not found!', 'Modules: Widgets', 'gnetwork' ), FALSE, '-empty' );
		}

		echo '</div>';
	}

	public function widget( $args, $instance )
	{
		if ( ! class_exists( '\geminorum\gNetwork\Modules\Navigation' ) )
			return;

		$location = isset( $instance['location'] ) ? $instance['location'] : FALSE;

		if ( $html = \geminorum\gNetwork\Modules\Navigation::getGlobalMenu( $location, FALSE ) )
			echo $args['before_widget'].$html.$args['after_widget'];
	}
}

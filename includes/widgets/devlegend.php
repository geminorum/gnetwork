<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class DevLegend_Widget extends \WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-legend-widget',
			_x( 'gNetwork: Development Legend', 'Widget: Title', GNETWORK_TEXTDOMAIN ),
			array(
				'classname'   => 'gnetwork-wrap-widget -legend-widget',
				'description' => _x( 'Simple Changelog Legend', 'Widget: Description', GNETWORK_TEXTDOMAIN )
			) );
	}

	public function widget( $args, $instance )
	{
		echo $args['before_widget'];
			echo $args['before_title'].'legend'.$args['after_title'];

			$legend = array(
				'*' => 'security fix',
				'#' => 'bug fix',
				'$' => 'language fix or change',
				'+' => 'addition',
				'^' => 'change',
				'-' => 'removed',
				'!' => 'note',
			);

			HTML::tableCode( $legend, TRUE );

		echo $args['after_widget'];
	}
}

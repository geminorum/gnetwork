<?php namespace geminorum\gNetwork\Widgets;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;

class CodeLegend extends \WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-legend-widget',
			_x( 'gNetwork Code: Legend', 'Widget: Title', GNETWORK_TEXTDOMAIN ),
			[
				'classname'   => 'gnetwork-wrap-widget -legend-widget',
				'description' => _x( 'Simple Changelog Legend', 'Widget: Description', GNETWORK_TEXTDOMAIN )
			]
		);
	}

	public function widget( $args, $instance )
	{
		echo $args['before_widget'];
			echo $args['before_title'].'legend'.$args['after_title'];

			$legend = [
				'*' => 'security fix',
				'#' => 'bug fix',
				'$' => 'language fix or change',
				'+' => 'addition',
				'^' => 'change',
				'-' => 'removed',
				'!' => 'note',
			];

			echo HTML::tableCode( $legend, TRUE );

		echo $args['after_widget'];
	}
}

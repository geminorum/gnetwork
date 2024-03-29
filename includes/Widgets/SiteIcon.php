<?php namespace geminorum\gNetwork\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;

class SiteIcon extends \WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-siteicon-widget',
			_x( 'gNetwork Branding: Site Icon', 'Widget: Title', 'gnetwork' ),
			[
				'classname'   => 'gnetwork-wrap-widget -siteicon-widget',
				'description' => _x( 'Site Icon', 'Widget: Description', 'gnetwork' ),

				'customize_selective_refresh' => TRUE,
				'show_instance_in_rest'       => TRUE,
			]
		);
	}

	public function form( $instance )
	{
		if ( is_customize_preview() ) {

			echo '<p><a class="button" href="javascript:wp.customize.control(\'site_icon\').focus()">';
				_ex( 'Setup or Change Site Icon', 'Modules: Widgets: Site Icon', 'gnetwork' );
			echo '</a></p>';

		} else {

			parent::form( $instance );
		}
	}

	public function widget( $args, $instance )
	{
		if ( has_site_icon() )
			echo $args['before_widget'].'<img alt="" class="-siteicon" src="'.esc_url( get_site_icon_url() ).'" />'.$args['after_widget'];

		else if ( is_customize_preview() )
			echo $args['before_widget']
				._x( 'Please set up your site icon in the &#8220;Site Identity&#8221; section.', 'Modules: Widgets: Site Icon', 'gnetwork' )
				.$args['after_widget'];
	}
}

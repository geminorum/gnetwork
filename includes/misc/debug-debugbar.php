<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Debug_Bar_gNetwork extends \Debug_Bar_Panel
{

	public function init()
	{
		$this->title( _x( 'Debug Extras', 'Debug Module: Debug Bar Panel Title', GNETWORK_TEXTDOMAIN ) );
	}

	public function render()
	{
		echo '<div id="gnetwork-debugbar-panel" class="gnetwork-admin-wrap debugbar-panel">';
		foreach ( apply_filters( 'gnetwork_debugbar_panel_groups', array() ) as $group_slug => $group_title ) {
			echo '<h3>'.$group_title.'</h3>';
			echo '<div class="group">';
			do_action( 'gnetwork_debugbar_panel_'.$group_slug );
			echo '</div>';
		}
		echo '</div>';
	}
}

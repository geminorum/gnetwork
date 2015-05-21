<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class Debug_Bar_gNetwork extends Debug_Bar_Panel 
{
	public function init() 
	{
		$this->title( _x( 'gNetwork', 'debug bar panel title', GNETWORK_TEXTDOMAIN ) );
	}

	public function render() 
	{
		echo '<div id="gnetwork-debugbar-panel">';
		foreach( apply_filters( 'gnetwork_debugbar_panel_groups', array() ) as $group_slug => $group_title ) {
			echo '<h3>'.$group_title.'</h3>';
			echo '<div class="gnetwork-debugbar-panel-group">';
			do_action( 'gnetwork_debugbar_panel_'.$group_slug );
			echo '</div>';
		}
		echo '</div>';
	}
}
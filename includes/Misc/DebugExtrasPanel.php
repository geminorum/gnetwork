<?php namespace geminorum\gNetwork\Misc;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;

class DebugExtrasPanel extends \Debug_Bar_Panel
{

	public function init()
	{
		$this->title( _x( 'Debug Extras', 'Modules: Debug: Debug Bar Panel Title', GNETWORK_TEXTDOMAIN ) );
	}

	public function render()
	{
		echo '<div id="gnetwork-debugbar-panel" class="gnetwork-admin-wrap debugbar-panel">';
		foreach ( apply_filters( 'gnetwork_debugbar_panel_groups', [] ) as $group_slug => $group_title ) {
			HTML::h3( $group_title, '-title' );
			echo '<div class="-group">';
				do_action( 'gnetwork_debugbar_panel_'.$group_slug );
			echo '</div>';
		}
		echo '</div>';
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class Debug_Bar_gNetwork extends Debug_Bar_Panel
{
	public function init()
	{
		$this->title( _x( 'Extras', 'debug bar panel title', GNETWORK_TEXTDOMAIN ) );
	}

	public function render()
	{
		echo '<div id="gnetwork-debugbar-panel" class="gnetwork-admin-wrap debugbar-panel">';
		foreach( apply_filters( 'gnetwork_debugbar_panel_groups', array() ) as $group_slug => $group_title ) {
			echo '<h3>'.$group_title.'</h3>';
			echo '<div class="group">';
			do_action( 'gnetwork_debugbar_panel_'.$group_slug );
			echo '</div>';
		}
		echo '</div>';
	}
}

class Debug_Bar_gNetworkMeta extends Debug_Bar_Panel
{
	public function init()
	{
		$this->title( _x( 'Post Meta', 'debug bar panel title', GNETWORK_TEXTDOMAIN ) );
	}

	public function render()
	{
		echo '<div id="gnetwork-meta-debugbar-panel" class="gnetwork-admin-wrap debugbar-panel">';

		$meta = get_post_meta( get_the_ID() );
		if ( $meta ) {
			foreach( $meta as $key => $values ) {
				echo '<h3>'.$key.'</h3>';
				echo '<div class="group">';
				foreach( $values as $value ){
					$data = maybe_unserialize( $value );
					gNetworkUtilities::dump( $data );
				}
				echo '</div>';
			}
		} else {
			echo '<div class="empty">';
				_ex( 'No Meta!', 'debug bar panel', GNETWORK_TEXTDOMAIN );
			echo '</div>';
		}

		echo '</div>';
	}
}

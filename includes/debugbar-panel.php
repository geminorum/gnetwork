<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class Debug_Bar_gNetwork extends Debug_Bar_Panel
{

	public function init()
	{
		$this->title( _x( 'Extras', '[Debug Module] debug bar panel title', GNETWORK_TEXTDOMAIN ) );
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

class Debug_Bar_gNetworkMeta extends Debug_Bar_Panel
{
	
	public function init()
	{
		$this->title( _x( 'Meta Data', '[Debug Module] debug bar panel title', GNETWORK_TEXTDOMAIN ) );
	}

	public function render()
	{
		echo '<div id="gnetwork-meta-debugbar-panel" class="gnetwork-admin-wrap debugbar-panel">';

		if ( is_tax() && function_exists( 'get_term_custom' ) )
			$meta = get_term_custom( get_queried_object_id() );

		else if ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE && current_user_can( 'edit_users' ) )
			$meta = get_user_meta( get_current_user_id() );

		else if ( isset( $_GET['user_id'] ) && $_GET['user_id'] && current_user_can( 'edit_users' ) )
			$meta = get_user_meta( $_GET['user_id'] );

		else // is_singular()
			$meta = get_post_meta( get_the_ID() );
			// $meta = get_post_custom( get_the_ID() );

		if ( $meta ) {
			foreach ( $meta as $key => $values ) {
				echo '<h3>'.$key.'</h3>';
				echo '<div class="group">';
				foreach ( $values as $value ){
					$data = maybe_unserialize( $value );
					if ( is_array( $value ) )
						gNetworkUtilities::tableSide( $data );
					else
						gNetworkUtilities::dump( $data );
				}
				echo '</div>';
			}
		} else {
			echo '<div class="empty">';
				_ex( 'No Meta Data!', '[Debug Module] debug bar panel', GNETWORK_TEXTDOMAIN );
			echo '</div>';
		}

		echo '</div>';
	}
}

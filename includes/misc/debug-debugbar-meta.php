<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Debug_Bar_gNetworkMeta extends \Debug_Bar_Panel
{

	public function init()
	{
		$this->title( _x( 'Meta Data', 'Debug Module: Debug Bar Panel Title', GNETWORK_TEXTDOMAIN ) );
	}

	public function render()
	{
		echo '<div id="gnetwork-meta-debugbar-panel" class="gnetwork-admin-wrap debugbar-panel">';

		if ( is_tax() && function_exists( 'get_term_custom' ) )
			$meta = get_term_custom( get_queried_object_id() );

		else if ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE && current_user_can( 'edit_users' ) )
			$meta = get_user_meta( get_current_user_id() );

		else if ( ! empty( $_GET['user_id'] ) && current_user_can( 'edit_users' ) )
			$meta = get_user_meta( $_GET['user_id'] );

		else if ( ! empty( $_GET['tag_ID'] ) )
			$meta = get_term_meta( $_GET['tag_ID'] );

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
						Utilities::tableSide( $data );
					else
						Utilities::dump( $data );
				}
				echo '</div>';
			}
		} else {
			echo '<div class="empty">';
				_ex( 'No Meta Data!', 'Debug Module: Debug Bar Panel', GNETWORK_TEXTDOMAIN );
			echo '</div>';
		}

		echo '</div>';
	}
}

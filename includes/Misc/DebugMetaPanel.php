<?php namespace geminorum\gNetwork\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;

class DebugMetaPanel extends \Debug_Bar_Panel
{

	public function init()
	{
		$this->title( _x( 'Meta Data', 'Modules: Debug: Debug Bar Panel Title', GNETWORK_TEXTDOMAIN ) );
	}

	public function render()
	{
		echo '<div id="gnetwork-meta-debugbar-panel" class="gnetwork-admin-wrap debugbar-panel">';

		if ( is_tax() && function_exists( 'get_term_custom' ) ) {

			$meta = get_term_custom( get_queried_object_id() );

		} else if ( ! is_admin() && is_singular() ) {

			$post = get_post( get_queried_object_id() );
			$meta = get_post_meta( $post->ID );

			$post->post_content = HTML::dump( $post->post_content, TRUE, FALSE );

			echo '<div class="-post">';
				HTML::tableSide( $post );
			echo '</div>';

		} else if ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE && current_user_can( 'edit_users' ) ) {

			$meta = get_user_meta( get_current_user_id() );

		} else if ( ! empty( $_GET['user_id'] ) && current_user_can( 'edit_users' ) ) {

			$meta = get_user_meta( $_GET['user_id'] );

		} else if ( ! empty( $_GET['tag_ID'] ) ) {

			$meta = get_term_meta( $_GET['tag_ID'] );

		} else if ( $post = get_post() ) {

			$meta = get_post_meta( $post->ID );

			$post->post_content = HTML::dump( $post->post_content, TRUE, FALSE );

			echo '<div class="-post">';
				HTML::tableSide( $post );
			echo '</div>';

		} else if ( ! empty( $_GET['post'] ) ) {

			$meta = get_post_meta( $_GET['post'] );

			echo '<div class="-post">';
				HTML::tableSide( get_post( $_GET['post'] ) );
			echo '</div>';

		} else {

			$meta = FALSE;
		}

		if ( $meta ) {

			HTML::tableSide( $meta );

		} else {

			HTML::desc( _x( 'No metadata found!', 'Modules: Debug: Debug Bar Panel', GNETWORK_TEXTDOMAIN ), TRUE, '-empty' );
		}

		echo '</div>';
	}
}

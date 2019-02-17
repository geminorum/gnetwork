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
		$meta = FALSE;

		echo '<div id="gnetwork-meta-debugbar-panel" class="gnetwork-admin-wrap debugbar-panel">';

		if ( is_admin() && function_exists( 'get_current_screen' ) ) {

			$screen = get_current_screen();

			if ( ! $screen ) {

				// no screen!

			} else if ( 'post' == $screen->base ) {

				$meta = $this->render_post( $_GET['post'] );

			} else if ( 'term' == $screen->base ) {

				echo '<div class="-taxonomy-object">';
					HTML::tableSide( get_term( $_GET['tag_ID'] ) );
				echo '</div>';

				$meta = get_term_meta( $_GET['tag_ID'] );

			} else if ( 'edit-tags' == $screen->base && $screen->taxonomy ) {

				echo '<div class="-taxonomy-object">';
					HTML::tableSide( get_taxonomy( $screen->taxonomy ) );
				echo '</div>';

			} else if ( 'edit' == $screen->base && $screen->post_type ) {

				echo '<div class="-posttype-object">';
					HTML::tableSide( get_post_type_object( $screen->post_type ) );
				echo '</div>';

				echo '<div class="-posttype-supports">';
					HTML::h3( _x( 'Posttype Supports', 'Modules: Debug: Debug Bar Panel', GNETWORK_TEXTDOMAIN ) );
					HTML::tableSide( get_all_post_type_supports( $screen->post_type ), FALSE );
				echo '</div>';

			} else if ( in_array( $screen->base, [ 'profile-user', 'profile-network', 'user-edit-network', 'profile', 'user-edit', 'users_page_profile' ] ) ) {

				if ( current_user_can( 'edit_users' ) )
					$meta = get_user_meta( empty( $_GET['user_id'] ) ? get_current_user_id() : $_GET['user_id'] );
			}

		} else if ( is_tax() && function_exists( 'get_term_custom' ) ) {

			$meta = get_term_custom( get_queried_object_id() );

		} else if ( ! is_admin() && is_singular() ) {

			$meta = $this->render_post( get_queried_object_id() );

		} else if ( $post = get_post() ) {

			$meta = $this->render_post( $post );

		} else if ( ! empty( $_GET['post'] ) ) {

			$meta = $this->render_post( $_GET['post'] );
		}

		if ( $meta ) {

			echo '<div class="-meta">';
				HTML::tableSide( $meta );
			echo '</div>';

		} else {

			HTML::h2( _x( 'No metadata found!', 'Modules: Debug: Debug Bar Panel', GNETWORK_TEXTDOMAIN ) );
		}

		echo '</div>';
	}

	private function render_post( $post )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$post->post_content = HTML::dump( $post->post_content, TRUE, FALSE );
		$post->post_name    = rawurldecode( $post->post_name );

		echo '<div class="-post">';
			HTML::tableSide( $post );
		echo '</div>';

		return get_post_meta( $post->ID );
	}
}

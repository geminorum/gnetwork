<?php namespace geminorum\gNetwork\Misc\QM;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;

class CollectorCurrentObject extends \QM_Collector
{

	public $id = 'currentobject';

	public function process()
	{
		$this->data['type']       = NULL;
		$this->data['object']     = NULL;
		$this->data['meta']       = NULL;
		$this->data['taxonomies'] = NULL;
		$this->data['supports']   = NULL;

		if ( is_admin() && function_exists( 'get_current_screen' ) ) {

			$screen = get_current_screen();

			if ( ! $screen ) {

				// no screen!

			} else if ( 'post' == $screen->base && ! empty( $_GET['post'] ) ) {

				$this->data['type']       = _x( 'Post', 'Modules: Debug: QM Collector Type', 'gnetwork' );
				$this->data['object']     = get_post( $_GET['post'] );
				$this->data['meta']       = get_post_meta( $_GET['post'] );
				$this->data['taxonomies'] = get_object_taxonomies( $this->data['object'] );

			} else if ( 'term' == $screen->base ) {

				$this->data['type']       = _x( 'Term', 'Modules: Debug: QM Collector Type', 'gnetwork' );
				$this->data['object']     = get_term( $_GET['tag_ID'] );
				$this->data['meta']       = get_term_meta( $_GET['tag_ID'] );
				$this->data['taxonomies'] = get_object_taxonomies( $this->data['object']->taxonomy );

			} else if ( 'edit-tags' == $screen->base && $screen->taxonomy ) {

				$this->data['type']       = _x( 'Taxonomy', 'Modules: Debug: QM Collector Type', 'gnetwork' );
				$this->data['object']     = get_taxonomy( $screen->taxonomy );
				$this->data['taxonomies'] = get_object_taxonomies( $screen->taxonomy );

			} else if ( 'edit' == $screen->base && $screen->post_type ) {

				$this->data['type']       = _x( 'PostType', 'Modules: Debug: QM Collector Type', 'gnetwork' );
				$this->data['object']     = get_post_type_object( $screen->post_type );
				$this->data['taxonomies'] = get_object_taxonomies( $screen->post_type );
				$this->data['supports']   = get_all_post_type_supports( $screen->post_type );

			} else if ( in_array( $screen->base, [ 'profile-user', 'profile-network', 'user-edit-network', 'profile', 'user-edit', 'users_page_profile' ] ) ) {

				$user_id = empty( $_GET['user_id'] ) ? get_current_user_id() : $_GET['user_id'];

				$this->data['type']       = _x( 'User', 'Modules: Debug: QM Collector Type', 'gnetwork' );
				$this->data['object']     = get_user_by( 'id', $user_id );
				$this->data['meta']       = get_user_meta( $user_id );
				$this->data['taxonomies'] = get_object_taxonomies( 'user' );
			}

		} else if ( is_tax() || is_tag() || is_category() ) {

			$term_id = get_queried_object_id();

			$this->data['type']       = _x( 'Term', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = get_term( $term_id );
			$this->data['meta']       = get_term_meta( $term_id );
			$this->data['taxonomies'] = get_object_taxonomies( $this->data['object']->taxonomy );

		} else if ( is_author() ) {

			$user_id = get_queried_object_id();

			$this->data['type']       = _x( 'User', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = get_user_by( 'id', $user_id );
			$this->data['meta']       = get_user_meta( $user_id );
			$this->data['taxonomies'] = get_object_taxonomies( 'user' );

		} else if ( ! is_admin() && is_singular() ) {

			$post_id = get_queried_object_id();

			$this->data['type']       = _x( 'Post', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = get_post( $post_id );
			$this->data['meta']       = get_post_meta( $post_id );
			$this->data['taxonomies'] = get_object_taxonomies( $this->data['object'] );

		} else if ( $post = get_post() ) {

			$this->data['type']       = _x( 'Post', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = $post;
			$this->data['meta']       = get_post_meta( $post->ID );
			$this->data['taxonomies'] = get_object_taxonomies( $this->data['object'] );

		} else if ( ! empty( $_GET['post'] ) ) {

			$this->data['type']       = _x( 'Post', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = get_post( $_GET['post'] );
			$this->data['meta']       = get_post_meta( $_GET['post'] );
			$this->data['taxonomies'] = get_object_taxonomies( $this->data['object'] );
		}
	}
}

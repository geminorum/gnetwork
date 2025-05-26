<?php namespace geminorum\gNetwork\Misc\QM;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

#[\AllowDynamicProperties]
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
				$this->data['taxonomies'] = $this->_get_object_taxonomies( $this->data['object'], $_GET['post'] );
				$this->data['comments']   = $this->_get_obejct_comments( $_GET['post'] );

			} else if ( 'term' == $screen->base ) {

				$this->data['type']       = _x( 'Term', 'Modules: Debug: QM Collector Type', 'gnetwork' );
				$this->data['object']     = get_term( $_GET['tag_ID'] );
				$this->data['meta']       = get_term_meta( $_GET['tag_ID'] );
				$this->data['taxonomies'] = $this->_get_object_taxonomies( $this->data['object']->taxonomy, $_GET['tag_ID'] );

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
				$this->data['taxonomies'] = $this->_get_object_taxonomies( 'user', $user_id );

			} else if ( 'woocommerce' === $screen->parent_base && 'woocommerce_page_wc-orders' === $screen->base ) {

				if ( ! $order = wc_get_order( (int) ( empty( $_GET['id'] ) ? 0 : $_GET['id'] ) ) )
					return;

				$this->data['type']   = _x( 'WooCommerce Order', 'Modules: Debug: QM Collector Type', 'gnetwork' );
				$this->data['object'] = $order->get_data();
				$this->data['meta']   = $order->get_meta_data();
			}

		} else if ( is_tax() || is_tag() || is_category() ) {

			$term_id = get_queried_object_id();

			$this->data['type']       = _x( 'Term', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = get_term( $term_id );
			$this->data['meta']       = get_term_meta( $term_id );
			$this->data['taxonomies'] = $this->_get_object_taxonomies( $this->data['object']->taxonomy ?? get_query_var( 'taxonomy' ), $term_id );

		} else if ( is_author() ) {

			$user_id = get_queried_object_id();

			$this->data['type']       = _x( 'User', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = get_user_by( 'id', $user_id );
			$this->data['meta']       = get_user_meta( $user_id );
			$this->data['taxonomies'] = $this->_get_object_taxonomies( 'user', $user_id );

		} else if ( ! is_admin() && is_singular() ) {

			$post_id = get_queried_object_id();

			$this->data['type']       = _x( 'Post', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = get_post( $post_id );
			$this->data['meta']       = get_post_meta( $post_id );
			$this->data['taxonomies'] = $this->_get_object_taxonomies( $this->data['object'], $post_id );

		} else if ( $post = get_post() ) {

			$this->data['type']       = _x( 'Post', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = $post;
			$this->data['meta']       = get_post_meta( $post->ID );
			$this->data['taxonomies'] = $this->_get_object_taxonomies( $this->data['object'], $post->ID );
			$this->data['comments']   = $this->_get_obejct_comments( $post->ID );

		} else if ( ! empty( $_GET['post'] ) ) {

			$this->data['type']       = _x( 'Post', 'Modules: Debug: QM Collector Type', 'gnetwork' );
			$this->data['object']     = get_post( $_GET['post'] );
			$this->data['meta']       = get_post_meta( $_GET['post'] );
			$this->data['taxonomies'] = $this->_get_object_taxonomies( $this->data['object'], $_GET['post'] );
			$this->data['comments']   = $this->_get_obejct_comments( $_GET['post'] );
		}
	}

	private function _get_object_taxonomies( $type, $id )
	{
		$list = [];

		foreach ( (array) get_object_taxonomies( $type ) as $taxonomy )
			$list[$taxonomy] = get_the_terms( $id, $taxonomy );

		return $list;
	}

	private function _get_obejct_comments( $id )
	{
		$args = [
			'post_id'   => $id,
			'post_type' => 'any',
			'status'    => 'any',
			'type'      => '',
			'fields'    => '',
			'number'    => '',
			// 'order'     => 'ASC',

			'update_comment_meta_cache' => TRUE,
			'update_comment_post_cache' => FALSE,
		];

		$query = new \WP_Comment_Query;
		$items = $query->query( $args );

		return $items;
	}
}

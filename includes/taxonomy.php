<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Taxonomy extends ModuleCore
{

	protected $key     = 'taxonomy';
	protected $network = FALSE;
	protected $front   = FALSE;

	protected function setup_actions()
	{
		add_action( 'admin_init', array( $this, 'admin_init' ), 20 );

		add_action( 'load-edit-tags.php', array( $this, 'load_edit_tags_php' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function admin_init()
	{
		// Originally from v1.3 : Visual Term Description Editor
		// http://wordpress.org/plugins/visual-term-description-editor/
		// https://github.com/bungeshea/visual-term-description-editor

		// only users with the "publish_posts" capability can use this feature
		if ( ! current_user_can( 'publish_posts' ) )
			return;

		$taxonomies = get_taxonomies( '', 'names' );

		// Remove the filters which disallow HTML in term descriptions
		remove_filter( 'pre_term_description', 'wp_filter_kses' );
		remove_filter( 'term_description', 'wp_kses_data' );

		// add filters to disallow unsafe HTML tags
		if ( ! current_user_can( 'unfiltered_html ' ) ) {
			add_filter( 'pre_term_description', 'wp_kses_post' );
			add_filter( 'term_description', 'wp_kses_post' );
		}

		// evaluate shortcodes
		// add_filter( 'term_description', 'do_shortcode' );
		// convert smilies
		// add_filter( 'term_description', 'convert_smilies' );

		// loop through the taxonomies, adding actions
		foreach ( $taxonomies as $taxonomy ) {
			add_action( $taxonomy.'_edit_form_fields', array( $this, 'render_field_edit' ), 1, 2 );
			add_action( $taxonomy.'_add_form_fields', array( $this, 'render_field_add' ), 1, 1 );
		}
	}

	// Add the visual editor to the edit tag screen
	public function render_field_edit( $tag, $taxonomy )
	{
		$settings = array(
			'textarea_name' => 'description',
			'textarea_rows' => 10,
		);

		?><tr class="form-field term-description-wrap">
			<th scope="row" valign="top"><label for="description"><?php _ex( 'Description', 'Taxonomy Description', GNETWORK_TEXTDOMAIN ); ?></label></th>
			<td><?php wp_editor( htmlspecialchars_decode( $tag->description ), 'html-description', $settings ); ?>
			<p class="description"><?php _e( 'The description is not prominent by default, however some themes may show it.', GNETWORK_TEXTDOMAIN ); ?></p></td>
			<script type="text/javascript">jQuery( 'textarea#description' ).closest( '.form-field' ).remove();</script>
		</tr><?php
	}

	// Add the visual editor to the add new tag screen
	public function render_field_add( $taxonomy )
	{
		$settings = array(
			'textarea_name' => 'description',
			'textarea_rows' => 7,
			'teeny'         => TRUE,
			'media_buttons' => FALSE,
		);

		?><div class="form-field term-description-wrap"><label for="tag-description"><?php _ex( 'Description', 'Taxonomy Description', GNETWORK_TEXTDOMAIN ); ?></label>
			<?php wp_editor( '', 'html-tag-description', $settings ); ?>
			<p class="description"> <?php _e( 'The description is not prominent by default; however, some themes may show it.' ); ?></p>
			<script type="text/javascript">
				jQuery( 'textarea#tag-description' ).closest( '.form-field' ).remove();
				jQuery(function($){$( '#addtag' ).on( 'mousedown', '#submit', function(){tinyMCE.triggerSave();});});
			</script>
		</div><?php
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally adapted from : Term Management Tools by scribu v1.1.4
// https://github.com/scribu/wp-term-management-tools
// https://wordpress.org/plugins/term-management-tools/
// http://scribu.net/wordpress/term-management-tools

	private function get_actions( $taxonomy )
	{
		$actions = apply_filters( 'gnetwork_taxonomy_bulk_actions', array(
			'empty'       => _x( 'Empty', 'Taxonomy Module: Bulk Action', GNETWORK_TEXTDOMAIN ),
			'merge'       => _x( 'Merge', 'Taxonomy Module: Bulk Action', GNETWORK_TEXTDOMAIN ),
			'change_tax'  => _x( 'Change Taxonomy', 'Taxonomy Module: Bulk Action', GNETWORK_TEXTDOMAIN ),
			'format_i18n' => _x( 'Format i18n', 'Taxonomy Module: Bulk Action', GNETWORK_TEXTDOMAIN ),
		), $taxonomy );

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			$actions = array_merge( array(
				'set_parent' => _x( 'Set Parent', 'Taxonomy Module: Bulk Action', GNETWORK_TEXTDOMAIN ),
			), $actions );
		}

		return $actions;
	}

	public function load_edit_tags_php()
	{
		$defaults = array(
			'taxonomy'    => 'post_tag',
			'delete_tags' => FALSE,
			'action'      => FALSE,
			'action2'     => FALSE,
		);

		$data = shortcode_atts( $defaults, $_REQUEST );
		$tax  = get_taxonomy( $data['taxonomy'] );

		if ( ! $tax )
			return;

		if ( ! current_user_can( $tax->cap->manage_terms ) )
			return;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );

		$action = FALSE;
		foreach ( array( 'action', 'action2' ) as $key ) {
			if ( $data[$key] && '-1' != $data[$key] ) {
				$action = $data[$key];
			}
		}

		if ( ! $action )
			return;

		$this->delegate_handling( $action, $data['taxonomy'], $data['delete_tags'] );
	}

	private function delegate_handling( $action, $taxonomy, $term_ids )
	{
		if ( empty( $term_ids ) )
			return;

		foreach ( array_keys( $this->get_actions( $taxonomy ) ) as $key ) {
			if ( 'bulk_'.$key == $action ) {
				check_admin_referer( 'bulk-tags' );
				$callback = apply_filters( 'gnetwork_taxonomy_bulk_callback', array( $this, 'handle_'.$key ), $key, $taxonomy );
				if ( is_callable( $callback ) )
					$results = call_user_func( $callback, $term_ids, $taxonomy );
				break;
			}
		}

		if ( ! isset( $results ) )
			return;

		$referer = wp_get_referer();
		if ( $referer && FALSE !== strpos( $referer, 'edit-tags.php' ) ) {
			$location = $referer;
		} else {
			$location = add_query_arg( 'taxonomy', $taxonomy, 'edit-tags.php' );
		}

		$query = array(
			'message' => $results ? 'gnetwork-taxonomy-updated' : 'gnetwork-taxonomy-error',
		);

		if ( isset( $_REQUEST['post_type'] ) && 'post' != $_REQUEST['post_type'] )
			$query['post_type'] = $_REQUEST['post_type'];

		if ( isset( $_REQUEST['paged'] ) && $_REQUEST['paged'] )
			$query['paged'] = $_REQUEST['paged'];

		self::redirect( add_query_arg( $query, $location ) );
	}

	public function admin_notices()
	{
		if ( ! isset( $_GET['message'] ) )
			return;

		switch ( $_GET['message'] ) {
			case  'gnetwork-taxonomy-updated':

				self::notice( _x( 'Terms updated.', 'Taxonomy Module: Notice', GNETWORK_TEXTDOMAIN ) );

			break;
			case 'gnetwork-taxonomy-error':

				self::notice( _x( 'Terms not updated.', 'Taxonomy Module: Notice', GNETWORK_TEXTDOMAIN ), 'error' );

			break;
		}
	}

	public function handle_empty( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			$posts = get_objects_in_term( (int) $term_id, $taxonomy );

			if ( self::isError( $posts ) )
				continue;

			foreach ( $posts as $post )
				wp_remove_object_terms( $post, (int) $term_id, $taxonomy );
		}

		return TRUE;
	}

	public function handle_format_i18n( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			$term = get_term( $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			$args = array(
				'name'        => apply_filters( 'string_format_i18n', $term->name ),
				'description' => apply_filters( 'html_format_i18n', $term->description ),
			);

			if ( $term->slug == sanitize_title( $term->name ) )
				$args['slug'] = $args['name'];

			wp_update_term( $term_id, $taxonomy, $args );
		}

		return TRUE;
	}

	public function handle_merge( $term_ids, $taxonomy )
	{
		$term_name = $_REQUEST['bulk_to_tag'];

		if ( ! $term = term_exists( $term_name, $taxonomy ) )
			$term = wp_insert_term( $term_name, $taxonomy );

		if ( self::isError( $term ) )
			return FALSE;

		$to_term = $term['term_id'];

		$to_term_obj = get_term( $to_term, $taxonomy );

		foreach ( $term_ids as $term_id ) {

			if ( $term_id == $to_term )
				continue;

			$old_term = get_term( $term_id, $taxonomy );
			$merged   = wp_delete_term( $term_id, $taxonomy, array(
				'default'       => $to_term,
				'force_default' => TRUE,
			) );

			if ( self::isError( $merged ) )
				continue;

			// OLD: 'term_management_tools_term_merged'
			do_action( 'gnetwork_taxonomy_term_merged', $taxonomy, $to_term_obj, $old_term );
		}

		return TRUE;
	}

	public function handle_set_parent( $term_ids, $taxonomy )
	{
		$parent_id = $_REQUEST['parent'];

		foreach ( $term_ids as $term_id ) {
			if ( $term_id == $parent_id )
				continue;

			$ret = wp_update_term( $term_id, $taxonomy, array( 'parent' => $parent_id ) );

			if ( self::isError( $ret ) )
				return FALSE;
		}

		return TRUE;
	}

	public function handle_change_tax( $term_ids, $taxonomy )
	{
		global $wpdb;

		$new_tax = $_POST['new_tax'];

		if ( ! taxonomy_exists( $new_tax ) )
			return FALSE;

		if ( $new_tax == $taxonomy )
			return FALSE;

		$tt_ids = array();
		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id, $taxonomy );

			if ( ! $term || self::isError( $term ) )
				continue;

			if ( $term->parent && ! in_array( $term->parent,$term_ids ) ) {
				$wpdb->update( $wpdb->term_taxonomy,
					array( 'parent' => 0 ),
					array( 'term_taxonomy_id' => $term->term_taxonomy_id )
				);
			}

			$tt_ids[] = $term->term_taxonomy_id;

			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				$child_terms = get_terms( $taxonomy, array(
					'child_of'   => $term_id,
					'hide_empty' => FALSE
				) );
				$tt_ids = array_merge( $tt_ids, wp_list_pluck( $child_terms, 'term_taxonomy_id' ) );
			}
		}
		$tt_ids = implode( ',', array_map( 'absint', $tt_ids ) );

		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->term_taxonomy SET taxonomy = %s WHERE term_taxonomy_id IN ($tt_ids)
		", $new_tax ) );

		if ( is_taxonomy_hierarchical( $taxonomy ) && ! is_taxonomy_hierarchical( $new_tax ) ) {
			$wpdb->query( "UPDATE $wpdb->term_taxonomy SET parent = 0 WHERE term_taxonomy_id IN ($tt_ids)" );
		}

		clean_term_cache( $tt_ids, $taxonomy );
		clean_term_cache( $tt_ids, $new_tax );

		// OLD: 'term_management_tools_term_changed_taxonomy'
		do_action( 'gnetwork_taxonomy_term_changed_taxonomy', $tt_ids, $new_tax, $taxonomy );

		return TRUE;
	}

	public function admin_enqueue_scripts()
	{
		global $taxonomy;

		wp_localize_script( Utilities::enqueueScript( 'admin.taxonomy' ),
			'gNetworkTaxonomy', $this->get_actions( $taxonomy ) );
	}

	public function admin_footer()
	{
		global $taxonomy;

		foreach ( array_keys( $this->get_actions( $taxonomy ) ) as $key ) {
			echo "<div id='gnetwork-taxonomy-input-$key' class='gnetwork-taxonomy-input-wrap' style='display:none'>\n";

				$callback = apply_filters( 'gnetwork_taxonomy_bulk_input', array( $this, 'input_'.$key ), $key, $taxonomy );
				if ( is_callable( $callback ) )
					call_user_func( $callback, $taxonomy );

			echo "</div>\n";
		}
	}

	public function input_merge( $taxonomy )
	{
		printf( _x( 'into: %s', 'Taxonomy Module', GNETWORK_TEXTDOMAIN ),
			'<input name="bulk_to_tag" type="text" size="20"></input>' );
	}

	public function input_change_tax( $taxonomy )
	{
		$args     = current_user_can( 'import' ) ? array() : array( 'show_ui' => TRUE );
		$tax_list = get_taxonomies( $args, 'objects' );

		echo '<select class="postform" name="new_tax">';
		foreach ( $tax_list as $new_tax => $tax_obj ) {
			if ( $new_tax == $taxonomy )
				continue;

			echo "<option value='$new_tax'>$tax_obj->label</option>\n";
		}
		echo '</select>';
	}

	public function input_set_parent( $taxonomy )
	{
		wp_dropdown_categories( array(
			'hide_empty'       => 0,
			'hide_if_empty'    => FALSE,
			'name'             => 'parent',
			'orderby'          => 'name',
			'taxonomy'         => $taxonomy,
			'hierarchical'     => TRUE,
			'show_option_none' => _x( 'None', 'Taxonomy Module: Set Parent None Option', GNETWORK_TEXTDOMAIN )
		) );
	}
}

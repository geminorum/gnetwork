<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Taxonomy extends gNetwork\Module
{

	protected $key     = 'taxonomy';
	protected $network = FALSE;
	protected $front   = FALSE;
	protected $ajax    = TRUE;

	protected $priority_current_screen = 12;

	protected function setup_actions()
	{
		add_filter( 'pre_term_name', 'normalize_whitespace', 9 );
		// add_filter( 'pre_term_description', 'normalize_whitespace', 9 ); // FIXME: drops double \n
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Taxonomy', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'management_tools'   => '1',
			'slug_actions'       => '0',
			'description_editor' => '0',
			'description_column' => '1',
			'search_fields'      => '1',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'management_tools',
					'title'       => _x( 'Management Tools', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Allows you to merge terms, set term parents in bulk, and swap term taxonomies.', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'slug_actions',
					'title'       => _x( 'Slug Actions', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds slug specific actions on the taxonomy management tools.', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'description_editor',
					'title'       => _x( 'Description Editor', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Replaces the term description editor with the WordPress TinyMCE editor.', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'description_column',
					'title'       => _x( 'Description Column', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds description column to term list table and quick edit.', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'search_fields',
					'title'       => _x( 'Search Fields', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Looks for criteria in term descriptions and slugs as well as term names.', 'Modules: Taxonomy: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
			],
		];
	}

	protected function setup_ajax( $request )
	{
		if ( ( $taxnow = empty( $request['taxonomy'] ) ? FALSE : $request['taxonomy'] ) ) {
			$this->action( 'edited_term', 3, 10 );
			add_filter( 'manage_edit-'.$taxnow.'_columns', [ $this, 'manage_edit_columns' ], 5 );
			add_filter( 'manage_'.$taxnow.'_custom_column', [ $this, 'manage_custom_column' ], 10, 3 );
		}
	}

	public function current_screen( $screen )
	{
		if ( 'edit-tags' == $screen->base
			|| 'term' == $screen->base ) {

			add_filter( 'admin_body_class', function( $classes ) {
				return $classes.' gnetowrk-taxonomy';
			} );

			if ( $this->options['description_editor']
				&& current_user_can( 'publish_posts' ) ) {

				// remove the filters which disallow HTML in term descriptions
				remove_filter( 'pre_term_description', 'wp_filter_kses' );
				remove_filter( 'term_description', 'wp_kses_data' );

				// add filters to disallow unsafe HTML tags
				if ( ! current_user_can( 'unfiltered_html' ) ) {
					add_filter( 'pre_term_description', 'wp_kses_post' );
					add_filter( 'term_description', 'wp_kses_post' );
				}

				Utilities::enqueueScript( 'admin.taxonomy.wordcount', [ 'jquery', 'word-count', 'underscore' ] );
			}

			if ( 'edit-tags' == $screen->base ) {

				if ( $this->options['management_tools'] )
					$this->term_management( $screen );

				if ( $this->options['description_editor'] )
					add_action( $screen->taxonomy.'_add_form_fields', [ $this, 'add_form_fields' ], 1, 1 );

				if ( $this->options['description_column'] ) {
					add_filter( 'manage_edit-'.$screen->taxonomy.'_columns', [ $this, 'manage_edit_columns' ], 5 );
					add_filter( 'manage_'.$screen->taxonomy.'_custom_column', [ $this, 'manage_custom_column' ], 10, 3 );
					add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_custom_box' ], 10, 3 );
				}

				if ( $this->options['search_fields'] ) {
					$this->filter( 'get_terms_args', 2 );
					$this->filter( 'terms_clauses', 3 );
				}

			} else if ( 'term' == $screen->base ) {

				if ( $this->options['description_editor'] )
					add_action( $screen->taxonomy.'_edit_form_fields', [ $this, 'edit_form_fields' ], 1, 2 );
			}
		}
	}

	public function manage_edit_columns( $columns )
	{
		$new = [];

		foreach ( $columns as $key => $value ) {

			if ( 'description' == $key )
				$new['gnetwork_description'] = _x( 'Description', 'Modules: Taxonomy: Column', GNETWORK_TEXTDOMAIN );

			else
				$new[$key] = $value;
		}

		return $new;
	}

	public function manage_custom_column( $empty, $column_name, $term_id )
	{
		if ( 'gnetwork_description' !== $column_name )
			return $empty;

		if ( $term = get_term( intval( $term_id ) ) )
			echo sanitize_term_field( 'description', $term->description, $term->term_id, $term->taxonomy, 'display' );
	}

	public function quick_edit_custom_box( $column_name, $screen, $taxonomy )
	{
		if ( 'gnetwork_description' != $column_name )
			return;

		if ( ! current_user_can( get_taxonomy( $taxonomy )->cap->edit_terms ) )
			return;

		echo '<fieldset><div class="inline-edit-col"><label>';
		echo '<span class="title">';
			_ex( 'Description', 'Modules: Taxonomy: Quick Edit Label', GNETWORK_TEXTDOMAIN );
		echo '</span><span class="input-text-wrap">';
			echo '<textarea id="inline-desc" name="gnetwork-description" rows="6" class="ptitle"></textarea>';
		echo '</span></label></div></fieldset>';

		HTML::wrapjQueryReady( '$("#the-list").on("click",".editinline",function(){var now=$(this).closest("tr").find("td.gnetwork_description").text();$("#inline-desc").text(now);});' );
	}

	// WTF: must be `edited_term` not `edit_term`
	public function edited_term( $term_id, $tt_id, $taxonomy )
	{
		remove_action( 'edited_term', [ $this, 'edited_term' ], 10, 3 );

		if ( wp_verify_nonce( @$_REQUEST['_inline_edit'], 'taxinlineeditnonce' ) )
			if ( isset( $_REQUEST['gnetwork-description'] ) )
				wp_update_term( $term_id, $taxonomy, [ 'description' => $_POST['gnetwork-description'] ] );
	}

	public function get_terms_args( $args, $taxonomies )
	{
		if ( ! empty( $args['search'] ) ) {
			$this->terms_search = $args['search'];
			unset( $args['search'] );
		}

		return $args;
	}

	public function terms_clauses( $clauses, $taxonomies, $args )
	{
		if ( ! empty( $this->terms_search ) ) {

			global $wpdb;

			$like = '%'.$wpdb->esc_like( $this->terms_search ).'%';

			$clauses['where'].= $wpdb->prepare( ' AND ((t.name LIKE %s) OR (t.slug LIKE %s) OR (tt.description LIKE %s))', $like, $like, $like );

			$this->terms_search = '';
		}

		return $clauses;
	}

	public function edit_form_fields( $tag, $taxonomy )
	{
		$settings = [
			'textarea_name'  => 'description',
			'textarea_rows'  => 10,
			'default_editor' => 'html',
			'editor_class'   => 'editor-status-counts i18n-multilingual', // qtranslate-x
			'quicktags'      => [ 'buttons' => 'link,em,strong,li,ul,ol,code' ],
			'tinymce'        => [
				'toolbar1' => 'bold,italic,alignleft,aligncenter,alignright,link,undo,redo',
				'toolbar2' => '',
				'toolbar3' => '',
				'toolbar4' => '',
			],
		];

		echo '<tr class="form-field term-description-wrap -wordcount-wrap">';
			echo '<th scope="row" valign="top"><label for="html-tag-description">';
				_ex( 'Description', 'Modules: Taxonomy', GNETWORK_TEXTDOMAIN );
			echo '</label></th><td>';

			wp_editor( htmlspecialchars_decode( $tag->description ), 'html-tag-description', $settings );

			$this->editor_status_info();

			HTML::desc( _x( 'The description is not prominent by default; however, some themes may show it.', 'Modules: Taxonomy', GNETWORK_TEXTDOMAIN ) );
			HTML::wrapScript( 'jQuery("textarea#description").closest(".form-field").remove();' );

		echo '</tr>';
	}

	public function add_form_fields( $taxonomy )
	{
		$settings = [
			'textarea_name'  => 'description',
			'textarea_rows'  => 7,
			'teeny'          => TRUE,
			'media_buttons'  => FALSE,
			'default_editor' => 'html',
			'editor_class'   => 'editor-status-counts i18n-multilingual', // qtranslate-x
			'quicktags'      => [ 'buttons' => 'link,em,strong,li,ul,ol,code' ],
			'tinymce'        => [
				'toolbar1' => 'bold,italic,alignleft,aligncenter,alignright,link,undo,redo',
				'toolbar2' => '',
				'toolbar3' => '',
				'toolbar4' => '',
			],
		];

		echo '<div class="form-field term-description-wrap -wordcount-wrap">';

			echo '<label for="html-tag-description">';
				_ex( 'Description', 'Modules: Taxonomy', GNETWORK_TEXTDOMAIN );
			echo '</label>';

			wp_editor( '', 'html-tag-description', $settings );

			$this->editor_status_info();

			HTML::desc( _x( 'The description is not prominent by default; however, some themes may show it.', 'Modules: Taxonomy', GNETWORK_TEXTDOMAIN ) );

			HTML::wrapScript( 'jQuery("textarea#tag-description").closest(".form-field").remove();' );
			HTML::wrapjQueryReady( '$("#addtag").on("mousedown","#submit",function(){tinyMCE.triggerSave();$(document).bind("ajaxSuccess.gnetwork_add_term",function(){if(tinyMCE.activeEditor){tinyMCE.activeEditor.setContent("");}$(document).unbind("ajaxSuccess.gnetwork_add_term",false);});});' );

		echo '</div>';
	}

	private function editor_status_info( $target = 'html-tag-description' )
	{
		$html = '<div id="description-editor-counts" class="-wordcount hide-if-no-js" data-target="'.$target.'">';
		$html.= sprintf( _x( 'Words: %s', 'Modules: Taxonomy', GNETWORK_TEXTDOMAIN ), '<span class="word-count">'.Number::format( '0' ).'</span>' );
		$html.= ' | ';
		$html.= sprintf( _x( 'Chars: %s', 'Modules: Taxonomy', GNETWORK_TEXTDOMAIN ), '<span class="char-count">'.Number::format( '0' ).'</span>' );
		$html.= '</div>';

		echo HTML::wrap( $html, '-editor-status-info' );
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally adapted from : Term Management Tools by scribu v1.1.4
// @REF: https://github.com/scribu/wp-term-management-tools
// @REF: https://wordpress.org/plugins/term-management-tools/

	private function get_actions( $taxonomy )
	{
		$actions = [];

		if ( is_taxonomy_hierarchical( $taxonomy ) )
			$actions['set_parent'] = _x( 'Set Parent', 'Modules: Taxonomy: Bulk Action', GNETWORK_TEXTDOMAIN );

		$actions['merge']       = _x( 'Merge', 'Modules: Taxonomy: Bulk Action', GNETWORK_TEXTDOMAIN );
		$actions['change_tax']  = _x( 'Change Taxonomy', 'Modules: Taxonomy: Bulk Action', GNETWORK_TEXTDOMAIN );
		$actions['format_i18n'] = _x( 'Format i18n', 'Modules: Taxonomy: Bulk Action', GNETWORK_TEXTDOMAIN );
		$actions['empty_posts'] = _x( 'Empty Posts', 'Modules: Taxonomy: Bulk Action', GNETWORK_TEXTDOMAIN );
		$actions['empty_desc']  = _x( 'Empty Description', 'Modules: Taxonomy: Bulk Action', GNETWORK_TEXTDOMAIN );

		if ( $this->options['slug_actions'] ) {
			$actions['rewrite_slug']  = _x( 'Rewrite Slug', 'Modules: Taxonomy: Bulk Action', GNETWORK_TEXTDOMAIN );
			$actions['downcode_slug'] = _x( 'Transliterate Slug', 'Modules: Taxonomy: Bulk Action', GNETWORK_TEXTDOMAIN );
		}

		return $this->filters( 'bulk_actions', $actions, $taxonomy );
	}

	private function term_management( $screen )
	{
		$data = self::atts( [
			'taxonomy'    => 'post_tag',
			'delete_tags' => FALSE,
			'action'      => FALSE,
			'action2'     => FALSE,
		], $_REQUEST );

		$tax = get_taxonomy( $data['taxonomy'] );

		if ( ! $tax )
			return;

		if ( ! current_user_can( $tax->cap->manage_terms ) )
			return;

		$this->action( 'admin_notices' );
		$this->action( 'admin_enqueue_scripts' );
		$this->action( 'admin_footer' );

		$screen->add_help_tab( [
			'id'      => $this->classs( 'help-bulk-actions' ),
			'title'   => _x( 'Bulk Actions', 'Modules: Taxonomy: Help Tab Title', GNETWORK_TEXTDOMAIN ),
			'content' => '<p>'._x( 'These are bulk actions provided for this taxonomy:', 'Modules: Taxonomy: Help Tab Content', GNETWORK_TEXTDOMAIN )
				.'</p>'.HTML::renderList( $this->get_actions( $data['taxonomy'] ) ),
		] );

		$action = FALSE;

		foreach ( [ 'action', 'action2' ] as $key )
			if ( $data[$key] && '-1' != $data[$key] )
				$action = $data[$key];

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

				$callback = $this->filters( 'bulk_callback', [ $this, 'handle_'.$key ], $key, $taxonomy );

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

		$query = [ 'message' => $results ? 'gnetwork-taxonomy-updated' : 'gnetwork-taxonomy-error' ];

		if ( ! empty( $_REQUEST['post_type'] ) && 'post' != $_REQUEST['post_type'] )
			$query['post_type'] = $_REQUEST['post_type'];

		if ( ! empty( $_REQUEST['paged'] ) )
			$query['paged'] = $_REQUEST['paged'];

		if ( ! empty( $_REQUEST['s'] ) )
			$query['s'] = $_REQUEST['s'];

		WordPress::redirect( add_query_arg( $query, $location ) );
	}

	public function admin_notices()
	{
		if ( ! isset( $_GET['message'] ) )
			return;

		switch ( $_GET['message'] ) {

			case 'gnetwork-taxonomy-updated':

				echo HTML::success( _x( 'Terms updated.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) );

			break;
			case 'gnetwork-taxonomy-error':

				echo HTML::error( _x( 'Terms not updated.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) );
		}
	}

	public function handle_empty_posts( $term_ids, $taxonomy )
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

	public function handle_rewrite_slug( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			$term = get_term( $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			$slug = $this->filters( 'term_rewrite_slug', $term->name, $term, $taxonomy );

			wp_update_term( $term_id, $taxonomy, [ 'slug' => $slug ] );
		}

		return TRUE;
	}

	public function handle_downcode_slug( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			$term = get_term( $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			if ( ! seems_utf8( $term->name ) )
				continue;

			$slug = Utilities::URLifyDownCode( $term->name );

			if ( $slug != $term->slug )
				wp_update_term( $term_id, $taxonomy, [ 'slug' => $slug ] );
		}

		return TRUE;
	}

	public function handle_empty_desc( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			$term = get_term( $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			wp_update_term( $term_id, $taxonomy, [ 'description' => '' ] );
		}

		return TRUE;
	}

	public function handle_format_i18n( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			$term = get_term( $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			$args = [
				'name'        => apply_filters( 'string_format_i18n', $term->name ),
				'description' => apply_filters( 'html_format_i18n', $term->description ),
			];

			if ( $term->slug == sanitize_title( $term->name ) )
				$args['slug'] = $args['name'];

			wp_update_term( $term_id, $taxonomy, $args );
		}

		return TRUE;
	}

	public function handle_merge( $term_ids, $taxonomy )
	{
		$term_name = $_REQUEST['bulk_to_tag'];

		// if it's term id
		if ( is_numeric( $term_name ) )
			$term_name = intval( $term_name );

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
			$old_meta = get_term_meta( $term_id );

			$merged = wp_delete_term( $term_id, $taxonomy, [
				'default'       => $to_term,
				'force_default' => TRUE,
			] );

			if ( ! $merged || self::isError( $merged ) )
				continue;

			foreach ( $old_meta as $meta_key => $meta_value )
				add_term_meta( $to_term, $meta_key, $meta_value, FALSE );

			$this->actions( 'term_merged', $taxonomy, $to_term_obj, $old_term, $old_meta );
		}

		return TRUE;
	}

	public function handle_set_parent( $term_ids, $taxonomy )
	{
		$parent_id = $_REQUEST['parent'];

		foreach ( $term_ids as $term_id ) {
			if ( $term_id == $parent_id )
				continue;

			$ret = wp_update_term( $term_id, $taxonomy, [ 'parent' => $parent_id ] );

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

		$tt_ids = [];

		foreach ( $term_ids as $term_id ) {

			$term = get_term( $term_id, $taxonomy );

			if ( ! $term || self::isError( $term ) )
				continue;

			if ( $term->parent && ! in_array( $term->parent, $term_ids ) ) {
				$wpdb->update( $wpdb->term_taxonomy,
					[ 'parent' => 0 ],
					[ 'term_taxonomy_id' => $term->term_taxonomy_id ]
				);
			}

			$tt_ids[] = $term->term_taxonomy_id;

			if ( is_taxonomy_hierarchical( $taxonomy ) ) {

				$child_terms = get_terms( $taxonomy, [
					'child_of'   => $term_id,
					'hide_empty' => FALSE
				] );

				$tt_ids = array_merge( $tt_ids, wp_list_pluck( $child_terms, 'term_taxonomy_id' ) );
			}
		}

		$tt_ids = implode( ',', array_map( 'absint', $tt_ids ) );

		$wpdb->query( $wpdb->prepare( "
			UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE term_taxonomy_id IN ({$tt_ids})
		", $new_tax ) );

		if ( is_taxonomy_hierarchical( $taxonomy )
			&& ! is_taxonomy_hierarchical( $new_tax ) )
				$wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET parent = 0 WHERE term_taxonomy_id IN ({$tt_ids})" );

		clean_term_cache( $tt_ids, $taxonomy );
		clean_term_cache( $tt_ids, $new_tax );

		$this->actions( 'term_changed_taxonomy', $tt_ids, $new_tax, $taxonomy );

		return TRUE;
	}

	public function admin_enqueue_scripts()
	{
		global $taxonomy;

		wp_localize_script( Utilities::enqueueScript( 'admin.taxonomy.actions' ),
			'gNetworkTaxonomyActions', $this->get_actions( $taxonomy ) );
	}

	public function admin_footer()
	{
		global $taxonomy;

		foreach ( array_keys( $this->get_actions( $taxonomy ) ) as $key ) {
			echo "<div id='gnetwork-taxonomy-input-$key' class='gnetwork-taxonomy-input-wrap' style='display:none'>\n";

				$callback = $this->filters( 'bulk_input', [ $this, 'input_'.$key ], $key, $taxonomy );
				if ( is_callable( $callback ) )
					call_user_func( $callback, $taxonomy );

			echo "</div>\n";
		}
	}

	public function input_merge( $taxonomy )
	{
		printf( _x( 'into: %s', 'Modules: Taxonomy', GNETWORK_TEXTDOMAIN ),
			'<input name="bulk_to_tag" type="text" size="20"></input>' );
	}

	public function input_change_tax( $taxonomy )
	{
		$args = current_user_can( 'import' ) ?  [] : [ 'show_ui' => TRUE ];
		$list = get_taxonomies( $args, 'objects' );

		echo '<select class="postform" name="new_tax">';

		foreach ( $list as $new_tax => $tax_obj ) {

			if ( $new_tax == $taxonomy )
				continue;

			echo "<option value='$new_tax'>$tax_obj->label</option>\n";
		}

		echo '</select>';
	}

	public function input_set_parent( $taxonomy )
	{
		wp_dropdown_categories( [
			'hide_empty'       => 0,
			'hide_if_empty'    => FALSE,
			'name'             => 'parent',
			'orderby'          => 'name',
			'taxonomy'         => $taxonomy,
			'hierarchical'     => TRUE,
			'show_option_none' => Settings::showOptionNone(),
		] );
	}
}

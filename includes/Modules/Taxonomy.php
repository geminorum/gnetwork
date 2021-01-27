<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\Taxonomy as WPTaxonomy;

class Taxonomy extends gNetwork\Module
{

	protected $key     = 'taxonomy';
	protected $network = FALSE;
	protected $front   = FALSE;
	protected $ajax    = TRUE;

	protected $priority_current_screen = 12;

	protected function setup_actions()
	{
		add_filter( 'pre_term_name', function ( $value ) {
			return Text::normalizeWhitespace( $value, FALSE );
		}, 9 );

		add_filter( 'pre_term_description', function ( $value ) {
			return Text::normalizeWhitespace( $value, TRUE );
		}, 9 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Taxonomy', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'management_tools'   => '1',
			'taxonomy_tabs'      => '0',
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
					'title'       => _x( 'Management Tools', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'description' => _x( 'Allows you to merge terms, set term parents in bulk, and swap term taxonomies.', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'taxonomy_tabs',
					'title'       => _x( 'Taxonomy Tabs', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'description' => _x( 'Extends taxonomy default user interface with extra features.', 'Modules: Taxonomy: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'slug_actions',
					'title'       => _x( 'Slug Actions', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'description' => _x( 'Adds slug specific actions on the taxonomy management tools.', 'Modules: Taxonomy: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'description_editor',
					'title'       => _x( 'Description Editor', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'description' => _x( 'Replaces the term description editor with the WordPress TinyMCE editor.', 'Modules: Taxonomy: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'description_column',
					'title'       => _x( 'Description Column', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'description' => _x( 'Adds description column to term list table and quick edit.', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'search_fields',
					'title'       => _x( 'Search Fields', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'description' => _x( 'Looks for criteria in term descriptions and slugs as well as term names.', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'default'     => '1',
				],
			],
		];
	}

	protected function setup_ajax( $request )
	{
		if ( ( $taxnow = empty( $request['taxonomy'] ) ? FALSE : $request['taxonomy'] ) ) {
			$this->action( 'edited_term', 3, 10, 'description' );
			add_filter( 'manage_edit-'.$taxnow.'_columns', [ $this, 'manage_edit_columns' ], 5 );
			add_filter( 'manage_'.$taxnow.'_custom_column', [ $this, 'manage_custom_column' ], 10, 3 );
		}
	}

	public function setup_screen( $screen )
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

				Scripts::enqueueScript( 'admin.taxonomy.wordcount', [ 'jquery', 'word-count', 'underscore' ] );
			}

			if ( $this->options['management_tools'] )
				$this->management_tools( $screen );

			if ( $this->options['taxonomy_tabs'] )
				$this->taxonomy_tabs( $screen );

			if ( 'edit-tags' == $screen->base ) {

				if ( $this->options['description_editor'] )
					add_action( $screen->taxonomy.'_add_form_fields', [ $this, 'add_form_fields_editor' ], 1, 1 );

				if ( $this->options['description_column'] ) {
					add_filter( 'manage_edit-'.$screen->taxonomy.'_columns', [ $this, 'manage_edit_columns' ], 5 );
					add_filter( 'manage_'.$screen->taxonomy.'_custom_column', [ $this, 'manage_custom_column' ], 10, 3 );
					add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_custom_box' ], 10, 3 );
				}

				if ( $this->options['search_fields'] ) {
					$this->filter( 'get_terms_args', 2 );
					$this->filter( 'terms_clauses', 3 );
				}

				add_action( 'after-'.$screen->taxonomy.'-table', [ $this, 'render_info_default_term' ], 12 );

			} else if ( 'term' == $screen->base ) {

				if ( $this->options['description_editor'] )
					add_action( $screen->taxonomy.'_edit_form_fields', [ $this, 'edit_form_fields_editor' ], 1, 2 );
			}
		}
	}

	public function manage_edit_columns( $columns )
	{
		$new = [];

		foreach ( $columns as $key => $value ) {

			if ( 'description' == $key )
				$new['gnetwork_description'] = _x( 'Description', 'Modules: Taxonomy: Column', 'gnetwork' );

			else
				$new[$key] = $value;
		}

		return $new;
	}

	public function manage_custom_column( $empty, $column_name, $term_id )
	{
		if ( 'gnetwork_description' !== $column_name )
			return $empty;

		if ( $term = get_term( (int) $term_id ) )
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
			_ex( 'Description', 'Modules: Taxonomy: Quick Edit Label', 'gnetwork' );
		echo '</span><span class="input-text-wrap">';
			echo '<textarea id="inline-desc" name="gnetwork-description" rows="6" class="ptitle"></textarea>';
		echo '</span></label></div></fieldset>';

		HTML::wrapjQueryReady( '$("#the-list").on("click",".editinline",function(){var now=$(this).closest("tr").find("td.gnetwork_description").text();$("#inline-desc").text(now);});' );
	}

	// WTF: has to be `edited_term` not `edit_term`
	public function edited_term_description( $term_id, $tt_id, $taxonomy )
	{
		if ( ! isset( $_POST['gnetwork-description'] ) )
			return;

		if ( ! wp_verify_nonce( @$_REQUEST['_inline_edit'], 'taxinlineeditnonce' ) )
			return;

		remove_action( 'edited_term', [ $this, 'edited_term_description' ], 10 );

		wp_update_term( $term_id, $taxonomy, [
			'description' => $_POST['gnetwork-description'], // raw POST expected
		] );
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

	private function taxonomy_tabs( $screen )
	{
		if ( 'edit-tags' != $screen->base )
			return FALSE;

		$object = get_taxonomy( $screen->taxonomy );

		if ( ! current_user_can( $object->cap->manage_terms ) )
			return FALSE;

		$this->handle_tab_content_actions( $object->name );

		add_action( $object->name.'_pre_add_form', [ $this, 'edittags_pre_add_form' ], -9999 );
		add_action( $object->name.'_add_form', [ $this, 'edittags_add_form' ], 9999 );

		$this->action_self( 'tab_maintenance_content', 2, 12, 'delete_empties' );

		$this->action_self( 'tab_extra_content', 2, 12, 'default_term' );
		// $this->action_self( 'tab_extra_content', 2, 22, 'terms_stats' ); // FIXME
		// $this->action_self( 'tab_extra_content', 2, 32, 'i18n_reports' ); // FIXME
	}

	private function get_taxonomy_tabs( $taxonomy )
	{
		$tabs = [];

		// $tabs['search'] = [ 'title' => _x( 'Search', 'Modules: Taxonomy: Tab Title', 'gnetwork' ), , 'callback' => NULL ]; // FIXME

		if ( $this->hooked( 'tab_maintenance_content' ) )
			$tabs['maintenance'] = [ 'title' => _x( 'Maintenance', 'Modules: Taxonomy: Tab Title', 'gnetwork' ), 'callback' => NULL ];

		$tabs['tools'] = [ 'title' => _x( 'Tools', 'Modules: Taxonomy: Tab Title', 'gnetwork' ), 'callback' => NULL ];

		if ( $this->hooked( 'tab_extra_content' ) )
			$tabs['extras'] = [ 'title' => _x( 'Extras', 'Modules: Taxonomy: Tab Title', 'gnetwork' ), 'callback' => NULL ];

		return $this->filters( 'tabs', $tabs, $taxonomy );
	}

	// @HOOK: `{$taxonomy}_pre_add_form`
	public function edittags_pre_add_form( $taxonomy )
	{
		$object = get_taxonomy( $taxonomy );
		$tabs   = $this->get_taxonomy_tabs( $taxonomy );

		echo '<div class="base-tabs-list -base nav-tab-base">';

		HTML::tabNav( 'addnew', [ 'addnew' => $object->labels->add_new_item ] + wp_list_pluck( $tabs, 'title' ) );

		echo '<div class="nav-tab-content -content nav-tab-active -active" data-tab="addnew">';
	}

	// @HOOK: `{$taxonomy}_add_form`
	public function edittags_add_form( $taxonomy )
	{
		echo '</form></div></div>';

		$tabs = $this->get_taxonomy_tabs( $taxonomy );

		foreach ( wp_list_pluck( $tabs, 'callback' ) as $tab => $callback ) {

			if ( FALSE === $callback )
				continue;

			if ( is_null( $callback ) )
				$callback = [ $this, 'callback_tab_content_'.$tab ];

			if ( ! is_callable( $callback ) )
				continue;

			echo '<div class="nav-tab-content -content -content-tab-'.$tab.'" data-tab="'.$tab.'">';
				call_user_func_array( $callback, [ $taxonomy, $tab, get_taxonomy( $taxonomy ) ] );
			echo '</div>';
		}

		echo '</div><div><form class="dummy-form">';
	}

	// FIXME: redirect messages wont appear on the edit-tags screen
	private function handle_tab_content_actions( $taxonomy )
	{
		if ( self::req( $this->classs( 'do-default-terms' ) ) ) {

			check_admin_referer( $this->classs( 'do-default-terms' ) );

			$terms    = $this->filters( 'default_terms_'.$taxonomy, [], $taxonomy );
			$selected = self::req( $this->classs( 'do-default-selected' ), [] );
			$data     = $selected ? Arraay::keepByKeys( $terms, array_keys( $selected ) ) : $terms;

			if ( count( $data ) && FALSE !== ( $count = WPTaxonomy::insertDefaultTerms( $taxonomy, $data ) ) )
				WordPress::redirectReferer( [
					'message' => 'imported',
					'count'   => $count,
				] );

			WordPress::redirectReferer( 'wrong' );

		} else if ( self::req( $this->classs( 'do-export-terms' ) ) ) {

			check_admin_referer( $this->classs( 'do-export-terms' ) );

			$fields = self::req( $this->classs( 'do-export-fields' ), [] );
			$data   = $this->get_csv_terms( $taxonomy, ( $fields ? array_keys( $fields ) : NULL ) );

			Text::download( $data, File::prepName( sprintf( '%s.csv', $taxonomy ) ) );

			WordPress::redirectReferer( 'wrong' );

		} else if ( self::req( $this->classs( 'do-delete-terms' ) ) ) {

			check_admin_referer( $this->classs( 'do-delete-terms' ) );

			// no need, we check the nounce
			// if ( ! current_user_can( get_taxonomy( $taxonomy )->cap->delete_terms ) )
			// 	WordPress::redirectReferer( 'noaccess' );

			if ( $taxonomy !== self::req( $this->classs( 'do-delete-confirm' ) ) )
				WordPress::redirectReferer( 'huh' );

			else
				$count = $this->handle_delete_terms( $taxonomy, FALSE, FALSE );

			WordPress::redirectReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );

		} else if ( self::req( $this->classs( 'do-delete-empties' ) ) ) {

			check_admin_referer( $this->classs( 'do-delete-empties' ) );

			$count = $this->handle_delete_terms( $taxonomy, TRUE, FALSE );

			WordPress::redirectReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );
		}
	}

	// FIXME: we cannot rely on `count` data from the database
	private function handle_delete_terms( $taxonomy, $empty = TRUE, $include_default = FALSE )
	{
		$count = 0;
		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => FALSE,
			'exclude'    => $include_default ? '' : get_option( $this->get_default_term_key( $taxonomy ), '' ),

			'update_term_meta_cache' => FALSE,
		] );

		foreach ( $terms as $term ) {

			if ( $empty && $term->count )
				continue;

			// MAYBE: check `delete_term` for each term
			// @SEE: https://wp.me/p2AvED-5kA

			$deleted = wp_delete_term( $term->term_id, $term->taxonomy );

			if ( $deleted && ! is_wp_error( $deleted ) )
				$count++;
		}

		return $count;
	}

	// TODO: ajax search
	// TODO: suggestion: misspelled
	// TODO: suggestion: i18n variations
	public function callback_tab_content_search( $taxonomy, $tab, $object )
	{
		$this->actions( 'tab_search_content_before', $taxonomy, $object );

		echo $this->wrap_open( '-tab-tools-search' );
			HTML::desc( gNetwork()->na() ); // FIXME
		echo '</div>';

		$this->actions( 'tab_search_content', $taxonomy, $object );
	}

	// TODO: delete empty terms
	// TODO: delete terms with single post
	public function callback_tab_content_tools( $taxonomy, $tab, $object )
	{
		$this->actions( 'tab_tools_content_before', $taxonomy, $object );

		$this->_tab_content_tools_defaults( $taxonomy, $object );
		// $this->_tab_content_tools_import( $taxonomy, $object ); // FIXME
		$this->_tab_content_tools_export( $taxonomy, $object );
		$this->_tab_content_tools_delete( $taxonomy, $object );

		$this->actions( 'tab_tools_content', $taxonomy, $object );
	}

	// TODO: indicate that default terms may already installed
	private function _tab_content_tools_defaults( $taxonomy, $object )
	{
		if ( ! current_user_can( $object->cap->edit_terms ) )
			return FALSE;

		echo $this->wrap_open( '-tab-tools-defaults card -toolbox-card' );
			HTML::h4( _x( 'Default Terms', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$hook = 'default_terms_'.$taxonomy;

			if ( $this->hooked( $hook ) ) {

				$this->render_form_start( NULL, 'defaults', 'install', 'tabs', FALSE );
					wp_nonce_field( $this->classs( 'do-default-terms' ) );

					echo HTML::multiSelect( $this->filters( $hook, [], $taxonomy ), [
						'name'     => $this->classs( 'do-default-selected' ),
						'selected' => TRUE,
						'panel'    => TRUE,
						'values'   => TRUE,
					] );

					HTML::desc( _x( 'Select to install pre-configured terms for this taxonomy.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ) );

					echo $this->wrap_open_buttons( '-toolbox-buttons' );
						Settings::submitButton( $this->classs( 'do-default-terms' ), _x( 'Install Defaults', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-primary' );
					echo '</p>';

				$this->render_form_end( NULL, 'defaults', 'install', 'tabs' );

			} else {

				HTML::desc( gNetwork()->na() );
			}

		echo '</div>';
	}

	private function _tab_content_tools_import( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-tools-import card -toolbox-card' );
			HTML::h4( _x( 'Import Terms', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			HTML::desc( gNetwork()->na() ); // FIXME

		echo '</div>';
	}

	private function _tab_content_tools_export( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-tools-export card -toolbox-card' );
			HTML::h4( _x( 'Export Terms', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$this->render_form_start( NULL, 'export', 'download', 'tabs', FALSE );
				wp_nonce_field( $this->classs( 'do-export-terms' ) );

				echo HTML::multiSelect( $this->get_export_term_fields( $taxonomy ), [
					'name'     => $this->classs( 'do-export-fields' ),
					'selected' => TRUE,
					'panel'    => TRUE,
					'values'   => TRUE,
				] );

				HTML::desc( _x( 'Select fields to include on the the exported CSV file.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ) );

				echo $this->wrap_open_buttons( '-toolbox-buttons' );
					Settings::submitButton( $this->classs( 'do-export-terms' ), _x( 'Export in CSV', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-primary' );
				echo '</p>';

			$this->render_form_end( NULL, 'export', 'download', 'tabs' );
		echo '</div>';
	}

	private function _tab_content_tools_delete( $taxonomy, $object )
	{
		if ( ! current_user_can( $object->cap->delete_terms ) )
			return FALSE;

		echo $this->wrap_open( '-tab-tools-delete card -toolbox-card' );
			HTML::h4( _x( 'Delete Terms', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$this->render_form_start( NULL, 'delete', 'bulk', 'tabs', FALSE );
				wp_nonce_field( $this->classs( 'do-delete-terms' ) );

				echo HTML::tag( 'input', [
					'type'         => 'text',
					'name'         => $this->classs( 'do-delete-confirm' ),
					'placeholder'  => $taxonomy,
					'autocomplete' => 'off',
					'class'        => [ 'regular-text', 'code' ],
					'dir'          => 'ltr',
				] );

				HTML::desc( _x( 'Confirm deletion of all terms by entering the taxonomy name.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ) );

				if ( $default = get_option( $this->get_default_term_key( $taxonomy ) ) ) {

					$term = get_term( $default, $taxonomy );

					if ( $term && ! self::isError( $term ) )
						/* translators: %s: default term name */
						HTML::desc( sprintf( _x( 'The Default term for this taxonomy is &ldquo;%s&rdquo; and will <b>not</b> be deleted.', 'Modules: Taxonomy: Info', 'gnetwork' ), '<i>'.$term->name.'</i>' ) );
				}

				echo $this->wrap_open_buttons( '-toolbox-buttons' );
					Settings::submitButton( $this->classs( 'do-delete-terms' ), _x( 'Delete All Terms', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-danger', TRUE );
				echo '</p>';

			$this->render_form_end( NULL, 'delete', 'bulk', 'tabs' );
		echo '</div>';
	}

	// TODO: card: apply i18n on all titles
	// TODO: card: merge i18n same titles
	public function callback_tab_content_maintenance( $taxonomy, $tab, $object )
	{
		$this->actions( 'tab_maintenance_content', $taxonomy, $object );
	}

	public function tab_maintenance_content_delete_empties( $taxonomy, $object )
	{
		if ( ! current_user_can( $object->cap->delete_terms ) )
			return FALSE;

		echo $this->wrap_open( '-tab-tools-delete-empties card -toolbox-card' );
			HTML::h4( _x( 'Delete Empties', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$empties = wp_count_terms( $taxonomy ) - wp_count_terms( $taxonomy, [ 'hide_empty' => TRUE ] );

			if ( $empties ) {

				$this->render_form_start( NULL, 'delete', 'empties', 'tabs', FALSE );
					wp_nonce_field( $this->classs( 'do-delete-empties' ) );

					/* translators: %s: number of empty terms */
					HTML::desc( Utilities::getCounted( $empties, _nx( 'Confirm deletion of %s empty term.', 'Confirm deletion of %s empty terms.', $empties, 'Modules: Taxonomy: Tab Tools', 'gnetwork' ) ) );

					echo $this->wrap_open_buttons( '-toolbox-buttons' );
						Settings::submitButton( $this->classs( 'do-delete-empties' ), _x( 'Delete Empty Terms', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-danger', TRUE );
					echo '</p>';

				$this->render_form_end( NULL, 'delete', 'empties', 'tabs' );

			} else {

				HTML::desc( _x( 'There are no empty terms in this taxonomy.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ) );
			}

		echo '</div>';
	}

	public function callback_tab_content_extras( $taxonomy, $tab, $object )
	{
		$this->actions( 'tab_extra_content', $taxonomy, $object );
	}

	public function tab_extra_content_i18n_reports( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-extras-i18n-reports card -toolbox-card' );
			HTML::h4( _x( 'i18n Reports', 'Modules: Taxonomy: Tab Extra', 'gnetwork' ), 'title' );

			HTML::desc( gNetwork()->na() ); // FIXME

		echo '</div>';
	}

	// TODO: count by meta fields
	public function tab_extra_content_terms_stats( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-extras-terms-stats card -toolbox-card' );
			HTML::h4( _x( 'Terms Stats', 'Modules: Taxonomy: Tab Extra', 'gnetwork' ), 'title' );

			HTML::desc( '<code>'.wp_count_terms( $taxonomy ).'</code>' );

		echo '</div>';
	}

	// FIXME: maybe move to `maintenance` tab
	// FIXME: must be link button to edit the default term
	// FIXME: unset default term button
	public function tab_extra_content_default_term( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-extras-default-term card -toolbox-card' );
			HTML::h4( _x( 'Default Term', 'Modules: Taxonomy: Tab Extra', 'gnetwork' ), 'title' );

			if ( ! $this->render_info_default_term( $taxonomy ) )
				HTML::desc( gNetwork()->na() );

		echo '</div>';
	}

	// ACTION HOOK: `after_{$taxonomy}_table`
	public function render_info_default_term( $taxonomy )
	{
		$default = get_option( $this->get_default_term_key( $taxonomy ) );

		if ( empty( $default ) )
			return;

		$term = get_term( $default, $taxonomy );

		if ( ! $term || self::isError( $term ) )
			return;

		/* translators: %s: default term name */
		HTML::desc( sprintf( _x( 'The Default term for this taxonomy is &ldquo;%s&rdquo;.', 'Modules: Taxonomy: Info', 'gnetwork' ), '<i>'.$term->name.'</i>' ) );

		return TRUE;
	}

	private function get_default_term_key( $taxonomy )
	{
		if ( 'category' == $taxonomy )
			return 'default_category'; // WordPress

		if ( 'product_cat' == $taxonomy )
			return 'default_product_cat'; // WooCommerce

		return 'default_term_'.$taxonomy;
	}

	public function edit_form_fields_editor( $tag, $taxonomy )
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
				_ex( 'Description', 'Modules: Taxonomy', 'gnetwork' );
			echo '</label></th><td>';

			wp_editor( htmlspecialchars_decode( $tag->description ), 'html-tag-description', $settings );

			$this->editor_status_info();

			HTML::desc( _x( 'The description is not prominent by default; however, some themes may show it.', 'Modules: Taxonomy', 'gnetwork' ) );
			HTML::wrapScript( 'jQuery("textarea#description").closest(".form-field").remove();' );

		echo '</tr>';
	}

	public function add_form_fields_editor( $taxonomy )
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
				_ex( 'Description', 'Modules: Taxonomy', 'gnetwork' );
			echo '</label>';

			wp_editor( '', 'html-tag-description', $settings );

			$this->editor_status_info();

			HTML::desc( _x( 'The description is not prominent by default; however, some themes may show it.', 'Modules: Taxonomy', 'gnetwork' ) );

			HTML::wrapScript( 'jQuery("textarea#tag-description").closest(".form-field").remove();' );
			HTML::wrapjQueryReady( '$("#addtag").on("mousedown","#submit",function(){tinyMCE.triggerSave();$(document).on("ajaxSuccess.gnetwork_add_term",function(){if(tinyMCE.activeEditor){tinyMCE.activeEditor.setContent("");}$(document).unbind("ajaxSuccess.gnetwork_add_term",false);});});' );

		echo '</div>';
	}

	private function editor_status_info( $target = 'html-tag-description' )
	{
		$html = '<div id="description-editor-counts" class="-wordcount hide-if-no-js" data-target="'.$target.'">';
		/* translators: %s: number of words */
		$html.= sprintf( _x( 'Words: %s', 'Modules: Taxonomy', 'gnetwork' ), '<span class="word-count">'.Number::format( '0' ).'</span>' );
		$html.= ' | ';
		/* translators: %s: number of chars */
		$html.= sprintf( _x( 'Chars: %s', 'Modules: Taxonomy', 'gnetwork' ), '<span class="char-count">'.Number::format( '0' ).'</span>' );
		$html.= '</div>';

		echo HTML::wrap( $html, '-editor-status-info' );
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally adapted from : Term Management Tools by scribu v1.1.4
// @REF: https://github.com/scribu/wp-term-management-tools
// @REF: https://wordpress.org/plugins/term-management-tools/

	private function management_tools( $screen )
	{
		if ( 'term' == $screen->base ) {

			$excludes = [
				'set_parent',
				'empty_desc',
				'merge',
			];

			$default = get_option( $this->get_default_term_key( $screen->taxonomy ) );

			if ( ! empty( $default ) && ( $current = self::req( 'tag_ID' ) ) )
				$excludes[] = $default == $current ? 'set_default' : 'unset_default';

		} else {

			$excludes = [
				'set_default',
				'unset_default',
			];
		}

		$actions = Arraay::stripByKeys( $this->get_actions( $screen->taxonomy ), $excludes );

		if ( ! count( $actions ) )
			return FALSE;

		if ( 'edit-tags' == $screen->base ) {

			if ( ! WordPress::cucTaxonomy( $screen->taxonomy, 'manage_terms' ) )
				return FALSE;

			add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'handle_bulk_actions' ], 10, 3 );

			$intro = _x( 'These are extra bulk actions available for this taxonomy:', 'Modules: Taxonomy: Help Tab Content', 'gnetwork' );

		} else {

			add_action( $screen->taxonomy.'_edit_form_fields', [ $this, 'edit_form_fields_default' ], 9, 2 );
			add_action( $screen->taxonomy.'_edit_form_fields', [ $this, 'edit_form_fields_actions' ], 99, 2 );

			$intro = _x( 'These are extra actions available for this term:', 'Modules: Taxonomy: Help Tab Content', 'gnetwork' );
		}

		$this->action( 'edited_term', 3, 12, 'actions' ); // fires on edit-tags.php
		$this->action( 'admin_notices' );
		$this->action( 'admin_footer' );

		// TODO: add help tab for supported posttypes

		$screen->add_help_tab( [
			'id'      => $this->classs( 'help-bulk-actions' ),
			'title'   => _x( 'Extra Actions', 'Modules: Taxonomy: Help Tab Title', 'gnetwork' ),
			'content' => '<p>'.$intro.'</p>'.HTML::renderList( $actions ),
		] );

		wp_localize_script( Scripts::enqueueScript( 'admin.taxonomy.actions' ), 'gNetworkTaxonomyActions', $actions );

		return TRUE;
	}

	private function get_actions( $taxonomy )
	{
		static $filtered = [];

		if ( empty( $taxonomy ) )
			return [];

		if ( isset( $filtered[$taxonomy] ) )
			return $filtered[$taxonomy];

		$actions = [];

		$actions['set_default']   = _x( 'Set Default', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['unset_default'] = _x( 'Unset Default', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );

		if ( is_taxonomy_hierarchical( $taxonomy ) )
			$actions['set_parent'] = _x( 'Set Parent', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );

		$actions['merge']          = _x( 'Merge', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['split']          = _x( 'Split', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['change_tax']     = _x( 'Change Taxonomy', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['format_i18n']    = _x( 'Format i18n', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['assign_parents'] = _x( 'Assign Parents', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['empty_posts']    = _x( 'Empty Posts', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['empty_desc']     = _x( 'Empty Description', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );

		if ( $this->options['slug_actions'] ) {
			$actions['rewrite_slug']  = _x( 'Rewrite Slug', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
			$actions['downcode_slug'] = _x( 'Transliterate Slug', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		}

		$filtered[$taxonomy] = $this->filters( 'bulk_actions', $actions, $taxonomy );

		return $filtered[$taxonomy];
	}

	public function edit_form_fields_default( $term, $taxonomy )
	{
		$default = get_option( $this->get_default_term_key( $taxonomy ) );

		if ( empty( $default ) )
			return;

		if ( $term->term_id != $default )
			return;

		$object = get_taxonomy( $taxonomy );

		echo '<tr class="form-field term-info-wrap">';
			echo '<th scope="row" valign="top">';
				_ex( 'Caution', 'Modules: Taxonomy', 'gnetwork' );
			echo '</th><td>';

			/* translators: %s: taxonomy label */
			HTML::desc( sprintf( _x( 'This is the default term for &ldquo;%s&rdquo; taxonomy.', 'Modules: Taxonomy: Info', 'gnetwork' ), '<strong>'.$object->label.'</strong>' ) );
		echo '</tr>';
	}

	public function edit_form_fields_actions( $tag, $taxonomy )
	{
		echo '<tr class="form-field term-actions-wrap actions">';
			echo '<th scope="row" valign="top"><label for="extra-action-selector">';
				_ex( 'Extra Actions', 'Modules: Taxonomy', 'gnetwork' );
			echo '</label></th><td>';

			echo '<select name="'.$this->classs( 'action' ).'" id="extra-action-selector">';
				echo '<option value="-1">'._x( '&ndash; Select Action &ndash;', 'Modules: Taxonomy', 'gnetwork' )."</option>\n";
			echo "</select>\n";

		echo '</tr>';
	}

	public function edited_term_actions( $term_id, $tt_id, $taxonomy )
	{
		$name = $this->classs( 'action' );

		if ( ! isset( $_POST[$name] ) )
			return;

		remove_action( 'edited_term', [ $this, 'edited_term_actions' ], 12 );

		$results = $this->delegate_handling( $_POST[$name], $taxonomy, [ $term_id ] );

		if ( is_null( $results ) )
			return; // default redirect

		$query = [
			'tag_ID'          => $term_id,
			'taxonomy'        => 'extra-change_tax' == $_POST[$name] ? $_POST['new_tax'] : $taxonomy,
			'message'         => $this->classs( $results ? 'updated' : 'error' ),
			'post_type'       => self::req( 'post_type', FALSE ),
			'wp_http_referer' => FALSE,
		];

		if ( 'post' == $query['post_type'] )
			unset( $query['post_type'] );

		WordPress::redirect( add_query_arg( $query, wp_get_referer() ) );
	}

	public function handle_bulk_actions( $location, $action, $term_ids )
	{
		if ( empty( $GLOBALS['taxonomy'] ) )
			return $location;

		$results = $this->delegate_handling( $action, $GLOBALS['taxonomy'], $term_ids );

		if ( is_null( $results ) )
			return $location;

		$query = [
			'taxonomy'  => $GLOBALS['taxonomy'],
			'message'   => $this->classs( $results ? 'updated' : 'error' ),
			'post_type' => self::req( 'post_type', FALSE ),
			'paged'     => self::req( 'paged', FALSE ),
			's'         => self::req( 's', FALSE ),
		];

		if ( 'post' == $query['post_type'] )
			unset( $query['post_type'] );

		if ( '1' == $query['paged'] )
			unset( $query['paged'] );

		return add_query_arg( $query, $location ?: 'edit-tags.php' );
	}

	private function delegate_handling( $action, $taxonomy, $term_ids, $actions = NULL )
	{
		if ( is_null( $actions ) )
			$actions = $this->get_actions( $taxonomy );

		foreach ( array_keys( $actions ) as $key ) {

			if ( 'extra-'.$key == $action ) {

				$callback = $this->filters( 'bulk_callback', [ $this, 'handle_'.$key ], $key, $taxonomy );

				if ( $callback && is_callable( $callback ) )
					return call_user_func( $callback, $term_ids, $taxonomy, $key );
			}
		}

		return NULL;
	}

	public function admin_notices()
	{
		if ( ! isset( $_GET['message'] ) )
			return;

		switch ( $_GET['message'] ) {

			case 'gnetwork-taxonomy-updated':

				echo HTML::success( _x( 'Terms updated.', 'Settings: Message', 'gnetwork' ) );

			break;
			case 'gnetwork-taxonomy-error':

				echo HTML::error( _x( 'Terms not updated.', 'Settings: Message', 'gnetwork' ) );
		}
	}

	public function handle_assign_parents( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			if ( ! $parents = WPTaxonomy::getTermParents( $term_id, $taxonomy ) )
				continue;

			$posts = get_objects_in_term( (int) $term_id, $taxonomy );

			if ( self::isError( $posts ) )
				continue;

			foreach ( $posts as $post )
				wp_set_object_terms( $post, $parents, $taxonomy, TRUE );
		}

		return TRUE;
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

	// separeted because we have to keep the connected object list
	public function handle_multiple_merge( $targets, $term_ids, $taxonomy )
	{
		global $wpdb;

		if ( empty( $targets ) )
			return FALSE;

		if ( ! is_array( $targets ) )
			$targets = array_filter( array_map( 'trim', explode( ',,', $targets ) ) );

		$new_terms = [];

		foreach ( $targets as $target )
			if ( $new_term = WPTaxonomy::getTargetTerm( $target, $taxonomy ) )
				$new_terms[$new_term->term_id] = $new_term;

		if ( ! count( $new_terms ) )
			return FALSE;

		foreach ( $term_ids as $term_id ) {

			if ( array_key_exists( $term_id, $new_terms ) )
				continue;

			$old_term = get_term( $term_id, $taxonomy );
			$old_meta = get_term_meta( $term_id );

			$old_objects = (array) $wpdb->get_col( $wpdb->prepare( "
				SELECT object_id FROM {$wpdb->term_relationships}
				WHERE term_taxonomy_id = %d
			", $old_term->term_taxonomy_id ) );

			foreach ( $new_terms as $new_term_id => $new_term ) {

				// needs to be set before our action fired
				foreach ( $old_objects as $old_object )
					wp_set_object_terms( $old_object, (int) $new_term_id, $taxonomy, TRUE );

				foreach ( $old_meta as $meta_key => $meta_value )
					foreach ( $meta_value as $value_value ) // multiple meta
						add_term_meta( $new_term_id, $meta_key, $value_value, FALSE );

				$this->actions( 'term_merged', $taxonomy, $new_term, $old_term, $old_meta );
			}

			// late delete to avoid losing relation data!
			$deleted = wp_delete_term( $term_id, $taxonomy );

			if ( ! $deleted || self::isError( $deleted ) )
				return FALSE; // bail if something's wrong!
		}

		return TRUE;
	}

	public function handle_merge( $term_ids, $taxonomy )
	{
		$target = $_REQUEST['bulk_to_tag'];

		// handle multiple merge
		if ( Text::has( $target, ',,' ) )
			return $this->handle_multiple_merge( $target, $term_ids, $taxonomy );

		if ( ! $new_term = WPTaxonomy::getTargetTerm( $target, $taxonomy ) )
			return FALSE;

		foreach ( $term_ids as $term_id ) {

			if ( $term_id == $new_term->term_id )
				continue;

			$old_term = get_term( $term_id, $taxonomy );
			$old_meta = get_term_meta( $term_id );

			$merged = wp_delete_term( $term_id, $taxonomy, [
				'default'       => $new_term->term_id,
				'force_default' => TRUE,
			] );

			if ( ! $merged || self::isError( $merged ) )
				continue;

			foreach ( $old_meta as $meta_key => $meta_value )
				foreach ( $meta_value as $value_value ) // multiple meta
					add_term_meta( $new_term->term_id, $meta_key, $value_value, FALSE );

			$this->actions( 'term_merged', $taxonomy, $new_term, $old_term, $old_meta );
		}

		return TRUE;
	}

	public function handle_split( $term_ids, $taxonomy )
	{
		global $wpdb;

		$delimiter = $_REQUEST['bulk_to_split'];

		foreach ( $term_ids as $term_id ) {

			$old_term = get_term( $term_id, $taxonomy );
			$targets  = Utilities::getSeparated( $old_term->name, $delimiter ?: NULL );

			if ( count( $targets ) < 2 )
				continue;

			$old_meta    = get_term_meta( $term_id );
			$old_objects = (array) $wpdb->get_col( $wpdb->prepare( "
				SELECT object_id FROM {$wpdb->term_relationships}
				WHERE term_taxonomy_id = %d
			", $old_term->term_taxonomy_id ) );

			foreach ( $targets as $target ) {

				if ( ! $new_term = WPTaxonomy::getTargetTerm( $target, $taxonomy ) )
					continue;

				// needs to be set before our action fired
				foreach ( $old_objects as $old_object )
					wp_set_object_terms( $old_object, $new_term->term_id, $taxonomy, TRUE );

				foreach ( $old_meta as $meta_key => $meta_value )
					foreach ( $meta_value as $value_value ) // multiple meta
						add_term_meta( $new_term_id, $meta_key, $value_value, FALSE );

				$this->actions( 'term_merged', $taxonomy, $new_term, $old_term, $old_meta );
			}

			// late delete to avoid losing relation data!
			$deleted = wp_delete_term( $term_id, $taxonomy );

			if ( ! $deleted || self::isError( $deleted ) )
				return FALSE; // bail if something's wrong!
		}

		return TRUE;
	}

	public function handle_set_default( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			update_option( $this->get_default_term_key( $taxonomy ), (int) $term_id );

			break; // only one can be default!
		}

		return TRUE;
	}

	public function handle_unset_default( $term_ids, $taxonomy )
	{
		return delete_option( $this->get_default_term_key( $taxonomy ) );
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

				$child_terms = get_terms( [
					'taxonomy'   => $taxonomy,
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

	public function admin_footer()
	{
		if ( ! empty( $GLOBALS['taxonomy'] ) )
			$this->render_secondary_inputs( $GLOBALS['taxonomy'], $this->get_actions( $GLOBALS['taxonomy'] ) );
	}

	private function render_secondary_inputs( $taxonomy, $actions )
	{
		foreach ( array_keys( $actions ) as $key ) {

			$callback = $this->filters( 'bulk_input', [ $this, 'secondary_input_'.$key ], $key, $taxonomy );

			if ( $callback && is_callable( $callback ) ) {

				echo "<div id='gnetwork-taxonomy-input-$key' class='gnetwork-taxonomy-input-wrap' style='display:none'>\n";

					call_user_func_array( $callback, [ $taxonomy ] );

				echo "</div>\n";
			}
		}
	}

	private function secondary_input_merge( $taxonomy )
	{
		/* translators: %s: merge/split into input */
		printf( _x( 'into: %s', 'Modules: Taxonomy', 'gnetwork' ),
			'<input name="bulk_to_tag" type="text" placeholder="'
			._x( 'Name, Slug or ID', 'Modules: Taxonomy', 'gnetwork' ).'" />' );
	}

	private function secondary_input_split( $taxonomy )
	{
		/* translators: %s: merge/split into input */
		printf( _x( 'into: %s', 'Modules: Taxonomy', 'gnetwork' ),
			'<input name="bulk_to_split" type="text" placeholder="'
			._x( 'Delimiter', 'Modules: Taxonomy', 'gnetwork' ).'" />' );
	}

	private function secondary_input_change_tax( $taxonomy )
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

	private function secondary_input_set_parent( $taxonomy )
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

	private function get_export_term_fields( $taxonomy )
	{
		return $this->filters( 'export_term_fields', [
			'parent'      => _x( 'Parent', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'name'        => _x( 'Name', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'slug'        => _x( 'Slug', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'description' => _x( 'Description', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'count'       => _x( 'Count', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
		], $taxonomy );
	}

	private function get_export_term_meta( $taxonomy )
	{
		return $this->filters( 'export_term_meta', [
			// 'example' => _x( 'Example', 'Modules: Taxonomy: Term Meta', 'gnetwork' ),
		], $taxonomy );
	}

	// FIXME: deal with line-breaks on descrioptions
	private function get_csv_terms( $taxonomy, $fields = NULL, $metas = NULL )
	{
		global $wpdb;

		if ( is_null( $fields ) )
			$fields = array_keys( $this->get_export_term_fields( $taxonomy ) );

		if ( is_null( $metas ) )
			$metas = array_keys( $this->get_export_term_meta( $taxonomy ) );

		$terms = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->term_taxonomy}
			INNER JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
			WHERE {$wpdb->term_taxonomy}.taxonomy = %s
			ORDER BY {$wpdb->terms}.term_id ASC
		", $taxonomy ) );

		// FIXME: handle empty results

		$data = [ array_merge( [ 'term_id' ], $fields, Arraay::prefixValues( $metas, 'meta_' ) ) ];

		foreach ( $terms as $term ) {
			$row = [ $term->term_id ];

			foreach ( $fields as $field )
				$row[] = trim( $term->{$field} );

			$meta = get_term_meta( $term->term_id );

			foreach ( $metas as $saved )
				$row[] = empty( $meta[$saved][0] ) ? '' : trim( $meta[$saved][0] );

			$data[] = $row;
		}

		return Text::toCSV( $data );
	}
}

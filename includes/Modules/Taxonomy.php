<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Taxonomy extends gNetwork\Module
{
	protected $key     = 'taxonomy';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected $priority_current_screen = 12;

	private $_terms_search = '';

	protected function setup_actions()
	{
		$this->filter_append( 'query_vars', 't' );
		$this->action( 'init', 0, 99, 'redirect_terms' );
		$this->action( 'template_redirect', 0, 99, 'redirect_terms' );
		$this->filter( 'tag_row_actions', 2, 99, 'redirect_terms' );

		add_filter( 'pre_term_name', static function ( $value ) {
			return Core\Text::normalizeWhitespace( $value, FALSE );
		}, 9 );

		add_filter( 'pre_term_description', static function ( $value ) {
			return Core\Text::normalizeWhitespace( $value, TRUE );
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
			'taxonomy_tabs'      => '1',
			'term_tabs'          => '0',
			'slug_actions'       => '0',
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
					'default'     => '1',
				],
				[
					'field'       => 'term_tabs',
					'title'       => _x( 'Term Tabs', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'description' => _x( 'Extends term default user interface with extra features.', 'Modules: Taxonomy: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'slug_actions',
					'title'       => _x( 'Slug Actions', 'Modules: Taxonomy: Settings', 'gnetwork' ),
					'description' => _x( 'Adds slug specific actions on the taxonomy management tools.', 'Modules: Taxonomy: Settings', 'gnetwork' ),
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

			$this->filter_append_string( 'admin_body_class', $this->classs() );

			if ( $this->options['management_tools'] )
				$this->management_tools( $screen );

			if ( $this->options['taxonomy_tabs'] )
				$this->taxonomy_tabs( $screen );

			if ( 'edit-tags' == $screen->base ) {

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

				if ( $this->options['term_tabs'] )
					$this->term_tabs( $screen );
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

	public function manage_custom_column( $string, $column_name, $term_id )
	{
		if ( 'gnetwork_description' !== $column_name )
			return $string;

		if ( ! $term = get_term( (int) $term_id ) )
			return $string;

		echo sanitize_term_field( 'description', $term->description, $term->term_id, $term->taxonomy, 'display' );
		echo '<div class="hidden">'.$term->description.'</div>';
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

		$script = <<<JS
(function($) {
	$("#the-list").on("click",".editinline",function(){
		$("#inline-desc").html($(this).closest("tr").find("td.gnetwork_description .hidden").html()); // `.text()`
	});
})(jQuery);
JS;
		Core\HTML::wrapjQueryReady( $script );
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
			$this->_terms_search = $args['search'];
			unset( $args['search'] );
		}

		return $args;
	}

	public function terms_clauses( $clauses, $taxonomies, $args )
	{
		if ( ! empty( $this->_terms_search ) ) {

			global $wpdb;

			$like = '%'.$wpdb->esc_like( $this->_terms_search ).'%';

			$clauses['where'].= $wpdb->prepare( ' AND ((t.name LIKE %s) OR (t.slug LIKE %s) OR (tt.description LIKE %s))', $like, $like, $like );

			$this->_terms_search = '';
		}

		return $clauses;
	}

	private function term_tabs( $screen )
	{
		if ( 'term' != $screen->base )
			return FALSE;

		$object = get_taxonomy( $screen->taxonomy );

		add_action( $object->name.'_term_edit_form_top', [ $this, 'term_edit_form_top' ], 1, 2 );
		add_action( $object->name.'_edit_form', [ $this, 'term_edit_form' ], 99, 2 );
	}

	private function get_term_tabs( $taxonomy, $term )
	{
		$tabs = [];

		if ( $this->hooked( 'term_tab_maintenance_content' ) )
			$tabs['maintenance'] = [ 'title' => _x( 'Maintenance', 'Modules: Taxonomy: Term Tab Title', 'gnetwork' ), 'callback' => NULL ];

		$tabs['metadata'] = [ 'title' => _x( 'Meta-data', 'Modules: Taxonomy: Term Tab Title', 'gnetwork' ), 'callback' => NULL ];
		$tabs['posts']    = [ 'title' => _x( 'Posts', 'Modules: Taxonomy: Term Tab Title', 'gnetwork' ), 'callback' => NULL ];
		$tabs['search'] = [ 'title' => _x( 'Search', 'Modules: Taxonomy: Term Tab Title', 'gnetwork' ), 'callback' => NULL ];

		if ( $this->hooked( 'term_tab_tools_content' ) )
			$tabs['tools'] = [ 'title' => _x( 'Tools', 'Modules: Taxonomy: Term Tab Title', 'gnetwork' ), 'callback' => NULL ];

		if ( $this->hooked( 'term_tab_extra_content' ) )
			$tabs['extras'] = [ 'title' => _x( 'Extras', 'Modules: Taxonomy: Term Tab Title', 'gnetwork' ), 'callback' => NULL ];

		return $this->filters( 'term_tabs', $tabs, $taxonomy, $term );
	}

	public function term_edit_form_top( $term, $taxonomy )
	{
		$object = get_taxonomy( $taxonomy );
		$tabs   = $this->get_term_tabs( $taxonomy, $term );

		echo '<div class="base-tabs-list -base nav-tab-base">';

		Settings::message( $this->filters( 'tabs_messages', Settings::messages() ) );

		Core\HTML::tabNav( 'edititem', [ 'edititem' => $object->labels->edit_item ] + Core\Arraay::pluck( $tabs, 'title' ) );

		echo '<div class="nav-tab-content -content nav-tab-active -active" data-tab="edititem">';
	}

	public function term_edit_form( $term, $taxonomy )
	{
		echo '</div>'; // `.div.nav-tab-content`

		$tabs = $this->get_term_tabs( $taxonomy, $term );

		foreach ( Core\Arraay::pluck( $tabs, 'callback' ) as $tab => $callback ) {

			if ( FALSE === $callback )
				continue;

			if ( is_null( $callback ) )
				$callback = [ $this, 'callback_term_tab_content_'.$tab ];

			if ( ! is_callable( $callback ) )
				continue;

			echo '<div class="nav-tab-content -content -content-tab-'.$tab.'" data-tab="'.$tab.'">';
				call_user_func_array( $callback, [ $taxonomy, $tab, get_taxonomy( $taxonomy ), $term ] );
			echo '</div>';
		}

		echo '</div>'; // `.div.nav-tab-base`
	}

	// TODO: search for similar name on *post-types*
	public function callback_term_tab_content_search( $taxonomy, $tab, $object, $term )
	{
		$this->actions( 'term_tab_search_content_before', $taxonomy, $object, $term );

		echo $this->wrap_open( '-tab-search-names card -toolbox-card' );

			Core\HTML::h4( _x( 'Similar Names', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$terms = get_terms( [
				'exclude'    => $term->term_id,
				'name__like' => $term->name,
				'hide_empty' => FALSE,
			] );

			// FIXME: table-list with edit/view links
			// TODO: show taxonomy name
			if ( ! empty( $terms ) )
				Core\HTML::tableSide( Core\Arraay::pluck( $terms, 'name', 'term_id' ) );

			else
				Core\HTML::desc( _x( 'There are no terms available with similar name.', 'Modules: Taxonomy: Message', 'gnetwork' ), TRUE, '-empty' );

		echo '</div>';

		echo $this->wrap_open( '-tab-search-descriptions card -toolbox-card' );

			Core\HTML::h4( _x( 'Similar Descriptions', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			if ( ! empty( $term->description ) ) {

				$terms = get_terms( [
					'exclude'           => $term->term_id,
					'description__like' => $term->description,
					'hide_empty'        => FALSE,
				] );

				// FIXME: table-list with edit/view links
				// TODO: show taxonomy name
				if ( ! empty( $terms ) )
					Core\HTML::tableSide( Core\Arraay::pluck( $terms, 'name', 'term_id' ) );

				else
					Core\HTML::desc( _x( 'There are no terms available with similar description.', 'Modules: Taxonomy: Message', 'gnetwork' ), TRUE, '-empty' );

			} else {

				Core\HTML::desc( _x( 'There is no description available.', 'Modules: Taxonomy: Message', 'gnetwork' ), TRUE, '-empty' );
			}

		echo '</div>';

		$this->actions( 'term_tab_search_content', $taxonomy, $object, $term );
	}

	public function callback_term_tab_content_maintenance( $taxonomy, $tab, $object, $term )
	{
		$this->actions( 'term_tab_maintenance_content', $taxonomy, $object, $term );
	}

	public function callback_term_tab_content_metadata( $taxonomy, $tab, $object, $term )
	{
		$this->actions( 'term_tab_metadata_content_before', $taxonomy, $object, $term );

		Core\HTML::tableSide( WordPress\Term::getMeta( $term, FALSE, FALSE ) );

		$this->actions( 'term_tab_metadata_content', $taxonomy, $object, $term );
	}

	public function callback_term_tab_content_posts( $taxonomy, $tab, $object, $term )
	{
		$this->actions( 'term_tab_posts_content_before', $taxonomy, $object, $term );

		$posts = get_posts( [
			'post_type' => $object->object_type, // self::req( 'post_type', 'any' ),
			'tax_query' => [
				[
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				]
			],
		] );

		// FIXME: table-list with edit/view links
		// TODO: show post-type name
		Core\HTML::tableSide( Core\Arraay::pluck( $posts, 'post_title', 'ID' ) );

		$this->actions( 'term_tab_posts_content', $taxonomy, $object, $term );
	}

	public function callback_term_tab_content_tools( $taxonomy, $tab, $object, $term )
	{
		$this->actions( 'term_tab_tools_content_before', $taxonomy, $object, $term );

		$this->actions( 'term_tab_tools_content', $taxonomy, $object, $term );
	}

	public function callback_term_tab_content_extras( $taxonomy, $tab, $object, $term )
	{
		$this->actions( 'term_tab_extra_content', $taxonomy, $object, $term );
	}

	private function taxonomy_tabs( $screen )
	{
		if ( 'edit-tags' != $screen->base || empty( $screen->taxonomy ) )
			return FALSE;

		if ( ! $object = get_taxonomy( $screen->taxonomy ) )
			return FALSE;

		if ( ! current_user_can( $object->cap->manage_terms ) )
			return FALSE;

		$this->handle_tab_content_actions( $object->name );

		add_action( $object->name.'_pre_add_form', [ $this, 'edittags_pre_add_form' ], -9999 );
		add_action( $object->name.'_add_form', [ $this, 'edittags_add_form' ], 9999 );

		if ( ! in_array( $object->name, (array) $this->filters( 'exclude_empty', [] ) ) ) {

			$this->action_self( 'tab_maintenance_content', 2, 12, 'delete_empties' );

			if ( ! $object->hierarchical ) // Allows category types to be onesies!
				$this->action_self( 'tab_maintenance_content', 2, 12, 'delete_onesies' );
		}

		$this->action_self( 'tab_extra_content', 2, 12, 'default_term' );
		// $this->action_self( 'tab_extra_content', 2, 22, 'terms_stats' ); // FIXME
		// $this->action_self( 'tab_extra_content', 2, 32, 'i18n_reports' ); // FIXME

		$this->action_self( 'tab_console_content', 2, 12, 'taxonomy_object' );
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

		if ( WordPress\User::isSuperAdmin() || WordPress\IsIt::dev() )
			$tabs['console'] = [ 'title' => _x( 'Console', 'Modules: Taxonomy: Tab Title', 'gnetwork' ), 'callback' => NULL ];

		return $this->filters( 'tabs', $tabs, $taxonomy );
	}

	// @HOOK: `{$taxonomy}_pre_add_form`
	public function edittags_pre_add_form( $taxonomy )
	{
		$object = get_taxonomy( $taxonomy );
		$tabs   = $this->get_taxonomy_tabs( $taxonomy );

		echo '<div class="base-tabs-list -base nav-tab-base">';

		Settings::message( $this->filters( 'tabs_messages', Settings::messages() ) );

		Core\HTML::tabNav( 'addnew', [ 'addnew' => $object->labels->add_new_item ] + Core\Arraay::pluck( $tabs, 'title' ) );

		echo '<div class="nav-tab-content -content nav-tab-active -active" data-tab="addnew">';
	}

	// @HOOK: `{$taxonomy}_add_form`
	public function edittags_add_form( $taxonomy )
	{
		echo '</form></div></div>';

		$tabs = $this->get_taxonomy_tabs( $taxonomy );

		foreach ( Core\Arraay::pluck( $tabs, 'callback' ) as $tab => $callback ) {

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

			$this->nonce_check( 'do-default-terms' );

			$terms    = $this->filters( 'default_terms', [], $taxonomy );
			$terms    = $this->filters( 'default_terms_'.$taxonomy, $terms, $taxonomy );
			$selected = self::req( $this->classs( 'do-default-selected' ), [] );
			$data     = $selected ? Core\Arraay::keepByKeys( $terms, array_keys( $selected ) ) : $terms;

			if ( count( $data ) && FALSE !== ( $imported = WordPress\Taxonomy::insertDefaultTerms( $taxonomy, $data ) ) )
				WordPress\Redirect::doReferer( [
					'message' => 'imported',
					'count'   => count( $imported ),
				] );

			WordPress\Redirect::doReferer( 'wrong' );

		} else if ( self::req( $this->classs( 'do-import-terms' ) ) ) {

			$this->nonce_check( 'do-import-terms' );

			$file = WordPress\Media::handleImportUpload( $this->classs( 'import' ) );

			if ( ! $file || isset( $file['error'] ) || empty( $file['file'] ) )
				WordPress\Redirect::doReferer( 'wrong' );

			$count = $this->import_terms_csv( $file['file'], $taxonomy );

			WordPress\Redirect::doReferer( [
				'message'    => 'imported',
				'count'      => $count,
				'attachment' => $file['id'],
			] );

		} else if ( self::req( $this->classs( 'do-export-terms' ) ) ) {

			$this->nonce_check( 'do-export-terms' );

			$fields = self::req( $this->classs( 'do-export-fields' ), [] );
			$data   = $this->get_csv_terms( $taxonomy, ( $fields ? array_keys( $fields ) : NULL ) );

			Core\Text::download( $data, Core\File::prepName( sprintf( '%s.csv', $taxonomy ) ) );

			WordPress\Redirect::doReferer( 'wrong' );

		} else if ( self::req( $this->classs( 'do-delete-terms' ) ) ) {

			$this->nonce_check( 'do-delete-terms' );

			// no need, we check the nounce
			// if ( ! current_user_can( get_taxonomy( $taxonomy )->cap->delete_terms ) )
			// 	WordPress\Redirect::doReferer( 'noaccess' );

			if ( $taxonomy !== self::req( $this->classs( 'do-delete-confirm' ) ) )
				WordPress\Redirect::doReferer( 'huh' );

			else
				$count = $this->_handle_delete_terms( $taxonomy, TRUE, FALSE );

			WordPress\Redirect::doReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );

		} else if ( self::req( $this->classs( 'do-delete-empties' ) ) ) {

			$this->nonce_check( 'do-delete-empties' );

			$count = $this->_handle_delete_empty_terms( $taxonomy );

			WordPress\Redirect::doReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );

		} else if ( self::req( $this->classs( 'do-delete-onesies' ) ) ) {

			$this->nonce_check( 'do-delete-onesies' );

			$count = $this->_handle_delete_onesie_terms( $taxonomy );

			WordPress\Redirect::doReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );

		} else {

			$this->actions( 'handle_tab_content_actions', $taxonomy );
		}
	}

	/**
	 * Retrieves terms with *zero* count, empty description and no children.
	 *
	 * @param string|object $taxonomy
	 * @param bool $check_description
	 * @return false|array
	 */
	private function _get_empty_terms( $taxonomy, $check_description = TRUE )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		// TODO: exclude terms with `protected_empty` meta
		$term_ids  = WordPress\Taxonomy::getEmptyTermIDs( $object->name, $check_description );
		$default   = WordPress\Taxonomy::getDefaultTermID( $object->name );
		$hierarchy = WordPress\Taxonomy::getHierarchy( $object );

		if ( count( $term_ids ) && ( $default ) )
			$term_ids = array_diff( $term_ids, [ $default ] );

		if ( count( $term_ids ) && count( $hierarchy ) )
			$term_ids = array_diff( $term_ids, array_keys( $hierarchy ) );

		return $this->filters( 'empty_terms', $term_ids, $object->name, $default );
	}

	/**
	 * Retrieves terms with *one* count, empty description and no children.
	 *
	 * @param string|object $taxonomy
	 * @param bool $check_description
	 * @return false|array
	 */
	private function _get_onesie_terms( $taxonomy, $check_description = TRUE )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		// TODO: exclude terms with `protected_empty` meta
		$term_ids = WordPress\Taxonomy::getEmptyTermIDs( $object->name, $check_description, 1, 1 );
		$default  = WordPress\Taxonomy::getDefaultTermID( $object->name );
		$children = WordPress\Taxonomy::getHierarchy( $object );

		if ( count( $term_ids ) && ( $default ) )
			$term_ids = array_diff( $term_ids, [ $default ] );

		if ( count( $term_ids ) && count( $children ) )
			$term_ids = array_diff( $term_ids, array_keys( $children ) );

		return $this->filters( 'empty_onesies', $term_ids, $object->name, $default );
	}

	/**
	 * Handles empty terms deletion.
	 *
	 * @param string|object $taxonomy
	 * @param null|array $term_ids
	 * @param bool $include_default
	 * @return int
	 */
	private function _handle_delete_empty_terms( $taxonomy, $term_ids = NULL, $include_default = FALSE )
	{
		$count   = 0;
		$default = WordPress\Taxonomy::getDefaultTermID( $taxonomy );

		if ( is_null( $term_ids ) )
			$term_ids = $this->_get_empty_terms( $taxonomy );

		if ( ! $term_ids )
			return $count;

		foreach ( $term_ids as $term_id ) {

			// NOTE: just to be safe, maybe added by filter or selected by user
			if ( ! $include_default && $default == $term_id )
				continue;

			// @REF: https://wp.me/p2AvED-5kA
			if ( ! current_user_can( 'delete_term', $term_id ) )
				continue;

			// Manually re-count to skip if the term has relationships.
			if ( WordPress\Taxonomy::countTermObjects( $term_id, $taxonomy ) )
				continue;

			if ( ! $this->filters( 'delete_empty_term', TRUE, $term_id, $taxonomy ) )
				continue;

			$deleted = wp_delete_term( $term_id, $taxonomy );

			if ( $deleted && ! is_wp_error( $deleted ) )
				$count++;
		}

		return $count;
	}

	/**
	 * Handles onesie terms deletion.
	 *
	 * @param string|object $taxonomy
	 * @param null|array $term_ids
	 * @param bool $include_default
	 * @return int
	 */
	private function _handle_delete_onesie_terms( $taxonomy, $term_ids = NULL, $include_default = FALSE )
	{
		$count   = 0;
		$default = WordPress\Taxonomy::getDefaultTermID( $taxonomy );

		if ( is_null( $term_ids ) )
			$term_ids = $this->_get_onesie_terms( $taxonomy );

		if ( ! $term_ids )
			return $count;

		foreach ( $term_ids as $term_id ) {

			// NOTE: just to be safe, maybe added by filter or selected by user
			if ( ! $include_default && $default == $term_id )
				continue;

			// @REF: https://wp.me/p2AvED-5kA
			if ( ! current_user_can( 'delete_term', $term_id ) )
				continue;

			// Manually re-count: skip if the term has relationships
			if ( WordPress\Taxonomy::countTermObjects( $term_id, $taxonomy ) > 1 )
				continue;

			if ( ! $this->filters( 'delete_onesie_term', TRUE, $term_id, $taxonomy ) )
				continue;

			$deleted = wp_delete_term( $term_id, $taxonomy );

			if ( $deleted && ! is_wp_error( $deleted ) )
				$count++;

			// TODO: must fire action hook with complete data!
		}

		return $count;
	}

	private function _handle_delete_terms( $taxonomy, $force = FALSE, $include_default = FALSE )
	{
		$count = 0;
		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'exclude'    => $include_default ? '' : WordPress\Taxonomy::getDefaultTermID( $taxonomy, '' ),
			'fields'     => 'all',
			'orderby'    => 'none',
			'hide_empty' => FALSE,

			'suppress_filter'        => TRUE,
			'update_term_meta_cache' => FALSE,
		] );

		foreach ( $terms as $term ) {

			$delete = TRUE;

			if ( ! $force ) {

				if ( ! WordPress\Strings::isEmpty( $term->description ) ) {

					// Skip if the term description is not an empty string.
					$delete = FALSE;

				} else {

					// Skip if the term has relationships.
					// NOTE: we can not rely on `$term->count` data from the term query.
					if ( WordPress\Taxonomy::countTermObjects( $term->term_id, $term->taxonomy ) )
						$delete = FALSE;
				}
			}

			if ( ! $this->filters( 'delete_term', $delete, $term, $taxonomy, $force ) )
				continue;

			// MAYBE: check `delete_term` cap for each term
			// @SEE: https://wp.me/p2AvED-5kA

			$deleted = wp_delete_term( $term->term_id, $taxonomy );

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
			Core\HTML::desc( gNetwork()->na() );  // FIXME
		echo '</div>';

		$this->actions( 'tab_search_content', $taxonomy, $object );
	}

	public function callback_tab_content_tools( $taxonomy, $tab, $object )
	{
		$this->actions( 'tab_tools_content_before', $taxonomy, $object );

		$this->_tab_content_tools_defaults( $taxonomy, $object );
		$this->_tab_content_tools_import( $taxonomy, $object );
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
			Core\HTML::h4( _x( 'Default Terms', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$hook  = sprintf( 'default_terms_%s', $taxonomy );
			$terms = $this->filters( 'default_terms', [], $taxonomy );

			if ( count( $terms ) || $this->hooked( $hook ) ) {

				$this->render_form_start( NULL, 'defaults', 'install', 'tabs', FALSE );
					$this->nonce_field( 'do-default-terms' );

					echo Core\HTML::multiSelect( $this->filters( $hook, $terms, $taxonomy ), [
						'name'     => $this->classs( 'do-default-selected' ),
						'selected' => TRUE,
						'panel'    => TRUE,
						'values'   => TRUE,
						'value'    => 'slug',
						'prop'     => 'name',
					] );

					Core\HTML::desc( _x( 'Select to install pre-configured terms for this taxonomy.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ) );

					echo $this->wrap_open_buttons( '-toolbox-buttons' );
						Settings::submitButton( $this->classs( 'do-default-terms' ), _x( 'Install Defaults', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-primary' );
					echo '</p>';

				$this->render_form_end( NULL, 'defaults', 'install', 'tabs' );

			} else {

				Core\HTML::desc( _x( 'There are no pre-defined terms available for this taxonomy.', 'Modules: Taxonomy: Message', 'gnetwork' ), TRUE, '-empty' );
			}

		echo '</div>';
	}

	private function _tab_content_tools_import( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-tools-import card -toolbox-card' );
			Core\HTML::h4( _x( 'Import Terms', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$this->render_form_start( NULL, 'import', 'download', 'tabs', FALSE );
				$this->nonce_field( 'do-import-terms' );

				$this->do_settings_field( [
					'type'      => 'file',
					'field'     => 'import_terms_file',
					'name_attr' => $this->classs( 'import' ),
					'values'    => [
						'.csv',
					],
				] );

				$size = Core\File::formatSize( apply_filters( 'import_upload_size_limit', wp_max_upload_size() ) );

				Core\HTML::desc( sprintf(
					/* translators: `%s`: maximum file size */
					_x( 'Upload a list of terms in CSV. Maximum size: <b>%s</b>', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ),
					Core\HTML::wrapLTR( $size )
				) );

				echo $this->wrap_open_buttons( '-toolbox-buttons' );
					Settings::submitButton( $this->classs( 'do-import-terms' ), _x( 'Import from CSV', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-primary' );
				echo '</p>';

			$this->render_form_end( NULL, 'import', 'download', 'tabs' );
		echo '</div>';
	}

	private function _tab_content_tools_export( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-tools-export card -toolbox-card' );
			Core\HTML::h4( _x( 'Export Terms', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$this->render_form_start( NULL, 'export', 'download', 'tabs', FALSE );
				$this->nonce_field( 'do-export-terms' );

				echo Core\HTML::multiSelect( $this->get_export_term_fields( $taxonomy ), [
					'name'     => $this->classs( 'do-export-fields' ),
					'selected' => TRUE,
					'panel'    => TRUE,
					'values'   => TRUE,
				] );

				Core\HTML::desc( _x( 'Select fields to include on the the exported CSV file.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ) );

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
			Core\HTML::h4( _x( 'Delete Terms', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			$this->render_form_start( NULL, 'delete', 'bulk', 'tabs', FALSE );
				$this->nonce_field( 'do-delete-terms' );

				echo Core\HTML::tag( 'input', [
					'type'         => 'text',
					'name'         => $this->classs( 'do-delete-confirm' ),
					'placeholder'  => $taxonomy,
					'autocomplete' => 'off',
					'class'        => [ 'regular-text', 'code' ],
					'dir'          => 'ltr',
				] );

				Core\HTML::desc( _x( 'Confirm deletion of all terms by entering the taxonomy name.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ) );

				if ( $default = WordPress\Taxonomy::getDefaultTermID( $taxonomy ) ) {

					$term = get_term( $default, $taxonomy );

					if ( $term && ! self::isError( $term ) )
						Core\HTML::desc( sprintf(
							/* translators: `%s`: default term name */
							_x( 'The default term for this taxonomy is &ldquo;%s&rdquo; and will <b>not</b> be deleted.', 'Modules: Taxonomy: Info', 'gnetwork' ),
							Core\HTML::tag( 'i', $term->name )
						) );
				}

				echo $this->wrap_open_buttons( '-toolbox-buttons' );
					Settings::submitButton( $this->classs( 'do-delete-terms' ), _x( 'Delete All Terms', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-danger', TRUE );
				echo '</p>';

			$this->render_form_end( NULL, 'delete', 'bulk', 'tabs' );
		echo '</div>';
	}

	// TODO: card: recount posts for all terms
	// TODO: card: delete terms with single post
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
			Core\HTML::h4( _x( 'Delete Empties', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			if ( $empties = $this->_get_empty_terms( $object ) ) {

				$count = count( $empties );

				$this->render_form_start( NULL, 'delete', 'empties', 'tabs', FALSE );
					$this->nonce_field( 'do-delete-empties' );

					Core\HTML::desc( Utilities::getCounted( $count,
						/* translators: `%s`: number of empty terms */
						_nx( 'Confirm deletion of %s empty term.', 'Confirm deletion of %s empty terms.', $count, 'Modules: Taxonomy: Tab Tools', 'gnetwork' )
					) );

					if ( $object->hierarchical )
						Core\HTML::desc( _x( 'The terms that have children or description and the taxonomy default term are not counted.', 'Modules: Taxonomy: Message', 'gnetwork' ) );

					else
						Core\HTML::desc( _x( 'The terms that have description and the taxonomy default term are not counted.', 'Modules: Taxonomy: Message', 'gnetwork' ) );

					echo $this->wrap_open_buttons( '-toolbox-buttons' );
						Settings::submitButton( $this->classs( 'do-delete-empties' ), _x( 'Delete Empty Terms', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-danger', TRUE );
					echo '</p>';

				$this->render_form_end( NULL, 'delete', 'empties', 'tabs' );

			} else {

				Core\HTML::desc( _x( 'There are no empty terms in this taxonomy.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), TRUE, '-empty' );
			}

		echo '</div>';
	}

	public function tab_maintenance_content_delete_onesies( $taxonomy, $object )
	{
		if ( ! current_user_can( $object->cap->delete_terms ) )
			return FALSE;

		echo $this->wrap_open( '-tab-tools-delete-onesies card -toolbox-card' );
			Core\HTML::h4( _x( 'Delete Onesies', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), 'title' );

			if ( $onesies = $this->_get_onesie_terms( $object ) ) {

				$count = count( $onesies );

				$this->render_form_start( NULL, 'delete', 'onesies', 'tabs', FALSE );
					$this->nonce_field( 'do-delete-onesies' );

					Core\HTML::desc( Utilities::getCounted( $count,
						/* translators: `%s`: number of one-count terms */
						_nx( 'Confirm deletion of %s one-count term.', 'Confirm deletion of %s one-count terms.', $count, 'Modules: Taxonomy: Tab Tools', 'gnetwork' )
					) );

					if ( $object->hierarchical )
						Core\HTML::desc( _x( 'The terms that have children or description and the taxonomy default term are not counted.', 'Modules: Taxonomy: Message', 'gnetwork' ) );

					else
						Core\HTML::desc( _x( 'The terms that have description and the taxonomy default term are not counted.', 'Modules: Taxonomy: Message', 'gnetwork' ) );

					echo $this->wrap_open_buttons( '-toolbox-buttons' );
						Settings::submitButton( $this->classs( 'do-delete-onesies' ), _x( 'Delete One-Count Terms', 'Modules: Taxonomy: Tab Tools: Button', 'gnetwork' ), 'small button-danger', TRUE );
					echo '</p>';

				$this->render_form_end( NULL, 'delete', 'onesies', 'tabs' );

			} else {

				Core\HTML::desc( _x( 'There are no one-count terms in this taxonomy.', 'Modules: Taxonomy: Tab Tools', 'gnetwork' ), TRUE, '-empty' );
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
			Core\HTML::h4( _x( 'i18n Reports', 'Modules: Taxonomy: Tab Extra', 'gnetwork' ), 'title' );

			Core\HTML::desc( gNetwork()->na() ); // FIXME

		echo '</div>';
	}

	// TODO: count by meta fields
	public function tab_extra_content_terms_stats( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-extras-terms-stats card -toolbox-card' );
			Core\HTML::h4( _x( 'Terms Stats', 'Modules: Taxonomy: Tab Extra', 'gnetwork' ), 'title' );
			Core\HTML::desc( Core\HTML::code( wp_count_terms( $taxonomy ) ) );
		echo '</div>';
	}

	// FIXME: maybe move to `maintenance` tab
	// FIXME: must be link button to edit the default term
	// FIXME: unset default term button
	public function tab_extra_content_default_term( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-extras-default-term card -toolbox-card' );
			Core\HTML::h4( _x( 'Default Term', 'Modules: Taxonomy: Tab Extra', 'gnetwork' ), 'title' );

			if ( ! $this->render_info_default_term( $taxonomy ) )
				Core\HTML::desc( _x( 'There is no default term available for this taxonomy.', 'Modules: Taxonomy: Message', 'gnetwork' ), TRUE, '-empty' );

		echo '</div>';
	}

	// ACTION HOOK: `after_{$taxonomy}_table`
	public function render_info_default_term( $taxonomy )
	{
		$default = WordPress\Taxonomy::getDefaultTermID( $taxonomy );

		if ( empty( $default ) )
			return;

		$term = get_term( $default, $taxonomy );

		if ( ! $term || self::isError( $term ) )
			return;

		Core\HTML::desc( sprintf(
			/* translators: `%s`: default term name */
			_x( 'The default term for this taxonomy is &ldquo;%s&rdquo;.', 'Modules: Taxonomy: Info', 'gnetwork' ),
			'<i>'.$term->name.'</i>'
		) );

		return TRUE;
	}

	public function callback_tab_content_console( $taxonomy, $tab, $object )
	{
		$this->actions( 'tab_console_content', $taxonomy, $object );
	}

	public function tab_console_content_taxonomy_object( $taxonomy, $object )
	{
		echo $this->wrap_open( '-tab-console-taxonomy-object card -toolbox-card' );
			Core\HTML::h4( _x( 'Taxonomy Object', 'Modules: Taxonomy: Tab Extra', 'gnetwork' ), 'title' );
			gNetwork\Misc\DumpDebug::render( $object );
		echo '</div>';
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally adapted from : Term Management Tools by `scribu` version 1.1.4
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

			$default = WordPress\Taxonomy::getDefaultTermID( $screen->taxonomy );

			if ( ! empty( $default ) && ( $current = self::req( 'tag_ID' ) ) )
				$excludes[] = $default == $current ? 'set_default' : 'unset_default';

		} else {

			$excludes = [
				'set_default',
				'unset_default',
			];
		}

		$actions = Core\Arraay::stripByKeys( $this->get_actions( $screen->taxonomy ), $excludes );

		if ( ! count( $actions ) )
			return FALSE;

		if ( 'edit-tags' == $screen->base ) {

			if ( ! WordPress\Taxonomy::can( $screen->taxonomy, 'manage_terms' ) )
				return FALSE;

			add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'handle_bulk_actions' ], 10, 3 );

			$intro = _x( 'These are extra bulk actions available for this taxonomy:', 'Modules: Taxonomy: Help Tab Content', 'gnetwork' );

		} else {

			add_action( $screen->taxonomy.'_edit_form_fields', [ $this, 'edit_form_fields_default' ], 9, 2 );
			add_action( $screen->taxonomy.'_edit_form_fields', [ $this, 'edit_form_fields_actions' ], 99, 2 );

			$intro = _x( 'These are extra actions available for this term:', 'Modules: Taxonomy: Help Tab Content', 'gnetwork' );
		}

		$this->action( 'edited_term', 3, 12, 'actions' ); // NOTE: fires on `edit-tags.php`
		$this->action( 'admin_notices' );
		$this->action( 'admin_footer' );

		// TODO: add help tab for supported post-types

		$screen->add_help_tab( [
			'id'      => $this->classs( 'help-bulk-actions' ),
			'title'   => _x( 'Extra Actions', 'Modules: Taxonomy: Help Tab Title', 'gnetwork' ),
			'content' => '<p>'.$intro.'</p>'.Core\HTML::renderList( $actions ),
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

		$hierarchical = WordPress\Taxonomy::hierarchical( $taxonomy );
		$actions      = [];

		$actions['set_default']   = _x( 'Set Default', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['unset_default'] = _x( 'Unset Default', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );

		if ( $hierarchical )
			$actions['set_parent'] = _x( 'Set Parent', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );

		$actions['merge']          = _x( 'Merge', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['split']          = _x( 'Split', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['change_tax']     = _x( 'Change Taxonomy', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['clone_tax']      = _x( 'Clone to Taxonomy', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['format_i18n']    = _x( 'Format i18n', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['format_ordinal'] = _x( 'Format Ordinal', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['assign_parents'] = _x( 'Assign Parents', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['empty_posts']    = _x( 'Empty Posts', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['empty_desc']     = _x( 'Empty Description', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		$actions['update_count']   = _x( 'Update Count', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );

		if ( $this->options['slug_actions'] ) {
			$actions['rewrite_slug']  = _x( 'Rewrite Slug', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
			$actions['downcode_slug'] = _x( 'Transliterate Slug', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );
		}

		$actions['delete_empty'] = _x( 'Delete Empty', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );

		if ( $hierarchical )
			$actions['delete_onesie'] = _x( 'Delete Onesie', 'Modules: Taxonomy: Bulk Action', 'gnetwork' );

		$filtered[$taxonomy] = $this->filters( 'bulk_actions', $actions, $taxonomy );

		return $filtered[$taxonomy];
	}

	public function edit_form_fields_default( $term, $taxonomy )
	{
		$default = WordPress\Taxonomy::getDefaultTermID( $taxonomy );

		if ( empty( $default ) )
			return;

		if ( $term->term_id != $default )
			return;

		$object = get_taxonomy( $taxonomy );

		echo '<tr class="form-field term-info-wrap">';
			echo '<th scope="row" valign="top">';
				_ex( 'Caution', 'Modules: Taxonomy', 'gnetwork' );
			echo '</th><td>';

			Core\HTML::desc( sprintf(
				/* translators: `%s`: taxonomy label */
				_x( 'This is the default term for &ldquo;%s&rdquo; taxonomy.', 'Modules: Taxonomy: Info', 'gnetwork' ),
				'<strong>'.$object->label.'</strong>'
			) );

		echo '</td></tr>';
	}

	public function edit_form_fields_actions( $term, $taxonomy )
	{
		echo '<tr class="form-field term-actions-wrap actions">';
			echo '<th scope="row" valign="top"><label for="extra-action-selector">';
				_ex( 'Extra Actions', 'Modules: Taxonomy', 'gnetwork' );
			echo '</label></th><td>';

			echo '<select name="'.$this->classs( 'action' ).'" id="extra-action-selector">';
				echo '<option value="-1">'._x( '&ndash; Select Action &ndash;', 'Modules: Taxonomy', 'gnetwork' )."</option>\n";
			echo "</select>\n";

		echo '</td></tr>';
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

		WordPress\Redirect::doWP( add_query_arg( $query, WordPress\Redirect::getReferer() ) );
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
					return \call_user_func_array( $callback, [ $term_ids, $taxonomy, $key ] );
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

				echo Core\HTML::success( _x( 'Terms updated.', 'Settings: Message', 'gnetwork' ) );

			break;
			case 'gnetwork-taxonomy-error':

				echo Core\HTML::error( _x( 'Terms not updated.', 'Settings: Message', 'gnetwork' ) );
		}
	}

	public function handle_assign_parents( $term_ids, $taxonomy )
	{
		WordPress\Taxonomy::disableTermCounting();

		foreach ( $term_ids as $term_id ) {

			if ( ! $parents = WordPress\Taxonomy::getTermParents( $term_id, $taxonomy ) )
				continue;

			foreach ( WordPress\Taxonomy::getTermObjects( $term_id, $taxonomy ) as $object_id )
				wp_set_object_terms( $object_id, $parents, $taxonomy, TRUE );
		}

		// flush the deferred term counts
		wp_update_term_count( NULL, NULL, TRUE );

		return TRUE;
	}

	public function handle_empty_posts( $term_ids, $taxonomy )
	{
		WordPress\Taxonomy::disableTermCounting();

		foreach ( $term_ids as $term_id )
			if ( FALSE === WordPress\Taxonomy::removeTermObjects( (int) $term_id, $taxonomy ) )
				return FALSE;

		// flush the deferred term counts
		wp_update_term_count( NULL, NULL, TRUE );

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

	public function handle_delete_onesie( $term_ids, $taxonomy )
	{
		return $this->_handle_delete_onesie_terms( $taxonomy, $term_ids );
	}

	public function handle_delete_empty( $term_ids, $taxonomy )
	{
		return $this->_handle_delete_empty_terms( $taxonomy, $term_ids );
	}

	public function handle_downcode_slug( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			$term = get_term( $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			if ( ! Core\Text::containsUTF8( $term->name ) )
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

	public function handle_update_count( $term_ids, $taxonomy )
	{
		return wp_update_term_count_now( $term_ids, $taxonomy );
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

	public function handle_format_ordinal( $term_ids, $taxonomy )
	{
		foreach ( $term_ids as $term_id ) {

			$term = get_term( $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			$ordinal = Core\Number::toOrdinal( $term->name );

			if ( $ordinal != $term->name )
				wp_update_term( $term_id, $taxonomy, [ 'name' => $ordinal ] );
		}

		return TRUE;
	}

	// Separated because we have to keep the connected object list
	public function handle_multiple_merge( $targets, $term_ids, $taxonomy )
	{
		global $wpdb;

		if ( empty( $targets ) )
			return FALSE;

		if ( ! is_array( $targets ) )
			$targets = array_filter( array_map( 'trim', explode( ',,', $targets ) ) );

		$new_terms = [];

		foreach ( $targets as $target )
			if ( $new_term = WordPress\Taxonomy::getTargetTerm( $target, $taxonomy ) )
				$new_terms[$new_term->term_id] = $new_term;

		if ( ! count( $new_terms ) )
			return FALSE;

		WordPress\Taxonomy::disableTermCounting();

		foreach ( (array) $term_ids as $term_id ) {

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

		// flush the deferred term counts
		wp_update_term_count( NULL, NULL, TRUE );

		return TRUE;
	}

	public function handle_merge( $term_ids, $taxonomy, $action = 'merge', $to = NULL )
	{
		if ( ! ( $target = $to ?? self::req( $this->classs( 'bulk-merge' ) ) ) )
			return FALSE;

		// handle multiple merge
		if ( Core\Text::has( $target, ',,' ) )
			return $this->handle_multiple_merge( $target, $term_ids, $taxonomy );

		if ( ! $new_term = WordPress\Taxonomy::getTargetTerm( $target, $taxonomy ) )
			return FALSE;

		WordPress\Taxonomy::disableTermCounting();

		foreach ( (array) $term_ids as $term_id ) {

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

		// flush the deferred term counts
		wp_update_term_count( NULL, NULL, TRUE );

		return TRUE;
	}

	public function handle_split( $term_ids, $taxonomy )
	{
		global $wpdb;

		$delimiter = self::req( $this->classs( 'bulk-split' ) );

		foreach ( $term_ids as $term_id ) {

			$old_term = get_term( $term_id, $taxonomy );
			$targets  = WordPress\Strings::getSeparated( $old_term->name, $delimiter ?: NULL );

			if ( count( $targets ) < 2 )
				continue;

			$old_meta    = get_term_meta( $term_id );
			$old_objects = (array) $wpdb->get_col( $wpdb->prepare( "
				SELECT object_id FROM {$wpdb->term_relationships}
				WHERE term_taxonomy_id = %d
			", $old_term->term_taxonomy_id ) );

			foreach ( $targets as $target ) {

				if ( ! $new_term = WordPress\Taxonomy::getTargetTerm( $target, $taxonomy ) )
					continue;

				// needs to be set before our action fired
				foreach ( $old_objects as $old_object )
					wp_set_object_terms( $old_object, $new_term->term_id, $taxonomy, TRUE );

				foreach ( $old_meta as $meta_key => $meta_value )
					foreach ( $meta_value as $value_value ) // multiple meta
						add_term_meta( $new_term->term_id, $meta_key, $value_value, FALSE );

				$this->actions( 'term_splited', $taxonomy, $new_term, $old_term, $old_meta );
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

			update_option( WordPress\Taxonomy::getDefaultTermOptionKey( $taxonomy ), (int) $term_id );

			break; // only one can be default!
		}

		return TRUE;
	}

	public function handle_unset_default( $term_ids, $taxonomy )
	{
		return delete_option( WordPress\Taxonomy::getDefaultTermOptionKey( $taxonomy ) );
	}

	public function handle_set_parent( $term_ids, $taxonomy )
	{
		$parent_id = self::req( $this->classs( 'parent-id' ) );

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

		$old_tax = $taxonomy;
		$new_tax = self::req( $this->classs( 'new-taxonomy' ) );

		if ( ! taxonomy_exists( $new_tax ) )
			return FALSE;

		if ( $new_tax == $old_tax )
			return FALSE;

		$tt_ids = $merging = [];

		foreach ( (array) $term_ids as $term_id ) {

			$term = get_term( $term_id, $old_tax );

			if ( ! $term || self::isError( $term ) )
				continue;

			if ( $already = get_term_by( 'slug', $term->slug, $new_tax ) )
				$merging[$term_id] = $already->term_id;

			if ( $term->parent && ! in_array( $term->parent, (array) $term_ids ) )
				$wpdb->update( $wpdb->term_taxonomy,
					[ 'parent' => 0 ],
					[ 'term_taxonomy_id' => $term->term_taxonomy_id ]
				);

			$tt_ids[] = $term->term_taxonomy_id;

			if ( is_taxonomy_hierarchical( $old_tax ) ) {

				$child_terms = get_terms( [
					'taxonomy'   => $old_tax,
					'child_of'   => $term_id,
					'hide_empty' => FALSE,
					'orderby'    => 'none',

					'suppress_filter'        => TRUE,
					'update_term_meta_cache' => FALSE,
				] );

				$tt_ids = array_merge( $tt_ids, Core\Arraay::pluck( $child_terms, 'term_taxonomy_id' ) );
			}
		}

		$tt_ids = array_map( 'absint', $tt_ids );
		$string = implode( ',', $tt_ids );

		$wpdb->query( $wpdb->prepare( "
			UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE term_taxonomy_id IN ({$string})
		", $new_tax ) );

		if ( is_taxonomy_hierarchical( $old_tax ) && ! is_taxonomy_hierarchical( $new_tax ) )
			$wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET parent = 0 WHERE term_taxonomy_id IN ({$string})" );

		foreach ( $merging as $merge_source => $merge_target )
			$this->handle_merge( $merge_source, $new_tax, 'merge', $merge_target );

		clean_term_cache( $tt_ids, $old_tax, FALSE );
		clean_term_cache( $tt_ids, $new_tax, FALSE );

		$this->actions( 'term_changed_taxonomy', $tt_ids, $new_tax, $old_tax );

		return TRUE;
	}

	public function handle_clone_tax( $term_ids, $taxonomy )
	{
		global $wpdb;

		$current_tax = $taxonomy;
		$cloned_tax  = self::req( $this->classs( 'clone-taxonomy' ) );

		if ( ! taxonomy_exists( $cloned_tax ) )
			return FALSE;

		if ( $cloned_tax == $current_tax )
			return FALSE;

		WordPress\Taxonomy::disableTermCounting();

		foreach ( $term_ids as $term_id ) {

			$current_term = get_term( $term_id, $current_tax );
			$cloned_args  = [ 'slug' => $current_term->slug, 'description' => $current_term->description ]; // not supporting parents

			$current_meta = get_term_meta( $term_id );
			$cloned_meta  = []; // empty( $current_meta ) ? [] : array_combine( Core\Arraay::pluck( $current_meta, 0 ), array_keys( $current_meta ) ); // added manually later

			if ( ! $cloned_term = WordPress\Taxonomy::getTargetTerm( $current_term->name, $cloned_tax, $cloned_args, $cloned_meta ) )
				continue;

			$current_objects = (array) $wpdb->get_col( $wpdb->prepare( "
				SELECT object_id FROM {$wpdb->term_relationships}
				WHERE term_taxonomy_id = %d
			", $current_term->term_taxonomy_id ) );

			// needs to be set before our action fired
			foreach ( $current_objects as $current_object )
				wp_set_object_terms( $current_object, $cloned_term->term_id, $cloned_tax, TRUE );

			foreach ( $current_meta as $meta_key => $meta_value )
				foreach ( $meta_value as $value_value ) // multiple meta
					add_term_meta( $cloned_term->term_id, $meta_key, $value_value, FALSE );

			$this->actions( 'term_cloned', $cloned_tax, $cloned_term, $current_term, $current_meta );
		}

		// flush the deferred term counts
		wp_update_term_count( NULL, NULL, TRUE );

		return TRUE;
	}

	public function admin_footer()
	{
		if ( ! empty( $GLOBALS['taxonomy'] ) )
			$this->render_secondary_inputs( $GLOBALS['taxonomy'], $this->get_actions( $GLOBALS['taxonomy'] ) );
	}

	private function render_secondary_inputs( $taxonomy, $actions )
	{
		foreach ( array_keys( $actions ) as $action ) {

			$callback = $this->filters( 'bulk_input', [ $this, 'secondary_input_'.$action ], $action, $taxonomy );

			if ( $callback && is_callable( $callback ) ) {

				echo "<div id='gnetwork-taxonomy-input-$action' class='gnetwork-taxonomy-input-wrap' style='display:none'>\n";

					call_user_func_array( $callback, [ $taxonomy, $action ] );

				echo "</div>\n";
			}
		}
	}

	private function secondary_input_merge( $taxonomy )
	{
		/* translators: `%s`: merge/split into input */
		printf( _x( 'into: %s', 'Modules: Taxonomy', 'gnetwork' ),
			'<input name="'.$this->classs( 'bulk-merge' ).'" type="text" placeholder="'
			._x( 'Name, Slug or ID', 'Modules: Taxonomy', 'gnetwork' ).'" />' );
	}

	private function secondary_input_split( $taxonomy )
	{
		/* translators: `%s`: merge/split into input */
		printf( _x( 'into: %s', 'Modules: Taxonomy', 'gnetwork' ),
			'<input name="'.$this->classs( 'bulk-split' ).'" type="text" placeholder="'
			._x( 'Delimiter', 'Modules: Taxonomy', 'gnetwork' ).'" />' );
	}

	private function secondary_input_change_tax( $taxonomy )
	{
		$args = current_user_can( 'import' ) ?  [] : [ 'show_ui' => TRUE ];
		$list = get_taxonomies( $args, 'objects' );

		echo '<select class="postform" name="'.$this->classs( 'new-taxonomy' ).'">';

		foreach ( $list as $new_tax => $tax_obj ) {

			if ( $new_tax == $taxonomy )
				continue;

			echo "<option value='$new_tax'>$tax_obj->label</option>\n";
		}

		echo '</select>';
	}

	private function secondary_input_clone_tax( $taxonomy )
	{
		$args = current_user_can( 'import' ) ?  [] : [ 'show_ui' => TRUE ];
		$list = get_taxonomies( $args, 'objects' );

		echo '<select class="postform" name="'.$this->classs( 'clone-taxonomy' ).'">';

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
			'name'             => $this->classs( 'parent-id' ),
			'orderby'          => 'name',
			'taxonomy'         => $taxonomy,
			'hierarchical'     => TRUE,
			'show_option_none' => Settings::showOptionNone(),
		] );
	}

	private function get_export_term_fields( $taxonomy )
	{
		return $this->filters( 'export_term_fields', [
			'name'        => _x( 'Name', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'slug'        => _x( 'Slug', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'description' => _x( 'Description', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'parent'      => _x( 'Parent', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'parent_name' => _x( 'Parent Name', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'parent_slug' => _x( 'Parent Slug', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
			'count'       => _x( 'Count', 'Modules: Taxonomy: Term Field', 'gnetwork' ),
		], $taxonomy );
	}

	private function get_export_term_meta( $taxonomy )
	{
		return $this->filters( 'export_term_meta', [
			// 'example' => _x( 'Example', 'Modules: Taxonomy: Term Meta', 'gnetwork' ),
		], $taxonomy );
	}

	// FIXME: deal with line-breaks on descriptions
	private function get_csv_terms( $taxonomy, $fields = NULL, $metas = NULL )
	{
		global $wpdb;

		if ( is_null( $fields ) )
			$fields = array_keys( $this->get_export_term_fields( $taxonomy ) );

		if ( is_null( $metas ) )
			$metas = array_keys( $this->get_export_term_meta( $taxonomy ) );

		$raw = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->term_taxonomy}
			INNER JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
			WHERE {$wpdb->term_taxonomy}.taxonomy = %s
			ORDER BY {$wpdb->terms}.term_id ASC
		", $taxonomy ) );

		// FIXME: handle empty results

		$data  = [ array_merge( [ 'term_id' ], $fields, Core\Arraay::prefixValues( $metas, 'meta_' ) ) ];
		$terms = Core\Arraay::reKey( $raw, 'term_id' );

		foreach ( $terms as $term ) {

			$row = [ $term->term_id ];

			foreach ( $fields as $field ) {

				if ( 'slug' === $field )
					$row[] = urldecode( $term->{$field} );

				else if ( property_exists( $term, $field ) )
					$row[] = trim( $term->{$field} );

				else if ( 'parent_name' === $field )
					$row[] = ( $term->parent && array_key_exists( $term->parent, $terms ) ) ? $terms[$term->parent]->name : '';

				else if ( 'parent_slug' === $field )
					$row[] = ( $term->parent && array_key_exists( $term->parent, $terms ) ) ? urldecode( $terms[$term->parent]->slug ) : '';

				else
					$row[] = ''; // unknown field!
			}

			$saved = get_term_meta( $term->term_id );

			foreach ( $metas as $meta ) {

				$row_cell = empty( $saved[$meta][0] ) ? '' : trim( $saved[$meta][0] );
				$filtered = $this->filters( 'export_term_meta_data', $row_cell, $meta, $taxonomy, $term );

				$row[] = $filtered ?: '';
			}

			$data[] = $row;
		}

		return Core\Text::toCSV( $data );
	}

	private function import_terms_csv( $file_path, $taxonomy )
	{
		$count = 0;

		$csv = new \ParseCsv\Csv();
		$csv->auto( Core\File::normalize( $file_path ) );

		foreach ( $csv->data as $offset => $row ) {

			$name = '';
			$args = [];
			$meta = [];

			foreach ( (array) $row as $key => $value ) {

				if ( 'name' == $key )
					$name = trim( $value );

				else if ( in_array( $key, [ 'parent', 'slug', 'description' ] ) )
					$args[$key] = trim( $value );

				else if ( Core\Text::starts( $key, 'meta_' ) )
					$meta[preg_replace( '/^meta\_/', '', $key )] = trim( $value );
			}

			if ( empty( $name ) )
				continue;

			if ( ! $term = WordPress\Taxonomy::getTargetTerm( $name, $taxonomy, $args ) )
				continue;

			// will bail if an entry with the same key is found
			foreach ( $meta as $meta_key => $meta_value )
				add_term_meta( $term->term_id, $meta_key, $meta_value, TRUE );

			$this->actions( 'import_terms_csv', $term, $taxonomy, $meta, $row, $file_path );

			$count++;
		}

		return $count;
	}

	public function init_redirect_terms()
	{
		add_rewrite_tag( '%t%', '([^&]+)' );
		add_rewrite_rule( '^t/([^/]*)/?', 'index.php?t=$matches[1]', 'top' );
	}

	public function template_redirect_redirect_terms()
	{
		if ( ! is_home() )
			return;

		if ( ! $term_id = get_query_var( 't' ) )
			return;

		$term = get_term( (int) $term_id );

		if ( ! $term || is_wp_error( $term ) )
			return;

		if ( in_array( $term->taxonomy, $this->filters( 'redirect_blacklist', [ 'nav_menu' ], $term ), TRUE ) )
			return;

		WordPress\Redirect::doWP( get_term_link( $term ), 301 );
	}

	public function tag_row_actions_redirect_terms( $actions, $term )
	{
		if ( is_taxonomy_viewable( $term->taxonomy ) )
			$actions['shortlink'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				WordPress\Term::shortlink( $term ),
				esc_attr( sprintf(
					/* translators: `%s`: Taxonomy term name. */
					_x( 'Copy Shortlink for &#8220;%s&#8221;', 'Modules: Taxonomy: Action', 'gnetwork' ),
					$term->name
				) ),
				_x( 'Shortlink', 'Modules: Taxonomy: Action', 'gnetwork' )
			);

		return $actions;
	}
}

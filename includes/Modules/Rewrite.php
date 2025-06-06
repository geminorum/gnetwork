<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\WordPress;

class Rewrite extends gNetwork\Module
{
	protected $key     = 'rewrite';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( $this->options['remove_category_base'] ) {

			$this->action( 'init' );
			$this->filter( 'query_vars' );
			$this->filter( 'request' );
			$this->filter( 'category_rewrite_rules' );

			add_action( 'created_category', [ $this, 'flush_rewrite_rules' ], 9, 0 );
			add_action( 'delete_category', [ $this, 'flush_rewrite_rules' ], 9, 0 );
			add_action( 'edited_category', [ $this, 'flush_rewrite_rules' ], 9, 0 );

			// TODO: add notice and disable category base input on Permalink Settings
		}

		if ( ! is_blog_admin() )
			return;

		$this->filter_module( 'dashboard', 'pointers', 1, -10 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Rewrite Rules', 'Modules: Menu Name', 'gnetwork' ) );
		$this->register_tool( _x( 'Rewrite Rules', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'remove_category_base' => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'remove_category_base',
					'title'       => _x( 'Remove Category Base', 'Modules: Rewrite: Settings', 'gnetwork' ),
					'description' => sprintf(
						/* translators: `%s`: default category slug */
						_x( 'Removes %s from your category permalinks.', 'Modules: Rewrite: Settings', 'gnetwork' ),
						Core\HTML::code( '/category' )
					),
				],
			],
		];
	}

	public function flush_rewrite_rules()
	{
		flush_rewrite_rules();
		wp_cache_delete( 'rewrite_rules', 'options' );
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( Core\WordPress::maybeFlushRules() )
			Core\HTML::desc( _x( 'You need to flush rewrite rules!', 'Modules: Rewrite', 'gnetwork' ), TRUE, '-color-danger' );

		echo $this->wrap_open_buttons();

		echo Core\HTML::tag( 'a', [
			'class' => 'button button-secondary button-small',
			'href'  => $this->get_menu_url( NULL, NULL, 'tools' ),
			'title' => _x( 'View and set network roles here.', 'Modules: Rewrite', 'gnetwork' ),
		], _x( 'Rewrite Rules', 'Modules: Menu Name', 'gnetwork' ) );

		echo '</p>';
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		$search = self::req( 's' );
		$source = self::req( 'source', 'all' );

		list( $rules, $sources ) = $this->get_rules( $source, $search );

		if ( $search )
			$title = sprintf(
				/* translators: `%1$s`: rules count, `%2$s`: search criteria, `%3$s`: search criteria */
				_x( 'A Listing of All %1$s Rewrite Rules for This Site that Match &ldquo;<a target="_blank" href="%2$s">%3$s</a>&rdquo;', 'Modules: Rewrite', 'gnetwork' ),
				Core\Number::format( count( $rules ) ),
				esc_url( $search ),
				esc_url( $search )
			);

		else
			$title = sprintf(
				/* translators: `%1$s`: rules count */
				_x( 'A Listing of All %1$s Rewrite Rules for This Site', 'Modules: Rewrite', 'gnetwork' ),
				Core\Number::format( count( $rules ) )
			);

		return Core\HTML::tableList( [
			'rule'    => _x( 'Rule', 'Modules: Rewrite', 'gnetwork' ),
			'rewrite' => _x( 'Rewrite', 'Modules: Rewrite', 'gnetwork' ),
			'source'  => _x( 'Source', 'Modules: Rewrite', 'gnetwork' ),
		], $rules, [
			'title'     => Core\HTML::tag( 'h3', $title ),
			'empty'     => _x( 'No rewrite rules were found.', 'Modules: Rewrite', 'gnetwork' ),
			'row_class' => [ $this, 'table_list_row_class' ],
			'before'    => [ $this, 'table_list_before' ],
			'extra'     => compact( 'search', 'source', 'sources' ),
		] );
	}

	public function table_list_before( $columns, $data, $args )
	{
		$url = $this->get_menu_url( NULL, NULL, 'tools' );

		$html = _x( 'Match URL:', 'Modules: Rewrite', 'gnetwork' );
		$html.= ' '.Core\HTML::tag( 'input', [
			'type'  => 'text',
			'name'  => 's',
			'id'    => $this->classs( 'search' ),
			'value' => $args['extra']['search'],
			'class' => 'regular-text code',
		] );

		Core\HTML::label( $html, $this->classs( 'search' ), FALSE );

		echo '&nbsp;&nbsp;';

		$sources = array_combine( $args['extra']['sources'], $args['extra']['sources'] );
		$sources['all'] = _x( 'All Sources', 'Modules: Rewrite', 'gnetwork' );
		echo Core\HTML::dropdown( $sources, [ 'name' => 'source', 'selected' => $args['extra']['source'] ] );

		echo '&nbsp;&nbsp;';

		Settings::submitButton( 'filter', _x( 'Filter', 'Modules: Rewrite', 'gnetwork' ), TRUE );
		Settings::submitButton( $url, _x( 'Reset', 'Modules: Rewrite', 'gnetwork' ), 'link' );

		echo $this->wrap_open_buttons( '-side -s1ide-ltr', FALSE );

		Settings::submitButton( add_query_arg( static::BASE.'_action', 'flushrewrite', $url ),
			_x( 'Flush Rewrite Rules', 'Modules: Rewrite', 'gnetwork' ), 'link', [], '' );

		echo '</span>';
	}

	public function table_list_row_class( $row_class, $row, $index, $args )
	{
		if ( 'missing' == $row['source'] )
			$row_class[] = '-row-color-danger';

		return $row_class;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// ADOPTED FROM: Rewrite Rules Inspector
// v1.4.0 - 2024-05-22
// @SOURCE: https://wordpress.org/plugins/rewrite-rules-inspector/
// @SOURCE: https://github.com/Automattic/Rewrite-Rules-Inspector/

	private function get_rules( $req_source = NULL, $req_search = '' )
	{
		global $wp_rewrite;

		$data = $list = [];

		// track down which rewrite rules are associated with which methods by breaking it down
		$by_source = [
			'post'     => $wp_rewrite->generate_rewrite_rules( $wp_rewrite->permalink_structure, EP_PERMALINK ),
			'date'     => $wp_rewrite->generate_rewrite_rules( $wp_rewrite->get_date_permastruct(), EP_DATE ),
			'root'     => $wp_rewrite->generate_rewrite_rules( $wp_rewrite->root.'/', EP_ROOT ),
			'comments' => $wp_rewrite->generate_rewrite_rules( $wp_rewrite->root.$wp_rewrite->comments_base, EP_COMMENTS, TRUE, TRUE, TRUE, FALSE ),
			'search'   => $wp_rewrite->generate_rewrite_rules( $wp_rewrite->get_search_permastruct(), EP_SEARCH ),
			'author'   => $wp_rewrite->generate_rewrite_rules( $wp_rewrite->get_author_permastruct(), EP_AUTHORS ),
			'page'     => $wp_rewrite->page_rewrite_rules(),
		];

		// extra permastructs including tags, categories, etc.
		foreach ( $wp_rewrite->extra_permastructs as $permastructname => $permastruct ) {

			if ( is_array( $permastruct ) ) {

				// Pre 3.4 compat
				if ( count( $permastruct ) == 2 )
					$by_source[$permastructname] = $wp_rewrite->generate_rewrite_rules( $permastruct[0], $permastruct[1] );

				else
					$by_source[$permastructname] = $wp_rewrite->generate_rewrite_rules( $permastruct['struct'], $permastruct['ep_mask'], $permastruct['paged'], $permastruct['feed'], $permastruct['forcomments'], $permastruct['walk_dirs'], $permastruct['endpoints'] );

			} else {

				$by_source[$permastructname] = $wp_rewrite->generate_rewrite_rules( $permastruct, EP_NONE );
			}
		}

		// apply the filters used in core just in case
		foreach ( $by_source as $source => $rules ) {

			$by_source[$source] = apply_filters( $source.'_rewrite_rules', $rules );

			if ( 'post_tag' == $source )
				$by_source[$source] = apply_filters( 'tag_rewrite_rules', $rules );
		}

		$option = get_option( 'rewrite_rules' );

		if ( ! $option )
			$option = [];

		foreach ( $option as $rule => $rewrite ) {

			$data[$rule]['rewrite'] = $rewrite;

			foreach ( $by_source as $source => $rules )
				if ( array_key_exists( $rule, $rules ) )
					$data[$rule]['source'] = $source;

			if ( ! isset( $data[$rule]['source'] ) )
				$data[$rule]['source'] = $this->filters( 'source', 'other', $rule, $rewrite );
		}

		// find any rewrite rules that should've been generated but weren't
		$data = array_reverse( $data, TRUE );

		foreach ( $wp_rewrite->rewrite_rules() as $rule => $rewrite ) {

			if ( ! array_key_exists( $rule, $data ) ) {

				$data[$rule] = [
					'rewrite' => $rewrite,
					'source'  => 'missing',
				];
			}
		}

		// allow static sources of rewrite rules to override, etc.
		$data = $this->filters( 'rewrite_rules', array_reverse( $data, TRUE ) );

		// set the sources used in our filtering
		$sources = ['all'] + array_unique( array_column( $data, 'source' ) );

		if ( ! empty( $req_search ) ) {

			$match_path = Core\URL::parse( esc_url( $req_search ), PHP_URL_PATH );
			$wordpress  = Core\URL::parse( home_url(), PHP_URL_PATH );

			if ( ! empty( $wordpress ) )
				$match_path = str_replace( $wordpress, '', $match_path ?: '' );

			$match_path = ltrim( $match_path, '/' );
		}

		$do_filter = ! empty( $req_source ) && 'all' !== $req_source && in_array( $req_source, $sources );

		$list = [];

		// filter based on match or source if necessary
		foreach ( $data as $rule => $row ) {

			// if we're searching rules based on URL and there's no match, don't return it
			if ( ! empty( $match_path ) && ! preg_match( "!^$rule!", $match_path ) )
				continue;

			else if ( $do_filter && $row['source'] != $req_source )
				continue;

			$list[] = [
				'rule'    => $rule,
				'rewrite' => $row['rewrite'],
				'source'  => $row['source'],
			];
		}

		return [ $list, $sources ];
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// ADOPTED FROM: Remove Category URL by Valerio Souza
// v1.1.4 - 2019-07-05
// @SOURCE: https://github.com/valeriosouza/remove-category-url

	public function init()
	{
		$GLOBALS['wp_rewrite']->extra_permastructs['category']['struct'] = '%category%';
	}

	public function query_vars( $public_query_vars )
	{
		return array_merge( $public_query_vars, [ 'category_redirect' ] );
	}

	public function request( $query_vars )
	{
		if ( ! isset( $query_vars['category_redirect'] ) )
			return $query_vars;

		$location = Core\URL::trail( get_option( 'home' ) )
			.user_trailingslashit( $query_vars['category_redirect'], 'category' );

		Core\WordPress::redirect( $location, 301 );
	}

	// @REF: https://wordpress.stackexchange.com/a/172961/
	// TODO: support AMP
	public function category_rewrite_rules( $rules )
	{
		$terms = get_terms( [
			'taxonomy'        => 'category',
			'hide_empty'      => FALSE,
			'suppress_filter' => TRUE,
		] );

		if ( is_wp_error( $terms ) || empty( $terms ) )
			return $rules;

		$old  = get_option( 'category_base' );
		$new  = [];
		$args = [ 'separator' => '/', 'link' => FALSE, 'format' => 'slug' ];

		foreach ( $terms as $term ) {

			$slug = $term->slug;

			if ( $term->parent )
				$slug = Core\URL::untrail( get_term_parents_list( $term->term_id, $term->taxonomy, $args ) );

			$slug = str_ireplace( '-', '\-', $slug );

			$new['('.$slug.')/feed/(feed|rdf|rss|rss2|atom)?/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
			$new['('.$slug.')/(feed|rdf|rss|rss2|atom)/?$']       = 'index.php?category_name=$matches[1]&feed=$matches[2]';
			$new['('.$slug.')/embed/?$']                          = 'index.php?category_name=$matches[1]&embed=true';
			$new['('.$slug.')(/page/(\d+)/?)?$']                  = 'index.php?category_name=$matches[1]&paged=$matches[3]';
			$new['('.$slug.')/?$']                                = 'index.php?category_name=$matches[1]';
		}

		// redirect old base
		if ( '.' != $old )
			$new[Core\URL::untrail( $old ).'/(.*)$'] = 'index.php?category_redirect=$matches[1]';

		return $new;
	}

	public function dashboard_pointers( $items )
	{
		if ( ! Core\WordPress::maybeFlushRules() )
			return $items;

		if ( Core\WordPress::cuc( 'manage_options' ) )
			$url = $this->get_menu_url( NULL, NULL, 'tools' );

		else if ( Core\WordPress::cuc( 'edit_others_posts' ) )
			$url = add_query_arg( static::BASE.'_action', 'flushrewrite', Core\URL::current() );

		else
			$url = FALSE;

		$items[] = Core\HTML::tag( $url ? 'a' : 'span', [
			'href'  => $url,
			'title' => _x( 'You need to flush rewrite rules!', 'Modules: Rewrite', 'gnetwork' ),
			'class' => '-flush-rules',
		], _x( 'Flush Rewrite Rules', 'Modules: Rewrite', 'gnetwork' ) );

		return $items;
	}
}

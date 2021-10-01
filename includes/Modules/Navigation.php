<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\PostType as WPPostType;
use geminorum\gNetwork\WordPress\Taxonomy as WPTaxonomy;

class Navigation extends gNetwork\Module
{

	protected $key  = 'navigation';
	protected $ajax = TRUE;

	protected function setup_actions()
	{
		$this->filter( 'wp_nav_menu_items', 2, 20 );

		if ( is_admin() ) {

			$this->action( 'load-nav-menus.php' );

			$this->filter( 'wp_setup_nav_menu_item', 1, 9, 'children' );
			$this->action( 'wp_update_nav_menu_item', 3, 9, 'children' );
			$this->action( 'wp_nav_menu_item_custom_fields', 5, 12, 'children' );

		} else {

			$this->filter( 'wp_setup_nav_menu_item' );

			$this->filter( 'wp_setup_nav_menu_item', 1, 9, 'children' );
			$this->filter( 'wp_get_nav_menu_items', 3, 9, 'children' );
		}

		if ( ! is_main_site() )
			return;

		$this->action( 'after_setup_theme' );
		$this->action( 'save_post_nav_menu_item', 2 );
	}

	public function get_global_menus()
	{
		$list  = [];
		$menus = [
			'GNETWORK_NETWORK_NAVIGATION' => _x( 'Network Global Navigation', 'Modules: Navigation: Global Menu', 'gnetwork' ),
			'GNETWORK_NETWORK_ADMINBAR'   => _x( 'Network Adminbar Navigation', 'Modules: Navigation: Global Menu', 'gnetwork' ),
			'GNETWORK_NETWORK_USERMENU'   => _x( 'Network User Navigation', 'Modules: Navigation: Global Menu', 'gnetwork' ),
			'GNETWORK_NETWORK_EXTRAMENU'  => _x( 'Network Extra Navigation', 'Modules: Navigation: Global Menu', 'gnetwork' ),
		];

		foreach ( $menus as $constant => $desc )
			if ( constant( $constant ) )
				$list[$constant] = $desc;

		return $list;
	}

	public function after_setup_theme()
	{
		foreach ( $this->get_global_menus() as $constant => $desc )
			register_nav_menu( constant( $constant ), $desc );
	}

	public function save_post_nav_menu_item( $post_id, $post )
	{
		foreach ( $this->get_global_menus() as $constant => $desc )
			update_network_option( NULL, static::BASE.'_'.constant( $constant ), '' );

		return $post_id;
	}

	public function load_nav_menus_php()
	{
		$screen = get_current_screen();

		$screen->add_help_tab( [
			'id'       => $this->classs( 'help-placeholders' ),
			'title'    => _x( 'Placeholders', 'Modules: Navigation: Help Tab Title', 'gnetwork' ),
			'callback' => [ $this, 'help_tab_placeholders' ],
		] );

		add_meta_box( $this->classs(),
			_x( 'Relative Links', 'Modules: Navigation: Meta Box Title', 'gnetwork' ),
			[ $this, 'do_meta_box' ],
			$screen,
			'side',
			'low' );
	}

	public function help_tab_placeholders( $screen, $tab )
	{
		echo '<ul class="base-list-code">';
			if ( ! $actions = $this->actions( 'help_placeholders', '<li>', '</li>', $screen, $tab ) )
				echo '<li>'.gNetwork()->na().'</li>';
		echo '</ul>';
	}

	// build and populate the accordion on Appearance > Menus
	// @SOURCE: `bp_admin_do_wp_nav_menu_meta_box()`
	public function do_meta_box()
	{
		$type = 'gnetworknav';
		$id   = 'gnetwork-menu'; // nav menu api spec
		$args = [ 'walker' => new Walker_Nav_Menu_Checklist( FALSE ) ];

		$tabs = [
			'general' => [
				'label'       => _x( 'General', 'Modules: Navigation: Tabs', 'gnetwork' ),
				'description' => _x( '<em>General</em> links are relative to the current user and are visible at any time.', 'Modules: Navigation', 'gnetwork' ),
				'pages'       => $this->get_general_pages(),
			],
			'loggedin' => [
				'label'       => _x( 'Logged-In', 'Modules: Navigation: Tabs', 'gnetwork' ),
				'description' => _x( '<em>Logged-In</em> links are relative to the current user, and are not visible to visitors who are not logged in.', 'Modules: Navigation', 'gnetwork' ),
				'pages'       => $this->get_loggedin_pages(),
			],
			'loggedout' => [
				'label'       => _x( 'Logged-Out', 'Modules: Navigation: Tabs', 'gnetwork' ),
				'description' => _x( '<em>Logged-Out</em> links are not visible to users who are logged in.', 'Modules: Navigation', 'gnetwork' ),
				'pages'       => $this->get_loggedout_pages(),
			],
		];

		if ( is_multisite() ) {
			$tabs['sites'] = [
				'label'       => _x( 'Sites', 'Modules: Navigation: Tabs', 'gnetwork' ),
				'description' => _x( '<em>Sites</em> on this network within your access.', 'Modules: Navigation', 'gnetwork' ),
				'pages'       => $this->get_sites_pages(),
			];
		}

		echo '<div id="'.$id.'" class="gnetwork-admin-wrap-metabox -navigation posttypediv">';

			foreach ( $tabs as $group => $items ) {

				Settings::fieldSection( $items['label'], $items['description'], 'h4' );

				echo '<div id="tabs-panel-posttype-'.$type.'-'.$group.'" class="tabs-panel tabs-panel-active">';
					echo '<ul id="'.$id.'-checklist-'.$group.'" class="categorychecklist form-no-clear">';
					echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $items['pages'] ), 0, (object) $args );
				echo '</ul></div>';
			}

			$this->add_to_menu_button( $id );

		echo '</div>';

		Scripts::enqueueScript( 'admin.nav-menus' );
	}

	private function add_to_menu_button( $id )
	{
		echo '<p class="button-controls"><span class="add-to-menu">';
			echo '<input type="submit"';
				if ( function_exists( 'wp_nav_menu_disabled_check' ) )
					wp_nav_menu_disabled_check( $GLOBALS['nav_menu_selected_id'] );
			echo ' class="button-secondary submit-add-to-menu right" value="';
				echo esc_attr_x( 'Add to Menu', 'Modules: Navigation', 'gnetwork' );
			echo '" name="add-custom-menu-item" id="submit-'.$id.'" />';
		echo '<span class="spinner"></span></span></p>';
	}

	public function get_general_pages()
	{
		$items   = [];
		$default = get_default_feed();

		$items[] = [
			'name' => _x( 'Default Posts Feed', 'Modules: Navigation', 'gnetwork' ),
			'slug' => 'posts_feed',
			'link' => get_feed_link( $default ),
		];

		$items[] = [
			'name' => _x( 'Default Comments Feed', 'Modules: Navigation', 'gnetwork' ),
			'slug' => 'comments_feed',
			'link' => get_feed_link( 'comments_'.$default ),
		];

		return $this->decorate_items( $this->filters( 'general_items', $items ) );
	}

	public function get_loggedin_pages()
	{
		$items = [];

		$items[] = [
			'name' => _x( 'Log Out', 'Modules: Navigation', 'gnetwork' ),
			'slug' => 'logout',
			'link' => $this->get_logout_url(),
		];

		$items[] = [
			'name' => _x( 'Edit Profile', 'Modules: Navigation', 'gnetwork' ),
			'slug' => 'edit_profile',
			'link' => $this->get_edit_profile_url(),
		];

		$items[] = [
			'name' => _x( 'Public Profile', 'Modules: Navigation', 'gnetwork' ),
			'slug' => 'public_profile',
			'link' => $this->get_public_profile_url(),
		];

		return $this->decorate_items( $this->filters( 'loggedin_items', $items ) );
	}

	public function get_loggedout_pages()
	{
		$items = [];

		$items[] = [
			'name' => _x( 'Log In', 'Modules: Navigation', 'gnetwork' ),
			'slug' => 'login',
			'link' => $this->get_login_url(),
		];

		if ( $register_url = $this->get_register_url() )
			$items[] = [
				'name' => _x( 'Register', 'Modules: Navigation', 'gnetwork' ),
				'slug' => 'register',
				'link' => $register_url,
			];

		return $this->decorate_items( $this->filters( 'loggedout_items', $items ) );
	}

	public function get_sites_pages()
	{
		$items = [];
		$admin = WordPress::isSuperAdmin();
		$sites = WordPress::getAllSites( ( $admin ? FALSE : get_current_user_id() ), $admin );

		foreach ( $sites as $site ) {

			if ( ! $name = WordPress::getSiteName( $site->userblog_id ) )
				$name = URL::untrail( $site->domain.$site->path );

			$items[] = [
				'name' => $name,
				'slug' => 'site-'.$site->userblog_id,
				'link' => URL::trail( $site->siteurl ),
			];
		}

		return $this->decorate_items( $this->filters( 'sites_items', $items ) );
	}

	private function decorate_items( $items )
	{
		$objects = [];

		foreach ( $items as $item )
			$objects[$item['slug']] = (object) [
				'label'          => isset( $item['label'] ) ? $item['label'] : Text::trimChars( $item['name'], 60 ),
				'ID'             => -1,
				'post_title'     => $item['name'],
				'post_author'    => 0,
				'post_date'      => 0,
				'post_excerpt'   => $item['slug'],
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'guid'           => $item['link']
			];

		return $objects;
	}

	public function get_item_url( $slug )
	{
		$items = $this->get_loggedin_pages();

		return isset( $items[$slug] ) ? $items[$slug]->guid : '';
	}

	// @SOURCE: `bp_setup_nav_menu_item()`
	public function wp_setup_nav_menu_item( $menu_item )
	{
		if ( empty( $menu_item->classes ) )
			return $menu_item;

		$classes = $menu_item->classes;

		if ( is_array( $classes ) )
			$classes = implode( ' ', $menu_item->classes );

		// we use information stored in the CSS class to determine
		// what kind of menu item this is, and how it should be treated
		$css_target = preg_match( '/\sgnetwork-(.*)-nav/', $classes, $matches );

		// if this isn't our menu item, we can stop here
		if ( empty( $matches[1] ) )
			return $menu_item;

		switch ( $matches[1] ) {

			case 'login':

				if ( is_user_logged_in() )
					$menu_item->_invalid = TRUE;

				else
					$menu_item->url = $this->get_login_url();

			break;
			case 'logout':

				if ( ! is_user_logged_in() )
					$menu_item->_invalid = TRUE;

				else
					$menu_item->url = $this->get_logout_url();

			break;
			case 'register':

				if ( is_user_logged_in() )
					$menu_item->_invalid = TRUE;

			break;
			case 'edit_profile':

				if ( is_user_logged_in() )
					$menu_item->url = $this->get_edit_profile_url();

				else
					$menu_item->_invalid = TRUE;

			break;
			case 'public_profile':

				if ( is_user_logged_in() )
					$menu_item->url = $this->get_public_profile_url();

				else
					$menu_item->_invalid = TRUE;

			break;
			case 'posts_feed':

				if ( class_exists( __NAMESPACE__.'\\Restricted' ) && Restricted::isEnabled() )
					WordPress::doNotCache();

				$menu_item->url = get_feed_link();

			break;
			case 'comments_feed':

				if ( class_exists( __NAMESPACE__.'\\Restricted' ) && Restricted::isEnabled() )
					WordPress::doNotCache();

				$menu_item->url = get_feed_link( 'comments_'.get_default_feed() );

			break;
			default:

				// network sites
				if ( $menu_item->url && Text::start( $matches[1], 'site-' ) )
					break;

				// via filter customs
				if ( $menu_item->url && Text::start( $matches[1], 'custom-' ) )
					break;

				// all other nav items are specific to the logged-in user,
				// and so are not relevant to logged-out users

				if ( is_user_logged_in() )
					$menu_item->url = $this->get_item_url( $matches[1] );
				else
					$menu_item->_invalid = TRUE;
		}

		if ( empty( $menu_item->url ) ) {

			// if component is deactivated, make sure menu item doesn't render
			$menu_item->_invalid = TRUE;

		} else if ( FALSE !== strpos( URL::current(), $menu_item->url ) ) {

			// highlight the current page
			if ( is_array( $menu_item->classes ) ) {
				$menu_item->classes[] = 'current_page_item';
				$menu_item->classes[] = 'current-menu-item';
			} else {
				$menu_item->classes = [
					'current_page_item',
					'current-menu-item',
				];
			}
		}

		return $menu_item;
	}

	public function wp_nav_menu_items( $items, $args )
	{
		$current = URL::current();

		foreach ( $this->filters( 'replace_nav_menu', [], $current ) as $pattern => $replacement )
			$items = preg_replace( $pattern, sprintf( $replacement, urlencode( $current ) ), $items );

		return $items;
	}

	private function get_register_url()
	{
		return $this->filters( 'register_url', WordPress::registerURL() );
	}

	// FIXME: check if not caching then add redirect arg
	// @SEE: `wp_using_ext_object_cache()`
	private function get_login_url()
	{
		return $this->filters( 'login_url', WordPress::loginURL() );
	}

	// FIXME: check if not caching then add redirect arg
	// @SEE: `wp_using_ext_object_cache()`
	private function get_logout_url()
	{
		return $this->filters( 'logout_url', WordPress::loginURL( '', TRUE ) );
	}

	private function get_edit_profile_url()
	{
		return $this->filters( 'edit_profile_url', get_edit_profile_url() );
	}

	private function get_public_profile_url()
	{
		return $this->filters( 'public_profile_url', get_edit_profile_url() );
	}

	public function wp_setup_nav_menu_item_children( $menu_item )
	{
		$menu_item->children = get_post_meta( $menu_item->ID, '_'.$this->hook( 'children' ), TRUE );

		return $menu_item;
	}

	public function wp_update_nav_menu_item_children( $menu_id, $menu_item_db_id, $args )
	{
		if ( empty( $_REQUEST['menu-item-children'][$menu_item_db_id] ) )
			delete_post_meta( $menu_item_db_id, '_'.$this->hook( 'children' ) );
		else
			update_post_meta( $menu_item_db_id, '_'.$this->hook( 'children' ), 1 );
	}

	// @REF: https://make.wordpress.org/core/2020/02/25/wordpress-5-4-introduces-new-hooks-to-add-custom-fields-to-menu-items/
	public function wp_nav_menu_item_custom_fields_children( $item_id, $item, $depth, $args, $id )
	{
		if ( ! in_array( $item->type, [ 'post_type', 'taxonomy' ], TRUE ) )
			return;

		if ( 'post_type' == $item->type && ! WPPostType::object( $item->object )->hierarchical )
			return;

		if ( 'taxonomy' == $item->type && ! WPTaxonomy::object( $item->object )->hierarchical )
			return;

		echo '<fieldset class="description description-wide"><label for="edit-menu-item-children-'.$item_id.'">';
			echo '<input type="checkbox" id="edit-menu-item-children-'.$item_id.'" value="1" name="menu-item-children['.$item_id.']"'.checked( $item->children, TRUE, FALSE ).' /> ';
			_ex( 'Include Children as Sub-menu', 'Modules: Navigation', 'gnetwork' );
		echo '</label></fieldset>';
	}

	public function wp_get_nav_menu_items_children( $items, $menu, $args )
	{
		$children = [];

		foreach ( $items as $item_key => $item ) {

			if ( empty( $item->children ) )
				continue;

			if ( 'post_type' === $item->type )
				$children = array_merge( $children, $this->_get_page_children( $item ) );

			else if ( 'taxonomy' === $item->type )
				$children = array_merge( $children, $this->_get_term_children( $item ) );
		}

		return array_merge( $items, $children );
	}

	private function _get_page_children( $item )
	{
		$i     = 1;
		$list  = [];
		$pages = get_pages( [
			'child_of'  => $item->object_id,
			'post_type' => $item->object,
			'order'     => 'ASC',
			'orderby'   => 'menu_order',
		] );

		foreach ( $pages as $page ) {

			$list[] = (object) [
				'menu_item_parent' => $page->post_parent == $item->object_id ? $item->ID : $page->post_parent,
				'object_id'        => $page->ID,
				'object'           => $page->post_type,
				'type'             => 'post_type',
				'url'              => get_page_link( $page ),
				'title'            => get_the_title( $page ),
				'classes'          => [],
				'menu_order'       => $i * $item->object_id, // most importante!
				'target'           => '',
				'xfn'              => '',
				'ID'               => $page->ID,
				'db_id'            => $page->ID,
				'post_parent'      => $page->post_parent,
			];

			$i++;
		}

		return $list;
	}

	private function _get_term_children( $item )
	{
		$i     = 1;
		$list  = [];
		$terms = WPTaxonomy::listTerms( $item->object, 'all', [
			'include'    => get_term_children( $item->object_id, $item->object ),
			'hide_empty' => TRUE, // TODO: make this optional
		] );

		foreach ( $terms as $term ) {

			$list[] = (object) [
				'menu_item_parent' => $term->parent == $item->object_id ? $item->ID : $term->parent,
				'object_id'        => $term->term_id,
				'object'           => $term->taxonomy,
				'type'             => 'taxonomy',
				'url'              => get_term_link( $term ),
				'title'            => sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
				'classes'          => [],
				'menu_order'       => $i * $item->object_id, // most importante!
				'target'           => '',
				'xfn'              => '',
				'ID'               => $term->term_id,
				'db_id'            => $term->term_id,
				'post_parent'      => $term->parent,
			];

			$i++;
		}

		return $list;
	}

	public static function getGlobalMenu( $name, $items = TRUE )
	{
		$menu = FALSE;

		if ( ! $name )
			return $menu;

		$key = static::BASE.'_'.$name.( $items ? '' : '_html' );

		if ( WordPress::isFlush() && is_main_site() )
			update_network_option( NULL, $key, '' );

		else if ( $menu = get_network_option( NULL, $key, NULL ) )
			return $menu;

		// bail because previously no menu found
		// and '0' stored to prevent unnecessary checks
		if ( '0' === $menu )
			return $menu;

		if ( is_main_site() ) {

			// only saved location menus
			$locations = get_nav_menu_locations();

			if ( array_key_exists( $name, $locations ) ) {

				$term = get_term( (int) $locations[$name], 'nav_menu' );

				if ( $term && ! self::isError( $term ) ) {

					if ( $items ) {

						$results = [];
						$objects = wp_get_nav_menu_items( $term->term_id, [ 'update_post_term_cache' => FALSE ] );

						// stripping non-essentials
						foreach ( $objects as $object ) {
							$results[] = (object) self::atts( [
								'ID'               => NULL,
								'target'           => NULL,
								'menu_item_parent' => NULL,
								'title'            => '',
								'url'              => '',
								'xfn'              => '', // used for access cap
								'attr_title'       => '',
								'classes'          => [],
							], get_object_vars( $object ) );
						}

						$menu = count( $results ) ? $results : FALSE;

					} else {

						$classes = [ 'menu', 'network-menu', '-print-hide' ];
						$classes = apply_filters( self::BASE.'_navigation_globalmenu_class', $classes, $term );

						$results = wp_nav_menu( [
							'menu'         => $term->term_id,
							'menu_id'      => $name,
							'menu_class'   => HTML::prepClass( $classes ),
							'container'    => '',
							'item_spacing' => 'discard',
							'fallback_cb'  => FALSE,
							'echo'         => FALSE,
						] );

						$menu = $results ? Text::minifyHTML( $results ) : FALSE;
					}
				}
			}

			if ( $menu ) {
				update_network_option( NULL, $key, $menu );
				return $menu;
			}
		}

		update_network_option( NULL, $key, '0' );
		return FALSE;
	}
}

// @SOURCE: `BP_Walker_Nav_Menu_Checklist`
class Walker_Nav_Menu_Checklist extends \Walker_Nav_Menu
{

	public function __construct( $fields = FALSE )
	{
		if ( $fields )
			$this->db_fields = $fields;
	}

	public function start_lvl( &$output, $depth = 0, $args = [] )
	{
		$indent = str_repeat( "\t", $depth );
		$output.= "\n$indent<ul class='children'>\n";
	}

	public function end_lvl( &$output, $depth = 0, $args = [] )
	{
		$indent = str_repeat( "\t", $depth );
		$output.= "\n$indent</ul>";
	}

	public function start_el( &$output, $item, $depth = 0, $args = [], $id = 0 )
	{
		global $_nav_menu_placeholder;

		$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? ( (int) $_nav_menu_placeholder ) - 1 : -1;
		$possible_object_id = isset( $item->post_type ) && 'nav_menu_item' == $item->post_type ? $item->object_id : $_nav_menu_placeholder;
		$possible_db_id = ( ! empty( $item->ID ) ) && ( 0 < $possible_object_id ) ? (int) $item->ID : 0;

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$output.= $indent.'<li>';
		$output.= '<label class="menu-item-title">';
		$output.= '<input type="checkbox" class="menu-item-checkbox';

		if ( property_exists( $item, 'label' ) ) {
			$title = $item->label;
		}

		$output.= '" name="menu-item['.$possible_object_id.'][menu-item-object-id]" value="'.esc_attr( $item->object_id ).'" /> ';
		$output.= isset( $title ) ? esc_html( $title ) : esc_html( $item->title );
		$output.= '</label>';

		if ( empty( $item->url ) ) {
			$item->url = $item->guid;
		}

		if ( ! in_array( [ 'gnetwork-menu', 'gnetwork-'.$item->post_excerpt.'-nav' ], $item->classes ) ) {
			$item->classes[] = 'gnetwork-menu';
			$item->classes[] = 'gnetwork-'. $item->post_excerpt .'-nav';
		}

		// menu item hidden fields
		$output.= '<input type="hidden" class="menu-item-db-id" name="menu-item['.$possible_object_id.'][menu-item-db-id]" value="'.$possible_db_id.'" />';
		$output.= '<input type="hidden" class="menu-item-object" name="menu-item['.$possible_object_id.'][menu-item-object]" value="'.esc_attr( $item->object ).'" />';
		$output.= '<input type="hidden" class="menu-item-parent-id" name="menu-item['.$possible_object_id.'][menu-item-parent-id]" value="'.esc_attr( $item->menu_item_parent ).'" />';
		$output.= '<input type="hidden" class="menu-item-type" name="menu-item['.$possible_object_id.'][menu-item-type]" value="custom" />';
		$output.= '<input type="hidden" class="menu-item-title" name="menu-item['.$possible_object_id.'][menu-item-title]" value="'.esc_attr( $item->title ).'" />';
		$output.= '<input type="hidden" class="menu-item-url" name="menu-item['.$possible_object_id.'][menu-item-url]" value="'.esc_attr( $item->url ).'" />';
		$output.= '<input type="hidden" class="menu-item-target" name="menu-item['.$possible_object_id.'][menu-item-target]" value="'.esc_attr( $item->target ).'" />';
		$output.= '<input type="hidden" class="menu-item-attr_title" name="menu-item['.$possible_object_id.'][menu-item-attr_title]" value="'.esc_attr( $item->attr_title ).'" />';
		$output.= '<input type="hidden" class="menu-item-classes" name="menu-item['.$possible_object_id.'][menu-item-classes]" value="'.esc_attr( implode( ' ', $item->classes ) ).'" />';
		$output.= '<input type="hidden" class="menu-item-xfn" name="menu-item['.$possible_object_id.'][menu-item-xfn]" value="'.esc_attr( $item->xfn ).'" />';
	}
}

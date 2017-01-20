<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Navigation extends ModuleCore
{

	protected $key     = 'navigation';
	protected $network = FALSE;

	private $restricted = FALSE;   // restricted support
	private $feeds      = array(); // restricted support

	private $general_pages   = array();
	private $loggedout_pages = array();
	private $loggedin_pages  = array();

	protected function setup_actions()
	{
		if ( is_admin() ) {
			add_action( 'load-nav-menus.php', array( $this, 'load_nav_menus_php' ) );
		} else {
			$this->filter( 'wp_setup_nav_menu_item' );
			$this->filter( 'wp_nav_menu_items', 2, 20 );
		}
	}

	public function init()
	{
		// restricted support
		if ( class_exists( __NAMESPACE__.'\\Restricted' ) ) {
			$this->restricted = Restricted::is();
			$this->feeds      = Restricted::getFeeds( FALSE, $this->restricted );
		}
	}

	public function load_nav_menus_php()
	{
		add_meta_box( 'add-gnetwork-nav-menu',
			_x( 'Network', 'Modules: Navigation: Meta Box Title', GNETWORK_TEXTDOMAIN ),
			array( $this, 'nav_menu_meta_box' ),
			'nav-menus',
			'side',
			'default' );

		add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ) );
	}

	// build and populate the accordion on Appearance > Menus
	// @SOURCE: `bp_admin_do_wp_nav_menu_meta_box()`
	public function nav_menu_meta_box()
	{
		global $nav_menu_selected_id;

		$post_type_name = 'gnetworknav';
		$args = array( 'walker' => new Walker_Nav_Menu_Checklist( FALSE ) );

		$tabs = array(
			'general' => array(
				'label'       => _x( 'General', 'Modules: Navigation: Tabs', GNETWORK_TEXTDOMAIN ),
				'description' => _x( '<em>General</em> links are relative to the current user and are visible at any time.', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
				'pages'       => $this->get_general_pages(),
			),
			'loggedin' => array(
				'label'       => _x( 'Logged-In', 'Modules: Navigation: Tabs', GNETWORK_TEXTDOMAIN ),
				'description' => _x( '<em>Logged-In</em> links are relative to the current user, and are not visible to visitors who are not logged in.', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
				'pages'       => $this->get_loggedin_pages(),
			),
			'loggedout' => array(
				'label'       => _x( 'Logged-Out', 'Modules: Navigation: Tabs', GNETWORK_TEXTDOMAIN ),
				'description' => _x( '<em>Logged-Out</em> links are not visible to users who are logged in.', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
				'pages'       => $this->get_loggedout_pages(),
			),
		);

		echo '<div id="gnetwork-menu" class="gnetwork-admin-wrap-metabox -navigation posttypediv">';

			foreach ( $tabs as $group => $items ) {

				Settings::fieldSection( $items['label'], $items['description'], 'h4' );

				echo '<div id="tabs-panel-posttype-'.$post_type_name.'-'.$group.'" class="tabs-panel tabs-panel-active">';
					echo '<ul id="gnetwork-menu-checklist-'.$group.'" class="categorychecklist form-no-clear">';
					echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $items['pages'] ), 0, (object) $args );
				echo '</ul></div>';
			}

			echo '<p class="button-controls"><span class="add-to-menu">';
				echo '<input type="submit"';
					if ( function_exists( 'wp_nav_menu_disabled_check' ) )
						wp_nav_menu_disabled_check( $nav_menu_selected_id );
				echo ' class="button-secondary submit-add-to-menu right" value="';
					echo esc_attr_x( 'Add to Menu', 'Modules: Navigation', GNETWORK_TEXTDOMAIN );
				echo '" name="add-custom-menu-item" id="submit-gnetwork-menu" />';
			echo '<span class="spinner"></span></span></p>';

		echo '</div>';
	}

	public function admin_print_footer_scripts()
	{
		?><script type="text/javascript">
		jQuery( '#menu-to-edit').on( 'click', 'a.item-edit', function() {
			var settings  = jQuery(this).closest( '.menu-item-bar' ).next( '.menu-item-settings' );
			var css_class = settings.find( '.edit-menu-item-classes' );

			if( css_class.val().indexOf( 'gnetwork-menu' ) === 0 ) {
				css_class.attr( 'readonly', 'readonly' );
				settings.find( '.field-url' ).css( 'display', 'none' );
			}
		});
		</script><?php
	}

	public function get_general_pages()
	{
		if ( count( $this->general_pages ) )
			return $this->general_pages;

		$items = array();

		$items[] = array(
			'name' => _x( 'RSS Feed', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
			'slug' => 'feed',
			'link' => get_feed_link( 'rss2' ),
		);

		$items[] = array(
			'name' => _x( 'RSS Comments Feed', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
			'slug' => 'comments_feed',
			'link' => get_feed_link( 'comments_rss2' ),
		);

		$items = $this->filters( 'general_items', $items );

		foreach ( $items as $item ) {
			$this->general_pages[ $item['slug'] ] = (object) array(
				'ID'             => -1,
				'post_title'     => $item['name'],
				'post_author'    => 0,
				'post_date'      => 0,
				'post_excerpt'   => $item['slug'],
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'guid'           => $item['link']
			);
		}

		return $this->general_pages;
	}

	public function get_loggedin_pages()
	{
		if ( count( $this->loggedin_pages ) )
			return $this->loggedin_pages;

		$items = array();

		$items[] = array(
			'name' => _x( 'Log Out', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
			'slug' => 'logout',
			'link' => $this->get_logout_url(),
		);

		$items[] = array(
			'name' => _x( 'Edit Profile', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
			'slug' => 'edit_profile',
			'link' => $this->get_edit_profile_url(),
		);

		$items[] = array(
			'name' => _x( 'Public Profile', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
			'slug' => 'public_profile',
			'link' => $this->get_public_profile_url(),
		);

		$items = $this->filters( 'loggedin_items', $items );

		foreach ( $items as $item ) {
			$this->loggedin_pages[ $item['slug'] ] = (object) array(
				'ID'             => -1,
				'post_title'     => $item['name'],
				'post_author'    => 0,
				'post_date'      => 0,
				'post_excerpt'   => $item['slug'],
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'guid'           => $item['link']
			);
		}

		return $this->loggedin_pages;
	}

	public function get_loggedout_pages()
	{
		if ( count( $this->loggedout_pages ) )
			return $this->loggedout_pages;

		$items = array();

		$items[] = array(
			'name' => _x( 'Log In', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
			'slug' => 'login',
			'link' => $this->get_login_url(),
		);

		if ( $register_url = $this->get_register_url() )
			$items[] = array(
				'name' => _x( 'Register', 'Modules: Navigation', GNETWORK_TEXTDOMAIN ),
				'slug' => 'register',
				'link' => $register_url,
			);

		$items = $this->filters( 'loggedout_items', $items );

		foreach ( $items as $item ) {
			$this->loggedout_pages[ $item['slug'] ] = (object) array(
				'ID'             => -1,
				'post_title'     => $item['name'],
				'post_author'    => 0,
				'post_date'      => 0,
				'post_excerpt'   => $item['slug'],
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'guid'           => $item['link']
			);
		}

		return $this->loggedout_pages;
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
			case 'feed':

				if ( $this->restricted ) {
					WordPress::doNotCache();
					$menu_item->url = $this->feeds['rss2'];
				}

			break;
			case 'comments_feed':

				if ( $this->restricted ) {
					WordPress::doNotCache();
					$menu_item->url = $this->feeds['comments_rss2_url'];
				}

			break;
			default:

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
				$menu_item->classes = array(
					'current_page_item',
					'current-menu-item',
				);
			}
		}

		return $menu_item;
	}

	public function wp_nav_menu_items( $items, $args )
	{
		$current = URL::current();

		foreach ( $this->filters( 'replace_nav_menu', array(), $current ) as $pattern => $replacement )
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
		return $this->filters( 'login_url', wp_login_url() );
	}

	// FIXME: check if not caching then add redirect arg
	// @SEE: `wp_using_ext_object_cache()`
	private function get_logout_url()
	{
		return $this->filters( 'logout_url', remove_query_arg( '_wpnonce', wp_logout_url() ) );
	}

	private function get_edit_profile_url()
	{
		return $this->filters( 'edit_profile_url', get_edit_profile_url() );
	}

	private function get_public_profile_url()
	{
		return $this->filters( 'public_profile_url', get_edit_profile_url() );
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

	public function start_lvl( &$output, $depth = 0, $args = array() )
	{
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent<ul class='children'>\n";
	}

	public function end_lvl( &$output, $depth = 0, $args = array() )
	{
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent</ul>";
	}

	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 )
	{
		global $_nav_menu_placeholder;

		$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? intval($_nav_menu_placeholder) - 1 : -1;
		$possible_object_id = isset( $item->post_type ) && 'nav_menu_item' == $item->post_type ? $item->object_id : $_nav_menu_placeholder;
		$possible_db_id = ( ! empty( $item->ID ) ) && ( 0 < $possible_object_id ) ? (int) $item->ID : 0;

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$output .= $indent . '<li>';
		$output .= '<label class="menu-item-title">';
		$output .= '<input type="checkbox" class="menu-item-checkbox';

		if ( property_exists( $item, 'label' ) ) {
			$title = $item->label;
		}

		$output .= '" name="menu-item['.$possible_object_id.'][menu-item-object-id]" value="'. esc_attr( $item->object_id ) .'" /> ';
		$output .= isset( $title ) ? esc_html( $title ) : esc_html( $item->title );
		$output .= '</label>';

		if ( empty( $item->url ) ) {
			$item->url = $item->guid;
		}

		if ( ! in_array( array( 'gnetwork-menu', 'gnetwork-'. $item->post_excerpt .'-nav' ), $item->classes ) ) {
			$item->classes[] = 'gnetwork-menu';
			$item->classes[] = 'gnetwork-'. $item->post_excerpt .'-nav';
		}

		// menu item hidden fields
		$output .= '<input type="hidden" class="menu-item-db-id" name="menu-item['.$possible_object_id.'][menu-item-db-id]" value="'.$possible_db_id.'" />';
		$output .= '<input type="hidden" class="menu-item-object" name="menu-item['.$possible_object_id.'][menu-item-object]" value="'. esc_attr( $item->object ) .'" />';
		$output .= '<input type="hidden" class="menu-item-parent-id" name="menu-item['.$possible_object_id.'][menu-item-parent-id]" value="'. esc_attr( $item->menu_item_parent ) .'" />';
		$output .= '<input type="hidden" class="menu-item-type" name="menu-item['.$possible_object_id.'][menu-item-type]" value="custom" />';
		$output .= '<input type="hidden" class="menu-item-title" name="menu-item['.$possible_object_id.'][menu-item-title]" value="'. esc_attr( $item->title ) .'" />';
		$output .= '<input type="hidden" class="menu-item-url" name="menu-item['.$possible_object_id.'][menu-item-url]" value="'. esc_attr( $item->url ) .'" />';
		$output .= '<input type="hidden" class="menu-item-target" name="menu-item['.$possible_object_id.'][menu-item-target]" value="'. esc_attr( $item->target ) .'" />';
		$output .= '<input type="hidden" class="menu-item-attr_title" name="menu-item['.$possible_object_id.'][menu-item-attr_title]" value="'. esc_attr( $item->attr_title ) .'" />';
		$output .= '<input type="hidden" class="menu-item-classes" name="menu-item['.$possible_object_id.'][menu-item-classes]" value="'. esc_attr( implode( ' ', $item->classes ) ) .'" />';
		$output .= '<input type="hidden" class="menu-item-xfn" name="menu-item['.$possible_object_id.'][menu-item-xfn]" value="'. esc_attr( $item->xfn ) .'" />';
	}
}

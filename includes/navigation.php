<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkNavigation extends gNetworkModuleCore
{

	var $_option_key = FALSE;
	var $_network    = TRUE;

	var $_restricted = FALSE;   // restricted support
	var $_feeds      = array(); // restricted support

	var $_general_pages   = array();
	var $_loggedout_pages = array();
	var $_loggedin_pages  = array();

	protected function setup_actions()
	{
		if ( is_admin() ) {
			add_action( 'load-nav-menus.php', array( &$this, 'load_nav_menus_php' ) );
		} else {
			add_filter( 'wp_setup_nav_menu_item', array( &$this, 'wp_setup_nav_menu_item' ) );
		}
	}

	public function init()
	{
		// restricted support
		if ( class_exists( 'gNetworkRestricted' ) ) {
			$this->_restricted = gNetworkRestricted::is();
			$this->_feeds      = gNetworkRestricted::getFeeds( FALSE, $this->_restricted );
		}
	}

	public function load_nav_menus_php()
	{
		add_meta_box( 'add-gnetwork-nav-menu',
			__( 'Network', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'nav_menu_meta_box' ),
			'nav-menus',
			'side',
			'default' );

		add_action( 'admin_print_footer_scripts', array( &$this, 'admin_print_footer_scripts' ) );
	}

	// Build and populate the accordion on Appearance > Menus.
	public function nav_menu_meta_box()
	{
		global $nav_menu_selected_id;

		$post_type_name = 'gnetworknav';
		$args = array( 'walker' => new gNetwork_Walker_Nav_Menu_Checklist( FALSE ) );

		$tabs = array(
			'general' => array(
				'label' => _x( 'General', 'Navigation: Tabs', GNETWORK_TEXTDOMAIN ),
				'desc'  => __( '<em>General</em> links are relative to the current user and are visible at any time.', GNETWORK_TEXTDOMAIN ),
				'pages' => $this->get_general_pages(),
			),
			'loggedin' => array(
				'label' => _x( 'Logged-In', 'Navigation: Tabs', GNETWORK_TEXTDOMAIN ),
				'desc'  => __( '<em>Logged-In</em> links are relative to the current user, and are not visible to visitors who are not logged in.', GNETWORK_TEXTDOMAIN ),
				'pages' => $this->get_loggedin_pages(),
			),
			'loggedout' => array(
				'label' => _x( 'Logged-Out', 'Navigation: Tabs', GNETWORK_TEXTDOMAIN ),
				'desc'  => __( '<em>Logged-Out</em> links are not visible to users who are logged in.', GNETWORK_TEXTDOMAIN ),
				'pages' => $this->get_loggedout_pages(),
			),
		);

		echo '<div id="gnetwork-menu" class="posttypediv">';

			foreach ( $tabs as $group => $items ) {
				printf( '<h4>%s</h4><p>%s</p>', $items['label'], $items['desc'] );
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
					esc_attr_e( 'Add to Menu', GNETWORK_TEXTDOMAIN );
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
		if ( count( $this->_general_pages ) )
			return $this->_general_pages;

		$items = array();

		$items[] = array(
			'name' => __( 'RSS Feed', GNETWORK_TEXTDOMAIN ),
			'slug' => 'feed',
			'link' => get_feed_link( 'rss2' ),
		);

		$items[] = array(
			'name' => __( 'RSS Comments Feed', GNETWORK_TEXTDOMAIN ),
			'slug' => 'comments_feed',
			'link' => get_feed_link( 'comments_rss2' ),
		);

		$items = apply_filters( 'gnetwork_navigation_general_items', $items );

		foreach ( $items as $item ) {
			$this->_general_pages[ $item['slug'] ] = (object) array(
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

		return $this->_general_pages;
	}

	public function get_loggedin_pages()
	{
		if ( count( $this->_loggedin_pages ) )
			return $this->_loggedin_pages;

		$items = array();

		$items[] = array(
			'name' => __( 'Log Out', GNETWORK_TEXTDOMAIN ),
			'slug' => 'logout',
			'link' => apply_filters( 'gnetwork_navigation_logout_url', wp_logout_url() ),
		);

		$items[] = array(
			'name' => __( 'Edit Profile', GNETWORK_TEXTDOMAIN ),
			'slug' => 'edit_profile',
			'link' => get_edit_profile_url(),
		);

		$items[] = array(
			'name' => __( 'Public Profile', GNETWORK_TEXTDOMAIN ),
			'slug' => 'profile',
			'link' => apply_filters( 'gnetwork_navigation_public_profile_url', get_edit_profile_url() ),
		);

		$items = apply_filters( 'gnetwork_navigation_loggedin_items', $items );

		foreach ( $items as $item ) {
			$this->_loggedin_pages[ $item['slug'] ] = (object) array(
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

		return $this->_loggedin_pages;
	}

	public function get_loggedout_pages()
	{
		if ( count( $this->_loggedout_pages ) )
			return $this->_loggedout_pages;

		$items = array();

		$items[] = array(
			'name' => __( 'Log In', GNETWORK_TEXTDOMAIN ),
			'slug' => 'login',
			'link' => wp_login_url(),
		);

		if ( $register_url = gNetworkUtilities::register_url() )
			$items[] = array(
				'name' => __( 'Register', GNETWORK_TEXTDOMAIN ),
				'slug' => 'register',
				'link' => $register_url,
			);

		$items = apply_filters( 'gnetwork_navigation_loggedout_items', $items );

		foreach ( $items as $item ) {
			$this->_loggedout_pages[ $item['slug'] ] = (object) array(
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

		return $this->_loggedout_pages;
	}

	public function get_item_url( $slug )
	{
		$nav_item_url   = '';
		$nav_menu_items = $this->get_loggedin_pages();

		if ( isset( $nav_menu_items[ $slug ] ) ) {
			$nav_item_url = $nav_menu_items[ $slug ]->guid;
		}

		return $nav_item_url;
	}

	public function wp_setup_nav_menu_item( $menu_item )
	{
		// we use information stored in the CSS class to determine what kind of menu item this is, and how it should be treated
		$css_target = preg_match( '/\sgnetwork-(.*)-nav/', implode( ' ', $menu_item->classes ), $matches );

		// if this isn't our menu item, we can stop here
		if ( empty( $matches[1] ) )
			return $menu_item;

		switch ( $matches[1] ) {
			case 'login' :
				if ( is_user_logged_in() ) {
					$menu_item->_invalid = TRUE;
					// __donot_cache_page();
				} else {
					// $menu_item->url = wp_login_url( wp_guess_url() );
					$menu_item->url = wp_login_url();
				}

			break;

			case 'logout' :
				if ( ! is_user_logged_in() ) {
					$menu_item->_invalid = TRUE;
				} else {
					// __donot_cache_page();
					// $menu_item->url = wp_logout_url( wp_guess_url() );
					$menu_item->url = apply_filters( 'gnetwork_navigation_logout_url', wp_logout_url() );
				}

			break;

			case 'register' :
				if ( is_user_logged_in() ) { // Don't show the Register link to logged-in users
					$menu_item->_invalid = TRUE;
					// __donot_cache_page();
				}
			break;

			case 'edit_profile' :
				if ( is_user_logged_in() ) {
					// __donot_cache_page();
					$menu_item->url = get_edit_profile_url( get_current_user_id() );
				} else {
					$menu_item->_invalid = TRUE;
				}
			break;

			case 'feed' :
				if ( $this->_restricted )
					__donot_cache_page();
				$menu_item->url = $this->_feeds['rss2'];
			break;

			case 'comments_feed' :
				if ( $this->_restricted )
					__donot_cache_page();
				$menu_item->url = $this->_feeds['comments_rss2_url'];
			break;

			// All other nav items are specific to the logged-in user,
			// and so are not relevant to logged-out users
			default:
				if ( is_user_logged_in() ) {
					// __donot_cache_page();
					$menu_item->url = $this->get_item_url( $matches[1] );
				} else {
					$menu_item->_invalid = TRUE;
				}

				break;
		}

		// If component is deactivated, make sure menu item doesn't render
		if ( empty( $menu_item->url ) ) {
			$menu_item->_invalid = TRUE;

		// Highlight the current page
		} else {
			// $current = bp_get_requested_url();
			// if ( strpos( $current, $menu_item->url ) !== FALSE ) {
			// 	$menu_item->classes[] = 'current_page_item';
			// }
		}

		return $menu_item;
	}
}

class gNetwork_Walker_Nav_Menu_Checklist extends Walker_Nav_Menu
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

		// Menu item hidden fields
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

// Based on BuddyPress!!

// Custom menus (and much more) for logged in WordPress users
// https://gist.github.com/chrisguitarguy/1342471
// IDEA : network wide widget for global menus, like admin bar
// http://codex.wordpress.org/Next_and_Previous_Links
// https://wordpress.org/plugins/next-page/
// https://wordpress.org/plugins/next-page-not-next-post/
// http://teleogistic.net/2013/02/dynamically-add-items-to-a-wp_nav_menu-list/

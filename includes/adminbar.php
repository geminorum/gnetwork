<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkAdminBar extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	var $_sidebar_admin = FALSE;
	var $remove_nodes   = array();

	protected function setup_actions()
	{
		add_action( 'init', array( &$this, 'init' ), 20 );
	}

	public function init()
	{
		if ( TRUE === constant( 'GNETWORK_ADMINBAR' )
			|| current_user_can( constant( 'GNETWORK_ADMINBAR' ) ) ) {

			add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_style' ) );
			add_filter( 'wp_admin_bar_class', 'gnetwork_adminbar_wp_admin_bar_class' );
			add_filter( 'wp_nav_menu_items', array( &$this, 'wp_nav_menu_items' ), 20, 2 );

			add_action( 'wp_before_admin_bar_render', array( &$this, 'wp_before_admin_bar_render' ) );

			if ( is_multisite() && is_super_admin() )
				add_action( 'admin_bar_init', array( &$this, 'admin_bar_init_allblogs' ) );

			if ( is_main_site() )
				add_action( 'save_post_nav_menu_item', array( &$this, 'save_post_nav_menu_item' ), 10, 2 );

			if ( is_admin() ) {
				$this->wp_enqueue_style();

				add_action( 'sidebar_admin_setup', array( &$this, 'sidebar_admin_setup' ) );

			} else {
				// http://wordpress.org/plugins/reenable-shortlink-item-in-admin-toolbar/
				if ( is_singular() && ! is_home() )
					add_action( 'admin_bar_menu', 'wp_admin_bar_shortlink_menu', 90 );
			}
		} else {
			show_admin_bar( FALSE );

			// add_action( 'admin_print_scripts-profile.php', function(){
			// 	echo '<style type="text/css">.show-admin-bar{display:none;}</style>';
			// } );
		}
	}

	public function wp_enqueue_style()
	{
		if ( ! is_admin_bar_showing() )
			return;

		if ( file_exists( WP_CONTENT_DIR.'/adminbar.css' ) )
			wp_enqueue_style( 'gnetwork-adminbar', WP_CONTENT_URL.'/adminbar.css', array( 'admin-bar' ), GNETWORK_VERSION );
	}

	public function wp_nav_menu_items( $items, $args )
	{
		// just put REPORTBUG_URL on a menu item url
		// url with %s for current page url : 'blog.salamzaban.com/bug-report?on=%s'
		if ( constant( 'GNETWORK_REPORTBUG_URL' ) )
			$items = preg_replace( '%REPORTBUG_URL%',
				sprintf( constant( 'GNETWORK_REPORTBUG_URL' ),
					urlencode( gNetworkUtilities::currentURL() ) ), $items );

		return $items;
	}

	// ORIGINALLY FROM : Hyper Admins by scribu
	// http://wordpress.org/extend/plugins/hyper-admins/
	public function admin_bar_init_allblogs()
	{
		global $wpdb;

		$blog_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE site_id = %d
			AND spam = '0'
			AND deleted = '0'
			AND archived = '0'
			ORDER BY registered DESC
		", $wpdb->siteid ) );

		$blogs = array();

		foreach ( $blog_ids as $blog_id ) {
			$blog_id = (int) $blog_id;

			$blog = get_blog_details( $blog_id );
			$blogs[ $blog_id ] = (object) array(
				'userblog_id' => $blog_id,
				'blogname'    => $blog->blogname,
				'domain'      => $blog->domain,
				'path'        => $blog->path,
				'site_id'     => $blog->site_id,
				'siteurl'     => $blog->siteurl,
			);
		}

		$GLOBALS['wp_admin_bar']->user->blogs = $blogs;
	}

	// fires early before the Widgets administration screen loads, after scripts are enqueued.
	public function sidebar_admin_setup()
	{
		if ( isset( $_GET['gnetwork-action'] ) && 'reset-sidebars' == $_GET['gnetwork-action'] ) {
			update_option( 'sidebars_widgets', array() );
			$_SERVER['REQUEST_URI'] = remove_query_arg( 'gnetwork-action', $_SERVER['REQUEST_URI'] );
		}

		$this->_sidebar_admin = TRUE;
	}

	public static function removeMenus( $nodes )
	{
		if ( self::isAJAX() )
			return;

		global $gNetwork;

		foreach ( (array) $nodes as $node )
			$gNetwork->adminbar->remove_nodes[] = $node;
	}

	public function wp_before_admin_bar_render()
	{
		global $wp_admin_bar;

		if ( is_super_admin() ) {

			if ( is_multisite() )
				$this->add_nodes_network( $wp_admin_bar );

			$this->add_nodes( $wp_admin_bar );
		}

		foreach ( $this->remove_nodes as $node )
			$wp_admin_bar->remove_node( $node );
	}

	private function add_nodes( &$wp_admin_bar )
	{
		$current_url = gNetworkUtilities::currentURL();
		$parent_id   = 'gnetwork-info';
		$group_id    = $parent_id.'-sub';

		$wp_admin_bar->add_node( array(
			'id'     => $parent_id,
			'title'  => '<span class="ab-icon dashicons dashicons-performance" style="margin:2px 0 0 0;"></span>',
			'parent' => 'top-secondary',
			'href'   => gNetworkNetwork::settingsURL(),
		) );

		$wp_admin_bar->add_node( array(
			'parent' => $parent_id,
			'id'     => 'gnetwork-debug',
			'title'  => __( 'Debug', GNETWORK_TEXTDOMAIN ),
			'href'   => add_query_arg( 'debug', 'debug', $current_url ),
			'meta'   => array(
				'title' => __( 'Display debug info for the current page', GNETWORK_TEXTDOMAIN ),
			),
		) );

		$wp_admin_bar->add_node( array(
			'parent' => $parent_id,
			'id'     => 'gnetwork-flush',
			'title'  => __( 'Flush', GNETWORK_TEXTDOMAIN ),
			'href'   => add_query_arg( 'flush', '', $current_url ),
			'meta'   => array(
				'title' => __( 'Flush cached data', GNETWORK_TEXTDOMAIN ),
			),
		) );

		$wp_admin_bar->add_group( array(
			'parent' => $parent_id,
			'id'     => $group_id,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		do_action_ref_array( 'gnetwork_adminbar_action', array( &$wp_admin_bar, $parent_id, $group_id, $current_url ) );

		if ( is_admin() ) {

			if ( $this->_sidebar_admin ) {
				$wp_admin_bar->add_node( array(
					'parent' => $group_id,
					'id'     => 'reset-sidebars',
					'title'  => __( 'Reset Sidebars', GNETWORK_TEXTDOMAIN ),
					'href'   => add_query_arg( 'gnetwork-action', 'reset-sidebars', $current_url ),
					'meta'   => array(
						'title' => __( 'Delete all previous sidebar widgets, be careful!', GNETWORK_TEXTDOMAIN ) ,
					),
				) );
			}

		} else {

			if ( current_user_can( 'edit_users' ) ) {

				$wp_admin_bar->add_node( array(
					'parent' => 'site-name',
					'id'     => 'all-users',
					'title'  => __( 'Users', GNETWORK_TEXTDOMAIN ),
					'href'   => admin_url( 'users.php' ),
				) );
			}
		}

		$wp_admin_bar->add_node( array(
			'parent' => $group_id,
			'id'     => 'gnetwork-info-queries',
			'title'  => gNetworkUtilities::stat( '%dq | %.3fs | %.2fMB' ),
			'href'   => gNetworkAdmin::settingsURL(),
			'meta'   => array(
				'title' => __( 'Queries | Timer Stop | Memory Usage', GNETWORK_TEXTDOMAIN ),
			),
		) );

		// TODO: add debug.log file size notice as notification for super admin and linkit to log_viewer
	}

	private function add_nodes_network( & $wp_admin_bar )
	{
		$wp_admin_bar->add_menu( array(
			'parent' => 'network-admin',
			'id'     => 'settings',
			'title'  => __( 'Settings', GNETWORK_TEXTDOMAIN ),
			'href'   => network_admin_url( 'settings.php' )
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'network-admin',
			'id'     => 'update-core',
			'title'  => __( 'Updates', GNETWORK_TEXTDOMAIN ),
			'href'   => network_admin_url( 'update-core.php' )
		) );

		foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {

			$wp_admin_bar->add_menu( array(
				'parent' => 'blog-'.$blog->userblog_id,
				'id'     => 'blog-'.$blog->userblog_id.'-e',
				'title'  => __( 'All Posts', GNETWORK_TEXTDOMAIN ),
				'href'   => get_admin_url( $blog->userblog_id, 'edit.php' ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'blog-'.$blog->userblog_id,
				'id'     => 'blog-'.$blog->userblog_id.'-u',
				'title'  => __( 'Users', GNETWORK_TEXTDOMAIN ),
				'href'   => get_admin_url( $blog->userblog_id, 'users.php' ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'blog-'.$blog->userblog_id,
				'id'     => 'blog-'.$blog->userblog_id.'-o-g',
				'title'  => __( 'Settings', GNETWORK_TEXTDOMAIN ),
				'href'   => get_admin_url( $blog->userblog_id, 'options-general.php' ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'blog-'.$blog->userblog_id,
				'id'     => 'blog-'.$blog->userblog_id.'-i',
				'title'  => __( 'Edit Site', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url( 'site-info.php?id='.$blog->userblog_id ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'blog-'.$blog->userblog_id,
				'id'     => 'blog-'.$blog->userblog_id.'-t',
				'title'  => __( 'Edit Site Themes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url( 'site-themes.php?id='.$blog->userblog_id ),
			) );
		}
	}

	public function save_post_nav_menu_item( $post_id, $post )
	{
		update_site_option( 'gnetwork_'.GNETWORK_NETWORK_ADMINBAR, '' );
		update_site_option( 'gnetwork_'.GNETWORK_NETWORK_EXTRAMENU, '' );

		return $post_id;
	}

	public static function get_network_menu( $name = GNETWORK_NETWORK_ADMINBAR )
	{
		$menu = get_site_option( 'gnetwork_'.$name );

		if ( ! empty( $menu ) )
			return $menu;

		if ( is_main_site() ) {

			$menu = wp_get_nav_menu_items( $name, array( 'update_post_term_cache' => FALSE ) );
			if ( $menu )
				update_site_option( 'gnetwork_'.$name, $menu );

			return $menu;
		}

		return FALSE;
	}

	public static function main_logo( $wp_admin_bar, $id = 'wp-logo', $title = '<span class="ab-icon"></span>' )
	{
		$wp_admin_bar->add_menu( array(
			'id'    => $id,
			'title' => $title,
			'href'  => GNETWORK_BASE,
		) );
	}

	public static function login_register( $wp_admin_bar, $parent = 'wp-logo-external' )
	{
		if ( ! is_admin_bar_showing() )
			return;

		if ( ! is_user_logged_in() ) {
			$wp_admin_bar->add_menu( array(
				'parent'    => $parent,
				'id'        => 'network-login',
				'title'     => __( 'Log in', GNETWORK_TEXTDOMAIN ),
				'href'      => wp_login_url(),
			) );

			$register = gNetworkUtilities::register_url();
			if ( $register )
				$wp_admin_bar->add_menu( array(
					'parent'    => $parent,
					'id'        => 'network-register',
					'title'     => __( 'Register', GNETWORK_TEXTDOMAIN ),
					'href'      => $register,
				) );
		} else {

		}
	}

	public static function search_menu( $wp_admin_bar )
	{
		if ( is_admin()
			|| ! is_admin_bar_showing() )
				return;

		$form  = '<form action="'.GNETWORK_SEARCH_URL.'" method="get" id="adminbarsearch">';
		$form .= '<input class="adminbar-input" name="'.GNETWORK_SEARCH_QUERYID.'" id="adminbar-search" type="text" value="" maxlength="150" />';
		$form .= '<input type="submit" class="adminbar-button" value="'.__( 'Search' ).'"/>';
		$form .= '</form>';

		$wp_admin_bar->add_menu( array(
			'parent' => 'top-secondary',
			'id'     => 'search',
			'title'  => $form,
			'meta'   => array(
				'class'    => 'admin-bar-search',
				'tabindex' => -1,
			)
		) );
	}

	public static function my_sites( &$wp_admin_bar )
	{
		if ( ! is_user_logged_in() || ! is_multisite() )
			return;

		if ( count( $wp_admin_bar->user->blogs ) < 1 && ! is_super_admin() )
			return;

		if ( $wp_admin_bar->user->active_blog ) {
			$my_sites_url = get_admin_url( $wp_admin_bar->user->active_blog->blog_id, 'my-sites.php' );
		} else {
			$my_sites_url = admin_url( 'my-sites.php' );
		}

		$wp_admin_bar->remove_node( 'my-sites' );
		$wp_admin_bar->add_menu( array(
			'id'    => 'my-sites',
			'title' => '',
			'href'  => $my_sites_url,
			'meta'   => array(
				'title' => __( 'My Sites' ),
			),
		) );
	}
	public static function wp_menu( &$wp_admin_bar )
	{
		if ( ! is_admin_bar_showing() )
			return;

		// custom menu by filter, it's better 'cause there are no default wp menu.
		$custom = apply_filters( 'gnetwork_adminbar_custom', false );
		if ( $custom ) {
			call_user_func_array( $custom, array( & $wp_admin_bar ) );
			return;
		}

		self::main_logo( $wp_admin_bar );

		$menu = self::get_network_menu();

		if ( $menu && is_array( $menu ) ) {

			foreach ( $menu as $item_id => $item ) {

				$wp_admin_bar->add_menu( array(
					'parent' => ( empty( $item->target ) ? ( empty( $item->menu_item_parent ) ? 'wp-logo' : 'network-menu-'.$item->menu_item_parent ) : 'wp-logo-external' ),
					'id'     => 'network-menu-'.$item->ID,
					'title'  => $item->title,
					'href'   => $item->url,
					'meta'  => array(
						'title' => $item->attr_title,
						'class' => join( ' ', $item->classes ),
					),
				) );
			}
		}

		self::login_register( $wp_admin_bar );
	}

	public static function extra_menu( &$wp_admin_bar )
	{
		$menu = self::get_network_menu( GNETWORK_NETWORK_EXTRAMENU );

		if ( $menu && is_array( $menu ) ) {

			$parent = 'gnetwork-extramenu';

			$wp_admin_bar->add_node( array(
				'id'     => $parent,
				'title'  => '<span class="ab-icon dashicons dashicons-menu" style="margin:2px 0 0 0;"></span>',
				// 'parent' => 'top-secondary', // Off on the right side
				'href'   => false,
			) );

			foreach ( $menu as $item_id => $item ) {
				$hide = false;
				if ( $item->xfn )
					$hide = current_user_can( $item->xfn ) ? false : true;

				if ( ! $hide ) {
					$wp_admin_bar->add_menu( array(
						// check target to place link on externals
						//'parent' => ( empty( $item->target ) ? ( empty( $item->menu_item_parent ) ? 'wp-logo' : 'network-menu-'.$item->menu_item_parent ) : 'wp-logo-external' ),
						'parent' => $parent,
						'id'     => 'network-extramenu-'.$item->ID,
						'title'  => $item->title,
						'href'   => $item->url,
						'meta'  => array(
							'title' => $item->attr_title,
							'class' => join( ' ', $item->classes ),
						),
					) );
				}
			}
		}
	}
}

function gnetwork_adminbar_wp_admin_bar_class( $class ) {
class gNetwork_WP_Admin_Bar extends WP_Admin_Bar {

	// // probably needed if want to change the styles site wide
	// function initialize()
	// {
	// 	parent::initialize();
	// 	if ( ! is_admin() )
	// 		wp_dequeue_style( 'admin-bar' );
	// }

	public function add_menus()
	{
		// User related, aligned right.
		add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_menu', 0 );

		// add_action( 'admin_bar_menu', 'wp_admin_bar_search_menu', 4 );
		add_action( 'admin_bar_menu', array( 'gNetworkAdminBar', 'search_menu' ), 4 );

		add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_item', 7 );

		// Site related.
		add_action( 'admin_bar_menu', array( 'gNetworkAdminBar', 'wp_menu' ), 10 );

		if ( GNETWORK_NETWORK_EXTRAMENU && current_user_can( GNETWORK_NETWORK_EXTRAMENU_CAP ) )
			add_action( 'admin_bar_menu', array( 'gNetworkAdminBar', 'extra_menu' ), 10 );

		add_action( 'admin_bar_menu', 'wp_admin_bar_my_sites_menu', 20 );
		add_action( 'admin_bar_menu', array( 'gNetworkAdminBar', 'my_sites' ), 25 );
		add_action( 'admin_bar_menu', 'wp_admin_bar_site_menu', 30 );
		add_action( 'admin_bar_menu', 'wp_admin_bar_updates_menu', 40 );

		// Content related.
		if ( ! is_network_admin() && ! is_user_admin() ) {
			add_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
			add_action( 'admin_bar_menu', 'wp_admin_bar_new_content_menu', 70 );
		}

		add_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );

		add_action( 'admin_bar_menu', 'wp_admin_bar_add_secondary_groups', 200 );

		do_action( 'add_admin_bar_menus' );
	}
}

	return 'gNetwork_WP_Admin_Bar';
}

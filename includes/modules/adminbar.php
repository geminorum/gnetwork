<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class AdminBar extends ModuleCore
{

	protected $key     = 'adminbar';
	protected $network = FALSE;
	protected $xmlrpc  = FALSE;
	protected $iframe  = FALSE;


	private $sidebar_admin = FALSE;
	private $show_adminbar = NULL;

	public $remove_nodes = array();

	protected function setup_actions()
	{
		$this->action( 'init', 0, 20 );
	}

	public function init()
	{
		if ( WordPress::mustRegisterUI() ) {

			$this->filter( 'admin_body_class' );

			$this->setup_adminbar();
			$this->wp_enqueue_style();

			$this->action( 'sidebar_admin_setup' );

		} else if ( $this->show_adminbar() ) {

			$this->setup_adminbar();
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_style' ) );

		} else {
			show_admin_bar( FALSE );
		}
	}

	protected function setup_adminbar()
	{
		add_filter( 'wp_admin_bar_class', __NAMESPACE__.'\\wp_admin_bar_class' );

		$this->action( 'wp_before_admin_bar_render' );

		if ( is_main_site() ) {
			$this->action( 'save_post_nav_menu_item', 2 );

			if ( GNETWORK_NETWORK_ADMINBAR )
				register_nav_menu( GNETWORK_NETWORK_ADMINBAR,
					_x( 'Network Adminbar', 'Modules: AdminBar: Menu Location', GNETWORK_TEXTDOMAIN ) );

			if ( GNETWORK_NETWORK_EXTRAMENU )
				register_nav_menu( GNETWORK_NETWORK_EXTRAMENU,
					_x( 'Network Adminbar Extra', 'Modules: AdminBar: Menu Location', GNETWORK_TEXTDOMAIN ) );
		}
	}

	public function add_menus()
	{
		// user related, aligned right
		add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_menu', 0 );
		add_action( 'admin_bar_menu', array( $this, 'wp_admin_bar_search_menu' ), 4 );
		add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_item', 7 );

		// site related
		add_action( 'admin_bar_menu', 'wp_admin_bar_sidebar_toggle', 0 );
		add_action( 'admin_bar_menu', array( $this, 'wp_admin_bar_wp_menu' ), 10 );

		if ( GNETWORK_NETWORK_EXTRAMENU && current_user_can( GNETWORK_NETWORK_EXTRAMENU_CAP ) )
			add_action( 'admin_bar_menu', array( $this, 'wp_admin_bar_extra_menu' ), 10 );

		add_action( 'admin_bar_menu', array( $this, 'wp_admin_bar_my_sites_menu' ), 25 );

		add_action( 'admin_bar_menu', 'wp_admin_bar_site_menu', 30 );
		add_action( 'admin_bar_menu', 'wp_admin_bar_customize_menu', 40 );
		add_action( 'admin_bar_menu', 'wp_admin_bar_updates_menu', 50 );

		// content related
		if ( ! is_network_admin() && ! is_user_admin() ) {
			add_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
			add_action( 'admin_bar_menu', 'wp_admin_bar_new_content_menu', 70 );
		}

		add_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
		add_action( 'admin_bar_menu', array( $this, 'wp_admin_bar_shortlink_menu' ), 90 );

		add_action( 'admin_bar_menu', 'wp_admin_bar_add_secondary_groups', 200 );
	}

	public function show_adminbar()
	{
		if ( ! is_null( $this->show_adminbar ) )
			return $this->show_adminbar;

		if ( defined( 'GNETWORK_ADMINBAR' ) ) {

			if ( TRUE === constant( 'GNETWORK_ADMINBAR' ) )
				return $this->show_adminbar = TRUE;

			if ( ! constant( 'GNETWORK_ADMINBAR' ) )
				return $this->show_adminbar = FALSE;

			if ( ! current_user_can( constant( 'GNETWORK_ADMINBAR' ) ) )
				return $this->show_adminbar = FALSE;
		}

		return $this->show_adminbar = is_admin_bar_showing();
	}

	public function admin_body_class( $classes )
	{
		if ( ! $this->show_adminbar() )
			$classes .= ' hide-adminbar-option';

		return $classes;
	}

	public function wp_enqueue_style()
	{
		if ( file_exists( WP_CONTENT_DIR.'/adminbar.css' ) )
			wp_enqueue_style( 'gnetwork-adminbar', WP_CONTENT_URL.'/adminbar.css', array( 'admin-bar' ), GNETWORK_VERSION );
	}

	// fires early before the Widgets administration screen loads, after scripts are enqueued.
	public function sidebar_admin_setup()
	{
		if ( $this->is_action( 'resetsidebars' ) ) {
			update_option( 'sidebars_widgets', array() );
			$_SERVER['REQUEST_URI'] = $this->remove_action( array(), $_SERVER['REQUEST_URI'] );
		}

		$this->sidebar_admin = TRUE;
	}

	public static function removeMenus( $nodes )
	{
		foreach ( (array) $nodes as $node )
			gNetwork()->adminbar->remove_nodes[] = $node;
	}

	public function wp_before_admin_bar_render()
	{
		global $wp_admin_bar;

		if ( WordPress::isSuperAdmin() )
			$this->add_nodes( $wp_admin_bar );

		foreach ( $this->remove_nodes as $node )
			$wp_admin_bar->remove_node( $node );
	}

	// super admins only
	private function add_nodes( &$wp_admin_bar )
	{
		global $pagenow;

		$current_url = URL::current();
		// $network_url = Settings::networkURL();
		$admin_url   = Settings::adminURL();

		$parent_id = $this->base.'-info';
		$group_id  = $parent_id.'-sub';

		$wp_admin_bar->add_node( array(
			'id'     => $parent_id,
			'title'  => self::getIcon( 'performance' ),
			'parent' => 'top-secondary',
			'href'   => $admin_url,
		) );

		$wp_admin_bar->add_node( array(
			'parent' => $parent_id,
			'id'     => $this->base.'-debug',
			'title'  => _x( 'Display Errors', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			'href'   => add_query_arg( 'debug', '', $current_url ),
			'meta'   => array(
				'title' => _x( 'Display debug info for the current page', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			),
		) );

		$wp_admin_bar->add_node( array(
			'parent' => $parent_id,
			'id'     => $this->base.'-flush',
			'title'  => _x( 'Flush Cached', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			'href'   => add_query_arg( 'flush', '', $current_url ),
			'meta'   => array(
				'title' => _x( 'Flush cached data for the current page', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			),
		) );

		$wp_admin_bar->add_node( array(
			'parent' => $parent_id,
			'id'     => $this->base.'-flushrewrite',
			'title'  => _x( 'Flush Permalinks', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			'href'   => add_query_arg( $this->base.'_action', 'flushrewrite', $current_url ),
			'meta'   => array(
				'title' => _x( 'Removes rewrite rules and then recreate rewrite rules', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			),
		) );

		if ( defined( 'GNETWORK_WPLANG' )
			&& class_exists( __NAMESPACE__.'\\Locale' ) ) {

			$wp_admin_bar->add_node( array(
				'parent' => $parent_id,
				'id'     => $this->base.'-locale',
				'title'  => _x( 'Change Locale', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => Settings::subURL( 'locale', FALSE ),
				'meta'   => array(
					'title' => _x( 'Quickly change current blog language', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				),
			) );

			foreach ( Locale::available() as $locale ) {
				$wp_admin_bar->add_node( array(
					'parent' => $this->base.'-locale',
					'id'     => $this->base.'-locale-'.$locale,
					'title'  => $locale,
					'href'   => add_query_arg( array(
						$this->base.'_action' => 'locale',
						'locale'              => $locale,
					), $current_url ),
				) );
			}
		}

		if ( class_exists( __NAMESPACE__.'\\Debug' ) ) {

			if ( $calls = gNetwork()->debug->get_http_calls() ) {

				$wp_admin_bar->add_node( array(
					'parent' => $parent_id,
					'id'     => $this->base.'-api-calls',
					'title'  => _x( 'HTTP Calls', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				) );

				foreach (  $calls as $offset => $call ) {

					$url = URL::parse( $call['url'] );

					$wp_admin_bar->add_node( array(
						'parent' => $this->base.'-api-calls',
						'id'     => $this->base.'-api-calls-'.$offset,
						'title'  => $call['class'].': '.$url['base'],
						'href'   => $call['url'],
					) );

					foreach ( $url['query'] as $key => $val )
						$wp_admin_bar->add_node( array(
							'parent' => $this->base.'-api-calls-'.$offset,
							'id'     => $this->base.'-api-calls-'.$offset.'-'.$key,
							'title'  => sprintf( '%s: %s', $key, $val ),
						) );
				}
			}
		}

		$wp_admin_bar->add_group( array(
			'parent' => $parent_id,
			'id'     => $group_id,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		do_action_ref_array( 'gnetwork_adminbar_action', array( &$wp_admin_bar, $parent_id, $group_id, $current_url ) );

		if ( $this->sidebar_admin )
			$wp_admin_bar->add_node( array(
				'parent' => $group_id,
				'id'     => $this->base.'-reset-sidebars',
				'title'  => _x( 'Reset Sidebars', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => add_query_arg( $this->base.'_action', 'resetsidebars', $current_url ),
				'meta'   => array(
					'title' => _x( 'Delete all previous sidebar widgets, be careful!', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ,
				),
			) );

		$wp_admin_bar->add_node( array(
			'parent' => $group_id,
			'id'     => $this->base.'-info-pagenow',
			'title'  => 'PageNow: '.( empty( $pagenow ) ? 'EMPTY' : $pagenow ),
			'href'   => GNETWORK_ANALOG_LOG ? Settings::subURL( 'analoglogs' ) : FALSE,
			'meta'   => array(
				'title' => _x( 'Click to see Logs', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			),
		) );

		$wp_admin_bar->add_node( array(
			'parent' => $group_id,
			'id'     => $this->base.'-info-queries',
			'title'  => self::stat( '%dq | %.3fs | %.2fMB' ),
			'href'   => GNETWORK_DEBUG_LOG ? Settings::subURL( 'errorlogs' ) : FALSE,
			'meta'   => array(
				'title' => _x( 'Queries | Timer Stop | Memory Usage', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			),
		) );
	}

	public function save_post_nav_menu_item( $post_id, $post )
	{
		if ( GNETWORK_NETWORK_ADMINBAR )
			update_site_option( 'gnetwork_'.GNETWORK_NETWORK_ADMINBAR, '' );

		if ( GNETWORK_NETWORK_EXTRAMENU )
			update_site_option( 'gnetwork_'.GNETWORK_NETWORK_EXTRAMENU, '' );

		return $post_id;
	}

	public static function getNetworkMenu( $name )
	{
		if ( ! $name )
			return FALSE;

		$key = 'gnetwork_'.$name;

		if ( $menu = get_site_option( $key, NULL ) )
			return $menu;

		// later will store false to prevent unnecessary checks
		if ( FALSE === $menu )
			return $menu;

		if ( is_main_site() ) {

			$nav_menu  = $name;
			$locations = get_nav_menu_locations();

			if ( isset( $locations[$name] )
				&& $term = get_term( $locations[$name], 'nav_menu' ) )
					$nav_menu = $term->term_id;

			if ( $menu = wp_get_nav_menu_items( $nav_menu, array( 'update_post_term_cache' => FALSE ) ) ) {
				update_site_option( $key, $menu );
				return $menu;
			}
		}

		update_site_option( $key, FALSE );
		return FALSE;
	}

	public function wp_admin_bar_search_menu( $wp_admin_bar )
	{
		if ( is_admin() )
			return;

		$form  = '<form action="'.GNETWORK_SEARCH_URL.'" method="get" id="adminbarsearch">';
		$form .= '<input class="adminbar-input" name="'.GNETWORK_SEARCH_QUERYID.'" id="adminbar-search" type="text" value="" maxlength="150" />';
		$form .= '<label for="adminbar-search" class="screen-reader-text">'._x( 'Search', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ).'</label>';
		$form .= '<input type="submit" class="adminbar-button" value="'._x( 'Search', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ).'"/>';
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

	public function wp_admin_bar_shortlink_menu( $wp_admin_bar )
	{
		if ( is_admin() || ! is_singular() )
			return;

		if ( ! $short = wp_get_shortlink( 0, 'query' ) )
			return;

		$wp_admin_bar->add_menu( array(
			'id'    => 'get-shortlink',
			'title' => self::getIcon( 'admin-links' ),
			'href'  => $short,
			'meta'  => array(
				'html'  => '<input class="shortlink-input" style="margin:2px 0 0 0;" type="text" readonly="readonly" value="'.esc_attr( $short ).'" />',
				'title' => _x( 'Shortlink', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			),
		) );
	}

	public function wp_admin_bar_my_sites_menu( $wp_admin_bar )
	{
		if ( ! is_user_logged_in() || ! is_multisite() )
			return;

		$super_admin = WordPress::isSuperAdmin();

		if ( count( $wp_admin_bar->user->blogs ) < 1 && ! $super_admin )
			return;

		if ( $wp_admin_bar->user->active_blog ) {
			$my_sites_url = get_admin_url( $wp_admin_bar->user->active_blog->blog_id, 'my-sites.php' );
		} else {
			$my_sites_url = admin_url( 'my-sites.php' );
		}

		$wp_admin_bar->add_menu( array(
			'id'    => 'my-sites',
			'title' => '',
			'href'  => $my_sites_url,
			'meta'  => array(
				'title' => _x( 'My Sites', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			),
		) );

		if ( $super_admin ) {

			$wp_admin_bar->add_group( array(
				'parent' => 'my-sites',
				'id'     => 'my-sites-super-admin',
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'my-sites-super-admin',
				'id'     => 'network-admin',
				'title'  => _x( 'Network Admin', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url(),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-s',
				'title'  => _x( 'Sites', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url( 'sites.php' ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-u',
				'title'  => _x( 'Users', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url( 'users.php' ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-t',
				'title'  => _x( 'Themes', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url( 'themes.php' ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-p',
				'title'  => _x( 'Plugins', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url( 'plugins.php' ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-o',
				'title'  => _x( 'Settings', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url( 'settings.php' ),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-uc',
				'title'  => _x( 'Updates', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url( 'update-core.php' ),
			) );
		}

		$wp_admin_bar->add_group( array(
			'parent' => 'my-sites',
			'id'     => 'my-sites-list',
			'meta'   => array(
				'class' => $super_admin ? 'ab-sub-secondary' : '',
			),
		) );

		$blogs = $super_admin ? self::getAllBlogs() : (array) $wp_admin_bar->user->blogs;

		foreach ( $blogs as $blog ) {

			switch_to_blog( $blog->userblog_id );

			// TODO: get class from site meta
			$blavatar = '<div class="blavatar"></div>';
			$menu_id  = 'blog-'.$blog->userblog_id;

			if ( ! $blogname = get_option( 'blogname' ) )
				$blogname = WordPress::currentBlog();

			$wp_admin_bar->add_menu( array(
				'parent'    => 'my-sites-list',
				'id'        => $menu_id,
				'title'     => $blavatar.$blogname,
				'href'      => admin_url(),
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => $menu_id,
				'id'     => $menu_id.'-d',
				'title'  => _x( 'Dashboard', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => admin_url(),
			) );

			if ( $super_admin || current_user_can( get_post_type_object( 'post' )->cap->edit_posts ) )
				$wp_admin_bar->add_menu( array(
					'parent' => $menu_id,
					'id'     => $menu_id.'-e',
					'title'  => _x( 'Posts', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => admin_url( 'edit.php' ),
				) );

			if ( $super_admin || current_user_can( 'list_users' ) )
				$wp_admin_bar->add_menu( array(
					'parent' => $menu_id,
					'id'     => $menu_id.'-u',
					'title'  => _x( 'Users', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => admin_url( 'users.php' ),
				) );

			if ( $super_admin || current_user_can( 'manage_options' ) )
				$wp_admin_bar->add_menu( array(
					'parent' => $menu_id,
					'id'     => $menu_id.'-s',
					'title'  => _x( 'Settings', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => admin_url( 'options-general.php' ),
				) );

			if ( $super_admin ) {
				$wp_admin_bar->add_menu( array(
					'parent' => $menu_id,
					'id'     => $menu_id.'-e-s',
					'title'  => _x( 'Edit Site', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'site-info.php?id='.$blog->userblog_id ),
				) );

				$wp_admin_bar->add_menu( array(
					'parent' => $menu_id,
					'id'     => $menu_id.'-e-t',
					'title'  => _x( 'Edit Themes', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'site-themes.php?id='.$blog->userblog_id ),
				) );
			}

			$wp_admin_bar->add_menu( array(
				'parent' => $menu_id,
				'id'     => $menu_id.'-v',
				'title'  => _x( 'Visit Site', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => home_url( '/' ),
			) );

			restore_current_blog();
		}
	}

	private static function getAllBlogs()
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

		foreach ( $blog_ids as $blog_id )
			$blogs[$blog_id] = (object) array( 'userblog_id' => $blog_id );

		return $blogs;
	}

	public function wp_admin_bar_wp_menu( $wp_admin_bar )
	{
		// custom menu by filter, it's better 'cause there are no default wp menu.
		if ( apply_filters( 'gnetwork_adminbar_custom', FALSE ) ) {
			call_user_func_array( $custom, array( &$wp_admin_bar ) );
			return;
		}

		self::addMainLogo( $wp_admin_bar );

		$menu = self::getNetworkMenu( GNETWORK_NETWORK_ADMINBAR );

		if ( $menu && is_array( $menu ) ) {

			foreach ( $menu as $item_id => $item ) {

				$wp_admin_bar->add_menu( array(
					'parent' => ( empty( $item->target ) ? ( empty( $item->menu_item_parent ) ? 'wp-logo' : 'network-menu-'.$item->menu_item_parent ) : 'wp-logo-external' ),
					'id'     => 'network-menu-'.$item->ID,
					'title'  => $item->title,
					'href'   => $item->url,
					'meta'   => array(
						'title' => $item->attr_title,
						'class' => join( ' ', $item->classes ),
					),
				) );
			}
		}

		self::addLoginRegister( $wp_admin_bar );
	}

	public static function addMainLogo( $wp_admin_bar, $id = 'wp-logo', $title = '<span class="ab-icon"></span>' )
	{
		$wp_admin_bar->add_menu( array(
			'id'    => $id,
			'title' => $title.'<span class="screen-reader-text">'.GNETWORK_NAME.'</span>',
			'href'  => GNETWORK_BASE,
		) );
	}

	public static function addLoginRegister( $wp_admin_bar, $parent = 'wp-logo-external' )
	{
		if ( ! is_user_logged_in() ) {

			$wp_admin_bar->add_menu( array(
				'parent' => $parent,
				'id'     => 'network-login',
				'title'  => _x( 'Log in', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => wp_login_url(),
			) );

			if ( $register_url = WordPress::registerURL() )
				$wp_admin_bar->add_menu( array(
					'parent' => $parent,
					'id'     => 'network-register',
					'title'  => _x( 'Register', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => $register_url,
				) );
		}
	}

	public function wp_admin_bar_extra_menu( $wp_admin_bar )
	{
		$menu = self::getNetworkMenu( GNETWORK_NETWORK_EXTRAMENU );

		if ( $menu && is_array( $menu ) ) {

			$parent = 'gnetwork-extramenu';

			$wp_admin_bar->add_node( array(
				// 'parent' => 'top-secondary', // off on the right side
				'id'     => $parent,
				'title'  => self::getIcon( 'menu' ),
				'href'   => FALSE,
			) );

			foreach ( $menu as $item_id => $item ) {

				if (  ( $item->xfn ? current_user_can( $item->xfn ) : TRUE ) ) {

					$wp_admin_bar->add_menu( array(
						// check target to place link on externals
						// 'parent' => ( empty( $item->target ) ? ( empty( $item->menu_item_parent ) ? 'wp-logo' : 'network-menu-'.$item->menu_item_parent ) : 'wp-logo-external' ),
						'parent' => $parent,
						'id'     => 'network-extramenu-'.$item->ID,
						'title'  => $item->title,
						'href'   => $item->url,
						'meta'   => array(
							'title' => $item->attr_title,
							'class' => join( ' ', $item->classes ),
						),
					) );
				}
			}
		}
	}

	public static function getIcon( $icon, $style = 'margin:2px 0 0 0;' )
	{
		return HTML::tag( 'span', array(
			'class' => array(
				'ab-icon',
				'dashicons',
				'dashicons-'.$icon,
			),
			'style' => $style,
		), NULL );
	}
}

function wp_admin_bar_class( $class ) {

	class WP_Admin_Bar extends \WP_Admin_Bar
	{
		public function add_menus()
		{
			gNetwork()->adminbar->add_menus();
			do_action( 'add_admin_bar_menus' );
		}
	}

	return __NAMESPACE__.'\\WP_Admin_Bar';
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class AdminBar extends gNetwork\Module
{

	protected $key     = 'adminbar';
	protected $network = FALSE;
	protected $xmlrpc  = FALSE;
	protected $iframe  = FALSE;


	private $sidebar_admin   = FALSE;
	private $wpcf7_admin     = FALSE;
	private $wpcf7_shortcode = FALSE;
	private $show_adminbar   = NULL;

	public $remove_nodes = [];

	protected function setup_actions()
	{
		$this->action( 'init', 0, 20 );
	}

	public function init()
	{
		if ( WordPress::mustRegisterUI() ) {

			$this->setup_adminbar();
			$this->wp_enqueue_style();

			$this->action( 'sidebar_admin_setup' );
			$this->action( 'load-toplevel_page_wpcf7' );

		} else if ( $this->show_adminbar() ) {

			$this->setup_adminbar();
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_style' ] );

			$this->filter( 'shortcode_atts_wpcf7', 4 );

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
					_x( 'Network Adminbar Navigation', 'Modules: AdminBar: Menu Location', GNETWORK_TEXTDOMAIN ) );

			if ( GNETWORK_NETWORK_USERMENU )
				register_nav_menu( GNETWORK_NETWORK_USERMENU,
					_x( 'Network User Navigation', 'Modules: AdminBar: Menu Location', GNETWORK_TEXTDOMAIN ) );

			if ( GNETWORK_NETWORK_EXTRAMENU )
				register_nav_menu( GNETWORK_NETWORK_EXTRAMENU,
					_x( 'Network Extra Navigation', 'Modules: AdminBar: Menu Location', GNETWORK_TEXTDOMAIN ) );
		}
	}

	// overrided to avoid `get_blogs_of_user()`
	public function initialize()
	{
		$user = new \stdClass;

		if ( is_multisite() && ( $user_id = get_current_user_id() ) ) {
			$super_admin       = WordPress::isSuperAdmin();
			$user->blogs       = WordPress::getAllBlogs( ( $super_admin ? FALSE : $user_id ), $super_admin, TRUE );
			$user->active_blog = get_user_meta( $user_id, 'primary_blog', TRUE );
		} else {
			$user->blogs       = [];
			$user->active_blog = FALSE;
		}

		add_action( 'wp_head', 'wp_admin_bar_header' );
		add_action( 'admin_head', 'wp_admin_bar_header' );

		if ( current_theme_supports( 'admin-bar' ) ) {
			$admin_bar_args = get_theme_support( 'admin-bar' );
			$header_callback = $admin_bar_args[0]['callback'];
		}

		if ( empty($header_callback) )
			$header_callback = '_admin_bar_bump_cb';

		add_action( 'wp_head', $header_callback );

		wp_enqueue_script( 'admin-bar' );
		wp_enqueue_style( 'admin-bar' );

		// fires after WP_Admin_Bar is initialized.
		do_action( 'admin_bar_init' );

		return $user;
	}

	public function add_menus()
	{
		// user related, aligned right
		add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_menu', 0 );
		add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_search_menu' ], 4 );
		add_action( 'admin_bar_menu', 'wp_admin_bar_my_account_item', 7 );

		// site related
		add_action( 'admin_bar_menu', 'wp_admin_bar_sidebar_toggle', 0 );
		add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_wp_menu' ], 10 );

		if ( GNETWORK_NETWORK_EXTRAMENU && current_user_can( GNETWORK_NETWORK_EXTRAMENU_CAP ) )
			add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_extra_menu' ], 10 );

		add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_my_sites_menu' ], 25 );

		add_action( 'admin_bar_menu', 'wp_admin_bar_site_menu', 30 );
		add_action( 'admin_bar_menu', 'wp_admin_bar_customize_menu', 40 );
		add_action( 'admin_bar_menu', 'wp_admin_bar_updates_menu', 50 );

		// content related
		if ( ! is_network_admin() && ! is_user_admin() ) {
			add_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
			add_action( 'admin_bar_menu', 'wp_admin_bar_new_content_menu', 70 );
		}

		add_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
		add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_shortlink_menu' ], 90 );

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

	public function wp_enqueue_style()
	{
		if ( file_exists( WP_CONTENT_DIR.'/adminbar.css' ) )
			wp_enqueue_style( 'gnetwork-adminbar', WP_CONTENT_URL.'/adminbar.css', [ 'admin-bar' ], GNETWORK_VERSION );
	}

	// fires early before the Widgets administration screen loads, after scripts are enqueued.
	public function sidebar_admin_setup()
	{
		if ( $this->is_action( 'resetsidebars' ) ) {
			update_option( 'sidebars_widgets', [] );
			$_SERVER['REQUEST_URI'] = $this->remove_action( [], $_SERVER['REQUEST_URI'] );
		}

		$this->sidebar_admin = TRUE;
	}

	public function load_toplevel_page_wpcf7()
	{
		if ( ! $post_id = self::req( 'post' )
			|| 'edit' != self::req( 'action' ) )
				return;

		if ( $this->is_action( 'resetwpcf7messages' ) ) {
			$this->filter( 'wpcf7_contact_form_properties', 2 );
			$_SERVER['REQUEST_URI'] = $this->remove_action( [], $_SERVER['REQUEST_URI'] );
		}

		$this->wpcf7_admin = TRUE;
	}

	public function wpcf7_contact_form_properties( $properties, $wpcf7 )
	{
		foreach ( wpcf7_messages() as $key => $args )
			$properties['messages'][$key] = $args['default'];

		return $properties;
	}

	public function shortcode_atts_wpcf7( $out, $pairs, $atts, $shortcode )
	{
		if ( ! empty( $atts['id'] ) && current_user_can( 'wpcf7_edit_contact_form' ) ) {
			$this->wpcf7_shortcode = $atts['id'];
			add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_wpcf7_shortcode' ], 90 );
		}

		return $out;
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

		$wp_admin_bar->add_node( [
			'id'     => $parent_id,
			'title'  => self::getIcon( 'performance' ),
			'parent' => 'top-secondary',
			'href'   => $admin_url,
			'meta'   => [ 'title' => sprintf( 'gNetwork v%s', GNETWORK_VERSION ) ],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $parent_id,
			'id'     => $this->base.'-debug',
			'title'  => _x( 'Display Errors', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			'href'   => add_query_arg( 'debug', '', $current_url ),
			'meta'   => [ 'title' => _x( 'Display debug info for the current page', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $parent_id,
			'id'     => $this->base.'-flush',
			'title'  => _x( 'Flush Cached', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			'href'   => add_query_arg( 'flush', '', $current_url ),
			'meta'   => [ 'title' => _x( 'Flush cached data for the current page', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $parent_id,
			'id'     => $this->base.'-flushrewrite',
			'title'  => _x( 'Flush Permalinks', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			'href'   => add_query_arg( $this->base.'_action', 'flushrewrite', $current_url ),
			'meta'   => [ 'title' => _x( 'Removes rewrite rules and then recreate rewrite rules', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
		] );

		if ( defined( 'GNETWORK_WPLANG' )
			&& WordPress::isDev()
			&& class_exists( __NAMESPACE__.'\\Locale' ) ) {

			$wp_admin_bar->add_node( [
				'parent' => $parent_id,
				'id'     => $this->base.'-locale',
				'title'  => _x( 'Change Locale', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => Settings::subURL( 'locale', FALSE ),
				'meta'   => [ 'title' => _x( 'Quickly change current blog language', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
			] );

			foreach ( Locale::available() as $locale ) {
				$wp_admin_bar->add_node( [
					'parent' => $this->base.'-locale',
					'id'     => $this->base.'-locale-'.$locale,
					'title'  => $locale,
					'href'   => add_query_arg( [
						$this->base.'_action' => 'locale',
						'locale'              => $locale,
					], $current_url ),
				] );
			}
		}

		if ( class_exists( __NAMESPACE__.'\\Debug' ) ) {

			if ( $calls = gNetwork()->debug->get_http_calls() ) {

				$wp_admin_bar->add_node( [
					'parent' => $parent_id,
					'id'     => $this->base.'-api-calls',
					'title'  => _x( 'HTTP Calls', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				] );

				foreach (  $calls as $offset => $call ) {

					$url = URL::parse_OLD( $call['url'] );

					$wp_admin_bar->add_node( [
						'parent' => $this->base.'-api-calls',
						'id'     => $this->base.'-api-calls-'.$offset,
						'title'  => $call['class'].': '.$url['base'],
						'href'   => $call['url'],
					] );

					foreach ( $url['query'] as $key => $val )
						$wp_admin_bar->add_node( [
							'parent' => $this->base.'-api-calls-'.$offset,
							'id'     => $this->base.'-api-calls-'.$offset.'-'.$key,
							'title'  => sprintf( '%s: %s', (string) $key, maybe_serialize( $val ) ),
						] );
				}
			}
		}

		if ( ! is_admin() && is_singular() ) {

			if ( $post = get_queried_object() ) {

				$wp_admin_bar->add_node( [
					'parent' => $parent_id,
					'id'     => $this->base.'-current-post',
					'title'  => _x( 'Current Post', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => WordPress::getPostEditLink( $post->ID ),
				] );

				$wp_admin_bar->add_node( [
					'parent' => $this->base.'-current-post',
					'id'     => $this->base.'-current-post-rest',
					'title'  => _x( 'Rest Endpoint', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => rest_url( '/wp/v2/posts/'.$post->ID ),
				] );

				$wp_admin_bar->add_node( [
					'parent' => $this->base.'-current-post',
					'id'     => $this->base.'-current-post-embed',
					'title'  => _x( 'Embed Endpoint', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => get_post_embed_url( $post ),
				] );
			}
		}

		$wp_admin_bar->add_group( [
			'parent' => $parent_id,
			'id'     => $group_id,
			'meta'   => [ 'class' => 'ab-sub-secondary' ],
		] );

		do_action_ref_array( 'gnetwork_adminbar_action', [ &$wp_admin_bar, $parent_id, $group_id, $current_url ] );

		if ( $this->sidebar_admin )
			$wp_admin_bar->add_node( [
				'parent' => $group_id,
				'id'     => $this->base.'-reset-sidebars',
				'title'  => _x( 'Reset Sidebars', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => add_query_arg( $this->base.'_action', 'resetsidebars', $current_url ),
				'meta'   => [ 'title' => _x( 'Delete all previous sidebar widgets, be careful!', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
			] );

		if ( $this->wpcf7_admin )
			$wp_admin_bar->add_node( [
				'parent' => $group_id,
				'id'     => $this->base.'-wpcf7-messages',
				'title'  => _x( 'Reset Messages', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => add_query_arg( $this->base.'_action', 'resetwpcf7messages', $current_url ),
				'meta'   => [ 'title' => _x( 'Reset all saved messages for this form, be careful!', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
			] );

		if ( class_exists( __NAMESPACE__.'\\Cron' ) ) {

			if ( is_blog_admin() && $status = gNetwork()->cron->get_status() )
				$wp_admin_bar->add_node( [
					'parent' => $group_id,
					'id'     => $this->base.'-cron-status',
					'title'  => strip_tags( $status ),
					'href'   => Settings::subURL( 'scheduled', FALSE ),
				] );
		}

		$wp_admin_bar->add_node( [
			'parent' => $group_id,
			'id'     => $this->base.'-info-pagenow',
			'title'  => 'PageNow: '.( empty( $pagenow ) ? 'EMPTY' : $pagenow ),
			'href'   => GNETWORK_ANALOG_LOG ? Settings::subURL( 'analoglogs' ) : FALSE,
			'meta'   => [ 'title' => _x( 'Click to see Logs', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $group_id,
			'id'     => $this->base.'-info-queries',
			'title'  => self::stat( '%dq | %.3fs | %.2fMB' ),
			'href'   => GNETWORK_DEBUG_LOG ? Settings::subURL( 'errorlogs' ) : FALSE,
			'meta'   => [ 'title' => _x( 'Queries | Timer Stop | Memory Usage', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
		] );
	}

	public function save_post_nav_menu_item( $post_id, $post )
	{
		if ( GNETWORK_NETWORK_ADMINBAR )
			update_site_option( 'gnetwork_'.GNETWORK_NETWORK_ADMINBAR, '' );

		if ( GNETWORK_NETWORK_USERMENU )
			update_site_option( 'gnetwork_'.GNETWORK_NETWORK_USERMENU, '' );

		if ( GNETWORK_NETWORK_EXTRAMENU )
			update_site_option( 'gnetwork_'.GNETWORK_NETWORK_EXTRAMENU, '' );

		return $post_id;
	}

	public static function getNetworkMenu( $name, $items = TRUE )
	{
		$menu = FALSE;

		if ( ! $name )
			return $menu;

		$key = 'gnetwork_'.$name;

		if ( WordPress::isFlush() )
			update_site_option( $key, '' );

		else if ( $menu = get_site_option( $key, NULL ) )
			return $menu;

		// bail because previously no menu found
		// and '0' stored to prevent unnecessary checks
		if ( '0' === $menu )
			return $menu;

		if ( is_main_site() ) {

			// only saved location menus
			$locations = get_nav_menu_locations();

			if ( array_key_exists( $name, $locations ) ) {

				$term = get_term( intval( $locations[$name] ), 'nav_menu' );

				if ( $term && ! self::isError( $term ) ) {

					if ( $items )
						$menu = wp_get_nav_menu_items( $term->term_id, [ 'update_post_term_cache' => FALSE ] );
					else
						$menu = wp_nav_menu( [ 'menu' => $term->term_id, 'echo' => FALSE, 'container' => '', 'item_spacing' => 'discard', 'fallback_cb' => FALSE ] );
				}
			}

			if ( $menu ) {
				update_site_option( $key, ( $items ? $menu : Text::minifyHTML( $menu ) ) );
				return $menu;
			}
		}

		update_site_option( $key, '0' );
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

		$wp_admin_bar->add_menu( [
			'parent' => 'top-secondary',
			'id'     => 'search',
			'title'  => $form,
			'meta'   => [
				'class'    => 'admin-bar-search',
				'tabindex' => -1,
			]
		] );
	}

	public function wp_admin_bar_shortlink_menu( $wp_admin_bar )
	{
		if ( is_admin() || ! is_singular() || is_front_page() )
			return;

		if ( function_exists( 'is_buddypress' ) && is_buddypress() )
			return;

		if ( ! $short = wp_get_shortlink( 0, 'query' ) )
			return;

		$wp_admin_bar->add_menu( [
			'id'    => 'get-shortlink',
			'title' => self::getIcon( 'admin-links' ),
			'href'  => $short,
			'meta'  => [
				'html'  => '<input class="shortlink-input" style="margin:2px 0 0 0;" type="text" readonly="readonly" value="'.esc_attr( $short ).'" />',
				'title' => _x( 'Shortlink', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			],
		] );
	}

	public function wp_admin_bar_wpcf7_shortcode( $wp_admin_bar )
	{
		if ( is_admin() || ! is_singular() || is_front_page() )
			return;

		$wp_admin_bar->add_menu( [
			'id'    => 'edit-contact-form',
			'title' => _x( 'Edit Contact Form', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			'href'  => add_query_arg( [ 'page' => 'wpcf7', 'post' => $this->wpcf7_shortcode ], admin_url( 'admin.php' ) ),
			'meta'  => [
				'title' => _x( 'Edit Current embeded contact form on admin', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			],
		] );
	}

	public function wp_admin_bar_my_sites_menu( $wp_admin_bar )
	{
		if ( ! is_user_logged_in() || ! is_multisite() )
			return;

		$super_admin = WordPress::isSuperAdmin();

		if ( count( $wp_admin_bar->user->blogs ) < 1 && ! $super_admin )
			return;

		$my_sites = admin_url( 'my-sites.php' );

		if ( $wp_admin_bar->user->active_blog && isset( $wp_admin_bar->user->blogs[$wp_admin_bar->user->active_blog] ) )
			$my_sites = $wp_admin_bar->user->blogs[$wp_admin_bar->user->active_blog]->siteurl.'/wp-admin/my-sites.php';

		$wp_admin_bar->add_menu( [
			'id'    => 'my-sites',
			'href'  => $my_sites,
			'title' => '', // more minimal!
			'meta'  => [ 'title' => _x( 'My Sites', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ) ],
		] );

		$wp_admin_bar->add_group( [
			'parent' => 'my-sites',
			'id'     => 'my-sites-admin',
		] );

		$wp_admin_bar->add_menu( [
			'parent' => 'my-sites-admin',
			'id'     => 'user-admin',
			'title'  => _x( 'Your Dashboard', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
			'href'   => user_admin_url(),
		] );

		if ( $super_admin ) {

			$wp_admin_bar->add_menu( [
				'parent' => 'my-sites-admin',
				'id'     => 'network-admin',
				'title'  => _x( 'Network Admin', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => network_admin_url(),
			] );

			if ( class_exists( __NAMESPACE__.'\\Debug' ) )
				$wp_admin_bar->add_menu( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-sr',
					'title'  => _x( 'System Report', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => Settings::subURL( 'systemreport' ),
				] );

			if ( current_user_can( 'manage_sites' ) )
				$wp_admin_bar->add_menu( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-s',
					'title'  => _x( 'Sites', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'sites.php' ),
				] );

			if ( current_user_can( 'manage_network_users' ) )
				$wp_admin_bar->add_menu( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-u',
					'title'  => _x( 'Users', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'users.php' ),
				] );

			if ( current_user_can( 'manage_network_themes' ) )
				$wp_admin_bar->add_menu( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-t',
					'title'  => _x( 'Themes', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'themes.php' ),
				] );

			if ( current_user_can( 'manage_network_plugins' ) )
				$wp_admin_bar->add_menu( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-p',
					'title'  => _x( 'Plugins', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'plugins.php' ),
				] );

			if ( current_user_can( 'manage_network_options' ) ) {

				$wp_admin_bar->add_menu( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-o',
					'title'  => _x( 'Settings', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'settings.php' ),
				] );

				$wp_admin_bar->add_menu( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-ne',
					'title'  => _x( 'Extras', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => Settings::subURL(),
				] );
			}

			if ( current_user_can( 'update_core' ) )
				$wp_admin_bar->add_menu( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-uc',
					'title'  => _x( 'Updates', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'update-core.php' ),
				] );
		}

		$wp_admin_bar->add_group( [
			'parent' => 'my-sites',
			'id'     => 'my-sites-list',
			'meta'   => [ 'class' => 'ab-sub-secondary' ],
		] );

		foreach ( $wp_admin_bar->user->blogs as $blog ) {

			// avoiding `switch_to_blog()`

			$menu_id  = 'blog-'.$blog->userblog_id;
			$blavatar = '<div class="blavatar"></div>';
			$blogname = URL::untrail( $blog->domain.$blog->path );

			$wp_admin_bar->add_menu( [
				'parent'    => 'my-sites-list',
				'id'        => $menu_id,
				'title'     => $blavatar.$blogname,
				'href'      => $blog->siteurl.'/wp-admin/',
			] );

			$wp_admin_bar->add_menu( [
				'parent' => $menu_id,
				'id'     => $menu_id.'-d',
				'title'  => _x( 'Dashboard', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'      => $blog->siteurl.'/wp-admin/',
			] );

			// extra links for super admins only (no cap checks)
			if ( $super_admin ) {
				$wp_admin_bar->add_menu( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e',
					'title'  => _x( 'Posts', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => $blog->siteurl.'/wp-admin/edit.php',
				] );

				$wp_admin_bar->add_menu( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-u',
					'title'  => _x( 'Users', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => $blog->siteurl.'/wp-admin/users.php',
				] );

				$wp_admin_bar->add_menu( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-s',
					'title'  => _x( 'Settings', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => $blog->siteurl.'/wp-admin/options-general.php',
				] );

				$wp_admin_bar->add_menu( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e-s',
					'title'  => _x( 'Edit Site', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'site-info.php?id='.$blog->userblog_id ),
				] );

				$wp_admin_bar->add_menu( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e-t',
					'title'  => _x( 'Edit Themes', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => network_admin_url( 'site-themes.php?id='.$blog->userblog_id ),
				] );
			}

			$wp_admin_bar->add_menu( [
				'parent' => $menu_id,
				'id'     => $menu_id.'-v',
				'title'  => _x( 'Visit Site', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => URL::trail( $blog->siteurl ),
			] );
		}
	}

	public function wp_admin_bar_wp_menu( $wp_admin_bar )
	{
		// custom menu by filter, it's better 'cause there are no default wp menu.
		if ( apply_filters( 'gnetwork_adminbar_custom', FALSE ) ) {
			call_user_func_array( $custom, [ &$wp_admin_bar ] );
			return;
		}

		self::addMainLogo( $wp_admin_bar );

		$menu = self::getNetworkMenu( GNETWORK_NETWORK_ADMINBAR );

		if ( $menu && is_array( $menu ) ) {

			foreach ( $menu as $item_id => $item ) {

				$wp_admin_bar->add_menu( [
					'parent' => ( empty( $item->target ) ? ( empty( $item->menu_item_parent ) ? 'wp-logo' : 'network-menu-'.$item->menu_item_parent ) : 'wp-logo-external' ),
					'id'     => 'network-menu-'.$item->ID,
					'title'  => $item->title,
					'href'   => $item->url,
					'meta'   => [
						'title' => $item->attr_title,
						'class' => join( ' ', $item->classes ),
					],
				] );
			}
		}

		self::addLoginRegister( $wp_admin_bar );
	}

	public static function addMainLogo( $wp_admin_bar, $id = 'wp-logo', $title = '<span class="ab-icon"></span>' )
	{
		$wp_admin_bar->add_menu( [
			'id'    => $id,
			'title' => $title.'<span class="screen-reader-text">'.GNETWORK_NAME.'</span>',
			'href'  => GNETWORK_BASE,
		] );
	}

	public static function addLoginRegister( $wp_admin_bar, $parent = 'wp-logo-external' )
	{
		if ( ! is_user_logged_in() ) {

			$wp_admin_bar->add_menu( [
				'parent' => $parent,
				'id'     => 'network-login',
				'title'  => _x( 'Log in', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
				'href'   => wp_login_url(),
			] );

			if ( $register_url = WordPress::registerURL() )
				$wp_admin_bar->add_menu( [
					'parent' => $parent,
					'id'     => 'network-register',
					'title'  => _x( 'Register', 'Modules: AdminBar: Nodes', GNETWORK_TEXTDOMAIN ),
					'href'   => $register_url,
				] );
		}
	}

	public function wp_admin_bar_extra_menu( $wp_admin_bar )
	{
		$menu = self::getNetworkMenu( GNETWORK_NETWORK_EXTRAMENU );

		if ( $menu && is_array( $menu ) ) {

			$parent = 'gnetwork-extramenu';

			$wp_admin_bar->add_node( [
				// 'parent' => 'top-secondary', // off on the right side
				'id'     => $parent,
				'title'  => self::getIcon( 'menu' ),
				'href'   => FALSE,
			] );

			foreach ( $menu as $item_id => $item ) {

				if (  ( $item->xfn ? current_user_can( $item->xfn ) : TRUE ) ) {

					$wp_admin_bar->add_menu( [
						// check target to place link on externals
						// 'parent' => ( empty( $item->target ) ? ( empty( $item->menu_item_parent ) ? 'wp-logo' : 'network-menu-'.$item->menu_item_parent ) : 'wp-logo-external' ),
						'parent' => $parent,
						'id'     => 'network-extramenu-'.$item->ID,
						'title'  => $item->title,
						'href'   => $item->url,
						'meta'   => [
							'title' => $item->attr_title,
							'class' => join( ' ', $item->classes ),
						],
					] );
				}
			}
		}
	}

	public static function getIcon( $icon, $style = 'margin:2px 1px 0 1px;' )
	{
		return HTML::tag( 'span', [
			'class' => [
				'ab-icon',
				'dashicons',
				'dashicons-'.$icon,
			],
			'style' => $style,
		], NULL );
	}
}

function wp_admin_bar_class( $class ) {

	class WP_Admin_Bar extends \WP_Admin_Bar
	{
		public function initialize()
		{
			$this->user = gNetwork()->adminbar->initialize();
		}

		public function add_menus()
		{
			gNetwork()->adminbar->add_menus();
			do_action( 'add_admin_bar_menus' );
		}
	}

	return __NAMESPACE__.'\\WP_Admin_Bar';
}

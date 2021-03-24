<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

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

			$this->action( 'sidebar_admin_setup' );
			$this->action( 'load-toplevel_page_wpcf7' );

		} else if ( $this->show_adminbar() ) {

			$this->setup_adminbar();

			$this->filter( 'shortcode_atts_wpcf7', 4 );

		} else {

			show_admin_bar( FALSE );
		}
	}

	protected function setup_adminbar()
	{
		add_filter( 'wp_admin_bar_class', __NAMESPACE__.'\\wp_admin_bar_class' );

		$this->action( 'wp_before_admin_bar_render' );
	}

	// overrided to avoid `get_blogs_of_user()`
	// overrided to avoid core styles
	public function initialize()
	{
		$user = new \stdClass;

		if ( is_multisite() && ( $user_id = get_current_user_id() ) ) {
			$super_admin       = WordPress::isSuperAdmin();
			$user->blogs       = WordPress::getAllSites( ( $super_admin ? FALSE : $user_id ), $super_admin );
			$user->active_blog = get_user_meta( $user_id, 'primary_blog', TRUE );
		} else {
			$user->blogs       = [];
			$user->active_blog = FALSE;
		}

		if ( current_theme_supports( 'admin-bar' ) ) {
			$admin_bar_args = get_theme_support( 'admin-bar' );

			if ( ! empty( $admin_bar_args[0]['callback'] ) )
				add_action( 'wp_head',  $admin_bar_args[0]['callback'] );
		}

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

		add_action( 'admin_bar_menu', 'wp_admin_bar_site_menu', 30 );
		add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_posttypes' ], 32 );
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

		if ( ! is_multisite() )
			return;

		if ( ! function_exists( 'user_has_networks' ) )
			add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_my_sites_menu' ], 25 );
		else
			add_action( 'admin_bar_menu', [ $this, 'wp_admin_bar_my_network_menu' ], 25 );
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

	// FIXME: not working anymore, shortcodes are too late for `admin_bar_menu` hook @since WP v5.4.0
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
		// $network_url = Network::menuURL();
		$admin_url   = Admin::menuURL();

		$parent_id = static::BASE.'-info';
		$group_id  = $parent_id.'-sub';

		$wp_admin_bar->add_node( [
			'id'     => $parent_id,
			'title'  => self::getIcon( 'admin-generic' ),
			'parent' => 'top-secondary',
			'href'   => $admin_url,
			'meta'   => [ 'title' => sprintf( 'gNetwork v%s', GNETWORK_VERSION ) ],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $parent_id,
			'id'     => static::BASE.'-debug',
			'title'  => _x( 'Display Errors', 'Modules: AdminBar: Nodes', 'gnetwork' ),
			'href'   => add_query_arg( 'debug', '', $current_url ),
			'meta'   => [ 'title' => _x( 'Display debug info for the current page', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $parent_id,
			'id'     => static::BASE.'-flush',
			'title'  => _x( 'Flush Cached', 'Modules: AdminBar: Nodes', 'gnetwork' ),
			'href'   => add_query_arg( 'flush', '', $current_url ),
			'meta'   => [ 'title' => _x( 'Flush cached data for the current page', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $parent_id,
			'id'     => static::BASE.'-flushrewrite',
			'title'  => _x( 'Flush Permalinks', 'Modules: AdminBar: Nodes', 'gnetwork' ),
			'href'   => add_query_arg( static::BASE.'_action', 'flushrewrite', $current_url ),
			'meta'   => [ 'title' => _x( 'Removes rewrite rules and then recreate rewrite rules', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
		] );

		if ( defined( 'GNETWORK_WPLANG' )
			&& ! is_network_admin()
			&& WordPress::isDev()
			&& class_exists( __NAMESPACE__.'\\Locale' ) ) {

			$wp_admin_bar->add_node( [
				'parent' => $parent_id,
				'id'     => static::BASE.'-locale',
				'title'  => _x( 'Change Locale', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'   => FALSE, // $this->get_menu_url( 'locale' ),
				'meta'   => [ 'title' => _x( 'Quickly change current blog language', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
			] );

			$current_locale = get_user_locale();

			foreach ( Locale::available() as $locale ) {

				if ( $current_locale == $locale )
					continue;

				$wp_admin_bar->add_node( [
					'parent' => static::BASE.'-locale',
					'id'     => static::BASE.'-locale-'.$locale,
					'title'  => $locale,
					'href'   => add_query_arg( [
						static::BASE.'_action' => 'locale',
						'locale'              => $locale,
					], $current_url ),
				] );
			}
		}

		if ( class_exists( __NAMESPACE__.'\\Debug' ) ) {

			if ( $calls = gNetwork()->debug->get_http_calls() ) {

				$wp_admin_bar->add_node( [
					'parent' => $parent_id,
					'id'     => static::BASE.'-api-calls',
					'title'  => _x( 'HTTP Calls', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				] );

				foreach (  $calls as $offset => $call ) {

					$url = URL::parse( $call['url'] );

					$wp_admin_bar->add_node( [
						'parent' => static::BASE.'-api-calls',
						'id'     => static::BASE.'-api-calls-'.$offset,
						'title'  => $call['method'].': '.$url['base'],
						'href'   => $call['url'],
					] );

					foreach ( $url['query'] as $key => $val )
						$wp_admin_bar->add_node( [
							'parent' => static::BASE.'-api-calls-'.$offset,
							'id'     => static::BASE.'-api-calls-'.$offset.'-'.$key,
							'title'  => sprintf( '%s: %s', (string) $key, maybe_serialize( $val ) ),
						] );
				}
			}
		}

		if ( ! is_admin() && is_singular() ) {

			if ( $post = get_queried_object() ) {

				$object = get_post_type_object( $post->post_type );

				$wp_admin_bar->add_node( [
					'parent' => $parent_id,
					'id'     => static::BASE.'-current-post',
					'title'  => _x( 'Current Post', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => WordPress::getPostEditLink( $post->ID ),
				] );

				$wp_admin_bar->add_node( [
					'parent' => static::BASE.'-current-post',
					'id'     => static::BASE.'-current-post-rest',
					'title'  => _x( 'Rest Endpoint', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => rest_url( sprintf( '/wp/v2/%s/%d', $object->rest_base, $post->ID ) ),
				] );

				$wp_admin_bar->add_node( [
					'parent' => static::BASE.'-current-post',
					'id'     => static::BASE.'-current-post-embed',
					'title'  => _x( 'Embed Endpoint', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => get_post_embed_url( $post ),
				] );
			}
		}

		$wp_admin_bar->add_group( [
			'parent' => $parent_id,
			'id'     => $group_id,
			'meta'   => [ 'class' => 'ab-sub-secondary' ],
		] );

		do_action_ref_array( static::BASE.'_adminbar_action', [ &$wp_admin_bar, $parent_id, $group_id, $current_url ] );

		if ( $this->sidebar_admin )
			$wp_admin_bar->add_node( [
				'parent' => $group_id,
				'id'     => static::BASE.'-reset-sidebars',
				'title'  => _x( 'Reset Sidebars', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'   => add_query_arg( static::BASE.'_action', 'resetsidebars', $current_url ),
				'meta'   => [ 'title' => _x( 'Deletes settings of all current sidebar widgets, be careful!', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
			] );

		if ( $this->wpcf7_admin )
			$wp_admin_bar->add_node( [
				'parent' => $group_id,
				'id'     => static::BASE.'-wpcf7-messages',
				'title'  => _x( 'Reset Messages', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'   => add_query_arg( static::BASE.'_action', 'resetwpcf7messages', $current_url ),
				'meta'   => [ 'title' => _x( 'Resets all saved messages for this form, be careful!', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
			] );

		if ( class_exists( __NAMESPACE__.'\\Cron' ) ) {

			// cron module is admin only
			if ( is_admin() && $status = gNetwork()->cron->get_status() )
				$wp_admin_bar->add_node( [
					'parent' => $group_id,
					'id'     => static::BASE.'-cron-status',
					'title'  => strip_tags( $status ),
					'href'   => $this->get_menu_url( 'cron', 'admin', 'tools' ),
				] );
		}

		$wp_admin_bar->add_node( [
			'parent' => $group_id,
			'id'     => static::BASE.'-info-pagenow',
			'title'  => 'PageNow: '.( empty( $pagenow ) ? 'EMPTY' : $pagenow ),
			'href'   => GNETWORK_ANALOG_LOG ? $this->get_menu_url( 'analoglogs', 'network', 'tools' ) : FALSE,
			'meta'   => [ 'title' => _x( 'Check System Logs', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $group_id,
			'id'     => static::BASE.'-info-queries',
			'title'  => self::stat( '%dq | %.3fs | %.2fMB' ),
			'href'   => GNETWORK_DEBUG_LOG ? $this->get_menu_url( 'errorlogs', 'network', 'tools' ) : FALSE,
			'meta'   => [ 'title' => _x( 'Queries | Timer Stop | Memory Usage', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
		] );
	}

	public function wp_admin_bar_search_menu( $wp_admin_bar )
	{
		if ( is_admin() )
			return;

		$form = '<form action="'.GNETWORK_SEARCH_URL.'" method="get" id="adminbarsearch">';
		$form.= '<input class="adminbar-input" name="'.GNETWORK_SEARCH_QUERYID.'" id="adminbar-search" type="text" value="" maxlength="150" />';
		$form.= '<label for="adminbar-search" class="screen-reader-text">'._x( 'Search', 'Modules: AdminBar: Nodes', 'gnetwork' ).'</label>';
		$form.= '<input type="submit" class="adminbar-button" value="'._x( 'Search', 'Modules: AdminBar: Nodes', 'gnetwork' ).'"/>';
		$form.= '</form>';

		$wp_admin_bar->add_node( [
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
		if ( defined( 'GNETWORK_IS_CUSTOM_404' ) && GNETWORK_IS_CUSTOM_404 )
			return;

		if ( is_admin() || ! is_singular() || is_front_page() )
			return;

		if ( function_exists( 'is_buddypress' ) && is_buddypress() )
			return;

		if ( ! $short = wp_get_shortlink( 0, 'query' ) )
			return;

		$wp_admin_bar->add_node( [
			'id'    => 'get-shortlink',
			'title' => self::getIcon( 'admin-links' ),
			'href'  => $short,
			'meta'  => [
				'html'  => '<input class="shortlink-input" type="text" readonly="readonly" value="'.HTML::escape( $short ).'" />',
				'title' => _x( 'Shortlink', 'Modules: AdminBar: Nodes', 'gnetwork' ),
			],
		] );
	}

	public function wp_admin_bar_wpcf7_shortcode( $wp_admin_bar )
	{
		if ( is_admin() || ! is_singular() || is_front_page() )
			return;

		$wp_admin_bar->add_node( [
			'id'    => 'edit-contact-form',
			'title' => _x( 'Edit Contact Form', 'Modules: AdminBar: Nodes', 'gnetwork' ),
			'href'  => add_query_arg( [ 'page' => 'wpcf7', 'post' => $this->wpcf7_shortcode ], admin_url( 'admin.php' ) ),
			'meta'  => [ 'title' => _x( 'Edit Current embeded contact form on admin', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
		] );
	}

	// TODO: merge with multi-network
	public function wp_admin_bar_my_sites_menu( $wp_admin_bar )
	{
		if ( ! is_user_logged_in() )
			return;

		$super_admin = WordPress::isSuperAdmin();

		if ( count( $wp_admin_bar->user->blogs ) < 1 && ! $super_admin )
			return;

		$my_sites = admin_url( 'my-sites.php' );

		if ( $wp_admin_bar->user->active_blog && isset( $wp_admin_bar->user->blogs[$wp_admin_bar->user->active_blog] ) )
			$my_sites = $wp_admin_bar->user->blogs[$wp_admin_bar->user->active_blog]->siteurl.'/wp-admin/my-sites.php';

		$wp_admin_bar->add_node( [
			'id'    => 'my-sites',
			'title' => '', // more minimal!
			'href'  => $my_sites,
			'meta'  => [ 'title' => _x( 'My Sites', 'Modules: AdminBar: Nodes', 'gnetwork' ) ],
		] );

		$wp_admin_bar->add_group( [
			'parent' => 'my-sites',
			'id'     => 'my-sites-admin',
		] );

		$wp_admin_bar->add_node( [
			'parent' => 'my-sites-admin',
			'id'     => 'user-admin',
			'title'  => '<div class="blavatar -user"></div>'._x( 'My Dashboard', 'Modules: AdminBar: Nodes', 'gnetwork' ),
			'href'   => user_admin_url(),
		] );

		if ( $super_admin ) {

			$wp_admin_bar->add_node( [
				'parent' => 'my-sites-admin',
				'id'     => 'network-admin',
				'title'  => '<div class="blavatar -network"></div>'._x( 'Network Admin', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'   => network_admin_url(),
			] );

			if ( class_exists( __NAMESPACE__.'\\Debug' ) )
				$wp_admin_bar->add_node( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-sr',
					'title'  => _x( 'System Report', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => $this->get_menu_url( 'overview', 'network', 'tools' ),
				] );

			if ( current_user_can( 'manage_sites' ) ) {

				$wp_admin_bar->add_node( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-s',
					'title'  => _x( 'Sites', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => network_admin_url( 'sites.php' ),
				] );
			}

			if ( current_user_can( 'manage_network_users' ) ) {

				$wp_admin_bar->add_node( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-u',
					'title'  => _x( 'Users', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => network_admin_url( 'users.php' ),
				] );
			}

			if ( current_user_can( 'manage_network_themes' ) ) {

				$wp_admin_bar->add_node( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-t',
					'title'  => _x( 'Themes', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => network_admin_url( 'themes.php' ),
				] );
			}

			if ( current_user_can( 'manage_network_plugins' ) ) {

				$wp_admin_bar->add_node( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-p',
					'title'  => _x( 'Plugins', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => network_admin_url( 'plugins.php' ),
				] );
			}

			if ( current_user_can( 'manage_network_options' ) ) {

				$wp_admin_bar->add_node( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-o',
					'title'  => _x( 'Settings', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => network_admin_url( 'settings.php' ),
				] );

				$wp_admin_bar->add_node( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-ne',
					'title'  => _x( 'Extras', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => $this->get_menu_url( FALSE, 'network' ),
				] );
			}

			if ( current_user_can( 'update_core' ) )
				$wp_admin_bar->add_node( [
					'parent' => 'network-admin',
					'id'     => 'network-admin-uc',
					'title'  => _x( 'Updates', 'Modules: AdminBar: Nodes', 'gnetwork' ),
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
			$blogname = WordPress::getSiteName( $blog->userblog_id );

			if ( ! $blogname )
				$blogname = URL::untrail( $blog->domain.$blog->path );

			$wp_admin_bar->add_node( [
				'parent'    => 'my-sites-list',
				'id'        => $menu_id,
				'title'     => '<div class="blavatar"></div>'.$blogname,
				'href'      => $blog->siteurl.'/wp-admin/',
			] );

			$wp_admin_bar->add_node( [
				'parent' => $menu_id,
				'id'     => $menu_id.'-d',
				'title'  => _x( 'Dashboard', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'      => $blog->siteurl.'/wp-admin/',
			] );

			// extra links for super admins only (no cap checks)
			if ( $super_admin ) {

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e',
					'title'  => _x( 'Posts', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => $blog->siteurl.'/wp-admin/edit.php',
				] );

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-u',
					'title'  => _x( 'Users', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => $blog->siteurl.'/wp-admin/users.php',
				] );

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-s',
					'title'  => _x( 'Settings', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => $blog->siteurl.'/wp-admin/options-general.php',
				] );

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e-s',
					'title'  => _x( 'Edit Site', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => network_admin_url( 'site-info.php?id='.$blog->userblog_id ),
				] );

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e-t',
					'title'  => _x( 'Edit Themes', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => network_admin_url( 'site-themes.php?id='.$blog->userblog_id ),
				] );
			}

			$wp_admin_bar->add_node( [
				'parent' => $menu_id,
				'id'     => $menu_id.'-v',
				'title'  => _x( 'Visit Site', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'   => URL::trail( $blog->siteurl ),
			] );
		}
	}

	public function wp_admin_bar_my_network_menu( $wp_admin_bar )
	{
		if ( ! is_user_logged_in() )
			return;

		// fallback
		if ( ! $networks = user_has_networks() )
			return $this->wp_admin_bar_my_sites_menu( $wp_admin_bar );

		// assumed the list from `user_has_networks()` have privileges!
		$super_admin = WordPress::isSuperAdmin();

		foreach ( $networks as $network_id ) {

			// has internal cache!
			if ( ! $network = get_network( $network_id ) )
				continue;

			$node = 'network-'.URL::prepTitle( str_replace( '.', '-', $network->domain ) );

			$wp_admin_bar->add_node( [
				'id'    => $node,
				'title' => self::getIcon( 'networking' ).'<span class="screen-reader-text">'.$network->site_name.'</span>',
				'href'  => WordPress::networkSiteURL( $network ),
				'meta'  => [ 'class' => $this->classs( 'network-node' ) ],
			] );

			$wp_admin_bar->add_group( [
				'parent' => $node,
				'id'     => 'network-links-'.$network->id,
			] );

			$wp_admin_bar->add_node( [
				'parent' => 'network-links-'.$network->id,
				'id'     => 'network-info-'.$network->id,
				'title'  => '<div class="blavatar -site"></div>'.$network->site_name,
				'href'   => $this->get_menu_url( 'overview', 'network', 'tools', [], 'admin', $network ),
				'meta'   => [ 'class' => $this->classs( 'network-title' ) ],
			] );

			$wp_admin_bar->add_node( [
				'parent' => 'network-links-'.$network->id,
				'id'     => 'user-admin-'.$network->id,
				'title'  => '<div class="blavatar -user"></div>'._x( 'My Dashboard', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'   => WordPress::userAdminURL( $network ),
			] );

			if ( $super_admin ) {

				$wp_admin_bar->add_node( [
					'parent' => 'network-links-'.$network->id,
					'id'     => 'network-admin-'.$network->id,
					'title'  => '<div class="blavatar -network"></div>'._x( 'Network Admin', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => WordPress::networkAdminURL( $network ),
				] );

				if ( current_user_can( 'manage_sites' ) ) {

					$wp_admin_bar->add_node( [
						'parent' => 'network-admin-'.$network->id,
						'id'     => 'network-admin-s-'.$network->id,
						'title'  => _x( 'Sites', 'Modules: AdminBar: Nodes', 'gnetwork' ),
						'href'   => WordPress::networkAdminURL( $network, 'sites.php' ),
					] );
				}

				if ( current_user_can( 'manage_network_users' ) ) {

					$wp_admin_bar->add_node( [
						'parent' => 'network-admin-'.$network->id,
						'id'     => 'network-admin-u-'.$network->id,
						'title'  => _x( 'Users', 'Modules: AdminBar: Nodes', 'gnetwork' ),
						'href'   => WordPress::networkAdminURL( $network, 'users.php' ),
					] );
				}

				if ( current_user_can( 'manage_network_themes' ) ) {

					$wp_admin_bar->add_node( [
						'parent' => 'network-admin-'.$network->id,
						'id'     => 'network-admin-t-'.$network->id,
						'title'  => _x( 'Themes', 'Modules: AdminBar: Nodes', 'gnetwork' ),
						'href'   => WordPress::networkAdminURL( $network, 'themes.php' ),
					] );
				}

				if ( current_user_can( 'manage_network_plugins' ) ) {

					$wp_admin_bar->add_node( [
						'parent' => 'network-admin-'.$network->id,
						'id'     => 'network-admin-p-'.$network->id,
						'title'  => _x( 'Plugins', 'Modules: AdminBar: Nodes', 'gnetwork' ),
						'href'   => WordPress::networkAdminURL( $network, 'plugins.php' ),
					] );
				}

				if ( current_user_can( 'manage_network_options' ) ) {

					$wp_admin_bar->add_node( [
						'parent' => 'network-admin-'.$network->id,
						'id'     => 'network-admin-o-'.$network->id,
						'title'  => _x( 'Settings', 'Modules: AdminBar: Nodes', 'gnetwork' ),
						'href'   => WordPress::networkAdminURL( $network, 'settings.php' ),
					] );

					$wp_admin_bar->add_node( [
						'parent' => 'network-admin-'.$network->id,
						'id'     => 'network-admin-ne-'.$network->id,
						'title'  => _x( 'Extras', 'Modules: AdminBar: Nodes', 'gnetwork' ),
						'href'   => $this->get_menu_url( FALSE, 'network', 'admin', [], 'admin', $network ),
					] );
				}

				if ( WordPress::isMainNetwork() && current_user_can( 'update_core' ) ) {

					$wp_admin_bar->add_node( [
						'parent' => 'network-admin-'.$network->id,
						'id'     => 'network-admin-uc-'.$network->id,
						'title'  => _x( 'Updates', 'Modules: AdminBar: Nodes', 'gnetwork' ),
						'href'   => WordPress::networkAdminURL( $network, 'update-core.php' ),
					] );
				}
			}

			$wp_admin_bar->add_group( [
				'parent' => $node,
				'id'     => 'network-list-'.$network->id,
				'meta'   => [ 'class' => 'ab-sub-secondary' ],
			] );
		}

		foreach ( $wp_admin_bar->user->blogs as $blog ) {

			// avoiding `switch_to_blog()`

			if ( ! $network = get_network( $blog->network_id ) )
				continue;

			$menu_id  = 'blog-'.$blog->userblog_id;
			$blogname = WordPress::getSiteName( $blog->userblog_id );

			if ( ! $blogname )
				$blogname = URL::untrail( $blog->domain.$blog->path );

			$wp_admin_bar->add_node( [
				'parent'    => 'network-list-'.$network->id,
				'id'        => $menu_id,
				'title'     => '<div class="blavatar"></div>'.$blogname,
				'href'      => $blog->siteurl.'/wp-admin/',
			] );

			$wp_admin_bar->add_node( [
				'parent' => $menu_id,
				'id'     => $menu_id.'-d',
				'title'  => _x( 'Dashboard', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'      => $blog->siteurl.'/wp-admin/',
			] );

			// extra links for super admins only (no cap checks)
			if ( $super_admin ) {

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e',
					'title'  => _x( 'Posts', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => $blog->siteurl.'/wp-admin/edit.php',
				] );

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-u',
					'title'  => _x( 'Users', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => $blog->siteurl.'/wp-admin/users.php',
				] );

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-s',
					'title'  => _x( 'Settings', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => $blog->siteurl.'/wp-admin/options-general.php',
				] );

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e-s',
					'title'  => _x( 'Edit Site', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => WordPress::networkAdminURL( $network, 'site-info.php?id='.$blog->userblog_id ),
				] );

				$wp_admin_bar->add_node( [
					'parent' => $menu_id,
					'id'     => $menu_id.'-e-t',
					'title'  => _x( 'Edit Themes', 'Modules: AdminBar: Nodes', 'gnetwork' ),
					'href'   => WordPress::networkAdminURL( $network, 'site-themes.php?id='.$blog->userblog_id ),
				] );
			}

			$wp_admin_bar->add_node( [
				'parent' => $menu_id,
				'id'     => $menu_id.'-v',
				'title'  => _x( 'Visit Site', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'   => URL::trail( $blog->siteurl ),
			] );
		}
	}

	public function wp_admin_bar_wp_menu( $wp_admin_bar )
	{
		// custom menu by filter, it's better 'cause there are no default wp menu.
		if ( $custom = apply_filters( static::BASE.'_adminbar_custom', NULL ) )
			return call_user_func_array( $custom, [ &$wp_admin_bar ] );

		self::addMainLogo( $wp_admin_bar );

		$menu = class_exists( __NAMESPACE__.'\\Navigation' )
			? Navigation::getGlobalMenu( GNETWORK_NETWORK_ADMINBAR )
			: FALSE;

		if ( $menu && is_array( $menu ) ) {

			foreach ( $menu as $item_id => $item ) {

				$wp_admin_bar->add_node( [
					'parent' => ( empty( $item->target ) ? ( empty( $item->menu_item_parent ) ? 'wp-logo' : 'network-menu-'.$item->menu_item_parent ) : 'wp-logo-external' ),
					'id'     => 'network-menu-'.$item->ID,
					'title'  => $item->title,
					'href'   => $item->url,
					'meta'   => [
						'title' => $item->attr_title,
						'class' => HTML::prepClass( $item->classes ),
					],
				] );
			}
		}

		self::addLoginRegister( $wp_admin_bar );
	}

	public static function addMainLogo( $wp_admin_bar, $id = 'wp-logo', $title = '<span class="ab-icon"></span>' )
	{
		$wp_admin_bar->add_node( [
			'id'    => $id,
			'title' => $title.'<span class="screen-reader-text">'.gNetwork()->brand( 'name' ).'</span>',
			'href'  => gNetwork()->brand( 'url' ),
		] );
	}

	public static function addLoginRegister( $wp_admin_bar, $parent = 'wp-logo-external' )
	{
		if ( ! GNETWORK_ADMINBAR_LOGIN )
			return;

		if ( is_user_logged_in() )
			return;

		$wp_admin_bar->add_node( [
			'parent' => $parent,
			'id'     => 'network-login',
			'title'  => _x( 'Log in', 'Modules: AdminBar: Nodes', 'gnetwork' ),
			'href'   => WordPress::loginURL(),
		] );

		if ( $register_url = WordPress::registerURL() )
			$wp_admin_bar->add_node( [
				'parent' => $parent,
				'id'     => 'network-register',
				'title'  => _x( 'Register', 'Modules: AdminBar: Nodes', 'gnetwork' ),
				'href'   => $register_url,
			] );
	}

	public function wp_admin_bar_extra_menu( $wp_admin_bar )
	{
		if ( ! class_exists( __NAMESPACE__.'\\Navigation' ) )
			return;

		$menu = Navigation::getGlobalMenu( GNETWORK_NETWORK_EXTRAMENU );

		if ( $menu && is_array( $menu ) ) {

			$parent = static::BASE.'-extramenu';

			$wp_admin_bar->add_node( [
				// 'parent' => 'top-secondary', // off on the right side
				'id'     => $parent,
				'title'  => self::getIcon( 'menu' ),
				'href'   => FALSE,
			] );

			foreach ( $menu as $item_id => $item ) {

				if ( ( empty( $item->xfn ) ?: current_user_can( $item->xfn ) ) ) {

					$wp_admin_bar->add_node( [
						// check target to place link on externals
						// 'parent' => ( empty( $item->target ) ? ( empty( $item->menu_item_parent ) ? 'wp-logo' : 'network-menu-'.$item->menu_item_parent ) : 'wp-logo-external' ),
						'parent' => $parent,
						'id'     => 'network-extramenu-'.$item->ID,
						'title'  => $item->title,
						'href'   => $item->url,
						'meta'   => [
							'title' => $item->attr_title,
							'class' => HTML::prepClass( $item->classes ),
						],
					] );
				}
			}
		}
	}

	public function wp_admin_bar_posttypes( $wp_admin_bar )
	{
		$posttypes = (array) get_post_types( [ 'show_in_admin_bar' => TRUE ], 'objects' );

		foreach ( $posttypes as $posttype )
			if ( current_user_can( $posttype->cap->edit_posts ) )
				$wp_admin_bar->add_node( [
					'parent' => 'site-name',
					'id'     => sprintf( 'all-%s', $posttype->rest_base ?: $posttype->name ),
					'title'  => $posttype->labels->menu_name,
					'href'   => 'post' == $posttype->name ? admin_url( 'edit.php' ) : admin_url( 'edit.php?post_type='.$posttype->name ),
				] );
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

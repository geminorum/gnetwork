<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\User as WPUser;

class User extends gNetwork\Module
{

	protected $key  = 'user';
	protected $cron = TRUE;

	protected function setup_actions()
	{
		$this->filter( 'authenticate', 3, 50 );

		if ( ! is_multisite() )
			return TRUE;

		if ( is_user_admin() ) {
			$this->action( 'user_admin_menu', 0, 12 );
			$this->action( 'user_admin_menu', 0, 999, 'late' );
			$this->filter( 'contextual_help', 3, 999 );
		}

		if ( $this->options['network_roles'] ) {
			$this->action( 'wpmu_new_user' );
			$this->action( 'wp_login', 2 );
		}

		if ( $this->options['admin_user_edit'] ) {
			$this->filter( 'map_meta_cap', 4, 99 );
			$this->filter_true( 'enable_edit_any_user_configuration' );
		}

		if ( is_network_admin() ) {

			$this->filter( 'views_users-network' );
			$this->filter( 'users_list_table_query_args' );

			$this->filter( 'wpmu_users_columns' );
			$this->filter( 'manage_users_custom_column', 3 );

			$this->filter( 'manage_users-network_sortable_columns' );
		}

		$this->action( 'deleted_user' );

		// cron hook / executes only on the mainsite
		$this->action( 'update_network_counts' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'User', 'Modules: Menu Name', 'gnetwork' ) );

		if ( ! is_multisite() )
			return;

		if ( $this->options['network_roles'] )
			$this->register_tool( _x( 'Network Roles', 'Modules: Menu Name', 'gnetwork' ), 'roles' );
	}

	public function default_options()
	{
		return [
			'site_user_id'    => '0', // GNETWORK_SITE_USER_ID
			'site_user_role'  => 'editor', // GNETWORK_SITE_USER_ROLE
			'network_roles'   => '0',
			'admin_user_edit' => '0',
			'dashboard_sites' => '0',
			'dashboard_menu'  => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'site_user_id',
					'type'        => 'number',
					'title'       => _x( 'Site User ID', 'Modules: User: Settings', 'gnetwork' ),
					'description' => _x( 'ID of site user for the network.', 'Modules: User: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( WordPress::getUserEditLink( $this->options['site_user_id'], [], TRUE, FALSE ) ),
				],
				[
					'field'       => 'site_user_role',
					'type'        => 'select',
					'title'       => _x( 'Site User Role', 'Modules: User: Settings', 'gnetwork' ),
					'description' => _x( 'Default site user role for new sites on the network.', 'Modules: User: Settings', 'gnetwork' ),
					'default'     => 'editor',
					'values'      => [
						'administrator' => _x( 'Administrator', 'User role' ),
						'editor'        => _x( 'Editor', 'User role' ),
						'author'        => _x( 'Author', 'User role' ),
						'contributor'   => _x( 'Contributor', 'User role' ),
						'subscriber'    => _x( 'Subscriber', 'User role' ),
					],
				],
				[
					'field'       => 'network_roles',
					'title'       => _x( 'Network Roles', 'Modules: User: Settings', 'gnetwork' ),
					'description' => _x( 'Tries to automatically add each user to the network sites.', 'Modules: User: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'admin_user_edit',
					'title'       => _x( 'Administrator User Edit', 'Modules: User: Settings', 'gnetwork' ),
					'description' => _x( 'Allows site administrators to edit users of their sites.', 'Modules: User: Settings', 'gnetwork' ),
				],
			],
			'_dashboard' => [
				[
					'field'       => 'dashboard_sites',
					'title'       => _x( 'Dashboard Sites', 'Modules: User: Settings', 'gnetwork' ),
					'description' => _x( 'Displays current user list of sites on the user dashboard.', 'Modules: User: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'dashboard_menu',
					'title'       => _x( 'Dashboard User Menu', 'Modules: User: Settings', 'gnetwork' ),
					'description' => _x( 'Displays global user menu navigation on the user dashboard.', 'Modules: User: Settings', 'gnetwork' ),
				],
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		$emtpy = TRUE;

		echo $this->wrap_open_buttons();

		if ( ! WordPress::isMainNetwork() ) {

			// for multi-network only!

			Settings::submitButton( 'default_role_for_users', _x( 'Default Role for All Users', 'Modules: User', 'gnetwork' ), 'small', [
				'title' => _x( 'Adds all registered users on the main site with default role.', 'Modules: User', 'gnetwork' ),
			] );

			$emtpy = FALSE;
		}

		if ( $this->options['network_roles'] ) {

			echo HTML::tag( 'a', [
				'class' => 'button button-secondary button-small',
				'href'  => $this->get_menu_url( 'roles', NULL, 'tools' ),
				'title' => _x( 'View and set network roles here.', 'Modules: User', 'gnetwork' ),
			], _x( 'Network Roles', 'Modules: Menu Name', 'gnetwork' ) );

			$emtpy = FALSE;
		}

		if ( $emtpy )
			HTML::desc( _x( 'Network Roles are disabled.', 'Modules: User', 'gnetwork' ), TRUE, '-empty' );

		echo '</p>';
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['default_role_for_users'] ) ) {

			$this->check_referer( $sub, 'settings' );

			$count   = 0;
			$site_id = get_current_blog_id();
			$default = get_option( 'default_role', 'subscriber' );
			$users   = get_users( [ 'blog_id' => 0, 'fields' => 'ID' ] );

			foreach ( $users as $user_id ) {

				if ( WordPress::isSuperAdmin( $user_id ) )
					continue;

				if ( is_user_member_of_blog( $user_id, $site_id ) )
					continue;

				if ( ! add_user_to_blog( $site_id, $user_id, $default ) )
					continue;

				$count++;
			}

			WordPress::redirectReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );
		}
	}

	public function tools( $sub = NULL, $key = NULL )
	{
		parent::tools( $sub, 'roles' );
	}

	protected function tools_buttons( $sub = NULL )
	{
		$this->settings_buttons( $sub );
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( 'roles' == $sub ) {

			if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub, 'tools' );

				$roles = [];

				foreach ( $_POST['role'] as $blog_id => $role )
					if ( $role != 'none' )
						$roles[$blog_id] = $role;

				if ( isset( $_POST['update_sites_roles'] ) ) {

					$saved = get_network_option( NULL, $this->hook( 'roles' ), [] );

					if ( ! $this->update_sites_roles( $saved, $roles ) )
						WordPress::redirectReferer( 'wrong' );
				}

				$result = update_network_option( NULL, $this->hook( 'roles' ), $roles );

				WordPress::redirectReferer( $result ? 'updated' : 'error' );
			}
		}
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		$sites = WordPress::getAllSites();
		$roles = array_reverse( get_editable_roles() ); // NOTE: roles of the main site
		$saved = get_network_option( NULL, $this->hook( 'roles' ), [] );

		if ( empty( $sites ) )
			return HTML::desc( gNetwork()->na() );

		Settings::fieldSection( _x( 'Default User Roles', 'Modules: User: Settings', 'gnetwork' ), [
			_x( 'New users will receive these roles when activating their account. Existing users will receive these roles only if they have the current default role or no role at all for each particular site.', 'Modules: User: Settings', 'gnetwork' ),
			_x( 'Please note that the roles listed here are from the main site of your network. Also only public, non-mature and non-dashboard sites appear here.', 'Modules: User: Settings', 'gnetwork' ),
		] );

		echo '<table class="form-table">';

		foreach ( $sites as $site_id => $site ) {

			if ( ! $name = WordPress::getSiteName( $site_id ) )
				$name = URL::untrail( $site->domain.$site->path );

			$this->do_settings_field( [
				'field'      => $site_id,
				'type'       => 'role',
				'title'      => $name,
				'wrap'       => 'tr',
				'values'     => $roles,
				'options'    => $saved,
				'name_attr'  => 'role['.$site_id.']',
				'none_value' => 'none',
				'after'      => Settings::fieldAfterIcon(
					$site->siteurl.'/wp-admin/users.php',
					_x( 'View Users List', 'Modules: User: Settings', 'gnetwork' ),
					'admin-users'
				),
			] );
		}

		echo '</table>';

		$this->do_settings_field( [
			'field'       => 'update_sites_roles',
			'name_attr'   => 'update_sites_roles',
			'type'        => 'checkbox',
			'description' => _x( 'Also Update Roles for Current Users', 'Modules: User: Settings', 'gnetwork' ),
		] );

		return TRUE;
	}

	public function user_admin_menu()
	{
		do_action( $this->base.'_setup_menu', 'user' );

		$hook = add_menu_page(
			_x( 'Network Extras', 'Modules: User: Page Menu', 'gnetwork' ),
			_x( 'Extras', 'Modules: User: Page Menu', 'gnetwork' ),
			'exist',
			$this->base,
			[ $this, 'settings_page' ],
			'dashicons-screenoptions',
			120
		);

		add_action( 'load-'.$hook, [ $this, 'settings_load' ] );

		foreach ( $this->get_menus() as $priority => $group )
			foreach ( $group as $sub => $args )
				add_submenu_page( $this->base,
					/* translators: %s: menu title */
					sprintf( _x( 'gNetwork Extras: %s', 'Modules: User: Page Menu', 'gnetwork' ), $args['title'] ),
					$args['title'],
					$args['cap'],
					$this->base.'&sub='.$sub,
					[ $this, 'settings_page' ]
				);
	}

	public function user_admin_menu_late()
	{
		$GLOBALS['submenu'][$this->base][0] = [
			_x( 'Overview', 'Modules: Menu Name', 'gnetwork' ),
			'exist',
			$this->base,
			_x( 'Network Extras', 'Modules: User: Page Menu', 'gnetwork' ),
		];
	}

	// removes help tabs on user admin
	public function contextual_help( $old_help, $screen_id, $screen )
	{
		$screen->remove_help_tabs();
		return $old_help;
	}

	public static function menuURL( $full = TRUE, $context = 'settings', $scheme = 'admin', $network = NULL )
	{
		$relative = 'admin.php?page='.static::BASE;

		return $full
			? WordPress::userAdminURL( $network, $relative, $scheme )
			: $relative;
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'read', $priority = 10 )
	{
		if ( ! is_user_admin() )
			return;

		gNetwork()->user->menus['settings'][intval( $priority )][$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

		if ( $callback )
			add_action( static::BASE.'_user_settings', $callback );
	}

	public function settings_load()
	{
		$sub = Settings::sub( 'overview' );

		if ( 'overview' !== $sub )
			$GLOBALS['submenu_file'] = $this->base.'&sub='.$sub;

		do_action( $this->base.'_user_settings', $sub );
	}

	public function settings_page()
	{
		$uri  = self::menuURL( FALSE );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'settings_subs', $this->get_subs() );

		Settings::wrapOpen( $sub );

		if ( $this->cucSub( $sub ) ) {

			Settings::sideOpen( NULL, $uri, $sub, $subs );
			Settings::message( $this->filters( 'settings_messages', Settings::messages(), $sub ) );

			if ( 'overview' == $sub )
				$this->settings_overview( $uri );

			else if ( 'console' == $sub )
				@require_once( GNETWORK_DIR.'includes/Layouts/console.'.$this->key.'.php' );

			else if ( ! $this->actions( 'settings_sub_'.$sub, $uri, $sub ) )
				Settings::cheatin();

			Settings::sideClose();

		} else {

			Settings::cheatin();
		}

		Settings::wrapClose();
	}

	protected function settings_overview( $uri )
	{
		// TODO
		// add setting option for page
		// display page content as over view
	}

	// @REF: https://medium.com/@omarkasem/login-with-phone-number-in-woocommerce-wordpress-f7d6d07964d8
	public function authenticate( $user, $username, $password )
	{
		if ( $user instanceof \WP_User || empty( $username ) || empty( $password ) )
			return $user;

		if ( ! $mobile = WPUser::getObjectbyMeta( 'mobile', $username ) )
			return $user;

		if ( wp_check_password( $password, $mobile->user_pass, $mobile->ID ) )
			return $mobile;

		return $user;
	}

	public function wpmu_new_user( $user_id )
	{
		$roles = get_network_option( NULL, $this->hook( 'roles' ), [] );

		if ( empty( $roles ) )
			return;

		foreach ( $roles as $blog_id => $role )
			if ( ! is_user_member_of_blog( $user_id, $blog_id ) )
				add_user_to_blog( $blog_id, $user_id, $role );

		update_user_meta( $user_id, $this->hook( 'roles' ), '1' );
	}

	public function wp_login( $user_login, $user )
	{
		if ( ! get_user_meta( $user->ID, $this->hook( 'roles' ), TRUE ) )
			$this->wpmu_new_user( $user->ID );
	}

	private function update_sites_roles( $old, $new )
	{
		foreach ( WordPress::getAllSites() as $site_id => $site ) {

			if ( empty( $new[$site_id] ) && empty( $old[$site_id] ) )
				continue;

			switch_to_blog( $site_id );

			$users = empty( $old[$site_id] )
				? WordPress::getUsersWithNoRole( $site_id )
				: WordPress::getUsersWithRole( $old[$site_id], $site_id );

			foreach ( $users as $user_id ) {

				if ( WordPress::isSuperAdmin( $user_id ) )
					continue;

				if ( ! $user = get_userdata( $user_id ) )
					continue;

				$user->set_role( empty( $new[$site_id] ) ? '' : $new[$site_id] );

				clean_user_cache( $user_id );
			}

			wp_cache_delete( $site_id.'_user_count', 'blog-details' );
		}

		restore_current_blog();

		return TRUE;
	}

	// Adopted from: WP User Edit by John James Jacoby v0.1.0 - 2017-11-16
	// @REF: https://github.com/stuttter/wp-user-edit
	public function map_meta_cap( $caps = [], $cap = '', $user_id = 0, $args = [] )
	{
		switch ( $cap ) {

			case 'edit_user':
			case 'edit_users':
			case 'manage_network_users':

				// allow user to edit themselves
				if ( ( 'edit_user' === $cap ) && isset( $args[0] ) && ( $user_id === $args[0] ) )
					break;

				// if previously not allowed, undo it; we'll check our own way
				if ( FALSE !== ( $index = array_search( 'do_not_allow', $caps ) ) )
					unset( $caps[$index] );

				// FIXME: WTF?
				// if multisite, user must be a member of the site
				if ( is_multisite() && isset( $args[0] ) && ! is_user_member_of_blog( $args[0] ) ) {
					$caps[] = 'do_not_allow';

				// admins cannot modify super admins
				} else if ( isset( $args[0] ) && WordPress::isSuperAdmin( $args[0] ) ) {
					$caps[] = 'do_not_allow';

				// fallback on `edit_users`
				} else {
					$caps[] = 'edit_users';
				}

				break;
		}

		return $caps;
	}

	public function update_network_counts( $network_id = NULL )
	{
		global $wpdb;

		update_network_option( NULL, $this->hook( 'spam_count' ), $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users} WHERE spam = '1' AND deleted = '0'" ) );
	}

	public function deleted_user()
	{
		update_network_option( NULL, $this->hook( 'spam_count' ), '' );
	}

	public function get_spam_count()
	{
		return get_network_option( NULL, $this->hook( 'spam_count' ) );
	}

	public function views_users_network( $views )
	{
		// FIXME: remove current class from other views
		$class = isset( $_GET['spam'] ) ? ' class="current"' : '';

		$view = '<a href="'.network_admin_url( 'users.php?spam' ).'"'.$class.'>';

		if ( $spams = $this->get_spam_count() )
			/* translators: %s: spam users count */
			$view.= Utilities::getCounted( $spams, _nx( 'Marked as Spam <span class="count">(%s)</span>', 'Marked as Spams <span class="count">(%s)</span>', $spams, 'Modules: User', 'gnetwork' ) ).'</a>';
		else
			$view.= _x( 'Marked as Spam', 'Modules: User', 'gnetwork' ).'</a>';

		return array_merge( $views, [ 'spam' => $view ] );
	}

	public function users_list_table_query_args( $args )
	{
		if ( isset( $_GET['spam'] ) )
			$this->action( 'pre_user_query' );

		// default sorting
		if ( empty( $args['orderby'] ) ) {
			$args['orderby'] = 'user_registered';
			if ( empty( $args['order'] ) )
				$args['order'] = 'DESC';
		}

		return $args;
	}

	public function pre_user_query( &$user_query )
	{
		global $wpdb;
		$user_query->query_where.= " AND {$wpdb->users}.spam = '1'";
	}

	// defaults: 'cb', 'username', 'name', 'email', 'registered', 'blogs'
	public function wpmu_users_columns( $columns )
	{
		$columns = Arraay::insert( $columns, [
			'extra' => _x( 'Extra', 'Modules: User', 'gnetwork' ),
		], 'username', 'after' );

		unset( $columns['name'], $columns['email'], $columns['registered'] );

		return array_merge( $columns, [
			'timestamps' => _x( 'Timestamps', 'Modules: User', 'gnetwork' ),
		] );
	}

	public function manage_users_custom_column( $empty, $column_name, $user_id )
	{
		if ( 'timestamps' == $column_name )
			$this->render_timestamps( $user_id );

		else if ( 'extra' == $column_name )
			$this->render_extra( $user_id );

		else
			return $empty;
	}

	private function render_extra( $user_id )
	{
		$user = get_user_by( 'id', $user_id );

		echo '<ul class="-rows">';

		if ( $user->first_name || $user->last_name ) {
			echo '<li class="-row -name">';
				echo $this->get_column_icon( FALSE, 'nametag', _x( 'Name', 'Modules: User', 'gnetwork' ) );
				printf( '%s %s', $user->first_name, $user->last_name );
			echo '</li>';
		}

		if ( $customs = get_user_meta( $user->ID, 'custom_display_name', TRUE ) ) {

			$blogs = get_blogs_of_user( $user->ID, TRUE );

			foreach ( $customs as $blog => $custom ) {

				$blogname = empty( $blogs[$blog] ) ? '#'.$blog : $blogs[$blog]->blogname;

				echo '<li class="-row -name">';
					echo $this->get_column_icon( FALSE, 'nametag', _x( 'Custom Name', 'Modules: User', 'gnetwork' ) );
					/* translators: %1$s: blog name, %2$s: custom display name */
					printf( _x( 'In %1$s as: %2$s', 'Modules: User: Custom Name', 'gnetwork' ), $blogname, $custom );
				echo '</li>';
			}
		}

		if ( $user->user_email ) {
			echo '<li class="-row -email">';
				echo $this->get_column_icon( FALSE, 'email', _x( 'Email', 'Modules: User', 'gnetwork' ) );
				echo HTML::mailto( $user->user_email );
			echo '</li>';
		}

		if ( $user->user_url ) {
			echo '<li class="-row -url">';
				echo $this->get_column_icon( FALSE, 'admin-links', _x( 'URL', 'Modules: User', 'gnetwork' ) );
				echo HTML::link( URL::prepTitle( $user->user_url ), $user->user_url );
			echo '</li>';
		}

		echo '</ul>';
	}

	private function render_timestamps( $user_id )
	{
		$html = '';
		$mode = empty( $_REQUEST['mode'] ) ? 'list' : $_REQUEST['mode'];

		$user        = get_user_by( 'id', $user_id );
		$lastlogin   = get_user_meta( $user->ID, 'lastlogin', TRUE );
		$register_ip = get_user_meta( $user->ID, 'register_ip', TRUE );

		$registered = strtotime( get_date_from_gmt( $user->user_registered ) );
		$lastlogged = $lastlogin ? strtotime( get_date_from_gmt( $lastlogin ) ) : NULL;

		$html.= '<table></tbody>';

		$html.= '<tr><td>'._x( 'Registered', 'Modules: User', 'gnetwork' ).'</td><td><code title="'
			.Utilities::dateFormat( $registered, 'timeampm' ).'">'
			.Utilities::dateFormat( $registered ).'</code></td></tr>';

		$html.= '<tr><td>'._x( 'Last Login', 'Modules: User', 'gnetwork' ).'</td><td>'
			.( $lastlogin ? '<code title="'.Utilities::dateFormat( $lastlogged, 'timeampm' ).'">'
				.Utilities::dateFormat( $lastlogged ).'</code>'
			: gNetwork()->na() ).'</td></tr>';

		if ( function_exists( 'bp_get_user_last_activity' ) ) {

			if ( $lastactivity = bp_get_user_last_activity( $user->ID ) )
				$lastactive = strtotime( get_date_from_gmt( $lastactivity ) );

			$html.= '<tr><td>'._x( 'Last Activity', 'Modules: User', 'gnetwork' ).'</td><td>'
				.( $lastactivity
					? '<code title="'.bp_core_time_since( $lastactivity ).'">'
						.Utilities::dateFormat( $lastactive )
					: '<code>'.gNetwork()->na( FALSE ) )
				.'</code></td></tr>';
		}

		$html.= '<tr><td>'._x( 'Register IP', 'Modules: User', 'gnetwork' ).'</td><td><code>'
			.( $register_ip ? gnetwork_ip_lookup( $register_ip ) : gNetwork()->na( FALSE ) ).'</code></td></tr>';

		$html.= '</tbody></table>';

		echo $html;
	}

	public function manage_users_network_sortable_columns( $sortable_columns )
	{
		return array_merge( $sortable_columns, [
			'timestamps' => 'user_registered',
			'extra'      => 'user_email',
		] );
	}
}

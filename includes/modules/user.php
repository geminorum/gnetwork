<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class User extends ModuleCore
{

	protected $key = 'user';

	protected $installing = TRUE;

	public $menus = array();

	protected function setup_actions()
	{
		if ( is_admin() ) {

			if ( ! $this->options['user_locale'] ) {
				$this->filter( 'admin_body_class' );
				$this->filter( 'insert_user_meta', 3, 8 );
			}

		} else {
			$this->action( 'get_header' );
		}

		if ( $this->options['contact_methods'] )
			$this->filter( 'user_contactmethods', 2 );

		if ( ! is_multisite() )
			return TRUE;

		if ( is_user_admin() ) {
			add_action( 'user_admin_menu', array( $this, 'user_admin_menu' ), 12 );
			add_action( 'user_admin_menu', array( $this, 'user_admin_menu_late' ), 999 );
		}

		if ( $this->options['blog_roles'] ) {
			$this->action( 'wpmu_new_user' );
			$this->action( 'wp_login', 2 );
		}
	}

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'User', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( is_multisite() && $this->options['blog_roles'] )
			Network::registerMenu( 'roles',
				_x( 'Roles', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN )
			);
	}

	public function default_options()
	{
		return array(
			'blog_roles'      => '0',
			'contact_methods' => '1',
			'user_locale'     => '0',
			'dashboard_sites' => '0',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'blog_roles',
					'title'       => _x( 'Blog Roles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Automatically adds each user to blogs', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'contact_methods',
					'title'       => _x( 'Contact Methods', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds extra contact methods to user profiles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				),
				array(
					'field'       => 'user_locale',
					'title'       => _x( 'User Language', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'User admin language switcher', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://core.trac.wordpress.org/ticket/29783' ),
				),
				array(
					'field'       => 'dashboard_sites',
					'title'       => _x( 'Dashboard Sites', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays current user list of sites on the user dashboard.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
				),
			),
		);
	}

	public function settings( $sub = NULL )
	{
		if ( $this->key == $sub ) {

			parent::settings( $sub );

		} else if ( 'roles' == $sub ) {

			if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub );

				$roles = array();

				foreach ( $_POST['role'] as $blog_id => $role )
					if ( $role != 'none' )
						$new_roles[$blog_id] = $role;

				if ( isset( $_POST['update_sites_roles'] ) ) {

					$saved = get_site_option( $this->hook( 'roles' ), array() );

					if ( ! $this->update_sites_roles( $saved, $roles ) )
						WordPress::redirectReferer( 'wrong' );
				}

				WordPress::redirectReferer( ( update_site_option( $this->hook( 'roles' ), $roles ) ? 'updated' : 'error' ) );
			}

			add_action( $this->settings_hook( $sub ), array( $this, 'settings_form' ), 10, 2 );

			$this->register_settings_buttons( $sub );
			$this->register_settings_help( $sub );
		}
	}

	public function settings_form( $uri, $sub = 'general' )
	{
		if ( $this->key == $sub ) {

			parent::settings_form( $uri, $sub );

		} else if ( 'roles' == $sub ) {

			$this->settings_form_before( $uri, $sub, 'bulk', FALSE );

				if ( $this->tableBlogRoles() )
					$this->settings_buttons( $sub );

			$this->settings_form_after( $uri, $sub );
		}
	}

	private function tableBlogRoles()
	{
		$blogs = $this->get_sites();
		$roles = get_site_option( $this->hook( 'roles' ), array() );

		if ( empty( $blogs ) )
			return HTML::desc( _x( 'No sites available.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ) );

		Settings::fieldSection(
			_x( 'Default User Roles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'New users will receive these roles when activating their account. Existing users will receive these roles only if they have the current default role or no role at all for each particular site.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN )
		);

		echo '<table class="form-table">';

		foreach( $blogs as $blog ) {

			switch_to_blog( $blog->blog_id );

			$this->do_settings_field( array(
				'field'      => $blog->blog_id,
				'type'       => 'role',
				'title'      => get_bloginfo( 'name' ),
				'wrap'       => TRUE,
				'options'    => $roles,
				'name_attr'  => 'role['.$blog->blog_id.']',
				'none_value' => 'none',
				'after'      => Settings::fieldAfterIcon(
					URL::untrail( $blog->domain.$blog->path ).'/wp-admin/users.php',
					_x( 'View Users List', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'admin-users'
				),
			) );

			restore_current_blog();
		}

		echo '</table>';

		HTML::desc( _x( '<b>Note:</b> only public, non-mature and non-dashboard sites appear here.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ) );

		$this->do_settings_field( array(
			'field'       => 'update_sites_roles',
			'name_attr'   => 'update_sites_roles',
			'type'        => 'checkbox',
			'description' => _x( 'Also Update Current Users Roles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
		) );

		return TRUE;
	}

	public function user_admin_menu()
	{
		do_action( $this->base.'_setup_menu', 'user' );

		$hook = add_menu_page(
			_x( 'Network Extras', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Extras', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ),
			'exist',
			$this->base,
			array( $this, 'settings_page' ),
			'dashicons-screenoptions',
			120
		);

		add_action( 'load-'.$hook, array( $this, 'settings_load' ) );

		foreach ( $this->menus as $sub => $args ) {
			add_submenu_page( $this->base,
				sprintf( _x( 'gNetwork Extras: %s', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
				$args['title'],
				$args['cap'],
				$this->base.'&sub='.$sub,
				array( $this, 'settings_page' )
			);
		}
	}

	public function user_admin_menu_late()
	{
		$GLOBALS['submenu'][$this->base][0] = array(
			_x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			'exist',
			$this->base,
			_x( 'Network Extras', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ),
		);
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'read' )
	{
		if ( ! is_user_admin() )
			return;

		gNetwork()->user->menus[$sub] = array(
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		);

		if ( $callback ) // && is_callable( $callback ) )
			add_action( 'gnetwork_user_settings', $callback );
	}

	public function settings_load()
	{
		if ( ( $sub = isset( $_REQUEST['sub'] ) ? $_REQUEST['sub'] : NULL ) )
			$GLOBALS['submenu_file'] = $this->base.'&sub='.$sub;

		do_action( $this->base.'_user_settings', $sub );
	}

	private function subs()
	{
		$subs = array();

		$subs['overview'] = _x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );

		foreach ( $this->menus as $sub => $args )
			if ( WordPress::cuc( $args['cap'] ) )
				$subs[$sub] = $args['title'];

		if ( WordPress::isSuperAdmin() )
			$subs['console'] = _x( 'Console', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );

		return $subs;
	}

	public function settings_page()
	{
		$uri  = Settings::userURL( FALSE );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'settings_subs', $this->subs() );

		Settings::wrapOpen( $sub, $this->base, 'settings' );

		if ( 'overview' == $sub
			|| ( 'console' == $sub && WordPress::isSuperAdmin() )
			|| ( isset( $this->menus[$sub] ) && WordPress::cuc( $this->menus[$sub]['cap'] ) ) ) {

			$messages = $this->filters( 'settings_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' );
			else
				$this->actions( 'settings_sub_'.$sub, $uri, $sub );

		} else {

			Settings::cheatin();
		}

		Settings::wrapClose();
	}

	public function user_contactmethods( $contactmethods, $user )
	{
		return array_merge( $contactmethods, array(
			'googleplus' => _x( 'Google+ Profile', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'twitter'    => _x( 'Twitter', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'mobile'     => _x( 'Mobile Phone', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
		) );
	}

	public function admin_body_class( $classes )
	{
		return $classes.' hide-userlocale-option';
	}

	public function insert_user_meta( $meta, $user, $update )
	{
		if ( $update )
			delete_user_meta( $user->ID, 'locale' );

		unset( $meta['locale'] );

		return $meta;
	}

	private function get_sites()
	{
		global $wpdb;

		$list = array();

		$blogs = $wpdb->get_results( $wpdb->prepare( "
			SELECT blog_id, domain, path
			FROM {$wpdb->blogs}
			WHERE site_id = %d
			AND spam = '0'
			AND deleted = '0'
			AND archived = '0'
			ORDER BY registered ASC
		", $wpdb->siteid ), ARRAY_A );

		foreach ( (array) $blogs as $details )
			$list[$details['blog_id']] = (object) $details;

		return $list;
	}

	public function wpmu_new_user( $user_id )
	{
		$roles = get_site_option( $this->hook( 'roles' ), array() );

		if ( ! count( $roles ) )
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
		foreach ( $this->get_sites() as $blog ) {

			if ( ! isset( $new[$blog->blog_id] )
				&& ! isset( $old[$blog->blog_id] ) )
					continue;

			switch_to_blog( $blog->blog_id );

			$users = isset( $old[$blog->blog_id] )
				? $this->get_users_with_role( $old[$blog->blog_id] )
				: wp_get_users_with_no_role();

			foreach ( $users as $user_id ) {

				if ( WordPress::isSuperAdmin( $user_id ) )
					continue;

				if ( $user = get_userdata( $user_id ) ) {

					$user->set_role(
						( isset( $new[$blog->blog_id] )
							? $new[$blog->blog_id]
							: ''
						)
					);

					wp_cache_delete( $user_id, 'users' );
				}
			}

			wp_cache_delete( $blog_id.'_user_count', 'blog-details' );
			restore_current_blog();
		}

		return TRUE;
	}

	// @REF: `wp_get_users_with_no_role()`
	private function get_users_with_role( $role )
	{
		global $wpdb;

		$prefix = $wpdb->get_blog_prefix();
		// $regex  = implode( '|', array_keys( wp_roles()->get_names() ) );
		$regex  = $role;
		$regex  = preg_replace( '/[^a-zA-Z_\|-]/', '', $regex );
		$users  = $wpdb->get_col( $wpdb->prepare( "
			SELECT user_id
			FROM $wpdb->usermeta
			WHERE meta_key = '{$prefix}capabilities'
			AND meta_value REGEXP %s
		", $regex ) );

		return $users;
	}

	public function get_header( $name )
	{
		if ( 'wp-signup' == $name ) {

			remove_action( 'wp_head', 'wpmu_signup_stylesheet' );

			add_action( 'wp_head', function(){
				Utilities::linkStyleSheet( GNETWORK_URL.'assets/css/signup.all.css' );
			} );

		} else if ( 'wp-activate' == $name ) {

			remove_action( 'wp_head', 'wpmu_activate_stylesheet' );

			add_action( 'wp_head', function(){
				Utilities::linkStyleSheet( GNETWORK_URL.'assets/css/activate.all.css' );
			} );
		}
	}
}

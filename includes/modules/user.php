<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class User extends gNetwork\Module
{

	protected $key = 'user';

	protected function setup_actions()
	{
		if ( is_admin() ) {

			if ( ! $this->options['user_locale'] )
				$this->filter( 'insert_user_meta', 3, 8 );

		} else {

			if ( $this->options['tos_display'] ) {
				$this->action( 'before_signup_header' ); // multisite signup
				$this->action( 'bp_init' ); // buddypress
			}
		}

		if ( $this->options['contact_methods'] )
			$this->filter( 'user_contactmethods', 2 );

		if ( ! is_multisite() )
			return TRUE;

		if ( is_user_admin() ) {
			$this->action( 'user_admin_menu', 0, 12 );
			$this->action( 'user_admin_menu', 0, 999, 'late' );
			$this->filter( 'contextual_help', 3, 999 );
		}

		if ( $this->options['blog_roles'] ) {
			$this->action( 'wpmu_new_user' );
			$this->action( 'wp_login', 2 );
		}

		if ( $this->options['admin_user_edit'] ) {
			$this->filter( 'map_meta_cap', 4, 99 );
			$this->filter_true( 'enable_edit_any_user_configuration' );
		}
	}

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'User', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);

		if ( is_multisite() && $this->options['blog_roles'] )
			Network::registerMenu( 'roles',
				_x( 'Roles', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN )
			);
	}

	public function default_options()
	{
		return [
			'blog_roles'      => '0',
			'admin_user_edit' => '0',
			'contact_methods' => '1',
			'user_locale'     => '0',
			'dashboard_sites' => '0',
			'dashboard_menu'  => '0',

			'tos_display' => '0',
			'tos_title'   => '',
			'tos_link'    => '',
			'tos_text'    => '',
			'tos_label'   => '',
			'tos_must'    => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'blog_roles',
					'title'       => _x( 'Blog Roles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Automatically adds each user to blogs', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'admin_user_edit',
					'title'       => _x( 'Administrator User Edit', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Allows site administrators to edit users of their sites.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'contact_methods',
					'title'       => _x( 'Contact Methods', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds extra contact methods to user profiles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'user_locale',
					'title'       => _x( 'User Language', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'User admin language switcher', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://core.trac.wordpress.org/ticket/29783' ),
				],
				[
					'field'       => 'dashboard_sites',
					'title'       => _x( 'Dashboard Sites', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays current user list of sites on the user dashboard.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'dashboard_menu',
					'title'       => _x( 'Dashboard User Menu', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays global user menu navigation on the user dashboard.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
			'_tos' => [
				[
					'field' => 'tos_display',
					'title' => _x( 'Display ToS', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'tos_title',
					'type'        => 'text',
					'title'       => _x( 'ToS Title', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Section Title, Usually : Terms of Service', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'Terms of Service', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text',
				],
				[
					'field'       => 'tos_link',
					'type'        => 'url',
					'title'       => _x( 'ToS Link', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'URL for section title link to actual agreement text', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'tos_text',
					'type'        => 'textarea',
					'title'       => _x( 'ToS Text', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Full text of the agreement', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text',
				],
				[
					'field'       => 'tos_label',
					'type'        => 'text',
					'title'       => _x( 'ToS Label', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Label next to the mandatory checkbox, below full text', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'By checking the Terms of Service Box you have read and agree to all the Policies set forth in this site\'s Terms of Service.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text',
				],
				[
					'field'       => 'tos_must',
					'type'        => 'text',
					'title'       => _x( 'ToS Must', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Error message upon not checking the box', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'You have to accept our terms of service. Otherwise we cannot register you on our site.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => 'large-text',
				],
			],
		];
	}

	public function settings_section_tos()
	{
		Settings::fieldSection(
			_x( 'Terms of Service', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'ToS Settings on Registration Page', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	// FIXME: needs better UX
	public function settings_sidebox( $sub, $uri )
	{
		echo $this->wrap_open_buttons( '-sidebox' );

			Settings::submitButton( 'export_users_csv', _x( 'Export Users', 'Modules: User', GNETWORK_TEXTDOMAIN ), 'small' );
			HTML::desc( _x( 'Get all users in a CSV file.', 'Modules: User', GNETWORK_TEXTDOMAIN ), FALSE );

		echo '</p>';
	}

	public function settings_help_tabs( $sub = NULL )
	{
		return [
			[
				'id'      => $this->classs( 'help' ),
				'title'   => _x( 'Contact Methods', 'Modules: User: Help Tab Title', GNETWORK_TEXTDOMAIN ),
				'content' => HTML::tableCode( wp_get_user_contact_methods() ),
			],
		];
	}

	public function settings( $sub = NULL )
	{
		if ( $this->key == $sub ) {

			if ( isset( $_POST['export_users_csv'] ) ) {

				$this->check_referer( $sub );

				Text::download( $this->get_csv_users(), File::prepName( 'users.csv' ) );

				WordPress::redirectReferer( 'wrong' );

			} else {
				parent::settings( $sub );
			}

		} else if ( 'roles' == $sub ) {

			if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub );

				$roles = [];

				foreach ( $_POST['role'] as $blog_id => $role )
					if ( $role != 'none' )
						$new_roles[$blog_id] = $role;

				if ( isset( $_POST['update_sites_roles'] ) ) {

					$saved = get_site_option( $this->hook( 'roles' ), [] );

					if ( ! $this->update_sites_roles( $saved, $roles ) )
						WordPress::redirectReferer( 'wrong' );
				}

				WordPress::redirectReferer( ( update_site_option( $this->hook( 'roles' ), $roles ) ? 'updated' : 'error' ) );
			}

			add_action( $this->settings_hook( $sub ), [ $this, 'settings_form' ], 10, 2 );

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
		$roles = get_site_option( $this->hook( 'roles' ), [] );

		if ( empty( $blogs ) )
			return HTML::desc( gNetwork()->na() );

		Settings::fieldSection(
			_x( 'Default User Roles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'New users will receive these roles when activating their account. Existing users will receive these roles only if they have the current default role or no role at all for each particular site.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN )
		);

		echo '<table class="form-table">';

		foreach( $blogs as $blog ) {

			switch_to_blog( $blog->blog_id );

			$this->do_settings_field( [
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
			] );

			restore_current_blog();
		}

		echo '</table>';

		HTML::desc( _x( '<b>Note:</b> only public, non-mature and non-dashboard sites appear here.', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ) );

		$this->do_settings_field( [
			'field'       => 'update_sites_roles',
			'name_attr'   => 'update_sites_roles',
			'type'        => 'checkbox',
			'description' => _x( 'Also Update Current Users Roles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
		] );

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
			[ $this, 'settings_page' ],
			'dashicons-screenoptions',
			120
		);

		add_action( 'load-'.$hook, [ $this, 'settings_load' ] );

		foreach ( $this->menus() as $priority => $group )
			foreach ( $group as $sub => $args )
				add_submenu_page( $this->base,
					sprintf( _x( 'gNetwork Extras: %s', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
					$args['title'],
					$args['cap'],
					$this->base.'&sub='.$sub,
					[ $this, 'settings_page' ]
				);
	}

	public function user_admin_menu_late()
	{
		$GLOBALS['submenu'][$this->base][0] = [
			_x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			'exist',
			$this->base,
			_x( 'Network Extras', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ),
		];
	}

	// removes help tabs on user admin
	public function contextual_help( $old_help, $screen_id, $screen )
	{
		$screen->remove_help_tabs();
		return $old_help;
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'read', $priority = 10 )
	{
		if ( ! is_user_admin() )
			return;

		gNetwork()->user->menus[intval( $priority )][$sub] = [
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		];

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
		$subs = [ 'overview' => _x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) ];

		foreach ( $this->menus() as $priority => $group )
			foreach ( $group as $sub => $args )
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

		if ( $this->cucSub( $sub ) ) {

			$messages = $this->filters( 'settings_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' );

			else if ( ! $this->actions( 'settings_sub_'.$sub, $uri, $sub ) )
				Settings::cheatin();

		} else {

			Settings::cheatin();
		}

		Settings::wrapClose();
	}

	public function user_contactmethods( $contactmethods, $user )
	{
		return array_merge( $contactmethods, [
			'mobile'     => _x( 'Mobile Phone', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'twitter'    => _x( 'Twitter', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'facebook'   => _x( 'Facebook', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'googleplus' => _x( 'Google+', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
		] );
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

		$query = $wpdb->prepare( "
			SELECT blog_id, domain, path
			FROM {$wpdb->blogs}
			WHERE site_id = %d
			AND spam = '0'
			AND deleted = '0'
			AND archived = '0'
			ORDER BY registered ASC
		", get_current_network_id() );

		$blogs = [];

		foreach ( (array) $wpdb->get_results( $query, ARRAY_A ) as $blog )
			$blogs[$blog['blog_id']] = (object) $blog;

		return $blogs;
	}

	public function wpmu_new_user( $user_id )
	{
		$roles = get_site_option( $this->hook( 'roles' ), [] );

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

		$users = $wpdb->get_col( $wpdb->prepare( "
			SELECT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = '{$prefix}capabilities'
			AND meta_value REGEXP %s
		", $regex ) );

		return $users;
	}

	public function before_signup_header()
	{
		$this->action( 'signup_extra_fields', 1, 20 );
		$this->filter( 'wpmu_validate_user_signup', 1, 20 );
	}

	public function bp_init()
	{
		$this->action( 'bp_before_registration_submit_buttons' );
		$this->filter( 'bp_core_validate_user_signup' );
	}

	public function signup_extra_fields( $errors )
	{
		$this->tos_form( $errors );
	}

	public function bp_before_registration_submit_buttons()
	{
		$this->tos_form( FALSE );
	}

	public function wpmu_validate_user_signup( $result )
	{
		if ( ! isset( $_POST['gnetwork_tos_agreement'] )
			|| 'accepted' != $_POST['gnetwork_tos_agreement'] )
				$result['errors']->add( 'gnetwork_tos', $this->options['tos_must'] );

		return $result;
	}

	public function bp_core_validate_user_signup( $result = [] )
	{
		if ( ! isset( $_POST['gnetwork_tos_agreement'] )
			|| 'accepted' != $_POST['gnetwork_tos_agreement'] )
				$GLOBALS['bp']->signup->errors['gnetwork_tos'] = $this->options['tos_must'];

		return $result;
	}

	// FALSE for buddypress
	private function tos_form( $errors = FALSE )
	{
		echo '<div style="clear:both;"></div><br />';
		echo '<div class="register-section register-section-tos checkbox gnetwork-wrap-tos">';

		$title = empty( $this->options['tos_title'] ) ? FALSE : $this->options['tos_title'];

		if ( $title && ! empty( $this->options['tos_link'] ) )
			printf( '<h4 class="-title"><a href="%1$s" title="%2$s">%3$s</a></h4>',
				esc_url( $this->options['tos_link'] ),
				_x( 'Read the full agreement', 'Modules: User', GNETWORK_TEXTDOMAIN ),
				$title
			);

		else if ( $title )
			printf( '<h4 class="-title">%s</h4>', $title );

		if ( FALSE === $errors ) {
			do_action( 'bp_gnetwork_tos_errors' );

		} else if ( $message = $errors->get_error_message( 'gnetwork_tos' ) ) {
			echo '<p class="error">'.$message.'</p>';
		}

		if ( ! empty( $this->options['tos_text'] ) ) {
			echo '<textarea class="-text no-autosize" readonly="readonly">';
				echo esc_textarea( $this->options['tos_text'] );
			echo '</textarea>';
		}

		if ( ! empty( $this->options['tos_label'] ) )
			echo '<label for="gnetwork-bp-tos">'
				.'<input type="checkbox" class="-checkbox" name="gnetwork_tos_agreement" value="accepted">&nbsp;'
					.$this->options['tos_label']
				.'</label>';

		echo '</div>';
	}

	// TODO: add support for BuddyPress fields
	// TODO: append contact methods
	// @REF: https://gist.github.com/boonebgorges/79b5d0f628a884cb3b3b
	private function get_csv_users()
	{
		global $wpdb;

		$header = [
			0 => 'Display Name',
			1 => 'Email',
			2 => 'Registration Date',
			// 3 => 'Institution',
		];

		$data   = [ $header ];
		$format = Utilities::dateFormats( 'default' );

		$users = $wpdb->get_results( "SELECT ID, user_email, user_registered, display_name, user_nicename FROM {$wpdb->users} WHERE user_status = 0" );

		foreach ( $users as $user ) {
			$row = [];

			$row[0] = empty( $user->display_name ) ? $user->user_nicename : $user->display_name;
			$row[1] = $user->user_email;
			$row[2] = date_i18n( $format, strtotime( $user->user_registered ) );
			// $row[3] = xprofile_get_field_data( 2, $user->ID );

			$data[] = $row;
		}

		return Text::toCSV( $data );
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
}

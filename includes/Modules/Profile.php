<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\Email;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\Media as WPMedia;

class Profile extends gNetwork\Module
{

	protected $key  = 'profile';
	protected $ajax = TRUE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 12 );
		$this->action( 'user_register' );

		if ( ! is_admin() ) {

			$this->action( 'before_signup_header', 0, 1 );

			if ( $this->options['redirect_signup_url'] ) {
				$this->filter( 'wp_signup_location', 1, 15 );
				$this->filter( 'bp_get_signup_page', 1, 15 );
				$this->filter( 'bp_get_activation_page', 1, 15 );
			}

			if ( $this->options['redirect_signup_after'] )
				$this->filter( 'registration_redirect', 1, 15 );
		}

		if ( $this->options['display_name_per_site'] ) {

			$this->action( 'personal_options', 1, 99 );

			if ( did_action( 'set_current_user' ) )
				$this->set_current_user();
			else
				$this->action( 'set_current_user', 1, 15 );

			$this->filter( 'the_author', 1, 12 );
			$this->filter( 'get_the_author_display_name', 2, 12 );
			$this->filter( 'get_comment_author', 3, 12 );

			$this->filter( 'p2_get_user_display_name', 1, 12 );
			$this->filter( 'p2_get_archive_author', 1, 12 );
		}

		if ( $this->options['disable_password_reset'] )
			$this->filter( 'allow_password_reset', 2 );

		if ( $this->options['contact_methods'] )
			$this->filter( 'user_contactmethods', 2, 9 );

		$this->filter( 'update_user_metadata', 5, 12 );
		$this->filter( 'get_user_metadata', 4, 12 );
		$this->filter( 'insert_user_meta', 3, 12 );

		add_filter( 'get_user_option_rich_editing', [ $this, 'get_user_option_option' ], 8, 3 );
		add_filter( 'get_user_option_comment_shortcuts', [ $this, 'get_user_option_option' ], 8, 3 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Profile', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'display_name_per_site'  => '0',
			'store_signup_ip'        => '1',
			'redirect_signup_url'    => '',
			'redirect_signup_after'  => '',
			'page_signup_disabled'   => '0',
			'default_colorscheme'    => '0',
			'disable_colorschemes'   => '0',
			'disable_password_reset' => '0',
			'contact_methods'        => '1',
			'user_locale'            => '0',
		];
	}

	public function default_settings()
	{
		$settings  = array_fill_keys( [ '_general', '_signup' ], [] );
		$multisite = is_multisite();

		$exclude = array_filter( [
			get_option( 'page_on_front' ),
			get_option( 'page_for_posts' ),
		] );

		if ( $multisite )
			$settings['_general'][] = [
				'field'       => 'display_name_per_site',
				'title'       => _x( 'Custom Display Name', 'Modules: Profile: Settings', 'gnetwork' ),
				'description' => _x( 'Enables custom display name per-site for each user.', 'Modules: Profile: Settings', 'gnetwork' ),
			];

		$settings['_general'][] = [
			'field'       => 'disable_password_reset',
			'type'        => 'disabled',
			'title'       => _x( 'Password Reset', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Controls whether to allow a password to be reset.', 'Modules: Profile: Settings', 'gnetwork' ),
		];

		$settings['_general'][] = [
			'field'       => 'disable_colorschemes',
			'type'        => 'disabled',
			'title'       => _x( 'Color Schemes', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Controls whether to allow a selection of admin color scheme.', 'Modules: Profile: Settings', 'gnetwork' ),
		];

		$settings['_general'][] = [
			'field'       => 'contact_methods',
			'title'       => _x( 'Contact Methods', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Adds extra contact methods to user profiles.', 'Modules: Profile: Settings', 'gnetwork' ),
			'default'     => '1',
		];

		$settings['_general'][] = [
			'field'       => 'user_locale',
			'title'       => _x( 'User Language', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Enables admin language switcher for each user.', 'Modules: Profile: Settings', 'gnetwork' ),
			'after'       => Settings::fieldAfterIcon( 'https://core.trac.wordpress.org/ticket/29783' ),
		];

		$settings['_signup'][] = [
			'field'       => 'page_signup_disabled',
			'type'        => 'page',
			'title'       => _x( 'Disabled Page', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Redirects network sign-up into this page, if registration have been <b>disabled</b>.', 'Modules: Profile: Settings', 'gnetwork' ),
			'default'     => '0',
			'exclude'     => $exclude,
			'after'       => Settings::fieldAfterNewPostType( 'page' ),
		];

		$settings['_signup'][] = [
			'field'       => 'redirect_signup_url',
			'type'        => 'url',
			'title'       => _x( 'Custom Location', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Full URL to the custom sign-up page.', 'Modules: Profile: Settings', 'gnetwork' ),
		];

		$settings['_signup'][] = [
			'field'       => 'redirect_signup_after',
			'type'        => 'url',
			'title'       => _x( 'Location After', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Redirects into this URL after a successful registraion.', 'Modules: Profile: Settings', 'gnetwork' ),
		];

		$settings['_signup'][] = [
			'field'       => 'store_signup_ip',
			'title'       => _x( 'IP Address', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Stores user\'s IP address upon registration.', 'Modules: Profile: Settings', 'gnetwork' ),
			'default'     => '1',
		];

		$settings['_signup'][] = [
			'field'       => 'default_colorscheme',
			'type'        => 'select',
			'title'       => _x( 'Default Color Scheme', 'Modules: Profile: Settings', 'gnetwork' ),
			'description' => _x( 'Sets as default upon each user registeration.', 'Modules: Profile: Settings', 'gnetwork' ),
			'values'      => [
				'0'         => _x( 'Default', 'Modules: Profile: Settings: Color Scheme', 'gnetwork' ),
				'light'     => _x( 'Light', 'Modules: Profile: Settings: Color Scheme', 'gnetwork' ),
				'blue'      => _x( 'Blue', 'Modules: Profile: Settings: Color Scheme', 'gnetwork' ),
				'coffee'    => _x( 'Coffee', 'Modules: Profile: Settings: Color Scheme', 'gnetwork' ),
				'ectoplasm' => _x( 'Ectoplasm', 'Modules: Profile: Settings: Color Scheme', 'gnetwork' ),
				'midnight'  => _x( 'Midnight', 'Modules: Profile: Settings: Color Scheme', 'gnetwork' ),
				'ocean'     => _x( 'Ocean', 'Modules: Profile: Settings: Color Scheme', 'gnetwork' ),
				'sunrise'   => _x( 'Sunrise', 'Modules: Profile: Settings: Color Scheme', 'gnetwork' ),
			],
		];

		return $settings;
	}

	public function settings_sidebox( $sub, $uri )
	{
		$wpupload = WordPress::upload();

		if ( ! empty( $wpupload['error'] ) ) {

			/* translators: %s: upload error */
			echo HTML::error( sprintf( _x( 'Before you can upload a file, you will need to fix the following error: %s', 'Modules: Profile', 'gnetwork' ), '<b>'.$wpupload['error'].'</b>' ), FALSE );

		} else {

			echo $this->wrap_open_buttons();

				$this->do_settings_field( [
					'type'      => 'file',
					'field'     => 'import_users_file',
					'name_attr' => $this->classs( 'import' ), // 'import',
					'values'    => [ '.csv' ],
				] );

				echo '<br />';

				$size = File::formatSize( apply_filters( 'import_upload_size_limit', wp_max_upload_size() ) );

				Settings::submitButton( 'import_users_csv', _x( 'Import Users', 'Modules: Profile', 'gnetwork' ), 'small' );
				/* translators: %s: maximum file size */
				HTML::desc( sprintf( _x( 'Upload a list of users in CSV. Maximum size: <b>%s</b>', 'Modules: Profile', 'gnetwork' ), HTML::wrapLTR( $size ) ), FALSE );

			echo '</p>';
			echo '<hr />';
		}

		echo $this->wrap_open_buttons();

			Settings::submitButton( 'export_users_csv', _x( 'Export Users', 'Modules: Profile', 'gnetwork' ), 'small' );
			HTML::desc( _x( 'Click to get all registered users in CSV.', 'Modules: Profile', 'gnetwork' ), FALSE );

		echo '</p>';
	}

	protected function register_help_tabs( $sub = NULL, $context = 'settings' )
	{
		return [
			[
				'id'      => $this->classs( 'help' ),
				'title'   => _x( 'Contact Methods', 'Modules: Profile: Help Tab Title', 'gnetwork' ),
				'content' => HTML::tableCode( wp_get_user_contact_methods() ),
			],
		];
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['import_users_csv'] ) ) {

			$this->check_referer( $sub, 'settings' );

			$file = WPMedia::handleImportUpload( $this->classs( 'import' ) );

			if ( ! $file || isset( $file['error'] ) || empty( $file['file'] ) )
				WordPress::redirectReferer( 'wrong' );

			$count = $this->import_users_csv( $file['file'], get_option( 'default_role' ) );

			WordPress::redirectReferer( [
				'message'    => 'imported',
				'count'      => $count,
				'attachment' => $file['id'],
			] );

		} else if ( isset( $_POST['export_users_csv'] ) ) {

			$this->check_referer( $sub, 'settings' );

			Text::download( $this->get_csv_users(), File::prepName( 'users.csv' ) );

			WordPress::redirectReferer( 'wrong' );
		}
	}

	private function import_users_csv( $file_path, $role )
	{
		$count = 0;

		// skiping ip on the imported
		$this->options['store_signup_ip'] = FALSE;

		$csv = new \ParseCsv\Csv();
		$csv->auto( File::normalize( $file_path ) );

		foreach ( $csv->data as $offset => $row ) {

			$data = self::args( (array) $row, [
				'user_email' => FALSE,
				'user_login' => FALSE,
				'role'       => $role,
			] );

			if ( $data['user_email'] )
				$data['user_email'] = strtolower( $data['user_email'] );

			if ( empty( $data['user_email'] ) || ! is_email( $data['user_email'] ) )
				continue;

			if ( email_exists( $data['user_email'] ) )
				continue;

			if ( $data['user_login'] )
				$data['user_login'] = strtolower( $data['user_login'] );

			if ( empty( $data['user_login'] ) )
				$data['user_login'] = Email::toUsername( $data['user_email'] );

			if ( empty( $data['user_login'] ) || ! validate_username( $data['user_login'] ) )
				continue;

			if ( username_exists( $data['user_login'] ) )
				continue;

			if ( ! empty( $data['display_name'] ) )
				$data['display_name'] = apply_filters( 'string_format_i18n', $data['display_name'] );

			if ( ! empty( $data['nickname'] ) )
				$data['nickname'] = apply_filters( 'string_format_i18n', $data['nickname'] );

			if ( $data['user_nicename'] == $data['user_login']
				&& ! empty( $data['display_name'] )
				&& $data['user_login'] != $data['display_name'] ) {

				$data['user_nicename'] = $this->sanitizeSlug( $data['display_name'] );
			}

			if ( empty( $data['nickname'] ) && ! empty( $data['display_name'] ) )
				$data['nickname'] = $data['display_name'];

			unset( $data['ID'], $data['id'] );

			$data['user_pass'] = wp_generate_password( 12, FALSE );

			$user_id = wp_insert_user( $data );

			if ( ! $user_id || is_wp_error( $user_id ) )
				continue;

			update_user_option( $user_id, 'default_password_nag', TRUE, TRUE );

			$count++;
		}

		return $count;
	}

	public function init()
	{
		if ( ! is_user_logged_in() )
			return;

		if ( $this->options['disable_colorschemes'] )
			remove_all_actions( 'admin_color_scheme_picker' );

		if ( ! current_user_can( 'edit_users' )
			&& get_user_meta( get_current_user_id(), 'disable_edit', TRUE ) ) {

			$this->action( 'admin_init', 0, 12, 'disable_edit' );
			$this->filter_false( 'edit_profile_url', 12 );
		}
	}

	public function admin_init_disable_edit()
	{
		remove_menu_page( 'profile.php' );

		$this->action( 'load-profile.php' );
		$this->action( 'admin_notices' );
	}

	// TODO: must check if user is member of the site
	public function load_profile_php()
	{
		WordPress::redirect( add_query_arg( [ static::BASE.'_action' => 'edit-profile-banned' ], admin_url( 'index.php' ) ) );
	}

	public function admin_notices()
	{
		if ( $this->is_action( 'edit-profile-banned' ) )
			echo HTML::warning( _x( 'Sorry, you are not allowed to edit your profile.', 'Modules: Profile', 'gnetwork' ) );
	}

	public function setup_screen( $screen )
	{
		if ( ! in_array( $screen->base, [ 'profile-user', 'profile-network', 'user-edit-network', 'profile', 'user-edit' ] ) )
			return;

		$this->action( 'personal_options_update' );
		$this->action( 'edit_user_profile_update' );
		$this->action( 'user_edit_form_tag', 0, 99 );
	}

	public function user_register( $user_id )
	{
		if ( $this->options['store_signup_ip']
			&& ( $ip = HTTP::normalizeIP( $_SERVER['REMOTE_ADDR'] ) ) )
				update_user_meta( $user_id, 'register_ip', $ip );

		if ( $this->options['default_colorscheme'] )
			wp_update_user( [
				'ID'          => $user_id,
				'admin_color' => $this->options['default_colorscheme'],
			] );

		// NO NEED: we're not going to let the meta stored in the first place!
		// @REF: http://wpengineer.com/2470/hide-welcome-panel-for-wordpress-multisite/
		// update_user_meta( $user_id, 'show_welcome_panel', 0 );
	}

	public function before_signup_header()
	{
		if ( $this->options['page_signup_disabled'] && 'none' == get_network_option( NULL, 'registration', 'none' ) )
			WordPress::redirect( get_page_link( $this->options['page_signup_disabled'] ), 303 );

		if ( $this->options['redirect_signup_url'] )
			WordPress::redirect( $this->options['redirect_signup_url'], 303 );
	}

	public function wp_signup_location( $url )
	{
		return $this->options['redirect_signup_url'];
	}

	public function bp_get_signup_page( $url )
	{
		return $this->options['redirect_signup_url'];
	}

	public function bp_get_activation_page( $url )
	{
		return $this->options['redirect_signup_url'];
	}

	public function registration_redirect( $url )
	{
		return $this->options['redirect_signup_after'];
	}

	// filters whether to allow a password to be reset
	public function allow_password_reset( $allow, $user_id )
	{
		if ( get_user_meta( $user_id, 'disable_password_reset', TRUE ) )
			return FALSE;

		return $allow;
	}

	public function user_contactmethods( $contactmethods, $user )
	{
		return array_merge( Arraay::stripByKeys( $contactmethods, [ 'aim', 'yim', 'jabber' ] ), [
			'mobile'    => _x( 'Mobile Phone', 'Modules: Profile: User Contact Method', 'gnetwork' ), // @SEE: `GNETWORK_COMMERCE_MOBILE_METAKEY`
			'twitter'   => _x( 'Twitter', 'Modules: Profile: User Contact Method', 'gnetwork' ),
			'facebook'  => _x( 'Facebook', 'Modules: Profile: User Contact Method', 'gnetwork' ),
			'instagram' => _x( 'Instagram', 'Modules: Profile: User Contact Method', 'gnetwork' ),
			'telegram'  => _x( 'Telegram', 'Modules: Profile: User Contact Method', 'gnetwork' ),
		] );
	}

	public function user_edit_form_tag()
	{
		global $profileuser;

		$current_time    = current_time( 'timestamp' );
		$store_lastlogin = gNetwork()->option( 'login', 'store_lastlogin', TRUE );

		echo '><h2>'._x( 'Account Information', 'Modules: Profile', 'gnetwork' ).'</h2>';
		echo '<table class="form-table">';

		if ( isset( $profileuser->register_ip )
			&& $profileuser->register_ip )
				$register_ip = '<code>'.gnetwork_ip_lookup( $profileuser->register_ip ).'</code>';
		else
			$register_ip = gNetwork()->na();

		echo '<tr class="register_ip"><th>'._x( 'Registration IP', 'Modules: Profile', 'gnetwork' )
			.'</th><td>'.$register_ip.'</td></tr>';

		$register_date = strtotime( get_date_from_gmt( $profileuser->user_registered ) );
		$register_on   = Utilities::dateFormat( $register_date, 'datetime' )
			.' <small><small><span class="description">('
			.Utilities::humanTimeAgo( $register_date, $current_time )
			.')</span></small></small>';

		echo '<tr class="register_date"><th>'
				._x( 'Registration on', 'Modules: Profile', 'gnetwork' )
			.'</th><td>'.$register_on.'</td></tr>';

		if ( $store_lastlogin || current_user_can( 'edit_users' ) ) {

			if ( isset( $profileuser->lastlogin ) && '' != $profileuser->lastlogin ) {
				$lastlogin_date = strtotime( get_date_from_gmt( $profileuser->lastlogin ) );
				$lastlogin = Utilities::dateFormat( $lastlogin_date, 'datetime' )
					.' <small><small><span class="description">('
					.Utilities::humanTimeAgo( $lastlogin_date, $current_time )
					.')</span></small></small>';
			} else {
				$lastlogin = gNetwork()->na();
			}

			echo '<tr class="last_login'.( $store_lastlogin ? '' : ' error' ).'"><th>'
					._x( 'Last Login', 'Modules: Profile', 'gnetwork' )
				.'</th><td>'
					.$lastlogin
					.( $store_lastlogin ? '' : ' &mdash; <strong>'._x( 'Last Logins are Disabled.', 'Modules: Profile', 'gnetwork' ).'</strong>' )
				.'</td></tr>';
		}

		if ( current_user_can( 'edit_users' ) ) {

			echo '</table><h2>'._x( 'Administrative Options', 'Modules: Profile', 'gnetwork' ).'</h2>';
			echo '<table class="form-table">';

			$nicename = $profileuser->user_login == $profileuser->user_nicename
				? $this->sanitizeSlug( $profileuser->display_name )
				: $profileuser->user_nicename;

			$name_nicename = $this->hook( 'nicename' );
			echo '<tr><th><label for="'.$name_nicename.'">'._x( 'Slug', 'Modules: Profile', 'gnetwork' )
				.'</label></th><td><input type="text" name="'.$name_nicename.'" id="'.$name_nicename.'" value="'
				.HTML::escape( $nicename ).'" class="regular-text" dir="ltr" /><p class="description">'.
					_x( 'This will be used in the URL of the user\'s page.', 'Modules: Profile', 'gnetwork' )
				.'</p></td></tr>';

			// prevents lockin himself out!
			if ( ! IS_PROFILE_PAGE ) {

				$name_disable = $this->hook( 'disable_user' );
				echo '<tr><th>'._x( 'Account Login', 'Modules: Profile', 'gnetwork' )
					.'</th><td><label for="'.$name_disable.'">'
					.'<input type="checkbox" name="'.$name_disable.'" id="'.$name_disable.'" value="1"';
						checked( 1, get_the_author_meta( 'disable_user', $profileuser->ID ) );
				echo ' /> '._x( 'Disable user login with this account', 'Modules: Profile', 'gnetwork' )
					.'</label></td></tr>';

				$edit_disable = $this->hook( 'disable_edit' );
				echo '<tr><th>'._x( 'Profile Edit', 'Modules: Profile', 'gnetwork' )
					.'</th><td><label for="'.$edit_disable.'">'
					.'<input type="checkbox" name="'.$edit_disable.'" id="'.$edit_disable.'" value="1"';
						checked( 1, get_the_author_meta( 'disable_edit', $profileuser->ID ) );
				echo ' /> '._x( 'Ban this user to edit profile', 'Modules: Profile', 'gnetwork' )
					.'</label></td></tr>';
			}

			if ( $this->options['disable_password_reset'] ) {

				$name_reset = $this->hook( 'password_reset' );
				echo '<tr><th>'._x( 'Password Reset', 'Modules: Profile', 'gnetwork' )
					.'</th><td><label for="'.$name_reset.'">'
					.'<input type="checkbox" name="'.$name_reset.'" id="'.$name_reset.'" value="1"';
						checked( 1, get_the_author_meta( 'disable_password_reset', $profileuser->ID ) );
				echo ' /> '._x( 'Disable this account password reset via default login page', 'Modules: Profile', 'gnetwork' )
					.'</label></td></tr>';
			}
		}

		echo '</table'; // YES, this is correct, check the hook!
	}

	protected function sanitizeSlug( $string )
	{
		// return sanitize_title( $string );
		return Utilities::URLifyFilter( $string );
	}

	public function personal_options_update( $user_id )
	{
		$this->edit_user_profile_update( $user_id );
	}

	public function edit_user_profile_update( $user_id )
	{
		if ( $nicename = self::req( $this->hook( 'nicename' ) ) ) {

			$user = get_user_by( 'id', $user_id );

			if ( $nicename == $user->user_login )
				$nicename = $this->sanitizeSlug( $nicename );

			if ( ! username_exists( $nicename ) )
				wp_update_user( [ 'ID' => $user_id, 'user_nicename' => $nicename ] );
		}

		if ( current_user_can( 'edit_users' ) ) {

			if ( self::req( $this->hook( 'disable_user' ) ) )
				update_user_meta( $user_id, 'disable_user', '1' );
			else
				delete_user_meta( $user_id, 'disable_user' );

			if ( self::req( $this->hook( 'disable_edit' ) ) )
				update_user_meta( $user_id, 'disable_edit', '1' );
			else
				delete_user_meta( $user_id, 'disable_edit' );

			if ( $this->options['disable_password_reset'] ) {

				if ( self::req( $this->hook( 'password_reset' ) ) )
					update_user_meta( $user_id, 'disable_password_reset', '1' );

				else
					delete_user_meta( $user_id, 'disable_password_reset' );
			}
		}

		if ( ! is_multisite() )
			return;

		if ( isset( $_POST['custom_display_name'] ) ) {

			$display_names = get_user_meta( $user_id, 'custom_display_name', TRUE );

			if ( empty( $display_names ) )
				$display_names = [];

			$site = get_current_blog_id();

			if ( empty( $_POST['custom_display_name'] ) )
				unset( $display_names[$site] );
			else
				$display_names[$site] = trim( $_POST['custom_display_name'] );

			update_user_meta( $user_id, 'custom_display_name', $display_names );
		}
	}

	public function personal_options( $profileuser )
	{
		if ( is_multisite() && ! is_network_admin() && ! is_user_admin() ) {

			echo '</table><h2>'._x( 'Blog Options', 'Modules: Profile', 'gnetwork' ).'</h2>';
			echo '<table class="form-table">';

			$site = get_current_blog_id();
			$name = empty( $profileuser->custom_display_name[$site] ) ? FALSE : $profileuser->custom_display_name[$site];

			echo '<tr><th><label for="custom_display_name">'
				._x( 'Nickname for this site', 'Modules: Profile', 'gnetwork' )
				.'</label></th><td><input type="text" name="custom_display_name" id="custom_display_name" value="'
				.( $name ? HTML::escape( $name ) : '' )
				.'" class="regular-text" /><p class="description">'
					._x( 'This will be displayed as your name in this site only.', 'Modules: Profile', 'gnetwork' )
				.'</p></td></tr>';

			echo '</table><table class="form-table">';
		}
	}

	// hook fire order changed @since WP 4.7.0
	// @SEE: https://make.wordpress.org/core/?p=20592
	public function set_current_user()
	{
		if ( ! is_user_logged_in() )
			return;

		global $current_user, $user_identity;

		$old = $current_user->display_name;
		$user_identity = $current_user->display_name = $this->get_display_name( $current_user->ID, $current_user->display_name );

		if ( $old != $user_identity )
			update_user_caches( $current_user );
	}

	public function the_author( $author = NULL )
	{
		if ( is_null( $author ) )
			return $author;

		global $authordata;

		return is_object( $authordata ) ? $this->get_display_name( $authordata->ID, $authordata->display_name ) : NULL;
	}

	public function get_the_author_display_name( $current, $user_id )
	{
		return $this->get_display_name( $user_id, $current );
	}

	public function get_display_name( $user_id, $current = '' )
	{
		if ( ! isset( $this->display_name[$user_id] ) )
			$this->display_name[$user_id] = get_user_meta( $user_id, 'custom_display_name', TRUE );

		$site = get_current_blog_id();

		if ( ! empty( $this->display_name[$user_id][$site] ) )
			return $this->display_name[$user_id][$site];

		return $current;
	}

	public function get_comment_author( $author, $comment_ID, $comment )
	{
		return empty( $comment->user_id ) ? $author : $this->get_display_name( $comment->user_id, $author );
	}

	public function p2_get_user_display_name( $current )
	{
		return $this->get_display_name( $GLOBALS['current_user']->ID, $current );
	}

	public function p2_get_archive_author( $current )
	{
		return is_author() ? $this->get_display_name( get_queried_object_id(), $current ) : $current;
	}

	public function update_user_metadata( $null, $object_id, $meta_key, $meta_value, $prev_value )
	{
		// prevents BP last activity back-comp
		// @SEE: http://wp.me/pLVLj-gc
		if ( function_exists( 'buddypress' ) && 'last_activity' === $meta_key )
			return TRUE;

		if ( array_key_exists( $meta_key, wp_get_user_contact_methods( $object_id ) ) ) {
			if ( ! $meta_value ) {

				if ( get_metadata( 'user', $object_id, $meta_key ) )
					delete_metadata( 'user', $object_id, $meta_key );

				return TRUE;
			}
		}

		return $null;
	}

	public function get_user_metadata( $null, $object_id, $meta_key, $single )
	{
		if ( 'show_welcome_panel' == $meta_key )
			return 0;

		return $null;
	}

	public function insert_user_meta( $meta, $user, $update )
	{
		if ( ! $update && isset( $meta['nickname'] ) && $user->user_login == $meta['nickname'] ) {

			// TODO: get default field from settings
			if ( ! empty( $meta['last_name'] ) )
				$meta['nickname'] = $meta['last_name'];
		}

		foreach ( $this->get_default_user_meta() as $key => $value ) {

			if ( isset( $meta[$key] ) && $value == $meta[$key] ) {

				unset( $meta[$key] );

				if ( $update )
					delete_user_meta( $user->ID, $key );
			}
		}

		if ( ! $this->options['user_locale'] ) {

			if ( $update )
				delete_user_meta( $user->ID, 'locale' );

			unset( $meta['locale'] );
		}

		return $meta;
	}

	private function get_default_user_meta()
	{
		return [
			'nickname'             => '',
			'first_name'           => '',
			'last_name'            => '',
			'description'          => '',
			'rich_editing'         => 'true',
			'comment_shortcuts'    => 'false',
			'admin_color'          => 'fresh',
			'use_ssl'              => 0,
			'show_admin_bar_front' => 'true',
			'locale'               => '',
		];
	}

	public function get_user_option_option( $result, $option, $user )
	{
		if ( FALSE === $result ) {
			$defaults = $this->get_default_user_meta();
			if ( isset( $defaults[$option] ) )
				return $defaults[$option];
		}

		return $result;
	}

	// TODO: add support for BuddyPress: `xprofile_get_field_data()`
	// @REF: https://gist.github.com/boonebgorges/79b5d0f628a884cb3b3b
	private function get_csv_users()
	{
		global $wpdb;

		$headers = [
			'ID',
			'user_login',
			'user_nicename',
			'user_email',
			'user_url',
			'user_registered',
			'display_name',
		];

		$metas = [
			'nickname',
			'first_name',
			'last_name',
			'description',
		];

		$contacts = array_keys( wp_get_user_contact_methods() );

		$data  = [ array_merge( $headers, $metas, $contacts ) ];
		$users = $wpdb->get_results( "SELECT * FROM {$wpdb->users} WHERE user_status = 0 ORDER BY ID ASC" );

		foreach ( $users as $user ) {
			$row = [];

			foreach ( $headers as $header )
				$row[] = $user->{$header};

			$meta = get_user_meta( $user->ID );

			foreach ( $metas as $saved )
				$row[] = empty( $meta[$saved][0] ) ? '' : $meta[$saved][0];

			foreach ( $contacts as $saved )
				$row[] = empty( $meta[$saved][0] ) ? '' : $meta[$saved][0];

			$data[] = $row;
		}

		return Text::toCSV( $data );
	}
}

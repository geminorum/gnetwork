<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\WordPress;

class Profile extends gNetwork\Module
{

	protected $key  = 'profile';
	protected $ajax = TRUE;

	protected function setup_actions()
	{
		$this->action( 'user_register' );

		if ( is_admin() ) {

			$this->action( 'user_edit_form_tag', 0, 99 );

			$this->action( 'edit_user_profile_update' );
			add_action( 'personal_options_update', [ $this, 'edit_user_profile_update' ], 10, 1 );

			if ( $this->options['disable_colorschemes'] )
				add_action( 'admin_init', function() {
					remove_all_actions( 'admin_color_scheme_picker' );
				} );

		} else {

			$this->filter( 'edit_profile_url', 3, 8 );

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


		$this->filter( 'update_user_metadata', 5, 12 );
		$this->filter( 'get_user_metadata', 4, 12 );
		$this->filter( 'insert_user_meta', 3, 12 );

		add_filter( 'get_user_option_rich_editing', [ $this, 'get_user_option_option' ], 8, 3 );
		add_filter( 'get_user_option_comment_shortcuts', [ $this, 'get_user_option_option' ], 8, 3 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Profile', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ) );
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
		];
	}

	public function default_settings()
	{
		$exclude = array_filter( [
			get_option( 'page_on_front' ),
			get_option( 'page_for_posts' ),
		] );

		return [
			'_general' => [
				[
					'field'       => 'display_name_per_site',
					'title'       => _x( 'Custom Display Name', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Enables custom display name per-site for each user.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'disable_password_reset',
					'title'       => _x( 'Password Reset', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Controls whether to allow a password to be reset.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Settings::reverseEnabled(),
				],
				[
					'field'       => 'disable_colorschemes',
					'title'       => _x( 'Color Schemes', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Controls whether to allow a selection of admin color scheme.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Settings::reverseEnabled(),
				],
			],
			'_signup' => [
				[
					'field'       => 'page_signup_disabled',
					'type'        => 'page',
					'title'       => _x( 'Disabled Page', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Redirects network sign-up into this page, if registration have been <b>disabled</b>.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => Settings::fieldAfterNewPostType( 'page' ),
				],
				[
					'field'       => 'redirect_signup_url',
					'type'        => 'url',
					'title'       => _x( 'Custom Location', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Full URL to the custom sign-up page.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'redirect_signup_after',
					'type'        => 'url',
					'title'       => _x( 'Location After', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Redirects into this URL after a successful registraion.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'store_signup_ip',
					'title'       => _x( 'IP Address', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Stores user\'s IP address upon registration.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'default_colorscheme',
					'type'        => 'select',
					'title'       => _x( 'Default Color Scheme', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Sets as default upon each user registeration.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => [
						'0'         => _x( 'Default', 'Modules: Profile: Settings: Color Scheme', GNETWORK_TEXTDOMAIN ),
						'light'     => _x( 'Light', 'Modules: Profile: Settings: Color Scheme', GNETWORK_TEXTDOMAIN ),
						'blue'      => _x( 'Blue', 'Modules: Profile: Settings: Color Scheme', GNETWORK_TEXTDOMAIN ),
						'coffee'    => _x( 'Coffee', 'Modules: Profile: Settings: Color Scheme', GNETWORK_TEXTDOMAIN ),
						'ectoplasm' => _x( 'Ectoplasm', 'Modules: Profile: Settings: Color Scheme', GNETWORK_TEXTDOMAIN ),
						'midnight'  => _x( 'Midnight', 'Modules: Profile: Settings: Color Scheme', GNETWORK_TEXTDOMAIN ),
						'ocean'     => _x( 'Ocean', 'Modules: Profile: Settings: Color Scheme', GNETWORK_TEXTDOMAIN ),
						'sunrise'   => _x( 'Sunrise', 'Modules: Profile: Settings: Color Scheme', GNETWORK_TEXTDOMAIN ),
					],
				],
			],
		];
	}

	public function settings_section_signup()
	{
		Settings::fieldSection(
			_x( 'Sign-up', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'Control the aspects of user registeration on this network.', 'Modules: Profile: Settings', GNETWORK_TEXTDOMAIN )
		);
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

	// set all non-admin edit profile links to the main site
	public function edit_profile_url( $url, $user_id, $scheme )
	{
		return get_admin_url( get_main_site_id(), 'profile.php', $scheme );
	}

	public function before_signup_header()
	{
		if ( $this->options['page_signup_disabled'] && 'none' == get_site_option( 'registration', 'none' ) )
			WordPress::redirect( get_page_link( $this->options['page_signup_disabled'] ) );

		if ( $this->options['redirect_signup_url'] )
			WordPress::redirect( $this->options['redirect_signup_url'] );
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

	// filter whether to allow a password to be reset.
	public function allow_password_reset( $allow, $user_id )
	{
		if ( get_user_meta( $user_id, 'disable_password_reset', TRUE ) )
			return FALSE;

		return $allow;
	}

	public function user_edit_form_tag()
	{
		global $profileuser;

		$current_time    = current_time( 'timestamp' );
		$store_lastlogin = gNetwork()->option( 'login', 'store_lastlogin', TRUE );

		echo '><h2>'._x( 'Account Information', 'Modules: Profile', GNETWORK_TEXTDOMAIN ).'</h2>';
		echo '<table class="form-table">';

		if ( isset( $profileuser->register_ip )
			&& $profileuser->register_ip )
				$register_ip = '<code>'.gnetwork_ip_lookup( $profileuser->register_ip ).'</code>';
		else
			$register_ip = gNetwork()->na();

		echo '<tr class="register_ip"><th>'._x( 'Registration IP', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
			.'</th><td>'.$register_ip.'</td></tr>';

		$register_date = strtotime( get_date_from_gmt( $profileuser->user_registered ) );
		$register_on   = Utilities::dateFormat( $register_date, 'datetime' )
			.' <small><small><span class="description">('
			.Utilities::humanTimeAgo( $register_date, $current_time )
			.')</span></small></small>';

		echo '<tr class="register_date"><th>'
				._x( 'Registration on', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
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
					._x( 'Last Login', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
				.'</th><td>'
					.$lastlogin
					.( $store_lastlogin ? '' : ' &mdash; <strong>'._x( 'Last Logins are Disabled.', 'Modules: Profile', GNETWORK_TEXTDOMAIN ).'</strong>' )
				.'</td></tr>';
		}

		if ( current_user_can( 'edit_users' ) ) {

			echo '</table><h2>'._x( 'Administrative Options', 'Modules: Profile', GNETWORK_TEXTDOMAIN ).'</h2>';
			echo '<table class="form-table">';

			$nicename = $profileuser->user_login == $profileuser->user_nicename
				? $this->sanitizeSlug( $profileuser->display_name )
				: $profileuser->user_nicename;

			echo '<tr><th><label for="gmember-slug">'._x( 'Slug', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
				.'</label></th><td><input type="text" name="gmember_slug" id="gmember_slug" value="'
				.HTML::escape( $nicename ).'" class="regular-text" dir="ltr" /><p class="description">'.
					_x( 'This will be used in the URL of the user\'s page.', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
				.'</p></td></tr>';

			if ( ! IS_PROFILE_PAGE ) {
				// prevent lockin out himself!
				echo '<tr><th>'._x( 'Account Login', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
					.'</th><td><label for="gmember_disable_user">'
					.'<input type="checkbox" name="gmember_disable_user" id="gmember_disable_user" value="1"';
						checked( 1, get_the_author_meta( 'disable_user', $profileuser->ID ) );
				echo ' /> '._x( 'Disable user login with this account', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
					.'</label></td></tr>';
			}

			echo '<tr><th>'._x( 'Password Reset', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
				.'</th><td><label for="gmember_password_reset">'
				.'<input type="checkbox" name="gmember_password_reset" id="gmember_password_reset" value="1"';
					checked( 1, get_the_author_meta( 'disable_password_reset', $profileuser->ID ) );
			echo ' /> '._x( 'Disable this account password reset via default login page', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
				.'</label></td></tr>';
		}

		echo '</table'; // it's correct, checkout the hook!
	}

	protected function sanitizeSlug( $string )
	{
		// return sanitize_title( $string );
		return Utilities::URLifyFilter( $string );
	}

	public function edit_user_profile_update( $user_id )
	{
		if ( $slug = self::req( $this->hook( 'slug' ) ) ) {

			$sanitized = $this->sanitizeSlug( $slug );

			if ( ! username_exists( $sanitized ) )
				wp_update_user( [ 'ID' => $user_id, 'user_nicename' => $sanitized ] );
		}

		if ( current_user_can( 'edit_users' ) ) {

			if ( self::req( $this->hook( 'disable_user' ) ) )
				update_user_meta( $user_id, 'disable_user', '1' );
			else
				delete_user_meta( $user_id, 'disable_user' );

			if ( self::req( $this->hook( 'password_reset' ) ) )
				update_user_meta( $user_id, 'disable_password_reset', '1' );
			else
				delete_user_meta( $user_id, 'disable_password_reset' );
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

			echo '</table><h2>'._x( 'Blog Options', 'Modules: Profile', GNETWORK_TEXTDOMAIN ).'</h2>';
			echo '<table class="form-table">';

			$site = get_current_blog_id();
			$name = empty( $profileuser->custom_display_name[$site] ) ? FALSE : $profileuser->custom_display_name[$site];

			echo '<tr><th><label for="custom_display_name">'
				._x( 'Nickname for this site', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
				.'</label></th><td><input type="text" name="custom_display_name" id="custom_display_name" value="'
				.( $name ? HTML::escape( $name ) : '' )
				.'" class="regular-text" /><p class="description">'
					._x( 'This will be displayed as your name in this site only.', 'Modules: Profile', GNETWORK_TEXTDOMAIN )
				.'</p></td></tr>';

			echo '</table><table class="form-table">';
		}
	}

	// fire order changed since WP 4.7.0
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
		// prevent BP last activity back-comp, SEE: http://wp.me/pLVLj-gc
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

	// TODO: add bulk actions to remove existing empty default user metas
	public function insert_user_meta( $meta, $user, $update )
	{
		if ( ! $update && isset( $meta['nickname'] ) && $user->user_login == $meta['nickname'] ) {
			// TODO: get default from plugin options
			if ( isset( $meta['last_name'] ) && $meta['last_name'] )
				$meta['nickname'] = $meta['last_name'];
		}

		foreach ( $this->get_default_user_meta() as $key => $value ) {
			if ( isset( $meta[$key] ) && $value == $meta[$key] ) {
				unset( $meta[$key] );
				if ( $update )
					delete_user_meta( $user->ID, $key );
			}
		}

		return $meta;
	}

	private function get_default_user_meta()
	{
		return array(
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
		);
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
}

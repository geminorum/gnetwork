<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class BuddyPress extends gNetwork\Module
{

	protected $key  = 'buddypress';
	protected $ajax = TRUE;

	private $field_name = 'vJD6QVKIjiWbjuxhLqJjVIuZ';

	// runs after bp so plugins are loaded!
	protected function setup_actions()
	{
		$this->action( 'bp_init', 0, 1 );
		$this->action( 'bp_setup_admin_bar', 0, 20 );

		$this->filter( 'register_url' );
		$this->action( 'before_signup_header' );

		// @REF: https://bpdevel.wordpress.com/2022/11/21/buddypress-will-soon-only-load-its-javascript-and-style-assets-into-the-community-area-of-your-site/
		// @REF: https://buddypress.trac.wordpress.org/ticket/8679
		$this->filter_true( 'bp_enqueue_assets_in_bp_pages_only' );

		if ( $this->options['disable_mentions'] )
			$this->filter_false( 'bp_activity_do_mentions' );

		if ( bp_is_root_blog() ) {

			$this->action( 'init' );

			if ( ! is_admin() ) {

				$this->action( 'wp_enqueue_scripts' );

				if ( $this->options['complete_signup'] )
					$this->action( 'bp_complete_signup' );

				if ( ! $this->options['open_directories'] )
					$this->action( 'bp_screens', 0, 99 );

				add_action( 'bp_before_register_page', '_donot_cache_page' );
				add_action( 'bp_before_activation_page', '_donot_cache_page' );
				add_action( 'bp_template_include_reset_dummy_post_data', '_gpersiandate_skip' );
			}

			// https://github.com/pixeljar/BuddyPress-Honeypot
			// https://www.pixeljar.com/?p=961
			$this->filter( 'bp_core_validate_user_signup' );
			$this->action( 'bp_after_signup_profile_fields' );

			$this->filter( 'bp_displayed_user_fullname', 1, 9 );
			$this->filter( 'bp_get_member_name', 1, 9 );

			if ( GNETWORK_BP_EXCLUDEUSERS )
				$this->action( 'bp_ajax_querystring', 2, 20 );

			if ( bp_is_active( 'notifications' ) )
				$this->action( 'bp_core_activated_user' );

			if ( bp_is_active( 'activity' ) ) {

				$this->action( 'bp_activity_before_save' );

				// allows activity authors to delete activity comments by others
				// https://buddydev.com/?p=17058
				$this->filter( 'bp_activity_user_can_delete', 2 );
				$this->action( 'wp_ajax_delete_activity_comment', 0, 1 );
			}

		} else {

			remove_all_actions( 'bp_register_widgets' );

			if ( ! is_admin() )
				$this->action( 'after_setup_theme' );
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'BuddyPress', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'disable_mentions' => '0',
			'open_directories' => '0',
			'complete_signup'  => '',
			'display_name'     => 'default',
			'check_completed'  => '0',

			'notification_defaults' => [],

			'avatars_thumb_width'        => '',
			'avatars_thumb_height'       => '',
			'avatars_full_width'         => '',
			'avatars_full_height'        => '',
			'avatars_original_max_width' => '',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'open_directories',
					'title'       => _x( 'Open Directories', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'description' => _x( 'Redirect directories to homepage for not logged-in users.', 'Modules: BuddyPress: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'disable_mentions',
					'type'        => 'disabled',
					'title'       => _x( 'Mentions', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'description' => _x( 'Disables mentions feature on BuddyPress.', 'Modules: BuddyPress: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'complete_signup',
					'type'        => 'url',
					'title'       => _x( 'Complete Signup', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'description' => _x( 'Redirect users after successful registration.', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'placeholder' => Core\URL::home( 'welcome' ),
				],
			],
			'_xprofile' => [
				[
					'field'       => 'display_name',
					'type'        => 'select',
					'title'       => _x( 'Display Name', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'description' => _x( 'Universal sidewide display name of the user.', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'default'     => 'default',
					'values'      => [
						'default'         => _x( '&ndash; Default &ndash;', 'Modules: BuddyPress: Settings', 'gnetwork' ),
						'first_last_name' => _x( 'First and Last Name', 'Modules: BuddyPress: Settings', 'gnetwork' ),
						'username'        => _x( 'Username', 'Modules: BuddyPress: Settings', 'gnetwork' ),
						'nickname'        => _x( 'Nickname', 'Modules: BuddyPress: Settings', 'gnetwork' ),
						'first_name'      => _x( 'First Name', 'Modules: BuddyPress: Settings', 'gnetwork' ),
						'last_name'       => _x( 'Last Name', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					],
				],
				[
					'field'       => 'check_completed',
					'title'       => _x( 'Check Completed', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'description' => _x( 'Notice member for empty required fields.', 'Modules: BuddyPress: Settings', 'gnetwork' ),
				],
			],
		];

		if ( bp_is_active( 'notifications' ) )
			$settings['_notifications'] = [
				[
					'field'       => 'notification_defaults',
					'type'        => 'checkboxes',
					'title'       => _x( 'Default Settings', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'description' => _x( 'Select enabled by default BuddyPress email notifications settings upon user activation', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'values'      => self::defaultNotifications(),
					'default'     => [],
				],
			];

		if ( bp_get_option( 'show_avatars' ) )
			$settings['_avatars'] = [
				[
					'field' => 'avatars_thumb_width',
					'type'  => 'number',
					'title' => _x( 'Thumbnail Width', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'after' => Settings::fieldAfterConstant( 'BP_AVATAR_THUMB_WIDTH' ),
				],
				[
					'field' => 'avatars_thumb_height',
					'type'  => 'number',
					'title' => _x( 'Thumbnail Height', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'after' => Settings::fieldAfterConstant( 'BP_AVATAR_THUMB_HEIGHT' ),
				],
				[
					'field' => 'avatars_full_width',
					'type'  => 'number',
					'title' => _x( 'Full Width', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'after' => Settings::fieldAfterConstant( 'BP_AVATAR_FULL_WIDTH' ),
				],
				[
					'field' => 'avatars_full_height',
					'type'  => 'number',
					'title' => _x( 'Full Height', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'after' => Settings::fieldAfterConstant( 'BP_AVATAR_FULL_HEIGHT' ),
				],
				[
					'field' => 'avatars_original_max_width',
					'type'  => 'number',
					'title' => _x( 'Original Max Width', 'Modules: BuddyPress: Settings', 'gnetwork' ),
					'after' => Settings::fieldAfterConstant( 'BP_AVATAR_ORIGINAL_MAX_WIDTH' ),
				],
			];

		return $settings;
	}

	public function settings_section_notifications()
	{
		Settings::fieldSection(
			_x( 'Email Notifications', 'Modules: BuddyPress: Settings', 'gnetwork' ),
			_x( 'Control the default email preference for users after activation', 'Modules: BuddyPress: Settings', 'gnetwork' )
		);
	}

	public function settings_section_avatars()
	{
		Settings::fieldSection(
			_x( 'Avatars Sizes', 'Modules: BuddyPress: Settings', 'gnetwork' ),
			_x( 'Change the default BuddyPress Avatar values. Leave empty to use BuddyPress defaults.', 'Modules: BuddyPress: Settings', 'gnetwork' )
		);
	}

	public function wp_enqueue_scripts()
	{
		if ( defined( 'GNETWORK_DISABLE_BUDDYPRESS_STYLES' )
			&& GNETWORK_DISABLE_BUDDYPRESS_STYLES )
				return;

		if ( ! is_buddypress() )
			return;

		wp_enqueue_style( static::BASE.'-buddypress', GNETWORK_URL.'assets/css/front.buddypress.css', [], GNETWORK_VERSION );
		wp_style_add_data( static::BASE.'-buddypress', 'rtl', 'replace' );
	}

	// cleanup!
	public function after_setup_theme()
	{
		remove_action( 'wp_head', 'bp_core_add_ajax_url_js' );
		remove_action( 'wp_footer', 'bp_core_print_generation_time' );

		$this->filter_false( 'bp_use_theme_compat_with_current_theme' );

		add_action( 'wp_enqueue_scripts', static function () {
			wp_dequeue_style( [ 'bp-parent-css', 'bp-child-css' ] );
		}, 20 );
	}

	public function init()
	{
		$super = WordPress\User::isSuperAdmin();
		$admin = is_admin();

		if ( $super && ! $admin ) {

			// Don't record activity by the site admins
			// or show them as recently active
			// REF: http://wp.me/pLVLj-gc
			$user_id = bp_loggedin_user_id();
			\BP_Core_User::delete_last_activity( $user_id );
			// delete_user_meta( $user_id, 'last_activity' );
			remove_action( 'wp_head', 'bp_core_record_activity' );
		}

		if ( ! $super && $admin )
			remove_action( 'tool_box', 'bp_core_admin_available_tools_intro' );

		if ( $admin )
			remove_action( 'admin_head-edit.php', 'bp_admin_email_maybe_add_translation_notice' );

		if ( ! $super && ! $admin && $this->options['check_completed'] )
			$this->check_completed();
	}

	public function bp_init()
	{
		return; // FIXME

		if ( '' !== $this->options['avatars_thumb_width'] && ! defined( 'BP_AVATAR_THUMB_WIDTH' ) )
			define( 'BP_AVATAR_THUMB_WIDTH', $this->options['avatars_thumb_width'] );

		if ( '' !== $this->options['avatars_thumb_height'] && ! defined( 'BP_AVATAR_THUMB_HEIGHT' ) )
			define( 'BP_AVATAR_THUMB_HEIGHT', $this->options['avatars_thumb_height'] );

		if ( '' !== $this->options['avatars_full_width'] && ! defined( 'BP_AVATAR_FULL_WIDTH' ) )
			define( 'BP_AVATAR_FULL_WIDTH', $this->options['avatars_full_width'] );

		if ( '' !== $this->options['avatars_full_height'] && ! defined( 'BP_AVATAR_FULL_HEIGHT' ) )
			define( 'BP_AVATAR_FULL_HEIGHT', $this->options['avatars_full_height'] );

		if ( '' !== $this->options['avatars_original_max_width'] && ! defined( 'BP_AVATAR_ORIGINAL_MAX_WIDTH' ) )
			define( 'BP_AVATAR_ORIGINAL_MAX_WIDTH', $this->options['avatars_original_max_width'] );
	}

	public function check_completed()
	{
		if ( ! is_user_logged_in() )
			return;

		if ( ! bp_is_root_blog() )
			return;

		if ( ! bp_is_active( 'xprofile' ) )
			return;

		if ( ! $displayed = bp_displayed_user_id() )
			return;

		// bp_current_user_can( 'bp_moderate' )
		if ( $displayed != bp_loggedin_user_id() )
			return;

		global $wpdb;

		$bp_prefix = bp_core_get_table_prefix();

		$fields = $wpdb->get_results( $wpdb->prepare( "
			SELECT `name` FROM {$bp_prefix}bp_xprofile_fields
			WHERE parent_id = 0
			AND is_required = 1
			AND id NOT IN (SELECT field_id FROM {$bp_prefix}bp_xprofile_data WHERE user_id = %s AND `value` IS NOT NULL AND `value` != '')
		", $displayed ) );

		if ( empty( $fields ) )
			return;

		/* translators: %s: filed name list */
		$message = sprintf( _x( 'Please complete your profile: %s', 'Modules: BuddyPress', 'gnetwork' ),
			Utilities::joinItems( Core\Arraay::column( $fields, 'name' ) ) );

		bp_core_add_message( $message, 'warning' );
	}

	public function bp_setup_admin_bar()
	{
		AdminBar::removeMenus( [
			'bp-about',
			'bp-register',
			'bp-login',
		] );
	}

	public function register_url( $url )
	{
		if ( bp_get_signup_allowed() )
			return bp_get_signup_page();

		return $url;
	}

	public function before_signup_header()
	{
		if ( bp_get_signup_allowed() )
			bp_core_redirect( bp_get_signup_page() );
	}

	public function bp_after_signup_profile_fields()
	{
		echo '<div style="position:absolute;'.( is_rtl() ? 'right' : 'left' ).':-5000px;">';
			echo '<input type="text" name="'.$this->field_name.'" val="" tabindex="-1" />';
		echo '</div>';
	}

	public function bp_core_validate_user_signup( $result = [] )
	{
		if ( isset( $_POST[$this->field_name] ) && ! empty( $_POST[$this->field_name] ) )
			$result['errors']->add( static::BASE.'_bp_honeypot',
				_x( 'You\'re totally a spammer. Go somewhere else with your spammy ways.', 'Modules: BuddyPress', 'gnetwork' ) );

		return $result;
	}

	public function bp_complete_signup()
	{
		bp_core_redirect( $this->options['complete_signup'] );
	}

	// checks for open directories
	public function bp_screens()
	{
		if ( bp_loggedin_user_id() )
			return;

		if ( ! bp_is_directory() )
			return;

		if ( bp_get_signup_allowed() )
			bp_core_redirect( bp_get_signup_page() );

		bp_core_redirect( bp_get_root_domain() );
	}

	public function bp_ajax_querystring( $querystring, $object = FALSE )
	{
		if ( ! $querystring )
			return $querystring;

		if ( $object != 'members' )
			return $querystring;

		$args = wp_parse_args( $querystring );

		// check if we are listing friends
		// check if we are searching
		if ( ! empty( $args['user_id'] ) || ! empty( $args['search_terms'] ) )
			return $querystring;

		if ( ! empty( $args['exclude'] ) )
			$args['exclude'].= ','.GNETWORK_BP_EXCLUDEUSERS;
		else
			$args['exclude'] = GNETWORK_BP_EXCLUDEUSERS;

		return build_query( $args );
	}

	// block certain activity types from being added
	public function bp_activity_before_save( &$activity )
	{
		$blocked = $this->filters( 'activity_blocked', [
			'updated_profile',
			'new_member',
			'new_avatar',
			'friendship_created',
			'joined_group'
		] );

		// if the type is empty, it stops BP_Activity_Activity::save()
		if ( in_array( $activity->type, $blocked ) )
			$activity->type = '';
	}

	public function bp_activity_user_can_delete( $can_delete, $activity )
	{
		if ( $can_delete )
			return $can_delete;

		if ( ! $current_user = bp_loggedin_user_id() )
			return $can_delete;

		// if it is an activity comment
		if ( $activity->item_id )
			$parent = new \BP_Activity_Activity( $activity->item_id );

		else
			$parent = $activity;

		if ( $parent->user_id == $current_user )
			return TRUE;

		return $can_delete;
	}

	public function wp_ajax_delete_activity_comment()
	{
		if ( ! Core\HTTP::isPOST() )
			return;

		check_admin_referer( 'bp_activity_delete_link' );

		if ( ! is_user_logged_in() )
			exit( '-1' );

		if ( empty( $_POST['id'] ) || ! is_numeric( $_POST['id'] ) )
			exit( '-1' );

		$activity = new \BP_Activity_Activity( (int) $_POST['id'] );

		// let others handle it
		if ( ! bp_activity_user_can_delete( $activity ) )
			return;

		do_action( 'bp_activity_before_action_delete_activity', $activity->id, $activity->user_id );

		if ( ! bp_activity_delete( [ 'id' => $activity->id, 'user_id' => $activity->user_id ] ) )
			exit( '-1<div id="message" class="error bp-ajax-message"><p>'
					.__( 'There was a problem when deleting. Please try again.', 'buddypress' )
					.'</p></div>' );

		do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );
		exit;
	}

	public static function defaultNotifications()
	{
		return [
			'activity_new_mention'        => _x( 'Activity: New Mention', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
			'activity_new_reply'          => _x( 'Activity: New Reply', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
			'friends_friendship_request'  => _x( 'Friends: Friendship Request', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
			'friends_friendship_accepted' => _x( 'Friends: Friendship Accepted', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
			'groups_invite'               => _x( 'Groups: Invite', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
			'groups_group_updated'        => _x( 'Groups: Group Updated', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
			'groups_admin_promotion'      => _x( 'Groups: Admin Promotion', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
			'groups_membership_request'   => _x( 'Groups: Membership Request', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
			'messages_new_message'        => _x( 'Messages: New Message', 'Modules: BuddyPress: Notifications', 'gnetwork' ),
		];
	}

	public function bp_core_activated_user( $user_id )
	{
		foreach ( self::defaultNotifications() as $setting => $title ) {
			$preference = in_array( $setting, $this->options['notification_defaults'] ) ? 'yes' : 'no';
			bp_update_user_meta( $user_id, 'notification_'.$setting, $preference );
		}
	}

	public function bp_displayed_user_fullname( $default )
	{
		if ( empty( buddypress()->displayed_user->userdata ) )
			return $default;

		buddypress()->displayed_user->userdata->id = buddypress()->displayed_user->userdata->ID;

		return $this->display_name( buddypress()->displayed_user->userdata, $default );
	}

	public function bp_get_member_name( $fullname )
	{
		global $members_template;

		if ( isset( $members_template->member ) )
			return $this->display_name( $members_template->member, $fullname );

		return $fullname;
	}

	private function display_name( $userdata, $fallback )
	{
		$name = NULL;

		switch ( $this->options['display_name'] ) {

			case 'default':

				$name = $fallback;

			break;
			case 'first_last_name':

				$name = sprintf( ' %s %s',
					get_user_meta( $userdata->id, 'first_name', TRUE ),
					get_user_meta( $userdata->id, 'last_name', TRUE ) );

			break;
			case 'username':

				$name = $userdata->user_login;

			break;
			case 'first_name':

				$name = get_user_meta( $userdata->id, 'first_name', TRUE );

			break;
			case 'last_name':

				$name = get_user_meta( $userdata->id, 'last_name', TRUE );

			break;
			case 'nickname':
			default:

				$name = get_user_meta( $userdata->id, 'nickname', TRUE );
		}

		return $name ?: $fallback;
	}
}

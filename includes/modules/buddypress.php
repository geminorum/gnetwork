<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class BuddyPress extends ModuleCore
{

	protected $key  = 'buddypress';
	protected $ajax = TRUE;

	private $field_name = 'vJD6QVKIjiWbjuxhLqJjVIuZ';

	protected function setup_actions()
	{
		add_action( 'init' , array( $this, 'init' ), 10 );
		add_action( 'bp_init' , array( $this, 'bp_init_early' ), 1 );
		add_action( 'bp_setup_admin_bar', array( $this, 'bp_setup_admin_bar' ), 20 );

		add_filter( 'register_url', array( $this, 'register_url' ) );

		// https://github.com/pixeljar/BuddyPress-Honeypot
		// http://pixeljar.net/2012/09/19/eliminate-buddypress-spam-registrations/
		add_action( 'bp_after_signup_profile_fields', array( $this, 'bp_after_signup_profile_fields' ) );
		add_filter( 'bp_core_validate_user_signup', array( $this, 'bp_core_validate_user_signup' ) );

		add_action( 'bp_ajax_querystring', array( $this, 'bp_ajax_querystring' ), 20, 2 );

		if ( $this->options['complete_signup'] )
			add_action( 'bp_complete_signup', array( $this, 'bp_complete_signup' ) );

		if ( $this->options['tos_display'] )
			add_action( 'bp_before_registration_submit_buttons', array( $this, 'bp_before_registration_submit_buttons' ) );

		add_action( 'bp_before_register_page', '__donot_cache_page' );
		add_action( 'bp_before_activation_page', '__donot_cache_page' );
		add_action( 'bp_template_include_reset_dummy_post_data', '__gpersiandate_skip' );

		if ( bp_is_active( 'activity' ) ) {
			add_filter( 'bp_activity_user_can_delete', array( $this, 'bp_activity_user_can_delete' ), 10, 2 );
			add_action( 'wp_ajax_delete_activity_comment', array( $this, 'wp_ajax_delete_activity_comment' ), 1 );
			add_action( 'bp_activity_before_save', array( $this, 'bp_activity_before_save' ) );
		}

		if ( ! bp_is_root_blog() ) {
			add_action( 'bp_include', array( $this, 'bp_include_remove_widgets' ), 5 );
			add_action( 'after_setup_theme' , array( $this, 'after_setup_theme'  ), 10 );
		}

		if ( bp_is_active( 'notifications' ) )
			add_action( 'bp_core_activated_user', array( $this, 'bp_core_activated_user' ) );
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'BuddyPress', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'complete_signup' => '',

			'tos_display' => 0,
			'tos_title'   => '',
			'tos_link'    => '',
			'tos_text'    => '',
			'tos_label'   => '',
			'tos_must'    => '',

			'notification_defaults' => array(),

			'avatars_thumb_width'        => defined( 'BP_AVATAR_THUMB_WIDTH' ) ? BP_AVATAR_THUMB_WIDTH : 50,
			'avatars_thumb_height'       => defined( 'BP_AVATAR_THUMB_HEIGHT' ) ? BP_AVATAR_THUMB_HEIGHT : 50,
			'avatars_full_width'         => defined( 'BP_AVATAR_FULL_WIDTH' ) ? BP_AVATAR_FULL_WIDTH : 150,
			'avatars_full_height'        => defined( 'BP_AVATAR_FULL_HEIGHT' ) ? BP_AVATAR_FULL_HEIGHT : 150,
			'avatars_original_max_width' => defined( 'BP_AVATAR_ORIGINAL_MAX_WIDTH' ) ? BP_AVATAR_ORIGINAL_MAX_WIDTH : 450,
		);
	}

	public function default_settings()
	{
		$settings = array(
			'_general' => array(
				array(
					'field'       => 'complete_signup',
					'type'        => 'url',
					'title'       => _x( 'Complete Signup', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Redirect users after successful registration.', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
					'placeholder' => 'http://example.com/welcome',
				),
			),
		);

		$settings['_tos'] = array(
			array(
				'field' => 'tos_display',
				'title' => _x( 'Display ToS', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
			),
			array(
				'field'       => 'tos_title',
				'type'        => 'text',
				'title'       => _x( 'ToS Title', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Section title, Usually : Terms of Service', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'Terms of Service', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'field_class' => 'large-text',
			),
			array(
				'field'       => 'tos_link',
				'type'        => 'url',
				'title'       => _x( 'ToS Link', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'URL for section title link to actual agreement text', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
			),
			array(
				'field'       => 'tos_text',
				'type'        => 'textarea',
				'title'       => _x( 'ToS Text', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Full text of the agreement', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'field_class' => 'large-text',
			),
			array(
				'field'       => 'tos_label',
				'type'        => 'text',
				'title'       => _x( 'ToS Label', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Label next to the mandatory checkbox, below full text', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'By checking the Terms of Service Box you have read and agree to all the Policies set forth in this site\'s Terms of Service.', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'field_class' => 'large-text',
			),
			array(
				'field'       => 'tos_must',
				'type'        => 'text',
				'title'       => _x( 'ToS Must', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Error message upon not checking the box', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'You have to accept our terms of service. Otherwise we cannot register you on our site.', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'field_class' => 'large-text',
			),
		);

		if ( bp_is_active( 'notifications' ) )
			$settings['_notifications'] = array(
				array(
					'field'       => 'notification_defaults',
					'type'        => 'checkbox',
					'title'       => _x( 'Default Settings', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Select enabled by default BuddyPress email notifications settings upon user activation', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => self::defaultNotifications(),
					'default'     => array(),
				),
			);

		$settings['_avatars'] = array(
			array(
				'field'   => 'avatars_thumb_width',
				'type'    => 'number',
				'title'   => _x( 'Thumbnail Width', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'default' => defined( 'BP_AVATAR_THUMB_WIDTH' ) ? BP_AVATAR_THUMB_WIDTH : 50,
			),
			array(
				'field'   => 'avatars_thumb_height',
				'type'    => 'number',
				'title'   => _x( 'Thumbnail Height', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'default' => defined( 'BP_AVATAR_THUMB_HEIGHT' ) ? BP_AVATAR_THUMB_HEIGHT : 50,
			),
			array(
				'field'   => 'avatars_full_width',
				'type'    => 'number',
				'title'   => _x( 'Full Width', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'default' => defined( 'BP_AVATAR_FULL_WIDTH' ) ? BP_AVATAR_FULL_WIDTH : 150,
			),
			array(
				'field'   => 'avatars_full_height',
				'type'    => 'number',
				'title'   => _x( 'Full Height', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'default' => defined( 'BP_AVATAR_FULL_HEIGHT' ) ? BP_AVATAR_FULL_HEIGHT : 150,
			),
			array(
				'field'   => 'avatars_original_max_width',
				'type'    => 'number',
				'title'   => _x( 'Original Max Width', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
				'default' => defined( 'BP_AVATAR_ORIGINAL_MAX_WIDTH' ) ? BP_AVATAR_ORIGINAL_MAX_WIDTH : 450,
			),
		);

		return $settings;
	}

	public function settings_section_tos()
	{
		Settings::fieldSection(
			_x( 'Terms of Service', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'ToS Settings on BuddyPress Registration Page', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	public function settings_section_notifications()
	{
		Settings::fieldSection(
			_x( 'Email Notifications', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'Control the default email preference for users after activation', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	public function settings_section_avatars()
	{
		Settings::fieldSection(
			_x( 'Avatars Sizes', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'Change the default BuddyPress Avatar values', 'Modules: BuddyPress: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	// cleanup!
	public function after_setup_theme()
	{
		if ( is_admin() ) {
			remove_action( 'tool_box', 'bp_core_admin_available_tools_intro' );
		} else {
			remove_action( 'wp_head', 'bp_core_add_ajax_url_js' );
			remove_action( 'wp_footer', 'bp_core_print_generation_time' );

			add_filter( 'bp_use_theme_compat_with_current_theme', '__return_false' );
			add_action( 'wp_enqueue_scripts', function(){
				wp_dequeue_style( 'bp-parent-css' );
				wp_dequeue_style( 'bp-child-css' );
			}, 20 ) ;
		}
	}

	public function init()
	{
		// TODO: add settings to disable this
		$this->check_compelete();

		// http://buddypress.org/support/topic/how-to-hide-admin-activity-on-buddypress-activity/#post-142995
		// Don't record activity by the site admins or show them as recently active

		// SEE : http://bpdevel.wordpress.com/2014/02/21/user-last_activity-data-and-buddypress-2-0/

		if ( is_super_admin() ) {
			remove_action( 'wp_head', 'bp_core_record_activity' );
			delete_user_meta( bp_loggedin_user_id(), 'last_activity' );
		}

		// Notify new users of a successful registration (without blog).
		// remove_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );
		// Notify new users of a successful registration (with blog).
		// remove_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 );
	}

	public function bp_init_early()
	{
		defined( 'BP_AVATAR_THUMB_WIDTH' ) or define( 'BP_AVATAR_THUMB_WIDTH', $this->options['avatars_thumb_width'] );
		defined( 'BP_AVATAR_THUMB_HEIGHT' ) or define( 'BP_AVATAR_THUMB_HEIGHT', $this->options['avatars_thumb_height'] );
		defined( 'BP_AVATAR_FULL_WIDTH' ) or define( 'BP_AVATAR_FULL_WIDTH', $this->options['avatars_full_width'] );
		defined( 'BP_AVATAR_FULL_HEIGHT' ) or define( 'BP_AVATAR_FULL_HEIGHT', $this->options['avatars_full_height'] );
		defined( 'BP_AVATAR_ORIGINAL_MAX_WIDTH' ) or define( 'BP_AVATAR_ORIGINAL_MAX_WIDTH', $this->options['avatars_original_max_width'] );
	}

	// originally from : Bp Force Profile v 1.1.1
	// http://wordpress.org/plugins/bp-force-profile/
	public function check_compelete()
	{
		if ( is_admin()
			|| ! is_user_logged_in()
			|| ! bp_is_root_blog()
			|| ! bp_is_active( 'xprofile' )
			|| bp_current_user_can( 'bp_moderate' )
			|| bp_loggedin_user_id() != bp_displayed_user_id() )
				return;

		global $wpdb;

		$bp_prefix = bp_core_get_table_prefix();
		// $xprofile_fields = $wpdb->get_results( "SELECT `name` FROM {$bp_prefix}bp_xprofile_fields WHERE parent_id = 0 AND is_required = 1 AND id NOT IN (SELECT field_id FROM {$bp_prefix}bp_xprofile_data WHERE user_id = {$user_id} AND `value` IS NOT NULL AND `value` != '')" );
		$xprofile_fields = $wpdb->get_results( $wpdb->prepare( "
			SELECT `name` FROM {$bp_prefix}bp_xprofile_fields
			WHERE parent_id = 0
			AND is_required = 1
			AND id NOT IN (SELECT field_id FROM {$bp_prefix}bp_xprofile_data WHERE user_id = %s AND `value` IS NOT NULL AND `value` != '')
		", bp_displayed_user_id() ) );

		if ( ! count( $xprofile_fields ) )
			return;

		$fields = array();
		foreach ( $xprofile_fields as $field )
			$fields[] = $field->name;

		bp_core_add_message( sprintf(
			_x( 'Please complete your profile: %s', 'Modules: BuddyPress', GNETWORK_TEXTDOMAIN ),
			Utilities::join_items( $fields ) ),
		'warning' );
	}

	// SEE : https://github.com/bphelp/custom_toolbar/blob/master/custom-toolbar.php
	public function bp_setup_admin_bar()
	{
		AdminBar::removeMenus( array(
			'bp-about',
			'bp-register',
			'bp-login',
		) );
	}

	public function register_url( $url )
	{
		if ( bp_get_signup_allowed() )
			return bp_get_signup_page();

		return $url;
	}

	public function bp_before_registration_submit_buttons()
	{
		echo '<div style="clear:both;"></div>';
		echo '<div class="register-section register-section-tos checkbox gnetwork-tos">';

		$title = empty( $this->options['tos_title'] ) ? FALSE : $this->options['tos_title'];

		if ( $title && ! empty( $this->options['tos_link'] ) )
			printf( '<h4><a href="%1$s" title="%2$s">%3$s</a></h4>',
				esc_url( $this->options['tos_link'] ),
				_x( 'Read full agreement', 'Modules: BuddyPress', GNETWORK_TEXTDOMAIN ),
				$title
			);
		else if ( $title )
			printf( '<h4>%s</h4>', $title );

		do_action( 'bp_gnetwork_bp_tos_errors' );

		if ( ! empty( $this->options['tos_text'] ) ) {
			echo '<textarea class="no-autosize" readonly="readonly" style="width:95%;height:220px">';
				echo esc_textarea( $this->options['tos_text'] );
			echo '</textarea>';
		}

		if ( ! empty( $this->options['tos_label'] ) )
			echo '<label for="gnetwork-bp-tos"><input type="checkbox" id="gnetwork-bp-tos" name="gnetwork_bp_tos" value="accepted" style="vertical-align:middle;"> '
				 .$this->options['tos_label'].'</label>';

		echo '</div>';
	}

	public function bp_after_signup_profile_fields()
	{
		echo '<div style="position:absolute;'.( is_rtl() ? 'right' : 'left' ).':-5000px;">';
			echo '<input type="text" name="'.$this->field_name.'" val="" tabindex="-1" />';
		echo '</div>';
	}

	public function bp_core_validate_user_signup( $result = array() )
	{
		global $bp;

		if ( $this->options['tos_display'] ) {
			if ( ! isset( $_POST['gnetwork_bp_tos'] )
				|| 'accepted' != $_POST['gnetwork_bp_tos'] )
					$bp->signup->errors['gnetwork_bp_tos'] = $this->options['tos_must'];
		}

		if ( isset( $_POST[$this->field_name] )
			&& ! empty( $_POST[$this->field_name] ) )
				$result['errors']->add( 'gnetwork_bp_honeypot',
					_x( 'You\'re totally a spammer. Go somewhere else with your spammy ways.', 'Modules: BuddyPress', GNETWORK_TEXTDOMAIN ) );

		return $result;
	}

	public function bp_complete_signup()
	{
		bp_core_redirect( $this->options['complete_signup'] );
	}

	// http://buddydev.com/buddypress/exclude-users-from-members-directory-on-a-buddypress-based-social-network/
	// http://wordpress.stackexchange.com/a/61875
	public function bp_ajax_querystring( $qs = FALSE, $object = FALSE )
	{
		if ( $object != 'members' ) // members only
			return $qs;

		$args = wp_parse_args( $qs );

		if ( ! empty( $args['user_id'] ) ) //check if we are listing friends?, do not exclude in this case
			return $qs;

		if ( ! empty( $args['exclude'] ) )
			$args['exclude'] = $args['exclude'].','.constant( 'GNETWORK_BP_EXCLUDEUSERS' );
		else
			$args['exclude'] = constant( 'GNETWORK_BP_EXCLUDEUSERS' );

		$qs = build_query( $args );

		return $qs;
	}

	// block certain activity types from being added
	// http://bp-tricks.com/snippets/block-activity-types-added-activity-stream/
	// https://gist.github.com/BoweFrankema/ed8ea0435223d7b361d5
	public function bp_activity_before_save( $activity_object )
	{
		$exclude = array(
			'updated_profile',
			'new_member',
			'new_avatar',
			'friendship_created',
			'joined_group'
		);

		// if the activity type is empty, it stops BuddyPress BP_Activity_Activity::save() function
		if ( in_array( $activity_object->type, $exclude ) )
			$activity_object->type = FALSE;
	}

	public function bp_include_remove_widgets()
	{
		remove_all_actions( 'bp_register_widgets' );
	}

	// allow activity authors to delete activity comments by other users on your BuddyPress based social network
	// http://buddydev.com/buddypress/allow-activity-authors-delete-activity-comments-users-buddypress-based-social-network/
	public function bp_activity_user_can_delete( $can_delete, $activity )
	{
		// if the user already has permission or the user is not logged in, we don't care
		if ( $can_delete || ! is_user_logged_in() )
			return $can_delete;

		// if we are here, let us check if the current user is the author of the parent activity

		$parent_activity = NULL;

		// if it is an activity comment
		if ( $activity->item_id ) {
			$parent_activity = new \BP_Activity_Activity( $activity->item_id );
		} else {
			$parent_activity = $activity;
		}

		// if the current user is author of main activity, he/she can delete it
		if ( $parent_activity->user_id == get_current_user_id() )
			$can_delete = TRUE;

		return $can_delete;
	}

	// http://buddydev.com/buddypress/allow-activity-authors-delete-activity-comments-users-buddypress-based-social-network/
	public function wp_ajax_delete_activity_comment()
	{

		// Bail if not a POST action
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) )
			return;

		check_admin_referer( 'bp_activity_delete_link' );

		if ( ! is_user_logged_in() )
		exit( '-1' );

		$comment = new \BP_Activity_Activity( $_POST['id'] );

		if ( ! bp_activity_user_can_delete( $comment ) )
			return FALSE; //let others handle it

		// Call the action before the delete so plugins can still fetch information about it
		do_action( 'bp_activity_before_action_delete_activity', $_POST['id'], $comment->user_id );

		if ( ! bp_activity_delete_comment( $comment->item_id, $comment->id ) )
			exit( '-1<div id="message" class="error"><p>'._x( 'There was a problem when deleting. Please try again.', 'Modules: BuddyPress', GNETWORK_TEXTDOMAIN ).'</p></div>' );

		do_action( 'bp_activity_action_delete_activity', $_POST['id'], $comment->user_id );
		exit;
	}

	public static function defaultNotifications()
	{
		return array(
			'activity_new_mention'        => _x( 'Activity: New Mention', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
			'activity_new_reply'          => _x( 'Activity: New Reply', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
			'friends_friendship_request'  => _x( 'Friends: Friendship Request', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
			'friends_friendship_accepted' => _x( 'Friends: Friendship Accepted', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
			'groups_invite'               => _x( 'Groups: Invite', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
			'groups_group_updated'        => _x( 'Groups: Group Updated', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
			'groups_admin_promotion'      => _x( 'Groups: Admin Promotion', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
			'groups_membership_request'   => _x( 'Groups: Membership Request', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
			'messages_new_message'        => _x( 'Messages: New Message', 'Modules: BuddyPress: Notifications', GNETWORK_TEXTDOMAIN ),
		);
	}

	public function bp_core_activated_user( $user_id )
	{
		foreach ( self::defaultNotifications() as $setting => $title ) {
			$preference = in_array( $setting, $this->options['notification_defaults'] ) ? 'yes' : 'no';
			bp_update_user_meta( $user_id, 'notification_'.$setting, $preference );
		}
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBuddyPress extends gNetworkModuleCore
{

	var $_option_key = 'buddypress';
	var $_network    = true;
	var $_ajax       = true;

	var $_field_name = 'Q2FuaXZuaW1FbnMyb2NwaG9h';
	var $_field_val  = 'R3JldGlwY3licmVrc3lpYkth';

	public function setup_actions()
	{
		gNetworkNetwork::registerMenu( 'buddypress',
			__( 'BuddyPress', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);

		add_action( 'init'              , array( &$this, 'init'               ), 10 );
		add_action( 'bp_init'           , array( &$this, 'bp_init_early'      ), 1  );
		add_action( 'bp_setup_admin_bar', array( &$this, 'bp_setup_admin_bar' ), 20 );

		add_filter( 'register_url', array( &$this, 'register_url' ) );

		// https://github.com/pixeljar/BuddyPress-Honeypot
		// http://pixeljar.net/2012/09/19/eliminate-buddypress-spam-registrations/
		add_action( 'bp_after_signup_profile_fields', array( &$this, 'bp_after_signup_profile_fields' ) );
		add_filter( 'bp_core_validate_user_signup', array( &$this, 'bp_core_validate_user_signup' ) );

		add_action( 'bp_ajax_querystring', array( &$this, 'bp_ajax_querystring' ), 20, 2 );

		if ( $this->options['tos_display'] )
			add_action( 'bp_before_registration_submit_buttons', array( &$this, 'bp_before_registration_submit_buttons' ) );

		add_action( 'bp_before_register_page', '__donot_cache_page' );
		add_action( 'bp_before_activation_page', '__donot_cache_page' );

		if ( bp_is_active( 'activity' ) ) {
			add_filter( 'bp_activity_user_can_delete', array( &$this, 'bp_activity_user_can_delete' ), 10, 2 );
			add_action( 'wp_ajax_delete_activity_comment', array( &$this, 'wp_ajax_delete_activity_comment' ), 1 );
			add_action( 'bp_activity_before_save', array( &$this, 'bp_activity_before_save' ) );
		}

		if ( ! bp_is_root_blog() ) {
			add_action( 'bp_include', array( &$this, 'bp_include_remove_widgets' ), 5 );
			add_action( 'after_setup_theme' , array( &$this, 'after_setup_theme'  ), 10 );
		}

		if ( bp_is_active( 'notifications' ) )
			add_action( 'bp_core_activated_user', array( &$this, 'bp_core_activated_user' ) );
	}

	public function default_options()
	{
		return array(
			'tos_display' => 0,
			'tos_title'   => _x( 'Terms of Service', 'BP ToS', GNETWORK_TEXTDOMAIN ),
			'tos_link'    => '',
			'tos_text'    => '',
			'tos_label'   => _x( 'By checking the Terms of Service Box you have read and agree to all the Policies set forth in this site\'s Terms of Service.', 'BP ToS', GNETWORK_TEXTDOMAIN ),
			'tos_must'    => _x( 'You have to accept our terms of service. Otherwise we cannot register you on our site.', 'BP ToS', GNETWORK_TEXTDOMAIN ),

			'notification_defaults' => array(),

			'avatars_thumb_width'        => defined( 'BP_AVATAR_THUMB_WIDTH'        ) ? BP_AVATAR_THUMB_WIDTH        : 50,
			'avatars_thumb_height'       => defined( 'BP_AVATAR_THUMB_HEIGHT'       ) ? BP_AVATAR_THUMB_HEIGHT       : 50,
			'avatars_full_width'         => defined( 'BP_AVATAR_FULL_WIDTH'         ) ? BP_AVATAR_FULL_WIDTH         : 150,
			'avatars_full_height'        => defined( 'BP_AVATAR_FULL_HEIGHT'        ) ? BP_AVATAR_FULL_HEIGHT        : 150,
			'avatars_original_max_width' => defined( 'BP_AVATAR_ORIGINAL_MAX_WIDTH' ) ? BP_AVATAR_ORIGINAL_MAX_WIDTH : 450,
		);
	}

	public function default_settings()
	{
		$settings = array(
			'_tos' => array(
				array(
					'field'   => 'tos_display',
					'type'    => 'enabled',
					'title'   => __( 'Display ToS', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field' => 'tos_title',
					'type'  => 'text',
					'title' => __( 'ToS Title', GNETWORK_TEXTDOMAIN ),
					'desc'  => __( 'Section title, Usually : Terms of Service', GNETWORK_TEXTDOMAIN ),
					'class' => 'large-text',
				),
				array(
					'field' => 'tos_link',
					'type'  => 'text',
					'title' => __( 'ToS Link', GNETWORK_TEXTDOMAIN ),
					'desc'  => __( 'URL for section title link to actual agreement text', GNETWORK_TEXTDOMAIN ),
					'class' => 'large-text',
				),
				array(
					'field' => 'tos_text',
					'type'  => 'textarea',
					'title' => __( 'ToS Text', GNETWORK_TEXTDOMAIN ),
					'desc'  => __( 'Full text of the agreement.', GNETWORK_TEXTDOMAIN ),
					'class' => 'large-text',
				),
				array(
					'field' => 'tos_label',
					'type'  => 'text',
					'title' => __( 'ToS Label', GNETWORK_TEXTDOMAIN ),
					'desc'  => __( 'Label next to the mandatory checkbox, below full text.', GNETWORK_TEXTDOMAIN ),
					'class' => 'large-text',
				),
				array(
					'field' => 'tos_must',
					'type'  => 'text',
					'title' => __( 'ToS Must', GNETWORK_TEXTDOMAIN ),
					'desc'  => __( 'Error message upon not checking the box.', GNETWORK_TEXTDOMAIN ),
					'class' => 'large-text',
				),
			),
		);

		if ( bp_is_active( 'notifications' ) )
			$settings['_notifications'] = array(
				array(
					'field'   => 'notification_defaults',
					'type'    => 'checkbox',
					'values'  => self::defaultNotifications(),
					'default' => array(),
					'title'   => __( 'Default Settings', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Select enabled by default BuddyPress email notifications settings upon user activation.', GNETWORK_TEXTDOMAIN ),
				),
			);

		$settings['_avatars'] = array(
			array(
				'field' => 'avatars_thumb_width',
				'type'  => 'text',
				'class' => 'small-text',
				'title' => __( 'Thumbnail Width', GNETWORK_TEXTDOMAIN ),
			),
			array(
				'field' => 'avatars_thumb_height',
				'type'  => 'text',
				'class' => 'small-text',
				'title' => __( 'Thumbnail Height', GNETWORK_TEXTDOMAIN ),
			),
			array(
				'field' => 'avatars_full_width',
				'type'  => 'text',
				'class' => 'small-text',
				'title' => __( 'Full Width', GNETWORK_TEXTDOMAIN ),
			),
			array(
				'field' => 'avatars_full_height',
				'type'  => 'text',
				'class' => 'small-text',
				'title' => __( 'Full Height', GNETWORK_TEXTDOMAIN ),
			),
			array(
				'field' => 'avatars_original_max_width',
				'type'  => 'text',
				'class' => 'small-text',
				'title' => __( 'Original Max Width', GNETWORK_TEXTDOMAIN ),
			),
		);

		return $settings;
	}

	public function settings_section_tos()
	{
		echo '<h3>'._x( 'Terms of Service', 'Settings Section Title', GNETWORK_TEXTDOMAIN ).'</h3>';
		echo '<p class="description">';
			_e( 'ToS Settings on BuddyPress Registration Page', GNETWORK_TEXTDOMAIN );
		echo '</p>';
	}

	public function settings_section_notifications()
	{
		echo '<h3>'._x( 'Email Notifications', 'Settings Section Title', GNETWORK_TEXTDOMAIN ).'</h3>';
		echo '<p class="description">';
			_e( 'Control the default email preference for users after activation', GNETWORK_TEXTDOMAIN );
		echo '</p>';
	}

	public function settings_section_avatars()
	{
		echo '<h3>'._x( 'Avatars Sizes', 'Settings Section Title', GNETWORK_TEXTDOMAIN ).'</h3>';
		echo '<p class="description">';
			_e( 'Change the default BuddyPress Avatar values', GNETWORK_TEXTDOMAIN );
		echo '</p>';
	}

	public function settings( $sub = NULL )
	{
		if ( 'buddypress' == $sub ) {
			$this->update( $sub );
			add_action( 'gnetwork_network_settings_sub_buddypress', array( &$this, 'settings_html' ), 10, 2 );
			$this->register_settings();
		}
	}

	// cleanup!
	public function after_setup_theme()
	{
		remove_action( 'wp_head', 'bp_core_add_ajax_url_js' );
		remove_action( 'wp_footer', 'bp_core_print_generation_time' );

		add_filter( 'bp_use_theme_compat_with_current_theme', '__return_false' );
		add_action( 'wp_enqueue_scripts', function(){
			wp_dequeue_style( 'bp-parent-css' );
			wp_dequeue_style( 'bp-child-css' );
		}, 20 ) ;
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
		//remove_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );
		// Notify new users of a successful registration (with blog).
		//remove_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 );
	}

	public function bp_init_early()
	{
		defined( 'BP_AVATAR_THUMB_WIDTH'        ) or define( 'BP_AVATAR_THUMB_WIDTH',        $this->options['avatars_thumb_width']        );
		defined( 'BP_AVATAR_THUMB_HEIGHT'       ) or define( 'BP_AVATAR_THUMB_HEIGHT',       $this->options['avatars_thumb_height']       );
		defined( 'BP_AVATAR_FULL_WIDTH'         ) or define( 'BP_AVATAR_FULL_WIDTH',         $this->options['avatars_full_width']         );
		defined( 'BP_AVATAR_FULL_HEIGHT'        ) or define( 'BP_AVATAR_FULL_HEIGHT',        $this->options['avatars_full_height']        );
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
			__( 'Please complete your profile: %s', GNETWORK_TEXTDOMAIN ),
			gNetworkUtilities::join_items( $fields ) ),
		'warning' );
	}

	// SEE : https://github.com/bphelp/custom_toolbar/blob/master/custom-toolbar.php
	public function bp_setup_admin_bar()
	{
		gNetworkAdminBar::removeMenus( array(
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


		$title = empty( $this->options['tos_title'] ) ? false : $this->options['tos_title'];

		if ( $title && ! empty( $this->options['tos_link'] ) )
			printf( '<h4><a href="%1$s" title="%2$s">%3$s</a></h4>',
				esc_url( $this->options['tos_link'] ),
				_x( 'Read full agreement', 'BP ToS', GNETWORK_TEXTDOMAIN ),
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
			echo '<input type="text" name="'.$this->_field_name.'" val="" tabindex="-1" />';
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

		if( isset( $_POST[$this->_field_name] )
			&& ! empty( $_POST[$this->_field_name] ) )
				$result['errors']->add( 'gnetwork_bp_honeypot',
					__( "You're totally a spammer. Go somewhere else with your spammy ways.", GNETWORK_TEXTDOMAIN ) );

		return $result;
	}

	// http://buddydev.com/buddypress/exclude-users-from-members-directory-on-a-buddypress-based-social-network/
	// http://wordpress.stackexchange.com/a/61875
	public function bp_ajax_querystring( $qs = false, $object = false )
	{
		if( $object != 'members' ) // members only
			return $qs;

		$args = wp_parse_args( $qs );

		if( ! empty( $args['user_id'] ) ) //check if we are listing friends?, do not exclude in this case
			return $qs;

		if( ! empty( $args['exclude'] ) )
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
		if( in_array( $activity_object->type, $exclude ) )
			$activity_object->type = false;
	}

	public function bp_include_remove_widgets()
	{
		remove_all_actions( 'bp_register_widgets' );
	}

	// Allow activity authors to delete activity comments by other users on your BuddyPress based social network
	// http://buddydev.com/buddypress/allow-activity-authors-delete-activity-comments-users-buddypress-based-social-network/
	public function bp_activity_user_can_delete( $can_delete, $activity )
	{
		//if the user already has permission or the user is not logged in, we don't care
		if( $can_delete || ! is_user_logged_in() )
			return $can_delete;

		//if we are here, let us check if the current user is the author of the parent activity

		$parent_activity = null;

		//if it is an activity comment
		if( $activity->item_id ) {
			$parent_activity = new BP_Activity_Activity( $activity->item_id );
		} else {
			$parent_activity = $activity;
		}

		//if the current user is author of main activity, he/she can delete it
		if( $parent_activity->user_id == get_current_user_id() )
			$can_delete = true;

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

		$comment = new BP_Activity_Activity( $_POST['id'] );

		if ( ! bp_activity_user_can_delete( $comment ) )
			return false; //let others handle it

		// Call the action before the delete so plugins can still fetch information about it
		do_action( 'bp_activity_before_action_delete_activity', $_POST['id'], $comment->user_id );

		if ( ! bp_activity_delete_comment( $comment->item_id, $comment->id ) )
			exit( '-1<div id="message" class="error"><p>'.__( 'There was a problem when deleting. Please try again.', 'buddypress' ).'</p></div>' );

		do_action( 'bp_activity_action_delete_activity', $_POST['id'], $comment->user_id );
		exit;
	}

	public static function defaultNotifications()
	{
		return array(
			'activity_new_mention'        => _X( 'Activity: New Mention',        'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
			'activity_new_reply'          => _X( 'Activity: New Reply',          'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
			'friends_friendship_request'  => _X( 'Friends: Friendship Request',  'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
			'friends_friendship_accepted' => _X( 'Friends: Friendship Accepted', 'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
			'groups_invite'               => _X( 'Groups: Invite',               'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
			'groups_group_updated'        => _X( 'Groups: Group Updated',        'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
			'groups_admin_promotion'      => _X( 'Groups: Admin Promotion',      'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
			'groups_membership_request'   => _X( 'Groups: Membership Request',   'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
			'messages_new_message'        => _X( 'Messages: New Message',        'BP Email Notification Settings', GNETWORK_TEXTDOMAIN ),
		);
	}

	public function bp_core_activated_user( $user_id )
	{
	    foreach( self::defaultNotifications() as $setting => $title ) {
			$preference = in_array( $setting, $this->options['notification_defaults'] ) ? 'yes' : 'no';
			bp_update_user_meta( $user_id, 'notification_'.$setting, $preference );
	    }
	}
}


// http://buddydev.com/buddypress-tricks/buddypress-fun-playing-with-buddypress-friends-list-visibility/
// http://buddydev.com/buddypress-tricks/showing-user-roles-buddypress-profile/

// https://premium.wpmudev.org/project/terms-of-service/
// http://buddypress.org/support/topic/terms-of-use-plugin-for-buddy-press/

// http://wordpress.org/plugins/buddypress-default-group-avatar/

// https://gist.github.com/BoweFrankema/95166be93b4f05a70a13

// bp_registration_needs_activation()
// http://bp-tricks.com/snippets/show-custom-message-multisite-site-creation/
// https://gist.github.com/BoweFrankema/9b298f9e64bb03b62f03

// https://gist.github.com/BoweFrankema/e95ec775f3ba0db92182


// CUSTOM FIELD TYPES
// https://github.com/donmik/buddypress-xprofile-custom-fields-type

// https://codex.buddypress.org/themes/guides/displaying-extended-profile-fields-on-member-profiles/

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

// http://buddypress.org/support/topic/dynamic-urls-for-buddypress-user-profiles-friends-etc/
// http://buddypress.org/support/topic/logged-in-user-profile-link-url/
// http://codex.buddypress.org/plugindev/playing-with-the-users-id-in-different-contexts/

class gNetwork_BP_Me_Component extends BP_Component
{

	public function __construct()
	{
		global $bp;

		parent::start( 'me', __( 'gNetwork Me', GNETWORK_TEXTDOMAIN ) );
		$bp->active_components[$this->id] = '1';

		if ( ! is_admin() ) {
			add_filter( 'bp_members_edit_profile_url', array( $this, 'bp_members_edit_profile_url' ), 12, 4 );
		}

		add_filter( 'gnetwork_bp_me_url', array( $this, 'url' ) );

		add_filter( 'gnetwork_navigation_loggedin_items', array( $this, 'navigation_loggedin_items' ) );
		add_filter( 'gnetwork_navigation_public_profile_url', array( $this, 'navigation_public_profile_url' ), 12, 4 );
		add_filter( 'gnetwork_navigation_logout_url', array( $this, 'navigation_logout_url' ), 12, 4 );
	}

	public function setup_globals( $args = array() )
	{
		parent::setup_globals( array(
			'slug'          => 'me',
			'root_slug'     => 'me',
			'has_directory' => TRUE,
		) );

		if ( ! bp_is_current_component( $this->id ) )
			return;

		__donot_cache_page();

		$this->current_action = bp_current_action();
		if ( empty( $this->current_action ) )
			$this->current_action = 'profile';

		if ( 'logout' == $this->current_action && ! bp_loggedin_user_id() )
			bp_core_redirect( bp_get_root_domain() );

		if ( ! bp_loggedin_user_id() )
			bp_core_redirect( wp_login_url( gNetworkUtilities::currentURL() ) );

		$actions = apply_filters( 'gnetwork_bp_me_actions', array(
			'profile'  => array( $this, 'me_action_profile' ),
			'settings' => array( $this, 'me_action_settings' ),
			'edit'     => array( $this, 'me_action_edit' ),
			'avatar'   => array( $this, 'me_action_avatar' ),
			'logout'   => array( $this, 'me_action_logout' ),
		) );

		if ( array_key_exists( $this->current_action, $actions )
			&& is_callable( $actions[$this->current_action] ) )
				call_user_func_array( $actions[$this->current_action], array( bp_action_variables() ) );

		$this->me_action_profile();
	}

	public function me_action_profile( $vars = FALSE )
	{
		bp_core_redirect( bp_get_loggedin_user_link() );
		die();
	}

	public function me_action_settings( $vars = FALSE )
	{
		global $bp;

		if ( bp_is_active( 'settings' ) )
			bp_core_redirect( bp_loggedin_user_domain().bp_get_settings_slug() );

		$this->me_action_profile();
	}

	public function me_action_edit( $vars = FALSE )
	{
		global $bp;

		if ( bp_is_active( 'xprofile' ) )
			bp_core_redirect( bp_loggedin_user_domain().$bp->profile->slug.'/edit' );

		$this->me_action_profile();
	}

	public function me_action_avatar( $vars = FALSE )
	{
		global $bp;

		if ( ! (int) $bp->site_options['bp-disable-avatar-uploads'] )
			bp_core_redirect( bp_loggedin_user_domain().$bp->profile->slug.'/change-avatar/' );

		$this->me_action_profile();
	}

	public function me_action_logout( $vars = FALSE )
	{
		// TODO : check $_SERVER['HTTP_REFERER']; then safe redirect within network ( must add a filter )

		$redirect = bp_get_loggedin_user_link();
		wp_logout();
		bp_core_redirect( $redirect );
		exit();
	}

	public function url( $link = '' )
	{
		return trailingslashit( bp_get_root_domain().'/'.$this->root_slug.'/'.$link );
	}

	public function bp_members_edit_profile_url( $profile_link, $url, $user_id, $scheme )
	{
		return $this->url( 'edit' );
	}

	public function navigation_loggedin_items( $items )
	{
		if ( bp_is_active( 'settings' ) )
			$items[] = array(
				'name' => __( 'Profile Settings', GNETWORK_TEXTDOMAIN ),
				'slug' => 'settings',
				'link' => $this->url( 'settings' ),
			);

		return $items;
	}

	public function navigation_public_profile_url( $profile_url )
	{
		return $this->url();
	}

	public function navigation_logout_url( $logout_url )
	{
		return $this->url( 'logout' );
	}
}

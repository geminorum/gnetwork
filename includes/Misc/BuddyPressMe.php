<?php namespace geminorum\gNetwork\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

class BuddyPressMe extends \BP_Component
{

	public $id        = '';  // BP
	public $root_slug = '';  // BP

	protected $current_action = '';

	public function __construct()
	{
		parent::start( 'me', _x( 'gNetwork Me', 'BuddyPress Me: Component Name', 'gnetwork-admin' ) );

		buddypress()->active_components[$this->id] = '1';

		if ( ! is_admin() ) {
			add_filter( 'get_edit_user_link', [ $this, 'get_edit_user_link' ], 12, 2 );
			add_filter( 'bp_members_edit_profile_url', [ $this, 'bp_members_edit_profile_url' ], 12, 4 );
		}

		add_filter( 'gnetwork_bp_me_url', [ $this, 'url' ] );

		add_filter( 'gnetwork_navigation_loggedin_items', [ $this, 'navigation_loggedin_items' ] );
		add_filter( 'gnetwork_navigation_public_profile_url', [ $this, 'navigation_public_profile_url' ], 12, 4 );
		add_filter( 'gnetwork_navigation_logout_url', [ $this, 'navigation_logout_url' ], 12, 4 );

		add_filter( 'wp_sitemaps_posts_query_args', [ $this, 'wp_sitemaps_posts_query_args' ], 12, 2 );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', [ $this, 'wpseo_exclude_from_sitemap_by_post_ids' ], 12 );
	}

	// @REF: https://buddypress.trac.wordpress.org/ticket/6325
	// @REF: https://gist.github.com/geminorum/c22dc0a07c1db6031cdc39d7f76292eb
	public function setup_globals( $args = [] )
	{
		parent::setup_globals( [
			'slug'          => 'me',
			'root_slug'     => 'me',
			'has_directory' => TRUE,
		] );

		if ( ! bp_is_current_component( $this->id ) )
			return;

		WordPress\Site::doNotCache();

		$this->current_action = bp_current_action();

		if ( empty( $this->current_action ) )
			$this->current_action = 'profile';

		if ( 'logout' == $this->current_action && ! bp_loggedin_user_id() )
			bp_core_redirect();

		if ( ! bp_loggedin_user_id() )
			bp_core_redirect( WordPress\URL::login( Core\URL::current() ) );

		$actions = apply_filters( 'gnetwork_bp_me_actions', [
			'profile'  => [ $this, 'me_action_profile' ],
			'settings' => [ $this, 'me_action_settings' ],
			'edit'     => [ $this, 'me_action_edit' ],
			'avatar'   => [ $this, 'me_action_avatar' ],
			'cover'    => [ $this, 'me_action_cover' ],
			'logout'   => [ $this, 'me_action_logout' ],
		] );

		if ( array_key_exists( $this->current_action, $actions )
			&& is_callable( $actions[$this->current_action] ) )
				call_user_func_array( $actions[$this->current_action], [ bp_action_variables() ] );

		$this->me_action_profile();
	}

	public function me_action_profile( $vars = FALSE )
	{
		bp_core_redirect( bp_get_loggedin_user_link() );
		exit;
	}

	public function me_action_settings( $vars = FALSE )
	{
		if ( bp_is_active( 'settings' ) )
			bp_core_redirect( bp_loggedin_user_domain().bp_get_settings_slug() );

		$this->me_action_profile();
	}

	public function me_action_edit( $vars = FALSE )
	{
		if ( bp_is_active( 'xprofile' ) )
			bp_core_redirect( trailingslashit( bp_loggedin_user_domain().bp_get_profile_slug().'/edit' ) );

		$this->me_action_profile();
	}

	public function me_action_avatar( $vars = FALSE )
	{
		if ( buddypress()->avatar->show_avatars )
			bp_core_redirect( trailingslashit( bp_loggedin_user_domain().bp_get_profile_slug().'/change-avatar' ) );

		$this->me_action_profile();
	}

	public function me_action_cover( $vars = FALSE )
	{
		if ( bp_displayed_user_use_cover_image_header() )
			bp_core_redirect( trailingslashit( bp_loggedin_user_domain().bp_get_profile_slug().'/change-cover-image' ) );

		$this->me_action_profile();
	}

	// FIXME: check $_SERVER['HTTP_REFERER']; then safe redirect within network ( must add a filter )
	public function me_action_logout( $vars = FALSE )
	{
		$redirect = bp_get_loggedin_user_link();
		wp_logout();
		bp_core_redirect( $redirect );
		exit;
	}

	public function url( $link = '' )
	{
		$root = function_exists( 'bp_get_root_url' )
			? bp_get_root_url()
			: bp_get_root_domain(); // DEPRECATED @since BP 12.0.0

		return trailingslashit( $root.'/'.$this->root_slug.'/'.$link );
	}

	public function bp_members_edit_profile_url( $profile_link, $url, $user_id, $scheme )
	{
		return $user_id == get_current_user_id() ? $this->url( 'edit' ) : $profile_link;
	}

	public function get_edit_user_link( $link, $user_id )
	{
		return $user_id == get_current_user_id() ? $this->url( 'edit' ) : $link;
	}

	public function navigation_loggedin_items( $items )
	{
		if ( bp_is_active( 'settings' ) )
			$items[] = [
				'name' => _x( 'Profile Settings', 'BuddyPress Me: Navigation Item', 'gnetwork-admin' ),
				'slug' => 'settings',
				'link' => $this->url( 'settings' ),
			];

		if ( buddypress()->avatar->show_avatars )
			$items[] = [
				'name' => _x( 'Change Avatar', 'BuddyPress Me: Navigation Item', 'gnetwork-admin' ),
				'slug' => 'avatar',
				'link' => $this->url( 'avatar' ),
			];

		if ( bp_displayed_user_use_cover_image_header() )
			$items[] = [
				'name' => _x( 'Change Cover', 'BuddyPress Me: Navigation Item', 'gnetwork-admin' ),
				'slug' => 'cover',
				'link' => $this->url( 'cover' ),
			];

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

	public function wp_sitemaps_posts_query_args( $args, $post_type )
	{
		if ( 'page' !== $post_type )
			return $args;

		if ( ! array_key_exists( 'post__not_in', $args ) )
			$args['post__not_in'] = [];

		$args['post__not_in'][] = bp_core_get_directory_page_id( $this->id );

		return $args;
	}

	public function wpseo_exclude_from_sitemap_by_post_ids( $excluded_posts_ids )
	{
		$excluded_posts_ids[] = bp_core_get_directory_page_id( $this->id );

		return $excluded_posts_ids;
	}
}

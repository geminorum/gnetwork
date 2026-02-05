<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Authors extends gNetwork\Module
{
	protected $key     = 'authors';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 8 );

		if ( $this->options['siteuser_as_default']
			&& is_admin() && gNetwork()->user() )
			$this->filter( 'wp_insert_post_data', 2, 9 );

		if ( $this->options['force_multi_author'] )
			$this->filter_true( 'pre_transient_is_multi_author', 20 );

		if ( is_admin() )
			return;

		if ( $this->options['remove_author_pages'] ) {
			$this->action( 'template_redirect' );
			$this->filter( 'wp_sitemaps_add_provider', 2 );
			// $this->filter_empty_string( 'author_link', 12 );
			// $this->filter_empty_string( 'the_author_posts_link', 12 );
		}

		if ( $this->options['replace_author_links'] )
			$this->filter( 'author_link' );

		if ( $this->options['authors_manage_tags'] )
			$this->filter( 'map_meta_cap', 4, 9 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Authors', 'Modules: Menu Name', 'gnetwork-admin' ) );
		$this->register_tool( _x( 'Authors', 'Modules: Menu Name', 'gnetwork-admin' ), NULL, 9, 'edit_users' );
	}

	public function default_options()
	{
		return [
			'force_multi_author'   => '1',
			'authors_manage_tags'  => '1',
			'siteuser_as_default'  => '0',
			'remove_author_pages'  => '0',
			'replace_author_links' => '',
			'replace_status_code'  => '301',
			'register_shortcodes'  => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'force_multi_author',
					'title'       => _x( 'Force Multi Author', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'description' => _x( 'Overrides checks for this site has more than one author.', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'default'     => '1',
				],
				[
					'field'       => 'authors_manage_tags',
					'title'       => _x( 'Manage Post Tags', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'description' => _x( 'Allows authors to manage post tags.', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'default'     => '1',
				],
				[
					'field'       => 'siteuser_as_default',
					'title'       => _x( 'Default Author', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'description' => _x( 'Uses site user as default author of new posts in admin.', 'Modules: Authors: Settings', 'gnetwork-admin' ),
				],
				[
					'field'       => 'remove_author_pages',
					'title'       => _x( 'Remove Author Pages', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'description' => _x( 'Triggers a 404 error for all author pages or redirect to custom URL.', 'Modules: Authors: Settings', 'gnetwork-admin' ),
				],
				[
					'field'       => 'replace_author_links',
					'type'        => 'url',
					'title'       => _x( 'Replace Author Links', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'description' => _x( 'Replaces author links with links to a custom URL.', 'Modules: Authors: Settings', 'gnetwork-admin' ),
				],
				[
					'field'       => 'replace_status_code',
					'type'        => 'select',
					'title'       => _x( 'Redirect Status Code', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'description' => _x( 'Defines HTTP status header code while redirecting to custom URL.', 'Modules: Authors: Settings', 'gnetwork-admin' ),
					'after'       => Settings::fieldAfterIcon( 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes' ),
					'dir'         => 'ltr',
					'default'     => '301',
					'values'      => Settings::statusOptions( [ 301, 302, 303, 304, 307, 308 ] ),
				],
				'register_shortcodes',
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		self::summarySiteUser();
	}

	public function tools_sidebox( $sub, $uri )
	{
		self::summarySiteUser();
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		$users = [
			'none' => Settings::showOptionNone(),
			'all'  => Settings::showOptionAll(),
		];

		foreach ( WordPress\User::get() as $user_id => $user )
			$users[$user_id] = sprintf( '%1$s (%2$s)', $user->display_name, $user->user_login );

		Settings::headerTitle( _x( 'Author Tools', 'Modules: Authors', 'gnetwork-admin' ) );

		echo '<table class="form-table">';

			if ( is_multisite() && ( $user = gNetwork()->user() ) ) {

				echo '<tr><th scope="row">'._x( 'Site User', 'Modules: Authors: Settings', 'gnetwork-admin' ).'</th><td>';
				echo $this->wrap_open_buttons();

				if ( is_user_member_of_blog( $user ) )
					Core\HTML::desc( _x( 'The user is already member of this blog.', 'Modules: Authors: Settings', 'gnetwork-admin' ) );

				else
					Settings::submitButton( 'add_site_user', _x( 'Add User to this Site', 'Modules: Authors: Settings', 'gnetwork-admin' ) );

				echo '</p></td></tr>';
			}

			echo '<tr><th scope="row">'._x( 'Bulk Change Author', 'Modules: Authors: Settings', 'gnetwork-admin' ).'</th><td>';

			echo $this->wrap_open_buttons();

			$this->do_settings_field( [
				'type'      => 'select',
				'field'     => 'from_user_id',
				'name_attr' => 'from_user_id',
				'cap'       => TRUE,
				'values'    => $users,
				'default'   => 'all',
			] );

			echo '&nbsp;&mdash;&nbsp;'._x( 'to', 'Modules: Authors: Settings', 'gnetwork-admin' ).'&nbsp;&ndash;&nbsp;';

			unset( $users['all'] );

			$this->do_settings_field( [
				'type'      => 'select',
				'field'     => 'to_user_id',
				'name_attr' => 'to_user_id',
				'cap'       => TRUE,
				'values'    => $users,
				'default'   => gNetwork()->user(),
			] );

			echo '&nbsp;&mdash;&nbsp;'._x( 'on', 'Modules: Authors: Settings', 'gnetwork-admin' ).'&nbsp;&ndash;&nbsp;';

			$this->do_settings_field( [
				'type'      => 'select',
				'field'     => 'on_post_type',
				'name_attr' => 'on_post_type',
				'cap'       => TRUE,
				'default'   => 'post',
				'values'    => WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ),
			] );

			echo '&nbsp;&mdash;&nbsp;'._x( 'do', 'Modules: Authors: Settings', 'gnetwork-admin' ).'&nbsp;&ndash;&nbsp;';

			Settings::submitButton( 'bulk_change_author', _x( 'Change', 'Modules: Authors: Settings', 'gnetwork-admin' ), FALSE, TRUE );

			echo '</p></td></tr>';
		echo '</table>';
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( ! empty( $_POST['add_site_user'] ) ) {

			if ( $user = gNetwork()->user() ) {

				$added = add_user_to_blog( get_current_blog_id(), $user, get_option( 'default_role', 'subscriber' ) );

				if ( $added && ! self::isError( $added ) )
					WordPress\Redirect::doReferer( 'updated' );
			}

			WordPress\Redirect::doReferer( 'wrong' );

		} else if ( ! empty( $_POST['bulk_change_author'] ) ) {

			$this->check_referer( $sub, 'tools' );

			if ( empty( $_POST['from_user_id'] ) || 'none' == $_POST['from_user_id'] )
				return;

			$to_user  = isset( $_POST['to_user_id'] ) ? (int) $_POST['to_user_id'] : gNetwork()->user( TRUE );
			$posttype = isset( $_POST['on_post_type'] ) ? $_POST['on_post_type'] : 'post';

			if ( $_POST['from_user_id'] == $to_user )
				return;

			if ( 'all' == $_POST['from_user_id'] )
				$count = $this->bulk_change_all_authors( $to_user, $posttype );

			else
				$count = $this->bulk_change_author( (int) $_POST['from_user_id'], $to_user, $posttype );

			if ( FALSE === $count )
				WordPress\Redirect::doReferer( 'wrong' );

			else
				WordPress\Redirect::doReferer( [
					'message' => 'changed',
					'count'   => $count,
				] );
		}
	}

	public static function userRoles()
	{
		Core\HTML::desc( _x( 'List of user roles that the current user is allowed to edit.', 'Modules: Authors: User Roles', 'gnetwork-admin' ) );
		Core\HTML::tableSide( get_editable_roles() );
	}

	public static function summarySiteUser( $default_role = TRUE )
	{
		if ( $user = gNetwork()->user() ) {

			$name = get_userdata( $user )->display_name;
			$edit = WordPress\User::edit( $user );

			Core\HTML::desc( sprintf(
				/* translators: `%s`: site-user */
				_x( 'Site-User for current network is: %s', 'Modules: Authors: Settings', 'gnetwork-admin' ),
				$edit ? Core\HTML::link( $name, $edit, TRUE ) : $name )
			);

		} else {

			Core\HTML::desc( _x( 'Site-User for current network is <strong>not</strong> defined.', 'Modules: Authors: Settings', 'gnetwork-admin' ) );
		}

		if ( $default_role ) {

			Core\HTML::desc( sprintf(
				/* translators: `%s`: user role */
				_x( 'Default role for this site is: %s', 'Modules: Authors: Settings', 'gnetwork-admin' ),
				Core\HTML::code( get_option( 'default_role' ) )
			) );
		}
	}

	private function bulk_change_author( $from_user, $to_user, $posttype = 'post' )
	{
		global $wpdb;

		$user = get_userdata( $to_user );

		if ( ! $user || ! $user->exists() )
			return FALSE;

		return $wpdb->query( $wpdb->prepare( "
			UPDATE {$wpdb->posts} SET post_author = %d WHERE post_author = %d AND post_type = %s
		", $user->ID, $from_user, $posttype ) );
	}

	private function bulk_change_all_authors( $to_user, $posttype = 'post' )
	{
		global $wpdb;

		$user = get_userdata( $to_user );

		if ( ! $user || ! $user->exists() )
			return FALSE;

		return $wpdb->query( $wpdb->prepare( "
			UPDATE {$wpdb->posts} SET post_author = %d WHERE post_type = %s
		", $user->ID, $posttype ) );
	}

	public function init()
	{
		$this->register_shortcodes();
	}

	public function template_redirect()
	{
		if ( ! is_author() )
			return;

		if ( $this->options['replace_author_links'] )
			WordPress\Redirect::doWP(
				$this->options['replace_author_links'],
				$this->options['replace_status_code']
			);

		Utilities::redirect404();
	}

	public function wp_sitemaps_add_provider( $provider, $name )
	{
		return 'users' == $name ? FALSE : $provider;
	}

	public function author_link( $link )
	{
		return $this->options['replace_author_links'];
	}

	// @REF: https://make.wordpress.org/core/2016/10/28/fine-grained-capabilities-for-taxonomy-terms-in-4-7/
	public function map_meta_cap( $caps = [], $cap = '', $user_id = 0, $args = [] )
	{
		switch ( $cap ) {
			case 'manage_post_tags':
			case 'edit_post_tags':
			case 'delete_post_tags':
			case 'assign_post_tags':
				$caps = [ 'publish_posts' ];
		}

		return $caps;
	}

	protected function get_shortcodes()
	{
		return [
			'logged-in'     => 'shortcode_logged_in',
			'not-logged-in' => 'shortcode_not_logged_in',
		];
	}

	public function shortcode_logged_in( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'text'    => NULL,
			'cap'     => NULL,
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] || WordPress\IsIt::xml() )
			return NULL;

		if ( $args['cap'] && ! WordPress\User::cuc( $args['cap'] ) )
			return $args['text'];

		if ( ! is_user_logged_in() )
			return $args['text'];

		WordPress\Site::doNotCache();

		return apply_shortcodes( $content );
	}

	public function shortcode_not_logged_in( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'text'    => NULL,
			'context' => NULL,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( is_user_logged_in() ) {

			WordPress\Site::doNotCache();

			return $args['text'];
		}

		return apply_shortcodes( $content );
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		global $user_ID;

		$post_type_object = get_post_type_object( $postarr['post_type'] );

		if ( current_user_can( $post_type_object->cap->edit_others_posts ) ) {

			if ( 'auto-draft' == $postarr['post_status']
				&& $user_ID == $postarr['post_author'] )
					$data['post_author'] = gNetwork()->user();
		}

		return $data;
	}
}

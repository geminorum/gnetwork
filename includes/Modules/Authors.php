<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

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
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Authors', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);

		Admin::registerTool( $this->key,
			_x( 'Authors', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'tools' ], 'list_users'
		);
	}

	public function default_options()
	{
		return [
			'siteuser_as_default' => '0',
			'register_shortcodes' => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'siteuser_as_default',
					'title'       => _x( 'Default Author', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Uses site user as default author of new posts in admin.', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ),
				],
				'register_shortcodes',
			],
		];
	}

	public function settings_sidebox( $sub, $uri )
	{
		self::summarySiteUser();
	}

	// TODO: add site user to this blog ( with cap select )
	protected function render_tools_html( $uri, $sub = 'general' )
	{
		$users = [
			'none' => Settings::showOptionNone(),
			'all'  => Settings::showOptionAll(),
		];

		foreach ( WordPress::getUsers() as $user_id => $user )
			$users[$user_id] = sprintf( '%1$s (%2$s)', $user->display_name, $user->user_login );

		echo '<table class="form-table">';

			if ( $user = gNetwork()->user() ) {

				$name = get_userdata( $user )->display_name;
				$edit = WordPress::getUserEditLink( $user );

				echo '<tr><th scope="row">'._x( 'Site User', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'</th><td>';
				echo $this->wrap_open_buttons();

				printf( _x( 'Site-User for current network is: %s', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ),
				( $edit ? HTML::link( $name, $edit, TRUE ) : $name ) );

				echo '&nbsp;&mdash;&nbsp;';

				printf( _x( 'Default role for this site is: %s', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ),
					( '<code>'.get_option( 'default_role' ).'</code>' ) );

				echo '&nbsp;&mdash;&nbsp;';

				if ( is_user_member_of_blog( $user, get_current_blog_id() ) )
					HTML::desc( _x( 'The user is already member of this blog.', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ), FALSE );

				else
					Settings::submitButton( 'add_site_user', _x( 'Add User to this Site', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ) );

				echo '</p></td></tr>';
			}

			echo '<tr><th scope="row">'._x( 'Bulk Change Author', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'</th><td>';

			echo $this->wrap_open_buttons();

			$this->do_settings_field( [
				'type'      => 'select',
				'field'     => 'from_user_id',
				'name_attr' => 'from_user_id',
				'values'    => $users,
				'default'   => 'none',
			] );

			echo '&nbsp;&mdash;&nbsp;'._x( 'to', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&ndash;&nbsp;';

			unset( $users['all'] );

			$this->do_settings_field( [
				'type'      => 'select',
				'field'     => 'to_user_id',
				'name_attr' => 'to_user_id',
				'values'    => $users,
				'default'   => gNetwork()->user(),
			] );

			echo '&nbsp;&mdash;&nbsp;'._x( 'on', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&ndash;&nbsp;';

			$this->do_settings_field( [
				'type'      => 'select',
				'field'     => 'on_post_type',
				'name_attr' => 'on_post_type',
				'default'   => 'post',
				'values'    => WordPress::getPostTypes(),
			] );

			echo '&nbsp;&mdash;&nbsp;'._x( 'do', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&ndash;&nbsp;';

			Settings::submitButton( 'bulk_change_author', _x( 'Change', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ), FALSE, TRUE );

			echo '</p></td></tr>';
		echo '</table>';
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( ! empty( $_POST['add_site_user'] ) ) {

			if ( $user = gNetwork()->user() ) {

				$added = add_user_to_blog( get_current_blog_id(), $user, get_option( 'default_role', 'subscriber' ) );

				if ( $added && ! self::isError( $added ) )
					WordPress::redirectReferer( 'updated' );
			}

			WordPress::redirectReferer( 'wrong' );

		} else if ( ! empty( $_POST['bulk_change_author'] ) ) {

			$this->check_referer( $sub );

			if ( empty( $_POST['from_user_id'] ) || 'none' == $_POST['from_user_id'] )
				return;

			$to_user  = isset( $_POST['to_user_id'] ) ? intval( $_POST['to_user_id'] ) : gNetwork()->user( TRUE );
			$posttype = isset( $_POST['on_post_type'] ) ? $_POST['on_post_type'] : 'post';

			if ( $_POST['from_user_id'] == $to_user )
				return;

			if ( 'all' == $_POST['from_user_id'] )
				$count = $this->bulk_change_all_authors( $to_user, $posttype );

			else
				$count = $this->bulk_change_author( intval( $_POST['from_user_id'] ), $to_user, $posttype );

			if ( FALSE === $count )
				WordPress::redirectReferer( 'wrong' );

			else
				WordPress::redirectReferer( [
					'message' => 'changed',
					'count'   => $count,
				] );
		}
	}

	public static function userRoles()
	{
		HTML::tableSide( get_editable_roles() );
	}

	public static function summarySiteUser( $default_role = TRUE )
	{
		if ( $user = gNetwork()->user() ) {

			$name = get_userdata( $user )->display_name;
			$edit = WordPress::getUserEditLink( $user );

			HTML::desc( sprintf( _x( 'Site-User for current network is: %s', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ),
				$edit ? HTML::link( $name, $edit, TRUE ) : $name ) );

		} else {

			HTML::desc( _x( 'Site-User for current network is <strong>not</strong> defined.', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ) );
		}

		if ( $default_role ) {

			HTML::desc( sprintf( _x( 'Default role for this site is: %s', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ),
				'<code>'.get_option( 'default_role' ).'</code>' ) );
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
		if ( $this->options['register_shortcodes'] )
			$this->shortcodes( $this->get_shortcodes() );
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

		if ( FALSE === $args['context'] || WordPress::isXML() )
			return NULL;

		if ( $args['cap'] && ! WordPress::cuc( $args['cap'] ) )
			return $args['text'];

		if ( ! is_user_logged_in() )
			return $args['text'];

		WordPress::doNotCache();

		return do_shortcode( $content );
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

			WordPress::doNotCache();

			return $args['text'];
		}

		return do_shortcode( $content );
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

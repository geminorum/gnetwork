<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Users extends ModuleCore
{

	protected $key     = 'users';
	protected $network = FALSE;
	protected $front   = FALSE;

	protected function setup_actions()
	{
		if ( WordPress::getSiteUserID() && $this->options['siteuser_as_default'] && is_admin() )
			add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 9, 2 );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Users', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' ), 'remove_users'
		);
	}

	public function default_options()
	{
		return array(
			'siteuser_as_default' => WordPress::getSiteUserID() ? '1' : '0',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'siteuser_as_default',
					'title'       => _x( 'Default Author', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'The site user as default author of new posts in admin', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => WordPress::getSiteUserID() ? '1' : '0',
				),
			),
		);
	}

	// TODO: link to user edit
	public function settings_sidebox( $sub, $uri )
	{
		if ( $user = WordPress::getSiteUserID() )
			printf( _x( 'Network site user is %s', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN ), get_userdata( $user )->display_name );
		else
			_ex( 'Network site user is not defined', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN );
	}

	public function settings_before( $sub, $uri )
	{
		echo '<table class="form-table">';

			// TODO: add site user to this blog ( with cap select )

			echo '<tr><th scope="row">'._x( 'Bulk Change Author', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN ).'</th><td>';

			$this->do_settings_field( array(
				'type'      => 'blog_users',
				'field'     => 'from_user_id',
				'name_attr' => 'from_user_id',
			), FALSE );

			echo '&nbsp;&mdash;&nbsp;'._x( 'to', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash; &nbsp;';

			$this->do_settings_field( array(
				'type'      => 'blog_users',
				'field'     => 'to_user_id',
				'name_attr' => 'to_user_id',
				'default'   => WordPress::getSiteUserID(),
			), FALSE );

			echo '&nbsp;&mdash;&nbsp;'._x( 'on', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash; &nbsp;';

			$this->do_settings_field( array(
				'type'      => 'select',
				'field'     => 'on_post_type',
				'name_attr' => 'on_post_type',
				'default'   => 'post',
				'values'    => WordPress::getPostTypes(),
			), FALSE );

			echo '&nbsp;&mdash;&nbsp;'._x( 'do', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash; &nbsp;';

			submit_button( _x( 'Change', 'Modules: Users: Settings', GNETWORK_TEXTDOMAIN ), 'secondary', 'bulk_change_author', FALSE, Settings::getButtonConfirm() );

			echo '</td></tr>';
		echo '</table>';
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['bulk_change_author'] ) ) {

			$this->check_referer( $sub );

			$from_user_id = isset( $_POST['from_user_id'] ) ? intval( $_POST['from_user_id'] ) : FALSE;
			$to_user_id   = isset( $_POST['to_user_id'] ) ? intval( $_POST['to_user_id'] ) : WordPress::getSiteUserID( TRUE );
			$on_post_type = isset( $_POST['on_post_type'] ) ? $_POST['on_post_type'] : 'post';

			if ( $from_user_id && $to_user_id && ( $from_user_id != $to_user_id ) ) {

				if ( $count = $this->bulk_change_author( $from_user_id, $to_user_id, $on_post_type ) )
					self::redirect_referer( array(
						'message' => 'changed',
						'count'   => $count,
					) );

				else
					self::redirect_referer( 'nochange' );
			}
		}
	}

	private function bulk_change_author( $from_user_id, $to_user_id, $on_post_type = 'post' )
	{
		global $wpdb;

		$user = get_userdata( $to_user_id );

		if ( ! $user || ! $user->exists() )
			return FALSE;

		return $wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->posts SET post_author = %s WHERE post_author = %s AND post_type = %s
		", $user->ID, $from_user_id, $on_post_type ) );
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		global $user_ID;

		$post_type_object = get_post_type_object( $postarr['post_type'] );

		if ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) {

			if ( 'auto-draft' == $postarr['post_status']
				&& $user_ID == $postarr['post_author'] )
					$data['post_author'] = WordPress::getSiteUserID();
		}

		return $data;
	}
}

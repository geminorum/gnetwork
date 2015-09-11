<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkUsers extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = 'users';

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'users',
			__( 'Users', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' ), 'remove_users'
		);

		if ( GNETWORK_SITE_USER_ID && $this->options['siteuser_as_default'] && is_admin() )
			add_filter( 'wp_insert_post_data', array( &$this, 'wp_insert_post_data' ), 9, 2 );
	}

	public function settings( $sub = NULL )
	{
		if ( 'users' == $sub ) {

			if ( isset( $_POST['bulk_change_author'] ) ) {

				$this->check_referer( $sub );

				$from_user_id = isset( $_POST['from_user_id'] ) ? intval( $_POST['from_user_id'] ) : FALSE;
				$to_user_id   = isset( $_POST['to_user_id']   ) ? intval( $_POST['to_user_id']   ) : GNETWORK_SITE_USER_ID;
				$on_post_type = isset( $_POST['on_post_type'] ) ? $_POST['on_post_type'] : 'post';

				if ( $from_user_id && $to_user_id && ( $from_user_id != $to_user_id ) )
					$this->bulk_change_author( $from_user_id, $to_user_id, $on_post_type );

			} else {
				$this->settings_update( $sub );
			}

			add_action( 'gnetwork_admin_settings_sub_users', array( &$this, 'settings_html' ), 10, 2 );
			add_filter( 'gnetwork_admin_settings_messages', array( &$this, 'admin_settings_messages' ), 10 );
			$this->register_settings();
		}
	}

	public function default_options()
	{
		return array(
			'siteuser_as_default' => GNETWORK_SITE_USER_ID ? '1' : '0',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'   => 'siteuser_as_default',
					'type'    => 'enabled',
					'title'   => _x( 'Default Author', 'Enable Full Comments On Dashboard', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'The site user as default author of new posts in admin', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
			),
		);
	}

	public function settings_sidebox( $sub, $settings_uri )
	{
		if ( GNETWORK_SITE_USER_ID )
			printf( __( 'Network site user is %s', GNETWORK_TEXTDOMAIN ), get_userdata( GNETWORK_SITE_USER_ID )->display_name );
		else
			_e( 'Network site user is not defined', GNETWORK_TEXTDOMAIN );
	}

	public function settings_before( $sub, $settings_uri )
	{
		echo '<table class="form-table">';
			echo '<tr><th scope="row">'.__( 'Bulk Change Author', GNETWORK_TEXTDOMAIN ).'</th><td>';

			$this->do_settings_field( array(
				'type'      => 'blog_users',
				'field'     => 'from_user_id',
				'name_attr' => 'from_user_id',
			), FALSE );

			echo '&nbsp;&mdash; &nbsp;'.__( 'to', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash; &nbsp;';

			$this->do_settings_field( array(
				'type'      => 'blog_users',
				'field'     => 'to_user_id',
				'name_attr' => 'to_user_id',
				'default'   => GNETWORK_SITE_USER_ID ? GNETWORK_SITE_USER_ID : '0',
			), FALSE );

			echo '&nbsp;&mdash; &nbsp;'.__( 'on', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash; &nbsp;';

			$this->do_settings_field( array(
				'type'      => 'select',
				'field'     => 'on_post_type',
				'name_attr' => 'on_post_type',
				'default'   => 'post',
				'values'    => gNetworkUtilities::getPostTypes(),
			), FALSE );

			echo '&nbsp;&mdash; &nbsp;'.__( 'do', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash; &nbsp;';

			submit_button( __( 'Change', GNETWORK_TEXTDOMAIN ), 'secondary', 'bulk_change_author', FALSE,
				sprintf( 'onclick="return confirm( \'%s\' )"', __( 'Are you sure? This operation can not be undone.', GNETWORK_TEXTDOMAIN ) ) );

			echo '</td></tr>';
		echo '</table>';
	}

	private function bulk_change_author( $from_user_id, $to_user_id, $on_post_type = 'post' )
	{
		global $wpdb;

		$user = get_userdata( $to_user_id );

		if ( ! $user || ! $user->exists() )
			return FALSE;

		$count = $wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->posts SET post_author = %s WHERE post_author = %s AND post_type = %s
		", $user->ID, $from_user_id, $on_post_type ) );

		if ( $count )
			self::redirect_referer( array(
				'message' => 'bulk-author-changed',
				'count'   => $count,
			) );
		else
			self::redirect_referer( 'bulk-author-not-changed' );
	}

	public function admin_settings_messages( $messages )
	{
		$count = isset( $_GET['count'] ) ? $_GET['count'] : 0 ;
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'count', $_SERVER['REQUEST_URI'] );

		$messages['bulk-author-changed']     = gNetworkUtilities::notice( sprintf( __( '%s Post(s) Changed', GNETWORK_TEXTDOMAIN ), $count ), 'updated fade', FALSE );
		$messages['bulk-author-not-changed'] = gNetworkUtilities::notice( __( 'No Post Changed', GNETWORK_TEXTDOMAIN ), 'error', FALSE );

		return $messages;
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		global $user_ID;

		$post_type_object = get_post_type_object( $postarr['post_type'] );

		if ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) {

			if ( 'auto-draft' == $postarr['post_status']
				&& $user_ID == $postarr['post_author'] )
					$data['post_author'] = (int) GNETWORK_SITE_USER_ID;
		}

		return $data;
	}
}

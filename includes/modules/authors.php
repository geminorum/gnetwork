<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Authors extends \geminorum\gNetwork\ModuleCore
{

	protected $key     = 'authors';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 8 );

		if ( WordPress::getSiteUserID() && $this->options['siteuser_as_default'] && is_admin() )
			$this->filter( 'wp_insert_post_data', 2, 9 );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Authors', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' ), 'list_users'
		);

		Admin::registerMenu( 'roles',
			_x( 'Roles', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			FALSE, 'list_users'
		);
	}

	public function default_options()
	{
		return array(
			'siteuser_as_default' => '0',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'siteuser_as_default',
					'title'       => _x( 'Default Author', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'The Site User as Default Author of New Posts in Admin', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ),
				),
			),
		);
	}

	// TODO: link to user edit
	public function settings_sidebox( $sub, $uri )
	{
		if ( $user = WordPress::getSiteUserID() )
			printf( _x( 'Network Site User Is %s', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ), get_userdata( $user )->display_name );
		else
			_ex( 'Network Site User Is Not Defined', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN );
	}

	public function settings_before( $sub, $uri )
	{
		echo '<table class="form-table">';

			// TODO: add site user to this blog ( with cap select )

			echo '<tr><th scope="row">'._x( 'Bulk Change Author', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'</th><td>';

			$this->do_settings_field( array(
				'type'      => 'user',
				'field'     => 'from_user_id',
				'name_attr' => 'from_user_id',
			) );

			echo '&nbsp;&mdash;&nbsp;'._x( 'to', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash; &nbsp;';

			$this->do_settings_field( array(
				'type'      => 'user',
				'field'     => 'to_user_id',
				'name_attr' => 'to_user_id',
				'default'   => WordPress::getSiteUserID(),
			) );

			echo '&nbsp;&mdash;&nbsp;'._x( 'on', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash;&nbsp;';

			$this->do_settings_field( array(
				'type'      => 'select',
				'field'     => 'on_post_type',
				'name_attr' => 'on_post_type',
				'default'   => 'post',
				'values'    => WordPress::getPostTypes(),
			) );

			echo '&nbsp;&mdash;&nbsp;'._x( 'do', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ).'&nbsp;&mdash;&nbsp;';

			Settings::submitButton( 'bulk_change_author', _x( 'Change', 'Modules: Authors: Settings', GNETWORK_TEXTDOMAIN ), FALSE, TRUE );

			echo '</td></tr>';
		echo '</table>';
	}

	public function settings( $sub = NULL )
	{
		if ( 'roles' == $sub )
			add_action( $this->settings_hook( $sub, 'admin' ), array( $this, 'settings_form_roles' ), 10, 2 );

		else
			parent::settings( $sub );
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( ! empty( $_POST['bulk_change_author'] ) ) {

			$this->check_referer( $sub );

			$from_user_id = isset( $_POST['from_user_id'] ) ? intval( $_POST['from_user_id'] ) : FALSE;
			$to_user_id   = isset( $_POST['to_user_id'] ) ? intval( $_POST['to_user_id'] ) : WordPress::getSiteUserID( TRUE );
			$on_post_type = isset( $_POST['on_post_type'] ) ? $_POST['on_post_type'] : 'post';

			if ( $from_user_id && $to_user_id && ( $from_user_id != $to_user_id ) ) {

				if ( $count = $this->bulk_change_author( $from_user_id, $to_user_id, $on_post_type ) )
					WordPress::redirectReferer( array(
						'message' => 'changed',
						'count'   => $count,
					) );

				else
					WordPress::redirectReferer( 'nochange' );
			}
		}
	}

	public function settings_form_roles( $uri, $sub = 'general' )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', FALSE );

			HTML::h3( _x( 'Current User Roles and Capabilities', 'Modules: Authors', GNETWORK_TEXTDOMAIN ) );
			HTML::tableSide( get_editable_roles() );

		$this->settings_form_after( $uri, $sub );
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

	public function init()
	{
		$this->shortcodes( array(
			'logged-in'     => 'shortcode_logged_in',
			'not-logged-in' => 'shortcode_not_logged_in',
		) );
	}

	public function shortcode_logged_in( $atts = array(), $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'text'    => NULL,
			'cap'     => NULL,
			'context' => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] || is_feed() )
			return NULL;

		if ( $args['cap'] && ! WordPress::cuc( $args['cap'] ) )
			return $args['text'];

		if ( ! is_user_logged_in() )
			return $args['text'];

		WordPress::doNotCache();

		return $content;
	}

	public function shortcode_not_logged_in( $atts = array(), $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( array(
			'text'    => NULL,
			'context' => NULL,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( is_user_logged_in() ) {

			WordPress::doNotCache();

			return $args['text'];
		}

		return $content;
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		global $user_ID;

		$post_type_object = get_post_type_object( $postarr['post_type'] );

		if ( current_user_can( $post_type_object->cap->edit_others_posts ) ) {

			if ( 'auto-draft' == $postarr['post_status']
				&& $user_ID == $postarr['post_author'] )
					$data['post_author'] = WordPress::getSiteUserID();
		}

		return $data;
	}
}

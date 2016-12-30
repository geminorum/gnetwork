<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class User extends ModuleCore
{

	protected $key = 'user';

	public $menus = array();

	protected function setup_actions()
	{
		if ( is_admin() ) {

			if ( ! $this->options['user_locale'] ) {
				$this->filter( 'admin_body_class' );
				$this->filter( 'insert_user_meta', 3, 8 );
			}
		}

		if ( $this->options['contact_methods'] )
			$this->filter( 'user_contactmethods', 2 );

		if ( ! is_multisite() )
			return TRUE;

		if ( is_user_admin() ) {
			add_action( 'user_admin_menu', array( $this, 'user_admin_menu' ), 12 );
			add_action( 'user_admin_menu', array( $this, 'user_admin_menu_late' ), 999 );
		}
	}

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'User', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'contact_methods' => '1',
			'user_locale'     => '0',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'contact_methods',
					'title'       => _x( 'Contact Methods', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds extra contact methods to user profiles', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				),
				array(
					'field'       => 'user_locale',
					'title'       => _x( 'User Language', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'User admin language switcher', 'Modules: User: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( Settings::getMoreInfoIcon( 'https://core.trac.wordpress.org/ticket/29783' ) ),
				),
			),
		);
	}

	public function user_admin_menu()
	{
		do_action( $this->base.'_setup_menu', 'user' );

		$hook = add_menu_page(
			_x( 'Network Extras', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ),
			_x( 'Extras', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ),
			'exist',
			$this->base,
			array( $this, 'settings_page' ),
			'dashicons-screenoptions',
			120
		);

		add_action( 'load-'.$hook, array( $this, 'settings_load' ) );

		foreach ( $this->menus as $sub => $args ) {
			add_submenu_page( $this->base,
				sprintf( _x( 'gNetwork Extras: %s', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ), $args['title'] ),
				$args['title'],
				$args['cap'],
				$this->base.'&sub='.$sub,
				array( $this, 'settings_page' )
			);
		}
	}

	public function user_admin_menu_late()
	{
		$GLOBALS['submenu'][$this->base][0] = array(
			_x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			'exist',
			$this->base,
			_x( 'Network Extras', 'Modules: User: Page Menu', GNETWORK_TEXTDOMAIN ),
		);
	}

	public static function registerMenu( $sub, $title = NULL, $callback = FALSE, $capability = 'read' )
	{
		if ( ! is_user_admin() )
			return;

		gNetwork()->user->menus[$sub] = array(
			'title' => $title ? $title : $sub,
			'cap'   => $capability,
		);

		if ( $callback ) // && is_callable( $callback ) )
			add_action( 'gnetwork_user_settings', $callback );
	}

	public function settings_load()
	{
		if ( ( $sub = isset( $_REQUEST['sub'] ) ? $_REQUEST['sub'] : NULL ) )
			$GLOBALS['submenu_file'] = $this->base.'&sub='.$sub;

		do_action( $this->base.'_user_settings', $sub );
	}

	private function subs()
	{
		$subs = array();

		$subs['overview'] = _x( 'Overview', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );

		foreach ( $this->menus as $sub => $args )
			if ( WordPress::cuc( $args['cap'] ) )
				$subs[$sub] = $args['title'];

		if ( WordPress::isSuperAdmin() )
			$subs['console'] = _x( 'Console', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN );

		return $subs;
	}

	public function settings_page()
	{
		$uri  = Settings::userURL( FALSE );
		$sub  = Settings::sub( 'overview' );
		$subs = $this->filters( 'settings_subs', $this->subs() );

		Settings::wrapOpen( $sub, $this->base, 'settings' );

		if ( 'overview' == $sub
			|| ( 'console' == $sub && WordPress::isSuperAdmin() )
			|| ( isset( $this->menus[$sub] ) && WordPress::cuc( $this->menus[$sub]['cap'] ) ) ) {

			$messages = $this->filters( 'settings_messages', Settings::messages(), $sub );

			Settings::headerTitle();
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' ) )
				require_once( GNETWORK_DIR.'includes/settings/'.$this->key.'.'.$sub.'.php' );
			else
				$this->actions( 'settings_sub_'.$sub, $uri, $sub );

		} else {

			Settings::cheatin();
		}

		Settings::wrapClose();
	}

	public function user_contactmethods( $contactmethods, $user )
	{
		return array_merge( $contactmethods, array(
			'googleplus' => _x( 'Google+ Profile', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'twitter'    => _x( 'Twitter', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
			'mobile'     => _x( 'Mobile Phone', 'Modules: User: User Contact Method', GNETWORK_TEXTDOMAIN ),
		) );
	}

	public function admin_body_class( $classes )
	{
		return $classes.' hide-userlocale-option';
	}

	public function insert_user_meta( $meta, $user, $update )
	{
		if ( $update )
			delete_user_meta( $user->ID, 'locale' );

		unset( $meta['locale'] );

		return $meta;
	}
}

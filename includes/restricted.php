<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkRestricted extends gNetworkModuleCore
{

	protected $option_key = 'restricted';
	protected $network    = FALSE;

	private $bouncer = FALSE;

	protected function setup_actions()
	{
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 1 );
	}

	public function plugins_loaded()
	{
		// FIXME: temporarly : bail if gMember Restricted is present
		if ( class_exists( 'gMemberRestrictedSettings' ) )
			return;

		if ( 'none' != $this->options['restricted_site'] )
			$this->bouncer = new gNetworkRestrictedBouncer( $this->options );

		add_action( 'init', array( $this, 'init' ), 1 );

		gNetworkAdmin::registerMenu( 'restricted',
			_x( 'Restricted', 'Restricted Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( is_admin() ) {
			add_filter( 'show_user_profile', array( $this, 'edit_user_profile' ), 10, 1  );
			add_action( 'edit_user_profile', array( $this, 'edit_user_profile' ), 10, 1  );
			add_action( 'personal_options_update', array( $this, 'edit_user_profile_update' ), 10, 1 );
			add_action( 'edit_user_profile_update', array( $this, 'edit_user_profile_update' ), 10, 1 );
		}
	}

	public function init()
	{
		if ( ! is_user_logged_in() )
			return;

		if ( ! self::cuc( $this->options['restricted_admin'] ) ) {

			if ( is_admin() ) {

				global $pagenow;

				if ( 'open' == $this->options['restricted_profile']
					&& 'profile.php' == $pagenow ) {

						// do nothing

				} else {
					wp_redirect( get_home_url() );
					exit();
				}
			}

			$this->remove_menus();

		} else if ( ! self::cuc( $this->options['restricted_site'] ) ) {

			$this->remove_menus();

		}
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'restricted_site',
					'type'        => 'roles',
					'title'       => _x( 'Site Restriction', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Selected and above can access to the site.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'default'     => 'none',
				),
				array(
					'field'       => 'restricted_admin',
					'type'        => 'roles',
					'title'       => _x( 'Admin Restriction', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Selected and above can access to the admin.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'default'     => 'none',
				),
				array(
					'field'       => 'restricted_profile',
					'type'        => 'select',
					'title'       => _x( 'Admin Profile', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Admin profile access if the site is restricted.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'default'     => 'open',
					'values'      => array(
						'open'   => _x( 'Open', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
						'closed' => _x( 'Restricted', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'restricted_feed',
					'type'        => 'select',
					'title'       => _x( 'Feed Restriction', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Feed access if the site is restricted.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'default'     => 'open',
					'values'      => array(
						'open'   => _x( 'Open', GNETWORK_TEXTDOMAIN ),
						'closed' => _x( 'Restricted', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'redirect_page',
					'type'        => 'page',
					'title'       => _x( 'Redirect Page', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Not authorized users will redirect to this page, to login page if not selected.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'none_title'  => _x( '&mdash; Login &mdash;', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'none_value'  => '0',
				),
				array(
					'field'       => 'restricted_notice',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Restricted Notice', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This will show on top of this site login page. <code>%1$s</code> for the role, <code>%2$s</code> for the page.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'restricted_access',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Restricted Access', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'This will show on 403 page for logged-in users. <code>%1$s</code> for the role, <code>%2$s</code> for the page.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				),
			),
		);
	}

	public function default_options()
	{
		return array(
			'restricted_site'    => 'none',
			'restricted_admin'   => 'none',
			'restricted_profile' => 'open',
			'restricted_feed'    => 'open',
			'redirect_page'      => '0',
			'restricted_notice'  => _x( '<p>This site is restricted to users with %1$s access level. Please visit <a href="%2$s">here</a> to request access.</p>', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
			'restricted_access'  => _x( '<p>You do not have %1$s access level. Please visit <a href="%2$s">here</a> to request access.</p>', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
		);
	}

	private function remove_menus()
	{
		gNetworkAdminBar::removeMenus( array(
			'site-name',
			'my-sites',
			'blog-'.get_current_blog_id(),
			'edit',
			'new-content',
			'comments',
		) );
	}

	public function edit_user_profile( $profileuser )
	{
		if ( 'none' == $this->options['restricted_site']
			&& ! self::isDev() )
				return;

		$feedkey = gNetworkRestrictedBouncer::getUserFeedKey( $profileuser->ID, FALSE );
		$urls    = self::getFeeds( $feedkey );

		echo self::html( 'h2', _x( 'Private Feeds', 'Restricted Module', GNETWORK_TEXTDOMAIN ) );
		echo self::html( 'p', _x( 'Used to access restricted site feeds.', 'Restricted Module', GNETWORK_TEXTDOMAIN ) );

		echo '<table class="form-table">';

			$this->do_settings_field( array(
				'title'       => _x( 'Access Key', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				'type'        => 'text',
				'field'       => 'restricted_feed_key',
				'default'     => $feedkey ? $feedkey : _x( 'Access key not found', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				'field_class' => 'regular-text code',
				'desc'        => _x( 'The key will be used on all restricted site feed URLs.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				'disabled'    => TRUE,
			), TRUE );

			$operations = array( 'none' => _x( '&mdash; Select &mdash;', 'Restricted Module', GNETWORK_TEXTDOMAIN ) );
			if ( $feedkey ) {
				$operations['reset']  = _x( 'Reset your access key', 'Restricted Module', GNETWORK_TEXTDOMAIN );
				$operations['remove'] = _x( 'Remove your access key', 'Restricted Module', GNETWORK_TEXTDOMAIN );
			} else {
				$operations['generate'] = _x( 'Generate new access key', 'Restricted Module', GNETWORK_TEXTDOMAIN );
			}

			$this->do_settings_field( array(
				'title'   => _x( 'Key Operations', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				'type'    => 'select',
				'field'   => 'feed_operations',
				'default' => 'none',
				'values'  => $operations,
				'desc'    => _x( 'Select an operation to work with your private feed access key.', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
			), TRUE );

			if ( $feedkey ) {

				$this->do_settings_field( array(
					'title'  => _x( 'Your Feed', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'type'   => 'custom',
					'field'  => 'restricted_feed_url',
					'values' => '<code><a href="'.$urls['rss2'].'">'.$urls['rss2'].'</a></code>',
				), TRUE );

				$this->do_settings_field( array(
					'title'  => _x( 'Your Comments Feed', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
					'type'   => 'custom',
					'field'  => 'restricted_feed_comments_url',
					'values' => '<code><a href="'.$urls['comments_rss2_url'].'">'.$urls['comments_rss2_url'].'</a></code>',
				), TRUE );
			}

		echo '</table>';
	}

	public function edit_user_profile_update( $user_id )
	{
		if ( isset( $_POST['gnetwork_restricted']['feed_operations'] )
			&& 'none' != $_POST['gnetwork_restricted']['feed_operations']
			&& strlen( $_POST['gnetwork_restricted']['feed_operations'] ) > 0 ) {

			switch ( $_POST['gnetwork_restricted']['feed_operations'] ) {
				case 'remove' :
					delete_user_meta( $user_id, 'feed_key' );
				break;
				case 'reset' :
				case 'generate' :
					$feedkey = gNetworkRestrictedBouncer::getUserFeedKey( $user_id, FALSE, TRUE );
				break;
			}
		}
	}

	public static function is()
	{
		global $gNetwork;

		return ( ! self::cuc( $gNetwork->restricted->options['restricted_site'] ) );
	}

	public static function getFeeds( $feed_key = FALSE, $check = TRUE )
	{
		if ( ! $feed_key && $check )
			$feed_key = gNetworkRestrictedBouncer::getUserFeedKey( FALSE, FALSE );

		return array(
			'rss2'              => ( $feed_key ? add_query_arg( 'feedkey', $feed_key, get_feed_link( 'rss2' ) ) : get_feed_link( 'rss2' ) ),
			'comments_rss2_url' => ( $feed_key ? add_query_arg( 'feedkey', $feed_key, get_feed_link( 'comments_rss2' ) ) : get_feed_link( 'comments_rss2' ) ),
		);
	}

	// HELPER
	public static function get403Logout( $class = 'logout' )
	{
		$html = self::html( 'a', array(
			'href'  => GNETWORK_BASE,
			'title' => GNETWORK_NAME,
		), _x( 'Home Page', 'Restricted Module', GNETWORK_TEXTDOMAIN ) );

		if ( is_user_logged_in() ) {
			$html .= ' / '.self::html( 'a', array(
				'href' => wp_logout_url(),
				'title' => _x( 'Logout of this site', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
			), _x( 'Log Out', 'Restricted Module', GNETWORK_TEXTDOMAIN ) );
		}

		if ( $class )
			$html = self::html( 'div', array(
				'class' => $class,
			), $html );

		return $html;
	}

	// HELPER
	public static function get403Message( $class = 'message' )
	{
		global $gNetwork;

		if ( isset( $gNetwork->restricted ) && $gNetwork->restricted->options['restricted_access'] )
			$html = self::getNotice(
				$gNetwork->restricted->options['restricted_access'],
				$gNetwork->restricted->options['restricted_site'],
				$gNetwork->restricted->options['redirect_page'],
				FALSE );
		else
			$html = _x( 'You do not have sufficient access level.', 'Restricted Module', GNETWORK_TEXTDOMAIN );

		if ( $class )
			$html = self::html( 'div', array(
				'class' => $class,
			), $html );

		return $html;
	}

	// HELPER
	public static function getNotice( $notice, $role, $page = FALSE, $register = TRUE )
	{
		return sprintf( $notice,
			gNetworkUtilities::getUserRoles( $role ),
			( $page ? get_page_link( $page )
				: ( $register ? gNetworkUtilities::registerURL( 'site' ) : '#' ) ) );
	}
}

class gNetworkRestrictedBouncer extends gNetworkBaseCore
{

	protected $options        = array();
	protected $feed_key       = FALSE;
	protected $feed_key_valid = FALSE;
	protected $feed_access    = FALSE;

	public function __construct( $options )
	{
		$this->options = $options;

		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'admin_init', array( $this, 'admin_init' ), 1 );
		add_action( 'template_redirect', array( $this, 'template_redirect' ), 1 );

		if ( ! empty( $options['restricted_notice'] ) )
			add_filter( 'login_message', array( $this, 'login_message' ) );

		// block search engines and robots
		add_filter( 'robots_txt', array( $this, 'robots_txt' ) );
		add_filter( 'option_blog_public', function( $option ){
			return 0;
		}, 20 );
	}

	public function init()
	{
		if ( 'open' == $this->options['restricted_feed'] )
			return;

		if ( is_admin() )
			return;

		$this->feed_key = self::getUserFeedKey();

		if ( $this->feed_key && is_user_logged_in() )
			add_filter( 'feed_link', array( $this, 'feed_link' ), 12, 2 );

		$feed_key = isset( $_GET['feedkey'] ) ? trim( $_GET['feedkey'] ) : FALSE;
		if ( ! $feed_key ) {

			// no feed key, do nothing
			// restrictions comes automatically

		} else if ( is_user_logged_in() ) {

			if ( $feed_key == $this->feed_key )
				$this->feed_key_valid = TRUE;

			if ( 'logged_in_user' == $this->options['restricted_site']
				|| current_user_can( $this->options['restricted_site'] ) )
					$this->feed_access = TRUE;

		} else {

			global $wpdb;

			$founded = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_value = %s", $feed_key ) );

			if ( ! empty( $founded ) ) {
				$this->feed_key_valid = TRUE;

				if ( 'logged_in_user' == $this->options['restricted_site'] )
					$this->feed_access = TRUE;
				else if ( user_can( intval( $founded ), $this->options['restricted_site'] ) )
					$this->feed_access = TRUE;
			}
		}

		// PROBABLY NO NEED TO CHECK FURTHER!!!
		// return;

		// wordpress feed files
		$files = array(
			'wp-rss.php',
			'wp-rss2.php',
			'wp-atom.php',
			'wp-rdf.php',
			'wp-commentsrss2.php',
			'wp-feed.php',
		);

		if ( FALSE !== self::strposArray( $files, basename( $_SERVER['PHP_SELF'] ) ) )
			$this->check_feed_access();

		// wordpress feed queries
		if ( isset( $_GET['feed'] ) ) {
			$feeds = array(
				'comments-rss2',
				'comments-atom',
				'rss',
				'rss2',
				'atom',
				'rdf',
			);

			if ( FALSE !== self::strposArray( $feeds, $_GET['feed'] ) )
				$this->check_feed_access();
		}

		$actions = array(
			'do_feed_comments_rss2',
			'do_feed_comments_atom',
			'do_feed_rss',
			'do_feed_rss2',
			'do_feed_atom',
			'do_feed_rdf',
			'do_feed_json',
			'do_feed',
		);

		foreach ( $actions as $action )
			add_action( $action, array( $this, '_check_feed_access' ), 1 );
	}

	public function admin_init()
	{
		add_filter( 'privacy_on_link_title', function( $title ){
			return _x( 'Your site is restricted to public', 'Restricted Module', GNETWORK_TEXTDOMAIN );
		}, 20 );

		add_filter( 'privacy_on_link_text', function( $content ){
			return _x( 'Public Access Discouraged', 'Restricted Module', GNETWORK_TEXTDOMAIN );
		}, 20 );

		if ( current_user_can( $this->options['restricted_admin'] ) )
			return;
		global $pagenow;

		if ( 'open' == $this->options['restricted_profile']
			&& 'profile.php' == $pagenow ) {

			// do nothing

		} else if ( $this->options['redirect_page'] ) {
			wp_redirect( get_page_link( $this->options['redirect_page'] ), 302 );
			die();
		} else {
			gNetworkUtilities::getLayout( '403', TRUE, TRUE );
			die();
		}
	}

	public function feed_link( $output, $feed )
	{
		if ( $this->feed_key )
			return add_query_arg( 'feedkey', $this->feed_key, $output );

		return $output;
	}

	private function check_feed_access()
	{
		if ( $this->feed_key_valid && $this->feed_access ) {

			return;

		} else if ( $this->feed_key_valid ) {

			// key is valid but no access
			// redirect to request access page

			self::temp_feed(
				_x( 'Key Valid but no Access', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				_x( 'Your Key is valid but you have no access to this site', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				( $this->options['redirect_page'] ? get_page_link( $this->options['redirect_page'] ) : FALSE ) );

		} else if ( is_user_logged_in() ) {

			// user have access but the key is invalid
			// TODO: add notice;

			return;

		} else {

			// key is not valid and no access
			// redirect to request access page

			self::temp_feed(
				_x( 'No key, no Access', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				_x( 'You have to have a key to access this site\'s feed', 'Restricted Module', GNETWORK_TEXTDOMAIN ),
				( $this->options['redirect_page'] ? get_page_link( $this->options['redirect_page'] ) : FALSE ) );
		}
	}

	private function temp_feed( $title = '', $desc = '', $link = FALSE )
	{
		if ( ! $link )
			$link = get_bloginfo_rss( 'url' );

		// TODO: use getLayout helper

		header( "Content-Type: application/xml; ".get_option( 'blog_charset' ) );
		require_once( GNETWORK_DIR.'assets/layouts/feed.temp.php' );
		die();
	}

	public static function getUserFeedKey( $user_id = FALSE, $gen = TRUE, $reset = FALSE )
	{
		if ( ! $user_id && ! is_user_logged_in() )
			return FALSE;

		if ( ! $user_id )
			$user_id = get_current_user_id();

		$feed_key = get_user_meta( $user_id, 'feed_key', TRUE );

		if ( ( $gen && ( empty( $feed_key ) || FALSE == $feed_key ) ) || $reset ) {
			$feed_key = self::genFeedKey();
			update_user_meta( $user_id, 'feed_key', $feed_key );
		} // else return FALSE;

		return $feed_key;
	}

	private static function genFeedKey()
	{
		global $userdata;

		return self::genRandomKey( $userdata->user_login );
	}

	public function template_redirect()
	{
		// using BuddyPress and on the register page
		if ( function_exists( 'bp_is_current_component' )
			&& bp_is_current_component( 'register' ) )
				return;

		if ( 'closed' == $this->options['restricted_feed']
			&& is_feed() ) {
				$this->check_feed_access();
				return;
		}

		if ( $this->options['redirect_page']
			&& is_page( intval( $this->options['redirect_page'] ) ) )
				return;

		if ( is_user_logged_in() ) {

			if ( 'logged_in_user' == $this->options['restricted_site'] )
				return;

			if ( current_user_can( $this->options['restricted_site'] ) )
				return;

			if ( $this->options['redirect_page'] ) {
				self::redirect( get_page_link( $this->options['redirect_page'] ), 403 );
			} else {
				gNetworkUtilities::getLayout( '403', TRUE, TRUE );
				die();
			}
		}

		$current_url = self::currentURL();

		if ( ! is_front_page() && ! is_home() )
			self::redirect_login( $current_url );

		if ( $this->options['redirect_page'] )
			self::redirect( get_page_link( $this->options['redirect_page'] ), 403 );

		self::redirect_login( $current_url );
	}

	public function login_message()
	{
		echo '<div id="login_error">';

			echo gNetworkRestricted::getNotice(
				$this->options['restricted_notice'],
				$this->options['restricted_site'],
				$this->options['redirect_page'] );

		echo '</div>';

		echo '<style>#backtoblog {display:none;}</style>';
	}

	public function robots_txt( $output )
	{
		$output .= 'Disallow: /';
		$output .= "\n";
		return $output;
	}
}

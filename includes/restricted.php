<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkRestricted extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = 'restricted';
	var $_bouncer    = FALSE;

	protected function setup_actions()
	{
		add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ), 1 );
	}

	public function plugins_loaded()
	{
		// FIXME: temporarly : bail if gMember Restricted is present
		if ( class_exists( 'gMemberRestrictedSettings' ) )
			return;

		if ( 'none' != $this->options['restricted_site'] )
			$this->_bouncer = new gNetworkRestrictedBouncer( $this->options );

		add_action( 'init', array( &$this, 'init' ), 1 );

		gNetworkAdmin::registerMenu( 'restricted',
			__( 'Restricted', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);

		if ( is_admin() ) {
			add_filter( 'show_user_profile', array( &$this, 'edit_user_profile' ), 10, 1  );
			add_action( 'edit_user_profile', array( &$this, 'edit_user_profile' ), 10, 1  );
			add_action( 'personal_options_update', array( &$this, 'edit_user_profile_update' ), 10, 1 );
			add_action( 'edit_user_profile_update', array( &$this, 'edit_user_profile_update' ), 10, 1 );
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
					'field'   => 'restricted_site',
					'type'    => 'roles',
					'title'   => __( 'Site Restriction', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Selected and above can access to the site.', GNETWORK_TEXTDOMAIN ),
					'default' => 'none',
				),
				array(
					'field'   => 'restricted_admin',
					'type'    => 'roles',
					'title'   => __( 'Admin Restriction', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Selected and above can access to the admin.', GNETWORK_TEXTDOMAIN ),
					'default' => 'none',
				),
				array(
					'field'   => 'restricted_profile',
					'type'    => 'select',
					'title'   => __( 'Admin Profile', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Admin profile access if the site is restricted.', GNETWORK_TEXTDOMAIN ),
					'default' => 'open',
					'values'  => array(
						'open'   => __( 'Open', GNETWORK_TEXTDOMAIN ),
						'closed' => __( 'Restricted', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'   => 'restricted_feed',
					'type'    => 'select',
					'title'   => __( 'Feed Restriction', GNETWORK_TEXTDOMAIN ),
					'desc'    => __( 'Feed access if the site is restricted.', GNETWORK_TEXTDOMAIN ),
					'default' => 'open',
					'values'  => array(
						'open'   => __( 'Open', GNETWORK_TEXTDOMAIN ),
						'closed' => __( 'Restricted', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'  => 'redirect_page',
					'type'   => 'custom',
					'title'  => __( 'Redirect Page', GNETWORK_TEXTDOMAIN ),
					'desc'   => __( 'Not authorized users will redirect to this page, to login page if not selected.', GNETWORK_TEXTDOMAIN ),
					'values' => wp_dropdown_pages( array(
						'name'              => 'gnetwork_restricted[redirect_page]',
						'show_option_none'  => __( '&mdash; Login &mdash;', GNETWORK_TEXTDOMAIN ),
						'option_none_value' => '0',
						'selected'          => $this->options['redirect_page'],
						'echo'              => FALSE,
					) ),
				),
				array(
					'field'       => 'restricted_notice',
					'type'        => 'textarea',
					'title'       => __( 'Restricted Notice', GNETWORK_TEXTDOMAIN ),
					'desc'        => __( 'This will show on top of this site login page.', GNETWORK_TEXTDOMAIN ),
					'default'     => '',
					'field_class' => 'large-text code',
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
			'restricted_notice'  => __( '<p>This site is restricted to users with %1$s access level. Please visit <a href="%2$s">here</a> to request access.</p>', GNETWORK_TEXTDOMAIN ),
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
			&& ! gNetworkUtilities::isDev() )
				return;

		$feedkey = gNetworkRestrictedBouncer::getUserFeedKey( $profileuser->ID, FALSE );
		$urls    = self::getFeeds( $feedkey );

		echo gNetworkUtilities::html( 'h2', __( 'Private Feeds', GNETWORK_TEXTDOMAIN ) );
		echo gNetworkUtilities::html( 'p', __( 'Used to access restricted site feeds.', GNETWORK_TEXTDOMAIN ) );

		echo '<table class="form-table">';

			$this->do_settings_field( array(
				'title'       => __( 'Access Key', GNETWORK_TEXTDOMAIN ),
				'type'        => 'text',
				'field'       => 'restricted_feed_key',
				'default'     => $feedkey ? $feedkey : __( 'Access key not found', GNETWORK_TEXTDOMAIN ),
				'field_class' => 'regular-text code',
				'desc'        => __( 'The key will be used on all restricted site feed URLs.', GNETWORK_TEXTDOMAIN ),
				'disabled'    => TRUE,
			), TRUE );

			$operations = array( 'none' => __( '&mdash; Select &mdash;', GNETWORK_TEXTDOMAIN ) );
			if ( $feedkey ) {
				$operations['reset']  = __( 'Reset your access key', GNETWORK_TEXTDOMAIN );
				$operations['remove'] = __( 'Remove your access key', GNETWORK_TEXTDOMAIN );
			} else {
				$operations['generate'] = __( 'Generate new access key', GNETWORK_TEXTDOMAIN );
			}

			$this->do_settings_field( array(
				'title'   => __( 'Key Operations', GNETWORK_TEXTDOMAIN ),
				'type'    => 'select',
				'field'   => 'feed_operations',
				'default' => 'none',
				'values'  => $operations,
				'desc'    => __( 'Select an operation to work with your private feed access key.', GNETWORK_TEXTDOMAIN ),
			), TRUE );

			if ( $feedkey ) {

				$this->do_settings_field( array(
					'title'  => __( 'Your Feed', GNETWORK_TEXTDOMAIN ),
					'type'   => 'custom',
					'field'  => 'restricted_feed_url',
					'values' => '<code><a href="'.$urls['rss2'].'">'.$urls['rss2'].'</a></code>',
				), TRUE );

				$this->do_settings_field( array(
					'title'  => __( 'Your Comments Feed', GNETWORK_TEXTDOMAIN ),
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
}

class gNetworkRestrictedBouncer
{

	var $_options        = array();
	var $_feed_key       = FALSE;
	var $_feed_key_valid = FALSE;
	var $_feed_access    = FALSE;

	public function __construct( $options )
	{
		$this->_options = $options;

		add_action( 'init', array( &$this, 'init' ), 1 );
		add_action( 'admin_init', array( &$this, 'admin_init' ), 1 );
		add_action( 'template_redirect', array( &$this, 'template_redirect' ), 1 );

		if ( ! empty( $options['restricted_notice'] ) )
			add_filter( 'login_message', array( &$this, 'login_message' ) );

		// block search engines and robots
		add_filter( 'robots_txt', array( &$this, 'robots_txt' ) );
		add_filter( 'option_blog_public', function( $option ){
			return 0;
		}, 20 );
	}

	public function init()
	{
		if ( 'open' == $this->_options['restricted_feed'] )
			return;

		if ( is_admin() )
			return;

		$this->_feed_key = self::getUserFeedKey();

		if ( $this->_feed_key && is_user_logged_in() )
			add_filter( 'feed_link', array( &$this, 'feed_link' ), 12, 2 );

		$feed_key = isset( $_GET['feedkey'] ) ? trim( $_GET['feedkey'] ) : FALSE;
		if ( ! $feed_key ) {

			// no feed key, do nothing
			// restrictions comes automatically

		} else if ( is_user_logged_in() ) {

			if ( $feed_key == $this->_feed_key )
				$this->_feed_key_valid = TRUE;

			if ( 'logged_in_user' == $this->_options['restricted_site']
				|| current_user_can( $this->_options['restricted_site'] ) )
					$this->_feed_access = TRUE;

		} else {

			global $wpdb;

			$founded = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_value = %s", $feed_key ) );

			if ( ! empty( $founded ) ) {
				$this->_feed_key_valid = TRUE;

				if ( 'logged_in_user' == $this->_options['restricted_site'] )
					$this->_feed_access = TRUE;
				else if ( user_can( intval( $founded ), $this->_options['restricted_site'] ) )
					$this->_feed_access = TRUE;


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

		if ( FALSE !== gNetworkUtilities::strpos_arr( $files, basename( $_SERVER['PHP_SELF'] ) ) )
			$this->_check_feed_access();

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

			if ( FALSE !== gNetworkUtilities::strpos_arr( $feeds, $_GET['feed'] ) )
				$this->_check_feed_access();
		}

		$actions = array(
			'do_feed_comments_rss2',
			'do_feed_comments_atom',
			'do_feed_rss',
			'do_feed_rss2',
			'do_feed_atom',
			'do_feed_rdf',
			'do_feed',
		);

		foreach ( $actions as $action )
			add_action( $action, array( &$this, '_check_feed_access' ), 1 );
	}

	public function admin_init()
	{
		add_filter( 'privacy_on_link_title', function( $title ){
			return __( 'Your site is restricted to public', GNETWORK_TEXTDOMAIN );
		}, 20 );

		add_filter( 'privacy_on_link_text', function( $content ){
			return __( 'Public Access Discouraged', GNETWORK_TEXTDOMAIN );
		}, 20 );

		if ( current_user_can( $this->_options['restricted_admin'] ) )
			return;
		global $pagenow;

		if ( 'open' == $this->_options['restricted_profile']
			&& 'profile.php' == $pagenow ) {

			// do nothing

		} else if ( $this->_options['redirect_page'] ) {
			wp_redirect( get_page_link( $this->_options['redirect_page'] ), 302 );
			die();
		} else {
			gNetworkUtilities::getLayout( '403', TRUE, TRUE );
			die();
		}
	}

	public function feed_link( $output, $feed )
	{
		if ( $this->_feed_key )
			return add_query_arg( 'feedkey', $this->_feed_key, $output );

		return $output;
	}

	function _check_feed_access()
	{
		if ( $this->_feed_key_valid && $this->_feed_access ) {

			return;

		} else if ( $this->_feed_key_valid ) {

			// key is valid but no access
			// redirect to request access page

			//echo 'valid';

			self::_temp_feed(
				__( 'Key Valid but no Access', GNETWORK_TEXTDOMAIN ),
				__( 'Your Key is valid but you have no access to this site', GNETWORK_TEXTDOMAIN ),
				( $this->_options['redirect_page'] ? get_page_link( $this->_options['redirect_page'] ) : FALSE ) );

		} else if ( is_user_logged_in() ) {

			// you have access but your key is invalid
			// add notice;

			return;

		} else {
			//echo 'not valid';
			// key is not valid and no access
			// redirect to request access page

			self::_temp_feed(
				__( 'No key, no Access', GNETWORK_TEXTDOMAIN ),
				__( 'You have to have a key to access this site\'s feed', GNETWORK_TEXTDOMAIN ),
				( $this->_options['redirect_page'] ? get_page_link( $this->_options['redirect_page'] ) : FALSE ) );
		}

	}

	private function _temp_feed( $title = '', $desc = '', $link = FALSE )
	{
		if ( FALSE == $link )
			$link = get_bloginfo_rss( 'url' );

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
		} //else return FALSE;
		return $feed_key;
	}

	private static function genFeedKey()
	{
		global $userdata;
		$charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$keylength = 32;
		$key = '';
		for ( $i = 0; $i < $keylength; $i++ )
			$key .= $charset[( mt_rand( 0,( strlen( $charset ) - 1 ) ) )];
		return md5( $userdata->user_login.$key );
	}

	public function template_redirect()
	{
		// using BuddyPress and on the register page
		if ( function_exists( 'bp_is_current_component' )
			&& bp_is_current_component( 'register' ) )
				return;

		if ( 'closed' == $this->_options['restricted_feed']
			&& is_feed() ) {
			$this->_check_feed_access();
			return;
		}

		if ( $this->_options['redirect_page']
			&& is_page( intval( $this->_options['redirect_page'] ) ) )
				return;

		if ( is_user_logged_in() ) {

			if ( 'logged_in_user' == $this->_options['restricted_site'] )
				return;

			if ( current_user_can( $this->_options['restricted_site'] ) )
				return;

			if ( $this->_options['redirect_page'] ) {
				self::redirect( get_page_link( $this->_options['redirect_page'] ), TRUE );
			} else {
				gNetworkUtilities::getLayout( '403', TRUE, TRUE );
				die();
			}
		}

		$current_url = gNetworkUtilities::currentURL();

		if ( ! is_front_page() && ! is_home() )
			self::redirect( wp_login_url( $current_url, TRUE ) );

		if ( $this->_options['redirect_page'] )
			self::redirect( get_page_link( $this->_options['redirect_page'] ), TRUE );

		self::redirect( wp_login_url( $current_url, TRUE ) );
	}

	public static function redirect( $url, $found = FALSE )
	{
		if ( $found )
			wp_redirect( $url, 302 );
		else
			wp_redirect( $url );
		exit();
	}

	public function login_message()
	{
		echo '<div id="login_error">';

		printf( $this->_options['restricted_notice'],
			gNetworkUtilities::getUserRoles( $this->_options['restricted_site'] ),
			( $this->_options['redirect_page'] ? get_page_link( $this->_options['redirect_page'] ) : gNetworkUtilities::register_url( 'site' ) ) );

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

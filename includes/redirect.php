<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

// TODO : use module core internal option handling

class gNetworkRedirect extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = 'redirect';

	public function setup_actions()
	{
		add_action( 'init', array( &$this, 'init' ), 1 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		// add_action( 'init', array( & $this, 'init_redirects' ) );

		// NOT WORKING YET!
		if ( GNETWORK_REDIRECT_MAP )
			add_action( 'plugins_loaded', array( &$this, 'plugins_loaded_redirect_map' ), 1 );

	}

	public static function getRequestURI()
	{
		return apply_filters( 'gnetwork_redirect_request_uri', $_SERVER['REQUEST_URI'] );
	}

	// redirect map, saved on oprions
	function dummy()
	{
		return array(
			'search-string' => array(
				'strips' => array(),
				'map' => array(),
				'type' => 'post_id', // 'raw' / 'network' => array( 'post_id', 'blog_id' ),
			),

		);
	}


	public function plugins_loaded()
	{
		$map = get_site_option( GNETWORK_REDIRECT_MAP, false );
		if ( $map === false )
			return;

		$req = self::getRequestURI();

		if ( array_key_exists( $req, $map ) )
			self::redirect( get_permalink( $map[$req] ), 301 );
	}

//
// 	$redirect_data = get_option( GNETWORK_REDIRECT_MAP, false );
// 	if ( $redirect_data === false ) return;
//
// 	$requested_uri = $_SERVER['REQUEST_URI'];
// 	$where = substr( $requested_uri, 0, ( strpos( $requested_uri, '?' ) == false ? strlen( $requested_uri ) : strpos( $requested_uri, '?' ) ) );
//
// 	if ( '/New/Article.php' == $where ) {
// 		$vals = gnetwork_parseQueryString( str_replace( '/New/Article.php?', '', $requested_uri ) );
// 		if ( array_key_exists( 'ID', $vals ) ) {
// 			if ( array_key_exists( $vals['ID'], $redirect_data ) ) {
// 				if ( 1 == $redirect_data[$vals['ID']]['w'] )
// 					gnetwork_redirect_wp_redirect( 'http://news.'.gnetwork_REDIRECT_BASE_URL.'/?p='.$redirect_data[$vals['ID']]['n'], 301 );
// 				else if ( 1 == $redirect_data[$vals['ID']]['p'] )
// 					gnetwork_redirect_wp_redirect( 'http://photo.'.gnetwork_REDIRECT_BASE_URL.'/?p='.$redirect_data[$vals['ID']]['n'], 301 );
// 				else
// 					gnetwork_redirect_wp_redirect( get_permalink( $redirect_data[$vals['ID']]['n'] ), 301 );
// 			} else return;
// 		} else if ( array_key_exists( 'SubjectID', $vals ) && 67 == $vals['SubjectID'] ) {
// 			gnetwork_redirect_wp_redirect( 'http://news.'.gnetwork_REDIRECT_BASE_URL, 301 );
// 		} else { gnetwork_redirect_wp_redirect( 'http://'.gnetwork_REDIRECT_BASE_URL ); }
// 	}
//
//
//
// function gnetwork_parseQueryString($str) {
//     $op = array();
//     $pairs = explode("&", $str);
//     foreach ($pairs as $pair) {
//         list($k, $v) = array_map("urldecode", explode("=", $pair));
//         $op[$k] = $v;
//     }
//     return $op;
// }
//
// function gnetwork_redirect_wp_redirect($location, $status = 302) {
// 	header("Location: $location", true, $status);
// 	exit();
// }

	public function init()
	{
		$redirect = get_option( 'gnetwork_redirect', false );
		if ( $redirect ) {
			if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) { // admin
				return;
			} else if ( $_SERVER['SERVER_NAME'] !== ( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) ) ) {
				return;
			} else if ( false === $this->whitelist() ) { // special wp pages
				wp_redirect( $redirect.$_SERVER['REQUEST_URI'], 307 );
				die();
			}
		}
	}

	function admin_init()
	{
		register_setting( 'reading',
			'gnetwork_redirect',
			array( & $this, 'settings_sanitize' ) );

		add_settings_field( 'gnetwork_redirect',
			__( 'Site Redirect to', GNETWORK_TEXTDOMAIN ),
			array( & $this, 'field_callback' ),
			'reading',
			'default' );
	}

	public function default_options()
	{
		return '';
	}

	function field_callback()
	{
		?><input type="text" class="regular-text ltr" id="gnetwork_redirect" name="gnetwork_redirect" placeholder="http://example.com" value="<?php echo esc_attr( get_option( 'gnetwork_redirect', '' ) ); ?>" />
		<br /><span class="description"> <?php esc_html_e( 'The site will redirect to this URL. Leave empty to disable.', GNETWORK_TEXTDOMAIN ); ?></span> <?php
	}

	function settings_sanitize( $input, $defaults = NULL )
	{
		return untrailingslashit( esc_url( $input ) );
	}

	function whitelist( $request_uri = null )
	{
		if ( is_null( $request_uri ) )
			$request_uri = $_SERVER['REQUEST_URI'];

		return gNetworkUtilities::strpos_arr( array(
			'wp-cron.php',
			'wp-mail.php',
			'wp-login.php',
			'wp-signup.php',
			'wp-activate.php',
			'wp-trackback.php',
			'wp-links-opml.php',
			'xmlrpc.php',
			'wp-admin',
		), $request_uri );
	}


	// DRAFT : get list from gnetwork_custom
	// https://gist.github.com/danielbachhuber/3258825
	// Quick way to handle redirects for old pages
	function init_redirects()
	{
		$mea_redirects = array(
				// Enter your old URI => new URI
				'/redundant_digital_control.html' => '/redundant-digital-control/',
			);

		foreach( $mea_redirects as $old_uri => $new_uri ) {
			if ( false !== strpos( $_SERVER['REQUEST_URI'], $old_uri ) ) {
				wp_safe_redirect( home_url( $new_uri ), 301 );
				exit;
			}
		}
	}
}

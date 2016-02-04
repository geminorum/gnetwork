<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkUtilities extends gNetworkBaseCore
{

	public static function creditsBadge()
	{
		return '<a href="http://geminorum.ir" title="it\'s a geminorum project"><img src="'
			.GNETWORK_URL.'assets/images/itsageminorumproject-lightgrey.svg" alt="" /></a>';
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache()
	{
		global $gNetworkMustache;

		if ( ! empty( $gNetworkMustache ) )
			return $gNetworkMustache;

		$gNetworkMustache = new Mustache_Engine(array(
			'template_class_prefix'  => '__MyTemplates_',
			'cache'                  => GNETWORK_DIR.'assets/views/cache', // get_temp_dir().'mustache',
			'cache_file_mode'        => FS_CHMOD_FILE,
			'cache_lambda_templates' => TRUE,
			'loader'                 => new Mustache_Loader_FilesystemLoader( GNETWORK_DIR.'assets/views' ),
			'partials_loader'        => new Mustache_Loader_FilesystemLoader( GNETWORK_DIR.'assets/views/partials' ),
			// 'logger'                 => new Mustache_Logger_StreamLogger('php://stderr'),
			// 'strict_callables'       => TRUE,
			// 'pragmas'                => [Mustache_Engine::PRAGMA_FILTERS],
			// 'helpers' => array(
			// 	'i18n' => function( $text ){
			// 		// do something translatey here...
			// 	},
			// ),
			'escape' => function( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		));

		return $gNetworkMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	public static function renderMustache( $part, $data = array(), $echo = TRUE )
	{
		$mustache = self::getMustache();
		$template = $mustache->loadTemplate( $part );
		$html     = $template->render( $data );

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public static function join_items( $items )
	{
		return
			_x( '&rdquo;', 'Utilities Module: Join Items Helper', GNETWORK_TEXTDOMAIN )
			.join( _x( '&ldquo; and &rdquo;', 'Utilities Module: Join Items Helper', GNETWORK_TEXTDOMAIN ),
				array_filter( array_merge( array(
					join( _x( '&ldquo;, &rdquo;', 'Utilities Module: Join Items Helper', GNETWORK_TEXTDOMAIN ),
					array_slice( $items, 0, -1 ) ) ),
					array_slice( $items, -1 ) ) ) )
			._x( '&ldquo;', 'Utilities Module: Join Items Helper', GNETWORK_TEXTDOMAIN ).'.';
	}

	public static function getLayout( $layout_name, $require_once = FALSE, $no_cache = FALSE )
	{
		// FIXME: must check if it's not admin!

		$layout = locate_template( $layout_name );

		if ( ! $layout )
			if ( file_exists( WP_CONTENT_DIR.'/'.$layout_name.'.php' ) )
				$layout = WP_CONTENT_DIR.'/'.$layout_name.'.php';

		if ( ! $layout )
			$layout = GNETWORK_DIR.'assets/layouts/'.$layout_name.'.php';

		if ( $no_cache )
			__donot_cache_page();

		if ( $require_once && $layout )
			require_once( $layout );
		else
			return $layout;
	}

	// using caps instead of roles
	public static function getUserRoles( $cap = NULL, $none_title = NULL, $none_value = NULL )
	{
		$caps = array(
			'edit_theme_options'   => _x( 'Administrators', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_others_posts'    => _x( 'Editors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_published_posts' => _x( 'Authors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_posts'           => _x( 'Contributors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'read'                 => _x( 'Subscribers', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
		);

		if ( is_multisite() ) {
			$caps = array(
				'manage_network' => _x( 'Super Admins', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			) + $caps + array(
				'logged_in_user' => _x( 'Network Users', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			);
		}

		if ( is_null( $none_title ) )
			$none_title = _x( '&mdash; No One &mdash;', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN );

		if ( is_null( $none_value ) )
			$none_value = 'none';

		if ( $none_title )
			$caps[$none_value] = $none_title;

		if ( is_null( $cap ) )
			return $caps;
		else
			return $caps[$cap];
	}

	// override to use plugin version
	public static function linkStyleSheet( $url, $version = GNETWORK_VERSION, $media = 'all' )
	{
		parent::linkStyleSheet( $url, $version, $media );
	}

	// override to use plugin version
	public static function customStyleSheet( $css, $link = TRUE, $version = GNETWORK_VERSION )
	{
		parent::customStyleSheet( $css, $link, $version );
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( 'gnetwork_tinymce_strings', array() );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.gnetwork", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	// for other plugins too!
	public static function githubREADME( $repo = 'geminorum/gnetwork', $wrap = TRUE )
	{
		global $gNetwork;

		if ( isset( $gNetwork->code ) ) {
			echo '<div class="gnetwork-overview-wrap">';
				echo $gNetwork->code->shortcode_github_readme( array(
					'context' => 'overview',
					'repo'    => $repo,
				) );
			echo '</div>';
		}
	}

	// FIXME: WTF ?!
	// http://www.webdesignerdepot.com/2012/08/wordpress-filesystem-api-the-right-way-to-operate-with-local-files/
	//http://ottopress.com/2011/tutorial-using-the-wp_filesystem/

	/**
	 * Initialize Filesystem object
	 *
	 * @param str $form_url - URL of the page to display request form
	 * @param str $method - connection method
	 * @param str $context - destination folder
	 * @param array $fields - fileds of $_POST array that should be preserved between screens
	 * @return bool/str - false on failure, stored text on success
	 **/
	public static function initWPFS( $form_url, $method, $context, $fields = NULL )
	{
		global $wp_filesystem;

		// first attempt to get credentials
		if ( FALSE === ( $creds = request_filesystem_credentials( $form_url, $method, FALSE, $context, $fields ) ) )
			// if we comes here - we don't have credentials so the request for them is displaying no need for further processing
			return FALSE;

		// now we got some credentials - try to use them
		if ( ! WP_Filesystem( $creds ) ) {

			// incorrect connection data - ask for credentials again, now with error message
			request_filesystem_credentials( $form_url, $method, TRUE, $context );
			return FALSE;
		}

		// filesystem object successfully initiated
		return TRUE;
	}

	/**
	 * Perform writing into file
	 *
	 * @param str $form_url - URL of the page to display request form
	 * @return bool/str - false on failure, stored text on success
	 **/
	public static function writeWPFS( $form_url )
	{
		global $wp_filesystem;

		$args = self::atts( array(
			'form_url' => '',
			'referer'  => 'filesystem_demo_screen',
			'content'  => sanitize_text_field( $_POST['demotext'] ),
			'method'   => '', // leave this empty to perform test for 'direct' writing
			'context'  => WP_CONTENT_DIR.'/gnetwork', // target folder
			'filename' => 'test.txt',
		), $atts );

		check_admin_referer( 'filesystem_demo_screen' );

		$demotext    = sanitize_text_field( $_POST['demotext'] ); // sanitize the input
		$form_fields = array( 'demotext' ); // fields that should be preserved across screens
		$method      = ''; // leave this empty to perform test for 'direct' writing
		$context     = WP_PLUGIN_DIR.'/filesystem-demo'; // target folder
		$form_url    = wp_nonce_url( $form_url, 'filesystem_demo_screen' ); // page url with nonce value

		if ( ! self::initWPFS( $form_url, $method, $context, $form_fields ) )
			return FALSE; // stop further processign when request form is displaying

		// now $wp_filesystem could be used
		// get correct target file first
		$target_dir  = $wp_filesystem->find_folder( $context );
		$target_file = trailingslashit( $target_dir ).'test.txt';

		// write into file
		if ( ! $wp_filesystem->put_contents( $target_file, $demotext, FS_CHMOD_FILE ) )
			return new WP_Error( 'writing_error', 'Error when writing file' ); // return error object

		return $demotext;
	}

	/**
	 * Read text from file
	 *
	 * @param str $form_url - URL of the page where request form will be displayed
	 * @return bool/str - false on failure, stored text on success
	 **/
	public static function readWPFS( $form_url )
	{
		global $wp_filesystem;

		$demotext = '';

		$form_url = wp_nonce_url( $form_url, 'filesystem_demo_screen' );
		$method   = ''; // leave this empty to perform test for 'direct' writing
		$context  = WP_PLUGIN_DIR.'/filesystem-demo'; // target folder

		if ( ! self::initWPFS( $form_url, $method, $context ) )
			return FALSE; // stop further processing when request forms displaying

		// now $wp_filesystem could be used
		// get correct target file first
		$target_dir  = $wp_filesystem->find_folder( $context );
		$target_file = trailingslashit( $target_dir ).'test.txt';

		// read the file
		if ( $wp_filesystem->exists( $target_file ) ) { // check for existence

			$demotext = $wp_filesystem->get_contents( $target_file );
			if ( ! $demotext )
				return new WP_Error( 'reading_error', 'Error when reading file' ); // return error object
		}

		return $demotext;
	}
}

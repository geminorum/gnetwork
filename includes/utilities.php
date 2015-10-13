<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

if ( ! class_exists( 'gNU' ) )
	class_alias( 'gNetworkUtilities', 'gNU' );

class gNetworkUtilities
{

	public static function headerNav( $uri = '', $active = '', $subs = array(), $prefix = 'nav-tab-', $tag = 'h2' )
	{
		if ( ! count( $subs ) )
			return;

		$html = '';

		foreach ( $subs as $slug => $page )
			$html .= self::html( 'a', array(
				'class' => 'nav-tab '.$prefix.$slug.( $slug == $active ? ' nav-tab-active' : '' ),
				'href'  => add_query_arg( 'sub', $slug, $uri ),
			), $page );

		echo self::html( $tag, array(
			'class' => 'nav-tab-wrapper',
		), $html );
	}

	// DEPRECATED : use gNetworkUtilities::headerTabs()
	public static function tabNav( $tabs, $active = 'manual', $prefix = 'nav-tab-', $tag = 'h2' )
	{
		self::headerTabs( $tabs, $active, $prefix, $tag );
	}

	public static function headerTabs( $tabs, $active = 'manual', $prefix = 'nav-tab-', $tag = 'h2' )
	{
		if ( ! count( $tabs ) )
			return;

		$html = '';

		foreach ( $tabs as $tab => $title )
			$html .= self::html( 'a', array(
				'class'    => 'gnetwork-nav-tab nav-tab '.$prefix.$tab.( $tab == $active ? ' nav-tab-active' : '' ),
				'href'     => '#',
				'data-tab' => $tab,
				'rel'      => $tab, // back comp
			), $title );

		echo self::html( $tag, array(
			'class' => 'nav-tab-wrapper',
		), $html );
	}

	public static function stat( $format = NULL )
	{
		if ( is_null( $format ) )
			$format = __( '%d queries in %.3f seconds, using %.2fMB memory.', GNETWORK_TEXTDOMAIN );

		return sprintf( $format,
			get_num_queries(),
			self::timer_stop( FALSE, 3 ),
			memory_get_peak_usage() / 1024 / 1024
		);
	}

	// WP Core function without number_format_i18n
	public static function timer_stop( $echo = FALSE, $precision = 3 )
	{
		global $timestart;

		$html = number_format( ( microtime( TRUE ) - $timestart ), $precision );

		if ( $echo )
			echo $html;

		return $html;
	}

	public static function currentURL( $trailingslashit = FALSE )
	{
		global $wp;

		$request = $wp->request ? add_query_arg( array(), $wp->request ) : add_query_arg( array() );
		$current = home_url( $request );

		if ( $trailingslashit )
			return trailingslashit( $current );

		return $current;
	}

	public static function registerURL( $register = FALSE )
	{
		if ( function_exists( 'buddypress' ) ) {
			if ( bp_get_signup_allowed() )
				return bp_get_signup_page();

		} else if ( get_option( 'users_can_register' ) ) {

			if ( is_multisite() )
				return apply_filters( 'wp_signup_location', network_site_url( 'wp-signup.php' ) );
			else
				return wp_registration_url();

		} else if ( 'site' == $register ) {
			return  site_url( '/' );
		}

		return $register;
	}

	// shows all the "filters" currently attached to a hook
	public static function filters( $hook )
	{
		global $wp_filter;

		if ( ! isset( $wp_filter[$hook] ) )
			return;

		self::dump( $wp_filter[$hook] );
	}

	public static function join_items( $items )
	{
		return
			__( '&rdquo;', GNETWORK_TEXTDOMAIN )
			.join( __( '&ldquo; and &rdquo;', GNETWORK_TEXTDOMAIN ),
				array_filter( array_merge( array(
					join( __( '&ldquo;, &rdquo;', GNETWORK_TEXTDOMAIN ),
					array_slice( $items, 0, -1 ) ) ),
					array_slice( $items, -1 ) ) ) )
			.__( '&ldquo;', GNETWORK_TEXTDOMAIN ).'.';
	}

	public static function getDomain( $string )
	{
		// FIXME: strip all the path
		// SEE: http://stackoverflow.com/questions/569137/how-to-get-domain-name-from-url

		if ( FALSE !== strpos( $string, '.' ) ) {
			$domain = explode( '.', $string );
			$domain = $domain[0];
		}

		return strtolower( $domain );
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

	public static function strpos_arr( $haystack, $needle )
	{
		if ( ! is_array( $haystack ) )
			$haystack = array( $haystack );

		foreach ( $haystack as $key => $what )
			if ( FALSE !== ( $pos = strpos( $what, $needle ) ) )
				return $pos; // must return $key / FIXME: but what about zero?!

		return FALSE;
	}

	public static function isDebug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::isDev() )
			return TRUE;

		return FALSE;
	}

	public static function isDev()
	{
		if ( defined( 'WP_STAGE' )
			&& 'development' == constant( 'WP_STAGE' ) )
				return TRUE;

		return FALSE;
	}

	public static function isFlush()
	{
		if ( isset( $_GET['flush'] ) )
			return did_action( 'init' ) && current_user_can( 'publish_posts' );

		return FALSE;
	}

	public static function notice( $notice, $class = 'updated fade', $echo = TRUE )
	{
		$html = sprintf( '<div id="message" class="%s notice is-dismissible"><p>%s</p></div>', $class, $notice );
		if ( ! $echo )
			return $html;
		echo $html;
	}

	public static function dropdown( $list, $name, $prop = FALSE, $selected = 0, $none = FALSE, $none_val = 0, $obj = FALSE )
	{
		$html = '<select name="'.$name.'" id="'.$name.'">';
		if ( $none )
			$html .= '<option value="'.$none_val.'" '.selected( $selected, $none_val, FALSE ).'>'.esc_html( $none ).'</option>';
		foreach ( $list as $key => $item ) {
			$html .= '<option value="'.$key.'" '.selected( $selected, $key, FALSE ).'>'
				.esc_html( ( $prop ? ( $obj ? $item->{$prop} : $item[$prop] ) : $item ) ).'</option>';
		}
		return $html.'</select>';
	}

	public static function same_key_array( $old )
	{
		$new = array();
		foreach ( $old as $key => $value )
			$new[$value] = $value;
		return $new;
	}

	// returns array of the keys if options values are TRUE
	public static function getKeys( $options = array() )
	{
		$keys = array();

		foreach ( (array) $options as $support => $enabled )
			if ( $enabled )
				$keys[] = $support;

		return $keys;
	}

	// NOTE: like core but without filter and fallback
	public static function sanitizeHTMLClass( $class )
	{
		// strip out any % encoded octets
		$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $class );

		// limit to A-Z,a-z,0-9,_,-
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );

		return trim( $sanitized );
	}

	private static function _tag_open( $tag, $atts, $content = TRUE )
	{
		$html = '<'.$tag;

		foreach ( $atts as $key => $att ) {

			$sanitized = FALSE;

			if ( is_array( $att ) && count( $att ) ) {

				if ( 'data' == $key ) {

					foreach ( $att as $data_key => $data_val ) {

						if ( is_array( $data_val ) )
							$html .= ' data-'.$data_key.'=\''.wp_json_encode( $data_val ).'\'';

						else if ( FALSE === $data_val )
							continue;

						else
							$html .= ' data-'.$data_key.'="'.esc_attr( $data_val ).'"';
					}

					continue;

				} else if ( 'class' == $key ) {
					$att = implode( ' ', array_unique( array_filter( $att, array( __CLASS__, 'sanitizeHTMLClass' ) ) ) );

				} else {
					$att = implode( ' ', array_unique( array_filter( $att, 'trim' ) ) );
				}

				$sanitized = TRUE;
			}

			if ( 'selected' == $key )
				$att = ( $att ? 'selected' : FALSE );

			if ( 'checked' == $key )
				$att = ( $att ? 'checked' : FALSE );

			if ( 'readonly' == $key )
				$att = ( $att ? 'readonly' : FALSE );

			if ( 'disabled' == $key )
				$att = ( $att ? 'disabled' : FALSE );

			if ( FALSE === $att )
				continue;

			if ( 'class' == $key && ! $sanitized )
				$att = implode( ' ', array_unique( array_filter( explode( ' ', $att ), array( __CLASS__, 'sanitizeHTMLClass' ) ) ) );

			else if ( 'class' == $key )
				$att = $att;

			else if ( 'href' == $key && '#' != $att )
				$att = esc_url( $att );

			else if ( 'src' == $key )
				$att = esc_url( $att );

			else
				$att = esc_attr( $att );

			$html .= ' '.$key.'="'.trim( $att ).'"';
		}

		if ( FALSE === $content )
			return $html.' />';

		return $html.'>';
	}

	public static function html( $tag, $atts = array(), $content = FALSE, $sep = "\n" )
	{
		if ( is_array( $atts ) )
			$html = self::_tag_open( $tag, $atts, $content );
		else
			return '<'.$tag.'>'.$atts.'</'.$tag.'>'.$sep;

		if ( FALSE === $content )
			return $html.$sep;

		if ( is_null( $content ) )
			return $html.'</'.$tag.'>'.$sep;

		return $html.$content.'</'.$tag.'>'.$sep;
	}

	// http://stackoverflow.com/a/4994188
	public static function esc_filename( $path )
	{
		// everything to lower and no spaces begin or end
		$path = strtolower( trim( $path ) );

		// adding - for spaces and union characters
		$path = str_replace( array( ' ', '&', '\r\n', '\n', '+', ',' ), '-', $path );

		// delete and replace rest of special chars
		$path = preg_replace( array( '/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/' ), array( '', '-', '' ), $path );

		return $path;
	}

	// for useing with $('form').serializeArray();
	// http://api.jquery.com/serializeArray/
	public static function parseJSArray( $array )
	{
		$parsed = array();
		foreach ( $array as $part )
			$parsed[$part['name']] = $part['value'];
		return $parsed;
	}

	public static function dump( $var, $htmlSafe = TRUE )
	{
		defined( 'GPERSIANDATE_SKIP' ) or define( 'GPERSIANDATE_SKIP', TRUE );
		$result = var_export( $var, TRUE );
		echo '<pre dir="ltr" style="text-align:left;direction:ltr;">'.( $htmlSafe ? htmlspecialchars( $result ) : $result).'</pre>';
	}

	public static function log( $data, $table = FALSE )
	{
		$func = $table ? 'table' : 'log';

		if ( is_array( $data ) || is_object( $data ) )
			echo '<script>console.'.$func.'('.wp_json_encode($data).');</script>';
		else
			echo '<script>console.'.$func.'('.$data.');</script>';
	}

	public static function trace( $old = TRUE )
	{
		// https://gist.github.com/eddieajau/2651181
		if ( $old ) {
			foreach ( debug_backtrace() as $trace )
				echo sprintf( "\n%s:%s %s::%s", $trace['file'], $trace['line'], $trace['class'], $trace['function'] );
			die();
		}

		// http://stackoverflow.com/a/7039409
		$e = new Exception;
		self::dump( $e->getTraceAsString() );
		die();
	}

	// http://stackoverflow.com/a/13272939
	public static function size( $var )
	{
		$start_memory = memory_get_usage();
		$var = unserialize( serialize( $var ) );
		return memory_get_usage() - $start_memory - PHP_INT_SIZE * 8;
	}

	// http://wordpress.mfields.org/2011/rekey-an-indexed-array-of-post-objects-by-post-id/
	public static function reKey( $list, $key )
	{
		if ( ! empty( $list ) ) {
			$ids  = wp_list_pluck( $list, $key );
			$list = array_combine( $ids, $list );
		}

		return $list;
	}

	public static function getPostTypes()
	{
		$registered = get_post_types( array(
			'_builtin' => FALSE,
			'public'   => TRUE,
		), 'objects' );

		$post_types = array(
			'post' => __( 'Posts' ),
			'page' => __( 'Pages' ),
		);

		foreach ( $registered as $post_type => $args )
			$post_types[$post_type] = $args->label;

		return $post_types;
	}

	public static function getUsers( $all_fields = FALSE, $network = FALSE )
	{
		$users = get_users( array(
			'blog_id' => ( $network ? '' : $GLOBALS['blog_id'] ),
			'orderby' => 'display_name',
			'fields'  => ( $all_fields ? 'all_with_meta' : 'all' ),
		) );

		return self::reKey( $users, 'ID' );
	}

	public static function getUserRoleList( $object = FALSE )
	{
		$roles = $object ? new stdClass : array();

		foreach ( get_editable_roles() as $role_name => $role )

			if ( $object )
				$roles->{$role_name} = translate_user_role( $role['name'] );

			else
				$roles[$role_name] = translate_user_role( $role['name'] );

		return $roles;
	}

	// NOTE: this actually use caps instead of roles
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

	public static function entities( $html )
	{
		return trim( htmlspecialchars( $html, ENT_QUOTES, get_option( 'blog_charset' ) ) );
	}

	public static function headers( $array )
	{
		foreach ( $array as $h => $k )
			header( "{$h}: {$k}", TRUE );
	}

	public static function IP()
	{
		if ( getenv( 'HTTP_CLIENT_IP' ) )
			return getenv( 'HTTP_CLIENT_IP' );

		if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
			return getenv( 'HTTP_X_FORWARDED_FOR' );

		if ( getenv( 'HTTP_X_FORWARDED' ) )
			return getenv( 'HTTP_X_FORWARDED' );

		if ( getenv( 'HTTP_FORWARDED_FOR' ) )
			return getenv( 'HTTP_FORWARDED_FOR' );

		if ( getenv( 'HTTP_FORWARDED' ) )
			return getenv( 'HTTP_FORWARDED' );

		return $_SERVER['REMOTE_ADDR'];
	}

	public static function range( $start, $end, $step = 1, $format = TRUE )
	{
		$array = array();
		foreach ( range( $start, $end, $step ) as $number )
			$array[$number] = $format ? number_format_i18n( $number ) : $number;
		return $array;
	}

	public static function linkStyleSheet( $url, $version = GNETWORK_VERSION, $media = 'all' )
	{
		echo "\t".self::html( 'link', array(
			'rel'   => 'stylesheet',
			'href'  => add_query_arg( 'ver', $version, $url ),
			'type'  => 'text/css',
			'media' => $media,
		) )."\n";
	}

	public static function customStyleSheet( $css, $link = TRUE )
	{
		$url = FALSE;

		if ( file_exists( get_stylesheet_directory().'/'.$css ) ) {
			$url = get_stylesheet_directory_uri().'/'.$css;
		} else if ( file_exists( get_template_directory().'/'.$css ) ) {
			$url = get_template_directory_uri().'/'.$css;
		} else if ( file_exists( WP_CONTENT_DIR.'/'.$css ) ) {
			$url = WP_CONTENT_URL.'/'.$css;
		}

		if ( ! $url || ! $link )
			return $link;

		self::linkStyleSheet( $url );
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( 'gnetwork_tinymce_strings', array() );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.gnetwork", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	public static function superAdminOnly()
	{
		if ( ! is_super_admin() )
			self::cheatin();
	}

	public static function cheatin( $message )
	{
		if ( is_null( $message) )
			$message = __( 'Cheatin&#8217; uh?' );

		wp_die( $message, 403 );
	}

	public static function isCLI()
	{
		return defined( 'WP_CLI' ) and WP_CLI;
	}

	// FIXME: WTF: not wrapping the child table!!
	// FIXME: DRAFT: needs styling
	// HELPER:
	public static function tableSideWrap( $array, $title = FALSE )
	{
		echo '<table class="w1idefat f1ixed helper-table-side">';
			if ( $title )
				echo '<thead><tr><th>'.$title.'</th></tr></thead>';
			echo '<tbody>';
			self::tableSide( $array );
		echo '</tbody></table>';
	}

	public static function tableSide( $array )
	{
		echo '<table style="direction:ltr;border: 1px solid #ccc;width:100%;border-spacing:0;">';

		if ( count( $array ) ) {

			foreach ( $array as $key => $val ) {

				echo '<tr class="-row">';

				if ( is_string( $key ) ) {
					echo '<td class="-key" style="padding:5px 5px;vertical-align:top;text-align:right;"><strong>'.$key;
						echo '</strong><br /><small style="color:gray;">'.gettype( $val ).'</small>';
					echo '</td>';
				}

				if ( is_array( $val ) || is_object( $val ) ) {
					echo '<td class="-val" style="vertical-align:top;">';
					self::tableSide( $val );
				} else if ( ! empty( $val ) ){
					echo '<td class="-val" style="padding:4px 2px;vertical-align:top;"><code>'.$val.'</code>';
				} else {
					echo '<td class="-val" style="padding:4px 2px;vertical-align:top;"><small>empty</small>';
				}

				echo '</td></tr>';
			}

		} else {
			echo '<tr class="-row"><td class="-val" style="padding:4px 2px;vertical-align:top;"><small>empty</small></td></tr>';
		}

		echo '</table>';
	}

	// ORIGINALLY BASED ON: Secure Folder wp-content/uploads v1.2
	// BY: Daniel Satria : http://ruanglaba.com
	// HELPER: puts index.html on given folder and subs
	public static function putIndexHTML( $base, $index )
	{
		copy( $index, $base.'/index.html' );

		if ( $dir = opendir( $base ) )
			while ( FALSE !== ( $file = readdir( $dir ) ) )
				if ( is_dir( $base.'/'.$file ) && $file != '.' && $file != '..' )
					self::putIndexHTML( $base.'/'. $file, $index );

		closedir( $dir );
	}

	// HELPER: puts .htaccess deny from all on a given folder
	public static function putHTAccessDeny( $path, $check_folder = TRUE )
	{
		$content = '<Files ~ ".*\..*">'.PHP_EOL.'order allow,deny'.PHP_EOL.'deny from all'.PHP_EOL.'</Files>';

		return self::filePutContents( '.htaccess', $content, $path, FALSE, $check_folder );
	}

	// HELPER: wrapper for file_get_contents()
	public static function fileGetContents( $filename )
	{
		return file_get_contents( $filename );
	}

	// HELPER: wrapper for file_put_contents()
	public static function filePutContents( $filename, $contents, $path = NULL, $append = TRUE, $check_folder = FALSE )
	{
		$dir = FALSE;

		if ( is_null( $path ) ) {
			$dir = WP_CONTENT_DIR; // FIXME: using get_temp_dir() ?!

		} else if ( $check_folder ) {
			$dir = wp_mkdir_p( $path );
			if ( TRUE === $dir )
				$dir = $path;

		} else if ( wp_is_writable( $path ) ) {
			$dir = $path;
		}

		if ( ! $dir )
			return $dir;

		if ( $append )
			return file_put_contents( path_join( $dir, $filename ), $contents.PHP_EOL, FILE_APPEND );

		return file_put_contents( path_join( $dir, $filename ), $contents.PHP_EOL );
	}

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

		$args = gNetworkModuleCore::atts( array(
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

	// from gMemberHelper
	public static function getCurrentSiteBlogID()
	{
		if ( ! is_multisite() )
			return get_current_blog_id();

		global $current_site;
		return absint( $current_site->blog_id );
	}

	public static function elog( $data )
	{
		error_log( print_r( compact( 'data' ), TRUE ) );
	}

	// USE: gNetworkUtilities::callStack( debug_backtrace() );
	// http://stackoverflow.com/a/8497530
	public static function callStack( $stacktrace )
	{
		print str_repeat( '=', 50 )."\n";
		$i = 1;
		foreach ( $stacktrace as $node ) {
			print "$i. ".basename( $node['file'] ).':'.$node['function'].'('.$node['line'].")\n";
			$i++;
		}
	}

	public static function wrapJS( $script = '', $echo = TRUE )
	{
		if ( $script ) {
			$data = '<script type="text/javascript">'."\n"
				.'/* <![CDATA[ */'."\n"
				.'jQuery(document).ready(function($) {'."\n"
					.$script
				.'});'."\n"
				.'/* ]]> */'."\n"
				.'</script>';

			if ( ! $echo )
				return $data;

			echo $data;
		}

		return '';
	}
}

function gnetwork_log( $data, $table = 0 ) { gNetworkUtilities::log( $data, $table ); }
function gnetwork_dump( $var, $htmlSafe = TRUE ) { gNetworkUtilities::dump( $var, $htmlSafe ); }
function gnetwork_trace( $old = TRUE ) { gNetworkUtilities::trace( $old ); }

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBaseCore
{

	// INTERNAL: used on anything deprecated
	protected static function __dep( $note = '' )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		$trace = debug_backtrace();

		$log = 'DEP: ';

		if ( isset( $trace[1]['object'] ) )
			$log .= get_class( $trace[1]['object'] ).'::';
		else if ( isset( $trace[1]['class'] ) )
			$log .= $trace[1]['class'].'::';

		$log .= $trace[1]['function'].'()';

		if ( isset( $trace[2]['function'] ) ) {
			$log .= '|FROM: ';
			if ( isset( $trace[2]['object'] ) )
				$log .= get_class( $trace[2]['object'] ).'::';
			else if ( isset( $trace[2]['class'] ) )
				$log .= $trace[2]['class'].'::';
			$log .= $trace[2]['function'].'()';
		}

		if ( $note )
			$log .= '|'.$note;

		error_log( $log );
	}

	// TODO: DRAFT: not tested
	// http://stackoverflow.com/a/9934684
	// SEE: http://xdebug.org/docs/install
	protected function __callee()
	{
		return sprintf("callee() called @ %s: %s from %s::%s",
			xdebug_call_file(),
			xdebug_call_line(),
			xdebug_call_class(),
			xdebug_call_function()
		);
	}

	public static function headerNav( $uri = '', $active = '', $subs = array(), $prefix = 'nav-tab-', $tag = 'h3' )
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
			$format = '%d queries in %.3f seconds, using %.2fMB memory.';

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

	public static function strposArray( $haystack, $needle )
	{
		if ( ! is_array( $haystack ) )
			$haystack = array( $haystack );

		foreach ( $haystack as $key => $what )
			if ( FALSE !== ( $pos = strpos( $what, $needle ) ) )
				return $key; // $pos;

		return FALSE; // NOTE: always check for FALSE
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

	public static function isAJAX()
	{
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	public static function isCRON()
	{
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	public static function isCLI()
	{
		return defined( 'WP_CLI' ) && WP_CLI;
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

	// OLD: same_key_array()
	public static function sameKey( $old )
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

	public static function html( $tag, $atts = array(), $content = FALSE, $sep = '' )
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
	public static function escFilename( $path )
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

	public static function console( $data, $table = FALSE )
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

	public static function linkStyleSheet( $url, $version = NULL, $media = 'all' )
	{
		echo "\t".self::html( 'link', array(
			'rel'   => 'stylesheet',
			'href'  => is_null( $version ) ? $url : add_query_arg( 'ver', $version, $url ),
			'type'  => 'text/css',
			'media' => $media,
		) )."\n";
	}

	public static function customStyleSheet( $css, $link = TRUE, $version = NULL )
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

		self::linkStyleSheet( $url, $version );
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

	// FIXME: WTF: not wrapping the child table!!
	// FIXME: DRAFT: needs styling
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
	// puts index.html on given folder and subs
	public static function putIndexHTML( $base, $index )
	{
		copy( $index, $base.'/index.html' );

		if ( $dir = opendir( $base ) )
			while ( FALSE !== ( $file = readdir( $dir ) ) )
				if ( is_dir( $base.'/'.$file ) && $file != '.' && $file != '..' )
					self::putIndexHTML( $base.'/'. $file, $index );

		closedir( $dir );
	}

	// puts .htaccess deny from all on a given folder
	public static function putHTAccessDeny( $path, $check_folder = TRUE )
	{
		$content = '<Files ~ ".*\..*">'.PHP_EOL.'order allow,deny'.PHP_EOL.'deny from all'.PHP_EOL.'</Files>';

		return self::filePutContents( '.htaccess', $content, $path, FALSE, $check_folder );
	}

	// wrapper for file_get_contents()
	public static function fileGetContents( $filename )
	{
		return file_get_contents( $filename );
	}

	// wrapper for file_put_contents()
	public static function filePutContents( $filename, $contents, $path = NULL, $append = TRUE, $check_folder = FALSE )
	{
		$dir = FALSE;

		if ( is_null( $path ) ) {
			$dir = WP_CONTENT_DIR;

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

	// USE: gNetworkBaseCore::callStack( debug_backtrace() );
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

	public static function genRandomKey( $salt )
	{
		$chr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$len = 32;
		$key = '';

		for ( $i = 0; $i < $len; $i++ )
			$key .= $chr[( mt_rand( 0,( strlen( $chr ) - 1 ) ) )];

		return md5( $salt.$key );
	}

	// http://code.tutsplus.com/tutorials/a-look-at-the-wordpress-http-api-a-brief-survey-of-wp_remote_get--wp-32065
	// http://wordpress.stackexchange.com/a/114922
	public static function getJSON( $url, $atts = array() )
	{
		$args = self::atts( array(
			'timeout' => 15,
		), $atts );

		$response = wp_remote_get( $url, $args );

		if ( ! is_wp_error( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return json_decode( wp_remote_retrieve_body( $response ) );
		}

		return FALSE;
	}

	public static function getHTML( $url, $atts = array() )
	{
		$args = self::atts( array(
			'timeout' => 15,
		), $atts );

		$response = wp_remote_get( $url, $args );

		if ( ! is_wp_error( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return wp_remote_retrieve_body( $response );
		}

		return FALSE;
	}

	public static function getSearchLink( $query = FALSE )
	{
		if ( GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( GNETWORK_SEARCH_QUERYID, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return $query ? add_query_arg( 's', urlencode( $query ), get_option( 'home' ) ) : get_option( 'home' );
	}

	// FIXME: add general options for on a network panel
	public static function getSiteUserID( $fallback = FALSE )
	{
		if ( defined( 'GNETWORK_SITE_USER_ID' ) && GNETWORK_SITE_USER_ID )
			return intval( GNETWORK_SITE_USER_ID );

		if ( function_exists( 'gtheme_get_option' ) ) {
			if ( $gtheme_user = gtheme_get_option( 'default_user', 0 ) )
				return intval( $gtheme_user );
		}

		if ( $fallback )
			return intval( get_current_user_id() );

		return 0;
	}

	// will remove trailing forward and backslashes if it exists already before adding
	// a trailing forward slash. This prevents double slashing a string or path.
	// ANCESTOR: trailingslashit()
	public static function trail( $string )
	{
		return self::untrail( $string ).'/';
	}

	// removes trailing forward slashes and backslashes if they exist.
	// ANCESTOR: untrailingslashit()
	public static function untrail( $string )
	{
		return rtrim( $string, '/\\' );
	}

	public static function error( $message )
	{
		return self::notice( $message, 'error fade', FALSE );
	}

	public static function updated( $message )
	{
		return self::notice( $message, 'updated fade', FALSE );
	}

	public static function log( $error = '{NO Error Code}', $data = array(), $wp_error = NULL )
	{
		if ( ! WP_DEBUG_LOG )
			return;

		$log = array_merge( array(
			'error'   => $error,
			'time'    => current_time( 'mysql' ),
			'ip'      => self::IP(),
			'message' => ( is_null( $wp_error ) ? '{NO WP_Error Object}' : $wp_error->get_error_message() ),
		), $data );

		error_log( print_r( $log, TRUE ) );
	}

	// ANCESTOR: shortcode_atts()
	public static function atts( $pairs, $atts )
	{
		$atts = (array) $atts;
		$out  = array();

		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}

		return $out;
	}

	// ANCESTOR: wp_parse_args()
	public static function args( $args, $defaults = '' )
	{
		if ( is_object( $args ) )
			$r = get_object_vars( $args );

		elseif ( is_array( $args ) )
			$r =& $args;

		else
			// wp_parse_str( $args, $r );
			parse_str( $args, $r );

		if ( is_array( $defaults ) )
			return array_merge( $defaults, $r );

		return $r;
	}

	// current user can
	public static function cuc( $cap, $none = TRUE )
	{
		if ( 'none' == $cap || '0' == $cap )
			return $none;

		return current_user_can( $cap );
	}

	public static function redirect( $location = NULL, $status = 302 )
	{
		if ( is_null( $location ) )
			$location = add_query_arg( wp_get_referer() );

		wp_redirect( $location, $status );
		exit();
	}

	public static function redirect_referer( $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, wp_get_referer() );
		else
			$url = add_query_arg( $key, $message, wp_get_referer() );

		self::redirect( $url );
	}
}

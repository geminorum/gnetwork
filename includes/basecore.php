<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBaseCore
{

	// INTERNAL: used on anything deprecated
	protected static function __dep( $note = '', $prefix = 'DEP: ' )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		$trace = debug_backtrace();

		$log = $prefix;

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

	// FIXME: DEPRICATED
	public static function headerTabs( $tabs, $active = 'manual', $prefix = 'nav-tab-', $tag = 'h3' )
	{
		self::__dep( 'tabsList()' );

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

	public static function tabsList( $tabs, $atts = array() )
	{
		if ( ! count( $tabs ) )
			return FALSE;

		$args = self::atts( array(
			'title'  => FALSE,
			'class'  => FALSE,
			'prefix' => 'nav-tab',
			'nav'    => 'h3',
		), $atts );

		$navs = $contents = '';

		foreach ( $tabs as $tab => $tab_atts ) {

			$tab_args = self::atts( array(
				'active'  => FALSE,
				'title'   => $tab,
				'link'    => '#',
				'cb'      => FALSE,
				'content' => '',
			), $tab_atts );

			$navs .= self::html( 'a', array(
				'href'  => $tab_args['link'],
				'class' => $args['prefix'].' -nav'.( $tab_args['active'] ? ' '.$args['prefix'].'-active -active' : '' ),
				'data'  => array(
					'toggle' => 'tab',
					'tab'    => $tab,
				),
			), $tab_args['title'] );

			$content = '';

			if ( $tab_args['cb'] && is_callable( $tab_args['cb'] ) ) {

				ob_start();
					call_user_func_array( $tab_args['cb'], array( $tab, $tab_args, $args ) );
				$content .= ob_get_clean();

			} else if ( $tab_args['content'] ) {
				$content = $tab_args['content'];
			}

			if ( $content )
				$contents .= self::html( 'div', array(
					'class' => $args['prefix'].'-content -content',
					'data'  => array(
						'tab' => $tab,
					),
				), $content );
		}

		if ( isset( $args['title'] ) && $args['title'] )
			echo $args['title'];

		$navs = self::html( $args['nav'], array(
			'class' => $args['prefix'].'-wrapper -wrapper',
		), $navs );

		echo self::html( 'div', array(
			'class' => array(
				'base-tabs-list',
				'-base',
				$args['prefix'].'-base',
				$args['class'],
			),
		), $navs.$contents );

		if ( class_exists( 'gNetworkUtilities' ) )
			gNetworkUtilities::enqueueScript( 'admin.tabs' );
	}

	// WP core function without number_format_i18n
	public static function size_format( $bytes, $decimals = 0 )
	{
		$quant = array(
			'TB' => TB_IN_BYTES,
			'GB' => GB_IN_BYTES,
			'MB' => MB_IN_BYTES,
			'KB' => KB_IN_BYTES,
			'B'  => 1,
		);

		foreach ( $quant as $unit => $mag )
			if ( doubleval( $bytes ) >= $mag )
				return number_format( $bytes / $mag, $decimals ).' '.$unit;

		return FALSE;
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

	// WP core function without number_format_i18n
	public static function timer_stop( $echo = FALSE, $precision = 3 )
	{
		global $timestart;

		$html = number_format( ( microtime( TRUE ) - $timestart ), $precision );

		if ( $echo )
			echo $html;

		return $html;
	}

	// FIXME: test this!
	// @SOURCE: http://stackoverflow.com/a/8891890/4864081
	public static function currentURL( $trailingslashit = FALSE, $forwarded_host = FALSE )
	{
	    $ssl      = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
	    $sp       = strtolower( $_SERVER['SERVER_PROTOCOL'] );
	    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
	    $port     = $_SERVER['SERVER_PORT'];
	    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
	    $host     = ( $forwarded_host && isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : NULL );
	    $host     = isset( $host ) ? $host : $_SERVER['SERVER_NAME'].$port;

		return $protocol.'://'.$host.$_SERVER['REQUEST_URI'];
	}

	public static function currentURL_OLD( $trailingslashit = FALSE )
	{
		global $wp;

		$request = $wp->request ? add_query_arg( array(), $wp->request ) : add_query_arg( array() );
		$current = home_url( $request );

		if ( $trailingslashit )
			return self::trail( $current );

		return $current;
	}

	public static function currentBlog()
	{
		$blog = home_url();

		$blog = str_ireplace( array( 'https://', 'http://' ), '', $blog );
		$blog = str_ireplace( array( '/', '\/' ), '-', $blog );

		return $blog;
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

		return FALSE; // must always check for FALSE
	}

	// deep array_filter()
	public static function filterArray( $input, $callback = NULL )
	{
		foreach ( $input as &$value )
			if ( is_array( $value ) )
				$value = self::filterArray( $value, $callback );

		return $callback ? array_filter( $input, $callback ) : array_filter( $input );
	}

	public static function roundArray( $array, $precision = -3, $mode = PHP_ROUND_HALF_UP )
	{
		$rounded = array();

		foreach( (array) $array as $key => $value )
			$rounded[$key] = round( (float) $value, $precision, $mode );

		return $rounded;
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

	public static function reKey( $list, $key )
	{
		if ( ! empty( $list ) ) {
			$ids  = wp_list_pluck( $list, $key );
			$list = array_combine( $ids, $list );
		}

		return $list;
	}

	public static function sameKey( $old )
	{
		$new = array();

		foreach ( $old as $key => $value )
			$new[$value] = $value;

		return $new;
	}

	public static function getKeys( $options, $if = TRUE )
	{
		$keys = array();

		foreach ( (array) $options as $key => $value )
			if ( $value == $if )
				$keys[] = $key;

		return $keys;
	}

	// like WP core but without filter and fallback
	// ANCESTOR: sanitize_html_class()
	public static function sanitizeHTMLClass( $class )
	{
		// strip out any % encoded octets
		$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $class );

		// limit to A-Z,a-z,0-9,_,-
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );

		return $sanitized;
	}

	// like WP core but without filter
	// ANCESTOR: tag_escape()
	public static function sanitizeHTMLTag( $tag )
	{
		return strtolower( preg_replace('/[^a-zA-Z0-9_:]/', '', $tag ) );
	}

	private static function _tag_open( $tag, $atts, $content = TRUE )
	{
		$html = '<'.$tag;

		foreach ( $atts as $key => $att ) {

			$sanitized = FALSE;

			if ( is_array( $att ) ) {

				if ( ! count( $att ) )
					continue;

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

			else if ( 'src' == $key && FALSE === strpos( $att, 'data:image' ) )
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
		$tag = self::sanitizeHTMLTag( $tag );

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

	public static function kill( $var = FALSE )
	{
		if ( $var )
			self::dump( $var );

		// FIXME: add query/memory/time info

		die();
	}

	public static function devDump( $var )
	{
		if ( self::isDev() )
			self::dump( $var );
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

	public static function isFuncDisabled( $func = NULL )
	{
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		if ( is_null( $func ) )
			return $disabled;

		return in_array( $func, $disabled );
	}

	// http://stackoverflow.com/a/13272939
	public static function size( $var )
	{
		$start_memory = memory_get_usage();
		$var = unserialize( serialize( $var ) );
		return memory_get_usage() - $start_memory - PHP_INT_SIZE * 8;
	}

	public static function getPostTypes( $title_key = 'name' )
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
			$post_types[$post_type] = isset( $args->labels->{$title_key} ) ? $args->labels->{$title_key} : $args->label;

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

	public static function IP( $pad = FALSE )
	{
		$ip = '';

		if ( getenv( 'HTTP_CLIENT_IP' ) )
			$ip = getenv( 'HTTP_CLIENT_IP' );

		else if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_X_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_X_FORWARDED' ) )
			$ip = getenv( 'HTTP_X_FORWARDED' );

		else if ( getenv( 'HTTP_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_FORWARDED' ) )
			$ip = getenv( 'HTTP_FORWARDED' );

		else
			$ip = getenv( 'REMOTE_ADDR' );

		if ( $pad )
			return str_pad( $ip, 15, ' ', STR_PAD_LEFT );

		return $ip;
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
		if ( is_array( $version ) )
			$url = add_query_arg( $version, $url );

		else if ( $version )
			$url = add_query_arg( 'ver', $version, $url );

		echo "\t".self::html( 'link', array(
			'rel' => 'stylesheet',
			'href' => $url,
			'type' => 'text/css',
			'media' => $media,
		) )."\n";
	}

	public static function customStyleSheet( $css, $link = TRUE, $version = NULL )
	{
		$url = FALSE;

		if ( file_exists( get_stylesheet_directory().'/'.$css ) )
			$url = get_stylesheet_directory_uri().'/'.$css;

		else if ( file_exists( get_template_directory().'/'.$css ) )
			$url = get_template_directory_uri().'/'.$css;

		else if ( file_exists( WP_CONTENT_DIR.'/'.$css ) )
			$url = WP_CONTENT_URL.'/'.$css;

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
		if ( is_null( $message ) )
			$message = __( 'Cheatin&#8217; uh?' );

		wp_die( $message, 403 );
	}

	public static function tableList( $columns, $data = array(), $args = array() )
	{
		if ( ! count( $columns ) )
			return FALSE;

		if ( ! $data || ! count( $data ) ) {
			if ( isset( $args['empty'] ) && $args['empty'] )
				echo '<div class="base-table-empty description">'.$args['empty'].'</div>';
			return FALSE;
		}

		if ( isset( $args['title'] ) && $args['title'] )
			echo '<div class="base-table-title">'.$args['title'].'</div>';

		$pagination = isset( $args['pagination'] ) ? $args['pagination'] : array();

		if ( isset( $args['before'] )
			|| ( isset( $args['navigation'] ) && 'before' == $args['navigation'] )
			|| ( isset( $args['search'] ) && 'before' == $args['search'] ) )
				echo '<div class="base-table-actions base-table-list-before">';
		else
			echo '<div>';

		if ( isset( $args['navigation'] ) && 'before' == $args['navigation'] )
			self::tableNavigation( $pagination );

		if ( isset( $args['before'] ) && is_callable( $args['before'] ) )
			call_user_func_array( $args['before'], array( $columns, $data, $args ) );

		echo '</div><table class="widefat fixed base-table-list"><thead><tr>';
			foreach ( $columns as $key => $column ) {

				$tag   = 'th';
				$class = '';

				if ( is_array( $column ) ) {
					$title = isset( $column['title'] ) ? $column['title'] : $key;

					if ( isset( $column['class'] ) )
						$class = esc_attr( $column['class'] );

				} else if ( '_cb' == $key ) {
					$title = '<input type="checkbox" id="cb-select-all-1" class="-cb-all" />';
					$class = ' check-column';
					$tag   = 'td';
				} else {
					$title = $column;
				}

				echo '<'.$tag.' class="-column -column-'.esc_attr( $key ).$class.'">'.$title.'</'.$tag.'>';
			}
		echo '</tr></thead><tbody>';

		$alt = TRUE;
		foreach ( $data as $index => $row ) {

			echo '<tr class="-row -row-'.$index.( $alt ? ' alternate' : '' ).'">';

			foreach ( $columns as $key => $column ) {

				$class = $callback = '';
				$cell = 'td';

				if ( '_cb' == $key ) {
					if ( '_index' == $column )
						$value = $index;
					else if ( is_array( $column ) && isset( $column['value'] ) )
						$value = call_user_func_array( $column['value'], array( NULL, $row, $column, $index ) );
					else if ( is_array( $row ) && isset( $row[$column] ) )
						$value = $row[$column];
					else if ( is_object( $row ) && isset( $row->{$column} ) )
						$value = $row->{$column};
					else
						$value = '';
					$value = '<input type="checkbox" name="_cb[]" value="'.esc_attr( $value ).'" class="-cb" />';
					$class .= ' check-column';
					$cell = 'th';

				} else if ( is_array( $row ) && isset( $row[$key] ) ) {
					$value = $row[$key];

				} else if ( is_object( $row ) && isset( $row->{$key} ) ) {
					$value = $row->{$key};

				} else {
					$value = NULL;
				}

				if ( is_array( $column ) ) {
					if ( isset( $column['class'] ) )
						$class .= ' '.esc_attr( $column['class'] );

					if ( isset( $column['callback'] ) )
						$callback = $column['callback'];
				}

				echo '<'.$cell.' class="-cell -cell-'.$key.$class.'">';

				if ( $callback ){
					echo call_user_func_array( $callback, array( $value, $row, $column, $index ) );

				} else if ( $value ) {
					echo $value;

				} else {
					echo '&nbsp;';
				}

				echo '</'.$cell.'>';
			}

			$alt = ! $alt;

			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '<div class="clear"></div>';

		if ( isset( $args['after'] )
			|| ( isset( $args['navigation'] ) && 'after' == $args['navigation'] )
			|| ( isset( $args['search'] ) && 'after' == $args['search'] ) )
				echo '<div class="base-table-actions base-table-list-after">';
		else
			echo '<div>';

		if ( isset( $args['navigation'] ) && 'after' == $args['navigation'] )
			self::tableNavigation( $pagination );

		// FIXME: add search box

		if ( isset( $args['after'] ) && is_callable( $args['after'] ) )
			call_user_func_array( $args['after'], array( $columns, $data, $args ) );

		echo '</div>';

		return TRUE;
	}

	public static function tableNavigation( $pagination = array() )
	{
		$args = self::atts( array(
			'total'    => 0,
			'pages'    => 0,
			'limit'    => self::limit(),
			'paged'    => self::paged(),
			'next'     => FALSE,
			'previous' => FALSE,
		), $pagination );

		$icons = array(
			'next'     => '<span class="dashicons dashicons-redo"></span>', // &rsaquo;
			'previous' => '<span class="dashicons dashicons-undo"></span>', // &lsaquo;
			'refresh'  => '<span class="dashicons dashicons-image-rotate"></span>',
		);

		echo '<div class="base-table-navigation">';

			echo '<input type="number" class="small-text -paged" name="paged" value="'.$args['paged'].'" />';
			echo '<input type="number" class="small-text -limit" name="limit" value="'.$args['limit'].'" />';

			vprintf( '<span class="-total-pages">%s / %s</span>', array(
				number_format_i18n( $args['total'] ),
				number_format_i18n( $args['pages'] ),
			) );

			vprintf( '<span class="-next-previous">%s %s %s</span>', array(
				( FALSE === $args['previous'] ? '<span class="-previous -span" aria-hidden="true">'.$icons['previous'].'</span>' : self::html( 'a', array(
					'href'  => add_query_arg( 'paged', $args['previous'] ),
					'class' => '-previous -link',
				), $icons['previous'] ) ),
				self::html( 'a', array(
					'href'  => add_query_arg(),
					'class' => '-refresh -link',
				), $icons['refresh'] ),
				( FALSE === $args['next'] ? '<span class="-next -span" aria-hidden="true">'.$icons['next'].'</span>' : self::html( 'a', array(
					'href'  => add_query_arg( 'paged', $args['next'] ),
					'class' => '-next -link',
				), $icons['next'] ) ),
			) );

		echo '</div>';
	}

	public static function limit( $default = 25, $key = 'limit' )
	{
		return intval( ( isset( $_REQUEST[$key] ) ? $_REQUEST[$key] : $default ) );
	}

	public static function paged( $default = 1, $key = 'paged' )
	{
		return intval( ( isset( $_REQUEST[$key] ) ? $_REQUEST[$key] : $default ) );
	}

	public static function listCode( $array, $row = NULL, $first = FALSE )
	{
		if ( count( $array ) ) {
			echo '<ul class="base-list-code">';

			if ( is_null( $row ) )
				$row = '<code title="%2$s">%1$s</code>';

			if ( $first )
				echo '<li>'.$first.'</li>';

			foreach ( $array as $key => $val )
				echo '<li>'.sprintf( $row, $key, $val ).'</li>';

			echo '</ul>';
		}
	}

	public static function tableCode( $array )
	{
		echo '<table class="base-table-code"><tbody>';
		foreach ( $array as $key => $val )
			echo sprintf( '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>', $key, $val );
		echo '</tbody></table>';
	}

	// FIXME: WTF: not wrapping the child table!!
	// FIXME: DRAFT: needs styling
	public static function tableSideWrap( $array, $title = FALSE )
	{
		echo '<table class="w1idefat f1ixed base-table-side-wrap">';
			if ( $title )
				echo '<thead><tr><th>'.$title.'</th></tr></thead>';
			echo '<tbody>';
			self::tableSide( $array );
		echo '</tbody></table>';
	}

	public static function tableSide( $array, $type = TRUE )
	{
		echo '<table class="base-table-side">';

		if ( count( $array ) ) {

			foreach ( $array as $key => $val ) {

				echo '<tr class="-row">';

				if ( is_string( $key ) ) {
					echo '<td class="-key" style=""><strong>'.$key.'</strong>';
						if ( $type ) echo '<br /><small>'.gettype( $val ).'</small>';
					echo '</td>';
				}

				if ( is_array( $val ) || is_object( $val ) ) {
					echo '<td class="-val -table">';
					self::tableSide( $val, $type );
				} else if ( ! empty( $val ) ){
					echo '<td class="-val -not-table"><code>'.$val.'</code>';
				} else {
					echo '<td class="-val -not-table"><small class="-empty">empty</small>';
				}

				echo '</td></tr>';
			}

		} else {
			echo '<tr class="-row"><td class="-val -not-table"><small class="-empty">empty</small></td></tr>';
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

	public static function urlGetContents( $url )
	{
		$handle = curl_init();

		curl_setopt( $handle, CURLOPT_URL, $url );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, TRUE );

		$contents = curl_exec( $handle );

		curl_close( $handle );

		if ( ! $contents )
			return FALSE;

		return $contents;
	}

	// wrapper for file_get_contents()
	public static function fileGetContents( $filename )
	{
		return @file_get_contents( $filename );
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

	// read the last n lines of a file without reading through all of it
	// @SOURCE: http://stackoverflow.com/a/6451391/4864081
	public static function fileGetLastLines( $path, $count, $block_size = 512 )
	{
	    $lines = array();

	    // we will always have a fragment of a non-complete line
	    // keep this in here till we have our next entire line.
	    $leftover = '';

	    $fh = fopen( $path, 'r' );

		// go to the end of the file
	    fseek( $fh, 0, SEEK_END );

		do {

	        // need to know whether we can actually go back
	        $can_read = $block_size; // $block_size in bytes

			if ( ftell( $fh ) < $block_size )
	            $can_read = ftell( $fh );

	        // go back as many bytes as we can
	        // read them to $data and then move the file pointer
	        // back to where we were.
	        fseek( $fh, -$can_read, SEEK_CUR );
	        $data = fread( $fh, $can_read );
	        $data .= $leftover;
	        fseek( $fh, -$can_read, SEEK_CUR );

	        // split lines by \n. Then reverse them,
	        // now the last line is most likely not a complete
	        // line which is why we do not directly add it, but
	        // append it to the data read the next time.
            $split_data = array_reverse( explode( "\n", $data ) );
            $new_lines  = array_slice( $split_data, 0, -1 );
            $lines      = array_merge( $lines, $new_lines );
            $leftover   = $split_data[count( $split_data ) - 1];
	    }

		while ( count( $lines ) < $count && 0 != ftell( $fh ) );

		if ( 0 == ftell( $fh ) )
	        $lines[] = $leftover;

	    fclose( $fh );

		// usually, we will read too many lines, correct that here.
	    return array_slice( $lines, 0, $count );
	}

	// determines the file size without any acrobatics
	// @SOURCE: http://stackoverflow.com/a/6674672/4864081
	public static function fileGetSize( $path, $format = TRUE )
	{
        $fh   = fopen( $path, 'r+' );
        $stat = fstat( $fh );
		fclose( $fh );

        return $format ? self::size_format( $stat['size'] ) : $stat['size'];
	}

	// FIXME: TEST
	// @SOURCE: http://stackoverflow.com/a/11267139/4864081
	public static function dirRemove( $dir )
	{
		foreach ( glob( "{$dir}/*" ) as $file )
			if ( is_dir( $file ) )
				self::dirRemove( $file );
			else
				unlink( $file );

		rmdir( $dir );
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
			$key .= $chr[( wp_rand( 0,( strlen( $chr ) - 1 ) ) )];

		return md5( $salt.$key );
	}

	// @API: https://developers.google.com/chart/infographics/docs/qr_codes
	// @EXAMPLE: https://createqrcode.appspot.com/
	// @SEE: https://github.com/endroid/QrCode
	// @SEE: https://github.com/aferrandini/PHPQRCode
	public static function getGoogleQRCode( $data, $atts = array() )
	{
		$args = self::atts( array(
            'tag'        => TRUE,
            'size'       => 150,
            'encoding'   => 'UTF-8',
            'correction' => 'H', // 'L', 'M', 'Q', 'H'
            'margin'     => 0,
            'url'        => 'https://chart.googleapis.com/chart',
		), $atts );

		$src = add_query_arg( array(
            'cht'  => 'qr',
            'chs'  => $args['size'].'x'.$args['size'],
            'chl'  => urlencode( $data ),
            'chld' => $args['correction'].'|'.$args['margin'],
            'choe' => $args['encoding'],
		), $args['url'] );

		if ( ! $args['tag'] )
			return $src;

		return self::html( 'img', array(
            'src'    => $src,
            'width'  => $args['size'],
            'height' => $args['size'],
            'alt'    => strip_tags( $data ),
		) );
	}

	// http://code.tutsplus.com/tutorials/a-look-at-the-wordpress-http-api-a-brief-survey-of-wp_remote_get--wp-32065
	// http://wordpress.stackexchange.com/a/114922
	public static function getJSON( $url, $atts = array(), $assoc = FALSE )
	{
		$args = self::atts( array(
			'timeout' => 15,
		), $atts );

		$response = wp_remote_get( $url, $args );

		if ( ! is_wp_error( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return json_decode( wp_remote_retrieve_body( $response ), $assoc );
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

	// FIXME: add to filter: 'search_link' / DEPRICATE THIS
	public static function getSearchLink( $query = '', $url = FALSE, $query_id = GNETWORK_SEARCH_QUERYID )
	{
		if ( $url )
			return $query ? add_query_arg( $query_id, urlencode( $query ), $url ) : $url;

		if ( GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( $query_id, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return get_search_link( $query );
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

	public static function log( $error = '[Unknown]', $message = FALSE, $extra = FALSE )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG )
			error_log( self::getLogTime()
				.$error.' '
				.self::IP( TRUE )
				.( $message ? ' :: '.strip_tags( $message ) : '' )
				.( $extra ? ' :: '.$extra : '' )
				."\n", 3, GNETWORK_DEBUG_LOG );
	}

	// EXAMPLE: [03-Feb-2015 21:20:19 UTC]
	public static function getLogTime()
	{
		return '['.gmdate( 'd-M-Y H:i:s e' ).'] ';
	}

	public static function logArray( $error = '[Unknown]', $data = array(), $wp_error = NULL )
	{
		if ( ! WP_DEBUG_LOG )
			return;

		$log = array_merge( array(
			'error'   => $error,
			'time'    => current_time( 'mysql' ),
			'ip'      => self::IP(),
			'message' => ( is_null( $wp_error ) ? '[NO WP_Error Object]' : $wp_error->get_error_message() ),
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

	public static function redirect_login( $location = '', $status = 302 )
	{
		self::redirect( wp_login_url( $location, TRUE ), $status );
	}

	// get an appropriate hostname. varies depending on site configuration.
	// originally from BuddyPress 2.5.0
	public static function getHostName()
	{
		if ( is_multisite() )
			return get_current_site()->domain;

		return preg_replace( '#^https?://#i', '', get_option( 'home' ) );
	}
}

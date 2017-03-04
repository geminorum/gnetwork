<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class HTML extends Base
{

	public static function link( $html, $link = '#', $target_blank = FALSE )
	{
		return self::tag( 'a', array( 'href' => $link, 'class' => '-link', 'target' => ( $target_blank ? '_blank' : FALSE ) ), $html );
	}

	public static function mailto( $email, $title = NULL )
	{
		return '<a class="-mailto" href="mailto:'.trim( $email ).'">'.( $title ? $title : trim( $email ) ).'</a>';
	}

	public static function scroll( $html, $to )
	{
		return '<a class="scroll" href="#'.$to.'">'.$html.'</a>';
	}

	public static function h2( $html, $class = FALSE )
	{
		echo self::tag( 'h2', array( 'class' => $class ), $html );
	}

	public static function h3( $html, $class = FALSE )
	{
		echo self::tag( 'h3', array( 'class' => $class ), $html );
	}

	public static function desc( $html, $block = TRUE, $class = '' )
	{
		if ( $html ) echo $block ? '<p class="description '.$class.'">'.$html.'</p>' : '<span class="description '.$class.'">'.$html.'</span>';
	}

	public static function inputHidden( $name, $value = '' )
	{
		echo '<input type="hidden" name="'.self::escapeAttr( $name ).'" value="'.self::escapeAttr( $value ).'" />';
	}

	public static function joined( $items, $before = '', $after = '', $sep = '|' )
	{
		return count( $items ) ? ( $before.join( $sep, $items ).$after ) : '';
	}

	public static function tag( $tag, $atts = array(), $content = FALSE, $sep = '' )
	{
		$tag = self::sanitizeTag( $tag );

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

	public static function attrClass()
	{
		$classes = array();

		foreach ( func_get_args() as $arg )

			if ( is_array( $arg ) )
				$classes = array_merge( $classes, $arg );

			else if ( $arg )
				$classes = array_merge( $classes, explode( ' ', $arg ) );

		return array_unique( array_filter( $classes, 'trim' ) );
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
							$html .= ' data-'.$data_key.'="'.self::escapeAttr( $data_val ).'"';
					}

					continue;

				} else if ( 'class' == $key ) {
					$att = implode( ' ', array_unique( array_filter( $att, array( __CLASS__, 'sanitizeClass' ) ) ) );

				} else {
					$att = implode( ' ', array_unique( array_filter( $att, 'trim' ) ) );
				}

				$sanitized = TRUE;
			}

			if ( in_array( $key, array( 'selected', 'checked', 'readonly', 'disabled', 'default' ) ) )
				$att = $att ? $key : FALSE;

			if ( FALSE === $att )
				continue;

			if ( 'class' == $key && ! $sanitized )
				$att = implode( ' ', array_unique( array_filter( explode( ' ', $att ), array( __CLASS__, 'sanitizeClass' ) ) ) );

			else if ( 'class' == $key )
				$att = $att;

			else if ( 'href' == $key && '#' != $att )
				$att = self::escapeURL( $att );

			else if ( 'src' == $key && FALSE === strpos( $att, 'data:image' ) )
				$att = self::escapeURL( $att );

			else
				$att = self::escapeAttr( $att );

			$html .= ' '.$key.'="'.trim( $att ).'"';
		}

		if ( FALSE === $content )
			return $html.' />';

		return $html.'>';
	}

	// like WP core but without filter
	// @SOURCE: `esc_attr()`
	public static function escapeAttr( $text )
	{
		$safe_text = wp_check_invalid_utf8( $text );
		$safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );

		return $safe_text;
	}

	public static function escapeURL( $url )
	{
		return esc_url( $url );
	}

	public static function escapeTextarea( $html )
	{
		return htmlspecialchars( $html, ENT_QUOTES, 'UTF-8' );
	}

	// like WP core but without filter and fallback
	// ANCESTOR: sanitize_html_class()
	public static function sanitizeClass( $class )
	{
		// strip out any % encoded octets
		$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $class );

		// limit to A-Z,a-z,0-9,_,-
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );

		return $sanitized;
	}

	// like WP core but without filter
	// ANCESTOR: tag_escape()
	public static function sanitizeTag( $tag )
	{
		// return strtolower( preg_replace( '/[^a-zA-Z0-9_:]/', '', $tag ) );
		return preg_replace( '/[^a-zA-Z0-9_:]/', '', $tag );
	}

	public static function sanitizePhoneNumber( $number )
	{
		return self::escapeURL( 'tel:'.str_replace( array( '(', ')', '-', '.', '|', ' ' ), '', $number ) );
	}

	public static function getAtts( $string, $expecting = array() )
	{
		foreach ( $expecting as $attr => $default ) {

			preg_match( "#".$attr."=\"(.*?)\"#s", $string, $matches );

			if ( isset( $matches[1] ) )
				$expecting[$attr] = trim( $matches[1] );
		}

		return $expecting;
	}

	// DEPRECATED
	public static function dropdown( $list, $name, $prop = FALSE, $selected = 0, $none = FALSE, $none_val = 0, $obj = FALSE )
	{
		$html = '<select name="'.$name.'" id="'.$name.'">';

		if ( $none )
			$html .= '<option value="'.$none_val.'" '.selected( $selected, $none_val, FALSE ).'>'.esc_html( $none ).'</option>';

		foreach ( $list as $key => $item )
			$html .= '<option value="'.$key.'" '.selected( $selected, $key, FALSE ).'>'
				.esc_html( ( $prop ? ( $obj ? $item->{$prop} : $item[$prop] ) : $item ) ).'</option>';

		return $html.'</select>';
	}

	public static function listCode( $array, $row = NULL, $first = FALSE )
	{
		if ( ! $array )
			return;

		echo '<ul class="base-list-code">';

		if ( is_null( $row ) )
			$row = '<code title="%2$s">%1$s</code>';

		if ( $first )
			echo '<li class="-first">'.$first.'</li>';

		foreach ( $array as $key => $val ) {

			if ( is_null( $val ) )
				$val = 'NULL';

			else if ( is_bool( $val ) )
				$val = $val ? 'TRUE' : 'FALSE';

			else if ( is_array( $val ) || is_object( $val ) )
				$val = json_encode( $val );

			echo '<li>';
				printf( $row, $key, $val );
			echo '</li>';
		}

		echo '</ul>';
	}

	public static function tableCode( $array, $reverse = FALSE, $caption = FALSE )
	{
		if ( ! $array )
			return;

		if ( $reverse )
			$row = '<tr><td class="-val"><code>%1$s</code></td><td class="-var">%2$s</td></tr>';
		else
			$row = '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>';

		echo '<table class="base-table-code'.( $reverse ? ' -reverse' : '' ).'">';

		if ( $caption )
			echo '<caption>'.$caption.'</caption>';

		echo '<tbody>';

		foreach ( (array) $array as $key => $val ) {

			if ( is_null( $val ) )
				$val = 'NULL';

			else if ( is_bool( $val ) )
				$val = $val ? 'TRUE' : 'FALSE';

			else if ( is_array( $val ) || is_object( $val ) )
				$val = json_encode( $val );

			printf( $row, $key, $val );
		}

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
				} else if ( is_null( $val ) ){
					echo '<td class="-val -not-table"><code>NULL</code>';
				} else if ( is_bool( $val ) ){
					echo '<td class="-val -not-table"><code>'.( $val ? 'TRUE' : 'FALSE' ).'</code>';
				} else if ( ! empty( $val ) ){
					echo '<td class="-val -not-table"><code>'.$val.'</code>';
				} else {
					echo '<td class="-val -not-table"><small class="-empty">EMPTY</small>';
				}

				echo '</td></tr>';
			}

		} else {
			echo '<tr class="-row"><td class="-val -not-table"><small class="-empty">EMPTY</small></td></tr>';
		}

		echo '</table>';
	}

	public static function linkStyleSheet( $url, $version = NULL, $media = 'all' )
	{
		if ( is_array( $version ) )
			$url = add_query_arg( $version, $url );

		else if ( $version )
			$url = add_query_arg( array( 'ver' => $version ), $url );

		echo "\t".self::tag( 'link', array(
			'rel'   => 'stylesheet',
			'href'  => $url,
			'type'  => 'text/css',
			'media' => $media,
		) )."\n";
	}

	public static function headerNav( $uri = '', $active = '', $subs = array(), $prefix = 'nav-tab-', $tag = 'h3' )
	{
		if ( ! count( $subs ) )
			return;

		$html = '';

		foreach ( $subs as $slug => $page )
			$html .= self::tag( 'a', array(
				'class' => 'nav-tab '.$prefix.$slug.( $slug == $active ? ' nav-tab-active' : '' ),
				'href'  => add_query_arg( 'sub', $slug, $uri ),
			), $page );

		echo self::tag( $tag, array(
			'class' => 'nav-tab-wrapper',
		), $html );
	}

	// FIXME: DROP THIS
	// DEPRICATED
	public static function headerTabs( $tabs, $active = 'manual', $prefix = 'nav-tab-', $tag = 'h3' )
	{
		self::__dep( 'tabsList()' );

		if ( ! count( $tabs ) )
			return;

		$html = '';

		foreach ( $tabs as $tab => $title )
			$html .= self::tag( 'a', array(
				'class'    => 'gnetwork-nav-tab nav-tab '.$prefix.$tab.( $tab == $active ? ' nav-tab-active' : '' ),
				'href'     => '#',
				'data-tab' => $tab,
				'rel'      => $tab, // back comp
			), $title );

		echo self::tag( $tag, array(
			'class' => 'nav-tab-wrapper',
		), $html );
	}

	public static function tabsList( $tabs, $atts = array() )
	{
		if ( ! count( $tabs ) )
			return FALSE;

		$args = self::atts( array(
			'title'  => FALSE,
			'active' => FALSE,
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

			$navs .= self::tag( 'a', array(
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
				$contents .= self::tag( 'div', array(
					'class' => $args['prefix'].'-content'.( $tab_args['active'] ? ' '.$args['prefix'].'-content-active -active' : '' ).' -content',
					'data'  => array(
						'tab' => $tab,
					),
				), $content );
		}

		if ( isset( $args['title'] ) && $args['title'] )
			echo $args['title'];

		$navs = self::tag( $args['nav'], array(
			'class' => $args['prefix'].'-wrapper -wrapper',
		), $navs );

		echo self::tag( 'div', array(
			'class' => array(
				'base-tabs-list',
				'-base',
				$args['prefix'].'-base',
				$args['class'],
			),
		), $navs.$contents );

		if ( class_exists( __NAMESPACE__.'\\Utilities' ) )
			Utilities::enqueueScript( 'admin.tabs' );
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

		echo '<div class="base-table-wrap">';

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
						$class = self::escapeAttr( $column['class'] );

				} else if ( '_cb' == $key ) {
					$title = '<input type="checkbox" id="cb-select-all-1" class="-cb-all" />';
					$class = ' check-column';
					$tag   = 'td';
				} else {
					$title = $column;
				}

				echo '<'.$tag.' class="-column -column-'.self::escapeAttr( $key ).$class.'">'.$title.'</'.$tag.'>';
			}
		echo '</tr></thead><tbody>';

		$alt = TRUE;
		foreach ( $data as $index => $row ) {

			echo '<tr class="-row -row-'.$index.( $alt ? ' alternate' : '' ).'">';

			foreach ( $columns as $key => $column ) {

				$class = $callback = $actions = '';
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
					$value = '<input type="checkbox" name="_cb[]" value="'.self::escapeAttr( $value ).'" class="-cb" />';
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
						$class .= ' '.self::escapeAttr( $column['class'] );

					if ( isset( $column['callback'] ) )
						$callback = $column['callback'];

					if ( isset( $column['actions'] ) ) {
						$actions = $column['actions'];
						$class .= ' has-row-actions';
					}
				}

				echo '<'.$cell.' class="-cell -cell-'.$key.$class.'">';

				if ( $callback ){
					echo call_user_func_array( $callback, array( $value, $row, $column, $index ) );

				} else if ( $value ) {
					echo $value;

				} else {
					echo '&nbsp;';
				}

				if ( $actions )
					self::tableActions( call_user_func_array( $actions,
						array( $value, $row, $column, $index ) ) );

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

		echo '</div></div>';

		return TRUE;
	}

	public static function tableActions( $actions )
	{
		if ( ! $actions || ! is_array( $actions ) )
			return;

		$count = count( $actions );

		$i = 0;

		echo '<div class="base-table-actions row-actions">';

			foreach ( $actions as $action => $html ) {
				++$i;
				$sep = $i == $count ? '' : ' | ';
				echo '<span class="-action-'.$action.'">'.$html.$sep.'</span>';
			}

		echo '</div>';
	}

	public static function tableNavigation( $pagination = array() )
	{
		$args = self::atts( array(
			'total'    => 0,
			'pages'    => 0,
			'limit'    => self::limit(),
			'paged'    => self::paged(),
			'order'    => self::order( 'asc' ),
			'all'      => FALSE,
			'next'     => FALSE,
			'previous' => FALSE,
		), $pagination );

		$icons = array(
			'next'     => self::getDashicon( 'controls-forward' ), // &rsaquo;
			'previous' => self::getDashicon( 'controls-back' ), // &lsaquo;
			'refresh'  => self::getDashicon( 'controls-repeat' ),
			'order'    => self::getDashicon( 'sort' ),
		);

		echo '<div class="base-table-navigation">';

			echo '<input type="number" class="small-text -paged" name="paged" value="'.$args['paged'].'" />';
			echo '<input type="number" class="small-text -limit" name="limit" value="'.$args['limit'].'" />';

			if ( FALSE === $args['previous'] ) {
				$previous = '<span class="-previous -span button" disabled="disabled">'.$icons['previous'].'</span>';
			} else {
				$previous = self::tag( 'a', array(
					'href'  => add_query_arg( 'paged', $args['previous'] ),
					'class' => '-previous -link button',
				), $icons['previous'] );
			}

			if ( FALSE === $args['next'] ) {
				$next = '<span class="-next -span button" disabled="disabled">'.$icons['next'].'</span>';
			} else {
				$next = self::tag( 'a', array(
					'href'  => add_query_arg( 'paged', $args['next'] ),
					'class' => '-next -link button',
				), $icons['next'] );
			}

			$refresh = self::tag( 'a', array(
				'href'  => URL::current(),
				'class' => '-refresh -link button',
			), $icons['refresh'] );

			$template = is_rtl() ? '<span class="-next-previous">%3$s %1$s %2$s</span>' : '<span class="-next-previous">%2$s %1$s %3$s</span>';

			vprintf( $template, array( $refresh, $previous, $next ) );

			vprintf( '<span class="-total-pages">%s / %s</span>', array(
				Number::format( $args['total'] ),
				Number::format( $args['pages'] ),
			) );

			echo self::tag( 'a', array(
				'href'  => add_query_arg( 'order', ( 'asc' == $args['order'] ? 'desc' : 'asc' ) ),
				'class' => '-order -link button',
			), $icons['order'] );

		echo '</div>';
	}

	public static function tablePagination( $found, $max, $limit, $paged, $all = FALSE )
	{
		$pagination = array(
			'total'    => intval( $found ),
			'pages'    => intval( $max ),
			'limit'    => intval( $limit ),
			'paged'    => intval( $paged ),
			'all'      => $all,
			'next'     => FALSE,
			'previous' => FALSE,
		);

		if ( $pagination['pages'] > 1 ) {
			if ( $pagination['paged'] != 1 )
				$pagination['previous'] = $pagination['paged'] - 1;

			if ( $pagination['paged'] != $pagination['pages'] )
				$pagination['next'] = $pagination['paged'] + 1;
		}

		return $pagination;
	}

	public static function menu( $menu, $callback = FALSE, $list = 'ul', $children = 'children' )
	{
		if ( ! $menu )
			return;

		echo '<'.$list.'>';

		foreach ( $menu as $item ) {

			echo '<li>';

			if ( is_callable( $callback ) )
				echo call_user_func_array( $callback, array( $item ) );
			else
				echo self::link( $item['title'], '#'.$item['slug'] );

			if ( ! empty( $item[$children] ) )
				self::menu( $item[$children], $callback, $list, $children );

			echo '</li>';
		}

		echo '</'.$list.'>';
	}

	// FIXME: DEPRECATED
	public static function wrapJS( $script = '', $echo = TRUE )
	{
		self::__dev_dep( 'HTML::wrapjQueryReady()' );

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

	public static function wrapScript( $script )
	{
		if ( ! $script )
			return;

		echo '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
			echo $script;
		echo "\n".'/* ]]> */'."\n".'</script>';
	}

	public static function wrapjQueryReady( $script )
	{
		if ( ! $script )
			return;

		echo '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
			echo 'jQuery(document).ready(function($) {'."\n".$script.'});'."\n";
		echo '/* ]]> */'."\n".'</script>'."\n";
	}

	// @REF: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	// CLASSES: notice-error, notice-warning, notice-success, notice-info, is-dismissible
	public static function notice( $notice, $class = 'notice-success fade', $echo = TRUE )
	{
		$html = sprintf( '<div class="notice %s is-dismissible"><p>%s</p></div>', $class, $notice );

		if ( ! $echo )
			return $html;

		echo $html;
	}

	// @REF: https://developer.wordpress.org/resource/dashicons/
	public static function getDashicon( $icon = 'wordpress-alt', $tag = 'span' )
	{
		return self::tag( $tag, array(
			'class' => array(
				'dashicons',
				'dashicons-'.$icon,
			),
		), NULL );
	}
}

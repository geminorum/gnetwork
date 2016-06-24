<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class HTML extends Base
{

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
					$att = implode( ' ', array_unique( array_filter( $att, array( __CLASS__, 'sanitizeClass' ) ) ) );

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
				$att = implode( ' ', array_unique( array_filter( explode( ' ', $att ), array( __CLASS__, 'sanitizeClass' ) ) ) );

			else if ( 'class' == $key )
				$att = $att;

			else if ( 'href' == $key && '#' != $att )
				$att = self::escapeURL( $att );

			else if ( 'src' == $key && FALSE === strpos( $att, 'data:image' ) )
				$att = self::escapeURL( $att );

			else
				$att = esc_attr( $att );

			$html .= ' '.$key.'="'.trim( $att ).'"';
		}

		if ( FALSE === $content )
			return $html.' />';

		return $html.'>';
	}

	public static function escapeURL( $url )
	{
		return esc_url( $url );
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

	public static function tableCode( $array, $reverse = FALSE, $caption = FALSE )
	{
		if ( $reverse )
			$row = '<tr><td class="-val"><code>%1$s</code></td><td class="-var">%2$s</td></tr>';
		else
			$row = '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>';

		echo '<table class="base-table-code'.( $reverse ? ' -reverse' : '' ).'">';

		if ( $caption )
			echo '<caption>'.$caption.'</caption>';

		echo '<tbody>';

		foreach ( (array) $array as $key => $val )
			printf( $row, $key, $val );

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
					'class' => $args['prefix'].'-content -content',
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
			'all'      => FALSE,
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
				( FALSE === $args['previous'] ? '<span class="-previous -span" aria-hidden="true">'.$icons['previous'].'</span>' : HTML::tag( 'a', array(
					'href'  => add_query_arg( 'paged', $args['previous'] ),
					'class' => '-previous -link',
				), $icons['previous'] ) ),
				HTML::tag( 'a', array(
					'href'  => HTTP::currentURL(),
					'class' => '-refresh -link',
				), $icons['refresh'] ),
				( FALSE === $args['next'] ? '<span class="-next -span" aria-hidden="true">'.$icons['next'].'</span>' : HTML::tag( 'a', array(
					'href'  => add_query_arg( 'paged', $args['next'] ),
					'class' => '-next -link',
				), $icons['next'] ) ),
			) );

		echo '</div>';
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

	// @REF: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
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

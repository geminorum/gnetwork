<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class HTML extends Base
{

	// NOTE: DEPRECATED: use `Core\L10n::rtl()`
	public static function rtl()
	{
		return L10n::rtl();
	}

	public static function link( $html, $link = '#', $target_blank = FALSE )
	{
		if ( is_null( $html ) )
			$html = $link;

		return self::tag( 'a', [
			'class'  => '-link',
			'href'   => $link,
			'target' => $target_blank ? '_blank' : FALSE,
			'dummy'  => 'wtf', // HACK: dummy attr to distract the `wordWrap()`!
		], $html );
	}

	// @SEE: https://github.com/zxing/zxing/wiki/Barcode-Contents#e-mail-address
	public static function mailto( $email, $title = FALSE, $content = NULL, $class = '' )
	{
		return '<a class="'.self::prepClass( '-mailto', $class ).'"'
			.' href="mailto:'.trim( $email ).'"'
			.( $title ? ' data-toggle="tooltip" title="'.self::escape( $title ).'"' : '' )
			.'>'.( $content ?? self::wrapLTR( trim( $email ) ) )
		.'</a>';
	}

	public static function tel( $number, $title = FALSE, $content = NULL, $class = '' )
	{
		return '<a class="'.self::prepClass( '-tel', $class ).'"'
			.' href="'.self::prepURLforTel( $number ).'"'
			.' data-tel-number="'.self::escape( $number ).'"'
			.( $title ? ' data-toggle="tooltip" title="'.self::escape( $title ).'"' : '' )
			.'>'.( $content ?? self::wrapLTR( Number::localize( $number ) ) )
		.'</a>';
	}

	public static function geo( $data, $title = FALSE, $content = NULL, $class = '' )
	{
		if ( is_null( $content ) )
			$content = Number::localize( $data );

		return '<a class="'.self::prepClass( '-geo', $class )
			.'" href="'.self::prepURLforGeo( $data )
			.'"'.( $title ? ' data-toggle="tooltip" title="'.self::escape( $title ).'"' : '' )
			.' data-geo-data="'.self::escape( $data ).'">'
			.self::wrapLTR( $content ).'</a>';
	}

	public static function scroll( $html, $to, $title = '' )
	{
		return '<a class="scroll" title="'.$title.'" href="#'.$to.'">'.$html.'</a>';
	}

	// @REF: https://web.dev/native-lazy-loading/
	// @SEE: https://www.smashingmagazine.com/2021/04/humble-img-element-core-web-vitals/
	public static function img( $src, $class = '', $alt = '' )
	{
		return $src ? '<img src="'.$src.'" class="'.self::prepClass( $class ).'" alt="'.self::escape( $alt ).'" decoding="async" loading="lazy" />' : '';
	}

	public static function heading( $level, $html, $class = NULL, $link = FALSE )
	{
		if ( $level && $html ) echo self::tag( sprintf( 'h%s', $level ), [ 'class' => $class ?? '-title' ], ( $link ? self::link( $html, $link ) : $html ) );
	}

	public static function h1( $html, $class = FALSE, $link = FALSE )
	{
		if ( $html ) echo self::tag( 'h1', [ 'class' => $class ], ( $link ? self::link( $html, $link ) : $html ) );
	}

	public static function h2( $html, $class = FALSE, $link = FALSE )
	{
		if ( $html ) echo self::tag( 'h2', [ 'class' => $class ], ( $link ? self::link( $html, $link ) : $html ) );
	}

	public static function h3( $html, $class = FALSE, $link = FALSE )
	{
		if ( $html ) echo self::tag( 'h3', [ 'class' => $class ], ( $link ? self::link( $html, $link ) : $html ) );
	}

	public static function h4( $html, $class = FALSE, $link = FALSE )
	{
		if ( $html ) echo self::tag( 'h4', [ 'class' => $class ], ( $link ? self::link( $html, $link ) : $html ) );
	}

	public static function code( $string, $class = FALSE )
	{
		return ( empty( $string ) && '0' !== $string ) ? '' : self::tag( 'code', [ 'class' => $class ], $string );
	}

	public static function small( $string, $class = FALSE, $space = FALSE )
	{
		return empty( $string ) ? '' : ( $space ? ' ' : '' ).self::tag( 'small', [ 'class' => $class ], $string );
	}

	public static function desc( $string, $block = TRUE, $class = '', $nl2br = TRUE )
	{
		if ( ! $string )
			return;

		if ( is_array( $string ) ) {

			$assoc = Arraay::isAssoc( $string );

			foreach ( $string as $desc_class => $desc_html )
				self::desc( $desc_html, $block, $assoc ? $desc_class : $class, $nl2br );

			return;
		}

		if ( ! $string = trim( $string ) )
			return;

		$tag = $block ? 'p' : 'span';

		if ( Text::starts( $string, [ '<p', '<ul', '<ol', '<h3', '<h4', '<h5', '<h6' ] ) )
			$tag = 'div';

		echo '<'.$tag.' class="'.self::prepClass( 'description', '-description', $class ).'">'
			// .Text::wordWrap( $nl2br ? nl2br( $string ) : $string ) // FIXME: messes with html attrs
			.( $nl2br ? nl2br( $string ) : $string )
		.'</'.$tag.'>';
	}

	public static function dieMessage( $html )
	{
		echo '<div class="wrap-die-message">';
			echo wpautop( $html );
		echo '</div>';

		return FALSE;
	}

	public static function label( $input, $for = FALSE, $wrap = 'p' )
	{
		$html = self::tag( 'label', [ 'for' => $for, 'class' => 'form-label' ], $input );
		echo $wrap ? self::tag( $wrap, $html ) : $html;
	}

	public static function buttonClass( $small = TRUE, $extra = [] )
	{
		$classes = array_merge( [
			'btn'                   ,   // BS5
			'btn-default'           ,   // BS: DEPRECATED
			'btn-outline-secondary' ,   // BS5
			'button'                ,   // WP Core: Admin
			'-button'               ,   // OURS!
		], (array) $extra );

		if ( ! $small )
			return $classes;

		$classes[] = 'btn-sm';        // BS5
		$classes[] = 'button-small';  // WP Core: Admin

		return $classes;
	}

	public static function button( $html, $link = '#', $title = FALSE, $icon = FALSE, $data = [], $id = FALSE )
	{
		if ( ! $html )
			return '';

		return self::tag( ( $link ? 'a' : 'span' ), [
			'id'     => $id ?: FALSE,
			'href'   => $link ?: FALSE,
			'title'  => $title,
			'class'  => self::buttonClass( TRUE, $icon ? '-button-icon' : [] ),
			'data'   => $data,
			// 'target' => '_blank',
		], $html );
	}

	public static function row( $html, $class = '', $data = [], $tag = 'li' )
	{
		if ( ! $html && ! '0' === $html )
			return '';

		return '<'.( $tag ?: 'div' )
			.' class="'.self::prepClass( '-row', $class )
			.'"'.self::propData( $data ).'>'.$html
			.'</'.( $tag ?: 'div' ).'>';
	}

	public static function rows( $rows, $class = '', $data = [], $tag = 'ul', $sub_tag = 'li' )
	{
		if ( empty( $rows ) )
			return '';

		$html = '<'.( $tag ?: 'div' )
			.' class="'.self::prepClass( '-rows', $class )
			.'"'.self::propData( $data ).'>';

			foreach ( $rows as $row )
				$html.= self::row( $row, '', [], $sub_tag );

		return $html.'</'.( $tag ?: 'div' ).'>';
	}

	public static function wrap( $html, $class = '', $block = TRUE, $data = [], $id = FALSE )
	{
		if ( '0' !== $html && ! $html )
			return '';

		return $block
			? '<div'.( $id ? ' id="'.$id.'" ' : ' ' ).' class="'.self::prepClass( '-wrap', $class ).'"'.self::propData( $data ).'>'.$html.'</div>'
			: '<span'.( $id ? ' id="'.$id.'" ' : ' ' ).' class="'.self::prepClass( '-wrap', $class ).'"'.self::propData( $data ).'>'.$html.'</span>';
	}

	public static function wrapLTR( $content )
	{
		// return '&lrm;'.$content.'&rlm;';
		return '&#8206;'.$content.'&#8207;';
	}

	public static function preCode( $content, $rows = 1 )
	{
		echo '<textarea dir="ltr" class="textarea-autosize" rows="'.$rows.'" style="width:100%;text-align:left;direction:ltr;" readonly>';
			echo self::escapeTextarea( $content );
		echo '</textarea>';
	}

	public static function inputHidden( $name, $value = '' )
	{
		echo '<input type="hidden" name="'.self::escape( $name ).'" value="'.self::escape( $value ).'" />';
	}

	// @REF: https://gist.github.com/eric1234/5802030
	// useful when you want to pass on a complex data structure via a form
	public static function inputHiddenArray( $array, $prefix = '' )
	{
		if ( empty( $array ) )
			return;

		if ( ! Arraay::isNumeric( $array ) ) {

			foreach ( $array as $key => $value ) {
				$name = empty( $prefix ) ? $key : $prefix.'['.$key.']';

				if ( is_array( $value ) )
					self::inputHiddenArray( $value, $name );
				else
					self::inputHidden( $name, $value );
			}

		} else {

			foreach ( $array as $item ) {
				if ( is_array( $item ) )
					self::inputHiddenArray( $item, $prefix.'[]' );
				else
					self::inputHidden( $prefix.'[]', $item );
			}
		}
	}

	public static function joined( $items, $before = '', $after = '', $sep = '|', $empty = '' )
	{
		return count( $items ) ? ( $before.implode( $sep, $items ).$after ) : $empty;
	}

	public static function tag( $tag, $atts = [], $content = FALSE, $sep = '' )
	{
		if ( empty( $tag ) ) {

			if ( ! is_array( $atts ) )
				return $atts.$sep;

			if ( $content )
				return ( (string) $content ).$sep;

			return '';
		}

		$tag = self::sanitizeTag( $tag );

		if ( is_array( $atts ) )
			$html = self::_tag_open( $tag, $atts, $content );

		else if ( $atts )
			return '<'.$tag.'>'.$atts.'</'.$tag.'>'.$sep;

		else
			return '<'.$tag.'></'.$tag.'>'.$sep;

		if ( FALSE === $content )
			return $html.$sep;

		/**
		 * Void elements: `area`, `base`, `br`, `col`, `embed`, `hr`,
		 * `img`, `input`, `link`, `meta`, `source`, `track`, `wbr`
		 * @source https://html.spec.whatwg.org/multipage/syntax.html#void-elements
		 * @link https://github.com/pugjs/void-elements
		 */

		if ( is_null( $content ) )
			return $html.'</'.$tag.'>'.$sep;

		return $html.( (string) $content ).'</'.$tag.'>'.$sep;
	}

	public static function attrBoolean( $value, $current = NULL, $fallback = FALSE )
	{
		if ( ! is_array( $value ) )
			return (bool) $value;

		if ( TRUE === $current )
			return TRUE;

		if ( ! is_null( $current ) )
			return in_array( $current, $value );

		return $fallback;
	}

	public static function attrClass()
	{
		$classes = [];

		foreach ( func_get_args() as $arg )

			if ( is_array( $arg ) )
				$classes = array_merge( $classes, $arg );

			else if ( $arg && TRUE !== $arg )
				$classes = array_merge( $classes, preg_split( '#\s+#', $arg ) );

		return Arraay::prepString( $classes );
	}

	public static function prepClass()
	{
		$classes = func_get_args();

		if ( TRUE === $classes[0] )
			return '';

		if ( 1 === count( $classes ) && empty( $classes[0] ) )
			return '';

		return implode( ' ', array_unique( array_filter( call_user_func_array( [ __CLASS__, 'attrClass' ], $classes ), [ __CLASS__, 'sanitizeClass' ] ) ) );
	}

	public static function propData( $data )
	{
		if ( empty( $data ) )
			return '';

		if ( ! is_array( $data ) )
			return ' data="'.trim( self::escape( $data ) ).'"';

		$html = '';

		foreach ( $data as $key => $value ) {

			if ( is_array( $value ) )
				$html.= ' data-'.$key.'=\''.self::encode( $value ).'\'';

			else if ( FALSE === $value )
				continue;

			else
				$html.= ' data-'.$key.'="'.trim( self::escape( $value ) ).'"';
		}

		return $html;
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

					$html.= self::propData( $att );

					continue;

				} else if ( 'class' == $key ) {

					$att = self::prepClass( $att );

				} else {

					$att = implode( ' ', Arraay::prepString( $att ) );
				}

				$sanitized = TRUE;
			}

			if ( in_array( $key, [ 'selected', 'checked', 'readonly', 'disabled', 'default', 'required', 'multiple', 'async' ], TRUE ) )
				$att = $att ? $key : FALSE;

			else if ( in_array( $key, [ 'autocomplete' ], TRUE ) )
				$att = $att ?: 'off';

			else if ( in_array( $key, [ 'spellcheck' ], TRUE ) )
				$att = $att ? 'true' : 'false';

			if ( FALSE === $att )
				continue;

			if ( 'class' == $key && ! $sanitized )
				$att = self::prepClass( $att );

			else if ( 'class' == $key || 'title' == $key )
				$att = $att;

			else if ( 'href' == $key && '#' != $att )
				$att = self::escapeURL( $att );

			else if ( 'src' == $key && FALSE === strpos( $att, 'data:image' ) )
				$att = self::escapeURL( $att );

			else
				$att = self::escape( $att );

			$html.= ' '.$key.'="'.trim( $att ).'"';
		}

		if ( FALSE === $content )
			return $html.' />';

		return $html.'>';
	}

	/**
	 * Encodes a variable into JSON, with some confidence checks.
	 * `wp_json_encode()` with default arguments is insufficient to safely
	 * escape JSON for script tags.
	 * @source https://core.trac.wordpress.org/ticket/63851
	 *
	 * @param mixed $data
	 * @return string
	 */
	public static function encode( $data )
	{
		return wp_json_encode( $data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
	}

	// @ref: `esc_html()`, `esc_attr()`
	public static function escape( $data )
	{
		if ( is_null( $data ) )
			return '';

		return Text::utf8Compliant( $data )
			? Text::utf8SpecialChars( $data, ENT_QUOTES )
			: '';
	}

	// NOTE: DEPRECATED
	public static function escapeAttr( $data )
	{
		return self::escape( $data );
	}

	public static function escapeURL( $data )
	{
		if ( is_null( $data ) )
			return '';

		return esc_url( $data );
	}

	public static function escapeTextarea( $data )
	{
		if ( is_null( $data ) )
			return '';

		return Text::utf8SpecialChars( $data, ENT_QUOTES );
	}

	// NOTE: like WP core but without filter and fallback
	// @source `sanitize_html_class()`
	public static function sanitizeClass( $data )
	{
		if ( is_null( $data ) )
			return '';

		// strip out any % encoded octets
		$sanitized = preg_replace( '/%[a-fA-F0-9][a-fA-F0-9]/', '', $data );

		// limit to A-Z,a-z,0-9,_,-
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );

		return $sanitized;
	}

	// NOTE: like WP core but without filter
	// @source: `tag_escape()`
	public static function sanitizeTag( $data )
	{
		if ( is_null( $data ) )
			return '';

		return preg_replace( '/[^a-zA-Z0-9_:]/', '', $data );
	}

	// @REF: https://www.billerickson.net/code/phone-number-url/
	// @SEE: https://www.iana.org/assignments/uri-schemes/uri-schemes.xhtml
	// @SEE: https://github.com/zxing/zxing/wiki/Barcode-Contents#telephone-numbers

	// OLD: `sanitizePhoneNumberURL()`
	public static function prepURLforTel( $data )
	{
		if ( is_null( $data ) )
			return '';

		return self::escapeURL( sprintf(
			Text::starts( $data, 'tel:' ) ? '%s' : 'tel:%s',
			str_replace( [ '(', ')', '-', '.', '|', ' ' ], '', $data )
		) );
	}

	// @SEE: https://github.com/zxing/zxing/wiki/Barcode-Contents#smsmmsfacetime
	// OLD: `sanitizeSMSNumberURL()`
	public static function prepURLforSMS( $data )
	{
		if ( is_null( $data ) )
			return '';

		return self::escapeURL( sprintf(
			Text::starts( $data, 'sms:' ) ? '%s' : 'sms:%s',
			str_replace( [ '(', ')', '-', '.', '|', ' ' ], '', $data )
		) );
	}

	// OLD: `sanitizeGeoURL()`
	public static function prepURLforGeo( $data )
	{
		if ( is_null( $data ) )
			return '';

		return self::escapeURL( sprintf(
			Text::starts( $data, 'geo:' ) ? '%s' : 'geo:%s',
			str_replace( [ '(', ')', '-', ' ' ], '', $data )
		) );
	}

	// NOTE: DEPRECATED
	public static function getAtts( $string, $expecting = [] )
	{
		self::_dev_dep( 'HTML::parseAtts()' );

		return self::parseAtts( $string, $expecting );
	}

	public static function parseAtts( $string, $expecting = [] )
	{
		foreach ( $expecting as $attr => $default ) {

			preg_match( "#".$attr."=\"(.*?)\"#s", $string, $matches );

			if ( isset( $matches[1] ) )
				$expecting[$attr] = trim( $matches[1] );
		}

		return $expecting;
	}

	public static function listCode( $array, $row = NULL, $first = FALSE )
	{
		if ( ! $array )
			return '';

		$html = '<ul class="base-list-code">';

		if ( is_null( $row ) )
			$row = '<code title="%2$s">%1$s</code>';

		if ( $first )
			$html.= '<li class="-first">'.$first.'</li>';

		foreach ( (array) $array as $key => $value )
			$html.= '<li>'.sprintf( $row, $key, self::sanitizeDisplay( $value ) ).'</li>';

		return $html.'</ul>';
	}

	public static function tableCode( $array, $reverse = FALSE, $caption = FALSE )
	{
		if ( ! $array )
			return '';

		if ( $reverse )
			$row = '<tr><td class="-val"><code>%1$s</code></td><td class="-var" valign="top">%2$s</td></tr>';
		else
			$row = '<tr><td class="-var" valign="top">%1$s</td><td class="-val"><code>%2$s</code></td></tr>';

		$html = '<table class="base-table-code'.( $reverse ? ' -reverse' : '' ).'">';

		if ( FALSE !== $caption )
			$html.= '<caption>'.$caption.'</caption>';

		$html.= '<tbody>';

		foreach ( (array) $array as $key => $value )
			$html.= sprintf( $row, $key, self::sanitizeDisplay( $value ) );

		return $html.'</tbody></table>';
	}

	public static function sanitizeDisplay( $value )
	{
		if ( is_null( $value ) )
			$value = 'NULL';

		else if ( is_bool( $value ) )
			$value = $value ? 'TRUE' : 'FALSE';

		else if ( is_array( $value ) )
			$value = self::joined( $value, '[', ']', ',', 'EMPTY ARRAY' );

		else if ( is_object( $value ) )
			$value = json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

		else if ( is_int( $value ) )
			$value = $value;

		else if ( empty( $value ) )
			$value = 'EMPTY';

		else
			$value = nl2br( trim( $value ) );

		return $value;
	}

	public static function tableDouble( $data, $columns = [], $verbose = TRUE, $class = '' )
	{
		$html = '<table class="'.self::prepClass( 'base-table-double', $class ).'">';

		foreach ( $data as $key => $value )
			$html.= vsprintf( '<tr data-key="%s"><td>%s</td><td>%s</td></tr>', [
				self::escape( $key ),
				array_key_exists( $key, (array) $columns ) ? $columns[$key] : $key,
				$value,
			] );

		$html.= '</table>';

		if ( ! $verbose )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function tableSimple( $data, $columns = [], $verbose = TRUE, $class = '' )
	{
		if ( empty( $data ) )
			return $verbose ? FALSE : '';

		$html = '<div class="wrap-base-table-simple"><table class="'.self::prepClass( 'base-table-simple', $class ).'">';

		if ( $columns && count( $columns ) ) {

			$html.= '<thead><tr>';

			foreach ( $columns as $column_key => $column_caption )
				$html.= sprintf( '<th data-key="%s">%s</th>', self::escape( $column_key ), $column_caption );

			$html.= '</tr></thead><tbody>';

			foreach ( $data as $id => $row ) {

				$html.= sprintf( '<tr data-key="%s">', $id );

				foreach ( $columns as $column_key => $column_caption )
					$html.= sprintf( '<td data-key="%s">%s</td>', $column_key, empty( $row[$column_key] ) ? '' : trim( $row[$column_key] ) );

				$html.= '</tr>';
			}

		} else {

			$html.= '<tbody>';

			foreach ( $data as $row )
				$html.= '<tr><td>'.implode( '</td><td>', $row ).'</td></tr>';
		}

		$html.= '</tbody></table></div>';

		if ( ! $verbose )
			return $html;

		echo $html;
		return TRUE;
	}

	// FIXME: WTF: not wrapping the child table!!
	// FIXME: DRAFT: needs styling
	public static function tableSideWrap( $array, $title = FALSE )
	{
		echo '<table class="widefat fixed base-table-side-wrap">';
			if ( $title )
				echo '<thead><tr><th>'.$title.'</th></tr></thead>';
			echo '<tbody>';
			self::tableSide( $array );
		echo '</tbody></table>';
	}

	public static function tableSide( $array, $type = TRUE, $caption = FALSE )
	{
		echo '<table class="base-table-side">';

		if ( $caption )
			echo '<caption style="caption-side:top">'.$caption.'</caption>';

		if ( ! empty( $array ) ) {

			foreach ( $array as $key => $val ) {

				$val = maybe_unserialize( $val );

				echo '<tr class="-row">';

				if ( is_string( $key ) ) {
					echo '<td class="-key">';
						echo '<strong>'.$key.'</strong>';
						if ( $type ) echo '<br /><small>'.gettype( $val ).'</small>';
					echo '</td>';
				}

				if ( is_array( $val ) || is_object( $val ) ) {

					echo '<td class="-val -table">';
					self::tableSide( $val, $type );

				} else if ( is_null( $val ) ) {

					echo '<td class="-val -not-table"><code>NULL</code>';

				} else if ( is_bool( $val ) ) {

					echo '<td class="-val -not-table"><code>'.( $val ? 'TRUE' : 'FALSE' ).'</code>';

				} else if ( ! empty( $val ) ) {

					echo '<td class="-val -not-table"><code>'.( $type ? self::escape( $val ) : nl2br( $val ) ).'</code>';

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

	public static function linkStyleSheet( $url, $version = NULL, $media = 'all', $verbose = TRUE )
	{
		if ( is_array( $version ) )
			$url = add_query_arg( $version, $url );

		else if ( $version )
			$url = add_query_arg( 'ver', $version, $url );

		$html = self::tag( 'link', [
			'rel'   => 'stylesheet',
			'href'  => $url,
			// 'type'  => 'text/css', // @REF: https://core.trac.wordpress.org/ticket/64428
			'media' => $media,
		] )."\n";

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public static function headerNav( $uri = '', $active = '', $subs = [], $prefix = 'nav-tab', $wrap = 'h3', $item = FALSE )
	{
		if ( empty( $subs ) )
			return '';

		$html = '';

		foreach ( $subs as $slug => $page ) {

			if ( is_array( $page ) ) {
				$title = empty( $page['title'] ) ? $slug : sprintf( '<span class="-nav-link-title">%s</span>', $page['title'] );
				$icon  = empty( $page['icon'] ) ? '' : sprintf( '<span class="-nav-link-icon">%s</span> ', $page['icon'] );
				$args  = empty( $page['args'] ) ? [ 'sub' => $slug ] : $page['args'];
			} else {
				$title = sprintf( '<span class="-nav-link-title">%s</span>', $page );
				$icon  = '';
				$args  = [ 'sub' => $slug ];
			}

			$url   = add_query_arg( $args, $uri );
			$class = [
				$prefix,
				sprintf( '%s-%s', $prefix, $slug ),
				$icon ? '-has-navicon' : '-has-not-navicon',
				$slug === $active ? sprintf( '%s-active -active', $prefix ) : '',
			];

			if ( $item )
				$html.= self::tag( $item, [ 'class' => $class ], self::link( $icon.$title, $url ) );
			else
				$html.= self::tag( 'a', [ 'class' => $class, 'href' => $url ], $icon.$title );
		}

		if ( $wrap )
			echo self::tag( $wrap, [ 'class' => $prefix.'-wrapper' ], $html );

		else
			echo $html;
	}

	public static function tabNav( $active = '', $tabs = [], $prefix = 'nav-tab-', $tag = 'div' )
	{
		if ( empty( $tabs ) )
			return;

		if ( empty( $active ) )
			$active = Arraay::keyFirst( $tabs );

		$html = '';

		foreach ( $tabs as $tab => $title )
			$html.= self::tag( 'a', [
				'class' => 'nav-tab '.$prefix.$tab.( $tab == $active ? ' nav-tab-active -active' : '' ),
				'href'  => '#'.$tab,
				'data'  => [ 'tab' => $tab, 'toggle' => 'tab' ],
			], $title );

		echo self::tag( $tag, [ 'class' => 'nav-tab-wrapper -wrapper' ], $html );
	}

	// @REF: https://make.wordpress.org/core/2019/04/02/admin-tabs-semantic-improvements-in-5-2/
	public static function tabsList( $tabs, $atts = [] )
	{
		if ( empty( $tabs ) )
			return FALSE;

		$args = self::atts( [
			'active' => NULL, // TRUE forces first tab active
			'title'  => FALSE,
			'class'  => FALSE,
			'prefix' => 'nav-tab',
			'nav'    => 'h3',
		], $atts );

		if ( TRUE === $args['active'] ) {

			$args['active'] = array_keys( $tabs )[0];

		} else if ( ! $args['active'] ) {

			$actives = @Arraay::pluck( $tabs, 'active' );
			$args['active'] = array_keys( ( count( $actives ) ? $actives : $tabs ) )[0];
		}

		$navs = $contents = '';

		foreach ( $tabs as $tab => $tab_atts ) {

			$tab_args = self::atts( [
				// 'active'  => FALSE, // not needed here, just for reference
				'title'   => $tab,
				'link'    => '#'.$tab,
				'cb'      => FALSE,
				'content' => '',
			], $tab_atts );

			$navs.= self::tag( 'a', [
				'href'  => $tab_args['link'],
				'class' => $args['prefix'].' -nav'.( $tab == $args['active'] ? ' '.$args['prefix'].'-active -active' : '' ),
				'data'  => [ 'tab' => $tab, 'toggle' => 'tab' ],
			], $tab_args['title'] );

			$content = '';

			if ( $tab_args['cb'] && is_callable( $tab_args['cb'] ) ) {

				ob_start();
					call_user_func_array( $tab_args['cb'], [ $tab, $tab_args, $args ] );
				$content.= ob_get_clean();

			} else if ( $tab_args['content'] ) {

				$content = $tab_args['content'];
			}

			if ( $content ) {

				$contents.= self::tag( 'div', [
					'class' => $args['prefix'].'-content'.( $tab == $args['active'] ? ' '.$args['prefix'].'-content-active -active' : '' ).' -content',
					'data'  => [ 'tab' => $tab ],
				], $content );
			}
		}

		if ( isset( $args['title'] ) && $args['title'] )
			echo $args['title'];

		$navs = self::tag( $args['nav'], [ 'class' => $args['prefix'].'-wrapper -wrapper' ], $navs );

		echo self::tag( 'div', [
			'class' => [
				'base-tabs-list',
				'-base',
				$args['prefix'].'-base',
				$args['class'],
			],
		], $navs.$contents );

		return TRUE;
	}

	public static function tableList( $columns, $data = [], $atts = [] )
	{
		if ( empty( $columns ) )
			return FALSE;

		$args = self::atts( [
			'empty'      => NULL,
			'title'      => NULL,
			'before'     => FALSE,
			'after'      => FALSE,
			'row_prep'   => FALSE,   // call back to prep each row data
			'row_class'  => FALSE,   // call back to filter each row class
			'callback'   => FALSE,   // for all cells
			'sanitize'   => TRUE,    // using `sanitizeDisplay()`
			'search'     => FALSE,   // 'before', // 'after', // FIXME: add search box
			'navigation' => FALSE,   // 'before', // 'after',
			'direction'  => NULL,
			'pagination' => [],
			'map'        => [],
			'extra'      => [],      // just passing around!
		], $atts );

		echo '<div'.( is_null( $args['direction'] ) ? '' : ' dir="'.$args['direction'].'"' ).' class="base-table-wrap">';

		if ( $args['title'] )
			echo '<div class="base-table-title">'.$args['title'].'</div>';

		if ( $args['before'] || 'before' == $args['navigation'] || 'before' == $args['search'] )
			echo '<div class="base-table-actions base-table-list-before">';
		else
			echo '<div>';

		if ( $args['before'] && is_callable( $args['before'] ) ) {

			echo '<div class="base-table-navigation -callable">';
				call_user_func_array( $args['before'], [ $columns, $data, $args ] );
			echo '</div>';
		}

		if ( 'before' == $args['navigation'] ) {

			echo '<div class="base-table-navigation -pagination">';
				self::tableNavigation( $args['pagination'] );
			echo '</div>';
		}

		echo '</div><table class="widefat fixed base-table-list"><thead><tr>';

			foreach ( $columns as $key => $column ) {

				if ( empty( $column ) )
					continue;

				$tag   = 'th';
				$class = [
					'-column',
					'-column-'.$key,
				];

				if ( is_array( $column ) ) {

					$title = empty( $column['title'] ) ? $key : $column['title'];

					if ( ! empty( $column['class'] ) )
						$class = array_merge( $class, (array) $column['class'] );

				} else if ( '_cb' === $key ) {

					$title   = '<input type="checkbox" id="cb-select-all-1" class="-cb-all" />';
					$tag     = 'td';
					$class[] = 'check-column';

				} else {

					$title = $column;
				}

				echo '<'.$tag.' class="'.self::prepClass( $class ).'">'.$title.'</'.$tag.'>';
			}

		echo '</tr></thead><tbody class="-list">';

		if ( empty( $data ) ) {

			echo '<tr><td colspan="'.count( $columns ).'">';
				self::desc( $args['empty'], TRUE, 'base-table-empty' );
			echo '</td></tr>';

		} else {

			$alt = TRUE;

			foreach ( $data as $index => $row ) {

				if ( is_callable( $args['row_prep'] ) )
					$row = call_user_func_array( $args['row_prep'], [ $row, $index, $args ] );

				if ( FALSE === $row )
					continue;

				$row_class = [ '-row', '-row-'.$index ];

				if ( $alt )
					$row_class[] = 'alternate';

				if ( $args['row_class'] )
					$row_class = call_user_func_array( $args['row_class'], [ $row_class, $row, $index, $args ] );

				echo '<tr class="'.self::prepClass( $row_class ).'">';

				foreach ( $columns as $offset => $column ) {

					$cell     = 'td';
					$value    = NULL;
					$key      = $offset;
					$class    = [];
					$callback = $actions = '';

					// override key using map
					if ( array_key_exists( $offset, $args['map'] ) )
						$key = $args['map'][$offset];

					if ( is_array( $column ) ) {

						if ( ! empty( $column['class'] ) )
							$class = array_merge( $class, (array) $column['class'] );

						if ( ! empty( $column['callback'] ) )
							$callback = $column['callback'];

						if ( ! empty( $column['actions'] ) ) {
							$actions = $column['actions'];
							$class[] = 'has-row-actions';
						}

						// again override key using map
						if ( ! empty( $column['map'] ) )
							$key = $column['map'];
					}

					if ( '_cb' === $key ) {

						$cell    = 'th';
						$value   = '';
						$class[] = 'check-column';

						if ( '_index' == $column )
							$value = $index;

						else if ( is_array( $column ) && ! empty( $column['value'] ) )
							$value = call_user_func_array( $column['value'], [ NULL, $row, $column, $index, $key, $args ] );

						else if ( is_array( $row ) && array_key_exists( $column, $row ) )
							$value = $row[$column];

						else if ( is_object( $row ) && property_exists( $row, $column ) )
							$value = $row->{$column};

						$value = '<input type="checkbox" name="_cb[]" value="'.self::escape( $value ).'" class="-cb" />';

					} else if ( is_array( $row ) ) {

						if ( array_key_exists( $key, $row ) )
							$value = $row[$key];

					} else if ( is_object( $row ) ) {

						if ( property_exists( $row, $key ) )
							$value = $row->{$key};
					}

					echo '<'.$cell.' class="'.self::prepClass( '-cell', '-cell-'.$key, $class ).'">';

					if ( $callback )
						echo call_user_func_array( $callback,
							array( $value, $row, $column, $index, $key, $args ) );

					else if ( $args['callback'] && '_cb' !== $key )
						echo call_user_func_array( $args['callback'],
							array( $value, $row, $column, $index, $key, $args ) );

					else if ( $args['sanitize'] && '_cb' !== $key )
						echo self::sanitizeDisplay( $value );

					else if ( $value )
						echo $value;

					else
						echo '&nbsp;';

					if ( $actions )
						self::tableActions( call_user_func_array( $actions,
							array( $value, $row, $column, $index, $key, $args ) ) );

					echo '</'.$cell.'>';
				}

				$alt = ! $alt;

				echo '</tr>';
			}
		}

		echo '</tbody></table>';
		echo '<div class="clear"></div>';

		if ( $args['after'] || 'after' == $args['navigation'] || 'after' == $args['search'] )
			echo '<div class="base-table-actions base-table-list-after">';
		else
			echo '<div>';

		if ( 'after' == $args['navigation'] ) {

			echo '<div class="base-table-navigation -pagination">';
				self::tableNavigation( $args['pagination'] );
			echo '</div>';
		}

		if ( $args['after'] && is_callable( $args['after'] ) ) {

			echo '<div class="base-table-navigation -callable">';
				call_user_func_array( $args['after'], [ $columns, $data, $args ] );
			echo '</div>';
		}

		echo '</div></div>';

		return TRUE;
	}

	public static function tableActions( $actions, $verbose = TRUE )
	{
		if ( ! $actions || ! is_array( $actions ) )
			return;

		$count = count( $actions );

		$i = 0;

		$html = '<div class="base-table-actions row-actions">';

			foreach ( $actions as $name => $action ) {
				++$i;
				$sep = $i == $count ? '' : ' | ';
				$html.= '<span class="-action-'.$name.' '.$name.'">'.$action.$sep.'</span>';
			}

		$html.= '</div>';

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public static function tableNavigation( $pagination = [] )
	{
		$args = self::atts( [
			'actions'  => [],
			'icons'    => [],
			'before'   => [],
			'after'    => [],
			'total'    => 0,
			'pages'    => 0,
			'limit'    => self::limit(),
			'paged'    => self::paged(),
			'order'    => self::order( 'ASC' ),
			'extra'    => [],
			'all'      => FALSE,
			'next'     => FALSE,
			'previous' => FALSE,
			'rtl'      => self::rtl(),
		], $pagination );

		$icons = self::atts( [
			'action'   => self::getDashicon( 'update' ),
			'filter'   => self::getDashicon( 'filter' ),
			'last'     => self::getDashicon( $args['rtl'] ? 'controls-skipback' : 'controls-skipforward' ),
			'first'    => self::getDashicon( $args['rtl'] ? 'controls-skipforward' : 'controls-skipback' ),
			'next'     => self::getDashicon( $args['rtl'] ? 'controls-back' : 'controls-forward' ), // &rsaquo;
			'previous' => self::getDashicon( $args['rtl'] ? 'controls-forward' : 'controls-back' ), // &lsaquo;
			'refresh'  => self::getDashicon( 'controls-repeat' ),
			'order'    => self::getDashicon( 'sort' ),
		], $args['icons'] );

		// echo '<div class="base-table-navigation">';

			if ( count( $args['actions'] ) ) {
				echo '<span class="-before">';
				echo self::dropdown( $args['actions'], [
					'name'       => 'table_action',
					'selected'   => self::req( 'table_action', 'none' ),
					'none_value' => 'none',
					'none_title' => '&mdash;',
				] );
				echo '</span>&nbsp;';
				echo '<button type="submit" class="button -action -icon" />'.$icons['action'].'</button>';
			}

			foreach ( (array) $args['before'] as $before )
				if ( $before )
					echo '<span class="-before">'.$before.'</span>&nbsp;';

			echo '<input type="number" class="small-text -paged" name="paged" value="'.$args['paged'].'" />&nbsp;';
			echo '<input type="number" class="small-text -limit" name="limit" value="'.$args['limit'].'" />&nbsp;';
			echo '<button type="submit" name="filter_action" class="button -filter -icon button-primary" />'.$icons['filter'].'</button>&nbsp;';

			echo self::tag( 'a', [
				'href' => add_query_arg( array_merge( $args['extra'], [
					'message' => FALSE,
					'count'   => FALSE,
					'order'   => ( 'ASC' === $args['order'] ) ? 'desc' : 'asc',
					'limit'   => $args['limit'],
			 ] ) ),
				'class' => '-order -link button -icon',
			], $icons['order'] );

			foreach ( (array) $args['after'] as $after )
				if ( $after )
					echo '&nbsp;<span class="-after">'.$after.'</span>';

			echo '<div class="-controls">';

			echo '&nbsp;';
			echo '&nbsp;';

			vprintf( '<span class="-total-pages">%s / %s</span>', [
				Number::format( $args['total'] ),
				Number::format( $args['pages'] ),
			] );

			echo '&nbsp;';
			echo '&nbsp;';

			if ( FALSE === $args['previous'] ) {
				echo '<span class="-first -span button -icon" disabled="disabled">'.$icons['first'].'</span>';
				echo '&nbsp;';
				echo '<span class="-previous -span button -icon" disabled="disabled">'.$icons['previous'].'</span>';
			} else {
				echo self::tag( 'a', [
					'href' => add_query_arg( array_merge( $args['extra'], [
						'message' => FALSE,
						'count'   => FALSE,
						'paged'   => FALSE,
						'limit'   => $args['limit'],
					] ) ),
					'class' => '-first -link button -icon',
				], $icons['first'] );
				echo '&nbsp;';
				echo self::tag( 'a', [
					'href' => add_query_arg( array_merge( $args['extra'], [
						'message' => FALSE,
						'count'   => FALSE,
						'paged'   => $args['previous'],
						'limit'   => $args['limit'],
					] ) ),
					'class' => '-previous -link button -icon',
				], $icons['previous'] );
			}

			echo '&nbsp;';
			echo self::tag( 'a', [
				'href' => add_query_arg( array_merge( $args['extra'], [
					'message' => FALSE,
					'count'   => FALSE,
					'paged'   => $args['paged'],
					'limit'   => $args['limit'],
				] ) ),
				'class' => '-refresh -link button -icon',
			], $icons['refresh'] );
			echo '&nbsp;';

			if ( FALSE === $args['next'] ) {
				echo '<span class="-last -span button -icon" disabled="disabled">'.$icons['last'].'</span>';
				echo '&nbsp;';
				echo '<span class="-next -span button -icon" disabled="disabled">'.$icons['next'].'</span>';
			} else {
				echo self::tag( 'a', [
					'href' => add_query_arg( array_merge( $args['extra'], [
						'message' => FALSE,
						'count'   => FALSE,
						'paged'   => $args['next'],
						'limit'   => $args['limit'],
					] ) ),
					'class' => '-next -link button -icon',
				], $icons['next'] );
				echo '&nbsp;';

				// when found count is not available
				if ( $args['pages'] )
					echo self::tag( 'a', [
						'href' => add_query_arg( array_merge( $args['extra'], [
							'message' => FALSE,
							'count'   => FALSE,
							'paged'   => $args['pages'],
							'limit' => $args['limit'],
					 	] ) ),
						'class' => '-last -link button -icon',
					], $icons['last'] );
				else
					echo '<span class="-last -span button -icon" disabled="disabled">'.$icons['last'].'</span>';
			}

			echo '</div>';
		// echo '</div>';
	}

	public static function tablePagination( $found, $max, $limit, $paged, $extra = [], $all = FALSE )
	{
		$pagination = [
			'total'    => (int) $found,
			'pages'    => (int) $max,
			'limit'    => (int) $limit,
			'paged'    => (int) $paged,
			'extra'    => $extra, // extra args to add to the links
			'all'      => $all, // WTF?! (probably display all!)
			'next'     => FALSE,
			'previous' => FALSE,
		];

		if ( FALSE === $max ) {

			if ( $pagination['paged'] != 1 )
				$pagination['previous'] = $pagination['paged'] - 1;

			// if ( $pagination['paged'] != $pagination['pages'] )
				$pagination['next'] = $pagination['paged'] + 1;

		} else if ( $pagination['pages'] > 1 ) {

			if ( $pagination['paged'] != 1 )
				$pagination['previous'] = $pagination['paged'] - 1;

			if ( $pagination['paged'] != $pagination['pages'] )
				$pagination['next'] = $pagination['paged'] + 1;
		}

		return $pagination;
	}

	public static function menu( $menu, $callback = FALSE, $list = 'ul', $children = 'children', $class = '-html-menu' )
	{
		if ( ! $menu )
			return;

		echo '<'.$list.( $class ? ' class="'.self::prepClass( $class ).'"' : '' ).'>';

		foreach ( $menu as $item ) {

			echo '<li>';

			if ( is_callable( $callback ) )
				echo call_user_func_array( $callback, [ $item ] );
			else
				echo self::link( $item['title'], '#'.$item['slug'] );

			if ( ! empty( $item[$children] ) )
				self::menu( $item[$children], $callback, $list, $children, '' );

			echo '</li>';
		}

		echo '</'.$list.'>';
	}

	public static function wrapScript( $code, $verbose = TRUE )
	{
		if ( ! $code )
			return '';

		$script = '<script>'."\n";
		$script.= "\n".$code."\n";
		$script.= '</script>'."\n";

		if ( ! $verbose )
			return $script;

		echo $script;
	}

	// @REF: https://jquery.com/upgrade-guide/3.0/#deprecated-document-ready-handlers-other-than-jquery-function
	public static function wrapjQueryReady( $code, $verbose = TRUE )
	{
		if ( ! $code )
			return '';

		$script = '<script>'."\n";
		$script.= 'jQuery(function($){'."\n".$code.'});'."\n";
		$script.= '</script>'."\n";

		if ( ! $verbose )
			return $script;

		echo $script;
	}

	// TODO: migrate to `wp_get_admin_notice()` @since WP 6.4.0
	// @REF: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	// CLASSES: `notice-error`, `notice-warning`, `notice-success`, `notice-info`, `is-dismissible`, `fade`, `inline`
	public static function notice( $notice, $class = 'notice-success fade', $dismissible = TRUE )
	{
		return sprintf( '<div class="notice %s%s -notice">%s</div>', $class, ( $dismissible ? ' is-dismissible' : '' ), Text::autoP( $notice ) );
	}

	public static function error( $notice, $dismissible = TRUE, $extra = '' )
	{
		return self::notice( $notice, 'notice-error fade '.self::prepClass( $extra ), $dismissible );
	}

	public static function success( $notice, $dismissible = TRUE, $extra = '' )
	{
		return self::notice( $notice, 'notice-success fade '.self::prepClass( $extra ), $dismissible );
	}

	public static function warning( $notice, $dismissible = TRUE, $extra = '' )
	{
		return self::notice( $notice, 'notice-warning fade '.self::prepClass( $extra ), $dismissible );
	}

	public static function info( $notice, $dismissible = TRUE, $extra = '' )
	{
		return self::notice( $notice, 'notice-info fade '.self::prepClass( $extra ), $dismissible );
	}

	// @REF: https://developer.wordpress.org/resource/dashicons/
	public static function getDashicon( $icon = NULL, $title = FALSE, $class = '' )
	{
		$icon = $icon ?? 'wordpress-alt';

		if ( ! Text::starts( $icon, 'dashicons-' ) )
			$icon = sprintf( 'dashicons-%s', $icon );

		return self::tag( 'span', [
			'data-icon' => 'dashicons',
			'title'     => $title,
			'class'     => self::attrClass( 'dashicons', $icon, $class ),
		], NULL );
	}

	public static function radioSelect( $list, $atts = [] )
	{
		$html = '';

		if ( FALSE === $list ) // allows hiding
			return $html;

		$args = self::atts( [
			'id'          => FALSE,
			'name'        => '',
			'title'       => FALSE,
			'none_title'  => NULL,
			'none_value'  => 0,
			'value_title' => TRUE,    // Displays value as the title of the option.
			'class'       => FALSE,
			'style'       => FALSE,
			'selected'    => 0,
			'disabled'    => FALSE,
			'dir'         => FALSE,
			'prop'        => FALSE,
			'value'       => FALSE,
			'wrap'        => 'div',
			'exclude'     => [],
			'data'        => [],
		], $atts );

		if ( empty( $args['id'] ) )
			$args['id'] = $args['name'];

		if ( ! is_null( $args['none_title'] ) ) {

			$id = implode( '-', [ $args['id'], $args['none_value'] ] );

			$html.= '<'.$args['wrap'].'><label for"'.self::escapeAttr( $id ).'">';
			$html.= self::tag( 'input', [
				'type'     => 'radio',
				'id'       => $id,
				'name'     => $args['name'],
				'value'    => $args['none_value'],
				'checked'  => in_array( $args['none_value'], (array) $args['selected'] ),
				'disabled' => self::attrBoolean( $args['disabled'], $args['none_value'] ),
				'title'    => $args['title'],
				'style'    => $args['style'],
				'class'    => $args['class'],
				'data'     => $args['data'],
				'dir'      => $args['dir'],
			] );

			$html.= '&nbsp;'.$args['none_title'];
			$html.= '</label></'.$args['wrap'].'>';
		}

		foreach ( $list as $offset => $value ) {

			if ( $args['value'] )
				$key = is_object( $value ) ? $value->{$args['value']} : $value[$args['value']];

			else
				$key = $offset;

			if ( in_array( $key, (array) $args['exclude'] ) )
				continue;

			if ( $args['prop'] )
				$title = is_object( $value ) ? $value->{$args['prop']} : $value[$args['prop']];

			else
				$title = $value;

			$id = implode( '-', [ $args['id'], $key ] );

			$html.= '<'.$args['wrap'].'><label for"'.self::escapeAttr( $id ).'">';
			$html.= self::tag( 'input', [
				'type'     => 'radio',
				'id'       => $id,
				'name'     => $args['name'],
				'value'    => $key,
				'checked'  => in_array( $key, (array) $args['selected'] ),
				'disabled' => self::attrBoolean( $args['disabled'], $key ),
				'title'    => $args['title'],
				'style'    => $args['style'],
				'class'    => $args['class'],
				'data'     => $args['data'],
				'dir'      => $args['dir'],
			] );

			$html.= '&nbsp;'.$title;
			$html.= '</label></'.$args['wrap'].'>';
		}

		return $html;
	}

	// TODO: support `optgroup`: https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/optgroup
	public static function dropdown( $list, $atts = [] )
	{
		$html = '';

		if ( FALSE === $list ) // allows hiding
			return $html;

		$args = self::atts( [
			'id'          => FALSE,
			'name'        => '',
			'title'       => FALSE,
			'none_title'  => NULL,
			'none_value'  => 0,
			'value_title' => TRUE,    // Displays value as the title of the option.
			'class'       => FALSE,
			'style'       => FALSE,
			'selected'    => 0,
			'disabled'    => FALSE,
			'dir'         => FALSE,
			'prop'        => FALSE,
			'value'       => FALSE,
			'exclude'     => [],
			'data'        => [],
		], $atts );

		if ( ! is_null( $args['none_title'] ) )
			$html.= self::tag( 'option', [
				'value'    => $args['none_value'],
				// NOTE: WTF: apparently `none` and `0` are the same via `==`
				'selected' => empty( $args['none_value'] ) ? ( $args['selected'] === $args['none_value'] ) : ( $args['selected'] == $args['none_value'] )
			], $args['none_title'] );

		foreach ( $list as $offset => $value ) {

			if ( $args['value'] )
				$key = is_object( $value ) ? $value->{$args['value']} : $value[$args['value']];

			else
				$key = $offset;

			if ( in_array( $key, (array) $args['exclude'] ) )
				continue;

			if ( $args['prop'] )
				$title = is_object( $value ) ? $value->{$args['prop']} : $value[$args['prop']];

			else
				$title = $value;

			$html.= self::tag( 'option', [
				// NOTE: WTF: apparently `none` and `0` are the same via `==`
				'selected' => empty( $key ) ? ( $args['selected'] === $key ) : ( $args['selected'] == $key ),
				'title'    => $args['value_title'] ? $key : FALSE,
				'value'    => $key,
			], $title );
		}

		return self::tag( 'select', [
			'name'     => $args['name'],
			'id'       => $args['id'],
			'class'    => $args['class'],
			'style'    => $args['style'],
			'title'    => $args['title'],
			'disabled' => $args['disabled'],
			'dir'      => $args['dir'],
			'data'     => $args['data'],
		], $html );
	}

	public static function multiSelect( $list, $atts = [] )
	{
		$html = '';

		if ( FALSE === $list ) // allows hiding
			return $html;

		$args = self::atts( [
			'id'       => FALSE,
			'name'     => '',
			'class'    => FALSE,
			'selected' => [],
			'disabled' => FALSE,
			'prop'     => FALSE,
			'value'    => FALSE,
			'exclude'  => [],
			'panel'    => FALSE, // wraps in `wp-tab-panel`
			'values'   => FALSE, // appends values after titles
			'item_tag' => NULL,
		], $atts );

		if ( is_null( $args['item_tag'] ) )
			$args['item_tag'] = $args['panel'] ? 'li' : 'p';

		foreach ( $list as $offset => $value ) {

			if ( ! $args['value'] )
				$key = $offset;

			else if ( is_object( $value ) )
				$key = property_exists( $value, $args['value'] ) ? $value->{$args['value']} : $offset;

			else if ( is_array( $value ) )
				$key = array_key_exists( $args['value'], $value ) ? $value[$args['value']] : $offset;

			else
				$key = $offset;

			if ( in_array( $key, (array) $args['exclude'] ) )
				continue;

			if ( ! $args['prop'] )
				$title = $value;

			else if ( is_object( $value ) )
				$title = property_exists( $value, $args['prop'] ) ? $value->{$args['prop']} : $value;

			else if ( is_array( $value ) )
				$title = array_key_exists( $args['prop'], $value ) ? $value[$args['prop']] : $value;

			else
				$title = $value;

			if ( ! $args['values'] )
				$suffix = FALSE;

			else if ( TRUE === $args['values'] )
				$suffix = $key;

			else if ( is_object( $value ) )
				$suffix = property_exists( $value, $args['values'] ) ? $value->{$args['values']} : $key;

			else if ( is_array( $value ) )
				$suffix = array_key_exists( $args['values'], $value ) ? $value[$args['values']] : $key;

			else
				$suffix = FALSE;

			$input = self::tag( 'input', [
				'type'     => 'checkbox',
				'id'       => $args['id'].'-'.$key,
				'name'     => $args['name'].'['.$key.']',
				'value'    => '1', // $key,
				'checked'  => self::attrBoolean( $args['selected'], $key ),
				'disabled' => self::attrBoolean( $args['disabled'], $key ),
				'class'    => self::attrClass( $args['class'], '-type-checkbox' ),
				'data'     => [ 'key' => $key ],
			] );

			if ( $suffix )
				$title.= ' &mdash; <code>'.$suffix.'</code>';

			$label = self::tag( 'label', [ 'for' => $args['id'].'-'.$key ], $input.$title );
			$html .= $args['item_tag'] ? self::tag( $args['item_tag'], [ 'class' => [ 'description', '-description' ] ], $label ) : $label;
		}

		if ( $args['panel'] )
			$html = self::tag( 'ul', $html );

		return self::wrap( $html, '-multiselect-wrap'.( $args['panel'] ? ' wp-tab-panel' : '' ) );
	}

	// NOTE: DEPRECATED: use `HTML::rows()`
	public static function renderList( $items, $keys = FALSE, $list = 'ul' )
	{
		return $items ? self::tag( $list, '<li>'.implode( '</li><li>', $keys ? array_keys( $items ) : array_filter( $items ) ).'</li>' ) : '';
	}

	public static function inputForCopy( $text, $class = 'large-text', $code = TRUE, $readonly = TRUE )
	{
		return '<input type="text" value="'
			.self::escapeAttr( $text )
			.'" class="'.self::prepClass( '-input-for-copy', $class, $code ? 'code' : '' )
			.'" onclick="this.focus();this.select()" '
			.( $readonly ? 'readonly' : '' ).' />';
	}

	public static function removeAll( $html )
	{
		while ( preg_match( '/<[^>]*>/', $html ) )
			$html = preg_replace( '/<[^>]*>.*?<\/[^>]*>|<[^>]*\/>|<[^>]*>/s', '', $html );

		return Text::trim( $html );
	}

	/**
	 * Cuts the HTML string given max length preserving the formatting.
	 * @source https://gist.github.com/yanknudtskov/13f42e8efdb8bb650db4d3230dc853dd
	 *
	 * @param string $html
	 * @param int $max_length
	 * @return string
	 */
	public static function cut( $html, $max_length )
	{
		if ( empty( $html ) )
			return $html;

		$tags     = [];
		$result   = $tag = '';
		$stripped = $i   = 0;

		$is_open = $is_close = $grab_open = $in_single_quotes = $in_double_quotes = FALSE;

		$html   = (string) $html;
		$forced = strip_tags( $html );

		while ( $i < strlen( $html ) && $stripped < strlen( $forced ) && $stripped < $max_length ) {

			$symbol = $html[$i];
			$result.= $symbol;

			switch ( $symbol ) {

				case '<':

					$grab_open = $is_open = TRUE;

					break;

				case '"':

					$in_double_quotes = ! $in_double_quotes;

					break;

				case "'":

					$in_single_quotes = ! $in_single_quotes;

					break;

				case '/':

					if ( $is_open && ! $in_double_quotes && ! $in_single_quotes ) {
						$is_close = TRUE;
						$is_open  = $grab_open = FALSE;
					}

					break;

				case ' ':

					if ( $is_open )
						$grab_open = FALSE;
					else
						$stripped++;

					break;

				case '>':

					if ( $is_open ) {

						$is_open = $grab_open = FALSE;
						array_push( $tags, $tag );
						$tag = '';

					} else if ( $is_close ) {
						$is_close = FALSE;
						array_pop( $tags );
						$tag = '';
					}

					break;

				default:

					if ( $grab_open || $is_close )
						$tag.= $symbol;

					if ( ! $is_open && ! $is_close )
						$stripped++;
			}

			$i++;
		}

		while ( $tags )
			$result.= '</'.array_pop($tags).'>';

		return $result;
	}
}

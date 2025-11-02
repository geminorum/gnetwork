<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Color extends Base
{

	// TODO: must convert to `DataType`

	public static function is( $text )
	{
		return self::validHex( $text );
	}

	// @REF: `rest_parse_hex_color()`
	public static function validHex( $color )
	{
		if ( ! $color )
			return FALSE;

		if ( ! preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color, $matches ) )
			return FALSE;

		return $color;
	}

	// https://github.com/mikeemoo/ColorJizz-PHP
	// https://github.com/mexitek/phpColors

	// @REF: https://jeffmatson.net/getting-current-wordpress-admin-color-scheme-colors/
	public static function getAdmin( $user_id = NULL )
	{
		global $_wp_admin_css_colors;

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( ! $current = get_user_meta( $user_id, 'admin_color', TRUE ) )
			$current = 'fresh'; // core default

		return array_merge(
			$_wp_admin_css_colors[$current]->colors,
			$_wp_admin_css_colors[$current]->icon_colors
		);
	}

	/**
	 * Lightens/darkens a given colour (hex format),
	 * returning the altered colour in hex format.7
	 *
	 * @source: https://gist.github.com/stephenharris/5532899
	 *
	 * @param string $hex Colour as hexadecimal (with or without hash);
	 * @param float $percent Decimal ( 0.2 = lighten by 20%(), -0.4 = darken by 40%() )
	 * @return string Lightened/Darkend colour as hexadecimal (with hash);
	 */
	public static function luminance( $hex, $percent )
	{
		$new = '#';
		$hex = preg_replace( '/[^0-9a-f]/i', '', $hex );

		if ( strlen( $hex ) < 6 )
			$hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2] + $hex[2];

		// convert to decimal and change luminosity
		for ( $i = 0; $i < 3; $i++ ) {
			$dec = hexdec( substr( $hex, $i * 2, 2 ) );
			$dec = min( max( 0, $dec + $dec * $percent ), 255 );
			$new.= str_pad( dechex( $dec ), 2, 0, STR_PAD_LEFT );
		}

		return $new;
	}

	// @REF: https://coderwall.com/p/dvecdg/darken-hex-color-in-php
	public static function darken( $rgb, $darker = 2 )
	{
		$hash = FALSE !== strpos( $rgb, '#' ) ? '#' : '';

		$rgb = 7 == strlen( $rgb )
			? str_replace( '#', '', $rgb )
			: ( 6 == strlen( $rgb ) ? $rgb : FALSE );

		if ( 6 != strlen( $rgb ) )
			return $hash.'000000';

		$darker = $darker > 1 ? $darker : 1;

		list( $R16, $G16, $B16 ) = str_split( $rgb, 2 );

		$R = sprintf( "%02X", floor( hexdec( $R16 ) / $darker ) );
		$G = sprintf( "%02X", floor( hexdec( $G16 ) / $darker ) );
		$B = sprintf( "%02X", floor( hexdec( $B16 ) / $darker ) );

		return $hash.$R.$G.$B;
	}

	// RGB values:    0-255, 0-255, 0-255
	// HSV values:    0-360, 0-100, 0-100
	// note that the return values are not rounded, you can do that yourself
	// if required. keep in mind that H(360) == H(0), so H values of 359.5
	// and greater should round to 0
	// @REF: https://stackoverflow.com/a/13887939/
	public static function rgbToHSV( $R, $G, $B )
	{
		// convert the RGB byte-values to percentages
		$R = $R / 255;
		$G = $G / 255;
		$B = $B / 255;

		// Calculate a few basic values, the maximum value of R,G,B, the
		//   minimum value, and the difference of the two (chroma).
		$maxRGB = max( $R, $G, $B );
		$minRGB = min( $R, $G, $B );
		$chroma = $maxRGB - $minRGB;

		// Value (also called Brightness) is the easiest component to calculate,
		//   and is simply the highest value among the R,G,B components.
		// We multiply by 100 to turn the decimal into a readable percent value.
		$computedV = 100 * $maxRGB;

		// Special case if hueless (equal parts RGB make black, white, or grays)
		// Note that Hue is technically undefined when chroma is zero, as
		//   attempting to calculate it would cause division by zero (see
		//   below), so most applications simply substitute a Hue of zero.
		// Saturation will always be zero in this case, see below for details.
		if ( 0 == $chroma )
			return [ 0, 0, $computedV ];

		// Saturation is also simple to compute, and is simply the chroma
		//   over the Value (or Brightness)
		// Again, multiplied by 100 to get a percentage.
		$computedS = 100 * ( $chroma / $maxRGB );

		// Calculate Hue component
		// Hue is calculated on the "chromacity plane", which is represented
		//   as a 2D hexagon, divided into six 60-degree sectors. We calculate
		//   the bisecting angle as a value 0 <= x < 6, that represents which
		//   portion of which sector the line falls on.
		if ( $R == $minRGB )
			$h = 3 - ( ( $G - $B ) / $chroma );

		else if ( $B == $minRGB )
			$h = 1 - ( ( $R - $G ) / $chroma );

		else // $G == $minRGB
			$h = 5 - ( ( $B - $R ) / $chroma );

		// After we have the sector position, we multiply it by the size of
		//   each sector's arc (60 degrees) to obtain the angle in degrees.
		$computedH = 60 * $h;

		return [ $computedH, $computedS, $computedV ];
	}

	/**
	 * Find the resulting colour by blending 2 colours
	 * and setting an opacity level for the foreground colour.
	 * @author J de Silva
	 *
	 * @param string $foreground Hexadecimal colour value of the foreground colour.
	 * @param integer $opacity Opacity percentage (of foreground colour). A number between 0 and 100.
	 * @param string $background Optional. Hexadecimal colour value of the background colour. Default is: <code>FFFFFF</code> aka white.
	 * @return string Hexadecimal colour value. <code>false</code> on errors.
	 *
	 * @REF: http://www.gidnetwork.com/b-135.html
	 */
	public static function blendByOpacity( $foreground, $opacity, $background = NULL )
	{
		static $rgb = [];

		if ( is_null( $background ) )
			$background = 'FFFFFF';

		// accept only valid hexadecimal colour values.
		$pattern = '~^[a-f0-9]{6,6}$~i';

		// "Invalid hexadecimal colour value(s) found"
		if ( ! @preg_match( $pattern, $foreground )
			|| ! @preg_match( $pattern, $background ) )
				return FALSE;

		// validate opacity data/number.
		$opacity = (int) $opacity;

		// "Opacity percentage error, valid numbers are between 0 - 100"
		if ( $opacity > 100  || $opacity < 0 )
			return FALSE;

		// $transparency == 0
		if ( 100 == $opacity )
			return strtoupper( $foreground );

		// $transparency == 100
		if ( 0 == $opacity )
			return strtoupper( $background );

		// calculate $transparency value.
		$transparency = 100 - $opacity;

		// do this only ONCE per script, for each unique colour.
		if ( ! isset( $rgb[$foreground] ) ) {
			$f = [
				'r' => hexdec( $foreground[0].$foreground[1] ),
				'g' => hexdec( $foreground[2].$foreground[3] ),
				'b' => hexdec( $foreground[4].$foreground[5] ),
			];

			$rgb[$foreground] = $f;

		} else {

			// if this function is used 100 times in a script, this block is run 99 times
			$f = $rgb[$foreground];
		}

		if ( ! isset( $rgb[$background] ) ) {

			$b = [
				'r' => hexdec( $background[0].$background[1] ),
				'g' => hexdec( $background[2].$background[3] ),
				'b' => hexdec( $background[4].$background[5] ),
			];

			$rgb[$background] = $b;

		} else {
			$b = $rgb[$background];
		}

		$add = [
			'r' => ( $b['r'] - $f['r'] ) / 100,
			'g' => ( $b['g'] - $f['g'] ) / 100,
			'b' => ( $b['b'] - $f['b'] ) / 100,
		];

		$f['r'] += (int) ( $add['r'] * $transparency );
		$f['g'] += (int) ( $add['g'] * $transparency );
		$f['b'] += (int) ( $add['b'] * $transparency );

		return sprintf( '%02X%02X%02X', $f['r'], $f['g'], $f['b'] );
	}

	// EXAMPLE: `hex2rgb( '#cc0' )`
	// @REF: https://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
	public static function hex2rgb( $hex )
	{
		$hex = trim( str_replace( '#', '', $hex ) );

		if ( 3 === strlen( $hex ) ) {
			$r = hexdec( substr( $hex, 0, 1 ).substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ).substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ).substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}

		return [ $r, $g, $b ];
	}

	// ff9900 -> 255 153 0
	// #ff9900 -> 255 153 0
	public static function hex2rgb_2( $hex )
	{
		return sscanf( $hex, '%2x%2x%2x' ); // @REF: http://php.net/manual/en/function.sscanf.php#25190
		return sscanf( $hex, "#%02x%02x%02x" ); // @REF: https://stackoverflow.com/a/15202130
	}

	// EXAMPLE: `rgb2hex(array( 255, 255, 255 ))`
	// @REF: https://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
	public static function rgb2hex( $rgb )
	{
		$hex = "#";
		$hex.= str_pad( dechex( $rgb[0] ), 2, '0', STR_PAD_LEFT );
		$hex.= str_pad( dechex( $rgb[1] ), 2, '0', STR_PAD_LEFT );
		$hex.= str_pad( dechex( $rgb[2] ), 2, '0', STR_PAD_LEFT );

		return $hex;
	}

	// @REF: https://github.com/aristath/ariColor
	// `composer require aristath/ari-color`
	public static function get( $color = '#ffffff' )
	{
		return \ariColor::newColor( $color, 'auto' );
	}

	// returns a CSS value, using the auto-detected mode
	public static function sanitize( $color )
	{
		if ( '' == $color )
			return '';

		if ( is_string( $color ) && 'transparent' == trim( $color ) )
			return 'transparent';

		$object = \ariColor::newColor( $color );

		return $object->toCSS( $object->mode );
	}

	public static function randHEX( $list = NULL )
	{
		if ( is_null( $list ) )
			$list = self::named();

		$key = array_rand( $list );

		return self::rgb2hex( $list[$key] );
	}

	// @REF: https://github.com/colorjs/color-name
	// @REF: http://dev.w3.org/csswg/css-color/#named-colors
	// v2.0.0 - 2024-11-05
	public static function named()
	{
		return [
			'aliceblue'            => [ 240, 248, 255 ],
			'antiquewhite'         => [ 250, 235, 215 ],
			'aqua'                 => [   0, 255, 255 ],
			'aquamarine'           => [ 127, 255, 212 ],
			'azure'                => [ 240, 255, 255 ],
			'beige'                => [ 245, 245, 220 ],
			'bisque'               => [ 255, 228, 196 ],
			'black'                => [   0,   0,   0 ],
			'blanchedalmond'       => [ 255, 235, 205 ],
			'blue'                 => [   0,   0, 255 ],
			'blueviolet'           => [ 138,  43, 226 ],
			'brown'                => [ 165,  42,  42 ],
			'burlywood'            => [ 222, 184, 135 ],
			'cadetblue'            => [  95, 158, 160 ],
			'chartreuse'           => [ 127, 255,   0 ],
			'chocolate'            => [ 210, 105,  30 ],
			'coral'                => [ 255, 127,  80 ],
			'cornflowerblue'       => [ 100, 149, 237 ],
			'cornsilk'             => [ 255, 248, 220 ],
			'crimson'              => [ 220,  20,  60 ],
			'cyan'                 => [   0, 255, 255 ],
			'darkblue'             => [   0,   0, 139 ],
			'darkcyan'             => [   0, 139, 139 ],
			'darkgoldenrod'        => [ 184, 134,  11 ],
			'darkgray'             => [ 169, 169, 169 ],
			'darkgreen'            => [   0, 100,   0 ],
			'darkgrey'             => [ 169, 169, 169 ],
			'darkkhaki'            => [ 189, 183, 107 ],
			'darkmagenta'          => [ 139,   0, 139 ],
			'darkolivegreen'       => [  85, 107,  47 ],
			'darkorange'           => [ 255, 140,   0 ],
			'darkorchid'           => [ 153,  50, 204 ],
			'darkred'              => [ 139,   0,   0 ],
			'darksalmon'           => [ 233, 150, 122 ],
			'darkseagreen'         => [ 143, 188, 143 ],
			'darkslateblue'        => [  72,  61, 139 ],
			'darkslategray'        => [  47,  79,  79 ],
			'darkslategrey'        => [  47,  79,  79 ],
			'darkturquoise'        => [   0, 206, 209 ],
			'darkviolet'           => [ 148,   0, 211 ],
			'deeppink'             => [ 255,  20, 147 ],
			'deepskyblue'          => [   0, 191, 255 ],
			'dimgray'              => [ 105, 105, 105 ],
			'dimgrey'              => [ 105, 105, 105 ],
			'dodgerblue'           => [  30, 144, 255 ],
			'firebrick'            => [ 178,  34,  34 ],
			'floralwhite'          => [ 255, 250, 240 ],
			'forestgreen'          => [  34, 139,  34 ],
			'fuchsia'              => [ 255,   0, 255 ],
			'gainsboro'            => [ 220, 220, 220 ],
			'ghostwhite'           => [ 248, 248, 255 ],
			'gold'                 => [ 255, 215,   0 ],
			'goldenrod'            => [ 218, 165,  32 ],
			'gray'                 => [ 128, 128, 128 ],
			'green'                => [   0, 128,   0 ],
			'greenyellow'          => [ 173, 255,  47 ],
			'grey'                 => [ 128, 128, 128 ],
			'honeydew'             => [ 240, 255, 240 ],
			'hotpink'              => [ 255, 105, 180 ],
			'indianred'            => [ 205,  92,  92 ],
			'indigo'               => [  75,   0, 130 ],
			'ivory'                => [ 255, 255, 240 ],
			'khaki'                => [ 240, 230, 140 ],
			'lavender'             => [ 230, 230, 250 ],
			'lavenderblush'        => [ 255, 240, 245 ],
			'lawngreen'            => [ 124, 252,   0 ],
			'lemonchiffon'         => [ 255, 250, 205 ],
			'lightblue'            => [ 173, 216, 230 ],
			'lightcoral'           => [ 240, 128, 128 ],
			'lightcyan'            => [ 224, 255, 255 ],
			'lightgoldenrodyellow' => [ 250, 250, 210 ],
			'lightgray'            => [ 211, 211, 211 ],
			'lightgreen'           => [ 144, 238, 144 ],
			'lightgrey'            => [ 211, 211, 211 ],
			'lightpink'            => [ 255, 182, 193 ],
			'lightsalmon'          => [ 255, 160, 122 ],
			'lightseagreen'        => [  32, 178, 170 ],
			'lightskyblue'         => [ 135, 206, 250 ],
			'lightslategray'       => [ 119, 136, 153 ],
			'lightslategrey'       => [ 119, 136, 153 ],
			'lightsteelblue'       => [ 176, 196, 222 ],
			'lightyellow'          => [ 255, 255, 224 ],
			'lime'                 => [   0, 255,   0 ],
			'limegreen'            => [  50, 205,  50 ],
			'linen'                => [ 250, 240, 230 ],
			'magenta'              => [ 255,   0, 255 ],
			'maroon'               => [ 128,   0,   0 ],
			'mediumaquamarine'     => [ 102, 205, 170 ],
			'mediumblue'           => [   0,   0, 205 ],
			'mediumorchid'         => [ 186,  85, 211 ],
			'mediumpurple'         => [ 147, 112, 219 ],
			'mediumseagreen'       => [  60, 179, 113 ],
			'mediumslateblue'      => [ 123, 104, 238 ],
			'mediumspringgreen'    => [   0, 250, 154 ],
			'mediumturquoise'      => [  72, 209, 204 ],
			'mediumvioletred'      => [ 199,  21, 133 ],
			'midnightblue'         => [  25,  25, 112 ],
			'mintcream'            => [ 245, 255, 250 ],
			'mistyrose'            => [ 255, 228, 225 ],
			'moccasin'             => [ 255, 228, 181 ],
			'navajowhite'          => [ 255, 222, 173 ],
			'navy'                 => [   0,   0, 128 ],
			'oldlace'              => [ 253, 245, 230 ],
			'olive'                => [ 128, 128,   0 ],
			'olivedrab'            => [ 107, 142,  35 ],
			'orange'               => [ 255, 165,   0 ],
			'orangered'            => [ 255,  69,   0 ],
			'orchid'               => [ 218, 112, 214 ],
			'palegoldenrod'        => [ 238, 232, 170 ],
			'palegreen'            => [ 152, 251, 152 ],
			'paleturquoise'        => [ 175, 238, 238 ],
			'palevioletred'        => [ 219, 112, 147 ],
			'papayawhip'           => [ 255, 239, 213 ],
			'peachpuff'            => [ 255, 218, 185 ],
			'peru'                 => [ 205, 133,  63 ],
			'pink'                 => [ 255, 192, 203 ],
			'plum'                 => [ 221, 160, 221 ],
			'powderblue'           => [ 176, 224, 230 ],
			'purple'               => [ 128,   0, 128 ],
			'rebeccapurple'        => [ 102,  51, 153 ],
			'red'                  => [ 255,   0,   0 ],
			'rosybrown'            => [ 188, 143, 143 ],
			'royalblue'            => [  65, 105, 225 ],
			'saddlebrown'          => [ 139,  69,  19 ],
			'salmon'               => [ 250, 128, 114 ],
			'sandybrown'           => [ 244, 164,  96 ],
			'seagreen'             => [  46, 139,  87 ],
			'seashell'             => [ 255, 245, 238 ],
			'sienna'               => [ 160,  82,  45 ],
			'silver'               => [ 192, 192, 192 ],
			'skyblue'              => [ 135, 206, 235 ],
			'slateblue'            => [ 106,  90, 205 ],
			'slategray'            => [ 112, 128, 144 ],
			'slategrey'            => [ 112, 128, 144 ],
			'snow'                 => [ 255, 250, 250 ],
			'springgreen'          => [   0, 255, 127 ],
			'steelblue'            => [  70, 130, 180 ],
			'tan'                  => [ 210, 180, 140 ],
			'teal'                 => [   0, 128, 128 ],
			'thistle'              => [ 216, 191, 216 ],
			'tomato'               => [ 255,  99,  71 ],
			'turquoise'            => [  64, 224, 208 ],
			'violet'               => [ 238, 130, 238 ],
			'wheat'                => [ 245, 222, 179 ],
			'white'                => [ 255, 255, 255 ],
			'whitesmoke'           => [ 245, 245, 245 ],
			'yellow'               => [ 255, 255,   0 ],
			'yellowgreen'          => [ 154, 205,  50 ],
		];
	}

	/**
	 * Convert RGB to HEX.
	 * @source: `wc_rgb_from_hex()`
	 *
	 * @param mixed $color Color.
	 *
	 * @return array
	 */
	public static function rgbFromHex( $color )
	{
		// convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', str_replace( '#', '', $color ) );

		return [
			'R' => hexdec( $color[0].$color[1] ),
			'G' => hexdec( $color[2].$color[3] ),
			'B' => hexdec( $color[4].$color[5] ),
		];
	}

	/**
	 * Make HEX color darker.
	 * @source `wc_hex_darker()`
	 *
	 * @param mixed $color  Color.
	 * @param int   $factor Darker factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	public static function hexDarker( $color, $factor = 30 )
	{
		$base  = self::rgbFromHex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {

			$amount      = $v / 100;
			$amount      = NumberUtil::round( $amount * $factor );
			$new_decimal = $v - $amount;

			$new_hex_component = dechex( $new_decimal );

			if ( strlen( $new_hex_component ) < 2 )
				$new_hex_component = '0' . $new_hex_component;

			$color.= $new_hex_component;
		}

		return $color;
	}

	/**
	 * Make HEX color lighter.
	 * @source `wc_hex_lighter()`
	 *
	 * @param mixed $color  Color.
	 * @param int   $factor Lighter factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	public static function hexLlighter( $color, $factor = 30 )
	{
		$base  = self::rgbFromHex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {

			$amount      = 255 - $v;
			$amount      = $amount / 100;
			$amount      = Number::round( $amount * $factor );
			$new_decimal = $v + $amount;

			$new_hex_component = dechex( $new_decimal );

			if ( strlen( $new_hex_component ) < 2 )
				$new_hex_component = '0' . $new_hex_component;

			$color .= $new_hex_component;
		}

		return $color;
	}

	/**
	 * Determine whether a hex color is light.
	 * @source `wc_hex_is_light()`
	 *
	 * @param mixed $color Color.
	 * @return bool  True if a light color.
	 */
	public static function hexIsLight( $color )
	{
		$hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );

		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155;
	}

	/**
	 * Detect if we should use a light or dark color on a background color.
	 * @source `wc_light_or_dark()`
	 *
	 * @param mixed  $color Color.
	 * @param string $dark  Darkest reference.
	 *                      Defaults to '#000000'.
	 * @param string $light Lightest reference.
	 *                      Defaults to '#FFFFFF'.
	 * @return string
	 */
	public static function lightOrDark( $hex, $dark = '#000000', $light = '#FFFFFF' )
	{
		return self::hexIsLight( $hex ) ? $dark : $light;
	}

	/**
	 * Format string as hex.
	 * @source `wc_format_hex()`
	 *
	 * @param string $hex HEX color.
	 * @return string|null
	 */
	public static function formatHex( $hex )
	{
		$hex = trim( str_replace( '#', '', $hex ) );

		if ( strlen( $hex ) === 3 )
			$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];

		return $hex ? '#'.$hex : NULL;
	}

	/**
	 * Color Difference
	 *
	 * This is a very simple algorithm that works by summing up the
	 * differences between the three color components red, green and
	 * blue. A value higher than 500 is recommended for good
	 * readability.
	 *
	 * @source https://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php
	 *
	 * @param  int $r1
	 * @param  int $g1
	 * @param  int $b1
	 * @param  int $r2
	 * @param  int $g2
	 * @param  int $b2
	 * @return int $difference
	 */
	public static function colorDiff( $r1, $g1, $b1, $r2, $g2, $b2 )
	{
		return max( $r1, $r2 ) - min( $r1, $r2 ) +
			   max( $g1, $g2 ) - min( $g1, $g2 ) +
			   max( $b1, $b2 ) - min( $b1, $b2 );
	}

	/**
	 * Brightness Difference
	 *
	 * This function is a bit more advanced as it tries to compare the
	 * brightness of the colors. A return value of more than 125 is
	 * recommended. Combining it with the color difference above might
	 * make sense.
	 *
	 * @source https://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php
	 *
	 * @param  int $r1
	 * @param  int $g1
	 * @param  int $b1
	 * @param  int $r2
	 * @param  int $g2
	 * @param  int $b2
	 * @return int $difference
	 */
	public static function brightnessDiff( $r1, $g1, $b1, $r2, $g2, $b2 )
	{
		return abs( ( ( 299 * $r1 + 587 * $g1 + 114 * $b1 ) / 1000 )
			- ( ( 299 * $r2 + 587 * $g2 + 114 * $b2 ) / 1000 ) );
	}

	/**
	 * Luminosity Contrast
	 *
	 * The third function is most complex but seems to work best.
	 * It uses the luminosity to calculate the difference between
	 * the given colors. The returned value should be bigger than 5
	 * for best readability.
	 *
	 * @source https://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php
	 *
	 * @param  int $r1
	 * @param  int $g1
	 * @param  int $b1
	 * @param  int $r2
	 * @param  int $g2
	 * @param  int $b2
	 * @return int $difference
	 */
	public static function luminosityDiff( $r1, $g1, $b1, $r2, $g2, $b2 )
	{
		$l1 = 0.2126 * pow( $r1 / 255, 2.2 ) +
			  0.7152 * pow( $g1 / 255, 2.2 ) +
			  0.0722 * pow( $b1 / 255, 2.2 );

		$l2 = 0.2126 * pow( $r2 / 255, 2.2 ) +
			  0.7152 * pow( $g2 / 255, 2.2 ) +
			  0.0722 * pow( $b2 / 255, 2.2 );

		return ( $l1 > $l2 )
			? ( ( $l1 + 0.05 ) / ( $l2 + 0.05 ) )
			: ( ( $l2 + 0.05 ) / ( $l1 + 0.05 ) );
	}

	/**
	 * Pythagorean Distance
	 *
	 * Manni suggested to use the Pythagorean Distance in the comments.
	 * I ported his code to PHP. However I found it hard to find a
	 * good threshold. Sometimes well contrasting colors return a very
	 * low value, sometimes their value is very high. I settled for
	 * a threshold of 250 in my script, but suggest to use the
	 * luminosity contrast function above instead.
	 *
	 * @see https://en.wikipedia.org/wiki/Pythagorean_theorem
	 * @source https://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php
	 *
	 * @param  int $r1
	 * @param  int $g1
	 * @param  int $b1
	 * @param  int $r2
	 * @param  int $g2
	 * @param  int $b2
	 * @return int $difference
	 */
	public static function pythagoreanDiff( $r1, $g1, $b1, $r2, $g2, $b2 )
	{
		$rd = $r1 - $r2;
		$gd = $g1 - $g2;
		$bd = $b1 - $b2;

		return sqrt( $rd * $rd + $gd * $gd + $bd * $bd );
	}

	/**
	 * YIQ algorithm (less precise)
	 *
	 * Another simpliest and less precise way called YIQ,
	 * because it converts the RGB color space into YIQ.
	 *
	 * @see	https://en.wikipedia.org/wiki/YIQ
	 * @source https://stackoverflow.com/a/42921358
	 *
	 * @param  string $hexcolor
	 * @return string
	 */
	public static function yiqDiff( $hex, $dark = '#000000', $light = '#FFFFFF' )
	{
		$r = hexdec( substr( $hex, 1, 2 ) );
		$g = hexdec( substr( $hex, 3, 2 ) );
		$b = hexdec( substr( $hex, 5, 2 ) );

		$yiq = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;

		return ( $yiq >= 128 ) ? $dark : $light;
	}

	/**
	 * Luminosity Contrast algorithm
	 *
	 * @source https://stackoverflow.com/a/42921358
	 *
	 * @param  string $hex
	 * @return string
	 */
	public static function luminosityContrast( $hex, $dark = '#000000', $light = '#FFFFFF' )
	{
		// hexColor RGB
		$R1 = hexdec( substr( $hex, 1, 2 ) );
		$G1 = hexdec( substr( $hex, 3, 2 ) );
		$B1 = hexdec( substr( $hex, 5, 2 ) );

		// Black RGB
		$blackColor   = "#000000";
		$R2BlackColor = hexdec( substr( $blackColor, 1, 2 ) );
		$G2BlackColor = hexdec( substr( $blackColor, 3, 2 ) );
		$B2BlackColor = hexdec( substr( $blackColor, 5, 2 ) );

		// Calc contrast ratio
		$L1 = 0.2126 * pow( $R1 / 255, 2.2 ) +
			  0.7152 * pow( $G1 / 255, 2.2 ) +
			  0.0722 * pow( $B1 / 255, 2.2 );

		$L2 = 0.2126 * pow( $R2BlackColor / 255, 2.2 ) +
			  0.7152 * pow( $G2BlackColor / 255, 2.2 ) +
			  0.0722 * pow( $B2BlackColor / 255, 2.2 );

		if ( $L1 > $L2 )
			$contrastRatio = (int) ( ( $L1 + 0.05 ) / ( $L2 + 0.05 ) );

		else
			$contrastRatio = (int) ( ( $L2 + 0.05 ) / ( $L1 + 0.05 ) );

		return $contrastRatio > 5 ? $dark : $light;
	}
}


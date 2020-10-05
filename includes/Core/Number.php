<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Number extends Base
{

	public static function localize( $number )
	{
		return apply_filters( 'number_format_i18n', $number );
	}

	public static function format( $number, $decimals = 0, $locale = NULL )
	{
		return apply_filters( 'number_format_i18n', number_format( $number, absint( $decimals ) ), $number, $decimals );
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function intval( $text, $force = TRUE )
	{
		$number = apply_filters( 'string_format_i18n_back', $text );

		return $force ? intval( $number ) : $number;
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function floatval( $text, $force = TRUE )
	{
		$number = apply_filters( 'string_format_i18n_back', $text );

		return $force ? floatval( $number ) : $number;
	}

	// never let a numeric value be less than zero
	// @SOURCE: `bbp_number_not_negative()`
	public static function notNegative( $number )
	{
		if ( is_string( $number ) ) {

			// protect against formatted strings
			$number = strip_tags( $number ); // no HTML
			$number = apply_filters( 'string_format_i18n_back', $number );
			$number = preg_replace( '/[^0-9-]/', '', $number ); // no number-format

		} else if ( ! is_numeric( $number ) ) {

			// protect against objects, arrays, scalars, etc...
			$number = 0;
		}

		// make the number an integer
		// pick the maximum value, never less than zero
		return max( 0, intval( $number ) );
	}

	// @SOURCE: WP's `zeroise()`
	public static function zeroise( $number, $threshold, $locale = NULL )
	{
		return sprintf( '%0'.$threshold.'s', $number );
	}

	public static $readable_suffix = array(
		'trillion' => '%s trillion',
		'billion'  => '%s billion',
		'million'  => '%s million',
		'thousand' => '%s thousand',
	);

	// @REF: http://php.net/manual/en/function.number-format.php#89888
	public static function formatReadable( $number, $suffix = NULL )
	{
		// strip any formatting;
		$number = ( 0 + str_replace( ',', '', $number ) );

		if ( ! is_numeric( $number ) )
			return FALSE;

		if ( is_null( $suffix ) )
			$suffix = self::$readable_suffix;

		if ( $number > 1000000000000 )
			return sprintf( $suffix['trillion'], round( ( $number / 1000000000000 ), 1 ) );

		else if ( $number > 1000000000 )
			return sprintf( $suffix['billion'], round( ( $number / 1000000000 ), 1 ) );

		else if ( $number > 1000000 )
			return sprintf( $suffix['million'], round( ( $number / 1000000 ), 1 ) );

		else if ( $number > 1000 )
			return sprintf( $suffix['thousand'], round( ( $number / 1000 ), 1 ) );

		return self::format( $number );
	}

	// @REF: http://php.net/manual/en/function.number-format.php#89655
	public static function formatOrdinal_en( $number )
	{
		// special case "teenth"
		if ( ( $number / 10 ) % 10 != 1 ) {

			// handle 1st, 2nd, 3rd
			switch ( $number % 10 ) {

				case 1: return $number.'st';
				case 2: return $number.'nd';
				case 3: return $number.'rd';
			}
		}

		// everything else is "nth"
		return $number.'th';
	}

	// FIXME: localize
	// FIXME: maybe case insensitive `strtr()`, SEE: `Text::strtr()`
	// @REF: https://www.irwebdesign.ir/work-with-number-or-int-varible-in-php/
	public static function wordsToNumber( $string )
	{
		// replace all number words with an equivalent numeric value
		$string = strtr( $string, [
			'zero'      => '0',
			'a'         => '1',
			'one'       => '1',
			'two'       => '2',
			'three'     => '3',
			'four'      => '4',
			'five'      => '5',
			'six'       => '6',
			'seven'     => '7',
			'eight'     => '8',
			'nine'      => '9',
			'ten'       => '10',
			'eleven'    => '11',
			'twelve'    => '12',
			'thirteen'  => '13',
			'fourteen'  => '14',
			'fifteen'   => '15',
			'sixteen'   => '16',
			'seventeen' => '17',
			'eighteen'  => '18',
			'nineteen'  => '19',
			'twenty'    => '20',
			'thirty'    => '30',
			'forty'     => '40',
			'fourty'    => '40', // common misspelling
			'fifty'     => '50',
			'sixty'     => '60',
			'seventy'   => '70',
			'eighty'    => '80',
			'ninety'    => '90',
			'hundred'   => '100',
			'thousand'  => '1000',
			'million'   => '1000000',
			'billion'   => '1000000000',
			'and'       => '',
		] );

		// coerce all tokens to numbers
		$parts = array_map( function ( $value ) {
			return floatval( $value );
		}, preg_split('/[\s-]+/', $string ) );

		$stack = new \SplStack; // current work stack
		$sum   = 0; // running total
		$last  = NULL;

		foreach ( $parts as $part ) {

			if ( ! $stack->isEmpty() ) {

				// we're part way through a phrase
				if ( $stack->top() > $part ) {

					// decreasing step, e.g. from hundreds to ones
					if ( $last >= 1000 ) {

						// if we drop from more than 1000 then we've finished the phrase
						$sum+= $stack->pop();

						// this is the first element of a new phrase
						$stack->push( $part );

					} else {

						// drop down from less than 1000, just addition
						// e.g. "seventy one" -> "70 1" -> "70 + 1"
						$stack->push( $stack->pop() + $part );
					}
				} else {

					// increasing step, e.g ones to hundreds
					$stack->push( $stack->pop() * $part );
				}
			} else {

				// this is the first element of a new phrase
				$stack->push( $part );
			}

			// store the last processed part
			$last = $part;
		}

		return $sum + $stack->pop();
	}
}

<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Number extends Base
{

	public static function toOrdinal( $number, $locale = NULL )
	{
		if ( ! $sanitized = self::intval( $number ) )
			return $number;

		if ( is_null( $locale ) )
			$locale = L10n::locale();

		if ( class_exists( 'NumberFormatter' ) ) {

			// $formatter = new \NumberFormatter( $locale, \NumberFormatter::ORDINAL );

			// @REF: https://stackoverflow.com/a/19411974
			$formatter = new \NumberFormatter( $locale, \NumberFormatter::SPELLOUT );
			$formatter->setTextAttribute( \NumberFormatter::DEFAULT_RULESET, "%spellout-ordinal" );

			$formatted = $formatter->format( $sanitized );

		} else if ( 'en_US' == $locale ) {

			$formatted = self::englishOrdinal( $sanitized );

		} else {

			$formatted = $sanitized;
		}

		return apply_filters( 'number_format_ordinal', $formatted, $sanitized, $locale );
	}

	// @REF: https://en.wikipedia.org/wiki/English_numerals#Ordinal_numbers
	// @REF: https://stackoverflow.com/a/3110033
	public static function englishOrdinal( $number )
	{
		$ends = [ 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' ];

		if ( ( ( $number % 100 ) >= 11 ) && ( ( $number % 100 ) <= 13 ) )
			return $number.'th';

		return $number.$ends[$number % 10];
	}

	public static function toWords( $number, $locale = NULL )
	{
		if ( ! $sanitized = self::intval( $number ) )
			return $number;

		if ( is_null( $locale ) )
			$locale = L10n::locale();

		if ( class_exists( 'NumberFormatter' ) ) {

			$formatter = new \NumberFormatter( $locale, \NumberFormatter::SPELLOUT );
			$formatter->setTextAttribute( \NumberFormatter::DEFAULT_RULESET, "%spellout-numbering-verbose" );

			$formatted = $formatter->format( $sanitized );

		} else if ( 'en_US' == $locale ) {

			$formatted = self::englishWords( $sanitized );

		} else {

			$formatted = $sanitized;
		}

		return apply_filters( 'number_format_words', $formatted, $sanitized, $locale );
	}

	// @REF: https://stackoverflow.com/a/30299572
	public static function englishWords( $number )
	{
		$number = (int) $number;
		$words = [];
		$list1 = [ '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen' ];
		$list2 = [ '', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred' ];
		$list3 = [ '', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion', 'sextillion', 'septillion', 'octillion', 'nonillion', 'decillion', 'undecillion', 'duodecillion', 'tredecillion', 'quattuordecillion', 'quindecillion', 'sexdecillion', 'septendecillion', 'octodecillion', 'novemdecillion', 'vigintillion' ];

		$num_length = strlen( $number );
		$levels     = (int) ( ( $num_length + 2 ) / 3 );
		$max_length = $levels * 3;
		$number     = substr( '00'.$number, -$max_length );
		$num_levels = str_split( $number, 3 );

		for ( $i = 0; $i < count( $num_levels ); $i++ ) {
			$levels--;

			$hundreds = (int) ( $num_levels[$i] / 100 );
			$hundreds = ( $hundreds ? ' '.$list1[$hundreds].' hundred'.' ' : '' );
			$tens     = (int) ( $num_levels[$i] % 100 );
			$singles  = '';

			if ( $tens < 20 ) {

				$tens = ( $tens ? ' '.$list1[$tens].' ' : '' );

			} else {

				$tens    = (int) ( $tens / 10 );
				$tens    = ' '.$list2[$tens].' ';
				$singles = (int) ( $num_levels[$i] % 10 );
				$singles = ' '.$list1[$singles].' ';
			}

			$words[] = $hundreds.$tens.$singles.( ( $levels && (int) ( $num_levels[$i] ) ) ? ' '.$list3[$levels].' ' : '' );
		}

		$commas = count( $words );

		if ( $commas > 1 )
			$commas = $commas - 1;

		return implode( ' ', $words );
	}

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

		return $force ? (int) $number : $number;
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function floatval( $text, $force = TRUE )
	{
		$number = apply_filters( 'string_format_i18n_back', $text );

		return $force ? (float) $number : $number;
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
		return max( 0, (int) $number );
	}

	// @SOURCE: `zeroise()` from WordPress
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

		$stack = new \SplStack(); // current work stack
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

	/**
	 * Round a number using the built-in `round` function, but unless the value to round is numeric
	 * (a number or a string that can be parsed as a number), apply 'floatval' first to it
	 * (so it will convert it to 0 in most cases).
	 *
	 * This is needed because in PHP 7 applying `round` to a non-numeric value returns 0,
	 * but in PHP 8 it throws an error. Specifically, in WooCommerce we have a few places where
	 * round('') is often executed.
	 *
	 * @source `Automattic\WooCommerce\Utilities\NumberUtil::round()`
	 *
	 * @param mixed $val The value to round.
	 * @param int   $precision The optional number of decimal digits to round to.
	 * @param int   $mode A constant to specify the mode in which rounding occurs.
	 *
	 * @return float The value rounded to the given precision as a float, or the supplied default value.
	 */
	public static function round( $val, int $precision = 0, int $mode = PHP_ROUND_HALF_UP )
	{
		if ( ! is_numeric( $val ) )
			$val = floatval( $val );

		return round( $val, $precision, $mode );
	}


	/**
	 * get modulus (substitute for bcmod)
	 * by Andrius Baranauskas and Laurynas Butkus
	 *
	 * left_operand can be really big, but be carefull with modulus
	 *
	 * @source https://www.php.net/manual/en/function.bcmod.php#38474
	 *
	 * @param  string $left_operand
	 * @param  int $modulus
	 *
	 * @return string
	 */
	public static function bcmod( $left_operand, $modulus )
	{
		// how many numbers to take at once? carefull not to exceed (int)
		$take = 5;
		$mod  = '';

		do {
			$a = (int) $mod.substr( $left_operand, 0, $take );
			$left_operand = substr( $left_operand, $take );
			$mod = $a % $modulus;
		} while ( strlen( $left_operand ) );

		return (int) $mod;
	}

	/**
	 * average value from array excluding empty
	 *
	 * @source https://stackoverflow.com/a/63839420
	 *
	 * @param  array $list
	 * @param  bool  $includeEmpties
	 * @return float
	 */
	public static function average( array $numbers, bool $roundUp = FALSE, bool $includeEmpties = TRUE )
	{
		$numbers = array_filter( $numbers, static function( $v ) use ( $includeEmpties ) {
			$includeEmpties ? is_numeric( $v ) : is_numeric( $v ) && ( $v > 0 );
		} );

		$average = array_sum( $numbers ) / count( $numbers );

		return $roundUp ? ceil( $average ) : $average;
	}
}

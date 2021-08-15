<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Orthography extends Base
{

	public static $maps = array(
		'pre' => array(),
		'sub' => array(
			'(c)'  => "\xC2\xA9", // '©', // Copyright Sign U+00A9
			'(r)'  => "\xC2\xAE", // '®', // Registered Sign U+00AE
			'(tm)' => "\xE2\x84\xA2", // '™', // Trade Mark Sign U+2122
			'c/o'  => "\xE2\x84\x85", // '℅', // Care Of U+2105
			'-->'  => "\xE2\x86\x92", // '→', // Rightwards Arrow U+2192
			'<--'  => "\xE2\x86\x90", // '←', // Leftwards Arrow U+2190
			'==>'  => "\xE2\x87\x92", // '⇒', // Rightwards Double Arrow U+21D2
			'<=>'  => "\xE2\x87\x94", // '⇔', // Left Right Double Arrow U+21D4
			'<=='  => "\xE2\x87\x90", // '⇐', // Leftwards Double Arrow U+21D0
			'...'  => "\xE2\x80\xA6", // '…', // Horizontal Ellipsis U+2026

			'1/4'  => "\xC2\xBC", // '¼', // Vulgar Fraction One Quarter U+00BC
			'1/2'  => "\xC2\xBD", // '½', // Vulgar Fraction One Half U+00BD
			'3/4'  => "\xC2\xBE", // '¾', // Vulgar Fraction Three Quarters U+00BE
			'1/3'  => "\xE2\x85\x93", // '⅓', // Vulgar Fraction One Third U+2153
			'2/3'  => "\xE2\x85\x94", // '⅔', // Vulgar Fraction Two Thirds U+2154
			'1/5'  => "\xE2\x85\x95", // '⅕', // Vulgar Fraction One Fifth U+2155
			'2/5'  => "\xE2\x85\x96", // '⅖', // Vulgar Fraction Two Fifths U+2156
			'3/5'  => "\xE2\x85\x97", // '⅗', // Vulgar Fraction Three Fifths U+2157
			'4/5'  => "\xE2\x85\x98", // '⅘', // Vulgar Fraction Four Fifths U+2158
			'1/6'  => "\xE2\x85\x99", // '⅙', // Vulgar Fraction One Sixth U+2159
			'5/6'  => "\xE2\x85\x9A", // '⅚', // Vulgar Fraction Five Sixths U+215A
			'1/8'  => "\xE2\x85\x9B", // '⅛', // Vulgar Fraction One Eighth U+215B
			'3/8'  => "\xE2\x85\x9C", // '⅜', // Vulgar Fraction Three Eighths U+215C
			'5/8'  => "\xE2\x85\x9D", // '⅝', // Vulgar Fraction Five Eighths U+215D
			'7/8'  => "\xE2\x85\x9E", // '⅞', // Vulgar Fraction Seven Eighths U+215E
		),
	);

	public static function cleanupPersian( $string )
	{
		if ( is_null( $string ) )
			return NULL;

		$string = self::cleanupPre( $string );
		$string = self::cleanupPersianChars( $string );
		$string = self::cleanupArabicChars( $string );
		$string = self::cleanupZWNJ( $string );
		$string = self::translatePersianNumbers( $string );

		return trim( $string );
	}

	public static function cleanupPre( $string )
	{
		return strtr( $string, self::pairsPre() );
	}

	private static function pairsPre()
	{
		return array(

			// UTF8 Bom => DELETE
			"\xEF\xBB\xBF" => '',

			// Right-To-Left Mark U+200F  => Zero Width Non-Joiner U+200C
			chr(0xE2).chr(0x80).chr(0x8F) => chr(0xE2).chr(0x80).chr(0x8C),
			// Left-To-Right Mark U+200E  => Zero Width Non-Joiner U+200C
			chr(0xE2).chr(0x80).chr(0x8E) => chr(0xE2).chr(0x80).chr(0x8C),

			// => Zero Width Non-Joiner U+200C
			// chr(0xC2).chr(0xAC) => chr(0xE2).chr(0x80).chr(0x8C),

			// Arabic Tatweel U+0640 => DELETE
			// "\xD9\x80" => '', // them using this as interjectional!

			// Arabic Letter Teh Marbuta U+0629 => Arabic Letter Heh U+0647 / Arabic Hamza Above U+0654
			chr(0xD8).chr(0xA9) => chr(0xD9).chr(0x87).chr(0xD9).chr(0x94),
			// Arabic Letter Heh with Yeh Above U+06C0 => Arabic Letter Heh U+0647 / Arabic Hamza Above U+0654
			chr(0xD8).chr(0x80) => chr(0xD9).chr(0x87).chr(0xD9).chr(0x94),
		);
	}

	public static function cleanupPersianChars( $string )
	{
		foreach ( self::mapPersianChars() as $group )
			$string = str_ireplace( $group[0], $group[1], $string );

		return $string;
	}

	public static function cleanupArabicChars( $string )
	{
		return strtr( $string, self::pairsArabic() );
	}

	private static function pairsArabic()
	{
		return array(
			chr(0xD9).chr(0xA0) => chr(0xDB).chr(0xB0),
			chr(0xD9).chr(0xA1) => chr(0xDB).chr(0xB1),
			chr(0xD9).chr(0xA2) => chr(0xDB).chr(0xB2),
			chr(0xD9).chr(0xA3) => chr(0xDB).chr(0xB3),
			chr(0xD9).chr(0xA4) => chr(0xDB).chr(0xB4),
			chr(0xD9).chr(0xA5) => chr(0xDB).chr(0xB5),
			chr(0xD9).chr(0xA6) => chr(0xDB).chr(0xB6),
			chr(0xD9).chr(0xA7) => chr(0xDB).chr(0xB7),
			chr(0xD9).chr(0xA8) => chr(0xDB).chr(0xB8),
			chr(0xD9).chr(0xA9) => chr(0xDB).chr(0xB9),

			chr(0xD9).chr(0x83) => chr(0xDA).chr(0xA9), // ARABIC LETTER KAF > ARABIC LETTER KEHEH
			chr(0xD9).chr(0x89) => chr(0xDB).chr(0x8C), // ARABIC LETTER ALEF MAKSURA > ARABIC LETTER FARSI YEH
			chr(0xD9).chr(0x8A) => chr(0xDB).chr(0x8C), // ARABIC LETTER YEH > ARABIC LETTER FARSI YEH
			chr(0xDB).chr(0x80) => chr(0xD9).chr(0x87).chr(0xD9).chr(0x94),
		);
	}

	public static function cleanupZWNJ( $string )
	{
		$string = str_replace( array(
			"&zwnj;".' ',
			"\xE2\x80\x8C".' ',
			' '."&zwnj;",
			' '."\xE2\x80\x8C",
		), ' ', $string );

		$string = str_replace( "&zwnj;", "\xE2\x80\x8C", $string );

		$string = str_replace( array(
			"\xE2\x80\x8C"."\xE2\x80\x8C"."\xE2\x80\x8C"."\xE2\x80\x8C"."\xE2\x80\x8C",
			"\xE2\x80\x8C"."\xE2\x80\x8C"."\xE2\x80\x8C"."\xE2\x80\x8C",
			"\xE2\x80\x8C"."\xE2\x80\x8C"."\xE2\x80\x8C",
			"\xE2\x80\x8C"."\xE2\x80\x8C",
		), "\xE2\x80\x8C", $string );

		return $string;
	}

	public static function translatePersianNumbers( $string )
	{
		$string = strtr( $string, self::pairsArabicNumbersBack() ); // arabic to english
		$string = strtr( $string, self::pairsPersianNumbers() ); // english to persian

		return $string;
	}

	private static function pairsPersianNumbers()
	{
		return array(
			'0' => chr(0xDB).chr(0xB0),
			'1' => chr(0xDB).chr(0xB1),
			'2' => chr(0xDB).chr(0xB2),
			'3' => chr(0xDB).chr(0xB3),
			'4' => chr(0xDB).chr(0xB4),
			'5' => chr(0xDB).chr(0xB5),
			'6' => chr(0xDB).chr(0xB6),
			'7' => chr(0xDB).chr(0xB7),
			'8' => chr(0xDB).chr(0xB8),
			'9' => chr(0xDB).chr(0xB9),
		);
	}

	public static function translateNumbersBack( $string )
	{
		$string = strtr( $string, self::pairsArabicNumbersBack() ); // arabic to english
		$string = strtr( $string, self::pairsPersianNumbersBack() ); // persian to english

		return $string;
	}

	public static function translatePersianNumbersBack( $string )
	{
		return strtr( $string, self::pairsPersianNumbersBack() );
	}

	private static function pairsPersianNumbersBack()
	{
		return array(
			chr(0xDB).chr(0xB0) => '0',
			chr(0xDB).chr(0xB1) => '1',
			chr(0xDB).chr(0xB2) => '2',
			chr(0xDB).chr(0xB3) => '3',
			chr(0xDB).chr(0xB4) => '4',
			chr(0xDB).chr(0xB5) => '5',
			chr(0xDB).chr(0xB6) => '6',
			chr(0xDB).chr(0xB7) => '7',
			chr(0xDB).chr(0xB8) => '8',
			chr(0xDB).chr(0xB9) => '9',
		);
	}

	public static function translateArabicNumbersBack( $string )
	{
		return strtr( $string, self::pairsArabicNumbersBack() );
	}

	private static function pairsArabicNumbersBack()
	{
		return array(
			chr(0xD9).chr(0xA0) => '0',
			chr(0xD9).chr(0xA1) => '1',
			chr(0xD9).chr(0xA2) => '2',
			chr(0xD9).chr(0xA3) => '3',
			chr(0xD9).chr(0xA4) => '4',
			chr(0xD9).chr(0xA5) => '5',
			chr(0xD9).chr(0xA6) => '6',
			chr(0xD9).chr(0xA7) => '7',
			chr(0xD9).chr(0xA8) => '8',
			chr(0xD9).chr(0xA9) => '9',
		);
	}

	// FIXME: also add : https://gist.github.com/geminorum/8aaa2e5740550984f77c415f7aa474d3
	// FIXME: @SEE: [wp-Typography](https://code.mundschenk.at/wp-typography/)
	public static function cleanupSubstitution( $string )
	{
		return strtr( $string, self::$maps['sub'] );
	}

	// adopted from `wp_replace_in_html_tags()`
	public static function cleanupPersianHTML( $html )
	{
		$changed = FALSE;
		$textarr = self::splitHTML( $html );

		$groups = array(
			'pre'     => self::pairsPre(),
			'arabic'  => self::pairsArabic(),
			'numbers' => self::pairsPersianNumbers(),
		);

		// loop through delimiters (elements) only.
		for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {

			foreach ( $groups as $pairs ) {

				foreach ( array_keys( $pairs ) as $needle ) {

					if ( FALSE !== strpos( $textarr[$i], $needle ) ) {
						$textarr[$i] = strtr( $textarr[$i], $pairs );
						$changed = TRUE;
						break; // after one strtr() break out of the foreach loop and look at next element.
					}
				}
			}

			// FIXME: make this strtr() comp
			$textarr[$i] = self::cleanupPersianChars( $textarr[$i] );
			$textarr[$i] = self::cleanupZWNJ( $textarr[$i] );
			$changed = TRUE;
		}

		return $changed ? implode( $textarr ) : $html;
	}

	private static function persian_html_legacy_cb( $matches )
	{
		return isset( $matches[1] ) ? self::cleanupPersian( $matches[1] ) : $matches[0];
	}

	public static function cleanupPersianHTMLLegacy( $html )
	{
		// $pattern = '/(?:&#\d{2,4};)|((?:\&nbsp\;)*\d+(?:\&nbsp\;)*\d*\.*(?:\&nbsp\;)*\d*(?:\&nbsp\;)*\d*)|(?:[a-z](?:[\x00-\x3B\x3D-\x7F]|<\s*[^>]+>)*)|<\s*[^>]+>/i';
		$pattern = '/(?:&#\d{2,4};)|(\d+[\.\d]*)|(?:[a-z](?:[\x00-\x3B\x3D-\x7F]|<\s*[^>]+>)*)|<\s*[^>]+>/iu';

		return preg_replace_callback( $pattern, array( __CLASS__, 'persian_html_legacy_cb' ), $html );
	}

	private static function mapSpecials()
	{
		return array(
			array(
				array(
					"\xD8\x86", // Arabic-Indic Cube Root U+0606
					"\xD8\x87", // Arabic-Indic Fourth Root U+0607
					"\xD8\x88", // Arabic Ray U+0608
					"\xD8\x89", // Arabic-Indic Per Mille Sign U+0609
					"\xD8\x8A", // Arabic-Indic Per Ten Thousand Sign U+060A
					"\xD8\x8D", // Arabic Date Separator U+060D // FIMXE: must repelace
					"\xD8\x8E", // Arabic Poetic Verse Sign U+060E
					"\xD8\x90", // Arabic Sign Sallallahou Alayhe Wassallam U+0610
					"\xD8\x91", // Arabic Sign Alayhe Assallam U+0611
					"\xD8\x92", // Arabic Sign Rahmatullah Alayhe U+0612
					"\xD8\x93", // Arabic Sign Radi Allahou Anhu U+0613
					"\xD8\x94", // Arabic Sign Takhallus U+0614
					"\xD8\x95", // Arabic Small High Tah U+0615
					"\xD8\x96", // Arabic Small High Ligature Alef with Lam with Yeh U+0616

					"\xD8\x98", // Arabic Small Fatha U+0618
					"\xD8\x99", // Arabic Small Damma U+0619
					"\xD8\x9A", // Arabic Small Kasra U+061A
					"\xD8\x9E", // Arabic Triple Dot Punctuation Mark U+061E
					"\xD9\x96", // Arabic Subscript Alef U+0656
					"\xD9\x97", // Arabic Inverted Damma U+0657
					"\xD9\x98", // Arabic Mark Noon Ghunna U+0658
					"\xD9\x99", // Arabic Zwarakay U+0659
					"\xD9\x9A", // Arabic Vowel Sign Small V Above U+065A
					"\xD9\x9B", // Arabic Vowel Sign Inverted Small V Above U+065B
					"\xD9\x9C", // Arabic Vowel Sign Dot Below U+065C
					"\xD9\x9D", // Arabic Reversed Damma U+065D
					"\xD9\x9E", // Arabic Fatha with Two Dots U+065E
					"\xD9\x9F", // Arabic Wavy Hamza Below U+065F

					// "\xD9\xAA", // Arabic Percent Sign U+066A
					"\xD9\xAC", // Arabic Thousands Separator U+066C // FIMXE: must repelace
					"\xD9\xAD", // Arabic Five Pointed Star U+066D // FIMXE: must repelace

					"\xDB\x81", // Arabic Letter Heh Goal U+06C1
					"\xDB\x82", // Arabic Letter Heh Goal with Hamza Above U+06C2 // FIMXE: must repelace
					"\xDB\x83", // Arabic Letter Teh Marbuta Goal U+06C3 // FIMXE: must repelace

					"\xDB\x94", // Arabic Full Stop U+06D4
					"\xDB\x96", // Arabic Small High Ligature Sad with Lam with Alef Maksura U+06D6
					"\xDB\x97", // Arabic Small High Ligature Qaf with Lam with Alef Maksura U+06D7
					"\xDB\x98", // Arabic Small High Meem Initial Form U+06D8
					"\xDB\x99", // Arabic Small High Lam Alef U+06D9
					"\xDB\x9A", // Arabic Small High Jeem U+06DA
					"\xDB\x9B", // Arabic Small High Three Dots U+06DB
					"\xDB\x9C", // Arabic Small High Seen U+06DC
					"\xDB\x9E", // Arabic Start of Rub El Hizb U+06DE
					"\xDB\x9F", // Arabic Small High Rounded Zero U+06DF
					"\xDB\xA0", // Arabic Small High Upright Rectangular Zero U+06E0
					"\xDB\xA1", // Arabic Small High Dotless Head of Khah U+06E1
					"\xDB\xA2", // Arabic Small High Meem Isolated Form U+06E2
					"\xDB\xA3", // Arabic Small Low Seen U+06E3
					"\xDB\xA4", // Arabic Small High Madda U+06E4
					"\xDB\xA5", // Arabic Small Waw U+06E5
					"\xDB\xA6", // Arabic Small Yeh U+06E6
					"\xDB\xA7", // Arabic Small High Yeh U+06E7
					"\xDB\xA8", // Arabic Small High Noon U+06E8

					"\xDB\xA9", // Arabic Place of Sajdah U+06E9
					"\xDB\xAA", // Arabic Empty Centre Low Stop U+06EA
					"\xDB\xAB", // Arabic Empty Centre High Stop U+06EB
					"\xDB\xAC", // Arabic Rounded High Stop with Filled Centre U+06EC
					"\xDB\xAD", // Arabic Small Low Meem U+06ED
					"\xDB\xAE", // Arabic Letter Dal with Inverted V U+06EE
					"\xDB\xAF", // Arabic Letter Reh with Inverted V U+06EF

					"\xEF\xAE\xA7", // Arabic Letter Heh Goal Final Form U+FBA7
					"\xEF\xAE\xB2", // Arabic Symbol Dot Above U+FBB2
					"\xEF\xAE\xB3", // Arabic Symbol Dot Below U+FBB3
					"\xEF\xAE\xB4", // Arabic Symbol Two Dots Above U+FBB4
					"\xEF\xAE\xB5", // Arabic Symbol Two Dots Below U+FBB5
					"\xEF\xAE\xB6", // Arabic Symbol Three Dots Above U+FBB6
					"\xEF\xAE\xB7", // Arabic Symbol Three Dots Below U+FBB7
					"\xEF\xAE\xB8", // Arabic Symbol Three Dots Pointing Downwards Above U+FBB8
					"\xEF\xAE\xB9", // Arabic Symbol Three Dots Pointing Downwards Below U+FBB9
					"\xEF\xAE\xBA", // Arabic Symbol Four Dots Above U+FBBA
					"\xEF\xAE\xBB", // Arabic Symbol Four Dots Below U+FBBB
					"\xEF\xAE\xBC", // Arabic Symbol Double Vertical Bar Below U+FBBC
					"\xEF\xAE\xBD", // Arabic Symbol Two Dots Vertically Above U+FBBD
					"\xEF\xAE\xBE", // Arabic Symbol Two Dots Vertically Below U+FBBE
					"\xEF\xAE\xBF", // Arabic Symbol Ring U+FBBF
					"\xEF\xAF\x80", // Arabic Symbol Small Tah Above U+FBC0
					"\xEF\xAF\x81", // Arabic Symbol Small Tah Below U+FBC1

					// FIMXE: must repelace
					"\xEF\xB1\x9E", // Arabic Ligature Shadda with Dammatan Isolated Form U+FC5E
					"\xEF\xB1\x9F", // Arabic Ligature Shadda with Kasratan Isolated Form U+FC5F
					"\xEF\xB1\xA0", // Arabic Ligature Shadda with Fatha Isolated Form U+FC60
					"\xEF\xB1\xA1", // Arabic Ligature Shadda with Damma Isolated Form U+FC61
					"\xEF\xB1\xA2", // Arabic Ligature Shadda with Kasra Isolated Form U+FC62
					"\xEF\xB1\xA3", // Arabic Ligature Shadda with Superscript Alef Isolated Form U+FC63
					"\xEF\xB9\xB0", // Arabic Fathatan Isolated Form U+FE70
					"\xEF\xB9\xB1", // Arabic Tatweel with Fathatan Above U+FE71
					"\xEF\xB9\xB2", // Arabic Dammatan Isolated Form U+FE72
					"\xEF\xB9\xB3", // Arabic Tail Fragment U+FE73
					"\xEF\xB9\xB4", // Arabic Kasratan Isolated Form U+FE74
					"\xEF\xB9\xB6", // Arabic Fatha Isolated Form U+FE76
					"\xEF\xB9\xB7", // Arabic Fatha Medial Form U+FE77
					"\xEF\xB9\xB8", // Arabic Damma Isolated Form U+FE78
					"\xEF\xB9\xB9", // Arabic Damma Medial Form U+FE79
					"\xEF\xB9\xBA", // Arabic Kasra Isolated Form U+FE7A
					"\xEF\xB9\xBB", // Arabic Kasra Medial Form U+FE7B
					"\xEF\xB9\xBC", // Arabic Shadda Isolated Form U+FE7C
					"\xEF\xB9\xBD", // Arabic Shadda Medial Form U+FE7D
					"\xEF\xB9\xBE", // Arabic Sukun Isolated Form U+FE7E
					"\xEF\xB9\xBF", // Arabic Sukun Medial Form U+FE7F
				),
				''
			),
		);
	}

	// @SOURCE: [intuxicated/PersianChar: Persian Char Conversion](https://github.com/intuxicated/PersianChar)
	private static function mapPersianChars()
	{
		return array(
			array(
				array(
					"\xD9\xB2", // 'ٲ', // Arabic Letter Alef with Wavy Hamza Above U+0672
					"\xD9\xB5", // 'ٵ', // Arabic Letter High Hamza Alef U+0675
					"\xEF\xBA\x83", // 'ﺃ', // Arabic Letter Alef with Hamza Above Isolated Form U+FE83
					"\xEF\xBA\x84", // 'ﺄ', // Arabic Letter Alef with Hamza Above Final Form U+FE84
				),
				"\xD8\xA3", // 'أ', // Arabic Letter Alef with Hamza Above U+0623
			),
			array(
				array(
					"\xD9\xB3", // 'ٳ', // Arabic Letter Alef with Wavy Hamza Below U+0673
					"\xEF\xBA\x87", // 'ﺇ', // Arabic Letter Alef with Hamza Below Isolated Form U+FE87
					"\xEF\xBA\x88", // 'ﺈ', // Arabic Letter Alef with Hamza Below Final Form U+FE88
				),
				"\xD8\xA5", // 'إ', // Arabic Letter Alef with Hamza Below U+0625
			),
			array(
				array(
					"\xD9\xB1", // 'ٱ', // Arabic Letter Alef Wasla U+0671
					"\xDD\xB3", // 'ݳ', // Arabic Letter Alef with Extended Arabic-Indic Digit Two Above U+0773
					"\xDD\xB4", // 'ݴ', // Arabic Letter Alef with Extended Arabic-Indic Digit Three Above U+0774
					"\xEF\xAD\x90", // 'ﭐ', // Arabic Letter Alef Wasla Isolated Form U+FB50
					"\xEF\xAD\x91", // 'ﭑ', // Arabic Letter Alef Wasla Final Form U+FB51
					"\xEF\xBA\x8D", // 'ﺍ', // Arabic Letter Alef Isolated Form U+FE8D
					"\xEF\xBA\x8E", // 'ﺎ', // Arabic Letter Alef Final Form U+FE8E
					"\xEF\xB4\xBC", // 'ﴼ', // Arabic Ligature Alef with Fathatan Final Form U+FD3C
					"\xEF\xB4\xBD", // 'ﴽ', // Arabic Ligature Alef with Fathatan Isolated Form U+FD3D
					"\xF0\x9E\xBA\x80", // '𞺀', // Arabic Mathematical Looped Alef U+1EE80
					"\xF0\x9E\xB8\x80", // '𞸀', // Arabic Mathematical Alef U+1EE00
				),
				"\xD8\xA7", // 'ا', // Arabic Letter Alef U+0627
			),
			array(
				array(
					"\xD9\xAE", // 'ٮ', // Arabic Letter Dotless Beh U+066E
					"\xDD\x95", // 'ݕ', // Arabic Letter Beh with Inverted Small V Below U+0755
					"\xDD\x96", // 'ݖ', // Arabic Letter Beh with Small V U+0756
					"\xEF\xAD\x92", // 'ﭒ', // Arabic Letter Beeh Isolated Form U+FB52
					"\xEF\xAD\x93", // 'ﭓ', // Arabic Letter Beeh Final Form U+FB53
					"\xEF\xAD\x94", // 'ﭔ', // Arabic Letter Beeh Initial Form U+FB54
					"\xEF\xAD\x95", // 'ﭕ', // Arabic Letter Beeh Medial Form U+FB55
					"\xEF\xBA\x8F", // 'ﺏ', // Arabic Letter Beh Isolated Form U+FE8F
					"\xEF\xBA\x90", // 'ﺐ', // Arabic Letter Beh Final Form U+FE90
					"\xEF\xBA\x91", // 'ﺑ', // Arabic Letter Beh Initial Form U+FE91
					"\xEF\xBA\x92", // 'ﺒ', // Arabic Letter Beh Medial Form U+FE92
					"\xF0\x9E\xB8\x81", // '𞸁', // Arabic Mathematical Beh U+1EE01
					"\xF0\x9E\xB8\x9C", // '𞸜', // Arabic Mathematical Dotless Beh U+1EE1C
					"\xF0\x9E\xB8\xA1", // '𞸡', // Arabic Mathematical Initial Beh U+1EE21
					"\xF0\x9E\xB9\xA1", // '𞹡', // Arabic Mathematical Stretched Beh U+1EE61
					"\xF0\x9E\xB9\xBC", // '𞹼', // Arabic Mathematical Stretched Dotless Beh U+1EE7C
					"\xF0\x9E\xBA\x81", // '𞺁', // Arabic Mathematical Looped Beh U+1EE81
					"\xF0\x9E\xBA\xA1", // '𞺡', // Arabic Mathematical Double-Struck Beh U+1EEA1
				),
				"\xD8\xA8", // 'ب', // Arabic Letter Beh U+0628
			),
			array(
				array(
					"\xDA\x80", // 'ڀ', // Arabic Letter Beheh U+0680
					"\xDD\x90", // 'ݐ', // Arabic Letter Beh with Three Dots Horizontally Below U+0750
					"\xDD\x94", // 'ݔ', // Arabic Letter Beh with Two Dots Below and Dot Above U+0754
					"\xEF\xAD\x96", // 'ﭖ', // Arabic Letter Peh Isolated Form U+FB56
					"\xEF\xAD\x97", // 'ﭗ', // Arabic Letter Peh Final Form U+FB57
					"\xEF\xAD\x98", // 'ﭘ', // Arabic Letter Peh Initial Form U+FB58
					"\xEF\xAD\x99", // 'ﭙ', // Arabic Letter Peh Medial Form U+FB59
					"\xEF\xAD\x9A", // 'ﭚ', // Arabic Letter Beheh Isolated Form U+FB5A
					"\xEF\xAD\x9B", // 'ﭛ', // Arabic Letter Beheh Final Form U+FB5B
					"\xEF\xAD\x9C", // 'ﭜ', // Arabic Letter Beheh Initial Form U+FB5C
					"\xEF\xAD\x9D", // 'ﭝ', // Arabic Letter Beheh Medial Form U+FB5D
				),
				"\xD9\xBE", // 'پ', // Arabic Letter Peh U+067E
			),
			array(
				array(
					"\xD9\xB9", // 'ٹ', // Arabic Letter Tteh U+0679
					"\xD9\xBA", // 'ٺ', // Arabic Letter Tteheh U+067A
					"\xD9\xBB", // 'ٻ', // Arabic Letter Beeh U+067B
					"\xD9\xBC", // 'ټ', // Arabic Letter Teh with Ring U+067C
					"\xDD\x93", // 'ݓ', // Arabic Letter Beh with Three Dots Pointing Upwards Below and Two Dots Above U+0753
					"\xEF\xAD\x9E", // 'ﭞ', // Arabic Letter Tteheh Isolated Form U+FB5E
					"\xEF\xAD\x9F", // 'ﭟ', // Arabic Letter Tteheh Final Form U+FB5F
					"\xEF\xAD\xA0", // 'ﭠ', // Arabic Letter Tteheh Initial Form U+FB60
					"\xEF\xAD\xA1", // 'ﭡ', // Arabic Letter Tteheh Medial Form U+FB61
					"\xEF\xAD\xA2", // 'ﭢ', // Arabic Letter Teheh Isolated Form U+FB62
					"\xEF\xAD\xA3", // 'ﭣ', // Arabic Letter Teheh Final Form U+FB63
					"\xEF\xAD\xA4", // 'ﭤ', // Arabic Letter Teheh Initial Form U+FB64
					"\xEF\xAD\xA5", // 'ﭥ', // Arabic Letter Teheh Medial Form U+FB65
					"\xEF\xAD\xA6", // 'ﭦ', // Arabic Letter Tteh Isolated Form U+FB66
					"\xEF\xAD\xA7", // 'ﭧ', // Arabic Letter Tteh Final Form U+FB67
					"\xEF\xAD\xA8", // 'ﭨ', // Arabic Letter Tteh Initial Form U+FB68
					"\xEF\xAD\xA9", // 'ﭩ', // Arabic Letter Tteh Medial Form U+FB69
					"\xEF\xBA\x95", // 'ﺕ', // Arabic Letter Teh Isolated Form U+FE95
					"\xEF\xBA\x96", // 'ﺖ', // Arabic Letter Teh Final Form U+FE96
					"\xEF\xBA\x97", // 'ﺗ', // Arabic Letter Teh Initial Form U+FE97
					"\xEF\xBA\x98", // 'ﺘ', // Arabic Letter Teh Medial Form U+FE98
					"\xF0\x9E\xB8\x95", // '𞸕', // Arabic Mathematical Teh U+1EE15
					"\xF0\x9E\xB8\xB5", // '𞸵', // Arabic Mathematical Initial Teh U+1EE35
					"\xF0\x9E\xB9\xB5", // '𞹵', // Arabic Mathematical Stretched Teh U+1EE75
					"\xF0\x9E\xBA\x95", // '𞺕', // Arabic Mathematical Looped Teh U+1EE95
					"\xF0\x9E\xBA\xB5", // '𞺵', // Arabic Mathematical Double-Struck Teh U+1EEB5
				),
				"\xD8\xAA", // 'ت', // Arabic Letter Teh U+062A
			),
			array(
				array(
					"\xD9\xBD", // 'ٽ', // Arabic Letter Teh with Three Dots Above Downwards U+067D
					"\xD9\xBF", // 'ٿ', // Arabic Letter Teheh U+067F
					"\xDD\x91", // 'ݑ', // Arabic Letter Beh with Dot Below and Three Dots Above U+0751
					"\xEF\xBA\x99", // 'ﺙ', // Arabic Letter Theh Isolated Form U+FE99
					"\xEF\xBA\x9A", // 'ﺚ', // Arabic Letter Theh Final Form U+FE9A
					"\xEF\xBA\x9B", // 'ﺛ', // Arabic Letter Theh Initial Form U+FE9B
					"\xEF\xBA\x9C", // 'ﺜ', // Arabic Letter Theh Medial Form U+FE9C
					"\xF0\x9E\xB8\x96", // '𞸖', // Arabic Mathematical Theh U+1EE16
					"\xF0\x9E\xB8\xB6", // '𞸶', // Arabic Mathematical Initial Theh U+1EE36
					"\xF0\x9E\xB9\xB6", // '𞹶', // Arabic Mathematical Stretched Theh U+1EE76
					"\xF0\x9E\xBA\x96", // '𞺖', // Arabic Mathematical Looped Theh U+1EE96
					"\xF0\x9E\xBA\xB6", // '𞺶', // Arabic Mathematical Double-Struck Theh U+1EEB6
				),
				"\xD8\xAB", // 'ث', // Arabic Letter Theh U+062B
			),
			array(
				array(
					"\xDA\x83", // 'ڃ', // Arabic Letter Nyeh U+0683
					"\xDA\x84", // 'ڄ', // Arabic Letter Dyeh U+0684
					"\xEF\xAD\xB2", // 'ﭲ', // Arabic Letter Dyeh Isolated Form U+FB72
					"\xEF\xAD\xB3", // 'ﭳ', // Arabic Letter Dyeh Final Form U+FB73
					"\xEF\xAD\xB4", // 'ﭴ', // Arabic Letter Dyeh Final Form U+FB73
					"\xEF\xAD\xB5", // 'ﭵ', // Arabic Letter Dyeh Medial Form U+FB75
					"\xEF\xAD\xB6", // 'ﭶ', // Arabic Letter Dyeh Medial Form U+FB75
					"\xEF\xAD\xB7", // 'ﭷ', // Arabic Letter Nyeh Final Form U+FB77
					"\xEF\xAD\xB8", // 'ﭸ', // Arabic Letter Nyeh Initial Form U+FB78
					"\xEF\xAD\xB9", // 'ﭹ', // Arabic Letter Nyeh Medial Form U+FB79
					"\xEF\xBA\x9D", // 'ﺝ', // Arabic Letter Jeem Isolated Form U+FE9D
					"\xEF\xBA\x9E", // 'ﺞ', // Arabic Letter Jeem Final Form U+FE9E
					"\xEF\xBA\x9F", // 'ﺟ', // Arabic Letter Jeem Final Form U+FE9E
					"\xEF\xBA\xA0", // 'ﺠ', // Arabic Letter Jeem Medial Form U+FEA0
					"\xF0\x9E\xB8\x82", // '𞸂', // Arabic Mathematical Jeem U+1EE02
					"\xF0\x9E\xB8\xA2", // '𞸢', // Arabic Mathematical Initial Jeem U+1EE22
					"\xF0\x9E\xB9\x82", // '𞹂', // Arabic Mathematical Tailed Jeem U+1EE42
					"\xF0\x9E\xB9\xA2", // '𞹢', // Arabic Mathematical Stretched Jeem U+1EE62
					"\xF0\x9E\xBA\x82", // '𞺂', // Arabic Mathematical Looped Jeem U+1EE82
					"\xF0\x9E\xBA\xA2", // '𞺢', // Arabic Mathematical Double-Struck Jeem U+1EEA2
				),
				"\xD8\xAC", // 'ج', // Arabic Letter Jeem U+062C
			),
			array(
				array(
					"\xDA\x87", // 'ڇ', // Arabic Letter Tcheheh U+0687
					"\xDA\xBF", // 'ڿ', // Arabic Letter Tcheh with Dot Above U+06BF
					"\xDD\x98", // 'ݘ', // Arabic Letter Hah with Three Dots Pointing Upwards Below U+0758
					"\xEF\xAD\xBA", // 'ﭺ', // Arabic Letter Tcheh Isolated Form U+FB7A
					"\xEF\xAD\xBB", // 'ﭻ', // Arabic Letter Tcheh Final Form U+FB7B
					"\xEF\xAD\xBC", // 'ﭼ', // Arabic Letter Tcheh Initial Form U+FB7C
					"\xEF\xAD\xBD", // 'ﭽ', // Arabic Letter Tcheh Medial Form U+FB7D
					"\xEF\xAD\xBE", // 'ﭾ', // Arabic Letter Tcheheh Isolated Form U+FB7E
					"\xEF\xAD\xBF", // 'ﭿ', // Arabic Letter Tcheheh Final Form U+FB7F
					"\xEF\xAE\x80", // 'ﮀ', // Arabic Letter Tcheheh Initial Form U+FB80
					"\xEF\xAE\x81", // 'ﮁ', // Arabic Letter Tcheheh Medial Form U+FB81
					// FIXME: search for Mathematical Tcheheh
				),
				"\xDA\x86", // 'چ', // Arabic Letter Tcheh U+0686
			),
			array(
				array(
					"\xDA\x81", // 'ځ', // Arabic Letter Hah with Hamza Above U+0681 // FIXME: maybe must separate
					"\xDD\xAE", // 'ݮ', // Arabic Letter Hah with Small Arabic Letter Tah Below U+076E
					"\xDD\xAF", // 'ݯ', // Arabic Letter Hah with Small Arabic Letter Tah and Two Dots U+076F
					"\xDD\xB2", // 'ݲ', // Arabic Letter Hah with Small Arabic Letter Tah Above U+0772
					"\xDD\xBC", // 'ݼ', // Arabic Letter Hah with Extended Arabic-Indic Digit Four Below U+077C
					"\xEF\xBA\xA1", // 'ﺡ', // Arabic Letter Hah Isolated Form U+FEA1
					"\xEF\xBA\xA2", // 'ﺢ', // Arabic Letter Hah Final Form U+FEA2
					"\xEF\xBA\xA3", // 'ﺣ', // Arabic Letter Hah Initial Form U+FEA3
					"\xEF\xBA\xA4", // 'ﺤ', // Arabic Letter Hah Medial Form U+FEA4
					"\xF0\x9E\xB8\x87", // '𞸇', // Arabic Mathematical Hah U+1EE07
					"\xF0\x9E\xB8\xA7", // '𞸧', // Arabic Mathematical Initial Hah U+1EE27
					"\xF0\x9E\xB9\x87", // '𞹇', // Arabic Mathematical Tailed Hah U+1EE47
					"\xF0\x9E\xB9\xA7", // '𞹧', // Arabic Mathematical Stretched Hah U+1EE67
					"\xF0\x9E\xBA\x87", // '𞺇', // Arabic Mathematical Looped Hah U+1EE87
					"\xF0\x9E\xBA\xA7", // '𞺧', // Arabic Mathematical Double-Struck Hah U+1EEA7
				),
				"\xD8\xAD", // 'ح', // Arabic Letter Hah U+062D
			),
			array(
				array(
					"\xDA\x82", // 'ڂ', // Arabic Letter Hah with Two Dots Vertical Above U+0682
					"\xDA\x85", // 'څ', // Arabic Letter Hah with Three Dots Above U+0685
					"\xDD\x97", // 'ݗ', // Arabic Letter Hah with Two Dots Above U+0757
					"\xEF\xBA\xA5", // 'ﺥ', // Arabic Letter Khah Isolated Form U+FEA5
					'ﺦ',
					'ﺧ',
					'ﺨ',
					'𞸗',
					'𞸷',
					'𞹗',
					'𞹷',
					'𞺗',
					'𞺷',
				),
				"\xD8\xAE", // 'خ', // Arabic Letter Khah U+062E
			),
			array(
				array(
					'ڈ',
					'ډ',
					'ڊ',
					'ڌ',
					'ڍ',
					'ڎ',
					'ڏ',
					'ڐ',
					'ݙ',
					'ݚ',
					'ﺩ',
					'ﺪ',
					'𞺣',
					'ﮂ',
					'ﮃ',
					'ﮈ',
					'ﮉ',
					"\xF0\x9E\xB8\x83", // '𞸃', // Arabic Mathematical Dal U+1EE03
					"\xF0\x9E\xBA\x83", // '𞺃', // Arabic Mathematical Looped Dal U+1EE83
				),
				"\xD8\xAF", // 'د', // Arabic Letter Dal U+062F
			),
			array(
				array(
					'ﱛ',
					'ﱝ',
					'ﺫ',
					'ﺬ',
					'𞸘',
					'𞺘',
					'𞺸',
					'ﮄ',
					'ﮅ',
					'ﮆ',
					'ﮇ',
				),
				"\xD8\xB0", // 'ذ', // Arabic Letter Thal U+0630
			),
			array(
				array(
					'٫',
					'ڑ',
					'ڒ',
					'ړ',
					'ڔ',
					'ڕ',
					'ږ',
					'ݛ',
					'ݬ',
					'ﮌ',
					'ﮍ',
					'ﱜ',
					'ﺭ',
					'ﺮ',
					'𞸓',
					'𞺓',
					'𞺳',
				),
				"\xD8\xB1", // 'ر', // Arabic Letter Reh U+0631
			),
			array(
				array(
					'ڗ',
					'ڙ',
					'ݫ',
					'ݱ',
					'ﺯ',
					'ﺰ',
					'𞸆',
					'𞺆',
					'𞺦',
					// http://unicode-table.com/en/0617/
				),
				"\xD8\xB2", // 'ز', // Arabic Letter Zain U+0632
			),
			array(
				array(
					'ﮊ',
					'ﮋ',
					'ژ',
				),
				"\xDA\x98", // 'ژ', // Arabic Letter Jeh U+0698
			),
			array(
				array(
					'ښ',
					'ݽ',
					'ݾ',
					'ﺱ',
					'ﺲ',
					'ﺳ',
					'ﺴ',
					'𞸎',
					'𞸮',
					'𞹎',
					'𞹮',
					'𞺎',
					'𞺮',
				),
				"\xD8\xB3", // 'س', // Arabic Letter Seen U+0633
			),
			array(
				array(
					'ڛ',
					'ۺ',
					'ݜ',
					'ݭ',
					'ݰ',
					'ﺵ',
					'ﺶ',
					'ﺷ',
					'ﺸ',
					'𞸔',
					'𞸴',
					'𞹔',
					'𞹴',
					'𞺔',
					'𞺴',
				),
				"\xD8\xB4", // 'ش', // Arabic Letter Sheen U+0634
			),
			array(
				array(
					'ڝ',
					'ﺹ',
					'ﺺ',
					'ﺻ',
					'ﺼ',
					'𞸑',
					'𞹑',
					'𞸱',
					'𞹱',
					'𞺑',
					'𞺱',
				),
				"\xD8\xB5", // 'ص', // Arabic Letter Sad U+0635
			),
			array(
				array(
					'ڞ',
					'ۻ',
					'ﺽ',
					'ﺾ',
					'ﺿ',
					'ﻀ',
					'𞸙',
					'𞸹',
					'𞹙',
					'𞹹',
					'𞺙',
					'𞺹',
				),
				"\xD8\xB6", // 'ض', // Arabic Letter Dad U+0636
			),
			array(
				array(
					'ﻁ',
					'ﻂ',
					'ﻃ',
					'ﻄ',
					'𞸈',
					'𞹨',
					'𞺈',
					'𞺨',
				),
				"\xD8\xB7", // 'ط', // Arabic Letter Tah U+0637
			),
			array(
				array(
					'ڟ',
					'ﻅ',
					'ﻆ',
					'ﻇ',
					'ﻈ',
					'𞸚',
					'𞹺',
					'𞺚',
					'𞺺',
				),
				"\xD8\xB8", // 'ظ', // Arabic Letter Zah U+0638
			),
			array(
				array(
					'؏',
					'ڠ',
					'ﻉ',
					'ﻊ',
					'ﻋ',
					'ﻌ',
					'𞸏',
					'𞸯',
					'𞹏',
					'𞹯',
					'𞺏',
					'𞺯',
				),
				"\xD8\xB9", // 'ع', // Arabic Letter Ain U+0639
			),
			array(
				array(
					'ۼ',
					'ݝ',
					'ݞ',
					'ݟ',
					'ﻍ',
					'ﻎ',
					'ﻏ',
					'ﻐ',
					'𞸛',
					'𞸻',
					'𞹛',
					'𞹻',
					'𞺛',
					'𞺻',
				),
				"\xD8\xBA", // 'غ', // Arabic Letter Ghain U+063A
			),
			array(
				array(
					"\xD8\x8B", // '؋', // Afghani Sign U+060B
					"\xDA\xA1", // 'ڡ', // Arabic Letter Dotless Feh U+06A1
					"\xDA\xA2", // 'ڢ', // Arabic Letter Feh with Dot Moved Below U+06A2
					"\xDA\xA3", // 'ڣ', // Arabic Letter Feh with Dot Below U+06A3
					"\xDA\xA4", // 'ڤ', // Arabic Letter Veh U+06A4
					"\xDA\xA5", // 'ڥ', // Arabic Letter Feh with Three Dots Below U+06A5
					"\xDA\xA6", // 'ڦ', // Arabic Letter Peheh U+06A6
					"\xDD\xA0", // 'ݠ', // Arabic Letter Feh with Two Dots Below U+0760
					"\xDD\xA1", // 'ݡ', // Arabic Letter Feh with Three Dots Pointing Upwards Below U+0761
					"\xEF\xAD\xAA", // 'ﭪ', // Arabic Letter Veh Isolated Form U+FB6A
					"\xEF\xAD\xAB", // 'ﭫ', // Arabic Letter Veh Final Form U+FB6B
					"\xEF\xAD\xAC", // 'ﭬ', // Arabic Letter Veh Initial Form U+FB6C
					"\xEF\xAD\xAD", // 'ﭭ', // Arabic Letter Veh Medial Form U+FB6D
					"\xEF\xAD\xAE", // 'ﭮ', // Arabic Letter Peheh Isolated Form U+FB6E
					"\xEF\xAD\xAF", // 'ﭯ', // Arabic Letter Peheh Final Form U+FB6F
					"\xEF\xAD\xB0", // 'ﭰ', // Arabic Letter Peheh Initial Form U+FB70
					"\xEF\xAD\xB1", // 'ﭱ', // Arabic Letter Peheh Medial Form U+FB71
					"\xEF\xBB\x91", // 'ﻑ', // Arabic Letter Feh Isolated Form U+FED1
					"\xEF\xBB\x92", // 'ﻒ', // Arabic Letter Feh Final Form U+FED2
					"\xEF\xBB\x93", // 'ﻓ', // Arabic Letter Feh Initial Form U+FED3
					"\xEF\xBB\x94", // 'ﻔ', // Arabic Letter Feh Medial Form U+FED4
					"\xF0\x9E\xB8\x90", // '𞸐', // Arabic Mathematical Feh U+1EE10
					"\xF0\x9E\xB8\x9E", // '𞸞', // Arabic Mathematical Dotless Feh U+1EE1E
					"\xF0\x9E\xB8\xB0", // '𞸰', // Arabic Mathematical Initial Feh U+1EE30
					"\xF0\x9E\xB9\xB0", // '𞹰', // Arabic Mathematical Stretched Feh U+1EE70
					"\xF0\x9E\xB9\xBE", // '𞹾', // Arabic Mathematical Stretched Dotless Feh U+1EE7E
					"\xF0\x9E\xBA\x90", // '𞺐', // Arabic Mathematical Looped Feh U+1EE90
					"\xF0\x9E\xBA\xB0", // '𞺰', // Arabic Mathematical Double-Struck Feh U+1EEB0
				),
				"\xD9\x81", // 'ف', // Arabic Letter Feh U+0641
			),
			array(
				array(
					"\xD9\xAF", // 'ٯ', // Arabic Letter Dotless Qaf U+066F
					"\xDA\xA7", // 'ڧ', // Arabic Letter Qaf with Dot Above U+06A7
					"\xDA\xA8", // 'ڨ', // Arabic Letter Qaf with Three Dots Above U+06A8
					"\xEF\xBB\x95", // 'ﻕ', // Arabic Letter Qaf Isolated Form U+FED5
					"\xEF\xBB\x96", // 'ﻖ', // Arabic Letter Qaf Final Form U+FED6
					"\xEF\xBB\x97", // 'ﻗ', // Arabic Letter Qaf Initial Form U+FED7
					"\xEF\xBB\x98", // 'ﻘ', // Arabic Letter Qaf Medial Form U+FED8
					"\xF0\x9E\xB8\x92", // '𞸒', // Arabic Mathematical Qaf U+1EE12
					"\xF0\x9E\xB8\x9F", // '𞸟', // Arabic Mathematical Dotless Qaf U+1EE1F
					"\xF0\x9E\xB8\xB2", // '𞸲', // Arabic Mathematical Initial Qaf U+1EE32
					"\xF0\x9E\xB9\x92", // '𞹒', // Arabic Mathematical Tailed Qaf U+1EE52
					"\xF0\x9E\xB9\x9F", // '𞹟', // Arabic Mathematical Tailed Dotless Qaf U+1EE5F
					"\xF0\x9E\xB9\xB2", // '𞹲', // Arabic Mathematical Stretched Qaf U+1EE72
					"\xF0\x9E\xBA\x92", // '𞺒', // Arabic Mathematical Looped Qaf U+1EE92
					"\xF0\x9E\xBA\xB2", // '𞺲', // Arabic Mathematical Double-Struck Qaf U+1EEB2
					"\xD8\x88", // '؈', // Arabic Ray U+0608
				),
				"\xD9\x82", // 'ق', // Arabic Letter Qaf U+0642
			),
			array(
				array(
					"\xD8\xBB", // 'ػ', // Arabic Letter Keheh with Two Dots Above U+063B
					"\xD8\xBC", // 'ؼ', // Arabic Letter Keheh with Three Dots Below U+063C
					"\xD9\x83", // 'ك', // Arabic Letter Kaf U+0643
					"\xDA\xAA", // 'ڪ', // Arabic Letter Swash Kaf U+06AA
					"\xDA\xAB", // 'ګ', // Arabic Letter Kaf with Ring U+06AB
					"\xDA\xAC", // 'ڬ', // Arabic Letter Kaf with Dot Above U+06AC
					"\xDA\xAD", // 'ڭ', // Arabic Letter Ng U+06AD
					"\xDA\xAE", // 'ڮ', // Arabic Letter Kaf with Three Dots Below U+06AE
					"\xDD\xA2", // 'ݢ', // Arabic Letter Keheh with Dot Above U+0762
					"\xDD\xA3", // 'ݣ', // Arabic Letter Keheh with Three Dots Above U+0763
					"\xDD\xA4", // 'ݤ', // Arabic Letter Keheh with Three Dots Pointing Upwards Below U+0764
					"\xDD\xBF", // 'ݿ', // Arabic Letter Kaf with Two Dots Above U+077F
					"\xEF\xAE\x8E", // 'ﮎ', // Arabic Letter Keheh Isolated Form U+FB8E
					"\xEF\xAE\x8F", // 'ﮏ', // Arabic Letter Keheh Final Form U+FB8F
					"\xEF\xAE\x90", // 'ﮐ', // Arabic Letter Keheh Initial Form U+FB90
					"\xEF\xAE\x91", // 'ﮑ', // Arabic Letter Keheh Medial Form U+FB91
					"\xEF\xAF\x93", // 'ﯓ', // Arabic Letter Ng Isolated Form U+FBD3
					"\xEF\xAF\x94", // 'ﯔ', // Arabic Letter Ng Final Form U+FBD4
					"\xEF\xAF\x95", // 'ﯕ', // Arabic Letter Ng Initial Form U+FBD5
					"\xEF\xAF\x96", // 'ﯖ', // Arabic Letter Ng Medial Form U+FBD6
					"\xEF\xBB\x99", // 'ﻙ', // Arabic Letter Kaf Isolated Form U+FED9
					"\xEF\xBB\x9A", // 'ﻚ', // Arabic Letter Kaf Final Form U+FEDA
					"\xEF\xBB\x9B", // 'ﻛ', // Arabic Letter Kaf Initial Form U+FEDB
					"\xEF\xBB\x9C", // 'ﻜ', // Arabic Letter Kaf Medial Form U+FEDC
					"\xF0\x9E\xB8\x8A", // '𞸊', // Arabic Mathematical Kaf U+1EE0A
					"\xF0\x9E\xB8\xAA", // '𞸪', // Arabic Mathematical Initial Kaf U+1EE2A
					"\xF0\x9E\xB9\xAA", // '𞹪', // Arabic Mathematical Stretched Kaf U+1EE6A
				),
				"\xDA\xA9", // 'ک', // Arabic Letter Keheh U+06A9
			),
			array(
				array(
					"\xDA\xB0", // 'ڰ', // Arabic Letter Gaf with Ring U+06B0
					"\xDA\xB1", // 'ڱ', // Arabic Letter Ngoeh U+06B1
					"\xDA\xB2", // 'ڲ', // Arabic Letter Gaf with Two Dots Below U+06B2
					"\xDA\xB3", // 'ڳ', // Arabic Letter Gueh U+06B3
					"\xDA\xB4", // 'ڴ', // Arabic Letter Gaf with Three Dots Above U+06B4
					"\xEF\xAE\x92", // 'ﮒ', // Arabic Letter Gaf Isolated Form U+FB92
					"\xEF\xAE\x93", // 'ﮓ', // Arabic Letter Gaf Final Form U+FB93
					"\xEF\xAE\x94", // 'ﮔ', // Arabic Letter Gaf Initial Form U+FB94
					"\xEF\xAE\x95", // 'ﮕ', // Arabic Letter Gaf Medial Form U+FB95
					"\xEF\xAE\x96", // 'ﮖ', // Arabic Letter Gueh Isolated Form U+FB96
					"\xEF\xAE\x97", // 'ﮗ', // Arabic Letter Gueh Final Form U+FB97
					"\xEF\xAE\x98", // 'ﮘ', // Arabic Letter Gueh Initial Form U+FB98
					"\xEF\xAE\x99", // 'ﮙ', // Arabic Letter Gueh Medial Form U+FB99
					"\xEF\xAE\x9A", // 'ﮚ', // Arabic Letter Ngoeh Isolated Form U+FB9A
					"\xEF\xAE\x9B", // 'ﮛ', // Arabic Letter Ngoeh Final Form U+FB9B
					"\xEF\xAE\x9C", // 'ﮜ', // Arabic Letter Ngoeh Initial Form U+FB9C
					"\xEF\xAE\x9D", // 'ﮝ', // Arabic Letter Ngoeh Medial Form U+FB9D
				),
				"\xDA\xAF", // 'گ', // Arabic Letter Gaf U+06AF
			),
			array(
				array(
					"\xDA\xB5", // 'ڵ', // Arabic Letter Lam with Small V U+06B5
					"\xDA\xB6", // 'ڶ', // Arabic Letter Lam with Dot Above U+06B6
					"\xDA\xB7", // 'ڷ', // Arabic Letter Lam with Three Dots Above U+06B7
					"\xDA\xB8", // 'ڸ', // Arabic Letter Lam with Three Dots Below U+06B8
					"\xDD\xAA", // 'ݪ', // Arabic Letter Lam with Bar U+076A
					"\xEF\xBB\x9D", // 'ﻝ', // Arabic Letter Lam Isolated Form U+FEDD
					"\xEF\xBB\x9E", // 'ﻞ', // Arabic Letter Lam Final Form U+FEDE
					"\xEF\xBB\x9F", // 'ﻟ', // Arabic Letter Lam Initial Form U+FEDF
					"\xEF\xBB\xA0", // 'ﻠ', // Arabic Letter Lam Medial Form U+FEE0
					"\xF0\x9E\xB8\x8B", // '𞸋', // Arabic Mathematical Lam U+1EE0B
					"\xF0\x9E\xB8\xAB", // '𞸫', // Arabic Mathematical Initial Lam U+1EE2B
					"\xF0\x9E\xB9\x8B", // '𞹋', // Arabic Mathematical Tailed Lam U+1EE4B
					"\xF0\x9E\xBA\x8B", // '𞺋', // Arabic Mathematical Looped Lam U+1EE8B
					"\xF0\x9E\xBA\xAB", // '𞺫', // Arabic Mathematical Double-Struck Lam U+1EEAB
				),
				"\xD9\x84", // 'ل', // Arabic Letter Lam U+0644
			),
			array(
				array(
					"\xDB\xBE", // '۾', // Arabic Sign Sindhi Postposition Men U+06FE
					"\xDD\xA5", // 'ݥ', // Arabic Letter Meem with Dot Above U+0765
					"\xDD\xA6", // 'ݦ', // Arabic Letter Meem with Dot Below U+0766
					"\xEF\xBB\xA1", // 'ﻡ', // Arabic Letter Meem Isolated Form U+FEE1
					"\xEF\xBB\xA2", // 'ﻢ', // Arabic Letter Meem Final Form U+FEE2
					"\xEF\xBB\xA3", // 'ﻣ', // Arabic Letter Meem Initial Form U+FEE3
					"\xEF\xBB\xA4", // 'ﻤ', // Arabic Letter Meem Medial Form U+FEE4
					"\xF0\x9E\xB8\x8C", // '𞸌', // Arabic Mathematical Meem U+1EE0C
					"\xF0\x9E\xB8\xAC", // '𞸬', // Arabic Mathematical Initial Meem U+1EE2C
					"\xF0\x9E\xB9\xAC", // '𞹬', // Arabic Mathematical Stretched Meem U+1EE6C
					"\xF0\x9E\xBA\x8C", // '𞺌', // Arabic Mathematical Looped Meem U+1EE8C
					"\xF0\x9E\xBA\xAC", // '𞺬', // Arabic Mathematical Double-Struck Meem U+1EEAC
				),
				"\xD9\x85", // 'م', // Arabic Letter Meem U+0645
			),
			array(
				array(
					"\xDA\xB9", // 'ڹ', // Arabic Letter Noon with Dot Below U+06B9
					"\xDA\xBA", // 'ں', // Arabic Letter Noon Ghunna U+06BA
					"\xDA\xBB", // 'ڻ', // Arabic Letter Rnoon U+06BB
					"\xDA\xBC", // 'ڼ', // Arabic Letter Noon with Ring U+06BC
					"\xDA\xBD", // 'ڽ', // Arabic Letter Noon with Three Dots Above U+06BD
					"\xDD\xA7", // 'ݧ', // Arabic Letter Noon with Two Dots Below U+0767
					"\xDD\xA8", // 'ݨ', // Arabic Letter Noon with Small Tah U+0768
					"\xDD\xA9", // 'ݩ', // Arabic Letter Noon with Small V U+0769
					"\xEF\xAE\x9E", // 'ﮞ', // Arabic Letter Noon Ghunna Isolated Form U+FB9E
					"\xEF\xAE\x9F", // 'ﮟ', // Arabic Letter Noon Ghunna Final Form U+FB9F
					"\xEF\xAE\xA0", // 'ﮠ', // Arabic Letter Rnoon Isolated Form U+FBA0
					"\xEF\xAE\xA1", // 'ﮡ', // Arabic Letter Rnoon Final Form U+FBA1
					"\xEF\xBB\xA5", // 'ﻥ', // Arabic Letter Noon Isolated Form U+FEE5
					"\xEF\xBB\xA6", // 'ﻦ', // Arabic Letter Noon Final Form U+FEE6
					"\xEF\xBB\xA7", // 'ﻧ', // Arabic Letter Noon Initial Form U+FEE7
					"\xEF\xBB\xA8", // 'ﻨ', // Arabic Letter Noon Medial Form U+FEE8
					"\xF0\x9E\xB8\x8D", // '𞸍', // Arabic Mathematical Noon U+1EE0D
					"\xF0\x9E\xB8\x9D", // '𞸝', // Arabic Mathematical Dotless Noon U+1EE1D
					"\xF0\x9E\xB8\xAD", // '𞸭', // Arabic Mathematical Initial Noon U+1EE2D
					"\xF0\x9E\xB9\x8D", // '𞹍', // Arabic Mathematical Tailed Noon U+1EE4D
					"\xF0\x9E\xB9\x9D", // '𞹝', // Arabic Mathematical Tailed Dotless Noon U+1EE5D
					"\xF0\x9E\xB9\xAD", // '𞹭', // Arabic Mathematical Stretched Noon U+1EE6D
					"\xF0\x9E\xBA\x8D", // '𞺍', // Arabic Mathematical Looped Noon U+1EE8D
					"\xF0\x9E\xBA\xAD", // '𞺭', // Arabic Mathematical Double-Struck Noon U+1EEAD
				),
				"\xD9\x86", // 'ن', // Arabic Letter Noon U+0646
			),
			array(
				array(
					"\xD9\xB6", // 'ٶ', // Arabic Letter High Hamza Waw U+0676
					"\xD9\xB7", // 'ٷ', // Arabic Letter U with Hamza Above U+0677
					"\xEF\xAF\x9D", // 'ﯝ', // Arabic Letter U with Hamza Above Isolated Form U+FBDD
					"\xEF\xBA\x85", // 'ﺅ', // Arabic Letter Waw with Hamza Above Isolated Form U+FE85
					"\xEF\xBA\x86", // 'ﺆ', // Arabic Letter Waw with Hamza Above Final Form U+FE86
				),
				"\xD8\xA4", // 'ؤ', // Arabic Letter Waw with Hamza Above U+0624
			),
			array(
				array(
					"\xDB\x84", // 'ۄ', // Arabic Letter Waw with Ring U+06C4
					"\xDB\x85", // 'ۅ', // Arabic Letter Kirghiz Oe U+06C5
					"\xDB\x86", // 'ۆ', // Arabic Letter Oe U+06C6
					"\xDB\x87", // 'ۇ', // Arabic Letter U U+06C7
					"\xDB\x88", // 'ۈ', // Arabic Letter Yu U+06C8
					"\xDB\x89", // 'ۉ', // Arabic Letter Kirghiz Yu U+06C9
					"\xDB\x8A", // 'ۊ', // Arabic Letter Waw with Two Dots Above U+06CA
					"\xDB\x8B", // 'ۋ', // Arabic Letter Ve U+06CB
					"\xDB\x8F", // 'ۏ', // Arabic Letter Waw with Dot Above U+06CF
					"\xDD\xB8", // 'ݸ', // Arabic Letter Waw with Extended Arabic-Indic Digit Two Above U+0778
					"\xDD\xB9", // 'ݹ', // Arabic Letter Waw with Extended Arabic-Indic Digit Three Above U+0779
					"\xEF\xAF\x97", // 'ﯗ', // Arabic Letter U Isolated Form U+FBD7
					"\xEF\xAF\x98", // 'ﯘ', // Arabic Letter U Final Form U+FBD8
					"\xEF\xAF\x99", // 'ﯙ', // Arabic Letter Oe Isolated Form U+FBD9
					"\xEF\xAF\x9A", // 'ﯚ', // Arabic Letter Oe Final Form U+FBDA
					"\xEF\xAF\x9B", // 'ﯛ', // Arabic Letter Yu Isolated Form U+FBDB
					"\xEF\xAF\x9C", // 'ﯜ', // Arabic Letter Yu Final Form U+FBDC
					"\xEF\xAF\x9E", // 'ﯞ', // Arabic Letter Ve Isolated Form U+FBDE
					"\xEF\xAF\x9F", // 'ﯟ', // Arabic Letter Ve Final Form U+FBDF
					"\xEF\xAF\xA0", // 'ﯠ', // Arabic Letter Kirghiz Oe Isolated Form U+FBE0
					"\xEF\xAF\xA1", // 'ﯡ', // Arabic Letter Kirghiz Oe Final Form U+FBE1
					"\xEF\xAF\xA2", // 'ﯢ', // Arabic Letter Kirghiz Yu Isolated Form U+FBE2
					"\xEF\xAF\xA3", // 'ﯣ', // Arabic Letter Kirghiz Yu Final Form U+FBE3
					"\xEF\xBB\xAD", // 'ﻭ', // Arabic Letter Waw Isolated Form U+FEED
					"\xEF\xBB\xAE", // 'ﻮ', // Arabic Letter Waw Final Form U+FEEE
					"\xF0\x9E\xB8\x85", // '𞸅', // Arabic Mathematical Waw U+1EE05
					"\xF0\x9E\xBA\x85", // '𞺅', // Arabic Mathematical Looped Waw U+1EE85
					"\xF0\x9E\xBA\xA5", // '𞺥', // Arabic Mathematical Double-Struck Waw U+1EEA5
				),
				"\xD9\x88", // 'و', // Arabic Letter Waw U+0648
			),
			array(
				array(
					"\xD8\xA9", // 'ة', // Arabic Letter Teh Marbuta U+0629
					"\xDA\xBE", // 'ھ', // Arabic Letter Heh Doachashmee U+06BE
					"\xDB\x80", // 'ۀ', // Arabic Letter Heh with Yeh Above U+06C0
					"\xDB\x95", // 'ە', // Arabic Letter Ae U+06D5
					"\xDB\xBF", // 'ۿ', // Arabic Letter Heh with Inverted V U+06FF
					"\xEF\xAE\xA4", // 'ﮤ', // Arabic Letter Heh with Yeh Above Isolated Form U+FBA4
					"\xEF\xAE\xA5", // 'ﮥ', // Arabic Letter Heh with Yeh Above Final Form U+FBA5
					"\xEF\xAE\xA6", // 'ﮦ', // Arabic Letter Heh Goal Isolated Form U+FBA6
					"\xEF\xAE\xA9", // 'ﮩ', // Arabic Letter Heh Goal Medial Form U+FBA9
					"\xEF\xAE\xA8", // 'ﮨ', // Arabic Letter Heh Goal Initial Form U+FBA8
					"\xEF\xAE\xAA", // 'ﮪ', // Arabic Letter Heh Doachashmee Isolated Form U+FBAA
					"\xEF\xAE\xAB", // 'ﮫ', // Arabic Letter Heh Doachashmee Final Form U+FBAB
					"\xEF\xAE\xAC", // 'ﮬ', // Arabic Letter Heh Doachashmee Initial Form U+FBAC
					"\xEF\xAE\xAD", // 'ﮭ', // Arabic Letter Heh Doachashmee Medial Form U+FBAD
					"\xEF\xBA\x93", // 'ﺓ', // Arabic Letter Teh Marbuta Isolated Form U+FE93
					"\xEF\xBA\x94", // 'ﺔ', // Arabic Letter Teh Marbuta Final Form U+FE94
					"\xEF\xBB\xA9", // 'ﻩ', // Arabic Letter Heh Isolated Form U+FEE9
					"\xEF\xBB\xAA", // 'ﻪ', // Arabic Letter Heh Final Form U+FEEA
					"\xEF\xBB\xAB", // 'ﻫ', // Arabic Letter Heh Initial Form U+FEEB
					"\xEF\xBB\xAC", // 'ﻬ', // Arabic Letter Heh Medial Form U+FEEC
					"\xF0\x9E\xB8\xA4", // '𞸤', // Arabic Mathematical Initial Heh U+1EE24
					"\xF0\x9E\xB9\xA4", // '𞹤', // Arabic Mathematical Stretched Heh U+1EE64
					"\xF0\x9E\xBA\x84", // '𞺄', // Arabic Mathematical Looped Heh U+1EE84
				),
				"\xD9\x87", // 'ه', // Arabic Letter Heh U+0647
			),
			array(
				array(
					"\xD9\xB8", // 'ٸ', // Arabic Letter High Hamza Yeh U+0678
					"\xDB\x93", // 'ۓ', // Arabic Letter Yeh Barree with Hamza Above U+06D3
					"\xEF\xAE\xB0", // 'ﮰ', // Arabic Letter Yeh Barree with Hamza Above Isolated Form U+FBB0
					"\xEF\xAE\xB1", // 'ﮱ', // Arabic Letter Yeh Barree with Hamza Above Final Form U+FBB1
					"\xEF\xBA\x89", // 'ﺉ', // Arabic Letter Yeh with Hamza Above Isolated Form U+FE89
					"\xEF\xBA\x8A", // 'ﺊ', // Arabic Letter Yeh with Hamza Above Final Form U+FE8A
					"\xEF\xBA\x8B", // 'ﺋ', // Arabic Letter Yeh with Hamza Above Initial Form U+FE8B
					"\xEF\xBA\x8C", // 'ﺌ', // Arabic Letter Yeh with Hamza Above Medial Form U+FE8C
				),
				"\xD8\xA6", // 'ئ', // Arabic Letter Yeh with Hamza Above U+0626
			),
			array(
				array(
					"\xD8\xA0", // 'ؠ', // Arabic Letter Kashmiri Yeh U+0620
					"\xD8\xBD", // 'ؽ', // Arabic Letter Farsi Yeh with Inverted V U+063D
					"\xD8\xBE", // 'ؾ', // Arabic Letter Farsi Yeh with Two Dots Above U+063E
					"\xD8\xBF", // 'ؿ', // Arabic Letter Farsi Yeh with Three Dots Above U+063F
					"\xD9\x89", // 'ى', // Arabic Letter Alef Maksura U+0649
					"\xD9\x8A", // 'ي', // Arabic Letter Yeh U+064A
					"\xDB\x8D", // 'ۍ', // Arabic Letter Yeh with Tail U+06CD
					"\xDB\x8E", // 'ێ', // Arabic Letter Yeh with Small V U+06CE
					"\xDB\x90", // 'ې', // Arabic Letter E U+06D0
					"\xDB\x91", // 'ۑ', // Arabic Letter Yeh with Three Dots Below U+06D1
					"\xDB\x92", // 'ے', // Arabic Letter Yeh Barree U+06D2
					"\xDD\xB5", // 'ݵ', // Arabic Letter Farsi Yeh with Extended Arabic-Indic Digit Two Above U+0775
					"\xDD\xB6", // 'ݶ', // Arabic Letter Farsi Yeh with Extended Arabic-Indic Digit Three Above U+0776
					"\xDD\xB7", // 'ݷ', // Arabic Letter Farsi Yeh with Extended Arabic-Indic Digit Four Below U+0777
					"\xDD\xBA", // 'ݺ', // Arabic Letter Yeh Barree with Extended Arabic-Indic Digit Two Above U+077A
					"\xDD\xBB", // 'ݻ', // Arabic Letter Yeh Barree with Extended Arabic-Indic Digit Three Above U+077B
					"\xEF\xAE\xA2", // 'ﮢ', // Arabic Letter Rnoon Initial Form U+FBA2
					"\xEF\xAE\xA3", // 'ﮣ', // Arabic Letter Rnoon Medial Form U+FBA3
					"\xEF\xAE\xAE", // 'ﮮ', // Arabic Letter Yeh Barree Isolated Form U+FBAE
					"\xEF\xAE\xAF", // 'ﮯ', // Arabic Letter Yeh Barree Final Form U+FBAF
					"\xEF\xAF\xA4", // 'ﯤ', // Arabic Letter E Isolated Form U+FBE4
					"\xEF\xAF\xA5", // 'ﯥ', // Arabic Letter E Final Form U+FBE5
					"\xEF\xAF\xA6", // 'ﯦ', // Arabic Letter E Initial Form U+FBE6
					"\xEF\xAF\xA7", // 'ﯧ', // Arabic Letter E Medial Form U+FBE7
					"\xEF\xAF\xA8", // 'ﯨ', // Arabic Letter Uighur Kazakh Kirghiz Alef Maksura Initial Form U+FBE8
					"\xEF\xAF\xA9", // 'ﯩ', // Arabic Letter Uighur Kazakh Kirghiz Alef Maksura Medial Form U+FBE9
					"\xEF\xAF\xBC", // 'ﯼ', // Arabic Letter Farsi Yeh Isolated Form U+FBFC
					"\xEF\xAF\xBD", // 'ﯽ', // Arabic Letter Farsi Yeh Final Form U+FBFD
					"\xEF\xAF\xBE", // 'ﯾ', // Arabic Letter Farsi Yeh Initial Form U+FBFE
					"\xEF\xAF\xBF", // 'ﯿ', // Arabic Letter Farsi Yeh Medial Form U+FBFF
					"\xEF\xBB\xAF", // 'ﻯ', // Arabic Letter Alef Maksura Isolated Form U+FEEF
					"\xEF\xBB\xB0", // 'ﻰ', // Arabic Letter Alef Maksura Final Form U+FEF0
					"\xEF\xBB\xB1", // 'ﻱ', // Arabic Letter Yeh Isolated Form U+FEF1
					"\xEF\xBB\xB2", // 'ﻲ', // Arabic Letter Yeh Final Form U+FEF2
					"\xEF\xBB\xB3", // 'ﻳ', // Arabic Letter Yeh Initial Form U+FEF3
					"\xEF\xBB\xB4", // 'ﻴ', // Arabic Letter Yeh Medial Form U+FEF4
					"\xF0\x9E\xB8\x89", // '𞸉', // Arabic Mathematical Yeh U+1EE09
					"\xF0\x9E\xB8\xA9", // '𞸩', // Arabic Mathematical Initial Yeh U+1EE29
					"\xF0\x9E\xB9\x89", // '𞹉', // Arabic Mathematical Tailed Yeh U+1EE49
					"\xF0\x9E\xB9\xA9", // '𞹩', // Arabic Mathematical Stretched Yeh U+1EE69
					"\xF0\x9E\xBA\x89", // '𞺉', // Arabic Mathematical Looped Yeh U+1EE89
					"\xF0\x9E\xBA\xA9", // '𞺩', // Arabic Mathematical Double-Struck Yeh U+1EEA9
				),
				"\xDB\x8C", // 'ی', // Arabic Letter Farsi Yeh U+06CC
			),
			array(
				array(
					"\xD9\xB4", // 'ٴ', // Arabic Letter High Hamza U+0674
					"\xDB\xBD", // '۽', // Arabic Sign Sindhi Ampersand U+06FD
					"\xEF\xBA\x80", // 'ﺀ', // Arabic Letter Hamza Isolated Form U+FE80
				),
				"\xD8\xA1", // 'ء', // Arabic Letter Hamza U+0621
			),
			array(
				array(
					"\xEF\xBB\xB5", // 'ﻵ', // Arabic Ligature Lam with Alef with Madda Above Isolated Form U+FEF5
					"\xEF\xBB\xB6", // 'ﻶ', // Arabic Ligature Lam with Alef with Madda Above Final Form U+FEF6
					"\xEF\xBB\xB7", // 'ﻷ', // Arabic Ligature Lam with Alef with Hamza Above Isolated Form U+FEF7
					"\xEF\xBB\xB8", // 'ﻸ', // Arabic Ligature Lam with Alef with Hamza Above Final Form U+FEF8
					"\xEF\xBB\xB9", // 'ﻹ', // Arabic Ligature Lam with Alef with Hamza Below Isolated Form U+FEF9
					"\xEF\xBB\xBA", // 'ﻺ', // Arabic Ligature Lam with Alef with Hamza Below Final Form U+FEFA
					"\xEF\xBB\xBB", // 'ﻻ', // Arabic Ligature Lam with Alef Isolated Form U+FEFB
					"\xEF\xBB\xBC", // 'ﻼ', // Arabic Ligature Lam with Alef Final Form U+FEFC
				),
				"\xD9\x84\xD8\xA7", // 'لا',
			),

			// @REF: https://en.wikipedia.org/wiki/Arabic_script_in_Unicode
			array(
				array(
					"\xEF\xB7\xB2", // 'ﷲ', // Arabic Ligature Allah Isolated Form U+FDF2
					"\xEF\xB7\xBC", // '﷼', // Rial Sign U+FDFC
					"\xEF\xB7\xB3", // 'ﷳ', // Arabic Ligature Akbar Isolated Form U+FDF3
					"\xEF\xB7\xB4", // 'ﷴ', // Arabic Ligature Mohammad Isolated Form U+FDF4
					"\xEF\xB7\xB5", // 'ﷵ', // Arabic Ligature Salam Isolated Form U+FDF5
					"\xEF\xB7\xB6", // 'ﷶ', // Arabic Ligature Rasoul Isolated Form U+FDF6
					"\xEF\xB7\xB7", // 'ﷷ', // Arabic Ligature Alayhe Isolated Form U+FDF7
					"\xEF\xB7\xB8", // 'ﷸ', // Arabic Ligature Wasallam Isolated Form U+FDF8
					"\xEF\xB7\xB9", // 'ﷹ', // Arabic Ligature Salla Isolated Form U+FDF9
					"\xEF\xB7\xBA", // 'ﷺ', // Arabic Ligature Sallallahou Alayhe Wasallam U+FDFA
					"\xEF\xB7\xBB", // 'ﷻ', // Arabic Ligature Jallajalalouhou U+FDFB
				),
				array(
					"\xD8\xA7\xD9\x84\xD9\x84\xD9\x87", // 'الله',
					"\xD8\xB1\xDB\x8C\xD8\xA7\xD9\x84", // 'ریال',
					"\xD8\xA7\xDA\xA9\xD8\xA8\xD8\xB1", // 'اکبر',
					"\xD9\x85\xD8\xAD\xD9\x85\xD8\xAF", // 'محمد',
					"\xD8\xB5\xD9\x84\xD8\xB9\xD9\x85", // 'صلعم', // FIXME
					"\xD8\xB1\xD8\xB3\xD9\x88\xD9\x84", // 'رسول',
					"\xD8\xB9\xD9\x84\xDB\x8C\xD9\x87", // 'علیه',
					"\xD9\x88\xD8\xB3\xD9\x84\xD9\x85", // 'وسلم',
					"\xD8\xB5\xD9\x84\xDB\x8C", // 'صلی',
					"\xD8\xB5\xD9\x84\xDB\x8C\x20\xD8\xA7\xD9\x84\xD9\x84\xD9\x87\x20\xD8\xB9\xD9\x84\xDB\x8C\xD9\x87\x20\xD9\x88\xD8\xB3\xD9\x84\xD9\x85", // 'صلی الله علیه وسلم',
					"\xD8\xAC\xD9\x84\x20\xD8\xAC\xD9\x84\xD8\xA7\xD9\x84\xD9\x87", // 'جل جلاله',
				),
			),
		);
	}

	// @SOURCE: [Vajehyab/abjad: convert Persian/Arabic alphabets to Abjad numerals.](https://github.com/Vajehyab/abjad)
	// @AUTHOR: [m-kermani (Mohammad Kermani)](https://github.com/m-kermani)
	// @REF: [Abjad numerals](https://en.wikipedia.org/wiki/Abjad_numerals)
	public static function abjad( $word )
	{
		$word = trim( $word );

		$word = str_replace( array( 'َ', 'ُ', 'ِ', 'ّ', 'ً', 'ٌ', 'ٍ', 'ـ', 'ْ' ), '', $word );
		$word = str_replace( array( 'گ', 'چ', 'پ', 'ژ', 'ة', 'آ' ), array( 'ک', 'ج', 'ب', 'ز', 'ه', 'ا' ), $word );
		$word = str_replace( array( ')', '(', '+', '$', '!', '~', '#', '%', '^', '&', '*', '-', ' ', '/', '`', '«', '»', 'ء' ), '', $word );

		$letter = array(
			'ا',
			'ب',
			'ج',
			'د',
			'ه',
			'و',
			'ز',
			'ح',
			'ط',
			'ی',
			'ک',
			'ل',
			'م',
			'ن',
			'س',
			'ع',
			'ف',
			'ص',
			'ق',
			'ر',
			'ش',
			'ت',
			'ث',
			'خ',
			'ذ',
			'ض',
			'ظ',
			'غ',
		);

		$abjad = array(
			'+1',
			'+2',
			'+3',
			'+4',
			'+5',
			'+6',
			'+7',
			'+8',
			'+9',
			'+10',
			'+20',
			'+30',
			'+40',
			'+50',
			'+60',
			'+70',
			'+80',
			'+90',
			'+100',
			'+200',
			'+300',
			'+400',
			'+500',
			'+600',
			'+700',
			'+800',
			'+900',
			'+1000',
		);

		$string  = '0'.str_replace( $letter, $abjad, $word );
		$compute = create_function( "", "return (".preg_replace( '[^0-9\+-\*\/\(\) ]', '', $string ).");" );

		return $compute ? 0 + $compute() : 0;
	}

	// separate HTML elements and comments from the text.
	// @SOURCE: `wp_html_split()`
	public static function splitHTML( $input )
	{
		return preg_split( self::getHTMLSplitRegex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
	}

	// retrieve the regular expression for an HTML element.
	// @SOURCE: `get_html_split_regex()`
	public static function getHTMLSplitRegex()
	{
		static $regex;

		if ( ! isset( $regex ) ) {
			$comments =
				  '!'           // Start of comment, after the <.
				. '(?:'         // Unroll the loop: Consume everything until --> is found.
				.     '-(?!->)' // Dash not followed by end of comment.
				.     '[^\-]*+' // Consume non-dashes.
				. ')*+'         // Loop possessively.
				. '(?:-->)?';   // End of comment. If not found, match all input.

			$cdata =
				  '!\[CDATA\['  // Start of comment, after the <.
				. '[^\]]*+'     // Consume non-].
				. '(?:'         // Unroll the loop: Consume everything until ]]> is found.
				.     '](?!]>)' // One ] not followed by end of comment.
				.     '[^\]]*+' // Consume non-].
				. ')*+'         // Loop possessively.
				. '(?:]]>)?';   // End of comment. If not found, match all input.

			$escaped =
				  '(?='           // Is the element escaped?
				.    '!--'
				. '|'
				.    '!\[CDATA\['
				. ')'
				. '(?(?=!-)'      // If yes, which type?
				.     $comments
				. '|'
				.     $cdata
				. ')';

			$regex =
				  '/('              // Capture the entire match.
				.     '<'           // Find start of element.
				.     '(?'          // Conditional expression follows.
				.         $escaped  // Find end of escaped element.
				.     '|'           // ... else ...
				.         '[^>]*>?' // Find end of normal element.
				.     ')'
				. ')/';
		}

		return $regex;
	}
}

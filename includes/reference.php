<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkReference extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	public static function parseFootnotes( $footnotes )
	{
		$count = 1;
		$notes = array();
		$rows  = array_filter( preg_split( "/\n/", $footnotes ) );

		$patterns = array(
			'/\[(\d+)\]\s\-/u',
			'/\[(\d+)\]\-/u',
			'/\[(\d+)\]/u',
			'/\((\d+)\)/u',
			'/\((\d+)\)\s\-/u',
			'/\((\d+)\)\-/u',
			'/^(\d+)\-/u',
			'/^(\d+)\./u', // 13.
			'/^(\d+)\s\./u', // 13 .
			'/^(\d+)/u',
			// '/^(\.\s)/u', // working but disabled
		);

		foreach ( $rows as $row ) {
			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, trim( $row ), $matches ) ) {
					$note = trim( str_ireplace( $matches[0], '', trim( $row ), $count ) );
					$note = str_ireplace( array( '-', '—' ), '–', $note );
					$notes[] = trim( trim( $note, '–' ) );
					// gNU::dump( $notes );
					break;
				}
			}
		}

		return $notes;
	}

	public static function replaceFootnotes( $content, $footnotes = '', $type = 'brackets', $shortcode = 'ref' )
	{
		switch ( $type ){
			case 'brackets' : $pattern = '/\[(\d+)\]/u'; break;
			case 'parentheses' : $pattern = '/\((\d+)\)/u'; break;
		}

		$html  = str_ireplace( $footnotes, '', $content );
		$notes = self::parseFootnotes( $footnotes );

		// gNU::dump($notes ); die();

		preg_match_all( $pattern, $html, $matches );

		// gNU::dump($matches ); die();

		$count = 1;
		foreach ( $matches[0] as $key => $match ) {
			$html = str_ireplace( $match,
				( isset( $notes[$key] ) ? '['.$shortcode.']'.$notes[$key].'[/'.$shortcode.']' : '['.$shortcode.']'.$matches[1][$key].'[/'.$shortcode.']' ),
			$html, $count );
		}

		return $html;
	}

	// DRAFT
	// http://stackoverflow.com/a/22877248
	// https://regex101.com/r/pF1qO4/1
	public static function extract( $html, $brackets = true )
	{
		//return $html;

		// whole line that contains word "session"
		// http://stackoverflow.com/a/14675462
		// $pattern = '/[^\n]*session[^\n]*/';

		// divide text into sentences
		// http://stackoverflow.com/q/3832945
		// $pattern = '/(?<!\..)([\?\!\.])\s(?!.\.)/';

		$pattern = "/(پاورقی|پانوشت)/u";

		$matches = preg_split ( $pattern, $html );
		echo $matches[0];
		echo '<br/>';
		echo $matches[1];
		$rowsapart = preg_split( "/\n/",$matches[1] );
		gnetwork_dump( $rowsapart );
		gnetwork_dump( $matches ); die();



		// http://stackoverflow.com/a/10311124
		$pattern = "/(?:پاورقی|پانوشت).*?(\n|$)/Uu";
		$pattern = "/(?:پاورقی|پانوشت).*\r?\n/Uu";
		$pattern = "/(?:پاورقی|پانوشت)(.*)+/u";

		preg_match_all( $pattern, $html, $matches );
		gnetwork_dump( $matches ); die();






		return $html;

		$pattern = $brackets ? '/\[(\d+)\]/u' : '/\((\d+)\)/u';
		//$pattern = $brackets ? '/\[(?<=@\[)\d+(?=\])\]/u' : '/\((?<=@\[)\d+(?=\])\)/u';

		$html = 'Hi @[123456789] :) [1297gd] [12] [234] [0]';
		//$html = 'Hi @(123456789);k :) (1297gd) [12] (234) (234) (0)';

		preg_match_all( $pattern, $html, $matches );
		gnetwork_dump( $matches ); die();

		//str_ireplace ( mixed $search , mixed $replace , mixed $subject [, int &$count ] )

		return $html;
	}

	///////////////////////////////////////////////////
	///////////////////////////////////////////////////
	///////////////////////////////////////////////////
	///////////////////////////////////////////////////
	///////////////////////////////////////////////////
	// http://stackoverflow.com/a/18541983
	private $number_of_notes = 0;
	private $footnote_texts = array();

	public function replace($input) {

		return preg_replace_callback('#<span class="fnt">(.*)</span>#i', array($this, 'replace_callback'), $input);

	}

	protected function replace_callback($matches)
	{

		// the text sits in the matches array
		// see http://php.net/manual/en/function.preg-replace-callback.php
		$this->footnote_texts[] = $matches[1];

		return '<sup><a href="#endnote_'.(++$this->number_of_notes).'">'.$this->number_of_notes.'</a></sup>';

	}

	public function getEndnotes()
	{
		$out = array();
		$out[] = '<ol>';

		foreach ( $this->footnote_texts as $text ) {
			$out[] = '<li>'.$text.'</li>';
		}

		$out[] = '</ol>';

		return implode("\n", $out);
	}


	///////////////////////////////////////////////////
	///////////////////////////////////////////////////
	///////////////////////////////////////////////////
	///////////////////////////////////////////////////
	///////////////////////////////////////////////////
	// http://ben.balter.com/2011/03/20/regular-expression-to-parse-word-style-footnotes/
	// https://github.com/benbalter/Convert-Microsoft-Word-Footnotes-to-WordPress-Simple-Footnotes

	/**
	 * Function which uses regular expression to parse Microsoft Word footnotes
	 * into WordPress's Simple Footnotes format
	 *
	 * @link http://ben.balter.com/2011/03/20/regular-expression-to-parse-word-style-footnotes/
	 * @param string $content post content from filter hook
	 * @returns string post content with parsed footnotes
	 */
	public function bb_parse_footnotes( $content )
	{

		global $post;
		if ( !isset( $post ) )
			return;

		//if we have already parsed, kick
		if ( get_post_meta($post->ID, 'parsed_footnotes') )
			return $content;

		$content = stripslashes( $content );

		//grab all the Word-style footnotes into an array
		$pattern = '/\<a( title\=\"\")? href\=\"[^\"]*\#_ftnref([0-9]+)\"\>\[([0-9]+)\]\<\/a\>(.*)/';
		preg_match_all( $pattern, $content, $footnotes, PREG_SET_ORDER);

		//build find and replace arrays
		foreach ($footnotes as $footnote) {
			$find[] = '/\<a( title\=\"\")? href\=\"[^\"]*\#_ftn'.$footnote[2].'\"\>(\<strong\>)?\['.$footnote[2].'\](\<\/strong\>)?\<\/a\>/';
			$replace[] = '[ref]' . str_replace( array("\r\n", "\r", "\n"), "", $footnote[4]) . '[/ref]';
		}

		//remove all the original footnotes when done
		$find[] = '/\<div\>\s*(\<p\>)?\<a( title\=\"\")? href\=\"[^\"]*\#_ftnref([0-9]+)\"\>\[([0-9]+)\]\<\/a\>(.*)\s*\<\/div\>\s+/s';
		$replace[] = '';

		//make the switch
		$content = preg_replace( $find, $replace, $content );

		//add meta so we know it has been parsed
		add_post_meta($post->ID, 'parsed_footnotes', true, true);

		return addslashes($content);
	}
	// add_filter( 'content_save_pre', 'bb_parse_footnotes' );
}


// http://andrewnacin.com/2010/07/24/simple-footnotes-0-3/
// https://wordpress.org/plugins/simple-footnotes/
// http://www.mediawiki.org/wiki/Extension:Cite
// http://meta.wikimedia.org/wiki/Help:Footnotes
// http://wikis.evergreen.edu/computing/index.php/References_-_Mediawiki

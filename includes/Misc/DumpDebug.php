<?php namespace geminorum\gNetwork\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

class DumpDebug extends Core\Base
{
	/**
	 * Dumps information about a variable.
	 * @source https://www.php.net/manual/en/function.var-dump.php#116041
	 *
	 * `var_dump()` with colors and collapse features. It can also adapt to
	 * terminal output if you execute it from there. No need to wrap
	 * it in a pre tag to get it to work in browsers.
	 *
	 * This function displays structured information about one or more
	 * expressions that includes its type and value. Arrays and objects are
	 * explored recursively with values indented to show structure.
	 *
	 * All public, private and protected properties of objects will be
	 * returned in the output.
	 *
	 * @param mixed $input
	 * @param bool $collapse
	 * @param string|false $wrap
	 * @return void
	 */
	public static function render( $input, $collapse = FALSE, $wrap = 'pre' )
	{
		$recursive = static function ( $data, $level = 0 ) use ( &$recursive, $collapse ) {
			global $argv;

			$isTerminal = isset( $argv );

			if ( ! $isTerminal && $level === 0 && ! defined( 'DUMP_DEBUG_SCRIPT' ) ) {

				define( 'DUMP_DEBUG_SCRIPT', TRUE );

				echo '<script language="Javascript">function toggleDisplay(id) {';
				echo 'var state = document.getElementById("container"+id).style.display;';
				echo 'document.getElementById("container"+id).style.display = state == "inline" ? "none" : "inline";';
				echo 'document.getElementById("plus"+id).style.display = state == "inline" ? "inline" : "none";';
				echo '}</script>';
			}

			$type        = ! is_string( $data ) && is_callable( $data ) ? 'Callable' : ucfirst( gettype( $data ) );
			$type_data   = NULL;
			$type_color  = NULL;
			$type_length = NULL;

			switch ( $type ) {

				case 'String':

					$type_color  = 'green';
					$type_length = strlen( $data );
					$type_data   = '"'.htmlentities( $data ).'"';

					break;

				case 'Double':
				case 'Float':

					$type        = 'Float';
					$type_color  = '#0099c5';
					$type_length = strlen( $data );
					$type_data   = htmlentities( $data );

					break;

				case 'Integer':

					$type_color  = 'red';
					$type_length = strlen( $data );
					$type_data   = htmlentities( $data );

					break;

				case 'Boolean':

					$type_color  = '#92008d';
					$type_length = strlen( $data );
					$type_data   = $data ? 'TRUE' : 'FALSE';

					break;

				case 'NULL':

					$type_length = 0;

					break;

				case 'Array':

					$type_length = count( $data );
			}

			if ( in_array( $type, [ 'Object', 'Array' ] ) ) {

				$notEmpty = FALSE;

				foreach ( $data as $key => $value ) {

					if ( ! $notEmpty ) {

						$notEmpty = TRUE;

						if ( $isTerminal ) {

							echo $type.( $type_length !== NULL ? '('.$type_length.')' : '' )."\n";

						} else {

							$id = substr( md5( rand().':'.$key.':'.$level ), 0, 8 );

							echo "<a href=\"javascript:toggleDisplay('". $id ."');\" style=\"text-decoration:none\">";
							echo "<span style='color:#666666'>" . $type . ($type_length !== null ? "(" . $type_length . ")" : "") . "</span>";
							echo "</a>";
							echo "<span id=\"plus". $id ."\" style=\"display: " . ($collapse ? "inline" : "none") . ";\">&nbsp;&#10549;</span>";
							echo "<div id=\"container". $id ."\" style=\"display: " . ($collapse ? "" : "inline") . ";\">";
							echo "<br />";
						}

						for ($i=0; $i <= $level; $i++) {
							echo $isTerminal ? "|" : "<span style='color:black'>|</span>&nbsp";
						}

						echo $isTerminal ? "\n" : "<br />";
					}

					for ($i=0; $i <= $level; $i++) {
						echo $isTerminal ? "|    " : "<span style='color:black'>|</span>&nbsp;";
					}

					echo $isTerminal ? "[" . $key . "] => " : "<span style='color:black'>[" . $key . "]&nbsp;=>&nbsp;</span>";

					call_user_func($recursive, $value, $level+1);
				}

				if ($notEmpty) {
					for ($i=0; $i <= $level; $i++) {
						echo $isTerminal ? "|" : "<span style='color:black'>|</span>&nbsp";
					}

					if (!$isTerminal) {
						echo "</div>";
					}

				} else {
					echo $isTerminal ?
							$type . ($type_length !== null ? "(" . $type_length . ")" : "") . "  " :
							"<span style='color:#666666'>" . $type . ($type_length !== null ? "(" . $type_length . ")" : "") . "</span>&nbsp;";
				}

			} else {
				echo $isTerminal ?
						$type . ($type_length !== null ? "(" . $type_length . ")" : "") . "  " :
						"<span style='color:#666666'>" . $type . ($type_length !== null ? "(" . $type_length . ")" : "") . "</span>&nbsp;";

				if ($type_data != null) {
					echo $isTerminal ? $type_data : "<span style='color:" . $type_color . "'>" . $type_data . "</span>";
				}
			}

			echo $isTerminal ? "\n" : "<br />";
		};

		if ( $wrap )
			echo '<'.$wrap.' class="-dump-debug">';

		call_user_func( $recursive, $input );

		if ( $wrap )
			echo '</'.$wrap.'>';
	}
}

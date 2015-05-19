<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetwork_Debug_Bar_Panel extends Debug_Bar_Panel 
{
	
	function init() 
	{
		$this->title( __( 'gNetwork: Panel', GNETWORK_TEXTDOMAIN ) );
	}

	function render() 
	{
		echo "<div>";
		//echo "<h3>Post Meta</h3>";
		$metas = get_post_meta( get_the_ID() );
		echo '<table style="direction:ltr;">';
		
		echo '<tr><td>Current Site Blog ID</td><td>';
			//echo gNU::getCurrentSiteBlogID();
			//echo get_current_blog_id();
			
			global $current_site;
			//return absint( $current_site->blog_id );
			gNU::dump( $current_site );

			
		echo '</td></tr>';
		
		
		
		foreach ( $metas as $key => $values ) {
			echo '<tr><td>' . $key . '</td><td>';
			
			foreach ($values as $value) {
				if ( ( is_serialized( $value ) )  !== false) {
					//$vals .= '<pre><code>' . print_r( unserialize( $value ), true ) . '</code></pre>';
					gNU::dump( unserialize( $value ) );
				} else {
					//$vals .= '<pre><code>' . print_r( $value, true ) . "</code></pre>\n";	
					gNU::dump( $value );
				}
				
			}
			echo '</td></tr>';
		}
		echo '</table>';
		echo "</div>";
	}
}
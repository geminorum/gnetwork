<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

global $gNetwork;

if ( isset( $gNetwork->code ) ) {
	echo $gNetwork->code->shortcode_github_readme( array(
		'repo' => 'geminorum/gnetwork',
	) );
}

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

echo '<form method="post" action="">';
	echo '<table class="form-table">';

	$manage_options = current_user_can( 'manage_options' );

	if ( class_exists( __NAMESPACE__.'\\Debug' ) && $manage_options ) {

		echo '<tr class="ltr"><th scope="row">'.__( 'Your IP Summary', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::summaryIPs();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Current Time', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::currentTime();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'PHP Versions', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::phpversion();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Core Versions', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::versions();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Initial Constants', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::initialConstants();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Plugin Paths', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::pluginPaths();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Upload Paths', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::wpUploadDIR();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'SERVER', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::dumpServer();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'gPlugin', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::gPlugin();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Stats of the Caching', GNETWORK_TEXTDOMAIN ).'</th><td>';
			Debug::cacheStats();
		echo '</td></tr>';
	}

	if ( class_exists( __NAMESPACE__.'\\ShortCodes' ) && current_user_can( 'edit_posts' ) ) {
		echo '<tr class="ltr"><th scope="row">'.__( 'Available Shortcodes', GNETWORK_TEXTDOMAIN ).'</th><td>';
			ShortCodes::available();
		echo '</td></tr>';
	}

	echo '</table>';
echo '</form>';

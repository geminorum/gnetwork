<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

echo '<form method="post" action="">';
	echo '<table class="form-table">';

	$manage_options = current_user_can( 'manage_options' );

	if ( class_exists( 'gNetworkDebug' ) && $manage_options ) {

		echo '<tr class="ltr"><th scope="row">'.__( 'PHP Versions', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::phpversion();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Core Versions', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::versions();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Initial Constants', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::initialConstants();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Plugin Paths', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::pluginPaths();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Upload Paths', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::wpUploadDIR();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'SERVER', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::dumpServer();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'gPlugin', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::gPlugin();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">'.__( 'Stats of the Caching', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::cacheStats();
		echo '</td></tr>';
	}

	if ( class_exists( 'gNetworkShortCodes' ) && current_user_can( 'edit_posts' ) ) {
		echo '<tr class="ltr"><th scope="row">'.__( 'Available Shortcodes', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkShortCodes::available();
		echo '</td></tr>';
	}

	echo '</table>';
echo '</form>';

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

echo '<form method="post" action="">';
	echo '<h3>'.__( 'WordPress', GNETWORK_TEXTDOMAIN ).'</h3>';
	echo '<table class="form-table gnetwork-settings">';

	$manage_options = current_user_can( 'manage_options' );

	if ( class_exists( 'gNetworkDebug' ) && $manage_options  ) {

		echo '<tr class="table-block ltr"><th scope="row">'.__( 'Core Versions', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::versions();
		echo '</td></tr>';

		echo '<tr class="ul-li-inline ltr"><th scope="row">'.__( 'Stats of the Caching', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkDebug::cacheStats();
		echo '</td></tr>';

	}

	if ( class_exists( 'gNetworkShortCodes' ) && current_user_can( 'edit_posts' ) ) {
		echo '<tr class="ul-li-inline ltr"><th scope="row">'.__( 'Available Shortcodes', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkShortCodes::available();
		echo '</td></tr>';
	}

	if ( class_exists( 'gNetworkLocale' ) && is_super_admin() ) {
		echo '<tr class="ltr"><th scope="row">'.__( 'Loaded MO Files', GNETWORK_TEXTDOMAIN ).'</th><td>';
			gNetworkLocale::loadedMOfiles();
		echo '</td></tr>';
	}

	echo '</table>';
echo '</form>';

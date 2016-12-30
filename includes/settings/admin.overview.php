<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

echo '<form method="post" action="">';
	echo '<table class="form-table">';

	if ( class_exists( __NAMESPACE__.'\\Debug' )
		&& current_user_can( 'manage_options' ) ) {

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Your IP Summary', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::summaryIPs();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Current Time', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::currentTime();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'PHP Versions', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::phpversion();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Core Versions', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::versions();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Initial Constants', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::initialConstants();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Plugin Paths', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::pluginPaths();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Upload Paths', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::wpUploadDIR();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'SERVER', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::dumpServer();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'gPlugin', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::gPlugin();
		echo '</td></tr>';

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Stats of the Caching', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::cacheStats();
		echo '</td></tr>';
	}

	if ( class_exists( __NAMESPACE__.'\\ShortCodes' )
		&& current_user_can( 'edit_posts' ) ) {

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Available Shortcodes', 'Modules: Shortcodes: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			ShortCodes::available();
		echo '</td></tr>';
	}

	echo '</table>';
echo '</form>';

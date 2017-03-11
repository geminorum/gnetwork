<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

echo '<form method="post" action="">';
	echo '<table class="form-table">';

	if ( class_exists( __NAMESPACE__.'\\ShortCodes' )
		&& current_user_can( 'edit_posts' ) ) {

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Available Shortcodes', 'Modules: Shortcodes: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			ShortCodes::available();
		echo '</td></tr>';
	}

	if ( class_exists( __NAMESPACE__.'\\Debug' )
		&& current_user_can( 'manage_options' ) ) {

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Stats of the Caching', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			Debug::cacheStats();
		echo '</td></tr>';
	}

	echo '</table>';
echo '</form>';

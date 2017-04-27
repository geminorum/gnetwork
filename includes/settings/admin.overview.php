<?php namespace geminorum\gNetwork\Settings;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

echo '<form method="post" action="">';
	echo '<table class="form-table">';

	if ( class_exists( 'geminorum\\gNetwork\\Modules\\ShortCodes' )
		&& current_user_can( 'edit_posts' ) ) {

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Available Shortcodes', 'Modules: Shortcodes: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			\geminorum\gNetwork\Modules\ShortCodes::available();
		echo '</td></tr>';
	}

	if ( class_exists( 'geminorum\\gNetwork\\Modules\\Debug' )
		&& current_user_can( 'manage_options' ) ) {

		echo '<tr class="ltr"><th scope="row">';
			_ex( 'Stats of the Caching', 'Modules: Debug: Admin Overview', GNETWORK_TEXTDOMAIN );
		echo '</th><td>';
			\geminorum\gNetwork\Modules\Debug::cacheStats();
		echo '</td></tr>';
	}

	echo '</table>';
echo '</form>';

<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Settings;

gNetwork()->mail->testmail_send();

?><form method="post" action="">
	<?php gNetwork()->mail->testmail_form(); ?>
	<?php wp_nonce_field( 'gnetwork-testmail' ); ?>
	<input type="hidden" name="action" value="sendtest" />
	<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( add_query_arg( 'sub', $sub, $uri ) ); ?>" />
	<?php submit_button( __( 'Send', GNETWORK_TEXTDOMAIN ) ); ?>
</form>

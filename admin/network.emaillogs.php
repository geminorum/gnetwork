<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

global $gNetwork;

// $gNetwork->mail->emaillogs_table();

?><form method="post" action="">
	<?php $gNetwork->mail->emaillogs_table(); ?>
	<?php wp_nonce_field( 'gnetwork-emaillogs' ); ?>
	<!-- <input type="hidden" name="action" value="table" /> -->
	<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( add_query_arg( 'sub', $sub, $uri ) ); ?>" />
	<!-- <?php // submit_button( __( 'Send', GNETWORK_TEXTDOMAIN ) ); ?> -->
</form>

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

/**
*	Based on : Proper Network Activation v1.0.5
*	By: scribu http://scribu.net/
*	https://github.com/scribu/wp-proper-network-activation
*/

class gNetworkActivation extends gNetworkModuleCore
{

	var $_network    = true;
	var $_option_key = false;
	var $_ajax       = true;

	public function setup_actions()
	{
		if ( ! is_multisite() )
			return;

		add_action( 'wpmu_new_blog', array( & $this, 'wpmu_new_blog' ) );
		add_action( 'wp_ajax_gnetwork_activation', array( & $this, 'ajax_response' ) );

		if ( ! is_network_admin()  )
			return;

		add_action( 'activated_plugin',  array( & $this, 'update_queue' ), 10, 2 );
		add_action( 'deactivated_plugin',  array( & $this, 'update_queue' ), 10, 2 );
		add_action( 'network_admin_notices', array( & $this, 'admin_notices' ) );
	}

	public function update_queue( $plugin, $network_wide = null )
	{
		if ( ! $network_wide )
			return;

		list( $action ) = explode( '_', current_filter(), 2 );

		$action = str_replace( 'activated', 'activate', $action );
		$queue = get_site_option( "network_{$action}_queue", array() );
		$queue[$plugin] = ( has_filter( $action.'_'.$plugin ) || has_filter( $action.'_plugin' ) );

		update_site_option( "network_{$action}_queue", $queue );
	}

	static function admin_notices()
	{
		if ( 'plugins-network' != get_current_screen()->id )
			return;

		$action = false;
		foreach ( array( 'activate', 'deactivate' ) as $key ) {
			if ( isset( $_REQUEST[ $key ] ) || isset( $_REQUEST[ $key . '-multi' ] ) ) {
				$action = $key;
				break;
			}
		}

		if ( ! $action )
			return;

		$queue = get_site_option( "network_{$action}_queue", array() );

		if ( empty( $queue ) )
			return;

		if ( ! in_array( true, $queue ) ) {
			delete_site_option( "network_{$action}_queue" );
			gNetworkUtilities::notice( __( 'Network (de)activation: no further action necessary.', GNETWORK_TEXTDOMAIN ) );
			return;
		}

		$total = get_blog_count();

		$messages = array(
			'activate' => __( 'Network activation: installed on %s / %s sites.', GNETWORK_TEXTDOMAIN ),
			'deactivate' => __( 'Network deactivation: uninstalled on %s / %s sites.', GNETWORK_TEXTDOMAIN ),
		);

		$message = sprintf( $messages[ $action ],
			"<span id='gnetwork-activation-count-current'>0</span>",
			"<span id='gnetwork-activation-count-total'>$total</span>"
		);

		gNetworkUtilities::notice( $message );
		//echo "<div class='updated'><p id='gnetwork-activation'>$message</p></div>";
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	var
		_action = '<?php echo $action; ?>',
		total = <?php echo $total; ?>,
		offset = 0,
		count = 5;

	var $display = $('#gnetwork-activation-count-current');

	function done() {
		var data = {
			action: 'gnetwork_activation',
			_action: _action,
			done: 1
		}

		$.post(ajaxurl, data, jQuery.noop);
	}

	function call_again() {
		var data = {
			action: 'gnetwork_activation',
			_action: _action,
			offset: offset
		}

		if ( offset > total ) {
			done();
			$display.html(total);
			return;
		}

		$.post(ajaxurl, data, function(response) {
			$display.html(offset);

			offset += count;
			call_again();
		});
	}

	call_again();
});
</script>
<?php
	}

	static function ajax_response()
	{
		$action = $_POST['_action'];

		if ( isset( $_POST['done'] ) ) {
			delete_site_option( "network_{$action}_queue" );
			die(1);
		}

		$offset = (int) $_POST['offset'];
		$queue = get_site_option( "network_{$action}_queue", array() );

		global $wpdb;

		$blogs = $wpdb->get_col( $wpdb->prepare( "
			SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE site_id = %d
			AND blog_id <> %d
			AND spam = '0'
			AND deleted = '0'
			AND archived = '0'
			ORDER BY registered DESC
			LIMIT %d, 5
		", $wpdb->siteid, $wpdb->blogid, $offset ) );

		foreach ( $blogs as $blog_id ) {
			switch_to_blog( $blog_id );

			foreach ( $queue as $plugin => $actionable )
				self::do_action( $action, $plugin );
		}

		die(1);
	}

	public function wpmu_new_blog( $blog_id )
	{
		switch_to_blog( $blog_id );

		foreach ( array_keys( get_site_option( 'active_sitewide_plugins' ) ) as $plugin )
			self::do_action( 'activate', $plugin );

		restore_current_blog();
	}

	private static function do_action( $action, $plugin )
	{
		do_action( $action.'_'.$plugin, false );
		do_action( $action.'_plugin', $plugin, false );
	}

}

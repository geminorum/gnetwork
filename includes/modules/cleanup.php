<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Cleanup extends ModuleCore
{

	protected $key     = 'cleanup';
	protected $network = FALSE;

	protected function setup_actions()
	{
		add_action( 'init' , array( $this, 'init_late' ), 99 );
		add_action( 'wp_default_scripts', array( $this, 'wp_default_scripts' ), 9 );

		add_action( 'admin_menu', array( $this, 'admin_menu_late' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 999 );

		add_action( 'wp_network_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );
		add_action( 'wp_user_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );
		add_action( 'wp_dashboard_setup', array( $this, 'wp_dashboard_setup' ), 20 );

		add_filter( 'wpcf7_load_css', '__return_false', 15 );

		// SEE: http://stephanis.info/2014/08/13/on-jetpack-and-auto-activating-modules
		add_filter( 'jetpack_get_default_modules', '__return_empty_array' );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Cleanup', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_settings()
	{
		$settings = array();
        $confirm  = Settings::getButtonConfirm();

		$settings['_transient'][] = array(
			'field'       => 'transient_purge',
			'type'        => 'button',
			'title'       => _x( 'Transient', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes Expired Transient Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Expired', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_transient'][] = array(
			'field'       => 'transient_purge_all',
			'type'        => 'button',
			'description' => _x( 'Removes All Transient Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge All', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		if ( is_main_site() ) {

			$settings['_transient'][] = array(
				'field'       => 'transient_purge_site',
				'type'        => 'button',
				'description' => _x( 'Removes Expired Network Transient Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'Purge Network Expired', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'values'      => $confirm,
			);

			$settings['_transient'][] = array(
				'field'       => 'transient_purge_site_all',
				'type'        => 'button',
				'description' => _x( 'Removes All Network Transient Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'Purge All Network', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'values'      => $confirm,
			);
		}

		$settings['_posts'][] = array(
			'field'       => 'postmeta_editdata',
			'type'        => 'button',
			'title'       => _x( 'Post Meta', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes Posts Last Edit User and Lock Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Last User & Post Lock Metadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_posts'][] = array(
			'field'       => 'postmeta_oldslug',
			'type'        => 'button',
			'description' => _x( 'Removes the Previous URL Slugs for Posts', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Old Slug Redirect Metadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_comments'][] = array(
			'field'       => 'comments_orphanedmeta',
			'type'        => 'button',
			'title'       => _x( 'Comments', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Checks for Orphaned Comment Metas', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Orphaned Matadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_comments'][] = array(
			'field'       => 'comments_akismetmeta',
			'type'        => 'button',
			'description' => _x( 'Removes Akismet Related Metadata from Comments', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Akismet Metadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_comments'][] = array(
			'field'       => 'comments_agentfield',
			'type'        => 'button',
			'description' => _x( 'Removes User Agent Fields from Comments', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge User Agent Fields', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		return $settings;
	}

	protected function settings_update( $sub )
	{
		if ( ! empty( $_POST ) && 'update' == $_POST['action'] ) {

			$this->check_referer( $sub );

			if ( isset( $_POST['transient_purge'] ) )
				$message = $this->purge_transient_data( FALSE, TRUE ) ? 'purged' : 'error';

			else if ( isset( $_POST['transient_purge_all'] ) )
				$message = $this->purge_transient_data( FALSE, FALSE ) ? 'purged' : 'error';

			else if ( isset( $_POST['transient_purge_site'] ) )
				$message = $this->purge_transient_data( TRUE, TRUE ) ? 'purged' : 'error';

			else if ( isset( $_POST['transient_purge_site_all'] ) )
				$message = $this->purge_transient_data( TRUE, FALSE ) ? 'purged' : 'error';

			else if ( isset( $_POST['postmeta_editdata'] ) )
				$message = $this->postmeta_editdata() ? 'purged' : 'error';

			else if ( isset( $_POST['postmeta_oldslug'] ) )
				$message = $this->postmeta_oldslug() ? 'purged' : 'error';

			else if ( isset( $_POST['comments_orphanedmeta'] ) )
				$message = $this->comments_orphanedmeta() ? 'optimized' : 'error';

			else if ( isset( $_POST['comments_agentfield'] ) )
				$message = $this->comments_agentfield() ? 'purged' : 'error';

			else if ( isset( $_POST['comments_akismetmeta'] ) )
				$message = $this->comments_akismetmeta() ? 'purged' : 'error';

			else
				$message = 'huh';

			self::redirect_referer( $message );
		}
	}

	public function init_late()
	{
		remove_action( 'wp_head', 'se_global_head' ); // by: Search Everything / http://wordpress.org/plugins/search-everything/
		remove_action( 'rightnow_end', array( 'Akismet_Admin', 'rightnow_stats' ) ); // by: Akismet
	}

	// @SOURCE: http://www.paulund.co.uk/remove-jquery-migrate-file-wordpress
	public function wp_default_scripts( &$scripts )
	{
		if ( is_admin() )
			return;

		if ( ! SCRIPT_DEBUG
			&& ( ! defined( 'GNETWORK_DISABLE_JQUERY_MIGRATE' )
				|| GNETWORK_DISABLE_JQUERY_MIGRATE ) ) {

			$scripts->remove( 'jquery' );
			$scripts->add( 'jquery', FALSE, array( 'jquery-core' ), '1.12.4' );
		}
	}

	// @SOURCE: http://justintadlock.com/archives/2011/06/13/removing-menu-pages-from-the-wordpress-admin
	public function admin_menu_late()
	{
		if ( ! WordPress::cuc( 'update_plugins' ) ) {
			remove_menu_page( 'link-manager.php' );
			remove_submenu_page( 'themes.php', 'theme-editor.php' );
		}

		if ( defined( 'BRUTEPROTECT_VERSION' ) && is_multisite() ) {
			remove_menu_page( 'bruteprotect-config' ); // BruteProtect notice
		}
	}

	public function admin_enqueue_scripts()
	{
		if ( defined( 'BRUTEPROTECT_VERSION' ) )
			wp_dequeue_style( 'bruteprotect-css' ); // BruteProtect global css!!
	}

	public function wp_dashboard_setup()
	{
		$screen = get_current_screen();

		// Removes the "Right Now" widget that tells you post/comment counts and what theme you're using.
		// remove_meta_box( 'dashboard_right_now', $screen, 'normal' );

		// Removes the recent comments widget
		// remove_meta_box( 'dashboard_recent_comments', $screen, 'normal' );

		// Removes the incoming links widget.
		// remove_meta_box( 'dashboard_incoming_links', $screen, 'normal' );

		// Removes the plugins widgets that displays the most popular, newest, and recently updated plugins
		remove_meta_box( 'dashboard_plugins', $screen, 'normal' );

		// Removes the quick press widget that allows you post right from the dashboard
		// remove_meta_box( 'dashboard_quick_press', $screen, 'side' );

		// Removes the widget containing the list of recent drafts
		// remove_meta_box( 'dashboard_recent_drafts', $screen, 'side' );

		// Removes the "WordPress Blog" widget
		remove_meta_box( 'dashboard_primary', $screen, 'side' );
	}

	// https://core.trac.wordpress.org/ticket/20316
	// http://wordpress.stackexchange.com/a/6652
	private function purge_transient_data( $site = FALSE, $time = FALSE )
	{
		global $wpdb, $_wp_using_ext_object_cache;

		if ( $_wp_using_ext_object_cache )
			return FALSE;

		if ( $site ) {
            $table = $wpdb->sitemeta;
            $key   = 'meta_key';
            $val   = 'meta_value';
            $like  = '%_site_transient_timeout_%';
		} else {
            $table = $wpdb->options;
            $key   = 'option_name';
            $val   = 'option_value';
            $like  = '%_transient_timeout_%';
		}

		$query = "SELECT {$key} FROM {$table} WHERE {$key} LIKE '{$like}'";

		if ( $time ) {
			$timestamp = isset( $_SERVER['REQUEST_TIME'] ) ? intval( $_SERVER['REQUEST_TIME'] ) : time();
			$query .= " AND {$val} < {$timestamp};";
		}

		foreach ( $wpdb->get_col( $query ) as $transient )
			if ( $site )
				delete_site_transient( str_replace( '_site_transient_timeout_', '', $transient ) );
			else
				delete_transient( str_replace( '_transient_timeout_', '', $transient ) );

		return TRUE;
	}

	private function comments_orphanedmeta()
	{
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_id FROM {$wpdb->comments})" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->commentmeta}" );

		return TRUE;
	}

	private function comments_agentfield()
	{
		global $wpdb;

		$wpdb->query( "UPDATE {$wpdb->comments} SET comment_agent = ''" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->comments}" );

		return TRUE;
	}

	private function comments_akismetmeta()
	{
		global $wpdb;

		// $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE 'meta_key' IN ( 'akismet_result', 'akismet_history', 'akismet_user', 'akismet_user_result' ) " );
		$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '%akismet%'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->commentmeta}" );

		return TRUE;
	}

	private function postmeta_editdata()
	{
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_edit_last'" );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_edit_lock'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return TRUE;
	}

	private function postmeta_oldslug()
	{
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_wp_old_slug'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return TRUE;
	}
}

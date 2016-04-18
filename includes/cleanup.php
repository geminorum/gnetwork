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
			_x( 'Cleanup', 'Cleanup Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_settings()
	{
		$settings = array();
        $confirm  = self::getButtonConfirm();

		$settings['_transient'][] = array(
			'field'       => 'transient_purge',
			'type'        => 'button',
			'title'       => _x( 'Transient', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Purge Expired Transient Data', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Expired', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_transient'][] = array(
			'field'       => 'transient_purge_all',
			'type'        => 'button',
			'description' => _x( 'Purge All Transient Data', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge All', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		if ( is_main_site() ) {

			$settings['_transient'][] = array(
				'field'       => 'transient_purge_site',
				'type'        => 'button',
				'description' => _x( 'Purge Expired Network Transient Data', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'Purge Network Expired', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
				'values'      => $confirm,
			);

			$settings['_transient'][] = array(
				'field'       => 'transient_purge_site_all',
				'type'        => 'button',
				'description' => _x( 'Purge All Network Transient Data', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'Purge All Network', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
				'values'      => $confirm,
			);
		}

		$settings['_comments'][] = array(
			'field'       => 'akismet_purge_meta',
			'type'        => 'button',
			'title'       => _x( 'Akismet', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes akismet related meta from comments', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Metadata', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_comments'][] = array(
			'field'       => 'purge_comment_agent',
			'type'        => 'button',
			'title'       => _x( 'Comments', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes user agent field of comments', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge User Agents', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_comments'][] = array(
			'field'       => 'optimize_tables',
			'type'        => 'button',
			'title'       => _x( 'Comment Tables', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Checks for orphaned comment metas and optimize tables', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Orphaned & Optimize', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		);

		$settings['_posts'][] = array(
			'field'       => 'delete_post_editmeta',
			'type'        => 'button',
			'title'       => _x( 'Post Edit Meta', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes posts last edit user and lock metas', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Delete Last User & Lock', 'Cleanup Module', GNETWORK_TEXTDOMAIN ),
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

			else if ( isset( $_POST['optimize_tables'] ) )
				$message = $this->optimize_tables() ? 'optimized' : 'error';

			else if ( isset( $_POST['purge_comment_agent'] ) )
				$message = $this->purge_comment_agent() ? 'purged' : 'error';

			else if ( isset( $_POST['delete_post_editmeta'] ) )
				$message = $this->delete_post_editmeta() ? 'purged' : 'error';

			else if ( isset( $_POST['akismet_purge_meta'] ) )
				$message = $this->akismet_purge_meta() ? 'purged' : 'error';

			else
				return FALSE;

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
			$scripts->add( 'jquery', FALSE, array( 'jquery-core' ), '1.12.3' );
		}
	}

	// @SOURCE: http://justintadlock.com/archives/2011/06/13/removing-menu-pages-from-the-wordpress-admin
	public function admin_menu_late()
	{
		if ( ! self::cuc( 'update_plugins' ) ) {
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

	// @SEE: http://www.catswhocode.com/blog/10-useful-sql-queries-to-clean-up-your-wordpress-database
	private function optimize_tables()
	{
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_id FROM {$wpdb->comments})" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->comments}" );
		$wpdb->query( "OPTIMIZE TABLE {$wpdb->commentmeta}" );

		return TRUE;
	}

	private function purge_comment_agent()
	{
		global $wpdb;

		$wpdb->query( "UPDATE {$wpdb->comments} SET comment_agent = ''" );

		return TRUE;
	}

	private function akismet_purge_meta()
	{
		global $wpdb;

		// $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE 'meta_key' IN ( 'akismet_result', 'akismet_history', 'akismet_user', 'akismet_user_result' ) " );
		$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '%akismet%'" );

		return TRUE;
	}

	private function delete_post_editmeta()
	{
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_edit_last'" );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_edit_lock'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return TRUE;
	}
}

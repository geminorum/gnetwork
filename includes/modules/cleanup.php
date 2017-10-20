<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\WordPress;

class Cleanup extends gNetwork\Module
{

	protected $key     = 'cleanup';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 99, 'late' );

		$this->action( 'admin_menu', 0, 999, 'late' );
		$this->action( 'admin_enqueue_scripts', 0, 999 );

		// SEE: http://stephanis.info/2014/08/13/on-jetpack-and-auto-activating-modules
		add_filter( 'jetpack_get_default_modules', '__return_empty_array' );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Cleanup', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_settings()
	{
		$settings   = [];
		$confirm    = Settings::getButtonConfirm();
		$superadmin = WordPress::isSuperAdmin();
		$multisite  = is_multisite();

		$settings['_options'][] = [
			'field'       => 'purge_options_blog',
			'type'        => 'button',
			'title'       => _x( 'Options', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes blog obsolete options', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Blog Options', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_transient'][] = [
			'field'       => 'transient_purge',
			'type'        => 'button',
			'title'       => _x( 'Transient', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes Expired Transient Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Expired', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_transient'][] = [
			'field'       => 'transient_purge_all',
			'type'        => 'button',
			'description' => _x( 'Removes All Transient Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge All', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		if ( is_main_site() ) {

			if ( $multisite && $superadmin ) {

				$settings['_options'][] = [
					'field'       => 'purge_options_site',
					'type'        => 'button',
					'description' => _x( 'Removes network obsolete options', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'Purge Network Options', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => $confirm,
				];

				$settings['_transient'][] = [
					'field'       => 'transient_purge_site',
					'type'        => 'button',
					'description' => _x( 'Removes Expired Network Transient Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'Purge Network Expired', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => $confirm,
				];

				$settings['_transient'][] = [
					'field'       => 'transient_purge_site_all',
					'type'        => 'button',
					'description' => _x( 'Removes All Network Transient Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'Purge All Network', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => $confirm,
				];
			}

			$settings['_users'][] = [
				'field'       => 'users_defaultmeta',
				'type'        => 'button',
				'title'       => _x( 'User Meta', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Removes Default Meta Stored for Each User', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'Purge Default Meta', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'values'      => $confirm,
			];

			$settings['_users'][] = [
				'field'       => 'users_contactmethods',
				'type'        => 'button',
				'description' => _x( 'Removes Empty Contact Methods Stored for Each User', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'Purge Empty Contact Methods', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'values'      => $confirm,
			];

			$settings['_users'][] = [
				'field'       => 'users_last_activity',
				'type'        => 'button',
				'description' => _x( 'Removes BuddyPress Last Activity Back-Comp Meta Stored for Each User', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => _x( 'Back-Comp Last Activity', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
				'after'       => Settings::fieldAfterIcon( 'http://wp.me/pLVLj-gc' ),
				'values'      => $confirm,
			];
		}

		$settings['_posts'][] = [
			'field'       => 'postmeta_editdata',
			'type'        => 'button',
			'title'       => _x( 'Post Meta', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Removes Posts Last Edit User and Lock Data', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Last User & Post Lock Metadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_posts'][] = [
			'field'       => 'postmeta_oldslug',
			'type'        => 'button',
			'description' => _x( 'Removes the Previous URL Slugs for Posts', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Old Slug Redirect Metadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_posts'][] = [
			'field'       => 'postmeta_obsolete',
			'type'        => 'button',
			'description' => _x( 'Removes the obsolete post meta keys.', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Obsolete Post Matadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_posts'][] = [
			'field'       => 'thumbnail_orphanedmeta',
			'type'        => 'button',
			'description' => _x( 'Checks for Orphaned Thumbnail Metas', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Orphaned Featured Image Matadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_comments'][] = [
			'field'       => 'comments_orphanedmeta',
			'type'        => 'button',
			'title'       => _x( 'Comments', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Checks for Orphaned Comment Metas', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Orphaned Matadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_comments'][] = [
			'field'       => 'comments_akismetmeta',
			'type'        => 'button',
			'description' => _x( 'Removes Akismet Related Metadata from Comments', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge Akismet Metadata', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_comments'][] = [
			'field'       => 'comments_agentfield',
			'type'        => 'button',
			'description' => _x( 'Removes User Agent Fields from Comments', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Purge User Agent Fields', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		$settings['_comments'][] = [
			'field'       => 'comments_oldposts',
			'type'        => 'button',
			'description' => _x( 'Disables comments and pings on posts published before <b>last month</b>.', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => _x( 'Close Old Posts Comment Form', 'Modules: Cleanup: Settings', GNETWORK_TEXTDOMAIN ),
			'values'      => $confirm,
		];

		return $settings;
	}

	// no buttons
	public function default_buttons( $sub = NULL ) {}

	protected function settings_update( $sub )
	{
		if ( ! empty( $_POST ) && 'update' == $_POST['action'] ) {

			$this->check_referer( $sub );

			if ( isset( $_POST['transient_purge'] ) )
				$message = $this->purge_transient_data( FALSE, TRUE );

			else if ( isset( $_POST['transient_purge_all'] ) )
				$message = $this->purge_transient_data( FALSE, FALSE );

			else if ( isset( $_POST['transient_purge_site'] ) )
				$message = $this->purge_transient_data( TRUE, TRUE );

			else if ( isset( $_POST['transient_purge_site_all'] ) )
				$message = $this->purge_transient_data( TRUE, FALSE );

			else if ( isset( $_POST['users_defaultmeta'] ) )
				$message = $this->users_defaultmeta();

			else if ( isset( $_POST['purge_options_site'] ) )
				$message = $this->purge_options_site();

			else if ( isset( $_POST['purge_options_blog'] ) )
				$message = $this->purge_options_blog();

			else if ( isset( $_POST['users_contactmethods'] ) )
				$message = $this->users_contactmethods();

			else if ( isset( $_POST['users_last_activity'] ) )
				$message = $this->users_last_activity();

			else if ( isset( $_POST['postmeta_editdata'] ) )
				$message = $this->postmeta_editdata();

			else if ( isset( $_POST['postmeta_oldslug'] ) )
				$message = $this->postmeta_oldslug();

			else if ( isset( $_POST['postmeta_obsolete'] ) )
				$message = $this->postmeta_obsolete();

			else if ( isset( $_POST['thumbnail_orphanedmeta'] ) )
				$message = $this->thumbnail_orphanedmeta();

			else if ( isset( $_POST['comments_orphanedmeta'] ) )
				$message = $this->comments_orphanedmeta();

			else if ( isset( $_POST['comments_agentfield'] ) )
				$message = $this->comments_agentfield();

			else if ( isset( $_POST['comments_akismetmeta'] ) )
				$message = $this->comments_akismetmeta();

			else if ( isset( $_POST['comments_oldposts'] ) )
				$message = $this->comments_oldposts();

			else
				$message = 'huh';

			WordPress::redirectReferer( $message );
		}
	}

	public function init_late()
	{
		remove_action( 'wp_head', 'se_global_head' ); // by: Search Everything / http://wordpress.org/plugins/search-everything/
		remove_action( 'rightnow_end', [ 'Akismet_Admin', 'rightnow_stats' ] ); // by: Akismet

		if ( defined( 'WPCF7_VERSION' ) ) {

			if ( defined( 'WPCF7_AUTOP' ) && WPCF7_AUTOP )
				$this->filter( 'wpcf7_form_elements' );

			$this->filter_false( 'wpcf7_load_css', 15 );
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

	// does not apply the `autop()` to the form content
	// ADOPTED FROM: Contact Form 7 Controls - v0.4.0 - 20170926
	// @SOURCE: https://github.com/kasparsd/contact-form-7-extras
	public function wpcf7_form_elements( $form )
	{
		$instance = \WPCF7_ContactForm::get_current();
		$manager  = \WPCF7_ShortcodeManager::get_instance();

		$form = $manager->do_shortcode( get_post_meta( $instance->id(), '_form', TRUE ) );

		$instance->set_properties( [ 'form' => $form ] );

		return $form;
	}

	// @REF: https://core.trac.wordpress.org/ticket/20316
	// @REF: https://core.trac.wordpress.org/changeset/25838
	private function purge_transient_data( $site = TRUE, $time = TRUE )
	{
		global $wpdb;

		if ( $site ) {

			if ( $time ) {

				$query = $wpdb->prepare( "DELETE a, b
					FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
					WHERE a.meta_key LIKE %s
					AND a.meta_key NOT LIKE %s
					AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
					AND b.meta_value < %d
				", $wpdb->esc_like( '_site_transient_' ).'%', $wpdb->esc_like( '_site_transient_timeout_' ).'%', time() );

			} else {

				$query = $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_site_transient_' ).'%' );
			}

		} else {

			if ( $time ) {

				$query = $wpdb->prepare( "DELETE a, b
					FROM {$wpdb->options} a, {$wpdb->options} b
					WHERE a.option_name LIKE %s
					AND a.option_name NOT LIKE %s
					AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
					AND b.option_value < %d
				", $wpdb->esc_like( '_transient_' ).'%', $wpdb->esc_like( '_transient_timeout_' ).'%', time() );

			} else {

				$query = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_' ).'%' );
			}
		}

		$count = $wpdb->query( $query );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'nochange';
	}

	// FIXME: DROP THIS
	// @REF: http://wordpress.stackexchange.com/a/6652
	private function purge_transient_data_OLD( $site = FALSE, $time = FALSE )
	{
		if ( wp_using_ext_object_cache() )
			return 'wrong';

		global $wpdb;

		$count = 0;

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

		foreach ( $wpdb->get_col( $query ) as $transient ) {
			if ( $site ) {

				if ( delete_site_transient( str_replace( '_site_transient_timeout_', '', $transient ) ) )
					$count++;
			} else {

				if ( delete_transient( str_replace( '_transient_timeout_', '', $transient ) ) )
					$count++;
			}
		}

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'nochange';
	}

	private function users_defaultmeta()
	{
		global $wpdb;

		$count = 0;

		$meta_keys = [
			'nickname'             => '',
			'first_name'           => '',
			'last_name'            => '',
			'description'          => '',
			'rich_editing'         => 'true',
			'comment_shortcuts'    => 'false',
			'admin_color'          => 'fresh',
			'use_ssl'              => 0,
			'show_admin_bar_front' => 'true',
			'locale'               => '',
		];

		foreach ( $meta_keys as $key => $val )
			$count += $wpdb->query( $wpdb->prepare( "
				DELETE FROM {$wpdb->usermeta}
				WHERE meta_key = %s
				AND meta_value = %s
			", $key, $val ) );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->usermeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function users_contactmethods()
	{
		global $wpdb;

		$count = 0;

		// old wp contact methods
		$meta_keys = array_merge( wp_get_user_contact_methods(), [
			'yim'    => '',
			'jabber' => '',
		] );

		foreach ( $meta_keys as $key => $val )
			$count += $wpdb->query( $wpdb->prepare( "
				DELETE FROM {$wpdb->usermeta}
				WHERE meta_key = %s
				AND meta_value = ''
			", $key ) );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->usermeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function users_last_activity()
	{
		global $wpdb;

		$count = $wpdb->query( $wpdb->prepare( "
			DELETE FROM {$wpdb->usermeta}
			WHERE meta_key = %s
		", 'last_activity' ) );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->usermeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function comments_orphanedmeta()
	{
		global $wpdb;

		$count = $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_id FROM {$wpdb->comments})" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->commentmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function comments_agentfield()
	{
		global $wpdb;

		$count = $wpdb->query( "UPDATE {$wpdb->comments} SET comment_agent = ''" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->comments}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function comments_akismetmeta()
	{
		global $wpdb;

		// $count = $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE 'meta_key' IN ( 'akismet_result', 'akismet_history', 'akismet_user', 'akismet_user_result' ) " );
		$count = $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '%akismet%'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->commentmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function postmeta_editdata()
	{
		global $wpdb;

		$count = 0;

		$count += $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_edit_last'" );
		$count += $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_edit_lock'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function postmeta_oldslug()
	{
		global $wpdb;

		$count = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_wp_old_slug'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function postmeta_obsolete()
	{
		global $wpdb;

		$count = 0;

		$count += $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '%_yoast_wpseo_%'" ); // Yoast SEO
		$count += $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '%_ad_participant_%'" ); // Assignment Desk
		$count += $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '%_ad_pitched_by_%'" ); // Assignment Desk
		$count += $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '%_ad_total_%'" ); // Assignment Desk

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function thumbnail_orphanedmeta()
	{
		global $wpdb;

		$count = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value NOT IN (SELECT ID FROM {$wpdb->posts})" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function purge_options_site()
	{
		global $wpdb;

		$count = 0;

		$count += $wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '%gletter_list_%'" );
		$count += $wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key = 'gnetwork_".GNETWORK_NETWORK_EXTRAMENU."'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->sitemeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	private function purge_options_blog()
	{
		global $wpdb;

		$count = 0;

		$count += $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%theme_mods_%'" );
		$count += $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%limit_login_%'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->options}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	// @REF: https://goo.gl/ZErzXR
	private function comments_oldposts()
	{
		global $wpdb;

		$count = 0;

		$lastmonth = gmdate( 'Y-m-d H:i:s', strtotime( '-1 month' ) );

		$count += $wpdb->query( "UPDATE {$wpdb->posts} SET comment_status = 'closed' WHERE post_date_gmt < '{$lastmonth}' AND post_status = 'publish'" );
		$count += $wpdb->query( "UPDATE {$wpdb->posts} SET ping_status = 'closed' WHERE post_date_gmt < '{$lastmonth}' AND post_status = 'publish'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->posts}" );

		return $count ? [
			'message' => 'closed',
			'count'   => $count,
		] : 'optimized';
	}
}

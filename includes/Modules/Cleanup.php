<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Date;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Cleanup extends gNetwork\Module
{

	protected $key     = 'cleanup';
	protected $network = FALSE;
	protected $user    = FALSE;
	protected $front   = FALSE;

	public function setup_menu( $context )
	{
		$this->register_tool( _x( 'Cleanup', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_settings()
	{
		$settings   = [];
		$confirm    = Settings::getButtonConfirm();
		$superadmin = WordPress::isSuperAdmin();
		$multisite  = is_multisite();
		$sitemeta   = function_exists( 'is_site_meta_supported' ) && is_site_meta_supported();

		$settings['_options'][] = [
			'field'       => 'purge_options_site',
			'type'        => 'button',
			'title'       => _x( 'Options', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'description' => _x( 'Removes site obsolete option data.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Blog Options', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_transient'][] = [
			'field'       => 'transient_purge_site',
			'type'        => 'button',
			'title'       => _x( 'Transient', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'description' => _x( 'Removes site expired transient cache.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Expired', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_transient'][] = [
			'field'       => 'transient_purge_site_all',
			'type'        => 'button',
			'description' => _x( 'Removes all site transient cache.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge All', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		if ( $multisite && $sitemeta )
			$settings['_sitemeta'][] = [
				'field'       => 'purge_sitemeta',
				'type'        => 'button',
				'title'       => _x( 'Site Meta', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'description' => _x( 'Removes cached site meta data.', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'default'     => _x( 'Purge Site Meta', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'values'      => $confirm,
			];

		if ( is_main_site() ) {

			if ( $multisite && $superadmin ) {

				$settings['_options'][] = [
					'field'       => 'purge_options_network',
					'type'        => 'button',
					'description' => _x( 'Removes network obsolete option data.', 'Modules: Cleanup: Settings', 'gnetwork' ),
					'default'     => _x( 'Purge Network Options', 'Modules: Cleanup: Settings', 'gnetwork' ),
					'values'      => $confirm,
				];

				$settings['_transient'][] = [
					'field'       => 'transient_purge_network',
					'type'        => 'button',
					'description' => _x( 'Removes network expired transient cache.', 'Modules: Cleanup: Settings', 'gnetwork' ),
					'default'     => _x( 'Purge Network Expired', 'Modules: Cleanup: Settings', 'gnetwork' ),
					'values'      => $confirm,
				];

				$settings['_transient'][] = [
					'field'       => 'transient_purge_network_all',
					'type'        => 'button',
					'description' => _x( 'Removes all network transient cache.', 'Modules: Cleanup: Settings', 'gnetwork' ),
					'default'     => _x( 'Purge All Network', 'Modules: Cleanup: Settings', 'gnetwork' ),
					'values'      => $confirm,
				];

				if ( $sitemeta )
					$settings['_sitemeta'][] = [
						'field'       => 'purge_sitemeta_all',
						'type'        => 'button',
						'description' => _x( 'Removes all cached site meta data.', 'Modules: Cleanup: Settings', 'gnetwork' ),
						'default'     => _x( 'Purge All Site Meta', 'Modules: Cleanup: Settings', 'gnetwork' ),
						'values'      => $confirm,
					];
			}

			$settings['_users'][] = [
				'field'       => 'users_defaultmeta',
				'type'        => 'button',
				'title'       => _x( 'User Meta', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'description' => _x( 'Removes default meta stored for each user.', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'default'     => _x( 'Purge Default Meta', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'values'      => $confirm,
			];

			$settings['_users'][] = [
				'field'       => 'users_contactmethods',
				'type'        => 'button',
				'description' => _x( 'Removes empty contact methods stored for each user.', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'default'     => _x( 'Purge Empty Contact Methods', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'values'      => $confirm,
			];

			$settings['_users'][] = [
				'field'       => 'users_last_activity',
				'type'        => 'button',
				'description' => _x( 'Removes BuddyPress last activity back-comp meta stored for each user.', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'default'     => _x( 'Back-Comp Last Activity', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'after'       => Settings::fieldAfterIcon( 'http://wp.me/pLVLj-gc' ),
				'values'      => $confirm,
			];

			$settings['_users'][] = [
				'field'       => 'users_meta_obsolete',
				'type'        => 'button',
				'description' => _x( 'Removes obsolete user meta keys.', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'default'     => _x( 'Purge Obsolete User Metadata', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'values'      => $confirm,
			];
		}

		$settings['_posts'][] = [
			'field'       => 'postmeta_editdata',
			'type'        => 'button',
			'title'       => _x( 'Post Meta', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'description' => _x( 'Removes last edit user and lock meta stored for each post.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Last User & Post Lock Metadata', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_posts'][] = [
			'field'       => 'postmeta_oldslug',
			'type'        => 'button',
			'description' => _x( 'Removes the old slug stored for each post.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Old Slug Redirect Metadata', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_posts'][] = [
			'field'       => 'postmeta_obsolete',
			'type'        => 'button',
			'description' => _x( 'Removes obsolete post meta data.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Obsolete Post Matadata', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_posts'][] = [
			'field'       => 'postmeta_orphaned',
			'type'        => 'button',
			'description' => _x( 'Removes orphaned post meta-data from database.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Orphaned Post Matadata', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_posts'][] = [
			'field'       => 'thumbnail_orphanedmeta',
			'type'        => 'button',
			'description' => _x( 'Checks for orphaned thumbnail meta data.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Orphaned Featured Image Matadata', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_comments'][] = [
			'field'       => 'comments_orphanedmeta',
			'type'        => 'button',
			'title'       => _x( 'Comments', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'description' => _x( 'Checks for orphaned comment meta data.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Orphaned Matadata', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_comments'][] = [
			'field'       => 'comments_akismetmeta',
			'type'        => 'button',
			'description' => _x( 'Removes Akismet related comment meta data.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge Akismet Metadata', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_comments'][] = [
			'field'       => 'comments_agentfield',
			'type'        => 'button',
			'description' => _x( 'Removes user agent data from comments.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Purge User Agent Fields', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		$settings['_comments'][] = [
			'field'       => 'comments_oldposts',
			'type'        => 'button',
			'description' => _x( 'Disables comments and pings on posts published before <b>last month</b>.', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'default'     => _x( 'Close Comments on Old Posts', 'Modules: Cleanup: Settings', 'gnetwork' ),
			'values'      => $confirm,
		];

		if ( $superadmin )
			$settings['_files'][] = [
				'field'       => 'files_clean_core',
				'type'        => 'button',
				'title'       => _x( 'Files', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'description' => _x( 'Removes unnecessary files on your WordPress install.', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'default'     => _x( 'Clean Core Files', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'values'      => $confirm,
			];

		if ( $superadmin )
			$settings['_tables'][] = [
				'field'       => 'db_table_obsolete',
				'type'        => 'button',
				'title'       => _x( 'DB Tables', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'description' => _x( 'Removes obsolete database tables on your WordPress install.', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'default'     => _x( 'Drop Obsolete Tables', 'Modules: Cleanup: Settings', 'gnetwork' ),
				'values'      => $confirm,
			];

		return $settings;
	}

	protected function tools_setup( $sub = NULL )
	{
		$this->register_settings();
	}

	public function render_tools( $uri, $sub = 'general' )
	{
		Settings::headerTitle( _x( 'Cleanup Tools', 'Modules: Cleanup', 'gnetwork' ) );

		$this->render_form_start( $uri, $sub, 'bulk', 'tools' );

		do_settings_sections( $this->hook_base( $sub ) );

		$this->render_form_end( $uri, $sub, 'bulk', 'tools' );
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

			$this->check_referer( $sub, 'tools' );

			if ( isset( $_POST['transient_purge_site'] ) )
				$message = $this->purge_transient_data( FALSE, TRUE );

			else if ( isset( $_POST['transient_purge_site_all'] ) )
				$message = $this->purge_transient_data( FALSE, FALSE );

			else if ( isset( $_POST['transient_purge_network'] ) )
				$message = $this->purge_transient_data( TRUE, TRUE );

			else if ( isset( $_POST['transient_purge_network_all'] ) )
				$message = $this->purge_transient_data( TRUE, FALSE );

			else if ( isset( $_POST['purge_sitemeta'] ) )
				$message = $this->purge_sitemeta();

			else if ( isset( $_POST['purge_sitemeta_all'] ) )
				$message = $this->purge_sitemeta( TRUE );

			else if ( isset( $_POST['users_defaultmeta'] ) )
				$message = $this->users_defaultmeta();

			else if ( isset( $_POST['purge_options_network'] ) )
				$message = $this->purge_options_network();

			else if ( isset( $_POST['purge_options_site'] ) )
				$message = $this->purge_options_site();

			else if ( isset( $_POST['users_contactmethods'] ) )
				$message = $this->users_contactmethods();

			else if ( isset( $_POST['users_last_activity'] ) )
				$message = $this->users_last_activity();

			else if ( isset( $_POST['users_meta_obsolete'] ) )
				$message = $this->users_meta_obsolete();

			else if ( isset( $_POST['postmeta_editdata'] ) )
				$message = $this->postmeta_editdata();

			else if ( isset( $_POST['postmeta_oldslug'] ) )
				$message = $this->postmeta_oldslug();

			else if ( isset( $_POST['postmeta_obsolete'] ) )
				$message = $this->postmeta_obsolete();

			else if ( isset( $_POST['postmeta_orphaned'] ) )
				$message = $this->postmeta_orphaned();

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

			else if ( isset( $_POST['db_table_obsolete'] ) )
				$message = $this->db_table_obsolete();

			else if ( isset( $_POST['files_clean_core'] ) )
				$message = self::files_clean_core();

			else
				$message = 'huh';

			WordPress::redirectReferer( $message );
		}
	}

	// @SEE: `delete_expired_transients()`
	// @REF: https://core.trac.wordpress.org/ticket/20316
	// @REF: https://core.trac.wordpress.org/changeset/25838
	private function purge_transient_data( $network = TRUE, $time = TRUE )
	{
		global $wpdb;

		if ( $network ) {

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

	private function purge_sitemeta( $all = FALSE )
	{
		$count = $all
			? gNetwork()->site->network_delete_sitemeta()
			: gNetwork()->site->delete_sitemeta();

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
			'primary_blog'         => '',
			'source_domain'        => '',
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

	// FIXME: remove `googleplus`
	private function users_contactmethods()
	{
		global $wpdb;

		$count    = 0;
		$metakeys = array_merge( array_keys( wp_get_user_contact_methods() ), [
			'feed_key',
			'aim',     // old wp contact method
			'yim',     // old wp contact method
			'jabber',  // old wp contact method
			'ssn',
			'identity_number',
			'identity',
			'mobile',
			'phone',
			'googleplus',
			'instagram',
			'telegram',
			'facebook',
			'twitter',
			'wikipedia',
			'youtube',
			'tumblr',
			'soundcloud',
			'pinterest',
			'myspace',
			'linkedin',
			'url',
			'email',
			'mail',

			// WooCommerce
			'shipping_first_name',
			'shipping_last_name',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_company',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'shipping_country',
			'shipping_method',
			'shipping_phone',
			'billing_last_name',
			'billing_first_name',
			'billing_postcode',
			'billing_country',
			'billing_district',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_email',
			'billing_phone',
			'billing_company',
			'_billing_mobile',
		] );

		foreach ( $metakeys as $key )
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

	private function users_meta_obsolete()
	{
		global $wpdb;

		$count    = 0;
		$metakeys = [
			'closedpostboxes_',
			'dismissed_update_notice',
			'dismissed_wp_pointers',
			'_yoast_alerts_dismissed',
			'elementor_introduction',
			'elementor_admin_notices',
			'wppb_review_request_dismiss_notification',
			'tgmpa_dismissed_notice_tgmpa',
			'wooccm-user-rating',
			'wc_last_active',
			'gform_recent_forms',
			'dark_mode',
			'ef_calendar_filters',
			'gmember_display_name',
		];

		foreach ( $metakeys as $key )
			$count += $wpdb->query( $wpdb->prepare( "
				DELETE FROM {$wpdb->usermeta}
				WHERE meta_key = %s
			", $key ) );

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

		$count = $wpdb->query( "UPDATE {$wpdb->comments} SET comment_agent = '' WHERE comment_type = 'comment'" );

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
		$count = $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE 'akismet%'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->commentmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	// TODO: `_wp_old_date`
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

		$keys = $this->filters( 'postmeta_obsolete_keys', [
			'_pingme',
			'_encloseme',
			'_trackbackme',
		] );

		foreach ( $keys as $key )
			$count += $wpdb->query( $wpdb->prepare( "
				DELETE FROM {$wpdb->postmeta}
				WHERE meta_key = %s
			", $key ) );

		$ins = $this->filters( 'postmeta_obsolete_ins', [
			'_gmeta' => [ '', 'a:1:{i:0;s:0:\"\";}' ],
		] );

		foreach ( $ins as $key => $val )
			$count += $wpdb->query( $wpdb->prepare( "
				DELETE FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				AND meta_value IN ( '".implode( "', '", esc_sql( $val ) )."' )
			", $key ) );

		$equals = $this->filters( 'postmeta_obsolete_equals', [
			'_ge_series'        => 'a:0:{}',
			'_wp_page_template' => 'default',
		] );

		foreach ( $equals as $key => $val )
			$count += $wpdb->query( $wpdb->prepare( "
				DELETE FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				AND meta_value = %s
			", $key, $val ) );

		$likes = $this->filters( 'postmeta_obsolete_likes', [
			'_yoast_wpseo_%', // Yoast SEO
			'_ad_participant_%', // Assignment Desk
			'_ad_pitched_by_%', // Assignment Desk
			'_ad_total_%', // Assignment Desk
		] );

		foreach ( $likes as $like )
			$count += $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '{$like}'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	// @REF: https://www.speakinginbytes.com/2013/02/delete-orphaned-post-meta-data-in-wordpress/
	// @REF: https://mehulgohil.com/blog/orphaned-data/
	private function postmeta_orphaned()
	{
		global $wpdb;

		// $count = $wpdb->query( "DELETE FROM {$wpdb->postmeta} AS meta LEFT JOIN {$wpdb->posts} AS posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL" );
		// $count = $wpdb->query( "DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL" );
		// $count = $wpdb->query( "SELECT pm.* FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL;" );
		$count = $wpdb->query( "DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );

		return $count ? [
			'message' => 'purged',
			'count'   => $count,
		] : 'optimized';
	}

	// FIXME: filter meta for multiple meta orphaned
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

	private function purge_options_network()
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

	private function purge_options_site()
	{
		global $wpdb;

		$count = 0;

		$options = [
			// 'finished_updating_comment_type',
			// 'finished_splitting_shared_terms',
			// 'show_comments_cookies_opt_in',

			'use_balanceTags',
			'fileupload_url', // old upload path
			// 'hack_file', // dep since wp1.5.0

			'gletter_components',
			'gnetwork_redirect',
			'widget_gnetwork-gplusbadge-widget',
			'widget_k2-asides',
			'widget_k2-about',
			'widget_aagwidget',

			'jarchive_widget',
			'mps_calendar_widget',

			// 'theme_mods_',
			// 'theme_mods_twentyeleven',
			// 'theme_mods_twentyfifteen',
			// 'theme_mods_twentysixteen',
			// 'theme_mods_publish',
			// 'theme_mods_bp-default',
			'theme_mods_gtwentyeleven',
			'theme_mods_gtwentytwelve',
			'theme_mods_gbp-default',
			'theme_mods_gari',
			'theme_mods_k2',
			'mods_K2',

			'loginlockdownAdminOptions',
			'loginlockdown_db1_version',
			'loginlockdown_db2_version',

			// GoogleSitemapGenerator
			'sm_options',
			'sm_status',

			'mps_jd_options_4.1',
			'1_log-viewer_settings',

			'dashboard_widget_options', // @REF: https://developer.wordpress.org/apis/handbook/dashboard-widgets/

			'k2blogornoblog',
			'k2headerimage',
			'k2stylesurl',
			'k2stylesdir',
			'k2stylespath',
			'k2styleinfo',
			'k2styles',
			'k2ajaxdonejs',
			'k2widgetoptions',
			'k2entrymeta2',
			'k2entrymeta1',
			'k2animations',
			'k2columns',
			'k2sidebarmanager',
			'k2archives',
			'k2rollingarchives',
			'k2livesearch',
			'k2asidescategory',
			'k2version',

			'dnh_dismissed_notices',

			// '_bp_ignore_deprecated_code',
			// '_bp_enable_heartbeat_refresh',
			// '_bp_force_buddybar',
			// '_bp_retain_bp_default',
			// 'bp-blogs-first-install',
			// 'bp-deactivated-components',
			// 'bp-xprofile-base-group-name',
			// 'bp-xprofile-fullname-field-name',
			// 'bp-emails-unsubscribe-salt',
			// 'bp_disable_blogforum_comments',
			// 'bp-disable-account-deletion',

			'bb-config-location',

			'bxcft_activated',
			'bxcft_notices',

			// [Really Simple SSL](https://wordpress.org/plugins/really-simple-ssl/)
			'rlrsssl_options',
		];

		if ( ! is_main_site() )
			$options = array_merge( $options, [
				'odb_rvg_options',
				'odb_rvg_excluded_tabs',
			] );

		foreach ( $options as $option )
			$count += $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name = '{$option}'" );

		$likes = [
			// 'theme\_mods\_%',
			'limit\_login\_%',
			'wpb2d\-%',
			'gmember\_%',
			'widget\_gmember\_%',
			'wpsupercache\_%',
			'bwp\_gxs\_%',
			'\_transient\_feed\_%',
			'widget\_bp\_core\_%',
		];

		foreach ( $likes as $like )
			$count += $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$like}'" );

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

		$lastmonth = gmdate( Date::MYSQL_FORMAT, strtotime( '-1 month' ) );

		$count += $wpdb->query( "UPDATE {$wpdb->posts} SET comment_status = 'closed' WHERE post_date_gmt < '{$lastmonth}' AND post_status = 'publish'" );
		$count += $wpdb->query( "UPDATE {$wpdb->posts} SET ping_status = 'closed' WHERE post_date_gmt < '{$lastmonth}' AND post_status = 'publish'" );

		$wpdb->query( "OPTIMIZE TABLE {$wpdb->posts}" );

		return $count ? [
			'message' => 'closed',
			'count'   => $count,
		] : 'optimized';
	}

	private function db_table_obsolete()
	{
		global $wpdb;

		$count  = 0;
		$tables = [
			"{$wpdb->prefix}login_fails",
			"{$wpdb->prefix}lockdowns",
		];

		foreach ( $tables as $table )
			$count += $wpdb->query( 'DROP TABLE IF EXISTS '.$table );

		return $count ? [
			'message' => 'emptied',
			'count'   => $count,
		] : 'nochange';
	}

	// public/static is for `_core_updated_successfully` hook
	public static function files_clean_core( $message = FALSE )
	{
		$count = 0;
		$files = [
			'license.txt',
			'readme.html',
			'wp-config-sample.php',
			'wp-admin/install.php',
		];

		foreach ( $files as $file ) {

			$path = ABSPATH.$file;

			if ( ! file_exists( $path ) )
				continue;

			if ( TRUE !== unlink( $path ) )
				continue;

			if ( $message )
				/* translators: %s: filename */
				HTML::desc( sprintf( _x( 'Removing %s &hellip;', 'Modules: Update', 'gnetwork' ), $file ) );

			$count++;
		}

		return [
			'message' => 'deleted',
			'count'   => $count,
		];
	}
}

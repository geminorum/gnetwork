<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Site extends gNetwork\Module
{

	protected $key = 'general';

	protected function setup_actions()
	{
		if ( ! is_multisite() )
			return FALSE;

		if ( GNETWORK_LARGE_NETWORK_IS )
			$this->filter( 'wp_is_large_network', 3, 9 );

		$this->action( 'wpmu_new_blog', 6, 12 );
		$this->action( 'ms_site_not_found', 3, 12 );

		if ( is_admin() ) {

			$this->action( 'admin_menu' );

		} else {

			$this->action( 'get_header' );
		}

		if ( $this->options['resync_sitemeta'] )
			$this->setup_meta_sync();
	}

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'Global', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'ssl_support'       => 0,
			'resync_sitemeta'   => 0,
			'redirect_notfound' => '',
			'admin_locale'      => 'en_US',
			'access_denied'     => '',
			'denied_message'    => '',
			'denied_extra'      => '',
			'list_sites'        => '1',
			'lookup_ip_service' => 'https://redirect.li/map/?ip=%s',
		];
	}

	public function default_settings()
	{
		$settings = [];

		$settings['_general'][] = [
			'field'       => 'ssl_support',
			'title'       => _x( 'SSL', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Enables SSL tools to support the network sites.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
		];

		if ( function_exists( 'is_site_meta_supported' ) )
			$settings['_general'][] = [
				'field'       => 'resync_sitemeta',
				'title'       => _x( 'Sync Metadata', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Re-syncs the site meta network-wide automatically.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
			];

		$settings['_general'][] = [
			'field'       => 'redirect_notfound',
			'type'        => 'url',
			'title'       => _x( 'Site Not Found', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Redirects to when the network can be determined but a site cannot.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
		];

		if ( class_exists( __NAMESPACE__.'\\Locale' ) ) {
			$settings['_general'][] = [
				'field'       => 'admin_locale',
				'type'        => 'select',
				'title'       => _x( 'Network Locale', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Overrides network admin language, despite of the main site locale.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => 'en_US',
				'values'      => Arraay::sameKey( Locale::available() ),
			];
		}

		$settings['_denied'] = [
			[
				'field'       => 'access_denied',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Access Denied', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Displays when access to an admin page is denied. Leave empty to use default or <code>0</code> to disable.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'placeholder' => __( 'Sorry, you are not allowed to access this page.' ),
			],
			[
				'field'       => 'denied_message',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Denied Message', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Displays this message when a user tries to view a site\'s dashboard they do not have access to. Leave empty to use default or <code>0</code> to disable. <code>%1$s</code>: Blog Name', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'placeholder' => __( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ),
			],
			[
				'field'       => 'denied_extra',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Extra Message', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Displays this message before the list of sites. Leave empty to use default or <code>0</code> to disable.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'placeholder' => __( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.' ),
			],
			[
				'field'       => 'list_sites',
				'title'       => _x( 'List of Sites', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'Displays the current user list of sites after access denied message.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => '1',
			],
		];

		$settings['_misc'][] = [
			'field'       => 'lookup_ip_service',
			'type'        => 'text',
			'title'       => _x( 'Lookup IP URL', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'URL template to to use for looking up IP adresses. Will replace <code>%s</code> with the IP.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
			'default'     => 'https://redirect.li/map/?ip=%s',
			'dir'         => 'ltr',
			'after'       => $this->options['lookup_ip_service'] ? Settings::fieldAfterLink( sprintf( $this->options['lookup_ip_service'], HTTP::IP() ) ) : '',
		];

		return $settings;
	}

	public function settings_section_denied()
	{
		Settings::fieldSection( _x( 'Dashboard Access', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ) );
	}

	public function settings_sidebox( $sub, $uri )
	{
		$sitemeta = function_exists( 'is_site_meta_supported' ) && is_site_meta_supported();

		if ( $this->options['ssl_support'] ) {

			if ( defined( 'GNETWORK_DISABLE_SSL' ) && GNETWORK_DISABLE_SSL ) {
				HTML::desc( sprintf( _x( 'The %s is set. The site will not redirect to HTTPS automatically.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ), '<code>GNETWORK_DISABLE_SSL</code>' ) );
				echo '<hr />';
			}

			echo $this->wrap_open_buttons();

			$ssl = Utilities::htmlSSLfromURL( $url = get_option( 'siteurl' ) );

			echo ' <code>'.$url.'</code> ';

			if ( $ssl )
				Settings::submitButton( 'disable_site_ssl', _x( 'Disable SSL', 'Modules: Site', GNETWORK_TEXTDOMAIN ), 'small' );

			else
				Settings::submitButton( 'enable_site_ssl', _x( 'Enable SSL', 'Modules: Site', GNETWORK_TEXTDOMAIN ), 'small' );

			echo '</p>';

			if ( $sitemeta )
				echo '<hr />';

		} else {

			if ( ! $sitemeta )
				HTML::desc( _x( 'SSL support disabled.', 'Modules: Site', GNETWORK_TEXTDOMAIN ), TRUE, '-empty' );
		}

		if ( $sitemeta ) {

			if ( $this->options['resync_sitemeta'] ) {

				Settings::submitButton( 'resync_sitemeta', _x( 'Re-sync Sites Meta', 'Modules: Site', GNETWORK_TEXTDOMAIN ), 'small' );
				HTML::desc( _x( 'Regenerates sites metadata.', 'Modules: Site', GNETWORK_TEXTDOMAIN ), FALSE );

			} else {

				HTML::desc( _x( 'Sync Metadata disabled.', 'Modules: Site', GNETWORK_TEXTDOMAIN ), TRUE, '-empty' );
			}
		}
	}

	public function settings( $sub = NULL )
	{
		if ( $this->key == $sub ) {

			if ( isset( $_POST['disable_site_ssl'] )
		 		|| isset( $_POST['enable_site_ssl'] ) ) {

				$this->check_referer( $sub );

				$switch = isset( $_POST['enable_site_ssl'] )
					? [ 'http://', 'https://' ]
					: [ 'https://', 'http://' ];

				update_option( 'siteurl', str_replace( $switch[0], $switch[1], get_option( 'siteurl' ) ) );
				update_option( 'home', str_replace( $switch[0], $switch[1], get_option( 'home' ) ) );

				Logger::siteINFO( 'SSL', sprintf( 'switched to: %s', str_replace( '://', '', $switch[1] ) ) );

				WordPress::redirectReferer();

			} else if ( isset( $_POST['resync_sitemeta'] ) ) {

				$this->check_referer( $sub );

				$count = $this->do_resync_sitemeta();

				WordPress::redirectReferer( FALSE === $count ? 'wrong' : [
					'message' => 'synced',
					'count'   => $count,
				] );

			} else {
				parent::settings( $sub );
			}
		}
	}

	public function admin_menu()
	{
		$this->action( 'admin_page_access_denied', 0, 98 ); // core's is on 99
	}

	// @REF: `_access_denied_splash()`
	public function admin_page_access_denied()
	{
		$access_denied = $this->default_option( 'access_denied',
			__( 'Sorry, you are not allowed to access this page.' ) );

		if ( ! $user_id = get_current_user_id() )
			wp_die( $access_denied, 403 );

		// getting lighter list of blogs
		$blogs = $this->options['list_sites']
			? get_blogs_of_user( $user_id )
			: WordPress::getUserSites( $user_id, $GLOBALS['wpdb']->base_prefix );

		// this will override default message
		if ( wp_list_filter( $blogs, [ 'userblog_id' => get_current_blog_id() ] ) )
			wp_die( $access_denied, 403 );

		$message = $this->default_option( 'denied_message',
			__( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ) );

		if ( $message )
			$message = Text::autoP( sprintf( $message, get_bloginfo( 'name' ) ) );

		if ( empty( $blogs ) )
			wp_die( $message, 403 );

		$extra = $this->default_option( 'denied_extra',
			__( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.' ) );

		if ( $extra )
			$message.= Text::autoP( $extra );

		if ( $this->options['list_sites'] )
			$message.= self::tableUserSites( $blogs );

		wp_die( $message, 403 );
	}

	// FIXME: customize the list
	public static function tableUserSites( $blogs, $title = NULL )
	{
		$html = '';

		if ( is_null( $title ) )
			$html.= '<h3>'._x( 'Your Sites', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ).'</h3>';

		else if ( $title )
			$html.= $title;

		$html.= '<table>';

		foreach ( $blogs as $blog ) {
			$html.= '<tr><td>'.$blog->blogname.'</td><td>';
			$html.= HTML::link( _x( 'Visit Dashboard', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ), get_admin_url( $blog->userblog_id ) );
			$html.= ' | ';
			$html.= HTML::link( _x( 'View Site', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ), $blog->siteurl );
			$html.= '</td></tr>';
		}

		return $html.'</table>';
	}

	// FIXME: DEPRECATED: @SEE: https://core.trac.wordpress.org/ticket/41333
	// TODO: on signup form: http://stackoverflow.com/a/10372861
	public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $network_id, $meta )
	{
		switch_to_blog( $blog_id );

		if ( $site_user_id = gNetwork()->user() )
			add_user_to_blog( $blog_id, $site_user_id, gNetwork()->option( 'site_user_role', 'user', 'editor' ) );

		$new_blog_options = $this->filters( 'new_blog_options', [
			'blogdescription'        => '',
			'permalink_structure'    => '/entries/%post_id%',
			'default_comment_status' => 'closed',
			'default_ping_status'    => 'closed',
			'use_smilies'            => FALSE,
			'comments_notify'        => FALSE,
			'moderation_notify'      => FALSE,
			// 'admin_email'            => get_network_option( NULL, 'admin_email' ), // FIXME: only when created by super admin
		] );

		foreach ( $new_blog_options as $new_blog_option_key => $new_blog_option )
			update_option( $new_blog_option_key, $new_blog_option );

		$new_post_content = $this->filters( 'new_post_content', _x( '[ This page is being completed ]', 'Modules: Site:‌ Initial Page Content', GNETWORK_TEXTDOMAIN ) );

		wp_update_post( [ 'ID' => 1, 'post_status' => 'draft', 'post_title' => '', 'post_name' => '', 'post_content' => $new_post_content, 'post_type' => 'page' ] );
		wp_update_post( [ 'ID' => 2, 'post_status' => 'draft', 'post_title' => '', 'post_name' => '', 'post_content' => $new_post_content ] );
		wp_set_comment_status( 1, 'trash' );

		$new_blog_plugins = $this->filters( 'new_blog_plugins', [
			'geditorial/geditorial.php'     => TRUE,
			'gpersiandate/gpersiandate.php' => TRUE,
		] );

		foreach ( $new_blog_plugins as $new_blog_plugin => $new_blog_plugin_silent )
			activate_plugin( $new_blog_plugin, '', FALSE, $new_blog_plugin_silent );

		restore_current_blog();
		clean_blog_cache( $blog_id );
	}

	// alternative to `NOBLOGREDIRECT`
	// @SEE: https://core.trac.wordpress.org/ticket/21573
	public function ms_site_not_found( $current_site, $domain, $path )
	{
		if ( $this->options['redirect_notfound'] )
			WordPress::redirect( $this->options['redirect_notfound'], 303 );

		Utilities::redirect404();
	}

	public function wp_is_large_network( $is, $using, $count )
	{
		if ( 'users' == $using )
			return $count > GNETWORK_LARGE_NETWORK_IS;

		return $is;
	}

	public function get_header( $name )
	{
		$disable_styles = defined( 'GNETWORK_DISABLE_FRONT_STYLES' ) && GNETWORK_DISABLE_FRONT_STYLES;

		if ( 'wp-signup' == $name ) {

			remove_action( 'wp_head', 'wpmu_signup_stylesheet' );

			if ( ! $disable_styles )
				add_action( 'wp_head', function(){
					Utilities::linkStyleSheet( 'signup.all' );
				} );

		} else if ( 'wp-activate' == $name ) {

			remove_action( 'wp_head', 'wpmu_activate_stylesheet' );

			if ( ! $disable_styles )
				add_action( 'wp_head', function(){
					Utilities::linkStyleSheet( 'activate.all' );
				} );
		}
	}

	protected $filters = [
		'stylesheet',
		'blog_charset',
		'template',
		'WPLANG',
		'blogname',
		'siteurl',
		'post_count',
		'home',
		'allowedthemes',
		'blog_public',
		'WPLANG',
		'blogdescription',
		'db_version',
		'db_upgraded',
		'active_plugins',
		'users_can_register',
		'admin_email',
		'wp_user_roles',
	];

	private function setup_meta_sync()
	{
		if ( ! function_exists( 'is_site_meta_supported' ) )
			return;

		if ( ! is_site_meta_supported() )
			return;

		$this->action( 'wpmu_new_blog', 1, 10, 'sync' );
		$this->action( 'wp_upgrade' );

		$this->action( 'updated_option', 3 );
		$this->action( 'added_option', 2 );
		$this->action( 'delete_option' );

		// WORKING but DISABLED
		// this will bypass blog options to use site meta table
		// foreach ( $this->get_filters() as $filter )
		// 	add_filter( 'pre_option_'.$filter, [ $this, 'pre_get_option' ], 1, 2 );
		// $this->action( 'switch_blog' );
		// $this->filter( 'the_sites' );
		// $this->filter( 'sites_clauses', 2 );
	}

	public function pre_get_option( $value, $option )
	{
		$meta = get_site_meta( get_current_blog_id(), $option, TRUE );

		if ( FALSE !== $meta ) {

			$value = maybe_unserialize( $meta );

			if ( in_array( $option, [ 'siteurl', 'home', 'category_base', 'tag_base' ] ) )
				$value = URL::untrail( $value );

		} else {

			add_filter( 'option_'.$option, [ $this, 'update_sitemeta' ], 1, 2 );
		}

		return $value;
	}

	public function update_sitemeta( $value, $option )
	{
		if ( ! $this->check_option( $option ) || FALSE == $value )
			return $value;

		update_site_meta( get_current_blog_id(), $option, maybe_unserialize( $value ) );

		return $value;
	}

	public function updated_option( $option, $old_value, $value )
	{
		if ( ! $this->check_option( $option ) )
			return;

		update_site_meta( get_current_blog_id(), $option, $value, maybe_unserialize( $old_value ) );
	}

	public function added_option( $option, $value )
	{
		if ( ! $this->check_option( $option ) )
			return;

		add_site_meta( get_current_blog_id(), $option, $value, TRUE );
	}

	public function delete_option( $option )
	{
		if ( ! $this->check_option( $option ) )
			return;

		delete_site_meta( get_current_blog_id(), $option );
	}

	public function wp_upgrade( $wp_db_version )
	{
		update_site_meta( get_current_blog_id(), 'wp_db_version', $wp_db_version );
	}

	public function wpmu_new_blog_sync( $blog_id )
	{
		switch_to_blog( $blog_id );

		$this->migrate_options();

		restore_current_blog();
	}

	public function migrate_options()
	{
		$all_option = wp_load_alloptions();
		$blog_id    = get_current_blog_id();

		foreach ( $this->get_filters() as $filter )
			if ( ! empty( $all_option[$filter] ) )
				update_site_meta( $blog_id, $filter, maybe_unserialize( $all_option[$filter] ) );
	}

	public function switch_blog( $blog_id )
	{
		if ( $blog_id == 1 )
			return;

		$filter = $GLOBALS['wpdb']->get_blog_prefix( $blog_id ).'user_roles';

		if ( ! in_array( $filter, $this->get_filters( ) ) ) {
			add_filter( 'pre_option_'.$filter, [ $this, 'pre_get_option' ], 1, 2 );
			$this->filters[] = $filter;
		}
	}

	public function the_sites( $sites )
	{
		foreach ( wp_list_pluck( $sites, 'id' ) as $blog_id )
			$this->switch_blog( $blog_id );

		return $sites;
	}

	public function sites_clauses( $clauses, &$wp_site )
	{
		global $wpdb;

		if ( strlen( $wp_site->query_vars['search'] ) ) {
			$clauses['join']   .= " LEFT JOIN {$wpdb->blogmeta} AS sq1 ON ( {$wpdb->blogs}.blog_id = sq1.blog_id AND sq1.meta_key = 'blogname' )";
			$clauses['groupby'] = "{$wpdb->blogs}.blog_id";
			$clauses['fields']  = "{$wpdb->blogs}.blog_id";
			$clauses['where']   = str_replace( "(domain LIKE", "(sq1.meta_value LIKE '%{$wp_site->query_vars['search']}%' OR domain LIKE", $clauses['where'] );
		}

		return $clauses;
	}

	// FIXME: use `WordPress::getAllSites()`
	private function do_resync_sitemeta( $network = NULL )
	{
		if ( is_null( $network ) )
			$network = get_current_network_id();

		$count = 0;
 		$sites = get_sites( [
			'network_id' => $network,
			'fields'     => 'ids',
			'number'     => FALSE,
		] );

		foreach ( $sites as $site ) {

			switch_to_blog( $site );

			$this->migrate_options();

			restore_current_blog();

			$count++;
		}

		return $count;
	}

	private function get_filters()
	{
		return $this->filters( 'meta_filters', $this->filters );
	}

	private function check_option( $option )
	{
		return in_array( $option, $this->get_filters( ) );
	}
}

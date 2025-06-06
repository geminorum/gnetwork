<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Site extends gNetwork\Module
{
	protected $key  = 'general';
	protected $cron = TRUE;

	protected function setup_actions()
	{
		if ( ! is_multisite() )
			return FALSE;

		$this->action( 'wp_initialize_site', 2, 12 );
		$this->action( 'ms_site_not_found', 3, 12 );

		if ( is_admin() ) {

			$this->action( 'admin_menu' );

		} else {

			$this->action( 'get_header' );
			$this->filter( 'allowed_redirect_hosts' );

			if ( $this->options['body_class'] )
				$this->filter_append( 'body_class', Core\HTML::attrClass( $this->options['body_class'] ) );
		}

		if ( $this->options['resync_sitemeta'] ) {

			$this->setup_meta_sync();

			if ( is_main_site() )
				add_action( $this->hook( 'resync_sitemeta' ), [ $this, 'network_resync_sitemeta' ], 10, 0 );
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Global', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'ssl_support'       => '0',
			'resync_sitemeta'   => '0',
			'redirect_notfound' => '',
			'admin_locale'      => 'en_US',
			'body_class'        => '',
			'access_denied'     => '',
			'denied_message'    => '',
			'denied_extra'      => '',
			'list_sites'        => '1',

			'lookup_ip_service'      => '',   // `https://redirect.li/ip/?ip=%s`
			'lookup_country_service' => '',   // `https://countrycode.org/%s`

			'base_country'        => '', // `IR`
			'base_province'       => '', // `TEH`
			'base_country_phone'  => '', // `98`
			'base_province_phone' => '', // `21`
		];
	}

	public function default_settings()
	{
		$settings = [];

		$settings['_general'][] = [
			'field'       => 'ssl_support',
			'title'       => _x( 'SSL', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => _x( 'Enables SSL tools to support the network sites.', 'Modules: Site: Settings', 'gnetwork' ),
		];

		$settings['_general'][] = [
			'field'       => 'resync_sitemeta',
			'title'       => _x( 'Sync Metadata', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => _x( 'Re-syncs the site meta network-wide automatically.', 'Modules: Site: Settings', 'gnetwork' ),
		];

		$settings['_general'][] = [
			'field'       => 'redirect_notfound',
			'type'        => 'url',
			'title'       => _x( 'Site Not Found', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => _x( 'Redirects to when the network can be determined but a site cannot.', 'Modules: Site: Settings', 'gnetwork' ),
		];

		if ( class_exists( __NAMESPACE__.'\\Locale' ) ) {
			$settings['_locale'][] = [
				'field'       => 'admin_locale',
				'type'        => 'select',
				'title'       => _x( 'Network Locale', 'Modules: Site: Settings', 'gnetwork' ),
				'description' => _x( 'Overrides network admin language, despite of the main site locale.', 'Modules: Site: Settings', 'gnetwork' ),
				'default'     => 'en_US',
				'values'      => Core\Arraay::sameKey( Locale::available() ),
			];
		}

		$settings['_general'][] = [
			'field'       => 'body_class',
			'type'        => 'text',
			'title'       => _x( 'Body Class', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => _x( 'Appends as extra HTML body class on all pages on front-end.', 'Modules: Site: Settings', 'gnetwork' ),
			'field_class' => [ 'regular-text', 'code-text' ],
		];

		$settings['_locale'][] = [
			'field'       => 'base_country',
			'type'        => 'text',
			'title'       => _x( 'Base Country', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => _x( 'Defines the base country for this network.', 'Modules: Site: Settings', 'gnetwork' ),
			'placeholder' => 'IR',
			'field_class' => [ 'small-text', 'code-text' ],
		];

		$settings['_locale'][] = [
			'field'       => 'base_province',
			'type'        => 'text',
			'title'       => _x( 'Base Province', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => _x( 'Defines the base province for this network.', 'Modules: Site: Settings', 'gnetwork' ),
			'placeholder' => 'TEH',
			'field_class' => [ 'small-text', 'code-text' ],
		];

		$settings['_locale'][] = [
			'field'       => 'base_country_phone',
			'type'        => 'text',
			'title'       => _x( 'Base Country Phone Prefix', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => _x( 'Defines the base country phone prefix for this network.', 'Modules: Site: Settings', 'gnetwork' ),
			'placeholder' => '98',
			'field_class' => [ 'small-text', 'code-text' ],
			'after'       => Settings::fieldAfterIcon( 'https://en.wikipedia.org/wiki/List_of_country_calling_codes' ),
		];

		$settings['_locale'][] = [
			'field'       => 'base_province_phone',
			'type'        => 'text',
			'title'       => _x( 'Base Province Phone Prefix', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => _x( 'Defines the base province phone prefix for this network.', 'Modules: Site: Settings', 'gnetwork' ),
			'placeholder' => '21',
			'field_class' => [ 'small-text', 'code-text' ],
		];

		$settings['_denied'] = [
			[
				'field'       => 'access_denied',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Access Denied', 'Modules: Site: Settings', 'gnetwork' ),
				'description' => sprintf(
					/* translators: `%s`: zero placeholder */
					_x( 'Displays when access to an admin page is denied. Leave empty to use default or %s to disable.', 'Modules: Site: Settings', 'gnetwork' ),
					Core\HTML::code( '0' )
				),
				'placeholder' => __( 'Sorry, you are not allowed to access this page.' ),
			],
			[
				'field'       => 'denied_message',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Denied Message', 'Modules: Site: Settings', 'gnetwork' ),
				'description' => sprintf(
					/* translators: `%1$s`: zero placeholder, `%2$s`: `%1$s` placeholder */
					_x( 'Displays this message when a user tries to view a site\'s dashboard they do not have access to. Leave empty to use default or %1$s to disable. %2$s: Blog Name', 'Modules: Site: Settings', 'gnetwork' ),
					Core\HTML::code( '0' ),
					Core\HTML::code( '%1$s' )
				),
				'placeholder' => __( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ),
			],
			[
				'field'       => 'denied_extra',
				'type'        => 'textarea-quicktags',
				'title'       => _x( 'Extra Message', 'Modules: Site: Settings', 'gnetwork' ),
				'description' => sprintf(
					/* translators: `%s`: zero placeholder */
					_x( 'Displays this message before the list of sites. Leave empty to use default or %s to disable.', 'Modules: Site: Settings', 'gnetwork' ),
					Core\HTML::code( '0' )
				),
				'placeholder' => __( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.' ),
			],
			[
				'field'       => 'list_sites',
				'title'       => _x( 'List of Sites', 'Modules: Site: Settings', 'gnetwork' ),
				'description' => _x( 'Displays the current user list of sites after access denied message.', 'Modules: Site: Settings', 'gnetwork' ),
				'default'     => '1',
			],
		];

		$settings['_misc'][] = [
			'field'       => 'lookup_ip_service',
			'type'        => 'text',
			'title'       => _x( 'Lookup IP URL', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => sprintf(
				/* translators: `%s`: `%s` placeholder */
				_x( 'URL template to to use for looking up IP adresses. Will replace %s with the IP.', 'Modules: Site: Settings', 'gnetwork' ),
				Core\HTML::code( '%s' )
			),
			'placeholder' => 'https://redirect.li/ip/?ip=%s',
			'dir'         => 'ltr',
			'after'       => $this->options['lookup_ip_service'] ? Settings::fieldAfterLink( sprintf( $this->options['lookup_ip_service'], Core\HTTP::IP() ) ) : '',
		];

		$settings['_misc'][] = [
			'field'       => 'lookup_country_service',
			'type'        => 'text',
			'title'       => _x( 'Lookup Country URL', 'Modules: Site: Settings', 'gnetwork' ),
			'description' => sprintf(
				/* translators: `%s`: `%s` placeholder */
				_x( 'URL template to to use for looking up Country Code. Will replace %s with the code.', 'Modules: Site: Settings', 'gnetwork' ),
				Core\HTML::code( '%s' )
			),
			'placeholder' => 'https://countrycode.org/%s',
			'dir'         => 'ltr',
			'after'       => $this->options['lookup_country_service'] ? Settings::fieldAfterLink( sprintf( $this->options['lookup_country_service'], GCORE_DEFAULT_COUNTRY_CODE ) ) : '',
		];

		return $settings;
	}

	public function settings_section_denied()
	{
		Settings::fieldSection( _x( 'Dashboard Access', 'Modules: Site: Settings', 'gnetwork' ) );
	}

	public function settings_sidebox( $sub, $uri )
	{
		$sitemeta = is_site_meta_supported();

		if ( $this->options['ssl_support'] ) {

			if ( GNETWORK_DISABLE_SSL ) {
				Core\HTML::desc( sprintf(
					/* translators: `%s`: constant name */
					_x( 'The %s is set. The site will not redirect to HTTPS automatically.', 'Modules: Site: Settings', 'gnetwork' ),
					Core\HTML::code( 'GNETWORK_DISABLE_SSL' )
				) );
				echo '<hr />';
			}

			echo $this->wrap_open_buttons();

			$ssl = Utilities::htmlSSLfromURL( $url = get_option( 'siteurl' ) );

			echo ' <code>'.$url.'</code> ';

			if ( $ssl )
				Settings::submitButton( 'disable_site_ssl', _x( 'Disable SSL', 'Modules: Site', 'gnetwork' ), 'small' );

			else
				Settings::submitButton( 'enable_site_ssl', _x( 'Enable SSL', 'Modules: Site', 'gnetwork' ), 'small' );

			echo '</p>';

			if ( $sitemeta )
				echo '<hr />';

		} else {

			if ( ! $sitemeta )
				Core\HTML::desc( _x( 'SSL support disabled.', 'Modules: Site', 'gnetwork' ), TRUE, '-empty' );
		}

		if ( $sitemeta ) {

			if ( $this->options['resync_sitemeta'] ) {

				echo $this->wrap_open_buttons();

				Settings::submitButton( 'resync_sitemeta', _x( 'Re-sync Sites Meta', 'Modules: Site', 'gnetwork' ), 'small' );

				echo '&nbsp;';

				Settings::submitButton( 'delete_sitemeta', _x( 'Delete Sites Meta', 'Modules: Site', 'gnetwork' ), 'small button-danger' );

				echo '</p>';

			} else {

				Core\HTML::desc( _x( 'Sync Metadata disabled.', 'Modules: Site', 'gnetwork' ), TRUE, '-empty' );
			}
		}
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['disable_site_ssl'] )
			|| isset( $_POST['enable_site_ssl'] ) ) {

			$this->check_referer( $sub, 'settings' );

			// @SEE: `wp_update_urls_to_https()`

			$switch = isset( $_POST['enable_site_ssl'] )
				? [ 'http://', 'https://' ]
				: [ 'https://', 'http://' ];

			update_option( 'siteurl', str_replace( $switch[0], $switch[1], get_option( 'siteurl' ) ) );
			update_option( 'home', str_replace( $switch[0], $switch[1], get_option( 'home' ) ) );

			Logger::siteINFO( 'SSL', sprintf( 'switched to: %s', str_replace( '://', '', $switch[1] ) ) );

			Core\WordPress::redirectReferer();

		} else if ( isset( $_POST['resync_sitemeta'] ) ) {

			$this->check_referer( $sub, 'settings' );

			$count = $this->network_resync_sitemeta();

			Core\WordPress::redirectReferer( FALSE === $count ? 'wrong' : [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( isset( $_POST['delete_sitemeta'] ) ) {

			$this->check_referer( $sub, 'settings' );

			$count = $this->network_delete_sitemeta();

			Core\WordPress::redirectReferer( FALSE === $count ? 'wrong' : [
				'message' => 'deleted',
				'count'   => $count,
			] );
		}
	}

	public function schedule_actions()
	{
		if ( $this->options['resync_sitemeta'] && is_main_site() )
			$this->_hook_event( 'resync_sitemeta', 'monthly' );
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
			: Core\WordPress::getUserSites( $user_id, $GLOBALS['wpdb']->base_prefix );

		// this will override default message
		if ( Core\Arraay::filter( $blogs, [ 'userblog_id' => get_current_blog_id() ] ) )
			wp_die( $access_denied, 403 );

		$message = $this->default_option( 'denied_message',
			__( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ) );

		if ( $message )
			$message = Core\Text::autoP( sprintf( $message, get_bloginfo( 'name' ) ) );

		if ( empty( $blogs ) )
			wp_die( $message, 403 );

		$extra = $this->default_option( 'denied_extra',
			__( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.' ) );

		if ( $extra )
			$message.= Core\Text::autoP( $extra );

		if ( $this->options['list_sites'] )
			$message.= self::tableUserSites( $blogs );

		wp_die( $message, 403 );
	}

	// FIXME: customize the list
	public static function tableUserSites( $blogs, $title = NULL )
	{
		$html = '';

		if ( is_null( $title ) )
			$html.= '<h3>'._x( 'Your Sites', 'Modules: Site: User Sites', 'gnetwork' ).'</h3>';

		else if ( $title )
			$html.= $title;

		$html.= '<table>';

		foreach ( $blogs as $blog ) {
			$html.= '<tr><td>'.$blog->blogname.'</td><td>';
			$html.= Core\HTML::link( _x( 'Visit Dashboard', 'Modules: Site: User Sites', 'gnetwork' ), get_admin_url( $blog->userblog_id ) );
			$html.= ' | ';
			$html.= Core\HTML::link( _x( 'View Site', 'Modules: Site: User Sites', 'gnetwork' ), $blog->siteurl );
			$html.= '</td></tr>';
		}

		return $html.'</table>';
	}

	// TODO: on signup form: http://stackoverflow.com/a/10372861
	// @REF: https://core.trac.wordpress.org/ticket/41333
	public function wp_initialize_site( $new_site, $args )
	{
		WordPress\SwitchSite::to( $new_site->id );

		if ( $site_user_id = gNetwork()->user() )
			add_user_to_blog( $new_site->id, $site_user_id, gNetwork()->option( 'site_user_role', 'user', 'editor' ) );

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

		$new_post_content = $this->filters( 'new_post_content', _x( '[ This page is being completed ]', 'Modules: Site: Initial Page Content', 'gnetwork' ) );

		wp_update_post( [ 'ID' => 1, 'post_status' => 'draft', 'post_title' => '', 'post_name' => '', 'post_content' => $new_post_content, 'post_type' => 'page' ] );
		wp_update_post( [ 'ID' => 2, 'post_status' => 'draft', 'post_title' => '', 'post_name' => '', 'post_content' => $new_post_content ] );
		wp_set_comment_status( 1, 'trash' );

		$new_blog_plugins = $this->filters( 'new_blog_plugins', [
			'geditorial/geditorial.php'     => TRUE,
			'gpersiandate/gpersiandate.php' => TRUE,
		] );

		foreach ( $new_blog_plugins as $new_blog_plugin => $new_blog_plugin_silent )
			activate_plugin( $new_blog_plugin, '', FALSE, $new_blog_plugin_silent );

		WordPress\SwitchSite::restore();
		clean_blog_cache( $new_site->id );
	}

	// alternative to `NOBLOGREDIRECT`
	// @SEE: https://core.trac.wordpress.org/ticket/21573
	public function ms_site_not_found( $current_site, $domain, $path )
	{
		if ( $this->options['redirect_notfound'] )
			Core\WordPress::redirect( $this->options['redirect_notfound'], 303 );

		Utilities::redirect404();
	}

	public function get_header( $name )
	{
		$disable_styles = defined( 'GNETWORK_DISABLE_FRONT_STYLES' ) && GNETWORK_DISABLE_FRONT_STYLES;

		if ( 'wp-signup' == $name ) {

			remove_action( 'wp_head', 'wpmu_signup_stylesheet' );

			if ( ! $disable_styles )
				add_action( 'wp_head', static function () {
					Utilities::linkStyleSheet( 'front.signup' );
				} );

		} else if ( 'wp-activate' == $name ) {

			remove_action( 'wp_head', 'wpmu_activate_stylesheet' );

			if ( ! $disable_styles )
				add_action( 'wp_head', static function () {
					Utilities::linkStyleSheet( 'front.activate' );
				} );
		}
	}

	public function allowed_redirect_hosts( $hosts )
	{
		static $sites = NULL;

		if ( is_null( $sites ) )
			$sites = Core\Arraay::pluck( Core\WordPress::getAllSites( FALSE, NULL, FALSE ), 'domain' );

		return array_unique( array_filter( array_merge( $hosts, $sites ) ) );
	}

	protected $filters = [
		'home',
		'siteurl',
		'blogname',
		'blogdescription',
		'admin_email',
		'WPLANG',
		'template',
		'stylesheet',
		'active_plugins',
		'post_count', // TODO: attachment count: must count media before hand
		// 'blog_charset',
		// 'blog_public',
		// 'db_version',
		// 'db_upgraded',
		// 'users_can_register',
		// 'wp_user_roles',
	];

	private function setup_meta_sync()
	{
		if ( ! is_site_meta_supported() )
			return;

		$this->action( 'wp_initialize_site', 2, 10, 'sync' );
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
				$value = Core\URL::untrail( $value );

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

	public function wp_initialize_site_sync( $new_site, $args )
	{
		WordPress\SwitchSite::to( $new_site->id );

		$this->resync_sitemeta();

		WordPress\SwitchSite::restore();
	}

	public function delete_sitemeta( $site_id = NULL )
	{
		if ( is_null( $site_id ) )
			$site_id = get_current_blog_id();

		$count = 0;

		foreach ( $this->get_filters() as $filter )
			if ( delete_site_meta( $site_id, $filter ) )
				$count++;

		if ( delete_site_meta( $site_id, 'site_icon_url' ) )
			$count++;

		return $count;
	}

	public function resync_sitemeta()
	{
		$all_option = wp_load_alloptions();
		$blog_id    = get_current_blog_id();

		foreach ( $this->get_filters() as $filter )
			if ( ! empty( $all_option[$filter] ) )
				update_site_meta( $blog_id, $filter, maybe_unserialize( $all_option[$filter] ) );

		// extras!
		update_site_meta( $blog_id, 'site_icon_url', get_site_icon_url() );
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
		foreach ( Core\Arraay::pluck( $sites, 'id' ) as $blog_id )
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

	public function network_resync_sitemeta( $network = NULL )
	{
		$count = 0;
		$sites = Core\WordPress::getAllSites( FALSE, $network, FALSE );

		foreach ( $sites as $site_id => $site ) {
			WordPress\SwitchSite::to( $site_id );
			$this->resync_sitemeta();
			$count++;
			WordPress\SwitchSite::lap();
		}

		WordPress\SwitchSite::restore();

		return $count;
	}

	public function network_delete_sitemeta( $network = NULL )
	{
		$count = 0;
		$sites = Core\WordPress::getAllSites( FALSE, $network, FALSE );

		foreach ( $sites as $site_id => $site )
			if ( $this->delete_sitemeta( $site_id ) )
				$count++;

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

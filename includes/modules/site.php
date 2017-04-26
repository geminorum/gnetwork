<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\WordPress;

class Site extends \geminorum\gNetwork\ModuleCore
{

	protected $key = 'general';

	protected function setup_actions()
	{
		if ( ! is_multisite() )
			return FALSE;

		if ( GNETWORK_LARGE_NETWORK_IS )
			$this->filter( 'wp_is_large_network', 3, 10 );

		$this->action( 'wpmu_new_blog', 6, 12 );

		if ( is_admin() ) {

			$this->action( 'admin_menu' );

		} else {

			if ( $this->options['page_signup'] )
				$this->action( 'before_signup_header', 0, 1 );
		}
	}

	public function setup_menu( $context )
	{
		Network::registerMenu( $this->key,
			_x( 'Global', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'admin_locale'      => 'en_US',
			'page_signup'       => '0',
			'denied_message'    => '',
			'denied_extra'      => '',
			'list_sites'        => '1',
			'lookup_ip_service' => 'http://freegeoip.net/?q=%s',
		);
	}

	public function default_settings()
	{
		$exclude = array_filter( array(
			get_option( 'page_on_front' ),
			get_option( 'page_for_posts' ),
		) );

		$settings = array();

		if ( class_exists( __NAMESPACE__.'\\Locale' ) ) {
			$settings['_locale'] = array(
				array(
					'field'       => 'admin_locale',
					'type'        => 'select',
					'title'       => _x( 'Network Language', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Network admin language, despite of the site locale', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 'en_US',
					'values'      => Arraay::sameKey( Locale::available() ),
				),
			);
		}

		if ( is_multisite() ) {
			$settings['_signup'] = array(
				array(
					'field'       => 'page_signup',
					'type'        => 'page',
					'title'       => _x( 'Page for Signup', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Redirects signups into this page, if registration <strong>disabled</strong>', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude'     => $exclude,
					'after'       => Settings::fieldAfterNewPostType( 'page' ),
				),
			);

			$settings['_denied'] = array(
				array(
					'field'       => 'denied_message',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Denied Message', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays when a user tries to view a site\'s dashboard they do not have access to.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'You attempted to access the &#8220;%1$s&#8221; dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the &#8220;%1$s&#8221; dashboard, please contact your network administrator.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'large-text', 'code-text' ),
				),
				array(
					'field'       => 'denied_extra',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Extra Message', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays before the list of sites.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => _x( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'field_class' => array( 'large-text', 'code-text' ),
				),
				array(
					'field'       => 'list_sites',
					'title'       => _x( 'List of Sites', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays current user list of sites after access denied message.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				),
			);
		}

		$settings['_misc'] = array(
			array(
				'field'       => 'lookup_ip_service',
				'type'        => 'text',
				'title'       => _x( 'Lookup IP URL', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'description' => _x( 'URL template to to use for looking up IP adresses. Will replace <code>%s</code> with the IP.', 'Modules: Site: Settings', GNETWORK_TEXTDOMAIN ),
				'default'     => 'http://freegeoip.net/?q=%s',
				'dir'         => 'ltr',
				'after'       => $this->options['lookup_ip_service'] ? Settings::fieldAfterLink( sprintf( $this->options['lookup_ip_service'], HTTP::IP() ) ) : '',
			),
		);

		return $settings;
	}

	public function admin_menu()
	{
		remove_action( 'admin_page_access_denied', '_access_denied_splash', 99 );
		$this->action( 'admin_page_access_denied' );
	}

	// @SOURCE: `_access_denied_splash()`
	public function admin_page_access_denied()
	{
		if ( ! is_user_logged_in() || is_network_admin() )
			return;

		$blogs = get_blogs_of_user( get_current_user_id() );

		if ( wp_list_filter( $blogs, array( 'userblog_id' => get_current_blog_id() ) ) )
			return;

		$output = '';

		if ( $this->options['denied_message'] )
			$output .= wpautop( sprintf( $this->options['denied_message'], get_bloginfo( 'name' ) ) );

		if ( empty( $blogs ) )
			wp_die( $output, 403 );

		if ( $this->options['denied_extra'] )
			$output .= wpautop( $this->options['denied_extra'] );

		if ( $this->options['list_sites'] )
			$output .= self::tableUserSites( $blogs );

		wp_die( $output, 403 );
	}

	// FIXME: customize the list
	public static function tableUserSites( $blogs, $title = NULL )
	{
		$output = '';

		if ( is_null( $title ) )
			$output .= '<h3>'._x( 'Your Sites', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ).'</h3>';

		else if ( $title )
			$output .= $title;

		$output .= '<table>';

		foreach ( $blogs as $blog ) {
			$output .= '<tr><td>'.$blog->blogname.'</td><td>';
			$output .= HTML::link( _x( 'Visit Dashboard', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ), get_admin_url( $blog->userblog_id ) );
			$output .= ' | ';
			$output .= HTML::link( _x( 'View Site', 'Modules: Site:‌ User Sites', GNETWORK_TEXTDOMAIN ), $blog->siteurl );
			$output .= '</td></tr>';
		}

		return $output.'</table>';
	}

	public function before_signup_header()
	{
		if ( 'none' == get_site_option( 'registration', 'none' ) )
			WordPress::redirect( get_page_link( $this->options['page_signup'] ) );
	}

	// TODO: on signup form: http://stackoverflow.com/a/10372861
	public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta )
	{
		switch_to_blog( $blog_id );

		if ( $site_user_id = WordPress::getSiteUserID() )
			add_user_to_blog( $blog_id, $site_user_id, GNETWORK_SITE_USER_ROLE );

		$new_blog_options = $this->filters( 'new_blog_options', array(
			'blogdescription'        => '',
			'permalink_structure'    => '/entries/%post_id%',
			'default_comment_status' => 'closed',
			'default_ping_status'    => 'closed',
			'comments_notify'        => FALSE,
			'moderation_notify'      => FALSE,
			'admin_email'            => get_site_option( 'admin_email' ),
		) );

		foreach ( $new_blog_options as $new_blog_option_key => $new_blog_option )
			update_option( $new_blog_option_key, $new_blog_option );

		wp_update_post( array( 'ID' => 1, 'post_status' => 'draft' ) );
		wp_update_post( array( 'ID' => 2, 'post_status' => 'draft' ) );
		wp_set_comment_status( 1, 'trash' );

		$new_blog_plugins = $this->filters( 'new_blog_plugins', array(
			'geditorial/geditorial.php'     => TRUE,
			'gpersiandate/gpersiandate.php' => TRUE,
		) );

		foreach ( $new_blog_plugins as $new_blog_plugin => $new_blog_plugin_silent )
			activate_plugin( $new_blog_plugin, '', FALSE, $new_blog_plugin_silent );

		restore_current_blog();
		refresh_blog_details( $blog_id );
	}

	public function wp_is_large_network( $is, $using, $count )
	{
		if ( 'users' == $using )
			return $count > GNETWORK_LARGE_NETWORK_IS;

		return $is;
	}
}

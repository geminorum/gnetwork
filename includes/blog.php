<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkBlog extends gNetworkModuleCore
{

	var $_option_key = 'general';
	var $_network    = FALSE;

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'general',
			__( 'General', GNETWORK_TEXTDOMAIN ),
			array( &$this, 'settings' )
		);

		add_filter( 'init', array( &$this, 'init_early' ), 1 );

		add_filter( 'frontpage_template', array( &$this, 'frontpage_template' ) );

		if ( $this->options['page_for_404'] )
			add_filter( '404_template', array( &$this, 'custom_404_template' ) );
	}

	public function default_options()
	{
		return array(
			'blog_redirect' => '',
			'page_for_404'  => '0',
			'feed_json'     => '0',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'blog_redirect',
					'type'        => 'text',
					'title'       => __( 'Blog Redirect to', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'The site will redirect to this URL. Leave empty to disable.', GNETWORK_TEXTDOMAIN ),
					'default'     => '',
					'dir'         => 'ltr',
				),
				array(
					'field'       => 'feed_json',
					'type'        => 'enabled',
					'title'       => __( 'Feed JSON', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'Adds JSON as new type of feed you can subscribe to.', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'after'       => sprintf( '<code class="field-after"><a href="%1$s">%1$s</a></code>', get_feed_link( 'json' ) ),
				),
				array(
					'field'       => 'page_for_404',
					'type'        => 'page',
					'title'       => __( 'Page for Error 404', GNETWORK_TEXTDOMAIN ),
					'description' => __( 'Set any page to be used as the 404 error page.', GNETWORK_TEXTDOMAIN ),
					'default'     => '0',
					'exclude' => implode( ',', array_filter( array(
						get_option( 'page_on_front' ),
						get_option( 'page_for_posts' ),
					) ) ),
					'after'       => sprintf( '<span class="field-after"><a href="%s">%s</a></span>',
						admin_url( '/post-new.php?post_type=page' ),
						__( 'Add New Page', GNETWORK_TEXTDOMAIN )
					),
				),
			),
		);
	}

	public function init_early()
	{
		if ( $this->options['blog_redirect'] )
			$this->blog_redirect();

		if ( $this->options['feed_json'] ) {

			add_feed( 'json', array( &$this, 'do_feed_json' ) );

			add_filter( 'query_vars', function( $public_query_vars ){
				$public_query_vars[] = 'callback';
				$public_query_vars[] = 'limit';
				return $public_query_vars;
			} );

			add_filter( 'template_include', array( &$this, 'feed_json_template_include' ) );
		}
	}

	// FIXME: test this
	private function blog_redirect()
	{
		if ( is_user_logged_in()
			&& current_user_can( 'manage_options' ) )
				return;

		if ( $_SERVER['SERVER_NAME'] !== ( str_ireplace( array( 'http://', 'https://' ), '', home_url() ) ) )
			return;

		if ( FALSE === self::whiteListed() )
			self::redirect( $this->options['blog_redirect'].$_SERVER['REQUEST_URI'], 307 );
	}

	public static function whiteListed( $request_uri = NULL )
	{
		if ( is_null( $request_uri ) )
			$request_uri = $_SERVER['REQUEST_URI'];

		return gNetworkUtilities::strpos_arr( array(
			'wp-cron.php',
			'wp-mail.php',
			'wp-login.php',
			'wp-signup.php',
			'wp-activate.php',
			'wp-trackback.php',
			'wp-links-opml.php',
			'xmlrpc.php',
			'wp-admin',
		), $request_uri );
	}

	// http://kaspars.net/blog/wordpress/custom-page-template-front-page
	public function frontpage_template( $template )
	{
		// check if a custom template has been selected
		if ( get_page_template_slug() )
			return get_page_template();

		return $template;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally Based on: [Feed JSON](https://wordpress.org/plugins/feed-json/)
/// By wokamoto : http://twitter.com/wokamoto
/// Updated on: 20150918 / v1.0.9

	public function do_feed_json()
	{
		gNetworkUtilities::getLayout( 'feed.json', TRUE );
	}

	public function feed_json_template_include( $template )
	{
		if ( 'json' === get_query_var( 'feed' )
			&& $layout = gNetworkUtilities::getLayout( 'feed.json' ) )
				return $layout;

		return $template;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally Based on: [kasparsd/custom-404-page](https://github.com/kasparsd/custom-404-page)
/// By Kaspars Dambis : http://kaspars.net/
/// Updated on: 20150918

	// set WP to use page template (page.php) even when returning 404
	public function custom_404_template( $template )
	{
		global $wp_query, $post;

		if ( is_404() ) {

			// get our custom 404 post object. We need to assign
			// $post global in order to force get_post() to work
			// during page template resolving.
			$post = get_post( $this->options['page_for_404'] );

			// populate the posts array with our 404 page object
			$wp_query->posts = array( $post );

			// set the query object to enable support for custom page templates
			$wp_query->queried_object_id = $this->options['page_for_404'];
			$wp_query->queried_object    = $post;

			// set post counters to avoid loop errors
			$wp_query->post_count    = 1;
			$wp_query->found_posts   = 1;
			$wp_query->max_num_pages = 0;

			return get_page_template();
		}

		return $template;
	}
}

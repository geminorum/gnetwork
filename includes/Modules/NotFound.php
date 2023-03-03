<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;

class NotFound extends gNetwork\Module
{

	protected $key     = 'notfound';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( is_admin() )
			return;

		if ( $this->options['page_404'] ) {
			add_filter( '404_template', [ $this, 'custom_404_template' ] );
			$this->filter( 'template_include', 1, 99, 'custom_404' );
			$this->filter( 'display_post_states', 2, 12 );
			$this->filter( 'wp_sitemaps_posts_query_args', 2, 12 );
			$this->filter( 'wpseo_exclude_from_sitemap_by_post_ids', 1, 12 );
		}

		if ( ! GNETWORK_NOTFOUND_LOG )
			return;

		$this->action( 'template_redirect', 0, 99999 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Not Found', 'Modules: Menu Name', 'gnetwork' ), NULL );
	}

	public function default_options()
	{
		return [
			'page_404' => '0',
		];
	}

	public function default_settings()
	{
		$settings = [];

		$settings['_front'][] = [
			'field'       => 'page_404',
			'type'        => 'page',
			'title'       => _x( 'Custom 404 Error', 'Modules: NotFound: Settings', 'gnetwork' ),
			'description' => _x( 'Displays the selected page as 404 Error page.', 'Modules: NotFound: Settings', 'gnetwork' ),
			'default'     => '0',
			'exclude'     => Settings::getPageExcludes(),
			'after'       => Settings::fieldAfterNewPostType( 'page' ),
		];

		return $settings;
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( $this->options['page_404'] )
			$location = get_page_link( $this->options['page_404'] );
		else
			$location = GNETWORK_REDIRECT_404_URL;

		/* translators: %s: notfound location */
		HTML::desc( sprintf( _x( 'Current Location: %s', 'Modules: NotFound: Settings', 'gnetwork' ),
			HTML::tag( 'code', HTML::link( URL::relative( $location ), $location, TRUE ) ) ) );
	}

	public function template_redirect()
	{
		if ( is_404() )
			Logger::siteNotFound( '404', HTML::escapeURL( rawurldecode( $_SERVER['REQUEST_URI'] ) ) );
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// originally based on: Custom 404 Error Page v0.2.5 - 20170925
/// @REF: https://github.com/kasparsd/custom-404-page
/// by Kaspars Dambis

	// set WP to use page template (page.php) even when returning 404
	public function custom_404_template( $template )
	{
		global $wp_query, $post;

		if ( is_404() ) {

			$page_404 = (int) $this->options['page_404'];

			// get our custom 404 post object. We need to assign
			// $post global in order to force get_post() to work
			// during page template resolving.
			$post = get_post( $page_404 );

			// populate the posts array with our 404 page object
			$wp_query->posts = [ $post ];

			// set the query object to enable support for custom page templates
			$wp_query->queried_object_id = $page_404;
			$wp_query->queried_object    = $post;

			// set post counters to avoid loop errors
			$wp_query->post_count    = 1;
			$wp_query->found_posts   = 1;
			$wp_query->max_num_pages = 0;

			// return the page.php template instead of 404.php
			return get_page_template();
		}

		return $template;
	}

	// @REF: https://bbpress.trac.wordpress.org/ticket/1973
	public function template_include_custom_404( $template )
	{
		$page_id = (int) $this->options['page_404'];

		if ( is_page( $page_id ) ) {

			status_header( 404 );
			nocache_headers();

			self::define( 'GNETWORK_IS_CUSTOM_404', $page_id );
		}

		return $template;
	}

	public function display_post_states( $states, $post )
	{
		if ( 'page' !== $post->post_type )
			return $states;

		if ( $post->ID === (int) $this->options['page_404'] )
			$states[$this->key] = _x( 'NotFound', 'Modules: NotFound: Page-State', 'gnetwork' );

		return $states;
	}

	// @REF: https://perishablepress.com/customize-wordpress-sitemaps/
	public function wp_sitemaps_posts_query_args( $args, $post_type )
	{
		if ( 'page' !== $post_type )
			return $args;

		if ( ! array_key_exists( 'post__not_in', $args ) )
			$args['post__not_in'] = [];

		$args['post__not_in'][] = (int) $this->options['page_404'];

		return $args;
	}

	// @REF: https://preventdirectaccess.com/5-ways-remove-pages-from-sitemap/
	public function wpseo_exclude_from_sitemap_by_post_ids( $excluded_posts_ids )
	{
		$excluded_posts_ids[] = (int) $this->options['page_404'];

		return $excluded_posts_ids;
	}
}

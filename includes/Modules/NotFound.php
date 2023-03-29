<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\PostType as WPPostType;
use geminorum\gNetwork\WordPress\Taxonomy as WPTaxonomy;

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

		if ( $this->options['disable_guessing'] )
			$this->filter_false( 'do_redirect_guess_404_permalink' );

		if ( $this->options['strict_guessing'] )
			$this->filter_true( 'strict_redirect_guess_404_permalink' );

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
			'check_slugs'      => '1',
			'page_404'         => '0',
			'disable_guessing' => '0',
			'strict_guessing'  => '0',
		];
	}

	public function default_settings()
	{
		$settings = [];

		$settings['_front'][] = [
			'field'       => 'check_slugs',
			'title'       => _x( 'Check Slugs', 'Modules: NotFound: Settings', 'gnetwork' ),
			'description' => _x( 'Tries to redirect uknown posttype to it&#8217;s archive.', 'Modules: NotFound: Settings', 'gnetwork' ),
			'default'     => '1',
		];

		$settings['_front'][] = [
			'field'       => 'page_404',
			'type'        => 'page',
			'title'       => _x( 'Custom 404 Error', 'Modules: NotFound: Settings', 'gnetwork' ),
			'description' => _x( 'Displays the selected page as 404 Error page.', 'Modules: NotFound: Settings', 'gnetwork' ),
			'default'     => '0',
			'exclude'     => Settings::getPageExcludes(),
			'after'       => Settings::fieldAfterNewPostType( 'page' ),
		];

		$settings['_front'][] = [
			'field'       => 'disable_guessing',
			'type'        => 'disabled',
			'title'       => _x( 'Disable 404 Guessing', 'Modules: NotFound: Settings', 'gnetwork' ),
			'description' => _x( 'Attempts to guess a redirect URL for a 404 request.', 'Modules: NotFound: Settings', 'gnetwork' ),
		];

		$settings['_front'][] = [
			'field'       => 'strict_guessing',
			'title'       => _x( 'Strict 404 Guessing', 'Modules: NotFound: Settings', 'gnetwork' ),
			'description' => _x( 'Whether to perform a strict guess for a 404 redirect.', 'Modules: NotFound: Settings', 'gnetwork' ),
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
		if ( ! is_404() )
			return;

		if ( $this->options['check_slugs'] )
			$this->_do_check_object_slugs();

		if ( $this->options['page_404'] && is_page( $this->options['page_404'] ) )
			return;

		if ( GNETWORK_REDIRECT_404_URL && GNETWORK_REDIRECT_404_URL == URL::current() )
			return;

		Logger::siteNotFound( '404', HTML::escapeURL( rawurldecode( $_SERVER['REQUEST_URI'] ) ) );
	}

	private function _do_check_object_slugs()
	{
		if ( FALSE === ( $query = get_query_var( 'pagename', FALSE ) ) )
			return;

		$posttypes = get_post_types( [
			'has_archive' => TRUE,
			'public'      => TRUE,
			'_builtin'    => FALSE, // NOTE: is this a good idea?
		], 'objects' );

		foreach ( $posttypes as $posttype ) {

			if ( empty( $posttype->rewrite['slug'] ) )
				continue;

			if ( $query !== $posttype->rewrite['slug'] )
				continue;

			if ( ! $link = WPPostType::getArchiveLink( $posttype->name ) )
				return;

			WordPress::redirect( $link, 303 );

			break;
		}

		$taxonomies = get_taxonomies( [
			'public' => TRUE,
		], 'objects' );

		foreach ( $taxonomies as $taxonomy ) {

			if ( empty( $taxonomy->rewrite['slug'] ) )
				continue;

			if ( $query !== $taxonomy->rewrite['slug'] )
				continue;

			if ( ! $link = WPTaxonomy::getArchiveLink( $taxonomy->name ) )
				return;

			WordPress::redirect( $link, 303 );

			break;
		}
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

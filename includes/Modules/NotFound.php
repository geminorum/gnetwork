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

		$exclude = array_filter( [
			get_option( 'page_on_front' ),
			get_option( 'page_for_posts' ),
		] );

		$settings['_front'][] = [
			'field'       => 'page_404',
			'type'        => 'page',
			'title'       => _x( 'Custom 404 Error', 'Modules: Not Found: Settings', 'gnetwork' ),
			'description' => _x( 'Displays the selected page as 404 Error page on this site.', 'Modules: Not Found: Settings', 'gnetwork' ),
			'default'     => '0',
			'exclude'     => $exclude,
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
			'<code>'.HTML::link( URL::relative( $location ), $location, TRUE ).'</code>' ) );
	}

	public function template_redirect()
	{
		if ( is_404() )
			Logger::siteNotFound( '404', HTML::escapeURL( $_SERVER['REQUEST_URI'] ) );
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

			defined( 'GNETWORK_IS_CUSTOM_404' )
				or define( 'GNETWORK_IS_CUSTOM_404', $page_id );
		}

		return $template;
	}
}

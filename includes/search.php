<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkSearch extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = false;

	public function setup_actions()
	{
		// add_action( 'init', array( & $this, 'init' ), 2 );
		// add_action( 'admin_init', array( & $this, 'admin_init' ) );

		add_filter( 'posts_search', array( & $this, 'posts_search' ) );
		add_action( 'template_redirect', array( & $this, 'template_redirect_singlepost' ), 9 );

		// add_action( 'template_redirect', array( & $this, 'template_redirect_search_again' ), 999 );

		if ( constant( 'GNETWORK_SEARCH_REDIRECT' ) )
			add_action( 'template_redirect', array( & $this, 'template_redirect_search' ), 1 );

	}

	// http://www.wprecipes.com/how-to-redirect-to-post-if-search-results-only-returns-one-post
	public function template_redirect_singlepost()
	{
		if ( is_search() ) {
			global $wp_query;
			if ( $wp_query->post_count == 1 )
				wp_redirect( get_permalink( $wp_query->posts['0']->ID ) );
		}
	}

	// DEPRECATED : see the other method
	// https://gist.github.com/danielbachhuber/1760666
	// Include posts from authors in the search results where either their display name or user login matches the query string
	// @author danielbachhuber
	public function posts_search_OLD( $posts_search )
	{
		// Don't modify the query at all if we're not on the search template or if the LIKE is empty
		if ( ! is_search() || empty( $posts_search ) )
			return $posts_search;

		global $wpdb, $wp_query;

		// Get all of the users of the blog and see if the search query matches either the display name or the user login
		$all_users = get_users();
		$matching_users = array();
		foreach( $all_users as $blog_user ) {
			if ( false !== stripos( $blog_user->display_name, $wp_query->query_vars['s'] ) || false !== stripos( $blog_user->user_login, $wp_query->query_vars['s'] ) )
				$matching_users[] = $blog_user->ID;
		}

		// Don't modify the query if there aren't any matching users
		if ( empty( $matching_users ) )
			return $posts_search;

		// Take a slightly different approach than core where we want all of the posts from these authors
		$posts_search = rtrim( $posts_search, ') ' );
		$posts_search .= ")) OR ( $wpdb->posts.post_author IN (".implode( ',', array_map( 'absint', $matching_users ) ).")";
		return $posts_search.'))';

	}

	// https://gist.github.com/danielbachhuber/7126249
	// Include posts from authors in the search results where either their display name or user login matches the query string
	// @author danielbachhuber
	public function posts_search( $posts_search )
	{
		if ( ! is_search() || empty( $posts_search ) )
			return $posts_search;

		global $wpdb;

		// get all of the users of the blog and see if the search query matches either the display name or the user login
		add_filter( 'pre_user_query', array( & $this, 'pre_user_query' ) );

		$matching_users = get_users( array(
			'count_total' => false,
			'search' => sprintf( '*%s*', sanitize_text_field( get_query_var( 's' ) ) ),
			'search_fields' => array(
				'display_name',
				'user_login',
			),
			'fields' => 'ID',
		) );

		remove_filter( 'pre_user_query', array( & $this, 'pre_user_query' ) );

		// don't modify the query if there aren't any matching users
		if ( empty( $matching_users ) )
			return $posts_search;

		// take a slightly different approach than core where we want all of the posts from these authors
		$posts_search = str_replace( ')))', ")) OR ( {$wpdb->posts}.post_author IN (" . implode( ',', array_map( 'absint', $matching_users ) ) . ")))", $posts_search );

		return $posts_search;
	}

	// Modify get_users() to search display_name instead of user_nicename
	public function pre_user_query( &$user_query ) {

		if ( is_object( $user_query ) )
			$user_query->query_where = str_replace( "user_nicename LIKE", "display_name LIKE", $user_query->query_where );
		return $user_query;
	}

	// Replace your WordPress error pages with a search for the request URI
	// https://gist.github.com/chrisguitarguy/4477015
	public function template_redirect_search_again()
	{
        global $wp_the_query, $wp_query;

        if ( ! is_404() )
            return;

        $uri = isset( $_SERVER['REQUEST_URI'] ) ? trim( $_SERVER['REQUEST_URI'], '/' ) : false;
        if ( ! $uri ) // no request uri? okay.
            return;

        // destroy the query and replace it with our own.
        $wp_the_query = $wp_query = new WP_Query(array(
            'post_type' => apply_filters( 'error_search_post_types', 'any' ),
            's' => str_replace( '/', ' ', $uri ),
        ));
	}

	// https://gist.github.com/danielbachhuber/4152335
	// Modify the main search query to include attachments too
	public function gnetwork_search_parse_query( $query ) {

		if ( is_admin()
			|| ! $query->is_search()
			|| ! $query->is_main_query() )
			return;

		$query->set( 'post_status', array( 'publish', 'inherit' ) );
	} // add_action( 'parse_query', 'gnetwork_search_parse_query' );

	// https://gist.github.com/danielbachhuber/3909732
	// Redirect requests if the timestamp dictating the URI changed.
	public function dbx_maybe_resolve_old_post() {
		if ( is_404() && get_query_var( 'name' ) ) {
			global $wpdb;
			$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='post' AND post_name=%s AND post_status='publish' LIMIT 1", get_query_var( 'name' ) ) );
			if ( $post_id ) {
				wp_safe_redirect( get_permalink( $post_id ) );
				exit;
			}
		}
	} // add_action( 'template_redirect', 'dbx_maybe_resolve_old_post' );

	// https://gist.github.com/danielbachhuber/1409229
	// Handling WordPress search
	// Give the user a 404 if they do an internal WP search
	public function db_unresolve_search()
	{
		global $wp_query;
		if ( $wp_query->is_search )
			$wp_query->is_404 = true;
	} // add_action( 'template_redirect', 'db_unresolve_search' );

	// https://gist.github.com/danielbachhuber/1409229
	//Redirect the user to a new URL path if they do an internal WP search
	public function template_redirect_search()
	{
		global $wp_query;
		if ( $wp_query->is_search ) {
			//wp_redirect( get_site_url( null, '/your-search-path/?s=' . urlencode( $wp_query->query_vars['s'] ) ) );
			wp_redirect( add_query_arg( GNETWORK_SEARCH_QUERYID, $wp_query->query_vars['s'], GNETWORK_SEARCH_URL ) );
			exit;
		}
	}



	// DRAFT
	// https://gist.github.com/norcross/11142989
	/**
	 * restrict search query to only compare titles
	 * @param  string	$search		search query passed by user
	 * @param  mixed	$wp_query	global query from WP being modified
	 * @return mixed			search results
	 */
	public function rkv_search_by_title_only( $search, $wp_query ) {

		global $wpdb;
			// skip processing - no search term in query
			if ( empty( $search ) ) {
			return $search;
			}

		$q	= $wp_query->query_vars;
		$n	= ! empty( $q['exact'] ) ? '' : '%';

		$search	= '';
		$s_and	= '';

		foreach ( (array) $q['search_terms'] as $term ) :
			$term		= esc_sql( like_escape( $term ) );
			$search		.= "{$s_and}($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')";
			$s_and		= ' AND ';
		endforeach;

		if ( ! empty( $search ) ) {
				$search	= " AND ({$search}) ";

				if ( ! is_user_logged_in() ) {
						$search	.= " AND ($wpdb->posts.post_password = '') ";
				}

		}

		return $search;
	}
	// add_filter ( 'posts_search', 'rkv_search_by_title_only', 500, 2	);

	// CHECK: if it's working and ADJUST!
	// http://stackoverflow.com/a/13493126
	public function tax_search_join( $join )
	{
	  global $wpdb;
	  if( is_search() )
	  {
		$join .= "
			INNER JOIN
			  {$wpdb->term_relationships} ON {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id
			INNER JOIN
			  {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id
			INNER JOIN
			  {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
		  ";
	  }
	  return $join;
	}
	//add_filter('posts_join', 'tax_search_join');

	public function tax_search_where( $where )
	{
	  global $wpdb;
	  if( is_search() )
	  {
		// add the search term to the query
		$where .= " OR
		(
		  {$wpdb->term_taxonomy}.taxonomy LIKE 'author'
		  AND
		  {$wpdb->terms}.name LIKE ('%".$wpdb->escape( get_query_var('s') )."%')
		) ";
	  }
	  return $where;
	}
	//add_filter('posts_where', 'tax_search_where');

	public function tax_search_groupby( $groupby )
	{
	  global $wpdb;
	  if( is_search() )
	  {
		$groupby = "{$wpdb->posts}.ID";
	  }
	  return $groupby;
	}
	//add_filter('posts_groupby', 'tax_search_groupby');


}

// http://digwp.com/2010/10/google-custom-search-in-wordpress/
// http://www.wpbeginner.com/wp-tutorials/how-to-add-google-search-in-a-wordpress-site/
// http://www.wpbeginner.com/wp-tutorials/how-to-create-advanced-search-form-in-wordpress-for-custom-post-types/
// http://bavotasan.com/2011/rewrite-search-result-url-for-wordpress/

// use it for opensearch suggestions
// http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/

// http://www.wpbeginner.com/wp-tutorials/how-to-disable-the-search-feature-in-wordpress/

// http://wordpress.org/plugins/wpsearch/
// http://wordpress.org/plugins/relevanssi/

// http://tympanus.net/codrops/2013/06/26/expanding-search-bar-deconstructed/

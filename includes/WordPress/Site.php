<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Site extends Core\Base
{

	// @SEE `nocache_headers()`
	// OLD: `Core\WordPress::doNotCache()`
	public static function doNotCache()
	{
		self::define( 'DONOTCACHEPAGE', TRUE );
	}

	// mocking `get_sites()` results
	// OLD: `Core\WordPress::getAllSites()`
	public static function get( $user_id = FALSE, $network = NULL, $retrieve_url = TRUE, $orderby_path = FALSE )
	{
		global $wpdb;

		$clause_site = $clause_network = '';

		if ( $user_id ) {

			$ids = User::getSites( $user_id, $wpdb->base_prefix );

			// user has no sites!
			if ( ! $ids )
				return [];

			$clause_site = "AND blog_id IN ( '".implode( "', '", esc_sql( $ids ) )."' )";
		}

		if ( TRUE !== $network )
			$clause_network = $wpdb->prepare( "AND site_id = %d", $network ?: get_current_network_id() );

		$clause_order = $orderby_path
			? 'ORDER BY domain, path ASC'
			: 'ORDER BY registered ASC';

		$query = "
			SELECT blog_id, site_id, domain, path
			FROM {$wpdb->blogs}
			WHERE spam = '0'
			AND deleted = '0'
			AND archived = '0'
			{$clause_network}
			{$clause_site}
			{$clause_order}
		";

		$blogs  = [];
		$scheme = IsIt::ssl() ? 'https' : 'http';

		foreach ( $wpdb->get_results( $query, ARRAY_A ) as $blog ) {

			if ( ! $blog )
				continue;

			$siteurl = FALSE;

			if ( $retrieve_url )
				$siteurl = self::url( $blog['blog_id'] );

			if ( ! $siteurl )
				$siteurl = $scheme.'://'.$blog['domain'].$blog['path'];

			$blogs[$blog['blog_id']] = (object) [
				'userblog_id' => $blog['blog_id'],
				'network_id'  => $blog['site_id'],
				'domain'      => $blog['domain'],
				'path'        => $blog['path'],
				'siteurl'     => Core\URL::untrail( $siteurl ),
			];
		}

		return $blogs;
	}

	// OLD: `Core\WordPress::getSiteURL()`
	public static function url( $blog_id, $switch = FALSE )
	{
		$url = FALSE;

		// WORKING BUT DISABLED!
		// if ( function_exists( 'bp_blogs_get_blogmeta' ) )
		// 	$url = bp_blogs_get_blogmeta( $blog_id, 'url', TRUE );

		if ( ! $url && function_exists( 'get_site_meta' ) )
			$url = get_site_meta( $blog_id, 'siteurl', TRUE );

		if ( ! $url && $blog_id == get_current_blog_id() )
			return get_option( 'siteurl' );

		if ( ! $url && $switch ) {

			switch_to_blog( $blog_id );
			$url = get_option( 'siteurl' );
			restore_current_blog();
		}

		return $url;
	}

	// OLD: `Core\WordPress::getHostName()`
	public static function hostname()
	{
		return is_multisite() && function_exists( 'get_network' )
			? get_network()->domain
			: preg_replace( '#^https?://#i', '', get_option( 'home' ) );
	}

	// OLD: `getBlogNameforEmail()`
	// OLD: `Core\WordPress::getSiteNameforEmail()`
	public static function nameforEmail( $site = FALSE )
	{
		if ( ! $site && is_multisite() && function_exists( 'get_network' ) )
			return get_network()->site_name;

		// The `blogname` option is escaped with `esc_html()` on the way into the database
		// in sanitize_option we want to reverse this for the plain text arena of emails.
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	// OLD: `currentBlog()`
	// OLD: `Core\WordPress::currentSiteName()`
	public static function name( $convert_slash = TRUE )
	{
		return Core\URL::prepTitle( get_option( 'home' ), $convert_slash );
	}

	// OLD: `Core\WordPress::getSiteName()`
	public static function title( $blog_id, $switch = FALSE )
	{
		$name = FALSE;

		// WORKING BUT DISABLED!
		// if ( function_exists( 'bp_blogs_get_blogmeta' ) )
		// 	$name = bp_blogs_get_blogmeta( $blog_id, 'name', TRUE );

		if ( ! $name && function_exists( 'get_site_meta' ) )
			$name = get_site_meta( $blog_id, 'blogname', TRUE );

		if ( ! $name && $blog_id == get_current_blog_id() )
			return get_option( 'blogname' );

		if ( ! $name && $switch ) {

			switch_to_blog( $blog_id );
			$name = get_option( 'blogname' );
			restore_current_blog();
		}

		return $name;
	}

	// @SOURCE: `wp-load.php`
	// OLD: `Core\WordPress::getConfigPHP()`
	public static function getConfigPHP( $path = ABSPATH )
	{
		// The config file resides in `ABSPATH`
		if ( file_exists( $path.'wp-config.php' ) )
			return $path.'wp-config.php';

		// The config file resides one level above `ABSPATH` but is not part of another install
		$above = dirname( $path );

		if ( @file_exists( $above.'/wp-config.php' ) && ! @file_exists( $above.'/wp-settings.php' ) )
			return $above.'/wp-config.php';

		return FALSE;
	}

	// OLD: `Core\WordPress::definedConfigPHP()`
	public static function definedConfigPHP( $constant = 'WP_DEBUG' )
	{
		if ( ! $file = self::getConfigPHP() )
			return FALSE;

		$pattern = "define\( ?'".$constant."'";
		$pattern = "/^$pattern.*/m";

		$contents = File::getContents( $file );

		if ( preg_match_all( $pattern, $contents, $matches ) )
			return TRUE;

		return FALSE;
	}

	// OLD: `Core\WordPress::customFile()`
	public static function customFile( $filename, $path = FALSE )
	{
		$stylesheet = get_stylesheet_directory();

		if ( file_exists( $stylesheet.'/'.$filename ) )
			return $path ? ( $stylesheet().'/'.$filename )
				: get_stylesheet_directory_uri().'/'.$filename;

		$template = get_template_directory();

		if ( file_exists( $template.'/'.$filename ) )
			return $path ? ( $template.'/'.$filename )
				: get_template_directory_uri().'/'.$filename;

		if ( file_exists( WP_CONTENT_DIR.'/'.$filename ) )
			return $path ? ( WP_CONTENT_DIR.'/'.$filename )
				: ( WP_CONTENT_URL.'/'.$filename );

		return FALSE;
	}
}

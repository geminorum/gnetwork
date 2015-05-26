<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

// modified from P2P_Mustache by http://scribu.net
// http://plugins.svn.wordpress.org/posts-to-posts/trunk/admin/mustache.php

// https://github.com/bobthecow/mustache.php/wiki

abstract class gNetworkMustache {

	private static $loader;
	private static $loader_custom;
	private static $mustache;

	public static function init()
	{
		if ( ! class_exists( 'Mustache' ) )
			require_once( GNETWORK_DIR.'assets/libs/mustache/Mustache.php');

		if ( ! class_exists( 'MustacheLoader' ) )
			require_once( GNETWORK_DIR.'assets/libs/mustache/MustacheLoader.php' );

		self::$loader = new MustacheLoader( GNETWORK_DIR.'templates', 'xml' );

		//if ( is_dir( WP_CONTENT_DIR.'/templates' ) )
			//self::$loader_custom = new MustacheLoader( WP_CONTENT_DIR.'/templates', 'xml' );

		self::$mustache = new Mustache( null, null, self::$loader );
	}

	public static function render( $template, $data )
	{
		try {

			return self::$mustache->render( self::$loader[$template], $data );

		} catch ( MustacheException $e ) {

			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	}

	public static function render_CUSTOM( $file, $data )
	{
		// WHAT IF: we merge partials(loaders) ?
		if ( file_exists( WP_CONTENT_DIR.'/templates/gnetwork-'.$file.'.xml' ) )
			return self::$mustache->render( file_get_contents( WP_CONTENT_DIR.'/templates/gnetwork-'.$file.'.xml' ), $data, self::$loader_custom );

		return self::$mustache->render( self::$loader[$file], $data );
	}

}

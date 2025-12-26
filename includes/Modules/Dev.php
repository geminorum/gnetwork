<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Dev extends gNetwork\Module
{

	protected $key = 'dev';

	protected $ajax       = TRUE;
	protected $cron       = TRUE;
	protected $installing = TRUE;

	protected function setup_actions()
	{
		// $this->filter( 'http_request_args', 2, 12 );
		$this->filter( 'redirect_canonical', 2, 99999 );

		// $this->action( 'http_api_curl', 3, 999 );
		$this->filter_false( 'https_ssl_verify' );
		$this->filter_false( 'https_local_ssl_verify' );
		// $this->filter_false( 'wp_is_php_version_acceptable', 1, 9999 ); // to help with translation
		// $this->filter_true( 'jetpack_development_mode' );
		// $this->filter_true( 'jetpack_offline_mode' ); // https://jetpack.com/support/offline-mode/
		// $this->filter_true( 'wp_is_application_passwords_available' );

		$this->action( 'pre_get_posts', 1, 99 );
		$this->action( 'shutdown', 1, 99 );

		// $this->filter( 'embed_oembed_html', 4, 1 );
		$this->filter( 'pre_get_avatar', 3, 99 );
		// remove_filter( 'get_avatar', 'bp_core_fetch_avatar_filter', 10 );
	}

	public function setup_menu( $context )
	{
		$this->register_tool( _x( 'Dev Tools', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function render_tools( $uri, $sub = 'general' )
	{
		echo '<br />';

		// self::generateCustomTax();
		// self::generateDropinFile();
	}

	public function http_request_args( $r, $url )
	{
		$r['sslverify'] = FALSE;
		return $r;
	}

	public function redirect_canonical( $redirect_url, $requested_url )
	{
		if ( $redirect_url && Core\URL::stripFragment( $redirect_url ) !== Core\URL::stripFragment( $requested_url ) )
			Logger::siteDEBUG( 'CANONICAL', esc_url( $requested_url ).' >> '.esc_url( $redirect_url ) );

		return $redirect_url;
	}

	public function http_api_curl( &$handle, $parsed_args, $url )
	{
		if ( $cert = ini_get( 'curl.cainfo' ) )
			curl_setopt( $handle, CURLOPT_CAINFO, $cert );
	}

	// blocks `oEmbeds` from displaying
	public function embed_oembed_html( $html, $url, $attr, $post_ID )
	{
		return '<div class="gnetwork-wrap -dev -placeholder"><p>oEmbed blocked for <code>'.esc_url( $url ).'</code></p></div>';
	}

	// Replace all instances of Gravatar with a local image file to remove the call to remote service.
	// It's faster than airplane-mode
	public function pre_get_avatar( $null, $id_or_email, $args )
	{
		return Core\HTML::tag( 'img', [
			'src'    => 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==',
			'alt'    => $args['alt'],
			'width'  => $args['size'],
			'height' => $args['size'],
			'style'  => 'background:#eee;',
			'class'  => [
				'avatar',
				'avatar-'.$args['size'],
				'photo',
			],
		] );
	}

	// https://wordpress.org/plugins/stop-query-posts/
	public function pre_get_posts( $query )
	{
		if ( empty( $GLOBALS['wp_query'] ) )
			return;

		if ( $query === $GLOBALS['wp_query'] && ! $query->is_main_query() )
			_doing_it_wrong( 'query_posts', 'You should <a href="http://wordpress.tv/2012/06/15/andrew-nacin-wp_query/">not use query_posts</a>.', NULL );
	}

	public function shutdown()
	{
		global $pagenow, $wpdb, $gPeopleNetwork, $gMemberNetwork;

		$log = [];

		$log[] = self::timerStop( FALSE, 3 ).'s';
		$log[] = number_format( ( memory_get_peak_usage() / 1024 / 1024 ), 1, ',', '' ).'/'.ini_get( 'memory_limit' );
		$log[] = $wpdb->num_queries.'q';

		if ( is_network_admin() )
			$log[] = 'NetworkAdmin';

		if ( is_blog_admin() )
			$log[] = 'BlogAdmin';

		if ( is_user_admin() )
			$log[] = 'UserAdmin';

		if ( is_admin() )
			$log[] = 'Admin';

		if ( WordPress\IsIt::flush() )
			$log[] = 'Flush';

		if ( WordPress\IsIt::cli() )
			$log[] = 'CLI';

		if ( WordPress\IsIt::cron() )
			$log[] = 'CRON';

		if ( WordPress\IsIt::ajax() )
			$log[] = 'AJAX';

		if ( WordPress\IsIt::rest() )
			$log[] = 'REST';

		if ( function_exists( 'gNetwork' ) )
			$log[] = 'gN:'.Core\File::formatSize( self::varSize( gNetwork() ) );

		if ( function_exists( 'gEditorial' ) )
			$log[] = 'gE:'.Core\File::formatSize( self::varSize( gEditorial() ) );

		if ( $gPeopleNetwork )
			$log[] = 'gP:'.Core\File::formatSize( self::varSize( $gPeopleNetwork ) );

		if ( $gMemberNetwork )
			$log[] = 'gM:'.Core\File::formatSize( self::varSize( $gMemberNetwork ) );

		if ( function_exists( 'gPersianDate' ) )
			$log[] = 'gPD:'.Core\File::formatSize( self::varSize( gPersianDate() ) );

		if ( $_SERVER['REQUEST_URI'] )
			$log[] = $_SERVER['REQUEST_URI'];

		if ( ! empty( $pagenow ) && ! WordPress\IsIt::rest() )
			$log[] = 'PAGE:'.$pagenow;

		$prefix = 'BENCHMARK: ';

		if ( is_multisite() )
			$prefix.= WordPress\Site::name().': ';

		Logger::DEBUG( $prefix.implode( '|', $log ) );
	}

	public static function generateCustomTax()
	{
		// Utilities::renderMustache( 'posttype-post', self::generateCustomTax_Post() );
		// Utilities::renderMustache( 'posttype-page', self::generateCustomTax_Post() );
		// Utilities::renderMustache( 'taxonomy-tag', self::generateCustomTax_Tag() );
		// Utilities::renderMustache( 'taxonomy-cat', self::generateCustomTax_Cat() );
	}

	public static function generateDropinFile()
	{
		$data = [
			'title'   => 'Database Error!',
			'message' => 'Error establishing a database connection.',
		];

		$contents = Utilities::renderMustache( 'db-error', $data, FALSE );
		Core\File::putContents( 'db-error.php', $contents, WP_CONTENT_DIR );
	}

	public static function generateCustomTax_Post()
	{
		return [
			'name'           => 'Divisions',
			'name_lower'     => 'divisions',
			'singular'       => 'Division',
			'singular_lower' => 'division',

			'description'    => 'Division Post Type',
			'featured'       => FALSE, //'Featured Image', // Featured Image / or FALSE to disable
			'featured_lower' => 'featured image', // featured image

			'context'        => 'Divisions Module: Division CPT Labels',
			'textdomain'     => 'GEDITORIAL_TEXTDOMAIN',
		];
	}

	public static function generateCustomTax_Tag()
	{
		return [
			// 'name'           => 'Publication Sizes',
			// 'name_lower'     => 'publication sizes',
			// 'singular'       => 'Publication Size',
			// 'singular_lower' => 'publication size',

			'name'           => 'Alphabets',
			'name_lower'     => 'alphabets',
			'singular'       => 'Alphabet',
			'singular_lower' => 'alphabet',

			'context'        => 'Alphabet Module: Alphabet Tax Labels',
			'textdomain'     => 'GEDITORIAL_TEXTDOMAIN',
		];
	}

	public static function generateCustomTax_Cat()
	{
		return [
			// 'name'           => 'Event Categories',
			// 'name_lower'     => 'event categories',
			// 'singular'       => 'Event Category',
			// 'singular_lower' => 'event category',

			// 'name'           => 'Publication Types',
			// 'name_lower'     => 'publication types',
			// 'singular'       => 'Publication Type',
			// 'singular_lower' => 'publication type',

			// 'name'           => 'Publication Statuses',
			// 'name_lower'     => 'publication statuses',
			// 'singular'       => 'Publication Status',
			// 'singular_lower' => 'publication status',

			'name'           => 'Sections',
			'name_lower'     => 'sections',
			'singular'       => 'Section',
			'singular_lower' => 'section',

			'context'        => 'Magazine Module: Section Tax Labels',
			'textdomain'     => 'GEDITORIAL_TEXTDOMAIN',
		];
	}
}

<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Dev extends ModuleCore
{

	protected $key  = 'dev';
	protected $ajax = TRUE;

	protected function setup_actions()
	{
		add_filter( 'http_request_args', array( $this, 'http_request_args' ), 12, 2 );
		add_filter( 'https_local_ssl_verify', '__return_false' );
		add_filter( 'https_ssl_verify', '__return_false' );

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 99 );

		add_action( 'shutdown', array( $this, 'shutdown' ), 99 );

		if ( is_admin() )
			add_filter( 'contextual_help', array( $this, 'contextual_help' ), 10, 3 );

		// add_filter( 'embed_oembed_html', array( $this, 'embed_oembed_html' ), 1,  4 );
		add_filter( 'pre_get_avatar', array( $this, 'pre_get_avatar' ), 99, 3 );
		remove_filter( 'get_avatar', 'bp_core_fetch_avatar_filter', 10, 6 );

		// add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		// add_filter( 'login_url', array( $this, 'login_url' ), 10, 2 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Dev Tools', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function settings_html( $uri, $sub = 'general' )
	{
		echo '<form class="gnetwork-form" method="post" action="">';

			$this->settings_fields( $sub, 'bulk' );

			self::generateCustomTax();

		echo '</form>';
	}

	public function http_request_args( $r, $url )
	{
		$r['sslverify'] = FALSE;
		return $r;
	}

	// block oEmbeds from displaying.
	public function embed_oembed_html( $html, $url, $attr, $post_ID )
	{
		return sprintf( '<div class="loading-placeholder gnetwork-dev-placeholder"><p>%s</p></div>',
			sprintf( _x( 'Airplane Mode is enabled. oEmbed blocked for %1$s.', 'Modules: Dev', GNETWORK_TEXTDOMAIN ), esc_url( $url ) ) );
	}

	// replace all instances of gravatar with a local image file to remove the call to remote service.
	// it's faster than airplane-mode
	public function pre_get_avatar( $null, $id_or_email, $args )
	{
		return HTML::tag( 'img', array(
			'src'    => 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==',
			'alt'    => $args['alt'],
			'width'  => $args['size'],
			'height' => $args['size'],
			'style'  => 'background:#eee;',
			'class'  => array(
				'avatar',
				'avatar-'.$args['size'],
				'photo',
			),
		) );
	}

	// https://wordpress.org/plugins/stop-query-posts/
	public function pre_get_posts( $query )
	{
		if ( $query === $GLOBALS['wp_query'] && ! $query->is_main_query() )
			_doing_it_wrong( 'query_posts', 'You should <a href="http://wordpress.tv/2012/06/15/andrew-nacin-wp_query/">not use query_posts</a>.', NULL );
	}

	public function shutdown()
	{
		global $pagenow, $wpdb, $gPeopleNetwork, $gMemberNetwork;

		$log = array(
			self::timer_stop( FALSE, 3 ).'s',
			number_format( ( memory_get_peak_usage() / 1024 / 1024 ), 1, ',', '' ).'/'.ini_get( 'memory_limit' ),
			$wpdb->num_queries.'q',
		);

		if ( is_network_admin() )
			$log[] = 'NetworkAdmin';

		if ( is_blog_admin() )
			$log[] = 'BlogAdmin';

		if ( is_user_admin() )
			$log[] = 'UserAdmin';

		if ( is_admin() )
			$log[] = 'Admin';

		if ( WordPress::isFlush() )
			$log[] = 'Flush';

		if ( WordPress::isCLI() )
			$log[] = 'CLI';

		if ( WordPress::isCRON() )
			$log[] = 'CRON';

		if ( WordPress::isAJAX() )
			$log[] = 'AJAX';

		if ( function_exists( 'gNetwork' ) )
			$log[] = 'gN:'.File::formatSize( self::size( gNetwork() ) );

		if ( function_exists( 'gEditorial' ) )
			$log[] = 'gE:'.File::formatSize( self::size( gEditorial() ) );

		if ( $gPeopleNetwork )
			$log[] = 'gP:'.File::formatSize( self::size( $gPeopleNetwork ) );

		if ( $gMemberNetwork )
			$log[] = 'gM:'.File::formatSize( self::size( $gMemberNetwork ) );

		if ( function_exists( 'gPersianDate' ) )
			$log[] = 'gPD:'.File::formatSize( self::size( gPersianDate() ) );

		if ( $_SERVER['REQUEST_URI'] )
			$log[] = $_SERVER['REQUEST_URI'];

		if ( ! empty( $pagenow ) )
			$log[] = 'PageNow:'.$pagenow;

		self::log( 'BENCHMARK', implode( '|', $log ) );
	}

	// FIXME: WORKING: ADJUST IT
	// @SOURCE: http://code.tutsplus.com/articles/quick-tip-get-the-current-screens-hooks--wp-26891
	public function contextual_help( $old_help, $screen_id, $screen )
	{
		global $hook_suffix;

		// List screen properties
		$variables = '<ul style="width:50%;float:left;"><strong>Screen variables</strong>'
			. sprintf( '<li>Screen id: %s</li>', $screen_id )
			. sprintf( '<li>Screen base: %s</li>', $screen->base )
			. sprintf( '<li>Parent base: %s</li>', $screen->parent_base )
			. sprintf( '<li>Parent file: %s</li>', $screen->parent_file )
			. sprintf( '<li>Hook suffix: %s</li>', $hook_suffix )
			. '</ul>';

		// Append global $hook_suffix to the hook stems
		$hooks = array(
			"load-$hook_suffix",
			"admin_print_styles-$hook_suffix",
			"admin_print_scripts-$hook_suffix",
			"admin_head-$hook_suffix",
			"admin_footer-$hook_suffix"
		);

		// If add_meta_boxes or add_meta_boxes_{screen_id} is used, list these too
		if ( did_action( 'add_meta_boxes_' . $screen_id ) )
			$hooks[] = 'add_meta_boxes_' . $screen_id;

		if ( did_action( 'add_meta_boxes' ) )
			$hooks[] = 'add_meta_boxes';

		// Get List HTML for the hooks
		$hooks = '<ul style="width:50%;float:left;"><strong>Hooks</strong><li>'.implode( '</li><li>', $hooks ).'</li></ul>';

		// Combine $variables list with $hooks list.
		$help_content = $variables . $hooks;

		// $help_content .= self::dump( $screen, TRUE, FALSE );

		// Add help panel
		$screen->add_help_tab( array(
			'id'      => 'gnetwork-screen-help',
			'title'   => 'Screen Information',
			'content' => $help_content,
		));

		return $old_help;
	}

	public static function generateCustomTax()
	{
		// Utilities::renderMustache( 'posttype-post', self::generateCustomTax_Post() );
		// Utilities::renderMustache( 'posttype-page', self::generateCustomTax_Post() );
		// Utilities::renderMustache( 'taxonomy-tag', self::generateCustomTax_Tag() );
		Utilities::renderMustache( 'taxonomy-cat', self::generateCustomTax_Cat() );
	}

	public static function generateCustomTax_Post()
	{
		return array(
			'name'           => 'Divisions',
			'name_lower'     => 'divisions',
			'singular'       => 'Division',
			'singular_lower' => 'division',

			'description'    => 'Division Post Type',
			'featured'       => FALSE, //'Featured Image', // Featured Image / or FALSE to disable
			'featured_lower' => 'featured image', // featured image

			'context'        => 'Divisions Module: Division CPT Labels',
			'textdomain'     => 'GEDITORIAL_TEXTDOMAIN',
		);
	}

	public static function generateCustomTax_Tag()
	{
		return array(
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
		);
	}

	public static function generateCustomTax_Cat()
	{
		return array(
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
		);
	}
}

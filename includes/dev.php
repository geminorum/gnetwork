<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkDev extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;
	protected $ajax       = TRUE;

	protected function setup_actions()
	{
		add_filter( 'http_request_args', array( $this, 'http_request_args' ), 12, 2 );
		add_filter( 'https_local_ssl_verify', '__return_false' );
		add_filter( 'https_ssl_verify', '__return_false' );

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 99 );

		register_shutdown_function( array( $this, 'shutdown' ) );

		if ( is_admin() )
			add_action( 'contextual_help', array( $this, 'contextual_help' ), 10, 3 );

		// add_filter( 'embed_oembed_html', array( $this, 'embed_oembed_html' ), 1,  4 );
		add_filter( 'pre_get_avatar', array( $this, 'pre_get_avatar' ), 99, 3 );
		remove_filter( 'get_avatar', 'bp_core_fetch_avatar_filter', 10, 6 );

		// add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		// add_filter( 'login_url', array( $this, 'login_url' ), 10, 2 );
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
			sprintf( __( 'Airplane Mode is enabled. oEmbed blocked for %1$s.', GNETWORK_TEXTDOMAIN ), esc_url( $url ) ) );
	}

	// replace all instances of gravatar with a local image file to remove the call to remote service.
	// it's faster than airplane-mode
	public function pre_get_avatar( $null, $id_or_email, $args )
	{
		return self::html( 'img', array(
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

	// FIXME: WORKING: BETTER
	public function shutdown()
	{
		global $gNetwork, $gEditorial, $gPeopleNetwork, $gMemberNetwork;

		$log = 'gNetwork:'.size_format( gNetworkUtilities::size( $gNetwork ) )
			.' | gEditorial:'.size_format( gNetworkUtilities::size( $gEditorial ) )
			.' | gPeople:'.size_format( gNetworkUtilities::size( $gPeopleNetwork ) )
			.' | gMember:'.size_format( gNetworkUtilities::size( $gMemberNetwork ) );

		error_log( $log );
	}

	// FIXME: WORKING: ADJUST IT
	// http://code.tutsplus.com/articles/quick-tip-get-the-current-screens-hooks--wp-26891
	public function contextual_help( $contextual_help, $screen_id, $screen )
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

		// Add help panel
		$screen->add_help_tab( array(
			'id'      => 'wptuts-screen-help',
			'title'   => 'Screen Information',
			'content' => $help_content,
		));

		return $contextual_help;
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Dashboard extends gNetwork\Module
{

	protected $key   = 'dashboard';
	protected $front = FALSE;
	protected $ajax  = TRUE;

	protected function setup_actions()
	{
		$this->action( 'current_screen' );

		foreach ( [
			'wp_network_dashboard_setup',
			'wp_user_dashboard_setup',
			'wp_dashboard_setup',
		] as $action )
			add_action( $action, [ $this, 'wp_dashboard_setup' ], 20 );
	}

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			&& $screen->post_type ) {

				if ( WordPress::cuc( 'manage_options' ) ) {

					ob_start();
						HTML::tableSide( get_all_post_type_supports( $screen->post_type ), FALSE );
					$content = ob_get_clean();

					$screen->add_help_tab( [
						'id'       => 'gnetwork-dashboard-posttype-overview',
						'title'    => _x( 'Post Type Supports', 'Modules: Dashboard: Help Content Title', GNETWORK_TEXTDOMAIN ),
						'content'  => '<p>'.$content.'</p>',
						'priority' => 99,
					] );
			}
		}
	}

	public function wp_dashboard_setup()
	{
		$multisite = is_multisite();
		$network   = is_network_admin();
		$blog      = is_blog_admin();
		$user      = is_user_admin();
		$screen    = get_current_screen();

		remove_meta_box( 'dashboard_primary', $screen, 'side' );
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );
		remove_action( 'activity_box_end', [ 'Akismet_Admin', 'dashboard_stats' ] );
		remove_action( 'rightnow_end', [ 'Akismet_Admin', 'rightnow_stats' ] );

		if ( $blog && current_user_can( 'edit_posts' ) ) {

			remove_meta_box( 'dashboard_right_now', $screen, 'normal' );

			add_meta_box( $this->classs( 'right-now' ),
				_x( 'At a Glance', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_right_now' ], $screen, 'normal', 'high' );

			remove_action( 'activity_box_end', 'wp_dashboard_quota' );

			if ( $multisite && current_user_can( 'upload_files' ) )
				add_action( 'activity_box_end', [ $this, 'dashboard_quota' ] );
		}

		if ( $user && gNetwork()->option( 'dashboard_sites', 'user' ) )
			wp_add_dashboard_widget(
				$this->classs( 'user-sites' ),
				_x( 'Your Sites', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_user_sites' ]
			);

		if ( $blog && has_filter( $this->hook( 'external_feeds' ) ) ) {
			wp_add_dashboard_widget(
				$this->classs( 'external-feed' ),
				_x( 'External Feed', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_external_feed' ]
			);
		}

		if ( $user && gNetwork()->option( 'tos_display', 'user' ) )
			wp_add_dashboard_widget(
				$this->classs( 'tos' ),
				gNetwork()->option( 'tos_title', 'user',
					_x( 'Terms of Service', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN )
				), [ $this, 'widget_tos' ]
			);

		if ( $user && GNETWORK_NETWORK_USERMENU && gNetwork()->option( 'dashboard_menu', 'user' ) )
			add_meta_box( $this->classs( 'usermenu' ),
				_x( 'Your Navigation', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_usermenu' ], $screen, 'normal', 'high' );
	}

	public function widget_external_feed()
	{
		if ( $this->check_hidden_metabox( 'external-feed' ) )
			return;

		$feeds = [];

		foreach ( $this->filters( 'external_feeds', [] ) as $name => $feed )
			$feeds[$name] = array_merge( [
				'link'         => 'http://geminorum.ir/',
				'url'          => 'http://geminorum.ir/feed',
				'title'        => $name,
				'items'        => 3,
				'show_summary' => 1,
				'show_author'  => 0,
				'show_date'    => 1,
			], $feed );

		wp_dashboard_cached_rss_widget( 'gnetwork_feeds', 'wp_dashboard_primary_output', $feeds );
	}

	public function ajax()
	{
		require_once( ABSPATH.'wp-admin/includes/dashboard.php' );

		switch ( $_GET['widget'] ) {

			case 'gnetwork_dashboard_external_feed':

				$this->widget_external_feed();

			break;
		}

		wp_die();
	}

	public function widget_tos()
	{
		echo '<div class="gnetwork-admin-wrap-widget -user-tos">';
			echo wpautop( gNetwork()->option( 'tos_text', 'user', gNetwork()->na() ) );
		echo '</div>';
	}

	// FIXME: needs better styling
	public function widget_usermenu()
	{
		if ( $this->check_hidden_metabox( 'usermenu' ) )
			return;

		if ( $html = Adminbar::getNetworkMenu( GNETWORK_NETWORK_USERMENU, FALSE ) )
			echo '<div class="gnetwork-admin-wrap-widget -usermenu">'.$html.'</div>';
		else
			HTML::desc( _x( '&#8220;Not all those who wander are lost!&#8221;', 'Modules: Dashboard: User Menu', GNETWORK_TEXTDOMAIN ), FALSE, '-empty' );
	}

	public function widget_user_sites()
	{
		if ( $this->check_hidden_metabox( 'user-sites' ) )
			return;

		$blogs = get_blogs_of_user( get_current_user_id() );

		echo '<div class="gnetwork-admin-wrap-widget -user-sites">';

			if ( empty( $blogs ) )
				HTML::desc( gNetwork()->na() );
			else
				echo Site::tableUserSites( $blogs, FALSE );

		echo '</div>';
	}

	public function widget_right_now()
	{
		// wrap to use core styles
		echo '<div id="dashboard_right_now" class="-core-styles">';

		if ( $this->check_hidden_metabox( 'right-now', '</div>' ) )
			return;

		$html = '';

		foreach ( [ 'post', 'page' ] as $post_type ) {

			$num_posts = wp_count_posts( $post_type );

			if ( $num_posts && $num_posts->publish ) {

				$text = 'post' == $post_type
					? _n( '%s Post', '%s Posts', $num_posts->publish )
					: _n( '%s Page', '%s Pages', $num_posts->publish );

				$text   = sprintf( $text, number_format_i18n( $num_posts->publish ) );
				$object = get_post_type_object( $post_type );

				if ( $object && current_user_can( $object->cap->edit_posts ) )
					$html.= sprintf( '<li class="%1$s-count"><a href="edit.php?post_type=%1$s">%2$s</a></li>', $post_type, $text );

				else
					$html.= sprintf( '<li class="%1$s-count"><span>%2$s</span></li>', $post_type, $text );
			}
		}

		// filters the array of extra elements to list in the 'At a Glance' dashboard widget
		if ( $elements = apply_filters( 'dashboard_glance_items', [] ) )
			$html.= '<li>'.implode( '</li><li>', $elements ).'</li>';

		if ( $num_comm = wp_count_comments() ) {

			if ( $num_comm->approved || $num_comm->moderated ) {

				$text = sprintf( _nx( '%s Comment', '%s Comments', $num_comm->approved, 'Modules: Dashboard: Right Now', GNETWORK_TEXTDOMAIN ), number_format_i18n( $num_comm->approved ) );

				$html.= '<li class="comment-count"><a href="edit-comments.php">'.$text.'</a></li>';

				$moderated_comments_count_i18n = number_format_i18n( $num_comm->moderated );

				$text = sprintf( _nx( '%s Awaiting Comment', '%s Awaiting Comments', $num_comm->moderated, 'Modules: Dashboard: Right Now', GNETWORK_TEXTDOMAIN ), $moderated_comments_count_i18n );

				/* translators: %s: number of comments in moderation */
				$aria_label = sprintf( _nx( '%s comment in moderation', '%s comments in moderation', $num_comm->moderated, 'comments' ), $moderated_comments_count_i18n );

				$html.= '<li class="comment-mod-count'.( $num_comm->moderated ? '' : ' hidden' ).'">';
				$html.= '<a href="edit-comments.php?comment_status=moderated" aria-label="'.esc_attr__( $aria_label ).'">'.$text.'</a></li>';
			}

			if ( $num_comm->spam > 0 ) {
				$spam = sprintf( _nx( '%s Spam Comment', '%s Spam Comments', $num_comm->spam, 'Modules: Dashboard: Right Now', GNETWORK_TEXTDOMAIN ), number_format_i18n( $num_comm->spam ) );
				$html.= '<li class="comment-spam-count"><a href="edit-comments.php?comment_status=spam">'.$spam.'</a></li>';
			}
		}

		if ( $html )
			$html = '<ul>'.$html.'</ul>';

		// FIXME: add better
		// update_right_now_message();

		// check if search engines are asked not to index this site.
		if ( current_user_can( 'manage_options' ) && '0' == get_option( 'blog_public' ) ) {

			$title   = apply_filters( 'privacy_on_link_title', '' );
			$content = apply_filters( 'privacy_on_link_text', __( 'Search Engines Discouraged' ) );
			$attr    = '' === $title ? '' : " title='$title'";

			$html.= "<p><a href='options-reading.php'$attr>$content</a></p>";
		}

		if ( $html )
			echo '<div class="main">'.$html.'</div>';
		else
			HTML::desc( _x( 'No Content available!', 'Modules: Dashboard: Right Now', GNETWORK_TEXTDOMAIN ), FALSE, '-empty' );

		ob_start();
		// do_action( 'rightnow_end' ); // old hook
		do_action( 'activity_box_end' );
		$actions = ob_get_clean();

		if ( ! empty( $actions ) )
			echo '<div class="sub">'.$actions.'</div>';

		echo '</div>';
	}

	public function dashboard_quota()
	{
		if ( get_site_option( 'upload_space_check_disabled' )  )
			return;

		$quota = get_space_allowed();
		$used  = get_space_used();

		$percent = $used > $quota ? '100' : ( ( $used / $quota ) * 100 );
		$class   = $percent >= 70 ? ' warning' : '';

		HTML::h3( __( 'Storage Space' ), 'mu-storage' );

		echo '<div class="mu-storage"><ul><li class="storage-count">';

		printf( '<a href="%1$s">%2$s <span class="screen-reader-text">(%3$s)</span></a>',
			esc_url( admin_url( 'upload.php' ) ),
			sprintf( __( '%s MB Space Allowed' ), number_format_i18n( $quota ) ),
			__( 'Manage Uploads' )
		);

		echo '</li><li class="storage-count '.$class.'">';

		$template = sprintf( _x( '%s Space Used', 'Modules: Dashboard: Space Quota', GNETWORK_TEXTDOMAIN ),
			'<span title="&lrm;%s MB&rlm;">%s'.( is_rtl() ? '&#1642;' : '&#37;' ).'</span>' );

		printf( '<a href="%1$s" class="musublink">%2$s <span class="screen-reader-text">(%3$s)</span></a>',
			esc_url( admin_url( 'upload.php' ) ),
			sprintf( $template, number_format_i18n( round( $used, 2 ), 2 ), number_format_i18n( $percent ) ),
			__( 'Manage Uploads' )
		);

		echo '</li></ul></div>';
	}
}

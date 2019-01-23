<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;

class Dashboard extends gNetwork\Module
{

	protected $key   = 'dashboard';
	protected $front = FALSE;
	protected $ajax  = TRUE;

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			$this->action( 'wp_dashboard_setup', 0, 20 );

		} else if ( 'dashboard-user' == $screen->base ) {

			$this->action( 'wp_user_dashboard_setup', 0, 20 );

		} else if ( 'dashboard-network' == $screen->base ) {

			$this->action( 'wp_network_dashboard_setup', 0, 20 );

		} else if ( 'edit' == $screen->base && $screen->post_type ) {

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
		$screen = get_current_screen();

		remove_meta_box( 'dashboard_primary', $screen, 'side' );
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		remove_filter( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' ); // @REF: https://core.trac.wordpress.org/ticket/41316
		remove_action( 'activity_box_end', [ 'Akismet_Admin', 'dashboard_stats' ] );
		remove_action( 'rightnow_end', [ 'Akismet_Admin', 'rightnow_stats' ] );

		if ( current_user_can( 'edit_posts' ) ) {

			remove_meta_box( 'dashboard_right_now', $screen, 'normal' );

			add_meta_box( $this->classs( 'right-now' ),
				_x( 'At a Glance', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_right_now' ], $screen, 'normal', 'high' );
		}

		if ( has_filter( $this->hook( 'external_feeds' ) ) ) {
			wp_add_dashboard_widget(
				$this->classs( 'external-feed' ),
				_x( 'External Feed', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_external_feed' ]
			);
		}

		$this->action( 'activity_box_end', 0, 4 );

		if ( ! is_multisite() )
			return;

		if ( current_user_can( 'upload_files' ) ) {
			remove_action( 'activity_box_end', 'wp_dashboard_quota' );
			$this->filter_module( 'dashboard', 'pointers', 1, 5, 'quota' );
		}
	}

	public function wp_user_dashboard_setup()
	{
		remove_meta_box( 'dashboard_primary', NULL, 'side' );

		if ( gNetwork()->option( 'dashboard_sites', 'user' ) )
			wp_add_dashboard_widget(
				$this->classs( 'user-sites' ),
				_x( 'Your Sites', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_user_sites' ]
			);

		if ( gNetwork()->option( 'tos_display', 'user' ) )
			wp_add_dashboard_widget(
				$this->classs( 'tos' ),
				gNetwork()->option( 'tos_title', 'user',
					_x( 'Terms of Service', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN )
				), [ $this, 'widget_tos' ]
			);

		if ( GNETWORK_NETWORK_USERMENU && gNetwork()->option( 'dashboard_menu', 'user' ) )
			add_meta_box( $this->classs( 'usermenu' ),
				_x( 'Your Navigation', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_usermenu' ], NULL, 'normal', 'high' );
	}

	public function wp_network_dashboard_setup()
	{
		remove_meta_box( 'dashboard_primary', NULL, 'side' );

		wp_add_dashboard_widget(
			$this->classs( 'signups' ),
			_x( 'Latest Signups', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
			[ $this, 'widget_signups' ]
		);

		if ( gNetwork()->option( 'login', 'store_lastlogin', TRUE ) )
			wp_add_dashboard_widget(
				$this->classs( 'logins' ),
				_x( 'Latest Logins', 'Modules: Dashboard: Widget Title', GNETWORK_TEXTDOMAIN ),
				[ $this, 'widget_logins' ]
			);
	}

	public function widget_external_feed()
	{
		if ( $this->check_hidden_metabox( 'external-feed' ) )
			return;

		$feeds = [];

		foreach ( $this->filters( 'external_feeds', [] ) as $name => $feed )
			$feeds[$name] = array_merge( [
				'link'         => 'https://geminorum.ir/',
				'url'          => 'https://geminorum.ir/feed',
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

		if ( ! class_exists( __NAMESPACE__.'\\Navigation' ) )
			return;

		else if ( $html = Navigation::getGlobalMenu( GNETWORK_NETWORK_USERMENU, FALSE ) )
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

			$html.= "<p class='-privacy-notice'><a href='options-reading.php'$attr>$content</a></p>";
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

	public function widget_signups()
	{
		if ( $this->check_hidden_metabox( 'signups' ) )
			return;

		echo '<div class="gnetwork-admin-wrap-widget -signups">';

		$query = new \WP_User_Query( [
			'blog_id' => 0,
			'orderby' => 'registered',
			'order'   => 'DESC',
			'number'  => 12,
			'fields'  => [
				'ID',
				'display_name',
				'user_email',
				'user_registered',
				// 'user_status',
				'user_login',
			],
			'count_total' => FALSE,
		] );

		if ( empty( $query->results ) ) {

			echo gNetwork()->na();

		} else {

			echo '<table class="widefat -table-signup"><thead><tr>';
			echo '<th class="-month-day">'._x( 'On', 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ).'</th>';
			echo '<th class="-edit-link">'._x( 'Name', 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ).'</th>';
			echo '<th class="-mail-link">'._x( 'E-mail', 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ).'</th>';
			echo '<th class="-ip-info">'._x( 'IP', 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ).'</th>';
			echo '</tr></thead>';

			$time = current_time( 'timestamp' );
			$last = FALSE;
			$alt  = TRUE;

			$template = '<tr%1$s>'
				.'<td class="-month-day" title="%5$s">%4$s</td>'
				.'<td class="-edit-link"><a title="%8$s" href="%6$s" target="_blank">%2$s</a></td>'
				.'<td class="-mail-link"><a title="%7$s" href="%8$s" target="_blank">%3$s</a></td>'
				.'<td class="-ip-info"><code>%9$s</code></td>'
			.'</tr>';

			foreach ( $query->results as $user ) {

				$registered  = strtotime( get_date_from_gmt( $user->user_registered ) );
				$register_ip = get_user_meta( $user->ID, 'register_ip', TRUE );

				vprintf( $template, [
					( $alt ? ' class="alternate"' : '' ),
					HTML::escape( $user->display_name ),
					HTML::escape( Text::truncateString( $user->user_email, 32 ) ),
					HTML::escape( Utilities::dateFormat( $registered, 'monthday' ) ),
					HTML::escape( sprintf(
						_x( '%1$s ago &mdash; %2$s', 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ),
						human_time_diff( $registered, $time ),
						Utilities::dateFormat( $registered )
					) ),
					get_edit_user_link( $user->ID ),
					'mailto:'.HTML::escape( $user->user_email ),
					$user->user_login,
					( $register_ip ? gnetwork_ip_lookup( $register_ip ) : gNetwork()->na( FALSE ) )
				] );

				$alt = ! $alt;

				if ( ! $last )
					$last = $registered;
			}

			echo '</table>';
			echo '<table class="-table-summary"></tbody>';

				echo '<tr><td>';

					printf( _x( 'Last User Registered %s ago', 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ), human_time_diff( $last, $time ) );

				echo '</td><td>';

					if ( $spam_users = gNetwork()->user->get_spam_count() )
						echo Utilities::getCounted( $spam_users, _nx( 'With %s Spam User', 'With %s Spam Users', $spam_users, 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ) );
					else
						_ex( 'With No Spam User', 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN );

				echo '</td></tr><tr><td>';

					$super_admins = count( get_super_admins() );
					echo Utilities::getCounted( $super_admins, _nx( 'And %s Super Admin', 'And %s Super Admins', $super_admins, 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ) );

				echo '</td><td>';

					$user_count = get_user_count();
					echo Utilities::getCounted( $user_count, _nx( 'Total of One User', 'Total of %s Users', $user_count, 'Modules: Dashboard: Signups', GNETWORK_TEXTDOMAIN ) );

				echo '</td></tr>';

			echo '</tbody></table>';
		}

		echo '</div>';
	}

	public function widget_logins()
	{
		if ( $this->check_hidden_metabox( 'logins' ) )
			return;

		echo '<div class="gnetwork-admin-wrap-widget -logins">';

		$query = new \WP_User_Query( [
			'blog_id'    => 0,
			'meta_key'   => 'lastlogin',
			'orderby'    => 'meta_value',
			'order'      => 'DESC',
			'number'     => 12,
			'meta_query' => [ [
				'key'     => 'lastlogin',
				'compare' => 'EXISTS',
			] ],
			'fields' => [
				'ID',
				'display_name',
				'user_email',
				'user_login',
			],
			'count_total' => FALSE,
		] );

		if ( empty( $query->results ) ) {

			echo gNetwork()->na();

		} else {

			echo '<table class="widefat -table-logins"><thead><tr>';
			echo '<th class="-time-ago">'._x( 'Ago', 'Modules: Dashboard: Logins', GNETWORK_TEXTDOMAIN ).'</th>';
			echo '<th class="-edit-link">'._x( 'Name', 'Modules: Dashboard: Logins', GNETWORK_TEXTDOMAIN ).'</th>';
			echo '<th class="-time-full">'._x( 'Timestamp', 'Modules: Dashboard: Logins', GNETWORK_TEXTDOMAIN ).'</th>';
			echo '</tr></thead>';

			$time = current_time( 'timestamp' );
			$last = FALSE;
			$alt  = TRUE;

			$template = '<tr%1$s>'
				.'<td class="-time-ago">%3$s</td>'
				.'<td class="-edit-link"><a title="%5$s" href="%4$s" target="_blank">%2$s</a></td>'
				.'<td class="-time-full">%6$s</td>'
			.'</tr>';

			foreach ( $query->results as $user ) {

				if ( $meta = get_user_meta( $user->ID, 'lastlogin', TRUE ) )
					$lastlogin = strtotime( get_date_from_gmt( $meta ) );
				else
					continue;

				vprintf( $template, [
					( $alt ? ' class="alternate"' : '' ),
					HTML::escape( $user->display_name ),
					HTML::escape( human_time_diff( $lastlogin, $time ) ),
					get_edit_user_link( $user->ID ),
					$user->user_login,
					HTML::escape( Utilities::dateFormat( $lastlogin, 'timedate' ) ),
				] );

				$alt = ! $alt;

				if ( ! $last )
					$last = $lastlogin;
			}

			echo '</table>';
		}

		echo '</div>';
	}

	public function dashboard_pointers_quota( $items )
	{
		if ( get_network_option( NULL, 'upload_space_check_disabled' )  )
			return $items;

		$quota   = get_space_allowed();
		$used    = get_space_used();
		$percent = number_format( ( $used / $quota ) * 100 );

		$items[] = HTML::tag( 'a', [
			'href'  => admin_url( 'upload.php' ),
			'title' => sprintf( '%s MB/%s MB', Number::format( number_format( round( $used, 2 ), 2 ) ), Number::format( $quota ) ),
			'class' => 'storage'.( $percent >= 70 ? ' warning' : '' ),
		], sprintf( _x( '%s Space Used', 'Modules: Dashboard: Space Quota', GNETWORK_TEXTDOMAIN ), Number::format( $percent.'%' ) ) );

		return $items;
	}

	public function activity_box_end()
	{
		if ( empty( $items = $this->filters( 'pointers', [] ) ) )
			return;

		echo '<ul class="-pointers">';
			echo '<li>'.implode( '</li><li>', $items ).'</li>';
		echo '</ul>';
	}
}

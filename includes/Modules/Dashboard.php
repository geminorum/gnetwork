<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\WordPress;

class Dashboard extends gNetwork\Module
{
	protected $key   = 'dashboard';
	protected $front = FALSE;
	protected $ajax  = TRUE;

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function setup_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			$this->disable_check_browser();
			$this->action( 'wp_dashboard_setup', 0, 20 );

		} else if ( 'dashboard-user' == $screen->base ) {

			$this->disable_check_browser();
			$this->action( 'wp_user_dashboard_setup', 0, 20 );

		} else if ( 'dashboard-network' == $screen->base ) {

			$this->disable_check_browser();
			$this->action( 'wp_network_dashboard_setup', 0, 20 );
		}
	}

	// disable dashboard browse-happy requests
	private function disable_check_browser()
	{
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) )
			$this->filter_empty_array( 'pre_site_transient_browser_'.md5( $_SERVER['HTTP_USER_AGENT'] ) );
	}

	public function wp_dashboard_setup()
	{
		$screen = get_current_screen();

		remove_meta_box( 'dashboard_primary', $screen, 'side' );
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		remove_action( 'activity_box_end', [ 'Akismet_Admin', 'dashboard_stats' ] );
		remove_action( 'rightnow_end', [ 'Akismet_Admin', 'rightnow_stats' ] );

		if ( current_user_can( 'edit_posts' ) ) {

			remove_meta_box( 'dashboard_right_now', $screen, 'normal' );

			add_meta_box( $this->classs( 'right-now' ),
				_x( 'At a Glance', 'Modules: Dashboard: Widget Title', 'gnetwork' ),
				[ $this, 'render_widget_right_now' ], $screen, 'normal', 'high' );
		}

		if ( has_filter( $this->hook( 'external_feeds' ) ) )
			$this->add_dashboard_widget( 'external-feed', _x( 'External Feed', 'Modules: Dashboard: Widget Title', 'gnetwork' ) );

		$this->action( 'activity_box_end', 0, 4 );

		if ( current_user_can( 'update_core' ) )
			$this->filter_module( 'dashboard', 'pointers', 1, 3, 'update' );

		if ( current_user_can( 'manage_options' ) )
			$this->filter_module( 'dashboard', 'pointers', 1, 4, 'public' );

		if ( is_multisite() ) {

			if ( current_user_can( 'upload_files' ) ) {
				remove_action( 'activity_box_end', 'wp_dashboard_quota' );
				$this->filter_module( 'dashboard', 'pointers', 1, 5, 'quota' );
			}

		} else {

			if ( gNetwork()->option( 'login', 'store_lastlogin', TRUE ) && current_user_can( 'list_users' ) )
				$this->add_dashboard_widget( 'logins', _x( 'Latest Logins', 'Modules: Dashboard: Widget Title', 'gnetwork' ) );
		}
	}

	public function wp_user_dashboard_setup()
	{
		remove_meta_box( 'dashboard_primary', NULL, 'side' );

		if ( gNetwork()->option( 'dashboard_sites', 'user' ) )
			$this->add_dashboard_widget( 'user-sites', _x( 'Your Sites', 'Modules: Dashboard: Widget Title', 'gnetwork' ) );

		if ( gNetwork()->option( 'tos_display', 'legal' ) )
			$this->add_dashboard_widget( 'tos', gNetwork()->option( 'tos_title', 'legal',
				_x( 'Terms of Service', 'Modules: Dashboard: Widget Title', 'gnetwork' )
			) );

		if ( GNETWORK_NETWORK_USERMENU && gNetwork()->option( 'dashboard_menu', 'user' ) )
			add_meta_box( $this->classs( 'usermenu' ),
				_x( 'Your Navigation', 'Modules: Dashboard: Widget Title', 'gnetwork' ),
				[ $this, 'render_widget_usermenu' ], NULL, 'normal', 'high' );
	}

	public function wp_network_dashboard_setup()
	{
		remove_meta_box( 'dashboard_primary', NULL, 'side' );

		$this->add_dashboard_widget( 'signups', _x( 'Latest Signups', 'Modules: Dashboard: Widget Title', 'gnetwork' ) );

		if ( gNetwork()->option( 'login', 'store_lastlogin', TRUE ) )
			$this->add_dashboard_widget( 'logins', _x( 'Latest Logins', 'Modules: Dashboard: Widget Title', 'gnetwork' ) );
	}

	public function render_widget_external_feed()
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

	public function do_ajax()
	{
		require_once ABSPATH.'wp-admin/includes/dashboard.php';

		switch ( $_GET['widget'] ) {

			case 'gnetwork_dashboard_external_feed':

				$this->widget_external_feed();

			break;
		}

		wp_die();
	}

	public function render_widget_tos()
	{
		echo '<div class="gnetwork-admin-wrap-widget -user-tos">';
			echo wpautop( gNetwork()->option( 'tos_text', 'user', gNetwork()->na() ) );
		echo '</div>';
	}

	// FIXME: needs better styling
	public function render_widget_usermenu()
	{
		if ( $this->check_hidden_metabox( 'usermenu' ) )
			return;

		if ( ! class_exists( __NAMESPACE__.'\\Navigation' ) )
			return;

		else if ( $html = Navigation::getGlobalMenu( GNETWORK_NETWORK_USERMENU, FALSE ) )
			echo '<div class="gnetwork-admin-wrap-widget -usermenu">'.$html.'</div>';

		else
			Core\HTML::desc( _x( '&#8220;Not all those who wander are lost!&#8221;', 'Modules: Dashboard: User Menu', 'gnetwork' ), FALSE, '-empty' );
	}

	public function render_widget_user_sites()
	{
		if ( $this->check_hidden_metabox( 'user-sites' ) )
			return;

		$blogs = get_blogs_of_user( get_current_user_id() );

		echo '<div class="gnetwork-admin-wrap-widget -user-sites">';

			if ( empty( $blogs ) )
				Core\HTML::desc( gNetwork()->na() );
			else
				echo Site::tableUserSites( $blogs, FALSE );

		echo '</div>';
	}

	public function render_widget_right_now()
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

				$text   = sprintf( $text, Core\Number::format( $num_posts->publish ) );
				$object = get_post_type_object( $post_type );

				if ( $object && current_user_can( $object->cap->edit_posts ) )
					$html.= sprintf( '<li class="%1$s-count"><a href="edit.php?post_type=%1$s">%2$s</a></li>', $post_type, $text );

				else
					$html.= sprintf( '<li class="%1$s-count"><span>%2$s</span></li>', $post_type, $text );
			}
		}

		// NOTE: filters the array of extra elements to list in the 'At a Glance' dashboard widget
		if ( $elements = apply_filters( 'dashboard_glance_items', [] ) )
			$html.= Core\HTML::renderList( $elements, FALSE, FALSE );

		if ( $num_comm = wp_count_comments() ) {

			if ( $num_comm->approved || $num_comm->moderated ) {

				/* translators: %s: comments count */
				$text = sprintf( _nx( '%s Comment', '%s Comments', $num_comm->approved, 'Modules: Dashboard: Right Now', 'gnetwork' ), Core\Number::format( $num_comm->approved ) );

				$html.= '<li class="comment-count"><a href="edit-comments.php">'.$text.'</a></li>';

				$moderated_comments_count_i18n = Core\Number::format( $num_comm->moderated );

				/* translators: %s: awaiting comments count */
				$text = sprintf( _nx( '%s Awaiting Comment', '%s Awaiting Comments', $num_comm->moderated, 'Modules: Dashboard: Right Now', 'gnetwork' ), $moderated_comments_count_i18n );

				/* translators: %s: number of comments in moderation */
				$aria_label = sprintf( _nx( '%s comment in moderation', '%s comments in moderation', $num_comm->moderated, 'comments' ), $moderated_comments_count_i18n );

				$html.= '<li class="comment-mod-count'.( $num_comm->moderated ? '' : ' hidden' ).'">';
				$html.= '<a href="edit-comments.php?comment_status=moderated" aria-label="'.esc_attr__( $aria_label ).'">'.$text.'</a></li>';
			}

			if ( $num_comm->spam > 0 ) {
				/* translators: %s: spam comments count */
				$spam = sprintf( _nx( '%s Spam Comment', '%s Spam Comments', $num_comm->spam, 'Modules: Dashboard: Right Now', 'gnetwork' ), Core\Number::format( $num_comm->spam ) );
				$html.= '<li class="comment-spam-count"><a href="edit-comments.php?comment_status=spam">'.$spam.'</a></li>';
			}
		}

		if ( $html )
			echo '<div class="main"><ul>'.$html.'</ul></div>';
		else
		Core\HTML::desc( _x( 'There are no contents available!', 'Modules: Dashboard: Right Now', 'gnetwork' ), FALSE, '-empty' );

		ob_start();
		// do_action( 'rightnow_end' ); // old hook
		do_action( 'activity_box_end' );
		$actions = ob_get_clean();

		if ( ! empty( $actions ) )
			echo '<div class="sub">'.$actions.'</div>';

		echo '</div>';
	}

	public function render_widget_signups()
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
			echo '<th class="-month-day">'._x( 'On', 'Modules: Dashboard: Signups', 'gnetwork' ).'</th>';
			echo '<th class="-name-email">'._x( 'Name/E-mail', 'Modules: Dashboard: Signups', 'gnetwork' ).'</th>';
			echo '<th class="-ip-info">'._x( 'IP', 'Modules: Dashboard: Signups', 'gnetwork' ).'</th>';
			echo '</tr></thead>';

			$time = current_time( 'timestamp' );
			$last = FALSE;
			$alt  = TRUE;


			$template = '<tr%1$s>'
				.'<td class="-month-day" title="%5$s">%4$s</td>'

				// FIXME: template: `Name (email@example.com)`
				.'<td class="-name-email"><div class="-wrap"><a class="-edit-link" title="%8$s" href="%6$s" target="_blank">%2$s</a>'
				.'<a class="-mail-link" title="%7$s" href="%8$s" target="_blank">%3$s</a></div></td>'

				// TODO: display mobile number
				.'<td class="-ip-info"><code>%9$s</code></td>'
			.'</tr>';

			foreach ( $query->results as $user ) {

				$registered  = strtotime( get_date_from_gmt( $user->user_registered ) );
				$register_ip = get_user_meta( $user->ID, 'register_ip', TRUE );

				vprintf( $template, [
					( $alt ? ' class="alternate"' : '' ),
					Core\HTML::escape( $user->display_name ),
					Core\HTML::escape( Core\Text::truncateString( $user->user_email, 32 ) ),
					Core\HTML::escape( Utilities::dateFormat( $registered, 'monthday' ) ),
					sprintf(
						/* translators: %1$s: human time diff, %2$s: registred date */
						_x( '%1$s ago &mdash; %2$s', 'Modules: Dashboard: Signups', 'gnetwork' ),
						human_time_diff( $registered, $time ),
						Utilities::dateFormat( $registered )
					),
					get_edit_user_link( $user->ID ),
					'mailto:'.Core\HTML::escape( $user->user_email ),
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

					/* translators: %s: human time diff */
					printf( _x( 'Last User Registered %s ago', 'Modules: Dashboard: Signups', 'gnetwork' ), human_time_diff( $last, $time ) );

				echo '</td><td>';

					if ( $spam_users = gNetwork()->user->get_spam_count() )
						/* translators: %s: spam users count */
						echo Utilities::getCounted( $spam_users, _nx( 'With %s Spam User', 'With %s Spam Users', $spam_users, 'Modules: Dashboard: Signups', 'gnetwork' ) );
					else
						_ex( 'With No Spam User', 'Modules: Dashboard: Signups', 'gnetwork' );

				echo '</td></tr><tr><td>';

					$super_admins = count( get_super_admins() );
					/* translators: %s: admin users count */
					echo Utilities::getCounted( $super_admins, _nx( 'And %s Super Admin', 'And %s Super Admins', $super_admins, 'Modules: Dashboard: Signups', 'gnetwork' ) );

				echo '</td><td>';

					$user_count = get_user_count();
					/* translators: %s: total user count */
					echo Utilities::getCounted( $user_count, _nx( 'Total of %s User', 'Total of %s Users', $user_count, 'Modules: Dashboard: Signups', 'gnetwork' ) );

				echo '</td></tr>';

			echo '</tbody></table>';
		}

		echo '</div>';
	}

	public function render_widget_logins()
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
			echo '<th class="-time-ago">'._x( 'Ago', 'Modules: Dashboard: Logins', 'gnetwork' ).'</th>';
			echo '<th class="-edit-link">'._x( 'Name', 'Modules: Dashboard: Logins', 'gnetwork' ).'</th>';
			echo '<th class="-time-full">'._x( 'Timestamp', 'Modules: Dashboard: Logins', 'gnetwork' ).'</th>';
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
					Core\HTML::escape( $user->display_name ),
					Core\HTML::escape( human_time_diff( $lastlogin, $time ) ),
					get_edit_user_link( $user->ID ),
					$user->user_login,
					Core\HTML::escape( Utilities::dateFormat( $lastlogin, 'timedate' ) ),
				] );

				$alt = ! $alt;

				if ( ! $last )
					$last = $lastlogin;
			}

			echo '</table>';
		}

		echo '</div>';
	}

	// @REF: `update_right_now_message()`
	public function dashboard_pointers_update( $items )
	{
		$preferred = get_preferred_from_update_core();

		if ( ! isset( $preferred->response ) || 'upgrade' != $preferred->response )
			return $items;

		$items[] = Core\HTML::tag( 'a', [
			'href'  => network_admin_url( 'update-core.php' ),
			'title' => sprintf( __( 'Update to %s' ), $preferred->current ? $preferred->current : __( 'Latest' ) ),
			'class' => '-update',
		], _x( 'Update WordPress', 'Modules: Dashboard: Update Core', 'gnetwork' ) );

		return $items;
	}

	// checks if search engines are asked not to index this site
	public function dashboard_pointers_public( $items )
	{
		if ( '0' != get_option( 'blog_public' ) )
			return $items;

		$title   = apply_filters( 'privacy_on_link_title', '' );
		$content = apply_filters( 'privacy_on_link_text', _x( 'Search Engines Discouraged', 'Modules: Dashboard: Blog Public', 'gnetwork' ) );

		if ( $content )
			$items[] = Core\HTML::tag( 'a', [
				'href'  => admin_url( 'options-reading.php' ),
				'title' => $title ?: FALSE,
				'class' => '-privacy',
			], $content );

		return $items;
	}

	public function dashboard_pointers_quota( $items )
	{
		if ( get_network_option( NULL, 'upload_space_check_disabled' )  )
			return $items;

		$quota   = get_space_allowed();
		$used    = get_space_used();
		$percent = number_format( ( $used / $quota ) * 100 );
		$classes = [ '-storage' ];

		if ( $percent >= 100 )
			$classes[] = 'danger';

		else if ( $percent >= 70 )
			$classes[] = 'warning';

		$items[] = Core\HTML::tag( 'a', [
			'href'  => admin_url( 'upload.php' ),
			'title' => sprintf( Core\HTML::wrapLTR( '%s MB/%s MB' ), Core\Number::format( round( $used, 2 ), 2 ), Core\Number::format( $quota ) ),
			'class' => $classes,
		/* translators: %s: space used percent */
		], sprintf( _x( '%s Space Used', 'Modules: Dashboard: Space Quota', 'gnetwork' ), Core\Number::localize( $percent.'%' ) ) );

		return $items;
	}

	public function activity_box_end()
	{
		if ( empty( $items = $this->filters( 'pointers', [] ) ) )
			return;

		echo '<ul class="-pointers">';
			echo Core\HTML::renderList( $items, FALSE, FALSE );
		echo '</ul>';
	}
}

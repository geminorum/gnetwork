<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;

class Comments extends gNetwork\Module
{
	protected $key     = 'comments';
	protected $network = FALSE;
	protected $ajax    = TRUE;
	protected $cron    = TRUE;

	private $textareas = [];

	protected function setup_actions()
	{
		if ( $this->options['disable_notifications'] ) {

			// filter the list of email addresses to receive a comment notification
			add_filter( 'comment_notification_recipients', '__return_empty_array', 12 );

			// filters whether to notify comment authors of their comments on their own posts
			add_filter( 'comment_notification_notify_author', '__return_false', 12 );

			// filter whether to send the site moderator email notifications, overriding the site setting
			add_filter( 'notify_moderator', '__return_false', 12 );

			// whether to send the post author new comment notification emails, overriding the site setting
			add_filter( 'notify_post_author', '__return_false', 12 );
		}

		if ( is_blog_admin() ) {

			if ( $this->options['admin_fullcomments'] )
				$this->filter( 'comment_excerpt' );
		}

		if ( ! is_admin() ) {

			if ( $this->options['blacklist_check'] )
				$this->action( 'wp_blacklist_check', 6 );

			if ( $this->options['front_quicktags'] )
				$this->action( 'wp_print_scripts' );

			if ( $this->options['front_autosize'] )
				$this->action( 'template_redirect' );

			if ( $this->options['disable_notes'] )
				$this->filter_set( 'comment_form_defaults', [ 'comment_notes_after' => '' ], 12 );

			if ( $this->options['front_nonce'] ) {
				$this->action( 'comment_form', 1, 10, 'nonce' );
				$this->action( 'pre_comment_approved', 1, 10, 'nonce' );
				add_action( 'explain_nonce_gnc-check_comments', [ $this, 'explain_nonce' ] );
			}

			if ( $this->options['strip_pings'] ) {
				$this->filter( 'comments_template_query_args' );
				$this->filter( 'get_comments_number', 2 );
				$this->filter( 'the_posts' );
			}
		}

		$this->filter( 'pre_comment_approved', 2, 99 );
		$this->filter( 'add_comment_metadata', 5, 20 );

		// register_shutdown_function( [ $this, 'delete_spam_comments' ] );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Comments', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'disable_notifications' => '1',
			'admin_fullcomments'    => '1',
			'front_quicktags'       => '0',
			'front_autosize'        => '0',
			'disable_notes'         => '1',
			'strip_pings'           => '1',
			'blacklist_check'       => '0', // FIXME: DRAFT: needs test / NO settings UI YET
			'front_nonce'           => '0', // FIXME: DRAFT: working / NO settings UI YET / check the hooks
			'captcha'               => '0',
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'strip_pings',
					'title'       => _x( 'Hide Pings', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Removes trackbacks and pingbacks form comment lists on the frontend.', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'disable_notifications',
					'type'        => 'disabled',
					'title'       => _x( 'Comment Notifications', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Prevents WordPress from sending any comment related notifications.', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
				[
					'field'       => 'admin_fullcomments',
					'title'       => _x( 'Full Comments', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays full comment content on admin dashboard widget.', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'front_quicktags',
					'title'       => _x( 'Frontend Quicktags', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Adds quick-tags on comment textarea on the frontend.', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'front_autosize',
					'title'       => _x( 'Frontend Autosize', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Makes the comment textarea expand in height automatically on the frontend.', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'disable_notes',
					'title'       => _x( 'Form Notes', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Removes extra notes after comment form on the frontend.', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				],
			],
		];

		if ( class_exists( __NAMESPACE__.'\\Captcha' ) )
			$settings['_captcha'] = [
				[
					'field'       => 'captcha',
					'title'       => _x( 'Captcha', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Displays captcha field after comment form on the frontend.', 'Modules: Comments: Settings', GNETWORK_TEXTDOMAIN ),
				],
			];

		return $settings;
	}

	public function settings_sidebox( $sub, $uri )
	{
		self::commentSummary();
	}

	public function wp_print_scripts()
	{
		$default_buttons = $this->filters( 'quicktags_buttons', [
			'link',
			'em',
			'strong',
		] );

		if ( is_singular() && comments_open() ) {
			$this->textareas['comment']  = $default_buttons;
			$this->textareas['posttext'] = $default_buttons;
		}

		if ( function_exists( 'is_bbpress' ) ) {
			if ( is_bbpress() && get_option( '_bbp_use_wp_editor' ) ) {
				$this->textareas['bbp_reply_content'] = $default_buttons;
				$this->textareas['bbp_topic_content'] = $default_buttons;
			}
		}

		$textareas = $this->filters( 'quicktags_textarea', $this->textareas, $default_buttons );

		if ( count( $textareas ) ) {

			foreach ( $textareas as $textarea => $buttons )
				$this->scripts[] = 'quicktags({id:"'.$textarea.'",buttons:"'.implode( ',', $buttons ).'"});';
				// $this->scripts[] = 'window.quicktags({id:"'.$textarea.'",buttons:"'.implode( ',', $buttons ).'"});';

			$this->scripts[] = 'QTags.addButton("quote","quote","<blockquote>","</blockquote>","quote");';
			// $this->scripts[] = 'window.edButton("quote","quote","<blockquote>","</blockquote>","quote");';

			add_action( 'wp_footer', [ $this, 'print_scripts' ], 99 );
			wp_enqueue_script( 'quicktags' );
		}
	}

	public function template_redirect()
	{
		if ( is_singular() && 'open' == $GLOBALS['wp_query']->post->comment_status )
			Utilities::pkgAutosize();
	}

	public function comment_excerpt( $excerpt )
	{
		return wpautop( trim( $GLOBALS['comment']->comment_content ) );
	}

	// http://css-tricks.com/snippets/wordpress/spam-comments-with-very-long-urls/
	public function pre_comment_approved( $approved , $commentdata )
	{
		return ( strlen( $commentdata['comment_author_url'] ) > 50 ) ? 'spam' : $approved;
	}

	// @SOURCE: http://rayofsolaris.net/blog/2012/akismet-bloat
	public function add_comment_metadata( $check, $object_id, $meta_key, $meta_value, $unique )
	{
		$to_filter = [
			'akismet_result',
			'akismet_history',
			'akismet_user',
			'akismet_user_result',
			// 'akismet_error',
			// 'akismet_as_submitted',
			// 'akismet_pro_tip',
		];

		if ( in_array( $meta_key, $to_filter ) )
			return FALSE;

		return $check;
	}

	// https://gist.github.com/boonebgorges/4714650
	// Delete spam comments from every site on a WordPress network, slowly but surely
	public function delete_spam_comments()
	{
		$in_progress = (bool) get_network_option( NULL, 'gnc_delete_in_progress' );

		if ( ! $in_progress ) {
			global $wpdb;

			update_network_option( NULL, 'gnc_delete_in_progress', '1' );

			// 4980
			$next = (int) get_network_option( NULL, 'gnc_delete_next_blog' );

			if ( empty( $next ) )
				$next = 1;

			if ( $next > 4980 )
				return;

			switch_to_blog( $next );

			$spams = $wpdb->get_col( "SELECT comment_id FROM {$wpdb->comments} WHERE comment_approved = 'spam' LIMIT 10" );

			if ( empty( $spams ) ) {
				$next++;
				update_network_option( NULL, 'gnc_delete_next_blog', $next );
			} else {
				foreach ( $spams as $spam ) {
					wp_delete_comment( $spam, TRUE );
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id = %d", $spam ) );
				}
			}

			// reclaim disk space
			$wpdb->query( "OPTIMIZE TABLE {$wpdb->comments}" );
			$wpdb->query( "OPTIMIZE TABLE {$wpdb->commentmeta}" );

			restore_current_blog();
			delete_network_option( NULL, 'gnc_delete_in_progress' );
		}
	}

	public function comment_form_nonce()
	{
		wp_nonce_field( 'gnc-check_comments', '_gnc_nonce', FALSE );
	}

	public function pre_comment_approved_nonce( $content )
	{
		check_admin_referer( 'gnc-check_comments', wp_verify_nonce( $_POST['_gnc_nonce'] ) );
	}

	public function explain_nonce()
	{
		return _x( 'Your attempt to add this comment has failed.', 'Modules: Comments', GNETWORK_TEXTDOMAIN );
	}

	public function comments_template_query_args( $comment_args )
	{
		if ( ! isset( $comment_args['type'] )
			&& ! isset( $comment_args['type__in'] )
			&& ! isset( $comment_args['type__not_in'] ) )
				$comment_args['type'] = 'comment';

		return $comment_args;
	}

	protected function get_count( $post_id )
	{
		$query = new \WP_Comment_Query;

		return $query->query( [
			'post_id' => $post_id,
			'status'  => 1,
			'count'   => TRUE,
			'type'    => 'comment',

			'update_comment_meta_cache' => FALSE,
			'update_comment_post_cache' => FALSE,
		] );
	}

	public function get_comments_number( $count, $post_id )
	{
		return $count ? $this->get_count( $post_id ) : $count;
	}

	public function the_posts( $posts )
	{
		foreach ( $posts as $id => $post )
			if ( $post->comment_count )
				$posts[$id]->comment_count = $this->get_count( $post->ID );

		return $posts;
	}

	// DRAFT
	public function close_ping()
	{
		// http://www.wpbeginner.com/wp-tutorials/how-to-disable-trackbacks-and-pings-on-existing-wordpress-posts/
		// UPDATE wp_posts SET ping_status='closed' WHERE post_status = 'publish' AND post_type = 'post';
		// UPDATE wp_posts SET ping_status='closed' WHERE post_status = 'publish' AND post_type = 'page';
	}

	// TODO: pie-chart
	public static function commentSummary( $post_id = 0 )
	{
		$comments = wp_count_comments( $post_id );

		$map = [
			'moderated'      => _x( 'Comments in moderation: %s', 'Modules: Comments: Total Comments Item', GNETWORK_TEXTDOMAIN ),
			'approved'       => _x( 'Comments approved: %s', 'Modules: Comments: Total Comments Item', GNETWORK_TEXTDOMAIN ),
			'spam'           => _x( 'Comments in Spam: %s', 'Modules: Comments: Total Comments Item', GNETWORK_TEXTDOMAIN ),
			'trash'          => _x( 'Comments in Trash: %s', 'Modules: Comments: Total Comments Item', GNETWORK_TEXTDOMAIN ),
			'total_comments' => _x( 'Total Comments: %s', 'Modules: Comments: Total Comments Item', GNETWORK_TEXTDOMAIN ),
		];

		echo '<ul>';
		foreach ( $map as $key => $string )
			echo '<li>'.sprintf( $string, Number::format( $comments->{$key} ) ).'</li>';
		echo '</ul>';
	}

	// TODO: add option for:
	private $links_limit        = 5;
	private $links_limit_action = 'reject';
	private $duplicate_action   = 'reject';
	private $known_sites_action = 'spam';
	private $group_action       = 'reject';
	private $known_ip_action    = 'spam';
	private $known_ip_limit     = 3;
	private $known_sites        = [];

	// @SOURCE: https://github.com/Rarst/deny-spam
	public function wp_blacklist_check( $author, $email, $url, $comment, $user_ip, $user_agent )
	{
		global $wpdb;

		// links limit
		if ( substr_count( strtolower( $comment ), 'http://' ) > $this->links_limit ) {

			if ( 'reject' == $this->links_limit_action )
				wp_die( sprintf( _x( 'Comment has <strong>over %s links</strong>. Please reduce number of those.', 'Modules: Comments', GNETWORK_TEXTDOMAIN ), $this->links_limit ) );

			else
				$this->filter( 'pre_comment_approved', 0, 10, 'spam' );

			return;
		}

		// duplicate comment content
		$dupe = "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved='spam' AND comment_content = '{$comment}' LIMIT 1";
		if ( $wpdb->get_var( $dupe ) ) {

			if ( 'reject' == $this->duplicate_action )
				wp_die( _x( 'Duplicate comment content. Please rephrase.', 'Modules: Comments', GNETWORK_TEXTDOMAIN ) );

			else
				$this->filter( 'pre_comment_approved', 0, 10, 'spam' );

			return;
		}

		// known spam URL
		if ( ! empty( $url ) ) {
			$dupe = "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved='spam' AND comment_author_url = '{$url}' LIMIT 1";
			if ( $wpdb->get_var( $dupe ) || $this->is_known_spam_domain( $url ) ) {

				if ( 'reject' == $this->known_sites_action )
					wp_die( _x( 'Your URL or domain is in list of known spam-promoted sites. If you believe this to be an error please contact site admin.', 'Modules: Comments', GNETWORK_TEXTDOMAIN ) );

				else
					$this->filter( 'pre_comment_approved', 0, 10, 'spam' );

				return;
			}
		}

		// known spam IP
		$dupe = "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved='spam' AND comment_author_IP = '$user_ip'";
		if ( $wpdb->get_var( $dupe ) > $this->known_ip_limit ) {

			if ( 'reject' == $this->known_ip_action )
				wp_die( _x( 'Your IP is in list of known spam sources. If you believe this to be an error please contact site admin.', 'Modules: Comments', GNETWORK_TEXTDOMAIN ) );

			else
				$this->filter( 'pre_comment_approved', 0, 10, 'spam' );

			return;
		}

		// group of spam duplicates
		$dupe = "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved='0' AND comment_content = '$comment' LIMIT 1";
		if ( $wpdb->get_var( $dupe ) ) {

			if ( 'reject' == $this->group_action ) {
				$wpdb->query( "UPDATE $wpdb->comments SET comment_approved='trash' WHERE comment_content = '$comment'" );
				wp_die( _x( 'Duplicate comment content. Please rephrase.', 'Modules: Comments', GNETWORK_TEXTDOMAIN ) );

			} else {

				$wpdb->query( "UPDATE $wpdb->comments SET comment_approved='spam' WHERE comment_content = '$comment'" );
				$this->filter( 'pre_comment_approved', 0, 10, 'spam' );
			}

			return;
		}
	}

	// overrides approved status with 'spam'
	public function pre_comment_approved_spam()
	{
		return 'spam';
	}

	// checks url against top spam domains
	private function is_known_spam_domain( $url )
	{
		$host = @parse_url( $url, PHP_URL_HOST );

		if ( empty( $host ) )
			return FALSE;

		return in_array( strtolower( $host ), $this->known_sites );
	}
}

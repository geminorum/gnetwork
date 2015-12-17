<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkComments extends gNetworkModuleCore
{

	protected $option_key = 'comments';
	protected $network    = FALSE;

	private $textareas = array();

	protected function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'comments',
			_x( 'Comments', 'Comments Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);

		if ( $this->options['disable_notifications'] ) {

			// filter the list of email addresses to receive a comment notification.
			add_filter( 'comment_notification_recipients', '__return_empty_array' );

			// notifies the moderator of the blog about a new comment that is awaiting approval.
			add_filter( 'pre_option_moderation_notify', '__return_zero' ); // FIXME: DROP THIS: AFTER WP 4.4

			// filter whether to send the site moderator email notifications, overriding the site setting.
			add_filter( 'notify_moderator', '__return_false' ); // since WP 4.4
		}

		if ( ! is_admin() && $this->options['front_quicktags'] )
			add_action( 'wp_print_scripts', array( $this, 'wp_print_scripts' ) );

		if ( ! is_admin() && $this->options['disable_notes'] )
			add_filter( 'comment_form_defaults', function( $defaults ){
				$defaults['comment_notes_after'] = '';
				return $defaults;
			}, 12 );

		if ( is_admin() && $this->options['admin_fullcomments'] )
			add_filter( 'comment_excerpt', array( $this, 'comment_excerpt' ) );

		add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved' ), 99, 2 );
		add_filter( 'add_comment_metadata', array( $this, 'add_comment_metadata' ), 20, 3 );

		// register_shutdown_function( array( $this, 'delete_spam_comments' ) );

		// // WORKING BUT MAKE SURE THIS IS NESSECARY?!
		// // ORIGINALLY FROM : http://wordpress.org/plugins/really-simple-comment-validation/
		// add_action( 'comment_form', array( $this, 'comment_form_nonce' ) );
		// add_action( 'pre_comment_approved', array( $this, 'pre_comment_approved_nounce' ) );
		// add_action( 'explain_nonce_gnc-check_comments', array( $this, 'explain_nonce' ) );
	}

	public function settings_sidebox( $sub, $uri )
	{
		$this->total_comments();
	}

	public function default_options()
	{
		return array(
			'disable_notifications' => '1',
			'admin_fullcomments'    => '1',
			'front_quicktags'       => '0',
			'disable_notes'         => '1',
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'   => 'disable_notifications',
					'type'    => 'enabled',
					'title'   => _x( 'Comment Notifications', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Disable all core comment notifications', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'default' => '1',
					'values'  => array(
						__( 'Enabled' , GNETWORK_TEXTDOMAIN ),
						__( 'Disabled', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'   => 'admin_fullcomments',
					'type'    => 'enabled',
					'title'   => _x( 'Full Comments', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Full comments on dashboard', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'front_quicktags',
					'type'    => 'enabled',
					'title'   => _x( 'Quicktags', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Activate Quicktags for comments on frontend', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
				array(
					'field'   => 'disable_notes',
					'type'    => 'enabled',
					'title'   => _x( 'Form Notes', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'desc'    => _x( 'Removes extra notes after comment form on frontend', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'default' => '1',
				),
			),
		);
	}

	public function wp_print_scripts()
	{
		$default_buttons = apply_filters( 'gnetwork_comments_quicktags_buttons', array(
			'link',
			'em',
			'strong',
		) );

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

		$textareas = apply_filters( 'gnetwork_comments_quicktags_textarea', $this->textareas, $default_buttons );

		if ( count( $textareas ) ) {

			foreach ( $textareas as $textarea => $buttons )
				$this->scripts[] = 'quicktags({id:"'.$textarea.'",buttons:"'.implode( ',', $buttons ).'"});';

			$this->scripts[] = 'QTags.addButton("quote","quote","<blockquote>","</blockquote>","quote");';

			add_action( 'wp_footer', array( $this, 'print_scripts' ), 99 );
			wp_enqueue_script( 'quicktags' );
		}
	}

	public function comment_excerpt( $excerpt )
	{
		global $comment;

		$content = wpautop( $comment->comment_content );

		// FIXME: disabled for testing
		// $content = substr( $content, 3, -5 );	// Remove first <p> and last </p>
		// $content = str_replace( '<p>', '<p style="display:block; margin:1em 0">', $content );
		// $content .= '</p>';

		return $content;
	}

	// http://css-tricks.com/snippets/wordpress/spam-comments-with-very-long-urls/
	public function pre_comment_approved( $approved , $commentdata )
	{
		return ( strlen( $commentdata['comment_author_url'] ) > 50 ) ? 'spam' : $approved;
	}

	// @SOURCE: http://rayofsolaris.net/blog/2012/akismet-bloat
	public function add_comment_metadata( $check, $object_id, $meta_key )
	{
		$to_filter = array(
			'akismet_result',
			'akismet_history',
			'akismet_user',
			'akismet_user_result',
			// 'akismet_error',
			// 'akismet_as_submitted',
			// 'akismet_pro_tip',
		);

		if ( in_array( $meta_key, $to_filter ) )
			return FALSE;

		return $check;
	}

	// https://gist.github.com/boonebgorges/4714650
	// Delete spam comments from every site on a WordPress network, slowly but surely
	public function delete_spam_comments()
	{
		$in_progress = (bool) get_site_option( 'gnc_delete_in_progress' );

		if ( ! $in_progress ) {
			global $wpdb;

			update_site_option( 'gnc_delete_in_progress', '1' );

			// 4980
			$next = (int) get_site_option( 'gnc_delete_next_blog' );

			if ( empty( $next ) )
				$next = 1;

			if ( $next > 4980 )
				return;

			switch_to_blog( $next );

			$spams = $wpdb->get_col( "SELECT comment_id FROM {$wpdb->comments} WHERE comment_approved = 'spam' LIMIT 10" );

			if ( empty( $spams ) ) {
				$next++;
				update_site_option( 'gnc_delete_next_blog', $next );
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
			delete_site_option( 'gnc_delete_in_progress' );
		}
	}

	public function comment_form_nonce()
	{
		wp_nonce_field( 'gnc-check_comments', '_gnc_nonce', FALSE );
	}

	public function pre_comment_approved_nounce( $content )
	{
		check_admin_referer( 'gnc-check_comments', wp_verify_nonce( $_POST['_gnc_nonce'] ) );
	}

	public function explain_nonce()
	{
		return _x( 'Your attempt to add this comment has failed.', 'Comments Module', GNETWORK_TEXTDOMAIN );
	}

	// DRAFT
	public function close_ping()
	{
		// http://www.wpbeginner.com/wp-tutorials/how-to-disable-trackbacks-and-pings-on-existing-wordpress-posts/
		// UPDATE wp_posts SET ping_status='closed' WHERE post_status = 'publish' AND post_type = 'post';
		// UPDATE wp_posts SET ping_status='closed' WHERE post_status = 'publish' AND post_type = 'page';
	}

	// http://www.codecheese.com/2013/11/wordpress-get-total-comment-count/
	public function total_comments( $post_id = 0 )
	{
		$comments = wp_count_comments( $post_id );

		$map = array(
			'moderated'      => _x( 'Comments in moderation: %s', 'Comments Module: Total Comments Item', GNETWORK_TEXTDOMAIN ),
			'approved'       => _x( 'Comments approved: %s', 'Comments Module: Total Comments Item', GNETWORK_TEXTDOMAIN ),
			'spam'           => _x( 'Comments in Spam: %s', 'Comments Module: Total Comments Item', GNETWORK_TEXTDOMAIN ),
			'trash'          => _x( 'Comments in Trash: %s', 'Comments Module: Total Comments Item', GNETWORK_TEXTDOMAIN ),
			'total_comments' => _x( 'Total Comments: %s', 'Comments Module: Total Comments Item', GNETWORK_TEXTDOMAIN ),
		);

		echo '<ul>';
		foreach ( $map as $key => $string )
			echo '<li>'.sprintf( $string, number_format_i18n( $comments->{$key} ) ).'</li>';
		echo '</ul>';
	}
}

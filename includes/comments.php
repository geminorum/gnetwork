<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Comments extends ModuleCore
{
	protected $key     = 'comments';
	protected $network = FALSE;

	private $textareas = array();

	private $type_archived = 'archived';

	protected function setup_actions()
	{
		if ( $this->options['disable_notifications'] ) {

			// filter the list of email addresses to receive a comment notification.
			add_filter( 'comment_notification_recipients', '__return_empty_array' );

			// filter whether to send the site moderator email notifications, overriding the site setting.
			add_filter( 'notify_moderator', '__return_false' );
		}

		if ( is_admin() ) {

			if ( $this->options['admin_fullcomments'] )
				add_filter( 'comment_excerpt', array( $this, 'comment_excerpt' ) );

			if ( $this->options['archived_comments'] ) {
				add_filter( 'comment_status_links', array( $this, 'comment_status_links' ), 1, 2 );
				add_filter( 'admin_comment_types_dropdown', array( $this, 'admin_comment_types_dropdown' ) );
				add_filter( 'comment_row_actions', array( $this, 'comment_row_actions' ), 15, 2 );

				// FIXME: UNFINISHED: add the actual actions!!
			}

		} else {

			if ( $this->options['blacklist_check'] )
				add_action( 'wp_blacklist_check', array( $this, 'wp_blacklist_check' ), 10, 6 );

			if ( $this->options['front_quicktags'] )
				add_action( 'wp_print_scripts', array( $this, 'wp_print_scripts' ) );

			if ( $this->options['front_autogrow'] )
				add_action( 'template_redirect', array( $this, 'template_redirect' ) );

			if ( $this->options['disable_notes'] )
				add_filter( 'comment_form_defaults', function( $defaults ){
					$defaults['comment_notes_after'] = '';
					return $defaults;
				}, 12 );

			if ( $this->options['front_nonce'] ) {
				add_action( 'comment_form', array( $this, 'comment_form_nonce' ) );
				add_action( 'pre_comment_approved', array( $this, 'pre_comment_approved_nounce' ) );
				add_action( 'explain_nonce_gnc-check_comments', array( $this, 'explain_nonce' ) );
			}
		}

		if ( $this->options['archived_comments'] ) {
			add_action( 'pre_get_comments', array( $this, 'pre_get_comments' ) );
			add_filter( 'wp_count_comments', array( $this, 'wp_count_comments' ), 1, 2 );
		}

		add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved' ), 99, 2 );
		add_filter( 'add_comment_metadata', array( $this, 'add_comment_metadata' ), 20, 3 );

		// register_shutdown_function( array( $this, 'delete_spam_comments' ) );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Comments', 'Comments Module: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' )
		);
	}

	public function default_options()
	{
		return array(
			'disable_notifications' => '1',
			'archived_comments'     => '0',
			'admin_fullcomments'    => '1',
			'front_quicktags'       => '0',
			'front_autogrow'        => '0',
			'disable_notes'         => '1',
			'blacklist_check'       => '0', // FIXME: DRAFT: needs test / NO Settgins UI YET
			'front_nonce'           => '0', // FIXME: DRAFT: working / NO Settgins UI YET / check the hooks
			'captcha'               => '0',
		);
	}

	public function default_settings()
	{
		$settings = array(
			'_general' => array(
				array(
					'field'       => 'disable_notifications',
					'title'       => _x( 'Comment Notifications', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Disable all core comment notifications', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
					'values'      => array(
						__( 'Enabled' , GNETWORK_TEXTDOMAIN ),
						__( 'Disabled', GNETWORK_TEXTDOMAIN ),
					),
				),
				array(
					'field'       => 'archived_comments',
					'title'       => _x( 'Archived Comments', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Archived comments and hide from counts', 'Comments Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'admin_fullcomments',
					'title'       => _x( 'Full Comments', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Full comments on dashboard', 'Comments Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'front_quicktags',
					'title'       => _x( 'Frontend Quicktags', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Activate Quicktags for comments on frontend', 'Comments Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'front_autogrow',
					'title'       => _x( 'Frontend Autogrow', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Makes the comment textarea expand in height automatically', 'Comments Module', GNETWORK_TEXTDOMAIN ),
				),
				array(
					'field'       => 'disable_notes',
					'title'       => _x( 'Form Notes', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Removes extra notes after comment form on frontend', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'default'     => '1',
				),
			),
		);

		if ( class_exists( __NAMESPACE__.'\\Captcha' ) )
			$settings['_captcha'] = array(
				array(
					'field'       => 'captcha',
					'title'       => _x( 'Captcha', 'Comments Module', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Display captcha field on comment form', 'Comments Module', GNETWORK_TEXTDOMAIN ),
				),
			);

		return $settings;
	}

	public function settings_sidebox( $sub, $uri )
	{
		$this->total_comments();
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

	public function template_redirect()
	{
		if ( is_singular()
			&& 'open' == $GLOBALS['wp_query']->post->comment_status ) {

			Utilities::enqueueScript( 'jquery.growfield' );

			$this->scripts[] = '$("#comment").growfield();';

			add_action( 'wp_footer', array( $this, 'print_scripts' ), 99 );
		}
	}

	public function comment_row_actions( $actions, $comment )
	{
		if ( ! $comment->comment_type ) {

			$nonce = esc_html( '_wpnonce='.wp_create_nonce( 'archive-comment_'.$comment->comment_ID ) );

			$actions['comment_archive'] = HTML::tag( 'a', array(
				'href'       => 'comment.php?c='.$comment->comment_ID.'&action=archive&'.$nonce,
				'aria-label' => _x( 'Move this comment to the Archives', 'Comments Module', GNETWORK_TEXTDOMAIN ),
			), _x( 'Archive', 'Comments Module', GNETWORK_TEXTDOMAIN ) );

		} else if ( $this->type_archived == $comment->comment_type ) {

			$nonce = esc_html( '_wpnonce='.wp_create_nonce( 'archive-comment_'.$comment->comment_ID ) );

			$actions['comment_unarchive'] = HTML::tag( 'a', array(
				'href'       => 'comment.php?c='.$comment->comment_ID.'&action=unarchive&'.$nonce,
				'aria-label' => _x( 'Move back this comment from the Archives', 'Comments Module', GNETWORK_TEXTDOMAIN ),
			), _x( 'Unarchive', 'Comments Module', GNETWORK_TEXTDOMAIN ) );
		}

		return $actions;
	}

	public function comment_status_links( $status_links )
	{
		global $comment_type;

		$status_links[$this->type_archived] = HTML::tag( 'a', array(
			'href'  => add_query_arg( 'comment_type', $this->type_archived, admin_url( 'edit-comments.php' ) ),
			'class' => ( $this->type_archived == $comment_type ? 'current' : FALSE ),
			'title' => _x( 'All Archived Comments', 'Comments Module', GNETWORK_TEXTDOMAIN ),
		), _x( 'Archives', 'Comments Module', GNETWORK_TEXTDOMAIN ) );

		return $status_links;
	}

	public function admin_comment_types_dropdown( $comment_types )
	{
		$comment_types[$this->type_archived] = _x( 'Archived', 'Comments Module', GNETWORK_TEXTDOMAIN );
		return $comment_types;
	}

	public function pre_get_comments( &$query )
	{
		if ( empty( $query->query_vars['type__in'] )
			&& empty( $query->query_vars['type'] ) ) {

			if ( empty( $query->query_vars['type__not_in'] ) )
				$query->query_vars['type__not_in'] = array( $this->type_archived );

			else if ( is_array( $query->query_vars['type__not_in'] ) )
				$query->query_vars['type__not_in'][] = $this->type_archived;

			else
				$query->query_vars['type__not_in'] .= ','.$this->type_archived;
		}
	}

	public function wp_count_comments( $filtered = array(), $post_id = 0 )
	{
		if ( FALSE !== ( $count = wp_cache_get( "comments-{$post_id}", 'counts' ) ) )
			return $count;

		$stats = $this->get_comment_count( $post_id );

		$stats['moderated'] = $stats['awaiting_moderation'];
		unset( $stats['awaiting_moderation'] );

		$stats_object = (object) $stats;
		wp_cache_set( "comments-{$post_id}", $stats_object, 'counts' );

		return $stats_object;
	}

	protected function get_comment_count( $post_id = 0 )
	{
		global $wpdb;

		$post_id = (int) $post_id;

		if ( $post_id > 0 ) {

			$where = $wpdb->prepare( "
				WHERE comment_type NOT IN ( %s )
				AND comment_post_ID = %d
			", $this->type_archived, $post_id );

		} else {

			$where = $wpdb->prepare( "
				WHERE comment_type NOT IN ( %s )
			", $this->type_archived );
		}

		$totals = (array) $wpdb->get_results("
			SELECT comment_approved, COUNT( * ) AS total
			FROM {$wpdb->comments}
			{$where}
			GROUP BY comment_approved
		", ARRAY_A);

		$comment_count = array(
			'approved'            => 0,
			'awaiting_moderation' => 0,
			'spam'                => 0,
			'trash'               => 0,
			'post-trashed'        => 0,
			'total_comments'      => 0,
			'all'                 => 0,
		);

		foreach ( $totals as $row ) {
			switch ( $row['comment_approved'] ) {
				case 'trash':
					$comment_count['trash'] = $row['total'];
					break;
				case 'post-trashed':
					$comment_count['post-trashed'] = $row['total'];
					break;
				case 'spam':
					$comment_count['spam'] = $row['total'];
					$comment_count['total_comments'] += $row['total'];
					break;
				case '1':
					$comment_count['approved'] = $row['total'];
					$comment_count['total_comments'] += $row['total'];
					$comment_count['all'] += $row['total'];
					break;
				case '0':
					$comment_count['awaiting_moderation'] = $row['total'];
					$comment_count['total_comments'] += $row['total'];
					$comment_count['all'] += $row['total'];
					break;
				default:
					break;
			}
		}

		return $comment_count;
	}

	public function comment_excerpt( $excerpt )
	{
		global $comment;

		return wpautop( trim( $comment->comment_content ) );
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

	// TODO: add option for:
	private $links_limit        = 5;
	private $links_limit_action = 'reject';
	private $duplicate_action   = 'reject';
	private $known_sites_action = 'spam';
	private $group_action       = 'reject';
	private $known_ip_action    = 'spam';
	private $known_ip_limit     = 3;
	private $known_sites        = array();

	// @SOURCE: https://github.com/Rarst/deny-spam
	public function wp_blacklist_check( $author, $email, $url, $comment, $user_ip, $user_agent )
	{
		global $wpdb;

		// links limit
		if ( substr_count( strtolower( $comment ), 'http://' ) > $this->links_limit ) {

			if ( 'reject' == $this->links_limit_action )
				wp_die( sprintf( _x( 'Comment has <strong>over %s links</strong>. Please reduce number of those.', 'Comments Module', GNETWORK_TEXTDOMAIN ), $this->links_limit ) );

			else
				add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved_spam' ) );

			return;
		}

		// duplicate comment content
		$dupe = "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved='spam' AND comment_content = '{$comment}' LIMIT 1";
		if ( $wpdb->get_var( $dupe ) ) {

			if ( 'reject' == $this->duplicate_action )
				wp_die( _x( 'Duplicate comment content. Please rephrase.', 'Comments Module', GNETWORK_TEXTDOMAIN ) );

			else
				add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved_spam' ) );

			return;
		}

		// known spam URL
		if ( ! empty( $url ) ) {
			$dupe = "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved='spam' AND comment_author_url = '{$url}' LIMIT 1";
			if ( $wpdb->get_var( $dupe ) || $this->is_known_spam_domain( $url ) ) {

				if ( 'reject' == $this->known_sites_action )
					wp_die( _x( 'Your URL or domain is in list of known spam-promoted sites. If you believe this to be an error please contact site admin.', 'Comments Module', GNETWORK_TEXTDOMAIN ) );

				else
					add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved_spam' ) );

				return;
			}
		}

		// known spam IP
		$dupe = "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved='spam' AND comment_author_IP = '$user_ip'";
		if ( $wpdb->get_var( $dupe ) > $this->known_ip_limit ) {

			if ( 'reject' == $this->known_ip_action )
				wp_die( _x( 'Your IP is in list of known spam sources. If you believe this to be an error please contact site admin.', 'Comments Module', GNETWORK_TEXTDOMAIN ) );

			else
				add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved_spam' ) );

			return;
		}

		// group of spam duplicates
		$dupe = "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved='0' AND comment_content = '$comment' LIMIT 1";
		if ( $wpdb->get_var( $dupe ) ) {

			if ( 'reject' == $this->group_action ) {
				$wpdb->query( "UPDATE $wpdb->comments SET comment_approved='trash' WHERE comment_content = '$comment'" );
				wp_die( _x( 'Duplicate comment content. Please rephrase.', 'Comments Module', GNETWORK_TEXTDOMAIN ) );

			} else {

				$wpdb->query( "UPDATE $wpdb->comments SET comment_approved='spam' WHERE comment_content = '$comment'" );
				add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved_spam' ) );
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

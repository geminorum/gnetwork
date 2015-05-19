<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkComments extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = 'comments';

	public function setup_actions()
	{
		gNetworkAdmin::registerMenu( 'comments',
			__( 'Comments', GNETWORK_TEXTDOMAIN ),
			array( & $this, 'settings' )
		);

		//if ( isset( $_GET['action'] ) && $_GET['action'] == 'gnetworkdeletespams' )
			//add_action( 'init', array( & $this, 'init_delete_spams' ) );

		add_filter( 'comment_excerpt', array( & $this, 'comment_excerpt' ) );
		add_filter( 'pre_comment_approved', array( & $this, 'pre_comment_approved' ), 99, 2 );

		add_filter( 'add_comment_metadata', array( & $this, 'add_comment_metadata' ), 20, 3 );

		//register_shutdown_function( array( & $this, 'delete_spam_comments' ) );

		/** WORKING BUT MAKE SURE THIS NESSECARY?!
		// ORIGINALLY FROM : http://wordpress.org/plugins/really-simple-comment-validation/
		add_action( 'comment_form', array( & $this, 'comment_form' ) );
		add_action( 'pre_comment_approved', array( & $this, 'pre_comment_approved_nounce' ) );
		add_action( 'explain_nonce_gnc-check_comments', array( & $this, 'explain_nonce' ) );
		**/
	}

	public function settings( $sub = null )
	{
		if ( 'comments' == $sub ) {

			if ( isset( $_POST['purge_spams'] ) ) {
				check_admin_referer( 'gnetwork_'.$sub.'-options' );
				$this->remove_spam_meta();
				self::redirect_referer( 'spamspurged' );
			}

			$this->update( $sub );
			$this->register_settings();
			$this->register_button( 'purge_spams', __( 'Purge Spam Comments', GNETWORK_TEXTDOMAIN ) );

			add_action( 'gnetwork_admin_settings_sub_comments', array( & $this, 'settings_html' ), 10, 2 );
		}
	}

	public function settings_sidebox( $sub, $settings_uri )
	{
		echo 'TODO: total comments count';
	}

	public function default_options()
    {
        return array(
            'admin_fullcomments' => ! GNETWORK_ADMIN_FULLCOMMENTS_DISABLED,
        );
    }

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field' => 'admin_fullcomments',
					'type' => 'enabled',
					'title' => _x( 'Full Comments', 'Enable Full Comments On Dashboard', GNETWORK_TEXTDOMAIN ),
					'desc' => __( 'Full Comments On Dashboard', GNETWORK_TEXTDOMAIN ),
					'default' => '0',
				),
			),
		);
	}

	// http://scribu.net/wordpress/full-comments-on-dashboard
	public function comment_excerpt( $excerpt )
	{
		//if ( ! is_admin() || GNETWORK_ADMIN_FULLCOMMENTS_DISABLED )
		if ( ! is_admin() || ! $this->options['admin_fullcomments'] )
			return $excerpt;

		global $comment;

		$content = wpautop( $comment->comment_content );
		$content = substr( $content, 3, -5 );	// Remove first <p> and last </p>
		$content = str_replace( '<p>', '<p style="display:block; margin:1em 0">', $content );
		$content .= '</p>';

		return $content;
	}

	// http://css-tricks.com/snippets/wordpress/spam-comments-with-very-long-urls/
	public function pre_comment_approved( $approved , $commentdata )
	{
		return ( strlen( $commentdata['comment_author_url'] ) > 50 ) ? 'spam' : $approved;
	}

	// http://south-gippsland.net/remove-spam-from-wordpress-database/
	public function remove_spam_meta()
	{
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_id FROM {$wpdb->comments})" );
		$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '%akismet%'" );
		$wpdb->query( "OPTIMIZE TABLE {$wpdb->comments}" );

		// http://rayofsolaris.net/blog/2012/akismet-bloat
		// "DELETE FROM {$wpdb->commentmeta} WHERE 'meta_key' IN ( 'akismet_result', 'akismet_history', 'akismet_user', 'akismet_user_result' ) ";

		// SEE : http://www.catswhocode.com/blog/10-useful-sql-queries-to-clean-up-your-wordpress-database
	}

	// http://rayofsolaris.net/blog/2012/akismet-bloat
	public function add_comment_metadata( $check, $object_id, $meta_key )
	{
		$to_filter = array(
			'akismet_result',
			'akismet_history',
			'akismet_user',
			'akismet_user_result',

			//'akismet_error',
			//'akismet_as_submitted',
			//'akismet_pro_tip',
		);

		if( in_array( $meta_key, $to_filter ) )
			return false;

		return $check;
	}

	// the admin-bar button moved to : gNetworkAdminBar
	public function init_delete_spams()
	{
		if ( ! is_super_admin() )
			return;


		// if ( function_exists( 'current_user_can' )
		// 	&& false == current_user_can( 'delete_others_posts' ) )
		// 		return false;


		if ( isset( $_GET['action'] )
			&& $_GET['action'] == 'gnetworkdeletespams'
			&& ( isset( $_GET['_wpnonce'] ) ? wp_verify_nonce( $_REQUEST['_wpnonce'], 'gnetwork-delete-spams' ) : false ) ) {

				// $path = trailingslashit( get_supercache_dir() . preg_replace( '/:.*$/', '', $_GET['path'] ) );
				// $files = get_all_supercache_filenames( $path );
				// foreach( $files as $cache_file )
				// 	prune_super_cache( $path . $cache_file, true );

				$this->remove_spam_meta();

				wp_redirect( preg_replace( '/[ <>\'\"\r\n\t\(\)]/', '', $_GET['path'] ) );
				die();
		}
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
			if ( empty( $next ) ) {
				$next = 1;
			}

			if ( $next > 4980 ) {
				return;
			}

			switch_to_blog( $next );

			$spams = $wpdb->get_col( "SELECT comment_id FROM {$wpdb->comments} WHERE comment_approved = 'spam' LIMIT 10"     );

			if ( empty( $spams ) ) {
				$next++;
				update_site_option( 'gnc_delete_next_blog', $next );
			} else {
				foreach ( $spams as $spam ) {
					wp_delete_comment( $spam, true );
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

	public function comment_form()
	{
		wp_nonce_field( 'gnc-check_comments', '_gnc_nonce', false );
	}

	public function pre_comment_approved_nounce( $content )
	{
		check_admin_referer( 'gnc-check_comments', wp_verify_nonce( $_POST['_gnc_nonce'] ) );
	}

	public function explain_nonce()
	{
		return __( 'Your attempt to add this comment has failed.', GNETWORK_TEXTDOMAIN );
	}

	// DRAFT
	public function close_ping()
	{
		// http://www.wpbeginner.com/wp-tutorials/how-to-disable-trackbacks-and-pings-on-existing-wordpress-posts/
		//UPDATE wp_posts SET ping_status='closed' WHERE post_status = 'publish' AND post_type = 'post';
		//UPDATE wp_posts SET ping_status='closed' WHERE post_status = 'publish' AND post_type = 'page';

	}
}

// http://www.codecheese.com/2013/11/wordpress-get-total-comment-count/

// https://wordpress.org/plugins/disable-comments/
// https://github.com/solarissmoke/disable-comments
// https://github.com/solarissmoke/disable-comments-mu

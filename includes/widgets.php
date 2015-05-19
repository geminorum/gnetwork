<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkWidgets extends gNetworkModuleCore
{

	var $_network = false;
	var $_option_key = false;

	public function setup_actions()
	{
		add_action( 'widgets_init', array( & $this, 'widgets_init' ) );

		// add shortcode support for widgets
		// add_filter( 'widget_text', 'do_shortcode' );
	}

	public function widgets_init()
	{
		$widgets = array(
			'gNetworkShortcode_Widget',
			'gNetworkTweets_Widget',
		);

		foreach ( $widgets as $widget )
			register_widget( $widget );
	}

	// widget custom css
	// add_action( 'in_widget_form', array( 'WCSSC', 'extend_widget_form' ), 10, 3 );
	// add_filter( 'widget_update_callback', array( 'WCSSC', 'update_widget' ), 10, 2 );

	// ! is_admin()
	// add_filter( 'dynamic_sidebar_params', array( 'WCSSC', 'add_widget_classes' ) );


}

// http://wpti.ps/plugins/multisite-dashboard-feed-widget-plugin/
// http://wpti.ps/functions/make-latest-news-dashboard-widget/
// http://wordpress.org/extend/plugins/multisite-dashboard-feed-widget/

// WORKING DRAFT
// https://gist.github.com/chrisguitarguy/1279630
class gNetworkTweets_Widget extends WP_Widget
{
	function __construct()
	{
		$opts = array(
			'classname' => 'gnetwork-tweets-widget',
			'description' => __( 'gNetwork: Display your most recent tweet', GNETWORK_TEXTDOMAIN )
		);

		parent::__construct( 'gnetwork-tweets-widget', __( 'Latest Tweet', GNETWORK_TEXTDOMAIN ), $opts );
	}

	function form( $instance )
	{

		$defaults = array(
			'title'		=> '',
			'twitter' 	=> 'nasserrafie',
		);

		$instance = wp_parse_args( $instance, $defaults );
		$display = array(
			'twitter' 	=> __( 'Your Latest Tweet', GNETWORK_TEXTDOMAIN ),
			'custom'	=> __( 'A Custom Mesage', GNETWORK_TEXTDOMAIN )
		);
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _ex( 'Title', 'Widget Form Label', GNETWORK_TEXTDOMAIN ); ?></label>
				<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'twitter' ); ?>"><?php _ex( 'Twitter', 'Widget Form Label', GNETWORK_TEXTDOMAIN ); ?></label>
				<input type="text" name="<?php echo $this->get_field_name( 'twitter' ); ?>" class="widefat" id="<?php echo $this->get_field_id( 'twitter' ); ?>" value="<?php echo esc_attr( $instance['twitter'] ); ?>" />
			</p>

		<?php
	}

	function update( $new, $old )
	{
		$clean = $old;
		$clean['title'] = isset( $new['title'] ) ? strip_tags( esc_html( $new['title'] ) ) : '';
		$clean['twitter'] = isset( $new['twitter'] ) ? esc_attr( $new['twitter'] ) : '';
		return $clean;
	}

	function widget( $args, $instance )
	{
		extract( $args );
		echo $before_widget;
		if( $instance['title'] )
		{
			echo $before_title . strip_tags( $instance['title'] ) . $after_title;
		}
		?>
			<div class="gnetwork-tweets">
				<?php echo $this->get_tweet( esc_attr( $instance['twitter'] ) ); ?>
			</div>
		<?php
		echo $after_widget;
	}

	// Get our tweet from the database or from twitter;
	function get_tweet( $user )
	{
		/**
		* Try to fetch our tweet from the database so we don't have to hit the
		* Twitter api
		*/
		if( $tweet = get_transient( 'apex_tweet_' . $user ) )
		{
			return $tweet;
		}
		else // no dice, gotta go for twitter
		{
			// build our url, can't pass strings directly to wp_remote_get :/
			$url = 'http://api.twitter.com/1/users/show.json?id=' . $user;

			// fetch!
			$resp = wp_remote_get( $url );

			// bail if we failed to get a 200 response
			if( is_wp_error( $resp ) || 200 != $resp['response']['code'] ) return '';

			// parse the json
			$obj = json_decode( $resp['body'] );

			// get the status and make any links clickable
			$status = isset( $obj->status->text ) ? make_clickable( $obj->status->text ) : '';

			// Set our transient so we don't have to hit twitter again for 12 hours.
			set_transient( 'apex_tweet_' . $user, $status, 60 * 60 * 12 );

			// Whew, return the tweet
			return $status;
		}
	}

}

// Originally from v0.3 : http://wordpress.org/plugins/shortcode-widget/
class gNetworkShortcode_Widget extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'shortcode_widget',
			'description' => __( 'Shortcode or HTML or Plain Text', GNETWORK_TEXTDOMAIN )
		);
		$control_ops = array( 'width' => 400, 'height' => 350 );
		parent::__construct( 'shortcode-widget',
			__( 'gNetwork: Shortcode Widget', GNETWORK_TEXTDOMAIN ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance )
	{
		extract( $args );
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$text = do_shortcode(apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance ));
		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } ?>
			<div class="textwidget"><?php echo !empty( $instance['filter'] ) ? wpautop( $text ) : $text; ?></div>
		<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		if ( current_user_can('unfiltered_html') )
			$instance['text'] =  $new_instance['text'];
		else
			$instance['text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['text']) ) ); // wp_filter_post_kses() expects slashed
		$instance['filter'] = isset($new_instance['filter']);
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '' ) );
		$title = strip_tags($instance['title']);
		$text = esc_textarea($instance['text']);
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _ex( 'Title', 'Widget Form Label', GNETWORK_TEXTDOMAIN ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

		<p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _ex( 'Automatically add paragraphs', 'Widget Form Label', GNETWORK_TEXTDOMAIN); ?></label></p>
<?php
	}
}

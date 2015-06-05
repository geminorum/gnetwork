<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkWidgets extends gNetworkModuleCore
{

	var $_network    = FALSE;
	var $_option_key = FALSE;

	public function setup_actions()
	{
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
	}

	public function widgets_init()
	{
		$widgets = array(
			'gNetworkShortcode_Widget',
		);

		foreach ( $widgets as $widget )
			register_widget( $widget );
	}
}

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

	public function widget( $args, $instance )
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

	public function update( $new_instance, $old_instance )
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

	public function form( $instance )
	{
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '' ) );
		$title = strip_tags($instance['title']);
		$text = esc_textarea($instance['text']);

		?><p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _ex( 'Title', 'Widget Form Label', GNETWORK_TEXTDOMAIN ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

		<p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _ex( 'Automatically add paragraphs', 'Widget Form Label', GNETWORK_TEXTDOMAIN); ?></label></p><?php
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkWidgets extends gNetworkModuleCore
{

	protected $option_key = FALSE;
	protected $network    = FALSE;
	protected $ajax       = TRUE;

	protected function setup_actions()
	{
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
	}

	public function widgets_init()
	{
		$widgets = array(
			'gNetworkDev_Legend_Widget',
			'gNetworkShortcode_Widget',
		);

		if ( class_exists( 'gNetworkTracking' ) ) {
			$widgets[] = 'gNetworkTracking_Quantcast_Widget';
			$widgets[] = 'gNetworkTracking_GPlusBadge_Widget';
		}

		foreach ( $widgets as $widget )
			register_widget( $widget );
	}
}

class gNetworkDev_Legend_Widget extends WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-legend-widget',
			_x( 'gNetwork Dev: Legend Widget', 'Widgets Module', GNETWORK_TEXTDOMAIN ),
			array(
				'classname'   => 'gnetwork-wrap-widget legend-widget',
				'description' => _x( 'Simple Changelog Legend', 'Widgets Module', GNETWORK_TEXTDOMAIN )
			) );
	}

	public function widget( $args, $instance )
	{
		echo $args['before_widget'];
			echo $args['before_title'].'legend'.$args['after_title'];

			echo '* &mdash; security fix<br />
			# &mdash; bug fix<br />
			$ &mdash; language fix or change<br />
			+ &mdash; addition<br />
			^ &mdash; change<br />
			- &mdash; removed<br />
			! &mdash; note';

		echo $args['after_widget'];
	}
}


class gNetworkTracking_GPlusBadge_Widget extends WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-gplusbadge-widget',
			_x( 'gNetwork Tracking: Google Plus Badge', 'Widgets Module', GNETWORK_TEXTDOMAIN ),
			array(
				'classname'   => 'gnetwork-wrap-widget gplusbadge-widget',
				'description' => _x( 'Simple Google Plus Badge', 'Widgets Module', GNETWORK_TEXTDOMAIN )
			) );
	}

	public function form( $instance )
	{
		$html = gNetworkUtilities::html( 'input', array(
			'type'  => 'number',
			'id'    => $this->get_field_id( 'width' ),
			'name'  => $this->get_field_name( 'width' ),
			'value' => isset( $instance['width'] ) ? $instance['width'] : '300',
			'class' => 'small-text',
			'dir'   => 'ltr',
		) );

		echo '<p>'. gNetworkUtilities::html( 'label', array(
			'for' => $this->get_field_id( 'width' ),
		), _x( 'Side bar width:', 'Widgets Module', GNETWORK_TEXTDOMAIN ).' '.$html ).'</p>';

		$html = gNetworkUtilities::html( 'input', array(
			'type'  => 'text',
			'id'    => $this->get_field_id( 'override' ),
			'name'  => $this->get_field_name( 'override' ),
			'value' => isset( $instance['override'] ) ? $instance['override'] : '',
			'class' => 'widefat',
			'dir'   => 'ltr',
		) );

		echo '<p>'. gNetworkUtilities::html( 'label', array(
			'for' => $this->get_field_id( 'override' ),
		), _x( 'Override Publisher ID:', 'Widgets Module', GNETWORK_TEXTDOMAIN ).' '.$html );

		echo '<br /><span class="description">'._x( 'Leave empty to use site Publisher ID', 'Widgets Module', GNETWORK_TEXTDOMAIN ).'</span>';
		echo '</p>';
	}

	public function widget( $args, $instance )
	{
		global $gNetwork;

		$override = isset( $instance['override'] ) ? $instance['override'] : FALSE;

		if ( isset( $gNetwork->tracking ) ) {

			if ( $override || $gNetwork->tracking->options['plus_publisher'] ) {

				$html = $gNetwork->tracking->shortcode_google_plus_badge( array(
					'id'      => $override,
					'width'   => isset( $instance['width'] ) ? $instance['width'] : '300',
					'context' => 'widget',
					'wrap'    => FALSE,
				) );

				if ( $html ) {
					echo $args['before_widget'];
						echo '<div class="gnetwork-wrap-iframe">'.$html.'</div>';
					echo $args['after_widget'];
				}
			}
		}
	}
}

class gNetworkTracking_Quantcast_Widget extends WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'gnetwork-quantcast-widget',
			_x( 'gNetwork Tracking: Quantcast Widget', 'Widgets Module', GNETWORK_TEXTDOMAIN ),
			array(
				'classname'   => 'gnetwork-wrap-widget quantcast-widget',
				'description' => _x( 'Simple Quantcast Data Badge', 'Widgets Module', GNETWORK_TEXTDOMAIN )
			) );
	}

	public function widget( $args, $instance )
	{
		global $gNetwork;

		if ( isset( $gNetwork->tracking ) && $gNetwork->tracking->options['primary_domain'] ) {

			echo $args['before_widget'];

			echo '<div class="gnetwork-wrap-iframe">'.gNetworkUtilities::html( 'iframe', array(
				'frameborder'  => '0',
				'marginheight' => '0',
				'marginwidth'  => '0',
				'height'       => '120',
				'width'        => '160',
				'scrolling'    => 'no',
				'src'          => 'http://widget.quantcast.com/'.$gNetwork->tracking->options['primary_domain'].'/10?&timeWidth=1&daysOfData=90',
			), NULL ).'</div>';

			echo $args['after_widget'];
		}
	}
}

class gNetworkShortcode_Widget extends WP_Widget
{

	public function __construct()
	{
		$widget_ops = array(
			'classname'   => 'shortcode_widget',
			'description' => _x( 'Arbitrary text or HTML or Shortcode!', 'Widgets Module', GNETWORK_TEXTDOMAIN )
		);

		$control_ops = array(
			'width'  => 400,
			'height' => 350,
		);

		parent::__construct( 'shortcode-widget',
			_x( 'gNetwork: Shortcode Widget', 'Widgets Module', GNETWORK_TEXTDOMAIN ),
			$widget_ops,
			$control_ops );
	}

	public function widget( $args, $instance )
	{
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$text  = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance );

		$text = do_shortcode( $text );

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'].$title.$args['after_title'];
		echo '<div class="textwidget">';
			echo ! empty( $instance['filter'] ) ? wpautop( $text ) : $text;
		echo '</div>';
		echo $args['after_widget'];
	}

	// EXACT COPY OF CORE
	public function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		if ( current_user_can('unfiltered_html') )
			$instance['text'] =  $new_instance['text'];
		else
			$instance['text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['text']) ) ); // wp_filter_post_kses() expects slashed
		$instance['filter'] = ! empty( $new_instance['filter'] );
		return $instance;
	}

	// EXACT COPY OF CORE
	public function form( $instance )
	{
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '' ) );
		$title = strip_tags($instance['title']);
		$text = esc_textarea($instance['text']);
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'text' ); ?>"><?php _e( 'Content:' ); ?></label>
		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea></p>

		<p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Automatically add paragraphs'); ?></label></p>
<?php
	}
}

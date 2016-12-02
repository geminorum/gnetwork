<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Widgets extends ModuleCore
{

	protected $key     = 'widgets';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	private $sidebar_widgets = array();

	protected function setup_actions()
	{
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		if ( count( $this->options['disabled_sidebar_widgets'] ) )
			add_action( 'widgets_init', array( $this, 'disable_sidebar_widgets' ), 100 );

		else if ( is_admin() )
			add_action( 'widgets_init', array( $this, 'populate_widgets' ), 100 );

		if ( count( $this->options['disabled_dashboard_widgets'] ) && WordPress::mustRegisterUI() )
			add_action( 'wp_dashboard_setup', array( $this, 'disable_dashboard_widgets' ), 100 );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Widgets', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			array( $this, 'settings' ), 'edit_theme_options'
		);
	}

	public function default_options()
	{
		return array(
			'disabled_sidebar_widgets'   => array(),
			'disabled_dashboard_widgets' => array(),
		);
	}

	public function default_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'disabled_sidebar_widgets',
					'type'        => 'callback',
					'title'       => _x( 'Sidebar Widgets', 'Modules: Widgets: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Choose the Sidebar Widgets You Would Like to Disable', 'Modules: Widgets: Settings', GNETWORK_TEXTDOMAIN ),
					'callback'    => array( $this, 'setting_sidebar_widgets' ),
				),
				array(
					'field'       => 'disabled_dashboard_widgets',
					'type'        => 'callback',
					'title'       => _x( 'Dashboard Widgets', 'Modules: Widgets: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Choose the Dashboard Widgets You Would Like to Disable', 'Modules: Widgets: Settings', GNETWORK_TEXTDOMAIN ),
					'callback'    => array( $this, 'setting_dashboard_widgets' ),
				),
			),
		);
	}

	public function setting_sidebar_widgets( $args, $pre )
	{
		extract( $pre, EXTR_SKIP );

		foreach ( $this->sidebar_widgets as $value_name => $value_title ) {

			if ( in_array( $value_name, $exclude ) )
				continue;

			$html = HTML::tag( 'input', array(
				'type'     => 'checkbox',
				'class'    => $args['field_class'],
				'name'     => $name.'['.$value_name.']',
				'id'       => $id.'-'.$value_name,
				'value'    => '1',
				'checked'  => in_array( $value_name, ( array ) $value ),
				'disabled' => $args['disabled'],
				'dir'      => $args['dir'],
			) );

			echo '<p>'.HTML::tag( 'label', array(
				'for' => $id.'-'.$value_name,
			), $html.'&nbsp;'.esc_html( $value_title ).' <code>'.$value_name.'</code>' ).'</p>';
		}
	}

	public function setting_dashboard_widgets( $args, $pre )
	{
		global $wp_meta_boxes;

		if ( ! is_array( $wp_meta_boxes['dashboard'] ) ) {
			require_once( ABSPATH.'/wp-admin/includes/dashboard.php' );
			set_current_screen( 'dashboard' );
			remove_action( 'wp_dashboard_setup', array( $this, 'disable_dashboard_widgets' ), 100 );
			wp_dashboard_setup();
			add_action( 'wp_dashboard_setup', array( $this, 'disable_dashboard_widgets' ), 100 );
			set_current_screen( Settings::getScreenHook( FALSE ) );
		}

		if ( isset( $wp_meta_boxes['dashboard'][0] ) )
			unset( $wp_meta_boxes['dashboard'][0] );

		extract( $pre, EXTR_SKIP );

		$html = HTML::tag( 'input', array(
			'type'    => 'checkbox',
			'class'   => $args['field_class'],
			'name'    => $name.'[core::dashboard_welcome_panel]',
			'id'      => $id.'-core-dashboard_welcome_panel',
			'value'   => '1',
			'checked' => in_array( 'core::dashboard_welcome_panel', ( array ) $value ),
		) );

		echo '<p>'.HTML::tag( 'label', array(
			'for' => $id.'-core-dashboard_welcome_panel',
		), $html.'&nbsp;'.__( 'Welcome to WordPress!' ).' <code>dashboard_welcome_panel</code>' ).'</p>';

		foreach ( $wp_meta_boxes['dashboard'] as $context => $priority ) {

			foreach ( $priority as $data ) {

				foreach ( $data as $value_name => $widget ) {

					if ( FALSE === $widget )
						continue;

					$html = HTML::tag( 'input', array(
						'type'    => 'checkbox',
						'class'   => $args['field_class'],
						'name'    => $name.'['.$context.'::'.$value_name.']',
						'id'      => $id.'-'.$context.'-'.$value_name,
						'value'   => '1',
						'checked' => in_array( $context.'::'.$value_name, ( array ) $value ),
					) );

					echo '<p>'.HTML::tag( 'label', array(
						'for' => $id.'-'.$context.'-'.$value_name,
					), $html.'&nbsp;'.esc_html( wp_strip_all_tags( $widget['title'] ) ).' <code>'.$value_name.'</code>' ).'</p>';
				}
			}
		}
	}

	public function populate_widgets()
	{
		if ( ! empty( $GLOBALS['wp_widget_factory'] ) )
			foreach ( $GLOBALS['wp_widget_factory']->widgets as $id => $widget )
				$this->sidebar_widgets[$id] = $widget->name;
	}

	public function disable_sidebar_widgets()
	{
		if ( is_admin() )
			$this->populate_widgets();

		foreach ( $this->options['disabled_sidebar_widgets'] as $id )
			unregister_widget( $id );
	}

	public function disable_dashboard_widgets()
	{
		foreach ( $this->options['disabled_dashboard_widgets'] as $widget ) {
			list( $context, $id ) = explode( '::', $widget );

			if ( 'dashboard_welcome_panel' === $id )
				remove_action( 'welcome_panel', 'wp_welcome_panel' );
			else
				remove_meta_box( $id, 'dashboard', $context );
		}
	}

	public function widgets_init()
	{
		$widgets = array(
			GNETWORK_DIR.'includes/widgets/devlegend.php' => __NAMESPACE__.'\\DevLegend_Widget',
			GNETWORK_DIR.'includes/widgets/shortcode.php' => __NAMESPACE__.'\\Shortcode_Widget',
		);

		if ( class_exists( __NAMESPACE__.'\\Tracking' ) ) {
			$widgets[GNETWORK_DIR.'includes/widgets/tracking-gplusbadge.php'] = __NAMESPACE__.'\\Tracking_GPlusBadge_Widget';
			$widgets[GNETWORK_DIR.'includes/widgets/tracking-quantcast.php']  = __NAMESPACE__.'\\Tracking_Quantcast_Widget';
		}

		foreach ( apply_filters( $this->hook(), $widgets ) as $path => $widget ) {

			if ( file_exists( $path ) ) {
				require_once( $path );

				if ( class_exists( $widget ) )
					register_widget( $widget );
			}
		}
	}
}

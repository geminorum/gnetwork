<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Widgets extends gNetwork\Module
{

	protected $key     = 'widgets';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	private $sidebar_widgets = [];

	protected function setup_actions()
	{
		if ( $this->options['register_sidebar_widgets'] ) {
			$this->action( 'widgets_init' );
			$this->action( 'customize_register', 1, 12 );
		}

		if ( count( $this->options['disabled_sidebar_widgets'] ) )
			add_action( 'widgets_init', [ $this, 'disable_sidebar_widgets' ], 100 );

		else if ( is_admin() )
			add_action( 'widgets_init', [ $this, 'populate_widgets' ], 100 );

		if ( count( $this->options['disabled_dashboard_widgets'] ) && WordPress::mustRegisterUI() )
			add_action( 'wp_dashboard_setup', [ $this, 'disable_dashboard_widgets' ], 100 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Widgets', 'Modules: Menu Name', 'gnetwork' ), NULL, 9, 'edit_theme_options' );
	}

	public function default_options()
	{
		return [
			'register_sidebar_widgets'   => '0',
			'disabled_sidebar_widgets'   => [],
			'disabled_dashboard_widgets' => [],
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'register_sidebar_widgets',
					'title'       => _x( 'Extra Widgets', 'Modules: Widgets: Settings', 'gnetwork' ),
					'description' => _x( 'Registers extra sidebar widgets.', 'Modules: Widgets: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'disabled_sidebar_widgets',
					'type'        => 'callback',
					'title'       => _x( 'Sidebar Widgets', 'Modules: Widgets: Settings', 'gnetwork' ),
					'description' => _x( 'Choose the sidebar widgets you would like to <b>disable</b>.', 'Modules: Widgets: Settings', 'gnetwork' ),
					'callback'    => [ $this, 'setting_sidebar_widgets' ],
				],
				[
					'field'       => 'disabled_dashboard_widgets',
					'type'        => 'callback',
					'title'       => _x( 'Dashboard Widgets', 'Modules: Widgets: Settings', 'gnetwork' ),
					'description' => _x( 'Choose the dashboard widgets you would like to <b>disable</b>.', 'Modules: Widgets: Settings', 'gnetwork' ),
					'callback'    => [ $this, 'setting_dashboard_widgets' ],
				],
			],
		];
	}

	public function setting_sidebar_widgets( $args, $pre )
	{
		extract( $pre, EXTR_SKIP );

		foreach ( $this->sidebar_widgets as $value_name => $value_title ) {

			if ( in_array( $value_name, $exclude ) )
				continue;

			$html = HTML::tag( 'input', [
				'type'     => 'checkbox',
				'class'    => $args['field_class'],
				'name'     => $name.'['.$value_name.']',
				'id'       => $id.'-'.$value_name,
				'value'    => '1',
				'checked'  => in_array( $value_name, (array) $value ),
				'disabled' => $args['disabled'],
				'dir'      => $args['dir'],
			] );

			HTML::label( $html.'&nbsp;'.HTML::escape( $value_title ).' <code>'.$value_name.'</code>', $id.'-'.$value_name );
		}
	}

	public function setting_dashboard_widgets( $args, $pre )
	{
		global $wp_meta_boxes;

		if ( ! isset( $wp_meta_boxes['dashboard'] )
			|| ! is_array( $wp_meta_boxes['dashboard'] ) ) {

			require_once( ABSPATH.'/wp-admin/includes/dashboard.php' );
			set_current_screen( 'dashboard' );
			remove_action( 'wp_dashboard_setup', [ $this, 'disable_dashboard_widgets' ], 100 );
			wp_dashboard_setup();
			add_action( 'wp_dashboard_setup', [ $this, 'disable_dashboard_widgets' ], 100 );
			set_current_screen( Settings::getScreenHook( FALSE ) );
		}

		if ( isset( $wp_meta_boxes['dashboard'][0] ) )
			unset( $wp_meta_boxes['dashboard'][0] );

		extract( $pre, EXTR_SKIP );

		$html = HTML::tag( 'input', [
			'type'    => 'checkbox',
			'class'   => $args['field_class'],
			'name'    => $name.'[core::dashboard_welcome_panel]',
			'id'      => $id.'-core-dashboard_welcome_panel',
			'value'   => '1',
			'checked' => in_array( 'core::dashboard_welcome_panel', (array) $value ),
		] );

		HTML::label( $html.'&nbsp;'.__( 'Welcome to WordPress!' ).' <code>dashboard_welcome_panel</code>', $id.'-core-dashboard_welcome_panel' );

		foreach ( $wp_meta_boxes['dashboard'] as $context => $priority ) {

			foreach ( $priority as $data ) {

				foreach ( $data as $value_name => $widget ) {

					if ( FALSE === $widget )
						continue;

					$html = HTML::tag( 'input', [
						'type'    => 'checkbox',
						'class'   => $args['field_class'],
						'name'    => $name.'['.$context.'::'.$value_name.']',
						'id'      => $id.'-'.$context.'-'.$value_name,
						'value'   => '1',
						'checked' => in_array( $context.'::'.$value_name, (array) $value ),
					] );

					$html.= '&nbsp;'.HTML::escape( strip_tags( $widget['title'] ) );
					$html.= ' <code>'.$value_name.'</code>';

					HTML::label( $html, $id.'-'.$context.'-'.$value_name );
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
		$widgets = [
			GNETWORK_DIR.'includes/Widgets/CodeLegend.php' => 'geminorum\\gNetwork\\Widgets\\CodeLegend',
			GNETWORK_DIR.'includes/Widgets/SiteIcon.php'   => 'geminorum\\gNetwork\\Widgets\\SiteIcon',
		];

		if ( class_exists( __NAMESPACE__.'\\Navigation' ) )
			$widgets[GNETWORK_DIR.'includes/Widgets/NavigationMenu.php'] = 'geminorum\\gNetwork\\Widgets\\NavigationMenu';

		if ( class_exists( __NAMESPACE__.'\\Tracking' ) )
			$widgets[GNETWORK_DIR.'includes/Widgets/TrackingQuantcast.php']  = 'geminorum\\gNetwork\\Widgets\\TrackingQuantcast';

		foreach ( apply_filters( $this->hook(), $widgets ) as $path => $widget ) {

			if ( is_readable( $path ) ) {
				require_once( $path );

				if ( class_exists( $widget ) )
					register_widget( $widget );
			}
		}
	}

	public function customize_register( $wp_customize )
	{
		$wp_customize->get_setting( 'site_icon' )->transport = 'refresh';
	}
}

<?php namespace geminorum\gNetwork\Misc\QM;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

class OutputterCurrentObject extends \QM_Output_Html
{

	protected $collector;

	public function __construct( $collector )
	{
		parent::__construct( $collector );

		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 200 );
	}

	public function name()
	{
		return _x( 'Current Object', 'Modules: Debug: QM Output Title', 'gnetwork' );
	}

	// TODO: add submenu on qm
	public function output()
	{
		$data = $this->collector->get_data();

		// $this->before_non_tabular_output();
		$this->before_tabular_output();

		Core\HTML::tableSide( $data['object'], TRUE, Core\HTML::tag( 'h2', sprintf(
			/* translators: `%s`: object type */
			_x( 'Current Object: %s', 'Modules: Debug: QM Output', 'gnetwork' ),
			Core\HTML::tag( 'strong', Core\HTML::sanitizeDisplay( $data['type'] ) )
		) ) );

		if ( ! empty( $data['meta'] ) )
			Core\HTML::tableSide( $data['meta'], TRUE, Core\HTML::tag( 'h2', _x( 'Current Object Meta', 'Modules: Debug: QM Output', 'gnetwork' ) ) );

		if ( ! empty( $data['supports'] ) )
			Core\HTML::tableSide( $data['supports'], TRUE, Core\HTML::tag( 'h2', _x( 'Current Object Supports', 'Modules: Debug: QM Output', 'gnetwork' ) ) );

		if ( ! empty( $data['taxonomies'] ) )
			Core\HTML::tableSide( $data['taxonomies'], TRUE, Core\HTML::tag( 'h2', _x( 'Current Object Taxonomies', 'Modules: Debug: QM Output', 'gnetwork' ) ) );

		if ( ! empty( $data['comments'] ) )
			Core\HTML::tableSide( $data['comments'], TRUE, Core\HTML::tag( 'h2', _x( 'Current Object Comments', 'Modules: Debug: QM Output', 'gnetwork' ) ) );

		// $this->after_non_tabular_output();
		$this->after_tabular_output();
	}
}

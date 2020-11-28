<?php namespace geminorum\gNetwork\Misc\QM;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;

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

		/* translators: %s: object type */
		HTML::tableSide( $data['object'], TRUE, HTML::tag( 'h2', sprintf( _x( 'Current Object: %s', 'Modules: Debug: QM Output', 'gnetwork' ), '<strong>'.HTML::sanitizeDisplay( $data['type'] ).'</strong>' ) ) );

		if ( ! empty( $data['meta'] ) )
			HTML::tableSide( $data['meta'], TRUE, HTML::tag( 'h2', _x( 'Current Object Meta', 'Modules: Debug: QM Output', 'gnetwork' ) ) );

		if ( ! empty( $data['supports'] ) )
			HTML::tableSide( $data['supports'], TRUE, HTML::tag( 'h2', _x( 'Current Object Supports', 'Modules: Debug: QM Output', 'gnetwork' ) ) );

		// $this->after_non_tabular_output();
		$this->after_tabular_output();
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Commerce extends gNetwork\Module
{

	protected $key     = 'commerce';
	protected $network = FALSE;
	protected $ajax    = TRUE;
	protected $beta    = TRUE; // FIXME

	protected function setup_actions()
	{
		if ( ! WordPress::isPluginActive( 'woocommerce/woocommerce.php' ) )
			return FALSE;

		if ( $this->options['shetab_card_notes'] ) {
			$this->action( 'woocommerce_after_order_notes' );
			$this->action( 'woocommerce_checkout_process' );
			$this->action( 'woocommerce_checkout_update_order_meta' );
			$this->action( 'woocommerce_admin_order_data_after_billing_address' );
			$this->filter( 'woocommerce_email_order_meta_keys' );
		}

		$this->filter_false( 'woocommerce_allow_marketplace_suggestions' ); // @REF: https://wp.me/pBMYe-n1W
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Commerce', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'shetab_card_fields' => '0',
			'shetab_card_notes'  => '',
		];
	}

	public function default_settings()
	{
		return [
			'_shetab' => [
				[
					'field'       => 'shetab_card_fields',
					'title'       => _x( 'Shetab Card Fields', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Adds extra fields for Shetab Card information after order form.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'shetab_card_notes',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Shetab Card Notes', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Appears before Shetab Card information after order form.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'default'     => _x( 'Please enter your card information, to use in case of refund.', 'Modules: Commerce', 'gnetwork' ),
				],
			],
		];
	}

	// ADOPTED FROM: woo-iran-shetab-card-field by Farhad Sakhaei
	// v1.1 - 2018-12-11
	// @REF: https://wordpress.org/plugins/woo-iran-shetab-card-field/
	public function woocommerce_after_order_notes( $checkout )
	{
		echo $this->wrap_open( 'shetab_card' );

			HTML::h2( _x( 'Shetab Card', 'Modules: Commerce', 'gnetwork' ) );
			HTML::desc( $this->options['shetab_card_notes'] );

			woocommerce_form_field( 'shetab_card_number', [
				'type'        => 'text',
				'class'       => [ 'form-row-wide' ],
				'label'       => _x( 'Card Number', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'XXXX-XXXX-XXXX-XXXX', 'Modules: Commerce', 'gnetwork' ),
				'required'    => TRUE,
				'clear'       => TRUE,
			], $checkout->get_value( 'shetab_card_number' ) );

		echo '<br />';

			woocommerce_form_field( 'shetab_card_owner', [
				'type'        => 'text',
				'class'       => [ 'form-row-wide' ],
				'label'       => _x( 'Card Owner', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'Mohammad Mohammadi', 'Modules: Commerce', 'gnetwork' ),
				'required'    => TRUE,
				'clear'       => TRUE,
			], $checkout->get_value( 'shetab_card_owner' ) );

		echo '</div>';
	}

	public function woocommerce_checkout_process()
	{
		if ( ! $_POST['shetab_card_number']
			|| strlen( $_POST['shetab_card_number'] ) < 16
			|| ! is_numeric( $_POST['shetab_card_number'] ) )
				wc_add_notice( _x( 'Please enter numbers without dash and spaces.', 'Modules: Commerce', 'gnetwork' ), 'error' );

		if ( ! $_POST['shetab_card_owner']
			|| strlen($_POST['shetab_card_owner']) < 3 )
				wc_add_notice( _x( 'Please enter the name of the owner to check for correct card number.', 'Modules: Commerce', 'gnetwork' ), 'error' );
	}

	public function woocommerce_checkout_update_order_meta( $order_id )
	{
		if ( ! empty( $_POST['shetab_card_number'] ) )
			update_post_meta( $order_id, 'shetab_card_number',
				sanitize_text_field( $_POST['shetab_card_number'] ) );

		if ( ! empty( $_POST['shetab_card_owner'] ) )
			update_post_meta( $order_id, 'shetab_card_owner',
				sanitize_text_field( $_POST['shetab_card_owner'] ) );
	}

	public function woocommerce_admin_order_data_after_billing_address( $order )
	{
		if ( $id = $order->get_id() ) {

			vprintf( '<p><strong>%s</strong>: %s</p>', [
				_x( 'Setab Card Number', 'Modules: Commerce', 'gnetwork' ),
				get_post_meta( $id, 'shetab_card_number', TRUE ),
			] );

			vprintf( '<p><strong>%s</strong>: %s</p>', [
				_x( 'Setab Card Owner', 'Modules: Commerce', 'gnetwork' ),
				get_post_meta( $id, 'shetab_card_owner', TRUE ),
			] );
		}
	}

	public function woocommerce_email_order_meta_keys( $keys )
	{
		$keys['shetab_card_number'] = _x( 'Setab Card Number', 'Modules: Commerce', 'gnetwork' );
		$keys['shetab_card_owner']  = _x( 'Setab Card Owner', 'Modules: Commerce', 'gnetwork' );

		return $keys;
	}
}

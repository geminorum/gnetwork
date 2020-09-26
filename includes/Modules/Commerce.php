<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\Arraay;
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

		$this->action( 'init' );

		if ( $this->options['purchased_products'] ) {
			$this->filter( 'woocommerce_account_menu_items', 2, 40 );
			$this->action( 'woocommerce_account_purchased-products_endpoint' );
		}

		$this->filter_false( 'woocommerce_allow_marketplace_suggestions' ); // @REF: https://wp.me/pBMYe-n1W

		if ( ! defined( 'GNETWORK_WPLANG' ) )
			return;

		if ( $this->options['shetab_card_notes'] ) {
			$this->action( 'woocommerce_after_order_notes' );
			$this->action( 'woocommerce_checkout_process' );
			$this->action( 'woocommerce_checkout_update_order_meta' );
			$this->action( 'woocommerce_admin_order_data_after_billing_address' );
			$this->filter( 'woocommerce_email_order_meta_keys' );
		}

	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Commerce', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'purchased_products'       => '0',
			'purchased_products_title' => '',

			'shetab_card_fields' => '0',
			'shetab_card_notes'  => '',
		];
	}

	public function default_settings()
	{
		return [
			'_frontend' => [
				[
					'field'       => 'purchased_products',
					'title'       => _x( 'Purchased Products', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Displays recently purchased products on front-end account page.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'purchased_products_title',
					'type'        => 'text',
					'title'       => _x( 'Purchased Products Title', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Appears as title of the purchased products menu on front-end account page.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'placeholder' => _x( 'Purchased Products', 'Modules: Commerce: Default', 'gnetwork' ),
				],
			],
			'_fields' => [
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

	public function init()
	{
		if ( $this->options['purchased_products'] )
			add_rewrite_endpoint( 'purchased-products', EP_PAGES );
	}

	public function woocommerce_account_menu_items( $items, $endpoints )
	{
		return Arraay::insert( $items, [
			'purchased-products' => $this->get_option_fallback( 'purchased_products_title', _x( 'Purchased Products', 'Modules: Commerce: Default', 'gnetwork' ) ),
		], 'orders', 'after' );
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

	// @REF: https://rudrastyh.com/woocommerce/display-purchased-products.html
	public function woocommerce_account_purchased_products_endpoint()
	{
		global $wpdb;

		// this SQL query allows to get all the products purchased by the
		// current user in this example we sort products by date but you
		// can reorder them another way
		$ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT      itemmeta.meta_value
			FROM        {$wpdb->prefix}woocommerce_order_itemmeta itemmeta
			INNER JOIN  {$wpdb->prefix}woocommerce_order_items items
			            ON itemmeta.order_item_id = items.order_item_id
			INNER JOIN  {$wpdb->posts} orders
			            ON orders.ID = items.order_id
			INNER JOIN  {$wpdb->postmeta} ordermeta
			            ON orders.ID = ordermeta.post_id
			WHERE       itemmeta.meta_key = '_product_id'
			            AND ordermeta.meta_key = '_customer_user'
			            AND ordermeta.meta_value = %s
			ORDER BY    orders.post_date DESC
		", get_current_user_id() ) );

		// some orders may contain the same product,
		// but we do not need it twice
		$ids = array_unique( $ids );

		if ( ! empty( $ids ) ) {

			$products = new \WP_Query( [
				'post_type'   => 'product',
				'post_status' => 'publish',
				'orderby'     => 'post__in',
				'post__in'    => $ids,
			] );

			echo $this->wrap_open( [ 'woocommerce', 'columns-3' ] );
			woocommerce_product_loop_start();

			while ( $products->have_posts() ) {
				$products->the_post();
				wc_get_template_part( 'content', 'product' );
			}

			woocommerce_product_loop_end();
			woocommerce_reset_loop();
			wp_reset_postdata();
			echo '</div>';

		} else {

			HTML::desc( _x( 'Nothing purchased yet.', 'Modules: Commerce', 'gnetwork' ) );
		}
	}
}

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
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
		$this->action( 'admin_init' );

		if ( $this->options['purchased_products'] ) {
			$this->filter( 'woocommerce_account_menu_items', 2, 40 );
			$this->action( 'woocommerce_account_purchased-products_endpoint' );
		}

		$this->filter_false( 'woocommerce_allow_marketplace_suggestions' ); // @REF: https://wp.me/pBMYe-n1W

		if ( ! defined( 'GNETWORK_WPLANG' ) )
			return;

		if ( $this->options['shetab_card_fields'] ) {
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

			'hide_price_on_outofstock' => '0',
			'hide_price_on_shoploops'  => '0',
			'custom_string_instock'    => '',
			'custom_string_outofstock' => '',

			'fallback_empty_weight' => '0',
			'fallback_empty_length' => '0',
			'fallback_empty_width'  => '0',
			'fallback_empty_height' => '0',

			'gtin_field_title'   => '',
			'shetab_card_fields' => '0',
			'shetab_card_notes'  => '',
		];
	}

	public function default_settings()
	{
		return [
			'_front' => [
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
			'_overrides' => [
				[
					'field'       => 'hide_price_on_outofstock',
					'title'       => _x( 'Hide Out-of-Stock Prices', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Hides prices of products that are out of stock.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'hide_price_on_shoploops',
					'title'       => _x( 'Hide Prices on Shop Loops', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Hides prices of products on shop pages loops.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'custom_string_instock',
					'type'        => 'text',
					'title'       => _x( 'In Stock Custom String', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Changes default `In stock` message for customers. Leave empty to use defaults.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'placeholder' => __( 'In stock', 'woocommerce' ),
				],
				[
					'field'       => 'custom_string_outofstock',
					'type'        => 'text',
					'title'       => _x( 'Out-of-Stock Custom String', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Changes default `Out of stock` message for customers. Leave empty to use defaults.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'placeholder' => __( 'Out of stock', 'woocommerce' ),
				],
			],
			'_measurements' => [
				[
					'field'       => 'fallback_empty_weight',
					'type'        => 'number',
					'title'       => _x( 'Weight Empty Fallback', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Sets a fallback value on products with empty <b>weight</b> field. Leave empty to disable.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'fallback_empty_length',
					'type'        => 'number',
					'title'       => _x( 'Length Empty Fallback', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Sets a fallback value on products with empty <b>length</b> field. Leave empty to disable.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'fallback_empty_width',
					'type'        => 'number',
					'title'       => _x( 'Width Empty Fallback', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Sets a fallback value on products with empty <b>width</b> field. Leave empty to disable.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'fallback_empty_height',
					'type'        => 'number',
					'title'       => _x( 'Height Empty Fallback', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Sets a fallback value on products with empty <b>height</b> field. Leave empty to disable.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
			],
			'_fields' => [
				[
					'field'       => 'gtin_field_title',
					'type'        => 'text',
					'title'       => _x( 'GTIN Field', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Adds extra field for GTIN information on product. Leave empty to disable.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'placeholder' => _x( 'GTIN', 'Modules: Commerce: Default', 'gnetwork' ),
					'after'       => Settings::fieldAfterConstant( 'GNETWORK_COMMERCE_GTIN_METAKEY' ),
				],
			],
			'_checkout' => [
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

	public function settings_section_measurements()
	{
		Settings::fieldSection( _x( 'Measurements', 'Modules: Commerce: Settings', 'gnetwork' ) );
	}

	public function settings_section_fields()
	{
		Settings::fieldSection( _x( 'Fields', 'Modules: Commerce: Settings', 'gnetwork' ) );
	}

	public function settings_section_checkout()
	{
		Settings::fieldSection( _x( 'Checkout', 'Modules: Commerce: Settings', 'gnetwork' ) );
	}

	public function init()
	{
		if ( $this->options['purchased_products'] )
			add_rewrite_endpoint( 'purchased-products', EP_PAGES );

		if ( $this->options['hide_price_on_outofstock'] )
			$this->filter( [
				'woocommerce_get_price_html',
				'woocommerce_variable_price_html',
				'woocommerce_variable_sale_price_html',
			], 2, 12 );

		if ( is_admin() )
			return;

		if ( $this->options['hide_price_on_shoploops'] )
			// @REF: https://rudrastyh.com/woocommerce/remove-product-prices.html
			remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

		// @REF: https://wallydavid.com/set-a-default-length-width-height-weight-in-woocommerce/
		foreach ( [ 'weight', 'length', 'width', 'height' ] as $measurement )
			if ( $this->options['fallback_empty_'.$measurement] )
				$this->filter( [
					'woocommerce_product_get_'.$measurement,
					'woocommerce_product_variation_get_'.$measurement,
				], 2, 8 );

		if ( $this->options['custom_string_outofstock'] || $this->options['custom_string_instock'] )
			$this->filter( 'woocommerce_get_availability_text', 2, 8 );
	}

	public function admin_init()
	{
		if ( $this->options['gtin_field_title'] ) {
			$this->action( 'woocommerce_product_options_inventory_product_data', 0, 10, 'gtin' );
			$this->action( 'woocommerce_process_product_meta', 2, 10, 'gtin' );
			$this->action( 'woocommerce_product_after_variable_attributes', 3, 10, 'gtin' );
			$this->action( 'woocommerce_save_product_variation', 2, 10, 'gtin' );
		}
	}

	public function woocommerce_account_menu_items( $items, $endpoints )
	{
		return Arraay::insert( $items, [
			'purchased-products' => $this->get_option_fallback( 'purchased_products_title', _x( 'Purchased Products', 'Modules: Commerce: Default', 'gnetwork' ) ),
		], 'orders', 'after' );
	}

	public function woocommerce_get_price_html( $price, $product )
	{
		return $product->is_in_stock() ? $price : '';
	}

	public function woocommerce_product_get_weight( $value, $product )
	{
		return empty( $value ) ? $this->options['fallback_empty_weight'] : $value;
	}

	public function woocommerce_product_get_length( $value, $product )
	{
		return empty( $value ) ? $this->options['fallback_empty_length'] : $value;
	}

	public function woocommerce_product_get_width( $value, $product )
	{
		return empty( $value ) ? $this->options['fallback_empty_width'] : $value;
	}

	public function woocommerce_product_get_height( $value, $product )
	{
		return empty( $value ) ? $this->options['fallback_empty_height'] : $value;
	}

	public function woocommerce_get_availability_text( $availability, $product )
	{
		if ( $this->options['custom_string_outofstock'] && ! $product->is_in_stock() )
			return trim( $this->options['custom_string_outofstock'] );

		if ( $this->options['custom_string_instock'] && $availability == __( 'In stock', 'woocommerce' ) )
			return trim( $this->options['custom_string_instock'] );

		return $availability;
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

	public function woocommerce_product_options_inventory_product_data_gtin()
	{
		woocommerce_wp_text_input( [
			'id'          => $this->base.'-product_gtin',
			'label'       => $this->options['gtin_field_title'],
			'desc_tip'    => TRUE,
			'description' => _x( 'Enter the Global Trade Item Number (UPC, EAN, ISBN)', 'Modules: Commerce', 'gnetwork' ),
			'value'       => get_post_meta( get_the_ID(), GNETWORK_COMMERCE_GTIN_METAKEY, TRUE ),
		] );
	}

	public function woocommerce_process_product_meta_gtin( $post_id, $post )
	{
		$key = $this->classs( 'product_gtin' );

		if ( array_key_exists( $key, $_POST ) )
			update_post_meta( $post_id, GNETWORK_COMMERCE_GTIN_METAKEY, trim( $_POST[$key] ) );
	}

	public function woocommerce_product_after_variable_attributes_gtin( $loop, $variation_data, $variation )
	{
		$key = $this->classs( 'product_gtin' );

		woocommerce_wp_text_input( [
			'id'          => sprintf( '%s-%s', $key, $variation->ID ),
			'name'        => sprintf( '%s[%s]', $key, $variation->ID ),
			'label'       => $this->options['gtin_field_title'],
			'desc_tip'    => TRUE,
			'description' => _x( 'Unique GTIN for variation? Enter it here.', 'Modules: Commerce', 'gnetwork' ),
			'value'       => get_post_meta( $variation->ID, GNETWORK_COMMERCE_GTIN_METAKEY, TRUE ),
		] );
	}

	public function woocommerce_save_product_variation_gtin( $variation_id, $i )
	{
		$key = $this->classs( 'product_gtin' );

		if ( array_key_exists( $variation_id, $_POST[$key] ) )
			update_post_meta( $variation_id, GNETWORK_COMMERCE_GTIN_METAKEY, trim( $_POST[$key][$variation_id] ) );
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

<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\Validation;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\User as WPUser;

class Commerce extends gNetwork\Module
{

	protected $key     = 'commerce';
	protected $network = FALSE;
	protected $ajax    = TRUE;

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
			$this->action( 'woocommerce_before_order_notes', 1, 10, 'shetab' );
			$this->action( 'woocommerce_checkout_process', 0, 10, 'shetab' );
			$this->action( 'woocommerce_checkout_update_order_meta', 1, 10, 'shetab' );
			$this->action( 'woocommerce_admin_order_data_after_billing_address', 1, 10, 'shetab' );
			$this->filter( 'woocommerce_email_order_meta_keys', 1, 10, 'shetab' );
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

			'gtin_field_title'        => '',
			'mobile_field'            => '1',
			'ssn_field'               => '0',
			'remove_order_notes'      => '0',
			'order_notes_label'       => '',
			'order_notes_placeholder' => '',
			'shetab_card_fields'      => '0',
			'shetab_card_notes'       => '',
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
					'field'       => 'mobile_field',
					'title'       => _x( 'Mobile Number Field', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Adds extra required field for mobile number after checkout form.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'default'     => '1',
				],
				[
					'field'       => 'ssn_field',
					'title'       => _x( 'Social Security Number Field', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Adds extra required field for social security number after checkout form.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'remove_order_notes',
					'title'       => _x( 'Remove Order Notes', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Removes default `Order notes` from checkout form.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'order_notes_label',
					'type'        => 'text',
					'title'       => _x( 'Order Notes Label', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Changes default `Order notes` label for customers. Leave empty to use defaults.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'placeholder' => __( 'Order notes', 'woocommerce' ),
				],
				[
					'field'       => 'order_notes_placeholder',
					'type'        => 'text',
					'title'       => _x( 'Order Notes Placeholder', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Changes default `Order notes` placeholder for customers. Leave empty to use defaults.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'placeholder' => __( 'Notes about your order, e.g. special notes for delivery.', 'woocommerce' ),
				],
				[
					'field'       => 'shetab_card_fields',
					'title'       => _x( 'Shetab Card Fields', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Adds extra fields for Shetab Card information after checkout form.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'shetab_card_notes',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Shetab Card Notes', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Appears before Shetab Card information after checkout form.', 'Modules: Commerce: Settings', 'gnetwork' ),
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

		$this->filter( 'woocommerce_email_order_meta_fields', 3 );

		if ( is_admin() )
			return;

		if ( $this->options['gtin_field_title'] )
			$this->action( 'woocommerce_product_meta_start', 0, 10, 'gtin' );

		$this->filter( 'woocommerce_checkout_fields' );
		$this->filter( 'woocommerce_checkout_posted_data' );
		$this->action( 'woocommerce_after_checkout_validation', 2 );
		$this->action( 'woocommerce_checkout_update_customer', 2 );
		$this->action( 'woocommerce_checkout_create_order', 2 );

		$this->action( 'woocommerce_edit_account_form_start' );
		$this->action( 'woocommerce_save_account_details' );
		$this->action( 'woocommerce_save_account_details_errors', 2 );
		$this->filter( 'woocommerce_save_account_details_required_fields' );
		$this->action( 'woocommerce_register_form' );
		$this->action( 'woocommerce_created_customer' );

		if ( $this->options['remove_order_notes'] )
			$this->filter_false( 'woocommerce_enable_order_notes_field' );

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
			$this->action( 'woocommerce_product_options_sku', 0, 10, 'gtin' );
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

	// TODO: re-order city and state
	// @REF: https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
	// NOTE: wc auto-stores meta with `billing` or `shipping` prefixes, we use `customer` to prevent this
	public function woocommerce_checkout_fields( $fields )
	{
		if ( $this->options['mobile_field'] ) {

			$fields['billing']['billing_phone']['class'] = [ 'form-row-first', 'phone' ];
			$fields['billing']['billing_phone']['placeholder'] = _x( 'For calling on land-line', 'Modules: Commerce', 'gnetwork' );
			$fields['billing']['billing_phone']['input_class'] = [ 'ltr', 'rtl-placeholder' ];

			$mobile = is_user_logged_in() ? get_user_meta( get_current_user_id(), GNETWORK_COMMERCE_MOBILE_METAKEY, TRUE ) : FALSE;

			$fields['billing']['customer_mobile'] = [
				'type'        => 'tel',
				'class'       => [ 'form-row-last', 'mobile' ],
				'input_class' => [ 'ltr', 'rtl-placeholder' ],
				'label'       => _x( 'Mobile', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'For short message purposes', 'Modules: Commerce', 'gnetwork' ),
				'priority'    => 105, // after the `billing_phone` with priority `100`
				'required'    => TRUE,
				'default'     => $mobile ?: '',
			];
		}

		if ( $this->options['ssn_field'] ) {

			$ssn = is_user_logged_in() ? get_user_meta( get_current_user_id(), GNETWORK_COMMERCE_SSN_METAKEY, TRUE ) : FALSE;

			$fields['billing']['customer_ssn'] = [
				'type'        => 'text',
				'class'       => [ 'form-row-wide', 'ssn' ],
				'input_class' => [ 'ltr', 'rtl-placeholder' ],
				'label'       => _x( 'SSN', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'Social Security Number', 'Modules: Commerce', 'gnetwork' ),
				'priority'    => 25, // before the `company` with priority `30`
				'required'    => TRUE,
				'default'     => $ssn ?: '',
			];
		}

		if ( $this->options['order_notes_label'] )
			$fields['order']['order_comments']['label'] = $this->options['order_notes_label'];

		if ( $this->options['order_notes_placeholder'] )
			$fields['order']['order_comments']['placeholder'] = $this->options['order_notes_placeholder'];

		return $fields;
	}

	private function sanitize_mobile_field( $input )
	{
		return wc_sanitize_phone_number( Number::intval( $input, FALSE ) );
	}

	private function sanitize_ssn_field( $input )
	{
		return preg_replace( '/[^\d]/', '', Number::intval( $input, FALSE ) );
	}

	// alternatively we can use `woocommerce_process_checkout_field_{$key}` filter
	public function woocommerce_checkout_posted_data( $data )
	{
		if ( $this->options['mobile_field'] )
			$data['customer_mobile'] = $this->sanitize_mobile_field( $data['customer_mobile'] );

		if ( $this->options['ssn_field'] )
			$data['customer_ssn'] = $this->sanitize_ssn_field( $data['customer_ssn'] );

		return $data;
	}

	public function woocommerce_after_checkout_validation( $data, $errors )
	{
		if ( $this->options['mobile_field'] ) {

			if ( empty( $data['customer_mobile'] ) )
				$errors->add( 'mobile_empty', _x( 'Mobile Number cannot be empty.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( ! Validation::isMobileNumber( $data['customer_mobile'] ) )
				$errors->add( 'mobile_invalid', _x( 'Mobile Number is not valid.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( ! is_user_logged_in() && WPUser::getIDbyMeta( GNETWORK_COMMERCE_MOBILE_METAKEY, $data['customer_mobile'] ) )
				$errors->add( 'mobile_registered', _x( 'Mobile Number is already registered.', 'Modules: Commerce', 'gnetwork' ) );
		}

		if ( $this->options['ssn_field'] ) {

			if ( empty( $data['customer_ssn'] ) )
				$errors->add( 'ssn_empty', _x( 'Social Security Number cannot be empty.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( ! GNETWORK_DISABLE_SSN_CHECKS && ! Validation::isSSN( $data['customer_ssn'] ) )
				$errors->add( 'ssn_invalid', _x( 'Social Security Number is not valid.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( ! is_user_logged_in() && WPUser::getIDbyMeta( GNETWORK_COMMERCE_SSN_METAKEY, $data['customer_ssn'] ) )
				$errors->add( 'ssn_registered', _x( 'Social Security Number is already registered.', 'Modules: Commerce', 'gnetwork' ) );
		}
	}

	public function woocommerce_checkout_update_customer( $customer, $data )
	{
		if ( $this->options['mobile_field'] )
			$customer->update_meta_data( GNETWORK_COMMERCE_MOBILE_METAKEY, $data['customer_mobile'] );

		if ( $this->options['ssn_field'] )
			$customer->update_meta_data( GNETWORK_COMMERCE_SSN_METAKEY, $data['customer_ssn'] );
	}

	public function woocommerce_checkout_create_order( $order, $data )
	{
		if ( $this->options['mobile_field'] )
			$order->update_meta_data( '_customer_mobile', $data['customer_mobile'] );

		if ( $this->options['ssn_field'] )
			$order->update_meta_data( '_customer_ssn', $data['customer_ssn'] );
	}

	// @REF: https://docs.woocommerce.com/document/add-a-custom-field-in-an-order-to-the-emails/
	// @SEE: https://rudrastyh.com/woocommerce/order-meta-in-emails.html
	// MAYBE: for better control/linking: use `woocommerce_email_order_meta` hook
	public function woocommerce_email_order_meta_fields( $fields, $sent_to_admin, $order )
	{
		if ( $this->options['mobile_field'] ) {

			$meta = get_post_meta( $order->id, '_customer_mobile', TRUE );

			if ( $meta && ( $mobile = wc_format_phone_number( $meta ) ) )
				$fields[] = [
					'label' => _x( 'Mobile Number', 'Modules: Commerce', 'gnetwork' ),
					'value' => Number::localize( $mobile ),
				];
		}

		if ( $this->options['ssn_field'] ) {

			if ( $meta = get_post_meta( $order->id, '_customer_ssn', TRUE ) )
				$fields[] = [
					'label' => _x( 'Social Security Number', 'Modules: Commerce', 'gnetwork' ),
					'value' => Number::localize( $meta ),
				];
		}

		return $fields;
	}

	// @REF: https://rudrastyh.com/woocommerce/edit-account-fields.html
	public function woocommerce_edit_account_form_start()
	{
		if ( $this->options['mobile_field'] ) {
			woocommerce_form_field( 'account_mobile', [
				'type'        => 'tel',
				'class'       => [ 'form-row-wide', 'mobile' ],
				'input_class' => [ 'ltr', 'rtl-placeholder' ],
				'label'       => _x( 'Mobile', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'For short message purposes', 'Modules: Commerce', 'gnetwork' ),
				'required'    => TRUE,
				'clear'       => TRUE,
			], get_user_meta( get_current_user_id(), GNETWORK_COMMERCE_MOBILE_METAKEY, TRUE ) );

			wc_enqueue_js( "$('p#account_mobile_field').insertAfter($('input#account_email').parent());" );
		}

		if ( $this->options['ssn_field'] ) {
			woocommerce_form_field( 'account_ssn', [
				'type'        => 'tel',
				'class'       => [ 'form-row-wide', 'ssn' ],
				'input_class' => [ 'ltr', 'rtl-placeholder' ],
				'label'       => _x( 'SSN', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'Social Security Number', 'Modules: Commerce', 'gnetwork' ),
				'required'    => TRUE,
				'clear'       => TRUE,
			], get_user_meta( get_current_user_id(), GNETWORK_COMMERCE_SSN_METAKEY, TRUE ) );

			wc_enqueue_js( "$('p#account_ssn_field').insertAfter($('input#account_display_name').parent());" );
		}
	}

	public function woocommerce_save_account_details( $user_id )
	{
		if ( $this->options['ssn_field'] && array_key_exists( 'account_ssn', $_POST ) )
			update_user_meta( $user_id, GNETWORK_COMMERCE_SSN_METAKEY, $this->sanitize_ssn_field( sanitize_text_field( $_POST['account_ssn'] ) ) );

		if ( $this->options['mobile_field'] && array_key_exists( 'account_mobile', $_POST ) )
			update_user_meta( $user_id, GNETWORK_COMMERCE_MOBILE_METAKEY, $this->sanitize_mobile_field( sanitize_text_field( $_POST['account_mobile'] ) ) );
	}

	public function woocommerce_save_account_details_errors( &$errors, &$user )
	{
		if ( $this->options['mobile_field'] ) {

			$mobile = wc_clean( wp_unslash( $_POST['account_mobile'] ) );

			if ( empty( $mobile ) )
				$errors->add( 'mobile_empty', _x( 'Mobile Number cannot be empty.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( ! Validation::isMobileNumber( $mobile ) )
				$errors->add( 'mobile_invalid', _x( 'Mobile Number is not valid.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( WPUser::getIDbyMeta( GNETWORK_COMMERCE_MOBILE_METAKEY, $mobile ) )
				$errors->add( 'mobile_registered', _x( 'Mobile Number is already registered.', 'Modules: Commerce', 'gnetwork' ) );
		}

		if ( $this->options['ssn_field'] ) {

			$ssn = wc_clean( wp_unslash( $_POST['account_ssn'] ) );

			if ( empty( $ssn ) )
				$errors->add( 'ssn_empty', _x( 'Social Security Number cannot be empty.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( ! GNETWORK_DISABLE_SSN_CHECKS && ! Validation::isSSN( $ssn ) )
				$errors->add( 'ssn_invalid', _x( 'Social Security Number is not valid.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( WPUser::getIDbyMeta( GNETWORK_COMMERCE_SSN_METAKEY, $ssn ) )
				$errors->add( 'ssn_registered', _x( 'Social Security Number is already registered.', 'Modules: Commerce', 'gnetwork' ) );
		}
	}

	public function woocommerce_save_account_details_required_fields( $fields )
	{
		$extra = [];

		if ( $this->options['ssn_field'] )
			$extra['account_ssn'] = _x( 'SSN', 'Modules: Commerce', 'gnetwork' );

		if ( $this->options['mobile_field'] )
			$extra['account_mobile'] = _x( 'Mobile', 'Modules: Commerce', 'gnetwork' );

		return array_merge( $fields, $extra );
	}

	public function woocommerce_register_form()
	{
		if ( $this->options['ssn_field'] )
			woocommerce_form_field( 'account_ssn', [
				'type'        => 'text',
				'class'       => [ 'form-row-wide', 'ssn' ],
				'input_class' => [ 'ltr', 'rtl-placeholder' ],
				'label'       => _x( 'SSN', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'Social Security Number', 'Modules: Commerce', 'gnetwork' ),
				'required'    => TRUE,
				'clear'       => TRUE,
			] );

		if ( $this->options['mobile_field'] )
			woocommerce_form_field( 'account_mobile', [
				'type'        => 'tel',
				'class'       => [ 'form-row-wide', 'mobile' ],
				'input_class' => [ 'ltr', 'rtl-placeholder' ],
				'label'       => _x( 'Mobile', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'For short message purposes', 'Modules: Commerce', 'gnetwork' ),
				'required'    => TRUE,
				'clear'       => TRUE,
			] );
	}

	public function woocommerce_created_customer( $customer_id )
	{
		$this->woocommerce_save_account_details( $customer_id );
	}

	// ADOPTED FROM: woo-iran-shetab-card-field by Farhad Sakhaei
	// v1.1 - 2018-12-11
	// @REF: https://wordpress.org/plugins/woo-iran-shetab-card-field/
	public function woocommerce_before_order_notes_shetab( $checkout )
	{
		// TODO: must move to billing
		// TODO: strip dash and spaces on sanitize

		echo $this->wrap_open( 'shetab-card' );

			HTML::h3( _x( 'Shetab Card', 'Modules: Commerce', 'gnetwork' ) );
			HTML::desc( $this->options['shetab_card_notes'] );

			woocommerce_form_field( 'shetab_card_owner', [
				'type'        => 'text',
				'class'       => [ 'form-row-first', 'shetab-card-owner' ],
				'label'       => _x( 'Card Owner', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'Mohammad Mohammadi', 'Modules: Commerce', 'gnetwork' ),
				'required'    => TRUE,
			], $checkout->get_value( 'shetab_card_owner' ) );

			woocommerce_form_field( 'shetab_card_number', [
				'type'        => 'text',
				'class'       => [ 'form-row-last', 'shetab-card-number' ],
				'input_class' => [ 'ltr' ],
				'label'       => _x( 'Card Number', 'Modules: Commerce', 'gnetwork' ),
				'placeholder' => _x( 'XXXX-XXXX-XXXX-XXXX', 'Modules: Commerce', 'gnetwork' ),
				'required'    => TRUE,
			], $checkout->get_value( 'shetab_card_number' ) );

		echo '</div>';
	}

	public function woocommerce_checkout_process_shetab()
	{
		if ( ! $_POST['shetab_card_number']
			|| strlen( $_POST['shetab_card_number'] ) < 16
			|| ! is_numeric( $_POST['shetab_card_number'] ) )
				wc_add_notice( _x( 'Please enter Shetab card numbers correctly.', 'Modules: Commerce', 'gnetwork' ), 'error' );

		if ( ! $_POST['shetab_card_owner']
			|| strlen($_POST['shetab_card_owner']) < 3 )
				wc_add_notice( _x( 'Please enter the name of the owner to check for correct Shetab card number.', 'Modules: Commerce', 'gnetwork' ), 'error' );
	}

	public function woocommerce_checkout_update_order_meta_shetab( $order_id )
	{
		if ( ! empty( $_POST['shetab_card_number'] ) )
			update_post_meta( $order_id, 'shetab_card_number',
				sanitize_text_field( $_POST['shetab_card_number'] ) );

		if ( ! empty( $_POST['shetab_card_owner'] ) )
			update_post_meta( $order_id, 'shetab_card_owner',
				sanitize_text_field( $_POST['shetab_card_owner'] ) );
	}

	public function woocommerce_admin_order_data_after_billing_address_shetab( $order )
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

	// FIXME: Deprecated wc filter
	public function woocommerce_email_order_meta_keys_shetab( $keys )
	{
		$keys['shetab_card_number'] = _x( 'Setab Card Number', 'Modules: Commerce', 'gnetwork' );
		$keys['shetab_card_owner']  = _x( 'Setab Card Owner', 'Modules: Commerce', 'gnetwork' );

		return $keys;
	}

	public function woocommerce_product_options_sku_gtin()
	{
		woocommerce_wp_text_input( [
			'id'          => $this->base.'-product_gtin',
			'label'       => $this->options['gtin_field_title'],
			'description' => _x( 'Enter the Global Trade Item Number (UPC, EAN, ISBN)', 'Modules: Commerce', 'gnetwork' ),
			'value'       => get_post_meta( get_the_ID(), GNETWORK_COMMERCE_GTIN_METAKEY, TRUE ),
			'desc_tip'    => TRUE,
		] );
	}

	public function woocommerce_product_meta_start_gtin()
	{
		global $product;

		if ( $meta = get_post_meta( $product->get_id(), GNETWORK_COMMERCE_GTIN_METAKEY, TRUE ) )
			echo '<span class="gtin_wrapper">'.sprintf( '%s: ', $this->options['gtin_field_title'] ).'<span class="gtin">'.esc_html( $meta ).'</span></span>';
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

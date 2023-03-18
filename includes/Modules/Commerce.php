<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\Validation;
use geminorum\gNetwork\Core\WordPress;
use geminorum\gNetwork\WordPress\PostType as WPPostType;
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
		$this->action( 'admin_bar_menu', 1, 35 );

		$this->filter( 'exclude_from_sitemap_by_post_ids', 1, 12, FALSE, 'wpseo' ); // @REF: https://github.com/Yoast/wpseo-woocommerce/pull/260
		$this->filter_false( 'woocommerce_allow_marketplace_suggestions' ); // @REF: https://wp.me/pBMYe-n1W
		$this->filter_false( 'woocommerce_background_image_regeneration' ); // @REF: https://github.com/woocommerce/woocommerce/wiki/Thumbnail-Image-Regeneration-in-3.3
		$this->filter_true( 'woocommerce_prevent_automatic_wizard_redirect' ); // @REF: https://stackoverflow.com/a/65476167

		$this->filter_true( 'pre_transient_pws_notice_all' ); // persian woocommerce shipping notices!

		if ( is_readable( GNETWORK_DIR.'includes/Misc/CommercePluggable.php' ) )
			require_once GNETWORK_DIR.'includes/Misc/CommercePluggable.php';

		if ( ! defined( 'GNETWORK_WPLANG' ) )
			return;

	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Commerce', 'Modules: Menu Name', 'gnetwork' ) );
		$this->register_tool( _x( 'Products', 'Modules: Menu Name', 'gnetwork' ), 'products', 12, 'edit_others_products' );
	}

	public function default_options()
	{
		return [
			'hide_price_on_outofstock' => '0',
			'hide_price_on_shoploops'  => '0',
			'hide_result_count'        => '0',   // Pluggable
			'hide_catalog_ordering'    => '0',
			'custom_string_instock'    => '',
			'custom_string_outofstock' => '',
			'quantity_price_preview'   => '0',
			'mobile_field'             => '1',
			'no_products_found'        => '0',
		];
	}

	public function default_settings()
	{
		return [
			// move to wc-tweaks
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
					'field'       => 'hide_result_count',
					'title'       => _x( 'Hide Result Count', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Hides the result count text on shop pages loops.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'hide_catalog_ordering',
					'title'       => _x( 'Hide Catalog Ordering', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Hides the product sorting options on shop pages loops.', 'Modules: Commerce: Settings', 'gnetwork' ),
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
			'_fields' => [
				[
					'field'       => 'quantity_price_preview',
					'title'       => _x( 'Quantity &times; Price', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Calculates subtotal on quantity increment by customer.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'mobile_field',
					'title'       => _x( 'Mobile Number Field', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Adds extra required field for mobile number after checkout form.', 'Modules: Commerce: Settings', 'gnetwork' ),
					'default'     => '1',
				],
			],
			'_misc' => [
				[
					'field'       => 'no_products_found',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'No Products Found', 'Modules: Commerce: Settings', 'gnetwork' ),
					'description' => _x( 'Adds a message on no products found page.', 'Modules: Commerce: Settings', 'gnetwork' ),
				],
			],
		];
	}

	public function settings_section_fields()
	{
		Settings::fieldSection( _x( 'Fields', 'Modules: Commerce: Settings', 'gnetwork' ) );
	}

	public function tools( $sub = NULL, $key = NULL )
	{
		parent::tools( $sub, 'products' );
	}

	protected function tools_buttons( $sub = NULL )
	{
		$this->register_button( 'recalculate_stocks', _x( 'Recalculate Stocks', 'Modules: Commerce', 'gnetwork' ) );
		// $this->register_button( 'cleanup_attributes', _x( 'Cleanup Attributes', 'Modules: Commerce', 'gnetwork' ) );
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( 'products' == $sub ) {

			if ( ! empty( $_POST ) && 'bulk' == $_POST['action'] ) {

				$this->check_referer( $sub, 'tools' );

				if ( self::isTablelistAction( 'recalculate_stocks', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( ! $product = wc_get_product( $post_id ) )
							continue;

						$meta = get_post_meta( $product->get_id(), '_stock', TRUE );

						if ( wc_update_product_stock( $product, $meta ) )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'synced',
						'count'   => $count,
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );

				} else if ( self::isTablelistAction( 'cleanup_attributes', TRUE ) ) {

					// FIXME

				} else {

					$this->actions( 'products_do_bulk_actions',
						self::req( 'table_action', NULL ),
						self::req( '_cb', NULL )
					);

					WordPress::redirectReferer( [
						'message' => 'wrong',
						'limit'   => self::limit(),
						'paged'   => self::paged(),
					] );
				}
			}
		}
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		list( $posts, $pagination ) = self::getTablelistPosts( [], [], 'product' );

		$pagination['actions'] = $this->filters( 'products_list_bulk_actions', [], $posts, $pagination );
		$pagination['before'][] = self::filterTablelistSearch();

		return HTML::tableList( [
			'_cb'    => 'ID',
			'ID'     => _x( 'ID', 'Modules: Commerce: Column Title', 'gnetwork' ),
			'status' => [
				'title'    => _x( 'Status', 'Modules: Commerce: Column Title', 'gnetwork' ),
				'args'     => [ 'statuses' => WPPostType::getStatuses() ],
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					if ( ! $product = wc_get_product( $row->ID ) )
						return Utilities::htmlEmpty();

					$status = $product->get_status();

					if ( isset( $column['args']['statuses'][$status] ) )
						return $column['args']['statuses'][$status];

					return HTML::tag( 'code', $status );
				},
			],
			'stock' => [
				'title'    => _x( 'Stock', 'Modules: Commerce: Column Title', 'gnetwork' ),
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					if ( ! $product = wc_get_product( $row->ID ) )
						return Utilities::htmlEmpty();

					return '<span style="color:'.( $product->is_in_stock() ? 'green' : 'red' ).'">'
						.Number::format( $product->get_stock_quantity() ).'</span>';
				},
			],
			'title' => [
				'title'    => _x( 'Title', 'Modules: Commerce: Column Title', 'gnetwork' ),
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					return Utilities::getPostTitle( $row );
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Woocommerce Products', 'Modules: Commerce', 'gnetwork' ) ),
			'empty'      => HTML::warning( _x( 'No Products!', 'Modules: Commerce', 'gnetwork' ) ),
			'pagination' => $pagination,
		] );
	}

	public function init()
	{
		if ( $this->options['hide_price_on_outofstock'] )
			$this->filter( [
				'woocommerce_get_price_html',
				'woocommerce_variable_price_html',
				'woocommerce_variable_sale_price_html',
			], 2, 12 );

		$this->action( 'admin_order_data_after_order_details', 1, 1, FALSE, 'woocommerce' );
		$this->action( 'admin_order_data_after_billing_address', 1, 1, FALSE, 'woocommerce' );
		$this->filter( 'woocommerce_email_order_meta_fields', 3 );

		if ( is_admin() )
			return;

		if ( $this->options['no_products_found'] )
			$this->action( 'woocommerce_no_products_found', 0, 15 );

		if ( $this->options['hide_catalog_ordering'] )
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

		if ( $this->options['quantity_price_preview'] )
			$this->action( 'woocommerce_after_add_to_cart_button' );

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

		$this->filter( [
			'woocommerce_process_myaccount_field_shipping_postcode',
			'woocommerce_process_myaccount_field_billing_postcode',
			'woocommerce_process_myaccount_field_billing_phone',
		] );

		if ( $this->options['hide_price_on_shoploops'] )
			// @REF: https://rudrastyh.com/woocommerce/remove-product-prices.html
			remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

		if ( $this->options['custom_string_outofstock'] || $this->options['custom_string_instock'] )
			$this->filter( 'woocommerce_get_availability_text', 2, 8 );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		if ( is_admin() )
			return;

		$parent = $this->classs();

		$wp_admin_bar->add_node( [
			'id'    => $parent,
			'title' => AdminBar::getIcon( 'store' ),
			'href'  => admin_url( 'edit.php?post_type=product' ),
			'meta'  => [ 'title' => __( 'WooCommerce', 'woocommerce' ) ],
		] );

		if ( current_user_can( 'edit_others_shop_orders' ) )
			$wp_admin_bar->add_node( [
				'parent' => $parent,
				'id'     => $this->classs( 'shop_orders' ),
				'title'  => __( 'Orders', 'woocommerce' ),
				'href'   => admin_url( 'edit.php?post_type=shop_order' ),
			] );

		if ( current_user_can( 'edit_others_products' ) )
			$wp_admin_bar->add_node( [
				'parent' => $parent,
				'id'     => $this->classs( 'products' ),
				'title'  => __( 'Products', 'woocommerce' ),
				'href'   => admin_url( 'edit.php?post_type=product' ),
			] );

		if ( current_user_can( 'manage_product_terms' ) ) {

			$wp_admin_bar->add_node( [
				'parent' => $parent,
				'id'     => $this->classs( 'product_cat' ),
				'title'  => get_taxonomy( 'product_cat' )->labels->menu_name,
				'href'   => admin_url( 'edit-tags.php?post_type=product&taxonomy=product_cat' ),
			] );

			$attributes = $this->classs( 'attributes' );

			$wp_admin_bar->add_node( [
				'parent' => $parent,
				'id'     => $attributes,
				'title'  => __( 'Attributes', 'woocommerce' ),
				'href'   => admin_url( 'edit.php?post_type=product&page=product_attributes' ),
			] );

			foreach ( wc_get_attribute_taxonomies() as $attribute )
				$wp_admin_bar->add_node( [
					'parent' => $attributes,
					'id'     => $this->classs( 'attributes', $attribute->attribute_name ),
					'title'  => $attribute->attribute_label,
					'href'   => admin_url( 'edit-tags.php?post_type=product&taxonomy=pa_'.$attribute->attribute_name ),
				] );
		}

		if ( current_user_can( 'manage_woocommerce' ) )
			$wp_admin_bar->add_node( [
				'parent' => $parent,
				'id'     => $this->classs( 'status' ),
				'title'  => __( 'Status', 'woocommerce' ),
				'href'   => admin_url( 'admin.php?page=wc-status' ),
			] );
	}

	public function woocommerce_get_price_html( $price, $product )
	{
		return $product->is_in_stock() ? $price : '';
	}

	// @REF: https://www.businessbloomer.com/woocommerce-calculate-subtotal-on-quantity-increment-single-product/
	// @SEE: https://wordpress.org/plugins/woo-product-price-x-quantity-preview/
	// FIXME: get currency placement form wc settings
	public function woocommerce_after_add_to_cart_button()
	{
		global $product;

		$price    = $product->get_price();
		$currency = get_woocommerce_currency_symbol();
		/* translators: %s: price x quantity total */
		$string   = sprintf( _x( 'Total: %s', 'Modules: Commerce', 'gnetwork' ), '<span></span>' );

		echo '<div id="subtot" style="display:inline-block;margin:0 1rem;">'.$string.'</div>';

		wc_enqueue_js( "$('[name=quantity]').on('input change', function() {
			var qty = $(this).val();
			var price = '" . esc_js( $price ) . "';
			// var price_string = (price*qty).toFixed();
			var price_string = parseFloat(price*qty);
			$('#subtot > span').html(price_string+' '+'" . esc_js( $currency ) . "');
		}).trigger('change');" );
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
			// $fields['billing']['billing_phone']['custom_attributes']['pattern'] = Validation::getMobileHTMLPattern();

			$mobile = is_user_logged_in() ? get_user_meta( get_current_user_id(), GNETWORK_COMMERCE_MOBILE_METAKEY, TRUE ) : FALSE;

			$fields['billing']['customer_mobile'] = [
				'type'              => 'tel',
				'class'             => [ 'form-row-last', 'mobile', 'validate-phone' ],
				'input_class'       => [ 'ltr', 'rtl-placeholder' ],
				'label'             => _x( 'Mobile', 'Modules: Commerce', 'gnetwork' ),
				'placeholder'       => _x( 'For short message purposes', 'Modules: Commerce', 'gnetwork' ),
				'priority'          => 105, // after the `billing_phone` with priority `100`
				'required'          => TRUE,
				'default'           => $mobile ?: '',
				'custom_attributes' => [ 'pattern' => Validation::getMobileHTMLPattern() ],
			];
		}

		return $fields;
	}

	private function sanitize_mobile_field( $input )
	{
		return wc_sanitize_phone_number( Number::intval( $input, FALSE ) );
	}

	// alternatively we can use `woocommerce_process_checkout_field_{$key}` filter
	public function woocommerce_checkout_posted_data( $data )
	{
		if ( $this->options['mobile_field'] && ! empty( $data['customer_mobile'] ) )
			$data['customer_mobile'] = $this->sanitize_mobile_field( $data['customer_mobile'] );

		if ( ! empty( $data['shipping_postcode'] ) )
			$data['shipping_postcode'] = Number::intval( $data['shipping_postcode'], FALSE );

		if ( ! empty( $data['billing_postcode'] ) )
			$data['billing_postcode'] = Number::intval( $data['billing_postcode'], FALSE );

		if ( ! empty( $data['billing_phone'] ) )
			$data['billing_phone'] = Number::intval( $data['billing_phone'], FALSE );

		return $data;
	}

	public function woocommerce_after_checkout_validation( $data, $errors )
	{
		if ( $this->options['mobile_field'] ) {

			if ( empty( $data['customer_mobile'] ) )
				$errors->add( 'mobile_empty',
					_x( 'Mobile Number cannot be empty.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( ! Validation::isMobileNumber( $data['customer_mobile'] ) )
				$errors->add( 'mobile_invalid',
					_x( 'Mobile Number is not valid.', 'Modules: Commerce', 'gnetwork' ) );

			else if ( ! is_user_logged_in() && WPUser::getIDbyMeta( GNETWORK_COMMERCE_MOBILE_METAKEY, $data['customer_mobile'] ) )
				$errors->add( 'mobile_registered',
					_x( 'Mobile Number is already registered.', 'Modules: Commerce', 'gnetwork' ) );
		}
	}

	public function woocommerce_checkout_update_customer( $customer, $data )
	{
		if ( $this->options['mobile_field'] )
			$customer->update_meta_data( GNETWORK_COMMERCE_MOBILE_METAKEY, $data['customer_mobile'] );
	}

	public function woocommerce_checkout_create_order( $order, $data )
	{
		if ( $this->options['mobile_field'] )
			$order->update_meta_data( '_customer_mobile', $data['customer_mobile'] );
	}

	// @REF: https://gist.github.com/bekarice/6d378372c49456dbe8345dc785b6d7f4
	public function admin_order_data_after_order_details( $order )
	{
		if ( ! ( $order instanceof \WC_Order ) )
			return;

		if ( ! $order->get_user_id() )
			return;

		$meta = get_user_meta( $order->get_user_id(), GNETWORK_COMMERCE_MOBILE_METAKEY, TRUE );

		echo '<p class="form-field form-field-wide wc-order-received wc-order-status"><label>';
			_ex( 'Mobile contact:', 'Modules: Commerce', 'gnetwork' );

			if ( $meta )
				echo HTML::tel( $meta, _x( 'The mobile number associated with this user.', 'Modules: Commerce', 'gnetwork' ) );

			else
				gNetwork()->na();

		echo '</label></p>';
	}

	// TODO: better styling
	public function admin_order_data_after_billing_address( $order )
	{
		if ( ! $meta = $order->get_meta( '_customer_mobile', TRUE, 'edit' ) )
			return;

		echo '<p class="form-field form-field-wide" style="margin:0">';
			echo '<strong style="display:block">'._x( 'Mobile:', 'Modules: Commerce: Action Title', 'gnetwork' );
			echo '</strong> '.HTML::tel( $meta );
		echo '</p>';
	}

	// @REF: https://docs.woocommerce.com/document/add-a-custom-field-in-an-order-to-the-emails/
	// @SEE: https://rudrastyh.com/woocommerce/order-meta-in-emails.html
	// MAYBE: for better control/linking: use `woocommerce_email_order_meta` hook
	public function woocommerce_email_order_meta_fields( $fields, $sent_to_admin, $order )
	{
		if ( $this->options['mobile_field'] ) {

			$meta = get_post_meta( $order->get_id(), '_customer_mobile', TRUE );

			if ( $meta && ( $mobile = wc_format_phone_number( $meta ) ) )
				$fields[] = [
					'label' => _x( 'Mobile Number', 'Modules: Commerce', 'gnetwork' ),
					'value' => Number::localize( $mobile ),
				];
		}

		return $fields;
	}

	// @REF: https://rudrastyh.com/woocommerce/edit-account-fields.html
	public function woocommerce_edit_account_form_start()
	{
		if ( $this->options['mobile_field'] ) {
			woocommerce_form_field( 'account_mobile', [
				'type'              => 'tel',
				'class'             => [ 'form-row-wide', 'mobile' ],
				'input_class'       => [ 'ltr', 'rtl-placeholder' ],
				'label'             => _x( 'Mobile', 'Modules: Commerce', 'gnetwork' ),
				'placeholder'       => _x( 'For short message purposes', 'Modules: Commerce', 'gnetwork' ),
				'required'          => TRUE,
				'clear'             => TRUE,
				'custom_attributes' => [ 'pattern' => Validation::getMobileHTMLPattern() ],
			], get_user_meta( get_current_user_id(), GNETWORK_COMMERCE_MOBILE_METAKEY, TRUE ) );

			wc_enqueue_js( "$('p#account_mobile_field').insertAfter($('input#account_email').parent());" );
		}
	}

	public function woocommerce_save_account_details( $user_id )
	{
		if ( $this->options['mobile_field'] && array_key_exists( 'account_mobile', $_POST ) )
			update_user_meta( $user_id, GNETWORK_COMMERCE_MOBILE_METAKEY, $this->sanitize_mobile_field( sanitize_text_field( $_POST['account_mobile'] ) ) );
	}

	public function woocommerce_save_account_details_errors( &$errors, &$user )
	{
		if ( $this->options['mobile_field'] ) {

			$mobile = wc_clean( wp_unslash( $_POST['account_mobile'] ) );

			if ( empty( $mobile ) ) {

				$errors->add( 'mobile_empty',
					_x( 'Mobile Number cannot be empty.', 'Modules: Commerce', 'gnetwork' ) );

			} else if ( ! Validation::isMobileNumber( $mobile ) ) {

				$errors->add( 'mobile_invalid',
					_x( 'Mobile Number is not valid.', 'Modules: Commerce', 'gnetwork' ) );

			} else if ( $already = WPUser::getIDbyMeta( GNETWORK_COMMERCE_MOBILE_METAKEY, $mobile ) ) {

				if ( $already != get_current_user_id() )
					$errors->add( 'mobile_registered',
						_x( 'Mobile Number is already registered.', 'Modules: Commerce', 'gnetwork' ) );
			}
		}
	}

	public function woocommerce_save_account_details_required_fields( $fields )
	{
		$extra = [];

		if ( $this->options['mobile_field'] )
			$extra['account_mobile'] = _x( 'Mobile', 'Modules: Commerce', 'gnetwork' );

		return array_merge( $fields, $extra );
	}

	public function woocommerce_register_form()
	{
		if ( $this->options['mobile_field'] )
			woocommerce_form_field( 'account_mobile', [
				'type'              => 'tel',
				'class'             => [ 'form-row-wide', 'mobile' ],
				'input_class'       => [ 'ltr', 'rtl-placeholder' ],
				'label'             => _x( 'Mobile', 'Modules: Commerce', 'gnetwork' ),
				'placeholder'       => _x( 'For short message purposes', 'Modules: Commerce', 'gnetwork' ),
				'required'          => TRUE,
				'clear'             => TRUE,
				'custom_attributes' => [ 'pattern' => Validation::getMobileHTMLPattern() ],
			] );
	}

	public function woocommerce_created_customer( $customer_id )
	{
		$this->woocommerce_save_account_details( $customer_id );
	}

	// MAYBE move to persiandate
	public function woocommerce_process_myaccount_field_shipping_postcode( $value )
	{
		return Number::intval( $value, FALSE );
	}

	// adds the page ids from the WooCommerce core pages to the excluded post ids on Yoast Sitemaps
	public function exclude_from_sitemap_by_post_ids( $excluded_posts_ids )
	{
		if ( ! function_exists( 'wc_get_page_id' ) )
			return $excluded_posts_ids;

		return array_merge( $excluded_posts_ids, array_filter( [
			wc_get_page_id( 'cart' ),
			wc_get_page_id( 'checkout' ),
			wc_get_page_id( 'myaccount' ),
		] ) );
	}

	// @REF: https://www.cssigniter.com/upgrade-your-woocommerce-no-products-found-page/
	public function woocommerce_no_products_found()
	{
		echo Utilities::prepDescription( $this->options['no_products_found'] );
	}
}

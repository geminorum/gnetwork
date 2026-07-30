<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class WooCommerce extends Core\Base
{

	const PLUGIN = 'woocommerce/woocommerce.php';

	const ORDER_POSTTYPE     = 'shop_order';
	const PRODUCT_POSTTYPE   = 'product';
	const PRODUCT_VARIATION  = 'product_variation';
	const PROCUCT_CATEGORY   = 'product_cat';
	const PRODUCT_TAXONOMIES = [
		'product_type',
		'product_cat',
		'product_tag',
		'product_brand',
	];

	const TERM_IMAGE_METAKEY = 'thumbnail_id';
	const GTIN_METAKEY       = '_global_unique_id';

	public static function isActive(): bool
	{
		return Extend::isPluginActive( static::PLUGIN );
	}

	public static function isActiveWoodMart(): bool
	{
		if ( defined( 'WOODMART_CORE_VERSION' ) )
			return TRUE;

		// fallback/unnesseary db call
		// if ( get_option( 'woodmart_is_activated' ) )
		// 	return TRUE;

		return FALSE;
	}

	public static function available(): bool
	{
		if ( ! function_exists( 'WC' ) )
			return FALSE;

		$woo = WC();

		return $woo instanceof \WooCommerce;
	}

	/**
	 * Declare (in)compatibility with a given feature for a given plugin.
	 *
	 * * NOTE: WooCommerce only shows an incompatibility message for plugins
	 * * that include a `WC tested up to` header.
	 *
	 * @param string $plugin_file
	 * @param array $features
	 * @return bool
	 */
	public static function declareCompat( string $plugin_file, ?array $features = NULL ): bool
	{
		$features = $features ?? [
			// Declares whether it’s compatible with `HPOS` or not.
			// https://developer.woocommerce.com/docs/hpos-extension-recipe-book/
			'custom_order_tables' => TRUE,

			// 'cart_checkout_blocks' => TRUE,
			// 'product_block_editor' => TRUE,
		];

		if ( empty( $plugin_file ) || empty( $features ) )
			return FALSE;

		return add_action( 'before_woocommerce_init',
			function () use ( $features, $plugin_file ) {

				if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) )
					return;

				foreach ( $features as $feature => $declaration )
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
						$feature,
						$plugin_file,
						$declaration
					);
			} );
	}

	/**
	 * Checks if a given feature is currently enabled.
	 *
	 * @param string $feature_id
	 * @return bool
	 */
	public static function featureEnabled( $feature_id ): bool
	{
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) )
			return FALSE;

		// `return \Automattic\WooCommerce\Utilities\FeaturesUtil::get_features();`
		return (bool) \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( $feature_id );
	}

	public static function skuEnabled(): bool
	{
		return function_exists( 'wc_product_sku_enabled' ) && wc_product_sku_enabled();
	}

	public static function manageStock(): bool
	{
		return 'yes' === get_option( 'woocommerce_manage_stock' );
	}

	/**
	 * Returns true if on a page which uses WooCommerce.
	 *
	 * @ref https://developer.woocommerce.com/docs/theming/theme-development/conditional-tags
	 * @ref https://www.businessbloomer.com/woocommerce-conditional-logic-ultimate-php-guide/
	 *
	 * @return false|string
	 */
	public static function isPage(): bool
	{
		if ( ! self::available() )
			return FALSE;

		// checks for `is_shop()`/`is_product_taxonomy()`/`is_product()`
		if ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
			return 'is_woocommerce';

		if ( function_exists( 'is_cart' ) && is_cart() )
			return 'is_cart';

		if ( function_exists( 'is_checkout' ) && is_checkout() )
			return 'is_checkout';

		if ( function_exists( 'is_account_page' ) && is_account_page() )
			return 'is_account_page';

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() )
			return 'is_wc_endpoint';

		return FALSE;
	}

	public static function getProductTaxonomies( $include_defaults = TRUE ): array
	{
		$list = get_object_taxonomies( static::PRODUCT_POSTTYPE, 'names' );

		return $include_defaults
			? $list
			: array_diff( $list, static::PRODUCT_TAXONOMIES );
	}

	// NOTE: it's always `product` then all sub-types are registered as a taxonomy `$product->get_type()`
	// @SEE: https://woocommerce.com/document/installed-taxonomies-post-types/#section-2
	// @REF https://rudrastyh.com/woocommerce/product-types.html
	#[\Deprecated()]
	public static function getProductPosttype(): string
	{
		self::_dep( 'WooCommerce::PRODUCT_POSTTYPE' );

		return 'product';
	}

	#[\Deprecated()]
	public static function getProductCategoryTaxonomy(): string
	{
		self::_dep( 'WooCommerce::PROCUCT_CATEGORY' );

		return 'product_cat';
	}

	public static function getOrderStatuses(): array
	{
		$statuses = [];

		foreach ( wc_get_order_statuses() as $status => $name )
			$statuses[( 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status )] = $name;

		return $statuses;
	}

	public static function getBaseCountry(): string
	{
		return WC()->countries->get_base_country();
	}

	public static function getBaseState(): string
	{
		return WC()->countries->get_base_state();
	}

	// @REF: https://wordpress.stackexchange.com/a/334608/93391
	// `$default = wc_get_base_location();`
	public static function getBaseAddress(): array
	{
		$country = self::getBaseCountry();

		return [
			'address'      => WC()->countries->get_base_address(),
			'address-2'    => WC()->countries->get_base_address_2(),
			'postcode'     => WC()->countries->get_base_postcode(),
			'city'         => WC()->countries->get_base_city(),
			'state'        => WC()->countries->get_base_state(),
			'country'      => WC()->countries->countries[$country],
			'country-code' => $country,
			'mail'         => get_option( 'address-public-mail' ),
		];
	}

	/**
	 * Changes the product type.
	 * If used an invalid type a `WC_Product_Simple` instance will be returned.
	 * NOTE: same as `wc_get_product_object()` with save trigger!
	 * @source https://stackoverflow.com/a/62761862
	 *
	 * @param int $product_id
	 * @param string $target_type
	 * @return object
	 */
	public static function changeProductType( int $product_id, string $target_type ): object
	{
 		// Get the correct product class-name from the new product type
		$classname = \WC_Product_Factory::get_product_classname( $product_id, $target_type );

		// Get the new product object from the correct class-name
		$product = new $classname( $product_id );

		// Save product to database and sync caches
		$product->save();

		return $product;
	}

	public static function getDefaultColumns( ?int $fallback = NULL ): int
	{
		return function_exists( 'wc_get_default_products_per_row' )
			? (int) wc_get_default_products_per_row()
			: $fallback ?? 4;
	}

	public static function getDefaultRows( ?int $fallback = NULL ): int
	{
		return function_exists( 'wc_get_default_product_rows_per_page' )
			? (int) wc_get_default_product_rows_per_page()
			: $fallback ?? 4;
	}
}

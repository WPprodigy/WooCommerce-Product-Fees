<?php
/**
 * WooCommerce Product Fees
 *
 * Creates and adds the fees at checkout.
 *
 * @class 	Woocommerce_Product_Fees
 * @version 1.0
 * @author 	Caleb Burks
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lod the helper class to handle multiple currencies
require_once 'class-currency-helper.php';

class Woocommerce_Product_Fees {

	public function __construct() {

		if ( is_admin() ) {
			// Load Admin Settings
			require_once 'class-woocommerce-product-fees-admin.php';
		}

		// Text Domain
		add_action( 'plugins_loaded', array( $this, 'text_domain' ) );

		// Add the fee
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'get_product_fee_data' ) );

	}

	/**
	 * Load Text Domain
	 */
	public function text_domain() {

	 	load_plugin_textdomain( 'woocommerce-product-fees', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Creates the fee.
	 */
	public function add_product_fee( $fee_amount, $fee_name ) {

 		global $woocommerce;

  		$woocommerce->cart->add_fee( __($fee_name, 'woocommerce-product-fees'), $fee_amount );

	}

	/**
	 * Checks if the fee has a percentage in it, and then converts the fee from a percentage to a decimal
	 */
	public function percentage_conversion( $product_fee, $cart_product_price ) {

		if ( strpos( $product_fee, '%' ) ) {

			// Convert to Decimal
			$decimal = str_replace( '%', '', $product_fee ) / 100;

			// Multiply by Product Price
			$fee = $cart_product_price * $decimal;

		} else {

			$fee = $product_fee;

		}

		return $fee;

	}

	/**
	 * Checks if the fee should be multiplied by the quantity of the product in the cart
	 */
	public function quantity_multiply( $product_fee, $cart_product_price, $quantity_multiply, $cart_product_qty ) {

		// Pull in the percentage check
		$product_fee = $this->percentage_conversion( $product_fee, $cart_product_price );

		if ( $quantity_multiply == 'yes' ) {

			// Multiply the fee by the quantity
			$new_product_fee = $cart_product_qty * $product_fee;

		} else {

			$new_product_fee = $product_fee;

		}

		return $new_product_fee;

	}

	/**
	 * Checks if products in the cart have added fees. If so, then it send the data to add_product_fee().
	 */
	public function product_specific_fee( $product_id, $product_fee, $product_fee_name, $quantity_multiply ) {

		global $woocommerce;

		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

			$cart_product = $values['data'];
			$cart_product_qty = $values['quantity'];
			$cart_product_price = $cart_product->price;

			// Checks if each product in the cart has additional fees that need to be added
			if ( $cart_product->id == $product_id ) {

				$new_product_fee = $this->quantity_multiply( $product_fee, $cart_product_price, $quantity_multiply, $cart_product_qty );

				// Send multiplied fee data to add_product_fee()
				$this->add_product_fee( $new_product_fee, $product_fee_name );

			}

		}

	}

	/**
	 * Pulls data from WooCommerce product settings and sends it to product_specific_fee().
	 */
	public function get_product_fee_data() {

		global $woocommerce;

		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

			$cart_product = $values['data'];

			// Checks for a fee name and fee amount in the product settings
			if ( get_post_meta( $cart_product->id, 'product-fee-name', true ) && get_post_meta( $cart_product->id, 'product-fee-amount', true ) ) {

				$fee_name = get_post_meta( $cart_product->id, 'product-fee-name', true );
				$fee_amount = get_post_meta( $cart_product->id, 'product-fee-amount', true );

				// Convert the fee to the active currency
				$fee_amount = WooCommerce_Product_Fees_Currency_Helper::get_price_in_currency($fee_amount);

				$fee_multiplier = get_post_meta( $cart_product->id, 'product-fee-multiplier', true );

				// Send fee data to product_specific_fee()
				$this->product_specific_fee( $cart_product->id, $fee_amount, $fee_name, $fee_multiplier );

			}

		}

	}


} // End Class
new Woocommerce_Product_Fees();

<?php
/**
 * Subscriptions Coupon Class
 *
 * Mirrors a few functions in the WC_Cart class to handle subscription-specific discounts
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Coupon
 * @category	Class
 * @author		Max Rice
 * @since		1.2
 */
class WC_Subscriptions_Coupon {

	/** @var string error message for invalid subscription coupons */
	public static $coupon_error;

	/**
	 * Set up the class, including it's hooks & filters, when the file is loaded.
	 *
	 * @since 1.2
	 **/
	public static function init() {

		// Add custom coupon types
		add_filter( 'woocommerce_coupon_discount_types', __CLASS__ . '::add_discount_types' );

		// Handle before tax discounts
		add_filter( 'woocommerce_get_discounted_price', __CLASS__ . '::apply_subscription_discount_before_tax', 10, 3 );

		// Handle after tax discounts
		add_action( 'woocommerce_product_discount_after_tax_sign_up_fee', __CLASS__ . '::apply_subscription_discount_after_tax' );
		add_action( 'woocommerce_product_discount_after_tax_recurring_fee', __CLASS__ . '::apply_subscription_discount_after_tax' );

		// Validate subscription coupons
		add_filter( 'woocommerce_coupon_is_valid', __CLASS__ . '::validate_subscription_coupon', 10, 2 );
	}

	/**
	 * Add discount types
	 *
	 * @since 1.2
	 */
	public static function add_discount_types( $discount_types ) {

		return array_merge( 
			$discount_types, 
			array( 
				'sign_up_fee'   => __( 'Sign Up Fee Discount', WC_Subscriptions::$text_domain ),
				'recurring_fee' => __( 'Recurring Fee Discount', WC_Subscriptions::$text_domain )
			) 
		);
	}

	/**
	 * Apply sign up fee or recurring fee discount before tax is calculated
	 *
	 * @since 1.2
	 */
	public static function apply_subscription_discount_before_tax( $original_price, $product, $cart ) {
		global $woocommerce;

		if( ! WC_Subscriptions_Product::is_subscription( $product['product_id'] ) )
			return $original_price;

		$price = $original_price;

		if ( ! empty( $cart->applied_coupons ) ) {
				foreach ( $cart->applied_coupons as $code ) {
					$coupon = new WC_Coupon( $code );

					if ( $coupon->apply_before_tax() && $coupon->is_valid() ) {

						// Sign up fee discount
						if ( ( 'sign_up_fee' == WC_Subscriptions_Cart::get_recalculation_type() && 'sign_up_fee' == $coupon->type ) ||
							 ( 'base_recurring_fee' == WC_Subscriptions_Cart::get_recalculation_type() && 'recurring_fee' == $coupon->type ) ||
							 ( 0 == WC_Subscriptions_Cart::get_cart_subscription_sign_up_fee() && 'recurring_fee' == $coupon->type ) ) {

							if ( $original_price < $coupon->amount ) {
								$discount_amount = $original_price;
							} else {
								$discount_amount = $coupon->amount;
							}

							$price = $original_price - $coupon->amount;

							if ( $price < 0 ) $price = 0;

							// add to discount totals
							$woocommerce->cart->discount_cart = $woocommerce->cart->discount_cart + ( $discount_amount * $product['quantity'] );
						}
					}
				}
		}

		return $price;
	}

	/**
	 * Apply sign up fee or recurring fee discount after tax is calculated
	 *
	 * Unable to handle percentage discounts without having correct price to calculate discount
	 * Unable to check if the after-tax price is less than the coupon amount without having after tax price available
	 * Hook added in WC 1.7 fixes these issues
	 *
	 * @since 1.2
	 */
	public static function apply_subscription_discount_after_tax( $coupon ) {
		global $woocommerce;

		if ( sizeof( $woocommerce->cart->cart_contents ) > 0 ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item_key => $values ) {
				if ( ! $coupon->apply_before_tax() && $coupon->is_valid() && self::is_subscription_discountable( $values, $coupon ) ) {
					if(
						// Sign up fee discount
						( 'sign_up_fee' == WC_Subscriptions_Cart::get_recalculation_type() && 'sign_up_fee' == $coupon->type ) ||
						// Base recurring fee discount with sign up fee present
						( 'base_recurring_fee' == WC_Subscriptions_Cart::get_recalculation_type() && 'recurring_fee' == $coupon->type ) ||
						// Base recurring fee discount with no sign up fee present
						// A standard cart/product discount would work, but users will probably select 'recurring fee discount' when trying to discount a subscription
						( 0 == WC_Subscriptions_Cart::get_cart_subscription_sign_up_fee() && 'recurring_fee' == $coupon->type ) )

							$woocommerce->cart->discount_total = $woocommerce->cart->discount_total + ( $coupon->amount * $values['quantity'] );
				}
			}
		}
	}

	/**
	 * Determines if cart contains a recurring fee discount code
	 *
	 * Does not check if the code is valid, etc
	 *
	 * @since 1.2
	 */
	public static function cart_contains_recurring_discount() {
		global $woocommerce;

		$contains_discount = false;

		if ( $woocommerce->cart->applied_coupons ) {

			foreach ( $woocommerce->cart->applied_coupons as $code ) {

				$coupon = new WC_Coupon( $code );

				if( 'recurring_fee' == $coupon->type )
					$contains_discount = true;
			}
		}

		return $contains_discount;
	}

	/**
	 * Determines if cart contains a sign up fee discount code
	 *
	 * Does not check if the code is valid, etc
	 *
	 * @since 1.2
	 */
	public static function cart_contains_sign_up_discount() {
		global $woocommerce;

		$contains_discount = false;

		if ( $woocommerce->cart->applied_coupons ) {

			foreach ( $woocommerce->cart->applied_coupons as $code ) {

				$coupon = new WC_Coupon( $code );

				if( 'sign_up_fee' == $coupon->type )
					$contains_discount = true;
			}
		}

		return $contains_discount;
	}

	/**
	 * Check is a subscription coupon is valid before applying
	 *
	 * @since 1.2
	 */
	public static function validate_subscription_coupon( $valid, $coupon ) {

		// only check subscription coupons
		if( ! in_array( $coupon->type, array( 'recurring_fee', 'sign_up_fee' ) ) )
			return $valid;

		// prevent subscription coupons from being applied to non-subscription products
		if( ! WC_Subscriptions_Cart::cart_contains_subscription() ) {

			$valid = false;

			self::$coupon_error = __( 'This coupon is only valid for subscriptions.', WC_Subscriptions::$text_domain );
			add_filter( 'woocommerce_coupon_error', __CLASS__ . '::add_coupon_error', 10 );
		}

		// prevent sign up fee coupons from being applied to subscriptions without a sign up fee
		if( 0 == WC_Subscriptions_Cart::get_cart_subscription_sign_up_fee() && 'sign_up_fee' == $coupon->type ) {

			$valid = false;

			self::$coupon_error = __( 'This coupon is only valid for subscriptions with a sign-up fee.', WC_Subscriptions::$text_domain );
			add_filter( 'woocommerce_coupon_error', __CLASS__ . '::add_coupon_error', 10 );
		}

		return $valid;
	}

	/**
	 * Returns a subscription coupon-specific error if validation failed
	 *
	 * @since 1.2
	 */
	public static function add_coupon_error( $error ) {

		if( self::$coupon_error )
			return self::$coupon_error;
		else
			return $error;

	}

	/**
	 * Checks a given product / coupon combination to determine if the subscription should be discounted
	 *
	 * @since 1.2
	 */
	private static function is_subscription_discountable( $values, $coupon ) {

		$product_cats = wp_get_post_terms( $values['product_id'], 'product_cat', array("fields" => "ids") );

		$this_item_is_discounted = false;

		// Specific products get the discount
		if ( sizeof( $coupon->product_ids ) > 0 ) {

			if (in_array($values['product_id'], $coupon->product_ids) || in_array($values['variation_id'], $coupon->product_ids) || in_array($values['data']->get_parent(), $coupon->product_ids))
				$this_item_is_discounted = true;

			// Category discounts
		} elseif ( sizeof( $coupon->product_categories ) > 0 ) {

			if ( sizeof( array_intersect( $product_cats, $coupon->product_categories ) ) > 0 )
				$this_item_is_discounted = true;

		} else {

			// No product ids - all items discounted
			$this_item_is_discounted = true;

		}

		// Specific product ID's excluded from the discount
		if ( sizeof( $coupon->exclude_product_ids ) > 0 )
			if ( in_array( $values['product_id'], $coupon->exclude_product_ids ) || in_array( $values['variation_id'], $coupon->exclude_product_ids ) || in_array( $values['data']->get_parent(), $coupon->exclude_product_ids ) )
				$this_item_is_discounted = false;

		// Specific categories excluded from the discount
		if ( sizeof( $coupon->exclude_product_categories ) > 0 )
			if ( sizeof( array_intersect( $product_cats, $coupon->exclude_product_categories ) ) > 0 )
				$this_item_is_discounted = false;

		// Apply filter
		return apply_filters( 'woocommerce_item_is_discounted', $this_item_is_discounted, $values, $before_tax = false );
	}

}

WC_Subscriptions_Coupon::init();

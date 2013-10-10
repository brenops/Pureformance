<?php
/**
 * Subscriptions Cart Class
 * 
 * Mirrors a few functions in the WC_Cart class to work for subscriptions. 
 * 
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Cart
 * @category	Class
 * @author		Brent Shepherd
 * @since		1.0
 */
class WC_Subscriptions_Cart {

	/**
	 * A flag to control how to modify the calculation of totals by WC_Cart::calculate_totals()
	 * 
	 * @since 1.2
	 */
	private static $recalculation_type = 'none';

	/**
	 * Stores the base price of a cart item so it can be used to correctly figure out the 
	 * proportion of the total cart price at a later stage. 
	 * 
	 * @since 1.2
	 */
	private static $base_product_price;

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 * 
	 * @since 1.0
	 */
	public static function init() {

		// Make sure the price per period totals persist in the cart
		add_action( 'init', __CLASS__ . '::get_cart_from_session', 6 );
		add_action( 'woocommerce_cart_updated', __CLASS__ . '::set_session' );
		add_action( 'woocommerce_cart_emptied', __CLASS__ . '::reset' );

		// Make sure WC calculates total on sign up fee + price per period, and keep a record of the price per period
		add_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::add_calculation_price_filter', 10 );
		add_action( 'woocommerce_calculate_totals', __CLASS__ . '::remove_calculation_price_filter' );
		add_filter( 'woocommerce_calculated_total', __CLASS__ . '::calculate_subscription_totals', 100, 1 );

		// Override Formatted Discount Totals
		add_filter( 'woocommerce_cart_discounts_before_tax', __CLASS__ . '::get_formatted_discounts_before_tax', 11, 2 );
		add_filter( 'woocommerce_cart_discounts_after_tax', __CLASS__ . '::get_formatted_discounts_after_tax', 11, 2 );

		// Override Formatted Cart Tax
		add_filter( 'woocommerce_cart_formatted_taxes', __CLASS__ . '::get_formatted_taxes', 11, 2 );

		// Display Formatted Totals
		add_filter( 'woocommerce_cart_product_subtotal', __CLASS__ . '::get_formatted_product_subtotal', 11, 2 );
		add_filter( 'woocommerce_cart_subtotal', __CLASS__ . '::get_formatted_cart_subtotal', 11, 3 );

		add_filter( 'woocommerce_cart_total_ex_tax', __CLASS__ . '::get_formatted_total_ex_tax', 11, 2 );
		add_filter( 'woocommerce_cart_total', __CLASS__ . '::get_formatted_total', 11, 2 );
	}

	/**
	 * Attaches the "set_subscription_prices_for_calculation" filter to the WC Product's woocommerce_get_price hook.
	 * 
	 * This function is hooked to "woocommerce_before_calculate_totals" so that WC will calculate a subscription
	 * product's total based on the total of it's price per period and sign up fee (if any).
	 *
	 * @since 1.2
	 */
	public static function add_calculation_price_filter(){

		// Only hook when totals are being calculated completely (on cart & checkout pages)
		if ( ! self::cart_contains_subscription() || ( ! is_checkout() && ! is_cart() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) && ! defined( 'WOOCOMMERCE_CART' ) ) )
			return;

		add_filter( 'woocommerce_get_price', __CLASS__ . '::set_subscription_prices_for_calculation', 100, 2 );
	}

	/**
	 * Removes the "set_subscription_prices_for_calculation" filter from the WC Product's woocommerce_get_price hook once
	 * calculations are complete. 
	 *
	 * @since 1.2
	 */
	public static function remove_calculation_price_filter( $total ){

		remove_filter( 'woocommerce_get_price', __CLASS__ . '::set_subscription_prices_for_calculation', 100, 2 );

		return $total;
	}

	/**
	 * For the purposes of simplifying calculations, add the sign up fee to the price per period
	 * so WC can do all the calculations and then we can figure out what proportion of the total
	 * calculated by WC is for the initial payment and how much is for the recurring payment.
	 *
	 * @since 1.2
	 */
	public static function set_subscription_prices_for_calculation( $price, $product ){
		global $woocommerce;

		if( WC_Subscriptions_Product::is_subscription( $product ) ) {

			self::$base_product_price = $price;

			$woocommerce->cart->base_recurring_prices[$product->id] = $price;

			$sign_up_fee = WC_Subscriptions_Product::get_sign_up_fee( $product );

			// only modify the price when calculating a sign up fee or entire cart
			if( 'sign_up_fee' == self::$recalculation_type ) {

				$price = $sign_up_fee;

			} elseif( 'none' == self::$recalculation_type ) {

				$price += $sign_up_fee;
			} else {
				// do not modify price
			}

		}

		return $price;
	}

	/**
	 * Uses the a subscription's combined price total calculated by WooCommerce to determine the 
	 * total price that should be charged per period.
	 *
	 * @since 1.2
	 */
	public static function calculate_subscription_totals( $total ){
		global $woocommerce;

		if ( ! self::cart_contains_subscription() ) {

			return $total;

		} elseif ( 'sign_up_fee' == self::$recalculation_type ) {

			// We've requested totals be recalculated with sign up fee only, we need to remove anything shipping related from the sign-up fee totals
			$total = $total - $woocommerce->cart->shipping_tax_total - $woocommerce->cart->shipping_total;

			$woocommerce->cart->shipping_taxes = array();
			$woocommerce->cart->shipping_tax_total = 0;

			self::$recalculation_type = 'none';
			return $total;
		}

		$base_sign_up_fee = self::get_cart_subscription_sign_up_fee();

		if ( $base_sign_up_fee == 0 || self::$recalculation_type == 'base_recurring_fee' ) {

			// Nothing to fudge here, but we still need to keep a record of recurring totals
			foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
				$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_total']        = $values['line_total'];
				$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_tax']          = $values['line_tax'];
				$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal']     = $values['line_subtotal'];
				$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal_tax'] = $values['line_subtotal_tax'];
			}

			$woocommerce->cart->recurring_cart_contents_total = $woocommerce->cart->cart_contents_total;
			$woocommerce->cart->recurring_discount_cart       = $woocommerce->cart->discount_cart;
			$woocommerce->cart->recurring_discount_total      = $woocommerce->cart->discount_total;
			$woocommerce->cart->recurring_subtotal            = $woocommerce->cart->subtotal;
			$woocommerce->cart->recurring_subtotal_ex_tax     = $woocommerce->cart->subtotal_ex_tax;

			$woocommerce->cart->recurring_taxes = $woocommerce->cart->get_taxes();

			$woocommerce->cart->recurring_tax_total    = $woocommerce->cart->tax_total;
			$woocommerce->cart->recurring_total        = $total;

			// after calculating the recurring fee with discount, reset flag
			if( self::$recalculation_type == 'base_recurring_fee' ) {
				self::$recalculation_type = 'none';
				return $total;
			}

		} else { // The $total = price per period + sign up fee, so lets derive each one individually 

			$sign_up_fee_proportion = $base_sign_up_fee / ( $base_sign_up_fee + self::$base_product_price ); // self::$base_product_price as set in set_subscription_prices_for_calculation()
			$recurring_proportion   = 1 - $sign_up_fee_proportion;

			foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
				$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_total']        = WC_Subscriptions_Manager::get_amount_from_proportion( $values['line_total'], $recurring_proportion );
				$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_tax']          = WC_Subscriptions_Manager::get_amount_from_proportion( $values['line_tax'], $recurring_proportion );
				$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal']     = WC_Subscriptions_Manager::get_amount_from_proportion( $values['line_subtotal'], $recurring_proportion );
				$woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal_tax'] = WC_Subscriptions_Manager::get_amount_from_proportion( $values['line_subtotal_tax'], $recurring_proportion );
			}

			// Calculate recurring totals, required for totals display correctly on cart and order page
			$woocommerce->cart->recurring_cart_contents_total = WC_Subscriptions_Manager::get_amount_from_proportion( $woocommerce->cart->cart_contents_total, $recurring_proportion );
			$woocommerce->cart->recurring_discount_cart       = WC_Subscriptions_Manager::get_amount_from_proportion( $woocommerce->cart->discount_cart, $recurring_proportion );
			$woocommerce->cart->recurring_discount_total      = WC_Subscriptions_Manager::get_amount_from_proportion( $woocommerce->cart->discount_total, $recurring_proportion );
			$woocommerce->cart->recurring_subtotal            = WC_Subscriptions_Manager::get_amount_from_proportion( $woocommerce->cart->subtotal, $recurring_proportion );
			$woocommerce->cart->recurring_subtotal_ex_tax     = WC_Subscriptions_Manager::get_amount_from_proportion( $woocommerce->cart->subtotal_ex_tax, $recurring_proportion );

			$woocommerce->cart->recurring_taxes = array();

			// Add non-shipping taxes
			foreach ( $woocommerce->cart->taxes as $tax_id => $tax_amount )
				$woocommerce->cart->recurring_taxes[$tax_id] = WC_Subscriptions_Manager::get_amount_from_proportion( $tax_amount, $recurring_proportion );

			// And shipping taxes
			foreach ( $woocommerce->cart->shipping_taxes as $tax_id => $tax_amount )
				$woocommerce->cart->recurring_taxes[$tax_id] = $tax_amount; // Shipping only applies to recurring payments

			// Shipping only applies to recurring amounts, not the sign-up fee, so we'll add it in its entirety to the recurring amount later
			$total_sans_shipping    = $total - $woocommerce->cart->shipping_tax_total - $woocommerce->cart->shipping_total;
			$total_ex_tax           = $total_sans_shipping - $woocommerce->cart->tax_total;
			$recurring_total_ex_tax = WC_Subscriptions_Manager::get_amount_from_proportion( $total_ex_tax, $recurring_proportion ) + $woocommerce->cart->shipping_total;

			$woocommerce->cart->recurring_total           = WC_Subscriptions_Manager::get_amount_from_proportion( $total_sans_shipping, $recurring_proportion ) + $woocommerce->cart->shipping_tax_total + $woocommerce->cart->shipping_total;
			$woocommerce->cart->recurring_tax_total       = $woocommerce->cart->recurring_total - $recurring_total_ex_tax;

			/** Handle pricing adjustments - Recurring / Sign up fee discounts / trial periods */

			// Recurring discount
			if( WC_Subscriptions_Coupon::cart_contains_recurring_discount() ) :

				// save cart with combined totals
				$original_cart = clone $woocommerce->cart;

				// calculate total with sign up fee only first
				self::$recalculation_type = 'sign_up_fee';
				$woocommerce->cart->calculate_totals();
				// save cart with just sign up fee
				$sign_up_fee_cart = clone $woocommerce->cart;

				// now calculate base recurring fee with discount included
				self::$recalculation_type = 'base_recurring_fee';
				$woocommerce->cart->calculate_totals();

				if( self::cart_contains_free_trial() ) {

					// restore sign up fee cart contents & total
					$woocommerce->cart->cart_contents = $sign_up_fee_cart->cart_contents;
					$woocommerce->cart->cart_contents_total = $sign_up_fee_cart->cart_contents_total;

					// restore sign up fee cart sub-totals
					$woocommerce->cart->subtotal = $sign_up_fee_cart->subtotal;
					$woocommerce->cart->subtotal_ex_tax = $sign_up_fee_cart->subtotal_ex_tax;

					// restore sign up fee cart taxes
					$woocommerce->cart->taxes = $sign_up_fee_cart->taxes;
					$woocommerce->cart->tax_total = $sign_up_fee_cart->tax_total;

					// final total is sign up fee cart total only
					$total = $sign_up_fee_cart->total;

					// Add sign up fee discounts to cart/total discounts (which already include recurring discounts)
					$woocommerce->cart->discount_cart  = ( $sign_up_fee_cart->discount_cart > 0 ) ? $sign_up_fee_cart->discount_cart : 0;
					$woocommerce->cart->discount_total = ( $sign_up_fee_cart->discount_total > 0 ) ? $sign_up_fee_cart->discount_total : 0;

				} else {
					// restore combined cart contents & total
					$woocommerce->cart->cart_contents = $original_cart->cart_contents;
					$woocommerce->cart->cart_contents_total = $original_cart->cart_contents_total;

					// restore combined cart sub-totals
					$woocommerce->cart->subtotal = $original_cart->subtotal;
					$woocommerce->cart->subtotal_ex_tax = $original_cart->subtotal_ex_tax;

					// combine and total any taxes on sign up fees to the cart total (which already includes taxes on recurring fees)
					foreach( $woocommerce->cart->taxes as $tax_key => $tax_amount )
						$woocommerce->cart->taxes[$tax_key] += $sign_up_fee_cart->taxes[$tax_key];
					$woocommerce->cart->tax_total += $sign_up_fee_cart->tax_total;

					// Add sign up fee discounts to cart/total discounts (which already include recurring discounts)
					$woocommerce->cart->discount_cart += ( $sign_up_fee_cart->discount_cart > 0 ) ? $sign_up_fee_cart->discount_cart : 0;
					$woocommerce->cart->discount_total += ( $sign_up_fee_cart->discount_total > 0 ) ? $sign_up_fee_cart->discount_total : 0;

					// Final total is sign up fee cart total + recurring fee cart total
					$total = $sign_up_fee_cart->total + $woocommerce->cart->recurring_total;
				}

			// Sign up fee discount
			elseif( WC_Subscriptions_Coupon::cart_contains_sign_up_discount() ) :

				// save cart with combined totals
				$original_cart = clone $woocommerce->cart;

				// calculate totals with sign up fee only first
				self::$recalculation_type = 'sign_up_fee';
				$woocommerce->cart->calculate_totals();

				if( self::cart_contains_free_trial() ) {
					// only need sign up total for the initial payment
					$total = $woocommerce->cart->total;
				} else {

					// restore combined cart contents & total
					$woocommerce->cart->cart_contents = $original_cart->cart_contents;
					$woocommerce->cart->cart_contents_total = $original_cart->cart_contents_total;

					// restore combined cart sub-totals
					$woocommerce->cart->subtotal = $original_cart->subtotal;
					$woocommerce->cart->subtotal_ex_tax = $original_cart->subtotal_ex_tax;

					$total = $woocommerce->cart->total + $woocommerce->cart->recurring_total;
				}

			// Free trial with no discounts - recalculate all the initial payment amounts just for the sign-up fee
			elseif( self::cart_contains_free_trial() ) :

				// only pass sign up fee thru get_price filters
				self::$recalculation_type = 'sign_up_fee';
				$woocommerce->cart->calculate_totals();

				// Make the sign up fee only total persist
				$total = $woocommerce->cart->total;

			endif;
		}

		return $total;
	}


	/* Formatted Totals Functions */

	/**
	 * Returns the subtotal for a cart item including the subscription period and duration details
	 *
	 * @since 1.0
	 */
	public static function get_formatted_product_subtotal( $product_subtotal, $product ){
		global $woocommerce;

		if( WC_Subscriptions_Product::is_subscription( $product ) ) {

			$exclude_tax = ( $woocommerce->cart->display_cart_ex_tax && $woocommerce->cart->prices_include_tax ) ? 'exclude_tax' : '';
			$product_subtotal = '<span class="subscription-price">' . WC_Subscriptions_Product::get_price_string( $product, array( 'price' => $product_subtotal, 'tax_calculation' => $exclude_tax ) ) . '</span>';

			if ( strpos( $product_subtotal, $woocommerce->countries->inc_tax_or_vat() ) !== false )
				$product_subtotal = str_replace( $woocommerce->countries->inc_tax_or_vat(), '', $product_subtotal ) . ' ' . $woocommerce->countries->inc_tax_or_vat();
			if ( strpos( $product_subtotal, $woocommerce->countries->ex_tax_or_vat() ) !== false )
				$product_subtotal = str_replace( $woocommerce->countries->ex_tax_or_vat(), '', $product_subtotal ) . ' ' . $woocommerce->countries->ex_tax_or_vat();
		}

		return $product_subtotal;
	}

	/**
	 * Returns a string with the cart discount and subscription period.
	 *
	 * @return mixed formatted price or false if there are none
	 * @since 1.2
	 */
	public static function get_formatted_discounts_before_tax( $discount, $cart ) {
		global $woocommerce;

		if( self::cart_contains_subscription() && ( $discount !== false || self::get_recurring_discount_cart() > 0 ) )
			$discount = self::get_cart_subscription_string( $discount, self::get_recurring_discount_cart() );

		return $discount;
	}

	/**
	 * Gets the order discount amount - these are applied after tax
	 *
	 * @return mixed formatted price or false if there are none
	 * @since 1.2
	 */
	public static function get_formatted_discounts_after_tax( $discount, $cart ) {

		if( self::cart_contains_subscription() && ( $discount !== false || self::get_recurring_discount_total() > 0 ) )
			$discount = self::get_cart_subscription_string( $discount, self::get_recurring_discount_total() );

		return $discount;
	}

	/**
	 * Includes the sign-up fee subtotal in the subtotal displayed in the cart.
	 *
	 * @since 1.2
	 */
	public static function get_formatted_cart_subtotal( $cart_subtotal, $compound, $cart ) {
		global $woocommerce;

		if( self::cart_contains_subscription() ) {

			// We're in the cart widget and totals haven't been properly calculated yet so just show the product's subscription price string
			if ( ! is_checkout() && ! is_cart() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) && ! defined( 'WOOCOMMERCE_CART' ) ) {

				$cart_subtotal = '';

				foreach ( $woocommerce->cart->get_cart() as $cart_item )
					$cart_subtotal .= WC_Subscriptions_Product::get_price_string( $cart_item['product_id'] );

			} elseif ( $compound ) { // If the cart has compound tax, we want to show the subtotal as cart + non-compound taxes (after discount)

				$cart_subtotal = self::get_cart_subscription_string( $cart_subtotal, self::get_recurring_cart_contents_total() + self::get_recurring_shipping_total() + self::get_recurring_taxes_total( false ) );

			} elseif ( $cart->display_totals_ex_tax || ! $cart->prices_include_tax ) {

				// Display ex tax if the option is set, or prices exclude tax
				$cart_subtotal = self::get_cart_subscription_string( $cart_subtotal, self::get_recurring_subtotal_ex_tax() );

				if ( $cart->tax_total > 0 && $cart->prices_include_tax )
					$cart_subtotal = str_replace( $woocommerce->countries->ex_tax_or_vat(), '', $cart_subtotal ) . ' ' . $woocommerce->countries->ex_tax_or_vat();

			} else {

				// Otherwise show cart items totals only (before discount)
				$cart_subtotal = self::get_cart_subscription_string( $cart_subtotal, self::get_recurring_subtotal() );

				if ( $cart->tax_total > 0 && ! $cart->prices_include_tax )
					$cart_subtotal = str_replace( $woocommerce->countries->inc_tax_or_vat(), '', $cart_subtotal ) . ' ' . $woocommerce->countries->inc_tax_or_vat();

			}
		}

		return $cart_subtotal;
	}

	/**
	 * Displays each cart tax in a subscription string and calculates the sign-up fee taxes (if any)
	 * to display in the string.
	 *
	 * @since 1.2
	 */
	public static function get_formatted_taxes( $formatted_taxes, $cart ) {

		if( self::cart_contains_subscription() ) {

			$recurring_taxes = self::get_recurring_taxes();

			foreach ( $formatted_taxes as $tax_id => $tax_amount )
				$formatted_taxes[$tax_id] = self::get_cart_subscription_string( $tax_amount, $recurring_taxes[$tax_id] );

			// Add any recurring tax not already handled - when a subscription has a free trial and a sign-up fee, we get a recurring shipping tax with no initial shipping tax
			foreach ( $recurring_taxes as $tax_id => $tax_amount )
				if ( ! array_key_exists( $tax_id, $formatted_taxes ) )
					$formatted_taxes[$tax_id] = self::get_cart_subscription_string( '', $tax_amount );

		}

		return $formatted_taxes;
	}

	/**
	 * Appends the cart subscription string to a cart total using the @see self::get_cart_subscription_string and then returns it. 
	 *
	 * @return string Formatted subscription price string for the cart total.
	 * @since 1.2
	 */
	public static function get_formatted_total( $total ) {
		global $woocommerce;

		if ( self::cart_contains_subscription() )
			$total = self::get_cart_subscription_string( $total, self::get_recurring_total(), true );

		return $total;
	}

	/**
	 * Appends the cart subscription string to a cart total using the @see self::get_cart_subscription_string and then returns it. 
	 *
	 * @return string Formatted subscription price string for the cart total.
	 * @since 1.2
	 */
	public static function get_formatted_total_ex_tax( $total_ex_tax ) {
		global $woocommerce;

		if ( self::cart_contains_subscription() )
			$total_ex_tax = self::get_cart_subscription_string( $total_ex_tax, self::get_recurring_total_ex_tax(), true );

		return $total_ex_tax;
	}


	/*
	 * Helper functions for extracting the details of subscriptions in the cart
	 */

	/**
	 * Creates a string representation of the subscription period/term for each item in the cart
	 * 
	 * @param $initial_amount string The initial amount to be displayed for the subscription as passed through the @see woocommerce_price() function.
	 * @param $recurring_amount float The price to display in the subscription.
	 * @param $include_lengths bool (optional) Whether to display the trial and length of the subscription. Default to false - don't display. 
	 * @since 1.0
	 */
	public static function get_cart_subscription_string( $initial_amount, $recurring_amount, $include_lengths = false ) {
		global $woocommerce;

		$subscription_details = array(
			'initial_amount'        => $initial_amount,
			'initial_description'   => __( 'now', WC_Subscriptions::$text_domain ),
			'recurring_amount'      => $recurring_amount,
			'subscription_interval' => self::get_cart_subscription_interval(),
			'subscription_period'   => self::get_cart_subscription_period(),
			'trial_length'          => self::get_cart_subscription_trial_length(),
			'trial_period'          => self::get_cart_subscription_trial_period()
		);

		if ( $include_lengths === true ) {
			$subscription_details += array(
				'subscription_length'   => self::get_cart_subscription_length()
			);
		}

		// Override defaults when subscription is for one billing period
		if ( self::get_cart_subscription_length() > 0 && self::get_cart_subscription_length() == self::get_cart_subscription_interval() ) {
			$subscription_details += array(
				'subscription_length' => self::get_cart_subscription_length()
			);
		}

		// Don't show up front fees when there is no trial period and no sign up fee
		if ( self::get_cart_subscription_trial_length() == 0 && self::get_cart_subscription_sign_up_fee() == 0 ) {
			$subscription_details['initial_amount'] = '';
		} elseif ( self::get_cart_subscription_trial_length() > 0 && self::get_cart_subscription_sign_up_fee() == 0 ) {
			/* The order total of a subscription with a free trial is equal to the recurring amount (instead of 0), because an order won't be paid for if it has a total of 0, a patch to allow payment on orders with 0 is coming in WC 1.7 */
			$subscription_details['initial_amount'] = '';
		}

		$subscription_string = WC_Subscriptions_Manager::get_subscription_price_string( $subscription_details );

		return $subscription_string;
	}

	/**
	 * Checks the cart to see if it contains a subscription product. 
	 * 
	 * @since 1.0
	 */
	public static function cart_contains_subscription() {
		global $woocommerce;

		$contains_subscription = false;

		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
					$contains_subscription = true;
					break;
				}
			}
		}

		return $contains_subscription;
	}

	/**
	 * Checks the cart to see if it contains a subscription product with a free trial
	 *
	 * @since 1.2
	 */
	public static function cart_contains_free_trial() {
		return ( self::get_cart_subscription_trial_length() > 0 ) ? true : false;
	}

	/**
	 * Gets the recalculate flag
	 *
	 * @since 1.2
	 */
	public static function get_recalculation_type() {
		return self::$recalculation_type;
	}

	/**
	 * Gets the subscription period from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 * 
	 * @since 1.0
	 */
	public static function get_cart_subscription_period() {
		global $woocommerce;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
				$period = WC_Subscriptions_Product::get_period( $cart_item['product_id'] );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_period', $period );
	}

	/**
	 * Gets the subscription period from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 * 
	 * @since 1.0
	 */
	public static function get_cart_subscription_interval() {
		global $woocommerce;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
				$interval = WC_Subscriptions_Product::get_interval( $cart_item['product_id'] );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_interval', $interval );
	}

	/**
	 * Gets the subscription length from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 * 
	 * @since 1.0
	 */
	public static function get_cart_subscription_length() {
		global $woocommerce;

		$length = 0;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
				$length = WC_Subscriptions_Product::get_length( $cart_item['product_id'] );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_length', $length );
	}

	/**
	 * Gets the subscription length from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 * 
	 * @since 1.1
	 */
	public static function get_cart_subscription_trial_length() {
		global $woocommerce;

		$trial_length = 0;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
				$trial_length = WC_Subscriptions_Product::get_trial_length( $cart_item['product_id'] );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_trial_length', $trial_length );
	}

	/**
	 * Gets the subscription trial period from the cart and returns it as an array (eg. array( 'month', 'day' ) )
	 *
	 * @since 1.2
	 */
	public static function get_cart_subscription_trial_period() {
		global $woocommerce;

		$trial_period = '';

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
				$trial_period = WC_Subscriptions_Product::get_trial_period( $cart_item['product_id'] );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_trial_period', $trial_period );
	}

	/**
	 * Gets the subscription sign up fee for the cart and returns it
	 * 
	 * Currently short-circuits to return just the sign-up fee of the first subscription, because only
	 * one subscription can be purchased at a time. 
	 * 
	 * @since 1.0
	 */
	public static function get_cart_subscription_sign_up_fee() {
		global $woocommerce;

		$sign_up_fee = 0;

		foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) ) {
				$sign_up_fee = WC_Subscriptions_Product::get_sign_up_fee( $cart_item['product_id'] );
				break;
			}
		}

		return apply_filters( 'woocommerce_subscriptions_cart_sign_up_fee', $sign_up_fee );
	}


	/* Total Getters */

	/**
	 * Get tax row amounts with or without compound taxes includes
	 *
	 * @return float price
	 */
	public static function get_recurring_cart_contents_total() {
		global $woocommerce;

		if ( ! $woocommerce->cart->prices_include_tax )
			$cart_contents_total = $woocommerce->cart->recurring_cart_contents_total;
		else
			$cart_contents_total = $woocommerce->cart->recurring_cart_contents_total + $woocommerce->cart->recurring_tax_total;

		return $cart_contents_total;
	}

	/**
	 * Returns the proportion of cart discount that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring item subtotal amount less tax for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_subtotal_ex_tax() {
		global $woocommerce;

		return $woocommerce->cart->recurring_subtotal_ex_tax;
	}

	/**
	 * Returns the proportion of cart discount that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring item subtotal amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_subtotal() {
		global $woocommerce;

		return $woocommerce->cart->recurring_subtotal;
	}

	/**
	 * Returns the proportion of cart discount that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring cart discount amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_discount_cart() {
		global $woocommerce;

		return $woocommerce->cart->recurring_discount_cart;
	}

	/**
	 * Returns the proportion of total discount that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring discount amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_discount_total() {
		global $woocommerce;

		return $woocommerce->cart->recurring_discount_total;
	}

	/**
	 * Returns the amount of shipping tax that is recurring. As shipping only applies
	 * to recurring payments, and only 1 subscription can be purchased at a time, 
	 * this is equal to @see WC_Cart::$shipping_tax_total
	 *
	 * @return double The total recurring shipping tax amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_shipping_tax_total() {
		global $woocommerce;

		return $woocommerce->cart->shipping_tax_total;
	}

	/**
	 * Returns the recurring shipping price . As shipping only applies to recurring
	 * payments, and only 1 subscription can be purchased at a time, this is
	 * equal to @see WC_Cart::shipping_total
	 *
	 * @return double The total recurring shipping amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_shipping_total() {
		global $woocommerce;

		return $woocommerce->cart->shipping_total;
	}

	/**
	 * Returns an array of taxes on an order with their recurring totals.
	 *
	 * @return array Array of tax_id => tax_amounts for items in the cart
	 * @since 1.2
	 */
	public static function get_recurring_taxes() {
		global $woocommerce;

		return $woocommerce->cart->recurring_taxes;
	}

	/**
	 * Get tax row amounts with or without compound taxes includes
	 *
	 * @return double The total recurring tax amount tax for items in the cart (maybe not including compound taxes)
	 * @since 1.2
	 */
	public static function get_recurring_taxes_total( $compound = true ) {
		global $woocommerce;

		$recurring_taxes_total = 0;

		foreach ( self::get_recurring_taxes() as $tax_id => $tax_amount ) {

			if ( ! $compound && $woocommerce->cart->tax->is_compound( $tax_id ) )
				continue;

			$recurring_taxes_total += $tax_amount;
		}

		return $recurring_taxes_total;
	}

	/**
	 * Returns the proportion of total tax on an order that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring tax amount tax for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_total_tax() {
		global $woocommerce;

		return $woocommerce->cart->recurring_tax_total;
	}

	/**
	 * Returns the proportion of total before tax on an order that is recurring for the product specified with $product_id
	 *
	 * @return double The total recurring amount less tax for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_total_ex_tax() {
		return self::get_recurring_total() - self::get_recurring_total_tax() - self::get_recurring_shipping_tax_total();
	}

	/**
	 * Returns the price per period for a subscription in an order.
	 * 
	 * @return double The total recurring amount for items in the cart.
	 * @since 1.2
	 */
	public static function get_recurring_total() {
		global $woocommerce;

		return $woocommerce->cart->recurring_total;
	}


	/* Session management */

	/**
	 * Get the recurring amounts values from the session
	 * 
	 * @since 1.0
	 */
	public static function get_cart_from_session(){
		global $woocommerce;

		if ( is_object( $woocommerce->cart ) ) {
			foreach ( self::get_recurring_totals_fields() as $field => $default_value ) {
				if ( isset( $woocommerce->session ) ) {
					$woocommerce->cart->{$field} = isset( $woocommerce->session->$field ) ? $woocommerce->session->$field : $default_value;
				} else { // WC 1.x
					$woocommerce->cart->{$field} = isset( $_SESSION[$field] ) ? $_SESSION[$field] : $default_value;
				}
			}
		}
	}

	/**
	 * Store the sign-up fee cart values in the session
	 * 
	 * @since 1.0
	 */
	public static function set_session(){
		global $woocommerce;

		foreach ( self::get_recurring_totals_fields() as $field => $default_value ) {

			$value = isset( $woocommerce->cart->{$field} ) ? $woocommerce->cart->{$field} : $default_value;

			if ( isset( $woocommerce->session ) )
				$woocommerce->session->$field = $value;
			else // WC 1.x
				$_SESSION[$field] = $value;
		}
	}

	/**
	 * Reset the sign-up fee fields in the current session
	 * 
	 * @since 1.0
	 */
	public static function reset(){
		global $woocommerce;

		foreach ( self::get_recurring_totals_fields() as $field => $default_value ) {
			$woocommerce->cart->{$field} = $default_value;

			if ( isset( $woocommerce->session ) )
				unset( $woocommerce->session->$field );
			else // WC 1.x
				unset( $_SESSION[$field] );

		}

	}

	/**
	 * Returns an array of the recurring total fields
	 * 
	 * @since 1.2
	 */
	public static function get_recurring_totals_fields(){
		return array(
			'recurring_cart_contents_total' => 0,
			'recurring_discount_cart' => 0,
			'recurring_discount_total' => 0,
			'recurring_subtotal' => 0,
			'recurring_subtotal_ex_tax' => 0,
			'recurring_taxes' => array(),
			'recurring_tax_total' => 0,
			'recurring_total' => 0
		);
	}


	/* Deprecated */

	/**
	 * Returns the formatted subscription price string for an item
	 *
	 * @since 1.0
	 */
	public static function get_cart_item_price_html( $price_string, $cart_item ) {

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		return $price_string;
	}

	/**
	 * Returns either the total if prices include tax because this doesn't include tax, or the 
	 * subtotal if prices don't includes tax, because this doesn't include tax. 
	 *
	 * @return string formatted price
	 *
	 * @since 1.0
	 */
	public static function get_cart_contents_total( $cart_contents_total ) {

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		return $cart_contents_total;
	}

	/**
	 * Calculate totals for the sign-up fees in the cart, based on @see WC_Cart::calculate_totals()
	 * 
	 * @since 1.0
	 */
	public static function calculate_sign_up_fee_totals() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );
	}

	/**
	 * Function to apply discounts to a product and get the discounted price (before tax is applied)
	 * 
	 * @param mixed $values
	 * @param mixed $price
	 * @param bool $add_totals (default: false)
	 * @return float price
	 * @since 1.0
	 */
	public static function get_discounted_price( $values, $price, $add_totals = false ){

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		return $price;
	}

	/**
	 * Function to apply product discounts after tax
	 * 
	 * @param mixed $values
	 * @param mixed $price
	 * @since 1.0
	 */
	public static function apply_product_discounts_after_tax( $values, $price ){
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );
	}

	/**
	 * Function to apply cart discounts after tax
	 * 
	 * @since 1.0
	 */
	public static function apply_cart_discounts_after_tax(){
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );
	}

	/**
	 * Get tax row amounts with or without compound taxes includes
	 *
	 * @return float price
	 */
	public static function get_sign_up_taxes_total( $compound = true ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );
		return 0;
	}

	public static function get_sign_up_fee_fields() {

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2' );

		return array(
			'cart_contents_sign_up_fee_total',
			'cart_contents_sign_up_fee_count',
			'sign_up_fee_total',
			'sign_up_fee_subtotal',
			'sign_up_fee_subtotal_ex_tax',
			'sign_up_fee_tax_total',
			'sign_up_fee_taxes',
			'sign_up_fee_discount_cart',
			'sign_up_fee_discount_total'
		);
	}


	/* Ambigious getters replaced with explict get_formatted_x functions */

	/**
	 * Returns the subtotal for a cart item including the subscription period and duration details
	 *
	 * @since 1.0
	 */
	public static function get_product_subtotal( $product_subtotal, $product ){
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_product_subtotal( $product_subtotal, $product )' );
		return self::get_formatted_product_subtotal( $product_subtotal, $product );
	}

	/**
	 * Returns a string with the cart discount and subscription period.
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_discounts_before_tax( $discount, $cart ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_discounts_before_tax( $discount )' );
		return self::get_formatted_discounts_before_tax( $total );
	}

	/**
	 * Gets the order discount amount - these are applied after tax
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_discounts_after_tax( $discount, $cart ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_discounts_after_tax( $discount )' );
		return self::get_formatted_discounts_after_tax( $total );
	}

	/**
	 * Includes the sign-up fee subtotal in the subtotal displayed in the cart.
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_cart_subtotal( $cart_subtotal, $compound, $cart ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_cart_subtotal( $cart_subtotal, $compound, $cart )' );
		return self::get_formatted_cart_subtotal( $total, $compound, $cart );
	}

	/**
	 * Appends the cart subscription string to a cart total using the @see self::get_cart_subscription_string and then returns it. 
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_total( $total ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_total( $total )' );
		return self::get_formatted_total( $total );
	}

	/**
	 * Appends the cart subscription string to a cart total using the @see self::get_cart_subscription_string and then returns it. 
	 *
	 * @deprecated 1.2
	 * @since 1.0
	 */
	public static function get_total_ex_tax( $total_ex_tax ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.2', __CLASS__ .'::get_formatted_total_ex_tax( $total_ex_tax )' );
		return self::get_formatted_total_ex_tax( $total_ex_tax );
	}


}

WC_Subscriptions_Cart::init();

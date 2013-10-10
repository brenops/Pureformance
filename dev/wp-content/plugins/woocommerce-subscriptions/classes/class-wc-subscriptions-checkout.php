<?php
/**
 * Subscriptions Checkout
 * 
 * Extends the WooCommerce checkout class to add subscription meta on checkout.
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Checkout
 * @category	Class
 * @author		Brent Shepherd
 */
class WC_Subscriptions_Checkout {

	private static $signup_option_changed = false;

	private static $guest_checkout_option_changed = false;

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 * 
	 * @since 1.0
	 */
	public static function init(){

		// Add the order item meta for WC 1.x - 'new_order_item' was removed in WC 2.0
		add_filter( 'new_order_item', __CLASS__ . '::add_order_item_meta_old', 10, 1 );

		// Add the order item meta for WC 2.0+
		add_action( 'woocommerce_add_order_item_meta', __CLASS__ . '::add_order_item_meta', 10, 2 );

		// Add the recurring totals meta
		add_action( 'woocommerce_checkout_update_order_meta', __CLASS__ . '::add_order_meta', 10, 2 );

		// Make sure users can register on checkout (before any other hooks before checkout)
		add_action( 'woocommerce_before_checkout_form', __CLASS__ . '::make_checkout_registration_possible', -1 );

		// Restore the settings after switching them for the checkout form
		add_action( 'woocommerce_after_checkout_form', __CLASS__ . '::restore_checkout_registration_settings', 100 );

		// Make sure guest checkout is not enabled in option param passed to WC JS 
		add_filter( 'woocommerce_params', __CLASS__ . '::filter_woocommerce_script_paramaters', 10, 1 );

		// Force checkout during checkout process
		add_action( 'woocommerce_before_checkout_process', __CLASS__ . '::force_registration_during_checkout', 10 );
	}

	/**
	 * When a new order is inserted, add subscriptions related order meta.
	 *
	 * @since 1.0
	 */
	public static function add_order_meta( $order_id, $posted ) {
		global $woocommerce;

		if( WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) { // This works because the 'woocommerce_add_order_item_meta' runs before the 'woocommerce_checkout_update_order_meta' hook

			// Set the recurring totals so totals display correctly on order page
			update_post_meta( $order_id, '_order_recurring_discount_cart', WC_Subscriptions_Cart::get_recurring_discount_cart() );
			update_post_meta( $order_id, '_order_recurring_discount_total', WC_Subscriptions_Cart::get_recurring_discount_total() );
			update_post_meta( $order_id, '_order_recurring_shipping_tax_total', WC_Subscriptions_Cart::get_recurring_shipping_tax_total() );
			update_post_meta( $order_id, '_order_recurring_tax_total', WC_Subscriptions_Cart::get_recurring_total_tax() );
			update_post_meta( $order_id, '_order_recurring_total', WC_Subscriptions_Cart::get_recurring_total() );

			// Get recurring taxes into same format as _order_taxes
			$order_recurring_taxes = array();

			foreach ( WC_Subscriptions_Cart::get_recurring_taxes() as $tax_key => $tax_amount ) {

				$is_compound = ( $woocommerce->cart->tax->is_compound( $tax_key ) ) ? 1 : 0;

				if ( isset( $woocommerce->cart->taxes[$tax_key] ) ) {
					$cart_tax     = $tax_amount;
					$shipping_tax = 0;
				} else {
					$cart_tax     = 0;
					$shipping_tax = $tax_amount;
				}

				if ( function_exists( 'woocommerce_add_order_item_meta' ) ) { // WC 2.0+

					$item_id = woocommerce_add_order_item( $order_id, array(
						'order_item_name' => $woocommerce->cart->tax->get_rate_code( $tax_key ),
						'order_item_type' => 'recurring_tax'
					) );

					if ( $item_id ) {
						woocommerce_add_order_item_meta( $item_id, 'rate_id', $tax_key );
						woocommerce_add_order_item_meta( $item_id, 'label', $woocommerce->cart->tax->get_rate_label( $tax_key ) );
						woocommerce_add_order_item_meta( $item_id, 'compound', $is_compound );
						woocommerce_add_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $cart_tax ) );
						woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $shipping_tax ) );
					}

				} else { // WC 1.x

					$order_recurring_taxes[] = array(
						'label' 		=> $woocommerce->cart->tax->get_rate_label( $tax_key ),
						'compound' 		=> $is_compound,
						'cart_tax' 		=> woocommerce_format_total( $cart_tax ),
						'shipping_tax' 	=> woocommerce_format_total( $shipping_tax )
					);

					// Inefficient but keeps WC 1.x code grouped together
					update_post_meta( $order_id, '_order_recurring_taxes', $order_recurring_taxes );
				}
			}

			$payment_gateways = $woocommerce->payment_gateways->payment_gateways();

			if ( isset( $payment_gateways[$posted['payment_method']] ) && ! $payment_gateways[$posted['payment_method']]->supports( 'subscriptions' ) )
				update_post_meta( $order_id, '_wcs_requires_manual_renewal', 'true' );

		}
	}

	/**
	 * Add each subscription product's details to an order so that the state of the subscription persists even when a product is changed
	 *
	 * For the pre WC 2.0 method, @see self::add_order_item_meta_old()
	 *
	 * @since 1.2.5
	 */
	public static function add_order_item_meta( $item_id, $values ) {
		global $woocommerce;

		if ( WC_Subscriptions_Product::is_subscription( $values['product_id'] ) ) {

			// Add subscription details so order state persists even when a product is changed
			woocommerce_add_order_item_meta( $item_id, '_subscription_period', WC_Subscriptions_Product::get_period( $values['product_id'] ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_interval', WC_Subscriptions_Product::get_interval( $values['product_id'] ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_length', WC_Subscriptions_Product::get_length( $values['product_id'] ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_trial_length', WC_Subscriptions_Product::get_trial_length( $values['product_id'] ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_trial_period', WC_Subscriptions_Product::get_trial_period( $values['product_id'] ) );
			woocommerce_add_order_item_meta( $item_id, '_subscription_recurring_amount', $woocommerce->cart->base_recurring_prices[$values['product_id']] ); // WC_Subscriptions_Product::get_price() would return a price without filters applied
			woocommerce_add_order_item_meta( $item_id, '_subscription_sign_up_fee', WC_Subscriptions_Product::get_sign_up_fee( $values['product_id'] ) );

			// Calculated recurring amounts for the item
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_total', $woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_total'] );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_tax', $woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_tax'] );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_subtotal', $woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal'] );
			woocommerce_add_order_item_meta( $item_id, '_recurring_line_subtotal_tax', $woocommerce->cart->recurring_cart_contents[$values['product_id']]['recurring_line_subtotal_tax'] );
		}
	}

	/**
	 * Add each subscription product's details to an order for versions of WooCommerce 1.x.
	 *
	 * @since 1.2
	 */
	public static function add_order_item_meta_old( $order_item ) {
		global $woocommerce;

		if ( WC_Subscriptions_Product::is_subscription( $order_item['id'] ) ) {

			// Make sure existing meta persists
			$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );

			// Add subscription details so order state persists even when a product is changed
			$item_meta->add( '_subscription_period', WC_Subscriptions_Product::get_period( $order_item['id'] ) );
			$item_meta->add( '_subscription_interval', WC_Subscriptions_Product::get_interval( $order_item['id'] ) );
			$item_meta->add( '_subscription_length', WC_Subscriptions_Product::get_length( $order_item['id'] ) );
			$item_meta->add( '_subscription_trial_length', WC_Subscriptions_Product::get_trial_length( $order_item['id'] ) );
			$item_meta->add( '_subscription_trial_period', WC_Subscriptions_Product::get_trial_period( $order_item['id'] ) );
			$item_meta->add( '_subscription_recurring_amount', $woocommerce->cart->base_recurring_prices[$order_item['id']] ); // WC_Subscriptions_Product::get_price() would return a price without filters applied
			$item_meta->add( '_subscription_sign_up_fee', WC_Subscriptions_Product::get_sign_up_fee( $order_item['id'] ) );

			// Calculated recurring amounts for the item
			$item_meta->add( '_recurring_line_total', $woocommerce->cart->recurring_cart_contents[$order_item['id']]['recurring_line_total'] );
			$item_meta->add( '_recurring_line_tax', $woocommerce->cart->recurring_cart_contents[$order_item['id']]['recurring_line_tax'] );
			$item_meta->add( '_recurring_line_subtotal', $woocommerce->cart->recurring_cart_contents[$order_item['id']]['recurring_line_subtotal'] );
			$item_meta->add( '_recurring_line_subtotal_tax', $woocommerce->cart->recurring_cart_contents[$order_item['id']]['recurring_line_subtotal_tax'] );

			$order_item['item_meta'] = $item_meta->meta;
		}

		return $order_item;
	}

	/**
	 * If shopping cart contains subscriptions, make sure a user can register on the checkout page
	 *
	 * @since 1.0
	 */
	public static function make_checkout_registration_possible( ) {
		global $woocommerce;

		if ( WC_Subscriptions_Cart::cart_contains_subscription() && ! is_user_logged_in() ) {

			if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) { // WC 2.0+

				// Make sure users can sign up
				if ( false === $woocommerce->checkout()->enable_signup ) {
					$woocommerce->checkout()->enable_signup = true;
					self::$signup_option_changed = true;
				}

				// Make sure users are required to register an account
				if ( true === $woocommerce->checkout()->enable_guest_checkout ) {
					$woocommerce->checkout()->enable_guest_checkout = false;
					self::$guest_checkout_option_changed = true;

					if ( ! is_user_logged_in() )
						$woocommerce->checkout()->must_create_account = true;
				}

			} else { // WC 1.x

				// Make sure users can sign up
				if ( 'no' == get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) ) {
					update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
					self::$signup_option_changed = true;
				}

				// Make sure users are required to register an account
				if ( 'yes' == get_option( 'woocommerce_enable_guest_checkout' ) ) {
					update_option( 'woocommerce_enable_guest_checkout', 'no' );
					self::$guest_checkout_option_changed = true;
				}

			}

		}

	}

	/**
	 * After displaying the checkout form, restore the store's original registration settings.
	 *
	 * @since 1.1
	 */
	public static function restore_checkout_registration_settings( $checkout ) {
		global $woocommerce;

		if ( self::$signup_option_changed ) {
			if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
				$woocommerce->checkout()->enable_signup = false;
			} else {
				update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' );
			}
		}

		if ( self::$guest_checkout_option_changed ) {
			if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
				$woocommerce->checkout()->enable_guest_checkout = true;
				if ( ! is_user_logged_in() ) // Also changed must_create_account
					$woocommerce->checkout()->must_create_account = false;
			} else {
				update_option( 'woocommerce_enable_guest_checkout', 'yes' );
			}
		}
	}

	/**
	 * Also make sure the guest checkout option value passed to the woocommerce.js forces registration.
	 * Otherwise the registration form is hidden by woocommerce.js.
	 *
	 * @since 1.1
	 */
	public static function filter_woocommerce_script_paramaters( $woocommerce_params ) {

		if ( WC_Subscriptions_Cart::cart_contains_subscription() && ! is_user_logged_in() && $woocommerce_params['option_guest_checkout'] == 'yes' )
			$woocommerce_params['option_guest_checkout'] = 'no';

		return $woocommerce_params;
	}

	/**
	 * During the checkout process, force registration when the cart contains a subscription.
	 *
	 * @since 1.1
	 */
	public static function force_registration_during_checkout( $woocommerce_params ) {

		if ( WC_Subscriptions_Cart::cart_contains_subscription() && ! is_user_logged_in() )
			$_POST['createaccount'] = 1;

	}


}

WC_Subscriptions_Checkout::init();

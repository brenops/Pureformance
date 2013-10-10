<?php
/**
 * Individual Subscription Product API
 * 
 * An API for accessing details of a subscription product.
 * 
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Product
 * @category	Class
 * @author		Brent Shepherd
 * @since		1.0
 */
class WC_Subscriptions_Product {

	/**
	 * Set up the class, including it's hooks & filters, when the file is loaded.
	 *
	 * @since 1.0
	 **/
	public static function init() {

		// Because the vanilla price meta field is empty, we need to output our custom subscription description
		add_filter( 'woocommerce_price_html', __CLASS__ . '::get_price_html', 10, 2 );
		add_filter( 'woocommerce_sale_price_html', __CLASS__ . '::get_price_html', 10, 2 );

		// Gravity Forms Add-ons
		add_filter( 'woocommerce_gform_base_price', __CLASS__ . '::get_gravity_form_prices', 10, 2 );
		add_filter( 'woocommerce_gform_total_price', __CLASS__ . '::get_gravity_form_prices', 10, 2 );
		add_filter( 'woocommerce_gform_variation_total_price', __CLASS__ . '::get_gravity_form_prices', 10, 2 );
	}

	/**
	 * Checks a given product to determine if it is a subscription.
	 *
	 * @param $product_id Either a product object or product's post ID.
	 * @since 1.0
	 */
	public static function is_subscription( $product_id ) {

		$is_subscription = false;

		if ( is_object( $product_id ) )
			$product_id = $product_id->id;

		$post_type = get_post_type( $product_id );

		if ( 'product' == $post_type ) { // Only products can be subscriptions

			$product = WC_Subscriptions::get_product( $product_id );

			if ( $product->is_type( WC_Subscriptions::$name ) )
				$is_subscription = true;

		}

		return apply_filters( 'woocommerce_is_subscription', $is_subscription, $product_id );
	}

	/**
	 * Output subscription string as the price html
	 *
	 * @since 1.0
	 */
	public static function get_price_html( $price, $product ) {

		if ( self::is_subscription( $product ) )
			$price = self::get_price_string( $product, array( 'price' => $price ) );

		return $price;
	}

	/**
	 * Output subscription string in Gravity Form fields.
	 *
	 * @since 1.1
	 */
	public static function get_gravity_form_prices( $price, $product ) {

		if ( self::is_subscription( $product ) )
			$price = self::get_price_string( $product, array( 'price' => $price, 'subscription_length' => false, 'sign_up_fee' => false, 'trial_length' => false ) );

		return $price;
	}

	/**
	 * Returns a string representing the details of the subscription. 
	 * 
	 * For example "$20 per Month for 3 Months with a $10 sign-up fee".
	 * 
	 * @param $product WC_Product | Int A WC_Product object or ID of a WC_Product.
	 * @param $inclusions array An associative array of flags to indicate how to calculate the price and what to include, values:
	 *			'tax_calculation'     => false to ignore tax, 'include_tax' or 'exclude_tax' To indicate that tax should be added or excluded respectively
	 *			'subscription_length' => true to include subscription's length (default) or false to exclude it
	 *			'sign_up_fee'         => true to include subscription's sign up fee (default) or false to exclude it
	 *			'price'               => string a price to short-circuit the price calculations and use in a string for the product
	 * @since 1.0
	 */
	public static function get_price_string( $product, $include = array() ) {

		if ( ! is_object( $product ) )
			$product = WC_Subscriptions::get_product( $product );

		if ( ! self::is_subscription( $product ) )
			return;

		$include = wp_parse_args( $include, array(
				'tax_calculation'     => false,
				'subscription_length' => true,
				'sign_up_fee'         => true,
				'trial_length'        => true
			)
		);

		$base_price          = self::get_price( $product );
		$sign_up_fee         = self::get_sign_up_fee( $product );

		if ( $include['tax_calculation'] != false ) {

			if ( $include['tax_calculation'] == 'exclude_tax' ) { // Subtract Tax

				$tax_per_period = self::calculate_tax_for_subscription( $base_price, $product );

				if ( isset( $include['price'] ) )
					$price = $include['price'];
				else
					$price = woocommerce_price( $base_price - $tax_per_period );

				if ( $sign_up_fee > 0 ) {
					$sign_up_tax = self::calculate_tax_for_subscription( $sign_up_fee, $product );
					$sign_up_fee = $sign_up_fee - $sign_up_tax;
				}

			} else { // Add Tax

				$tax_per_period = self::calculate_tax_for_subscription( $base_price, $product, true );

				if ( isset( $include['price'] ) )
					$price = $include['price'];
				else
					$price = woocommerce_price( $base_price + $tax_per_period );

				if ( $sign_up_fee > 0 ) {
					$sign_up_tax = self::calculate_tax_for_subscription( $sign_up_fee, $product, true );
					$sign_up_fee = $sign_up_fee - $sign_up_tax;
				}

			}

		} else {

			if ( isset( $include['price'] ) )
				$price = $include['price'];
			else
				$price = woocommerce_price( $base_price );

		}

		$billing_interval    = self::get_interval( $product );
		$subscription_length = self::get_length( $product );
		$trial_length        = self::get_trial_length( $product );
		$trial_period        = self::get_trial_period( $product );

		if ( $include['subscription_length'] )
			$ranges = WC_Subscriptions_Manager::get_subscription_ranges( self::get_period( $product ) );

		if ( $include['subscription_length'] && $subscription_length != 0 )
			$include_length = true;
		else
			$include_length = false;

		if ( $include_length && $subscription_length == $billing_interval )
			$subscription_string = $price; // Only for one billing period so show "$5 for 3 months" instead of "$5 every 3 months for 3 months"
		else
			$subscription_string = sprintf( _n( ' %s / %s', ' %s every %s', $billing_interval, WC_Subscriptions::$text_domain ), $price, WC_Subscriptions_Manager::get_subscription_period_strings( $billing_interval, self::get_period( $product ) ) );

		// Add the length to the end
		if ( $include_length )
			$subscription_string = sprintf( __( '%s for %s', WC_Subscriptions::$text_domain ), $subscription_string, $ranges[$subscription_length] );

		if ( $include['trial_length'] && $trial_length != 0 ) {
			$trial_string = WC_Subscriptions_Manager::get_subscription_trial_period_strings( $trial_length, $trial_period );
			$subscription_string = sprintf( __( '%s with %s free trial', WC_Subscriptions::$text_domain ), $subscription_string, $trial_string );
		}

		if ( $include['sign_up_fee'] && self::get_sign_up_fee( $product ) > 0 )
			$subscription_string = sprintf( __( '%s and a %s sign-up fee', WC_Subscriptions::$text_domain ), $subscription_string, woocommerce_price( $sign_up_fee ) );

		return apply_filters( 'woocommerce_subscriptions_product_price_string', $subscription_string, $product, $include );
	}

	/**
	 * Returns the price per period for a product if it is a subscription.
	 * 
	 * @param $product mixed A WC_Product object or product ID
	 * @return float The price charged per period for the subscription, or an empty string if the product is not a subscription.
	 * @since 1.0
	 */
	public static function get_price( $product ) {

		if ( ! is_object( $product ) )
			$product = WC_Subscriptions::get_product( $product );

		if ( ! self::is_subscription( $product ) || empty( $product->product_custom_fields['_subscription_price'][0] ) )
			return '';

		return $product->product_custom_fields['_subscription_price'][0];
	}

	/**
	 * Returns the subscription period for a product, if it's a subscription.
	 * 
	 * @param $product mixed A WC_Product object or product ID
	 * @return string A string representation of the period, either Day, Week, Month or Year, or an empty string if product is not a subscription.
	 * @since 1.0
	 */
	public static function get_period( $product ) {

		if ( ! is_object( $product ) )
			$product = WC_Subscriptions::get_product( $product );

		if ( ! self::is_subscription( $product ) || empty( $product->product_custom_fields['_subscription_period'][0] ) )
			return '';

		return $product->product_custom_fields['_subscription_period'][0];
	}

	/**
	 * Returns the subscription interval for a product, if it's a subscription.
	 * 
	 * @param $product mixed A WC_Product object or product ID
	 * @return string A string representation of the period, either Day, Week, Month or Year, or an empty string if product is not a subscription.
	 * @since 1.0
	 */
	public static function get_interval( $product ) {

		if ( ! is_object( $product ) )
			$product = WC_Subscriptions::get_product( $product );

		if ( ! self::is_subscription( $product ) || empty( $product->product_custom_fields['_subscription_period_interval'][0] ) )
			return 1;

		return $product->product_custom_fields['_subscription_period_interval'][0];
	}

	/**
	 * Returns the length of a subscription product, if it is a subscription.
	 * 
	 * @param $product mixed A WC_Product object or product ID
	 * @return int An integer representing the length of the subscription, or 0 if the product is not a subscription or the subscription continues for perpetuity
	 * @since 1.0
	 */
	public static function get_length( $product ) {

		if ( ! is_object( $product ) )
			$product = WC_Subscriptions::get_product( $product );

		if ( ! self::is_subscription( $product ) || $product->product_custom_fields['_subscription_length'][0] == 0 )
			return 0;

		return $product->product_custom_fields['_subscription_length'][0];
	}

	/**
	 * Returns the trial length of a subscription product, if it is a subscription.
	 * 
	 * @param $product mixed A WC_Product object or product ID
	 * @return int An integer representing the length of the subscription trial, or 0 if the product is not a subscription or there is no trial
	 * @since 1.0
	 */
	public static function get_trial_length( $product ) {

		if ( ! is_object( $product ) )
			$product = WC_Subscriptions::get_product( $product );

		if ( ! self::is_subscription( $product ) || ! isset( $product->product_custom_fields['_subscription_trial_length'][0] ) )
			return 0;

		return $product->product_custom_fields['_subscription_trial_length'][0];
	}

	/**
	 * Returns the trial period of a subscription product, if it is a subscription.
	 *
	 * @param $product mixed A WC_Product object or product ID
	 * @return string A string representation of the period, either Day, Week, Month or Year, or an empty string if product is not a subscription or there is no trial
	 * @since 1.2
	 */
	public static function get_trial_period( $product ) {

		if ( ! is_object( $product ) )
			$product = WC_Subscriptions::get_product( $product );

		if ( ! self::is_subscription( $product ) )
			return '';

		// Backward compatibility
		if ( ! isset( $product->product_custom_fields['_subscription_trial_period'][0] ) )
			return self::get_period( $product );

		return $product->product_custom_fields['_subscription_trial_period'][0];
	}

	/**
	 * Returns the sign-up fee for a subscription, if it is a subscription.
	 * 
	 * @param $product mixed A WC_Product object or product ID
	 * @return float The value of the sign-up fee, or 0 if the product is not a subscription or the subscription has no sign-up fee
	 * @since 1.0
	 */
	public static function get_sign_up_fee( $product ) {

		if ( ! is_object( $product ) )
			$product = WC_Subscriptions::get_product( $product );

		if ( ! self::is_subscription( $product ) || empty( $product->product_custom_fields['_subscription_sign_up_fee'][0] ) )
			return 0;

		return $product->product_custom_fields['_subscription_sign_up_fee'][0];
	}

	/**
	 * Takes a subscription product's ID and returns the date on which the subscription product will expire, 
	 * based on the subscription's length and calculated from either the $order_date if specified, or the current date/time.
	 * 
	 * @param $product_id int The product/post ID of the subscription
	 * @param $order_date mixed A MySQL formatted date/time string from which to calculate the expiration date, or empty (default), which will use today's date/time.
	 * @since 1.0
	 */
	public static function get_expiration_date( $product_id, $order_date = '' ) {

		$subscription_length = self::get_length( $product_id );

		if( $subscription_length > 0 ){

			$subscription_period = self::get_period( $product_id );
			$trial_period        = self::get_trial_period( $product_id );
			$trial_length        = self::get_trial_length( $product_id );

			if( empty( $order_date ) )
				$order_date = date( 'Y-m-d H:i:s' );

			$expiration_date = date( 'Y-m-d H:i:s', strtotime( "+ $trial_length {$trial_period}s + $subscription_length {$subscription_period}s", strtotime( $order_date ) ) );

		} else {

			$expiration_date = 0;

		}

		return $expiration_date;
	}

	/**
	 * Takes a subscription product's ID and returns the date on which the subscription trial will expire,
	 * based on the subscription's trial length and calculated from either the $order_date if specified, or the current date/time.
	 * 
	 * @param $product_id int The product/post ID of the subscription
	 * @param $order_date mixed A MySQL formatted date/time string from which to calculate the expiration date, or empty (default), which will use today's date/time.
	 * @since 1.0
	 */
	public static function get_trial_expiration_date( $product_id, $order_date = '' ) {

		$trial_period = self::get_trial_period( $product_id );
		$trial_length = self::get_trial_length( $product_id );

		if( $trial_length > 0 ){

			if( empty( $order_date ) )
				$order_date = date( 'Y-m-d H:i:s' );

			$trial_expiration_date = date( 'Y-m-d H:i:s', strtotime( "+ $trial_length {$trial_period}s", strtotime( $order_date ) ) );

		} else {

			$trial_expiration_date = 0;

		}

		return $trial_expiration_date;
	}

	/**
	 * Returns the sign-up fee for a subscription excluding tax - ignores tax_class filters since the price may *include* tax and thus needs subtracting
	 * 
	 * @param $product mixed A WC_Product object or product ID
	 * @return float The value of the sign-up fee, or 0 if the product is not a subscription or the subscription has no sign-up fee
	 * @since 1.0
	 */
	public static function get_sign_up_fee_excluding_tax( $product ) {

		$price = self::get_sign_up_fee( $product );

		if ( $product->is_taxable() && get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) :

			$_tax = new WC_Tax();

			$tax_rates  = $_tax->get_shop_base_rate( $product->tax_class );
			$taxes      = $_tax->calc_tax( $price, $tax_rates, true );
			$tax_amount = $_tax->get_tax_total( $taxes );
			$price      = round( $price - $tax_amount, 2);

		endif;

		return $price;
	}

	/**
	 * Calculates a price (could be per period price or sign-up fee) for a subscription less tax
	 * if the subscription is taxable and the prices in the store include tax.
	 * 
	 * Based on the WC_Product::get_price_excluding_tax() function.
	 *
	 * @param $price float The price to adjust based on taxes
	 * @param $product WC_Product The product the price belongs too (needed to determine tax class)
	 * @since 1.0
	 */
	public static function calculate_tax_for_subscription( $price, $product, $deduct_base_taxes = false ) {

		if ( $product->is_taxable() ) {

			$tax = new WC_Tax();

			$base_tax_rates = $tax->get_shop_base_rate( $product->tax_class );
			$tax_rates      = $tax->get_rates( $product->get_tax_class() ); // This will get the base rate unless we're on the checkout page

			if ( $deduct_base_taxes && get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {

				$base_taxes = $tax->calc_tax( $price, $base_tax_rates, true );
				$taxes      = $tax->calc_tax( $price - array_sum( $base_taxes ), $tax_rates, false );

			} elseif ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {

				$taxes = $tax->calc_tax( $price, $base_tax_rates, true );

			} else {

				$taxes = $tax->calc_tax( $price, $base_tax_rates, false );

			}

			$tax_amount = $tax->get_tax_total( $taxes );

		} else {

			$tax_amount = 0;

		}

		return $tax_amount;
	}
}

WC_Subscriptions_Product::init();

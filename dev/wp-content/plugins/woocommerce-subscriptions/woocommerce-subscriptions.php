<?php
/**
 * Plugin Name: WooCommerce Subscriptions
 * Plugin URI: http://www.woothemes.com/products/woocommerce-subscriptions/
 * Description: Sell products and services with recurring payments in your WooCommerce Store.
 * Author: Brent Shepherd
 * Author URI: http://find.brentshepherd.com/
 * Version: 1.2.5
 *
 * Copyright 2013  Leonard's Ego Pty. Ltd.  (email : freedoms@leonardsego.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package		WooCommerce Subscriptions
 * @author		Brent Shepherd
 * @since		1.0
 */

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '6115e6d7e297b623a169fdcf5728b224', '27147' );

/**
 * Check if WooCommerce is active, and if it isn't, disable Subscriptions.
 *
 * @since 1.0
 */
if ( ! is_woocommerce_active() ) {
	add_action( 'admin_notices', 'WC_Subscriptions::woocommerce_inactive_notice' );
	return;
}

require_once( 'classes/class-wc-subscriptions-coupon.php' );

require_once( 'classes/class-wc-subscriptions-product.php' );

require_once( 'classes/class-wc-subscriptions-admin.php' );

require_once( 'classes/class-wc-subscriptions-manager.php' );

require_once( 'classes/class-wc-subscriptions-cart.php' );

require_once( 'classes/class-wc-subscriptions-order.php' );

require_once( 'classes/class-wc-subscriptions-renewal-order.php' );

require_once( 'classes/class-wc-subscriptions-checkout.php' );

require_once( 'classes/class-wc-subscriptions-email.php' );

require_once( 'classes/gateways/class-wc-subscriptions-payment-gateways.php' );

require_once( 'classes/gateways/gateway-paypal-standard-subscriptions.php' );

require_once( 'classes/class-wc-subscriptions-upgrader.php' );

/**
 * The main subscriptions class.
 *
 * @since 1.0
 */
class WC_Subscriptions {

	public static $name = 'subscription';

	public static $text_domain = 'woocommerce-subscriptions';

	public static $activation_transient = 'woocommerce_subscriptions_activated';

	public static $plugin_file = __FILE__;

	public static $version = '1.2.5';

	/**
	 * Set up the class, including it's hooks & filters, when the file is loaded.
	 *
	 * @since 1.0
	 **/
	public static function init() {

		add_action( 'admin_init', __CLASS__ . '::maybe_activate_woocommerce_subscriptions' );

		register_deactivation_hook( __FILE__, __CLASS__ . '::deactivate_woocommerce_subscriptions' );

		// Overide the WC default "Add to Cart" text to "Sign Up Now" (in various places/templates)
		add_filter( 'add_to_cart_text', __CLASS__ . '::add_to_cart_text' );
		add_filter( 'single_add_to_cart_text', __CLASS__ . '::add_to_cart_text' );
		add_filter( 'woocommerce_order_button_text', __CLASS__ . '::order_button_text' );
		add_action( 'woocommerce_' . self::$name . '_add_to_cart', __CLASS__ . '::subscription_add_to_cart', 30 );

		// Redirect the user immediately to the checkout page after clicking "Sign Up Now" buttons to encourage immediate checkout
		add_filter( 'add_to_cart_redirect', __CLASS__ . '::add_to_cart_redirect' );

		// Ensure a subscription is never in the cart with products
		add_filter( 'woocommerce_add_to_cart_validation', __CLASS__ . '::maybe_empty_cart', 10, 3 );

		// Mark subscriptions as individual items
		add_filter( 'woocommerce_is_sold_individually', __CLASS__ . '::is_sold_individually', 10, 2 );

		// Update Order totals via Ajax when a order form is updated
		add_action( 'wp_ajax_woocommerce_subscriptions_update_order_total', __CLASS__ . '::ajax_get_order_totals' );
		add_action( 'wp_ajax_nopriv_woocommerce_subscriptions_update_order_total', __CLASS__ . '::ajax_get_order_totals' );

		// Display Subscriptions on a User's account page
		add_action( 'woocommerce_before_my_account', __CLASS__ . '::get_my_subscriptions_template' );

		// Load translation files
		add_action( 'plugins_loaded', __CLASS__ . '::load_plugin_textdomain' );

		// Load dependant files
		add_action( 'plugins_loaded', __CLASS__ . '::load_dependant_classes' );
	}

	/**
	 * Loads the my-subscriptions.php template on the My Account page.
	 *
	 * @since 1.0
	 */
	public static function get_my_subscriptions_template() {
		woocommerce_get_template( 'myaccount/my-subscriptions.php', array(), '', plugin_dir_path( __FILE__ ) . 'templates/' );
	}

	/**
	 * Output a redirect URL when an item is added to the cart when a subscription was already in the cart.
	 *
	 * @since 1.0
	 */
	public static function redirect_ajax_add_to_cart( $fragments ) {
		global $woocommerce;

		$data = array(
			'error' => true,
			'product_url' => $woocommerce->cart->get_cart_url()
		);

		return $data;
	}

	/**
	 * When a subscription is added to the cart, remove other products/subscriptions to
	 * work with PayPal Standard, which only accept one subscription per checkout.
	 *
	 * @since 1.0
	 */
	public static function maybe_empty_cart( $valid, $product_id, $quantity ) {
		global $woocommerce;

		if ( WC_Subscriptions_Product::is_subscription( $product_id ) ) {

			$woocommerce->cart->empty_cart();

		} elseif ( WC_Subscriptions_Cart::cart_contains_subscription() ) {

			self::remove_subscriptions_from_cart();

			$woocommerce->add_error( __( 'A subscription has been removed from your cart. Due to payment gateway restrictions, products and subscriptions can not be purchased at the same time.', self::$text_domain ) );
			$woocommerce->set_messages();

			// Redirect to cart page to remove subscription & notify shopper
			add_filter( 'add_to_cart_fragments', __CLASS__ . '::redirect_ajax_add_to_cart' );

		}

		return $valid;
	}

	/**
	 * Removes all subscription products from the shopping cart.
	 *
	 * @since 1.0
	 */
	public static function remove_subscriptions_from_cart() {
		global $woocommerce;

		foreach( $woocommerce->cart->cart_contents as $cart_item_key => $cart_item )
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['product_id'] ) )
				$woocommerce->cart->set_quantity( $cart_item_key, 0 );
	}

	/**
	 * For a smoother sign up process, tell WooCommerce to redirect the shopper immediately to
	 * the checkout page after she clicks the "Sign Up Now" button
	 *
	 * @param $url string The cart redirect $url WooCommerce determined.
	 * @since 1.0
	 */
	public static function add_to_cart_redirect( $url ) {
		global $woocommerce;

		// If product is of the subscription type
		if ( is_numeric( $_REQUEST['add-to-cart'] ) && WC_Subscriptions_Product::is_subscription( (int) $_REQUEST['add-to-cart'] ) ) {
			// Remove default cart message
			$woocommerce->clear_messages();

			// Redirect to checkout
			$url = $woocommerce->cart->get_checkout_url();
		}

		return $url;
	}

	/**
	 * Subscriptions are individual items so override the WC_Product is_sold_individually function
	 * to reflect this.
	 *
	 * @since 1.0
	 */
	public static function is_sold_individually( $is_individual, $product ) {

		// Sold individually if downloadable, virtual, and the option is enabled
		if ( WC_Subscriptions_Product::is_subscription( $product ) )
			$is_individual = true;

		return $is_individual;
	}

	/**
	 * Override the WooCommerce "Add to Cart" text with "Sign Up Now"
	 *
	 * @since 1.0
	 */
	public static function add_to_cart_text( $button_text ) {
		global $product;

		if ( WC_Subscriptions_Product::is_subscription( $product ) )
			$button_text = get_option( WC_Subscriptions_Admin::$option_prefix . '_add_to_cart_button_text', __( 'Sign Up Now', self::$text_domain ) );

		return $button_text;
	}

	/**
	 * Override the WooCommerce "Place Order" text with "Sign Up Now"
	 *
	 * @since 1.0
	 */
	public static function order_button_text( $button_text ) {
		global $product;

		if ( WC_Subscriptions_Cart::cart_contains_subscription() )
			$button_text = get_option( WC_Subscriptions_Admin::$option_prefix . '_order_button_text', __( 'Sign Up Now', self::$text_domain ) );

		return $button_text;
	}

	/**
	 * Load the subscription add_to_cart template.
	 *
	 * Use the same cart template for subscription as that which is used for simple products. Reduce code duplication
	 * and is made possible by the friendly actions & filters found through WC.
	 *
	 * Not using a custom template both prevents code duplication and helps future proof this extension from core changes.
	 *
	 * @since 1.0
	 */
	public static function subscription_add_to_cart() {

		require_once( plugin_dir_path( __FILE__ ) . 'templates/single-product/add-to-cart/subscription.php' );
	}

	/**
	 * Takes a number and returns the number with its relevant suffix appended, eg. for 2, the function returns 2nd
	 *
	 * @since 1.0
	 */
	public static function append_numeral_suffix( $number ) {

		// If the tens digit of a number is 1, then write "th" after the number. For example: 13th, 19th, 112th, 9311th. http://en.wikipedia.org/wiki/English_numerals
		if ( strlen( $number ) > 1 && substr( $number, -2 ) ) {
			$number_string = sprintf( __( '%sth', self::$text_domain ), $number );
		} else { // Append relevant suffix
			switch( substr( $number, -1 ) ) {
				case 1:
					$number_string = sprintf( __( '%sst', self::$text_domain ), $number );
					break;
				case 2:
					$number_string = sprintf( __( '%snd', self::$text_domain ), $number );
					break;
				case 3:
					$number_string = sprintf( __( '%srd', self::$text_domain ), $number );
					break;
				default:
					$number_string = sprintf( __( '%sth', self::$text_domain ), $number );
					break;
			}
		}

		return apply_filters( 'woocommerce_numeral_suffix', $number_string, $number );
	}


	/*
	 * Plugin House Keeping
	 */

	/**
	 * Called when WooCommerce is inactive to display an inactive notice.
	 *
	 * @since 1.2
	 */
	public static function woocommerce_inactive_notice() { ?>
<div id="message" class="error">
	<p><?php printf( __( '%sWooCommerce Subscriptions is inactive.%s The %sWooCommerce plugin%s must be active for the WooCommerce Subscriptions to work. Please %sinstall & activate WooCommerce%s', self::$text_domain ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url( 'plugins.php' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
</div>
<?php
	}

	/**
	 * Checks on each admin page load if Subscriptions plugin is activated.
	 *
	 * Apparently the official WP API is "lame" and it's far better to use an upgrade routine fired on admin_init: http://core.trac.wordpress.org/ticket/14170
	 *
	 * @since 1.1
	 */
	public static function maybe_activate_woocommerce_subscriptions(){
		global $wpdb;

		$is_active = get_option( WC_Subscriptions_Admin::$option_prefix . '_is_active', false );

		if ( $is_active == false ) {

			// Add the "Subscriptions" product type
			if ( ! get_term_by( 'slug', sanitize_title( self::$name ), 'product_type' ) )
				wp_insert_term( self::$name, 'product_type' );

			// If no Subscription settings exist, its the first activation, so add defaults
			if ( get_option( WC_Subscriptions_Admin::$option_prefix . '_cancelled_role', false ) == false )
				WC_Subscriptions_Admin::add_default_settings();

			add_option( WC_Subscriptions_Admin::$option_prefix . '_is_active', true );

			set_transient( self::$activation_transient, true, 60 * 60 );

			do_action( 'woocommerce_subscriptions_activated' );
		}

	}

	/**
	 * Called when the plugin is deactivated. Deletes the subscription product type and fires an action.
	 *
	 * @since 1.0
	 */
	public static function deactivate_woocommerce_subscriptions() {

		wp_delete_term( self::$name, 'product_type' );

		delete_option( WC_Subscriptions_Admin::$option_prefix . '_is_active' );

		do_action( 'woocommerce_subscriptions_deactivated' );
	}

	/**
	 * Called on plugins_loaded to load any translation files.
	 *
	 * @since 1.1
	 */
	public static function load_plugin_textdomain(){

		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-subscriptions' );

		// Allow upgrade safe, site specific language files in /wp-content/languages/woocommerce-subscriptions/
		load_textdomain( 'woocommerce-subscriptions', WP_LANG_DIR.'/woocommerce/woocommerce-subscriptions-'.$locale.'.mo' );

		$plugin_rel_path = apply_filters( 'woocommerce_subscriptions_translation_file_rel_path', dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Then check for a language file in /wp-content/plugins/woocommerce-subscriptions/languages/ (this will be overriden by any file already loaded)
		load_plugin_textdomain( self::$text_domain, false, $plugin_rel_path );
	}

	/**
	 * Loads classes that depend on WooCommerce base classes.
	 *
	 * @since 1.2.4
	 */
	public static function load_dependant_classes() {
		require_once( 'classes/class-wc-product-subscription.php' );
	}

	/**
	 * Get's a WC_Product using the new core WC @see get_product() function if available, otherwise
	 * instantiating an instance of the WC_Product class.
	 *
	 * @since 1.2.4
	 */
	public static function get_product( $product_id ) {

		if ( function_exists( 'get_product' ) )
			$product = get_product( $product_id );
		else
			$product = new WC_Product( $product_id );  // Shouldn't matter if product is variation as all we need is the product_type

		return $product;
	}


	/**
	 * Workaround the last day of month quirk in PHP's strtotime function.
	 *
	 * Adding +1 month to the last day of the month can yield unexpected results with strtotime().
	 * For example,
	 * - 30 Jan 2013 + 1 month = 3rd March 2013
	 * - 28 Feb 2013 + 1 month = 28th March 2013
	 *
	 * What humans usually want is for the charge to continue on the last day of the month.
	 *
	 * @since 1.2.5
	 */
	public static function add_months( $from_timestamp, $months_to_add ) {

		if ( date( 'd m Y', $from_timestamp ) === date( 't m Y', $from_timestamp ) )
			$next_timestamp = strtotime( date( 'Y-m-t H:i:s', strtotime( '+ 1 day', $from_timestamp ) ) ); // NB not the "t" to get last day of next month
		else // Safe to just add a month
			$next_timestamp = strtotime( "+ {$months_to_add} month", $from_timestamp );

		return $next_timestamp;
	}

	/* Deprecated Functions */

	/**
	 * Was called when a plugin is activated using official register_activation_hook() API
	 *
	 * Upgrade routine is now in @see maybe_activate_woocommerce_subscriptions()
	 *
	 * @since 1.0
	 */
	public static function activate_woocommerce_subscriptions(){
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.1', __CLASS__ . '::maybe_activate_woocommerce_subscriptions()' );
	}
}

WC_Subscriptions::init();

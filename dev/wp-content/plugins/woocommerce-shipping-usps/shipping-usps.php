<?php
/*
	Plugin Name: WooCommerce USPS Shipping
	Plugin URI: http://woothemes.com/woocommerce
	Description: Obtain shipping rates dynamically via the USPS Shipping API for your orders.
	Version: 3.1.6
	Author: WooThemes
	Author URI: http://woothemes.com

	Copyright: 2009-2011 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html

	https://www.usps.com/webtools/htm/Rate-Calculators-v1-5.htm
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '83d1524e8f5f1913e58889f83d442c32', '18657' );

/**
 * Plugin activation check
 */
function wc_usps_activation_check(){
	if ( ! function_exists( 'simplexml_load_string' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        wp_die( "Sorry, but you can't run this plugin, it requires the SimpleXML library installed on your server/hosting to function." );
	}
}

register_activation_hook( __FILE__, 'wc_usps_activation_check' );

/**
 * Localisation
 */
load_plugin_textdomain( 'wc_usps', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/**
 * Plugin page links
 */
function wc_usps_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=shipping&section=WC_Shipping_USPS' ) . '">' . __( 'Settings', 'wc_usps' ) . '</a>',
		'<a href="http://support.woothemes.com/">' . __( 'Support', 'wc_usps' ) . '</a>',
		'<a href="http://wcdocs.woothemes.com/user-guide/usps/">' . __( 'Docs', 'wc_usps' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_usps_plugin_links' );

/**
 * Check if WooCommerce is active
 */
if ( is_woocommerce_active() ) {

	/**
	 * woocommerce_init_shipping_table_rate function.
	 *
	 * @access public
	 * @return void
	 */
	function wc_usps_init() {
		include_once( 'classes/class-wc-shipping-usps.php' );
	}

	add_action( 'woocommerce_shipping_init', 'wc_usps_init' );

	/**
	 * wc_usps_add_method function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function wc_usps_add_method( $methods ) {
		$methods[] = 'WC_Shipping_USPS';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'wc_usps_add_method' );

	/**
	 * wc_usps_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	function wc_usps_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	add_action( 'admin_enqueue_scripts', 'wc_usps_scripts' );
}
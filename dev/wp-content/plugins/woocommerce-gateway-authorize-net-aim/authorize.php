<?php
/*
Plugin Name: WooCommerce Authorize.net Gateway
Plugin URI: http://woocommerce.com
Description: Extends WooCommerce with a <a href="https://www.authorize.net" target="_blank">Authorize.net</a> gateway. An Authorize.net gateway account, and a server with SSL support and an SSL certificate is required for security reasons.
Version: 2.0.4
Author: Daniel Espinoza
Author URI: http://www.growdevelopment.com

	Copyright: © 2009-2011 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
	
	Docs: http://developer.authorize.net/guides/
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '1a345d194a0d01e903f7a1363b6c86d2', '18598' );

add_action('plugins_loaded', 'woocommerce_authorize_net_init', 0);

function woocommerce_authorize_net_init() {

	if (!class_exists('WC_Payment_Gateway')) return;
	
	if(!defined('AUTHORIZE_DIR')) {
		define('AUTHORIZE_DIR', WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__) ) . '/');
	}
	if(!defined('AUTHORIZE_URL')) {	
		define('AUTHORIZE_URL', WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__) ) . '/');
	}
	
	include_once( 'classes/class-wc-gateway-authorize-net.php' );
	
	if ( class_exists( 'WC_Subscriptions_Order' ) ) 
		include_once( 'classes/class-wc-gateway-authorize-net-subscriptions.php' );


	/**
	 * Add the gateway to woocommerce
	 **/
	function add_authorize_gateway( $methods ) {
		$methods[] = 'WC_Authorize_Net';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'add_authorize_gateway' );
}
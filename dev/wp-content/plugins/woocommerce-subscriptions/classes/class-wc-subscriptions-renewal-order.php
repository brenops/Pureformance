<?php
/**
 * Subscriptions Renewal Order Class
 * 
 * Provides an API for creating and handling renewal orders.
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	WC_Subscriptions_Order
 * @category	Class
 * @author		Brent Shepherd
 * @since 		1.2
 */
class WC_Subscriptions_Renewal_Order {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 * 
	 * @since 1.0
	 */
	public static function init() {

		// Generate an order to keep a record of each subscription payment
		add_action( 'processed_subscription_payment', __CLASS__ . '::generate_paid_renewal_order', 10, 2 );
		add_action( 'processed_subscription_payment_failure', __CLASS__ . '::generate_failed_payment_renewal_order', 10, 2 );

		// If a subscription requires manual payment, generate an order to accept the payment
		add_action( 'scheduled_subscription_payment', __CLASS__ . '::maybe_generate_manual_renewal_order', 10, 2 );

		// Make sure *manual* payment on a renewal order is correctly processed
		add_action( 'woocommerce_payment_complete', __CLASS__ . '::maybe_record_renewal_order_payment', 10, 1 );

		// Make sure *manual* payment on renewal orders is correctly processed for gateways that do not call WC_Order::payment_complete()
		add_action( 'woocommerce_order_status_on-hold_to_processing', __CLASS__ . '::maybe_record_renewal_order_payment', 10, 1 );
		add_action( 'woocommerce_order_status_on-hold_to_completed', __CLASS__ . '::maybe_record_renewal_order_payment', 10, 1 );

		// Make sure payment on renewal orders is correctly processed when the *automatic* payment had previously failed
		add_action( 'woocommerce_order_status_failed_to_processing', __CLASS__ . '::process_failed_renewal_order_payment', 10, 1 );
		add_action( 'woocommerce_order_status_failed_to_completed', __CLASS__ . '::process_failed_renewal_order_payment', 10, 1 );
		add_action( 'woocommerce_order_status_failed', __CLASS__ . '::maybe_record_renewal_order_payment_failure', 10, 1 );

		add_action( 'woocommerce_subscriptions_renewal_order_created', __CLASS__ . '::maybe_send_customer_renewal_order_email', 10, 1 );

		// We want to customise the default email subjects for renewal orders
		add_filter( 'woocommerce_email_subject_new_order', __CLASS__ . '::email_subject_new_renewal_order', 11, 2 );
		add_filter( 'woocommerce_email_subject_customer_procesing_order', __CLASS__ . '::email_subject_customer_procesing_renewal_order', 11, 2 );
		add_filter( 'woocommerce_email_subject_customer_completed_order', __CLASS__ . '::email_subject_customer_completed_renewal_order', 11, 2 );

		// Check if a user is requesting to create a renewal order for a subscription
		add_action( 'init', __CLASS__ . '::maybe_create_renewal_order_for_user', 100 );
	}

	/**
	 * Creates a new order for renewing a subscription product based on the details of a previous order.
	 *
	 * No trial periods or sign up fees are applied to the renewal order. However, if the order has failed
	 * payments and the store manager has set failed payments to be added to renewal orders, then the
	 * orders totals will be set to include the outstanding balance.
	 *
	 * If the $new_order_role flag is set to 'parent', then the renewal order will supersede the existing 
	 * order. The existing order and subscription associated with it will be cancelled. A new order and
	 * subscription will be created. 
	 *
	 * If the $new_order_role flag is 'child', the $original_order will remain the master order for the
	 * subscription and the new order is just for accepting a recurring payment on the subscription.
	 *
	 * Renewal orders have the same meta data as the original order. If the renewal order is set to be a 'child'
	 * then any subscription related meta data will not be stored on the new order. This is to keep subscription
	 * meta data associated only with the one master order for the subscription.
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of the order for which the a new order should be created.
	 * @param $product_id string The ID of the subscription product in the order which needs to be added to the new order.
	 * @param $new_order_role string A flag to indicate whether the new order should become the master order for the subscription. Accepts either 'parent' or 'child'. Defaults to 'parent' - replace the existing order.
	 * @since 1.2
	 */
	public static function generate_renewal_order( $original_order, $product_id, $new_order_role = 'parent' ) {
		global $wpdb;

		if ( ! is_object( $original_order ) )
			$original_order = new WC_Order( $original_order );

		if ( ! WC_Subscriptions_Order::order_contains_subscription( $original_order ) || ! WC_Subscriptions_Order::is_item_subscription( $original_order, $product_id ) )
			return false;

		if ( self::is_renewal( $original_order, 'child' ) )
			$original_order = self::get_parent_order( $original_order );

		$renewal_order_key = uniqid( 'order_' );

		// Create the new order
		$renewal_order_data = array(
			'post_type'     => 'shop_order',
			'post_title' 	=> sprintf( __( 'Subscription Renewal Order &ndash; %s', WC_Subscriptions::$text_domain ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', WC_Subscriptions::$text_domain ) ) ),
			'post_status'   => 'publish',
			'ping_status'   => 'closed',
			'post_excerpt'  => $original_order->customer_note,
			'post_author'   => 1,
			'post_password' => $renewal_order_key,
		);

		if ( 'child' == $new_order_role )
			$renewal_order_data['post_parent'] = $original_order->id;

		$renewal_order_id = wp_insert_post( $renewal_order_data );

		// Set the order as pending
		wp_set_object_terms( $renewal_order_id, 'pending', 'shop_order_status' );

		// Set a unique key for this order
		update_post_meta( $renewal_order_id, '_order_key', $renewal_order_key );

		$order_meta_query = "SELECT `meta_key`, `meta_value` FROM $wpdb->postmeta WHERE `post_id` = $original_order->id AND `meta_key` NOT IN ('_paid_date', '_completed_date', '_order_key', '_edit_lock', '_original_order')";

		// Superseding existing order so don't carry over payment details
		if ( 'parent' == $new_order_role )
			$order_meta_query .= " AND `meta_key` NOT IN ('_payment_method', '_payment_method_title')";
		else
			$order_meta_query .= " AND `meta_key` NOT LIKE '_order_recurring_%'";

		// Allow extensions to add/remove order meta
		$order_meta_query = apply_filters( 'woocommerce_subscriptions_renewal_order_meta_query', $order_meta_query, $original_order->id, $renewal_order_id, $new_order_role );

		// Carry all the required meta from the old order over to the new order
		$order_meta = $wpdb->get_results( $order_meta_query, 'ARRAY_A' );

		$order_meta = apply_filters( 'woocommerce_subscriptions_renewal_order_meta', $order_meta, $original_order->id, $renewal_order_id, $new_order_role );

		foreach( $order_meta as $meta_item )
			add_post_meta( $renewal_order_id, $meta_item['meta_key'], maybe_unserialize( $meta_item['meta_value'] ), true );

		$outstanding_balance = WC_Subscriptions_Order::get_outstanding_balance( $original_order, $product_id );

		// If there are outstanding payment amounts, add them to the order, otherwise set the order details to the values of the recurring totals
		if ( $outstanding_balance > 0 && 'yes' == get_option( WC_Subscriptions_Admin::$option_prefix . '_add_outstanding_balance' ) )
			$failed_payment_multiplier = WC_Subscriptions_Order::get_failed_payment_count( $original_order, $product_id );
		else
			$failed_payment_multiplier = 1;

		// Set order totals based on recurring totals from the original order
		$cart_discount      = $failed_payment_multiplier * get_post_meta( $original_order->id, '_order_recurring_discount_cart', true );
		$order_discount     = $failed_payment_multiplier * get_post_meta( $original_order->id, '_order_recurring_discount_total', true );
		$order_shipping_tax = $failed_payment_multiplier * get_post_meta( $original_order->id, '_order_recurring_shipping_tax_total', true );
		$order_tax          = $failed_payment_multiplier * get_post_meta( $original_order->id, '_order_recurring_tax_total', true );
		$order_total        = $failed_payment_multiplier * get_post_meta( $original_order->id, '_order_recurring_total', true );

		update_post_meta( $renewal_order_id, '_cart_discount', $cart_discount );
		update_post_meta( $renewal_order_id, '_order_discount', $order_discount );
		update_post_meta( $renewal_order_id, '_order_shipping_tax', $order_shipping_tax );
		update_post_meta( $renewal_order_id, '_order_tax', $order_tax );
		update_post_meta( $renewal_order_id, '_order_total', $order_total );

		// Set order taxes based on recurring taxes from the original order
		$recurring_order_taxes = WC_Subscriptions_Order::get_recurring_taxes( $original_order );

		foreach ( $recurring_order_taxes as $index => $recurring_order_tax ) {

			if ( function_exists( 'woocommerce_update_order_item_meta' ) ) { // WC 2.0+

				$item_ids = array();

				$item_ids[] = woocommerce_add_order_item( $renewal_order_id, array(
					'order_item_name' => $recurring_order_tax['name'],
					'order_item_type' => 'tax'
				) );

				// Also set recurring taxes on parent renewal orders
				if ( 'parent' == $new_order_role ) {
					$item_ids[] = woocommerce_add_order_item( $renewal_order_id, array(
						'order_item_name' => $recurring_order_tax['name'],
						'order_item_type' => 'recurring_tax'
					) );
				}

				// Add line item meta
				foreach( $item_ids as $item_id ) {

					woocommerce_add_order_item_meta( $item_id, 'compound', absint( isset( $recurring_order_tax['compound'] ) ? $recurring_order_tax['compound'] : 0 ) );
					woocommerce_add_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $failed_payment_multiplier * $recurring_order_tax['tax_amount'] ) );
					woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $failed_payment_multiplier * $recurring_order_tax['shipping_tax_amount'] ) );

					if ( isset( $recurring_order_tax['rate_id'] ) )
						woocommerce_add_order_item_meta( $item_id, 'rate_id', $recurring_order_tax['rate_id'] );

					if ( isset( $recurring_order_tax['label'] ) )
						woocommerce_add_order_item_meta( $item_id, 'label', $recurring_order_tax['label'] );
				}

			} else { // WC 1.x

				if ( isset( $recurring_order_tax['cart_tax'] ) && $recurring_order_tax['cart_tax'] > 0 )
					$recurring_order_taxes[$index]['cart_tax'] = $failed_payment_multiplier * $recurring_order_tax['cart_tax'];
				else
					$recurring_order_taxes[$index]['cart_tax'] = 0;

				if ( isset( $recurring_order_tax['shipping_tax'] ) && $recurring_order_tax['shipping_tax'] > 0 )
					$recurring_order_taxes[$index]['shipping_tax'] = $failed_payment_multiplier * $recurring_order_tax['shipping_tax'];
				else
					$recurring_order_taxes[$index]['shipping_tax'] = 0;

				// Inefficient but keeps WC 1.x code grouped together
				update_post_meta( $renewal_order_id, '_order_taxes', $recurring_order_taxes );
			}
		}

		// Set line totals to be recurring line totals and remove the subscription/recurring related item meta from each order item
		$order_items = WC_Subscriptions_Order::get_recurring_items( $original_order );

		// Allow extensions to add/remove items or item meta
		$order_items = apply_filters( 'woocommerce_subscriptions_renewal_order_items', $order_items, $original_order->id, $renewal_order_id, $product_id, $new_order_role );

		foreach ( $order_items as $item_index => $order_item ) {

			$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );

			// WC 2.0+ order item structure - as of WC 2.0 item_meta is stored as $key => meta pairs, not 'meta_name'/'meta_value'
			if ( function_exists( 'woocommerce_add_order_item_meta' ) ) {

				if ( 'child' == $new_order_role )
					$renewal_order_item_name = sprintf( __( 'Renewal of "%s" purchased in Order %s', WC_Subscriptions::$text_domain ), $order_item['name'], $original_order->get_order_number() );
				else
					$renewal_order_item_name = $order_item['name'];

				// Create order line item on the renewal order
				$recurring_item_id = woocommerce_add_order_item( $renewal_order_id, array(
					'order_item_name' => $renewal_order_item_name,
					'order_item_type' => 'line_item'
				));

				// Remove recurring line items and set item totals based on recurring line totals
				foreach ( $item_meta->meta as $meta_key => $meta ) {

					// $meta is an array, so the item needs to be extracted from $meta[0] (just like order meta on a WC Order)
					$meta_value = $meta[0];

					// Map line item totals based on recurring line totals
					switch( $meta_key ) {
						case '_recurring_line_total':
							woocommerce_delete_order_item_meta( $recurring_item_id, '_line_total');
							woocommerce_add_order_item_meta( $recurring_item_id, '_line_total', woocommerce_format_decimal( $failed_payment_multiplier * $meta_value ) );
							break;
						case '_recurring_line_tax':
							woocommerce_delete_order_item_meta( $recurring_item_id, '_line_tax');
							woocommerce_add_order_item_meta( $recurring_item_id, '_line_tax', woocommerce_format_decimal( $failed_payment_multiplier * $meta_value ) );
							break;
						case '_recurring_line_subtotal':
							woocommerce_delete_order_item_meta( $recurring_item_id, '_line_subtotal');
							woocommerce_add_order_item_meta( $recurring_item_id, '_line_subtotal', woocommerce_format_decimal( $failed_payment_multiplier * $meta_value ) );
							break;
						case '_recurring_line_subtotal_tax':
							woocommerce_delete_order_item_meta( $recurring_item_id, '_line_subtotal_tax');
							woocommerce_add_order_item_meta( $recurring_item_id, '_line_subtotal_tax', woocommerce_format_decimal( $failed_payment_multiplier * $meta_value ) );
							break;
						default:
							break;
					}

					// Copy over line item meta data, with some parent/child role based exceptions for recurring amounts
					$copy_to_renewal_item = true;
					switch( $meta_key ) {
						case '_recurring_line_total':
						case '_recurring_line_tax':
						case '_recurring_line_subtotal':
						case '_recurring_line_subtotal_tax':
						case '_subscription_recurring_amount':
						case '_subscription_sign_up_fee':
						case '_subscription_period':
						case '_subscription_interval':
						case '_subscription_length':
						case '_subscription_trial_period':
							if ( 'child' == $new_order_role )
								$copy_to_renewal_item = false;
							break;
						case '_subscription_trial_length': // We never want to duplicate free trials on renewal orders
							$copy_to_renewal_item = false;
							break;
						default:
							break;
					}

					// Copy existing item over to new recurring order item
					if ( $copy_to_renewal_item )
						woocommerce_add_order_item_meta( $recurring_item_id, $meta_key, $meta_value );

				}

			} else { // WC 1.x order item structure

				foreach ( $item_meta->meta as $meta_index => $meta_item ) {
					switch( $meta_item['meta_name'] ) {
						case '_recurring_line_total':
							$order_items[$item_index]['line_total'] = $failed_payment_multiplier * $meta_item['meta_value'];
						case '_recurring_line_tax':
							$order_items[$item_index]['line_tax'] = $failed_payment_multiplier * $meta_item['meta_value'];
						case '_recurring_line_subtotal':
							$order_items[$item_index]['line_subtotal'] = $failed_payment_multiplier * $meta_item['meta_value'];
						case '_recurring_line_subtotal_tax':
							$order_items[$item_index]['line_subtotal_tax'] = $failed_payment_multiplier * $meta_item['meta_value'];
						case '_recurring_line_total':
						case '_recurring_line_tax':
						case '_recurring_line_subtotal':
						case '_recurring_line_subtotal_tax':
						case '_recurring_line_subtotal_tax':
						case '_subscription_recurring_amount':
						case '_subscription_sign_up_fee':
						case '_subscription_period':
						case '_subscription_interval':
						case '_subscription_length':
						case '_subscription_trial_length':
						case '_subscription_trial_period':
							if ( 'child' == $new_order_role )
								unset( $item_meta->meta[$meta_index] );
							break;
						case '_subscription_trial_length': // We never want to duplicate free trials on renewal orders
							if ( 'child' == $new_order_role )
								unset( $item_meta->meta[$meta_index] );
							else
								$item_meta->meta[$meta_index] = 0;
							break;
					}

					if ( 'child' == $new_order_role )
						$order_items[$item_index]['name'] = sprintf( __( 'Renewal of "%s" purchased in Order %s', WC_Subscriptions::$text_domain ), $order_item['name'], $original_order->get_order_number() );

					$order_items[$item_index]['item_meta'] = $item_meta->meta;
				}

				// Save the item meta on the new order
				update_post_meta( $renewal_order_id, '_order_items', $order_items );

			}

		}

		// Keep a record of the original order's ID on the renewal order
		update_post_meta( $renewal_order_id, '_original_order', $original_order->id, true );

		$renewal_order = new WC_Order( $renewal_order_id );

		if ( 'parent' == $new_order_role ) {
			WC_Subscriptions_Manager::process_subscriptions_on_checkout( $renewal_order_id );
			$original_order->add_order_note( sprintf( __( 'Order superseded by Renewal Order %s.', WC_Subscriptions::$text_domain ), $renewal_order->get_order_number() ) );
		}

		do_action( 'woocommerce_subscriptions_renewal_order_created', $renewal_order, $original_order, $product_id, $new_order_role );

		return apply_filters( 'woocommerce_subscriptions_renewal_order_id', $renewal_order_id, $original_order, $product_id, $new_order_role );
	}

	/**
	 * Generate an order to record an automatic subscription payment.
	 *
	 * This function is hooked to the 'process_subscription_payment' which is fired when a payment gateway calls 
	 * the @see WC_Subscriptions_Manager::process_subscription_payment() function. Because manual payments will
	 * also call this function, the function only generates a renewal order if the @see WC_Order::payment_complete()
	 * will be called for the renewal order.
	 *
	 * @param $user_id int The id of the user who purchased the subscription
	 * @param $subscription_key string A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 * @since 1.2
	 */
	public static function generate_paid_renewal_order( $user_id, $subscription_key ) {
		global $woocommerce;

		$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );

		// The email will be sent by WooCommerce once it's marked as paid
		remove_action( 'woocommerce_subscriptions_renewal_order_created', __CLASS__ . '::maybe_send_customer_renewal_order_email', 10, 1 );

		$parent_order = new WC_Order( $subscription['order_id'] );

		$renewal_order_id = self::generate_renewal_order( $parent_order, $subscription['product_id'], 'child' );

		$renewal_order = new WC_Order( $renewal_order_id );

		if ( 'yes' != get_option( WC_Subscriptions_Admin::$option_prefix . '_email_automatic_renewal_order' ) ) {
			remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $woocommerce->mailer(), 'customer_processing_order' ) );
			remove_action( 'woocommerce_order_status_completed_notification', array( $woocommerce->mailer(), 'customer_completed_order' ) );
		}

		// Don't duplicate renewal orders
		remove_action( 'processed_subscription_payment', __CLASS__ . '::generate_paid_renewal_order', 10, 2 );

		$renewal_order->payment_complete();

		// But make sure orders are still generated for other payments in the same request
		add_action( 'processed_subscription_payment', __CLASS__ . '::generate_paid_renewal_order', 10, 2 );

		WC_Subscriptions_Manager::reactivate_subscription( $user_id, $subscription_key );

		$parent_order->add_order_note( sprintf( __( 'Subscription payment recorded in renewal order %s', WC_Subscriptions::$text_domain ), $renewal_order->get_order_number() ) );

		return $renewal_order_id;
	}

	/**
	 * Generate an order to record a subscription payment failure.
	 *
	 * This function is hooked to the 'processed_subscription_payment_failure' hook called when a payment
	 * gateway calls the @see WC_Subscriptions_Manager::process_subscription_payment_failure()
	 *
	 * @param $user_id int The id of the user who purchased the subscription
	 * @param $subscription_key string A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 * @since 1.2
	 */
	public static function generate_failed_payment_renewal_order( $user_id, $subscription_key ) {

		$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );

		$renewal_order_id = self::generate_renewal_order( $subscription['order_id'], $subscription['product_id'], 'child' );

		// Mark payment completed on order
		$renewal_order = new WC_Order( $renewal_order_id );

		$renewal_order->update_status( 'failed' );

		return $renewal_order_id;
	}

	/**
	 * Generate an order to record a subscription payment. 
	 *
	 * This function is hooked to the scheduled subscription payment hook to create a pending
	 * order for each scheduled subscription payment.
	 *
	 * When a payment gateway calls the @see WC_Subscriptions_Manager::process_subscription_payment()
	 * @see WC_Order::payment_complete() will be called for the renewal order.
	 *
	 * @param $user_id int The id of the user who purchased the subscription
	 * @param $subscription_key string A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 * @since 1.2
	 */
	public static function maybe_generate_manual_renewal_order( $user_id, $subscription_key ) {

		if ( WC_Subscriptions_Manager::requires_manual_renewal( $subscription_key, $user_id ) ) {

			$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );

			self::generate_renewal_order( $subscription['order_id'], $subscription['product_id'], 'child' );

		}

	}

	/**
	 * If the payment for a renewal order has previously failed and is then paid, then the
	 * @see WC_Subscriptions_Manager::process_subscription_payments_on_order() function would
	 * never be called. This function makes sure it is called.
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function process_failed_renewal_order_payment( $order_id ) {
		if ( self::is_renewal( $order_id, 'child' ) && ! WC_Subscriptions_Order::requires_manual_renewal( self::get_parent_order( $order_id ) ) ) {
			add_action( 'woocommerce_payment_complete', __CLASS__ . '::process_subscription_payment_on_child_order', 10, 1 );
		}
	}

	/**
	 * Records manual payment of a renewal order against a subscription.
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function maybe_record_renewal_order_payment( $order_id ) {
		if ( self::is_renewal( $order_id, 'child' ) && WC_Subscriptions_Order::requires_manual_renewal( self::get_parent_order( $order_id ) ) ) {
			self::process_subscription_payment_on_child_order( $order_id );
		}
	}

	/**
	 * Records manual payment of a renewal order against a subscription.
	 *
	 * @param $order WC_Order | int A WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function maybe_record_renewal_order_payment_failure( $order_id ) {
		if ( self::is_renewal( $order_id, 'child' ) && WC_Subscriptions_Order::requires_manual_renewal( self::get_parent_order( $order_id ) ) ) {
			self::process_subscription_payment_on_child_order( $order_id, 'failed' );
		}
	}

	/**
	 * If the payment for a renewal order has previously failed and is then paid, we need to make sure the
	 * subscription payment function is called.
	 *
	 * @param $user_id int The id of the user who purchased the subscription
	 * @param $subscription_key string A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 * @since 1.2
	 */
	public static function process_subscription_payment_on_child_order( $order_id, $payment_status = 'completed' ) {

		if ( self::is_renewal( $order_id, 'child' ) ) {

			$child_order = new WC_Order( $order_id );

			$parent_order = self::get_parent_order( $child_order );

			$subscriptions_in_order = $child_order->get_items();

			// Should only be one subscription in the renewal order, but just in case
			foreach ( $subscriptions_in_order as $item ) {

				$item_id = WC_Subscriptions_Order::get_items_product_id( $item );

				if ( WC_Subscriptions_Order::is_item_subscription( $parent_order, $item_id ) ) {

					if ( 'failed' == $payment_status ) {

						// Don't duplicate renewal order
						remove_action( 'processed_subscription_payment_failure', __CLASS__ . '::generate_failed_payment_renewal_order', 10, 2 );

						WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $parent_order->id, $item_id );

						// But make sure orders are still generated for other payments in the same request
						add_action( 'processed_subscription_payment_failure', __CLASS__ . '::generate_failed_payment_renewal_order', 10, 2 );

					} else {

						// Don't duplicate renewal order
						remove_action( 'processed_subscription_payment', __CLASS__ . '::generate_paid_renewal_order', 10, 2 );

						WC_Subscriptions_Manager::process_subscription_payments_on_order( $parent_order->id, $item_id );

						// But make sure orders are still generated for other payments in the same request
						add_action( 'processed_subscription_payment', __CLASS__ . '::generate_paid_renewal_order', 10, 2 );

						// Reactivate the subscription - activate_subscription doesn't operate on child orders
						$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $parent_order->id, $item_id );
						WC_Subscriptions_Manager::reactivate_subscription( $parent_order->customer_user, $subscription_key );
					}
				}
			}
		}
	}

	/* Helper functions */

	/**
	 * Check if a given order is a subscription renewal order and optionally, if it is a renewal order of a certain role.
	 *
	 * If an order 
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of a WC_Order order.
	 * @param $role string (optional) A specific role to check the order against. Either 'parent' or 'child'
	 * @since 1.2
	 */
	public static function is_renewal( $order, $role = '' ) {

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		if ( isset( $order->order_custom_fields['_original_order'] ) && ! empty( $order->order_custom_fields['_original_order'][0] ) )
			$is_renewal = true;
		else
			$is_renewal = false;

		if ( ! empty ( $role ) ) {
			$order_post = get_post( $order->id );

			if ( 'parent' == $role && 0 != $order_post->post_parent ) // It's a child order
				$is_renewal = false;
			elseif ( 'child' == $role && 0 == $order_post->post_parent ) // It's a parent order
				$is_renewal = false;
		}

		return apply_filters( 'woocommerce_subscriptions_is_renewal_order', $is_renewal, $order );
	}

	/**
	 * Get the ID of the parent order for a subscription renewal order. 
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_parent_order_id( $renewal_order ) {

		$parent_order = self::get_parent_order( $renewal_order );

		return $parent_order->id;
	}

	/**
	 * Get the parent order for a subscription renewal order.
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function get_parent_order( $renewal_order ) {

		if ( ! is_object( $renewal_order ) )
			$renewal_order = new WC_Order( $renewal_order );

		$order_post = get_post( $renewal_order->id );

		if ( 0 == $order_post->post_parent ) // The renewal order is the parent order
			$parent_order = $renewal_order;
		else
			$parent_order = new WC_Order( $order_post->post_parent );

		return apply_filters( 'woocommerce_subscriptions_parent_order', $parent_order, $renewal_order );
	}

	/**
	 * Returns the number of renewals for a given parent order
	 *
	 * @param $order_id int The ID of a WC_Order object.
	 * @since 1.2
	 */
	public static function get_renewal_order_count( $order_id ) {
		global $wpdb;

		/** @var wpdb $wpdb  */
		$renewal_order_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'shop_order'", $order_id ) );

		return apply_filters( 'woocommerce_subscriptions_renewal_order_count', $renewal_order_count, $order_id );
	}

	/**
	 * Returns the renewal orders for a given parent order
	 *
	 * @param $order_id int The ID of a WC_Order object.
	 * @param $output string (optional) How you'd like the result. Can be 'ID' for IDs only or 'WC_Order' for order objects.
	 * @since 1.2
	 */
	public static function get_renewal_orders( $order_id, $output = 'ID' ) {
		global $wpdb;

		$renewal_order_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'shop_order'", $order_id ) );

		if ( 'WC_Order' == $output ) {

			$renewal_orders = array();

			foreach ( $renewal_order_ids as $renewal_order_id )
				$renewal_orders[$renewal_order_id] = new WC_Order( $renewal_order_id );

		} else {

			$renewal_orders = $renewal_order_ids;

		}

		return apply_filters( 'woocommerce_subscriptions_renewal_orders', $renewal_orders, $order_id );
	}

	/* Email Functions */

	/**
	 * Hooks to the renewal order created action to determine if the order should be emailed to the customer. 
	 *
	 * @param $order WC_Order | int The WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function maybe_send_customer_renewal_order_email( $order ) {
		if ( 'yes' == get_option( WC_Subscriptions_Admin::$option_prefix . '_email_renewal_order' ) )
			self::send_customer_renewal_order_email( $order );
	}

	/**
	 * Processing Order
	 * 
	 * @param $order WC_Order | int The WC_Order object or ID of a WC_Order order.
	 * @since 1.2
	 */
	public static function send_customer_renewal_order_email( $order ) {
		global $woocommerce;

		if ( ! is_object( $order ) )
			$order = new WC_Order( $order );

		$emailer = $woocommerce->mailer();

		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		if ( $order->status == 'processing' || $order->status == 'completed' ) {
			$email_heading = sprintf( __( 'Subscription Renewal with %s', WC_Subscriptions::$text_domain ), $blogname );
			$subject       = sprintf( __( '[%s] Subscription Renewal Order', WC_Subscriptions::$text_domain ), $blogname );
		} else {
			$email_heading = __( 'Invoice for Subscription Renewal', WC_Subscriptions::$text_domain );
			$subject       = sprintf( __( '[%s] Pay for Subscription Renewal', WC_Subscriptions::$text_domain ), $blogname );
		}

		$email_heading = apply_filters( 'woocommerce_email_heading_customer_renewal_order', $email_heading, $order );
		$subject       = apply_filters( 'woocommerce_email_subject_customer_renewal_order', $subject, $order );

		// Buffer
		ob_start();

		// Get mail template
		woocommerce_get_template( 
			'emails/customer-renewal-order.php', 
			array(
				'order'         => $order,
				'email_heading' => $email_heading
			),
			'',
			plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/'
		);

		// Get contents
		$message = ob_get_clean();

		//	CC, BCC, additional headers
		$headers = apply_filters( 'woocommerce_email_headers', '', 'customer_renewal_order' );

		// Attachments
		$attachments = apply_filters( 'woocommerce_email_attachments', '', 'customer_renewal_order' );

		// Send the mail
		$emailer->send( $order->billing_email, $subject, $message, $headers, $attachments );
	}

	/**
	 * Change the email subject of the new order email to specify the order is a subscription renewal order
	 *
	 * @param $subject String The default WooCommerce email subject
	 * @param $order WC_Order The WC_Order object which the email relates to
	 * @since 1.2
	 */
	public static function email_subject_new_renewal_order( $subject, $order ) {

		if ( self::is_renewal( $order ) ) {
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$subject  = apply_filters( 'woocommerce_subscriptions_email_subject_new_renewal_order', sprintf( __( '[%s] New Subscription Renewal Order (%s)', WC_Subscriptions::$text_domain ), $blogname, $order->get_order_number() ), $order );
		}

		return $subject;
	}

	/**
	 * Change the email subject of the processing order email to specify the order is a subscription renewal order
	 *
	 * @param $subject String The default WooCommerce email subject
	 * @param $order WC_Order The WC_Order object which the email relates to
	 * @since 1.2
	 */
	public static function email_subject_customer_procesing_renewal_order( $subject, $order ) {

		if ( self::is_renewal( $order ) ) {
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$subject  = apply_filters( 'woocommerce_subscriptions_email_subject_customer_procesing_renewal_order', sprintf( __( '[%s] Subscription Renewal Order', WC_Subscriptions::$text_domain ), $blogname ), $order );
		}

		return $subject;
	}

	/**
	 * Change the email subject of the completed order email to specify the order is a subscription renewal order
	 *
	 * @param $subject String The default WooCommerce email subject
	 * @param $order WC_Order The WC_Order object which the email relates to
	 * @since 1.2
	 */
	public static function email_subject_customer_completed_renewal_order( $subject, $order ) {

		if ( self::is_renewal( $order ) ) {
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$subject  = apply_filters( 'woocommerce_subscriptions_email_subject_customer_completed_renewal_order', sprintf( __( '[%s] Subscription Renewal Order', WC_Subscriptions::$text_domain ), $blogname ), $order );
		}

		return $subject;
	}

	/**
	 * Check if a given subscription can be renewed. 
	 *
	 * For a subscription to be renewable, it must be inactive (expired or cancelled) and its parent order
	 * must not have already been superseded by a renewal order. This last part is mainly just to prevent
	 * displaying "Renew" links on subscriptions that have already been renewed.
	 *
	 * @param $subscription_key string A subscription key of the form created by @see self::get_subscription_key()
	 * @param $user_id int The ID of the user who owns the subscriptions. Although this parameter is optional, if you have the User ID you should pass it to improve performance.
	 * @since 1.2
	 */
	public static function can_subscription_be_renewed( $subscription_key, $user_id = '' ) {

		$subscription = WC_Subscriptions_Manager::get_subscription( $subscription_key, $user_id );

		if ( empty( $subscription ) ) {
			$subscription_can_be_renewed = false;
		} else {

			$renewal_orders = get_posts( array(
				'meta_key'    => '_original_order', 
				'meta_value'  => $subscription['order_id'], 
				'post_type'   => 'shop_order', 
				'post_parent' => 0 
				)
			);

			if( empty( $renewal_orders ) && in_array( $subscription['status'], array( 'cancelled', 'expired', 'trash', 'failed' ) ) )
				$subscription_can_be_renewed = true;
			else
				$subscription_can_be_renewed = false;

		}

		return apply_filters( 'woocommerce_can_subscription_be_renewed', $subscription_can_be_renewed, $subscription, $subscription_key, $user_id );
	}

	/**
	 * Returns a URL including required parameters for an authenticated user to renew a subscription
	 *
	 * @param $subscription_key string A subscription key of the form created by @see self::get_subscription_key()
	 * @since 1.2
	 */
	public static function get_users_renewal_link( $subscription_key, $role = 'parent' ) {

		$renewal_url = add_query_arg( array( 'renew_subscription' => $subscription_key, 'role' => $role ) );
		$renewal_url = wp_nonce_url( $renewal_url, __FILE__ );

		return apply_filters( 'woocommerce_subscriptions_users_renewal_link', $renewal_url, $subscription_key );
	}

	/**
	 * Checks if the current request is by a user to change the status of their subscription, and if it is
	 * validate the subscription cancellation request and maybe processes the cancellation. 
	 * 
	 * @since 1.2
	 */
	public static function maybe_create_renewal_order_for_user() {
		global $woocommerce;

		if ( isset( $_GET['renew_subscription'] ) && isset( $_GET['_wpnonce'] )  ) {

			$user_id      = get_current_user_id();
			$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $_GET['renew_subscription'] );
			
			if ( isset( $_GET['role'] ) ) {
				$role = woocommerce_clean( $_GET['role'] );
			} else {
				$role = 'parent';
			}

			$redirect_to = get_permalink( woocommerce_get_page_id( 'myaccount' ) );

			if ( wp_verify_nonce( $_GET['_wpnonce'], __FILE__ ) === false ) {

				$woocommerce->add_error( __( 'There was an error with the renewal request. Please try again.', WC_Subscriptions::$text_domain ) );

			} elseif ( empty( $subscription ) ) {

				$woocommerce->add_error( __( 'That doesn\'t appear to be one of your subscriptions.', WC_Subscriptions::$text_domain ) );

			} elseif ( ! self::can_subscription_be_renewed( $_GET['renew_subscription'], $user_id ) ) {

				$woocommerce->add_error( __( 'That subscription can not be renewed. Please contact us if you need assistance.', WC_Subscriptions::$text_domain ) );

			} else {

				$order = new WC_Order( $subscription['order_id'] );

				// Don't email requesting payment when the customer requested the renewal
				remove_action( 'woocommerce_subscriptions_renewal_order_created', __CLASS__ . '::maybe_send_customer_renewal_order_email', 10, 1 );

				$renewal_order_id = self::generate_renewal_order( $order, $subscription['product_id'], $role );

				$renewal_order = new WC_Order( $renewal_order_id );

				$order->add_order_note( sprintf( __( 'Subscriber generated renewal order %s', WC_Subscriptions::$text_domain ), $renewal_order->get_order_number() ) );

				$woocommerce->add_message( __( 'Renew your subscription.', WC_Subscriptions::$text_domain ) );

				$redirect_to = $renewal_order->get_checkout_payment_url();
			}

			wp_safe_redirect( $redirect_to );
			exit;
		}
	}
}

WC_Subscriptions_Renewal_Order::init();

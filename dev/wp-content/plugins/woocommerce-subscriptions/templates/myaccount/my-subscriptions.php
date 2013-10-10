<?php
/**
 * My Subscriptoins
 */

global $woocommerce;

$subscriptions = WC_Subscriptions_Manager::get_users_subscriptions();

$user_id = get_current_user_id();

foreach ( $subscriptions as $subscription_key => $subscription_details )
	if ( $subscription_details['status'] == 'trash' )
		unset( $subscriptions[$subscription_key] );

?>

<h2><?php _e( 'My Subscriptions', WC_Subscriptions::$text_domain ); ?></h2>

<?php if ( ! empty( $subscriptions ) ) : ?>
<table class="shop_table my_account_subscriptions my_account_orders">

	<thead>
		<tr>
			<th class="subscription-order-number"><span class="nobr"><?php _e( 'Order', WC_Subscriptions::$text_domain ); ?></span></th>
			<th class="subscription-title"><span class="nobr"><?php _e( 'Subscription', WC_Subscriptions::$text_domain ); ?></span></th>
			<th class="subscription-status"><span class="nobr"><?php _e( 'Status', WC_Subscriptions::$text_domain ); ?></span></th>
			<th class="subscription-next-payment"><span class="nobr"><?php _e( 'Next Payment', WC_Subscriptions::$text_domain ); ?></span></th>
			<th class="subscription-expiry"><span class="nobr"><?php _e( 'Expiration', WC_Subscriptions::$text_domain ); ?></span></th>
		</tr>
	</thead>

	<tbody>
	<?php foreach ( $subscriptions as $subscription_key => $subscription_details ) : ?>
		<?php $order = new WC_Order( $subscription_details['order_id'] ); ?>
		<tr class="order">
			<td class="order-number" width="1%">
				<a href="<?php echo esc_url( add_query_arg( 'order', $subscription_details['order_id'], get_permalink( woocommerce_get_page_id( 'view_order' ) ) ) ); ?>"><?php echo $order->get_order_number(); ?></a>
			</td>
			<td class="subscription-title">
				<a href="<?php echo get_post_permalink( $subscription_details['product_id'] ); ?>">
					<?php echo WC_Subscriptions_Order::get_item_name( $subscription_details['order_id'], $subscription_details['product_id'] ); ?>
				</a>
			</td>
			<td class="subscription-status" style="text-align:left; white-space:nowrap;">
				<?php echo WC_Subscriptions_Manager::get_status_to_display( $subscription_details['status'] ); ?>
				<?php if ( ! WC_Subscriptions_Manager::subscription_requires_payment( $subscription_key, $user_id ) && WC_Subscriptions_Manager::can_subscription_be_changed_to( 'on-hold', $subscription_key, $user_id ) ) : ?>
				<a href="<?php echo esc_url( WC_Subscriptions_Manager::get_users_change_status_link( $subscription_key, 'on-hold' ) ); ?>" class="on-hold" title="<?php _e( 'Click to put this subscription on-hold', WC_Subscriptions::$text_domain ); ?>">(<?php _e( 'Suspend', WC_Subscriptions::$text_domain ); ?>)</a>
				<?php elseif ( ! WC_Subscriptions_Manager::subscription_requires_payment( $subscription_key, $user_id ) && WC_Subscriptions_Manager::can_subscription_be_changed_to( 'active', $subscription_key, $user_id ) ) : ?>
				<a href="<?php echo esc_url( WC_Subscriptions_Manager::get_users_change_status_link( $subscription_key, 'active' ) ); ?>" class="activate" title="<?php _e( 'Click to reactivate this subscription', WC_Subscriptions::$text_domain ); ?>">(<?php _e( 'Reactivate', WC_Subscriptions::$text_domain ); ?>)</a>
				<?php endif; ?>
				<?php if ( WC_Subscriptions_Manager::can_subscription_be_changed_to( 'cancelled', $subscription_key, $user_id ) ) : ?>
				<a href="<?php echo esc_url( WC_Subscriptions_Manager::get_users_change_status_link( $subscription_key, 'cancelled' ) ); ?>" class="cancel" title="<?php _e( 'Click to cancel this subscription', WC_Subscriptions::$text_domain ); ?>">(<?php _e( 'Cancel', WC_Subscriptions::$text_domain ); ?>)</a>
				<?php endif; ?>
				<?php if ( WC_Subscriptions_Renewal_Order::can_subscription_be_renewed( $subscription_key, $user_id ) ) : ?>
				<a href="<?php echo esc_url( WC_Subscriptions_Renewal_Order::get_users_renewal_link( $subscription_key ) ); ?>" class="renew" title="<?php _e( 'Click to renew this subscription', WC_Subscriptions::$text_domain ); ?>">(<?php _e( 'Renew', WC_Subscriptions::$text_domain ); ?>)</a>
				<?php endif; ?>
			</td>
			<td class="subscription-next-payment">
				<?php $next_payment_timestamp = WC_Subscriptions_Manager::get_next_payment_date( $subscription_key, $user_id, 'timestamp' ); ?>
				<?php if ( $next_payment_timestamp == 0 ) : ?>
					-
				<?php else : ?>
					<?php $time_diff = $next_payment_timestamp - time(); ?>
					<?php if ( $time_diff > 0 && $time_diff < 7 * 24 * 60 * 60 ) : ?>
						<?php $next_payment = sprintf( __( 'In %s', WC_Subscriptions::$text_domain ), human_time_diff( $next_payment_timestamp ) ); ?>
					<?php else : ?>
						<?php $next_payment = date_i18n( get_option( 'date_format' ), $next_payment_timestamp ); ?>
					<?php endif; ?>
				<time title="<?php echo esc_attr( $next_payment_timestamp ); ?>">
					<?php echo $next_payment; ?>
				</time>
				<?php endif; ?>
			</td>
			<td class="subscription-expiry">
				<?php if ( $subscription_details['expiry_date'] == 0 ) : ?>
					<?php _e( 'Never', WC_Subscriptions::$text_domain ); ?>
				<?php else : ?>
					<?php $expiry_timestamp = strtotime( $subscription_details['expiry_date'] ); ?>
					<?php $time_diff = $expiry_timestamp - time(); ?>
					<?php if ( absint( $time_diff ) > 0 && absint( $time_diff ) < 7 * 24 * 60 * 60 ) : ?>
						<?php if ( $time_diff > 0 ) : // In the future ?>
							<?php $expiry = sprintf( __( 'In %s', WC_Subscriptions::$text_domain ), human_time_diff( $expiry_timestamp ) ); ?>
						<?php else : // In the past ?>
							<?php $expiry = sprintf( __( '%s ago', WC_Subscriptions::$text_domain ), human_time_diff( $expiry_timestamp ) ); ?>
						<?php endif; ?>
					<?php else : ?>
						<?php $expiry = date_i18n( get_option( 'date_format' ), $expiry_timestamp ); ?>
					<?php endif; ?>
					<time title="<?php echo esc_attr( $expiry_timestamp ); ?>">
						<?php echo $expiry; ?>
					</time>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>

</table>

<?php else : ?>

	<p><?php _e( 'You have no active subscriptions.', WC_Subscriptions::$text_domain ); ?></p>

<?php endif;

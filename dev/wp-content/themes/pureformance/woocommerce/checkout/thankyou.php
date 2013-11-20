<?php
/**
 * Thankyou page for Give a Gift
 *
 * @author
 * @package
 * @version
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// get receiver data from db
$firstname = '';
global $wpdb;
global $woocommerce;

$purchaserUserId = get_current_user_id();
$receiver = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT user_receiver, user_receiver_firstname, user_receiver_message FROM {$wpdb->prefix}gift_history WHERE user_purchaser = %d AND status = 0 ORDER BY id DESC LIMIT 1",
        $purchaserUserId
    ), ARRAY_A
);

if ($receiver && isset($receiver['user_receiver_firstname'])) {
    $firstname = $receiver['user_receiver_firstname'];
}

//
do_action( 'givegift' );

if ( $order ) : ?>

	<?php if ( in_array( $order->status, array( 'failed' ) ) ) : ?>

		<p><?php _e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'woocommerce' ); ?></p>

		<p><?php
			if ( is_user_logged_in() ) {
				_e( 'Please attempt your purchase again or go to your account page.', 'woocommerce' );
                        } else {
				_e( 'Please attempt your purchase again.', 'woocommerce' );
                        }
		?></p>

		<p>
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e( 'Pay', 'woocommerce' ) ?></a>
			<?php if ( is_user_logged_in() ) : ?>
			<a href="<?php echo esc_url( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ); ?>" class="button pay"><?php _e( 'My Account', 'woocommerce' ); ?></a>
			<?php endif; ?>
		</p>

	<?php else : ?>

		<h2>Thank You For Giving the Gift of Opportunity</h2>
		<p>
                    Just gave <strong><?php echo $firstname; ?></strong> a chance to better themselves and become a<br />
                    game changer!<br />
                    An email has been sent for him to create an account and begin enjoying his first month of content,<br />
                    courtesy of your kindness.
                </p>

                <div class="clear"></div>

                <h2>Wow, that felt good ...</h2>
                <div>
                    <form method="GET" id="give-gift-form" action="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ); ?>">
                    <div>
                        <input type="submit" class="btn1" name="giveGift" value="<?php esc_attr_e( 'Give Another Gift', 'twentyeleven' ); ?>" />
                    </div>
                    </form>
                </div>

		<div class="clear"></div>

		<?php endif; ?>

	<?php do_action( 'woocommerce_thankyou_' . $order->payment_method, $order->id ); ?>
	<?php do_action( 'woocommerce_thankyou', $order->id ); ?>

<?php else : ?>

	<p><?php _e( 'Thank you. Your order has been received.', 'woocommerce' ); ?></p>

<?php endif; ?>

<script>
	$(function(){
		if(is_subscriber==1){ $('#subscription_upsell').remove(); } //use jquery to remove the upsell
	});
</script>
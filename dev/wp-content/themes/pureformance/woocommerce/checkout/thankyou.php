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
			if ( is_user_logged_in() )
				_e( 'Please attempt your purchase again or go to your account page.', 'woocommerce' );
			else
				_e( 'Please attempt your purchase again.', 'woocommerce' );
		?></p>

		<p>
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e( 'Pay', 'woocommerce' ) ?></a>
			<?php if ( is_user_logged_in() ) : ?>
			<a href="<?php echo esc_url( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ); ?>" class="button pay"><?php _e( 'My Account', 'woocommerce' ); ?></a>
			<?php endif; ?>
		</p>

	<?php else : ?>
		<?php
			//ADD ORDER NOTE IF SUCCESSFULL
			if(isset($_SESSION['portal'])) { $order->add_order_note( 'Portal : '.$_SESSION['portal'], 0 ); }
		?>

		<?php if($firstname == '') { ?>
		<p><?php _e( 'Thank you. Your order has been received.', 'woocommerce' ); ?></p>
		<? } else { ?>
		<h2>Thank You For Giving the Gift of Opportunity</h2>
		<p>You just gave <strong><?php echo $firstname; ?></strong> the thoughtful and cool gift of access to our one of kind
resources and community. This is a great opportunity for them to empower what they do
in life with You and Pureformance by their side. Use our
<a href="">Social Media</a>
tools to get gifted
in return and gain your access to PF.
                </p>
                <p>An email has been sent for him/her to begin enjoying his/her first month of full access,
courtesy of your kindness. The World is getting brighter!</p>

                <h2>Wow, that felt good ...</h2>
                <div>
                    <form method="GET" id="give-gift-form" action="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ); ?>">
                    <div>
                        <input type="submit" class="btn1" name="giveGift" value="<?php esc_attr_e( 'Give Another Gift', 'twentyeleven' ); ?>" />
                    </div>
                    </form>
                </div>
                <div class="clear"></div><br><br>
        <? } ?>

		<ul class="order_details">
			<li class="order">
				<?php _e( 'Order:', 'woocommerce' ); ?>
				<strong><?php echo $order->get_order_number(); ?></strong>
			</li>
			<li class="date">
				<?php _e( 'Date:', 'woocommerce' ); ?>
				<strong><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></strong>
			</li>
			<li class="total">
				<?php _e( 'Total:', 'woocommerce' ); ?>
				<strong><?php echo $order->get_formatted_order_total(); ?></strong>
			</li>
			<?php if ( $order->payment_method_title ) : ?>
			<li class="method">
				<?php _e( 'Payment method:', 'woocommerce' ); ?>
				<strong><?php echo $order->payment_method_title; ?></strong>
			</li>
			<?php endif; ?>
		</ul>
		<div class="clear"></div>
		<?php
		$_SESSION['is_subscriber']=0; //begin with false for the subscriber status
		$subscriptions = WC_Subscriptions_Manager::get_users_subscriptions();
		$user_id = get_current_user_id();
		
		$_SESSION['is_subscriber']=0;
		
		foreach ( $subscriptions as $subscription_key => $subscription_details )
			if ( $subscription_details['status'] == 'trash' )
				unset( $subscriptions[$subscription_key] );
		foreach ( $subscriptions as $subscription_key => $subscription_details )
		{
			//print_r($subscription_details);
			if($subscription_details['status']=='active'){$_SESSION['is_subscriber']=1;}
		}
		if($_SESSION['is_subscriber'] == 0) {
		?>
		<p>Thanks for your order and wait... the best is yet to come! Please take a moment to also join our team and gain full access to our site and all our strategies, features, interviews, videos, events and special team offers. Choose from an annual or monthly subscription.</p>
		<div class="plans monthly">
							<div class="price">$5/mo</div>
							<ul>
								<li>Monthly Membership</li>
							</ul>
							<a href="<?php echo home_url( '/' ); ?>membership/?add-to-cart=267">Join Now</a>
						</div>
						<div class="plans annual1">
							<div class="price">$85</div>
							<ul>
								<li>Annual Team Membership</li>
								<li>Pure Blood Builder</li>
								<li>Free Shipping</li>
							</ul>
							<div class="save">Save $20</div>
							<a href="<?php echo home_url( '/' ); ?>membership/?add-to-cart=269">Join Now</a>
						</div>
						<div class="plans annual2">
							<div class="price">$175</div>
							<ul>
								<li>Annual Team Membership</li>
								<li>Pure Blood Builder</li>
								<li>Free Shipping</li>
								<li>Pure Journal</li>
								<li>Pure Supplement Tin</li>
								<li>eGuidebooks II and III</li>
								<!--li>Super Intent Poster</li-->
								<!--li>Pure Rest and Recover</li-->
							</ul>
							<div class="save">Save $50</div>
							<a href="<?php echo home_url( '/' ); ?>membership/?add-to-cart=557">Join Now</a>
						</div>
		<? } ?>
		

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
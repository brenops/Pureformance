<?php if (!defined('ABSPATH')) exit; ?>

<?php woocommerce_get_template('emails/email-header.php', array( 'email_heading' => $email_heading )); ?>

<p>We are on a mission to provide people with resources and a community to strengthen us as we reach into our human potential.</p>

<p>Your gift has granted you immediate access to Pureformance, our interviews, videos, strategies, community page and more.</p>

<p>We welcome you to explore what we have created for you and what your friend thought is valuable enough for your time and attention.</p>

<p>To jump in to Pureformance and your account,
    <a href="<?php echo home_url( '/' ); ?>promo-code/?coupon=<?php echo $coupon_code ?>" style="color: hsl(0, 0%, 45%);">
    click here
    </a>.
</p>
<br />

<p>To ensure that you continue to receive emails from us, please add <u><b>team@pureformance.com</b></u> to your address book or safe senders list.</p>

<?php /*echo $message_from_sender; ?>

<p><?php echo sprintf(__("To redeem your discount use the following coupon code during checkout:", 'wc_smart_coupons'), $blogname); ?></p>

<strong style="margin: 10px 0; font-size: 2em; line-height: 1.2em; font-weight: bold; display: block; text-align: center;" title="<?php echo __( 'Click to apply', 'wc_smart_coupons' ); ?>">
	<a href="<?php echo home_url( '/' ); ?>promo-code/?coupon=<?php echo $coupon_code ?>" style="text-decoration: none; color: hsl(0, 0%, 45%);">
	<?php echo $coupon_code; ?>
	</a>
</strong>

<center><a href="<?php echo home_url( '/' ); ?>promo-code/?coupon=<?php echo $coupon_code ?>"><?php echo sprintf(__("Join now", 'wc_smart_coupons') ); ?></a></center>

<?php if ( !empty( $from ) ) { ?>
	<p><?php echo __( 'You got this gift card for Free Membership', 'wc_smart_coupons' ) . ' ' . $from . $sender; ?></p>
<?php }*/ ?>

<div style="clear:both;"></div>

<?php woocommerce_get_template('emails/email-footer.php'); ?>
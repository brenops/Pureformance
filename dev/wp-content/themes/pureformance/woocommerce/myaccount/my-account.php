<?php
/**
 * My Account page
 *
 * @author
 * @package
 * @version
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;
global $wpdb;

// get user from pool by ID
$userId = get_current_user_id();
$row = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT ID, status, gift_key FROM {$wpdb->prefix}users_pool WHERE ID = %d",
        $userId
    )
);

$is_user_in_pool = (isset($row) && $row->status == 0);
$gifted_count    = 0;
$gifterFirstname = '';
$gift_key        = '';

if ($row) {
    // gift key for sharing
    $gift_key = $row->gift_key;
    // how many users were gifted
    $gifted_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gift_history WHERE user_purchaser = {$userId} AND status = 1 AND order_id IS NOT NULL" );

    if ($row->status == 1) {
        //
    }

}

$woocommerce->show_messages(); ?>

<p class="myaccount_user">
	<?php
	printf(
		__( 'Hello, <strong>%s</strong>. From your account dashboard you can view your recent orders, manage your shipping and billing addresses and <a href="%s">change your password</a>.', 'woocommerce' ),
		$current_user->display_name,
		get_permalink( woocommerce_get_page_id( 'change_password' ) )
	);
	?>
</p>

<?php do_action( 'woocommerce_before_my_account' ); ?>

<?php woocommerce_get_template( 'myaccount/my-downloads.php' ); ?>

<?php woocommerce_get_template( 'myaccount/my-orders.php', array( 'order_count' => $order_count ) ); ?>

<?php woocommerce_get_template( 'myaccount/my-address.php' ); ?>
 

<p class="myaccount_user">You have gifted <?php echo $gifted_count ?> people so far. <?php echo $gifted_count > 0 ? 'Great work!' : '' ?></p>

<?php if ($is_user_in_pool) : ?>

    <div class="copy">
        <h2>Need help getting out of the pool?</h2>

        <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
        <div style="float:left; width:400px;">
            <label for="link">Your Personal Invite link (copy and paste this link to anyone)</label>
        </div>
        <div>
            <input type="text" style="width:380px;" class="input-text" name="link" value="<?php echo home_url( '/' ) . 'give-gift/?key=' . $gift_key ?>" />
        </div>
        </form>
    </div>
    <div class="clear"></div>

    <div class="copy">
        <p class="myaccount_user">Want to ask someone specifically to gift you out of the pool. Enter their email address below and they
    will receive some background on your story and can click the link to gift your right into the site!</p>
    </div>
    <div class="clear"></div>

    <div class="copy">
        <form method="POST" id="send-email-form" action="">
        <div style="float:left; width:270px;">
            <label for="firstname">First Name:</label>
        </div>
        <div>
            <input type="text" class="input-text" name="firstname" id="ma-firstname" value="" />
        </div>
        <div style="float:left; width:270px;">
            <label for="email">Email:</label>
        </div>
        <div>
            <input type="text" class="input-text" name="email" id="ma-email" value="" />
        </div>
        <div>
            <input type="submit" class="btn1" name="sendEmail" value="<?php esc_attr_e( 'Send Email', 'twentyeleven' ); ?>" />
        </div>
        </form>
    </div>
    <div class="clear"></div>

<?php else : ?>

    <div class="copy">
        <p class="myaccount_user">You just been gifted into the pool by <?php echo $gifterFirstname ?>. He was kind enough to let you in the pool.
    Help him out by sharing his URL so he can join you in Performance.com as soon as possible!</p>
    </div>
    <div class="clear"></div>

    <div class="copy">
        <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
        <div style="float:left; width:400px;">
            <label for="link">Use this link to Your personal invite Link to help <?php echo $gifterFirstname ?> find someone
    to help him into the site (copy and paste this link to anyone)</label>
        </div>
        <div>
            <input type="text" class="input-text" style="width:380px;" name="link" value="<?php echo home_url( '/' ) . 'give-gift/?key=' . $gift_key ?>" />
        </div>
        </form>
    </div>
    <div class="clear"></div>

    <div class="copy">
        <p class="myaccount_user">Know someone else that would benefit from the gift of opportunity with Pureformance?</p>

        <form method="POST" id="give-gift-form" action="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ); ?>">
        <div style="float:left; width:270px;">
            <label for="firstname">First Name:</label>
        </div>
        <div>
            <input type="text" class="input-text" name="firstname" id="ca-firstname" value="" />
        </div>
        <div style="float:left; width:270px;">
            <label for="email">Email:</label>
        </div>
        <div>
            <input type="text" class="input-text" name="email" id="ca-email" value="" />
        </div>

        <div style="float:left; width:270px;">
            <label for="message">Custom Message:</label>
        </div>
        <div>
            <textarea class="input-text" rows="10" cols="30" name="message"></textarea>
        </div>

        <div>
            <input type="submit" class="btn1" name="giveGift" value="<?php esc_attr_e( 'Give Gift Now', 'twentyeleven' ); ?>" />
        </div>
        </form>
    </div>
    <div class="clear"></div>

<?php endif; ?>


<?php do_action( 'woocommerce_before_my_account' ); ?>

<?php //woocommerce_get_template( 'myaccount/my-downloads.php' ); ?>

<?php woocommerce_get_template( 'myaccount/my-orders.php', array( 'order_count' => $order_count ) ); ?>

<?php //woocommerce_get_template( 'myaccount/my-address.php' ); ?>

<?php do_action( 'woocommerce_after_my_account' ); ?>
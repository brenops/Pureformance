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

$isPool = null; // user who did not give a gift yet (empty or new user)


$giftedCount     = 0;
$gifterFirstname = '';
$purchaserFirstname = '';
$giftKey         = '';

if ( !empty($row) ) {
    if ( $row->status == 0 ) {
        // user in POOL (C2) - He gave a Gift
        $isPool = true;
        $giftKey = $row->gift_key; // key for share your page
        $gifter = get_userdata( $userId );
        $gifterFirstname = !empty($gifter->display_name) ? $gifter->display_name : $gifter->user_login;
        // how many users were gifted by current user
        $giftedCount = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gift_history WHERE user_purchaser = {$userId} AND status = 1 AND order_id IS NOT NULL" );
    }
    if ( $row->status == 1 ) {
        // user got a Gift from somebody (D2) - somebody gave him a Gift
        $isPool = false;
        // get last purchaser
        $userIdPurchaser = $wpdb->get_var( "SELECT user_purchaser FROM {$wpdb->prefix}gift_history WHERE user_receiver = {$userId} AND status = 1 AND order_id IS NOT NULL ORDER BY updated DESC LIMIT 1" );
        if ( $userIdPurchaser ) {
            $purchaser = get_userdata( $userIdPurchaser );
            $purchaserFirstname = !empty($purchaser->display_name) ? $purchaser->display_name : $purchaser->user_login;
            // get his giftKey
            $giftKey = $wpdb->get_var( "SELECT gift_key FROM {$wpdb->prefix}users_pool WHERE ID = {$userIdPurchaser}" );
        }
    }
}


$woocommerce->show_messages(); ?>

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=650155288367842";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<p class="myaccount_user">
    <?php
    printf(
            __( 'Hello, <strong>%s</strong>. From your account dashboard you can view your recent orders, manage your shipping and billing addresses and <a href="%s">change your password</a>.', 'woocommerce' ),
            $current_user->display_name,
            get_permalink( woocommerce_get_page_id( 'change_password' ) )
    );
    ?>
</p>

<?php if ($isPool == true) : /* User in the POOL - he needs to be promoted by himself */ ?>

    <div class="copy">
        <h2>Need help getting out of the pool?</h2>
    </div>
    <div class="clear"></div>

    <div class="copy">
        <div style="float:left; margin:5px 20px;">
            <div class="fb-share-button" data-href="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" data-width="300" data-type="box_count"></div>
        </div>
        <div style="float:left; margin:5px 20px;">
            <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" data-via="Pureformance" data-lang="en" data-text="Help to <?php echo isset($gifterFirstname) ? esc_attr( $gifterFirstname ) : '' ?>" data-count="vertical">Tweet</a>
        </div>
    </div>
    <div class="clear"></div>

    <div class="copy">
        <p class="myaccount_user">Want to ask someone specifically for access to Pureformance?
        Send them your custom URL.</p>

        <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
        <div style="float:left; width:400px;">
            <label for="link">Your Personal Invite link (copy and paste this link to anyone)</label>
        </div>
        <div>
            <input type="text" style="width:380px;" class="input-text" name="link" value="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" />
        </div>
        </form>
    </div>
    <div class="clear"></div>

    <div class="copy">
        <p class="myaccount_user">You have gifted <?php echo $giftedCount ?> people so far. <?php echo $giftedCount > 0 ? 'The World is glowing brighter!' : '' ?></p>
    </div>
    <div class="clear"></div>


<?php elseif ($isPool == false) : /* User not in the POOL, but was there */ ?>


    <div class="copy">
        <p class="myaccount_user">You just been gifted into the pool by <?php echo $purchaserFirstname ?>. He was kind enough to let you in the pool.
    Help him out by sharing his URL so he can join you in Performance.com as soon as possible!</p>
    </div>
    <div class="clear"></div>

    <div class="copy">
        <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
        <div style="float:left; width:400px;">
            <label for="link">Use this link to help <?php echo $purchaserFirstname ?> find someone into the site (copy and paste this link to anyone)</label>
        </div>
        <div>
            <input type="text" class="input-text" style="width:380px;" name="link" value="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" />
        </div>
        </form>
    </div>
    <div class="clear"></div>
    <div class="copy">
        <div style="float:left; margin:5px 20px;">
            <div class="fb-share-button" data-href="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" data-width="300" data-type="box_count"></div>
        </div>
        <div style="float:left; margin:5px 20px;">
            <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" data-via="Pureformance" data-lang="en" data-text="Help to <?php echo isset($purchaserFirstname) ? esc_attr( $purchaserFirstname ) : '' ?>" data-count="vertical">Tweet</a>
        </div>
    </div>
    <div class="clear"></div>

<?php endif; ?>

    <h2>Tell Us Who To Give Pureformance To</h2>
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


<?php do_action( 'woocommerce_before_my_account' ); ?>

<?php //woocommerce_get_template( 'myaccount/my-downloads.php' ); ?>

<?php woocommerce_get_template( 'myaccount/my-orders.php', array( 'order_count' => $order_count ) ); ?>

<?php //woocommerce_get_template( 'myaccount/my-address.php' ); ?>

<?php do_action( 'woocommerce_after_my_account' ); ?>
<?php
// Template Name: Promo-code

$firstname = null;
$email = null;
$purchaserName = null;
// Get coupon code from GET request
$couponCode = null;
if ( isset($_GET['coupon']) ) {
    $couponCode = trim($_GET['coupon']);
    $couponCode = preg_replace("/[^a-zA-Z0-9_\s]/", '', $couponCode);
    $coupon = new WC_Coupon( $couponCode );

    if ( $coupon && isset($coupon->customer_email) ) { // $coupon->is_valid()
        $email = is_array($coupon->customer_email) ? $coupon->customer_email[0] : '';

        // search in history
        global $wpdb;
        $receiver = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_purchaser, user_receiver_firstname FROM {$wpdb->prefix}gift_history WHERE user_receiver_email = '%s' AND status = 1 ORDER BY id DESC LIMIT 1",
                $email
            )
        );
        if ($receiver) {
            if ($receiver->user_receiver_firstname) {
                $firstname = $receiver->user_receiver_firstname;
            }
            // get purchaser Firstname
            if ($receiver->user_purchaser) {
                $purchaser = get_userdata( $receiver->user_purchaser );
                $purchaserName = !empty($purchaser->display_name) ? $purchaser->display_name : $purchaser->user_login;
            }
        }
    }
}

// if the coupon code is empty then redirect to promo page
if ( empty($couponCode) ) {
    header( 'Location: ' . home_url( '/' ) . 'promo/' );
    exit;
}

get_header();

?>
<script>
$(document).ready(function(){

});
</script>
<div id="content" class="wrapper portal">

    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="header">
            <?php
                $headline = get_post_meta($post->ID, 'headline', true);
                if ($headline == '') { $headline = '<span>PURE</span> Simple, Powerful, Effective, Trusted'; }
            ?>
            <h1 class="entry-title">You Got a Gift!<?php //echo $headline?></h1>
        </div>

        <div class="entry-content">
            <div class="copy">
                <h2 class="entry-title">Welcome to Pureformance!</h2>
                <?php if (!empty($purchaserName)) : ?>
                <h2 class="entry-title">You have just been gifted in by <?php echo $purchaserName ?>!</h2>
                <?php endif; ?>

                With this Gift, you have 30 days of unlimited access to find resources, connect with others,
                and access to interviews, videos, articles, and applications for your benefit.<br /><br />

                Before you get started, please enter a password for your new account.
            </div>
            <div class="clear"></div>
            <div class="copy">
                <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
                    <input type="hidden" name="coupon" value="<?php echo isset($couponCode) ? esc_attr($couponCode) : '' ?>" />
                <div style="float:left; width:270px;">
                    <label for="firstname">First Name:</label>
                </div>
                <div>
                    <input type="text" class="input-text" name="firstname" id="ca-firstname" value="<?php echo isset($firstname) ? esc_attr($firstname) : '' ?>" />
                </div>
                <div style="float:left; width:270px;">
                    <label for="email">Email:</label>
                </div>
                <div>
                    <input type="text" class="input-text" name="email" id="ca-email" value="<?php echo isset($email) ? esc_attr($email) : '' ?>" />
                </div>

                <div style="float:left; width:270px;">
                    <label for="password">Password:</label>
                </div>
                <div>
                    <input type="password" class="input-text" name="password" id="ca-password" value="" />
                </div>
                <div>
                    <input type="submit" class="btn1" name="createAccount" value="<?php esc_attr_e( "Let's Go!", 'twentyeleven' ); ?>" />
                </div>
                </form>
            </div>
            <div class="clear"></div>
        </div><!-- .entry-content -->
        <!--<div class="entry-content">
            <div class="copy">

                <a href="<?php echo home_url( '/' ); ?>create-account/?coupon=<?php echo $couponCode ?>" class="btn1"><span>Access My Account</span></a><br /><br />

            </div>
            <div class="clear"></div>
        </div>--><!-- .entry-content -->
    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

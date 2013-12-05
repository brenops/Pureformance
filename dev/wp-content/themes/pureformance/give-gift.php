<?php
// Template Name: Give the Gift

if ( !is_user_logged_in() ) {
    header( 'Location: ' . home_url( '/' ) . 'create-account/' );
    exit;
}

// preload information about user
$firstname = '';
$email     = '';
if (isset($_GET['key'])) {
    global $wpdb;
    $gift_key = trim($_GET['key']);
    $gift_key = preg_replace("/[^a-zA-Z0-9_\s]/", '', $gift_key);
    // get user from pool by gift key (unique key for user in pool)
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT ID, status FROM {$wpdb->prefix}users_pool WHERE gift_key = %s AND status = %d",
            $gift_key,
            0
        )
    );
    if ( $row && $row->ID ) {
        $receiver = get_userdata($row->ID);

        $firstname = !empty($receiver->user_nicename) ? $receiver->user_nicename : $receiver->user_login;
        $email     = $receiver->user_email;
    }
}

// Add a Gift Membership to cart of current user
do_action( 'addgifttocart' );
// Check if user has coupon (gift) and in POOL then go to checkout to buy a Membership
do_action( 'giftproceed' );

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
            <h1 class="entry-title">Give the Gift!<?php //echo $headline?></h1>
        </div>

        <div class="entry-content">
        <div class="copy">
            <h2>Give the Gift of Opportunity</h2>
            Enter the name and email of someone that you know can benefit from the higher principles and game changing content that Pureformance contains.
        </div>
        <div class="clear"></div>
        <div class="copy">
            <?php the_content(); ?>
            <div>
                <form method="POST" id="give-gift-form" action="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ); ?>">
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
        </div>

        <div class="clear"></div>
        </div><!-- .entry-content -->

    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

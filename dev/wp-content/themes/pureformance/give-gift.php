<?php
// Template Name: Give the Gift

if ( !is_user_logged_in() ) { // && !isset($_GET['key'])
    header( 'Location: ' . home_url( '/' ) . 'create-account/' );
    exit;
}

// preload from cookies

// preload information about user which need a help
$firstname = '';
$email     = '';
if ( isset($_GET['key']) ) {
    global $wpdb;
    $giftKey = trim($_GET['key']);
    $giftKey = preg_replace("/[^a-zA-Z0-9_\s]/", '', $giftKey);
    // get user from pool by gift key (unique key for user in pool)
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT ID, status FROM {$wpdb->prefix}users_pool WHERE gift_key = %s AND status = %d",
            $giftKey,
            0
        )
    );
    if ( $row && $row->ID ) {
        $receiver = get_userdata($row->ID);

        $firstname = !empty($receiver->display_name) ? $receiver->display_name : $receiver->user_login;
        $email     = $receiver->user_email;
    }
}

$isRandom = isset($_GET['random']) ? true : false;

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
<div id="content" class="wrapper">

    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="header">
            <?php
                $headline = get_post_meta($post->ID, 'headline', true);
                if ($headline == '') { $headline = 'The Gift That Matters'; }
            ?>
            <h1 class="entry-title"><?php echo $headline?></h1>
        </div>

        <div class="entry-content">

        <?php if ($isRandom) : ?>
        <div class="copy">
            <h2>Give the Gift to a Random user</h2>
            <p>Your friend was already gifted by someone. You can Give a Gift to a Random user or enter a First Name and Email of someone else below.</p>
        </div>
        <div class="clear"></div>
        <div class="copy">
            <p></p>
            <div>
                <form method="POST" id="give-gift-random-form" action="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ); ?>">
                    <input type="hidden" name="random" value="1" />
                <div>
                    <input type="submit" class="btn1" name="giveGift" value="<?php esc_attr_e( 'Give Gift To Random User', 'twentyeleven' ); ?>" />
                </div>
                </form>
            </div>
        </div>
        <div class="clear"></div>
        <?php endif; ?>

        <div class="copy">
            <h2>Tell Us Who To Give Pureformance To</h2>
            <p>Enter the name, email, and message of someone that you know can
benefit from the higher principles and game changing content <br>that
Pureformance contains.</p>
        </div>
        <div class="clear"></div>
        <div class="copy">
            <?php the_content(); ?>
            <div>
                <form method="POST" id="give-gift-form" action="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ); ?>">
                <div>
                    <input type="text" class="input-text" name="firstname" id="ca-firstname" value="<?php echo isset($firstname) ? esc_attr($firstname) : '' ?>" placeholder="First Name" />
                </div>
                <div>
                    <input type="text" class="input-text" name="email" id="ca-email" value="<?php echo isset($email) ? esc_attr($email) : '' ?>" placeholder="Email" />
                </div>
                <div>
                    <textarea class="input-text" rows="10" cols="30" name="message" placeholder="Message"></textarea>
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

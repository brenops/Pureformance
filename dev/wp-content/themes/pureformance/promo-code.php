<?php
// Template Name: Promo-code

// get coupon code from GET request
$code = isset($_GET['c']) ? trim($_GET['c']) : null;
$couponCode = null;

// if the code is empty then redirect to promo page
if (empty($code)) {
    header( 'Location: ' . home_url( '/' ) . 'promo/' );
    exit;
} else {

    // get the code from wpsc_coupon_codes table
    global $wpdb;

    /*if ($wpdb && $wpdb->prefix) {
        $couponCode = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, coupon_code, value, `is-used`, active, start, expiry FROM {$wpdb->prefix}wpsc_coupon_codes WHERE coupon_code = %s",
                $code
            ),
            ARRAY_A
        );

        //$couponCode['id'];
    }*/

    // if the code not found then redirect to promo page
    if (empty($couponCode)) {
        header( 'Location: ' . home_url( '/' ) . 'promo/' );
        exit;
    } else {

        // @todo check for expired?

        $code = isset($couponCode['coupon_code']) ? $couponCode['coupon_code'] : '';
    }

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
            <h1 class="entry-title">Welcome to Pureformance!<?php //echo $headline?></h1>
        </div>

        <div class="entry-content">
            <div class="copy">

                You have been chosen by Pureformance Team to be a Firestarter and begin a<br />
                movement that is sure to create waves online/<br /><br />

                The video below will help you to understand that Pureformance is all about and<br />
                the new Gifting experience that you will be leading.<br />
            </div>
            <div class="clear"></div>
        </div><!-- .entry-content -->
        <div class="entry-content">
            <div class="copy">

                <a href="<?php echo home_url( '/' ); ?>create-account/?c=<?php echo $code ?>" class="btn1"><span>Access My Account</span></a><br /><br />

            </div>
            <div class="clear"></div>
        </div><!-- .entry-content -->
    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

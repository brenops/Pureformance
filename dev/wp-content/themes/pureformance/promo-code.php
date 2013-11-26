<?php
// Template Name: Promo-code

// get coupon code from GET request
$couponCode = isset($_GET['coupon']) ? trim($_GET['coupon']) : null;
$couponCode = preg_replace("/[^a-zA-Z0-9_\s]/", '', $couponCode);

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

                <a href="<?php echo home_url( '/' ); ?>create-account/?coupon=<?php echo $couponCode ?>" class="btn1"><span>Access My Account</span></a><br /><br />

            </div>
            <div class="clear"></div>
        </div><!-- .entry-content -->
    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

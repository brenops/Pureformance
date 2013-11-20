<?php
// Template Name: Create Account

if ( is_user_logged_in() ) {
    header( 'Location: ' . home_url( '/' ) . 'give-gift/' );
    exit;
}

$firstname = null;
$email = null;

/*$couponCode = null;
// if the code is not empty
if (!empty($code)) {
    // get the code from wpsc_coupon_codes table
    global $wpdb;

    if ($wpdb && $wpdb->prefix) {
        $couponCode = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, coupon_code, value, `is-used`, active, start, expiry FROM {$wpdb->prefix}wpsc_coupon_codes WHERE coupon_code = %s",
                $code
            ),
            ARRAY_A
        );

        //$couponCode['id'];
    }

    // if the code not found then redirect to promo page
    if (!empty($couponCode)) {
        // @todo check for expired?

        $code = isset($couponCode['coupon_code']) ? $couponCode['coupon_code'] : '';

        $firstname = isset($couponCode['firstname']) ? $couponCode['firstname'] : '';
        $email = isset($couponCode['email']) ? $couponCode['email'] : '';
    }
}*/

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
            <h1 class="entry-title">Create your Account below to proceed!<?php //echo $headline?></h1>
        </div>

        <div class="entry-content">
        <div class="copy">
            <h2>Create Account</h2>
            <?php the_content(); ?>
            <div>
                <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
                <div style="float:left; width:270px;">
                    <label for="firstname">Username:</label>
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
                <div style="float:left; width:270px;">
                    <label for="passwordconfirm">Confirm Password:</label>
                </div>
                <div>
                    <input type="password" class="input-text" name="passwordconfirm" id="ca-passwordconfirm" value="" />
                </div>

                <div style="float:left; width:270px;">
                    Already have an account? <a href="#sign-in" id="sign-in-trigger" style="text-decoration:underline;">Sign In</a>
                </div>
                <div>
                    <input type="submit" class="btn1" name="createAccount" value="<?php esc_attr_e( 'Submit', 'twentyeleven' ); ?>" />
                </div>
                </form>
            </div>
        </div>
        <div class="clear"></div>
        <div style="display:none">
            <div id="sign-in" class="ask-expert-popup">
                <h2>You must be logged in to send the Gift of Opportunity</h2>
                <div>
                    <form method="post" id="sign-in-form">
                        <input type="text" class="input-text" name="username" id="username" placeholder="Username" />
                        <input class="input-text" type="password" name="password" id="password" placeholder="Password" />

                        <input type="hidden" name="redirect" value="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ) ?>" />

                        <div class="form-row">
                            <?php global $woocommerce; ?>
                            <?php $woocommerce->show_messages(); ?>
                            <?php $woocommerce->nonce_field('login', 'login') ?>
                            <input type="submit" class="button" name="login" value="<?php _e( 'Login', 'woocommerce' ); ?>" />
                            <a class="lost_password" href="<?php
                            $lost_password_page_id = woocommerce_get_page_id( 'lost_password' );
                            if ( $lost_password_page_id ) {
                                echo esc_url( get_permalink( $lost_password_page_id ) );
                            } else {
                                echo esc_url( wp_lostpassword_url( home_url() ) );
                            }
                            ?>"><?php _e( 'Lost Password?', 'woocommerce' ); ?></a>
                        </div>
                        <div class="form-row">
                            <p>Don't yet have an account? It's fast, free and easy.<br>Join now and start giving.</p>
                            <center><a href="<?php echo home_url( '/' ); ?>create-account/" class="btn1" style="display:inline-block"><span>Join Now</span></a></center>
			</div>
                    </form>
                </div>
            </div>
        </div>
        </div><!-- .entry-content -->

    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

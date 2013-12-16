<?php
// Template Name: Create Account

$firstname = null;
$email = null;
// Get coupon code and check
$couponCode = null;
if ( isset($_GET['coupon']) ) {
    $couponCode = trim($_GET['coupon']);
    $couponCode = preg_replace("/[^a-zA-Z0-9_\s]/", '', $couponCode);

    $coupon = new WC_Coupon( $couponCode );
    if ( $coupon && $coupon->is_valid() && isset($coupon->customer_email) ) {
        $email = is_array($coupon->customer_email) ? $coupon->customer_email[0] : '';
    }

    // Add a Membership (product) to cart of current user
    //do_action( 'addgifttocart' );
}

$giftKey = '';
if (isset($_GET['key'])) {
    $giftKey = trim($_GET['key']);
    $giftKey = preg_replace("/[^a-zA-Z0-9_\s]/", '', $giftKey);
}

if ( is_user_logged_in() ) {
    header( 'Location: ' . home_url( '/' ) . 'give-gift/' . ( !empty($giftKey) ? '?key=' . $giftKey : '' ) );
    exit;
}

get_header();
?>
<script>
$(document).ready(function(){
	$('#sign-in-trigger').fancybox({
		'scrolling'		: 'no', 
		'titleShow'		: false,
		'centerOnScroll' : true, 
		'onClosed'		: function() {
		    $("#login_error").hide();
		}
	});
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
        <div class="woocommerce">
        	<h2>To send a gift, please create a quick account.</h2>
			<h4>Easy as 1, 2, 3</h4>
            <?php the_content(); ?>
            <div class="col2-set">
	            <div class="col-1">
	                <form method="POST" id="create-account-form" class="register" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
	                    <input type="hidden" name="key" value="<?php echo isset($giftKey) ? esc_attr($giftKey) : '' ?>" />
	                    <input type="hidden" name="coupon" value="<?php echo isset($couponCode) ? esc_attr($couponCode) : '' ?>" />
	                <div>
	                    <input type="text" class="input-text" name="firstname" id="ca-firstname" value="<?php echo isset($firstname) ? esc_attr($firstname) : '' ?>" placeholder="First Name" />
	                </div>
	                <div>
	                    <input type="text" class="input-text" name="email" id="ca-email" value="<?php echo isset($email) ? esc_attr($email) : '' ?>" placeholder="Email Address" />
	                </div>
	                <div>
	                    <input type="password" class="input-text" name="password" id="ca-password" value="" placeholder="Password" />
	                </div>
	                <!--<div style="float:left; width:270px;">
	                    <label for="passwordconfirm">Confirm Password:</label>
	                </div>
	                <div>
	                    <input type="password" class="input-text" name="passwordconfirm" id="ca-passwordconfirm" value="" />
	                </div>-->
	
	                <div style="float:left; width:270px;font-size:12px;">
	                    Already have an account? <a href="#sign-in" id="sign-in-trigger" style="text-decoration:underline;">Sign In</a>
	                </div>
	                <div>
	                    <input type="submit" class="btn1" name="createAccount" value="<?php esc_attr_e( 'Submit', 'twentyeleven' ); ?>" />
	                </div>
	                </form>
                </div>
            </div>
        </div>
        <div class="clear"></div>
        <div style="display:none">
            <div id="sign-in" class="ask-expert-popup">
                <h2>You must be logged in to send the Gift of Opportunity</h2>
                <div>
                    <form method="post" id="sign-in-form">
                        <input type="text" class="input-text" name="username" id="username" placeholder="Username or Email" />
                        <input class="input-text" type="password" name="password" id="password" placeholder="Password" />

                        <input type="hidden" name="redirect" value="<?php echo esc_url( home_url( '/' ) . 'give-gift/' . ( !empty($giftKey) ? '?key=' . $giftKey : '' ) ) ?>" />

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
                        <div class="clear"></div>
                        <div class="form-row bottom">
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

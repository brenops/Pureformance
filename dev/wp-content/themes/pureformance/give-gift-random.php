<?php
// Template Name: Give Gift Random

// preload information about user which need a help
$firstname = '';
$email     = '';
$giftKey   = '';
if (isset($_GET['key'])) {
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

get_header();

?>
<!-- -->
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
            <h1 class="entry-title">Help to <?php echo isset($firstname) ? esc_attr( strtoupper( $firstname ) ) : '' ?><?php //echo $headline?></h1>
        </div>

        <div class="entry-content">
        <div class="copy">
            <h2>Help to <?php echo isset($firstname) ? esc_attr( strtoupper( $firstname ) ) : '' ?></h2>
            <p>My friend just gifted me into Pureformance! Who wants to give me a hand, pay it forward and gift them in? Oh, you get access too!</p>

            <p>Create your Account below to proceed!</p>
            <?php the_content(); ?>
            <div>
                <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
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
                        <input type="text" class="input-text" name="username" id="username" placeholder="Username or Email" />
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

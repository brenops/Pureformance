<?php
// Template Name: Friend Help

// preload information about user which need a help
$firstname = '';
$email     = '';

$firstnameReceiver = '';
$emailReceiver     = '';
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

        $firstnameReceiver = !empty($receiver->display_name) ? $receiver->display_name : $receiver->user_login;
        $emailReceiver     = $receiver->user_email;
    }
} else {
    header( 'Location: ' . home_url( '/' ) . 'create-account/' );
    exit;
}

if ( is_user_logged_in() ) {
    header( 'Location: ' . home_url( '/' ) . 'give-gift/' . ( !empty($giftKey) ? '?key=' . $giftKey : '' ) );
    exit;
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
            <h1 class="entry-title">The Gift That Matters<?php //echo $headline?></h1>
        </div>

        <div class="entry-content">
        <div class="copy">
            <h2>Gift <?php echo isset($firstnameReceiver) ? esc_attr( strtoupper( $firstnameReceiver ) ) : '' ?> in!</h2>
            <p><?php echo isset($firstnameReceiver) ? esc_attr( strtoupper( $firstnameReceiver ) ) : '' ?> gave a friend the gift of one month's membership
                and is looking for someone to give him the same gift in return so he can join them and gain access to our game changing site</p>

            <p>Get Started</p>
            <?php the_content(); ?>
            <div>
                <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
                    <input type="hidden" name="key" value="<?php echo isset($giftKey) ? esc_attr($giftKey) : '' ?>" />
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

                        <input type="hidden" name="redirect" value="<?php echo esc_url( home_url( '/' ) . 'give-gift/?key=' . $giftKey ) ?>" />

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
                            <center><a href="<?php echo home_url( '/' ) . 'create-account/?key=' . $giftKey; ?>" class="btn1" style="display:inline-block"><span>Join Now</span></a></center>
			</div>
                    </form>
                </div>
            </div>
        </div>
        </div><!-- .entry-content -->

    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

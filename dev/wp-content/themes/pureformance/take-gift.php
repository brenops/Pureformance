<?php
// Template Name: Take Gift

if (isset($_GET['coupon'])) {
    error_log('Take Gift: call addgifttocart' );
    // Add a Gift Membership to cart of current user
    do_action( 'addgifttocart' );
    wp_redirect( esc_url( home_url( '/' ) . 'checkout/' ) );
    exit;
} else {
    wp_redirect( esc_url( home_url( '/' ) . 'promo/' ) );
    exit;
}
?>
<?php
/*
Plugin Name: Send Coupon
Plugin URI:
Description: Check Add "new post" and if post_type=shop_coupon then send email to the user
Version: 1.0
Author:
Author URI:
*/

add_action( 'save_post', 'scSendCouponByEmail' );

$scOutput = '';

function scSendCouponByEmail($post_id) {

    $post = get_post($post_id);

    if ($post->post_status != 'publish') { // e.g. == 'draft'
        return;
    }

    // only for add Coupon
    if ($post->post_type != 'shop_coupon') { // e.g. == 'shop_order'
        return;
    }

    // skip on update or should we send a new email?
    if ($post->post_date != $post->post_modified) {
        return;
    }

    global $wpdb;
    global $scOutput;

    $customer_email = get_post_meta( $post_id, 'customer_email', true );
    if (is_array($customer_email)) {
        $customer_email = array_shift($customer_email);
    }
    $discount_type = get_post_meta( $post_id, 'discount_type', true );
    $expiry_date = get_post_meta( $post_id, 'expiry_date', true );

    error_log('post save: post_status:' . var_export($post->post_status, 1) ); // post_status:'publish'
    error_log('post save: post_type:' . var_export($post->post_type, 1) ); // post_type:'shop_coupon'
    error_log('post save: post_name:' . var_export($post->post_name, 1) ); // post_name:'test-coupon-004'
    error_log('post save: post_excerpt:' . var_export($post->post_excerpt, 1) ); // post_excerpt:'test coupon 4 you 004'
    error_log('post save: post_date:' . var_export($post->post_date, 1) . ' post_modified:' . var_export($post->post_modified, 1) ); // post_date:'2013-11-11 23:03:58' post_modified:'2013-11-11 23:03:58' not equal on update, equal on add
    error_log('post save: customer_email:' . var_export($customer_email, 1) ); // customer_email:'nikitaleksikov@gmail.com'
    error_log('post save: discount_type:' . var_export($discount_type, 1) ); //  discount_type:'sign_up_fee'
    error_log('post save: expiry_date:' . var_export($expiry_date, 1) ); // expiry_date:'2013-11-29',

    $coupon_code = $post->post_name;
    $created = time();
    // send email
    $result = wp_mail($customer_email, __('Pureformance send a Coupon for you'), "You got a new coupon:{$coupon_code} ({$post->post_excerpt}) for Free access to Pureformance content till {$expiry_date}. For activation go to: " . home_url( '/' ) . '/promo-code/?coupon=' . $coupon_code);

    if ($result) {
        $wpdb->insert(
            $wpdb->prefix . 'coupon_send',
            array('user_email' => $customer_email, 'user_coupon' => $coupon_code, 'created' => $created),
            array('%s', '%s', '%d')
        );

        $scOutput = '<p>Coupon has been successfully sent.</p>';
    } else {
        $scOutput = '<p>Coupon was not sent.</p>';
    }

    add_filter('the_content', 'scContentFilter');
}

function scContentFilter($content) {
    global $scOutput;

    return $content . $scOutput;
}

?>
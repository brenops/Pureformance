<?php
/*
Plugin Name: Create Account
Plugin URI:
Description: Check First Name, Email, Password. Create a temp user account and send activation email to the user.
Version: 1.0
Author:
Author URI:
*/

$caOutput = '';

add_action( 'plugins_loaded', 'caInit' );

function caInit() {
    if ( isset( $_POST['createAccount'] ) ) {
        // register and create temp account
        caAddTempUser();
    } elseif ( isset($_GET['c']) ) {
        // create permanent wp user account
        caAddUser();
    }
}

function caLoginWithEmail( $username ) {
    $user = get_user_by( 'email', $username );
    if ( !empty( $user->user_login ) ) {
        $username = $user->user_login;
    }

    return $username;
}
add_action( 'wp_authenticate', 'caLoginWithEmail' );

function changeUsernameWpsText( $text ) {
    if ( in_array( $GLOBALS['pagenow'], array('wp-login.php')) ) {
        if ( $text == 'Username' ) {
            $text = 'Username or Email';
        }
    }
    return $text;
}
add_filter( 'gettext', 'changeUsernameWpsText' );

/**
 * 1. Coupon exists - user got it by email from Admin. Then create and login this user automatically.
 * 2. Regular registration - create temp user and then activate account
 * 2.1 Check if new temp user has a coupon - when somebody bought the coupon for him, when he was not registered yet. Then register him and add Membership to cart.
 *
 * User create new account
 * - check all fields
 * - check coupon, add to card if valid
 * - create temp user
 * - send email with activation link
 *
 * @global type $wpdb
 * @global type $caOutput
 * @global type $woocommerce
 * @return type
 */
function caAddTempUser() {
    global $wpdb;
    global $caOutput;

    $errors = array();

    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $email     = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password  = isset($_POST['password']) ? trim($_POST['password']) : '';
    //$passwordconfirm = isset($_POST['passwordconfirm']) ? trim($_POST['passwordconfirm']) : '';
    // [optional] in case if admin added a coupon for the new user (which not exists in db) and the user create an account
    $couponCode = isset($_POST['coupon']) ? trim($_POST['coupon']) : null;
    $giftKey    = isset($_POST['key']) ? trim($_POST['key']) : null;

    // allow only a-zA-Z0-9_
    // $firstname = preg_replace("/[^a-zA-Z0-9_\s]/", '', $firstname);
    if ( !empty($couponCode) ) {
        $couponCode = preg_replace("/[^a-zA-Z0-9_\s]/", '', $couponCode);
    }
    if ( !empty($giftKey) ) {
        $giftKey = preg_replace("/[^a-zA-Z0-9_\s]/", '', $giftKey);
    }

    error_log('caAddTempUser: giftKey:' . var_export($giftKey, 1) );

    $data = array(
        'firstname' => $firstname,
        'email'     => $email,
        'password'  => $password,
        //'passwordconfirm' => $passwordconfirm,
    );

    $errors = caUserDataValidation( $data );

    // 1. Coupon exists - user got it from Admin by email
    if ( !empty( $couponCode ) ) {

        // @todo a function
        if ($errors) {
            $caOutput = '<div style="color:red;"><ul>';
            foreach ($errors as $error) {
                $caOutput .= '<li>' . $error . '</li>';
            }
            $caOutput .= '</ul></div>';

            add_filter('the_content', 'caContentFilter');

            return;
        }

        // validate coupon
        $coupon = new WC_Coupon( $couponCode );
        if ( !$coupon->is_valid() ) {
            $errors['coupon'] = $coupon->get_error_message();
        } else {
            error_log('caAddTempUser: coupon is valid:' . var_export($couponCode, 1) );
            // Create new permanent wp user (only if the user has an invite from admin (coupon)
            //$userId = wp_create_user($firstname, $password, $email);

            $username = caCreateUsernameFromEmail($email);

            $userId = wp_insert_user( array(
                'user_login'  => $username,
                'user_pass'   => $password,
                'user_email'  => $email,
                'display_name' => $firstname
            ) );
            error_log('caAddTempUser: new user created. user id:' . var_export($userId, 1) );
            if ($userId) {
                // get login
                //$userInfo = get_userdata($userId);
                //$username = $user_info->user_login;
                error_log('caAddTempUser: username:' . var_export($username, 1) );

                $caOutput = '<p>' . __('You have successfully created an Account') . '</p>';
                // auto sign in
                $credentials = array(
                    'user_login'    => $username,
                    'user_password' => $password,
                    'remember'      => false
                );
                $user = wp_signon($credentials, false);

                // add to pool
                // check if user in pool
                $row = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT ID, status, gift_key FROM {$wpdb->prefix}users_pool WHERE ID = %d",
                        $userId
                    )
                );
                // add user to pool (only once) with status = 0 (user IN POOL)
                if ( !$row ) {
                    // generate a key for invite page
                    $gift_key = wp_generate_password( 20, false );
                    $created = time();
                    $wpdb->insert(
                        $wpdb->prefix . 'users_pool',
                        array('ID' => $userId, 'status' => 0, 'gift_key' => $gift_key, 'created' => $created),
                        array('%d', '%d', '%s', '%d')
                    );
                }

                // @todo redirect to checkout page
                wp_redirect( esc_url( home_url( '/' ) . 'cart/?coupon=' . $couponCode ) );
                exit;

            } else {
                $caOutput = '<p>' . __('Something happened. Please try again.') . '</p>';
            }
        }
    }

    // @todo put into a function
    if ($errors) {
        $caOutput = '<div style="color:red;"><ul>';
        foreach ($errors as $error) {
            $caOutput .= '<li>' . $error . '</li>';
        }
        $caOutput .= '</ul></div>';

        add_filter( 'the_content', 'caContentFilter' );

        return;
    }

    // 2. Regular registration temp user account
    if (empty($couponCode)) {
        $code = wp_generate_password(16, false);
        $created = time();

        // store $firstname in user_login field
        // @todo rename column user_login to user_firstname
        $wpdb->insert(
            $wpdb->prefix . 'users_temp',
            array('user_login' => $firstname, 'user_pass' => $password, 'user_email' => $email, 'user_activation_key' => $code, 'created' => $created),
            array('%s', '%s', '%s', '%s', '%d')
        );

        wp_mail($email, __('Registration'), "Welcome {$firstname}! For activation go to: " . home_url( '/' ) . 'give-gift/?c=' . $code . ( !empty($giftKey) ? '&key=' . $giftKey : '' ) );

        $caOutput = '<p>You have been successfully registered. Check Your Email To Activate Your Account.</p>';
    }

    add_filter('the_content', 'caContentFilter');
}

function caUserDataValidation($data) {
    $errors = array();

    if (empty($data['firstname'])) {
        $errors['firstname'] = __('Firstname is empty');
    } elseif (mb_strlen($data['firstname']) > 60) {
        $errors['firstname'] = __('Firstname can not be longer than 60 characters');
    }

    if (empty($data['email'])) {
        $errors['email'] = __('Email is empty');
    } elseif (mb_strlen($data['email']) > 100) {
        $errors['email'] = __('Email can not be longer than 100 characters');
    } else if (!is_email($data['email'])) {
        $errors['email'] = __('Provided incorrect Email address');
    } else if (email_exists($data['email'])) {
        $errors['email'] = __('Email already exists');
    }

    if (empty($data['password'])) {
        $errors['password'] = __('Password is empty');
    } elseif (mb_strlen($data['password']) < 6) {
        $errors['password'] = __('Password must be longer than 5 characters');
    } /*elseif (strcmp($data['password'], $data['passwordconfirm']) != 0) {
        $errors['passwordconfirm'] = __('Different Passwords');
    }*/

    return $errors;
}

/**
 * Create new user using temporary account
 *
 * @global type $wpdb
 * @global type $caOutput
 */
function caAddUser() {
    global $wpdb;
    global $caOutput;

    $code = trim($_GET['c']);
    $tempUserExist = null;

    $giftKey = isset($_GET['key']) ? trim($_GET['key']) : null;
    if ( !empty($giftKey) ) {
        $giftKey = preg_replace("/[^a-zA-Z0-9_\s]/", '', $giftKey);
    }

    error_log('caAddUser: giftKey:' . var_export($giftKey, 1) );

    if ( !empty($code) ) {
        $tempUserExist = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, user_pass FROM {$wpdb->prefix}users_temp WHERE user_activation_key = %s",
                $code
            )
        );
    }
    // , user_coupon

    if ( $tempUserExist ) {

        $data = array(
            'firstname' => $tempUserExist->user_login,
            'email'     => $tempUserExist->user_email,
            'password'  => $tempUserExist->user_pass,
            //'passwordconfirm' => $tempUserExist->user_pass,
        );

	$errors = caUserDataValidation($data);

        if ($errors) {
            $caOutput = '<p>' . __('Account already activated') . '</p>';
        } else {
            //$userId = wp_create_user($tempUserExist->user_login, $tempUserExist->user_pass, $tempUserExist->user_email);

            $username = caCreateUsernameFromEmail($tempUserExist->user_email);

            $userId = wp_insert_user( array (
                'user_login'   => $username,
                'user_pass'    => $tempUserExist->user_pass,
                'user_email'   => $tempUserExist->user_email,
                'display_name' => $tempUserExist->user_login
            ) );

            if ($userId) {
                // get login
                //$userInfo = get_userdata($userId);
                //$username = $user_info->user_login;
                error_log('caAddUser: username:' . var_export($username, 1) );

                $caOutput = '<p>' . __('You have successfully activated your Account') . '</p>';

                // auto sign in
                $credentials = array(
                    'user_login'    => $username,
                    'user_password' => $tempUserExist->user_pass,
                    'remember'      => false
                );
                $user = wp_signon($credentials, false);

                // Check Coupon for exists. If someone bought for the user which was not registered.
                $userHasCoupon = false;
                $args = array(
                    'numberposts' => -1,
                    'meta_key'    => 'customer_email',
                    'meta_value'  => serialize( array($tempUserExist->user_email) ),
                    'post_type'   => 'shop_coupon',
                    'post_status' => 'publish'
                );

                error_log('caAddUser: get coupons with args:' . var_export($args, 1) );
                $coupons = get_posts($args);
                //error_log('caAddUser: coupons:' . var_export($coupons, 1) );

                $couponForMembership = '';
                if ($coupons) {
                    foreach ($coupons as $coupon) {
                        $couponForMembership = $coupon->post_title;
                        error_log('caAddUser: coupon:' . var_export($couponForMembership, 1) );

                        $couponObj = new WC_Coupon($couponForMembership);
                        error_log( 'ggAddProductToCard: coupon given, check is valid:' . var_export($couponForMembership, 1) );

                        if ( $couponObj->is_valid() ) {
                            $userHasCoupon = true;
                            error_log( 'ggAddProductToCard: coupon is valid:' . var_export($couponForMembership, 1) );
                            break;
                        }
                    }
                }

                // remove temp entry
                $wpdb->delete( $wpdb->prefix . 'users_temp', array( 'ID' => $tempUserExist->ID ), array( '%d' ) );
                unset($tempUserExist);

                // then add entry to POOL
                if ($userHasCoupon) {
                    error_log( 'ggAddProductToCard: coupon is valid, add user to POOL:' . var_export($userHasCoupon, 1) );
                    // add to pool
                    // check if user in POOL
                    $row = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT ID, status, gift_key FROM {$wpdb->prefix}users_pool WHERE ID = %d",
                            $userId
                        )
                    );
                    // add user to pool (only once) with status = 0 (user IN POOL)
                    if ( !$row ) {
                        // generate a key for invite page
                        $gift_key = wp_generate_password( 20, false );
                        $created = time();
                        $wpdb->insert(
                            $wpdb->prefix . 'users_pool',
                            array('ID' => $userId, 'status' => 0, 'gift_key' => $gift_key, 'created' => $created),
                            array('%d', '%d', '%s', '%d')
                        );
                    }

                    // @todo redirect to checkout page
                    wp_redirect( esc_url( home_url( '/' ) . 'cart/?coupon=' . $couponForMembership ) );
                    exit;
                }
            } else {
                $caOutput = '<p>' . __('Something happened. Please try again.') . '</p>';
            }

        }
    } else {
        $caOutput = '<p>' . __('Incorrect code') . '</p>';
        error_log('caAddUser: Incorrect code:' . var_export($code, 1) );
    }

    add_filter('the_content', 'caContentFilter');

    // redirect to Give a Gift page
    error_log('caAddUser: got to give-gift with giftKey:' . var_export($giftKey, 1) );
    wp_redirect( esc_url( home_url( '/' ) . 'give-gift/' . ( !empty($giftKey) ? '?key=' . $giftKey : '' ) ) );
    exit;

    /*if ( is_user_logged_in() ) {
        wp_redirect( esc_url( home_url( '/' ) . 'give-gift/' ) );
        exit;
    } else {
        wp_redirect( esc_url( home_url( '/' ) ) );
        exit;
    }*/
}

function caCreateUsernameFromEmail( $email ) {
    $username = null;
    error_log('caCreateUsernameFromEmail: email:' . var_export($email, 1) );
    if (!is_email($email)) {
        return $username;
    }

    $usernameTemplate = explode('@', $email);
    $usernameTemplate = isset($usernameTemplate[0]) ? $usernameTemplate[0] : null;

    error_log('caCreateUsernameFromEmail: username:' . var_export($usernameTemplate, 1) );

    if ( empty( $usernameTemplate ) ) {
        return $username;
    }

    $i = 1;
    $username = $usernameTemplate;
    while ( username_exists( $username ) != null ) {
        $username = $usernameTemplate . $i;
        error_log('caCreateUsernameFromEmail: try username:' . var_export($username, 1) );
        $i++;
    }

    return $username;
}

function caContentFilter($content) {
    global $caOutput;

    return $content . $caOutput;
}

?>
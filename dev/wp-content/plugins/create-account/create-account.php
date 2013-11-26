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
    if (isset($_POST['createAccount'])) {
        // register and create temp account
        caAddTempUser();
    } elseif (isset($_GET['c'])) {
        // create permanent wp user account
        caAddUser();
    }
}

function caAddTempUser() {
    global $wpdb;
    global $caOutput;

    $errors = array();

    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $email     = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password  = isset($_POST['password']) ? trim($_POST['password']) : '';
    $passwordconfirm = isset($_POST['passwordconfirm']) ? trim($_POST['passwordconfirm']) : '';
    // optional in case if admin added a coupon for the new user
    $couponCode = isset($_POST['coupon']) ? trim($_POST['coupon']) : null;


    // allow only a-z and 0-9
    $firstname = preg_replace("/[^a-zA-Z0-9_\s]/", '', $firstname);
    if (!empty($couponCode)) {
        $couponCode = preg_replace("/[^a-zA-Z0-9_\s]/", '', $couponCode);
    }

    $data = array(
        'firstname' => $firstname,
        'email'     => $email,
        'password'  => $password,
        'passwordconfirm' => $passwordconfirm,
    );

    $errors = caUserDataValidation($data);

    // check coupon
    if (!empty($couponCode)) {
        // validate
        $coupon = new WC_Coupon($couponCode);
        if (!$coupon->is_valid()) {
            $errors['coupon'] = $coupon->get_error_message();
        } else {
            error_log('coupon is valid:' . var_export($couponCode, 1) );
            // buy a membership using coupon
            global $woocommerce;
            if (!$woocommerce->cart->add_discount( sanitize_text_field( $couponCode ))) {
                error_log('error add coupon:' . $couponCode . ' to cart');
                $caOutput = '<p>' . __('Something happened. Please try again.') . '</p>';
                //$woocommerce->show_messages();
            }
            $woocommerce->cart->calculate_totals();

            // create new permanent wp user
            $userId = wp_create_user($tempUserExist->user_login, $tempUserExist->user_pass, $tempUserExist->user_email);
            error_log('new user created. user id:' . var_export($userId, 1) );
            if ($userId) {
                $caOutput = '<p>' . __('You have successfully created an Account') . '</p>';
                // auto sign in
                $credentials = array(
                    'user_login'    => $firstname,
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
                // add user to pool (only once) with status = 1 (active user)
                if (!$row) {
                    // generate a key for invite page
                    $gift_key = wp_generate_password(20, false);
                    $created = time();
                    $wpdb->insert(
                        $wpdb->prefix . 'users_pool',
                        array('ID' => $userId, 'status' => 1, 'gift_key' => $gift_key, 'created' => $created),
                        array('%d', '%d', '%s', '%d')
                    );
                }

                // send email
                

                // redirect to my-account page
                wp_redirect( esc_url( home_url( '/' ) . 'my-account/' ) );
                exit;

            } else {
                $caOutput = '<p>' . __('Something happened. Please try again.') . '</p>';
            }
        }
    }

    if ($errors) {
        $caOutput = '<div style="color:red;"><ul>';
        foreach ($errors as $error) {
            $caOutput .= '<li>' . $error . '</li>';
        }
        $caOutput .= '</ul></div>';

        add_filter('the_content', 'caContentFilter');

        return;
    }

    // only for register temp user accounts
    if (empty($couponCode)) {
        $code = wp_generate_password(16, false);
        $created = time();

        $wpdb->insert(
            $wpdb->prefix . 'users_temp',
            array('user_login' => $firstname, 'user_pass' => $password, 'user_email' => $email, 'user_activation_key' => $code, 'created' => $created),
            array('%s', '%s', '%s', '%s', '%d')
        );

        wp_mail($email, __('Registration'), "Welcome {$firstname}, Your password: {$password}. For activation go to: " . home_url( '/' ) . '?c=' . $code);

        $caOutput = '<p>You have been successfully registered. Check Your Email To Activate Your Account.</p>';
                //. home_url( '/' ) . '?c=' . $code;
    }

    add_filter('the_content', 'caContentFilter');
}

function caUserDataValidation($data) {
    $errors = array();

    if (empty($data['firstname'])) {
        $errors['firstname'] = __('Username is empty');
    } elseif (mb_strlen($data['firstname']) > 60) {
        $errors['firstname'] = __('Username can not be longer than 60 characters');
    } elseif (username_exists($data['firstname'])) {
        $errors['firstname'] = __('Username already exists');
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
    } elseif (strcmp($data['password'], $data['passwordconfirm']) != 0) {
        $errors['passwordconfirm'] = __('Different Passwords');
    }

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

    if (!empty($code)) {
        $tempUserExist = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, user_pass FROM {$wpdb->prefix}users_temp WHERE user_activation_key = %s",
                $code
            )
        );
    }

    if ($tempUserExist) {

        $data = array(
            'firstname' => $tempUserExist->user_login,
            'email'     => $tempUserExist->user_email,
            'password'  => $tempUserExist->user_pass,
            'passwordconfirm' => $tempUserExist->user_pass,
        );

	$errors = caUserDataValidation($data);

        if ($errors) {
            $caOutput = '<p>' . __('Account already activated') . '</p>';
        } else {
            $userId = wp_create_user($tempUserExist->user_login, $tempUserExist->user_pass, $tempUserExist->user_email);
            if ($userId) {
                $caOutput = '<p>' . __('You have successfully activated your Account') . '</p>';

                // auto sign in
                $credentials = array(
                    'user_login'    => $tempUserExist->user_login,
                    'user_password' => $tempUserExist->user_pass,
                    'remember'      => false
                );
                $user = wp_signon($credentials, false);
                // remove temp entry
                $wpdb->delete( $wpdb->prefix . 'users_temp', array( 'ID' => $tempUserExist->ID ), array( '%d' ) );
                unset($tempUserExist);
            } else {
                $caOutput = '<p>' . __('Something happened. Please try again.') . '</p>';
            }

        }
    } else {
        $caOutput = '<p>' . __('Incorrect code') . '</p>';
    }

    add_filter('the_content', 'caContentFilter');

    // redirect to Give a Gift page
    wp_redirect( esc_url( home_url( '/' ) . 'give-gift/' ) );
    exit;

    /*if ( is_user_logged_in() ) {
        wp_redirect( esc_url( home_url( '/' ) . 'give-gift/' ) );
        exit;
    } else {
        wp_redirect( esc_url( home_url( '/' ) ) );
        exit;
    }*/
}

function caContentFilter($content) {
    global $caOutput;

    return $content . $caOutput;
}

?>
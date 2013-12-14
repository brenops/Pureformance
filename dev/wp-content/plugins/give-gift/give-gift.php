<?php
/*
Plugin Name: Give Gift
Plugin URI:
Description: Give a Gift to somebody. Check First Name, Email, Message and if the recipient exists in Pool.
Version:
Author:
Author URI:
*/

/**
 * Add a new product in admin panel (membership),
 * Add a smart coupon for this product
 * Get a product_id and update the constant
 */
define('MEMBERSHIP_GIFT_PRODUCT_ID', 637);
define('MEMBERSHIP_PRODUCT_ID', 267); // Membership (monthly)


$ggOutput = '';
$ggReceiver = null;

// init plugin
add_action( 'plugins_loaded', 'ggInit' );

// On checkout submit, update gift history and send emails
add_action( 'woocommerce_thankyou', 'ggGiveGift', 10 );

// On Give a Gift page add a product (gift for membership) to cart
add_action( 'addgifttocart', 'ggAddProductToCard' );

// On Give a Gift page check the user in POOL and coupon exists then go to checkout
add_action( 'giftproceed', 'ggGiftProceed' );


function ggIsGift() {
    return true;
}

function ggInfoMessage() {
    return 'Give a gift for:';
}

function ggGetLastReceiver() {
    global $wpdb;
    global $ggReceiver;

    if ( !$ggReceiver ) {
        $purchaserUserId = get_current_user_id();

        $ggReceiver = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_receiver, user_receiver_email, user_receiver_firstname, user_receiver_message FROM {$wpdb->prefix}gift_history WHERE user_purchaser = %d AND status = 0 ORDER BY updated DESC LIMIT 1",
                $purchaserUserId
            )
        );
    }
}

function ggReceiverEmail() {
    global $ggReceiver;
    $receiverEmail = '';

    ggGetLastReceiver();

    if ($ggReceiver && $ggReceiver->user_receiver_email) {
        // case when user Gave a Gift to receiver who is not registered yet
        $receiverEmail = $ggReceiver->user_receiver_email;
    } if ($ggReceiver && $ggReceiver->user_receiver) {
        $receiver = get_userdata($ggReceiver->user_receiver);
        $receiverEmail = $receiver->user_email;
    }

    return $receiverEmail;
}

function ggReceiverName() {
    global $ggReceiver;
    $receiverName = '';

    ggGetLastReceiver();

    if ($ggReceiver && $ggReceiver->user_receiver_firstname) {
        // case when user Gave a Gift to receiver who is not registered yet
        $receiverName = $ggReceiver->user_receiver_firstname;
    } else if ($ggReceiver && $ggReceiver->user_receiver) {
        $receiver = get_userdata($ggReceiver->user_receiver);
        $receiverName = !empty($receiver->display_name) ? $receiver->display_name : $receiver->user_login;
    }

    return $receiverName;
}

function ggReceiverMessage() {
    global $ggReceiver;
    $receiverMessage = '';

    ggGetLastReceiver();

    if ($ggReceiver && $ggReceiver->user_receiver_message) {
        $receiverMessage = $ggReceiver->user_receiver_message;
    }

    return $receiverMessage;
}

/**
 * Init plugin
 * Check Give a Gift form submit
 *
 */
function ggInit() {

    if (isset($_POST['giveGift'])) {

        if ( !is_user_logged_in() ) {
            // @todo save form fields to cookies

            wp_redirect( esc_url( home_url( '/' ) . 'create-account/' ) );
            exit;
        }

        // Give a Gift form submit - save receiver data and go to checkout
        ggSaveGiftReceiver();
    }

}

/**
 * On Give a Gift page submit
 * Save Gift receiver Firstname, email, message and go to checkout
 *
 * @global type $wpdb
 * @global type $ggOutput
 * @global type $current_user
 * @return type
 */
function ggSaveGiftReceiver() {
    global $wpdb;
    global $ggOutput;

    $errors = array();

    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $email     = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message   = isset($_POST['message']) ? trim($_POST['message']) : '';
    $isRandom  = isset($_POST['random']) ? intval($_POST['random']) : null;

    error_log('ggSaveGiftReceiver: isRandom:' . var_export($isRandom, 1) );
    // allow only a-z and 0-9
    //$firstname = preg_replace("/[^a-zA-Z0-9_\s]/", '', $firstname);

    $data = array(
        'firstname' => $firstname,
        'email'     => $email,
        'message'   => $message
    );

    if ( $isRandom ) {
        // get random user from POOL (the oldest user)
        if ( $wpdb && $wpdb->prefix ) {
                // select the oldest user from POOL
                $userPool = $wpdb->get_row(
                    "SELECT ID, status FROM {$wpdb->prefix}users_pool WHERE status = 0 ORDER BY created LIMIT 1",
                    ARRAY_A
                );

                error_log( 'ggSaveGiftReceiver: get from POOL:' . var_export($userPool, 1) );

                if ( $userPool && !empty($userPool['ID']) ) {
                    $userId = intval($userPool['ID']);
                    $receiver = get_userdata($userId);
                    error_log( 'ggSaveGiftReceiver: userId:' . $userId . ' receiver:' . var_export($receiver, 1) );

                    $firstname = !empty($receiver->display_name) ? $receiver->display_name : $receiver->user_login;
                    $email     = $receiver->user_email;
                    $data = array(
                        'firstname' => $firstname,
                        'email'     => $email,
                        'message'   => _('Here is my Gift for you')
                    );
                    error_log( 'ggSaveGiftReceiver: prepare data:' . var_export($data, 1) );
                } else {
                    error_log( 'ggSaveGiftReceiver: POOL is empty' );
                    // no one in the POOL
                    wp_redirect( esc_url( home_url( '/' ) . 'give-gift/?random=false' ) );
                    exit;
                }
            }
    } else {
        // check if user already gifted by another user
        // add check is the user in the POOL?
        $userId = email_exists($data['email']);
        if ( $userId ) {
            error_log( 'ggSaveGiftReceiver: check POOL for user id:' . var_export($userId, 1) );

            // check if user in POOL
            if ( $wpdb && $wpdb->prefix ) {
                $userPool = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT ID, status FROM {$wpdb->prefix}users_pool WHERE ID = %d",
                        $userId
                    ),
                    ARRAY_A
                );

                error_log( 'ggSaveGiftReceiver: get from POOL:' . var_export($userPool, 1) );

                if ( isset($userPool['status']) ) {
                    // if user gifted by someone else
                    if ( $userPool['status'] == 1 ) {
                        //$data['firstname'];
                        // redirect with random flag
                        wp_redirect( esc_url( home_url( '/' ) . 'give-gift/?random=1' ) );
                        exit;
                    }
                }
            }
        }
    }

    $errors = ggUserDataValidation( $data, $isRandom );

    if ( $errors ) {
        $ggOutput = '<div style="color:red;"><ul>';
        foreach ($errors as $error) {
            $ggOutput .= '<li>' . $error . '</li>';
        }
        $ggOutput .= '</ul></div>';

        add_filter('the_content', 'ggContentFilter');

        return;
    }

    // save receiver info to gift_history with status = 0
    global $current_user;
    get_currentuserinfo();
    $purchaserUserId = $current_user->ID;
    $receiverUserId = email_exists($email);
    $created = time();

    error_log( 'ggSaveGiftReceiver: save Gift history for receiver userId:' . var_export($receiverUserId, 1) . ' email:' . var_export($email, 1) );
    // check for exists
    $receiver = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT user_receiver, user_receiver_email, user_receiver_firstname, user_receiver_message FROM {$wpdb->prefix}gift_history WHERE user_purchaser = %d AND user_receiver_email = '%s' AND status = 0 ORDER BY id DESC LIMIT 1",
            $purchaserUserId, $email
        ), ARRAY_A
    );
    // if already exists (e.g. second try to give a gift) then update receiver data
    if ($receiver) {
        $wpdb->update(
            $wpdb->prefix . 'gift_history',
            array(
                'user_receiver_firstname' => $firstname,
                'user_receiver_message'   => $message,
                'updated'                 => $created
            ),
            array(
                'user_purchaser'      => $purchaserUserId,
                'user_receiver_email' => $email,
                'status'              => 0,
            ),
            array(
                '%s',
                '%s',
                '%d'
            ),
            array(
                '%d',
                '%s',
                '%d'
            )
        );
    } else {
        $wpdb->insert(
            $wpdb->prefix . 'gift_history',
            array('user_purchaser' => $purchaserUserId, 'user_receiver' => $receiverUserId, 'user_receiver_email' => $email, 'user_receiver_firstname' => $firstname, 'user_receiver_message' => $message, 'status' => 0, 'created' => $created),
            array('%d', '%d', '%s', '%s', '%s', '%d', '%d')
        );
    }

    // go to checkout
    //wp_redirect( esc_url( home_url( '/' ) . 'checkout/' ) );
    wp_redirect( esc_url( home_url( '/' ) . 'create-account/' ) );
    exit;
}

/**
 * On checkout submit (buy membership)
 *
 * 1. User buy a Gift for somebody (friend in POOL or random user from POOL)
 * - get receiver data
 * - update user gift history
 * - add purchaser to pool (status = 0)
 * - update receiver in pool - remove from pool (set status = 1)
 *
 * 2. User has a coupon (Got a Gift) and buy a membership for himself using the coupon
 * remove the user from POOL?
 *
 * @global type $wpdb
 * @global type $woocommerce
 * @global type $current_user
 * @param type $order_id
 */
function ggGiveGift( $order_id ) {

    error_log( 'Call ggGiveGift' );

    global $wpdb;
    global $woocommerce;
    global $current_user;
    get_currentuserinfo();
    $purchaserEmail  = $current_user->user_email;
    $purchaserUserId = $current_user->ID;

    error_log( 'ggGiveGift: order id:' . var_export($order_id, 1) );

    $order = new WC_Order( $order_id );

    if ( $order ) {
        if ( in_array( $order->status, array( 'failed' ) ) ) {
            // error
            error_log( 'ggGiveGift: order status is failed for:' . var_export($purchaserEmail, 1) );
        } else {
            // Check which product id in the order
            $isGift = false;
            $isMembership = false;
            $orderItems = $order->get_items();
            if ( sizeof( $orderItems ) ) {
                foreach ($orderItems as $item) {
                    error_log('order product_id:' . var_export($item['product_id'], 1) . ' item:' . var_export($item, 1) );

                    if ( !empty($item['product_id']) && is_array($item['product_id']) && isset($item['product_id'][0]) ) {
                        $productId = $item['product_id'][0];
                    } else {
                        $productId = $item['product_id'];
                    }

                    if ($productId == MEMBERSHIP_PRODUCT_ID) {
                        $isMembership = true;
                    }
                    if ($productId == MEMBERSHIP_GIFT_PRODUCT_ID) {
                        $isGift = true;
                    }
                }
            }

            $created = time();
            // 1. User has bought a Gift for somebody
            if ( $isGift && !$isMembership ) {
                // get receiver data from database
                $receiver = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT user_receiver, user_receiver_email, user_receiver_firstname, user_receiver_message FROM {$wpdb->prefix}gift_history WHERE user_purchaser = %d AND status = 0 ORDER BY id DESC LIMIT 1",
                        $purchaserUserId
                    ), ARRAY_A
                );

                error_log('user has bought a gift for receiver:' . var_export($receiver, 1) );

                $receiverFirstname = isset($receiver['user_receiver_firstname']) ? $receiver['user_receiver_firstname'] : null;
                $receiverUserId    = isset($receiver['user_receiver']) ? $receiver['user_receiver'] : null;
                $receiverEmail     = isset($receiver['user_receiver_email']) ? $receiver['user_receiver_email'] : null;
                $receiverMessage   = isset($receiver['user_receiver_message']) ? $receiver['user_receiver_message'] : null;
                // if email is empty then get receiver email by user id
                if ( empty( $receiverEmail ) ) {
                    $receiver = get_userdata($receiverUserId);
                    $receiverEmail = $receiver->email;
                }

                // update gift history
                $wpdb->update(
                    $wpdb->prefix . 'gift_history',
                    array(
                        'order_id' => $order->id,
                        'status'   => 1,
                        'updated'  => $created
                    ),
                    array(
                        'user_purchaser' => $purchaserUserId,
                        'user_receiver'  => $receiverUserId,
                        'status'         => 0,
                    ),
                    array(
                        '%d',
                        '%d',
                        '%d'
                    ),
                    array(
                        '%d',
                        '%d',
                        '%d'
                    )
                );

                $gift_key = null;
                // check if purchaser in pool
                $row = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT ID, status, gift_key FROM {$wpdb->prefix}users_pool WHERE ID = %d",
                        $purchaserUserId
                    )
                );
                // add purchaser to pool (only once)
                if (!$row) {
                    // generate a key for invite page
                    $gift_key = wp_generate_password(20, false);

                    $wpdb->insert(
                        $wpdb->prefix . 'users_pool',
                        array('ID' => $purchaserUserId, 'status' => 0, 'gift_key' => $gift_key, 'created' => $created),
                        array('%d', '%d', '%s', '%d')
                    );
                } else {
                    $gift_key = $row->gift_key;
                }

                // if $receiverUserId exists in WP the check if receiver in POOL
                if ( $receiverUserId ) {
                    $row = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT ID, status FROM {$wpdb->prefix}users_pool WHERE ID = %d AND status = %d",
                            $receiverUserId,
                            0
                        )
                    );
                    // update receiver in pool if exists and status = 0
                    if ( $row ) {
                        $wpdb->update(
                            $wpdb->prefix . 'users_pool',
                            array(
                                'status'  => 1,
                                'updated' => $created
                            ),
                            array('ID' => $receiverUserId),
                            array(
                                '%d',
                                '%d'
                            ),
                            array('%d')
                        );
                    }
                }

                // 1. send an email to receiver
                // use standart email from smart-coupons plugin
                /*ob_start();
                //woocommerce_get_template('emails/email-header.php', array( 'email_heading' => $email_heading ));
                ?>
                <h2>Somebody has bought a Membership for you.</h2>

                <p><?php echo $receiverMessage ?></p>

                <a href="<?php echo home_url( '/' ) . 'create-account/' ?>">Activate now</a>

                <?php
                //woocommerce_get_template('emails/email-footer.php');

                $receiverMessage = ob_get_clean();
                $result1 = wp_mail($receiverEmail, __('Pureformance Membership'), $receiverMessage);
                error_log('send email to receiver:' . var_export($result1, 1) . ' email:' . $receiverEmail . ' message:' . $receiverMessage);
                */

                // 2. send an email to purchaser
                ob_start();
                //woocommerce_get_template('emails/email-header.php', array( 'email_heading' => $email_heading ));
                ?>

                <h2>You have bought a Membership for <?php echo $receiverFirstname ?></h2>

                <p>Somebody will help you soon!</p>

                <p>Share this link with your friends:</p>
                <p><?php echo home_url( '/' ) . 'give-gift/?key=' . $gift_key ?></p>

                <?php
                //woocommerce_get_template('emails/email-footer.php');

                $purchaserMessage = ob_get_clean();
                $result2 = wp_mail($purchaserEmail, __('Pureformance Membership'), $purchaserMessage);
                error_log('send email to purchaser:' . var_export($result2, 1) . ' email:' . $purchaserEmail . ' message:' . $purchaserMessage);

            } // if ($isGift && !$isMembership) {


            // 2. User has bought a Membership
            if ( !$isGift && $isMembership ) {
                // check if current user IN POOL
                $row = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT ID, status FROM {$wpdb->prefix}users_pool WHERE ID = %d AND status = %d",
                        $purchaserUserId,
                        0
                    )
                );
                // update current user in pool if exists and status = 0
                if ( $row ) {
                    $wpdb->update(
                        $wpdb->prefix . 'users_pool',
                        array(
                            'status'  => 1,
                            'updated' => $created
                        ),
                        array( 'ID' => $purchaserUserId ),
                        array(
                            '%d',
                            '%d'
                        ),
                        array( '%d' )
                    );
                }

                // 1. send an email to current user
                ob_start();

                //woocommerce_get_template('emails/email-header.php', array( 'email_heading' => $email_heading ));
                ?>
                <h2>You have bought a Membership.</h2>

                <a href="<?php echo home_url( '/' ) ?>">Go to Pureformance</a>

                <?php
                //woocommerce_get_template('emails/email-footer.php');

                $message = ob_get_clean();
                $result = wp_mail($purchaserEmail, __('Pureformance Membership'), $message);
                error_log('send email to receiver:' . var_export($result, 1) . ' email:' . $purchaserEmail . ' message:' . $message);
            }

        } // $order->status != 'failed'
    } // if ( $order )

}

function ggUserDataValidation( $data ) {
    global $wpdb;

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
    }

    // check user != current user
    $userId = email_exists($data['email']);
    if ($userId == get_current_user_id()) {
        $errors['email'] = __('You can not give a Gift to yourself');
        return $errors;
    }

    // add check is the user in the POOL?
    /*$userId = email_exists($data['email']);
    if ($userId) {

        // check user != current user
        if ($userId == get_current_user_id()) {
            $errors['email'] = __('You can not give a Gift to yourself');
            return $errors;
        }

        error_log( 'ggUserDataValidation: check POOL for user id:' . var_export($userId, 1) );

        // check if user in POOL, we can give a gift only for users in pool
        if ($wpdb && $wpdb->prefix) {
            $userPool = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT ID, status FROM {$wpdb->prefix}users_pool WHERE ID = %d",
                    $userId
                ),
                ARRAY_A
            );

            error_log( 'ggUserDataValidation: get from POOL:' . var_export($userPool, 1) );

            if ( isset($userPool['status']) ) {
                if ( $userPool['status'] == 1 ) {
                    // user already have a Gift
                    $errors['email'] = sprintf(__('User %s has already been gifted by someone else'), $data['firstname']);
                } else if ( $userPool['status'] == 0 ) {
                    // user in the POOL and without Gift - OK
                }
            }

        }

    } else {
        // user not found by email
        // $errors['email'] = sprintf(__('User %s not found by email. Select another user.'), $data['firstname']);
        error_log( 'ggUserDataValidation: User does not exist with email:' . var_export($data['email'], 1) );
    }*/

    if (empty($data['message'])) {
        $errors['message'] = __('Message is empty. Please add a message.');
    } elseif (mb_strlen($data['message']) < 10) {
        $errors['message'] = __('Too short message');
    } elseif (mb_strlen($data['message']) > 1000) {
        $errors['message'] = __('Too long message');
    }

    return $errors;
}

/**
 * On Give a Gift page Add a product with id: <MEMBERSHIP_GIFT_PRODUCT_ID> to user cart
 *
 * @global type $woocommerce
 * @param int $product_id
 */
function ggAddProductToCard( $product_id ) {
    error_log( 'ggAddProductToCard' );

    if ( !is_user_logged_in() ) {
        error_log( 'ggAddProductToCard: user is not logged in' );
        return;
    }

    if ( ! is_admin() ) {
        global $woocommerce;
        global $wpdb;
        $found = false;
        $couponCode = null;
        $productId = null;

        // simply check if user has a coupon then he need to apply it to Membership
        if ( isset( $_GET['coupon'] ) ) {
            $couponCode = trim( $_GET['coupon'] );
            $couponCode = preg_replace( "/[^a-zA-Z0-9_\s]/", '', $couponCode );
            // validate
            $coupon = new WC_Coupon($couponCode);

            error_log( 'ggAddProductToCard: coupon given, check is valid:' . var_export($couponCode, 1) );

            if ( !$coupon->is_valid() ) {
                return;
            }
            error_log( 'ggAddProductToCard: coupon is valid:' . var_export($couponCode, 1) );

            $productId = MEMBERSHIP_PRODUCT_ID; // Membership (monthly)
        } else {
            // check if user want to Give a Gift to somebody
            $purchaserUserId = get_current_user_id();
            $receiver = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT user_receiver, user_receiver_email, user_receiver_firstname, user_receiver_message FROM {$wpdb->prefix}gift_history WHERE user_purchaser = %d AND status = 0 ORDER BY updated DESC LIMIT 1",
                    $purchaserUserId
                )
            );
            error_log( 'ggAddProductToCard: without coupon, check receiver:' . var_export($receiver, 1) );
            if ( $receiver && ( $receiver->user_receiver || $receiver->user_receiver_email ) ) {
                $productId = MEMBERSHIP_GIFT_PRODUCT_ID; // Gift Membership (monthly)
            }
        }

        error_log( 'ggAddProductToCard: set productId:' . var_export($productId, 1) );
        if ( empty( $productId ) ) {
            return;
        }

        $cartItems = $woocommerce->cart->get_cart();
        error_log( 'ggAddProductToCard: productId:' . $productId . ' before check cart items:' . var_export($cartItems, 1) );

        if ( sizeof( $cartItems ) > 0 ) {
            foreach ( $cartItems as $cart_item_key => $values ) {
                $_product = $values['data'];
                if ( $_product->id == $productId ) {
                    $found = true;
                    error_log( 'ggAddProductToCard: productId:' . $productId . ' already in the cart:' . var_export($woocommerce->cart, 1) );
                }
            }
            // if product not found then add it
            if ( ! $found ) {
                $result = $woocommerce->cart->add_to_cart( $productId );
                error_log( 'ggAddProductToCard: productId:' . $productId . ' result:' . var_export($result, 1) );
            }
        } else {
            // if no products in cart then add it
            $result = $woocommerce->cart->add_to_cart( $productId );
            error_log( 'ggAddProductToCard: productId:' . $productId . ' result:' . var_export($result, 1) );
        }

        // apply coupon if exists
        if ( !empty( $couponCode ) ) {
            if ( $woocommerce->cart && !$woocommerce->cart->add_discount( sanitize_text_field( $couponCode )) ) {
                error_log( 'ggAddProductToCard: error add coupon:' . $couponCode . ' to cart' );
                $woocommerce->show_messages();
            } else {
                error_log( 'ggAddProductToCard: add coupon:' . $couponCode . ' to cart' );
            }

            $woocommerce->cart->calculate_totals();
        }

        if ( $productId == MEMBERSHIP_GIFT_PRODUCT_ID ) {
            wp_redirect( esc_url( home_url( '/' ) . 'checkout/' ) );
            exit;
        }
    }
}

/**
 * Check if current user in POOL (with status = 0) and has coupons
 * then go to checkout and add coupon
 * This is only for users which are in POOL and somebody gave them a Gift
 */
function ggGiftProceed() {
    global $woocommerce;
    global $wpdb;

    $isPool = false;
    $isGotAGift = false;
    $currentUserId = get_current_user_id();

    // check if user in POOL
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT ID, status, gift_key FROM {$wpdb->prefix}users_pool WHERE ID = %d",
            $currentUserId
        )
    );
    if ($row && $row->status == 0) {
        $isPool = true;
    }

    error_log( 'ggGiftProceed: isPool:' . var_export($isPool, 1) );

    if (!$isPool) {
        return;
    }

    // check coupons
    $args = array(
        'numberposts'     => -1,
        'meta_key'        => '_customer_user',
        'meta_value'	  => $currentUserId,
        'post_type'       => 'shop_order',
        'post_status'     => 'publish'
    );
    $orders = get_posts($args);

    $couponForMembership = '';
    if ($orders) {
        foreach ($orders as $or) {
            $order = new WC_Order();

            $order->populate( $or );
            $orderId = $order->id;
            //error_log( 'ggGiftProceed: order id: ' . var_export($orderId, 1) . 'order:' . var_export($order, 1) );
            // Get the coupon array
            $coupon_receiver_details = get_post_meta( $orderId, 'sc_coupon_receiver_details', true );

            //error_log( 'ggGiftProceed: coupon details:' . var_export($coupon_receiver_details, 1) );
            if ( is_array( $coupon_receiver_details ) && !empty( $coupon_receiver_details ) ) {
                foreach ($coupon_receiver_details as $coupon_receiver_detail) {
                    if ( isset($coupon_receiver_detail['code'], $coupon_receiver_detail['amount']) ) {

                        $couponForMembership = $coupon_receiver_detail['code'];

                        if ( $woocommerce->cart->has_discount( sanitize_text_field( $couponForMembership ) ) ) {
                            error_log( 'ggGiftProceed: has_discount:' . var_export($couponForMembership, 1) );
                            $woocommerce->cart->remove_coupons( sanitize_text_field( $couponForMembership ) );
                        }

                        error_log( 'ggGiftProceed: add_discount:' . var_export($couponForMembership, 1) );
                        $woocommerce->cart->add_discount( sanitize_text_field($couponForMembership) );

                        $woocommerce->cart->calculate_totals();
                        break;

                        // coupon found and appied
                        $isGotAGift = true;
                    }
                } // foreach
            }

        } // foreach
    }

    if ( $isGotAGift ) {
        // redirect to Give a Gift page
        wp_redirect( esc_url( home_url( '/' ) . 'checkout/' ) );
        exit;
    }

}

function ggContentFilter($content) {
    global $ggOutput;

    return $content . $ggOutput;
}

?>
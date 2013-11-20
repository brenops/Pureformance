<?php
/*
Plugin Name: Give Gift
Plugin URI:
Description: Give a Gift to somebody. Check First Name, Email, Message and if the recipient exists in Pool.
Version: 1.0
Author:
Author URI:
*/

$ggOutput = '';

add_action( 'plugins_loaded', 'ggInit' );

//add_action( 'givegift', 'ggGiveGift' );

add_action( 'woocommerce_thankyou', 'ggGiveGift', 10 );

add_action( 'addgifttocart', 'ggAddProductToCard' );

function ggInit() {

    if (isset($_POST['giveGift'])) {
        ggSaveGiftReceiver();
    }

    // add product to card
    //$product_id = 267; // Membership (monthly)
    //ggAddProductToCard($product_id);
}

function ggSaveGiftReceiver() {
    global $wpdb;
    global $ggOutput;

    $errors = array();

    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $email     = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message   = isset($_POST['message']) ? trim($_POST['message']) : '';

    // allow only a-z and 0-9
    $firstname = preg_replace("/[^a-zA-Z0-9_\s]/", '', $firstname);

    $data = array(
        'firstname' => $firstname,
        'email'     => $email,
        'message'   => $message
    );

    $errors = ggUserDataValidation($data);

    if ($errors) {
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
    // check for exists
    $receiver = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT user_receiver, user_receiver_firstname, user_receiver_message FROM {$wpdb->prefix}gift_history WHERE user_purchaser = %d AND user_receiver = %d AND status = 0 ORDER BY id DESC LIMIT 1",
            $purchaserUserId, $receiverUserId
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
                'user_purchaser' => $purchaserUserId,
                'user_receiver'  => $receiverUserId,
                'status'         => 0,
            ),
            array(
                '%s',
                '%s',
                '%d'
            ),
            array(
                '%d',
                '%d',
                '%d'
            )
        );
    } else {
        $wpdb->insert(
            $wpdb->prefix . 'gift_history',
            array('user_purchaser' => $purchaserUserId, 'user_receiver' => $receiverUserId, 'user_receiver_firstname' => $firstname, 'user_receiver_message' => $message, 'status' => 0, 'created' => $created),
            array('%d', '%d', '%s', '%s', '%d', '%d')
        );
    }

    // add product to card
    // $product_id = 267; // Membership (monthly)
    // @todo How to add details about receiver?
    // ggAddProductToCard( $product_id );

    // go to checkout
    wp_redirect( esc_url( home_url( '/' ) . 'checkout/' ) );
    exit;
}

function ggGiveGift($order_id) {

    error_log( 'Call ggGiveGift' );

    global $wpdb;
    global $woocommerce;
    global $current_user;
    get_currentuserinfo();
    $purchaserEmail  = $current_user->user_email;
    $purchaserUserId = $current_user->ID;

    $receiver = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT user_receiver, user_receiver_firstname, user_receiver_message FROM {$wpdb->prefix}gift_history WHERE user_purchaser = %d AND status = 0 ORDER BY id DESC LIMIT 1",
            $purchaserUserId
        ), ARRAY_A
    );

    error_log('user has bought a gift for receiver:' . var_export($receiver, 1) );

    error_log('order id:' . var_export($order_id, 1) );

    $order = new WC_Order( $order_id );

    if ( $order ) {
        if ( in_array( $order->status, array( 'failed' ) ) ) {
            // error
            error_log('order status is failed for:' . var_export($purchaserEmail, 1) );
        } else {
            $created = time();
            // get receiver data from session
            $receiverFirstname = isset($receiver['user_receiver_firstname']) ? $receiver['user_receiver_firstname'] : null;
            $receiverUserId    = isset($receiver['user_receiver']) ? $receiver['user_receiver'] : null;
            $receiverMessage   = isset($receiver['user_receiver_message']) ? $receiver['user_receiver_message'] : null;
            // get email by user id
            $receiver = get_userdata($receiverUserId);
            $receiverEmail = $receiver->email;

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
                $gift_key = wp_generate_password($length = 20, $include_standard_special_chars = false);

                $wpdb->insert(
                    $wpdb->prefix . 'users_pool',
                    array('ID' => $purchaserUserId, 'status' => 0, 'gift_key' => $gift_key, 'created' => $created),
                    array('%d', '%s', '%d')
                );
            } else {
                $gift_key = $row->gift_key;
            }

            // check if receiver in pool
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT ID, status FROM {$wpdb->prefix}users_pool WHERE ID = %d AND status = %d",
                    $receiverUserId,
                    0
                )
            );
            // update receiver in pool if exists and status = 0
            if ($row) {
                $wpdb->update(
                    $wpdb->prefix . 'users_pool',
                    array(
                        'status'  => 1,
                        'updated' => $created
                    ),
                    array( 'ID' => $receiverUserId ),
                    array(
                        '%d',
                        '%d'
                    ),
                    array( '%d' )
                );
            }

            // 1. send an email to receiver
            ob_start();
            ?>

            <h2>Somebody has bought a Membership for you.</h2>

            <?php echo $receiverMessage ?>

            <a href="<?php echo home_url( '/' ) . '/create-account/' ?>">Activate now</a>

            <?php
            $receiverMessage = ob_get_clean();
            //$receiverMessage = "Somebody has bought a Membership for you. {$receiverMessage} For activation go to: " . home_url( '/' ) . '/create-account/';
            $result1 = wp_mail($receiverEmail, __('Pureformance Membership'), $receiverMessage);
            error_log('send email to receiver:' . var_export($result1, 1) . ' email:' . $receiverEmail . ' message:' . $receiverMessage);



            // 2. send an email to purchaser
            ob_start();
            ?>

            <h2>You have bought a Membership for <?php echo $receiverFirstname ?></h2>

            Somebody will help you soon!

            Share this link with your friends:
            <?php echo home_url( '/' ) . '/give-gift/?key=' . $gift_key ?>

            <?php
            $purchaserMessage = ob_get_clean();
            //$purchaserMessage = "You have bought a Membership for user {$receiverFirstname}. Somebody will help you soon!";
            $result2 = wp_mail($purchaserEmail, __('Pureformance Membership'), $purchaserMessage);
            error_log('send email to purchaser:' . var_export($result2, 1) . ' email:' . $purchaserEmail . ' message:' . $purchaserMessage);

            if ($result1 && $result2) {
                //
            }

        } // $order->status != 'failed'
    } // if ( $order )

}

function ggUserDataValidation($data) {
    global $wpdb;

    $errors = array();

    if (empty($data['firstname'])) {
        $errors['firstname'] = 'First Name is empty'; //__('Username is empty');
    } elseif (mb_strlen($data['firstname']) > 60) {
        $errors['firstname'] = __('First Name can not be longer than 60 characters');
    }

    if (empty($data['email'])) {
        $errors['email'] = __('Email is empty');
    } elseif (mb_strlen($data['email']) > 100) {
        $errors['email'] = __('Email can not be longer than 100 characters');
    } else if (!is_email($data['email'])) {
        $errors['email'] = __('Provided incorrect Email address');
    }

    // add check is the user in the POOL?
    $userId = email_exists($data['email']);
    if ($userId) {

        error_log( 'ggUserDataValidation: check POOL for user id:' . var_export($userId, 1) );

        // check if user in pool, we can give a gift only for users in pool
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
                    // user in the POOL and without Gift
                }
            }

        }

    } else {
        // user not found, use another user
        $errors['email'] = sprintf(__('User %s not found by email. Select another user.'), $data['firstname']);
        error_log( 'ggUserDataValidation: User not found:' . var_export($userId, 1) );
    }

    if (empty($data['message'])) {
        $errors['message'] = __('Password is empty');
    } elseif (mb_strlen($data['message']) < 10) {
        $errors['message'] = __('Too short message');
    } elseif (mb_strlen($data['message']) > 1000) {
        $errors['message'] = __('Too long message');
    }

    return $errors;
}

function ggAddProductToCard( $product_id ) {

    if ( ! is_admin() ) {
        global $woocommerce;
        $found = false;
        $product_id = 267; // Membership (monthly)

        error_log( 'ggAddProductToCard: cart:' . var_export($woocommerce->cart, 1) );

        if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
            foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
                $_product = $values['data'];
                if ( $_product->id == $product_id ) {
                    $found = true;
                }
            }
            // if product not found, add it
            if ( ! $found ) {
                $woocommerce->cart->add_to_cart( $product_id );
            }
        } else {
            // if no products in cart, add it
            $woocommerce->cart->add_to_cart( $product_id );
        }
    }
}

function ggContentFilter($content) {
    global $ggOutput;

    return $content . $ggOutput;
}

?>
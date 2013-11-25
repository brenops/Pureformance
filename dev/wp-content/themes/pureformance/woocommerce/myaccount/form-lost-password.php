<?php
/**
 * Lost password form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce, $post;

?>

<?php $woocommerce->show_messages(); ?>

<form action="<?php echo esc_url( get_permalink($post->ID) ); ?>" method="post" class="lost_reset_password">

	<?php	if( 'lost_password' == $args['form'] ) : ?>
	<h2>Forgot your password? </h2>
    <p><?php echo apply_filters( 'woocommerce_lost_password_message', __( 'Please enter your email address. You will receive a link to create a new password via email.', 'woocommerce' ) ); ?></p>

    <p class="form-row form-row-first"><label for="user_login"></label> <input class="input-text" type="text" name="user_login" id="user_login" placeholder="<?php _e( 'Email', 'woocommerce' ); ?>" /></p>

	<?php else : ?>

    <h2><?php echo apply_filters( 'woocommerce_reset_password_message', __( 'Enter a new password below', 'woocommerce') ); ?></h2>

    <p class="form-row form-row-first">
        <label for="password_1"></label>
        <input type="password" class="input-text" name="password_1" id="password_1" placeholder="<?php _e( 'New password', 'woocommerce' ); ?>" />
    </p>
    <p class="form-row form-row-last">
        <label for="password_2"></label>
        <input type="password" class="input-text" name="password_2" id="password_2" placeholder="<?php _e( 'Re-enter new password', 'woocommerce' ); ?>" />
    </p>

    <input type="hidden" name="reset_key" value="<?php echo isset( $args['key'] ) ? $args['key'] : ''; ?>" />
    <input type="hidden" name="reset_login" value="<?php echo isset( $args['login'] ) ? $args['login'] : ''; ?>" />
	<?php endif; ?>

    <div class="clear"></div>

    <p class="form-row"><input type="submit" class="btn1" name="reset" value="<?php echo 'lost_password' == $args['form'] ? __( 'Reset Password', 'woocommerce' ) : __( 'Save', 'woocommerce' ); ?>" /></p>
	<?php $woocommerce->nonce_field( $args['form'] ); ?>

</form>
<?php
/**
 * My Account page
 *
 * @author
 * @package
 * @version
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;
global $wpdb;

// get user from pool by ID
$userId = get_current_user_id();
$row = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT ID, status, gift_key FROM {$wpdb->prefix}users_pool WHERE ID = %d",
        $userId
    )
);

$isPool = null; // user who did not give a gift yet (empty or new user)


$giftedCount     = 0;
$gifterFirstname = '';
$purchaserFirstname = '';
$giftKey         = '';

if ( !empty($row) ) {
    if ( $row->status == 0 ) {
        // user in POOL (C2) - He gave a Gift
        $isPool = true;
        $giftKey = $row->gift_key; // key for share your page
        $gifter = get_userdata( $userId );
        $gifterFirstname = !empty($gifter->display_name) ? $gifter->display_name : $gifter->user_login;
        // how many users were gifted by current user
        $giftedCount = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gift_history WHERE user_purchaser = {$userId} AND status = 1 AND order_id IS NOT NULL" );
    }
    if ( $row->status == 1 ) {
        // user got a Gift from somebody (D2) - somebody gave him a Gift
        $isPool = false;
        // get last purchaser
        $userIdPurchaser = $wpdb->get_var( "SELECT user_purchaser FROM {$wpdb->prefix}gift_history WHERE user_receiver = {$userId} AND status = 1 AND order_id IS NOT NULL ORDER BY updated DESC LIMIT 1" );
        if ( $userIdPurchaser ) {
            $purchaser = get_userdata( $userIdPurchaser );
            $purchaserFirstname = !empty($purchaser->display_name) ? $purchaser->display_name : $purchaser->user_login;
            // get his giftKey
            $giftKey = $wpdb->get_var( "SELECT gift_key FROM {$wpdb->prefix}users_pool WHERE ID = {$userIdPurchaser}" );
        }
    }
}


$woocommerce->show_messages(); ?>
<script>
$(document).ready(function() { 
	$('ul.tabs li a').click(function(){
		$('ul.tabs li').removeClass('active');
		$(this).parent().addClass('active');
		var currentTab = $(this).attr('href');
		$('.tab-content').hide();
		$(currentTab).show();
		return false;
	}); 
}); 
</script>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=650155288367842";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<p class="myaccount_user">
	<?php
	printf(
		__( 'Hello, <strong>%s</strong>. From your account dashboard you can send direct emails to friends, shout out to
Facebook and Twitter, see how many gifts you have given, view recent orders, manage your billing and
shipping information, ask for help, and <a href="%s">change your password</a>.', 'woocommerce' ),
		$current_user->display_name,
		get_permalink( woocommerce_get_page_id( 'change_password' ) )
	);
	?>
</p>

<ul class="tabs">
	<li class="active"><a href="#gifting">Gifting</a></li>
	<li><a href="#history">Order History / Billing Info</a></li>
</ul>
<div id="gifting" class="tab-content copy">
	<p class="gifted">You have gifted <strong><?php echo $giftedCount ?></strong> people so far. <?php echo $giftedCount > 0 ? 'Great work!' : '' ?></p>
	
	<?php if ($isPool == true) : /* User in the POOL - he needs to be promoted by himself */ ?>
	
	    <h2>Need help getting out of the pool?</h2>
	    <p>Our team at PF is here to help you and your friends gain access as quickly as
possible. Simply shout out on
Facebook
and
Twitter
for all of your friends to hear
about how great your friend was for giving you the Gift, and ask for their help in
returning the favor to get them into PF, too!</p>

        <div style="float:left; margin:5px 20px 50px 0">
            <div class="fb-share-button" data-href="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" data-width="300" data-type="box_count"></div>
        </div>
        <div style="float:left; margin:5px 20px 50px 0;">
            <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" data-via="Pureformance" data-lang="en" data-text="Help to <?php echo isset($gifterFirstname) ? esc_attr( $gifterFirstname ) : '' ?>" data-count="vertical">Tweet</a>
        </div>
        <div style="float:left; ">
        	<h4>Or your can share your Personal Invite link</h4>
        	<form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">
        		<input type="text" style="width:480px;" class="input-text" name="link" value="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" />
        	</form>
        </div>

	    <div class="clear"></div>
	
	<?php elseif ($isPool == false) : /* User not in the POOL, but was there */ ?>
	
	
	    <p class="myaccount_user">You just been gifted into the pool by <strong><?php echo $purchaserFirstname ?></strong>. He was kind enough to let you in the pool.<br>
	    Help him out by sharing his URL so he can join you in Performance.com as soon as possible!</p>
	    </div>
	    <div class="clear"></div>
        
        <div style="float:left; margin:5px 20px 50px 0">
            <div class="fb-share-button" data-href="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" data-width="300" data-type="box_count"></div>
        </div>
        <div style="float:left; margin:5px 20px 50px 0;">
            <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" data-via="Pureformance" data-lang="en" data-text="Help to <?php echo isset($purchaserFirstname) ? esc_attr( $purchaserFirstname ) : '' ?>" data-count="vertical">Tweet</a>
        </div>
        
        <div style="float:left;">
        <form method="POST" id="create-account-form" action="<?php echo esc_url( home_url( '/' ) . 'create-account/' ); ?>">\
            <h4>Use this link to help <?php echo $purchaserFirstname ?> find someone into the site</h4>
            <input type="text" class="input-text" style="width:480px;" name="link" value="<?php echo home_url( '/' ) . 'friend-help/?key=' . $giftKey ?>" />
        </form>
        </div>
	    <div class="clear"></div>
	
	<?php endif; ?>
	
	    <h2>Tell Us Who To Give Pureformance To</h2>
	    <div class="copy">
	        <p class="myaccount_user">Know someone else that would benefit from the gift of opportunity with Pureformance?</p>
	
	        <form method="POST" id="give-gift-form" action="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ); ?>">
	        <div>
	            <input type="text" class="input-text" name="firstname" id="ca-firstname" value="" placeholder="First Name" />
	        </div>
	        <div>
	            <input type="text" class="input-text" name="email" id="ca-email" value="" placeholder="Email" />
	        </div>
	        <div>
	            <textarea class="input-text" rows="10" cols="30" name="message" placeholder="Message"></textarea>
	        </div>
	
	        <div>
	            <input type="submit" class="btn1" name="giveGift" value="<?php esc_attr_e( 'Give Gift Now', 'twentyeleven' ); ?>" />
	        </div>
	        </form>
	    </div>
	    <div class="clear"></div>
</div>
<div id="history" class="tab-content" style="display:none">
	<?php do_action( 'woocommerce_before_my_account' ); ?>
	
	<?php woocommerce_get_template( 'myaccount/my-downloads.php' ); ?>
	
	<?php woocommerce_get_template( 'myaccount/my-orders.php', array( 'order_count' => $order_count ) ); ?>
	
	<?php woocommerce_get_template( 'myaccount/my-address.php' ); ?>
</div>

<?php do_action( 'woocommerce_after_my_account' ); ?>
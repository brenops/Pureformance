<?php
	//set the is subscriber status
	//----------------------------taken from the subscriptions template my-subscriptions
	$_SESSION['is_subscriber'] = 0; //begin with false for the subscriber status
	$subscriptions = WC_Subscriptions_Manager::get_users_subscriptions();
	$user_id = get_current_user_id();

	$_SESSION['is_subscriber'] = 0;

	foreach ( $subscriptions as $subscription_key => $subscription_details ) {
		if ( $subscription_details['status'] == 'trash' ) {
			unset( $subscriptions[$subscription_key] );
                }
                //print_r($subscription_details);
		if ( $subscription_details['status'] == 'active' ) {
                    $_SESSION['is_subscriber'] = 1;
                }
        }
	// print_r($_SESSION);
	//----------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
    <link rel="icon" href="http://pureformance.com/dev/favicon.ico" type="image/x-icon">
    <title><?php
	/*
	 * Print the <title> tag based on what is being viewed.
	 */
	global $page, $paged;

	wp_title( '|', true, 'right' );

	// Add the blog name.
	bloginfo( 'name' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) ) {
		echo " | $site_description";
        }

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 ) {
		echo ' | ' . sprintf( __( 'Page %s', 'twentyten' ), max( $paged, $page ) );
        }
	?></title>
    <link rel="profile" href="http://gmpg.org/xfn/11" />
    <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
    <link href="<?php bloginfo( 'template_directory' ); ?>/js/fancybox/jquery.fancybox-1.3.4.css" rel="stylesheet" type="text/css">
    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
    <?php
	if ( is_singular() && get_option( 'thread_comments' ) ) {
            wp_enqueue_script( 'comment-reply' );
        }
	wp_head();
    ?>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script src="<?php bloginfo( 'template_directory' ); ?>/js/main.js" type="text/javascript"></script>
    <script src="<?php bloginfo( 'template_directory' ); ?>/js/contact.js" type="text/javascript"></script>
    <script src="<?php bloginfo( 'template_directory' ); ?>/js/ask-expert.js" type="text/javascript"></script>
    <script src="<?php bloginfo( 'template_directory' ); ?>/js/superfish.js" type="text/javascript"></script>
    <script src="<?php bloginfo( 'template_directory' ); ?>/js/fancybox/jquery.fancybox-1.3.4.js" type="text/javascript"></script>
    <script type="text/javascript">
	var is_subscriber = '<?php echo $_SESSION['is_subscriber'] ?>';
	var is_logged_in = '<?php echo is_user_logged_in() ?>';
	$(function(){
		if ( typeof(site_loc) != 'undefined') {
			if (site_loc == 'access_forum') {
				if (is_logged_in) {
					$('#login-form').remove();
					$('#access-forum-msg').html('Please purchase a subscription.');
					$('.woocommerce').hide();
					$('.product').show();
				} else { //user is already lgged in so remove the login form
					$('#access-forum-msg').html('Please log in or purchase a new subscription.')
				}
			}
		}
	});
    </script>
<?php if (is_home()) { ?>
    <script language="javascript">
    //document.onmousedown=disableclick;

    //THIS WAS SET BEFORE I EDITED BUT WAS CRASHING JS
    // Function disableclick(e)
    // {
      // if(event.button==2)
       // {
         // return false;
       // }
    // }

    function setPortal(portal, redirect) {
        $.ajax({
            type: "POST",
            url: "wp-content/ajax/set_portal_process.php",
            data: { portal:portal },
            async: false
        });
    }
    </script>
<?php } ?>
</head>
<body id="<?php echo the_slug(); ?>" <?php body_class(); ?> <?php if ( is_home() ) { echo 'oncontextmenu="return false"'; } ?>>
<?php if ( !is_home() ) { ?>
<div id="header">
	<div class="wrapper">
        <a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home" class="logo"><!-- <img src="<?php bloginfo( 'template_directory' ); ?>/images/small-logo.png" alt="<?php bloginfo( 'name' ); ?>" title="<?php bloginfo( 'name' ); ?>"> --><span>Pure</span>formance</a>
        <ul class="site-top-links">
            <?php //if (!is_user_logged_in() || $_SESSION['is_subscriber']==0) { ?><!-- <li class="joinus"><a href="#signup-form" id="signup">Join Us</a></li> --><?php //} ?>
            <?php if (the_slug() == "blog" || is_category() || $post && $post->post_type == "post") { ?>
                <!--li class="categories"><a href="">Categories</a-->
                    <?php wp_list_categories('orderby=name&exclude=1,4,5&title_li=<a href="">Categories</a>'); ?>
                <!--</li>-->
                <li class="tags"><a href="javascript:void(0);" class="icon">Tags</a>
                    <ul>
                    <?php
                    $tags = get_tags( array('order' => 'ASC') );
                    foreach ( (array) $tags as $tag ) {
                        echo '<li><a href="' . get_tag_link( $tag->term_id ) . '" title="' . sprintf( __( "View all posts in %s" ), $tag->name ) . '" >' . $tag->name . '</a></li>';
                    }
                    ?>
                    </ul>
                </li>
            <?php } ?>
            <li class="cart"><a href="<?php echo home_url( '/' ); ?>cart/" class="icon">View Cart</a></li>

            <li class="signin"><a href="<?php echo home_url( '/' ) ?>my-account" class="icon"><?php echo (is_user_logged_in() ? 'My Account' : 'Sign in') ?></a>
            	<?php if ( !is_user_logged_in() ) { ?>
            	<ul class="popup">
                    <h2>My Account</h2>
                    <!-- <div class="logged">
                    	<a href="<?php echo home_url( '/' ); ?>my-account/">My Orders</a>
                    	<a href="<?php echo wp_logout_url(home_url( '/' )); ?>">Logout</a>
                    </div> -->
                    <form method="post" class="">
                            <input type="text" class="input-text" name="username" id="username" placeholder="Username or Email" />
                            <input class="input-text" type="password" name="password" id="password" placeholder="Password" />

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
                    </form>
                </ul>
                <?php } ?>
            </li>
            <?php if ( is_user_logged_in() ) { ?><li class="signout"><a href="<?php echo wp_logout_url(home_url( '/' )); ?>" class="icon">Sign Out</a></li><?php } ?>
            <?php if ( $_SESSION['is_subscriber'] == 0 ) { ?><li class="joinus"><a href="<?php echo home_url( '/' ) ?>membership/">Join Us</a></li><?php } ?>
        </ul>
    </div>
</div>
<?php } ?>
<a href="#members-only" id="members-only-trigger"></a>
<div style="display:none">
	<div id="members-only" class="ask-expert-popup">
		<h2>Please log in to access this content.</h2>
		<form method="post" class="">
			<input type="text" class="input-text" name="username" id="username" placeholder="Username or Email" />
			<input class="input-text" type="password" name="password" id="password" placeholder="Password" />
			<div class="form-row">
				<?php global $woocommerce; ?>
				<?php $woocommerce->show_messages(); ?>
				<?php $woocommerce->nonce_field('login', 'login') ?>
				<input type="submit" class="button" name="login" value="Go Pure" />
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
                            <p>Not yet a member?<br>We offer monthly and annual membership packages!</p>
                            <center><a href="<?php echo home_url( '/' ); ?>access-forum/" class="btn1" style="display:inline-block"><span>Join Now</span></a></center>
			</div>
		</form>
	</div>
</div>
<div id="side">
    <div id="hideside"></div>
    <div class="inner">
    <a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home" class="logo"><img src="<?php bloginfo( 'template_directory' ); ?>/images/logo.png" alt="<?php bloginfo( 'name' ); ?>" title="<?php bloginfo( 'name' ); ?>"></a>
    <h2 class="tagline">
            <span>Inspiring Athletes,</span>
            <span>Game-Changers, and </span>
            <span>Lifestyle Performance </span>
    </h2>
    <ul class="menu" id="menu-side-menu">
            <li><a href="<?php echo home_url( '/' ); ?>strategies/">Strategies</a></li>
            <li><a href="<?php echo home_url( '/' ); ?>blog/">Community & Features</a></li>
            <li><a href="<?php echo home_url( '/' ); ?>shop/">Products</a></li>
            <?php if (is_home()) { ?><!-- <li><a href="javascript:void(0)">Login/Sign Up</a></li> --><?php } ?>
            <li><a href="<?php echo home_url( '/' ); ?>about-us/">About Us</a></li>
    </ul>
    <?php if ($_SESSION['is_subscriber'] == 0) { ?>
    <div class="sign-up">
        <a href="<?php echo home_url( '/' ); ?>membership/">
            <h2>We Want You To Succeed!<span>Join Now</span></h2>
        </a>
        <div class="clear"></div>
    </div>
    <?php } ?>
    </div>
</div>
<div id="showside"></div>

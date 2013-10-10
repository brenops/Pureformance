<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
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
	if ( $site_description && ( is_home() || is_front_page() ) )
		echo " | $site_description";

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . sprintf( __( 'Page %s', 'twentyten' ), max( $paged, $page ) );

	?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<!--link href="http://cdn-images.mailchimp.com/embedcode/slim-081711.css" rel="stylesheet" type="text/css">
<style type="text/css">
	#mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }
</style-->
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<?php
	if ( is_singular() && get_option( 'thread_comments' ) )
		wp_enqueue_script( 'comment-reply' );

	wp_head();
?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js" type="text/javascript"></script>
<script src="<?php bloginfo( 'template_directory' ); ?>/js/main.js" type="text/javascript"></script>
<script src="<?php bloginfo( 'template_directory' ); ?>/js/contact.js" type="text/javascript"></script>
<script src="<?php bloginfo( 'template_directory' ); ?>/js/superfish.js" type="text/javascript"></script>
<?php if(is_home()) { ?>

    <script language="javascript">
    document.onmousedown=disableclick;
    Function disableclick(e)
    {
      if(event.button==2)
       {
         return false;    
       }
    }
    </script>

<?php } ?>
</head>

<body id="<?php echo the_slug();?>" <?php body_class(); ?> <?php if(is_home()) { echo 'oncontextmenu="return false"'; } ?>>
<?php if(!is_home()) { ?>
<div id="header">
	<div class="wrapper">
        <a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home" class="logo"><img src="<?php bloginfo( 'template_directory' ); ?>/images/small-logo.png" alt="<?php bloginfo( 'name' ); ?>" title="<?php bloginfo( 'name' ); ?>"></a>
        <ul class="site-top-links">
        	<?php if(the_slug() == "blog" || is_single() || is_category()) { ?>
            <!--li class="categories"><a href="">Categories</a-->
            	<?php wp_list_categories('orderby=name&exclude=1,4,5&title_li=<a href="">Categories</a>'); ?> 
            </li>
            <li class="tags"><a href="javascript:void(0);">Tags</a>
            <ul>
            <?php
            $tags = get_tags( array('name__like' => "ç", 'order' => 'ASC') );
            foreach ( (array) $tags as $tag ) {
            echo '<li><a href="' . get_tag_link( $tag->term_id ) . '" title="' . sprintf( __( "View all posts in %s" ), $tag->name ) . '" ' . '>' . $tag->name.'</a></li>';
            }
            ?>
            </ul>
            </li>
            <?php } ?>
            <li class="cart"><a href="">View Cart</a></li>
            <li class="signin"><a href="">Sign in</a>
            	<ul class="popup">
                	<form>
                    	<h2>My Account</h2>
                        <input type="text" name="email" placeholder="Email">
                        <input type="password" name="password" placegolder="Password">
                        <a href="" class="btn2"><span>Sign In</span></a>
                    </form>
                </ul>
            </li>
        </ul>
    </div>
</div>
<?php } ?>
<div id="side">
	<div id="hideside"></div>
	<div class="inner">
    	<a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home" class="logo"><img src="<?php bloginfo( 'template_directory' ); ?>/images/logo.png" alt="<?php bloginfo( 'name' ); ?>" title="<?php bloginfo( 'name' ); ?>"></a>
        <h2 class="tagline">
        	<span>Inspiring Athletes,</span>
			<span>Game-Changers, and </span>
			<span>Lifestyle Performance </span>
		</h2>
        <?php //wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>
        <ul class="menu" id="menu-side-menu">
        	<li><a href="<?php echo home_url( '/' ); ?>strategies/">Strategies<p>Lorem ipsum dolor sit amet, consectetur libero enim adipiscing elit.</p></a></li>
        	<li><a href="<?php echo home_url( '/' ); ?>blog/">Community<p>Lorem ipsum dolor sit amet, consectetur libero enim adipiscing elit.</p></a></li>
        	<li><a href="<?php echo home_url( '/' ); ?>our-store/">Products<p>Lorem ipsum dolor sit amet, consectetur libero enim adipiscing elit.</p></a></li>
        	<?php if(is_home()) { ?><li><a href="javascript:void(0)">Login/Sign Up<p>Lorem ipsum dolor sit amet, consectetur libero enim adipiscing elit.</p></a></li><?php } ?>
        	<li><a href="<?php echo home_url( '/' ); ?>about-us/">About Us<p>Lorem ipsum dolor sit amet, consectetur libero enim adipiscing elit.</p></a></li>
	</ul>
        <div class="sign-up">
        	<h2>Sign up to <span>our newsletter</span></h2>
            <input type="text" name="your_name" placeholder="Your Name">
            <input type="text" name="your_email" placeholder="Your Email">
            <a href="" class="btn1"><span>Sign me up</span></a>
            <div class="clear"></div>
        </div>
        <div class="footer">
        	<ul>
            	<li class="facebook"><a href="">Facebook</a></li>
                <li class="twitter"><a href="">Twitter</a></li>
                <li class="google"><a href="">Google Plus</a></li>
            </ul>
            <div class="clear"></div>
            <div class="links">
            	<a href="">Terms</a> <a href="<?php echo home_url( '/' ); ?>contact-us/">Contact Us</a>
            </div>
            <div class="copyright">
            	Copyright &copy; <?=date('Y')?> - pureformance
            	<a href="http://www.f5interactive.com" target="_blank">Created by F5 Interactive</a>
            </div>
        </div>
    </div>
</div>
<div id="showside"></div>
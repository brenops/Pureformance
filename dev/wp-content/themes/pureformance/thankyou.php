<?php
// Template Name: Thank you

if ( !is_user_logged_in() ) {
    header( 'Location: ' . home_url( '/' ) . '/' );
    exit;
}

get_header();

?>
<script>
$(document).ready(function(){

});
</script>
<div id="content" class="wrapper portal">

    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="header">
            <?php
                $headline = get_post_meta($post->ID, 'headline', true);
                if ($headline == '') { $headline = '<span>PURE</span> Simple, Powerful, Effective, Trusted'; }
            ?>
            <h1 class="entry-title">Thank You For Being You!<?php //echo $headline?></h1>
        </div>

        <div class="entry-content">
        <div class="copy">
            <h2>Thank You For Giving the Gift of Opportunity</h2>
            <?php the_content(); ?>
            Just gave <?php echo 'Jonathan'; ?> a chance to better themselves and become a<br />
            game changer!<br />
            An email has been sent for him to create an account and begin enjoying his first month of content,<br />
            courtesy of your kindness.
        </div>
        <div class="clear"></div>
        <div class="copy">
            <h2>Wow, that felt good ...</h2>
            <div>
                <form method="POST" id="give-gift-form" action="<?php echo esc_url( home_url( '/' ) . 'give-gift/' ); ?>">
                <div>
                    <input type="submit" class="btn1" name="giveGift" value="<?php esc_attr_e( 'Give Another Gift', 'twentyeleven' ); ?>" />
                </div>
                </form>
            </div>
        </div>

        <div class="clear"></div>
        </div><!-- .entry-content -->

    </div><!-- #post-## -->

    <ul class="nav-icons">
        <li>
            <img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-write.png" />
            <span>View Account</span>
            <a href="<?php echo home_url( '/' ); ?>my-account/" class="over">
                <h2>View Account</h2>
                <p>Tell Your Story.</p>
            </a>
        </li>
        <li>
            <img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-strategies.png" />
            <span>View Strategies</span>
            <a href="<?php echo home_url( '/' ); ?>strategies/" class="over">
                <h2>Strategies</h2>
                <p>Take me back to the eBook and Strategies page for deeper looks into Pureformance concepts and applications.</p>
            </a>
        </li>
        <li class="last">
            <img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-shopping.png" />
            <span>Shop Products</span>
            <a href="<?php echo home_url( '/' ); ?>shop/" class="over">
                <h2>Shop Products</h2>
                <p>Take a look at our innovative products purely designed to help you succeed. We don’t compromise, we strive to make the benefits to you our highest priority!</p>
            </a>
        </li>
    </ul>
</div><!-- #container -->

<?php get_footer(); ?>

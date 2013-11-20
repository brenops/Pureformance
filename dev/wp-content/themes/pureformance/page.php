<?php get_header(); ?>

<div id="content" class="wrapper">

        <?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

        <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="header">
                <?php
                    $headline = get_post_meta($post->ID, 'headline', true);
                    if($headline == '') { $headline = '<span>PURE</span> Simple, Powerful, Effective, Trusted'; }
                ?>
                <h1 class="entry-title"><?php echo $headline; ?></h1>
            </div>
            <?php if (is_cart() || is_checkout()) { ?>
            <ul class="progress-bar">
                    <li>STEP 1<span>Browse Products</span></li>
                    <li>STEP 2<span>Choose the best solution</span></li>
                    <li class="current">STEP 3<span>Easy checkout process</span></li>
                    <li class="last">STEP 4<span>Enhance your life</span></li>
            </ul>
            <?php } ?>
            <div class="entry-content">
                <?php
                    $side_image = get_post_meta($post->ID, 'side_image', true);
                    if (!empty($side_image)) {
                        $side_image_url = wp_get_attachment_image_src($side_image, 'full');
                ?>
                <div class="side_image">
                        <img src="<?php echo $side_image_url[0] ?>">
                </div>
                <?php
                    }
                ?>
                <?php the_content(); ?>
                <?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
                <div class="clear"></div>
            </div><!-- .entry-content -->
        </div><!-- #post-## -->

        <?php endwhile; // end of the loop. ?>

<?php include (TEMPLATEPATH . '/bottom-boxes.php'); ?>
</div><!-- #container -->

<?php get_footer(); ?>

<?php
if ( !is_user_logged_in() ) {
    header( 'Location: ' . home_url( '/' ) . 'create-account/' );
    exit;
} else {
    header( 'Location: ' . home_url( '/' ) . 'give-gift/' );
    exit;
}

// Template Name: Join Us
get_header(); ?>

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

					<div class="entry-content">
						<?php $side_image = get_post_meta($post->ID, 'side_image', true);
							if(!empty($side_image)) {
							$side_image_url = wp_get_attachment_image_src($side_image, 'full');
						?>
						<div class="side_image">
							<img src="<?php echo $side_image_url[0]?>">
						</div>
						<?php } ?>
                        <?php echo get_post_meta($post->ID, 'intro_content', true); ?>
                        <div class="clear"></div>
						<div class="plans monthly">
							<div class="price">$5/mo</div>
							<ul>
								<li>Monthly Membership</li>
							</ul>
							<a href="<?php echo home_url( '/' ); ?>membership/?add-to-cart=267">Join Now</a>
						</div>
						<div class="plans annual1">
							<div class="price">$85</div>
							<ul>
								<li>Annual Team Membership</li>
								<li>Pure Blood Builder</li>
								<li>Free Shipping</li>
							</ul>
							<div class="save">Save $20</div>
							<a href="<?php echo home_url( '/' ); ?>membership/?add-to-cart=269">Join Now</a>
						</div>
						<div class="plans annual2">
							<div class="price">$175</div>
							<ul>
								<li>Annual Team Membership</li>
								<li>Pure Blood Builder</li>
								<li>Free Shipping</li>
								<li>Pure Journal</li>
								<li>Pure Supplement Tin</li>
								<li>eGuidebooks II and III</li>
								<!--li>Super Intent Poster</li-->
								<!--li>Pure Rest and Recover</li-->
							</ul>
							<div class="save">Save $50</div>
							<a href="<?php echo home_url( '/' ); ?>membership/?add-to-cart=557">Join Now</a>
						</div>
                        <div class="clear"></div>
                        <?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>

					</div><!-- .entry-content -->
				</div><!-- #post-## -->

			<?php endwhile; // end of the loop. ?>

            <?php include (TEMPLATEPATH . '/bottom-boxes.php'); ?>
		</div><!-- #container -->

<?php get_footer(); ?>

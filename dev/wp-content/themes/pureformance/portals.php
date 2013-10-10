<?php 
// Template Name: Portal
get_header(); 
?>
<script>
$(document).ready(function(){
	$('.portal-options li a').hover(function(){
		$(this).children('.black').fadeOut(400);
		$(this).children('.color').fadeIn(400);
	}, function() {
		$(this).children('.black').fadeIn(400);
		$(this).children('.color').fadeOut(400);
	});
});
</script>
		<div id="content" class="wrapper portal">

			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); 
			
			if($post->post_name == 'high-performance') {
				$portal = "1";
			} else if($post->post_name == 'game-changer') {
				$portal = "2";
			} else if($post->post_name == 'lifestyle') {
				$portal = "3";
			}
			?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                	<div class="header">
                    	<?php 
							$headline = get_post_meta($post->ID, 'headline', true);
							if($headline == '') { $headline = '<span>PURE</span> Simple, Powerful, Effective, Trusted'; }
						?>
						<h1 class="entry-title"><?=$headline?></h1>
                    </div>

					<div class="entry-content" style="background-color:<?php if($portal==1) { echo '#dbb5b7'; } else if($portal==2) { echo '#5c8aac'; } else { echo '#f0e98e'; }?>">
                    	<img src="<?php bloginfo( 'template_directory' ); ?>/images/portal<?=$portal?>-medium-color.jpg" class="portal-image" />
                        <div class="portal-options">
                            <ul>
                            	<li<?php if($portal == 1) { echo ' style="display:none;"'; } ?>><a href="<?php echo home_url( '/' ); ?>portals/high-performance/"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal1-thumb.jpg" class="black"/><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal1-thumb-color.jpg" class="color"/></li>
                                <li<?php if($portal == 2) { echo ' style="display:none;"'; } ?>><a href="<?php echo home_url( '/' ); ?>portals/game-changer/"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal2-thumb.jpg" class="black"/><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal2-thumb-color.jpg" class="color"/></a></li>
                                <li<?php if($portal == 3) { echo ' style="display:none;"'; } ?>><a href="<?php echo home_url( '/' ); ?>portals/lifestyle/"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal3-thumb.jpg" class="black"/><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal3-thumb-color.jpg" class="color"/></a></li>
                            </ul>
                        	<h3>CHOOSE ANOTHER PORTAL: </h3>
                        </div>
                        <div class="copy">
							<h2><?php the_title(); ?></h2>
							<?php the_content(); ?>
							<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
                            <!--a href="<?php echo home_url( '/' ); ?>our-store/" class="btn1"><span>Start Shopping Now</span></a-->
                        </div>
                        <div class="clear"></div>
					</div><!-- .entry-content -->
                    <?php include (TEMPLATEPATH . '/bottom-boxes.php'); ?>
				</div><!-- #post-## -->

			<?php endwhile; // end of the loop. ?>
            
		</div><!-- #container -->
        
<?php get_footer(); ?>

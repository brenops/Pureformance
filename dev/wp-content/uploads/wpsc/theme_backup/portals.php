<?php 
// Template Name: Portal
get_header(); 
echo $post->post_name;
?>

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
							if($headline == '') { $headline = get_the_title(); }
						?>
						<h1 class="entry-title"><span>PURE</span> Simple, Powerful, Effective, Trusted</h1>
                    </div>

					<div class="entry-content">
                    	<img src="<?php bloginfo( 'template_directory' ); ?>/images/portal<?=$portal?>-medium.jpg" class="portal-image" />
                        <div class="portal-options">
                            <ul>
                            	<li<?php if($portal == 1) { echo ' style="display:none;"'; } ?>><a href="<?php echo home_url( '/' ); ?>portals/high-performance/"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal1-thumb.jpg"/></li>
                                <li<?php if($portal == 2) { echo ' style="display:none;"'; } ?>><a href="<?php echo home_url( '/' ); ?>portals/game-changer/"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal2-thumb.jpg"/></a></li>
                                <li<?php if($portal == 3) { echo ' style="display:none;"'; } ?>><a href="<?php echo home_url( '/' ); ?>portals/lifestyle/"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal3-thumb.jpg"/></a></li>
                            </ul>
                        	<h3>CHOOSE ANOTHER PORTAL: </h3>
                        </div>
                        <div class="copy">
							<?php the_content(); ?>
							<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
                            <!--a href="<?php echo home_url( '/' ); ?>our-store/" class="btn1"><span>Start Shopping Now</span></a-->
                        </div>
                        <div class="clear"></div>
					</div><!-- .entry-content -->
                    <ul class="nav-icons">
                    	<li>
                        	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-shopping.png" />
                            <span>Shop Products</span>
                        	<a href="<?php echo home_url( '/' ); ?>our-store/" class="over">
                            	<h2>Shop Products</h2>
                            	<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ac nibh vitae urna convallis tristique. Sed molestie hendrerit est, facilisis luctus odio tincidunt sed. Nam semper mauris nunc.</p>
                            </a>
                        </li>
                        <li>
                        	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-write.png" />
                            <span>Our Blog</span>
                            <a href="<?php echo home_url( '/' ); ?>blog/" class="over">
                            	<h2>Our Blog</h2>
                            	<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ac nibh vitae urna convallis tristique. Sed molestie hendrerit est, facilisis luctus odio tincidunt sed. Nam semper mauris nunc. Cras purus ipsum, pretium eu tincidunt vel, imperdiet vitae lorem.</p>
                            </a>
                        </li>
                        <li class="last">
                        	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-strategies.png" />
                            <span>View Strategies</span>
                            <a href="<?php echo home_url( '/' ); ?>strategies/" class="over">
                            	<h2>Strategies</h2>
                            	<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ac nibh vitae urna convallis tristique. Sed molestie hendrerit est, facilisis luctus odio tincidunt sed. Nam semper mauris nunc. Cras purus ipsum, pretium eu tincidunt vel, imperdiet vitae lorem.</p>
                            </a>
                        </li>
                    </ul>
				</div><!-- #post-## -->

			<?php endwhile; // end of the loop. ?>
            
		</div><!-- #container -->
        
<?php get_footer(); ?>

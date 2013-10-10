<?php 
//Template Name: Store
get_header(); ?>

		<div id="content" class="wrapper">

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                	<div class="header">
                    	<?php 
							$headline = get_post_meta($post->ID, 'headline', true);
							if($headline == '') { $headline = get_the_title(); }
						?>
						<h1 class="entry-title"><?php echo $headline; ?></h1>
                    </div>

					<?php 
						query_posts( 'cat=1&order=ASC' ); 
						$x=1;
						while (have_posts()) : the_post();
							$prod_img = get_post_meta($post->ID, 'prod_img', true);
							$price = get_post_meta($post->ID, 'price', true);
							$small_description = get_post_meta($post->ID, 'small-description', true);
							
							$modi = $x%2;
					?>
                    	<a href="<?php echo home_url( '/' ); ?>our-store/products-details/" class="products<?php if($modi == 1) { echo ' odd'; } else { echo ' even'; } ?>" style="background:url(<?php bloginfo( 'template_directory' ); ?>/images/<?=$prod_img?>) no-repeat;">
                        	<div class="non-over">
								<h2><?php the_title(); ?></h2>
                            	<div class="price">$ <?php echo $price; ?></div>
                            </div>
                            <div class="over">
                            	<h2><?php the_title(); ?></h2>
                                <p><?php echo $small_description; ?></p>
                            	<div class="price">$ <?php echo $price; ?></div>
                            </div>
                        </a>
                    <?php
						$x++;
						endwhile;
					?>
                    <div class="clear"></div>
				</div><!-- #post-## -->
            
		</div><!-- #container -->
        
<?php get_footer(); ?>

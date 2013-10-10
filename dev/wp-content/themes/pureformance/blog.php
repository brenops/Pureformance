<?php 
// Template Name: Blog
get_header(); ?>

		<div id="content" class="wrapper">

				<div id="post-<?php the_ID(); ?>" class="blog">

					<div class="entry-content">
						<?php
						/*query_posts( 'cat=5&posts_per_page=1&order=DESC' );
						while (have_posts()) : the_post();
						?>
                        	<div class="post featured">
                            	<div class="tag"></div>
                            	<?php echo the_post_thumbnail('thumbnail'); ?>
                                <div class="copy">
                                	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                    <div class="entry-meta">
										<?php pure_posted_on(); ?>
                                    </div>
                                    <?php the_excerpt(); ?>
                                </div>
                            </div>
                        <?php
						endwhile;*/
						?>
                        <?php
						query_posts(array('posts_per_page' => 6, 'orderby' => 'post_date', 'order' => 'DESC', 'paged' => ( get_query_var('paged') ? get_query_var('paged') : 1 )));
						$x=0;
						while (have_posts()) : the_post();
							$modi = $x % 2;
						?>
                        	<div class="post <?php if($modi == 1) { echo 'even'; } ?>">
                            	<div class="category"><?php $category = get_the_category(); echo $category[0]->cat_name; ?></div>
                            	<div class="thumb"><a href="<?php the_permalink(); ?>"><?php echo the_post_thumbnail('thumbnail'); ?></a></div>
                                <div class="copy">
                                	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                   <!--
 <div class="entry-meta">
										<?php //pure_posted_on(); ?>
                                    </div>
-->
                                    <?php the_excerpt(); ?>
                                </div>
                            </div>
                            <?php if($modi == 1) { echo '<div class="clear"></div>'; } ?>
						<?php
							$x++;
						endwhile;
						?>
                        <div class="clear"></div>
 						<?php if(function_exists('wp_paginate')) {
							wp_paginate();
						} ?>
                        <div class="clear"></div>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->

                <?php get_sidebar(); ?>
            	<div class="clear"></div>
                <ul class="nav-icons">
                    	<li>
    	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-shopping.png" />
        <span>Shop Products</span>
    	<a href="<?php echo home_url( '/' ); ?>shop/" class="over">
        	<h2>Shop Products</h2>
        	<p>Take a look at our innovative products purely designed to help you succeed. We don’t compromise, we strive to make the benefits to you our highest priority!</p>
        </a>
    </li>
    <li class="active">
    	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-write.png" />
        <span>Community</span>
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
                        	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-search.png" />
                            <span>Search</span>
                            <div class="over">
                            	<h2>Search</h2>
                            	<p>Enter keywords to search through our community page</p>
                                <?php echo get_search_form(); ?> 
                            </div>
                        </li>
                    </ul>
                <!--
<div class="footer">
                	<ul>
                        <li><a href="<?php echo home_url( '/' ); ?>strategies/">Strategies</a></li>
                        <li><a href="<?php echo home_url( '/' ); ?>blog/">Community</a></li>
                        <li><a href="<?php echo home_url( '/' ); ?>shop/">Products</a></li>
                        <li><a href="<?php echo home_url( '/' ); ?>about-us/">About Us</a></li>
                        <li><a href="<?php echo home_url( '/' ); ?>contact-us/">Contact</a></li>
                    </ul>
                    <div class="clear"></div>
                    <div class="copyright">Pureformance  &copy  <?=date('Y');?> All Rights Reserved</div>
                </div>
                <div class="footer-shadow"></div>
-->
		</div><!-- #container -->
        
<?php get_footer(); ?>

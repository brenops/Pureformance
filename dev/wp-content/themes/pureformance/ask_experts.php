<?php
// When default permalinks are enabled, redirect shop page to post type archive url
if (!is_user_logged_in() || $_SESSION['is_subscriber']==0) //check if user is not logged in or if user is not a subscriber
{
	header('Location:'.home_url( '/' ).'access-forum'); //redirect to login page
}
else
{
	//echo 'user is a blog subscriber';
}
?>


<?php
// Template Name: Ask Experts
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
						query_posts(array('posts_per_page' => 6, 'orderby' => 'post_date', 'order' => 'DESC', 'cat' => '-1', 'paged' => ( get_query_var('paged') ? get_query_var('paged') : 1 )));
						$x=0;
						while (have_posts()) : the_post();
							$modi = $x % 2;
						?>
                        	<div class="post <?php if($modi == 1) { echo 'even'; } ?>">
                            	<div class="category"><?php $category = get_the_category(); echo $category[0]->cat_name; ?></div>
                            	<div class="thumb"><a href="<?php the_permalink(); ?>"><?php echo the_post_thumbnail('thumbnail'); ?></a></div>
                                <div class="copy">
                                	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                    <div class="entry-meta">
										<?php pure_posted_on(); ?>
                                    </div>
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
                        	<a href="<?php echo home_url( '/' ); ?>our-store/" class="over">
                            	<h2>Shop Products</h2>
                            	<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ac nibh vitae urna convallis tristique. Sed molestie hendrerit est, facilisis luctus odio tincidunt sed. Nam semper mauris nunc.</p>
                            </a>
                        </li>
                        <li>
                        	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-write.png" />
                            <span>Write For Us</span>
                            <a href="<?php echo home_url( '/' ); ?>contact-us/" class="over">
                            	<h2>Write for us</h2>
                            	<p>We're currently looking for writers that can write articles. To apply, please send us a brief email detailing your experience as well as links to your published material. Learn more</p>
                            </a>
                        </li>
                        <li>
                        	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-newsletter.png" />
                            <span>Newsletter</span>
                            <div class="over">
                            	<h2>Newsletter </h2>
                                <!-- Begin MailChimp Signup Form -->
                                <div id="mc_embed_signup">
                                <form action="http://pureformance.us6.list-manage2.com/subscribe/post?u=5d0616e2292ae1b215551e6be&amp;id=7364afaaaf" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
                                    <label for="mce-EMAIL">Subscribe to our mailing list</label>
                                    <input type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email address" required>
                                    <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
                                </form>
                                </div>

                                <!--End mc_embed_signup-->
                            </div>
                        </li>
                        <li class="last">
                        	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-search.png" />
                            <span>Search</span>
                            <div class="over">
                            	<h2>Search</h2>
                            	<p>Search any articles on our blog</p>
                                <?php echo get_search_form(); ?>
                            </div>
                        </li>
                    </ul>
                <div class="footer">
                	<ul>
                        <li><a href="<?php echo home_url( '/' ); ?>strategies/">Strategies</a></li>
                        <li><a href="<?php echo home_url( '/' ); ?>blog/">Community</a></li>
                        <li><a href="<?php echo home_url( '/' ); ?>our-store/">Products</a></li>
                        <li><a href="<?php echo home_url( '/' ); ?>about-us/">About Us</a></li>
                        <li><a href="<?php echo home_url( '/' ); ?>contact-us/">Contact</a></li>
                    </ul>
                    <div class="clear"></div>
                    <div class="copyright">Pureformance  &copy  <?php echo date('Y');?> All Rights Reserved</div>
                </div>
                <div class="footer-shadow"></div>
		</div><!-- #container -->

<?php get_footer(); ?>

<?php get_header(); ?>

			<div id="content" role="main" class="wrapper">

			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" class="blog">


					<div class="entry-content">
						<h1 class="entry-title"><?php the_title(); ?></h1>
                        <div class="entry-meta">
                            <?php pure_posted_on(); ?>
                        </div><!-- .entry-meta -->
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
                        <div class="entry-utility">
                            <?php //pure_posted_in(); ?>
                        </div><!-- .entry-utility -->
					</div><!-- .entry-content -->

				</div><!-- #post-## -->

				<?php// comments_template( '', true ); ?>

			<?php endwhile; // end of the loop. ?>
				 <?php get_sidebar(); ?>
            	<div class="clear"></div><br /><br />
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
			</div><!-- #content -->

<?php get_footer(); ?>

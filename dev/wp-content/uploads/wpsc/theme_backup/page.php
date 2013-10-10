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

					<div class="entry-content">
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
                        <div class="clear"></div>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->

			<?php endwhile; // end of the loop. ?>
            
		</div><!-- #container -->
        
<?php get_footer(); ?>

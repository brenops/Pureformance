<?php
get_header(); ?>

		<div id="content" class="wrapper">
			<div id="post-<?php the_ID(); ?>" class="blog">
				<div class="entry-content">
				<div class="back">
						<a href="<?php echo home_url( '/' ); ?>/blog/" class="view-all btn2">&laquo; View All</a>	
					</div>
				<h1 class="page-title"><?php
					printf( __( 'Tag Archives: %s', 'twentyten' ), '<span>' . single_tag_title( '', false ) . '</span>' );
				?></h1>

<?php
/* Run the loop for the tag archive to output the posts
 * If you want to overload this in a child theme then include a file
 * called loop-tag.php and that will be used instead.
 */
 get_template_part( 'loop', 'tag' );
?>
				</div>
                <?php if(function_exists('wp_paginate')) {
							wp_paginate();
						} ?>
			</div><!-- #content -->
			<?php get_sidebar(); ?>
            <div class="clear"></div>
		</div><!-- #container -->
<?php get_footer(); ?>
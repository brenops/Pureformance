<?php
/**
 * The template for displaying Search Results pages.
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 */

get_header(); ?>

		<div id="content" class="wrapper">

				<div id="post-<?php the_ID(); ?>" class="blog">

					<div class="entry-content">

<?php if ( have_posts() ) : ?>
				<h1 class="page-title"><?php printf( __( 'Search Results for: %s', 'twentyten' ), '<span>' . get_search_query() . '</span>' ); ?></h1>
				<?php
				/* Run the loop for the search to output the results.
				 * If you want to overload this in a child theme then include a file
				 * called loop-search.php and that will be used instead.
				 */
				 get_template_part( 'loop', 'search' );
				?>
<?php else : ?>
					<h2 class="entry-title"><?php _e( 'Nothing Found', 'twentyten' ); ?></h2>
						<p><?php _e( 'Sorry, but nothing matched your search criteria. Please try again with some different keywords.', 'twentyten' ); ?></p>
<?php endif; ?>
						<div class="clear"></div>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->

                <?php get_sidebar(); ?>
            	<div class="clear"></div>
<?php get_footer(); ?>

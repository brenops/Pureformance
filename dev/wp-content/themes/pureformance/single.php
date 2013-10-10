<?php get_header(); ?>
<?php if ($_SESSION['is_subscriber']==0) {?>
<script>
	$(document).ready(function() {
        $("#members-only-trigger").fancybox({
        	'showCloseButton' : false,
        	'hideOnOverlayClick' : false
        });
    	$("#members-only-trigger").trigger( "click" );
    });
</script>
<?php } ?>
			<div id="content" role="main" class="wrapper">

			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" class="blog">


					<div class="entry-content">
						<div class="back">
							<a href="javascript:history.back()" class="btn2">&laquo; Back</a>
						</div>
						<h1 class="entry-title"><?php the_title(); ?></h1>
                        <!--
<div class="entry-meta">
                            <?php //pure_posted_on(); ?>
                        </div>
--><!-- .entry-meta -->
                        <br>
                        <!-- AddThis Button BEGIN -->
<div class="addthis_toolbox addthis_default_style ">
<a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
<a class="addthis_button_tweet"></a>
<a class="addthis_button_pinterest_pinit"></a>
<a class="addthis_counter addthis_pill_style"></a>
</div>
<script type="text/javascript">var addthis_config = {"data_track_addressbar":false};</script>
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-4f9aa73e5e1140b7"></script>
<!-- AddThis Button END --><br>
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
                        <div class="entry-utility">
                            <?php //pure_posted_in(); ?>
                        </div><!-- .entry-utility -->
                        <!-- AddThis Button BEGIN -->
<div class="addthis_toolbox addthis_default_style ">
<a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
<a class="addthis_button_tweet"></a>
<a class="addthis_button_pinterest_pinit"></a>
<a class="addthis_counter addthis_pill_style"></a>
</div>
<script type="text/javascript">var addthis_config = {"data_track_addressbar":false};</script>
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-4f9aa73e5e1140b7"></script>
<!-- AddThis Button END -->
					</div><!-- .entry-content -->

				</div><!-- #post-## -->

				<?php// comments_template( '', true ); ?>

			<?php endwhile; // end of the loop. ?>
				 <?php get_sidebar(); ?>
            	<div class="clear"></div><br /><br />
			</div><!-- #content -->

<?php get_footer(); ?>

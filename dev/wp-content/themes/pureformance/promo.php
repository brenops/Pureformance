<?php
// Template Name: Promo
get_header();
?>
<script>
$(document).ready(function(){

});
</script>
<div id="content" class="wrapper gift-lp">

    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="header">
            <?php
                $headline = get_post_meta($post->ID, 'headline', true);
                if ($headline == '') { $headline = 'The Gift That Matters'; }
            ?>
            <h1 class="entry-title"><?php echo $headline?></h1>
        </div>
        <div class="entry-content">
			<div class="what-is">
				<h1>What is Pureformance?</h1>
				<div class="video">
					<span>Video Goes here</span>
					<p>What's Inside the Gift?</p>
				</div>
				<ul>
					<li>A place to find resources to help you perform in what you do</li>
					<li>A place to connect with others who want the most out of life</li>
					<li>Interviews, videos, articles, and applications for your benefit</li>
				</ul>
				<a href="<?php echo home_url( '/' ); ?>create-account/" class="get-started">Get Started<span>I'm ready to Give $5 Gift</span></a>
			</div>
		</div>
    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

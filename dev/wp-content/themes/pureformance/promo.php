<?php
// Template Name: Promo
get_header();
?>
<script>
$(document).ready(function(){

});
</script>
<div id="content" class="wrapper portal">

    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="header">
            <?php
                $headline = get_post_meta($post->ID, 'headline', true);
                if ($headline == '') { $headline = '<span>PURE</span> Simple, Powerful, Effective, Trusted'; }
            ?>
            <h1 class="entry-title">Welcome to Pureformance!<?php //echo $headline?></h1>
        </div>

        <div class="entry-content">
            <div class="copy">

                Pureformance is all about ... <br /><br />

                We are introducing a new model of gifting ... <br /><br />

                Watch the video below to learn more ... <br /><br />
            </div>
            <div class="clear"></div>
        </div><!-- .entry-content -->
        <div class="entry-content">
            <div class="copy">

                <a href="<?php echo home_url( '/' ); ?>create-account/" class="btn1"><span>Give Gift Now</span></a><br /><br />

                <a href="<?php echo home_url( '/' ); ?>/" style="text-decoration:underline;"><span>Not yet, I'd like to explore the site more</span></a>

            </div>
            <div class="clear"></div>
        </div><!-- .entry-content -->
    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

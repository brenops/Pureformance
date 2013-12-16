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
			<div class="the-power">
				<h2>The Power of Gifting</h2>
				<p>Feel great, help someone out and build real connections</p>
				<ul>
					<li>Choose a friend, family member, or teammate to help out</li>
					<li>Make a quick, one-time payment of $5</li>
					<li>You have started their first-month's membership access at PF,<br>
for the cost of a cup of coffee</li>
				</ul>
				<div class="disclaimer">
					<span>Disclaimer:</span> You are only giving a one-time $5 membership gift.<br>
					There is <span>no commitment</span> to you after this first month.<br>
					<span>We will not store your credit card</span> or bill you for any future months. 
				</div>
				<a href="<?php echo home_url( '/' ); ?>create-account/" class="get-started">Get Started</a>
			</div>
			<div class="now-what">
				<h2>I gave my friend the GIFT. What now?</h2>
				<div class="left">
					<h3>You're Done!</h3>
					<p>Your friend has access to our high powered team and performance content and you are
on your way to having a <strong>PURE</strong> day.</p>
				</div>
				<div class="or"></div>
				<div class="right">
					<h3>You Can Join Your Friends at PF</h3>
					<ul>
						<li>Give the Gift, Get the Gift
						<li>Use our social media and direct email tools to shout out to your friends
						<li>They click your unique link to GIFT you your first month’s membership access
						<li><span>YOU'RE IN!</span></li>
					</ul>
					<a href="<?php echo home_url( '/' ); ?>membership/" class="get-started">Get Started</a>
				</div>
				<div class="clear"></div>
			</div>
			<div class="share-pureformance">
				<h2>I can't wait to share Pureformance with
someone that will benefit</h2>
		    	<img src="<?php bloginfo( 'template_directory' ); ?>/images/giving-gift.jpg" /><br><br>
				<a href="<?php echo home_url( '/' ); ?>create-account/" class="get-started">Give the gift now</a>
			</div>
		</div>
		<ul class="nav-icons">
			<li>
		    	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-pureformance.png" />
		        <span>Explore Pureformance</span>
		    	<a href="<?php echo home_url( '/' ); ?>" class="over">
		        	<h2>Explore Pureformance</h2>
		        	<p>Come inside and explore. We want to
see YOUR best, hear YOUR stories,
expand YOUR minds, save YOU time,
celebrate the amazing things YOU do
and bring YOU the secrets of other
game-changers we celebrate</p>
		        </a>
		    </li>
		    <li>
		    	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-video.png" />
		        <span>Watch Welcome Video</span>
		        <a href="<?php echo home_url( '/' ); ?>" class="over">
		        	<h2>Watch Welcome Video</h2>
		        	<p>Want to know more about us? Watch
our cool welcome video and see more
about PF and what your experience will
be like when you gain access.</p>
		        </a>
		    </li>
		    <li class="last">
		    	<img src="<?php bloginfo( 'template_directory' ); ?>/images/icon-write.png" />
		        <span>Gifting FAQ</span>
		        <a href="<?php echo home_url( '/' ); ?>gifting-faqs/" class="over">
		        	<h2>Gifting FAQ</h2>
		        	<p>Our “Give the Gift” is a new and powerful
idea never done on this scale so we
have taken the time to give you a
detailed explanation of how it works, why
it is so powerful and how it benefits you
and everyone involved!</p>
		        </a>
		    </li>
		</ul>
    </div><!-- #post-## -->

</div><!-- #container -->

<?php get_footer(); ?>

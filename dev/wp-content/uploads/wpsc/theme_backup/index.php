<?php get_header(); ?>

<div class="portals">
	<div class="portal1"><a href="<?php echo home_url( '/' ); ?>"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal1.jpg" /></a>
    	<div class="over">
        	<h2>High Performance</h2>
            <p>We have exactly what do you need to help you reach that next level!</p>
            <a class="btn3" href="<?php echo home_url( '/' ); ?>portals/high-performance/"><span>Move</span></a>
        </div>
    </div>
    <div class="portal2"><a href="<?php echo home_url( '/' ); ?>portals/game-changer/"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal2.jpg"/></a>
    	<div class="over">
        	<h2>Game-Changer</h2>
            <p>We have exactly what do you need to help you reach that next level!</p>
            <a class="btn3" href="<?php echo home_url( '/' ); ?>portals/game-changer/"><span>Do</span></a>
        </div>
    </div>
    <div class="portal3"><a href="<?php echo home_url( '/' ); ?>portals/lifestyle/"><img src="<?php bloginfo( 'template_directory' ); ?>/images/portal3.jpg"/></a>
    	<div class="over">
        	<h2>Lifestyle</h2>
            <p>We have exactly what do you need to help you reach that next level!</p>
            <a class="btn3" href="<?php echo home_url( '/' ); ?>portals/lifestyle/"><span>Be</span></a>
        </div>
    </div>
</div>
<script src="<?php bloginfo( 'template_directory' ); ?>/js/index.js" type="text/javascript"></script>

<?php get_footer(); ?>

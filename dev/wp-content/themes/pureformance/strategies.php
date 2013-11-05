<?php 
//Template Name: Strategies
get_header(); ?>

		<div id="content" class="wrapper">

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php while (have_posts()) : the_post(); ?>
                	<div class="header">
                    	<?php 
							$headline = get_post_meta($post->ID, 'headline', true);
							if($headline == '') { $headline = "<span>PURE</span> Simple, Powerful, Effective, Trusted"; }
						?>
						<h1 class="entry-title"><?php echo $headline; ?></h1>
                    </div>
                    <div class="entry-content">
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>
                        <div class="clear"></div>
					</div><!-- .entry-content -->
				<?php
						endwhile;
					?>
					<?php 
						$args = array(
						'post_type' => 'strategies', 
						'orderby' => 'meta_value', 
						'meta_key' => 'order',
						'order' => 'ASC', 
						'numberposts' => -1
						);
						$postlist = get_posts($args);
						$x=1;
						foreach( $postlist as $bookpost ) {
						
							$member_access = get_post_meta($bookpost->ID, 'member_access', true);
							$rollover_description = get_post_meta($bookpost->ID, 'rollover_description', true);
							$book_url = get_post_meta($bookpost->ID, 'book_url', true);
							$book_image = wp_get_attachment_url( get_post_thumbnail_id($bookpost->ID) );
							$book_type = get_post_meta($bookpost->ID, 'book_type', true);	
							if($book_type == 'Guidebook') {
					?>
                    	<div class="book-list" style="background:url(<?=$book_image?>) no-repeat;">
                        	<div class="non-over">
                            	<h2><?php echo $bookpost->post_title; ?></h2>
                                <p><?php echo $bookpost->post_content; ?></p>
                                <?php
                            	if($book_url == '') {
                            		echo '<div class="soon">Coming Soon</div>';
                            	} else {
                            		if($member_access == 1) {
										if (!is_user_logged_in() || $_SESSION['is_subscriber']==0) //check if user is not logged in or if user is not a subscriber 
										{
											echo '<a href="'.get_bloginfo('url').'/membership/" class="btn1"><span>Sign Up</span></a>';
										}
										else
										{
											echo '<a href="'.$book_url.'" class="btn1" target="_blank"><span>Read Now</span></a>';
										}
									} else {
										echo '<a href="'.$book_url.'" class="btn1" target="_blank"><span>Read Now</span></a>';
									}
                            	} ?>
                            </div>
                            <div class="over">
                                <p><?php echo $rollover_description; ?></p>
                            	<?php
                            	if($book_url == '') {
                            		echo '<div class="soon">Coming Soon</div>';
                            	} else {
	                            	if($member_access == 1) {
										if (!is_user_logged_in() || $_SESSION['is_subscriber']==0) //check if user is not logged in or if user is not a subscriber 
										{
											echo '<a href="'.get_bloginfo('url').'/membership/" class="btn2"><span>Sign Up</span></a>';
										}
										else
										{
											echo '<a href="'.$book_url.'" class="btn2" target="_blank"><span>Read Now</span></a>';
										}
									} else {
										echo '<a href="'.$book_url.'" class="btn2" target="_blank"><span>Read Now</span></a>';
									}
								}
								?>
                            </div>
                        </div>
                    <?php
                    		}
						}
					?>
					<div class="clear"></div>
					<div class="entry-content">
						<!-- <div class="not-member">Not Yet a Member? <a href="<?php echo get_bloginfo('url'); ?>/access-forum">Click Here to Sign Up</a></div> -->
						<h2>Strategies</h2>
						<p>Looking for a way to cut through all of the advice, studies, data and tips out there and get to the PURE heart of the matter? Seeking clear, easy-to-understand applications that will allow you to access your full power and reap the benefits? You've come to the right place! </p>
					</div>
					<?php 
						$args = array(
						'post_type' => 'strategies', 
						'orderby' => 'name', 
						'order' => 'ASC', 
						'numberposts' => -1
						);
						$postlist = get_posts($args);
						$x=1;
						foreach( $postlist as $bookpost ) {
						
							$member_access = get_post_meta($bookpost->ID, 'member_access', true);
							$rollover_description = get_post_meta($bookpost->ID, 'rollover_description', true);
							$book_url = get_post_meta($bookpost->ID, 'book_url', true);
							$book_image = wp_get_attachment_url( get_post_thumbnail_id($bookpost->ID) );
							$book_type = get_post_meta($bookpost->ID, 'book_type', true);	
							if($book_type == 'Strategies') {	
					?>
                    	<div class="book-list" style="background:url(<?=$book_image?>) no-repeat;">
                        	<div class="non-over">
                            	<h2><?php echo $bookpost->post_title; ?></h2>
                                <p><?php echo $bookpost->post_content; ?></p>
                                <?php
                            	if($book_url == '') {
                            		echo '<div class="soon">Coming Soon</div>';
                            	} else {
                            		if($member_access == 1) {
										if (!is_user_logged_in() || $_SESSION['is_subscriber']==0) //check if user is not logged in or if user is not a subscriber 
										{
											echo '<a href="'.get_bloginfo('url').'/membership/" class="btn1"><span>Sign Up</span></a>';
										}
										else
										{
											echo '<a href="'.$book_url.'" class="btn1" target="_blank"><span>Read Now</span></a>';
										}
									} else {
										echo '<a href="'.$book_url.'" class="btn1" target="_blank"><span>Read Now</span></a>';
									}
                            	} ?>
                            </div>
                            <div class="over">
                                <p><?php echo $rollover_description; ?></p>
                            	<?php
                            	if($book_url == '') {
                            		echo '<div class="soon">Coming Soon</div>';
                            	} else {
	                            	if($member_access == 1) {
										if (!is_user_logged_in() || $_SESSION['is_subscriber']==0) //check if user is not logged in or if user is not a subscriber 
										{
											echo '<a href="'.get_bloginfo('url').'/membership/" class="btn2"><span>Sign Up</span></a>';
										}
										else
										{
											echo '<a href="'.$book_url.'" class="btn2" target="_blank"><span>Read Now</span></a>';
										}
									} else {
										echo '<a href="'.$book_url.'" class="btn2" target="_blank"><span>Read Now</span></a>';
									}
								}
								?>
                            </div>
                        </div>
                    <?php
                    		}
						}
					?>
                    <div class="clear"></div>
                    <br><br>
				</div><!-- #post-## -->
            	
                    <?php //include (TEMPLATEPATH . '/bottom-boxes.php'); ?>
		</div><!-- #container -->
        
<?php get_footer(); ?>

<div id="sidebar" role="complementary">
	<div class="ask-expert">
    	<h2>We are here to help!</h2>
        <p>Reach into your community for questions, comments, help and how to contribute.</p>
		<a href="<?php if (!is_user_logged_in() || $_SESSION['is_subscriber']==0) { echo '#members-only'; } else	{ echo '#ask-expert-form'; } ?>" id="ask-expert">Ask</a>
    </div>
	<?php dynamic_sidebar( 'blog-sidebar' ); ?>
	<div class="widget-container widget_text featured-athlete">
		<h3>Game Changer</h3>			
		<div class="textwidget">
			<?php 
			query_posts( 'post_type=page&post_parent=523&posts_per_page=1&order=DESC' );
			while (have_posts()) : the_post();
			?>
				<?php echo the_post_thumbnail('thumbnail'); ?>
				<h2><?php the_title(); ?></h2>
				<?php the_content(); ?>
            <?php
			endwhile;
			?>
		</div>
	</div>
</div>
<div style="display:none">
	<div id="ask-expert-form" class="ask-expert-popup">
		<h2>We are here to help!</h2>
		<div id="form-error"></div>
		<?
		global $current_user;
      	get_currentuserinfo();
      	?>
		<input type="text" name="name" value="<?=$current_user->user_firstname?>" onblur="if(this.value=='')this.value='Name';" onfocus="if(this.value=='Name')this.value='';">
		<input type="text" name="email_address" value="<?=$current_user->user_email?>" onblur="if(this.value=='')this.value='Email Address';" onfocus="if(this.value=='Email Address')this.value='';">
        <input type="text" name="subject" value="Subject" onblur="if(this.value=='')this.value='Subject';" onfocus="if(this.value=='Subject')this.value='';" class="subject">
        <textarea name="message" onblur="if(this.value=='')this.value='Enter your question here';" onfocus="if(this.value=='Enter your question here')this.value='';" rows="5">Enter your question here</textarea>
        <a href="javascript:void(0)" class="btn1" id="submitQuestion">Submit Question</a>
	</div>
</div>
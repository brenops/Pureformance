<?php
//Template Name: Product Detail
get_header(); ?>

		<div id="content" class="wrapper products-details">

			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                	<div class="header">
                    	<?php
							$headline = get_post_meta($post->ID, 'headline', true);
							if($headline == '') { $headline = get_the_title(); }
						?>
						<h1 class="entry-title"><span>PURE</span> Simple, Powerful, Effective, Trusted</h1>
                    </div>

					<div class="entry-content">
                    	<div class="main-description">
                        	<div class="left-side">
                            	<img src="<?php bloginfo( 'template_directory' ); ?>/images/tshirt-large.jpg" />
                            </div>
                            <div class="right-side">
                            	<div class="desc">
                            		<h2><?php echo the_title(); ?></h2>
                                        <?php the_content(); ?>
                                </div>
                                <div class="price">
                                        <span class="amount"><?php echo get_post_meta($post->ID, 'price', true);?></span>
                                	<label>Qty:</label> <input type="text" name="qty" value="1" />
                                	<a href="" class="btn1"><span>Add to Cart</span></a>
                                </div>
                                <div class="tabs">
                                	<ul>
                                    	<li class="high active"><a href="#highperformance">High Performance</a></li>
                                        <li class="changer"><a href="#gamechanger">Game-Changer</a></li>
                                        <li class="lifestyle"><a href="#lifestyle">Lifestyle</a></li>
                                    </ul>
                                    <div id="highperformance">
                                    	.<?php echo get_post_meta($post->ID, 'high-performance', true);?>
                                    </div>
                                    <div id="gamechanger" style="display:none">
                                    	..<?php echo get_post_meta($post->ID, 'game-changer', true);?>
                                    </div>
                                    <div id="lifestyle" style="display:none">
                                    	...<?php echo get_post_meta($post->ID, 'lifestyle', true);?>
                                    </div>
                                </div>
                            </div>
                        	<div class="clear"></div>
                        </div>
                        <div class="experience">
                        	<div class="col first">
                                <h3>With This Product, Experience:</h3>
                                <ul>
                                    <?php echo get_post_meta($post->ID, 'experience', true);?>
                                </ul>
                            </div>
                        	<div class="col">
                                <h3>You Will Not Experience:</h3>
                                <ul>
                                    <?php echo get_post_meta($post->ID, 'not-experience', true);?>
                                </ul>
                            </div>
                        	<div class="clear"></div>
                        </div>
                        <div class="experts">
                        	<h4>Want to see what the experts say, or have a question for the experts?</h4>
                            <div class="info">
                            	<div class="testimonial">
                                	<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque dapibus urna eu quam convallis laoreet. Nam sagittis, lorem non fermentum laoreet.</p>
									<p>Pellentesque dapibus urna eu quam convallis laoreet. Nam sagittis, lorem non fermentum laoreet.</p>
                                    <p><strong>- Hope Solo</strong></p>
                                    <img src="<?php bloginfo( 'template_directory' ); ?>/images/hope-solo.png" />
                                </div>
                                <div class="question">
                                	<p>Want to ask an expert a question about this product or anything pertaining to your workout, routines, or lifestyle? Login or Sign up now for exclusive access to our blog and access to our experts and their vast pools of knowledge.</p>
                                    <input type="text" name="subject" placeholder="Subject" />
                                    <textarea name="comment" placeholder="Enter your question here"></textarea>
                                    <a href="" class="btn1"><span>Submit Question</span></a>
                                </div>
                            	<div class="clear"></div>
                            </div>
                        </div>
					</div><!-- .entry-content -->
				</div><!-- #post-## -->

			<?php endwhile; // end of the loop. ?>

		</div><!-- #container -->

<?php get_footer(); ?>

<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * Override this template by copying it to yourtheme/woocommerce/content-single-product.php
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<?php
	/**
	 * woocommerce_before_single_product hook
	 *
	 * @hooked woocommerce_show_messages - 10
	 */
	 do_action( 'woocommerce_before_single_product' );
	 global $post, $product;
?>
<ul class="progress-bar">
	<li>STEP 1<span>Browse Products</span></li>
	<li class="current">STEP 2<span>Choose the best solution</span></li>
	<li>STEP 3<span>Easy checkout process</span></li>
	<li class="last">STEP 4<span>Enhance your life</span></li>
</ul>
<div itemscope itemtype="http://schema.org/Product" id="product-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="main-description">
    	<div class="left-side">        	
			<?php
				/**
				 * woocommerce_show_product_images hook
				 *
				 * @hooked woocommerce_show_product_sale_flash - 10
				 * @hooked woocommerce_show_product_images - 20
				 */
				do_action( 'woocommerce_before_single_product_summary' );
			?>
        </div>
        <div class="right-side">
        	<div class="desc">
        		<h2><?=the_title();?></h2>
				<?php the_content(); ?>
            </div>
            <div class="price">
				<?php echo $product->get_price_html(); ?>
            	<!--
<label>Qty:</label> <input type="text" name="qty" value="1" />
            	<a href="" class="btn1"><span>Add to Cart</span></a>
-->
				<?php
				if ( ! $product->is_purchasable() ) return;
				?>
				
				<?php
					// Availability
					$availability = $product->get_availability();
				
					if ($availability['availability']) :
						echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</p>', $availability['availability'] );
				    endif;
				?>
				
				<?php if ( $product->is_in_stock() ) : ?>
				
					<?php do_action('woocommerce_before_add_to_cart_form'); ?>
				
					<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart" method="post" enctype='multipart/form-data'>
				
					 	<?php do_action('woocommerce_before_add_to_cart_button'); ?>
				
					 	<?php
					 		if ( ! $product->is_sold_individually() )
					 			woocommerce_quantity_input( array(
					 				'min_value' => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
					 				'max_value' => apply_filters( 'woocommerce_quantity_input_max', $product->backorders_allowed() ? '' : $product->get_stock_quantity(), $product )
					 			) );
					 	?>
				
					 	<button type="submit" class="single_add_to_cart_button button alt"><?php echo apply_filters('single_add_to_cart_text', __( 'Add to cart', 'woocommerce' ), $product->product_type); ?></button>
				
					 	<?php do_action('woocommerce_after_add_to_cart_button'); ?>
				
					</form>
				
					<?php do_action('woocommerce_after_add_to_cart_form'); ?>
				
				<?php endif; ?>
            </div>
            <!--
<div class="tabs">
            	<ul>
                	<li class="high active"><a href="#highperformance">High Performance</a></li>
                    <li class="changer"><a href="#gamechanger">Game-Changer</a></li>
                    <li class="lifestyle"><a href="#lifestyle">Lifestyle</a></li>
                </ul>
                <div id="highperformance">
                	<?=get_post_meta($post->ID, 'high-performance', true);?>
                </div>
                <div id="gamechanger" style="display:none">
                	<?=get_post_meta($post->ID, 'game-changer', true);?>
                </div>
                <div id="lifestyle" style="display:none">
                	<?=get_post_meta($post->ID, 'lifestyle', true);?>
                </div>
            </div>
-->
        </div>
    	<div class="clear"></div>
    </div>
    <div class="experience">
    	<div class="col first">
            <h3>With This Product, Experience:</h3>
            <ul>
                <?=get_post_meta($post->ID, 'experience', true);?>
            </ul>
        </div>
    	<div class="col">
            <h3>You Will Not Experience:</h3>
            <ul>
                <?=get_post_meta($post->ID, 'not-experience', true);?>
            </ul>
        </div>
    	<div class="clear"></div>
    </div>
    <!--div class="experts">
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
				<?php if ($_SESSION['is_subscriber']==0) {?>
				<p><strong>You have to be a member to submit questions.</strong></p>
				<a href="<?php echo home_url( '/' ); ?>access-forum/" class="btn1">Sign up now</a>
				<? } else { 
				global $current_user;
      			get_currentuserinfo();
      			?>
				<div id="form-error"></div>
				<input type="hidden" name="name" value="<?=$current_user->user_firstname?>">
				<input type="hidden" name="email_address" value="<?=$current_user->user_email?>">
                <input type="text" name="subject" value="Subject" onblur="if(this.value=='')this.value='Subject';" onfocus="if(this.value=='Subject')this.value='';">
                <textarea name="message" onblur="if(this.value=='')this.value='Enter your question here';" onfocus="if(this.value=='Enter your question here')this.value='';" rows="5">Enter your question here</textarea>
                <a href="javascript:void(0)" class="btn1" id="submitQuestion">Submit Question</a>
                <? } ?>
            </div>
        	<div class="clear"></div>
        </div>
    </div-->
		<?php
			/**
			 * woocommerce_single_product_summary hook
			 *
			 * @hooked woocommerce_template_single_title - 5
			 * @hooked woocommerce_template_single_price - 10
			 * @hooked woocommerce_template_single_excerpt - 20
			 * @hooked woocommerce_template_single_add_to_cart - 30
			 * @hooked woocommerce_template_single_meta - 40
			 * @hooked woocommerce_template_single_sharing - 50
			 */
			//do_action( 'woocommerce_single_product_summary' );
		?>

	<?php
		/**
		 * woocommerce_after_single_product_summary hook
		 *
		 * @hooked woocommerce_output_product_data_tabs - 10
		 * @hooked woocommerce_output_related_products - 20
		 */
		//do_action( 'woocommerce_after_single_product_summary' );
	?>

</div><!-- #product-<?php the_ID(); ?> -->

<?php do_action( 'woocommerce_after_single_product' ); ?>
<?php

add_action( 'after_setup_theme', 'twentyten_setup' );

if ( ! function_exists( 'twentyten_setup' ) ):
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 */
function twentyten_setup() {

	add_filter('show_admin_bar', '__return_false');

	// This theme uses post thumbnails
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus( array(
		'primary' => __( 'Primary Navigation', 'twentyten' ),
	) );
}
endif;

/**
 * Sets the post excerpt length to 40 characters.
 *
 */
function twentyten_excerpt_length( $length ) {
	return 40;
}
add_filter( 'excerpt_length', 'twentyten_excerpt_length' );

/**
 * Returns a "Continue Reading" link for excerpts
 */
function twentyten_continue_reading_link() {
	return ' <a href="'. get_permalink() . '">' . __( 'Read More <span class="meta-nav">&raquo;</span>', 'twentyten' ) . '</a>';
}

/**
 * Replaces "[...]" (appended to automatically generated excerpts) with an ellipsis and twentyten_continue_reading_link().
 */
function twentyten_auto_excerpt_more( $more ) {
	return ' &hellip;' . twentyten_continue_reading_link();
}
add_filter( 'excerpt_more', 'twentyten_auto_excerpt_more' );

/**
 * Adds a pretty "Continue Reading" link to custom post excerpts.
 *
 */
function twentyten_custom_excerpt_more( $output ) {
	if ( has_excerpt() && ! is_attachment() ) {
		$output .= twentyten_continue_reading_link();
	}
	return $output;
}
add_filter( 'get_the_excerpt', 'twentyten_custom_excerpt_more' );

if ( ! function_exists( 'twentyten_comment' ) ) :
/**
 * Template for comments and pingbacks.
 */
function twentyten_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case '' :
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<div id="comment-<?php comment_ID(); ?>">
		<div class="comment-author vcard">
			<?php echo get_avatar( $comment, 40 ); ?>
			<?php printf( __( '%s <span class="says">says:</span>', 'twentyten' ), sprintf( '<cite class="fn">%s</cite>', get_comment_author_link() ) ); ?>
		</div><!-- .comment-author .vcard -->
		<?php if ( $comment->comment_approved == '0' ) : ?>
			<em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'twentyten' ); ?></em>
			<br />
		<?php endif; ?>

		<div class="comment-meta commentmetadata"><a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
			<?php
				/* translators: 1: date, 2: time */
				printf( __( '%1$s at %2$s', 'twentyten' ), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( '(Edit)', 'twentyten' ), ' ' );
			?>
		</div><!-- .comment-meta .commentmetadata -->

		<div class="comment-body"><?php comment_text(); ?></div>

		<div class="reply">
			<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
		</div><!-- .reply -->
	</div><!-- #comment-##  -->

	<?php
			break;
		case 'pingback'  :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p><?php _e( 'Pingback:', 'twentyten' ); ?> <?php comment_author_link(); ?><?php edit_comment_link( __( '(Edit)', 'twentyten' ), ' ' ); ?></p>
	<?php
			break;
	endswitch;
}
endif;

/**
 * Register widgetized areas, including two sidebars and four widget-ready columns in the footer.
 *
 */
function twentyten_widgets_init() {
	// Area 1, located at the top of the sidebar.
	register_sidebar( array(
		'name' => __( 'Blog - Sidebar', 'twentyten' ),
		'id' => 'blog-sidebar',
		'before_widget' => '<div id="%1$s" class="widget-container %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
	register_sidebar( array(
		'name' => __( 'Login', 'twentyten' ),
		'id' => 'ecommerce-login',
		'before_widget' => '<div id="%1$s" class="widget-container %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h2>',
		'after_title' => '</h2>',
	) );
}
/** Register sidebars by running twentyten_widgets_init() on the widgets_init hook. */
add_action( 'widgets_init', 'twentyten_widgets_init' );

/**
 * Removes the default styles that are packaged with the Recent Comments widget.
 *
 */
function twentyten_remove_recent_comments_style() {
	add_filter( 'show_recent_comments_widget_style', '__return_false' );
}
add_action( 'widgets_init', 'twentyten_remove_recent_comments_style' );

if ( ! function_exists( 'pure_posted_on' ) ) :
/**
 * Prints HTML with meta information for the current post-date/time and author.
 *
 * @since Twenty Ten 1.0
 */
function pure_posted_on() {
	printf( __( 'By %3$s | %2$s', 'twentyten' ),
		'meta-prep meta-prep-author',
		sprintf( '%3$s',
			get_permalink(),
			esc_attr( get_the_time() ),
			get_the_date()
		),
		sprintf( '<a class="url fn n" href="%1$s" title="%2$s">%3$s</a>',
			get_author_posts_url( get_the_author_meta( 'ID' ) ),
			sprintf( esc_attr__( 'View all posts by %s', 'twentyten' ), get_the_author() ),
			get_the_author()
		)
	);
}
endif;

if ( ! function_exists( 'pure_posted_in' ) ) :
/**
 * Prints HTML with meta information for the current post (category, tags and permalink).
 *
 * @since Twenty Ten 1.0
 */
function pure_posted_in() {
	// Retrieves tag list of current post, separated by commas.
	$tag_list = get_the_tag_list( '', ', ' );
	if ( $tag_list ) {
		$posted_in = __( 'This article was posted in %1$s and tagged %2$s.', 'twentyten' );
	} elseif ( is_object_in_taxonomy( get_post_type(), 'category' ) ) {
		$posted_in = __( 'This entry was posted in %1$s. ', 'twentyten' );
	}
	// Prints the string, replacing the placeholders.
	printf(
		$posted_in,
		get_the_category_list( ', ' ),
		$tag_list,
		get_permalink(),
		the_title_attribute( 'echo=0' )
	);
}
endif;

/**
* Add Contact Form shortcodes
*/
function form_func( $atts ){
	$form = '<div class="contact_form"><form id="contactForm">';
		$form .= '<div id="form-error"></div>';
		$form .= '<div class="form-entry"><input type="text" name="name" value="Name*" onfocus="if(this.value==\'Name*\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'Name*\';"></div>';	
		$form .= '<div class="form-entry"><input type="text" name="phone" value="Phone" onfocus="if(this.value==\'Phone\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'Phone\';"></div>';	
		$form .= '<div class="form-entry"><input type="text" name="email_address" value="Email*" onfocus="if(this.value==\'Email*\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'Email*\';"></div>';	
		$form .= '<div class="form-entry"><span class="css-select-moz"><select name="subject">
			<option value="" class="default">Subject*</option>
			<option>I\'d like to see</option>
			<option>I\'d like to submit content</option>
			<option>I\'d like to get involved</option>
			<option>I found a bug</option>
			<option>I want to know more</option>
			<option>I have a concern</option>
			<option>Technical help</option>
		</select></span></div>';	
		$form .= '<div class="form-entry full"><textarea name="message" rows="7" onfocus="if(this.value==\'Message*\')this.value=\'\';" onblur="if(this.value==\'\')this.value=\'Message*\';">Message*</textarea></div>';	
		$form .= '<div class="captcha"><div class="img"><img src="'.get_bloginfo('template_directory').'/captcha.php?date='.date('YmdHis').'" /></div><label>Prove you are a Game-Changer and not a robot. <br>Enter the code from the image on the left.</label><input type="text" name="captcha"></div>';
		$form .= '<a href="javascript:void(0)" id="submitContact" class="btn1"><span>Submit</span></a>';
	$form .= '</form></div>';

	return $form;
}
add_shortcode( 'contact-form', 'form_func' );

function the_slug() {
    global $post;
    $slug = '';
    
    if (isset($post->ID)) {
        $post_data = get_post($post->ID, ARRAY_A);
        $slug = $post_data['post_name'];
    }

    return $slug;
}

/**
* Customize admin tabs
*/
function change_post_menu_label() {
    global $menu;
    global $submenu;
    $menu[5][0] = 'Community';
    $submenu['edit.php'][5][0] = 'Community Posts';
    $submenu['edit.php'][10][0] = 'Add Post';
    echo '';
}

function change_post_object_label() {
    global $wp_post_types;
    $labels = &$wp_post_types['post']->labels;
    $labels->name = 'Blog';
    $labels->singular_name = 'Posts';
    $labels->add_new = 'Add Post';
    $labels->add_new_item = 'Add Post';
    $labels->edit_item = 'Edit Posts';
    $labels->new_item = 'Blog';
    $labels->view_item = 'View Post';
    $labels->search_items = 'Search Posts';
    $labels->not_found = 'No Posts found';
    $labels->not_found_in_trash = 'No Post found in Trash';
}
add_action( 'init', 'change_post_object_label' );
add_action( 'admin_menu', 'change_post_menu_label' );
/*add_action( 'admin_init', 'remove_links_tab_menu_pages' );

function remove_links_tab_menu_pages() {
	remove_menu_page('link-manager.php');
	remove_menu_page('tools.php');
}*/
add_action('init', 'strategies_register');

function strategies_register() {

	$labels = array(
		'name' => _x('Strategies', 'post type general name'),
		'singular_name' => _x('book_type', 'post type singular name'),
		'add_new' => _x('Add New Book', 'portfolio item'),
		'add_new_item' => __('Add New Book'),
		'edit_item' => __('Edit Book'),
		'new_item' => __('New Book'),
		'view_item' => __('View Book'),
		'search_items' => __('Search Books'),
		'not_found' =>  __('Nothing found'),
		'not_found_in_trash' => __('Nothing found in Trash'),
		'parent_item_colon' => ''
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => true,
		//'menu_icon' => get_stylesheet_directory_uri() . '/article16.png',
		'rewrite' => true,
		'capability_type' => 'post',
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array('title','editor','thumbnail')
	  );

	register_post_type( 'strategies' , $args );
}

add_action('init', 'myStartSession', 1);

function myStartSession() {
    if(!session_id()) {
        session_start();
    }
}

add_action( 'show_user_profile', 'my_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'my_show_extra_profile_fields' );

function my_show_extra_profile_fields( $user ) { ?>

	<h3>Blog Subscriber</h3>

	<table class="form-table">

		<tr>
			<th><label for="blog_subscriber">Blog subscriber</label></th>

			<td>
				<input disabled="true" type="text" name="blog_subscriber" id="blog_subscriber" value="<?php echo esc_attr( get_the_author_meta( 'blog_subscriber', $user->ID ) ); ?>" class="regular-text" /><br />
				<span class="description">1=monthly subscriber, 2=yearly subscriber</span>
			</td>
		</tr>

	</table>
<?php }

//THIS IS TRIGGERED ON PERSONAL OPTIONS UPDATE CALLED
add_action( 'personal_options_update', 'my_save_extra_profile_fields' );

//ALSO CALL IT WHEN THE USER PROFILE UPDATE FUNCTIN IS CALLED
add_action( 'edit_user_profile_update', 'my_save_extra_profile_fields' );


function my_save_extra_profile_fields( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

	/* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */
	update_usermeta( $user_id, 'blog_subscriber', $_POST['blog_subscriber'] );
}


//portal
add_action( 'show_user_profile', 'my_show_portal_field' );
add_action( 'edit_user_profile', 'my_show_portal_field' );

function my_show_portal_field( $user ) { ?>

	<h3>Portal</h3>

	<table class="form-table">

		<tr>
			<th><label for="portal">Portal</label></th>

			<td>
				<input type="text" name="portal" id="portal" value="<?php echo esc_attr( get_the_author_meta( 'portal', $user->ID ) ); ?>" class="regular-text" /><br />
				<span class="description">what portal the user came through</span>
			</td>
		</tr>

	</table>
<?php }

add_action( 'personal_options_update', 'my_save_portal_field' );
add_action( 'edit_user_profile_update', 'my_save_portal_field' );

function my_save_portal_field( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

	/* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */
	update_usermeta( $user_id, 'portal', $_POST['portal'] );
}

function my_login_logo() { ?>
    <style type="text/css">
        body.login div#login h1 a {
            background-image: url(<?php echo get_bloginfo( 'template_directory' ) ?>/images/logo.png);
            height: 180px;
            background-size: auto!important;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'my_login_logo' );

/**
 * Returns all the orders made by the user
 *
 * @param int $user_id
 * @param string $status (completed|processing|canceled|on-hold etc)
 * @return array of order ids
 */
function get_all_user_orders($user_id,$status='completed'){
    if(!$user_id)
        return false;
    
    $orders=array();//order ids
     
    $args = array(
        'numberposts'     => -1,
        'meta_key'        => '_customer_user',
        'meta_value'      => $user_id,
        'post_type'       => 'shop_order',
        'post_status'     => 'publish',
        'tax_query'=>array(
                array(
                    'taxonomy'  =>'shop_order_status',
                    'field'     => 'slug',
                    'terms'     =>$status
                    )
        )  
    );
    
    $posts=get_posts($args);
    //get the post ids as order ids
    $orders=wp_list_pluck( $posts, 'ID' );
    
    return $orders;
 
}

/**
 * Get all Products Successfully Ordered by the user
 *
 * @global type $wpdb
 * @param int $user_id
 * @return bool|array false if no products otherwise array of product ids
 */
function get_all_products_ordered_by_user($user_id=false,$status='completed'){
 
 $orders=get_all_user_orders($user_id,$status);
 if(empty($orders))
   return false;
 
 $order_list='('.join(',', $orders).')';//let us make a list for query
 
 //so we have all the orders made by this user which was successfull
 
 //we need to find the products in these order and make sure they are downloadable
 
 // find all products in these order
 
 global $wpdb;
 $query_select_order_items="SELECT order_item_id as id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id IN {$order_list}";
 
 $query_select_product_ids="SELECT meta_value as product_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key=%s AND order_item_id IN ($query_select_order_items)";
 
 $products=$wpdb->get_col($wpdb->prepare($query_select_product_ids,'_product_id'));
 
 return $products;
}

/**
 * has the user bought the product
 * @param type $user_id
 * @param type $product_id
 * @return boolean
 */
function has_user_bought($user_id,$product_id){
 $ordered_products=get_all_products_ordered_by_user($user_id);
 
 if(in_array($product_id, (array)$ordered_products))
   return true;
 return false;
 
}


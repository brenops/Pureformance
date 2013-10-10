<?php
/*
Plugin Name: WP Audio Player
Plugin URI: http://tommcfarlin.com/wp-audio-player/
Description: An easy way to embed an audio file in your posts using the responsive and touch-friendly audio player by Codrops.
Version: 1.9.1
Author: Tom McFarlin
Author URI: http://tommcfarlin.com/
Author Email: tom@tommcfarlin.com
License:

	Copyright 2013 Tom McFarlin (tom@tommcfarlin.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

if( ! class_exists( 'Mobile_Detect' ) ) {
	include_once( 'lib/Mobile_Detect.class.php' );
} // end if

if( ! defined( 'WP_AUDIO_PLAYER_VERSION' ) ) {
	define( 'WP_AUDIO_PLAYER_VERSION', '1.9.1' );
} // end if

class WP_Audio_Player {

	/*--------------------------------------------*
	 * Attributes
	 *--------------------------------------------*/

	private $audio_player_nonce = 'wp_audio_player_nonce';

	private $detect;

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	public function __construct() {

		$this->detect = new Mobile_Detect();

		// Load plugin text domain
		add_action( 'init', array( $this, 'plugin_textdomain' ) );

		// Add the HTML5 Shiv
		add_action( 'wp_enqueue_scripts', array( $this, 'include_html5_shiv' ) );

		// Register site styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
		add_action( 'admin_print_styles', array( $this, 'register_admin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_scripts' ) );

		// Setup the metaboxes and the save post functionality
		add_action( 'add_meta_boxes', array( $this, 'display_audio_url' ), 10 );
		add_action( 'save_post', array( $this, 'save_audio_url' ) );

		// Append the player to the end of the post
		add_filter( 'the_content', array( $this, 'display_audio_content' ) );

		// add ie conditional html5 shim to header
function add_ie_html5_shim () {
    echo '<!--[if lt IE 9]>';
    echo '<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>';
    echo '<![endif]-->';
}
add_action('wp_head', 'add_ie_html5_shim');

	} // end constructor

	/**
	 * Loads the plugin text domain for translation
	 */
	public function plugin_textdomain() {
		load_plugin_textdomain( 'wp-audio-player', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
	} // end plugin_textdomain

	/**
	 * Registers and enqueues plugin-specific styles.
	 */
	public function register_plugin_styles() {

		wp_enqueue_style( 'wp-audio-player', plugins_url( 'wp-audio-player/css/audioplayer.css' ) );
		wp_enqueue_style( 'wp-audio-player-theme', plugins_url( 'wp-audio-player/css/plugin.css' ) );

	} // end register_plugin_styles

	/**
	 * Registers and enqueues admin-specific scripts.
	 */
	public function register_admin_scripts() {
		wp_enqueue_script( 'wp-audio-player-meta', plugins_url( 'wp-audio-player/js/admin.min.js' ) );
	} // end register_admin_scripts

	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {
		wp_enqueue_style( 'wp-audio-player-meta', plugins_url( 'wp-audio-player/css/admin.css' ) );
	} // end register_admin_styles

	/**
	 * Registers and enqueues plugin-specific scripts.
	 */
	public function register_plugin_scripts() {

		wp_enqueue_script( 'wp-audio-player', plugins_url( 'wp-audio-player/js/audioplayer.min.js' ), array( 'jquery' ), WP_AUDIO_PLAYER_VERSION, false );
		wp_enqueue_script( 'wp-audio-player-plugin', plugins_url( 'wp-audio-player/js/plugin.min.js' ), array( 'wp-audio-player' ), WP_AUDIO_PLAYER_VERSION, false );

	} // end register_plugin_scripts

	public function include_html5_shiv() {
		if( $this->user_is_using_ie8() ) {
			wp_enqueue_script( 'wp-audio-player-html5shiv', plugins_url( 'wp-audio-player/js/html5shiv.min.js' ), null, WP_AUDIO_PLAYER_VERSION, false );
		} // end if
	} // end include_html5_shiv

	/*--------------------------------------------*
	 * Core Functions
	 *--------------------------------------------*/

	/**
	 * Adds a meta box to the post edit screen.
	 */
	public function display_audio_url() {

		// First, get all of the post types in the theme
		$args = array(
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_in_nav_menus'   => true
		);

		// Next, build up the string used to represent the post types
		foreach( get_post_types( $args ) as $post_type ) {
			$this->add_meta_box( $post_type );
		} // end foreach

		// And finally add support for pages
		$this->add_meta_box( 'page' );

	} // end display_audio_url

	/**
	 * Renders the input field in which to paste the URL of the audio file.
	 *
	 * @param   object   $post   The post to which this meta box is associated.
	 */
	public function display_audio_url_input( $post ) {

		wp_nonce_field( plugin_basename( __FILE__ ), $this->audio_player_nonce );

		$html  = '<p class="description">';
			$html .= __( 'Place the URL to your audio file here.', 'wp-audio-player' );
		$html .= '</p>';
		$html .= '<input type="text" id="wp_audio_url" name="wp_audio_url" value="' . esc_url( get_post_meta( $post->ID, 'wp_audio_url', true ) ) . '" />';

		// If there has MP3's in the Media Library, give them that option.
		if( $this->has_mp3_files() ) {

			$html  .= '<p class="description">';
				$html .= __( 'Or select an MP3 from your media library.', 'wp-audio-player' );
			$html .= '</p>';
			$html .= '<select id="wp-audio-player-media" name="wp-audio-player-media" multiple>';

				/* Build up the list of MP3 files
				 *
				 * Note that for some reason, using the traditional `while( have_posts() )` in the admin
				 * was causing problems with post excerpts. Honestly, I'm unsure as to *why*; however,
				 * doing a `foreach` and checking the object's type and title property allows this to work
				 * just as well without interferring with other meta data and post types.
				 *
				 * This code is a result of a bug reported here:
				 * http://wordpress.org/support/topic/blog-excerpts-on-sidebar-are-all-the-same?replies=11#post-3862451
				 *
				 * 17 February 2012
				 */
				$mp3_query = $this->get_mp3_files();
				foreach( $mp3_query->posts as $mp3_post ) {

					$html .= '<option value="' . $mp3_post->guid . '" ' . selected( $mp3_post->guid, esc_url( get_post_meta( $mp3_post->ID, 'wp_audio_url', true ) ), false ) . '>' . $mp3_post->post_title . '</option>';

				} // end if

			$html .= '</select><!-- /#wp-audio-player-media -->';

		} // end if

		// Firefox notice
		$html .= '<div id="wp-audio-player-notice">';
			$html .= __( "<strong>Heads up!</strong> This browser doesn't support WP Audio Player, so it will be using the basic player.", 'wp-audio-player' );
		$html .= '</div>';

		echo $html;

	} // display_audio_url

	/**
	 * Saves the post data for the Audio URL to post defined by the incoming ID.
	 *
	 * @param   string   $post_id   The ID of the post to which we're saving the post data.
	 */
	public function save_audio_url( $post_id ) {

		// Make sure the user can save the meta data
		if( $this->user_can_save( $post_id, $this->audio_player_nonce ) ) {

			// Read the post URL
			$wp_audio_url = '';
			if( isset( $_POST['wp_audio_url'] ) ) {
				$wp_audio_url = strip_tags( stripslashes( $_POST['wp_audio_url'] ) );
			} // end if

			// If the value exists, delete it first. I don't want to write extra rows into the table.
			if ( 0 == count( get_post_meta( $post_id, 'wp_audio_url' ) ) ) {
				delete_post_meta( $post_id, 'wp_audio_url' );
			} // end if

			// Update it for this post.
			update_post_meta( $post_id, 'wp_audio_url', $wp_audio_url );

		} // end if/else

	} // end save_audio_url

	/**
	 * Appends the audio player to the end of the content, if it's been defined for the given post.
	 *
	 * @param   string   $content   The post content to which we're appending the player.
	 * @return  string              The content with the player at the bottom of the content.
	 *
	 * @version	1.3
	 * @since	1.4
	 */
	public function display_audio_content( $content ) {

		// We really only want to do this if we're on the single post page
		if( ( is_single() || is_page() ) && ! post_password_required() ) {

			// Append the audio URL ot the content, if it's defined.
			$audio_url = get_post_meta( get_the_ID(), 'wp_audio_url', true );
			if( 0 != strlen( $audio_url ) ) {

				// Firefox doesn't support MP3's. Sad story. Give them an option to use the embed.
				if( $this->detect->isMobile() || $this->user_is_using_firefox() ) {

					$audio_html = '<div class="wp-audio-player-firefox">';
						$audio_html .= '<embed src="' . esc_url ( $audio_url ) . '" autostart="false" />';
					$audio_html .= '</div>';

				// If they're use IE8, we need to use a completely different version of the audio embed element
				} else if ( $this->user_is_using_ie8() ) {

					$audio_html = '<div class="wp-audio-player-ie8">';
						$audio_html .= '<embed src="' . esc_url ( $audio_url ) . '" autostart="false" />';
					$audio_html .= '</div><!-- /.wp-audio-player-ie8 -->';

				// Otherwise, we are good to go with the fancy-schmancy player so let's do it!
				} else {

					// Actually write out the meta data
					$audio_html = '<audio preload="auto" controls src="' . esc_url ( $audio_url ) . '" class="wp-audio-player"></audio>';

					// Add the meta data to the plugin
					$audio_html .= '<div class="wp-audio-player-meta">';
						$audio_html .= '<span class="wp-audio-player-length"></span>';
						$audio_html .= '<span class="wp-audio-player-start"></span>';
						$audio_html .= '<span class="wp-audio-player-end"></span>';
					$audio_html .= '</div><!-- /.wp-audio-player-meta -->';

				} // end if/else

				$content .= $audio_html;

			} // end if

		} // end if

		return $content;

	} // end display_audio_content

	/*--------------------------------------------*
	 * Private Functions
	 *--------------------------------------------*/

	/**
	 * Adds a 'Feature Audio' meta box to the specified post type.
	 *
	 * @param   string   $post_type   The post type to which we're adding the meta box.
	 */
	private function add_meta_box( $post_type ) {

		add_meta_box(
			'wp_audio_url',
			__( 'Featured Audio', 'wp-audio-player' ),
			array( $this, 'display_audio_url_input' ),
			$post_type,
			'side',
			'low'
		);

	} // end add_meta_box

	/**
	 * Determines whether or not the current user has the ability to save meta data associated with this post.
	 *
	 * @param		int		$post_id	The ID of the post being save
	 * @param		bool				Whether or not the user has the ability to save this post.
	 * @version		1.0
	 * @since		1.4
	 */
	private function user_can_save( $post_id, $nonce ) {

	    $is_autosave = wp_is_post_autosave( $post_id );
	    $is_revision = wp_is_post_revision( $post_id );
	    $is_valid_nonce = ( isset( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], plugin_basename( __FILE__ ) ) ) ? true : false;

	    // Return true if the user is able to save; otherwise, false.
	    return ! ( $is_autosave || $is_revision ) && $is_valid_nonce;

	} // end user_can_save

	/**
	 * Determines whether or not the user is using Firefox to view the page
	 *
	 * @return		True if the user is using Firefox; false, otherwise.
	 * @version		1.0
	 * @since		1.4
	 */
	private function user_is_using_firefox() {
		return false != stristr( $_SERVER['HTTP_USER_AGENT'], 'firefox' );
	} // end user_is_using_firefox

	/**
	 * Determines whether or not the user is using IE8 to view the page
	 *
	 * @return		True if the user is using Firefox; false, otherwise.
	 * @version		1.0
	 * @since		1.9
	 */
	private function user_is_using_ie8() {
		return false != stristr( $_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0' );
	} // user_is_using_ie8

	/**
	 * Creates an array of all of the media uploads the user has.
	 *
	 * @return		The array of MP3's in the user's media library
	 * @version		1.0
	 * @since		1.4
	 */
	private function get_mp3_files() {

		$args = array(
			'post_type'			=>	'attachment',
			'post_mime_type'	=>	'audio/mpeg',
			'post_status'		=>	'inherit'
		);
		$mp3_query = new WP_Query( $args );

		return $mp3_query;

	} // end get_mp3_files

	/**
	 * Determines if there are any files stored in the database.
	 *
	 * @return		True if there are MP3's in the media library; false, otherwise.
	 * @version		1.1
	 * @since		1.4
	 */
	private function has_mp3_files() {

		$mp3_query = $this->get_mp3_files();
		$mp3_count = $mp3_query->found_posts;

		return 0 < $mp3_count;

	} // end has_mp3_files

} // end class

$GLOBALS['wp-audio-player'] = new WP_Audio_Player();
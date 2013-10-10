<?php
/*
Plugin Name: Audio
Plugin URI: http://scotty-t.com
Description: HTML5 Audio (on supported browsers), Flash fallback, CSS-skin'd player, hAudio Micro-formats, attach images to MP3s (when used with Shuffle)
Author: Scott Taylor
Author URI: http://scotty-t.com
Version: 0.6.1
License: GPLv2 or later
*/
define( 'A_THUMB_WIDTH',    50 );
define( 'A_THUMB_HEIGHT',   50 );

class Audio_WP_Plugin {
	function init() {
		add_action( 'init', 			array( $this, 'shortcodes' ) );
		add_action( 'wp_print_scripts', array( $this, 'scripts'  ) );
		add_action( 'wp_print_styles', 	array( $this, 'styles'  ) );
	}

	function shortcodes() {
		add_shortcode( 'audio', 		array( $this, 'shortcode' ) );
	}

	function has_audio() {
		return $this->the_audio( get_the_ID(), false );
	}

	function enclosure( $mime, $url, $song ) {
		printf( '<a type="%s" rel="enclosure" data-src="%s">"%s"</a>', $mime, base64_encode( $url ), $song );
		echo "\n";
	}

	function shortcode( $atts, $content = null ) {
		ob_start();
		$this->the_audio();
        return ob_get_clean();
	}

	function styles() {
		if ( !is_admin() ) {
			$local = STYLESHEETPATH . '/audio.css';

			if ( is_file( $local ) ) {
				wp_enqueue_style( 'audio-override', get_stylesheet_directory_uri() . '/audio.css' );
			} else {
				wp_enqueue_style( 'audio', WP_PLUGIN_URL . '/audio/css/audio.css' );
			}
		}
	}

	function scripts() {
		if ( !is_admin() ) {
			wp_enqueue_script( 'base64',    WP_PLUGIN_URL . '/audio/js/base64.js', '', '', true );
			wp_enqueue_script( 'jplayer',   WP_PLUGIN_URL . '/audio/js/jquery.jplayer.min.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'audio',     WP_PLUGIN_URL . '/audio/js/audio.js', array( 'jplayer', 'base64' ), '', true );
		}
	}

	function get_attachment_post_id( $parent_id, $mime_type = 'audio/mp3' ) {
		global $wpdb;
		$search = is_array( $mime_type ) ? "IN ('%s')" : '= %s';
		$sql = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_mime_type {$search} AND post_status = 'publish'",
			$parent_id,
			is_array( $mime_type ) ? join( "','", $mime_type ) : $mime_type
		);
		return $wpdb->get_var( $sql );
	}

	function ogg_object( $mp3_id ) {
		$ogg_id = $this->get_attachment_post_id( $mp3_id, 'audio/ogg' );
		if ( !empty( $ogg_id ) )
			return get_post( $ogg_id );
	}

	function image_id( $mp3_id ) {
		return $this->get_attachment_post_id( $mp3_id, array( 'image/jpg', 'image/gif', 'image/png' ) );
	}

	function image( $mp3_id, $title ) {
		$image = $this->image_id( $mp3_id );
		if ( !empty( $image ) ) {
			$meta = wp_get_attachment_image_src( $image, array( A_THUMB_WIDTH, A_THUMB_HEIGHT ), true );
			if ( !empty( $meta ) ) 
				printf(
					'<img class="photo" src="%s" width="%d" height="%d" alt="%s" />',
					$meta[0], 
					$meta[1], 
					$meta[2], 
					apply_filters( 'the_title_attribute', $title ) 
				);
		}
	}

	function item_formatted( $mp3 ) {
		$song = apply_filters( 'the_title', $mp3->post_title );
		$artist = $mp3->post_excerpt;
		$album = $mp3->post_content;

		$url = wp_get_attachment_url( $mp3->ID );

		?>
	<div class="haudio">
		<?php $this->image( $mp3->ID, $song ); ?>
		<span class="fn"><?php echo $song ?></span>
		<span class="contributor"><span class="vcard"><span class="fn org"><?php echo $artist ?></span></span></span>
		<span class="album"><?php echo $album ?></span>
		<?php
		$this->enclosure( $mp3->post_mime_type, $url, $song );

		$ogg = $this->ogg_object( $mp3->ID );
		if ( !empty( $ogg ) ) {
			$url = wp_get_attachment_url( $ogg->ID );
			$song = apply_filters( 'the_title', $ogg->post_title );
			$this->enclosure( $ogg->post_mime_type, $url, $song );
		}
		?>
	</div>
	<?php
	}

	function the_audio( $id = 0, $display = true ) {
		$the_id = 0 === $id ? get_the_ID() : $id;

		if ( !empty( $the_id ) ):
			$audio = get_posts( array(
				'post_parent'	=> $the_id,
				'post_mime_type'=> 'audio/mpeg',
				'order'         => 'ASC',
				'orderby'	 	=> 'menu_order',
				'post_type'   	=> 'attachment',
				'post_status' 	=> 'inherit',
				'numberposts' 	=> -1
			) );

			if ( !empty( $audio ) ):
				if ( $display ):
			?>
				<div class="audio-playlist">
				<?php 
                foreach ( $audio as $a ) 
                    $this->item_formatted( $a ); 
                ?>
				</div>
			<?php	
				endif;
                
                return $audio;
			endif;
		endif;
	}
}
$_audio_wp_plugin = new Audio_WP_Plugin();
$_audio_wp_plugin->init();

function has_audio() {
	global $_audio_wp_plugin;
	return $_audio_wp_plugin->has_audio();
}

function the_audio( $id = 0, $display = true ) {
	global $_audio_wp_plugin;
	return $_audio_wp_plugin->the_audio( $id, $display );
}
(function($) {
	"use strict";

	$(function() {
	
		// Go ahead and hide the player while we wait for it to load
		$('audio').hide();

		// Now actually setup the information necessary to work with the styled Audio Player
		$('audio').bind( 'loadeddata', function() {
			
			// Set the length, the start, and the end based on the loaded meta data.
			$( '.wp-audio-player-length' ).text( this.duration );
			$( '.wp-audio-player-start' ).text( this.buffered.start( 0 ) );	
			$( '.wp-audio-player-end' ).text( this.buffered.end( 0 ) );

			// Kick off the plugin
			$('audio').audioPlayer();
			
		});

	});
	
}(jQuery));
(function($) {
	"use strict";
	
	$(function() {
		
		// If the user is using Firefox, notify them in the admin
		if( $.browser.mozilla ) {
			$('#wp-audio-player-notice').show();
		} // end if
		
		// When the user clicks a file from the multiselect, add it to the input
		$('#wp-audio-player-media').click(function() {

			if( null !== $(this).val() && '' !== $.trim( $(this).val() ) ) {
				$('input[type="text"]#wp_audio_url').val( $(this).val() );
			} // end if
			
		});
		
	});
	
}(jQuery));
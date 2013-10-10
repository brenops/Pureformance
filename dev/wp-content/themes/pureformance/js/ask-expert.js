$(document).ready(function(){		
	
	$('#submitQuestion').click(function () {	
		
		//Get the data from all the fields
		var name = $('input[name=name]');
		var email = $('input[name=email_address]');
		var subject = $('input[name=subject]');
		var message = $('textarea[name=message]');
		
		
		//organize the data properly
		var data = 'name=' + name.val() + '&email=' + email.val() + '&subject=' + subject.val() + '&message='  + encodeURIComponent(message.val());
		
		//show the loading sign
		//$('.loading').show();
		
		
		//start the ajax
		$.ajax({
			url: "http://pureformance.com/dev/wp-content/themes/pureformance/submitQuestion.php",	
			type: "GET",		
			data: data,		
			cache: false,
			
			success: function (html) {				
				//if process.php returned 1/true (send mail success)
				if (html==1) {		
					$(':input','#contactForm').not(':button, :submit, :reset, :hidden').val('');							
					$('#form-error').html('Your message was successfully sent.');
					$('#form-error').fadeIn('slow');
					setTimeout(function() { $('#form-error').fadeOut('slow') }, 5000);
					
				//if process.php returned 0/false (send mail failed)
				} else {
					$('#form-error').html(html);
					$('#form-error').fadeIn('slow');
				}			
			}		
		});
		return false;
	});
});
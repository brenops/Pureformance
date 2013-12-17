if( /iPad/i.test(navigator.platform) ) { is_ipad = true; }else{ is_ipad=false; }
$(document).ready(function(){
	// Home Page Portals
	
	if(is_ipad==false) {
		$('.portals div a').hover(function(){
			$(this).children('.black').fadeOut(400);
			$(this).children('.color').fadeIn(400);
		}, function() {
			$(this).children('.black').fadeIn(400);
			$(this).children('.color').fadeOut(400);
		});
	}
	
	//Menu hint
	setTimeout(function() { 
	  	$('#showside').stop().animate({width:'95px'},{queue:false,duration:100});
	  	setTimeout(function() { 
	  		$('#showside').stop().animate({width:'90px'},{queue:false,duration:100});
	  	}, 100);
	}, 3000);
	
	setTimeout(function() { mycode(); }, 3000);
	function mycode() {
	    $('#showside').stop().animate({width:'95px'},{queue:false,duration:200});
	  	setTimeout(function() { 
	  		$('#showside').stop().animate({width:'90px'},{queue:false,duration:200});
	  	}, 200);
	  	setTimeout(function() { 
	  		$('#showside').stop().animate({width:'95px'},{queue:false,duration:200});
	  	}, 400);
	  	setTimeout(function() { 
	  		$('#showside').stop().animate({width:'90px'},{queue:false,duration:200});
	  	}, 600);
	    setTimeout(function() { mycode(); }, 10000);
	}	
	
});
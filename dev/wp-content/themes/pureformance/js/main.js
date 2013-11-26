$(document).ready(function(){

	resizeHeight();
	$(window).bind('resize', function(event) {
		resizeHeight();
	});

	// Menu Description
	/*$('.menu a').hover(function(){
		$(this).stop().animate({height:'50px'},{queue:false,duration:400});
		$(this).children('span').stop().animate({'margin-top':'2px'},{queue:false,duration:200});
	}, function() {
		$(this).stop().animate({height:'19px'},{queue:false,duration:400});
		$(this).children('span').stop().animate({'margin-top':'6px'},{queue:false,duration:200});
	});*/

	//Show hide side menu
	$('#showside').click(function(){
		$('#side').stop().animate({left:'0px'},{queue:false,duration:1200});
		$('#side').addClass('active');
	});
	$('#hideside').click(function(){
		$('#side').stop().animate({left:'-374px'},{queue:false,duration:1200});
		$('#side').removeClass('active');
	});
	$('#side').mouseleave(function(){
		if($('#side').hasClass('active')) {
			$('#side').stop().animate({left:'-374px'},{queue:false,duration:1200});
		}
	});

	//Hover effect on products
	$('.product-list.odd').hover(function(){
		$(this).children('.over').stop().animate({right:'0px'},{queue:false,duration:300});
	}, function() {
		$(this).children('.over').stop().animate({right:'-150px'},{queue:false,duration:300});
	});

	$('.product-list.even').hover(function(){
		$(this).children('.over').stop().animate({left:'0px'},{queue:false,duration:300});
	}, function() {
		$(this).children('.over').stop().animate({left:'-150px'},{queue:false,duration:300});
	});

	$('.book-list').hover(function(){
		$(this).children('.over').stop().animate({right:'0px'},{queue:false,duration:300});
	}, function() {
		$(this).children('.over').stop().animate({right:'-150px'},{queue:false,duration:300});
	});

	//Products Tabs
	$('.tabs li a').click(function(){
		$('.tabs li').removeClass('active');
		$(this).parent().addClass('active');
		var currentTab = $(this).attr('href');
		$('.tabs div').hide();
		$(currentTab).show();
		return false;
	});

	//Bottom boxes products
	$('.nav-icons li').hover(function(){
		$(this).children('.over').stop().animate({bottom:'0px'},{queue:false,duration:300});
	}, function() {
		$(this).children('.over').stop().animate({bottom:'-210px'},{queue:false,duration:300});
	});

	//Blog thumbs
	$('#blog .post .thumb a').hover(function(){
		$(this).children('img').stop().animate({width:'360px'},{queue:false,duration:300});
		$(this).children('img').animate({height:'241px'},{queue:false,duration:300});
		$(this).children('img').animate({'margin-left':'-12px'},{queue:false,duration:300});
	}, function() {
		$(this).children('img').stop().animate({width:'333px'},{queue:false,duration:300});
		$(this).children('img').animate({height:'223px'},{queue:false,duration:300});
		$(this).children('img').animate({'margin-left':'0'},{queue:false,duration:300});
	});

	//Experts
	$('.experts h4').click(function(){
		if($('.experts').hasClass('open')) {
			$('.experts .info').fadeOut('slow', function() {
				$('.experts').removeClass('open');
			});
		} else {
			$('.experts').addClass('open');
			$('.experts .info').fadeIn('slow');
		}
	});

	/*DROPDOWN NAVIGATION*/
	$('ul.site-top-links').superfish({
		delay:400,
		autoArrows:false
	});
	
	$('#members-only-trigger').fancybox({
		'centerOnScroll' : true
	});
	$('#ask-expert').fancybox({
		'centerOnScroll' : true
	});

	$('#signup').fancybox({
		'scrolling'		: 'no', 
		'titleShow'		: false,
		'centerOnScroll' : true, 
		'onClosed'		: function() {
		    $("#login_error").hide();
		}
	});
	
	var navigation = responsiveNav("#mobile-nav", {
        animate: true,        // Boolean: Use CSS3 transitions, true or false
        transition: 400,      // Integer: Speed of the transition, in milliseconds
        label: "Menu",        // String: Label for the navigation toggle
        insert: "after",      // String: Insert the toggle before or after the navigation
        customToggle: "",     // Selector: Specify the ID of a custom toggle
        openPos: "relative",  // String: Position of the opened nav, relative or static
        jsClass: "js",        // String: 'JS enabled' class which is added to <html> el
        init: function(){},   // Function: Init callback
        open: function(){},   // Function: Open callback
        close: function(){}   // Function: Close callback
    });
});

// FUNCTION TO RESIZE THE HOME PAGE BACKGROUND
function resizeHeight()
{
	var windowHeight = $(window).height();

	var height = windowHeight-107+'px';

	$('#content').css('min-height',height);
}
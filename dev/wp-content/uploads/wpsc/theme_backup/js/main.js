$(document).ready(function(){
	
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
		$('#side').stop().animate({left:'0px'},{queue:false,duration:500});
		$('#side').addClass('active');
	});
	$('#hideside').click(function(){
		$('#side').stop().animate({left:'-374px'},{queue:false,duration:500});
		$('#side').removeClass('active');
	});
	$('#side').mouseleave(function(){
		if($('#side').hasClass('active')) {
			$('#side').stop().animate({left:'-374px'},{queue:false,duration:500});
		}
	});
	
	//Hover effect on products
	$('.products.odd').hover(function(){
		$(this).children('.over').stop().animate({right:'0px'},{queue:false,duration:300});
	}, function() {
		$(this).children('.over').stop().animate({right:'-150px'},{queue:false,duration:300});
	});
	
	$('.products.even').hover(function(){
		$(this).children('.over').stop().animate({left:'0px'},{queue:false,duration:300});
	}, function() {
		$(this).children('.over').stop().animate({left:'-150px'},{queue:false,duration:300});
	});
	
	//Sign in pop up
	/*$('#header li.signin').hover(function(){
		$(this).children('.popup').stop().animate({top:'42px'},{queue:false,duration:300});
		$(this).children('.popup').fadeIn('400');
	}, function() {
		$(this).children('.popup').stop().animate({top:'60px'},{queue:false,duration:300});
		$(this).children('.popup').fadeOut('400');
	});*/
	
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
});
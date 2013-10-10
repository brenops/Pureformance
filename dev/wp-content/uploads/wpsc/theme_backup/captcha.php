<?php
	session_start();
	unset($_SESSION["image_random_value"]);
	$alphaNumeric = "0123456789";
	$random = substr(str_shuffle($alphaNumeric), 0, 5);
	$image = imagecreatefromjpeg("images/captcha.jpg");
	$textColor = imagecolorallocate($image, 0,0,0);
	imagestring($image, 5, 25, 15, $random, $textColor);
	$_SESSION['image_random_value'] = md5($random);
	
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Content-type: image/jpeg");
	imagejpeg($image);
	imagedestroy($image);
?>
<?php
session_start();

//Retrieve form data. 
//GET - user submitted data using AJAX
//POST - in case user does not support javascript, we'll use POST instead
$name = ($_GET['name']) ? $_GET['name'] : $_POST['name'];
$phone = ($_GET['phone']) ?$_GET['phone'] : $_POST['phone'];
$email = ($_GET['email']) ?$_GET['email'] : $_POST['email'];
$subject = ($_GET['subject']) ?$_GET['subject'] : $_POST['subject'];
$comment = ($_GET['message']) ?$_GET['message'] : $_POST['message'];
$captcha = ($_GET['captcha']) ?$_GET['captcha'] : $_POST['captcha'];

//flag to indicate which method it uses. If POST set it to 1
if ($_POST) $post=1;

//Simple server side validation for POST data, of course, you should validate the email
if (!$name || $name == 'Name:') $errors[count($errors)] = 'Please enter your name.';
if (!$email || $email == 'Email:') $errors[count($errors)] = 'Please enter your email.'; 
if (!$subject) $errors[count($errors)] = 'Please select a subject.'; 
if (md5($captcha) != $_SESSION['image_random_value']) $errors[count($errors)] = 'Code does not match. '; 

//if the errors array is empty, send the mail
if (!$errors) {

	//recipient
	$to = 'jonathan@f5interactive.com';	
	//sender
	$from = $name . ' <' . $email . '>';
	
	//subject and the html message
	$subject = 'Contact from Pureformance.com';	
	$message = '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head></head>
	<body>
	<table>
		<tr><td>Name</td><td>' . $name . '</td></tr>
		<tr><td>Phone</td><td>' . $phone . '</td></tr>
		<tr><td>Email</td><td>' . $email . '</td></tr>
		<tr><td>Subject</td><td>' . $subject . '</td></tr>
		<tr><td>Comment</td><td>' . nl2br($comment) . '</td></tr>
	</table>
	</body>
	</html>';

	//send the mail
	$result = sendmail($to, $subject, $message, $from);
	
	//if POST was used, display the message straight away
	if ($_POST) {
		if ($result) echo 'Thank you! We have received your message.';
		else echo 'Sorry, unexpected error. Please try again later';
		
	//else if GET was used, return the boolean value so that 
	//ajax script can react accordingly
	//1 means success, 0 means failed
	} else {
		echo $result;	
	}

//if the errors array has values
} else {
	//display the errors message
	for ($i=0; $i<count($errors); $i++) echo $errors[$i] . '<br/>';
	exit;
}


//Simple mail function with HTML header
function sendmail($to, $subject, $message, $from) {
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
	$headers .= 'From: ' . $from . "\r\n";
	
	$result = mail($to,$subject,$message,$headers);
	
	if ($result) return 1;
	else return 0;
}

?>
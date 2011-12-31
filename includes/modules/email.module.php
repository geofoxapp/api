<?php

####################################################################################################
#
# GEOFOX GROUP CONFIDENTIAL
# Unpublished Copyright (c) 2010 GEOFOX GROUP, All Rights Reserved.
# 
# Viewing and/or usage of this source code indicates acceptance of the full license terms as 
# outlined in the index.php file.
#
####################################################################################################

// Here, and forever more, a tab is 4 spaces, as God intended.

function email_send($to,$subject,$body) {
	$headers = "From: no-reply@geofoxapp.com\r\n"."X-Mailer: php";
	$subject = "GEOfox App - ".$subject;
	$body = "This is an automated message. Please do not reply.\n\n".$body;
	$body .= "\n\nThanks,\nThe GEOfox App Development Team\nhttp://www.geofoxapp.com";
	$body .= "\n\nThanks for your continued patronage! Questions, comments, or suggestions can be directed to help@geofoxapp.com.";
	return mail($to,$subject,$body,$headers);
	return true;
}

###################################################################################################
# CANNED RESPONSES
###################################################################################################

function email_db_reset() {
	$subject = "NOTICE: Database has been reset!";
	$content = "Please be aware that the server database has just been reset. All auth_keys are now invalid.";
	email_send("geofox-admin@test.com",$subject,$content); 
}

function email_welcome($user_id,$password,$email) {
	$user = user_details($user_id);
	$subject = "Welcome!";
	$content = 	$user["fname"].",\n\n".
				"Welcome to the GEOfox App community!\n\n".
				"Your account has been created and you can begin using the application right now.\n\n".
				"Here's your account information:\n".
				"Login: ".$user["email"]."\n".
				"Password: $password \n\n";
	//email_send($email,$subject,$content); 
}

function email_send_code($user_id,$email,$code) {
	global $settings;
	$subject = "Please confirm your email address";
	$content = "You must confirm your email address before your account becomes fully active.\n\n".
			   $settings["path"]."/special/email_confirm.php?user_id=$user_id&code=$code";
	//email_send($email,$subject,$content);
}

function email_confirm_verification($user_id) {
	$subject = "Your email address has been confirmed";
	$content = "Your email address has been confirmed and you now have full access to the GEOfox features.";
	email_send($email,$subject,$content);
}

function email_password_reset($email,$password) {
	$subject = "A password reset has been requested";
	$content = "We have received a request to have your password reset.\n\n".
			   "Your new password is: $password";
	email_send($email,$subject,$content);
}

?>
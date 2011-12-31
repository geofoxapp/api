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

include_once("user.module.php");

// create or update an notification template
function notification_update($templates,$text) {
	
}

// get all details for a template
function notitication_details($template) {
	
}

// remove an existing template
function notification_remove($template) {
	
}

// send a notification to a user
function notification_send($template,$user_id) {
	
}

// get template from DB, fill in blanks, and return finished string
function notification_prepare($template,$user_id) {
	$template = notifications_details($template);
	// check if active
	if ( $template["active"] == true ) {
		$text = $template["text"];
		// get user
		$user = user_details($user_id);
		// fill in blanks
		$text = str_replace("{{user_name}}",$user["fname"]." ".$user["lname"],$text);
		$text = str_replace("{{user_email}}",$user["email"],$text);
		$text = str_replace("{{user_phone}}",$user["phone"],$text);
		// more
		
		// return finished string
		return $text;
	}
	return false;
}


?>
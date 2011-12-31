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

require_once realpath(dirname( __FILE__ )."/"."../settings.php" );
require_once realpath(dirname( __FILE__ )."/"."../includes/exception.class.php" );
require_once realpath(dirname( __FILE__ )."/"."../includes/modules/database.module.php" );
require_once realpath(dirname( __FILE__ )."/"."../includes/modules/user.module.php" );
require_once realpath(dirname( __FILE__ )."/"."../includes/modules/email.module.php" );
require_once realpath(dirname( __FILE__ )."/"."../includes/modules/helpers.module.php" );

global $settings;

// connect to database
try {
	$db = new GEOFOX_DATABASE($settings["db"]["hostname"],$settings["db"]["username"],$settings["db"]["password"],$settings["db"]["database"]);
} catch(chain_exception $e) {
	echo "Unable to connect to database server. Please try again later.";
	exit();
}

// get input parameters
if ( !isset($_REQUEST["code"]) ) {
	echo "Required parameter code is not set. Please double check the link you received.";
	exit();
}
if ( !isset($_REQUEST["user_id"]) ) {
	echo "Required parameter user_id is not set. Please double check the link you received.";
	exit();
}
$code = sanitize($_REQUEST["code"],"string");
$user_id = sanitize($_REQUEST["user_id"],"int");

// confirm email address
try {
	if ( email_confirm($code) ) {
		echo "Your email address has been confirmed.";
		exit();
	}
	else {
		echo "Sorry, we were unable to confirm your email address. A new code has been sent to the email address on file.";
		email_verify($user_id,null);
		exit();
	}
} catch (chain_exception $e) {
	echo "Unable to complete request. Please try again later.";
	exit();
}


?>
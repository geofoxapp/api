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
if ( !isset($_REQUEST["email"]) ) {
	echo "Required parameter email is not set.";
	exit();
}
$email = sanitize($_REQUEST["email"],"string");

// request new password
try {
	if (password_reset($email)) {
		echo "Your password has been reset. You will receive a new password at your registered email address momentarily.";
		exit();
	}
} catch (chain_exception $e) {
	echo "Unable to complete request. Please try again later.";
	exit();
}


?>
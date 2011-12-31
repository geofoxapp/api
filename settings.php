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

$settings = array();

####################################################################################################
# SERVICE SETTINGS
####################################################################################################

$settings["path"] = "http://dev.api.geofoxapp.com";

// DATABASE CONNECTION DETAILS
$settings["db"] = array(
	"type" => "mysql",
	"hostname" => "db.geofoxapp.com",
	"username" => "geofox",
	"password" => "ti1MFfa!",
	"database" => "geofox",
	"backup_path" => "/home/geofox/web/api/backups/"
);

// DATA ENCRYPTION DETAILS
$settings["encrypt_salt"] =					// can not be changed once set!
	"";				

// SERVICE STATUS DETAILS
$settings["debug"] =						// should full errors be shown?
	true;						
$settings["online"] = 						// is the service available for use?
	true;						
$settings["reset_db"] =						// DROP AND RESET DB TABLES
	false;									// CAREFUL!

// SERVICE DEFAULTS
$settings["output_mode"] =					// default output data format
	"json";						
$settings["timeout"] =						// how many seconds before auth keys expire
	2592000;					
$settings["key_length"] = 					// how long to make auth keys
	10;							
$settings["confirm_length"] =				// how long to make email keys
	8;							
$settings["confirm_expire"] =				// how many seconds before email keys expire
	604800;							
	
// SECURITY SETTINGS
$settings["allow_http"] = 					// allow unencrypted API usage?
	true;
$settings["allow_unsigned_devs"] = 			// allow usage by non-signed developers?
	true;
	
// DEFAULT CATEGORIES
$settings["categories"] = array(
	"restaurants",
	"bars",
	"coffee",
	"drugstores",
	"banks",
	"servicestations",
	"fitness",
	"hotels",
	"arts"
);

####################################################################################################
# THIRD PARTY SERVICE SETTINGS
####################################################################################################	
	
// GOOGLE PLACES

// nata; lazy bastards	
	
// FACEBOOK

$settings["facebook"] = array(
	"app_name" => "GEOfox",
	"app_id" => "",
	"app_secret" => "",
	"api_key" => ""
);
	
// TWITTER
$settings["twitter"] = array(
	"api_key" => "",
	"consumer_key" => "",
	"consumer_secret" => "",
	"request_token_url" => "https://api.twitter.com/oauth/request_token",
	"access_token_url" => "https://api.twitter.com/oauth/access_token",
	"authorize_url" => "https://api.twitter.com/oauth/authorize"
);
	
// YAHOO
$settings["yahoo"] = array(
	"api_key" => "",
	"shared_secret" => "",
	"app_id" => ""
);
	
// YELP
$settings["yelp"] = array(
	"ywsid" => "",
	"consumer_key" => "",
	"consumer_secret" => "",
	"token" => "",
	"token_secret" => "",
);

####################################################################################################
# STATUS STRINGS
####################################################################################################

$settings["status"] = array(

	// SUCCESS STATUS (0000-0099)
	// "no problems here"
	0 => "Success. No errors encountered.",
	// NOTICE STATUS (0100-0199)
	// "no problems, but there's something you should know"
	100 => "I was supposed to tell you something, but I forgot.",
	101 => "Service is offline for maintenance. Please try again later.",
	// SERVICE ERROR STATUS (0200-0299)
	// "it's my fault"
	200 => "An unknown service error has occurred.",
	201 => "An internal database error has occurred.",
	// INPUT ERROR STATUS (0300-0399)
	// "it's your fault"
	300 => "An unknown input error has occurred.",
	301 => "Missing a required parameter. Check your syntax.",
	302 => "Problem with one or more parameters. Check your data.",
	303 => "Invalid or missing authentication code. You need to run login again.",
	304 => "Invalid or missing developer code. Please contact support.",
	305 => "Invalid action. Check the documentation.",
	306 => "You're not allowed to perform this action.",
	307 => "Access is denied. Your use of this service has been restricted. Please contact support.",
	308 => "Bad password email combination.",
	// THIRD PARTY ERROR STATUS (0400-0499)
	// "sorry, ___'s API isn't working and I don't know why"
	400 => "An unknown third party error has occurred."
	
);

####################################################################################################
# DEVELOPER KEYS
####################################################################################################

$settings["dev_keys"] = array(

	"random_developer_key" => true // grant access to developer with this key

);

####################################################################################################
# BLACKLIST
####################################################################################################

$settings["blacklist"] = array(
	
	"0.0.0.0",
	"127.0.0.1"
	
);

####################################################################################################
# LOGGING 
####################################################################################################

// show full errors during debug conditions
error_reporting(E_ALL ^ E_NOTICE);
if ( $settings["debug"] ) ini_set('display_errors',1);
else ini_set('display_errors',0);


?>
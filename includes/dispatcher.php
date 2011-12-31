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
	
$db = null;	
$remote_user = null;
	
// import modules
require_once("exception.class.php");
require_once("modules/database.module.php");
require_once("modules/helpers.module.php");
require_once("modules/email.module.php");	
require_once("modules/user.module.php");
require_once("modules/notifications.module.php");
require_once("modules/places.module.php");
require_once("modules/checkin.module.php");
require_once("modules/recommendations.module.php");
	
function service_init() {
	global $settings, $db, $remote_user;
	$remote_user = null;
	// import settings and connect to database
	try {
		$db = new GEOFOX_DATABASE($settings["db"]["hostname"],$settings["db"]["username"],$settings["db"]["password"],$settings["db"]["database"]);
	} catch(chain_exception $e) {
		throw new chain_exception("Unable to initialize service.",201,$e);
	}
}

function service_reset() {
	global $db;
	try {
		$db->reset_tables();
		return true;
	} catch(chain_exception $e) {
		throw new chain_exception("Unable to reset database.",201,$e);
	}
}

function service_end() {
	global $db;
	$db->close();
}
	
function service_dispatcher($data) {
	global $remote_user, $db;
	// get requested action
	$action = sanitize($data["action"],"string");
	
	###############################################################################################
	# RUN REQUESTED ACTION
	# Important! All unhandled exceptions will generate an 'Internal Server Error" response.
	# All errors that are the fault of the client should be handled internally before reaching
	# this level. Action implimentations should load their own modules. [require_once()]
	###############################################################################################
	
	try {
	
		###########################################################################################
		# PUBLIC ACTIONS
		###########################################################################################
		// LOGIN
		// @params: (string) email, (string) password
		if ( $action == "login" ) {
				$user_id = authenticate(sanitize($data["email"],"string"),sanitize($data["password"],"string"));
				return generate_key($user_id);
		}
		// USER_CREATE
		// @params: (string) password, (string) fname, (string) lname, (string) email, (string) phone
		else if ( $action == "user_create" ) {
			return user_create(sanitize($data["password"],"string"),sanitize($data["fname"],"string"),sanitize($data["lname"],"string"),sanitize($data["email"],"string"),sanitize($data["phone"],"string"));
		}
		// PASSWORD_RESET
		// @params: (string) email
		else if ( $action == "password_reset" ) {
			return password_reset(sanitize($data["email"],"string"));
		}
		// TEST
		else if ( $action == "welcome" ) {
			return "Hey, this is output from a sample action. It's kind of lonely in here...";
		}
		// DATABASE RESET <-- WARNING: MUST BE REMOVED BEFORE LAUNCH!
		else if ( $action == "resetdb" ) {
			$db->reset();
			email_db_reset();
			load_data();
			return "TABLES HAVE BEEN DROPPED AND DATABASE HAS BEEN RESET!";
		}
		###########################################################################################
		# SECURE ACTIONS
		# Actions should get the current user_id by accessing the global $remote_user
		###########################################################################################	
		else if ( isset($data["auth_key"]) && $remote_user = get_user(sanitize($data["auth_key"],"string") ) ) {
			
			// USER_DETAILS
			// @params: none
			if ( $action == "user_details" ) {
				return user_details($remote_user);
			}
			// USER_UPDATE
			// @params: (string) password, (string) fname, (string) lname, (string) email, (string) phone
			else if ( $action == "user_update" ) {
				return user_update($remote_user,sanitize($data["password"],"string"),sanitize($data["fname"],"string"),sanitize($data["lname"],"string"),sanitize($data["email"],"string"),sanitize($data["phone"],"string"));
			}
			// EMAIL_VERIFY
			// @params: none
			else if ( $action == "email_verify" ) {
				return email_verify($remote_user);
			}
			// EMAIL_CONFIRM
			// @params: (string) code
			else if ( $action == "email_confirm" ) {
				return email_confirm($remote_user,sanitize($data["code"],"string"));
			}
			// PLACE_SEARCH_NEAR
			// @params: (float) lat, (float) lon
			else if ( $action == "place_search_near" ) {
				return place_search_near(sanitize($data["lat"],"float"),sanitize($data["lon"],"float"));
			}
			// PLACE_SEARCH_CATEGORY
			// @params: (float) lat, (float) lon, (string) category
			else if ( $action == "place_search_category" ) {
				return place_search_category(sanitize($data["lat"],"float"),sanitize($data["lon"],"float"),sanitize($data["category"],"string"));
			}
			// PLACE_SEARCH_NEIGHBORHOOD
			// @params: (float) lat, (float) lon
			else if ( $action == "place_search_neighborhood" ) {
				return place_search_neighborhood(sanitize($data["lat"],"float"),sanitize($data["lon"],"float"));
			}
			// PLACE_GET_DETAILS
			// @params: (string) place_id
			else if ( $action == "place_get_details" ) {
				return place_get_details(sanitize($data["place_id"],"string"));
			}
			// PLACE_GET_DETAILS_LIVE
			// @params: (string) place_id
			else if ( $action == "place_get_details_live" ) {
				return place_get_details_live(sanitize($data["place_id"],"string"));
			}
			// PLACE_MORE_LIKE
			// @params: (string) place_id
			else if ( $action == "place_more_like" ) {
				return place_more_like(sanitize($data["place_id"],"string"));
			}
			// PLACE_MORE_NEAR
			// @params: (string) place_id
			else if ( $action == "place_more_near" ) {
				return place_more_near(sanitize($data["place_id"],"string"));
			}
			// PLACE_CATEGORIES_GET_ALL
			// @params: none
			else if ( $action == "place_categories_get_all" ) {
				return place_categories_get_all();
			}
			// PLACE_CATEGORIES_GET_SHORT
			// @params: (string) long
			else if ( $action == "place_categories_get_short" ) {
				return place_categories_get_short(sanitize($data["long"],"string"));
			}
			// PLACE_CATEGORIES_GET_LONG
			// @params: (string) short
			else if ( $action == "place_categories_get_long" ) {
				return place_categories_get_long(sanitize($data["short"],"string"));
			}
			// CHECKIN_ADD
			// @params: (string) place_id, (string) note
			else if ( $action == "checkin_add" ) {
				return checkin_add($remote_user,sanitize($data["place_id"],"string"),sanitize($data["note"],"string"));
			}
			// CHECKIN_REMOVE
			// @params: (int) checkin_id
			else if ( $action == "checkin_remove" ) {
				return checkin_remove($remote_user,sanitize($data["checkin_id"],"int"));
			}
			// CHECKIN_HISTORY
			// @params: none
			else if ( $action == "checkin_history" ) {
				return checkin_history($remote_user);
			}
			// CHECKIN_HISTORY_DATE
			// @params: (int) date_start, (int) date_end
			else if ( $action == "checkin_history_date" ) {
				return checkin_history_date($remote_user,sanitize($data["date_start"],"int"),sanitize($data["date_end"],"int"));
			}
			// REC_GET_ALL
			//@params: none
			else if ( $action == "rec_get_all" ) {
				return rec_get_all($remote_user);
			}
			// NO ACTION PROVIDED
			else if ( $action == "" ) {
				throw new chain_exception("Required parameter was not provided.",301);
			}
			// UNKNOWN ACTION
			else {
				throw new chain_exception("Unknown action.",305);
			}
			
		}
		###########################################################################################
		# BAD AUTHENTICATION KEY
		###########################################################################################
		else {
			throw new chain_exception("Invalid authentication key.",306);
		}
	} catch (Exception $e) {
		// pass exception up to service controller
		throw $e;
	}
	
	return $output;
}
	


?>
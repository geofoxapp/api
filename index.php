<?php

####################################################################################################
#
# GEOFOX API VERSION 0.1 PRE-RELEASE
# 
####################################################################################################
#
# Hereafter, GEOFOX GROUP refers to the developers of the GEOFOX service, applications, and related 
# media as referenced below.
#     Adam Budde (abudde@umich.edu)
#     Russel Dekema (dekemar@umich.edu)
#     Michael Billau (mbillau@umich.edu)
#     Matthew Colf (mcolf@umich.edu)
#     Michael Brown (mikegb@umich.edu)
# 
# GEOFOX GROUP CONFIDENTIAL
# Unpublished Copyright (c) 2010 GEOFOX GROUP, All Rights Reserved.
# 
# NOTICE: All information contained herin is, and remains the property of GEOFOX GROUP. The 
# intellectual and technical concepts contained heroin are proprietary to GEOFOX GROUP and may be 
# covered by U.S. and Foreign Patents, patents in process, and are proteced by trade secret or 
# copyright law. Dissemination of this information or reproduction of this material is strictly
# forbudden unless priot written permission is obtained from all members of GEOFOX GROUP. Access to
# the source code contained herein is herby forbidden to anyone except current GEOFOX GROUP 
# members, managers, or contractors who have executed Confidentiality and Non-disclosure agreements
# explicitly coversing such access. 
#
# The copyright notice above does not evidence any actual or intended publication or disclosure  of  
# this source code, which includes information that is confidential and/or proprietary, and is a 
# trade secret, of the GEOFOX GROUP. ANY REPRODUCTION, MODIFICATION, DISTRIBUTION, PUBLIC 
# PERFORMANCE, OR PUBLIC DISPLAY OF OR THROUGH USE OF THIS SOURCE CODE WITHOUT THE EXPRESS WRITTEN
# CONSENT OF THE GEOFOX GROUP IS STRICTLY PROHIBITED, AND IN VIOLATION OF APPLICABLE LAWS AND 
# INTERNATIONAL TREATIES. THE RECEIPT OR POSSESSION OF THIS SOURCE CODE AND/OR RELATED INFORMATION
# DOES NOT CONVEY OR IMPLY ANY RIGHTS TO REPRODUCE, DISCLOSE OR DISTRIBUTE ITS CONTENTS, OR TO 
# MANUFACTURE, USE, OR SELL ANYTHING THAT IT MAY DESCRIBE, IN WHOLE OR IN PART.
#
####################################################################################################

// Here, and forever more, a tab is 4 spaces, as God intended.

####################################################################################################
# PREPARE SERVICE
####################################################################################################

// load settings & helpers
require_once("settings.php");
require_once("includes/index.helpers.php");
require_once("includes/exception.class.php");
require_once("includes/libraries/xml/xmlLib.php");
global $settings;

// start execution time tracking
$time_start = get_time();

// load default responses
$response = array( "http" => 200, "error" => false, "code" => 0, "message" => "" );

// check service status
if ( $settings["online"] == false ) {
	$response["http"] = 503;
	$response["error"] = true;
	$response["code"] = 101;
	$response["message"] = "Service is offline for maintenance. Please try again later.";
}

// check blacklist
if ( in_array(($_SERVER["REMOTE_ADDR"]),$settings["blacklist"]) ) {
	$response["http"] = 403;
	$response["error"] = true;
	$response["code"] = 307;
	$response["message"] = "Service denied. Remote address has been blacklisted.";
}

####################################################################################################
# PARSE INPUT
# The service supports both GET and POST data encoded in JSON, XML, or plaintext.
####################################################################################################

$raw = file_get_contents("php://input");

// check for JSON payload
$input = json_decode($raw,true);

// check for XML payload
if ( $input == null ) {
	$xml = new XMLToArray( $raw, array(), array( 'story' => '_array_' ), true, false );
	if ( $xml != false ) $input = $xml->getArray();
}

// fall back to GET
if ( $input == false or $input = null ) $input = $_REQUEST;

####################################################################################################
# PERFORM INITIAL CHECKS
####################################################################################################

// check input
if ( !is_array($input) ) {
	$response["error"] = true;
	$response["code"] = 300;
	$response["message"] = "Input data cannot be parsed. Raw input: $input";
}

// check for service directives
if ( isset($input["format"]) ) $settings["output_mode"] = $input["format"];

####################################################################################################
# LOAD & RUN SERVICE
# This section runs once all constraints have been checked, thereby minimizing server load as a
# result of bad or fraudulent input.
####################################################################################################

if ( $response["error"] == false ) {

	// load web service
	require_once("includes/dispatcher.php");
	try {
		service_init();
		// run web service
		$result = service_dispatcher($input);
		// delegate server response
		$response["error"] = false;
		$response["code"] = 0;
		$response["http"] = get_http($response["code"]);
		$response["message"] = get_message($response["code"]);
		$response["result"] = $result;
		// shut down service
		service_end();
	} catch(Exception $e) {
		// an error has occured
		$response["error"] = true;
		$response["code"] = $e->base_code();
		$response["http"] = get_http($response["code"]);
		$response["message"] = get_message($response["code"])." ".$e->base_message();
		if ( $settings["debug"] ) $response["trace"] = $e->fullTrace();
	}
	
}

####################################################################################################
# SHOW SERVER RESPONSE
# This is the only place where output should happen!
####################################################################################################

// compute total execution time
$time_end = get_time();
$response["execution_time"] = $time_end - $time_start;

// set appropriate HTTP status
if ( $response["http"] == 503 ) 			header("HTTP/1.0 503 Service Unavailable");		// try again later
else if ( $response["http"] == 401 )		header("HTTP/1.0 401 Unauthorized");			// auth required
else if ( $response["http"] == 403 ) 		header("HTTP/1.0 403 Forbidden");				// service denied
else if ( $response["http"] == 500 )		header("HTTP/1.0 500 Internal Server Error");	// server error
else if ( $response["http"] == 501 )		header("HTTP/1.0 501 Not Implemented");			// not implimented
else if ( $response["error"] ) 				header("HTTP/1.0 400 Bad Request");				// client error
else 										header("HTTP/1.0 200 OK");						// success

// return server response
if ( $settings["output_mode"] == "json" ) {
	header("Content-type: application/json");
	echo json_encode($response);
}
else if ( $settings["output_mode"] == "xml" ) {
	header ("Content-Type: text/xml"); 
	$xml = new SimpleXMLElement("<output/>");
	echo array_to_xml($output,$xml)->asXML();
}
else {
	header("Content-type: text/html");
	echo "Unknown format type. Showing response in cleartext.<br />";
	echo "<pre>";
	print_r($response);
	echo "</pre>";
}
      

?>

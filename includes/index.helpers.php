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

// return current time as a fload
function get_time() {
	list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

// convert array to xml
function array_to_xml($object,$xml) {
	foreach ($object as $key => $value ) {
		if (is_array($value)) array_to_xml($value,$xml->addChild($key));
		else $xml->addChild($key,$value);
	}
	return $xml;
}

// get status code full text
function get_message($code) {
	global $settings;
	$message = "";
	$keys = array_keys($settings["status"]);
	if ( in_array($code,$keys) ) {
		$status = $settings["status"][$code];
	}
	return $message;
}

// get http return code
function get_http($code) {
	if ( $code < 200 ) return 200; // ok
	if ( $code < 300 ) return 500; // server error
	if ( $code < 400 ) return 400; // input error
	if ( $code < 500 ) return 500; // third party api error
	return 200;
}

?>
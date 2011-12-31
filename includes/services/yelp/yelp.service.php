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

require_once("categories.php");

function yelp_api_v1_parallel_query($requests) {
	global $settings;
	// setup paralell curl request
	$master = curl_multi_init();
	$children = array();
	// setup child requests
	foreach ( $requests as $params ) {
		// prepare parameters
		$params["ywsid"] = $settings["yelp"]["ywsid"];
		$params["output"] = "json";
		// prepare query
		$options = array(
			CURLOPT_URL => "http://api.yelp.com/business_review_search",
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POSTFIELDS => http_build_query($params)
		);
		// prepare child query
		$child = curl_init();
		curl_setopt_array($child,$options);
		// save child queries
		$children[] = $child;
		// push child to master
		curl_multi_add_handle($master,$child);
	}
	// execute requests
	$running = null;
	do {
		curl_multi_exec($master,$running);
		usleep(10000); 
	} while ($running > 0);
	// get results
	$results = array();
	foreach($children as $child) {
		$raw = curl_multi_getcontent($child);
		// parse response
		$data = json_decode($raw,true);
		if ( $data == false or $data == null ) 
			throw new chain_exception("Invalid Yelp API JSON response.",400);
		// check for yelp response status
		if ( $data["message"]["code"] != 0 ) {
			if ( $data["message"]["code"] == 4 ) email_send("geofoxa-admin@test.com","Yelp API limit excedded","Shit, somebody should do something about this.");
			throw new chain_exception("Yelp API error ".$data["message"]["code"].": ".$data["message"]["text"],400);
		}
		// save data
		$results[] = $data;
	}
	// remove handles and close query
	foreach ($children as $child) {
		curl_multi_remove_handle($master,$child);
	}
	curl_multi_close($master);
	// return all results
	return $results;	
}

function yelp_api_v1_query($parameters) {
	global $settings;
	// prepare parameters
	$parameters["ywsid"] = $settings["yelp"]["ywsid"];
	$parameters["output"] = "json";
	// prepare query
	$options = array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_POSTFIELDS => http_build_query($parameters)
	);
	// run query
	$session = curl_init("http://api.yelp.com/business_review_search");
	curl_setopt_array($session,$options);
	$result = curl_exec($session);
	if ( $result === false ) 
		throw new chain_exception("Yelp API unreachable.",1);
	if ( ($code = curl_getinfo($session,CURLINFO_HTTP_CODE)) != 200 ) 
		throw new chain_exception("Yelp server error (".$code.").",400);
	curl_close($session);
	// parse response
	$data = json_decode($result,true);
	if ( $data == false or $data == null ) 
		throw new chain_exception("Invalid Yelp API JSON response.",400);
	// check yelp response status
	if ( $data["message"]["code"] != 0 ) {
		if ( $data["message"]["code"] == 4 ) email_send("geofox-admin@test.com","Yelp API limit excedded","Shit, somebody should do something about this.");
		throw new chain_exception("Yelp API error ".$data["message"]["code"].": ".$data["message"]["text"],400);
	}
	// return data
	return $data;
}

function yelp_search_box($tl_lat,$tl_long,$br_lat,$br_long,$term,$category,$limit) {
	// required
	$parameters = array(
		"tl_lat" => $tl_lat,
		"tl_long" => $tl_long,
		"br_lat" => $br_lat,
		"br_long" => $br_long
	);
	// optional
	if ($term != "") $parameters["term"] = $term;
	if ($category != "") $parameters["category"] = $category;
	if ($limit > 0) $parameters["limit"] = $limit;
	// run query
	try {
		$output = yelp_api_v1_query($parameters);
		return $output;
	} catch ( chain_exception $e ) {
		throw new chain_exception("Error in yelp_search_box.",400,$e);
	}
}

function yelp_search_multi_category_radius($lat,$long,$radius,$term,$categories,$limit) {
	$requests = array();
	foreach ( $categories as $category ) {
		// required
		$request = array(
			"lat" => $lat,
			"long" => $long,
			"category" => $category
		);
		// optional
		if ($radius >= 0) $request["radius"] = $radius;
		if ($term != "") $request["term"] = $term;
		if ($limit > 0) $request["limit"] = $limit;
		// save individual query		
		$requests[] = $request;
	}	
	// run query
	try {
		$output = yelp_api_v1_parallel_query($requests);
		return $output;
	} catch ( chain_exception $e ) {
		throw new chain_exception("Error in yelp_search_multi_category_radius.",400,$e);
	}
}

function yelp_search_radius($lat,$long,$radius,$term,$category,$limit) {
	// required
	$parameters = array(
		"lat" => $lat,
		"long" => $long
	);
	// optional
	if ($radius > 0) $parameters["radius"] = $radius;
	if ($term != "") $parameters["term"] = $term;
	if ($category != "") $parameters["category"] = $category;
	if ($limit > 0) $parameters["limit"] = $limit;
	// run query
	try {
		$output = yelp_api_v1_query($parameters);
		return $output;
	} catch ( chain_exception $e ) {
		throw new chain_exception("Error in yelp_search_radius.",400,$e);
	}
}

function yelp_search_address($location,$radius,$term,$category,$limit,$cc="US") {
	// required
	$parameters = array(
		"location" => $location
	);
	// optional
	if ($radius > 0) $parameters["radius"] = $radius;
	if ($term != "") $parameters["term"] = $term;
	if ($category != "") $parameters["category"] = $category;
	if ($limit > 0) $parameters["limit"] = $limit;
	if ($cc != "US") $parameters["cc"] = $cc;
	// run query
	try {
		$output = yelp_api_v1_query($parameters);
		return $output;
	} catch ( chain_exception $e ) {
		throw new chain_exception("Error in yelp_search_address.",400,$e);
	}	
}

function yelp_search_id($lat,$long,$id,$category,$term) {
	// required
	$parameters = array(
		"lat" => $lat,
		"long" => $long,
		"radius" => 10
	);
	// optional
	if ($category != "") $parameters["category"] = $category;
	if ($term != "") $parameters["term"] = $term;
	// run query
	try {
		$output = yelp_api_v1_query($parameters);
	} catch ( chain_exception $e ) {
		throw new chain_exception("Error in yelp_search_id.",400,$e);
	}
	// check for matching ID
	foreach ( $output["businesses"] as $business ) {
		if ( $business["id"] == $id ) return $business;
	}
	// not found
	throw new chain_exception("Business not found.",302);
}

function yelp_categories_get_all() {
	global $categories;
	return $categories;
}

function yelp_categories_get_long($short) {
	global $categories;
	$keys = array_keys($categories);
	if ( !in_array($short,$keys) ) throw new chain_exception("Category not found",302);
	return $categories[$short];
}

function yelp_categories_get_short($long) {
	global $categories;
	if ( !in_array($long,$categories) ) throw new chain_exception("Category not found",302);
	return array_search($long,$categories);
}

?>
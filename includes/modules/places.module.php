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

require_once realpath(dirname( __FILE__ )."/"."../services/yelp/yelp.service.php" );

function place_search_near($lat,$lon) {
	try {
		// perform search
		$results = yelp_search_radius($lat,$lon,20,"","",25);
		// update database
		foreach($results["businesses"] as $business) {
			place_db_update($business);
		}
		$businesses = $results["businesses"];
		// sort results
		place_sort_distance($businesses);
		place_add_checkin_count_array($businesses);
		// return results
		return $businesses;
	} catch (chain_exception $e) {
		throw new chain_exception("Unable to perform place search.",400,$e);
	}
}

function place_search_category($lat,$lon,$category) {
	try {
		// perform search
		$results = yelp_search_radius($lat,$lon,25,"",$category,25);
		// update database
		foreach($results["businesses"] as $business) {
			place_db_update($business);
		}
		$businesses = $results["businesses"];
		// sort results
		place_sort_distance($businesses);
		place_add_checkin_count_array($businesses);
		// return results
		return $businesses;
	} catch (chain_exception $e) {
		throw new chain_exception("Unable to perform category search.",400,$e);
	}
}

function place_search_multi_category($lat,$lon,$categories) {
	try {
		// perform search
		$results = yelp_search_multi_category_radius($lat,$lon,0,"",$categories,20);
		// update database
		foreach( $results as $result ) {
			foreach($result["businesses"] as $business) {
				place_db_update($business);
			}
		}
		// build result set
		$resultset = array();
		for( $i=0; $i < count($categories); $i++ ) {
			$businesses = $results[$i]["businesses"];
			// sort results
			place_sort_distance($businesses);
			place_add_checkin_count_array($businesses);
			// save results
			$resultset[$categories[$i]] = $businesses;
		}
		return $resultset;
	} catch (chain_exception $e) {
		throw new chain_exception("Unable to perform category search.",400,$e);
	}
}

function place_get_details_live($place_id) {
	try {
		$place = place_db_fetch($place_id);
		$business = yelp_search_id($place["lat"],$place["lon"],$place_id,$place["category1"],$place["name"]);
		place_db_update($business);
		place_add_checkin_count($business);
		return $business;
	} catch ( chain_exception $e ) {
		throw new chain_exception("Unable to retrieve business details.",400,$e);
	}	
}

function place_get_details($place_id) {
	try {
		$place = place_db_fetch($place_id);
		$business_cache = place_cache_fetch($place_id);
		// check if cache is less than 7 days old
		if ( $business_cache["timestamp"] < (time()-(7*24*60*60)) ) {
			// refresh cache and return full results
			$business = yelp_search_id($place["lat"],$place["lon"],$place_id,$place["category1"],$place["name"]);
			place_db_update($business);
			place_add_checkin_count($business);
			$business["cache"] = false;
			return $business;
		}	
		// cache is still valid, return from there
		place_add_checkin_count($business_cache);
		$business_cache["cache"] = true;
		// add categories
		$business_cache["category1"] = $place["category1"];
		$business_cache["category2"] = $place["category2"];
		$business_cache["category3"] = $place["category3"];
		// return final object
		return $business_cache;		
	} catch ( chain_exception $e ) {
		throw new chain_exception("Unable to retrieve business details.",400,$e);
	}		
}

function place_search_neighborhood($lat,$lon) {
	global $settings;
	try {
		return place_search_multi_category($lat,$lon,$settings["categories"]);
	} catch (chain_exception $e) {
		throw new chain_exception("Unable to perform neighhborhood search.",400,$e);
	}
}

function place_more_like($place_id) {
	try {
		// get place categories
		$place = place_db_fetch($place_id);
		$categories = array(
			$place["category1"],
			$place["category2"],
			$place["category3"]
		);
		// get local businesses that fit categories
		$bus_all = place_search_multi_category($place["lat"],$place["lon"],$categories);
		$bus_cat1 = $bus_all[$place["category1"]];
		$bus_cat2 = $bus_all[$place["category2"]];
		$bus_cat3 = $bus_all[$place["category3"]];
		// check to make sure valid data was returned
		if (!is_array($bus_cat1) or !is_array($bus_cat2) or !is_array($bus_cat3) )
			throw new chain_exception("Returned data in place_more_like is invalid.",200);
		// check for 3, 2, or 1 intersect matches
		$int3 = array_intersect($bus_cat1,$bus_cat2,$bus_cat3);
		$int2 = array_unique(array_merge(array_intersect($bus_cat1,$bus_cat2),array_intersect($bus_cat2,$bus_cat3),array_intersect($bus_cat3,$bus_cat1)));
		$int1 = array_diff(array_unique(array_merge($int3,$int3)),array_unique(array_merge($bus_cat1,$bus_cat2,$bus_cat3)));
		// merge results in order of best match
		$bus_sort = array_merge($int3,$int2,$int1);
		// limit to 25 results and remove original place
		$results = array();
		foreach( $bus_sort as $business ) {
			if ( $business["id"] != $place_id ) $results[] = $business;
			if ( sizeof($results) >= 25 ) break;
		}
		return $results;
	} catch ( chain_exception $e ) {
		throw new chain_exception("Unknown error in place_more_like.",200,$e);
	}
}

function place_more_near($place_id) {
	try {
		$place = place_db_fetch($place_id);
		return place_search_near($place["lat"],$place["lon"],5);
	} catch ( chain_exception $e ) {
		throw new chain_exception("Unknown error in place_more_near.",200,$e);
	}
}

####################################################################################################
# CACHE MANAGEMENT
####################################################################################################

function place_db_update($business) {
	global $db;

	// SETUP DATA
	$place_id = $business["id"];
	$lat = $business["latitude"];
	$lon = $business["longitude"];
	$name = $business["name"];
	// parse categories
	$categories = $business["categories"];
	$category1 = $category2 = $category3 = "";
	if ( isset($categories[0]) ) $category1 = $categories[0]["category_filter"];
	if ( isset($categories[1]) ) $category2 = $categories[1]["category_filter"];
	if ( isset($categories[2]) ) $category3 = $categories[2]["category_filter"];
	
	// UPDATE PLACES TABLE
	$sql = "INSERT INTO places (place_id,lat,lon,name,category1,category2,category3) VALUES (?,?,?,?,?,?,?)
			ON DUPLICATE KEY UPDATE lat = ?, lon = ?, name = ?, category1 = ?, category2 = ?, category3 = ?";
	if ( $statement = $db->prepare($sql) ) {
		$statement->bind_param("sddssssddssss",
				$place_id, $lat, $lon, $name, $category1, $category2, $category3, $lat, $lon, $name, $category1, $category2, $category3);
		if ( !$statement->execute() ) 
			throw new chain_exception("SQL Error: ".$statement->error,201);
		$statement->close();
	}
	else throw new chain_exception("Unable to prepare update statement in place_db_update.",201);
	
	// UPDATE PLACE_CACHE TABLE
	$b = $business;
	$timestamp = time();
	$sql = "INSERT INTO place_cache (
				rating_img_url, country_code, id, is_closed, city, mobile_url, review_count, zip, state, latitude, rating_img_url_small, 
				address1, address2, address3, phone, state_code, photo_url, distance, name, url, country, avg_rating, longitude, 
				nearby_url, photo_url_small, timestamp
				) 
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) 
			ON DUPLICATE KEY UPDATE rating_img_url = ?, country_code = ?, is_closed = ?, city = ?, mobile_url = ?, review_count = ?, zip = ?,
				state = ?, latitude = ?, rating_img_url_small = ?, address1 = ?, address2 = ?, address3 = ?, phone = ?, state_code = ?, photo_url = ?,
				distance = ?, name = ?, url = ?, country = ?, avg_rating = ?, longitude = ?, nearby_url = ?, photo_url_small = ?, timestamp = ?";
	if ( $statement = $db->prepare($sql) ) {
		$statement->bind_param("sssissiisdsssssssdsssddssississiisdsssssssdsssddssi",
				$b["rating_img_url"], $b["country_code"], $b["id"], $b["is_closed"], $b["city"], $b["mobile_url"], $b["review_count"], $b["zip"],
				$b["state"], $b["latitude"], $b["rating_img_url_small"], $b["address1"], $b["address2"], $b["address3"], $b["phone"], $b["state_code"],
				$b["photo_url"], $b["distance"], $b["name"], $b["url"], $b["country"], $b["avg_rating"], $b["longitude"], $b["nearby_url"], 
				$b["photo_url_small"], $timestamp, $b["rating_img_url"], $b["country_code"], $b["is_closed"], $b["city"], $b["mobile_url"], 
				$b["review_count"], $b["zip"], $b["state"], $b["latitude"], $b["rating_img_url_small"], $b["address1"], $b["address2"], $b["address3"], 
				$b["phone"], $b["state_code"], $b["photo_url"], $b["distance"], $b["name"], $b["url"], $b["country"], $b["avg_rating"], $b["longitude"], 
				$b["nearby_url"], $b["photo_url_small"], $timestamp);
		if ( !$statement->execute() ) 
			throw new chain_exception("SQL Error: ".$statement->error,201);
		$statement->close();
	}
	else throw new chain_exception("Unable to prepare update statement in place_db_update. ".$db->error,201);
	
}

function place_db_fetch($place_id) {
	global $db;
	if ($statement = $db->prepare("SELECT lat, lon, name, category1, category2, category3 FROM places WHERE place_id = ?")) {
		$statement->bind_param("s",$place_id);
		if ( $statement->execute() ) {
			$business = array();
			$statement->bind_result($business["lat"],$business["lon"],$business["name"],$business["category1"],$business["category2"],$business["category3"]);
			$statement->fetch();
			$statement->close();
			return $business;
		}
		else throw new chain_exception("SQL Error",201);
	}
	else throw new chain_exception("Unable to prepare statement in place_db_fetch.",201);
}

function place_cache_fetch($place_id) {
	global $db;
	$sql = "SELECT rating_img_url,country_code,id,is_closed,city,mobile_url,review_count,zip,state,latitude,rating_img_url_small,address1,address2,address3,
			phone,state_code,photo_url,distance,name,url,country,avg_rating,longitude,nearby_url,photo_url_small,timestamp FROM place_cache WHERE id = ?";
	if ( $statement = $db->prepare($sql) ) {
		$statement->bind_param("s",$place_id);
		if ( $statement->execute() ) {
			$b = array();
			$statement->bind_result($b["rating_img_url"],$b["country_code"],$b["id"],$b["is_closed"],$b["city"],$b["mobile_url"],$b["review_count"],$b["zip"],$b["state"],$b["latitude"],$b["rating_img_url_small"],$b["address1"],$b["address2"],
									$b["address3"],$b["phone"],$b["state_code"],$b["photo_url"],$b["distance"],$b["name"],$b["url"],$b["country"],$b["avg_rating"],$b["longitude"],$b["nearby_url"],$b["photo_url_small"],$b["timestamp"]);
			$statement->fetch();
			$statement->close();
			return $b;
		}
		else throw new chain_exception("SQL Error",201);
	}
	else throw new chain_exception("Unable to prepare statement in place_cache_fetch.",201);
}

####################################################################################################
# CATEGORIES
####################################################################################################

function place_categories_get_all() {
	return yelp_categories_get_all();
}

function place_categories_get_long($short) {
	return yelp_categories_get_long($short);
}

function place_categories_get_short($long) {
	return yelp_categories_get_short($long);
}

####################################################################################################
# UTILITY
####################################################################################################

// add checkin_count to an array of businesses
function place_add_checkin_count_array(&$businesses) {
	for ( $i=0; $i < sizeof($businesses); $i++ ) {
		$businesses[$i]["cache"] = false;
		place_add_checkin_count($businesses[$i]);
	}
}

// add checkin_count to a business
function place_add_checkin_count(&$business) {
	global $remote_user;
	$place_id = $business["id"];
	$business["checkin_count"] = checkin_count($remote_user,$place_id);
}

// sort an array of places by distance to the origin
function place_sort_distance(&$businesses) {
	usort($businesses,"place_compare");
}

// distance sort callback
function place_compare($a,$b) {
	if ($a["distance"] == $b["distance"]) return 0;
	return ($a["distance"] < $b["distance"]) ? -1 : 1;
}



?>
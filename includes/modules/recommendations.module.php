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

function rec_get_all($user_id) {
	global $db;
	$sql = "SELECT place_id, rank FROM recommendations WHERE user_id = ? ORDER BY rank DESC";
	if ( $statement = $db->prepare($sql) ) {
		$statement->bind_param("i",$user_id);
		$businesses = array();
		if ( $statement->execute() ) {
			// get list of recommended places
			$places = array();
			$statement->bind_result($place_id,$rank);
			while ( $statement->fetch() ) {
				$place = array("id" => $place_id, "rank" => $rank);
				$places[] = $place;
			}
			$statement->close();
			// get details for each place
			foreach( $places as $place ) {
				try {
					$business = place_get_details($place["id"]);
					$business["rank"] = $place["rank"];
					$businesses[] = $business;
				} catch (chain_exception $e) {
					throw new chain_exception("Unable get recommended places.",200);
				}
			}
		}	
		return $businesses;
	}
	else throw new chain_exception("Unable to compile SQL statement in checkin_count.",201);
}

?>
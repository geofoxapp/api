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

function checkin_add($user_id,$place_id,$note) {
	if ( !$user_id or !$place_id ) 
		throw new chain_exception("Missing place_id.",302);		
	// insert new checkin entry
	global $db;
	if ( $statement = $db->prepare("INSERT INTO checkins(place_id,user_id,note,timestamp) VALUES(?,?,?,?)") ) {
		$timestamp = time();
		$statement->bind_param("sisi",$place_id,$user_id,$note,$timestamp);
		if ( $statement->execute() ) {
			$checkin_id = $statement->insert_id;
			$statement->close();
			return $checkin_id;
		}
		else throw new chain_exception("SQL error: ".$db->error,201);
	}
	else throw new chain_exception("Unable to compile SQL statement in checkin_add.",201);
}

function checkin_remove($user_id,$checkin_id) {
	if ( !$user_id or !$checkin_id ) 
		throw new chain_exception("Missing checkin_id.",302);
	// get a copy of the checkin entry
	global $db;
	if ( $statement = $db->prepare("SELECT user_id FROM checkins WHERE id = ?") ) {
		$statement->bind_param("i",$checkin_id);
		if ( $statement->execute() ) {
			$statement->bind_result($owner_id);
			$statement->fetch();
			$statement->close();
			// check for ownership
			if ( $user_id != $owner_id ) 
				throw new chain_exception("You can only remove your own checkin entry.",306);
		}
		else throw new chain_exception("SQL error: ".$db->error,201);
	}
	else throw new chain_exception("Unable to compile SQL statement in checkin_remove.",201);
	// remove checkin entry
	if ( $statement = $db->prepare("DELETE FROM checkins WHERE id = ? AND user_id = ?") ) {
		$statement->bind_param("ii",$checkin_id,$user_id);
		if ( $statement->execute() ) {
			$statement->close();
			return true;
		}
		else throw new chain_exception("Unable to remove checkin entry.",201);
	}
	else throw new chain_exception("Unable to compile SQL statement in checkin_remove.",201);
}

// get number of checkin at a place_id for a given user_id
function checkin_count($user_id,$place_id) {
	global $db;
	$sql = "SELECT COUNT(*) FROM checkins WHERE user_id = ? AND place_id = ?";
	if ( $statement = $db->prepare($sql) ) {
		$statement->bind_param("is",$user_id,$place_id);
		$count = 0;
		if ( $statement->execute() ) {
			$statement->bind_result($count);
			$statement->fetch();
		}	
		$statement->close();
		return $count;
	}
	else throw new chain_exception("Unable to compile SQL statement in checkin_count.",201);
}

####################################################################################################
# HISTORY
####################################################################################################

function checkin_history($user_id) {
	try {
		$timestamp = time();
		return checkin_history_date($user_id,0,$timestamp);
	} catch (chain_exception $e) {
		throw $e;
	}
}

function checkin_history_date($user_id,$date_start,$date_end) {
	global $db;
	// check params
	if ( $date_start < 0 or $date_end < $date_start or $date_end > time() )
		throw new chain_exception("Bad date_start or date_end.",302);
	$sql = "SELECT c.id, c.place_id, c.note, c.timestamp, p.name FROM checkins AS c, places AS p 
			WHERE c.user_id = ? AND c.place_id = p.place_id AND c.timestamp > ? AND c.timestamp < ? 
			ORDER BY c.timestamp DESC";
	if ( $statement = $db->prepare($sql) ) {
		$statement->bind_param("iii",$user_id,$date_start,$date_end);
		if ( $statement->execute() ) {
			$all = array();
			$statement->bind_result($checkin_id,$place_id,$note,$timestamp,$name);
			while ( $statement->fetch() ) {
				$entry = array(
					"checkin_id" => $checkin_id,
					"note" => $note,
					"timestamp" => $timestamp,
					"place_id" => $place_id,
					"place_name" => $name
				);
				$all[$checkin_id] = $entry;
			}
			$statement->close();
			// add additional information to each checkin
			foreach ( $all as $checkin ) {
				try {
					$business = place_get_details($checkin["place_id"]);
				} catch (chain_exception $e) {
					throw new chain_exception("Unable to get place information.",200,$e);
				}
				$all[$checkin["checkin_id"]]["business"] = $business;
				$all[$checkin["checkin_id"]]["checkin_count"] = $business["checkin_count"];
			}
			return $all;
		}
		else throw new chain_exception("Unable to get checkin history.",201);
	}
	else throw new chain_exception("Unable to compile SQL statement in checkin_history.",201);	
}

?>
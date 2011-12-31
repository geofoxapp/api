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

// convert an auth_code into a user_id
function get_user($auth_key) {
	global $db;
	// expire old authentication keys
	expire_keys();
	// lookup authentication key
	if ( $statement = $db->prepare("SELECT user_id FROM sessions WHERE auth_key = ?") ) {
		$statement->bind_param("s",$auth_key);
		if ( $statement->execute() ) {
			$statement->bind_result($user_id);
			$statement->fetch();
			$statement->close();
			return $user_id;
		}
		else throw new chain_exception("SQL error: ".$db->error,201);
	} else throw new chain_exception("Error compiling SQL statment in get_user",201);
}

// check if email and password match
function authenticate($email,$password) {
	global $db;
	if ( $email == "" or $password == "" ) 
		throw new chain_exception("Email cannot be blank.",308);
	// lookup password for comparison
	if ( $statement = $db->prepare("SELECT user_id, password FROM users WHERE email = ?") ) {
		$statement->bind_param("s",$email);
		if ( $statement->execute() ) {
			$statement->bind_result($user_id,$db_password);
			$statement->fetch();
			$statement->close();
			// check password
			if ( encrypt($password) == $db_password ) return $user_id;
			else throw new chain_exception("Password does not match.",308);
		}
		else throw new chain_exception("SQL error: ".$db->error,201);
	} else throw new chain_exception("Error compiling SQL statement in authenticate.",201);
}

// create a new user
function user_create($password,$fname,$lname,$email,$phone) {
	// check for required fields
	if ( !$password or !$fname or !$lname or !$email )
		throw new chain_exception("Missing password, fname, lname, or email.",302);
	// clean up user data
	$email = ($email) ? $email : "";
	$phone = phone_clean($phone);
	$password_clear = $password;
	$password = encrypt($password);
	// insert new user entry
	global $db;
	if ( $statement = $db->prepare("INSERT INTO users (password,fname,lname,phone,created) VALUES (?,?,?,?,?)")) {
		$timestamp = time();
		$statement->bind_param("ssssi",$password,$fname,$lname,$phone,$timestamp);
		if ( $statement->execute() ) {
			$user_id = $statement->insert_id;
			$statement->close();
			// set user's email address
			email_set($user_id,$email);
			// send welcome email
			email_welcome($user_id,$password_clear,$email);
			return $user_id;
		}
		else throw new chain_exception("SQL error: ".$db->error,201);
	} else throw new chain_exception("Error compiling SQL statement in user_create.",201);
}

// get all details for a user
function user_details($user_id) {
	global $db;
	// get user details
	if ( $statement = $db->prepare("SELECT user_id, fname, lname, email, phone, email_valid FROM users WHERE user_id = ?")) {
		$statement->bind_param("i",$user_id);
		if ( $statement->execute() ) {
			$u = array();
			$statement->bind_result($d["user_id"],$d["fname"],$d["lname"],$d["email"],$d["phone"],$d["email_valid"]);
			$statement->fetch();
			$statement->close();
			return $d;
		}
		else throw new chain_exception("SQL error: ".$db->error,201);
	} else throw new chain_exception("Error compiling SQL statement in user_create.",201);
}

// update all user details
function user_update($user_id,$password,$fname,$lname,$email,$phone) {
	global $db;
	// get user details
	$user = user_details($user_id);
	// update user details
	$statement = null;
	if ( strlen($password) < 1) {
		// no password update
		if ( $statement = $db->prepare("UPDATE users SET fname = ?, lname = ?, phone = ? WHERE user_id = ?")) {
			$statement->bind_param("sss",$fname,$lname,$phone);
		} else throw new chain_exception("Error compiling SQL statement 1 in user_update.",201);
	}
	else {
		// encrypt password
		$password = encrypt($password);
		if ( $statement = $db->prepare("UPDATE users SET fname = ?, lname = ?, phone = ?, password = ? WHERE user_id = ?")) {
			$statement->bind_param("ssss",$fname,$lname,$phone,$password);
		} else throw new chain_exception("Error compiling SQL statement 2 in user_update.",201);
	}
	if ( $statement->execute() ) {
		$statement->close();
		// update user's email address
		email_set($user_id,$email);
		return true;
	}
	throw new chain_exception("SQL error: ".$db->error,201);
}

// set a user's email address
function email_set($user_id,$email) {
	global $db;
	// get old email address
	if ( $statement = $db->prepare("SELECT email FROM users WHERE user_id = ?")) {
		$statement->bind_param("i",$user_id);
		if ( $statement->execute() ) {
			$statement->bind_result($old_email);
			$statement->fetch();
			$statement->close();
			// compare addresses
			if ( $email != $old_email ) {
				if ( $email != "" ) {
					// check validity
					if ( valid_email($email) ) {
						email_verify($user_id,$email);
					}
					else throw new chain_exception("Email address is not valid.",302);
				}
				// insert new address
				$statement = $db->prepare("UPDATE users SET email = ?, email_valid = FALSE WHERE user_id = ?");
				$statement->bind_param("si",$email,$user_id);
				if ( $statement->execute() ) {
					$statement->close();
					return true;
				}
				else throw new chain_exception("SQL error: ".$db->error,201);
			}
		}
		return false;
	} else throw new chain_exception("Error compiling SQL statement in email_set.",201);
}

// verify a user's email address (send code)
function email_verify($user_id,$email = null) {
	global $db, $settings;
	// remove all previous confirmation keys for this user
	if ( $statement = $db->prepare("DELETE FROM confirmation WHERE user_id = ?")) {
		$statement->bind_param("i",$user_id);
		$statement->execute();
		$statement->close();
		// generate a new confirmation code (loop for duplicates)
		$statement = $db->prepare("INSERT INTO confirmation (code,user_id,expiration) VALUES (?,?,?)");
		$new_code = "";
		do {
			$new_code = random_string($settings["confirm_length"]);
			$expire = time()+$settings["confirm_expire"];
			$statement->bind_param("sii",$new_code,$user_id,$expire);
		} while ( !$statement->execute() );
		// get email address, if needed
		if ( $email == null ) {
			$user = user_details($user_id);
			$email = $user["email"];
		}
		// send code to email address
		email_send_code($user_id,$email,$new_code);
		return true;
	} else throw new chain_exception("Error compiling SQL statement in email_verify.",201);
}

// confirm a user's email address (enter code)
function email_confirm($code) {
	global $db;
	if ( $statement = $db->prepare("SELECT expiration, user_id FROM confirmation WHERE code = ?")) {
		$statement->bind_param("s",$code);
		if ( $statement->execute() ) {
			$success = false;
			$statement->bind_result($expiration,$user_id);
			$statement->fetch();
			$statement->close();
			// check expiration date
			if ( time() < $expiration ) {
				// mark email as valid
				$statement = $db->prepare("UPDATE users SET email_valid = TRUE WHERE user_id = ?");
				$statement->bind_param("i",$user_id);
				$statement->execute();
				$statement->close();
				$success = true;
				email_confirm_verification($user_id);
			}
			// delete code
			$statement = $db->prepare("DELETE FROM confirmation WHERE code = ? AND user_id = ?");
			$statement->bind_param("si",$code,$user_id);
			$statement->execute();
			$statement->close();
			return $success;
		}
		throw new chain_exception("SQL error: ".$db->error,201);
	} else throw new chain_exception("Error compiling SQL statement in user_confirm.",201);
}

// reset a user's password and email it to them
function password_reset($email) {
	global $db;
	// create a random password
	$pass_raw = random_string(10);
	$pass_crypt = encrypt($pass_raw);
	// update user entry
	if ( $statement = $db->prepare("UPDATE users SET password = ? WHERE email = ?")) {
		$statement->bind_param("ss",$pass_crypt,$email);
		if ( $statement->execute() ) {
			$statement->close();
			// email new password to user
			email_password_reset($email,$pass_raw);
			return true;
		}
		throw new chain_exception("SQL error: ".$db->error,201);
	} else throw new chain_exception("Error compiling SQL statement in password_reset.",201);
}





?>